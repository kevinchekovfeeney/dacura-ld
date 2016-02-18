<?php
include_once("phplib/libs/easyrdf-0.9.0/lib/EasyRdf.php");
include_once("LDUtils.php");
include_once("LDODisplay.php");

/**
 * Class representing functionality and properties that are useful for all Dacura Linked Data Objects
 * 
 * Linked data objects consist of a meta-data array (which can be pretty much any json structure
 *
 * Other classes inherit from this class so that they can utilise the common functions
 * Creation Date: 25/12/2014
 * @author Chekov
 * @license GPL V2
 */
class LDO extends DacuraObject {
	/** @var Array<string:object> an index to the object by subject id: objid -> array(&$obj) */
	var $index = false; 
	/** @var Array<string:array<string>> an index of 'bad' links - references within the object to non-existant objects / fragments */
	var $bad_links = array(); 
	/** @var Array<string:string> a map of original IDs to new ids - blank nodes that have been mapped to new names during processing */
	var $idmap = array(); 
	/** @var string closed world URL of the document. If present all internal ids in the document will be prefixed with this URL .*/
	var $cwurl = "";
	/** @var array the rules governing the linked data object */
	var $rules;
	/** @var string the collection id of the document - which collection own this document */
	var $cid;
	/** @var integer the version number of this object - starts from 1 and incremented with each update */
	var $version;
	/** @var integer the current version number of this object - if different from $version, the object under examination is an old version */
	var $latest_version;
	/** @var string the current status of this object - if different from $status, the object under examination is an old version whose status has been modified since it was created */
	var $latest_status;
	/** @var integer the UNIX timestamp of the moment the object was created */
	var $created;
	/** @var integer the UNIX timestamp of the moment the object was last modified (refers to latest update, not update of this version) */
	var $modified;
	/** @var integer the UNIX timestamp of the moment this version of the object was created */
	var $version_created;
	/** @var integer the UNIX timestamp of the moment this version of the object was replaced - 0 if this version has not been replaced, either because it has never been accepted or is the current version */
	var $version_replaced;
	/** @var array<string:array> an array of linked data properties, indexed by graphids - this represents the contents of the linked data object - documented in the LDUtils library */
	var $ldprops; 
	/** @var array an associative array of meta-data about the object. What goes in the metadata array is defined by the object type */
	var $meta;
	/** @var RequestLog a reference to a request log object to allow the object to take care of its own logging. */
	var $logger;
	/** @var NSResolver a reference to the corresponding namespace resolver object for this object - allows the object to internalise name space compression, etc */
	var $nsres;
	/** @var boolean set to true if the namespaces in the document have been compressed - replaced with prefixes, false otherwise */
	var $compressed = false;
	/** @var string what is the ldtype of the object */
	var $ldtype = "ldo";
	
	/** @var array<string:string> a map of object types to their full titles */	
	static $ldo_types = array("ontology" => "Ontology", "graph" => "Named Graph", "widget" => "User Interface Widget", "candidate" => "Instance Data Object", "import" => "Data Import Process", "publish" => "Data Publication Process", "task" => "Data Processing Task");
	
	/** @var the set of input formats that are supported by the system (including those supported by easy rdf) */	
	static $valid_input_formats = array(
			"json" => "Internal JSON Format", 
			"jsonld" => "Simplified JSON LD", 
			"triples" => "Triples", 
			"quads" => "Quads", 
			"turtle" => "Turtle Terse RDF", 
			"rdfxml" => "RDF/XML format", 
			"ntriples" => "N-Triples", 
			"rdfa" => "RDFa"
	);
	/** @var the set of display formats that are supported by the system (including those supported by easy rdf) */	
	static $valid_display_formats = array(
			"json" => "Internal JSON Format", 
			"png"=>"Portable Network Graphics (PNG)", 
			"dot" => "Graphviz", 
			"n3" => "Notation3", 
			"gif" => "Graphics Interchange Format (GIF)", 
			"svg" => "Scalable Vector Graphics (SVG)",
			"html" => "internal html view", 
			"jsonld" => "Simplified JSON LD", 
			"triples" => "Triples", 
			"quads" => "Quads", 
			"turtle" => "Turtle Terse RDF", 
			"rdfxml" => "RDF/XML format", 
			"ntriples" => "N-Triples", 
			"rdfa" => "RDFa"
	);
	
	static $format_mimetypes = array(
			"json" => "application/json",
			"png"=>"image/png",
			"dot" => "text/vnd.graphviz",
			"n3" => "text/n3",
			"gif" => "image/gif",
			"svg" => "image/svg+xml",
			"html" => "text/html",
			"jsonld" => "application/json",
			"triples" => "application/json",
			"quads" => "application/json",
			"turtle" => "text/turtle",
			"rdfxml" => "application/rdf+xml",
			"ntriples" => "application/n-triples",
			"rdfa" => "text/html"
	);
	/** @var the formats that do not depend upon easy-rdf */
	static $native_formats = array("json", "html", "triples", "quads", "jsonld");
	/**
	 * Constructor sets up closed world url, object id, and logger object, sets default values for object creation and modification 
	 * @param string $id the id of this object
	 * @param string $cwbase the base URL from which linked data objects' closed URLs are constructed
	 * @param RequestLog|boolean $logger - the request log object for logging information relating to the document
	 */
	function __construct($id, $cwbase = false, $logger = false){
		$this->id = $id;
		$this->created = time();
		$this->modified = time();
		if($cwbase){
			$this->cwurl = $cwbase."/".$id;
		}
		else {
			$this->cwurl = false;
		}
		$this->logger = $logger;
	}
	
	/**
	 * Copies the basic information from another object to this one
	 * 
	 * @param LDObject $other - the object from which the information will be copied. 
	 */
	function copyBasics(&$other){
		$this->cid = $other->cid;
		$this->version = $other->version;
		$this->created = $other->created;
		$this->status = $other->status;
		$this->modified = $other->modified;
	}
	
	function getPropertiesAsArray(){
		$props = array();
		if($this->cid) $props['cid'] = $this->cid;
		if($this->version) $props['version'] = $this->version;
		if($this->created) $props['created'] = $this->created;
		if($this->modified) $props['modified'] = $this->modified;
		if($this->latest_status) $props['latest_status'] = $this->latest_status;
		if($this->latest_version) $props['latest_version'] = $this->latest_version;
		if($this->version_replaced) $props['version_replaced'] = $this->version_replaced;
		if($this->version_created) $props['version_created'] = $this->version_created;
		return $props;
	}
	
	/** 
	 * Loads the object from the associative array representing the object in the database (field: value)
	 * @param array $row the associative array, indexed by column names in the ld_objects database table
	 * @param boolean $latest set to true if the latest version of the object is being loaded
	 */
	function loadFromDBRow($row, $latest = true){
		$this->setContext($row['collectionid']);
		$this->ldprops = json_decode($row['contents'], true);
		$this->meta = json_decode($row['meta'], true);
		$this->ldtype = $row['type'];
		/* object properties are copied into metadata array of object - the status value in meta has precedent, the db row values are just indeces for sql queries */
		if(!isset($this->meta['status'])){
			$this->status = $row['status'];
			$this->meta['status'] = $this->status;
		}
		else {
			$this->status = $this->meta['status'];
		}
		$this->version = $row['version'];
		$this->created = $row['createtime'];
		$this->modified = $row['modtime'];
		if($latest){
			$this->version_created = $this->modified;
			$this->version_replaced = 0;
			$this->latest_status = $this->status; 
			$this->latest_version = $this->version; 
		}
	}

	/**
	 * Called to copy the values of one LD object into another LD object 
	 * 
	 * Does a deep array copy of the contents and meta and bad-links index and resets the index (so that it doesn't point to the original objects)
	 */
	function __clone(){
		$this->ldprops = deepArrCopy($this->ldprops);
		$this->index = false;
		$this->bad_links = deepArrCopy($this->bad_links);
		$this->meta = deepArrCopy($this->meta);
	}
	
	/**
	 * Loads an array into the ldprops property: the contents of the object 
	 * @param array $arr associative LD array graphid -> contents
	 */
	function load($arr){
		$this->ldprops = $arr;
	}
	
	/**
	 * The rules that apply for ld validity when a new object is created 
	 * @return array name value property array
	 */
	function getNewLDORules(){
		$rules = array("cwurl" => $this->cwurl);
		return $rules;
	}
	
	/**
	 * The rules that apply for ld validity when a new object is created
	 * @return array name value property array
	 */
	function getLDORules(){
		$rules = array("cwurl" => $this->cwurl);
		return $rules;
	}
	
	/**
	 * Loads a new object from the api 
	 * @param array $obj the object as received by the api
	 * @param string $format one of LDO::$valid_input_formats
	 * @param array $options name value array of properties and their values
	 * @return boolean true if successful
	 */
	function loadNewObjectFromAPI($obj, $format, $options){
		if(!isset($obj['contents']) && !isset($obj['meta'])){
			return $this->failure_result("Create Object was malformed : both meta and contents are missing", 400);
		}
		$this->meta = isset($obj['meta']) ? $obj['meta'] : array();
		$this->version = 1;
		if(isset($obj['contents'])){
			$rules = $this->getNewLDORules();
			if($format == "json"){
				$this->ldprops = $obj['contents'];
				if(!$this->expand($rules, $options) || $this->validate($rules)){
					return false;
				}
			}
			else {
				if(!$this->import("text", $obj['contents'], false, $format, $rules)){
					return false;
				}			
			}
			$this->expandNS();//use fully expanded urls internally - support prefixes in input		
		}
		return true;
	}
	
	/**
	 * Validates the structure of a new ld object upon importation
	 * @param array $rules a settings array
	 * @param array|boolean [$obj] the object in question default is $this->ldprops
	 * @return boolean true if the object is valid according to the rules
	 */
	function validate($rules, $obj=false){
		if($obj === false) $obj = $this->ldprops;
		if(!is_array($obj)){
			return $this->failure_result("Input is not an array object", 500);
		}
		foreach($obj as $k => $v){
			$pv = new LDPropertyValue($v, $rules);
			if($pv->illegal()) {
				return $this->failure_result("Illegal JSON LD structure passed (property of $k) ".$pv->errmsg, $pv->errcode);
			}
			if(isset($rules['forbid_empty']) && $rules['forbid_empty']){
				if($pv->isempty()){
					return $this->failure_result("Illegal JSON LD structure passed: $k has an empty value - not supported in create requests", 400);
				}
			}
			if($pv->embeddedlist()){
				if(isset($rules['forbid_defined_node_ids']) && $rules['forbid_defined_node_ids']){
					$cwlinks = $pv->getupdates();
					if(count($cwlinks) > 0){
						return $this->failure_result("New candidates cannot update anything but their own properties: $k has closed world links ".$cwlinks[0], 400);
					}
				}
				foreach($v as $id => $emb){
					if(!$this->validate($rules, $emb)){
						return false;
					}
				}
			}
			elseif($pv->embedded()){
				if(!$this->validate($rules, $v)){
					return false;
				}
			}
			elseif($pv->objectlist()){
				foreach($v as $emb){
					if(!$this->validate($rules, $emb)){
						return false;
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * Returns a json representation of the object properties, optionally for a particular key of the properties array
	 * 
	 * The key represents a particular graph id 
	 * 
	 * @param string [$key] the graph id to json-ifiy - if absent all properties will be included
	 * @return string the jsonified representation of the object
	 */
	function get_json($key = false){
		if($key){
			if(!isset($this->ldprops[$key])){
				return "{}";
			}
			return json_encode($this->ldprops[$key]);
		}
		return json_encode($this->ldprops);
	}
	
	/* Some basic gets and sets	 */

	/**
	 * Sets the collection context of the object
	 * @param string $cid the collection id
	 */
	function setContext($cid){
		$this->cid = $cid;
	}
	
	/**
	 * gets / sets the version number of this object
	 * 
	 * @param integer|boolean $v if set, the object's version is set to $v
	 * @return integer the object's version number
	 */
	function version($v = false){
		if($v !== false){
			$this->version = $v;
		}
		return $this->version;
	}
	
	/**
	 * Does this object instance represent the latest version of the object?
	 * @return boolean true if it is the latest version
	 */
	function isLatestVersion(){
		return $this->version == $this->latest_version;
	}
	
	/**
	 * Sets the version
	 * @param unknown $v
	 * @param string $is_latest
	 */
	function set_version($v, $is_latest = false){
		$this->version($v);
		if($is_latest){
			$this->latest_version = $v;
		}
	}	

	/**
	 * Just a synonym for the status() function defined in DacuraObject
	 * @return Ambigous <string, array>
	 */
	function get_status(){
		return $this->status();
	}
	
	/**
	 * Sets the status of the object to the passed value
	 * 
	 * sets the value both in the meta-object and the object property
	 * @param string $s the status - one of DacuraObject::$valid_statuses
	 * @param boolean $is_latest - true if this is the latest version of the object
	 */
	function set_status($s, $is_latest = false){
		$this->status($s, $is_latest);
	}
	
	/**
	 * overrides the base status function to ensure that the status value in meta is always aligned with the status value in the object property
	 * @param $s - the status to be updated 
	 * @param $is_latest - is this the latest status?
	 * (non-PHPdoc)
	 * @see DacuraObject::status()
	 */
	function status($s= false, $is_latest = false){
		if($s === false) return parent::status();
		if(!isset($this->meta) or !is_array($this->meta)){
			$this->meta = array();
		}
		$this->meta['status'] = $s;
		if($is_latest){
			$this->latest_status = $s;
		}
		return parent::status($s);		
	}
	
	/**
	 * Sets the object's name space resolver object - used for compressing urls with prefixes
	 * @param NSResolver $nsres
	 */
	function setNamespaces(NSResolver $nsres){
		$this->nsres = $nsres;
	}

	/**
	 * Expands the object's contained urls into full urls using the object's NSResolver property 
	 * 
	 * used for expanding urls with prefixes
	 */
	function expandNS(){
		if(!$this->nsres){
			return $this->failure_result("No name space resolver object set for LD object - cannot expand Namespaces", 500);
		}
		$res = $this->nsres->expandNamespaces($this->ldprops, $this->cwurl);
		if($res){
			$this->compressed = false;
		}
		return $res;		
	}
	
	/**
	 * Compresses the object's contained urls into prefixed urls using the object's NSResolver property 
	 * 
	 * used for compressing urls with prefixes
	 */
	function compressNS(){
		if(!$this->nsres){
			return $this->failure_result("No name space resolver object set for LD object - cannot compress Namespaces", 500);
		}
		$res = $this->nsres->compressNamespaces($this->ldprops, $this->cwurl);
		if($res){
			$this->compressed = true;
		}
		return $res;		
	}
	
	/**
	 * Returns a map of the namespaces used in the document 
	 * 
	 * prefix => full url 
	 * 
	 * @return array<string:string> a map of prefixes to their full form 
	 */
	function getNS(){
		return $this->nsres->getNamespaces($this->id, $this->ldprops, $this->cwurl, $this->compressed);
	}
	
	/**
	 * Returns the rdf:type of the passed ld object
	 * @param array $obj json ld object array
	 * @return boolean|string the rdf type of the object
	 */
	function getObjectType($obj){
		if(!isset($obj['rdf:type']) and !isset($obj[$this->nsres->getURL("rdf")."type"])){
			return false;
		}
		return isset($obj['rdf:type']) ? $obj['rdf:type'] : $obj[$this->nsres->getURL("rdf")."type"];
	}		
	
	/**
	 * Retrieves the fragment id of a particular local node id
	 * @param string $f fragment id
	 * @param string $ext local node id
	 * @return string|boolean the full fragment id or false if it does not exist
	 */
	function getFragIDForExtension($f, $ext){
		if(isset($this->ldprops[$f][$this->cwurl."/".$ext])){
			return $this->cwurl."/".$ext;
		}
		if(isset($this->ldprops[$f][$this->id.":".$ext])){
			return $this->id.":".$ext;
		}
		if(isset($this->ldprops[$f]["local:".$this->id."/".$ext])){
			return "local:".$this->id."/".$ext;
		}
		if(isset($this->ldprops[$f]["_:".$ext])){
			return "_:".$ext;
		}
		return false;
	}

	/**
	 * Does the object contain a fragment with the given id?
	 * @param string $frag_id the object's fragment id
	 * @return boolean true if the fragment with the given id exists in the object
	 */
	function hasFragment($frag_id){
		if($this->index === false){
			$this->buildIndex($this->ldprops, $this->index);
		}
		return isset($this->index[$frag_id]);
	}
	
	/**
	 * Retrieve a particular fragment by id
	 * @param string $fid fragment id
	 * @return array - array of fragment values or false if not found
	 */
	function getFragment($fid){
		if($this->index === false){
			$this->buildIndex();
		}
		return isset($this->index[$fid]) ? $this->index[$fid] : false;
	}
	
	/**
	 * Is the passed value an id of an internal node in the document?
	 * @param string $val
	 * @return boolean true if the value is an internal link
	 */
	function isDocumentLocalLink($val){
		return isInternalLink($val, $this->id, $this->cwurl);
	}
	
	/**
	 * Returns the object embedding paths to the fragments with subjects of the passed id
	 * @param string $fid the fragment id to find
	 * @param array $rules rules governing the resolution of paths
	 * @return array<array> an array of paths to the object in question
	 */
	function getFragmentPaths($fid){
		$paths = getFragmentContext($fid, $this->ldprops, $this->rules);
		return $paths;
	}
	
	/**
	 * Set the contents of this object to be a fragment of itself.
	 * @param unknown $fragment_id
	 */
	function setContentsToFragment($fragment_id){
		$this->ldprops = getFragmentInContext($fragment_id, $this->ldprops, $this->rules);
	}
	
	/**
	 * Builds an index of the object id => [values]
	 */
	function buildIndex(){
		$this->index = array();
		indexLD($this->ldprops, $this->index, $this->rules);
	}
	
	/**
	 * Some state is duplicated between the meta ld field and the object properties
	 * In such cases the meta field is authoritative (as it is part of state-management)
	 */
	function readStateFromMeta(){
		$this->status = $this->meta['status'];
	}
	
	/**
	 * Called when a new ld entity is created - expands it by inserting structural id nodes where necessary
	 * @param string $rules - rules governing transformation
	 * @return boolean true if expansion is successful
	 */
	function expand($rules = false, $options = array()){
		$rules = $rules ? $rules : $this->rules;
		$rep = expandLD($this->ldprops, $rules);
		if($rep === false){
			return $this->failure_result("Failed to expand input into structure linked data object", 400);
		}
		if(isset($rep["missing"])){
			$this->bad_links = $rep["missing"];
		}
		$this->idmap = $rep['idmap'];
		return true;
	}
	
	/**
	 * Calculates the transforms necessary to get to current from other
	 * 
	 * @param LDObject $other the object to be compared to this one
	 * @return LDDelta
	 */
	function compare(LDObject $other){
		$aprops = $this->ldprops;
		$aprops['meta'] = $this->meta;
		$bprops = $other->ldprops;
		$bprops['meta'] = $other->meta;
		$cdelta = compareLDGraphs($this->id, $aprops, $bprops, $this->rules, true);
		if($cdelta->containsChanges()){
			$cdelta->setMissingLinks($this->missingLinks(), $other->missingLinks());
		}
		return $cdelta;
	}
	
	/**
	 * Updates a ld property array according to the passed update object
	 * @param array $update_obj ld update object
	 * @param array $rules rules that will apply to thos update
	 * @return boolean
	 */
	function update($update_obj, $rules = false){
		$rules = $rules ? $rules : $this->rules;
		if(isset($update_obj['meta'])){
			$umeta = $update_obj['meta'];
			unset($update_obj['meta']);
		}
		else {
			$umeta = false;
		}
		if($this->applyUpdates($update_obj, $this->ldprops, $this->idmap, $rules)){
			if($umeta === false || $this->applyUpdates($umeta, $this->meta, $this->idmap, $rules)){
				if(count($this->idmap) > 0){
					$unresolved = updateBNReferences($this->ldprops, $this->idmap, $rules);
					if($unresolved === false){
						return false;
					}
					elseif(count($unresolved) > 0){
						$this->bad_links = $unresolved;
					}
				}
				$this->buildIndex();
				return true;
			}
		}
		return false;
	}

	/**
	 * Apply changes specified in props to properties in dprops
	 * Generates new ids for each blank node and returns mapping in idmap.
	 *
	 * @param array $uprops - the update instructions
	 * @param array $dprops - the properties to be updated (delta)
	 * @param array $idmap - map of local ids to newly generated IDs
	 * @param array $rules - rules that apply to this update
	 * @return boolean true if updates worked
	 */
	function applyUpdates($uprops, &$dprops, &$idmap, $rules){
		foreach($uprops as $prop => $v){
			if(!is_array($dprops)){
				$dprops = array();
			}
			$pv = new LDPropertyValue($v, $rules);
			if($pv->illegal()){
				return $this->failure_result($pv->errmsg, $pv->errcode);
			}
			elseif($pv->scalar() or $pv->objectliteral()){
				$dprops[$prop] = $v;
			}
			elseif($pv->valuelist() or $pv->objectliterallist()){
				$dprops[$prop] = $v;
			}
			elseif($pv->isempty()){ // delete property or complain
				if(isset($dprops[$prop])){
					unset($dprops[$prop]);
				}
				elseif(isset($rules['fail_on_bad_deletes']) && $rules['fail_on_bad_deletes']) {
					return $this->failure_result("Attempted to remove non-existant property $prop", 404);
				}
			}
			elseif($pv->objectlist()){ //list of new objects (may have @ids inside)
				foreach($v as $obj){
					addAnonObj($obj, $dprops, $prop, $idmap, $rules);
				}
			}
			elseif($pv->embedded()){ //new object to add to the list - give her an id and insert her
				$rep = expandLD($v, $rules);
				if($rep === false){
					return $this->failure_result("Failed to expand blank nodes", 400);
				}
				if(isset($rep["missing"])){
					$this->bad_links = array_merge($this->bad_links, $rep["missing"]);
				}
				$idmap = array_merge($idmap, $rep['idmap']);
				addAnonObj($v, $dprops, $prop, $idmap, $rules);				
			}
			elseif($pv->embeddedlist()){
				$bnids = $pv->getbnids();//new nodes
				foreach($bnids as $bnid){
					addAnonObj($v[$bnid], $dprops, $prop, $idmap, $rules, $bnid);
				}
				$delids = $pv->getdelids();//delete nodes
				foreach($delids as $did){
					if(isset($dprops[$prop][$did])){
						unset($dprops[$prop][$did]);
					}
					elseif(isset($rules['fail_on_bad_deletes']) && $rules['fail_on_bad_deletes']) {
						return $this->failure_result("Attempted to delete non-existant embedded object $did from $prop", 404);
					}
				}
				$update_ids = $pv->getupdates();
				foreach($update_ids as $uid){
					if(!isset($dprops[$prop])){
						$dprops[$prop] = array();
					}
					if(!isset($dprops[$prop][$uid])){
						if(isset($rules['set_id_allowed']) && $rules['set_id_allowed']) {
							$dprops[$prop][$uid] = array();
						}
						elseif(isset($rules['fail_on_bad_update']) && $rules['fail_on_bad_update']) {						
							return $this->failure_result("Attempted to update non existent element $uid of property $prop", 404);
						}
						else {
							continue;//just ignore the node
						}
					}
					if(!$this->applyUpdates($uprops[$prop][$uid], $dprops[$prop][$uid], $idmap, $rules)){
						return false;
					}
					//opr($dprops[$prop][$uid]);
					if(isset($dprops[$prop][$uid]) && is_array($dprops[$prop][$uid]) and count($dprops[$prop][$uid]) == 0){
						unset($dprops[$prop][$uid]);
					}
				}
			}
			if(isset($dprops[$prop]) && is_array($dprops[$prop]) && count($dprops[$prop])==0) {
				unset($dprops[$prop]);
			}
		}
		return true;
	}

	/**
	 * imports a graph from easy rdf into our object
	 * @param string $type the import type: text, file, url
	 * @param string $arg the id of the object
	 * @param string $gurl the graph id
	 * @param string $format the format of the source
	 * @param array $rules rules applying to the import
	 * @return boolean true on success
	 */
	function import($type, $arg, $gurl = false, $format = false, $rules = false){
		$rules = $rules ? $rules : $this->rules;
		$graph = $this->importERDF($type, $arg, $gurl, $format);
		$op = $graph->serialise("php");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->expand($rules);
		return $this->validate($rules);
		/*$errs = validLD($this->ldprops, $rules);
		if(count($errs) > 0){
			$msg = "<ul><li>".implode("<li>", $errs)."</ul>";
			return $this->failure_result("Graph had ". count($errs)." errors. $msg", 400);
		}*/
		//return true;
	}
		
	
	/**
	 * Imports a data structure from Easy RDF internal format
	 * @param string $type the type of import source (url, text, file)
	 * @param string $arg the id of the object or the full text of the object
	 * @param string $gurl the id of the graph
	 * @param string $format the format of the source (must be a supported type of easy rdf)
	 * @return boolean|Ambigous <EasyRdf_Graph, object, EasyRdf_Graph>
	 */
	function importERDF($type, $arg, $gurl = false, $format = false){
		try {
			if($type == "url"){
				$graph = EasyRdf_Graph::newAndLoad($arg, $format, $this->id);
			}
			elseif($type == "text"){
				$graph = new EasyRdf_Graph($gurl, $arg, $format, $this->id);
			}
			elseif($type == "file"){
				$graph = new EasyRdf_Graph($gurl);
				$graph->genid = $this->id;//sets a unique prefix for the blank node ids generated by easy rdf 
				$graph->parseFile($arg, $format);//the slow one
				$this->logger && $this->logger->timeEvent("Load Graph", "debug");
			}
			if($graph->isEmpty()){
				return $this->failure_result("Graph loaded from $type was empty.", 500);
			}
			return $graph;
		}
		catch(Exception $e){
			//opr($graph);
			return $this->failure_result("Failed to load graph from $type. ".$e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Generate a list of all the namespaces / prefixes supported by the library
	 * @return array namespace list
	 */
	function getERDFSupportedNamespaces(){
		return EasyRdf_Namespace::namespaces();		
	}
	
	
	/**
	 * Exports from the local ld format into an external format
	 * @param string $format the desired output format
	 * @param array $nsobj a ns resolver object to create the list of namespaces with
	 * @return boolean|string the serialised export of the object
	 */
	function export($format, $nsobj = false){
		$easy = exportEasyRDFPHP($this->id, $this->ldprops, $this->rules);
		try{
			foreach($this->nsres->prefixes as $id => $url){
				EasyRdf_Namespace::set($id, $url);
			}
				
			$graph = new EasyRdf_Graph($this->id, $easy, "php", $this->id);
			if($graph->isEmpty()){
				return $this->failure_result("Graph was empty.", 400);
			}
			if($nsobj){
				$nslist = $this->getNS($nsobj);
				if($nslist){
					foreach($nslist as $prefix => $full){
						EasyRdf_Namespace::set($prefix, $full);
					}
				}
			}
			$res = $graph->serialise($format);
			if(!$res){
				return $this->failure_result("failed to serialise graph", 500);
			}
			return $res;
		}
		catch(Exception $e){
			return $this->failure_result("Graph croaked on input. ".$e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Returns a list of the bad links in the object
	 */
	function problems(){
		if(count($this->bad_links) > 0){
			return $this->bad_links;
		}
		return false;
	}
	/**
	 * Returns a list of the bad links in the object
	 */
	function missingLinks(){
		if(isset($this->bad_links)){
			return $this->bad_links;
		}
		return $this->findMissingLinks();
	}
	
	/**
	 * Returns a list of the bad links in the object
	 */
	function findMissingLinks(){
		if($this->index === false){
			$this->buildIndex();
		}
		$ml = findInternalMissingLinks($this->ldprops, array_keys($this->index), $this->id, $this->rules);
		$x = count($ml);
		if($x > 0){
			$this->bad_links = $ml;
		}
		return $ml;
	}
	
	/**
	 * Returns true if the object complies with its rules
	 */
	function compliant(){
		$errs = validLD($this->ldprops, $this->rules);
		if(count($errs) == 0){
			return true;
		}
		else {
			$errmsg = "Errors in input formatting:<ol> ";
			foreach($errs as $err){
				$errmsg .= "<li>".$err[0]." ".$err[1];
			}
			$errmsg .= "</ol>";
			return $this->failure_result($errmsg, 400);
		}
	}
	
	/**
	 * Is the given format natively supported by dacura
	 * @param string $format the format id
	 * @return boolean true if natively supported
	 */
	function isNativeFormat($format){
		return $format == "" or in_array($format, LDO::$native_formats);
	}
	
	function getContentInFormat($format, $options, $srvr = null, $for = "internal"){
		if($this->isNativeFormat($format)){
			if(in_array('ns', $options)) {
				$ldo->compressNS();
			}
		}
		if($for == "display"){
			$this->display($format, $options, $srvr);
			return $this->display;
		}
		if($format == "json"){
			$payload = $this->ldprops;
		}
		elseif($format == "html"){
			$this->display("html", $options, $srvr);
			$payload = $this->display;
		}
		elseif($format == "triples"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedTriples() : $this->triples();
		}
		elseif($format == "quads"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedQuads() : $this->quads();		
		}
		elseif($format == "jsonld"){
			$payload = toJSONLD($this->ldprops, $this->cwurl);				
		}
		else {
			$exported = $this->export($format);
			if(!$exported){
				return false;
			}
			$payload = $exported;
		}
		if($for == "api" && $format != "html" && $this->isNativeFormat($format)){
			$payload = json_encode($payload);
		}
		return $payload;
	}
	
	function display($format, $options, $srvr){
		$lddisp = new LDODisplay();	
		if($format == "json"){
			$this->display = $lddisp->displayJSON($this->ldprops, $options);
		}
		elseif($format == "html"){
			$this->display = $lddisp->displayHTML($this->ldprops, $options);
		}
		elseif($format == "triples"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedTriples() : $this->triples();
			$this->display = $lddisp->displayTriples($payload, $options);
		}
		elseif($format == "quads"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedQuads() : $this->quads();
			$this->display = $lddisp->displayQuads($payload, $options);
		}
		elseif($format == "jsonld"){
			$this->display = $lddisp->displayJSONLD($this->ldprops, $options);
		}
		else {
			$exported = $this->export($format);
			if(!$exported){
				return false;
			}
			$this->display = $lddisp->displayExport($exported, $format, $options);
		}
	}
	
	/**
	 * Return an array of triples as a representation of this ld property array - with literals typed 
	 */
	function typedTriples(){
		return getObjectAsTypedTriples($this->id, $this->ldprops, $this->rules);
	}
	
	/**
	 * Return an array of triples as a representation of this ld property array - with untyped literals 
	 */
	function triples(){
		return getObjectAsTriples($this->id, $this->ldprops, $this->rules);
	}

	function typedQuads(){
		return getObjectAsTypedQuads($this->id, $this->ldprops, $this->rules);		
	}
	
	function quads(){
		return getObjectAsQuads($this->id, $this->ldprops, $this->rules);		
	}
	
	/**
	 * Return an array of all the internal triples as a representation of this ld property array 
	 */
	function internalTriples(){
		return getPropertiesAsArray($this->id, $this->ldprops, $this->rules, array($this, "showTriples"));
	}
		
	/**
	 * Return an array of quads - for a particular property
	 * @param string $gname graph id
	 * @return array<quads> an array of quads
	 */
	function getPropertyAsQuads($prop, $gname){
		if(!isset($this->ldprops[$prop])) return array();
		$quads = array();
		$trips = getEOLAsTypedTriples($this->ldprops[$prop], $this->rules);
		foreach($trips as $trip){
			$trip[] = $gname;
			$quads[] = $trip;
		}
		return $quads;
	}
	
	/**
	 * Return a view of the object for sending to the api (turns off lots of stuff)
	 */
	function forAPI($format, $opts){
		$meta = deepArrCopy($this->meta);
		$meta = array_merge($this->getPropertiesAsArray(), $meta);
		$apirep = array("id" => $this->id, "ldtype" => $this->ldtype, "meta" => $meta, "ldprops" => $this->display, "format" => $format, "options" => $opts);
		if(isset($opts['history']) && $opts['history']){
			$apirep["history"] = $this->history;	
		}
		if(isset($opts['updates']) && $opts['updates']){
			$apirep["updates"] = $this->updates;	
		}
		return $apirep;
	}
	
	/* Shared functionality for specifiying dqs configs */
	/**
	 * Returns true if the dqs configuration is specified
	 */
	function dqsSpecified(){
		return isset($this->meta['imports']) && is_array($this->meta['imports']) &&
		(isset($this->meta['schema_dqs']) || isset($this->meta['instance_dqs']));
	}
	
	/**
	 * Returns the list of dqs tests that have been specified 
	 * @param string $which - instance or schema
	 */
	function getDQSTests($which){
		if(isset($this->meta[$which.'_dqs'])){
			return $this->meta[$which.'_dqs'];
		}
		return false;
	}
	
	
	/**
	 * Returns the list of ontologies that have been imported
	 */
	function getImportedOntologies(){
		if(isset($this->meta['imports'])){
			return $this->meta['imports'];
		}
		return false;
	}
	

}