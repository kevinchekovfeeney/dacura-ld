<?php 
class ConsoleDacuraServer extends LdDacuraServer {
	function getURLConnections($url){
		$ret = array("connectors" => array(), "locators" => array());
		return $ret;
	}
	
	function sendConsoleCapabilities($key, $val){
		$caps = $this->getClientCapabilities($key, $value);
		if($caps){
			return $caps;
		}
		return false;
	}
	
	function getGraphsForConsole($cands){
		$glist = array();
		foreach($cands->graphs as $gid => $graph){
			$glist[$gid] = $graph->getConsoleData($cands);
			$glist[$gid]['status'] = $graph->status;
			$glist[$gid]['status'] = $graph->version;
		}
		return $glist;
	}
	
	
	function getUserCapabilities($u){
		$bits = array();
		if($u){
			$bits['name'] = $u->handle;
			$bits['id'] = $u->id;
			if($u->rolesSpanCollections()){
				$bits["profile"] = $this->service->get_service_url("users", array(), "html", "all", "all")."/profile";
			}
			else {
				$bits["profile"] = $this->service->get_service_url("users", array(), "html", $u->getRoleCollectionId(), "all")."/profile";
			}
		}
		else {
			$bits['name'] = "anonymous";
		}
		return $bits;
	}
	
	function getClientCapabilities($key, $value){
		$caps = array("collections" => array());
		$caps['user'] = $this->getUserCapabilities($this->getUser());
		$pc = $this->getUserAvailableContexts();
		if($pc && count($pc) > 0){
			foreach($pc as $cid => $col){
				$caps['collections'][$cid] = $this->getCollectionCapabilities($cid, $col, $key, $value);
			}
		}
		$caps['ontology_config'] = array(
				"boxtypes" => $this->getBoxedTypes(),
				"entity_tag" => "dacura:Entity");
		return $caps;
	}
	
	function getBoxedTypes(){
		$cs = $this->createDependantService("candidate");
		$cands = $this->createDependantServer("candidate", $cs);
		$cands->init();
		if(!($dont = $cands->loadLDO("dacura", "ontology", "all"))){
			return array();
		}
		return $dont->getBoxedClasses();
	}
	
	
	
	
	function getCollectionCapabilities($cid, $col, $key, $value){
		$this->service->collection_id = $cid;
		$sc = $this->createDependantService("candidate");
		$cands = $this->createDependantServer("candidate", $sc);
		$cands->init();
		$contents = $col;
		$contents['roles'] = $this->userman->getAvailableRoles($cid);
		$ents = $cands->getValidCandidateTypes();
		$filter = array("type" => "candidate", 'collectionid' => $cid, "status" => array("accept", "pending"));
		$instances = $cands->getLDOs($filter);
		$entities = array();
		foreach($instances as $inst){
			if(isset($inst['meta']) && isset($inst['meta']['type']) && $inst['meta']['type']){
				$clsid = $cands->nsres->compress($inst['meta']['type']);
				if(!$clsid){
					$clsid = $inst['meta']['type'];
				}
				
				if(!isset($entities[$clsid])){
					$entities[$clsid] = array();
				}
				$entities[$clsid][$inst['id']] = $inst;
			}
		}
		$contents['demand_id_token'] = $cands->getServiceSetting("demand_id_token");
		$contents['candidates'] = $entities;
		$contents['entity_classes'] = $cands->getValidCandidateTypes();
		$contents['graphs'] = $this->getGraphsForConsole($cands);
		$filter['type'] = "ontology";
		$onts = $cands->getLDOs($filter);
		$nonts = array();
		foreach($onts as $ont){
			$nont = array("id" => $ont['id']);
			if(isset($ont['meta']['title']) && $ont['meta']['title']){
				$nont['title'] = $ont['meta']['title'];
			}
			else {
				$nont['title'] = $ont['meta']['url'];
			}
			$nont['url'] = $this->service->get_service_url("ontology", array(), "api", $ont['collectionid'])."/".$ont['id'];
			$nont['version'] = $ont['version'];
			$nont['status'] = $ont['status'];
			$nonts[$ont['id']] = $nont;
		}
		$contents['ontologies'] = $nonts;
		return $contents;
	}
	
}