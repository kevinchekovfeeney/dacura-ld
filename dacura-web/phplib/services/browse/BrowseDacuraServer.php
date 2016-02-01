<?php
//include_once("phplib/services/ld/LdDacuraServer.php");
include_once("phplib/services/config/ConfigDacuraServer.php");

/**
 * The browse service shows the collection and system front pages
 * 
 * It inherits from LD server rather than Dacura Server because it uses the ld calls (getEntities) to get stats about 
 * LD entities. I really need to change this to allow servers to use other servers!
 * * Creation Date: 01/03/2015
 * @package browse
 * @author Chekov
 * @license: GPL v2
 */
class BrowseDacuraServer extends DacuraServer {
	
	/**
	 * Generates a list of configuration objects which represent the services that can be accessed by the user from the browse screen
	 * @return array<string:array> an array indexed by serviceids, each of which contains (title, help, id)
	 */
	function getServicesWidgetList(){
		$wl = array();
		$servs = $this->getServiceList();
		foreach($servs as $sid){
			if(in_array($sid, array("browse", "login", "home"))) continue;//have no widgets associated
			$ns = $this->createDependantService($sid);
			//get service setting to make sure it is enabled...
			
			if($ns->getServiceSetting("status") != "disable" && $this->userHasFacet(false, $ns)){
				$wl[$sid] = array();
				$wl[$sid]['title'] = $ns->getServiceSetting('service-button-title', ucfirst($sid));
				$wl[$sid]['help'] = $ns->getServiceSetting('service-title', ucfirst($sid)." Service");
				$wl[$sid]['help'] .= ". ".$ns->getServiceSetting('service-description', "");
			}
		}
		return $wl;
	}
	
	/**
	 * Returns a list of the collections available to the user
	 * @return array<array<string:string>> a tree of the contexts available to the user
	 */
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
		return $context_hierarchy;	
	}
	
	/**
	 * Generates the parameters to populate the status pane in the browse menu
	 * @return array<string:number> an array of name:count pairs with information about various collection statistics in it
	 */
	function getStatusParams(){
		$params = array();
		if($this->cid() == "all"){
			$params['type'] = "system";
			$cols = $this->getCollectionList();
			$ds = $this->getCollectionList(false, false);
			$users = $this->getUsers();
			$params['user-count'] = count($users);
			$params['collection-count'] = count($cols);
		}
		else {
			$params['type'] = "admin";
			$params['user-count'] = 0;
		}
		/* this will be put back once the ld sub-system is back in place */
		//$lds = $this->createDependantServer("ld"); 
		//$filter = array("collection_id" => $this->cid(), "type" => "graph");
		//$graphs = $lds->getEntities($filter);
		//$params['graph-count'] = count($graphs);
		//filter['type'] = "ontology";
		//$ontologies = $lds->getEntities($filter);
		$params['ontology-count'] = 0;//count($ontologies);
		$params['instance-count'] = 0;
		$params['schema-count'] = 0;
		$params['graph-count'] = 0;
		return $params;
	}
	
	/**
	 * Generates the parameters to populate the menu panel
	 * Includes the images, the text, ext, identifying thecontext
	 * @return array<string:number> a parameter array to be passed to the view screen
	 */
	function getMenuPanelParams(){
		$params = array();
		$widgets = $this->getServicesWidgetList();
		$params['system_logo'] = array(
			"blurb" => "", 
			"title" => "Dacura Server", 
			"link" => $this->getSystemSetting("install_url", "/")."system/", 
			"img" => $this->service->get_system_file_url("images", "system/dacura-platform-logo-160w.png")
		);
		$params['dashboard_panes'] = array();
		$params['menu_items'] = $this->getContextNavigationHierarchy();
		if($this->cid() != "all"){
			$col = $this->getCollection($this->cid());
			$col_logo = $this->getSystemSetting("image", $this->service->furl("images", "system/default_collection.png"));
			$params['collection_logo'] = array(
				"link" => $this->getSystemSetting("install_url", "/").$this->cid(), 
				"img" => $col_logo, 
				"title" => $col->name,
				"blurb" => $this->getSystemSetting("description", "")
			); 
		}
		return $params;
	}
}
