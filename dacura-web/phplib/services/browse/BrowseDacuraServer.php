<?php

class BrowseDacuraServer extends DacuraServer {
	
	function getContextNavigationHierarchy(){
		$context_hierarchy = array();
		$contexts = $this->getUserAvailableContexts();
		$mycid = $this->ucontext->getCollectionID();
		$mydid = $this->ucontext->getDatasetID();
		//if we are in a global context - show all collections
		if($mycid == "all"){
			foreach($contexts as $cid => $details){
				$context_hierarchy[] = array("id" => $cid, "link" => $this->getSystemSetting("install_url", "/").$cid."/all/", "selected" => ($cid == $mycid), "contents" => $details['title']);				
			}
		}
		if($mydid == "all") { // collection context - show all 
			if(isset($my_context['datasets']) && count($my_context['datasets'] > 0)){
				foreach($my_context['datasets'] as $i => $ds){
					$context_hierarchy[] = array("id" => $i, "link" => $this->getSystemSetting("install_url", "/").$mycid."/$i/", "selected" => ($i == $mydid), "contents" => $ds);				
				}
			}
		}
		else {
			$my_context = $contexts[$mycid];
			foreach($my_context['datasets'] as $i => $ds){
				$context_hierarchy[] = array("id" => $i, "link" => $this->getSystemSetting("install_url", "/").$mycid."/$i/", "selected" => ($i == $mydid), "contents" => $ds);				
			}
		}
		return $context_hierarchy;	
	}
	
	function getTaskPanelParams($params){
		
	}
	
	function getStatsPanelParams($params){
		
	}
	
	function getGraphPanelParams($params){
		
	}
	function getMenuPanelParams($params){
		$params['system_logo'] = array("link" => $this->getSystemSetting("install_url", "/")."system/", "img" => $this->ucontext->get_system_file_url("image", "dacura-platform-logo-160w.png"));
		$params['dashboard_panes'] = array();
		$params['menu_items'] = $this->getContextNavigationHierarchy();
		$mycid = $this->ucontext->getCollectionID();
		if($mycid != "all"){
			$col = $this->getCollection($mycid);
			$col_logo = ($col->getConfig("logo") && file_exists($col->getConfig("logo"))) ? $col->getConfig("logo") : $this->ucontext->get_system_file_url("image", "collection_bg.jpg");
			$params['collection_logo'] = array("link" => $this->getSystemSetting("install_url", "/").$mycid."/all", "img" => $col_logo, "title" => $col->name); 
		}
		
		return $params;
	}
	
	function getToolPanelParams($params){
		
	}
	
	
	
	
	function getDataPanelParams($params){
		return false;
		//need to get a list of the collections and datasets that exist...
		if($params['collection'] && $params['dataset']){
			return false;			
		}
		if($params['collection']){
			return $this->dbman->getDatasetsWithManagementRoles($params['collection']);			
		}
		else return $this->dbman->getCollectionsWithManagementRoles();
	}
	
	
}
