<?php

include_once("phplib/services/ld/LdDacuraServer.php");

class BrowseDacuraServer extends LdDacuraServer {
	
	function getContextNavigationHierarchy(){
		$context_hierarchy = array();
		$contexts = $this->getUserAvailableContexts();
		//if we are in a global context - show all collections
		if($this->cid() == "all"){
			foreach($contexts as $cid => $details){
				$context_hierarchy[] = array(
					"id" => $cid, 
					"link" => $this->getSystemSetting("install_url", "/").$cid, 
					"selected" => ($cid == $this->cid()), 
					"contents" => $details['title']
				);				
			}
		}
		if($this->did() == "all") { // collection context - show all 
			if(isset($my_context['datasets']) && count($my_context['datasets'] > 0)){
				foreach($my_context['datasets'] as $i => $ds){
					$context_hierarchy[] = array(
						"id" => $i, 
						"link" => $this->getSystemSetting("install_url", "/").$this->cid()."/$i/", 
						"selected" => ($i == $this->did()), 
						"contents" => $ds);				
				}
			}
		}
		else {
			$my_context = $contexts[$mycid];
			foreach($my_context['datasets'] as $i => $ds){
				$context_hierarchy[] = array(
					"id" => $i, 
					"link" => $this->getSystemSetting("install_url", "/").$this->cid()."/$i/", 
					"selected" => ($i == $this->did()), "contents" => $ds);				
			}
		}
		return $context_hierarchy;	
	}
	
	function getStatusParams(){
		$params = array();
		if($this->cid() == "all"){
			$params['type'] = "system";
			$cols = $this->getCollectionList();
			$ds = $this->getCollectionList(false, false);
			$users = $this->getusers();
			$params['user-count'] = count($users);
			$params['collection-count'] = count($cols);
			$params['dataset-count'] = count($ds);
		}
		else {
			$params['type'] = "admin";
			$params['user-count'] = 0;
		}
		$filter = array("collection_id" => $this->cid(), "dataset_id" => $this->did(), "type" => "graph");
		$graphs = $this->getEntities($filter);
		$params['graph-count'] = count($graphs);
		$filter['type'] = "ontology";
		$ontologies = $this->getEntities($filter);
		$params['ontology-count'] = count($ontologies);
		$params['instance-count'] = 0;
		$params['schema-count'] = 0;
		return $params;
	}
	
	function getMenuPanelParams(){
		$params = array();
		$params['system_logo'] = array(
			"blurb" => "", 
			"title" => "Dacura Server", 
			"link" => $this->getSystemSetting("install_url", "/")."system/", 
			"img" => $this->ucontext->get_system_file_url("image", "dacura-platform-logo-160w.png")
		);
		$params['dashboard_panes'] = array();
		$params['menu_items'] = $this->getContextNavigationHierarchy();
		if($this->cid() != "all"){
			$col = $this->getCollection($this->cid());
			$col_logo = ($col->getConfig("image")) ? $col->getConfig("image") : $this->ucontext->get_system_file_url("image", "collection_bg.jpg");
			$params['collection_logo'] = array(
				"link" => $this->getSystemSetting("install_url", "/").$this->cid(), 
				"img" => $col_logo, 
				"title" => $col->name,
				"blurb" => $col->getConfig("description")
			); 
		}
		return $params;
	}
	

	
	
}
