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
		$this->included_css[] = $this->get_service_file_url("style.css", "ld");
		$this->included_scripts[] = $this->get_service_script_url("ldoviewer.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("ldo.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("ldoupdate.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("ldresult.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("ldgraphresult.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("rvo.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("ontologyimporter.js", "ld");
		$this->included_scripts[] = $this->get_service_script_url("dqsconfigurator.js", "ld");
	}
	
	function ldtn(){
		$tn = $this->name();
		if($tn == "ld") $tn = "linked data object";
		return $tn;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see DacuraService::getMinimumFacetForAccess()
	 */
	function getMinimumFacetForAccess(DacuraServer &$dacura_server){
		return true;
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
			$options = $this->readLDUpdateArgs();
			$params['fetch_args'] = json_encode($options);
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
	function getListSubscreens(LdDacuraServer &$dacura_server, DacuraUser &$u){
		return array("ldo-list", "update-list", "ldo-create");
	}

	/**
	 * What subscreens should we load for the list screen?
	 * @param LdDacuraServer $dacura_server
	 * @param DacuraUser $u
	 * @return array<string> the ids of the subscreens (tabs) to load
	 */
	function getUpdateSubscreens(LdDacuraServer &$dacura_server, DacuraUser &$u){
		return array("update-contents", "update-before", "update-after");
	}
	
	/**
	 * What subscreens should we load for the view screen? 
	 * @param LdDacuraServer $dacura_server
	 * @param DacuraUser $u
	 * @return array<string> the ids of the subscreens (tabs) to load
	 */
	function getViewSubscreens(LdDacuraServer &$dacura_server, DacuraUser &$u){
		$s = array("ldo-meta", "ldo-history", "ldo-contents", "ldo-updates");
		if($this->name() != "ld"){
			$s[] = array("ldo-analysis");
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
		$params['multi_ldo_update_allowed'] = true;//facet
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
		$udtab = $this->getDatatableSetting("updates", false);
		if($this->cid() != "all"){
			$udtab['aoColumns'][3] = array("bVisible" => false);
		}
		if($this->name() != "ld"){
			$udtab['aoColumns'][2] = array("bVisible" => false);
		}
		$params['multi_updates_update_allowed'] = true;//facet
		if(!$params['multi_updates_update_allowed']){
			$udtab['aoColumns'][11] = array("bVisible" => false);
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
	 * Loads the necessary parameters from php -> html / js for drawing the create subscreen
	 * @param array $params the parameters to be interpolated into the object list html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForCreateTab(&$params, LdDacuraServer &$dacura_server){
		$params['create_ldo_config'] = array("display_type" => "create", "show-header" => 2, "objtype" => $this->name(), "header-html" => "New " . $this->ldtn(). " details");
		$params["demand_id_token"] = $this->getServiceSetting("demand_id_token", "@id");
		$params['create_options'] = json_encode($this->getCreateOptions(false));
		$params['test_create_options'] = json_encode($this->getCreateOptions(true));
		$params['show_create_button'] = $this->getServiceSetting("show_create_button", true);
		$params['show_test_button'] = $this->getServiceSetting("show_test_button", true);
		$cf = $this->getServiceSetting("create_ldoviewer_config", array());
		if(!isset($cf['edit_formats'])){
			$cf['edit_formats'] = LDO::$valid_input_formats;
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
		$params['specify_create_status_allowed'] = true;//facet
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
	function loadParamsForViewScreen($id, &$params, LdDacuraServer &$dacura_server, DacuraUser &$user){
		$options = $this->getLDViewArgs();
		if($options){
			$params['fetch_args'] = json_encode($options);
		}	
		$params['ldoviewer_init'] = array();
		$params["id"] = $id;
		$params["title"] = $this->smsg("view_page_title");
		$params["subtitle"] = $this->smsg("view_page_subtitle");
		$params["description"] = $this->smsg("view_page_description");
		$params['direct_create_allowed'] = true;
		$params['valid_view_formats'] = LDO::$valid_display_formats;
		$params['default_view_options'] = $this->getDefaultViewOptions();
		$params['editmode_options'] = array("replace" => "Replace Mode", "update" => "Update Mode");
		$params['update_result_options'] = array("No LDO result returned", "Updated LDO returned", "LDO Update Object Returned");
		$params['view_graph_options'] = array("ld" => "LD Object Store", "dqs" => "DQS Triplestore", "meta" => "metadata", "update" => "Update Store");
		$params['view_actions'] = array("restore" => "Restore this version", "edit" => "Edit", "import" => "Import", "export" => "Export", "accept" => "Publish", "reject" => "Reject", "pending" => "Unpublish");		
		$params['valid_input_formats'] = LDO::$valid_input_formats;
		$params['dqs_schema_tests'] = json_encode(RVO::getSchemaTests());
		$params['default_dqs_tests'] = json_encode(RVO::getSchemaTests(false));
		$params['dqs_instance_tests'] = json_encode(RVO::getInstanceTests());
		$params['default_instance_dqs_tests'] = json_encode(RVO::getInstanceTests(false));
		$avs = $dacura_server->ontversions;
		//an ontology is not available to itself...
		if($this->name() == "ontology" && isset($avs[$id])) unset($avs[$id]);
		$params['available_ontologies'] = json_encode($avs);
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
	 * Reads the optional arguments supported by the linked data view api
	 * @return array options array
	 */
	function getLDViewArgs(){
		$args = array();
		if(isset($_GET['version'])){
			$args['version'] = $_GET['version'];
		}
		if(isset($_GET['mode'])){
			$args['mode'] = $_GET['mode'];
		}
		else {
			$args['mode'] = "view";
		}
		if(isset($_GET['format'])){
			$args['format'] = $_GET['format'];
		}
		else {
			$args['format'] = "json";
		}
		if(isset($_GET['ldtype']) && $_GET['ldtype']){
			$args['ldtype'] = $_GET['ldtype'];
		}
		if(isset($_GET['options'])){
			$args['options'] = $_GET['options'];
		}
		else {
			$args['options'] = array("history" => 1, "updates" => 1, "ns" => 1, "addressable" => 0, "analysis" => 1);
		}
		return $args;
	}
	
	/** 
	 * Read the default view options from config
	 * @return array options array
	 */
	function getDefaultViewOptions(){
		$a = array("ns" => array("title" => "Namespace prefixes", "value" => 1), "plain" => array("title" => "Plain", "value" => 0), "addressable" => array("title" => "Replace Blank Nodes", "value" => 1));
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
	function loadParamsForAnalysisTab($id, &$params, LdDacuraServer &$dacura_server){
		$params['analysis_screen_title'] = $this->smsg('analysis_screen_title');	
		$params['analysis_intro_msg'] = $this->smsg('view_analysis_intro');		
	}	

	/**
	 * Loads the necessary parameters from php -> html / js for drawing the meta subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the meta html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForMetaTab($id, &$params, LdDacuraServer &$dacura_server){
		$params['meta_screen_title'] = $this->smsg('meta_screen_title');	
		$params["ldo_analysis_fields"] = $this->sform("ldo_analysis_fields");
		$params['update_meta_button_text'] = $this->smsg('update_meta_button');
		$params['test_update_meta_button_text'] = $this->smsg('test_update_meta_button');
		$params['update_meta_fields'] = $this->sform("update_meta_fields");		
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
		$params['updates_datatable'] = $this->getDatatableSetting("ldoupdates");		
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
		$params['update_options'] = $this->getServiceSetting("update_options", array());	
	}
	
	/* Update Screen Related Functions */
	
	/**
	 * Loads the parameters specific to the list screen
	 * @param array $params key value array of parameters to be substituted into the html via php variable interpolation
	 * @param LdDacuraServer $dacura_server
	 */		
	function loadParamsForUpdateScreen($id, &$params, &$dacura_server){
		$params['ldoviewer_init'] = array();
		if(in_array("ldo-meta", $params['subscreens'] )){
			$params['meta_intro_msg'] = $this->smsg('view_update_meta_intro');
		}
		if(in_array("ldo-analysis", $subscreens)){
			$params['analysis_intro_msg'] = $this->smsg('view_update_analysis_intro');
		}
		if(in_array("ldo-raw", $subscreens)){
			$params['raw_intro_msg'] = $this->smsg('update_raw_intro_msg');
		}
		if(in_array("ldo-after", $subscreens)){
			$params['after_intro_msg'] = $this->smsg('view_update_after_msg');
		}
		if(in_array("ldo-before", $subscreens)){
			$params['before_intro_msg'] = $this->smsg('view_before_after_msg');
		}
		$params["id"] = $id;
		$params["title"] = "Linked Data Object Manager";
		$params["subtitle"] = "Object view";
		$params["description"] = "View and update your managed linked data objects";
		$params['create_button_text'] = $this->smsg('raw_edit_text');
		$params['testcreate_button_text'] = $this->smsg('testraw_edit_text');
		$params['raw_ldo_fields']['format']['options'] = LDO::$valid_input_formats;
		$params['direct_create_allowed'] = true;
		$params['update_options'] = $this->getServiceSetting("update_options", array());
		return $params;
	}

	/*
	 * The various optional arguments supported by the linked data api
	 */
	function readLDUpdateArgs(){
		$args = array();
		if(isset($_GET['version'])){
			$args['version'] = $_GET['version'];
		}
		if(isset($_GET['mode'])){
			$args['mode'] = $_GET['mode'];
		}
		else {
			$args['mode'] = "view";
		}
		if(isset($_GET['format'])){
			$args['format'] = $_GET['format'];
		}
		else {
			$args['format'] = "json";
		}
		if(isset($_GET['ldtype']) && $_GET['ldtype']){
			$args['ldtype'] = $_GET['ldtype'];
		}
		if(isset($_GET['options'])){
			$args['options'] = $_GET['options'];
		}
		else {
			$args['options'] = array("show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 0, "analysis" => 1);
		}
		return $args;
	}

}
