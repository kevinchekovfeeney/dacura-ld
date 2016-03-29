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
	
	/**
	 * Adds import and simport to standard LDO meta properties. 
	 * (non-PHPdoc)
	 * @see LDO::getValidMetaProperties()
	 */
	function getValidMetaProperties(){
		$valids = array_merge(array("import", "simport"), parent::getValidMetaProperties());
		return $valids;
	}
	
	/**
	 * Ensures that both the url and the prefix of the new ontology are unique 
	 * (non-PHPdoc)
	 * @see LDO::validateMeta()
	 */
	function validateMeta($rules, LdDacuraServer &$srvr){
		if(!parent::validateMeta($rules, $srvr)){
			return false;
		}
		if(!isset($this->meta['url']) or !($this->meta['url'])){
			return $this->failure_result("Ontologies must specify a canonical URL in order to be referenced by other ontologies!", 400);
		}
		if(!isURL($this->meta['url'])){
			return $this->failure_result($this->meta['url'] . " is not a valid URL.Ontologies must specify a valid URL", 400);				
		}
		//if the id exists in the 'all' context, we can't have one in the collection - collections can't override universal ontology ids
		if($srvr->cid() != "all" && $srvr->dbman->hasLDO($this->id, "ontology", "all")){
			return $this->failure_result("An ontology with id $this->id exists on the platform - you must choose a different id", 400);
		}
		//if the url already exists, then we don't want to replicate it
		$onts = $srvr->getLDOs(array("type" => "ontology", "collectionid" => "all"));
		foreach($onts as $onet){
			if(isset($onet['meta']['url']) && $this->id != $onet['id'] && $onet['meta']['url'] == $this->meta['url']){
				return $this->failure_result($onet['id'] ." ontology already exists on platform with the url ".$this->meta['url'], 400);				
			}
		}
		if($srvr->cid() != 'all'){
			$onts = $srvr->getLDOs(array("type" => "ontology", "collectionid" => $srvr->cid()));
			foreach($onts as $onet){
				if(isset($onet['meta']['url']) && $this->id != $onet['id'] && $onet['meta']['url'] == $this->meta['url']){
					return $this->failure_result($onet['id'] ." ontology already exists in collection with the url ".$this->meta['url'], 400);
				}
			}				
		}
		return true;
	}	
	
	/**
	 * Validate dependencies is called as part of ontology publication
	 * 
	 * Ontologies which fail this check will not be active (cannot be used by other ontologies)
	 * @param a2rray $rules - configuration settings which impinge upon dependency analysis (currently none)
	 */
	function validateDependencies($rules){
		if(!$this->dependencies){
			$this->dependencies = $this->generateDependencies($rules);
		}
		//opr($this->dependencies);
		$ores = new DQSResult("dependency analysis");
		//what makes a set of dependencies invalid? 
		//1 unknown structural elements
		if(isset($this->dependencies['unknown']) && isset($this->dependencies['unknown']['structural']) && count($this->dependencies['unknown']['structural']) > 0){
			foreach($this->dependencies['unknown']['structural'] as $serr){
				$ores->errors[] = array("rdf:type" => "MissingDependency", "message" => "Ontology has a structural dependency on an ontology that is not present", "subject" => $serr[0], "predicate" => $serr[1], "object" => $serr[2]);
			}
			return $ores->failure(400, "Unknown required dependencies in ontology", count($ores->errors) . " unknown structural dependencies detected in ontology");		
		}
		//2 problem elements (dc:type)
		foreach($this->dependencies as $sh => $ontdata){
			if(isset($ontdata['problem_predicates']) && count($ontdata['problem_predicates']) > 0){
				foreach($ontdata['problem_predicates'] as $onepred){
					$ores->errors[] = array("rdf:type" => "IllegalPredicate", "predicate" => $onepred);
				}
			}
			if(count($ores->errors) > 0){
				return $ores->failure(400, "Use of bad predicates in ontology ", count($ores->errors) . " problem predicates detected in ontology");				
			}
		}
		//3 check for incorrect url -> no statements in current namespace -> also check unknown to try to find a url recommendation...
		if(!isset($this->dependencies[$this->id]) || count($this->dependencies[$this->id]) == 0){
			$ores->warnings[] = array("rdf:type" => "IncorrectURL", "message" => "The Ontology URL entered: ".$this->meta['url']." does not appear to be correct - there are no assertions about this ontology");	
		}
		//4 ontology hijacking -> warning.
		foreach($this->dependencies as $sh => $ontdata){
			if(!in_array($sh, array("_", "unknown", $this->id)) && count($ontdata['subject']) > 0){
				foreach($ontdata['subject'] as $hijack => $hcount){
					$ores->warnings[] = array("rdf:type" => "OntologyHijack", "message" => "Ontology contains assertions ($hcount) about $hijack from $sh ontology - this constitutes ontology hijacking");
				}
			}							
		}
		return $ores->accept();
	}
	
	/**
	 * Returns the list of ontologies that are required to validate instance data
	 * 
	 * @param OntologyDacuraServer $srvr
	 * @return array of ontologies indexed by prefix with collection and version also
	 */
	function getSchemaDependencies(OntologyDacuraServer &$srvr){
		if(isset($this->meta['import'])){
			return $this->meta['import'];
		}
		$deps = array();
		foreach($this->dependencies as $d => $info){
			if($this->isImportableOntology($d) && isset($info['structural']) && count($info['structural']) > 0){
				$deps[$d] = array("collection" => $srvr->getOntologyCollection($d), "version" => 0);
			}
		}
		return $deps;
	}
		
	/**
	 * Fetches the list of ontologies that are required by the schema schema graph 
	 * 
	 * First checks to see if the value is saved in the simport meta value, otherwise calculates it from scratch
	 * @param OntologyDacuraServer $srvr
	 * @return array of ontologies indexed by prefix with collection and version also
	 */
	function getSchemaSchemaDependencies(OntologyDacuraServer &$srvr){
		if(isset($this->meta['simport'])){
			return $this->meta['simport'];
		}
		$deps = array();
		foreach(array_keys($this->dependencies) as $d){
			if($this->isImportableOntology($d)){
				$deps[$d] = array("collection" => $srvr->getOntologyCollection($d), "version" => 0);
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
	 * Generates the information about the dependencies needed for an ontology 
	 * @param array $rules - rules which impinge upon dependency generation
	 * @return array of information about the ontologies used and what's in them
	 */
	function generateDependencies($rules){
		$nsutil = array();
		$this->nsres->getNamespaceUtilisation($this->ldprops, $this->cwurl, $nsutil);
		if(isset($rules['collapse_blank_nodes']) && $rules['collapse_blank_nodes']){
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
	function copyBlankNodesToLocalID(&$nsutil){
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