<?php

include_once("Ontology.php");

class Graph extends Ontology {
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
	
	function setSchemaImports($onts){
		$importurls = array();
		$deps = $this->getSchemaDependencies($srvr);
		foreach($deps as $id => $ont){
			$importurls[] = $ont->getImportURL();
		}
		$this->ldprops["_:schema"] = array($this->nsres->expand("owl:imports") => $importurls);
	}
	
	function setSchemaSchemaImports($onts, $srvr){
		$importurls = array();
		$deps = $this->getSchemaSchemaDependencies($srvr);
		foreach($deps as $id => $ont){
			$importurls[] = $ont->getImportURL();
		}
		$this->ldprops["_:schema_schema"] = array($this->nsres->expand("owl:imports") => $importurls);
	}
	
	
	function loadNewObjectFromAPI($create_obj, $format, $options, $rules, &$srvr){
		if(!parent::loadNewObjectFromAPI($create_obj, $format, $options, $rules, $srvr)){
			return false;
		}
		$this->loadDQSTestConfiguration($srvr);
		$simports = $this->getSchemaImports();
		foreach($simports as $simport){
			if(!isset($simport['version']) || $simport['version'] == 0){
				if(!isset($meta['explicit_schema_imports'])){
					$meta['explicit_schema_imports'] = array();
				}
				$meta['explicit_schema_imports'][] = $simport['id'];
			}
		}
		/*	if($this->generateDependencies($srvr)){
				$dep_urls = array();
				$treated = array();
				if(isset($this->dependencies['schema']) && is_array($this->dependencies['schema']) && count($this->dependencies['schema']) > 0){
					foreach($this->dependencies['schema'] as $sh => $ont){
						$ontref = $ont->cwurl;
						if($ont->fragment_id){
							$ontref .= "/".$ont->fragment_id;
						}
						$ontref.="?version=".$ont->version;
						$dep_urls[] = $ontref;
						$treated[] = $sh;
					}
					$this->ldprops["_:schema"] = array($this->nsres->expand("owl:imports") => $dep_urls);
				}
				if(isset($this->dependencies['schema_schema']) && is_array($this->dependencies['schema_schema']) && count($this->dependencies['schema_schema']) > 0){
					foreach($this->dependencies['schema_schema'] as $sh => $ont){
						if(in_array($sh, $treated)) continue;
						$treated[] = $sh;
						$ontref = $ont->cwurl;
						if($ont->fragment_id){
							$ontref .= "/".$ont->fragment_id;
						}
						$ontref.="?version=".$ont->version;
						$dep_urls[] = $ontref;
					}				
					$this->ldprops["_:schema_schema"] = array($this->nsres->expand("owl:imports") => $dep_urls);
				}
				return $format ? $format : "json";
			}
			else {
				return false;
			}
		}*/
		return true;			
	}
	
	function generateDependenciesFromURLs(&$srvr, $urls, $is_schema_schema = false){
		$dependencies = array();
		foreach($urls as $url){
			if($parsed_url = Ontology::parseOntologyURL($url, $srvr->durl())){
				if(!isset($dependencies[$parsed_url['id']])){
					$sont = $srvr->loadLDO($parsed_url['id'], "ontology", $parsed_url['collection'], $parsed_url['fragment'], $parsed_url['version']);
					if($sont){
						$dependencies[$parsed_url['id']] = $sont;
					}
					else {
						return $this->failure_result("Failed to load ontology ".$parsed_ur['id']. $srvr->errmsg, $srvr->errcode);
					}
					$rules = array();
					if($is_schema_schema){
						$ss_deps = $sont->getSchemaSchemaDependencies($srvr, $rules, array_keys($dependencies));
						foreach($ss_deps as $ssid => $ssdep){
							if(!isset($dependencies[$ssid])){
								$dependencies[$ssid] = $ssdep;
							}
						}
					}
					else {
						$sdeps = $sont->getSchemaDependencies($srvr, $rules, array_keys($dependencies));
						foreach($sdeps as $sid => $sdep){
							if(!isset($dependencies[$sid])){
								$dependencies[$sid] = $sdep;
							}								
						}
					}
				}
			}
			else {
				return $this->failure_result("Could not translate $url into dacura schema dependency", 404);
			}
		}
		return $dependencies;
	}

	function generateSchemaDependencies(&$srvr){
		$urls = $this->getPredicateValues("_:schema", "imports", "owl");
		if($urls){
			if(is_string($urls)) $urls = array($urls);
			if($urls && is_array($urls) && count($urls) > 0){
				return $this->generateDependenciesFromURLs($srvr, $urls, false);
			}
		}
	}
	
	function generateSchemaSchemaDependencies(&$srvr){
		$urls = $this->getPredicateValues("_:schema", "imports", "owl");
		if($urls){
			if(is_string($urls)) $urls = array($urls);
		}
		if(!is_array($urls)){
			$urls = array();
		}
		$surls = $this->getPredicateValues("_:schema_schema", "imports", "owl");	
		if($surls){
			if(is_string($surls)) $surls = array($surls);
		}
		if(is_array($surls)){
			foreach($surls as $surl){
				if(!in_array($surl, $urls)){
					$urls[] = $surl;
				}
			}
		}
		if($urls) {
			return $this->generateDependenciesFromURLs($srvr, $urls, true);
		}
		return array();				
	}
	
	function generateDependencies(&$srvr){
		$this->dependencies = array("schema" => $this->generateSchemaDependencies($srvr));
		//opr(array_keys($this->dependencies['schema']));
		$this->dependencies['schema_schema'] = $this->generateSchemaSchemaDependencies($srvr);
		return ($this->dependencies["schema"] !== false);		
	}
	
	function getSchemaDependencies(&$srvr){
		if(!$this->dependencies){
			if(!$this->generateDependencies($srvr)){
				return false;
			}		
		}
		return $this->dependencies['schema'];
	}
	
	function getSchemaSchemaDependencies(&$srvr){
		if(!$this->dependencies){
			if(!$this->generateDependencies($srvr)){
				return false;
			}
		}
		return $this->dependencies['schema_schema'];
	}
	
	function validateDependencies(&$srvr){
		if(!$this->dependencies){
			$this->generateDependencies($srvr);
		}
		if(isset($this->dependencies['schema']) && $this->dependencies['schema'] === false){
			return false;
		}
		if(isset($this->dependencies['schema_schema']) && $this->dependencies['schema_schema'] === false){
			return false;
		}
		return true;
	}
	
	function getValidMetaProperties(){
		return array("status", "title", "two_tier_schemas", "image", "instance_dqs_tests", "schema_dqs_tests", "selected_ontologies");
	}		
	
	
	/**
	 * Ensures that both the url and the prefix of the new ontology are unique
	 * (non-PHPdoc)
	 * @see LDO::validateMeta()
	 */
	function validateMeta($rules, LdDacuraServer &$srvr){
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
}
