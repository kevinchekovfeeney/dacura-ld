<?php 
/**
 * Class representing an ontology
 * 
 * Contains logic for analysing dependencies of ontology
 * 
 * @author chekov
 * @license GPL v2
 */
Class Ontology extends LDO {
	
	/** @var array contains the calculated data about the ontologies dependencies - array has two keys, simport and import for schema schema imports and schema imports */
	var $dependencies = false;
	/** @var array contains any ontologies that have been loaded as dependent ontologies of this ontology */
	var $loaded_dependencies = array();
	
	/**
	 * Parses an internal dacura link to an ontology and separates out the id, fragment, collection and version information
	 * @param string $url internal dacura link to ontology
	 * @return array|boolean an array representing the parsed url with fields, id, fragment, collection, version
	 **/
	static function parseOntologyURL($url, $durl){
		$parsed_url = array("id" => false, "fragment" => false, "collection" => false, "version" => 0);
		if(stristr($url, "?")){
			$qstr = substr($url, strpos($url, "?")+1);
			parse_str($qstr, $query_parts);
			if(isset($query_parts['version']) && $query_parts['version']){
				$parsed_url['version'] = $query_parts['version'];
			}
			$url = substr($url, 0, strpos($url, "?"));
		}
		if(substr($url, 0, strlen($durl)) != $durl){
			return false;
		}
		$system_ontology_url = $durl . "ontology/";
		if(substr($url, 0, strlen($system_ontology_url)) == $system_ontology_url && (strlen($url) > (strlen($system_ontology_url)))){
			$parsed_url['collection'] = "all";
			$idstr = substr($url, strlen($system_ontology_url));
			if(stristr($idstr, "/") && strlen($idstr) > 1){
				$parsed_url["id"] = substr($idstr, 0, strpos($idstr, "/"));
				if(strlen(substr($idstr, 0, strpos($idstr, "/")))){
					$parsed_url["fragment"] = substr($idstr, 0, strpos($idstr, "/"));
				}
			}
			else {
				$parsed_url["id"] = $idstr;
			}
		}
		else {
			$idstr = substr($url, strlen($durl));
			$cid = substr($idstr, 0, strpos($idstr, "/"));
			$parsed_url['collection'] = $cid;
			$idbase = $cid . "/ontology/";
			if(substr($idstr, 0, strlen($idbase)) == $idbase){
				//echo "<P>$idbase $idstr";
				$idstr = substr($idstr, strlen($idbase));
				if(stristr($idstr, "/") && strlen($idstr) > 1){
					$parsed_url["id"] = substr($idstr, 0, strpos($idstr, "/"));
					if(strlen(substr($idstr, 0, strpos($idstr, "/")))){
						$parsed_url["fragment"] = substr($idstr, 0, strpos($idstr, "/"));
					}
				}
				else {
					$parsed_url["id"] = $idstr;
				}
			}
			else {
				return false;
			}
		}
		return $parsed_url;
	}
	
	/**
	 * Generate a url from a parsed ontology url structure
	 * 
	 * structure is {id: collection: version: fragment: }
	 * @param array $parsed_url parsed url with above structure
	 * @param string $durl dacura baseline url
	 * @return string the url 
	 */
	static function getOntologyURL($parsed_url, $durl){
		$url = $durl;
		if(isset($parsed_url['collection']) && $parsed_url['collection'] && $parsed_url['collection'] != "all"){
			$url .= $parsed_url['collection']."/";
		}
		$url .= "ontology/".$parsed_url['id'];
		if(isset($parsed_url['fragment']) && $parsed_url['fragment']){
			$url .= "/".$parsed_url['fragment'];
		}
		$url .= (isset($parsed_url['version']) && $parsed_url['version']) ? "?version=".$parsed_url['version'] : "";
		return $url;
	}
	

	/**
	 * Override defaults to specify loading of dependencies and switch off blank node requirement
	 * @see LDO::setLDRules()
	 */
	function setLDRules(&$srvr){
		parent::setLDRules($srvr);
		$this->rules->setRule("import", "load_dependencies", true);
		$this->rules->setRule("validate", "require_blank_nodes", false);
		//also need to do unavailable_urls
	}
	
	/**
	 * sets up the namespaces properly and loads dependencies once an ontology has been imported from the api 
	 * @param $mode the access mode (create, update, replace, ...)
	 * @param LdDacuraServer $srvr the server object 
	 * @see LDO::importLD()
	 */
	function importLD($mode, LdDacuraServer &$srvr){
		parent::importLD($mode, $srvr);
		if(isset($this->meta['url'])){
			$this->nsres->addPrefix($this->id, $this->meta['url']);
		}
		if($this->rule($mode, "import", 'load_dependencies')){
			if($srvr->getServiceSetting("two_tier_schemas", true)){
				$deps = $this->getDependencies($srvr, "schema/schema");
			}
			else {
				$deps = $this->getDependencies($srvr, "schema");
			}
		}
		return true;
	}
	
	/**
	 * Adds import and schema_import to standard LDO meta properties. 
	 * (non-PHPdoc)
	 * @see LDO::getValidMetaProperties()
	 */
	function getValidMetaProperties(){
		$valids = array_merge(array("import", "schema_import", 'instance_dqs_tests', 'schema_dqs_tests'), parent::getValidMetaProperties());
		return $valids;
	}

	/**
	 * Specifies that type should be ignored by update api 
	 * @see LDO::getStandardProperties()
	 */
	function getStandardProperties(){
		$props = parent::getStandardProperties();
		$props[] = "type";
		return $props;
	}
	
	/**
	 * @see LDO::getDefaultGraphURL()
	 */
	function getDefaultGraphURL(){
		return $this->instanceGname();
	}
	
	/**
	 * Graph name of ontology instance data (only for testing)
	 * @return string url
	 */
	function instanceGname(){
		return $this->cwurl;
	}
	
	/**
	 * Graph name of ontology schema graph (only for testing)
	 * @return string url
	 */
	function schemaGname(){
		return $this->cwurl ."/schema";
	}
	
	/**
	 * Graph name of ontology schema schema graph (only for testing)
	 * @return string url
	 */
	function schemaSchemaGname(){
		return $this->cwurl ."/schema/schema";
	}
	
	/**
	 * Get the set of tests to be used when instance data is being created (only used in testing - ontology is instance)
	 */
	function getCreateInstanceTests(){
		isset($this->meta['instance_dqs_tests']) ? 	$this->meta['instance_dqs_tests'] : false;			
	}
	
	/**
	 * Get the set of tests to be used when ontology is used to create a schema
	 */
	function getCreateSchemaTests(){
		isset($this->meta['schema_dqs_tests']) ? 	$this->meta['schema_dqs_tests'] : false;
	}
	
	/**
	 * Get the url that is used to import this ontology (includes version, fragment, collection, id)
	 */
	function getImportURL(){
		//opr($this);
		return $this->cwurl.($this->fragment_id ? "/".$this->fragment_id : "") ."?version=".$this->version;
	}
	
	/**
	 * Ensures that both the url and the prefix of the new ontology are unique 
	 * (non-PHPdoc)
	 * @see LDO::validateMeta()
	 */
	function validateMeta($mode, &$srvr){
		if(!parent::validateMeta($mode, $srvr)){
			return false;
		}
		if($this->rule($mode, "validate", 'required_meta_properties')){
			if(!isset($this->meta['url']) or !($this->meta['url'])){
				return $this->failure_result("Ontologies must specify a canonical URL in order to be referenced by other ontologies!", 400);
			}
			if(!isURL($this->meta['url'])){
				return $this->failure_result($this->meta['url'] . " is not a valid URL. Ontologies must specify a valid URL", 400);
			}				
			//if the id exists in the 'all' context, we can't have one in the collection - collections can't override universal ontology ids
			if($srvr->cid() != "all" && $srvr->dbman->hasLDO($this->id, "ontology", "all")){
				return $this->failure_result("An ontology with id $this->id exists on the platform - you must choose a different id", 400);
			}
			//if the url already exists, then we don't want to replicate it
			if(($taken_urls = $this->rule($mode, "validate", 'unavailable_urls')) && in_array($this->meta['url'],$taken_urls)){
				return $this->failure_result($onet['id'] ." ontology already exists on platform with the url ".$this->meta['url'], 400);				
			}
		}
		return true;
	}	
	
	/**
	 * Validate dependencies is called as part of ontology publication
	 * 
	 * Ontologies which fail this check will not be active (cannot be used by other ontologies)
	 */
	function validateDependencies($srvr, $test_flag){
		if(!$this->dependencies){
			$this->dependencies = $this->generateDependencies($srvr);
		}
		$ores = new DQSResult("Ontology " .$this->id . "dependency analysis", $test_flag);
		//what makes a set of dependencies invalid? 
		//1 unknown structural elements
		if(isset($this->dependencies['unknown']) && isset($this->dependencies['unknown']['structural']) && count($this->dependencies['unknown']['structural']) > 0){
			foreach($this->dependencies['unknown']['structural'] as $serr){
				$ores->warning("MissingDependency", array("subject" => $serr[0], "predicate" => $serr[1], "object" => $serr[2]));
			}
			if($srvr->getServiceSetting("fail_on_missing_dependency", true)){
				return $ores->failure(400, "Unknown required dependencies in ontology", count($ores->warnings) . " unknown structural dependencies detected in ontology");
			}		
		}
		//2 problem elements (dc:type)
		foreach($this->dependencies as $sh => $ontdata){
			if(isset($ontdata['problem_predicates']) && count($ontdata['problem_predicates']) > 0){
				foreach($ontdata['problem_predicates'] as $onepred){
					$ores->warning("IllegalPredicate", array("predicate" => $onepred));
				}
			}
			if(count($ores->warnings) > 0 && $srvr->getServiceSetting("fail_on_bad_predicate", true)){
				return $ores->failure(400, "Use of bad predicates in ontology ", count($ores->warnings) . " problem predicates detected in ontology");				
			}
		}
		//3 check for incorrect url -> no statements in current namespace -> also check unknown to try to find a url recommendation...
		if(!isset($this->dependencies[$this->id]) || count($this->dependencies[$this->id]) == 0){
			$ores->warning("IncorrectURL", "The Ontology URL entered: ".$this->meta['url']." does not appear to be correct - there are no assertions about this ontology");
			//$ores->warnings[] = array("rdf:type" => "IncorrectURL", "message" => "The Ontology URL entered: ".$this->meta['url']." does not appear to be correct - there are no assertions about this ontology");	
		}
		//4 ontology hijacking -> warning.
		foreach($this->dependencies as $sh => $ontdata){
			if(!in_array($sh, array("_", "unknown", $this->id)) && count($ontdata['subject']) > 0){
				foreach($ontdata['subject'] as $hijack => $hcount){
					$ores->warning("OntologyHijack", "$hcount assertions about $hijack from $sh ontology");
					//$ores->warnings[] = array("rdf:type" => "OntologyHijack", "message" =>  - this constitutes ontology hijacking");
				}
			}							
		}
		if(count($ores->warnings) > 0 && $srvr->getServiceSetting("fail_on_ontology_hijack", false)){
			return $ores->failure(400, "Ontology Hijacking identified in ontology", count($ores->warnings) . " instances detected");				
		}
		return $ores->accept();
	}
	
	/**
	 * Loads the ontologies dependency tree
	 * @param LdDacuraServer $srvr the active server object
	 * @param string $type schema or schema/schema
	 * @param array $ignore an array of dependencies (prefixes) that should be ignored (allows us to stop loading already loaded ones)
	 * @param boolean $forcegen if true, depedencies will be generated even when they are already specified
	 * @return boolean|array the dependency array of the ontology
	 */
	function getDependencies(LdDacuraServer $srvr, $type, $ignore = array(), $forcegen = false){
		$ignore[] = $this->id;
		if($type == "schema"){
			if($forcegen || !isset($this->meta['import'])){
				if($chain = $this->loadDependencyChain($srvr, "schema", $ignore)){
					foreach($chain as $id => $ont){
						$this->meta['import'][$id] = array("id" => $ont->id, "collection" => $ont->cid(), "version" => $ont->version);
					}
					$this->loaded_dependencies[$type][$id] = $ont;
				}
				else {
					return false;
				}
			}
			return $this->meta['import'];
		}
		if($type == "schema/schema"){
			if($forcegen || !isset($this->meta['schema_import'])){
				if($chain = $this->loadDependencyChain($srvr, "schema/schema", $ignore)){
					foreach($chain as $id => $ont){
						$this->meta['schema_import'][$id] = array("id" => $ont->id, "collection" => $ont->cid(), "version" => $ont->version);
						$this->loaded_dependencies[$type][$id] = $ont;
					}
				}
				else {
					return false;
				}
			}
			return $this->meta['schema_import'];
		}
	}
	
	/**
	 * Retrieves the actual ontologies that this ontology is dependent on
	 * @param LdDacuraServer $srvr
	 * @param string $type schema or schema/schema
	 * @param array $ignore ids of ontologies to be ignored
	 * @return array array of loaded ontologies
	 */
	function getDependentOntologies(LdDacuraServer $srvr, $type, $ignore = array()){
		if(isset($this->loaded_dependencies[$type])){
			return $this->loaded_dependencies[$type];
		}
		$ignore[] = $this->id;
		$onts = array();
		if($type == "schema"){
			if(isset($this->meta['import'])){
				foreach($this->meta['import'] as $id => $rec){
					$onts[$id] = $srvr->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);					
				}
				$this->loaded_dependencies[$type] = $onts;
				return $onts;
			}
			return $this->loadDependencyChain($srvr, $type, $ignore);				
		}
		else {
			if(isset($this->meta['schema_import'])){
				foreach($this->meta['schema_import'] as $id => $rec){
					$onts[$id] = $srvr->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
				}
				$this->loaded_dependencies[$type] = $onts;
				return $onts;
			}
			return $this->loadDependencyChain($srvr, $type, $ignore);				
		}			
	}
	
	/**
	 * Loads an ontologies dependency chain
	 * @param LdDacuraServer $srvr
	 * @param string $type schema schema/schema
	 * @param array $ignore array of prefixes to ignore in chain
	 * @param number $level how many levels along are we in the chain
	 * @return boolean|Ambigous <multitype:, multitype:unknown >
	 */
	private function loadDependencyChain(LdDacuraServer $srvr, $type, $ignore, $level = 0){
		if(!$this->dependencies){
			$this->dependencies = $this->generateDependencies($srvr);
			//opr($this->dependencies);
		}
		if(!is_array($this->dependencies)){
			return false;
		}
		$deps = array();
		foreach($this->dependencies as $d => $info){
			if(!in_array($d, $ignore) && $this->isImportableOntology($d)){
				if(($type == 'schema/schema' && $level == 0 && isset($info['predicate']))|| isset($info['structural']) && count($info['structural']) > 0){
					if(!$nont = $srvr->loadLDO($d, "ontology", $srvr->getOntologyCollection($d))){
						return $this->failure_result($srvr->errmsg, $srvr->errcode);
					}
					if(!isset($this->loaded_dependencies[$type])){
						$this->loaded_dependencies[$type] = array();
					}
					$this->loaded_dependencies[$type][$d] = $nont;
					$deps[$d] = $nont;
					$ignore[] = $nont->id;
					$sdeps = $nont->loadDependencyChain($srvr, $type, $ignore, $level+1);
					if($sdeps === false){
						return $this->failure_result($nont->errmsg, $nont->errcode);						
					}
					$deps = array_merge($deps, $sdeps);
				}
			}
		}
		return $deps;
	}
			
	/**
	 * Only some of the ontologies are importable - some of them are built in or pseudo names 
	 * @param string $oid the ontology id
	 * @return boolean true if the ontology is importable
	 */
	function isImportableOntology($oid){
		if($oid != "unknown" && $oid != "_" && $oid != $this->id && !$this->nsres->isBuiltInOntology($oid)){
			return true;
		}
		return false;
	}
	
	/**
	 * Analyzes the contents of the ontology to identify the dependencies
	 * @return array of information about the ontologies used and what's in them
	 */
	function generateDependencies(LdDacuraServer $srvr){
		$nsutil = array();
		$this->nsres->getNamespaceUtilisation($this->ldprops, $this->cwurl, $nsutil);
		if($srvr->getServiceSetting("collapse_blank_nodes_for_dependencies", true)){
			$this->copyBlankNodesToLocalID($nsutil);
		}
		$deps = array();
		foreach($nsutil as $sh => $contents){
			$deps[$sh] = array(
				"url" => $this->nsres->getURL($sh),
				"subject" => isset($contents["subject"]) ? $contents["subject"] : array(),
				"predicate" => isset($contents["predicate"]) ? $contents["predicate"] : array(),
				"object" => isset($contents["object"]) ? $contents["object"] : array(),
				"structural" => isset($contents["structural"])? $contents["structural"]: array(),
				"distinct_predicates" => isset($contents["predicate"]) ? count($contents["predicate"]) : 0,
				"distinct_subjects" => isset($contents["subject"]) ? count($contents["subject"]) : 0,
				"structural_links" => isset($contents["structural"])? count($contents['structural']) : 0,
				"values_used" => isset($contents["object"]) ? count($contents['object']) : 0,
				"predicates_used" => 0,
				"subjects_used" => 0
			);
			if(isset($contents["predicate"] )){
				foreach($contents["predicate"] as $p => $c){
					$deps[$sh]["predicates_used"] += $c;
				}
			}
			if(isset($contents["subject"])){
				foreach($contents["subject"] as $p => $c){
					$deps[$sh]["subjects_used"] += $c;
				}
			}
		}
		return $deps;
	}

	/**
	 * Changes dependency analysis by combining blank nodes and local node ids together. 
	 * @param array $nsutil the namespace utilisation array as returned by NSResolver->getNSUtilisation
	 */
	private function copyBlankNodesToLocalID(&$nsutil){
		if(isset($nsutil["_"])){
			foreach($nsutil["_"]["predicate"] as $prop => $c){
				if(isset($nsutil[$this->id]["predicate"][$prop])){
					$nsutil[$this->id]["predicate"][$prop] += $c;
				}
				else {
					$nsutil[$this->id]["predicate"][$prop] = $c;
				}
			}
			foreach($nsutil["_"]["subject"] as $prop => $c){
				if(isset($nsutil[$this->id]["subject"][$prop])){
					$nsutil[$this->id]["subject"][$prop] += $c;
				}
				else {
					$nsutil[$this->id]["subject"][$prop] = $c;
				}
			}
			if(isset($nsutil[$this->id]["structural"]) && isset($nsutil["_"]["structural"])){
				$nsutil[$this->id]["structural"] = array_merge($nsutil[$this->id]["structural"], $nsutil["_"]["structural"]);
			}
			if(isset($nsutil[$this->id]["object"]) && isset($nsutil["_"]["object"])){
				$nsutil[$this->id]["object"] = array_merge($nsutil[$this->id]["object"], $nsutil["_"]["object"]);
			}
			unset($nsutil["_"]);
		}
	}
}