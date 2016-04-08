<?php

include_once("Ontology.php");

class Graph extends LDO {
	var $dqs = array();
	
	function getCreateInstanceTests() {
		return $this->dqs['instance'];
	}

	function getCreateSchemaTests() {
		return $this->dqs['schema'];
	}

	function getUpdateSchemaTests() {
		return $this->dqs['schema'];
	}
	
	function getUpdateInstanceTests() {
		return $this->dqs['instance'];
	}

	function getValidateInstanceTests() {
		return array_merge($this->dqs['instance'], $this->dqs['schema']);
	}
	
	
	function getDeleteSchemaTests(){
		return array();
	}
	
	function getDeleteInstanceTests(){
		return array();
	}
	
	function getRollbackSchemaTests(){
		return array();
	}
	
	function loadDQSTestConfiguration(&$srvr){
		if(isset($this->meta['instance_dqs_tests'])){
			$this->dqs['instance'] = $this->meta['instance_dqs_tests'];
		}
		else {
			$this->dqs['instance'] = $srvr->getServiceSetting("create_dqs_instance_tests", array_keys(RVO::getInstanceTests(false)));
		}
		if(isset($this->meta['schema_dqs_tests'])){
			$this->dqs['schema'] = $this->meta['schema_dqs_tests'];
		}
		else {
			$this->dqs['schema'] = $srvr->getServiceSetting("create_dqs_schema_tests", array_keys(RVO::getSchemaTests(false)));
		}
		if(!isset($this->meta['two_tier_schemas'])){
			$this->meta['two_tier_schemas'] = $srvr->getServiceSetting("two_tier_schemas", true);
		}
	}
	
	function hasTwoTierSchema(){
		return isset($this->meta['two_tier_schemas']) && $this->meta['two_tier_schemas'];
	}
	
	function deserialise(LdDacuraServer &$srvr){
		$this->loadDQSTestConfiguration($srvr);			
	}
	
	function getSchemaImports($durl){
		$urls = $this->getPredicateValues("_:schema", "imports", "owl");
		$imports = array();
		if($urls){
			foreach($urls as $url){
				if($parsed_url = Ontology::parseOntologyURL($url, $durl)){
					$imports[$parsed_url['id']] = $parsed_url;
				}
			}
		}
		return $imports;
	}
	
	function getSchemaSchemaImports($durl){
		$urls = $this->getPredicateValues("_:schema_schema", "imports", "owl");
		$imports = array();
		if($urls){
			foreach($urls as $url){
				if($parsed_url = Ontology::parseOntologyURL($url, $durl)){
					$imports[$parsed_url['id']] = $parsed_url;
				}
			}
		}
		return $imports;
	}
	
	function loadNewObjectFromAPI($create_obj, $format, $options, $rules, &$srvr){
		if(!parent::loadNewObjectFromAPI($create_obj, $format, $options, $rules, $srvr)){
			return false;
		}
		if(isset($rules['load_dependencies']) && $rules['load_dependencies']){
			$this->processImports($srvr, $rules);
		}
		$this->loadDQSTestConfiguration($srvr);
		return true;			
	}
	
	function processImports(LdDacuraServer $srvr, $rules){
		$simports = $this->getSchemaImports($srvr->durl());
		$nimporturls = array();
		$changed_schema = false;
		foreach($simports as $id => $simport){
			if(!isset($simport['version']) || $simport['version'] == 0){
				if(!isset($this->meta['explicit_schema_imports'])){
					$this->meta['explicit_schema_imports'] = array();
				}
				$changed_schema = true;
				$this->meta['explicit_schema_imports'][] = $id;
				$cid = isset($simport['collection']) && $simport['collection'] ? $simport['collection'] : $srvr->getOntologyCollection($id);
				if(!$ont = $srvr->loadLDO($id, "ontology", $cid)){
					return false;
				}
				$nimporturls[$id] = $ont->getImportURL();
				$deps = $ont->getDependencies($srvr, "schema", $rules, array_keys($nimporturls), true);
				foreach($deps as $oid => $rec){
					if(!isset($simports[$oid])){
						$nimporturls[$oid] = Ontology::getOntologyURL($rec, $srvr->durl());						
					}
				}
			}
			else {
				$nimporturls[$id] = Ontology::getOntologyURL($simport, $srvr->durl());
			}
		}
		
		$this->ldprops["_:schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));
		if($this->hasTwoTierSchema()){
			if($changed_schema){
				$this->generateSchemaSchemaImports($srvr, $rules);				
			}
			else {
				$simports = $this->getSchemaSchemaImports($srvr->durl());
				$nimporturls = array();
				foreach($simports as $id => $simport){
					if(!isset($simport['version']) || $simport['version'] == 0){
						if(!isset($meta['explicit_schema_schema_imports'])){
							$meta['explicit_schema_schema_imports'] = array();
						}
						$meta['explicit_schema_schema_imports'][] = $simport['id'];
						$cid = isset($simport['collection']) && $simport['collection'] ? $simport['collection'] : $srvr->getOntologyCollection($id);
						$nimporturls[$id] = $ont->getImportURL();						
						$ont = $srvr->loadLDO($id, "ontology", $cid);
						$deps = $ont->getDependencies($srvr, "schema_schema", $rules, array_keys($nimporturls), true);
						foreach($deps as $oid => $rec){
							if(!isset($simports[$oid])){
								$nimporturls[$id] = Ontology::getOntologyURL($rec, $srvr->durl());						
							}
						}
					}
					else {
						$nimporturls[$id] = Ontology::getOntologyURL($simport, $srvr->durl());
					}
				}
				$this->ldprops["_:schema_schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));				
			}
		}
	}
	

	function generateSchemaImports(&$srvr, $rules){
		$parsed_urls = $this->getSchemaImports($srvr->durl());
		$dependencies = $this->generateDependenciesFromURLs($srvr, $parsed_urls, "schema", $rules);
		$nimporturls = array();
		foreach($dependencies as $id => $ont){
			$nimporturls[$id] = $ont->getImportURL();
		}
		$this->ldprops["_:schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));
	}
	
	function generateSchemaSchemaImports(&$srvr, $rules){
		$parsed_urls = array_merge($this->getSchemaImports($srvr->durl()), $this->getSchemaSchemaImports($srvr->durl()));
		$dependencies = $this->generateDependenciesFromURLs($srvr, $parsed_urls, "schema_schema", $rules);
		$nimporturls = array();
		foreach($dependencies as $id => $ont){
			$nimporturls[$id] = $ont->getImportURL();
		}
		$this->ldprops["_:schema_schema"] = array($this->nsres->expand("owl:imports") => array_values($nimporturls));
	}
	
	function instanceGname(){
		return $this->cwurl;
	}
	
	function schemaGname(){
		return $this->cwurl ."/schema";
	}
	
	function schemaSchemaGname(){
		return $this->cwurl ."/schema_schema";
	}
	
	
	function generateDependenciesFromURLs(&$srvr, $parsed_urls, $type = 'schema'){
		$dependencies = array();
		foreach($parsed_urls as $parsed_url){
			if(!isset($dependencies[$parsed_url['id']])){
				$sont = $srvr->loadLDO($parsed_url['id'], "ontology", $parsed_url['collection'], $parsed_url['fragment'], $parsed_url['version']);
				if($sont){
					$dependencies[$parsed_url['id']] = $sont;
				}
				else {
					return $this->failure_result("Failed to load ontology ".$parsed_ur['id']. $srvr->errmsg, $srvr->errcode);
				}
				$rules = array();
				$dependencies = array_merge($dependencies, $sont->getDependentOntologies($srvr, $rules, $type, array_keys($dependencies)));
			}
			else {
				return $this->failure_result("Could not translate parsed url into dacura schema dependency", 404);
			}
		}
		return $dependencies;
	}
	
	
	
	function getValidMetaProperties(){
		return array("status", "title", "two_tier_schemas", "image", "explicit_schema_imports", "instance_dqs_tests", "schema_dqs_tests", "selected_ontologies");
	}		
	
	
}
