<?php

include_once("BrowseDacuraServer.php");

class BrowseService extends DacuraService {
	
	var $collection_context;
	var $dataset_context;
	var $public_screens = array();
	var $protected_screens = array("view" => array("user"));
	
	var $internal_services = array(
		"config" => array(
			"role" => array("admin"),
			"title" => "settings",
			"help" => "View and update the configuration of the ENTITY"								
		),
		"widget" => array(
			"role" => array("admin"),
			"title" => "widgets",
			"help" => "Create and manage user interfaces, tools and forms for managing your data"								
		),
		"users" => array(
			"role" => array("admin"),
			"title" => "users",
			"help" => "Manage the users of the ENTITY"								
		),
		"task" => array(
			"role" => array("admin", "all"),
			"title" => "tasks",
			"help" => "Manage the tasks to be carried out on your data"								
		),				
	);

	var $data_services = array(
		"import" => array(
			"role" => array("admin", "all"), 
			"title" => "import",
			"help" => "Import data into your dataset from elsewhere"								
		),
		"candidate" => array(
			"role" => array("user"),
			"title" => "data",
			"help" => "View and update the data in your dataset."								
		),
		"schema" => array(
			"role" => array("user"),
			"title" => "schema",
			"help" => "Manage the structure and organisation of your dataset"								
		),
		"publish" => array(
			"role" => array("admin", "all"),
			"title" => "publish",
			"help" => "Publish and share your data in a wide range of ways"
		),
	);

	var $tool_services = array(
		"ld" => array(
			"title" => "Linked Data Browser",
			"help" => "Direct, low-level access to all of the Linked Data Objects in the ENTITY",								
			"role" => array("admin", "all")
		),
		"scraper" => array(
			"title" => "Seshat Scraper",
			"help" => "A tool for extracting data from the Seshat wiki and converting it into a ",								
			"role" => array("user", "seshat")
		)

	);
	
	function getSingleButtonParams($id, $sb){
		if(!isset($sb["url"])){
			$sb["url"] = $id;
		}
		$sb["url"] = $this->get_service_url($sb["url"]);
		if(!isset($sb["img"])){
			$sb["img"] = $id;
		}
		$sb["img"] = $this->url("file", "buttons/".$sb["img"].".png");
		$ent = $this->getCollectionID() == "all" ? "System" : "Dataset";
		$sb['help'] = str_replace("ENTITY", $ent, $sb['help']);
		$sb['title'] = str_replace("ENTITY", $ent, $sb['title']);
		return $sb;
	}
	
	function userCanSee($user, $srvc){
		$req_role = $srvc['role'];
		if(!isset($req_role[1]) or $req_role[1] === false) $req_role[1] = $this->getCollectionID();
		if(!isset($req_role[2]) or $req_role[2] === false) $req_role[2] = $this->getDatasetID();
		if($user->hasSufficientRole($req_role[0], $req_role[1], $req_role[2])){
			return true;
		}
		return false;		
	}
	
	function getServiceButtonParams($user){
		$params = array();
		$params['internal_services'] = array();
		foreach($this->internal_services as $id => $is){
			$sb = $this->getSingleButtonParams($id, $is);
			if($this->userCanSee($user, $sb)){
				$params['internal_services'][] = $sb;
			} 
		}
		$params['data_services'] = array();
		foreach($this->data_services as $id => $is){
			$sb = $this->getSingleButtonParams($id, $is);
			if($this->userCanSee($user, $sb)){
				$params['data_services'][] = $sb;
			} 
		}
		$params['tool_services'] = array();
		foreach($this->tool_services as $id => $is){
			$sb = $this->getSingleButtonParams($id, $is);
			if($this->userCanSee($user, $sb)){
				$params['tool_services'][] = $sb;
			} 
		}
		return $params;		
	}
	
	function getServiceContextLinks(){
		return array();
	}
	
	function getScreenForCall(&$dacura_server){
		return "view";
	}
	
	//suppress these as we are not in a tool context
	function renderToolHeader($p){}
	function renderToolFooter($p){}
	function writeBodyHeader(&$dacura_server){}
	function writeBodyFooter(&$dacura_server){}
	
	
	function getParamsForScreen($screen, $dacura_server){
		$user = $dacura_server->getUser();
		$params = $dacura_server->getMenuPanelParams();
		$cparams = $dacura_server->loadContextParams();
		$params = array_merge($this->getServiceButtonParams($user), $params);
		$nparams = $dacura_server->getStatusParams();
		$params['collection_blurb'] = $this->renderScreenAsString("status", $nparams);
		if($this->getCollectionID() == 'all'){
			$params['collection_logo'] = $params['system_logo'];//array();
		}
		return $params;
	}
		
}