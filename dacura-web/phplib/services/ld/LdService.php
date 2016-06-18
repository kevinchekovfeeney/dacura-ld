<?php
include_once("LdDacuraServer.php");
/**
 * LD Service - generic functionality common to all linked data objects.
 * 
 * Services (ontology, that extend the LD service are processed through the linked data processing pipeline
 * and a default user-interface that can be specialised by derived classes
 *
 * @package ld
 * @author Chekov
 * @license: GPL v2
 */
class LdService extends DacuraService {
	/** @var string the screen that is loaded when the service is invoked without any url arguments */
	var $default_screen = "list";
		
	/**
	 * Loads the generic ld css and the various linked data javascript libraries
	 * the id of the ld service is specified in each case to allow them to be inherited by derived classes
	 * @see DacuraService::init()
	 */
	function init(){
		if($this->name() != "ld"){
			$this->included_scripts[] = $this->get_service_script_url("dacura.ld.js", "ld");
		}
		$this->included_scripts[] = $this->get_service_script_url("dacura.upload.js", "upload");
		$this->included_css[] = $this->get_service_file_url("style.css", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ldoviewer.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ldo.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ldoupdate.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ldoupdateviewer.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ldresult.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ldgraphresult.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/rvo.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/ontologyimporter.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("jslib/dqsconfigurator.js", "ld");
	}
	
	/**
	 * Returns the current linked data type name (may be a derived type)
	 * @return string
	 */
	function ldtn(){
		$tn = $this->name();
		if($tn == "ld") $tn = "linked data object";
		return $tn;
	}
	
	/**
	 * In the whole LD sub-system there are only three top level screens
	 * We are either viewing a LD object, viewing a list of LD objects or viewing an update to a LD object
	 * @see DacuraService::getScreenForCall()
	 */
	function getScreenForCall(){
		if($this->screen == "list"){
			return "list";
		}
		elseif($this->screen == "update"){
			return "update";
		}
		return "view";
	}

	function compareFacets($a, $b){
		if($b != 'admin' && $a == "approve"){ //approve is above all but admin
			return true;
		}
		return parent::compareFacets($a, $b);
	}
	
	function getMinimumFacetForAccess(DacuraServer &$dacura_server){
		if($this->screen == "list"){
			return "create";
		}
		elseif($this->screen == "update"){
			return "inspect";
		}
		return $this->getScreenForAC($dacura_server);
	}
	
	/**
	 * Overrides method to support screen inheritance from ld service
	 * If the screen exists in the derived service, it is loaded, otherwise the generic ld screen is loaded 
	 * ui inheritance and specialisation mechanism
	 * @see DacuraService::renderScreen()
	 * @param string $screen the name of the screen to render
	 * @param array $params name value associate array of substitution parameters to be passed to screen
	 * @param string $other_service if set, the screen will be taken from this service, rather than the current one which is default
	 */
	public function renderScreen($screen, $params, $other_service = false){
		if($this->name() != "ld" && !$other_service){ //derived class that has no other service explicitly set.
			$service =& $this;
			global $dacura_server;
			//first check to see if the service has defined its own screen
			$f = $this->mydir."screens/$screen.php";
			if(file_exists($f)){
				include_once($f);
			}
			else {
				//if not then use the one inherited from the ld service....
				return $this->renderScreen($screen, $params, "ld");
			}
		}
		else {
			return parent::renderScreen($screen, $params, $other_service);
		}
	}
	
	/**
	 * Subscreen include - used by derived services to first try loading the service's subscreen, 
	 * then loading the ld base class's server
	 * @param string $screen the name of the subscreen in question
	 * @return string 
	 */
	function ssInclude($screen){
		$f = $this->mydir."screens/$screen.php";
		if(file_exists($f)){
			return $f;
		}
		else if($this->name() != "ld"){
			$f = $this->getSystemSetting('path_to_services')."ld/screens/".$screen .".php";			
			if(file_exists($f)){
				return $f;
			}
		}		
		return $screen.".php";
	}
	
	/**
	 * The default approach is to treat url args /a/b/c/d/ as name value pairs (a=b, c=d) 
	 * But in the linked data subsystem, the only url patterns that are used are
	 * @see DacuraService::loadArgsFromBrowserURL()
	 */
	function loadArgsFromBrowserURL($sections){
		if(count($sections) > 0){
			$this->screen = array_shift($sections);
			$this->args = $sections;
		}
		else {
			$this->screen = $this->default_screen;
		}
	}

	/**
	 * Renders a screen when viewed in full page mode
	 * @param DacuraServer $dacura_server
	 */
	public function renderFullPage(LdDacuraServer &$dacura_server){
		if(!$this->showFullPage($dacura_server) && $this->getScreenForCall() == "view"){
			return $this->renderContentDirectly($dacura_server);
		}
		else {
			return parent::renderFullPage($dacura_server);
		}
	}
	
	/** 
	 * Do we need to show the full page or just the linked data contents? 
	 * 
	 * Provides support for rendering content directly in response to browser Accept header
	 * @param LdDacuraServer $dacura_server
	 * @return boolean
	 */
	function showFullPage(LdDacuraServer &$dacura_server){
		if(isset($_GET['direct']) && $_GET['direct']){
			return false;
		}
		$available_types = $dacura_server->getAvailableMimeTypes();
		$mime = getBestSupportedMimeType($available_types);
		if($mime != "text/html"){
			return false;
		}
		return true;
	}
	
	/**
	 * Renders the linked data content directly (with the appropriate MIME content type, etc) without wrapping it in html
	 * @param LdDacuraServer $dacura_server
	 * @return boolean
	 */
	function renderContentDirectly(LdDacuraServer $dacura_server){
		$type = isset($_GET['ldtype']) ? $_GET['ldtype'] : $this->servicename;
		$dr = $dacura_server->getLDO($this->screen, $type);
		if(!$dr->is_accept()){
			return $dacura_server->writeDecision($dr);
		}
		$options = $this->readLDViewArgs();
		$available_types = $dacura_server->getAvailableMimeTypes();
		$mime = getBestSupportedMimeType($available_types);
		if($mime && $mime != $dacura_server->getMimeTypeForFormat("html")){
			if(isset($options['format'])){
				if($dacura_server->getMimeTypeForFormat($options['format']) != $mime){
					return $dacura_server->write_http_error(400, "mismatch betweeen format request ".$options['format']. " and mime type $mime");
				}
			}
			$format = $dacura_server->getFormatForMimeType($mime);
		}
		elseif(isset($options['format'])){
			$format = $options['format'];	
		}
		if(!$dacura_server->supportsFormat($format)){
			$format = "json";
		}
		$dacura_server->display_content_directly($dr->result, $format, $options['options']);
	}
		
	/**
	 * Loads the service parameters that are common to all linked data screens
	 * and calls the methods to load parameters for specific sub-screens. 
	 * @param {String} screen the name of the screen that is being viewed ('list' or 'view')
	 * @param {LdDacuraServer} $dacura_server currently active DacuraServer object
	 * (non-PHPdoc)
	 * @see DacuraService::getParamsForScreen()
	 */
	function getParamsForScreen($screen, LdDacuraServer &$dacura_server){
		$params = array("image" => $this->furl("image", "services/".$this->name().".png"));
		if($this->name() != "ld") {
			$params['ldtype'] = $this->name();
		}
		$params['dt'] = true;
		$params['tooltip_config'] = $this->getSystemSetting('tooltip_config', array());
		$params['help_tooltip_config'] = $this->getSystemSetting('help_tooltip_config', array());
		$params["breadcrumbs"] = array(array(), array());
		$user = $dacura_server->getUser();		
		if($screen == "list"){
			$params['subscreens'] = $this->getListSubscreens($dacura_server, $user);
			$this->loadParamsForListScreen($params, $dacura_server);
		}
		elseif($screen == "view"){
			if($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$params['subscreens'] = $this->getViewSubscreens($dacura_server, $user);				
			$this->loadParamsForViewScreen($id, $params, $dacura_server, $user);				
		}
		elseif($screen == "update"){
			$id = "update/".implode("/", $this->args);
			$params['subscreens'] = $this->getUpdateSubscreens($dacura_server, $user);				
			$this->loadParamsForUpdateScreen($id, $params, $dacura_server);				
		}
		else {
			return $this->failure_result("Attempt to load unknown LD screen $screen", 404);
		}
		return $params;
	}
	
	/**
	 * What subscreens should we load for the list screen?
	 * @param LdDacuraServer $dacura_server
	 * @param DacuraUser $u
	 * @return array<string> the ids of the subscreens (tabs) to load
	 */
	function getListSubscreens(LdDacuraServer &$dacura_server, &$u){
		$subscreens = array();
		if($dacura_server->userHasFacet("list")){
			$subscreens[] = 'ldo-list';
		}
		if($dacura_server->userHasFacet("inspect")){
			$subscreens[] = 'update-list';
		}
		if($dacura_server->userHasFacet("create")){
			$subscreens[] = 'ldo-create';
		}
		if($dacura_server->userHasFacet("export")){
			$subscreens[] = 'ldo-export';
		}
		return $subscreens;
	}

	/**
	 * What subscreens should we load for the view screen? 
	 * @param LdDacuraServer $dacura_server
	 * @param DacuraUser $u
	 * @return array<string> the ids of the subscreens (tabs) to load
	 */
	function getViewSubscreens(LdDacuraServer &$dacura_server, &$u){
		$s = array();
		if($dacura_server->userHasFacet("inspect")){
			$s[] = "ldo-history";
			$s[] = "ldo-updates";
		}
		if($dacura_server->userHasFacet("view")){
			$s[] = "ldo-contents";
			$s[] = "ldo-meta";
		}
		return $s;
	}

	/* List Screen Related Functions */		
	
	/**
	 * Loads the parameters specific to the list screen
	 * @param array $params key value array of parameters to be substituted into the html via php variable interpolation
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForListScreen(&$params, LdDacuraServer &$dacura_server){
		$params["title"] = $this->smsg("list_page_title");
		$params["subtitle"] = $this->smsg("list_page_subtitle");
		if(in_array('ldo-list', $params['subscreens'])){
			$this->loadParamsForObjectListTab($params, $dacura_server);			
		}
		if(in_array('update-list', $params['subscreens'])){
			$this->loadParamsForUpdateListTab($params, $dacura_server);			
		}
		if(in_array('ldo-create', $params['subscreens'])){
			$this->loadParamsForCreateTab($params, $dacura_server);
		}
        if(in_array('ldo-export', $params['subscreens'])){
			$this->loadParamsForExportTab($params, $dacura_server);
		}
	}

	/**
	 * Loads the necessary parameters from php -> html / js for drawing the list subscreen
	 * @param array $params the parameters to be interpolated into the object list html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForObjectListTab(&$params, LdDacuraServer &$dacura_server){
		$options = $this->getLDListArgs();
		if($options){
			$params['fetch_args'] = json_encode($options);				
		}
		$params["ld_list_title"] = $this->smsg('ld_list_title');	
		$params['objectlist_intro_msg'] = $this->smsg('list_objects_intro');
		$params['ldo_multiselect_options'] = json_encode(DacuraObject::$valid_statuses);
		$ldtab = $this->getDatatableSetting("ld", false);
		if($this->cid() != "all"){
			$ldtab['aoColumns'][3] = array("bVisible" => false);
		}
		if($this->name() != "ld"){
			$ldtab['aoColumns'][2] = array("bVisible" => false);
		}
		$params['multi_ldo_update_allowed'] = $dacura_server->userHasFacet("approve");//facet
		if(!$params['multi_ldo_update_allowed']){
			$ldtab['aoColumns'][11] = array("bVisible" => false);					
		}		
		$params['ldo_datatable'] = json_encode($ldtab);
	}
	
	/**
	 * Unifies the options set in the request (?a=b&c=d ..) with the configured options
	 * @return array - options array
	 */
	function getLDListArgs(){
		$args = $this->getServiceSetting("ldolist_fixed_filters", array());
		if(isset($_GET['options']) && is_array($_GET['options'])){
			$choices = $this->getServiceSetting("ldolist_user_filters", array());
			foreach($choices as $choice){
				if(!isset($args[$choice]) && isset($_GET['options'][$choice])){
					$args[$choice] = $_GET['options'][$choice];
				}
			}
		}
		return $args;
	}	
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the update list subscreen
	 * @param array $params the parameters to be interpolated into the object list html subscreen
	 * @param LdDacuraServer $dacura_server
	 */	
	function loadParamsForUpdateListTab(&$params, LdDacuraServer &$dacura_server){
		$options = $this->getLDListUpdatesArgs("uoptions");
		if($options){
			$params['fetch_update_args'] = json_encode($options);
		}
		$params["ld_updates_title"] = $this->smsg('ld_updates_title');
		$params['updates_intro_msg'] = $this->smsg('list_updates_intro');
        $params['ld_export_query_title'] = $this->smsg('ld_export_query_title');
		$udtab = $this->getDatatableSetting("updates", false);
		if($this->cid() != "all"){
			$udtab['aoColumns'][3] = array("bVisible" => false);
		}
		if($this->name() != "ld"){
			$udtab['aoColumns'][2] = array("bVisible" => false);
		}
		$params['multi_updates_update_allowed'] = $dacura_server->userHasFacet("approve");//facet
		if(!$params['multi_updates_update_allowed']){
			$udtab['aoColumns'][12] = array("bVisible" => false);
		}
		else {
			$params['updates_multiselect_options'] = json_encode(DacuraObject::$valid_statuses);				
		}
		$params['update_datatable'] = json_encode($udtab);
	}

	/**
	 * Figures out which options should be set in the call to the ldo list updates api call - mixed from fixed settings and configurable ones.
	 * @return array - options array
	 */
	function getLDListUpdatesArgs($opt = "options"){
		$args = $this->getServiceSetting("updatelist_fixed_filters", array());
		if(isset($_GET[$opt]) && is_array($_GET[$opt])){
			$choices = $this->getServiceSetting("updatelist_user_filters", array());
			foreach($choices as $choice){
				if(!isset($args[$choice]) && isset($_GET[$opt][$choice])){
					$args[$choice] = $_GET[$opt][$choice];
				}
			}
		}
		return $args;
	}
	
	/**
	 * Placeholder for loading the export ldo tab
	 * @param array $params
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForExportTab(&$params, LdDacuraServer &$dacura_server){
	}
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the create subscreen
	 * @param array $params the parameters to be interpolated into the object list html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForCreateTab(&$params, LdDacuraServer &$dacura_server){
		$params['create_ldo_config'] = array("display_type" => "create", "show-header" => 2, "objtype" => $this->name(), "header-html" => "New " . $this->ldtn(). " details");
		$params["demand_id_token"] = $this->getServiceSetting("demand_id_token", "@id");
		$params['create_options'] = json_encode($this->getCreateOptions(false));
		$params['test_create_options'] = json_encode($this->getCreateOptions(true));
		$params['show_create_button'] = $this->getServiceSetting("show_create_button", 1);
		$params['show_test_button'] = $this->getServiceSetting("show_test_button", 1);
		$cf = $this->getServiceSetting("create_ldoviewer_config", array());
		if(!isset($cf['edit_formats'])){
			$cf['edit_formats'] = LDO::$valid_input_formats;
		}
		$cf['show_options'] = true;
		$cf['show_buttons'] = false;
		$ns = $dacura_server->createDependantService("upload");
		if($dacura_server->userHasFacet("manage", $ns)){
			$cf['fileupload'] = true;
		}
		$params['create_ldoviewer_config'] = json_encode($cf);
		//strings
		$params["ld_create_title"] = $this->smsg('ld_create_title');
		$params['create_intro_msg'] = $this->smsg('create_ldo_intro');
		$params['create_button_text'] = $this->smsg('create_button_text');
		$params['test_create_button_text'] = $this->smsg('test_create_button_text');
		//forms
		$params['create_ldo_fields'] = $this->sform("create_ldo_fields");
		$params['create_ldo_fields']['ldtype']['options'] = LDO::$ldo_types;
		if(isset($params['create_ldo_fields']['image'])){
			$params['create_ldo_fields']['image']['default_value'] = $this->get_system_file_url("image", "services/".$this->name()."_icon.png");
		}
		if(isset($params['create_ldo_fields']['format'])){
			$params['create_ldo_fields']['format']['options'] = array_merge(array("" => "Auto-detect"), LDO::$valid_input_formats);
		}
		if($this->name() != "ld"){
			unset($params['create_ldo_fields']['ldtype']);
		}
		$params['specify_create_status_allowed'] = $dacura_server->userHasFacet("approve");
		if(!$params['specify_create_status_allowed']){
			unset($params['create_ldo_fields']['status']);
		}
		//this is only really for graph create (I think..)
		$params['available_ontologies'] = json_encode($dacura_server->ontversions);
	}
	
	/** 
	 * What options should be sent and read from the Dacura Create API 
	 * merges those updates specified by the request with those configured by the user and filters out the unknown ones
	 * @param boolean $test_flag
	 */
	function getCreateOptions($test_flag, $uopts = false){
		$ting = $test_flag ? "test_create" : "create";
		if($uopts === false){
			return $this->getServiceSetting($ting."_default_options", array());
		}
		$args = $this->getServiceSetting($ting."_fixed_options", array());
		$choices = $this->getServiceSetting("create_user_options", array());
		foreach($choices as $choice){
			if(!isset($args[$choice]) && isset($uopts[$choice])){
				$args[$choice] = $uopts[$choice];
			}
		}
		return $args;
	}	

	/* View Screen Related Functions */

	/**
	 * Loads the parameters specific to the view screen
	 * @param string $id the id of the linked data object. 
	 * @param array $params key value array of parameters to be substituted into the html via php variable interpolation
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForViewScreen($id, &$params, LdDacuraServer &$dacura_server, $user = null){
		$params["id"] = $id;
		$params["title"] = $this->smsg("view_page_title");
		$params["subtitle"] = $this->smsg("view_page_subtitle");
		$params["description"] = $this->smsg("view_page_description");
		$lv = $this->getLDArgs("ldoview");
		if(!$dacura_server->userHasFacet("inspect")){
			$lv['options']['history'] = 0;
			$lv['options']['updates'] = 0;
			$lv['options']['analysis'] = 0;
		}
		$params['fetch_args'] = json_encode($lv);
		$ldov = $this->getLDOViewerConfig("ldo_viewer_config", $dacura_server);
		$params['ldov_config'] = json_encode($ldov);
		if(in_array('ldo-contents', $params['subscreens'])){
			$this->loadParamsForContentsTab($id, $params, $dacura_server);
		}
		if(in_array('ldo-meta', $params['subscreens'])){
			$this->loadParamsForMetaTab($id, $params, $dacura_server);
		}
		if(in_array('ldo-analysis', $params['subscreens'])){
			$this->loadParamsForAnalysisTab($id, $params, $dacura_server);
		}
		if(in_array('ldo-history', $params['subscreens'])){
			$this->loadParamsForHistoryTab($id, $params, $dacura_server);
		}
		if(in_array('ldo-updates', $params['subscreens'])){
			$this->loadParamsForUpdatesTab($id, $params, $dacura_server);
		}	
	}
	
	/** 
	 * Loads the various configuration fields to be sent to the LDOViewer javascript object
	 * @param string $id - the id of the configuration variable in the ld settings file to start from
	 * @return Ambigous <multitype:string , string, string, mixed>
	 */
	function getLDOViewerConfig($id, LdDacuraServer &$dacura_server){
		$ldov_config = $this->getServiceSetting($id, array());
		if(!isset($ldov_config['view_formats'])){
			$ldov_config['view_formats'] = LDO::$valid_display_formats;
		}
		if(!isset($ldov_config['edit_formats'])){
			$ldov_config['edit_formats'] = LDO::$valid_input_formats;
		}
		if(!isset($ldov_config['update_options'])){
			$ldov_config['update_options'] = $this->getLDOptions("ldo_update");
		}
		if(!isset($ldov_config['test_update_options'])){
			$ldov_config['test_update_options'] = $this->getLDOptions("ldo_test_update");
		}
		if(!isset($ldov_config['view_options'])){
			$ldov_config['view_options'] = $this->getViewOptions();
		}
		if($dacura_server->userHasFacet("admin")){
			if(!isset($ldov_config['editmode_options'])){
				$ldov_config['editmode_options'] =  array("replace" => "Replace Mode", "update" => "Update Mode");
			}
			if(!isset($ldov_config['result_options'])){
				$ldov_config['result_options'] = array("No LDO result returned", "Updated LDO returned", "LDO Update Object Returned");
			}
			if(!isset($ldov_config['view_graph_options'])){
				$ldov_config['view_graph_options'] = array("ld" => "LD Object Store", "dqs" => "DQS Triplestore", "meta" => "metadata", "update" => "Update Store");
			}
		}
		if(!isset($ldov_config['view_actions'])){
			$va = array();
			if($dacura_server->userHasFacet("manage")){
				$va = array("restore" => "Restore this version", "edit" => "Edit", "import" => "Import");
			}
			if($dacura_server->userHasFacet("export")){
				$va["export"] = "Export";
			}
			if($dacura_server->userHasFacet("approve")){
			 	$va = array_merge($va, array("accept" => "Publish", "reject" => "Reject", "pending" => "Unpublish"));
			}
			$ldov_config['view_actions'] = $va;
		}
		$ns = $dacura_server->createDependantService("upload");
		if($dacura_server->userHasFacet("manage", $ns)){
			$ldov_config['fileupload'] = true;
		}
		$ldov_config['show_buttons'] = true;
		return $ldov_config;
	}
	
	function getLDOUpdateViewerConfig($id, LdDacuraServer &$dacura_server){
		$ldov_config = $this->getServiceSetting($id, array());
		if(!isset($ldov_config['view_formats'])){
			$ldov_config['view_formats'] = LDO::$valid_display_formats;
		}
		if(!isset($ldov_config['edit_formats'])){
			$ldov_config['edit_formats'] = LDO::$valid_input_formats;
		}
		if(!isset($ldov_config['update_options'])){
			$ldov_config['update_options'] = $this->getLDOptions("update_update");
		}
		if(!isset($ldov_config['test_update_options'])){
			$ldov_config['test_update_options'] = $this->getLDOptions("test_update_update");
		}
		if(!isset($ldov_config['view_options'])){
			$ldov_config['view_options'] = $this->getViewOptions();
		}
		if(!isset($ldov_config['view_actions']) && $dacura_server->userHasFacet("approve")){
			$ldov_config['view_actions'] = array("approve" => "Approve");
		}
		return $ldov_config;
	}
	
	
	/**
	 * Reads the arguments supported by the linked data view api
	 * @return array arguments array
	 */
	function getLDArgs($prefix){
		$args = $this->getServiceSetting($prefix."_fixed_args", array());
		$defs = $this->getServiceSetting($prefix."_default_args", array());
		$choices = $this->getServiceSetting($prefix."_user_args", array());
		foreach($choices as $choice){
			if(!isset($args[$choice])){
				if(isset($_GET[$choice])){
					$args[$choice] = $_GET[$choice];
				}
				elseif(isset($defs[$choice])){
					$args[$choice] = $defs[$choice];
				}
			}
		}
		$opts = $this->getLDOptions($prefix);
		if($opts){
			$args['options'] = $opts;
		}
		return $args;
	}
	
	/**
	 * Unifies the options set in the request (?option[a]=b&option[c]=d ..) with the configured options
	 * @return array - options array
	 */
	function getLDOptions($prefix){
		$args = $this->getServiceSetting($prefix."_fixed_options", array());
		$defs = $this->getServiceSetting($prefix."_default_options", array());
		$choices = $this->getServiceSetting($prefix."_user_options", array());
		if(isset($_GET['options']) && is_array($_GET['options'])){
			foreach($choices as $choice){
				if(!isset($args[$choice])){
					if(isset($_GET['options'][$choice])){
						$args[$choice] = $_GET['options'][$choice];
					}
				}
			}
		}
		foreach($choices as $choice){
			if(isset($defs[$choice]) && !isset($args[$choice])){
				$args[$choice] = $defs[$choice];
			}
		}
		return $args;
	}	

	/**
	 * Loads the necessary parameters from php -> html / js for drawing the contents subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the contents html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForContentsTab($id, &$params, LdDacuraServer &$dacura_server){
		$params['contents_screen_title'] = $this->smsg('contents_screen_title');
		$params['contents_intro_msg'] = $this->smsg('view_contents_intro');
		$params['update_contents_args'] = json_encode($this->getLDArgs("ldo_update"));
		$params['test_update_contents_args'] = json_encode($this->getLDArgs("ldo_test_update"));
	}
	
	/** 
	 * Read the default view options from config
	 * @return array options array
	 */
	function getViewOptions(){
		$a = array("ns" => "Namespaces", "plain" => "Plain", "addressable" => "Addressable");
		return $a;
	}
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the history subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the history html subscreen
	 * @param LdDacuraServer $dacura_server
	 */	
	function loadParamsForHistoryTab($id, &$params, &$dacura_server){
		$params['history_screen_title'] = $this->smsg('history_screen_title');	
		$params['history_intro_msg'] = $this->smsg('view_updates_intro');
		$params['history_datatable'] = $this->getDatatableSetting("history");
	}
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the analysis subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the analysis html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForAnalysisTab($id, &$params, LdDacuraServer &$dacura_server){}	

	/**
	 * Loads the necessary parameters from php -> html / js for drawing the meta subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the meta html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForMetaTab($id, &$params, LdDacuraServer &$dacura_server){
		$params['meta_screen_title'] = $this->smsg('meta_screen_title');	
		$params['meta_intro_msg'] = $this->smsg('view_meta_intro');
		$display = $dacura_server->userHasFacet("manage") ? "update" : "view";
		$params['update_meta_config'] = array("display_type" => $display, "show-header" => 2, "objtype" => $this->name(), "header-html" => $this->ldtn(). " Metadata");
		$params['show_update_meta_button'] = $dacura_server->userHasFacet("approve");
		$params['show_update_meta_test_button'] = $dacura_server->userHasFacet("manage");
		$params['update_meta_button_text'] = $this->smsg('update_meta_button');
		$params['test_update_meta_button_text'] = $this->smsg('test_update_meta_button');
		$params['update_meta_fields'] = $this->sform("update_meta_fields");		
		$params['test_update_meta_options'] = json_encode($this->getLDOptions("ldo_test_meta"));
		$params['update_meta_options'] = json_encode($this->getLDOptions("ldo_meta"));
	}	
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the updates subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the updates html subscreen
	 * @param LdDacuraServer $dacura_server
	 */	
	function loadParamsForUpdatesTab($id, &$params, LdDacuraServer &$dacura_server){	
		$params['updates_screen_title'] = $this->smsg('updates_screen_title');	
		$params['updates_intro_msg'] = $this->smsg('view_updates_intro');
		$udtab = $this->getDatatableSetting("ldoupdates", false);
		$params['multi_updates_update_allowed'] = $dacura_server->userHasFacet("approve");
		if(!$params['multi_updates_update_allowed']){
			$udtab['aoColumns'][10] = array("bVisible" => false);
		}
		else {
			$params['updates_multiselect_options'] = json_encode(DacuraObject::$valid_statuses);
		}
		$params['updates_datatable'] = json_encode($udtab);
	}
	
	/* Update Screen Related Functions */
	
	/**
	 * What subscreens should we load for the update screen?
	 * @param LdDacuraServer $dacura_server
	 * @param DacuraUser $u
	 * @return array<string> the ids of the subscreens (tabs) to load
	 */
	function getUpdateSubscreens(LdDacuraServer &$dacura_server, &$u){
		return array("update-contents", "update-commands", "ldo-meta", "update-before", "update-after");
	}
	
	/**
	 * Loads the parameters specific to the view update screen
	 * @param string $id the id of the update
	 * @param array $params key value array of parameters to be substituted into the html via php variable interpolation
	 * @param LdDacuraServer $dacura_server
	 */		
	function loadParamsForUpdateScreen($id, &$params, &$dacura_server){
		$tn = (isset($params['ldtype']) && $params['ldtype']) ? $params['ldtype'] : "LDO";
		$params["id"] = $id;
		
		$params["title"] = $this->smsg("update_screen_title");
		$params["subtitle"] = $this->smsg("update_screen_subtitle");
		if(in_array("ldo-meta", $params['subscreens'] )){
			$this->loadParamsForUpdateMetaTab($id, $params, $dacura_server);
		}
		if(in_array("update-contents", $params['subscreens'])){
			$params["update_contents_screen_title"] = $tn . " " . $this->getServiceSetting("update_contents_screen_title", "update contents");
			$params['view_update_contents_intro_msg'] = $this->smsg('view_update_contents_intro_msg');
		}
		if(in_array("update-after", $params['subscreens'])){
			$params["update_after_screen_title"] = $tn . " " . $this->getServiceSetting("update_after_screen_title", "after update");
			$params['view_after_intro_msg'] = $this->smsg('view_after_intro_msg');
		}
		if(in_array("update-before", $params['subscreens'])){
			$params["update_before_screen_title"] = $tn . " " . $this->getServiceSetting("update_before_screen_title", "before update");
			$params['view_before_intro_msg'] = $this->smsg('view_before_intro_msg');
		}
		$params['fetch_args'] = json_encode($this->getLDArgs("update_view"));
		$params['update_button_text'] = $this->smsg('update_update_button_text');
		$params['test_update_button_text'] = $this->smsg('test_update_update_button_text');
		$params['update_args'] = json_encode($this->getLDArgs("update_update"));
		$params['test_update_args'] = json_encode($this->getLDArgs("test_update_update"));
		$params['update_commands_screen_title'] = $this->smsg("update_commands_screen_title");
		$pldov = $this->getLDOUpdateViewerConfig("ldoupdate_viewer_config", $dacura_server);
		$params['ldov_config'] = json_encode($pldov);
		return $params;
	}
	

	/**
	 * Loads the necessary parameters from php -> html / js for drawing the update metadata subscreen
	 * @param string $id the id of the update
	 * @param array $params the parameters to be interpolated into the meta html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForUpdateMetaTab($id, &$params, LdDacuraServer &$dacura_server){
		$params['meta_screen_title'] = $this->smsg('update_meta_screen_title');
		$params['meta_intro_msg'] = $this->smsg('view_update_meta_intro');
		$display = $dacura_server->userHasFacet("manage") ? "update" : "view";
		$params['update_meta_config'] = array("display_type" => $display, "show-header" => 2, "objtype" => "update", "header-html" => "Update to ".$this->ldtn(). " Metadata");
		$params['show_update_meta_button'] = $dacura_server->userHasFacet("approve");
		$params['show_update_meta_test_button'] = $dacura_server->userHasFacet("manage");
		$params['update_meta_button_text'] = $this->smsg('update_meta_button');
		$params['test_update_meta_button_text'] = $this->smsg('test_update_meta_button');
		$params['update_meta_fields'] = $this->sform("update_meta_fields");
		$params['test_update_meta_options'] = json_encode($this->getLDOptions("update_test_meta"));
		$params['update_meta_options'] = json_encode($this->getLDOptions("update_meta"));
	}
}
