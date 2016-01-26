<?php
include_once("ConfigDacuraServer.php");
/**
 * Config Service - provides access to updating / editing / viewing collection configurations.
 *
 * Creation Date: 30/01/2015
 * @package config
 * @author Chekov
 * @license: GPL v2
 */

class ConfigService extends DacuraService {
	/** @var array(roles) two pages, system (system wide configuration - platform admins), collection (collection admins) */
	//var $protected_screens = array("view" => array("admin"));
	/** @var string the view page is the default screen - the only screen of the service (albeit divided into system / collection) */
	var $default_screen = "view";
	

	/* HTML Generation */
	
	/**
	 * Generates a set of html tables, each with a header and body element for each service in the system
	 * @param array $fsettings an array of settings to be passed to the DacuraForm element underlying the tables... 
	 * @param unknown $sfields
	 * @return multitype:multitype:NULL string
	 */
	function getServiceConfigTables(ConfigDacuraServer &$dacura_server, $fsettings, $sfields = array()){
		$stables = array();
		$services = $dacura_server->getServiceList();
		foreach($services as $s){
			$fields = $dacura_server->getServiceConfigFields($s, isset($sfields) ? $sfields : array());
			$tid = "service-".$s;
			$configs = $dacura_server->getServiceConfig($s);
			$stables[$s] = array(
				"body" => $this->getInputTableHTML($tid, $fields, $fsettings),
				"header" => $this->getServiceConfigPageHeaderHTML($s, $configs));
		}
		return $stables;
	}
	
	/**
	 * Generates the HTML for the service page subscreen headers
	 * @param string $sid the service id
	 * @param array $configs the settings for the service
	 * @return string the html string representing the header of the service configuration page
	 */
	function getServiceConfigPageHeaderHTML($sid, $configs){
		$html = "<span class='service-icon'><img class='serivce-config-img' src='";
		$html .= $this->get_system_file_url("image", "services/".$sid.".png")."'>";
		$html .= "</span><span class='service-title'>";
		if(isset($configs['service-title'])){
			$html .= $configs['service-title']. " configuration";
		}
		else {
			$html .= ucfirst($sid)." service configuration";
		}
		$html .= "</span><span class='service-description'>";
		if(isset($configs['service-description'])){
			$html .= $configs['service-description'];
		}
		$html .= "</span>";
		return $html;
	}
	
	/**
	 * Generates the url for the appropriate file browser url depending on the context
	 * @return string the filebrowser url
	 */
	function getFileBrowserURL(){
		return $this->durl().$this->getSystemSetting('filebrowser')."browse.php";
	}
	
	/* generating the settings for the dacura forms, depending on the facet, etc */
	
	/**
	 * System Configuration form settings
	 * 
	 * adds meta data column to system level forms and sets up various configuration values
	 * @param ConfigDacuraServer $srvr active server object
	 * @param Collection $col current collection context object
	 * @return array a DacuraForm initialisation settings array
	 */
	function getSysConfigFormSettings(ConfigDacuraServer &$srvr, Collection $col){
		if($this->cid() == "all"){
			$settings = array("meta" => array("changeable" => array("changeable", "choice", 
						array("hidden" => "Hidden", "fixed" => "Fixed", "changeable" => "Variable"))),
				"display_type" => "update",
				"embedstyle" => "flat",
				"show-header" => 2,
				"header-html" => "System Configuration Settings"
			);
		}
		else {
			//need to check facets
			$settings = array("meta" => array(), "display_type" => "view", "embedstyle" => "flat", 
				"show-header" => 2, "header-html" => $col->name ." collection Settings");
			if($srvr->userHasFacet("manage")){
				$settings['display_type'] = "update";
			}	
		}
		return $settings;
	}
	
	/**
	 * Service Configuration form settings
	 * 
	 * adds meta data column to system level forms and sets up various configuration values
	 * @param ConfigDacuraServer $srvr active server object
	 * @return array a DacuraForm settings array
	 */
	 function getServiceConfigFormSettings(ConfigDacuraServer &$srvr){
		if($this->cid() == "all"){
			$settings = array("meta" => array("changeable" => array("changeable","choice",
				array("hidden" => "Hidden", "fixed" => "Fixed", "changeable" => "Variable"))));
		}
		else {
			$settings = array("meta" => array());
		}
		$settings["display_type"] = "view";
		if($srvr->userHasFacet("manage")){
			$settings['display_type'] = "update";
		}
		if(!($srvr->userHasFacet("admin") || $srvr->userHasFacet("inspect"))){
			$settings['shortform'] = true;
		}
		$settings["embedstyle"] = "flat";
		$settings["show-header"] = 0;
		$settings["header-html"] = "";
		return $settings;
	}
	
	/**
	 * Create Collection form settings
	 *
	 * @return array a DacuraForm settings array
	 */	
	function getCreateFormSettings(){
		$settings = array("meta" => array(), "display_type" => "create", "show-header" => 2, 
			"header-html" => "New Collection Details");
		return $settings;
	}
	
	/**
	 * Loads the parameters, depending on context, that must be sent to the configuration tab
	 * @param array $params an array of parameters to be modified (added to)
	 * @param ConfigDacuraServer $dacura_server the active server object
	 * @param string $screen the html id of the screen that the tab is associated with
	 * @param Collection $col the current collection object
	 * @param DacuraUser $u the current logged in user (false if not logged in)
	 */
	function getSysconfigTabParams(&$params, ConfigDacuraServer &$dacura_server, $screen, Collection $col, $u = false){
		$params['sysconfig_settings'] = $this->getSysConfigFormSettings($dacura_server, $col);				
		if($screen == 'system'){
			$params['sysconfig_fields'] = $dacura_server->getSysconfigFields($this->getServiceSetting("sysconfig_form_fields"));			
		}
		else {
			$basic_fields = array("id" => array("id" => "id", "length" => "short", "type" => "text", "value" => $dacura_server->cid(), "disabled" => true, "label" => "id", "help" => "The id of the collection - used in urls"));
			$basic_fields = array_merge($basic_fields, $this->getServiceSetting("update_collection_fields"));
			$defs = $col->getDefaultSettings($dacura_server);
			foreach($defs as $k => $v){
				if(isset($basic_fields[$k]) && !isset($basic_fields[$k]['value'])){
					$basic_fields[$k]['value'] = $defs[$k];
				}
			}
			if(!$u or !$u->isPlatformAdmin()){
				//$basic_fields['status']['disabled'] = true;
			}
			if($dacura_server->userHasFacet("admin") || $dacura_server->userHasFacet("inspect")){
				$params['sysconfig_fields'] = array_merge($basic_fields, $dacura_server->getSysconfigFields($this->getServiceSetting("sysconfig_form_fields")));
				if($u && $u->isPlatformAdmin()){
					$params['candelete'] = true;
				}
			}
			else {
				$params['sysconfig_fields'] = $basic_fields;
			}
		}
	}
	
	/** 
	 * Loads the parameters, depending on context, that must be sent to the view logs tab
	 * @param array $params an array of parameters to be modified (added to)
	 * @param ConfigDacuraServer $dacura_server the active server object
	 * @param string $screen the html id of the screen that the tab is associated with
	 * @param Collection $col the current collection object
	 * @param DacuraUser $u the current logged in user (false if not logged in)
	 */
	function getLogTabParams(&$params, ConfigDacuraServer &$dacura_server, $screen, Collection $col, $u = false){
		$params['log_table_settings'] = $this->getDatatableSetting("logs");
	}
	
	/**
	 * Loads the parameters, depending on context, that must be sent to the view logs tab
	 * @param array $params an array of parameters to be modified (added to)
	 * @param ConfigDacuraServer $dacura_server the active server object
	 * @param string $screen the html id of the screen that the tab is associated with
	 * @param Collection $col the current collection object
	 * @param DacuraUser $u the current logged in user (false if not logged in)
	 */
	function getServicesTabParams(&$params, ConfigDacuraServer &$dacura_server, $screen, Collection $col, $u = false){
		$params['service_table_settings'] = $this->getDatatableSetting("services");
		$ss = $this->getServiceConfigFormSettings($dacura_server);
		$sf = $this->getServiceSetting("service_form_fields");
		$sf["facets"]['extras'] = UserRole::$extended_dacura_roles;
		if($screen == "system"){
			$sf["facets"]['hidden'] = true;	
			$params['selection_options'] = array("enable" => "Enabled", "disable" => "Disabled");				
		}
		else {
			$params['all_roles'] = UserRole::$extended_dacura_roles;				
		}
		if(!$dacura_server->userHasFacet("manage")){
			$sx = json_decode($params['service_table_settings'], true);
			$sx["aoColumns"] = array(null, null, array("bVisible" => false));
			$params['service_table_settings'] = json_encode($sx);
		}
		$params['services_config'] = $dacura_server->getServicesConfig();
		$params['service_tables'] = $this->getServiceConfigTables($dacura_server, $ss, $sf);
		$params['service_config_settings'] = $ss;
	}
	
	/**
	 * Loads the parameters, depending on context, that must be sent to the create collection tab
	 * @param array $params an array of parameters to be modified (added to)
	 * @param ConfigDacuraServer $dacura_server the active server object
	 * @param string $screen the html id of the screen that the tab is associated with
	 * @param Collection $col the current collection object
	 * @param DacuraUser $u the current logged in user (false if not logged in)
	 */
	function getCreateTabParams(&$params, ConfigDacuraServer &$dacura_server, $screen, Collection $col, $u = false){
		$params['create_collection_fields'] = $this->getServiceSetting("create_collection_fields");
		$params['create_collection_settings'] = $this->getCreateFormSettings();
	}	
	
	/**
	 * Get the list of subscreens that should be loaded for this context (user, collection)
	 * 
	 * @param string $screen
	 * @param ConfigDacuraServer $dacura_server
	 * @return array<string> an array of sub-screen html ids that should be loaded for the current context
	 */
	function getSubscreens($screen, ConfigDacuraServer &$dacura_server){
		$ss = array();
		if($screen == "system"){
			$ss = array("system-configuration", "view-services", "list-collections", "add-collection", "view-logs", "view-files");				
		}
		else {
			if($dacura_server->userHasFacet("view")){
				$ss[] = "collection-configuration";
				$ss[] = "view-services";
			}
			if($dacura_server->userHasFacet("admin") || $dacura_server->userHasFacet("inspect")){
				$ss[] = "view-logs";
			}
			if($dacura_server->userHasFacet("admin") || $dacura_server->userHasFacet("manage")){
				$ss[] = "view-files";
			}		
		}
		return $ss;
	}
	
	/**
	 * Loads the messages from the collection's services' settings for showing to the user
	 * @param string $screen the screen in question
	 * @param array $params the parameter array to be filled with messages for sending to the html template
	 * @param array<string> $sscreens an array of strings, each being the html id of a subscreen
	 */
	function loadSubscreenMessages(&$params, $screen, $sscreens){
		foreach($sscreens as $sid){
			$params[$sid.'-intro'] = $this->smsg($screen.'-'.$sid.'-intro');
		}
	}
	
	/* overridden inherited functions */
	
	/**
	 * treat all access as a view from an access control point of view to simplify roles
	 * @param string screen the name of the screen to render
	 * @param ConfigDacuraServer $dacura_server server object
	 * @return string always "view"
	 */
	function getMinimumFacetForAccess(&$dacura_server){
		if($this->cid() == "all"){
			return "admin";
		}
		return "view";
	}
	
	/**
	 * If the context is "all" load platform config page
	 * Otherwise load collection config page
	 * @return string page id
	 * @see DacuraService::getScreenForCall()
	 */
	function getScreenForCall(){
		return ($this->cid() == "all" ?  "system" : "collection");
	}
	
	/**
	 * Basic workhorse function that generates the parameters that have to be passed to the html screens
	 * @see DacuraService::getParamsForScreen()
	 */
	function getParamsForScreen($screen, ConfigDacuraServer &$dacura_server){
		$u = $dacura_server->getUser();
		$col = $dacura_server->getCollection();
		$ss = $this->getSubscreens($screen, $dacura_server);
		if(in_array('collection-configuration', $ss) || in_array('system-configuration', $ss)){
			$this->getSysconfigTabParams($params, $dacura_server, $screen, $col, $u);
		}
		if(in_array('view-services', $ss)){
			$this->getServicesTabParams($params, $dacura_server, $screen, $col, $u);
		}
		if(in_array('view-logs', $ss)){
			$this->getLogTabParams($params, $dacura_server, $screen, $col, $u);				
		}
		if(in_array("add-collection", $ss)){
			$this->getCreateTabParams($params, $dacura_server, $screen, $col, $u);				
		}
		$params['messages'] = $this->loadSubscreenMessages($params, $screen, $ss);
		$params['subscreens'] = $ss;
		$params['image'] = $this->furl("image", "services/config.png");
		$params['dt'] = true;
		$params["breadcrumbs"] = array(array(), array());
		
		if($screen == "system"){
			$params['dacura_table_settings'] = $this->getDatatableSetting("collections");
			$params["title"] = "Dacura Platform Management";
			$params["subtitle"] = "Manage the configuration of the Dacura Platform and all of its hosted collections";				
			$params['subscreens'] = array("system-configuration", "view-services", "list-collections", "add-collection", "view-logs", "view-files");
		}
		else {
			$params["title"] = $col->id." settings";
			$params["subtitle"] = "Manage the configuration of the ".$col->name . " collection";
		}
		return $params;
	}
}