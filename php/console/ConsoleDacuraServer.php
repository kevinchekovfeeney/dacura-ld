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
			$glist[$gid]['version'] = $graph->version;
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
		if(!isset($pc["seshat"])){
			$scol = $this->getCollection("seshat");
			if($scol){
				$icon = $scol->getIcon() ? $scol->getIcon() : $this->service->furl("images", "system/collection_icon.png");
				$pc["seshat"] = array("title" => $scol->name, "icon" => $icon);
			}
		}
		if(!isset($pc["feedback"])){
			$scol = $this->getCollection("feedback");
			if($scol){
				$icon = $scol->getIcon() ? $scol->getIcon() : $this->service->furl("images", "system/collection_icon.png");
				$pc["feedback"] = array("title" => $scol->name, "icon" => $icon);
			}
		}
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
		$contents['url'] = $this->durl() . $cid;
		$contents['roles'] = $this->userman->getAvailableRoles($cid);
		$contents['demand_id_token'] = $cands->getServiceSetting("demand_id_token");
		$contents['entities'] = $cands->getExistingEntities();
		$contents['candidates'] = $cands->getCandidateList();
		$contents['entity_classes'] = $cands->getValidCandidateTypes();
		$contents['harvests'] = $this->service->getConnectorsForURL($this->service->getURLofLoad(), $cands, $this);
		$contents['harvested'] = $this->service->getLocatorsForURL($this->service->getURLofLoad(), $cands);
		$contents['graphs'] = $this->getGraphsForConsole($cands);
		$filter = array("type" => "ontology", 'collectionid' => $this->cid(), "status" => array("accept", "pending"));
		$onts = $cands->getLDOs($filter);
		$nonts = array();
		foreach($onts as $ont){
			$nont = array("id" => $ont['id']);
			if(isset($ont['meta']['title']) && $ont['meta']['title']){
				$nont['title'] = $ont['meta']['title'];
			}
			else {
				$nont['title'] = isset($ont['meta']['url']) ? $ont['meta']['url'] : "";
			}
			$nont['url'] = $this->service->get_service_url("ontology", array(), "api", $ont['collectionid'])."/".$ont['id'];
			$nont['version'] = $ont['version'];
			$nont['status'] = $ont['status'];
			$nonts[$ont['id']] = $nont;
		}
		$contents['ontologies'] = $nonts;
		$contents['frame_renderers'] = $cands->getFrameRenderingMap();
		return $contents;
	}
	
}