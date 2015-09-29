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
	//An array of scripts which will be interpolated in PHP
	var $interpolated_screens = array();

	function __construct($settings){
		$this->settings = $settings;
	}

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
				return $this->renderScreen("error", array("title" => "Navigation Error", "message" => "No '$screen' page found in $other_service service"), "core");
			}
		}
		else {
			$f = $this->mydir."screens/$screen.php";
			if(file_exists($f)){
				include_once($f);
			}
			else {
				return $this->renderScreen("error", array("title" => "Navigation Error", "message" => "No '$screen' page found"), "core");
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
		$service = &$this;
		echo "<script>"; 
		include_once($path);
		echo "</script>";
	}
	
	function renderToolHeader($option){
		$params = array();
		$params['title']= isset($option['title']) ? $option['title'] : "Dacura Tool";
		if(isset($option['subtitle'])){
			$params['subtitle'] = $option['subtitle'];
		}
		if(isset($option['description'])){
			$params['description'] = $option['description'];
		}
		if(isset($option['image'])){
			$params['image'] = $option['image'];
		}
		if(isset($option["css_class"]) && strlen(trim($option["css_class"])) > 1){
			$params['css_class'] = $option["css_class"];
		}
		$params['init-msg'] = isset($option['msg']) ? $option['msg'] : "";
		$params['close-msg'] = isset($option['close-tool-msg']) ? $option['close-tool-msg'] : "Close the tool and return to the main menu";
		if(isset($option['breadcrumbs'])){
			$params['breadcrumbs'] = '<div class="pcbreadcrumbs">'.$this->getBreadCrumbsHTML($option['breadcrumbs'][0], $option['breadcrumbs'][1])."</div>";			
		}
		$service = &$this;
		global $dacura_server;
		include_once("phplib/snippets/toolheader.php");
	}

	function includeSnippet($sn){
		include_once("phplib/snippets/$sn.php");
	}
	
	function renderToolFooter($option){
		include_once("phplib/snippets/toolfooter.php");
	}
	
	function renderFullPageHeader(){
		$service = &$this;
		global $dacura_server;
		foreach($this->interpolated_screens as $is){
			$this->writeIncludedInterpolatedScripts($is);
		}
		include_once("phplib/snippets/header.php");
		include_once("phplib/snippets/topbar.php");		
	}
	
	function renderFullPageFooter(){
		$service = &$this;
		global $dacura_server;
		include_once("phplib/snippets/footer.php");
	}
	
	function showLDEditor($params){
		$service = $this;
		$entity = isset($params['entity']) ? $params['entity'] : "Entity";
		include_once("phplib/snippets/LDEditor.php");		
	}
	
	function isPublicScreen(){
		if($this->screen == "") $this->screen = "home";
		return in_array($this->screen, $this->public_screens); 
	}
	
	function userCanViewScreen($user){
		if(!isset($this->protected_screens[$this->screen])){
			return $this->failure_result("Service: $this->servicename does not have an access rule for $this->screen", 401);				
		}
		$req_role = $this->protected_screens[$this->screen];
		if(!isset($req_role[1]) or $req_role[1] === false) $req_role[1] = $this->collection_id;
		if(!isset($req_role[2]) or $req_role[2] === false) $req_role[2] = $this->dataset_id;	
		if($user->hasSufficientRole($req_role[0], $req_role[1], $req_role[2])){			
			return true;
		}
		return $this->failure_result("User " . $user->getName(). " does not have role required to view $this->screen screen.", 401);
	}
	
	function renderFullPage(&$dacura_server){
		$this->renderFullPageHeader();
		$this->handlePageLoad($dacura_server);
		$this->renderFullPageFooter();	
	}
	
	function handlePageLoad($dacura_server){
		$this->renderScreen($this->screen, $this->args);
	}
	
	function getServiceContextLinks(){
		return "<a href='".$this->get_service_url()."'>".$this->getTitle()."</a>";
	}
	
	function getTitle(){
		return ucfirst($this->servicename)." Service";
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
	
	function get_service_breadcrumbs($top_level = "All Collections"){
		$url = $this->settings['install_url'];
		$path = array();
		$path[] = array("url" => $this->settings['install_url'].$this->servicename, "title" => $top_level);
		if($this->getCollectionID() && $this->getCollectionID() != "all"){
			$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/".$this->servicename, "title" => $this->getCollectionID());
			if($this->getDatasetID() && $this->getDatasetID() != "all"){
				$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/". $this->getDatasetID()."/".$this->servicename, "title" => $this->getDatasetID());
			}
		}
		return $path;
	}
	
	function getBreadCrumbsHTML($x = array(), $append = array()){
		$paths = $this->get_service_breadcrumbs();
		$html = "<ul class='service-breadcrumbs'>";
		$z = 20;
		foreach($paths as $i => $path){
			$n = $z--;
			//$n = count($path) - $i;
			if($i == 0){
				$html .= "<li class='first'><a href='".$path['url']."' style='z-index:$n;'><span></span>".$path['title']."</a></li>";
			}
			else {
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
		}
		foreach($x as $onex){
			$n = $z--;
			$html .= "<li><a href='".$onex[0]."' style='z-index:" . ($z++). ";'>" .$onex[1]."</a></li>";				
		}
		foreach($append as $app){
			$html .= "<li>$app</li>";
		}
		$html .= "</ul>";
		return $html;
	}
	
	//url associated with a file in a particular collection or dataset (http)
	function get_cds_url($fname, $col_id = false, $ds_id = false){
		$col_bit = ($col_id ? $col_id : $this->collection_id)."/";
		$ds_bit = ($ds_id ? $ds_id : $this->dataset_id)."/";
		return $this->settings['collections_url'].$col_bit.$ds_bit.$fname;
	}
	
	//url associated with a file in the local service (http)
	function get_service_file_url($fname, $servicen = false){
		$servicen = ($servicen ? $servicen : $this->servicename);
		return $this->settings['services_url'].$servicen."/files/".$fname;
	}
	
	function url($type, $name, $c = false, $d = false){
		if($type == 'service'){
			return $this->get_service_file_url($name, $c);
		}
		elseif($type == "collection"){
			return $this->get_cds_url($name, $c, $d);
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
	
	function getSystemSetting($cname, $def){
		if(isset($this->settings[$cname])){
			return $this->settings[$cname];
		}
		return $def;
	}
	
	//returns a setting for a particular service or the default if it does not exist
	function getServiceSetting($cname, $def){
		if(isset($this->settings[$this->servicename]) && isset($this->settings[$this->servicename][$cname])){
			return $this->settings[$this->servicename][$cname];
		}
		return $def;
	}
	
	
	
}
