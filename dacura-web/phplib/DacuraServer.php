<?php
require_once("DacuraController.php");
require_once("Collection.php");
require_once("DacuraUser.php");
require_once("DBManager.php");
require_once("UserManager.php");
require_once("utilities.php");
require_once("FileManager.php");

/** The Core Dacura Server Class
 * 
 * It includes functionality that is used by multiple services
 * It provides common logging functions, path and url generation, etc
 * For service specific functionality, this class is extended by services
 * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */
class DacuraServer extends DacuraController {
	/** @var @array name-value array of server settings (see settings.php) */
	var $settings;
	/** @var string the name of the Database Manager Class of this server */
	var $dbclass = "DBManager";//the php class of the associated dbmanager
	/** @var DBManager the server's database manager object */
	var $dbman; 
	/** @var FileManager log manager, responsible for logging, caching, dumping data, etc */
	var $fileman; 
	/** @var UserManager server's user & session manager */
	var $userman;	
	/** @var <string:Collection> associativee array of collection objects - just a cache to prevent reloading the same thing over and over */
	var $loaded_configs; 
	
	/**
	 * Creates the dacura server for the service invocation passed 
	 * 
	 * The server consists of several encapsulated controller / manager classes, they are all instantiated in the constructor
	 * @param DacuraService $service
	 * @return void (if the constructor fails, the new object's $errcode > 0)
	 */
	function __construct(DacuraService &$service){
		parent::__construct($service);
		try {
			$this->dbman =  new $this->dbclass($service);
			$dbc = $this->getDBConfig();
			$this->dbman->connect($dbc[0], $dbc[1], $dbc[2], $dbc[3], $dbc[4]);
		}
		catch (PDOException $e) {
			return $this->failure_result('DB Connection failed: ' . $e->getMessage(), 500);
		}
		catch (Exception $e) {
			return $this->failure_result('DB manager creation failed: ' . $e->getMessage(), 500);
		}
		
		$this->userman = new UserManager($service, $this->dbman);
		$this->fileman = new FileManager($service);
	}
	
	/**
	 * Called Immediately after server creation. 
	 * Used to initialise the server and initialise the request log for the service invocation
	 * @param string $action a string describing the action that is being invoked
	 * @param mixed $object any further parameters that should be added to the invocation request log (e.g. object of action)
	 * @return void
	 */
	function init($action, $object=""){
		$this->service->logger->setEvent($action, $object);
		$user = $this->getUser();
		$name = $user ? $user->handle : $_SERVER['REMOTE_ADDR'];
		$this->service->logger->user_name = $name;
	}
	
	/**
	 * Creates another dacura server that is dependant on this server
	 * 
	 * This is the mechanism that allows servers to overcome their isolation from one another.  
	 * When a dacura server wants to access the capabilities of another server, it calls this function
	 * and can then use the methods of that service.  The dependant server shares the same service context object, 
	 * the service and the server will be of different types.  I don't think this causes a problem anywhere, 
	 * but it might :)
	 * @param string $sname the id of the secondary server to load
	 * @return DacuraServer|boolean
	 */
	function createDependantServer($sname){
		$sclass = ucfirst($sname)."DacuraServer";
		try {
			$ds = new $sclass($this->service);
			return $ds;
		}
		catch (Exception $e){
			return $this->failure_result("Failed to create new $sname server ".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Creates another dacura service that is dependant on the current service
	 *
	 * This is the mechanism that allows servers to create service objects on top of the one that invoked them.
	 * When a dacura server wants to access the capabilities of another service, it calls this function
	 * and can then use the methods of that service.  
	 * @param string $sid the id of the service to load
	 * @return DacuraService|boolean
	 */
	function createDependantService($sid){
		$scls = ucfirst($sid)."Service";
		$sfile = $this->getSystemSetting("path_to_services")."$sid/$scls.php";
		if(!file_exists($sfile)){
			return $this->failure_result("Service file $sfile not found for service $sid", 500);
		}
		try {
			include_once($sfile);
			$settings = deepArrCopy($this->service->settings);
			$settings[$sid] = $this->getServiceConfig($sid);
			if(!$ns = new $scls($settings)){
				return $this->failure_result("Service class created $scls failed for service $sid", 500);				
			}
		}
		catch (Exception $e){
			return $this->failure_result("Failed to create new $sname service ".$e->getMessage(), 500);
		}
		$ns->loadAsDependant($sid, $this->service);
		return $ns;			
	}
	
	/**
	 *
	 * @param string $id the request id of the entity
	 * @param number $maxlen the maximum length that entity ids may be
	 * @param boolean $allow_sname true if service names are allowed as entity ids
	 * @return boolean - true if the requested id is valid
	 */
	function isValidDacuraID($id, $maxlen = 40, $allow_sname = false){
		$reserved_words = ($allow_sname) ? array() : $this->getServiceList();
		return parent::isValidDacuraID($id, $maxlen, $reserved_words);
	}
	
	/**
	 * Fetches the database configuration details in a format ready to be passed to Mysql
	 * @return array(string) an array [host, user, password, name, [options]] for accessing the db
	 */
	private function getDBConfig(){
		$dbconfig = $this->getSystemSetting("db");
		$config = array($dbconfig['host'], $dbconfig['user'], $dbconfig['pass'], $dbconfig['name']);
		if(isset($_GET['include_deleted'])){
			$config[] = array('include_deleted' => true);
		}
		else {
			$config[] = array();
		}
		return $config;
	}
	
	/**
	 * Fetches the configuration settings for a particular service by loading the service_settings file directly
	 * and then passing it to have contextual settings loaded.
	 *
	 * @param string $sname service name
	 * @return array settings array (arbitrary json structure)
	 */
	function getServiceConfig($sname){
		$dacura_settings = $this->service->settings;
		$fp = $this->getSystemSetting('path_to_services').$sname."/".$sname."_settings.php";
		if(file_exists($fp)) include($fp);
		else { $settings = array();}
		//incorporate settings from collection configurations
		$this->service->loadServiceContextSettings($sname, $settings, $this);
		return $settings;
	}	
	
	/**
	 * Fetch the collection object which contains the collection's configuration
	 * 
	 * loaded collections are saved in a loaded_configs cache array to prevent reloading multiple times
	 * @param string $id collection id
	 * @return Collection|boolean
	 */
	function getCollection($id = false){
		if($id === false) $id = $this->cid();//current collection is default
		if(isset($this->loaded_configs[$id])){
			return $this->loaded_configs[$id];
		}
		$obj = $this->dbman->getCollection($id);
		if($obj){
			$this->loaded_configs[$id] = $obj;
			return $obj;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/**
	 * Fetch the list of collections on the server
	 * @return array(array) |boolean an array of associative arrays, each containing information about a collection
	 */
	function getCollectionList(){
		$obj = $this->dbman->getCollectionList();
		if($obj){
			return $obj;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}

	/**
	 * Loads certain parameters for displaying the service depending on its context
	 * @return array an associative array with the name, icon, url and class attributes set for the current collection context
	 */
	function loadContextParams(){
		$params = array();
		if($this->cid() != "all"){
			$col = $this->getCollection();
			$icon = $this->getSystemSetting("icon", $this->service->furl("images", "system/collection_icon.png"));
			$params[] = array(
					"name" => $col->name,
					"icon" => $icon,
					"url" => $this->durl().$this->cid(),
					"class" => "ucontext first collection-context");
		}
		return $params;
	}
	
	/**
	 * returns an array listing the ids of all the dacura services. 
	 * @return string[] |boolean either the list of all the services, or false on error. 
	 */
	function getServiceList(){
		$srvcs = array();
		$sdir = $this->getSystemSetting("path_to_services");
		if ($handle = opendir($sdir)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					if(is_dir($sdir.$entry)
							&& file_exists($sdir.$entry."/".ucfirst($entry)."Service.php") && $entry != "core"){
						//only show login as a platform service
						if($this->cid() == "all" or $entry != "login"){
							$srvcs[] = $entry;
						}
					}
				}
			}
			closedir($handle);
			return $srvcs;
		}
		return $this->failure_result("Failed to read services directory for service list", 500);
	}	
	
	/**
	 * Generates the id of the associated service according to the server / service naming convention
	 * @return string id the id of the service associated with this server
	 */
	function my_service_id(){
		return strtolower(substr(get_class($this), 0, strlen(get_class($this))-strlen("DacuraServer")));
	}

	/* User related functions */
	
	/**
	 * is the user invoking the server logged in?
	 * @return boolean
	 */
	function isLoggedIn(){
		return $this->userman->isLoggedIn();
	}
	
	/**
	 * Fetch a DacuraUser object
	 * @param string $id the id of the user desired - empty string denotes current user
	 * @return DacuraUser | boolean the user object if it exists, otherwise false
	 */
	function getUser($id=""){
		$u = $this->userman->getUser($id);
		return ($u) ? $u : $this->failure_result("Failed to retrieve user $id: ".$this->userman->errmsg, $this->userman->errcode);
	}
	
	/**
	 * Update the passed user object
	 * @param DacuraUser $u the updated user object
	 * @return DacuraUser | boolean the update user object if update succeeded, false otherwise
	 */
	function updateUser(&$u, $params = array()){
		if(!$this->userman->saveUser($u)){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		if(isset($params['status']) || isset($params['email']) || isset($params['name'])){
			$u->recordAction("system", $this->cid(), "updated", true);
		}
		$params['user'] = $u->id;
		$this->recordUserAction("update user", $params);//the subject of the update
		return true;		
	}
	
	/**
	 * fetch the list of all the users in the system
	 * @return array(string => DacuraUser) | boolean an array of user objects indexed by their ids, or false on failure
	 */
	function getUsers(){
		$u =  $this->userman->getUsers();
		return ($u) ? $u : $this->failure_result("Failed to retrieve user list: ".$this->userman->errmsg, 404);
	}
	
	/**
	 * checks to see if the user invoking the server has permission to view the page specified in the service context
	 * @return boolean if true, meaning the user has permission to view the current screen
	 */
	function userHasViewPagePermission(){
		if(!$this->contextIsValid()){
			return $this->failure_result("Invalid context ".$this->contextStr(), 404);
		}
		//get service setting to make sure it is enabled...
		if($this->getServiceSetting("status") == "disable"){
			return $this->failure_result("The ". $this->sname()." service is not enabled for ".$this->cid(), 401);							
		}
		$facet = $this->service->getMinimumFacetForAccess($this);
		if($facet === true || $this->userHasFacet($facet)){
			return true;				
		}
		return $this->failure_result("User does not have permission to view this page ($facet)", 401);
	}
	
	/**
	 * Checks to see whether a user has a particular role
	 * @param string $role the name of the role
	 * @param string $cid The collection ID to check for the role (if omitted, current collection id is used)
	 * @return boolean true if the user has a role that is greater than or equal to the passed role
	 */
	function userHasRole($role, $cid = false){
		$u = $this->getUser();
		if(!$u)	return $this->failure_result("Access Denied! User is not logged in.", 401);
		if($cid === false) $cid = $this->cid();
		if($u->hasSufficientRole($role, $cid)){
			return true;
		}
		return $this->failure_result("User ".$u->getName()." does not have the required role $role for $cid", 401);
	}

	/**
	 * Checks to see whether a user is entitled to a demanded facet 
	 * @param string $f the facet name
	 * @return true if the user has >= facet to that requested
	 */
	function userHasFacet($f = false, $srvc = false){
		$srvc = $srvc ? $srvc : $this->service;
		$u = $this->getUser();
		if($u && $u->isPlatformAdmin()) return true;
		if($f){
			$facets = $srvc->getActiveFacets($u);
			foreach($facets as $onef){
				if($srvc->compareFacets($onef['facet'], $f)){
					return true;
				}
			}
			return false;
		}
		return $srvc->getActiveFacets($u);
	}
	
	/**
	 * Returns a data structure describing the collections available to the user 
	 * where the user has a role that is at least as senior as the role argument
	 * @param string $role the minimum role required 
	 * @return array indexed by collection id with a collection data structure.
	 */
	function getUserAvailableContexts($role=false){
		if(!$u = $this->getUser()){
			return $this->failure_result("User is not logged in - no roles", 400);
		}
		$cols = $this->getCollectionList();
		$choices = array();
		if($u->hasCollectionRole("all", $role)){
			$choices["all"] = array("title" => "All collections");
		}	
		foreach($cols as $colid => $col){
			if($u->hasCollectionRole($colid, $role) or $u->hasCollectionRole("all", $role)){
				$choices[$colid] = array("title" => $col->name);
			}
		}
		return $choices;
	}
	
	/**
	 * Returns the user's home context (i.e. which collection they belong to) 
	 * "all" indicates that they are a dacura system-level user and their home context is the system root
	 * @param DacuraUser $u the user in question
	 * @return boolean|string the id of the user's home collection or false if it does not exist
	 */
	function getUserHomeContext(DacuraUser $u){
		if($u->hasCollectionRole("all")){
			return "all";
		}
		if(isset($u->roles[0])){
			return $u->roles[0]->collectionID();
		}
		return $this->failure_result("User $u->email has no roles", 403);
	}
	
	/**
	 * Checks to make sure that the collection id in the service context is valid
	 * The collection id in the context must exist and not be deleted
	 * @return boolean true if the context is valid 
	 */
	function contextIsValid(){
		if($this->cid() != "all"){
			$col = $this->getCollection($this->cid());
			if(!$col or $col->is_deleted()){
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Records an action by the user to a session log
	 * 
	 * The default behaviour is to maintain one session log per service 
	 * @param DacuraUser $u the user carrying out the action 
	 * @param string $action the action itself (verb noun)
	 * @param array $params an array of parameters for the action that will be recorded
	 * @param string $sid an optional id of the session log, default is the name of the current service
	 */
	function recordUserAction($action, $params = array(), $sid = false){
		if(!($u = $this->getUser())){
			//record anonymous session...
			//$this->userman->recordAnonymousSession($sid, $this->cid(), $params);
			return $this->failure_result("Current user is not logged in", 401);
		}
		$sid = $sid ? $sid : $this->my_service_id();
		$params['action'] = $action;
		$u->registerSessionEvent($sid, $this->cid(), $params);
	}
			
	/* Output related functions - Common IO functions for all Dacura Servers*/

	/**
	 * Writes the passed result in JSON format over HTTP
	 * @param mixed $ting : the thing to be json-ified and returned to the user
	 * @param string $note : the note to add to the request log
	 * @return boolean to indicate success result
	 */
	function write_json_result($ting, $note = "Result returned"){
		//header("Content-Type: application/json");
		$json = json_encode($ting);
		if($json){
			echo $json;
			$this->logResult(200, $note);
			return true;
		}
		else {
			http_response_code(500);
			$msg = "JSON error: ".json_last_error() . " " . json_last_error_msg();
			$this->logResult(500, $note." ".$msg);
			echo $msg;
			return false;
		}
	}
	
	/**
	 * Writes an error to HTTP with a structured JSON body (for structured errors in response to updates)
	 * @param mixed $ting : the thing to be json-ified and returned to the user
	 * @param integer $code : the http error return code 
	 * @param string $note : the note to add to the request log
	 * @return void
	 */
	function write_json_error($ting, $code, $note = "JSON error returned"){
		http_response_code($code);
		$this->logResult($code, $note);
		$json = json_encode($ting);
		if($json){
			echo $json;
		}
		else {
			echo json_last_error_msg()."\n".$ting;
		}
	}
	
	/**
	 * Writes a http result - with passed message and response code, and optionally logs the result 
	 * @param number $code : http return code
	 * @param string $msg : text to be written to http response body
	 * @param string $log : log level of this result (if the current system log level is less than this it is logged
	 */
	function write_http_result($code = 0, $msg = "", $log = "debug"){
		$msg = $msg ? $msg : $this->errmsg;
		$code = $code ? $code : $this->errcode;
		$code = $code ? $code : 400;
		$this->logResult($code, $msg);
		$this->logEvent($log, $code, $msg);
		http_response_code($code);
		echo $msg;
	}
	
	/**
	 * Writes a error message (code > 400) to http 
	 * @param number $code http error return code
	 * @param string $msg message to be written to body of http response
	 */
	function write_http_error($code = 0, $msg = ""){
		$this->write_http_result($code, $msg, "error");
	}
	
	/* Comet style output functions return multiple messages over the course of a single service invocation */
	
	/**
	 * Writes headers and flushes buffers in preparation for comet output sequence of messages
	 * 
	 * Comet messsages are persistent channels to the client through which multiple atomic messages can be sent
	 * They are suitable for long-running server processes where the user should be informed of the process's status
	 */
	function start_comet_output(){
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		ob_flush();
		flush();
	}
	
	/**
	 * 
	 * @param string $status - the status of the service invocation 
	 * @param mixed $ting - the message to be sent to the client 
	 */
	function write_comet_update($status, $ting){
		$struct = array(
			"message_type" => "comet_update",
			"status" => $type,
			"payload" => $ting
		);
		echo json_encode($struct)."\n";
		ob_flush();
		flush();
	}
	
	/**
	 * Terminates a comet session with an errorcode and message
	 * @param string $msg
	 * @param number $code
	 */
	function write_comet_error($msg = "", $code = 0){
		$msg = $msg ? $msg : $this->errmsg;
		$code = $code ? $code : $this->errcode;
		$this->ucontext->logger->setResult($code, $msg);
		$this->end_comet_output("error", "$code: $msg");
	}
	
	/**
	 * Terminates a comet session with a final comet message that has a message_type = comet_result
	 * @param string $status the status code of the message
	 * @param mixed $result the final result message to terminate the comet channel 
	 */
	function end_comet_output($status, $result){
		$struct = array(
				"message_type" => "comet_result",
				"status" => $status,
				"payload" => $result
		);
		echo json_encode($struct);
		ob_end_flush();
	}	
}
