<?php

/*
 * Class representing the value of a linked data property
 * At the highest level, values are broken down into:
 * 1. literal: v
 * 	1.5 objectliteral (type: string, value: ...}
 * 2. value list [v, v, ]
 * 2.5 object literal list[{v}, {v}, ]
 * 3. embedded object {}
 * 4. object list [{}, {}]
 * 5. embedded object list { id: {}, id: {} }
 * This depends on strongly typed property ranges and (somewhat) on the allocation of ids to all nodes in the dataset.
 *
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

class LDPropertyValue extends DacuraObject {
	var $json_type;//empty, scalar, array, object
	var $scalar_type;//BN (blank node id), CW (Closed World ID), OW (URL), L (Literal)
	var $array_type;//literal, object
	var $object_type;//literal, EO, EOL -> embedded object, embedded object list
	var $cwurl;//closed world url

	function __construct($val, $cwurl = false){
		$this->cwurl = $cwurl;
		$this->load($val);
	}

	/*
	 * Loads the value, parses it and returns its type information...
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
	
	/*
	 * Loads and parses any embedded objects
	 */
	function loadObjectDetails($obj){
		if($this->isObjectLiteral($obj)){
			$this->object_type = "literal";
			return true;
		}
		//big requirement to be able to distinguish between closed world ids and BN ids and property names -
		//they can't share the same url base
		//this is needed to distinguish embedded object lists and embedded objects.
		$p = 0;
		$ids = array("BN" => array(), "CW" => array(), "DEL" => array(), "UPD" => array());
		foreach($obj as $id => $val){
			//note: if it is embedded object list id can't point to [] -> not allowed in JSON LD
			if(isBlankNode($id)){
				$ids["BN"][] = $id;
				if((!is_array($val) or count($val) == 0) && $id != "_:meta" && $id != "_:candidate"){
					return $this->failure_result("Blank node $id has empty value - it must contain an embedded object", 400);
				}
			}
			elseif(!$this->cwurl){
				if(!is_array($val) or !isAssoc($val)){
					$p++;
				}
				elseif(is_array($val) && count($val) == 0){
					$ids["DEL"][] = $id;						
				}
				else {	
					$ids["UPD"][] = $id;
				}
			}
			elseif($this->isCWLink($id)){
				$ids["CW"][] = $id;
				if(!is_array($val) or count($val) == 0){
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
		if($this->cwurl && ($p > 0 && (count($ids["BN"]) > 0 or count($ids["CW"]) > 0))){
			return $this->failure_result("Illegal construct - internal ids cannot be mixed with external urls as node IDs", 400);
		}
		elseif($p > 0){
			$this->object_type = "EO";
		}
		else {
			$this->object_type = "EOL";
			$this->obj_id_types = $ids;
		}
		return true;
	}
	
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
		if($vtypes["array"] > 0){
			return $this->failure_result("Arrays cannot directly contain other arrays in Dacura LD", 400);
		}
		if(($vtypes["scalar"] > 0 or $vtypes['objectliteral'] > 0) && $vtypes["object"] > 0){
			return $this->failure_result("Arrays cannot contain a mix of scalars and embedded objects in Dacura LD", 400);
		}
		if($vtypes['object'] > 0){
			$this->array_type = "object";
		}
		elseif($vtypes['scalar'] > 0){
			$this->array_type = "scalar";
		}
		elseif($vtypes['objectliteral'] > 0){
			$this->array_type = "literal";
		}
		else {
			return $this->failure_result("No valid types found in LD property array - strange", 500);
		}
		return true;
	}
	
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
	
	/*
	 * Is a scalar a literal or an id (open world, blank node, closed world)
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
	
	function isOWLink($val){
		if(isURL($val) or isNamespacedURL($val)){
			return !$this->isCWLink($val);
		}
		return false;
	}
	
	function isCWLink($v){
		if(!$this->cwurl) return false;
		return substr($v, 0, 6) == "local:" || substr($v, 0, strlen($this->cwurl)) == $this->cwurl;
	}

	function sameLDType($other){
		return $this->ldtype() == $other->ldtype();
	}

	/*
	 * Reporting on state
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
	}
	
	function legal(){
		return $this->errcode <= 0;
	}

	function illegal(){
		return $this->errcode > 0;
	}
	
	/*
	 * Scalar types
	 */
	function scalar(){
		return $this->json_type == "scalar";
	}

	function cwlink(){
		return $this->json_type == "scalar" && $this->scalar_type == "CW";
	}

	function owlink(){
		return $this->json_type == "scalar" && $this->scalar_type == "OW";
	}
	
	function link(){
		return $this->json_type == "scalar" && $this->scalar_type != "L";
	}

	function literal(){
		return $this->json_type == "scalar" && $this->scalar_type == "L";
	}
	
	function bn(){
		return $this->json_type == "scalar" && $this->scalar_type == "BN";
	}
		
	//structured types
	function objectliteral(){
		return $this->json_type == "object" && $this->object_type == "literal";
	}
	
	function objectliterallist(){
		return $this->json_type == "array" && $this->array_type == "literal";
	}
	
	function embedded(){
		return $this->json_type == "object" && $this->object_type == "EO";
	}
	
	//is it an embedded object list? { id => {p => v}, id2 => {p => v2} }
	function embeddedlist(){
		return $this->json_type == "object" && $this->object_type == "EOL";
	}
	
	function isempty(){
		return $this->json_type == "empty";
	}

	function valuelist(){
		return $this->json_type == "array" && $this->array_type == "scalar";
	}

	function objectlist(){
		return $this->json_type == "array" && $this->array_type == "object";
	}

	
	/*
	 * retrieving information about embedded object lists: what ids are to be updated, what are to be deleted and what are to be created.
	 */
	
	function getupdates(){
		return $this->obj_id_types["UPD"];
	}

	function getbnids(){
		return $this->obj_id_types['BN'];
	}

	function getdelids(){
		return $this->obj_id_types['DEL'];
	}
}