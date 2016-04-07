<?php
include_once("phplib/libs/easyrdf-0.9.0/lib/EasyRdf.php");
include_once("LDUtils.php");
include_once("LDODisplay.php");

/**
 * Class representing a linked data object - the fundamental unit of Dacura Managemnt 
 * 
 * The class contains functionality and properties that are useful for all Dacura Linked Data Objects. 
 * These are extended and overriden in sub-classes (Ontology, Graph, Candidate) to cover their particularities
 * But, wherever possible, this object is written to be universal and only needing minimal extension by sub-classes
 * 
 * Linked data objects consist of a meta-data array (any json structure) 
 * and a ldprops array containing the linked data propositions according to the rules in LDPropertyValue.php
 *
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
	/** @var boolean set to true if the blank nodes in the document have been prefixed with the document url (cwurl) to make them addressable externally */
	var $addressable_bnids = false;
	/** @var string what is the ldtype of the object */
	var $ldtype = "ldo";
	/** @var boolean does this object span multiple graphs? if so, the ldprops will be indexed by graph */
	var $multigraph = false;
	/** @var string the fragment id (internal blank node) that is loaded in the ldprops array (only set when a fragment is loaded)*/
	var $fragment_id = false;
	/** @var array the path to the fragment within the object's ldprops array - for embedded fragments (only set when a fragment is loaded)*/
	var $fragment_path = false;
	
	/** @var array<string:string> a map of object types to their full titles */	
	static $ldo_types = array("ontology" => "Ontology", "graph" => "Named Graph", "widget" => "User Interface Widget", "candidate" => "Instance Data Object", "import" => "Data Import Process", "publish" => "Data Publication Process", "task" => "Data Processing Task");
	
	/** @var the set of input formats that are supported by the system (including those supported by easy rdf) */	
	static $valid_input_formats = array(
			"json" => "Dacura JSON", 
			"jsonld" => "JSON LD", 
			"nquads" => "N-Quads", 
			"turtle" => "Turtle Terse RDF", 
			"rdfxml" => "RDF/XML format", 
			"ntriples" => "N-Triples", 
			"triples" => "JSON Triples",
			"quads" => "JSON Quads"					
	);
	/** @var the set of display formats that are supported by the system (including those supported by easy rdf) */	
	static $valid_display_formats = array(
			"json" => "Internal JSON Format", 
			"html" => "internal html view", 
			"jsonld" => "JSON LD", 
			"turtle" => "Turtle Terse RDF", 
			"rdfxml" => "RDF/XML format", 
			"n3" => "Notation3", 
			"triples" => "Triples", 
			"ntriples" => "N-Triples",
			"quads" => "Quads", 
			"nquads" => "N-Quads", 
			"dot" => "Graphviz Dot Notation", 
			"png" => "Portable Network Graphics (PNG)", 
			"gif" => "Graphics Interchange Format (GIF)", 
			"svg" => "Scalable Vector Graphics (SVG)",
	);
	/** @var the mimetypes corresponding to each of the supported formats */
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
	/** @var the formats that do not depend upon easy-rdf for import / export */
	static $native_formats = array("json", "html", "triples", "quads", "jsonld", "nquads");
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
			$this->cwurl = $cwbase.$id;
		}
		else {
			$this->cwurl = false;
		}
		$this->logger = $logger;
	}
	
	/**
	 * Returns the canonical dacura url for this linked data objects
	 * 
	 * The canonical url is always http://dacura/[collection]/[ldtype]|ld/id
	 * @return string the canonical url of the linked data object
	 */
	function durl(){
		return $this->cwurl;
	}
	
	/**
	 * Copies the basic information from another object to this one
	 * 
	 * @param LDObject $other - the object from which the information will be copied. 
	 */
	function copyBasics(&$other){
		$this->cid = $other->cid;
		$this->ldtype = $other->ldtype;
		$this->version = $other->version;
		$this->created = $other->created;
		$this->status = $other->status;
		$this->modified = $other->modified;
	}
	
	/**
	 * Returns the properties of the object as an array - for copying into api datastructures without copying the whole object 
	 * @return array of properties cid, ldtype, version, created, modified, latest_status, ...
	 */
	function getPropertiesAsArray(){
		$props = array();
		if($this->cid) $props['cid'] = $this->cid;
		if($this->ldtype) $props['ldtype'] = $this->ldtype;
		if($this->version) $props['version'] = $this->version;
		if($this->created) $props['created'] = $this->created;
		if($this->modified) $props['modified'] = $this->modified;
		if($this->latest_status) $props['latest_status'] = $this->latest_status;
		if($this->latest_version) $props['latest_version'] = $this->latest_version;
		if($this->version_replaced) $props['version_replaced'] = $this->version_replaced;
		if($this->version_created) $props['version_created'] = $this->version_created;
		$props['compressed'] = $this->compressed;
		$props['addressable_bnids'] = $this->addressable_bnids;
		return $props;
	}
	
	function getStandardProperties(){
		return array('cid', 'ldtype', 'version', 'created', 'modified', 
			'latest_status', 'latest_version', 'version_replaced',
			"version_created", 'compressed', 'addressable_bnids');
	}
	
	/** 
	 * Loads the object from the associative array representing the object in the database (field: value)
	 * @param array $row the associative array, indexed by column names in the ld_objects database table
	 * @param boolean $latest set to true if the latest version of the object is being loaded
	 */
	function loadFromDBRow($row, $latest = true){
		$this->setContext($row['collectionid']);
		$this->ldprops = $row['contents'];
		if(!is_array($this->ldprops)){
			$this->ldprops = array();
		}
		$this->meta = $row['meta'];
		if(!is_array($this->meta)){
			$this->meta = array();
		}
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
	 * Called immediately after a LDO record is loaded from database
	 * 
	 * Allows derived classes to do special stuff
	 * @param LdDacuraServer $srvr the currently active linked data server
	 */
	function deserialise(LdDacuraServer &$srvr){}
	
	/**
	 * Loads a new object from the api 
	 * @param array $obj the object as received by the api
	 * @param string $format one of LDO::$valid_input_formats
	 * @param array $options name value array of properties and their values
	 * @return boolean true if successful
	 */
	function loadNewObjectFromAPI($obj, $format, $options, $rules, &$srvr){	
		if(!isset($obj['contents']) && !isset($obj['meta']) && !isset($obj['ldurl']) && !isset($obj['ldfile'])){
			return $this->failure_result("Create Object was malformed : both meta and contents are missing", 400);
		}
		$this->version = 1;
		$this->meta = array();
		if(isset($obj['meta']) && $obj['meta']){
			$ignore_meta = $this->getStandardProperties();
			foreach($obj['meta'] as $k => $v){
				if(!in_array($k, $ignore_meta)){
					$this->meta[$k] = $v;
				}	
			}
			if(!$this->validateMeta($rules, $srvr)){
				return false;
			}
		}
		if(isset($obj['contents']) && $obj['contents']){
			return $this->import("text", $obj['contents'], $format, $rules, $srvr);
		}
		elseif(isset($obj['ldurl']) && $obj['ldurl']){
			return $this->import("url", $obj['ldurl'], $format, $rules, $srvr);						
		}
		elseif(isset($obj['ldfile']) && $obj['ldfile']){
			return $this->import("file", $obj['ldfile'], $format, $rules, $srvr);
		}
		else {
			$format = "json";
			$this->ldprops = array();
		}
		return $format;
	}
	
	/**
	 * Returns true if the LDO contains data from multiple graphs
	 * 
	 * In all multi-graph ldos, the indices of the ldprops arrays are the graph ids / urls
	 * 
	 * @return boolean
	 */
	function is_multigraph(){
		return $this->multigraph;
	}
	
	/**
	 * Callback function to generate an internal id for blank nodes within this object. 
	 * 
	 * The default behaviour is to employ a simple counter with the object's id - to increase chances of regeneration
	 * @param unknown $extra
	 * @param unknown $old
	 * @return string
	 */
	function generateInternalID($extra, $old){
		static $i = 0;
		return $this->id.++$i;
	}
		
	/**
	 * imports a graph from easy rdf into our object
	 * @param string $source the import source: text, file, url
	 * @param string $arg either the filename (if file) the url (if url) or the text itself (if text)
	 * @param string $gurl the graph id
	 * @param string $format the format of the source
	 * @param array $rules rules applying to the import
	 * @return string the format that was loaded (to support format detection)
	 */
	function import($source, $arg, $format = false, $rules = false, LdDacuraServer &$srvr){
		global $dacura_server;
		if($source == "file" && ($contents = file_get_contents($arg))){
			return $this->failure_result("Failed to load file ".htmlspecialchars($arg), 500);				
		}
		elseif($source == "url"){
			if(!($contents = $dacura_server->fileman->fetchFileFromURL($arg))){
				return $this->failure_result($dacura_server->fileman->errmsg, $dacura_server->fileman->errcode);
			}
		}
		else {
			$contents = $arg;
		}
		return $this->importContents($contents, $format, $rules, $srvr);
	}
	
	/**
	 * imports a graph from a string into our object via easyrdf
	 * @param string $contents text to be imported 
	 * @param string $format the format of the source
	 * @param array $rules rules applying to the import
	 * @param LdDacuraServer $srvr - the server object, passed to make its api available 
	 * @return string the format that was loaded (to support format detection)
	 */
	function importContents($contents, $format, $rules, LdDacuraServer &$srvr){
		if(!$format){
			$format = $this->importContentsFromJSON($contents, $rules, false, $srvr);
			if(!$format){
				$format = $this->importERDF("text", $contents, $rules);
				if(!$format && $this->importContentsfromNQuads($contents, $rules)){
					$format = "nquads";
				}
				elseif(!$format) {
					return $this->failure_result("Failed to parse contents with any supported format", 400);
				}
			}
		}
		elseif(!isset(LDO::$valid_input_formats[$format])){
			return $this->failure_result("$format is not a valid linked data input format", 400);
		}
		elseif($this->isNativeFormat($format)){ //first try to see if we can import as a native format
			if($format == "nquads"){
				if(!$this->importContentsfromNQuads($contents, $rules)){
					return $this->failure_result("Failed to import nquads", 400);
				}
			}
			else {
				if(!$this->importContentsFromJSON($contents, $rules, $format, $srvr)){
					return false;
				}
										
			}
		}
		else {
			if($format != $this->importERDF("text", $contents, $rules, $format)){
				return false;
			}				
		}
		$this->expandNS();//deal with full urls
		//opr($this->ldprops);
		$this->idmap = importLD($this->ldprops, $rules, $this->multigraph);//expands structure by generating blank node ids, etc			
		if(!$this->validateLD($rules, $srvr)){
			return false;
		}
		return $format;		
	}
	
	/**
	 * imports a graph from a json object of several flavours into one of our objects
	 * @param array $json json array
	 * @param array $rules rules applying to the import
	 * @param string $format the format of the source
	 * @param LdDacuraServer $srvr - the server object, passed to make its api available 
	 * @return string the format that was loaded (to support format detection)
	 */
	function importContentsFromJSON($json, $rules = array(), $format = false, $srvr = false){
		if(!is_array($json) && !($json = json_decode($json, true))){
			return $this->failure_result("Contents were not decipherable as a json array", 400);
		}
		if(!$format){
			if(isAssoc($json)){
				if(isset($json['@graph']) || isset($json['@id'])) $format = "jsonld";
				else $format = "json";
			}
			//quads or triples
			elseif(is_array($json)){
				if(count($json[0]) == 3){
					$format = "triples";
				}
				elseif(count($json[0]) == 4){
					$format = "quads";
				}
			}
			else {
				return $this->failure_result("Failed to interpret json format", 400);
			}
		}
		if($format == "quads"){
			$this->importFromQuads($json, $srvr);
		}
		elseif($format == "json"){
			$this->importFromDacuraJSON($json, $srvr);
		}
		elseif($format == "triples"){
			$this->importFromTriples($json);
		}
		elseif($format == "jsonld"){
			require_once("JSONLD.php");
			$this->ldprops = fromJSONLD($json, $rules);
			if(isset($this->ldprops['multigraph'])){
				$this->multigraph = $this->ldprops['multigraph'];
				unset($this->ldprops['multigraph']);
			}
		}
		else {
			return $this->failure_result("No method available to import content in format $format", 400);
		}
		return $format;
	}
	
	function importFromDacuraJSON($json, $srvr = false){
		$this->ldprops = $json;
	}
	
	function importFromTriples($triples){
		$this->ldprops = getPropsFromTriples($triples);
	}
	
	function importFromQuads($quads, $srvr){
		$this->ldprops = getPropsFromQuads($quads);
		if(count(array_keys($this->ldprops)) == 1){
			$vals = array_values($this->ldprops);
			$this->ldprops = $vals[0];
		}
		else {
			$this->multigraph = true;
		}
	}
	
	function importContentsfromNQuads($contents, $rules){
		require_once("JSONLD.php");
		$this->ldprops = fromNQuads($contents, $rules);
		if(!$this->ldprops){
			return false;
		}
		if(isset($this->ldprops['multigraph'])){
			$this->multigraph = $this->ldprops['multigraph'];
			unset($this->ldprops['multigraph']);
		}
		return true;
	}
		
	function importERDF($source, $arg, $rules, $format = ""){
		if(!($graph = $this->importERDFGraph($source, $arg, $format))){
			return false;			
		}
		$op = $graph->serialise("php");
		if($op){
			if($this->ldprops = importEasyRDFPHP($op)){
				return ($format ? $format : "json");
			}
			else {
				return $this->failure_result("Failed to import data - format error ($format)", 400);
			}
		}
		else {
			return $this->failure_result("Graph failed to serialise php structure.", 500);
		}
	}
	
	/**
	 * Imports a data structure from Easy RDF internal format
	 * @param string $source the type of import source (url, text, file)
	 * @param string $arg the id of the object or the full text of the object
	 * @param string $gurl the id of the graph
	 * @param string $format the format of the source (must be a supported type of easy rdf)
	 * @return EasyRdf_Graph|boolean an easy rdf graph object containing the imported rdf, or false on failure
	 */
	function importERDFGraph($source, $arg, $format = false){
		try {
			if($source == "url"){
				$graph = EasyRdf_Graph::newAndLoad($arg, $format, $this->id);
			}
			elseif($source == "text"){
				$graph = new EasyRdf_Graph($this->durl(), $arg, $format, $this->id);
			}
			elseif($source == "file"){
				$graph = new EasyRdf_Graph($this->durl());
				$graph->genid = $this->id;//sets a unique prefix for the blank node ids generated by easy rdf
				$graph->parseFile($arg, $format);//the slow one
				$this->logger && $this->logger->timeEvent("Parse Graph File", "debug");
			}
			if(!$graph || $graph->isEmpty()){
				return $this->failure_result("Graph loaded from $source was empty.", 500);
			}
			return $graph;
		}
		catch(Exception $e){
			return $this->failure_result("Failed to load graph from $source. ".$e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Generate a list of all the namespaces / prefixes supported by the library
	 * @return array namespace list
	 */
	function getERDFSupportedNamespaces(){
		return EasyRdf_Namespace::namespaces();
	}
	
	function validate($rules, &$srvr){
		if(!$this->validateMeta($rules, $srvr)){
			return false;
		}
		return $this->validateLD($rules, $srvr);
	}
	
	/**
	 * Validate Meta is called before an object is accepted into the linked data object store
	 * 
	 * It should only ever catch errors that are catastrophic to the 
	 * structure / function of the object and should never be stored
	 * 
	 * In general, we want to catch validation errors at the graph / dqs analysis stage because when objects fail those tests, 
	 * they can still be saved in the linked data object store and iteratively updated. 
	 * @param array $rules configuration settings relevant to meta data validation
	 * @return boolean true if valid
	 */
	function validateMeta($rules, &$srvr){
		if(isset($rules['allow_arbitrary_metadata']) && $rules['allow_arbitrary_metadata']){
			return true;
		}
		else {
			$vprops = $this->getValidMetaProperties();
			foreach($this->meta as $k => $v){
				if(!in_array($k, $vprops)){
					return $this->failure_result("Meta Property $k is not a valid metadata property", 400);	
				}				
			}
		}
		return true;
	}
	
	/**
	 * ValidateLD is called before an object is accepted into the linked data object store to validate 
	 * the basic structure of the linked data contents
	 * 
	 * It should only ever catch errors that are catastrophic to the 
	 * structure / function of the object and should never be stored
	 * 
	 * In general, we want to catch validation errors at the graph / dqs analysis stage because when objects fail those tests, 
	 * they can still be saved in the linked data object store and iteratively updated. 
	 * @param array $rules configuration settings relevant to linked data validation
	 * @return boolean true if valid
	 */
	function validateLD($rules, &$srvr){
		if($this->is_multigraph()){
			foreach($this->ldprops as $gid => $props){
				if(!$this->validateLDProps($props, $rules, $srvr)){
					$this->errmsg = "Failed validation for graph $gid ".$this->errmsg;
					return false;
				}
			}
		}
		else {
			return $this->validateLDProps($this->ldprops, $rules, $srvr);
		}
		return true;
	}
	
	
	function getValidMetaProperties(){
		return array("status", "title", "url", "image");
	}
	
	/**
	 * Validates the structure of a new ld object upon importation
	 * @param array $rules a settings array
	 * @param array|boolean [$obj] the object in question default is $this->ldprops
	 * @return boolean true if the object is valid according to the rules
	 */
	function validateLDProps($props, $rules, &$srvr){
		if(!is_array($props)){
			return $this->failure_result("Input $props is not an array object", 500);
		}
		foreach($props as $s => $obj){
			if(!$this->validateLDSubject($s, $rules, $srvr)) return false;
			if(!$this->validateLDObject($obj, $rules, $srvr)){
				$this->errmsg = "object with node id $s has errors: ".$this->errmsg;
				return false;
			} 
		}
		if(isset($rules['unique_subject_ids']) && $rules['unique_subject_ids']){
			if(!$this->subjectIDsUnique()){
				$this->errmsg = "node ids are not unique ".$this->errmsg;
				return false;
			}
		}
		return true;
	}
	
	function validateLDObject($obj, $rules, $srvr){
		if(!isAssoc($obj)){
			if(!(isset($rules['allow_invalid_ld']) && $rules['allow_invalid_ld'])){
				$decl = is_string($obj) ? htmlspecialchars($obj) : " is simple array";
				return $this->failure_result("linked data input structure is broken: $decl value - should be json object {property: value})", 400);				
			}
		}
		else {
			foreach($obj as $p => $v){
				if(!$this->validateLDPredicate($p, $rules, $srvr)){
					return false;
				}
				if(!$this->validateLDValue($v, $rules, $srvr)){
					$this->errmsg = "Predicate $p ".$this->errmsg;
					return false;						
				}
			}
		}
		return true;
	} 
	
	function validateLDSubject($s, $rules, $srvr){
		if(isBlankNode($s)){
			if(isset($rules['forbid_blank_nodes']) && $rules['forbid_blank_nodes']){
				return $this->failure_result("Linked data input structure contains forbidden blank node $s", 400);				
			}
		}
		else {
			if(isset($rules['require_blank_nodes']) && $rules['require_blank_nodes']){
				return $this->failure_result("Linked data input structure contains embedded ids that are not blank nodes - forbidden", 400);				
			}
			if(isNamespacedURL($s) && isset($rules['forbid_unknown_prefixes']) && $rules['forbid_unknown_prefixes']){
				return $this->failure_result("Linked data input structure contains url $s that includes unknown prefixes", 400);						
			}
			if(!isURL($s) && isset($rules['require_subject_urls']) && $rules['require_subject_urls']){
				return $this->failure_result("Linked data input structure contains subject $s that is not a url", 400);						
			}
		}
		return true;
	}
		
	function validateLDPredicate($p, $rules, $srvr){
		if(isBlankNode($p)){
			if(!(isset($rules['allow_blanknode_predicates']) && $rules['allow_blanknode_predicates'])){
				return $this->failure_result("Linked data input structure contains forbidden predicate blank node $p", 400);
			}
		}
		else {
			if(isNamespacedURL($p) && isset($rules['forbid_unknown_prefixes']) && $rules['forbid_unknown_prefixes']){
				return $this->failure_result("Linked data input structure contains predicate url $p that includes unknown prefixes", 400);
			}
			if(!isURL($p) && isset($rules['require_predicate_urls']) && $rules['require_predicate_urls']){
				return $this->failure_result("Linked data input structure contains predicate $p that is not a url", 400);
			}				
		}
		return true;
	}
	
	function validateLDValue($obj, $rules, $srvr){
		$pv = new LDPropertyValue($obj, $this->cwurl);
		if($pv->illegal($rules)) {
			return $this->failure_result("Illegal JSON LD object structure ".$pv->errmsg, $pv->errcode);
		}
		if($pv->embeddedlist()){
			return $this->validateLDProps($obj, $rules, $srvr);
		}
		elseif($pv->embedded()){
			return $this->validateLDObject($obj, $rules, $srvr);
		}
		elseif($pv->objectlist()){
			foreach($obj as $emb){
				if(!$this->$this->validateLDObject($emb, $rules, $srvr)){
					return false;
				}
			}
		}
		return true;
	}
	
	function analyse(){
		$this->analysis = "something something";
	}
	
	/**, $srvr
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
	
	function cid(){
		return $this->cid;
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
		if($this->is_multigraph()){
			foreach($this->ldprops as $gid => $gprops){
				$res = $this->nsres->expandNamespaces($this->ldprops[$gid], $this->cwurl);
			}
		}
		else {
			$res = $this->nsres->expandNamespaces($this->ldprops, $this->cwurl);
		}
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
		return $this->nsres->getNamespaces($this->ldprops, $this->cwurl, $this->compressed);
	}
	
	function makeBNsAddressable(){
		makeBNIDsAddressable($this->ldprops, $this->cwurl);
		$this->addressable_bnids = true;
	}
	
	function getPredicateValues($fid, $pred_url, $pred_ns = false){
		if($this->compressed){
			$this->expandNS();
		}
		if($pred_ns){
			if(!($pred_url = $this->nsres->expand($pred_ns.":".$pred_url))){
				return $this->failure_result("Failed to expand namespace ".htmlspecialchars($pred_ns), 400);
			}
		}
		if(!($frag = $this->getFragment($fid))){
			return $this->failure_result("Fragment id ".htmlspecialchars($fid)." not found", 400);				
		}
		if(isset($frag[$pred_url])){
			return $frag[$pred_url];
		}
		return $this->failure_result("Predicate ".htmlspecialchars($pred_url)." not found", 400);
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

	function loadFragment($fragid){
		if($this->addressable_bnids){
			$fid = $this->cwurl."/".$fragid;
		}
		else {
			$fid = "_:".$fragid;
		}
		$frag = $this->getFragment($fid);
		$this->fragment_path = $this->getFragmentPath($fid);
		$this->fragment_id = $fragid;
		$this->ldprops = array($fid => $frag);
		if($vals = $this->getPredicateValues($fid, "type", "rdf")){
			$this->meta['types'] = $vals;
		}
		return true;
	}
	

	/**
	 * Does the object contain a fragment with the given id?
	 * @param string $frag_id the object's fragment id
	 * @return boolean true if the fragment with the given id exists in the object
	 */
	function hasFragment($frag_id){
		if($this->index === false){
			$this->buildIndex();
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
		if(!isset($this->index[$fid])){
			return false;
		}
		$frag = array();
		foreach($this->index[$fid] as $i => $ldobj){
			$frag = array_merge($frag, $ldobj);
		}
		return $frag;
	}
	

	/**
	 * Returns the object embedding paths to the fragments with subjects of the passed id
	 * @param string $fid the fragment id to find
	 * @param array $rules rules governing the resolution of paths
	 * @return array<array> an array of paths to the object in question
	 */
	function getFragmentPath($fid){
		$path = getFragmentContext($fid, $this->ldprops, $this->cwurl);
		return $path;
	}
	
	function is_empty(){
		return count($this->ldprops) == 0;
	}
	
	/**
	 * Set the contents of this object to be a fragment of itself.
	 * @param unknown $fragment_id
	 */
	function setContentsToFragment($fragment_id){
		//$this->ldprops = getFragmentInContext($fragment_id, $this->ldprops, $this->rules);
	}
	
	/**
	 * Builds an index of the object id => [values]
	 */
	function buildIndex(){
		$this->index = array();
		indexLDProps($this->ldprops, $this->index, $this->cwurl);
	}
	
	/**
	 * Does the obect have unique ids for internal subjects (i.e. do node ids appear only once as a subject)
	 * @return boolean
	 */
	function subjectIDsUnique(){
		if($this->index === false){
			$this->buildIndex();
		}
		foreach($this->index as $nid => $vals){
			if(count($vals) != 1){
				return $this->failure_result("node $nid appears ".count($vals) ." times", 400);				
			}
		}
		return true;
	}
	
	/**
	 * Exports from the local ld format into an external format
	 * @param string $format the desired output format
	 * @param array $nsobj a ns resolver object to create the list of namespaces with
	 * @return boolean|string the serialised export of the object
	 */
	function export($format, $nsobj = false){
		if($this->is_multigraph()){
			$easy = array();
			foreach($this->ldprops as $gid => $gprops){
				$neasy = exportEasyRDFPHP($gprops, $this->cwurl);
				foreach($neasy as $s => $eldo){
					if(!isset($easy[$s])){
						$easy[$s] = $eldo;
					}
					else {
						foreach($eldo as $p => $v){
							if(!isset($easy[$s][$p])){
								$easy[$s][$p] = $v;
							}
							elseif(is_array($easy[$s][$p])){
								$easy[$s][$p] += $v;
							}
							else {
								$easy[$s][$p] = array($easy[$s][$p]);
								$easy[$s][$p] += $v;
							}
						}
					}
				}
			}	
		}
		else {
			$easy = exportEasyRDFPHP($this->ldprops, $this->cwurl);
		}
		try{
			foreach($this->nsres->prefixes as $id => $url){
				EasyRdf_Namespace::set($id, $url);
			}
	
			$graph = new EasyRdf_Graph($this->cwurl, $easy, "php", $this->id);
			if($graph->isEmpty()){
				return "";//return $this->failure_result("exported graph was empty.", 400);
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
	 * Is the given format natively supported by dacura
	 * @param string $format the format id
	 * @return boolean true if natively supported
	 */
	static function isNativeFormat($format){
		return $format == "" or in_array($format, LDO::$native_formats);
	}
	
	function getContentInFormat($format, $options, $srvr = null, $for = "internal"){
		if(isset($options['addressable']) && $options['addressable']) {
			$this->makeBNsAddressable();
		}
		if($this->isNativeFormat($format)){
			if(isset($options['ns']) && $options['ns']) {
				$this->compressNS();
			}
		}
		if($for == "display"){
			if($this->display($format, $options, $srvr)){
				return $this->display;
			}
			//return $this->display;
			return false;
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
			require_once("JSONLD.php");				
			$payload = toJSONLD($this->ldprops, $this->getNS(), array("cwurl" => $this->cwurl));
		}
		elseif($format == "nquads"){
			$payload = $this->nQuads();
		}
		else {
			$exported = $this->export($format);
			if(!$exported){
				return false;
			}
			$payload = $exported;
		}
		if($for == "api" && $format != "html" && $format != "nquads" && $this->isNativeFormat($format)){
			$payload = json_encode($payload);
		}
		return $payload;
	}
	
	function display($format, $options, $srvr){
		$lddisp = new LDODisplay($this->id, $this->cwurl);
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
			require_once("JSONLD.php");
			$jsonld = toJSONLD($this->ldprops, $this->getNS(), array("cwurl" => $this->cwurl));
			$this->display = $lddisp->displayJSONLD($jsonld, $options);
		}
		elseif($format == "nquads"){
			$payload = $this->nQuads();
			$this->display = $lddisp->displayNQuads($payload, $options);
		}
		else {
			$exported = $this->export($format);
			if($exported === false){
				return false;
			}
			$this->display = $lddisp->displayExport($exported, $format, $options);
		}
		return true;
	}
	
	/**
	 * Return an array of triples as a representation of this ld property array - with literals typed
	 */
	function typedTriples(){
		$triples = array();
		foreach($this->ldprops as $sid => $props){
			$triples = array_merge($triples, getObjectAsTypedTriples($sid, $props, $this->rules));
		}
		return $triples;
	}
	
	/**
	 * Return an array of triples as a representation of this ld property array - with untyped literals
	 */
	function triples(){
		$triples = array();
		foreach($this->ldprops as $sid => $props){
			$triples = array_merge($triples, getObjectAsTriples($sid, $props, $this->rules));
		}
		return $triples;
	}
	
	function typedQuads($graphid = false){
		$graphid = $graphid ? $graphid : $this->cwurl;
		return getPropsAsTypedQuads($graphid, $this->ldprops, $this->rules);
	}
	
	function quads($graphid = false){
		$graphid = $graphid ? $graphid : $this->cwurl;		
		return getPropsAsQuads($graphid, $this->ldprops, $this->rules);
	}
	
	function nQuads($graphid = false){
		$graphid = $graphid ? $graphid : $this->cwurl;
		require_once("JSONLD.php");
		//function assumes that properties are indexed by graph id
		return toNQuads(array($graphid => $this->ldprops), array("cwurl" => $this->cwurl));
	}
	
	/**
	 * Return a view of the object for sending to the api (turns off lots of stuff)
	 */
	function forAPI($format, $opts){
		$meta = deepArrCopy($this->meta);
		$meta = array_merge($this->getPropertiesAsArray(), $meta);
		$apirep = array(
				"id" => $this->id, 
				"ldtype" => $this->ldtype, 
				"meta" => $meta, 
				"contents" => $this->display, 
				"format" => $format, 
				"options" => $opts
		);
		if($this->fragment_id){
			$apirep['fragment_id'] = $this->fragment_id;
			$apirep['fragment_path'] = $this->fragment_path;
		}
		if(isset($opts['history']) && $opts['history'] && isset($this->history) && $this->history) {
			$apirep["history"] = $this->history;
		}
		if(isset($opts['updates']) && $opts['updates'] && isset($this->updates) && $this->updates){
			$apirep["updates"] = $this->updates;
		}
		if(isset($opts['analysis']) && $opts['analysis'] && isset($this->analysis) && $this->analysis){
			$apirep["analysis"] = $this->analysis;
		}
		return $apirep;
	}	
	
	/**
	 * Some state is duplicated between the meta ld field and the object properties
	 * In such cases the meta field is authoritative (as it is part of state-management)
	 */
	function readStateFromMeta(){
		$this->status = $this->meta['status'];
	}
		
	/**
	 * Calculates the transforms necessary to get to current from other
	 * 
	 * @param LDO $other the object to be compared to this one
	 * @return LDDelta
	 */
	function compare(LDO $other, $rules = array()){
		if($this->is_multigraph()){
			$cdelta = compareLDGraphs($this->id, $this->ldprops, $other->ldprops, $rules, true);
		}
		else {
			$cdelta = compareLDGraph($this->ldprops, $other->ldprops, $rules, false);
		}
		$ndd = compareLD($this->id, $this->meta, $other->meta, $rules, "meta");
		if($ndd->containsChanges()){
			$cdelta->addNamedGraphDelta($ndd, "meta");
		}
		//if($cdelta->containsChanges()){
		//	$cdelta->setMissingLinks($this->missingLinks(), $other->missingLinks());
		//}
		//opr($cdelta);
		return $cdelta;
	}
	
	/**
	 * Updates a ld property array according to the passed update object
	 * @param array $update_obj ld update object
	 * @param array $rules rules that will apply to thos update
	 * @return boolean
	 */
	function update($update_obj, $rules = array(), $force_multi = false){
		if(isset($update_obj['meta'])){
			if(!$this->updateJSON($update_obj['meta'], $this->meta, $this->idmap, $rules)){
				return false;
			}
			unset($update_obj['meta']);				
		}
		if($this->is_multigraph() or $force_multi){
			foreach($update_obj as $gid => $gprops){
				if(is_array($gprops) && count($gprops) == 0){
					if(isset($this->ldprops[$gid])){
						unset($this->ldprops[$gid]);
					}
					elseif(isset($rules['fail_on_bad_deletes']) && $rules['fail_on_bad_deletes']) {
						return $this->failure_result("Attempted to remove non-existant property $k", 404);
					}
				}
				elseif(isAssoc($gprops)){
					if(!isset($this->ldprops[$gid])){
						$this->ldprops[$gid] = $gprops;
					}
					else {
						if(!$this->updateLDProps($gprops, $this->ldprops[$gid], $this->idmap, $rules)){
							return false;
						}
					}
				}
			}
		}
		else {
			if(count($update_obj) > 0){
				if(!$this->updateLDProps($update_obj, $this->ldprops, $this->idmap, $rules)){
					return false;
				}
			}				
		}
		if(count($this->idmap) > 0){
			$this->ldprops = updateLDReferences($this->ldprops, $this->idmap, $rules, $this->is_multigraph());			
		}
		$this->buildIndex();
		return true;
	}
	
	function updateJSON($umeta, &$dmeta, $rules){
		if(isAssoc($umeta)){
			if(!is_array($dmeta)){
				$dmeta = array();
			}
			foreach($umeta as $k => $v){
				if(is_array($v) && count($v) == 0){
					if(isset($dmeta[$k])){
						unset($dmeta[$k]);
					}
					elseif(isset($rules['fail_on_bad_deletes']) && $rules['fail_on_bad_deletes']) {
						return $this->failure_result("Attempted to remove non-existant property $k", 404);
					}						
				}
				elseif(isAssoc($v)){
					$this->updateJSON($v, $dmeta[$k], $rules);					
				} 
				else {
					$dmeta[$k] = $v;
				}				
			}
		}
		return true;
	}
		
	function updateLDProps($uprops, &$ldprops, &$idmap, $rules){
		foreach($uprops as $subj => $ldo){
			if(!is_array($ldprops)){
				$ldprops = array();
			}
			if(is_array($ldo) && count($ldo) == 0){
				if(isset($ldprops[$subj])){
					unset($ldprops[$subj]);
				}
				elseif(isset($rules['fail_on_bad_deletes']) && $rules['fail_on_bad_deletes']) {
					return $this->failure_result("Attempted to remove non-existant subject $subj", 404);
				}
			}
			elseif(isAssoc($ldo)){
				if(isBlankNode($subj)){
					$new_id = getNewBNIDForLDObj($ldo, $idmap, $rules, $subj);
					$ldprops[$new_id] = $ldo;
					if($subj != $new_id){
						unset($ldprops[$subj]);
					}
					$subj = $new_id;
				}
				if(!$this->updateLDO($ldo, $ldprops[$subj], $idmap, $rules)){
					return false;
				}			
			}
		}
		return true;	
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
	function updateLDO($uprops, &$dprops, &$idmap, $rules){
		foreach($uprops as $prop => $v){
			//if(!is_array($dprops)){
			//	$dprops = array();
			//}
			$pv = new LDPropertyValue($v, $this->cwurl);
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
				$rep = importLD($v, $rules);
				if($rep === false){
					return $this->failure_result("Failed to import linked data structure", 400);
				}
				$idmap = array_merge($idmap, $rep);
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
					if(!$this->updateLDO($uprops[$prop][$uid], $dprops[$prop][$uid], $idmap, $rules)){
						return false;
					}
					if(isset($dprops[$prop][$uid]) && is_array($dprops[$prop][$uid]) and count($dprops[$prop][$uid]) == 0){
						unset($dprops[$prop][$uid]);
					}
				}
			}
			elseif($pv->complex()){
				if(!$this->updateJSON($uprops[$prop], $dprops[$prop], $idmap, $rules)){
					return false;
				}						
			}
			else {
				opr($pv);
				return $this->failure_result("Unknown value type in LD structure", 500);
			}
			if(isset($dprops[$prop]) && is_array($dprops[$prop]) && count($dprops[$prop])==0) {
				unset($dprops[$prop]);
			}
		}
		return true;
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
		$missing = array();
		if($this->is_multigraph()){
			foreach($this->ldprops as $gid => $ldprops){
				foreach($ldprops as $s => $ldo){
					$missing = array_merge($missing, findInternalMissingLinks($ldo, array_keys($this->index), $this->id, $rules));
				}
			}
		}
		else {
			foreach($this->ldprops as $s => $ldo){
				$missing = array_merge($missing, findInternalMissingLinks($ldo, array_keys($this->index), $this->id, $rules));
			}				
		}
		$this->bad_links = $missing;
		return $missing;
	}
	
	/**
	 * Returns true if the object complies with its rules
	 */
	function compliant($rules = array()){
		$errs = validLDProps($this->ldprops, $rules);
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