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
	
	var $supported_formats = array(
			
	);
	
	function init(){
		$this->included_css[] = $this->get_service_file_url("style.css");
	}
	
	/*
	 * The next functions render snippets of html that are needed by multiple services
	 */
	
	/**
	 * Renders the LD editor screen (from the ld service)
	 * @param array $params
	 */
	
	function getLDOViewer($params){
		$ldov = new LDODisplay();
		echo $ldov->showLDOViewer($params, $this);
	}
	
	/**
	 * Renders the Linked Data Update Result box
	 * @param array $params
	 */
	public function showLDResultbox($params){
		$service = $this;
		$entity = isset($params['entity']) ? $params['entity'] : "Entity";
		$this->renderScreen("resultbox", $params, "ld");
	}	
	
	function getScreenForCall(){
		if($this->screen == "list"){
			return "list";
		}
		return "view";
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
		
	/*
	 * The various optional arguments supported by the linked data api
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
			$args['options'] = array("history" => 1, "updates" => 1, "ns" => 1);
		}
		return $args;
	}
	
	function readLDCreateArgs($is_post = false){
		if(isset($_GET['format'])){
			$args['format'] = $_GET['format'];
		}
		else {
			$args['format'] = "json";
		}
		return $args;
	}

	function readLDListArgs($is_post = false){
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
		if(isset($_GET['ldtype']) && $_GET['ldtype']){
			$args['ldtype'] = $_GET['ldtype'];
		}
		if(isset($_GET['format']) && $_GET['format']){
			$args['format'] = $_GET['format'];
		}
		else {
			$args['format'] = "json";
		}
		if(isset($_GET['display'])) {
			$args['display'] = $_GET['display'];
		}
		else {
			$args['display'] = "ns_links_typed_problems";
		}
		$args['options'] = array("history");
		return $args;
	}
	

	function getOptionalArgs(){
		return $this->readLDListArgs();
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
		$type = $_GET['type'];
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
					//write http error
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
		$dacura_server->display_content_directly($dr->result, $format, $options);
	}
	
	function showFullPage($dacura_server){
		if(isset($_GET['content_directly']) && $_GET['content_directly']){
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
		$params = array("image" => $this->furl("image", "services/ld.png"));
		$params['dt'] = true;
		$params["breadcrumbs"] = array(array(), array());
		if($screen == "list"){
			$options = $this->readLDListArgs();
			$params['fetch_args'] = json_encode($options);
			$this->loadParamsForListScreen($params, $dacura_server);
		}
		elseif($screen == "view"){
			$options = $this->readLDViewArgs();
			$params['fetch_args'] = json_encode($options);
			if($this->args && $this->screen == 'update'){
				$id = "update/".implode("/", $this->args);
			}
			elseif($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$this->loadParamsForViewScreen($id, $params, $dacura_server);				
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
		$params["title"] = "Linked Data Objects";
		$params["subtitle"] = "A list of the linked data objects managed in the system";
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
			$ldtab['aoColumns'][2] = array("bVisible" => false);
			$udtab['aoColumns'][3] = array("bVisible" => false);
		}		
		$params['ldo_datatable'] = json_encode($ldtab);
		$params['update_datatable'] = json_encode($udtab);
		return $params;
	}
	
	function loadSubscreenMessages(&$params, $screen, $subscreens){
		$mappings = array("LDO" => "Linked Data Object");
		if($screen == 'view'){
			if(in_array("ldo-history", $subscreens)){
				$params['history_intro_msg'] = $this->smsg('view_history_intro', $mappings);
			}	
			if(in_array("ldo-updates", $subscreens)){
				$params['updates_intro_msg'] = $this->smsg('view_updates_intro', $mappings);
			}
			if(in_array("ldo-details", $subscreens)){
				$params['contents_intro_msg'] = $this->smsg('view_contents_intro', $mappings);
			}				
			if(in_array("ldo-meta", $subscreens)){
				$params['meta_intro_msg'] = $this->smsg('view_meta_intro', $mappings);
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
	}
		
	function loadParamsForObjectListTab(&$params, &$dacura_server){
		
	}
	
	function loadParamsForUpdateListTab(&$params, &$dacura_server){
		
	}
	
	function loadParamsForCreateTab(&$params, &$dacura_server){
		$params['create_button_text'] = $this->smsg('create_button_text');
		$params['testcreate_button_text'] = $this->smsg('testcreate_button_text');
		$params['create_ldo_fields'] = $this->sform("create_ldo_fields");
		$params['create_ldo_fields']['ldtype']['options'] = LDO::$ldo_types;
		$params['create_ldo_fields']['ldformat']['options'] = LDO::$valid_input_formats;
		$params['direct_create_allowed'] = true;
	}

	function getListSubscreens($dacura_server, $u){
		return array("ldo-list", "update-list", "ldo-create");
	}
	
	function getViewSubscreens(){
		return array("ldo-meta", "ldo-history", "ldo-details", "ldo-updates");		
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
		return $params;
	}		
}
