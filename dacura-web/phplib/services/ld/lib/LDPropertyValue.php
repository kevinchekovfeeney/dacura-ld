<?php
/**
 * Class representing the value of a linked data object property
 * 
 * Because we support property embedding and blank nodes, the object part of the subject:predicate:object can be complex. 
 * 
 * This class is used to analyse an object property and answer questions about its structure. 
 * 
 * Property values cannot mix literal types with structured types
 * literals can be typed or simple values
 * 
 * At the highest level, LD property values are either literals, lists of literals or some type of embedded object
 * There are 2 types of literals: 
 * * literal: "v"
 * * objectliteral {type: "string", value: "v"}
 * 
 * And similarly, there are 2 types of literal lists
 * * value list ["v1", "v2",... ]
 * * object literal list[{type: "string", value: "v1"}, ...]
 * 
 * Embedded objects come in four forms:
 * * embedded object list { id: {}, id: {} } - the property value is a list of embedded objects indexed by their ids
 * it maps directly to a set of triples of the form [s, p, id]
 * * embedded object {} - an object nakedly embedded as a property value.  In order to serialise this as a triple, 
 * an id must be generated for the embedded object
 * * object list [{}, {}] - a list of embedded objects
 * 6. complex: anything else - any mixing of object types, embedded and non-embedded, 
 *
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

class LDPropertyValue extends DacuraObject {
	/** @var $json_type the json type of the value representing the json structure: empty, scalar, array, object */
	var $json_type;
	/** @var string the Linked data type of a json scalar: BN (blank node id), CW (Closed World ID), OW (URL), L (Literal)*/
	var $scalar_type;
	/** @var string the linked data type of a json array: literal (an array of object literals), scalar (an array of scalar values), object (an array of objects - objectlist), complex (any mix of types) */
	var $array_type;//
	/** @var string the linked data type of a json object: literal, EO (embedded object), EOL (embedded object list) */
	var $object_type;
	/** @var array an array of the ids within an embedded object categorised as BN, CW, DEL (empty values), UPD (non bn ids)	var $object_id_type;
	/** @var string url of the linked data object that this value belongs to */
	var $cwurl;
	/** @var boolean - set to true if a complex type is encountered. */
	var $invalidld = false; 
	
	function __construct($val, $cwurl = false){
		$this->cwurl = $cwurl;
		$this->load($val);
	}

	/**
	 * Loads the value, parses it and stores its type information in object properties
	 * @param $val the value of the LD property
	 */
	function load($val){
		if(!is_array($val)) {
			$this->json_type = "scalar";
			$this->scalar_type = $this->getScalarType($val);
		}
		else {
			if(count($val) == 0){
				$this->json_type = "empty";
			}
			elseif(isAssoc($val)){
				$this->json_type = "object";
				$this->loadObjectDetails($val);
			}
			else {
				$this->json_type = "array";
				$this->loadArrayDetails($val);
			}
		}
	}
	
	/**
	 * Is a scalar a literal or an id (open world, blank node, closed world)
	 * 
	 * @param $val the literal value
	 * @return string BN|CW|OW|L -> meaning blank-node, closed world (object internal) link, open world link, literal
	 */
	function getScalarType($val){
		if(isBlankNode($val)){
			return "BN";
		}
		elseif($this->isCWLink($val)){
			return "CW";
		}
		elseif($this->isOWLink($val)){
			return "OW";
		}
		else {
			return "L";
		}
	}
	
	/**
	 * Is the passed value a url? 
	 * 
	 * @param $val the literal value
	 * @return boolean true if the passed value is a url or namespaced link and is not a closed world link
	 */
	function isOWLink($val){
		if(isURL($val) or isNamespacedURL($val)){
			return !$this->isCWLink($val);
		}
		return false;
	}
	
	/**
	 * Is the passed value a closed world url (an id of a node internal to the object)? 
	 * 
	 * @param $val the literal value
	 * @return boolean true if the passed value is a closed world url (only in types where $cwurl !== false)
	 */
	function isCWLink($v){
		if(!$this->cwurl) return false;
		$id = substr($this->cwurl, strrpos($this->cwurl, '/') + 1);
		return ((substr($v, 0, 6) == "local:") || (substr($v, 0, strlen($id) + 1) == $id.":") || (substr($v, 0, strlen($this->cwurl)) == $this->cwurl));
	}
	
	
	/**
	 * Reads the value a property that is represented as a json array [a,b,c,...]
	 * 
	 * Analyses the array to identify what types of content it contains
	 * how many scalar values, how many array values, how many object literals and how many objects
	 * @param array $arr the array that is the value of the property
	 * @return array - {scalar: count, array: count, object: count, objectliteral: count}
	 */
	function loadArrayDetails($arr){
		$vtypes = array("scalar" => 0, "array" => 0, "object" => 0, "objectliteral" => 0);//scalar, array, object
		foreach($arr as $wun){
			if(!is_array($wun)){
				$vtypes["scalar"]++;
			}
			elseif(isAssoc($wun)){
				if($this->isObjectLiteral($wun)){
					$vtypes['objectliteral']++;
				}
				else {
					$vtypes['object']++;
				}
			}
			else {
				$vtypes['array']++;
			}
		}
		if(($vtypes['objectliteral'] > 0) && $vtypes['array'] == 0 && $vtypes['object'] == 0 ){
			$this->array_type = 'literal';
		}
		elseif(($vtypes['scalar'] > 0) && $vtypes['array'] == 0 && $vtypes['object'] == 0 ){
			$this->array_type = 'scalar';
		}
		elseif(($vtypes['object'] > 0) && $vtypes['array'] == 0 && $vtypes['scalar'] == 0 && $vtypes['objectliteral'] == 0 ){
			$this->array_type = 'object';
		}
		else {
			$this->array_type = "complex";
			$this->invalidld = true;
		}
		return $vtypes;
	}	

	/**
	 * Loads and parses embedded objects in a property value
	 * 
	 * This function is used to analyse linked data property values whenever their json type is object {}
	 * @param $obj the object as transformed into php directly from the json object that was found in the value 
	 */
	function loadObjectDetails($obj){
		if($this->isObjectLiteral($obj)){
			$this->object_type = "literal";
			return true;
		}
		//big requirement to be able to distinguish between closed world ids and BN ids and predicates -
		//they can't share the same url base
		//this is needed to distinguish embedded object lists and embedded objects.
		$p = 0;//the count of 'properties' - not 
		$ids = array("BN" => array(), "DEL" => array(), "UPD" => array());
		foreach($obj as $id => $val){
			if(is_array($val) && (isBlankNode($id) || $this->isCWLink($id))){
				$ids["BN"][] = $id;
				if(count($val) == 0){
					$ids["DEL"][] = $id;
				}
				else {
					$ids["UPD"][] = $id;
				}
			}
			else {
				$p++;
			}
		}
		if($p > 0 && count($ids['BN']) == 0){
			$this->object_type = "EO";
		}
		elseif($p > 0){
			$this->object_type = "complex";//mixes together blank node ids and non blank node ids 				
		}
		else {
			$this->object_type = "EOL";
			$this->obj_id_types = $ids;
		}
		return true;
	}
	
	/**
	 * Checks whether the passed json object is an object literal
	 * 
	 * Object literals are objects whose properties are solely drawn from: [datatype, type, value, data, lang]
	 * @param array $obj the associative array (json object) that is the value 
	 * @return boolean true if the passed object is an object literal
	 */
	function isObjectLiteral($obj){
		$allowedproperties = array("datatype", "type", "value", "data", "lang");
		if(!$obj or !is_array($obj) or count($obj) < 1 or count($obj) > 5){
			return false;
		} 
		$keys = array_keys($obj);
		foreach($keys as $key){
			if(!in_array($key, $allowedproperties)){
				return false;
			}
		}
		return true;
	}
	
	function regulariseObjectLiteralList($objl){
		$list = array();
		foreach($objl as $obj){
			$list[] = $this->regulariseObjectLiteral($obj);
		}
		return $list;
	}
	
	function regulariseObjectLiteral($obj){
		$nobj = array();
		if((isset($obj['type']) && $obj['type'] == "string") || (isset($obj['datatype']) && $obj['datatype'] == "string")){
			$nobj['lang'] = isset($obj['lang']) ? $obj['lang'] : "en";
		}
		elseif(isset($obj['type']) || isset($obj['datatype'])) {
			$nobj['type'] = isset($obj['type']) ? $obj['type'] : $obj['datatype'];
		}
		else {
			$nobj['lang'] = isset($obj['lang']) ? $obj['lang'] : "en";
		}
		if(!isset($obj['data']) && !isset($obj['value'])){
			$nobj['data'] = "";
		}
		$nobj['data'] = isset($obj['data']) ? $obj['data'] : $obj['value'];
		return $nobj;
	}

	/* retrieving information about embedded object lists */
	
	/**
	 * what ids are to be updated?
	 * @return array<string> an array of ids (urls) of nodes that are specified as 'updates'
	 */
	function getupdates(){
		return $this->obj_id_types["UPD"];
	}
	
	/**
	 * what blank nodes were encountered?
	 * @return array<string> an array of ids (urls) of blank nodes (starting with "_:")
	 */
	function getbnids(){
		return $this->obj_id_types['BN'];
	}
	
	/**
	 * what nodes are to be deleted?
	 * @return array<string> an array of ids (urls) of nodes that are specified as being deletes (value is ld type empty)
	 */
	
	function getdelids(){
		return $this->obj_id_types['DEL'];
	}

	/**
	 * Return the linked data type of a property value
	 * 
	 * Linked data values can be 
	 * * empty - an empty array or object
	 * * scalar - a scalar value (i.e. not a json object) - url or literal. Two types of scalars:
	 * ** literal - a literal value
	 * ** link - a url  
	 * * valuelist - an array of scalar values [x,y,z...] 
	 * * objectliteral - a typed literal with ONLY some of the following properties {datatype, data, type, lang, value}
	 * * objectliterallist - a simple array of object literals [objlit1, objlit2, ...]
	 * * embedded - an object directly embedded as a property value: {p1: , p2: ...}, 
	 * * objectlist - an array of embedded objects, 
	 * * embeddedobjectlist - a json object, properties are object ids with property values being the embedded object
	 * * complex - an array whose structure fits into none of the above categories..
	 * 
	 * @param boolean [$compress_scalars] if true, both literal and link types will be reported as 'scalar'
	 */
	function ldtype($compress_scalars = false){
		if($this->isempty()) return "empty";
		if($compress_scalars){
			if($this->scalar()) return "scalar";
		}
		else {
			if($this->literal()) return "literal";
			if($this->link()) return "link";
		}
		if($this->objectliteral()) return "objectliteral";
		if($this->objectliterallist()) return "objectliterallist";
		if($this->valuelist()) return "valuelist";
		if($this->embedded()) return "embedded";
		if($this->objectlist()) return "objectlist";
		if($this->embeddedlist()) return "embeddedobjectlist";
		return "complex";
	}
	
	/**
	 * Checks whether another LDPropertyValue object has the same linked data type
	 * @param LDPropertyValue $other the object of the comparison
	 * @param string $compress_scalars true if scalar types are to be treated as equal
	 * @return boolean true if the ld types of the object are the same.
	 */
	function sameLDType(LDPropertyValue $other, $compress_scalars = false){
		return $this->ldtype($compress_scalars) == $other->ldtype($compress_scalars);
	}
	
	/**
	 * Is the value legal?
	 * @return boolean true if legal
	 */
	function legal($rules = array()){
		if($this->errcode > 0) return false;
		if($this->invalidld && !(isset($rules['allow_invalid_ld']) && $rules['allow_invalid_ld'])){
			return $this->failure_result("value contains invalid linked data", 400);
		}
		if($this->literal()){
			if(isset($rules['require_object_literals']) && $rules['require_object_literals']){
				return $this->failure_result("value is simple literal, object literals are required", 400);
			}
		}
		if($this->isempty() && isset($rules['forbid_empty']) && $rules['forbid_empty']){
			return $this->failure_result("Illegal JSON LD structure passed: empty array - not supported in linked data form", 400);
		}
		if(isset($rules['expand_embedded_objects']) && ($this->objectlist() || $this->embedded())){
			return $this->failure_result("Illegal JSON LD structure passed: embedded object which was not expanded", 400);				
		}
		return true;
	}

	/**
	 * Is the value illegal?
	 * @return boolean true if illegal
	 */
	function illegal($rules = array()){
		return !$this->legal($rules);		
	}
	
	/* querying object for type information */
	
	/**
	 * Is it a scalar ld type?
	 * @return boolean true if the value is a scalar
	 */
	function scalar(){
		return $this->json_type == "scalar";
	}

	/**
	 * Is it a closed world link?
	 * @return boolean true if the value is a closed world link
	 */
	function cwlink(){
		return $this->json_type == "scalar" && $this->scalar_type == "CW";
	}

	/**
	 * Is it an open world link?
	 * @return boolean true if the value is an open world link
	 */
	function owlink(){
		return $this->json_type == "scalar" && $this->scalar_type == "OW";
	}
	
	/**
	 * Is it a link (either Blank Node, Closed World or Open World)?
	 * @return boolean true if the value is a link (URL/ID)
	 */
	function link(){
		return $this->json_type == "scalar" && $this->scalar_type != "L";
	}

	/**
	 * Is it a literal?
	 * @return boolean true if the value is a literal
	 */
	function literal(){
		return $this->json_type == "scalar" && $this->scalar_type == "L";
	}
	
	/**
	 * Is it a blank node id?
	 * @return boolean true if the value is a blank node id (starting with "_:")
	 */
	function bn(){
		return $this->json_type == "scalar" && $this->scalar_type == "BN";
	}
		
	//structured types

	/**
	 * Is it an object literal?
	 * @return boolean true if the value is an object literal
	 */
	function objectliteral(){
		return $this->json_type == "object" && $this->object_type == "literal";
	}
	
	/**
	 * Is it a list of object literals?
	 * @return boolean true if the value is a list of object literals
	 */
	function objectliterallist(){
		return $this->json_type == "array" && $this->array_type == "literal";
	}
	
	/**
	 * Is it an embedded ld object?
	 * @return boolean true if the value is a ld object
	 */
	function embedded(){
		return $this->json_type == "object" && $this->object_type == "EO";
	}
	
	/**
	 * is it an embedded object list? { id => {p => v}, id2 => {p => v2} }	
	 * @return boolean true if the value is en embedded object list
	 */
	function embeddedlist(){
		return $this->json_type == "object" && $this->object_type == "EOL";
	}
	
	/**
	 * Is it an empty object or array?
	 * @return boolean true if the value is an empty object / array
	 */
	function isempty(){
		return $this->json_type == "empty";
	}

	/**
	 * Is it an array of scalar values?
	 * @return boolean true if the value is an array of scalar values
	 */
	function valuelist(){
		return $this->json_type == "array" && $this->array_type == "scalar";
	}

	/**
	 * Is it an array of objects?
	 * @return boolean true if the value is an array of objects
	 */
	function objectlist(){
		return $this->json_type == "array" && $this->array_type == "object";
	}
}