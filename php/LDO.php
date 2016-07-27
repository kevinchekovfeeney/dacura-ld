<?php
include_once("phplib/libs/easyrdf-0.9.0/lib/EasyRdf.php");
include_once("LDUtils.php");
include_once("LDODisplay.php");
include_once("LDRules.php");
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
			"html" => "HTML",
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
	 * Sets up the ld rules object of the ldo - used for linked data validation and tranformations
	 * @param LdDacuraServer $srvr - server for config access
	 */
	function setLDRules(LdDacuraServer &$srvr){
		$this->rules = new LDRules();
		$this->rules->init($this, $srvr);
	}
	
	/**
	 * Evaluates a linked data rule for this ldo
	 * @param string $mode - the mode (create, replace, update, delete, view, rollback}
	 * @param string $action - the action (import, update, view, validate ??)
	 * @param string $rule name of the rule
	 * @return boolean|mixed the contents of the rule or false if not specified
	 */
	function rule($mode, $action, $rule){
		$obj = false;
		if($this->fragment_id){
			$obj = "fragment";
		}
		elseif($mode == "update" && $action == "import"){
			$obj = "update";				
		}
		return $this->rules->getRule($mode, $action, $rule, $obj);
	}
	
	/**
	 * Returns a particular section of rules for a specific type of action
	 * @param string $mode the mode
	 * @param string $action - generate, ldvalidate, ...
	 * @return array the rules array
	 */
	function rules($mode, $action){
		$obj = false;
		if($this->fragment_id){
			$obj = "fragment";
		}
		return $this->rules->rulesFor($mode, $action);
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
	 * Returns the linked data type of the object (ontology, graph, candidate, ldo)
	 * @return string the typename
	 */
	function ldtype(){
		if($this->ldtype) return $this->ldtype;
		return strtolower(get_class($this));
	}
	
	/**
	 * Copies the basic information from another object to this one
	 * 
	 * @param LDObject $other - the object from which the information will be copied. 
	 */
	function copyBasics(&$other){
		$this->cid = $other->cid;
		$this->ldtype = $other->ldtype();
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
		if($this->cwurl) $props['cwurl'] = $this->cwurl;
		if($this->ldtype) $props['ldtype'] = $this->ldtype;
		if($this->version) $props['version'] = $this->version;
		if($this->created) $props['created'] = $this->created;
		if($this->modified) $props['modified'] = $this->modified;
		if($this->latest_status) $props['latest_status'] = $this->latest_status;
		if($this->latest_version) $props['latest_version'] = $this->latest_version;
		if($this->version_replaced) $props['version_replaced'] = $this->version_replaced;
		if($this->version_created) $props['version_created'] = $this->version_created;
		if($this->compressed) $props['compressed'] = $this->compressed;
		if($this->addressable_bnids) $props['addressable_bnids'] = $this->addressable_bnids;
		return $props;
	}
	
	/**
	 * A list of the standard properties that are produced by the system in the object's meta-data
	 * @return array<string> the property names
	 */
	function getStandardProperties(){
		return array('cid', "cwurl", 'ldtype', 'version', 'created', 'modified', 
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
		/* object properties are copied into metadata array of object - the status value in meta has precedence, the db row values are just indeces for sql queries */
		if(!isset($this->meta['status'])){
			$this->status = $row['status'];
			$this->meta['status'] = $this->status;
		}
		else {
			$this->status = $this->meta['status'];
		}
		$this->ldtype = $row['type'];
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
	function deserialise(LdDacuraServer &$srvr){
		if($srvr->graphs) {
			$this->graphs =& $srvr->graphs;
		}
	}
	
	/**
	 * Builds an index of the object id => [values]
	 */
	function buildIndex(){
		$this->index = array();
		if($this->ldprops){
			indexLDProps($this->ldprops, $this->index, $this->cwurl);
		}
	}
	
	/**
	 * Loads a new object from the api 
	 * @param array $obj the object as received by the api
	 * @param string $format one of LDO::$valid_input_formats
	 * @param array $options name value array of properties and their values
	 * @param LdDacuraServer $srvr the currently active linked data server
	 * @param string $mode the mode in which the object is being loaded from the api (replace, update, create)
	 * @return string representing the format that the object is in
	 */
	function loadNewObjectFromAPI($obj, $format, $options, LdDacuraServer &$srvr, $mode){
		if(!$srvr->APIObjectIncludesContents($obj) && !isset($obj['meta'])){
			return $this->failure_result("Object sento to API was malformed : both meta and contents are missing", 400);
		}
		$this->version = 1;
		$this->meta = array();
		if(isset($obj['meta']) && $obj['meta']){
			$ignore_meta = $this->getStandardProperties();
			foreach($obj['meta'] as $k => $v){
				if(!in_array($k, $ignore_meta)){
					$this->meta[$k] = $v;
				}
				if($k == "status"){
					$this->status = $v;
				}	
			}
		}
		if(isset($obj['contents']) && $obj['contents']){
			$format = $this->import("text", $obj['contents'], $format);
		}
		elseif(isset($obj['ldurl']) && $obj['ldurl']){
			$format = $this->import("url", $obj['ldurl'], $format);						
		}
		elseif(isset($obj['ldfile']) && $obj['ldfile']){
			$format = $this->import("file", $obj['ldfile'], $format);
		}
		else {
			$format = "json";
			$this->ldprops = array();
		}
		if($format === false){
			return false;
		}
		if($this->rule($mode, "import", 'transform_import') && !$this->importLD($mode, $srvr)){//expands structure by generating blank node ids, etc			
			return false;
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
	 * Just a shim to enable ld server to work with multigraph objects
	 * @param array $upd the update obect
	 * @return boolean
	 */
	function isMultigraphUpdate($upd){
		return false;
	}
	
	/**
	 * Returns the url of the default graph associated with the linked data object
	 * @return unknown|boolean
	 */
	function getDefaultGraphURL(){
		if(isset($this->graphs["main"])){
			$ig = $this->graphs['main']->instanceGname();
			return $ig;
		}
		return false;
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
		return $this->id."D".++$i;
	}
		
	/**
	 * imports a graph from easy rdf into our object
	 * @param string $source the import source: text, file, url
	 * @param string $arg either the filename (if file) the url (if url) or the text itself (if text)
	 * @param string $format the format of the source
	 * @return string the format that was loaded (to support format detection)
	 */
	function import($source, $arg, $format = false){
		global $dacura_server;
		if($source == "file"){
			if(!file_exists($arg)){
				return $this->failure_result("File ".htmlspecialchars($arg)." does not exist", 400);				
			}
			if(!$contents = file_get_contents($arg)){
				return $this->failure_result("Failed to load file ".htmlspecialchars($arg), 500);
			}				
		}
		elseif($source == "url"){
			if(!($contents = $dacura_server->fileman->fetchFileFromURL($arg))){
				return $this->failure_result($dacura_server->fileman->errmsg, $dacura_server->fileman->errcode);
			}
		}
		else {
			$contents = $arg;
		}
		return $this->importContents($contents, $format);
	}
	
	/**
	 * imports a graph from a string into our object via easyrdf
	 * @param string $contents text to be imported 
	 * @param string $format the format of the source
	 * @return string the format that was loaded (to support format detection)
	 */
	function importContents($contents, $format){
		if(!$format){
			$format = $this->importContentsFromJSON($contents, false);
			if(!$format){
				$format = $this->importERDF("text", $contents);
				if(!$format && $this->importContentsfromNQuads($contents, $this->getDefaultGraphURL())){
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
				if(!$this->importContentsfromNQuads($contents, $this->getDefaultGraphURL())){
					return $this->failure_result("Failed to import nquads", 400);
				}
			}
			else {
				if(!$this->importContentsFromJSON($contents, $format)){
					return false;
				}				
			}
		}
		else {
			if($format != $this->importERDF("text", $contents, $format)){
				return false;
			}				
		}
		$this->expandNS();//deal with full urls
		return $format;		
	}
	
	/**
	 * imports a graph from a json object of several flavours into one of our objects
	 * @param array $json json array
	 * @param string $format the format of the source
	 * @return string the format that was loaded (to support format detection)
	 */
	function importContentsFromJSON($json, $format = false){
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
			$this->importFromQuads($json, $this->getDefaultGraphURL());
		}
		elseif($format == "json"){
			$this->importFromDacuraJSON($json, $this->getDefaultGraphURL(), $this->getValidGraphURLs());
		}
		elseif($format == "triples"){
			$this->importFromTriples($json);
		}
		elseif($format == "jsonld"){
			$this->importFromJSONLD($json, $this->getDefaultGraphURL());
		}
		else {
			return $this->failure_result("No method available to import content in format $format", 400);
		}
		return $format;
	}
	
	/**
	 * Called to import a dacura ldo directly from dacura json format
	 * @param array $json dacura formated ldo
	 * @param string $default_graph url of the default graph
	 * @param array $graph_urls the graph urls available to this object
	 */
	function importFromDacuraJSON($json, $default_graph, $graph_urls = false){
		$this->ldprops = $json;
	}
	
	/**
	 * Import from array of triples
	 * @param $triples array an array of triples to be imported
	 */
	function importFromTriples($triples){
		$this->ldprops = getPropsFromTriples($triples);
	}

	/**
	 * Import from quads - into the default graph specified
	 * @param unknown $quads
	 * @param unknown $default_graph
	 */
	function importFromQuads($quads, $default_graph){
		$this->ldprops = getPropsFromQuads($quads);
	}
	
	/**
	 * Import from json ld
	 * @param array $json json ld array
	 * @param string $default_graph array of default graph
	 */
	function importFromJSONLD($json, $default_graph){
		require_once("JSONLD.php");
		$this->ldprops = fromJSONLD($json);
	}
	
	/**
	 * Import from nquads
	 * @param string $contents nquads as string
	 * @param string $def_graph - url of default graph (only used in multi-graph - just here for compatibility)
	 */
	function importContentsfromNQuads($contents, $def_graph){
		require_once("JSONLD.php");
		$this->ldprops = fromNQuads($contents);
	}

	/**
	 * Import from easy rdf 
	 * @param string $source the source of the data
	 * @param unknown $arg argument to import
	 * @param string $format the format to import from 
	 * @return boolean|string - format imported
	 */
	function importERDF($source, $arg, $format = ""){
		if(!($graph = $this->importERDFGraph($source, $arg, $format))){
			return false;			
		}
		$op = $graph->serialise("php");
		if($op){
			if($this->ldprops = importEasyRDFPHP($op)){
				//reembedBNodes($this->ldprops, $this->cwurl);
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
	
	/**
	 * Imports linked data in ldprops into the proper internal format
	 * @param string $mode update | replace | create
	 * @param LdDacuraServer $srvr server object 
	 * @return boolean
	 */
	function importLD($mode, LdDacuraServer &$srvr){
		if($mode == "update"){
			$this->idmap = importLDUpdate($this->ldprops, $this->rules($mode, "import"), $this->is_multigraph());//expands structure by generating blank node ids, etc
		}
		else {
			$this->idmap = importLD($this->ldprops, $this->rules($mode, "import"), $this->is_multigraph());//expands structure by generating blank node ids, etc	
		}
		return true;
	}
	
	/**
	 * Validates linked data and meta contents
	 * @param string $mode
	 * @param LdDacuraServer $srvr
	 * @return boolean
	 */
	function validate($mode, LdDacuraServer &$srvr){
		if(!(($mode == "update" || $mode == "replace") && !$this->meta) && !$this->validateMeta($mode, $srvr)){
			return false;
		}
		if(!$this->fragment_id && !(($mode == "update" || $mode == "replace") && !$this->ldprops) && !$this->validateLD($mode, $srvr)){
			return false;
		}
		return true;
	}
	
	/**
	 * Validate Meta is called before an object is accepted into the linked data object store
	 * 
	 * In general, we want to catch validation errors at the graph / dqs analysis stage because when objects fail those tests, 
	 * they can still be saved in the linked data object store and iteratively updated. 
	 * @param string $mode - the mode - replace, update, create, etc
	 * @param LdDacuraServer $srvr
	 * @return boolean true if valid
	 */
	function validateMeta($mode, LdDacuraServer &$srvr){
		if($this->rule($mode, "validate", 'allow_arbitrary_metadata')){
			return true;
		}
		if($allowed = $this->rule($mode, "validate", 'allowed_meta_properties') ) {
			foreach($this->meta as $k => $v){
				if(!in_array($k, $allowed)){
					return $this->failure_result("Meta Property $k is not a valid metadata property", 400);
				}
			}
		}
		if($required = $this->rule($mode, "validate", 'required_meta_properties') ) {
			foreach($required as $k){
				if(!isset($this->meta[$k])){
					return $this->failure_result("Missing required property $k from metadata", 400);						
				}
			}		
		}
		return true;
	}
	
	/**
	 * ValidateLD is called before an object is accepted into the linked data object store to validate 
	 * the basic structure of the linked data contents
	 * 
	 * In general, we want to catch validation errors at the graph / dqs analysis stage because when objects fail those tests, 
	 * they can still be saved in the linked data object store and iteratively updated. This is for basic structural soundness
	 * @param string $mode - the mode - replace, update, create, etc
	 * @param LdDacuraServer $srvr
	 * @return boolean true if valid
	 */
	function validateLD($mode, &$srvr){
		if(!$x = $this->validateLDProps($this->ldprops, $mode, $srvr)){
			return false;				
		}
		return true;
	}
	
	/**
	 * The list of valid meta properties that will be accepted by the api
	 * @return array<string> the list of the meta data types that can be set in the api
	 */
	function getValidMetaProperties(){
		return array("status", "title", "url", "image");
	}
	
	/**
	 * Validates the structure of an ld object in various modes
	 * @param array $obj the ld props array to be validated
	 * @param string $mode the mode in which validation is takiing place : rollback, import,...
	 * @param LdDacuraServer $srvr
	 * @return boolean true if the object is valid according to the rules
	 */
	function validateLDProps($props, $mode, LdDacuraServer &$srvr){
		if(!is_array($props)){
			return $this->failure_result("Input $props is not an array object", 500);
		}
		foreach($props as $s => $obj){
			if(!$this->validateLDSubject($s, $mode, $srvr)) return false;
			if(!$this->validateLDObject($obj, $mode, $srvr)){
				$this->errmsg = "object with node id $s has errors: ".$this->errmsg;
				return false;
			} 
		}
		if($this->rule($mode, "validate", 'unique_subject_ids')){
			if(!$this->subjectIDsUnique()){
				$this->errmsg = "node ids are not unique ".$this->errmsg;
				return false;
			}				
		}
		return true;
	}
	
	/**
	 * validates the structure of an ldo {property:object} array
	 * @param array $obj the ldo array to be validated
	 * @param string $mode the mode in which validation is takiing place : rollback, import,...
	 * @param LdDacuraServer $srvr
	 * @return boolean true if the object is valid according to the rules
	 */
	function validateLDObject($obj, $mode, LdDacuraServer &$srvr){
		if(!isAssoc($obj)){
			if(!$this->rule($mode, "validate", 'allow_invalid_ld')){
				$decl = is_string($obj) ? htmlspecialchars($obj) : " is simple array";
				return $this->failure_result("linked data input structure is broken: $decl value - should be json object {property: value})", 400);				
			}
		}
		else {
			foreach($obj as $p => $v){
				if($mode == "update" && $p == $this->rule($mode, "import", "demand_id_token")) continue;
				if(!$this->validateLDPredicate($p, $mode, $srvr)){
					return false;
				}
				if(!$this->validateLDValue($v, $mode, $srvr)){
					$this->errmsg = "Predicate $p ".$this->errmsg;
					return false;						
				}
			}
		}
		return true;
	} 
	
	/**
	 * 
	 * @param string $s the subject string to be validated
	 * @param string $mode the mode in which validation is takiing place : rollback, import,...
	 * @param LdDacuraServer $srvr
	 * @return boolean true if the subject is valid according to the rules
 	*/
	function validateLDSubject($s, $mode, LdDacuraServer &$srvr){
		if(isBlankNode($s)){
			if($this->rule($mode, "validate", 'forbid_blank_nodes')){
				return $this->failure_result("Linked data input structure contains forbidden blank node $s", 400);				
			}
		}
		else {
			if($this->rule($mode, "validate", 'require_blank_nodes') && $s != $this->cwurl){ //second condition prevents failure on new candidate with id = cwurl
				return $this->failure_result("Linked data input structure contains embedded ids that are not blank nodes - forbidden", 400);				
			}
			if(isNamespacedURL($s) && $this->rule($mode, "validate", 'forbid_unknown_prefixes')){
				return $this->failure_result("Linked data input structure contains url $s that includes unknown prefixes", 400);						
			}
			if(!isURL($s) && $this->rule($mode, "validate", 'require_subject_urls')){
				return $this->failure_result("Linked data input structure contains subject $s that is not a url", 400);						
			}
		}
		return true;
	}

	/**
	 * validates the predicate in a ld object
	 * @param string $p the predicate to be validated
	 * @param string $mode the mode in which validation is takiing place : rollback, import,...
	 * @param LdDacuraServer $srvr
	 * @return boolean true if the predicate is valid according to the rules
	 */
	function validateLDPredicate($p, $mode, LdDacuraServer &$srvr){
		if(isBlankNode($p)){
			if($this->rule($mode, "validate", 'allow_blanknode_predicates')){
				return $this->failure_result("Linked data input structure contains forbidden predicate blank node $p", 400);
			}
		}
		else {
			if(isNamespacedURL($p) && $this->rule($mode, "validate", 'forbid_unknown_prefixes')){
				return $this->failure_result("Linked data input structure contains predicate url $p that includes unknown prefixes", 400);
			}
			$x = $this->rule($mode, "import", 'demand_id_token');
			if(!isURL($p) && ($p != $x && $this->rule($mode, "validate", 'require_predicate_urls'))){
				return $this->failure_result("Linked data input structure contains predicate $p that is not a url", 400);
			}				
		}
		return true;
	}
	
	/**
	 * Validates the value of a ld predicate in an ld object
	 * @param string $p the predicate to be validated
	 * @param string $mode the mode in which validation is takiing place : rollback, import,...
	 * @param LdDacuraServer $srvr
	 * @return boolean true if the value is valid according to the rules
	 */
	function validateLDValue($obj, $mode, LdDacuraServer &$srvr){
		$pv = new LDPropertyValue($obj, $this->cwurl);
		if($pv->illegal($this->rules($mode, "validate"))) {
			return $this->failure_result($pv->errmsg, $pv->errcode);
		}
		if($pv->embeddedlist()){
			return $this->validateLDProps($obj, $mode, $srvr);
		}
		elseif($pv->embedded()){
			return $this->validateLDObject($obj, $mode, $srvr);
		}
		elseif($pv->objectlist()){
			foreach($obj as $emb){
				if(!$this->validateLDObject($emb, $mode, $srvr)){
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * Called to produce an analysis of the object populated by sub-classes of ldo
	 */
	function analyse(LdDacuraServer &$srvr){
		$astruct = array("created" => time(), "version" => $this->version);
		return $astruct;
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
	 * Get the collection id of the ldo 
	 * @return string the collection id
	 */
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
		return $this->nsres->getNamespaces($this->ldprops, $this->cwurl, $this->compressed);
	}
	
	/**
	 * Updates the ld properties to make blank nodes within them as addressable cwurl/bnid
	 */
	function makeBNsAddressable(){
		makeBNIDsAddressable($this->ldprops, $this->cwurl);
		$this->addressable_bnids = true;
	}
	
	/**
	 * Get the value of a particular predicate in a fragment 
	 * @param string $fid the fragment id
	 * @param string $pred_url the predicate's url
	 * @param string $pred_ns the namespace of the predicate
	 * @param string $gid the graph id in which to locate the fragment
	 * @return boolean|mixed - the value of the predicate or false if not present
	 */
	function getPredicateValues($fid, $pred_url, $pred_ns = false, $gid = false){
		if($this->compressed){
			$this->expandNS();
		}
		if($pred_ns){
			if(!($pred_url = $this->nsres->expand($pred_ns.":".$pred_url))){
				return $this->failure_result("Failed to expand namespace ".htmlspecialchars($pred_ns), 400);
			}
		}
		if(!($frag = $this->getFragment($fid, $gid))){
			return $this->failure_result("Fragment id ".htmlspecialchars($fid)." not found", 400);				
		}
		if(isset($frag[$pred_url])){
			return $frag[$pred_url];
		}
		return $this->failure_result("Predicate ".htmlspecialchars($pred_url)." not found", 400);
	}
	
	/**
	 * Sets the fragment: predicate part of ld props to the passed value
	 * @param unknown $value the value to set the predicate to
	 * @param string $fid the fragment id
	 * @param string $pred_url the predicate url
	 * @param string $pred_ns the predicate namespace prefix (to be expanded)
	 * @param string $gid the graph id in which to look
	 * @return boolean true if successful
	 */
	function setPredicateValue($value, $fid, $pred_url, $pred_ns = false, $gid = false){
		if($this->compressed){
			$this->expandNS();
		}
		if($pred_ns){
			if(!($pred_url = $this->nsres->expand($pred_ns.":".$pred_url))){
				return $this->failure_result("Failed to expand namespace ".htmlspecialchars($pred_ns), 400);
			}
		}
		if(!($this->setFragmentPredicateValue($value, $fid, $pred_url, $gid))){
			return $this->failure_result("Fragment id $fid with predicate $pred_url not found", 400);				
		}
		return true;
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
	 * loads a particular gragment into the ldprops of the ldo
	 * @param string $fragid the id of the fragment to load 
	 * @return boolean true if successful
	 */
	function loadFragment($fragid){
		if($this->addressable_bnids){
			$fid = $this->cwurl."/".$fragid;
		}
		else {
			$fid = "_:".$fragid;
		}
		$frag = $this->getFragment($fid);
		if(!$frag){
			return $this->failure_result("fragment $fid not found", 404);
		}
		$this->fragment_path = $this->getFragmentPath($fid);
		$this->fragment_id = $fragid;
		$this->ldprops = array($fid => $frag);
		if($vals = $this->getPredicateValues($fid, "type", "rdf")){
			$this->meta['type'] = $vals;
		}
		return true;
	}
	
	/**
	 * Sets a fragment to a particular ldo
	 * @param string $fragid the fragment id
	 * @param array $ldo the ldo that will be the fragment contents
	 * @param string $gid graph id of the graph to look in
	 * @return boolean true if successful
	 */
	function setFragment($fragid, $ldo, $gid = false){
		if($this->addressable_bnids){
			$fid = $this->cwurl."/".$fragid;
		}
		else {
			$fid = "_:".$fragid;
		}
		$target =& $this->ldprops;
		if(!setFragment($fid, $this->ldprops, $ldo, $this->cwurl)){
			return $this->failure_result("Failed to set fragment $fragid to new value", 404);
		}
		return true;
	}

	/**
	 * Does the object contain a fragment with the given id?
	 * @param string $frag_id the object's fragment id
	 * @param string $gid the graph id to look in
	 * @return boolean true if the fragment with the given id exists in the object
	 */
	function hasFragment($frag_id, $gid = false){
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
	function getFragment($fid, $gid = false){
		if($this->index === false){
			$this->buildIndex();
		}
		$frag = array();
		if(!isset($this->index[$fid])){
			return false;
		}
		foreach($this->index[$fid] as $i => $ldobj){
			$frag = array_merge($frag, $ldobj);
		}
		return $frag;
	}
	
	/**
	 * Sets the value of a particular fragment:predicate:value part of the ld structure
	 * @param unknown $value the value to set
	 * @param string $fid the fragment id
	 * @param string $p the predicate
	 * @param string $gid the graph id 
	 * @return boolean true if successful
	 */
	function setFragmentPredicateValue($value, $fid, $p, $gid = false){
		if($this->index === false){
			$this->buildIndex();
		}
		if(!isset($this->index[$fid])){
			return $this->failure_result("Fragment $fid does not exist", 404);
		}
		setFragmentPredicate($fid, $p, $this->ldprops, $value, $this->cwurl);
		return true;
	}
	
	/**
	 * Returns the object embedding paths to the fragments with subjects of the passed 
	 * @param string $fid the fragment id to find
	 * @param string $gid the graph id
	 * @param array $frag the fragment to be put into the path
	 * @return array<array> an array of paths to the object in question
	 */
	function getFragmentPath($fid, $gid = false, $frag = false){
		$path = false;
		$path = getFragmentContext($fid, $this->ldprops, $this->cwurl, $frag);
		return $path;
	}
	
	/**
	 * Are the object's contents empty?
	 * @return boolean
	 */
	function isEmpty(){
		return count($this->ldprops) == 0 || !$this->ldprops;
	}
	
	/**
	 * Does the obect have unique ids for internal subjects (i.e. do node ids appear only once as a subject)
	 * @param string $gid the graph id to limit the search to 
	 * @return boolean
	 */
	function subjectIDsUnique($gid = false){
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
	function export($format, $options = array()){
		$easy = exportEasyRDFPHP($this->ldprops, $this->cwurl);
		try{
			$ns = EasyRdf_Namespace::namespaces();
			foreach($ns as $id => $url){
				EasyRdf_Namespace::delete($id);
			}
			if(isset($options['ns']) && $options['ns']){
				foreach($this->nsres->prefixes as $id => $url){
					EasyRdf_Namespace::set($id, $url);
				}
			}
			$graph = new EasyRdf_Graph($this->cwurl, $easy, "php", $this->id);
			if($graph->isEmpty()){
				return "";//return $this->failure_result("exported graph was empty.", 400);
			}
			$res = $graph->serialise($format);
			if(!$res){
				return $this->failure_result("failed to serialise graph", 500);
			}
			return $res;
		}
		catch(Exception $e){
			return $this->failure_result("Easy RDF Graph croaked on input. ".$e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Is the given format natively supported by dacura?
	 * @param string $format the format id
	 * @return boolean true if natively supported
	 */
	static function isNativeFormat($format){
		return $format == "" or in_array($format, LDO::$native_formats);
	}
	
	/**
	 * Retrieve the contents of the object in the format specified
	 * @param string $format one of dacura's LDO::valid_display_formats
	 * @param array $options an array of display flags for options 
	 * @param LdDacuraServer $srvr the server object 
	 * @param string $for the use of the content - display | internal
	 * @return string|boolean|array the contents in the specified format
	 */
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
			$ns =  isset($options['ns']) && $options['ns'] ? $this->getNS() : false;				
			$payload = toJSONLD($this->ldprops, $ns, $this->cwurl, $this->is_multigraph());
		}
		elseif($format == "nquads"){
			$payload = $this->nQuads();
		}
		else {
			$exported = $this->export($format, $options);
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
	
	/**
	 * Shows the face of the object for HTML display
	 * @param string $format the format for display
	 * @param array $options the options for display
	 * @param LdDacuraServer $srvr the server object
	 * @return boolean true if successful
	 */
	function display($format, $options, LdDacuraServer &$srvr){
		$lddisp = new LDODisplay($this, $options);
		$this->display = $lddisp->display($format);
		return true;
	}
	
	/**
	 * Return an array of triples as a representation of this ld property array - with literals typed
	 */
	function typedTriples(){
		$triples = array();
		foreach($this->ldprops as $sid => $props){
			$triples = array_merge($triples, getObjectAsTypedTriples($sid, $props, $this->cwurl));
		}
		return $triples;
	}
	
	/**
	 * Return an array of triples as a representation of this ld property array - with untyped literals
	 */
	function triples(){
		$triples = array();
		foreach($this->ldprops as $sid => $props){
			$triples = array_merge($triples, getObjectAsTriples($sid, $props, $this->cwurl));
		}
		return $triples;
	}
	
	/**
	 * Get the object as typed {data:, type:} quads
	 * @param string $graphid the graph id to pick
	 * @return array quad array
	 */
	function typedQuads($graphid = false){
		$graphid = $graphid ? $graphid : $this->cwurl;
		return getPropsAsTypedQuads($graphid, $this->ldprops, $this->cwurl);
	}
	
	/**
	 * Get the object as typed (simple literals) quads
	 * @param string $graphid the graph id to pick
	 * @return array quad array
	 */
	function quads($graphid = false){
		$graphid = $graphid ? $graphid : $this->cwurl;		
		return getPropsAsQuads($graphid, $this->ldprops, $this->cwurl);
	}
	
	/**
	 * Get the ldo as nquads
	 * @param string $graphid the graph id to pick
	 * @return string the nquads string
	 */
	function nQuads($graphid = false){
		$graphid = $graphid ? $graphid : $this->cwurl;
		require_once("JSONLD.php");
		//function assumes that properties are indexed by graph id
		return toNQuads(array($graphid => $this->ldprops), $this->cwurl);
	}
	
	/**
	 * Return a view of the object for sending to the api (turns off lots of stuff)
	 * @param string $format the format to show
	 * @param $opts the options for display
	 */
	function forAPI($format, $opts){
		$meta = deepArrCopy($this->meta);
		$meta = array_merge($this->getPropertiesAsArray(), $meta);
		$apirep = array(
				"type" => "LDO",
				"id" => $this->id, 
				"meta" => $meta, 
				"contents" => $this->display, 
				"format" => $format, 
				"options" => $opts
		);
		if(isset($opts['ns']) && $opts['ns'] && isset($this->nsres)){
			$apirep['ns'] = $this->nsres->prefixes;
		}
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
		if(isset($this->meta['status'])){
			$this->status = $this->meta['status'];
		}
	}
		
	/**
	 * Calculates the transforms necessary to get to current from other
	 * 
	 * @param LDO $other the object to be compared to this one
	 * @return LDDelta
	 */
	function compare(LDO $other){
		$cdelta = compareLDGraph($this->ldprops, $other->ldprops, $this->cwurl);
		$ndd = compareJSON($this->id, $this->meta, $other->meta, $this->cwurl, "meta");
		//opr($ndd);
		if($ndd->containsChanges()){
			$cdelta->addJSONDelta($ndd);
		}
		return $cdelta;
	}
	
	/**
	 * Updates a ld property array according to the passed update object
	 * @param array $update_obj ld update object
	 * @param string $mode the mode: replace, update, create, rollback, etc
	 * @param boolean $ismulti true if the update object is multi-graph
	 * @return boolean true if successful
	 */
	function update($update_obj, $mode){
		if(isset($update_obj['meta'])){
			updateJSON($update_obj['meta'], $this->meta, $mode);
			unset($update_obj['meta']);				
		}	
		if(count($update_obj) > 0){
			if(!$this->updateLDProps($update_obj, $this->ldprops, $this->idmap, $mode)){
				return false;
			}			
		}				
		if(count($this->idmap) > 0){
			$this->ldprops = updateLDReferences($this->ldprops, $this->idmap, $this->cwurl, false);			
		}
		$this->buildIndex();
		return true;
	}
	
	/**
	 * Called to update one ld props array with another 
	 * @param array $uprops array containing update
	 * @param array $ldprops props to be updated
	 * @param array $idmap mapping array of ids
	 * @param string $mode the update mode 
	 * @return boolean true if successful
	 */
	function updateLDProps($uprops, &$ldprops, &$idmap, $mode){
		foreach($uprops as $subj => $ldo){
			if(!is_array($ldprops)){
				$ldprops = array();
			}
			if(is_array($ldo) && count($ldo) == 0){
				if(isset($ldprops[$subj])){
					unset($ldprops[$subj]);
				}
				elseif($this->rule($mode, "update", "fail_on_bad_delete")){
					return $this->failure_result("Attempted to remove non-existant subject $subj", 404);
				}
			}
			elseif(isAssoc($ldo)){
				if(!isset($ldprops[$subj])){
					if(isBlankNode($subj) && $this->rule($mode, "update", 'replace_blank_ids')){
						addAnonSubject($ldo, $ldprops, $idmap, $this->rules($mode, "generate"), $subj);
					}
					else {
						$ldprops[$subj] = $ldo;
					}
					
				}
				elseif(!$this->updateLDO($ldo, $ldprops[$subj], $idmap, $mode)){
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
	 * @param string $mode - update mode in operation
	 * @return boolean true if updates worked
	 */
	function updateLDO($uprops, &$dprops, &$idmap, $mode){
		foreach($uprops as $prop => $v){
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
				elseif($this->rule($mode, "update", 'fail_on_bad_delete')){
					return $this->failure_result("Attempted to remove non-existant property $prop", 404);
				}
			}
			elseif($pv->objectlist()){ //list of new objects (may have @ids inside)
				foreach($v as $obj){
					$x = addAnonObj($obj, $dprops, $prop, $idmap, $this->rules($mode, "generate"));
				}
			}
			elseif($pv->embedded()){ //new object to add to the list - give her an id and insert her
				$rep = importLD($v, $this->rules($mode, "import"));
				if($rep === false){
					return $this->failure_result("Failed to import linked data structure", 400);
				}
				$idmap = array_merge($idmap, $rep);
				$x = addAnonObj($v, $dprops, $prop, $idmap, $this->rules($mode, "generate"));	
			}
			elseif($pv->embeddedlist()){
				$delids = $pv->getdelids();//delete nodes
				foreach($delids as $did){
					if(isset($dprops[$prop][$did])){
						unset($dprops[$prop][$did]);
					}
					elseif($this->rule($mode, "update", 'fail_on_bad_delete')){
						return $this->failure_result("Attempted to delete non-existant embedded object $did from $prop", 404);
					}
				}
				$bnids = $pv->getbnids();//internal nodes
				foreach($bnids as $bnid){
					if(!isset($dprops[$prop][$bnid])) {
						if(is_array($v[$bnid]) && count($v[$bnid]) > 1){
							addAnonSubject($v[$bnid], $dprops[$prop], $idmap, $this->rules($mode, "update"), $bnid);
						}
					}
					elseif(is_array($v[$bnid]) && count($v[$bnid]) == 0) {
						unset($dprops[$prop][$bnid]);
					}
					else if(!$this->updateLDO($uprops[$prop][$bnid], $dprops[$prop][$bnid], $idmap, $mode)){
						return false;
					}
				}
			}
			elseif($pv->complex()){
				updateJSON($uprops[$prop], $dprops[$prop], $mode);									
			}
			else {
				return $this->failure_result("Unknown value type in LD structure", 500);
			}
			if(isset($dprops[$prop]) && is_array($dprops[$prop]) && count($dprops[$prop])==0) {
				unset($dprops[$prop]);
			}
		}
		return true;
	}

	/**
	 * Returns a list of the missing links in the object
	 * @return boolean|array - array of the missing links encountered
	 */
	function missingLinks(){
		if(isset($this->bad_links)){
			return $this->bad_links;
		}
		return $this->findMissingLinks();
	}
	
	/**
	 * generates a list of the bad links in the object
	 * @return array - array of the missing links encountered
	 */
	function findMissingLinks(){
		if($this->index === false){
			$this->buildIndex();
		}
		$missing = array();	
		foreach($this->ldprops as $s => $ldo){
			$missing = array_merge($missing, findInternalMissingLinks($ldo, array_keys($this->index), $this->id, $this->cwurl));
		}				
		$this->bad_links = $missing;
		return $missing;
	}
	
	/**
	 * Returns a list of all the urls of valid graphs available to this ldo
	 * @return <string, boolean>
	 */
	function getValidGraphURLs(){
		return array($this->getDefaultGraphURL());
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
	
	/**
	 * Gets the url to a local ontology that is imported by this ldo
	 * @param string $prefix - the ontology prefix
	 * @return boolean|string - the url extension after the dacura url or false if it is not imported
	 */
	function prefixToLocalOntologyURL($prefix){
		if(!isset($this->meta['imports']) || !isset($this->meta['imports'][$prefix])){
			return false;
		}
		$url = "";
		$imp = $this->meta['imports'][$prefix];
		if(is_array($imp)){
			if(isset($imp['collection']) && $imp['collection'] != "all"){
				$url = $imp['collection'] ."/";
			} 
			$url .= "ontology"."/".$prefix;
			if(isset($imp['version']) && $imp['version']){
				$url .= "?version=".$imp['version'];
			}
		}	
		else {
			global $dacura_server;
			$cid = $dacura_server->getOntologyCollection($prefix);
			if($cid != "all"){
				$url .= $cid."/";
			}
			$url .= "ontology/$prefix";
		}
		return $url;
	}

}