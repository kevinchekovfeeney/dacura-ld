<?php
include_once("LdDacuraServer.php");
/**
 * LD Service - generic functionality common to all linked data objects.
 * 
 * Services that extend the LD service are processed through the linked data processing pipeline
 * and a default user-interface that can be specialised by derived classes
 *
 * @package ld
 * @author Chekov
 * @license: GPL v2
 */
class LdService extends DacuraService {
	
	var $default_screen = "list";
		
	function init(){
		if($this->name() == "ld"){
			$this->included_css[] = $this->get_service_file_url("style.css");
		}
		else {
			$this->included_css[] = $this->get_service_file_url("style.css", "ld");
			$this->included_scripts[] = $this->get_service_script_url("dacura.ld.js", "ld");
		}
		$this->included_scripts[] = $this->get_service_script_url("dacura.ldresult.js", "ld");
	}
	
	function getMinimumFacetForAccess(DacuraServer &$dacura_server){
		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see DacuraService::renderScreen()
	 * Overrides method to support screen inheritance from ld service...
	 * @param string $screen the name of the screen to render
	 * @param array $params name value associate array of substitution parameters to be passed to screen
	 * @param string $other_service if set, the screen will be taken from this service, rather than the current one which is default
	 * @return void
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
				//opr($params);
				//if not then use the one inherited from the ld service....
				return $this->renderScreen($screen, $params, "ld");
			}
		}
		else {
			return parent::renderScreen($screen, $params, $other_service);
		}
	}
	
	function loadArgsFromBrowserURL($sections){
		if(count($sections) > 0){
			$this->screen = array_shift($sections);
			$this->args = $sections;
		}
		else {
			$this->screen = $this->default_screen;
		}
	}
	
	function getScreenForCall(){
		if($this->screen == "list"){
			return "list";
		}
		elseif($this->screen == "update"){
			return "update";
		}
		return "view";
	}
	

	/* Organised by screen - list screen first */
	function readLDListArgs($is_post = false){
		$args = array();
		if(isset($_GET['options'])){
			$args = $_GET['options'];
		}
		else {
			$args = false;
		}
		return $args;
	}
	
	function readLDListUpdatesArgs($is_post = false){
		$args = array();
		if(isset($_GET['uoptions'])){
			$args = $_GET['uoptions'];
		}
		else {
			$args = false;
		}
		return $args;
	}
	
	
	/**
	 * Reads the optional arguments supported by the linked data view api
	 * @param string $is_post
	 * @return multitype:string multitype:number  unknown
	 */
	function readLDViewArgs($is_post = false){
		$args = array();
		if(isset($_GET['version']) || ($is_post && isset($_POST['version']))){
			$args['version'] = $is_post ? $_POST['version'] : $_GET['version'];
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

	function getDefaultViewOptions(){
		$a = array("ns" => array("title" => "Namespace prefixes", "value" => 1), "plain" => array("title" => "Plain", "value" => 0), "addressable" => array("title" => "Replace Blank Nodes", "value" => 1));
		return $a;
	}

	/*
	 * The various optional arguments supported by the linked data api
	 */
	function readLDUpdateArgs($is_post = false){
		$args = array();
		if(isset($_GET['version']) || ($is_post && isset($_POST['version']))){
			$args['version'] = $is_post ? $_POST['version'] : $_GET['version'];
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
	
	function showFullPage($dacura_server){
		if(isset($_GET['direct']) && $_GET['direct']){
			return false;
		}
		$available_types = $dacura_server->getAvailableMimeTypes();
		$mime = getBestSupportedMimeType($available_types);
		if($mime != "text/html"){
			return false;
		}
		return true;
		//does accept include 
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
		$params["breadcrumbs"] = array(array(), array());
		if($screen == "list"){
			$options = $this->readLDListArgs();
			$uoptions = $this->readLDListUpdatesArgs();
			$params['fetch_args'] = json_encode($options);
			$params['fetch_update_args'] = json_encode($uoptions);
			$this->loadParamsForListScreen($params, $dacura_server);
		}
		elseif($screen == "view"){
			$options = $this->readLDViewArgs();
			$params['fetch_args'] = json_encode($options);
			if($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$this->loadParamsForViewScreen($id, $params, $dacura_server);				
		}
		elseif($screen == "update"){
			$options = $this->readLDUpdateArgs();
			$params['fetch_args'] = json_encode($options);
			$id = "update/".implode("/", $this->args);
			$this->loadParamsForUpdateScreen($id, $params, $dacura_server);				
		}
		else {
			return $this->failure_result("Attempt to load unknown LD screen $screen", 404);
		}
		return $params;
	}

	/**
	 * Loads the parameters specific to the list screen
	 * @param array $params key value array of parameters to be substituted into the html via php variable interpolation
	 * @param LdDacuraServer $dacura_server
	 * @return unknown
	 */
	function loadParamsForListScreen(&$params, LdDacuraServer &$dacura_server){
		$u = $dacura_server->getUser();
		$args = $this->readLDListArgs();
		$params["title"] = $this->smsg("list_page_title");
		$params["subtitle"] = $this->smsg("list_page_subtitle");
		if($this->collection_id == "all"){
			$params['show_collection'] = true;
		}
		//$params['status_options'] = $this->getCreateStatusOptions();
		
		$subscreens = $this->getListSubscreens($dacura_server, $u);
		$params['subscreens'] = $subscreens;
		$this->loadSubscreenMessages($params, "list", $subscreens);
		if(in_array('ldo-list', $subscreens)){
			$this->loadParamsForObjectListTab($params, $dacura_server);			
		}
		if(in_array('update-list', $subscreens)){
			$this->loadParamsForUpdateListTab($params, $dacura_server);			
		}
		if(in_array('ldo-create', $subscreens)){
			$this->loadParamsForCreateTab($params, $dacura_server);
		}
		$ldtab = $this->getDatatableSetting("ld", false);
		$udtab = $this->getDatatableSetting("updates", false);
		if($this->cid() != "all"){
			$ldtab['aoColumns'][3] = array("bVisible" => false);
			$udtab['aoColumns'][3] = array("bVisible" => false);
		}
		if($this->name() != "ld"){
			$ldtab['aoColumns'][2] = array("bVisible" => false);
			$udtab['aoColumns'][2] = array("bVisible" => false);
		}		
		$params['ldo_datatable'] = json_encode($ldtab);
		$params['update_datatable'] = json_encode($udtab);
		return $params;
	}
	
	function loadSubscreenMessages(&$params, $screen, $subscreens){
		$mappings = array("LDO" => "Linked Data Object");
		if($screen == 'view'){
			if(in_array("ldo-frame", $subscreens)){
				$params['frame_intro_msg'] = $this->smsg('view_frame_intro', $mappings);
			}				
			if(in_array("ldo-history", $subscreens)){
				$params['history_intro_msg'] = $this->smsg('view_history_intro', $mappings);
			}	
			if(in_array("ldo-updates", $subscreens)){
				$params['updates_intro_msg'] = $this->smsg('view_updates_intro', $mappings);
			}
			if(in_array("ldo-contents", $subscreens)){
				$params['contents_intro_msg'] = $this->smsg('view_contents_intro', $mappings);
			}				
			if(in_array("ldo-meta", $subscreens)){
				$params['meta_intro_msg'] = $this->smsg('view_meta_intro', $mappings);
			}				
			if(in_array("ldo-analysis", $subscreens)){
				$params['analysis_intro_msg'] = $this->smsg('view_analysis_intro', $mappings);
			}				
			if(in_array("ldo-raw", $subscreens)){
				$params['raw_intro_msg'] = $this->smsg('raw_intro_msg', $mappings);
			}				
		}
		elseif($screen == "list"){
			if(in_array("ldo-list", $subscreens)){
				$params['objectlist_intro_msg'] = $this->smsg('list_objects_intro', $mappings);
			}
			if(in_array("update-list", $subscreens)){
				$params['updates_intro_msg'] = $this->smsg('list_updates_intro', $mappings);
			}
			if(in_array("ldo-create", $subscreens)){
				$params['create_intro_msg'] = $this->smsg('create_ldo_intro', $mappings);
			}
		}
		elseif($screen == "update"){
			if(in_array("ldo-contents", $subscreens)){
				$params['contents_intro_msg'] = $this->smsg('view_update_contents_intro', $mappings);
			}				
			if(in_array("ldo-meta", $subscreens)){
				$params['meta_intro_msg'] = $this->smsg('view_update_meta_intro', $mappings);
			}				
			if(in_array("ldo-analysis", $subscreens)){
				$params['analysis_intro_msg'] = $this->smsg('view_update_analysis_intro', $mappings);
			}				
			if(in_array("ldo-raw", $subscreens)){
				$params['raw_intro_msg'] = $this->smsg('update_raw_intro_msg', $mappings);
			}
			if(in_array("ldo-after", $subscreens)){
				$params['after_intro_msg'] = $this->smsg('view_update_after_msg', $mappings);
			}		
			if(in_array("ldo-before", $subscreens)){
				$params['before_intro_msg'] = $this->smsg('view_before_after_msg', $mappings);
			}		
		}
	}
		
	function loadParamsForObjectListTab(&$params, &$dacura_server){
		$params["ld_list_title"] = $this->smsg('ld_list_title');	
	}
	
	function loadParamsForUpdateListTab(&$params, &$dacura_server){
		$params["ld_updates_title"] = $this->smsg('ld_updates_title');
	}
	
	function loadParamsForCreateTab(&$params, &$dacura_server){
		$params["ld_create_title"] = $this->smsg('ld_create_title');
		$params['create_button_text'] = $this->smsg('create_button_text');
		$params['testcreate_button_text'] = $this->smsg('testcreate_button_text');
		$params['create_ldo_fields'] = $this->sform("create_ldo_fields");
		$params['create_ldo_fields']['ldtype']['options'] = LDO::$ldo_types;
		if(isset($params['create_ldo_fields']['image'])){
			$params['create_ldo_fields']['image']['default_value'] = $this->get_system_file_url("image", "services/".$this->name()."_icon.png");
		}
		if(isset($params['create_ldo_fields']['format'])){
			$params['create_ldo_fields']['format']['options'] = array_merge(array("" => "Auto-detect"), LDO::$valid_input_formats);
		}
		$params['direct_create_allowed'] = true;
		$params["demand_id_token"] = $this->getServiceSetting("demand_id_token", "@id");
		$params['create_options'] = $this->getServiceSetting("create_options", array());
		if($this->name() != "ld"){
			unset($params['create_ldo_fields']['ldtype']);
		}
		
	}

	function getListSubscreens($dacura_server, $u){
		return array("ldo-list", "update-list", "ldo-create");
	}
	
	function getViewSubscreens(){
		return array("ldo-meta", "ldo-history", "ldo-contents", "ldo-analysis", "ldo-updates");		
	}
	
	function getUpdateSubscreens(){
		return array("ldo-meta", "ldo-before", "ldo-contents", "ldo-analysis", "ldo-after");
	}
	
	
	function loadParamsForViewScreen($id, &$params, &$dacura_server){
		$params['ldoviewer_init'] = array();
		$params['subscreens'] = $this->getViewSubscreens();
		$this->loadSubscreenMessages($params, "view", $params['subscreens']);
		$params["id"] = $id;
		$params['history_datatable'] = $this->getDatatableSetting("history");
		$params['updates_datatable'] = $this->getDatatableSetting("ldoupdates");
		
		$params["title"] = "Linked Data Object Manager";
		$params["subtitle"] = "Object view";
		$params["description"] = "View and update your managed linked data objects";
		$params["ldo_analysis_fields"] = $this->sform("ldo_analysis_fields");
		$params['update_meta_button_text'] = $this->smsg('update_meta_button');
		$params['test_update_meta_button_text'] = $this->smsg('test_update_meta_button');
		$params['raw_ldo_fields']['format']['options'] = LDO::$valid_input_formats;
		$params['direct_create_allowed'] = true;
		$params['update_options'] = $this->getServiceSetting("update_options", array());
		$params['valid_view_formats'] = LDO::$valid_display_formats;
		$params['default_view_options'] = $this->getDefaultViewOptions();
		$params['editmode_options'] = array("replace" => "Replace Mode", "update" => "Update Mode");
		$params['update_result_options'] = array("No LDO result returned", "Updated LDO returned", "LDO Update Object Returned");
		$params['view_graph_options'] = array("ld" => "LD Object Store", "dqs" => "DQS Triplestore", "meta" => "metadata", "update" => "Update Store");
		$params['view_actions'] = array("restore" => "Restore this version", "edit" => "Edit", "import" => "Import", "export" => "Export", "accept" => "Publish", "reject" => "Reject", "pending" => "Unpublish");		
		$params['valid_input_formats'] = LDO::$valid_input_formats;
		$params['update_meta_fields'] = $this->sform("update_meta_fields");
		
		$params['dqs_schema_tests'] = json_encode(RVO::getSchemaTests());
		$params['default_dqs_tests'] = json_encode(RVO::getSchemaTests(false));
		$params['dqs_instance_tests'] = json_encode(RVO::getInstanceTests());
		$params['default_instance_dqs_tests'] = json_encode(RVO::getInstanceTests(false));
		$avs = $dacura_server->ontversions;
		//if(isset($avs[$this->id])) unset($avs, $this->id);
		$params['available_ontologies'] = json_encode($avs);
		return $params;
	}

	function loadParamsForUpdateScreen($id, &$params, &$dacura_server){
		$params['ldoviewer_init'] = array();
		$params['subscreens'] = $this->getUpdateSubscreens();
		$this->loadSubscreenMessages($params, "update", $params['subscreens']);
		$params["id"] = $id;
	
		$params["title"] = "Linked Data Object Manager";
		$params["subtitle"] = "Object view";
		$params["description"] = "View and update your managed linked data objects";
		$params["raw_ldo_fields"] = $this->sform("raw_edit_fields");
		$params['create_button_text'] = $this->smsg('raw_edit_text');
		$params['testcreate_button_text'] = $this->smsg('testraw_edit_text');
		$params['raw_ldo_fields']['format']['options'] = LDO::$valid_input_formats;
		$params['direct_create_allowed'] = true;
		$params['update_options'] = $this->getServiceSetting("update_options", array());
		//opr($params);
		return $params;
	}

}
