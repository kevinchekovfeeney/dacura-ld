<?php
include_once("BrowseDacuraServer.php");
/** 
 * Browse Service - provides access to home page of collection and system.
 * 
 * Creation Date: 01/03/2015
 * @package browse
 * @author Chekov
 * @license: GPL v2
 */
class BrowseService extends DacuraService {
	/** @var array<string:array> any user of a collection can view the collection home page */
	//var $protected_screens = array("view" => array("user"));
	/** @var array a list of the services available to the user in the current collection context */
	var $services;
	var $public_screens = array("view");
	
	/**
	 * Loads the set of available services from configuration 
	 * @see DacuraService::init()
	 */
	function init(){
		parent::init();
		$this->services =  $this->getServiceSetting("services");		
	}
	
	/**
	 * loads the parameters for a service button on the collection home page 
	 * @param string $id button id
	 * @param array $sb button configuration array
	 * @return array update button configuration array
	 */
	function getSingleButtonParams($id, $sb){
		if(!isset($sb["url"])){
			if($id == "ontology"){
				$sb["url"] = $this->getSystemSetting('install_url')."schema";				
			}
			else {
				$sb["url"] = $id;
				$sb["url"] = $this->get_service_url($sb["url"]);
			}
		}
		if(!isset($sb["img"])){
			$sb["img"] = $id;
		}
		$sb["img"] = $this->furl("file", "buttons/".$sb["img"].".png");
		$ent = $this->cid() == "all" ? "System" : "Dataset";
		$sb['help'] = str_replace("ENTITY", $ent, $sb['help']);
		$sb['title'] = str_replace("ENTITY", $ent, $sb['title']);
		return $sb;
	}
	
	/**
	 * Checks to see if a user has permission to see a particular button
	 * @param DacuraUser $user
	 * @param array $srvc a role requirement array [rolename, collectionid]
	 * @return boolean true if the user has a role that allows them to see the button
	 */
	function userCanSee(DacuraUser $user, $srvc){
		$req_role = $srvc['role'];
		if(!isset($req_role[1]) or $req_role[1] === false) $req_role[1] = $this->cid();
		if($user->hasSufficientRole($req_role[0], $req_role[1])){
			return true;
		}
		return false;		
	}
	
	/**
	 * Generate the template parameters for all of the service buttons that they user has permission to see 
	 * @param DacuraUser $user the user in question
	 * @return array an array of service configuration settings for each of internal_services, data_services, tool_services
	 */
	function getServiceButtonParams($user){
		$params = array();
		foreach(array("internal", "data", "tool") as $stype){
			if(isset($this->services[$stype])){
				foreach($this->services[$stype] as $id => $is){
					$sb = $this->getSingleButtonParams($id, $is);
					if($this->userCanSee($user, $sb)){
						$params[$stype."_services"][] = $sb;
					}
				}
			}
		}
		return $params;		
	}
	
	/**
	 * We don't want a link to the browse service to appear so we override it and do nothing
	 * @see DacuraService::getServiceContextLinks()
	 */
	function getServiceContextLinks(){
		return array();
	}
	
	/**
	 * The view screen is the only one supported by the browse service
	 * @see DacuraService::getScreenForCall()
	 * @param BrowseDacuraServer dacura_server the server object
	 */
	function getScreenForCall(BrowseDacuraServer &$dacura_server){
		return "view";
	}
	
	/**
	 * Does nothing - just override it to suppress it
	 * @param array $p parameters
	 */
	protected function renderToolHeader($p){}
	/**
	 * Does nothing - just override it to suppress it
	 * @param array $p parameters
	 */
	protected function renderToolFooter($p){}
	/**
	 * Does nothing - just override it to suppress it
	 * @param BrowseDacuraServer $dacura_server
	 */
	protected function writeBodyHeader(BrowseDacuraServer &$dacura_server){}
	/**
	 * Does nothing - just override it to suppress it
	 * @param BrowseDacuraServer $dacura_server
	 */
	protected function writeBodyFooter(BrowseDacuraServer &$dacura_server){}
	
	/**
	 * Generates the parameters for the view screen
	 * 
	 * See the view screen for details of what parameters are accepted :sheep:
	 * @see DacuraService::getParamsForScreen()
	 * @param string $screen the name of the screen - always view in this case
	 * @param BrowseDacuraServer $dacura_server the server object
	 */
	function getParamsForScreen($screen, BrowseDacuraServer $dacura_server){
		$user = $dacura_server->getUser();
		$params = $dacura_server->getMenuPanelParams();
		$cparams = $dacura_server->loadContextParams();
		if($user){
			$params = array_merge($this->getServiceButtonParams($user), $params);
		}
		$nparams = $dacura_server->getStatusParams();
		$params['collection_blurb'] = $this->renderScreenAsString("status", $nparams);
		if($this->getCollectionID() == 'all'){
			$params['collection_logo'] = $params['system_logo'];
		}
		return $params;
	}	
}