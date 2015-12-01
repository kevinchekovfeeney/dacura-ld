<?php

/*
 * Class representing an invocation of a service
 * This is extended by actual service classes
 * The base class contains common functionality for producing service paths and urls for the current context
 *
 * Also where the access control will be inserted...
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class DacuraService extends DacuraObject {
	var $settings;  // the server wide settings that the service has been invoked in.
	var $mydir;
	var $servicename = "abstract_base_class";
	//var $servicecall;
	//Variables representing the context in which the service is invoked
	var $collection_id;
	var $dataset_id;
	var $args;	//associative array of the arguments that have been passed
	var $screen = ""; //special value passed to indicate the screen in html mode....
	var $connection_type; //html / api -> what mode has the service been invoked in..

	var $default_screen = "view";
	
	var $logger;
	//An array of screens provided by the service that are publically accesible
	var $public_screens = array();
	//An array of screens provided by the service that require specific dacura roles..
	var $protected_screens = array();
	//An array of scripts which will be included in screens
	var $included_scripts = array();
	//An array of stylesheet urls which will be included in screens
	var $included_css = array();
	//The set of forms that are included in the service's pages
	var $dacura_forms = array();
	//The set of tables that are included in the service's pages
	var $dacura_tables = array();
	//the messages for the user
	var $dacura_messages = array();
	
	function __construct($settings){
		$this->settings = $settings;
	}
	
	function init(){}//to be overwritten by derived classes

	function getCollectionID(){
		return $this->collection_id;
	}

	function getDatasetID(){
		return $this->dataset_id;
	}
	
	function name(){
		return $this->servicename;
	}
	
	function loadArgsFromBrowserURL($sections){
		if(count($sections) > 0){
			$this->screen = array_shift($sections);
			$this->args = array();
			for($i = 0; $i < count($sections); $i+=2){
				$this->args[$sections[$i]] = (isset($sections[$i + 1]) ? $sections[$i + 1] : "");
			}
			$this->args['screen'] = $this->screen;
		}
		else {
			$this->screen = $this->default_screen;
		}		
	}
	
	/*
	 * Loads the service context from the service call object passed in
	 */
	function load($sc, &$logger){
		$this->servicename = $sc->servicename;
		//$this->servicecall = $sc;
		$this->collection_id = $sc->collection_id;
		$this->dataset_id = $sc->dataset_id;
		$this->connection_type = $sc->provenance;
		if($sc->provenance == "html"){ //if it is html, all the arguments are in the URL
			$this->loadArgsFromBrowserURL($sc->args);
		}
		else {
			$this->args = $sc->args;
		}
		$this->mydir = $this->settings['path_to_services'].$this->servicename."/";
		$logger->loadFromService($this);
		$this->logger =& $logger;
		if($sc->provenance == "html"){ //if it is html, all the arguments are in the URL
			$this->logger->setEvent("read", $this->screen);
		}
		$this->init();
	}
	
	
	/*
	 * Loads the appropriate server for this service
	 */
	function loadServer(){
		$srvclass = ucfirst($this->servicename)."DacuraServer";
		try {
			$srvr = new $srvclass($this);
			if($srvr->errcode){
				return $this->failure_result($srvr->errmsg, $srvr->errcode);
			}				
			$u = $srvr->getUser(0);
			if($u){
				$this->logger->user_name = $u->getName();
			}
			return $srvr;
		}
		catch(Exception $e){
			return $this->failure_result("Failed to create $srvrclass object: ".$e->getMessage(), 500);
		}
		
	}

	function hasScreen($screen){
		return file_exists($this->mydir."screens/$screen.php");
	}

	function renderScreen($screen, $params, $other_service = false){
		$service =& $this;
		global $dacura_server;
		if($other_service){
			$f = $this->settings['path_to_services'].$other_service."/screens/$screen.php";
			if(file_exists($f)){				
				include_once($f);
			}
			else {
				return $this->renderScreen("error", array("title" => "Navigation Misstep", "message" => "No '$screen' page found in $other_service service"), "core");
			}
		}
		else {
			$f = $this->mydir."screens/$screen.php";
			if(file_exists($f)){
				include_once($f);
			}
			else {
				return $this->renderScreen("error", array("title" => "Navigation Error", "message" => "No '$screen' page found in ".$this->servicename), "core");
			}
		}
	}
	
	function renderScreenAsString($screen, $params, $other_service = false){
		ob_start();
		$this->renderScreen($screen, $params, $other_service);
		$page = ob_get_contents();
		ob_end_clean();
		return $page;
	}
	
	function writeIncludedInterpolatedScripts($path){
		$this->included_scripts[] = $path;
/*		$path = $this->mydir.$path;
		$service = &$this;
		global $dacura_server;
		echo "<script>alert('".$path."')"; 
		//include_once($path);
		echo "</script>";*/
	}
	
	function renderToolHeader($option){
		global $dacura_server;//make it available in the scope of the tool header template
		$params = array();
		$params['close-link'] = isset($option['close-link']) ? $option['close-link'] : $this->get_cds_url("", $this->collection_id, $this->dataset_id);
		$params['close-msg'] = isset($option['close-tool-msg']) ? $option['close-tool-msg'] : "Close the tool and return to the main menu";
		$params['title']= isset($option['title']) ? $option['title'] : "Dacura Tool";
		$params['subtitle'] = isset($option['subtitle']) ? $option['subtitle'] : "";
		$params['description'] = isset($option['description']) ? $option['description'] : "";
		$params['image'] = isset($option['image']) ? $option['image'] : false;
		$params['css_class'] = isset($option["css_class"]) ? $option["css_class"] : "";
		$params['tabs'] = isset($option['tabs']) ? $option['tabs'] : false;
		$params['jsoned'] = isset($option['jsoned']) ? true : false;
		$params['dt'] = isset($option['dt']) ? true : false;
		$params['init-msg'] = isset($option['msg']) ? $option['msg'] : "";
		$tl = isset($option['topbreadcrumb']) ? $option['topbreadcrumb'] : false;
		$tx = isset($option['collectionbreadcrumb']) ? $option['collectionbreadcrumb'] : false;
		$params['breadcrumbs'] = isset($option['breadcrumbs']) ? $this->getBreadCrumbsHTML($option['breadcrumbs'][0], $option['breadcrumbs'][1], $tl, $tx) : false;			
		$service = &$this;//make $service available in the scope of the tool header template
		include_once(path_to_snippet("toolheader"));
	}

	function includeSnippet($sn){
		include(path_to_snippet($sn));
	}
	
	function renderToolFooter($option){
		include_once(path_to_snippet("toolfooter"));
	}
	
	function renderHeaderSnippet(&$dacura_server){
		$service = &$this;
		$params = array();
		if($dacura_server->cid() != "all"){
			$col = $dacura_server->getCollection($dacura_server->cid());
			if($col && $col->getConfig("background")){
				$params['bgimage'] = $col->getConfig("background");
			} 
		}
		include_once(path_to_snippet("header"));
	}
	
	function getTopbarParams(&$dacura_server){
		$params = array();
		$params["context"] = $dacura_server->loadContextParams();
		$scl = $this->getServiceContextLinks();
		if(count($scl) > 0){
			$params["context"][] = $scl;
		}
		$u = $dacura_server->getUser();
		if($u){
			$params["username"] = $u->handle;
			$params["usericon"] = $this->url("image", "user_icon.png");
			if($u->rolesSpanCollections()){
				$params["profileurl"] = $this->get_service_url("users", array(), "html", "all", "all")."/profile";
			}
			else {
				$cid = $u->getRoleCollectionId();
				$params["profileurl"] = $this->get_service_url("users", array(), "html", $cid, "all")."/profile";
			}
			$params["logouturl"] = $this->getSystemSetting("install_url")."login/logout";
			if(isset($u->sesssion['syste'])){
				$sys = $u->sessions["system"];
				$params["activity"] = "logged in for ".gmdate("H:i", $sys->activeDuration());
			}
			else {
				$params["activity"] = "unknown access history";
			}
		}
		return $params;
	}
	
	function renderTopbarSnippet(&$dacura_server){
		$params = $this->getTopbarParams($dacura_server);
		$service = &$this;	
		$this->renderScreen("topbar", $params, "core");		
	}
	
	function writeIncludedScripts(&$dacura_server){
		$jsname = "dacura.".$this->servicename.".js";
		if(file_exists($this->mydir.$jsname)){
			$this->included_scripts[] = $this->url("script", $jsname);
		}
		foreach($this->included_scripts as $url){
			echo "<script src='$url'></script>";
		}		
	}

	function writeIncludedCSS(&$dacura_server){
		foreach($this->included_css as $url){
			echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$url.'">';
		}
	}
	
	function writeBodyHeader(&$dacura_server){
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent-nopad'>";		
	}
	
	function writeBodyFooter(&$dacura_server){
		echo "</div></div>";
	}
	
	function renderFullPageHeader(&$dacura_server){
		$this->renderHeaderSnippet($dacura_server);
		$this->renderTopbarSnippet($dacura_server);
		$this->writeIncludedScripts($dacura_server);
		$this->writeIncludedCSS($dacura_server);
		$this->writeBodyHeader($dacura_server);
	}
	
	function renderFullPageFooter(&$dacura_server){
		$service = &$this;
		$this->writeBodyFooter($dacura_server);
		include_once(path_to_snippet("footer"));
	}
	
	function getServiceContextLinks(){
		$cls = ($this->getCollectionID() == "all") ? "ucontext first" : "ucontext";
		$sparams = array(
			"class" => $cls,
			"url" => $this->get_service_url(),
			"icon" => $this->url("image", "buttons/".$this->servicename."_icon.png"),
			"name" => $this->getTitle()
		);
		return $sparams;
	}
	
	function getTitle(){
		if(isset($this->title) && $this->title){
			return $this->title;
		}
		return ucfirst($this->servicename)." Service";
	}
	
	function showLDEditor($params){
		$service = $this;
		$entity = isset($params['entity']) ? $params['entity'] : "Entity";
		$this->renderScreen("editor", $params, "ld");
		//include_once("phplib/snippets/LDEditor.php");		
	}
	
	function showLDResultbox($params){
		$service = $this;
		$entity = isset($params['entity']) ? $params['entity'] : "Entity";
		$this->renderScreen("resultbox", $params, "ld");
		//include_once("phplib/snippets/LDEditor.php");
	}
	
	function showDQSControls($graph, $set_tests){
		$params = array("graph" => $graph, "tests" => $set_tests);
		$this->renderScreen("dqs", $params, "ld");		
	}
	
	
	function isPublicScreen(){
		if($this->screen == "") $this->screen = "home";
		return in_array($this->screen, $this->public_screens); 
	}
	
	function userCanViewScreen($user, &$dacura_server){
		$screen = $this->getScreenForAC($dacura_server);
		if(!isset($this->protected_screens[$screen])){
			return $this->failure_result("Service: $this->servicename does not have an access rule for $screen", 401);				
		}
		$req_role = $this->protected_screens[$screen];
		if(!isset($req_role[1]) or $req_role[1] === false) $req_role[1] = $this->collection_id;
		if(!isset($req_role[2]) or $req_role[2] === false) $req_role[2] = $this->dataset_id;	
		if($user->hasSufficientRole($req_role[0], $req_role[1], $req_role[2])){			
			return true;
		}
		return $this->failure_result("User " . $user->getName(). " does not have role required to view $screen screen.", 401);
	}
	
	function renderFullPage(&$dacura_server){
		$this->loadDisplaySettings();
		$this->renderFullPageHeader($dacura_server);
		$this->handlePageLoad($dacura_server);
		$this->renderFullPageFooter($dacura_server);	
	}
	
	function getScreenForAC(&$dacura_server){
		return $this->getScreenForCall($dacura_server);
	}
	
	function getScreenForCall(&$dacura_server){
		return $this->screen;
	}
	
	function getParamsForScreen($screen, &$dacura_server){
		return $this->args;
	}
	
	function getParamsFor404(&$dacura_server){
		return array("title" => "The screen could not be found", "message" => "The service was not able to identify a screen for the call");;
	}
	
	function handlePageLoad(&$dacura_server){
		$screen = $this->getScreenForCall($dacura_server);
		if($screen){
			$params = $this->getParamsForScreen($screen, $dacura_server);
			$this->renderToolHeader($params);
			$this->renderScreen($screen, $params);
			$this->renderToolFooter($params);
		}
		else {
			$params = $this->getParamsFor404($dacura_server);
			$this->renderScreen("error", $params, "core");
		}
	}
	
	
	/*
	 * to provide url services to html files...
	 */
	
	/*
	 * Service calls include the collection/dataset id and may include parameters and may come through multiple interfaces
	 */
	function get_service_url($servicen = false, $args = array(), $interface="html", $col_id = false, $ds_id = false){
		$args_ext = (count($args) > 0) ? "/".implode("/", $args) : "";
		$servicen = ($servicen ? $servicen : $this->servicename);
		if($servicen == 'login'){
			return $this->settings['install_url']."login".$args_ext;
		}
		else {
			$api_bit = ($interface == $this->settings['apistr'] ? $this->settings['apistr']."/" : "");
			$col_id = $col_id ? $col_id : $this->collection_id;
			if($col_id == "all"){
				$col_bit = "";
			}
			else {
				$col_bit = $col_id ."/";
			}
			$ds_id = $ds_id ? $ds_id : $this->dataset_id;
			if($ds_id == "all"){
				$ds_bit = "";
			}
			else {
				$ds_bit = $ds_id."/";
			}
			return $this->settings['install_url'].$api_bit.$col_bit.$ds_bit.$servicen.$args_ext;
		}
	}
	
	function get_service_breadcrumbs($top_level = "", $collection = ""){
		$path = array();
		if($top_level){
			$url = $this->settings['install_url'];
			$path = array();
			$path[] = array("url" => $this->settings['install_url'].$this->servicename, "title" => $top_level);
			if($this->getCollectionID() && $this->getCollectionID() != "all"){
				$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/".$this->servicename, "title" => $this->getCollectionID());
				if($this->getDatasetID() && $this->getDatasetID() != "all"){
					$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/". $this->getDatasetID()."/".$this->servicename, "title" => $this->getDatasetID());
				}
			}
		}
		else {
			if($this->getCollectionID() && $this->getCollectionID() != "all"){
				if($this->getDatasetID() && $this->getDatasetID() != "all"){
					$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/".$this->servicename, "title" => $this->getCollectionID());
					$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/". $this->getDatasetID()."/".$this->servicename, "title" => $this->getDatasetID(). " " . $collection);
				}
				else {
					$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/".$this->servicename, "title" => $this->getCollectionID()." " . $collection);
				}
			}
		}
		return $path;
	}
	
	function getBreadCrumbsHTML($x = array(), $append = array(), $top_level = "", $collection = ""){
		$paths = $this->get_service_breadcrumbs($top_level, $collection);
		$html = "<ul class='service-breadcrumbs'>";
		$z = 20;
		$tot = 0;
		foreach($paths as $i => $path){
			$n = $z--;
			$tot++;
			//$n = count($path) - $i;
			if($i == 0){
				$html .= "<li class='first'><a href='".$path['url']."' style='z-index:$n;'><span></span>".$path['title']."</a></li>";
			}
			else {
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
		}
		foreach($x as $onex){
			$tot++;
			$n = $z--;
			$html .= "<li><a href='".$onex[0]."' style='z-index:" . ($z++). ";'>" .$onex[1]."</a></li>";				
		}
		foreach($append as $app){
			$tot++;
			$html .= "<li>$app</li>";
		}
		$html .= "</ul>";
		if($tot > 0){
			$html .= "<script>$('.pcbreadcrumbs').css('height', '29px')</script>";
		}
		return $html;
	}
	
	function getInputValueHTML($field_id, $field_help = "", $field_type = "input", $flen = "regular", $field_value = "", $fdisabl = false, $field_submit = ""){
		$html = "<table class='dacura-property-value-bundle'><tr><td class='dacura-property-input'>";
		if($field_type == "input"){
			$cls = 'dacura-'.$flen.'-input';
			$disabled = ($fdisabl) ? " disabled " : "";
			$html .= "<input id='$field_id' class='$cls' $disabled type='text' value='$field_value'>";				
		}
		$html .= "</td>";
		if($field_submit){
			$html .= "<td class='dacura-property-submit'>".$field_submit."</td>";				
		}
		if($field_help){
			$html .= "<td class='dacura-property-help'>".$field_help."</td>";
		}
		$html .= "</tr></table>";
		return $html;				
	}
	
	function getInputTableHTML($jdid, $type, $rows){
		$fm = new DacuraForm($type);
		if(!$fm->addElements($rows)){
			opr($fm);
			return "<div class='dacura-error'>".$fm->errmsg." ".$fm->errcode." </div>";			
		}
		return $fm->html($jdid);
	}
	
	
	//url associated with a file in a particular collection or dataset (http)
	function get_cds_url($fname, $col_id = false, $ds_id = false){
		$col_bit = ($col_id ? $col_id : $this->collection_id)."/";
		$ds_bit = ($ds_id ? $ds_id : $this->dataset_id)."/";
		return $this->settings['install_url'].$col_bit.$ds_bit.$fname;
	}
	
	//url associated with a file in the local service (http)
	function get_service_file_url($fname, $servicen = false){
		$servicen = ($servicen ? $servicen : $this->servicename);
		return $this->settings['services_url'].$servicen."/files/".$fname;
	}
	
	function get_service_script_url($fname, $servicen = false){
		$servicen = ($servicen ? $servicen : $this->servicename);
		return $this->settings['services_url'].$servicen."/".$fname;
	}
	
	function url($type, $name, $c = false, $d = false){
		if($type == 'service'){
			return $this->get_service_file_url($name, $c);
		}
		elseif($type == "collection"){
			return $this->get_cds_url($name, $c, $d);
		}
		elseif($type == "script"){
			return $this->get_service_script_url($name, $c);			
		}
		else return $this->get_system_file_url($type, $name);
	}
		
	function my_url($interface = "html"){
		$api_bit = ($interface == $this->settings['apistr'] ? $this->settings['apistr']."/" : "");
		$ext = $this->collection_id == "all" ? "" : $this->collection_id."/";
		$ext .= $this->dataset_id == "all" ? "" : $this->dataset_id."/";
		return $this->settings['install_url'].$api_bit.$ext.$this->servicename;
	}
	
	//these are all html -> all api access is via services.
	/*
	 * System calls are for files (css, img, etc)
	 */
	function get_system_file_url($type, $name){
		if($type == "js"){
			$ext_bit = $this->settings['files_url']."js/";
		}
		elseif($type == "css"){
			$ext_bit = $this->settings['files_url']."css/";
		}
		else {
			$ext_bit = $this->settings['files_url']."images/";
		}
		return $ext_bit.$name;
	}
	
	//returns setting information
	
	function getSystemSetting($cname, $def = false){
		if(isset($this->settings[$cname])){
			return $this->settings[$cname];
		}
		return $def;
	}
	
	//returns a setting for a particular service or the default if it does not exist
	function getServiceSetting($cname, $def = false){
		if(isset($this->settings[$this->servicename]) && isset($this->settings[$this->servicename][$cname])){
			return $this->settings[$this->servicename][$cname];
		}
		return $def;
	}
	
	function loadDisplaySettings(){
		$this->loadSettingForms();
		$this->loadSettingTables();
		$this->loadSettingMessages();
	}
	
	function loadSettingForms(){
		$forms = $this->getServiceSetting("forms", array());
		$fields = $this->getServiceSetting("form_fields", array());
		foreach($forms as $formid => $fieldids){
			$this->dacura_forms[$formid] = array();
			foreach($fieldids as $fieldid){
				$onef = $fields[$fieldid];
				if(!isset($onef['id'])){
					$onef['id'] = $formid."-".$fieldid;						
				}
				else {
					$onef['id'] = $formid."-".$onef['id'];
				}
				$this->dacura_forms[$formid][$fieldid] = $onef;
			}
		}
	}
	
	function sform($id){
		return isset($this->dacura_forms[$id]) ? $this->dacura_forms[$id] : array();
	}

	function smsg($id){
		return isset($this->dacura_messages[$id]) ? $this->dacura_messages[$id] : "";
	}
	
	function stab($id){
		return isset($this->dacura_tables[$id]) ? $this->dacura_tables[$id] : array();
	}
	
	
	function loadSettingMessages(){
		$this->dacura_messages = $this->getServiceSetting("messages", array());
	}
	
	function loadSettingTables(){
		$this->dacura_tables = $this->getServiceSetting("tables", array());
	}
	
	function getDatatableSetting($id){
		if(isset($this->dacura_tables[$id]['datatable_options'])){
			return json_encode($this->dacura_tables[$id]["datatable_options"]);
		}
		return false;
	}
	
	
}
