<?php
/**
 * Class representing a service invocation
 * 
 * This is extended by actual service classes
 * The base class contains common functionality for producing service paths and urls for the current context
 * Creation Date: 20/11/2014
 * 
 * @author Chekov
 * @license GPL v2
 */
class DacuraService extends DacuraObject {
	/** @var array a name-value associate array of configuration settings that apply to this service invocation */
	var $settings; 
	/** @var string the path to the service's home directory (phplib/services/sname/) */
	var $mydir;
	/** @var string the service's name */
	var $servicename = "abstract_base_class";
	/** @var string the id of the collection in which the service was invoked */
	var $collection_id;
	/** @var array associative array of the arguments that were passed to the service invocation */
	var $args;	
	/** @var string either "html" or "api" to indicate that the service was invoked from either a webpage or an api call*/ 
	var $connection_type; 
	/** @var string the name of the screen requested when the service is invoked in html mode */
	var $screen = ""; 
	/** @var string the screen to be used if no screen is passed with invocation in html mode */
	var $default_screen = "view";
	/** @var RequestLog the service logger object */
	var $logger;
	/** @var string[] An array of java script urls which will be included in service screens */
	var $included_scripts = array();
	/** @var string[] An array of stylesheet urls which will be included in screens */
	var $included_css = array();

	/**
	 * 
	 * @param array $settings a name-value associative array of settings for the service invocation 
	 */
	function __construct($settings){
		$this->settings = $settings;
	}
	
	/**
	 * Called immediately after service creation.  
	 * 
	 * To be defined by derived services who have particular initiallisation requirements
	 */
	function init(){}

	/**
	 * returns the id of the collection in which the service was invokved. 
	 * 
	 * If there is no collection id (server root context) the string "all" will be returned
	 * @return string the collection id 
	 */
	function getCollectionID(){
		return $this->collection_id;
	}
	
	/**
	 * Returns the name of the service that was invoked
	 * @return string
	 */
	function name(){
		return $this->servicename;
	}
	
	/**
	 * Returns a user-readable title of the service 
	 * If no title property is set, the title is service id followed by "service"
	 * @return string the service title
	 */
	function getTitle(){
		if(isset($this->title) && $this->title){
			return $this->title;
		}
		return ucfirst($this->servicename)." Service";
	}
	
	/* Shorthand methods to access context details.*/
	
	/**
	 * shorthand for get collection id
	 * @return string collection id
	 */
	function cid(){
		return $this->getCollectionID();
	}
	
	/**
	 * Returns the base url of the dacura server
	 * @param string $ajax if true, the ajax url is returned, otherwise the html url
	 * @return string the url
	 */
	function durl($ajax = false){
		return (!$ajax) ? $this->getSystemSetting('install_url') : $this->getSystemSetting('ajaxurl');
	}
	
	/**
	 * Loads the service context from the service call object passed in
	 * @param ServiceCall $sc the object containing the parameters of the service invocation
	 * @param RequestLog $logger the object which will be used to log the results of the request
	 */
	function load(ServiceCall $sc, RequestLog &$logger){
		$this->servicename = $sc->servicename;
		$this->collection_id = $sc->collection_id;
		$this->connection_type = $sc->provenance;
		if($sc->provenance == "html"){ //if it is html, all the arguments are in the URL
			$this->loadArgsFromBrowserURL($sc->args);
		}
		else {
			$this->args = $sc->args;
		}
		$this->mydir = $this->getSystemSetting('path_to_services').$this->name()."/";
		$logger->loadFromService($this);
		$this->logger =& $logger;
		if($sc->provenance == "html"){ //if it is html, all the arguments are in the URL
			$this->logger->setEvent("read", $this->screen);
		}
		$this->init();
	}
	
	/**
	 * Loads the service as a dependant service of another service (i.e. the other service is the one being called by the user
	 * and using this service incidentally)
	 * @param string $sid the id of the service being loaded
	 * @param DacuraService $other the controlling service doing the loading
	 */
	function loadAsDependant($sid, DacuraService &$other){
		$this->servicename = $sid;
		$this->collection_id = $other->cid();
		$this->connection_type = $other->connection_type;
		$this->mydir = $this->getSystemSetting('path_to_services').$sid."/";
		$this->logger =& $other->logger;
		$this->init();
	}
	
	/**
	 * Loads the appropriate server for this service
	 * 
	 * Every Dacura Service has an associated Server which serves as an interface between the service and the rest 
	 * of the system. By convention, the server name is the service name followed by "DacuraServer" 
	 * 
	 *  At this stage we also load all of the configuration information in the system and local collections.
	 */
	function loadServer(){
		$srvclass = ucfirst($this->servicename)."DacuraServer";
		try {
			$srvr = new $srvclass($this);
			if($srvr->errcode){
				return $this->failure_result($srvr->errmsg, $srvr->errcode);
			}
			if($this->cid() != "all"){
				if(!$this->loadDefaultCollectionSettings($srvr)){
					default_settings($this->settings);//must load them explicitly as they aren't loaded yet
					return false;
				}
			}
			$this->loadContextSettings($this->settings, $srvr);
			$this->loadServiceContextSettings($this->servicename, $this->settings[$this->servicename], $srvr);
			return $srvr; 
		}
		catch(Exception $e){
			return $this->failure_result("Failed to create $srvclass object: ".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Loads the necessary settings for a collection from the default values if they have not already been set by
	 * the configuration. 
	 * @param DacuraServer $srvr
	 * @return boolean true if success
	 */
	function loadDefaultCollectionSettings(DacuraServer $srvr){
		if(!$col = $srvr->getCollection()){
			return $this->failure_result($this->cid() . " is not a valid id of a dacura collection or service", 404);
		}
		if($col->status != "accept" && !$srvr->userHasRole("admin", "all")){
			return $this->failure_result($this->cid() . " is currently in state $col->status and cannot be accessed", 401);
		}
		$defs = $col->getDefaultSettings($srvr);
		foreach($defs as $k => $v){
			if(!isset($this->settings[$k])){
				$this->settings[$k] = $defs[$k];
			}
		}
		return true;
	}
	
	/**
	 * Loads the settings for the context (collection)
	 * @param array $settings the settings array to load
	 * @param DacuraServer $srvr the active server
	 */
	function loadContextSettings(&$settings, DacuraServer $srvr){
		$sys = $srvr->getCollection("all");
		$this->applyCollectionSettings($settings, $sys);
		if($this->cid() != "all"){
			$c = $srvr->getCollection();
			$this->applyCollectionSettings($settings, $c);
		}
		$this->overwriteLocked($settings, $sys->getConfig("settings"), $sys->getConfig("meta"));
		default_settings($settings);
	}

	/**
	 * Loads the service's settings for this context (collection)
	 * @param string $sid the id of the service
	 * @param array $settings the settings array to load
	 * @param DacuraServer $srvr the active server
	 */
	function loadServiceContextSettings($sid, &$settings, DacuraServer $srvr){
		$sys = $srvr->getCollection("all");
		$this->applyServiceSettings($sid, $settings, $sys);
		if($this->cid() != "all"){
			$c = $srvr->getCollection();
			$this->applyServiceSettings($sid, $settings, $c);
		}
		$sset = $sys->getConfig("services.".$sid);
		$smeta = $sys->getConfig("servicesmeta.".$sid);
		$this->overwriteLocked($settings, $sset, $smeta);
		if(isset($sset['status']) && isset($smeta['status']) && isset($smeta['status']) && $smeta['status']['changeable'] != "changeable"){
			$settings['status_locked'] = true;	
		}
		//opr($sset);
		if(isset($sset['status']) && $sset['status'] != "enable"){
			$settings['status'] = 'disable';
		}
	}
	
	/**
	 * Overwrites any values in the settings that are locked at a system level with the corresponding system level values
	 * 
	 * Provides the basic ability to lock certain variables system wide 
	 * @param array $arr collection settings array
	 * @param array $lvals system level settings array
	 * @param array $meta an array of metadata about the lvals - only those with a meta[changeable] = changeable can vary in collections
	 */
	function overwriteLocked(&$arr, $lvals, $meta){
		if(!is_array($lvals)) return;
		foreach($lvals as $i => $v){
			if(isset($meta[$i]) && isset($meta[$i]['changeable']) && $meta[$i]['changeable'] != "changeable"){
				$arr[$i] = $v;
			}
			if(is_array($lvals[$i])){
				$this->overwriteLocked($arr[$i], $lvals[$i], $meta);
			}
		}
	}
	
	/**
	 * Applies the settings from the passed collection to a particular service's settings
	 * @param string $sid the service id
	 * @param array $ssettings the server level settings array
	 * @param Collection $c the collection object to apply
	 */
	function applyServiceSettings($sid, &$ssettings, $c){
		if($service_conf = $c->getConfig("services.".$sid)){
			foreach($service_conf as $k => $v){
				$ssettings[$k] = $v;
			}
		}
	}
	
	/**
	 * Applies the settings from the passed collection to the system settings
	 * @param array $settings the settings array to be modified
	 * @param Collection $c the collection object to apply
	 */
	function applyCollectionSettings(&$settings, $c){
		if($csets = $c->getConfig("settings")){
			foreach($csets as $k => $v){
				$settings[$k] = $v;				
			}
		}
	}
	
	/*  Service access control / facet related functions */
	
	/**
	 * Returns a list of the facets that are considered active for the current user
	 * @param DacuraUser $u (or false if not logged in)
	 * @return array<string> the list of the facet names that are active
	 */
	function getActiveFacets($u = false){
		if(!$facets = $this->getServiceSetting("facets")){
			if(isset($this->default_facets) && is_array($this->default_facets)){
				return $this->default_facets;
			}
			return array();
		}
		$allowed = array();
		foreach($facets as $f){
			if($f['role'] == "public"){
				$allowed[] = $f;
			}
			elseif($u && $u->hasSufficientRole($f['role'], $this->cid())){
				$allowed[] = $f;				
			}
		}
		return $allowed;
	}
	
	/**
	 * Compares facets according to some hierarchy - returns true if the first is >= the second
	 * @param string $a facet name
	 * @param string $b facet name
	 * @return true if $a >= $b
	 */
	function compareFacets($a, $b){
		//hierarchy... admin->manage
		//admin->inspect->view inspect->list
		if($a == $b) return true;
		if($a == "admin") return true;
		elseif($b == "admin") return false;
		if(($a == "manage" || $a == "inspect") && ($b == 'view' or $b == 'list')) return true;
		return false;
	}
	
	/**
	 * What is the minimum facet required to access the service at the given screen?
	 * @param DacuraServer $dacura_server
	 * @return string the facet name required
	 */
	function getMinimumFacetForAccess(DacuraServer &$dacura_server){
		return $this->getScreenForAC($dacura_server);
	}
		
	/**
	 * Load any URL arguments from the browser into the service context
	 * 
	 * Sets the first arg to screen
	 * Sets args to a name value associative array covering the rest of the args /a/b/c/d => [a=>b, c=>d]
	 * 
	 * @param string[] $sections the segments of the URL (divided by /) that appeared after the servicename in the URL 
	 */
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
	 * Functions for getting configuration settings
	 */

	/**
	 * Return the value of a system configuration setting
	 * @param string $cname the name of the configuration variable
	 * @param string $def the default value to give it if the variable does not exist
	 * @param array $fillings name value array of parameter subsitutions ("TITLE" => "this is the title to be subbed in")
	 * @return string the parameterised configuration variable value (or default if not set)
	 */
	function getSystemSetting($cname, $def = false, $fillings = array()){
		if(isset($this->settings[$cname])){
			$cval = $this->settings[$cname];
			return $this->subParamsIntoConfigValue($cval, $fillings);
		}
		return $def;
	}
	
	/**
	 * returns the value of a service configuration variable (or default if it does not exist)
	 * @param unknown $cname
	 * @param string $def the default value to give it if the variable does not exist
	 * @param array $fillings name value array of parameter subsitutions ("TITLE" => "this is the title to be subbed in")
	 * @return string the parameterised configuration variable value (or default if not set)
	 */
	function getServiceSetting($cname, $def = false, $fillings = array()){
		if(isset($this->settings[$this->servicename]) && isset($this->settings[$this->servicename][$cname])){
			$cval = $this->settings[$this->servicename][$cname];
			return $this->subParamsIntoConfigValue($cval, $fillings);
		}
		return $def;
	}
	
	/**
	 * Substitutes parameters (fillings) into the configuration variable value 
	 * 
	 * If the value is an array, it will apply the subtitution recursively to the array values
	 * @param mixed $val the configuration variable value
	 * @param array $fillings name value array of parameter subsitutions ("TITLE" => "this is the title to be subbed in")
	 */
	protected function subParamsIntoConfigValue(&$val, $fillings){
		if(is_array($val)){
			foreach($val as $i => $nval){
				$this->subParamsIntoConfigValue($val[$i], $fillings);
			}
		}
		else {
			foreach($fillings as $n => $v){
				if(strstr($n, $val) !== false){
					$val = str_replace($n, $v, $val);
				}		
			}
		}
		return $val;//just for convenience - the value is changed by being passed by reference
	}
	
	/**
	 * Get the necessary configuration for a particular dacura form 
	 * @param string $id the form id
	 * @param array $fillings name value array of parameter subsitutions ("TITLE" => "this is the title to be subbed in")
	 * @return array an array of form element configurations (associative arrays with id, etc. set]
	 * @see DacuraForm 
	 */
	protected function sform($id, $fillings = array()){
		$form = $this->getServiceSetting($id);
		if(!$form){
			return array();
		}
		$sform = array();
		foreach($form as $fieldid => $onef){
			if(!isset($onef['id'])){
				$onef['id'] = $fieldid;
			}
			$sform[$fieldid] = $this->subParamsIntoConfigValue($onef, $fillings);
		}
		return $sform;
	}
	
	/**
	 * Get a particular natural language message from a service's setting
	 * @param string $id the message id
	 * @param array $fillings name value array of parameter subsitutions ("TITLE" => "this is the title to be subbed in")
	 * @return string the message itself
	 */
	protected function smsg($id, $fillings = array()){
		$msgs = $this->getServiceSetting("messages", array());
		$msg = isset($msgs[$id]) ? $msgs[$id] : "";
		return $this->subParamsIntoConfigValue($msg, $fillings);
	}
	
	/**
	 * Get a particular listing table configuration from a service's settings 
	 * @param string $id the table id
	 * @param array $fillings
	 * @return mixed
	 */
	protected function stab($id, $fillings = array()){
		$tabs = $this->getServiceSetting("tables", array());
		$tab = isset($tabs[$id]) ? $tabs[$id] : array();
		return $this->subParamsIntoConfigValue($tab, $fillings);
	}
	
	/**
	 * Return the services datatable settings for a particular table
	 * @param string $id table id
	 * @param boolean [$json_encode=true] if true, the response will be json-encoded, otherwise the array will be passed
	 * @return string|boolean the json-encoded datatable setting string or false if not found
	 */
	protected function getDatatableSetting($id, $json_encode = true){
		$tab = $this->stab($id);
		if(isset($tab['datatable_options'])){
			return $json_encode ? json_encode($tab["datatable_options"]) : $tab["datatable_options"];
		}
		return false;
	}
	
	/* Functions for rendering screens. Listed in the order that they are called */
	
	/**
	 * Includes a snippet of html (from services/core/snippets) and sets the parameters in it
	 *
	 * @param string $sn snippet name
	 * @param array $params substitution name value array for snippet parameters
	 */
	public function includeSnippet($sn, $params = array()){
		$service = &$this;//make $service available in the scope of the snippet
		global $dacura_server;//make server available too!
		include(path_to_snippet($sn));
	}
	
	/**
	 * Renders a screen when viewed in full page mode
	 * @param DacuraServer $dacura_server
	 */
	public function renderFullPage(DacuraServer &$dacura_server){
		$this->renderFullPageHeader($dacura_server);
		$this->handlePageLoad($dacura_server);
		$this->renderFullPageFooter($dacura_server);
	}

	/**
	 * Renders the html header for a service when viewed in full page mode
	 * @param DacuraServer $dacura_server
	 */
	protected function renderFullPageHeader(DacuraServer &$dacura_server){
		$this->renderHeaderSnippet($dacura_server);
		$this->renderTopbarSnippet($dacura_server);
		$this->writeIncludedScripts($dacura_server);
		$this->writeIncludedCSS($dacura_server);
		$this->writeBodyHeader($dacura_server);
	}
	
	/**
	 * Render the page's HTML header
	 * @param DacuraServer $dacura_server the dacura server object that was invoked
	 */
	protected function renderHeaderSnippet(DacuraServer &$dacura_server){
		$service = &$this;
		$params = $this->settings;
		$this->includeSnippet("header", $params);
	}
	
	/**
	 * Render the bar at the top of each Dacura Page
	 * @param DacuraServer $dacura_server
	 */
	protected function renderTopbarSnippet(DacuraServer &$dacura_server){
		$params = $this->getTopbarParams($dacura_server);
		$service = &$this;
		$this->renderScreen("topbar", $params, "core");
	}
	
	/**
	 * Generates a parameter array for passing to the topbar snippet rendering
	 * @param DacuraServer $dacura_server
	 * @return array a name-value array of parameters
	 */
	protected function getTopbarParams(DacuraServer &$dacura_server){
		$params = array();
		$params["context"] = $dacura_server->loadContextParams();
		$scl = $this->getServiceContextLinks();
		if(count($scl) > 0){
			$params["context"][] = $scl;
		}
		$u = $dacura_server->getUser();
		if($u){
			$params["username"] = $u->handle;
			$params["usericon"] = $this->furl("image", "icons/user_icon.png");
			if($u->rolesSpanCollections()){
				$params["profileurl"] = $this->get_service_url("users", array(), "html", "all", "all")."/profile";
			}
			else {
				$cid = $u->getRoleCollectionId();
				$params["profileurl"] = $this->get_service_url("users", array(), "html", $cid, "all")."/profile";
			}
			$params["logouturl"] = $this->getSystemSetting("install_url")."login/logout";
			if(isset($u->sesssion['system'])){
				$sys = $u->sessions["system"];
				$params["activity"] = "logged in for ".gmdate("H:i", $sys->activeDuration());
			}
			else {
				$params["activity"] = "unknown access history";
			}
		}
		return $params;
	}
	/**
	 * Loads a parameter array for the current service
	 * @return array parameter array("class": css class, "url": service url, "icon": service icon file, "name": service name)
	 */
	protected function getServiceContextLinks(){
		$cls = ($this->getCollectionID() == "all") ? "service-context ucontext first" : "service-context ucontext";
		$sparams = array(
				"class" => $cls,
				"url" => $this->get_service_url(),
				"icon" => $this->furl("image", "services/".$this->servicename."_icon.png"),
				"name" => $this->getServiceSetting("service-title", $this->getTitle())
		);
		return $sparams;
	}
	
	/**
	 * Includes any necessary javascripts in the page
	 * 
	 * If there is a script in the service directory called dacura.[servicename].js it will be included
	 * As will any scripts that are added to $this->included_scripts
	 * @param DacuraServer $dacura_server
	 */
	protected function writeIncludedScripts(DacuraServer &$dacura_server){
		$jsname = "dacura.".$this->servicename.".js";
		if(file_exists($this->mydir.$jsname)){
			$this->included_scripts[] = $this->furl("script", $jsname);
		}
		foreach($this->included_scripts as $url){
			echo "<script src='$url'></script>";
		}
	}
	
	/**
	 * Writes the html to include necessary css files in page
	 * 
	 * Whatever is included in $this->included_css is included
	 * @param DacuraServer $dacura_server
	 */
	protected function writeIncludedCSS(DacuraServer &$dacura_server){
		foreach($this->included_css as $url){
			echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$url.'">';
		}
	}
	
	/**
	 * Writes the header of the page body
	 * @param DacuraServer $dacura_server
	 */
	protected function writeBodyHeader(DacuraServer &$dacura_server){
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent-nopad'>";
	}
	
	/**
	 * Called to deal with the actual invocation when a page is loaded after the headers have been rendered
	 * @param DacuraServer $dacura_server
	 */
	function handlePageLoad(DacuraServer &$dacura_server){
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

	/**
	 * Which screen is being accessed by the browser?
	 * @param DacuraServer $dacura_server
	 * @return string the id of the screen
	 */
	function getScreenForCall(DacuraServer &$dacura_server){
		return $this->screen;
	}
	
	/**
	 * Which screen is being accessed from an access control point of view?
	 * 
	 * This is separated out to allow services to override this to get 
	 * complex relationships between screens and access control
	 * @param DacuraServer $dacura_server
	 * @return string the id of the screen 
	 */
	function getScreenForAC(DacuraServer &$dacura_server){
		return $this->getScreenForCall($dacura_server);
	}
	
	/**
	 * Generate the name-value array of parameters to pass to a particular screen
	 * @param string $screen the id of the screen in question
	 * @param DacuraServer $dacura_server
	 * @return array parameter array 
	 */
	function getParamsForScreen($screen, DacuraServer &$dacura_server){
		return $this->args;
	}
	
	/**
	 * Gets the necessary parameter array for 404 screens (when the screen being accessed does not exist)
	 * @param DacuraServer $dacura_server
	 * @return array Parameter array(title => "", "message" => )
	 */
	function getParamsFor404(&$dacura_server){
		return array("title" => "The screen could not be found", "message" => "The service was not able to identify a screen for the call");;
	}	
	
	/**
	 * Writes the tool header html to the browser
	 * @param array $option name value array of options to pass to tool
	 */
	protected function renderToolHeader($option){
		global $dacura_server;//make it available in the scope of the tool header template
		$params = array();
		$params['close-link'] = isset($option['close-link']) ? $option['close-link'] : $this->get_service_url("browse");
		$params['close-msg'] = isset($option['close-tool-msg']) ? $option['close-tool-msg'] : "Close the tool and return to the main menu";
		$params['title']= isset($option['title']) ? $option['title'] : "Dacura Tool";
		$params['subtitle'] = isset($option['subtitle']) ? $option['subtitle'] : "";
		$params['description'] = isset($option['description']) ? $option['description'] : "";
		$params['image'] = isset($option['image']) ? $option['image'] : false;
		$params['image-link'] = $dacura_server->userHasRole("admin", "all") ? $this->durl().$this->servicename : "";
		$params['css_class'] = isset($option["css_class"]) ? $option["css_class"] : "";
		$params['tabs'] = isset($option['tabs']) ? $option['tabs'] : false;
		$params['jsoned'] = isset($option['jsoned']) ? true : false;
		$params['dt'] = isset($option['dt']) ? true : false;
		$params['init-msg'] = isset($option['msg']) ? $option['msg'] : "";
		$tl = isset($option['breadcrumb_labels']) ? $option['breadcrumb_labels'] : false;
		$tx = isset($option['breadcrumb_links']) ? $option['breadcrumb_links'] : false;
		$params['breadcrumbs'] = isset($option['breadcrumbs']) ? $this->getBreadCrumbsHTML($tl, $tx) : false;
		$this->includeSnippet("toolheader", $params);
	}

	/**
	 * Called to render a screen to the browser
	 * @param string $screen the name of the screen to render
	 * @param array $params name value associate array of substitution parameters to be passed to screen
	 * @param string $other_service if set, the screen will be taken from this service, rather than the current one which is default
	 * @return void
	 */
	public function renderScreen($screen, $params, $other_service = false){
		$service =& $this;
		global $dacura_server;
		if($other_service){
			$f = $this->getSystemSetting('path_to_services').$other_service."/screens/$screen.php";
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
	
	/**
	 * Renders a screen and returns the HTML string
	 * @param string $screen
	 * @param array $params - name:value substitution parameters for screen 
	 * @param string $other_service the name of the service which owns the screen (if omitted, the current service is assumed)
	 * @return string the html encoding of the screen
	 */
	public function renderScreenAsString($screen, $params, $other_service = false){
		ob_start();
		$this->renderScreen($screen, $params, $other_service);
		$page = ob_get_contents();
		ob_end_clean();
		return $page;
	}
	
	/**
	 * Tests whether the service has a particular screen
	 * @param string $screen the name of the screen
	 * @return boolean true if it exists
	 */
	function hasScreen($screen){
		return file_exists($this->mydir."screens/$screen.php");
	}
	
	/**
	 * Writes the tool footer html to the browser
	 * @param array $params
	 */
	protected function renderToolFooter($params){
		$this->includeSnippet("toolfooter", $params);
	}
	
	/**
	 * Render the html footer of a full page
	 * @param DacuraServer $dacura_server
	 */
	protected function renderFullPageFooter(DacuraServer &$dacura_server){
		$service = &$this;
		$this->writeBodyFooter($dacura_server);
		$this->includeSnippet("footer");
	}
	
	/**
	 * Writes the footer of the page body
	 * @param DacuraServer $dacura_server
	 */
	protected function writeBodyFooter(DacuraServer &$dacura_server){
		echo "</div></div>";
	}
	
	/**
	 * Renders the Dacura Quality Service Control screen 
	 * @param string $graph the name of the graph that it is being applied to 
	 * @param array $set_tests an array of the ids of the dqs tests that are available
	 */
	public function showDQSControls($graph, $set_tests){
		$params = array("graph" => $graph, "tests" => $set_tests);
		$this->renderScreen("dqs", $params, "ld");		
	}

	/**
	 * Generates the HTML for a service's breadcrumb tabs
	 * @param array $xcrumbs an array of extra breadcrumbs to add to the regular service ones
	 * @param string $append a string (non linked) to be added at the end of the breadcrumb list
	 * @param string $top_level the label on the top level breadcrumb (leave blank to omit top level)
	 * @param string $collection a string that is appended to the collection breadcrumb label (e.g. "users")
	 * @return string html breadcrumbs (in a html ul element)
	 */
	function getBreadCrumbsHTML($link_overrides = false){
		$paths = $this->getBreadcrumbsPaths($link_overrides);
		$html = "<ul class='service-breadcrumbs'>";
		$z = 20;
		$tot = 0;
		foreach($paths as $i => $path){
			$n = $z--;
			$tot++;
			if($i == 0){
				$html .= "<li class='first'><a href='".$path['url']."' style='z-index:$n;'><span></span>".$path['title']."</a></li>";
			}
			else {
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
		}
		$html .= "</ul>";
		if($tot > 0){
			$html .= "<script>$('.service-breadcrumbs').css('height', '29px')</script>";
		}
		return $html;
	}
	
	/**
	 * Generates the breadcrumbs that return the user to the root context from their current context
	 * @param string $top_level the label on the top level breadcrumb (leave blank to omit top level)
	 * @param string $collection a string that is appended to the collection breadcrumb label (e.g. "users")
	 * @return array an array where each entry is an associative array with 'url' & 'title' parameters
	 */
	protected function getBreadcrumbsPaths(){
		$path = array();
		$stitle = $this->getServiceSetting("service-title");
		if(!$stitle) $stitle = ucfirst($this->servicename)." service";
		if($this->cid() != "all"){
			$ctitle = $this->settings['name'];
			$path[] = array("url" => $this->durl().$this->cid(), "title" => $ctitle);
			$path[] = array("url" => $this->durl().$this->cid()."/".$this->servicename, "title" => $stitle);
		}
		else {
			$path[] = array("url" => $this->durl(), "title" => "Dacura Platform");
			$path[] = array("url" => $this->durl()."/".$this->servicename, "title" => $stitle);				
		}
		return $path;
	}

	/**
	 * Produces html for a given confirugation of table rows 
	 * @param string $tid the html id of the table
	 * @param string $ttype the type of table being shown one of DacuraForm::type
	 * @param array<rows> $fields an array of filed configuration rows (name value pairs)
 	 */
	function getInputTableHTML($tid, $fields, $settings = array()){
		$df = new DacuraForm($tid, $settings);
		if(is_array($fields) && $df->addElements($fields)){
			return $df->html($tid);				
		}
		else {
			return $this->renderScreenAsString("errormsg", array("title" => "failed to load dacura table $tid ".$df->errcode, "message" => $df->errmsg), "core");
		}
	}

/* functions to get the appropriate urls for stuff */
	
	/**
	 * Gets the url for this service 
	 * @param string $interface html | api (whether the api or webpage is desired
	 * @return string the url
	 */
	function my_url($interface = "html"){
		$api_sign = $this->getSystemSetting('apistr');
		$api_bit = ($interface ==  $api_sign ? $api_sign ."/" : "");
		$ext = $this->cid() == "all" ? "" : $this->cid()."/";
		return $this->durl().$api_bit.$ext.$this->name();
	}
	
	/**
	 * Returns the URL for accessing a service
	 * @param string $servicen the name of the service (if omitted, the current service is used)
	 * @param array $args the arguments to be passed as url parameters
	 * @param string $interface html | api
	 * @param string $col_id collection id
	 * @return string the url to the service
	 */
	function get_service_url($servicen = false, $args = array(), $interface="html", $col_id = false){
		$args_ext = (count($args) > 0) ? "/".implode("/", $args) : "";
		$servicen = ($servicen ? $servicen : $this->servicename);
		if($servicen == 'login'){
			return $this->durl()."login".$args_ext;
		}
		else {
			$api_bit = ($interface == $this->getSystemSetting('apistr') ? $this->getSystemSetting('apistr') ."/" : "");
			$col_id = $col_id ? $col_id : $this->cid();
			if($col_id == "all"){
				$col_bit = "";
			}
			else {
				$col_bit = $col_id ."/";
			}
			return $this->durl().$api_bit.$col_bit.$servicen.$args_ext;
		}
	}
	
	/**
	 * Get the URL of a particular file
	 * @param string $type [service, collection, script, image, css, js]
	 * @param string $name filename
	 * @param string $cid collection id if type = service|collection|script
	 * @return string the file url
	 */
	function furl($type, $name, $cid = false){
		if($type == 'service'){
			return $this->get_service_file_url($name, $cid);
		}
		elseif($type == "collection"){
			return $this->get_cds_url($name, $cid);
		}
		elseif($type == "script"){
			return $this->get_service_script_url($name, $cid);
		}
		else return $this->get_system_file_url($type, $name);
	}
	
	/**
	 * URL of a file that is associated with a particular collection
	 * @param string $fname filename
	 * @param string $col_id collection id
	 * @return string url
	 */
	function get_cds_url($fname, $col_id = false){
		$col_bit = ($col_id ? $col_id : $this->cid())."/";
		return $this->getSystemSetting("collections_urlbase").$col_bit.$fname;
	}
	
	/**
	 * URL of a file that is associated with a particular service
	 * @param string $fname filename
	 * @param string $servicen service name
	 * @return string url
	 */
	function get_service_file_url($fname, $servicen = false){
		$servicen = ($servicen ? $servicen : $this->name());
		$durl = $this->getSystemSetting("services_url");
		return $durl.$servicen."/files/".$fname;
	}
	
	/**
	 * Get the URL of a script associated with a particular service
	 * @param string $fname the script name
	 * @param string $servicen the service name
	 * @return string url
	 */
	function get_service_script_url($fname, $servicen = false){
		$servicen = ($servicen ? $servicen : $this->name());
		$durl = $this->getSystemSetting("services_url");
		return $durl.$servicen."/".$fname;
	}
	
	/**
	 * System files are dacura media (css, image, js, etc)
	 * @param string $type (css, image, js)
	 * @param unknown $name
	 * @return string
	 */
	function get_system_file_url($type, $name){
		$fu = $this->getSystemSetting("files_url");
		if($type == "js" || $type == "css"){
			$ext_bit = $fu.$type."/";
		}
		elseif($type == "master"){
			return $fu."master.css";
		}
		else {
			$ext_bit = $fu."images/";
		}
		return $ext_bit.$name;
	}
	
	/**
	 * Generates the url for the appropriate file browser url depending on the context
	 * @return string the filebrowser url
	 */
	function getFileBrowserURL(){
		return $this->durl().$this->getSystemSetting('filebrowser')."browse.php";
	}	
}
