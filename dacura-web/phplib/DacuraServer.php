<?php

/*
 * The Core Dacura Server
 * It includes functionality that is used by multiple services
 * It provides common logging functions, path and url generation, etc
 * For service specific functionality, extend this class in the service's directory
 * 
 * Created By: Chekov
 * Contributors: 
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */

require_once("DacuraObject.php");
require_once("db/UsersDBManager.php");
require_once("UserManager.php");
require_once("utilities.php");
require_once("FileManager.php");

class DacuraServer extends DacuraObject {
	var $settings;
	var $userman;	//user & session manager
	var $ucontext;
	var $config; //configuration for the context -> dataset, collection, schema, etc
	//has contents schema, 
	//var $collection; //collection object in which context the call is made
	//var $dataset; //dataset object in which the call is made
	var $dbclass = "UsersDBManager";//the class of the associated dbmanager
	var $dbman; //storage manager
	var $fileman; //log manager, responsible for logging, caching, dumping data
	
	function __construct($service){
		$this->settings = $service->settings;
		$this->ucontext = $service;
		try {
			$this->dbman =  new $this->dbclass($this->settings['db_host'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_name']);
		}
		catch (PDOException $e) {
			return $this->failure_result('Connection failed: ' . $e->getMessage(), 500);
		}
		$this->userman = new UserManager($this->dbman, $service);
		$this->fileman = new FileManager($service);
		$this->loadContextConfiguration();
	}

	/* 
	 * Config related functions
	 */
	function getDataset($id){
		$obj = $this->dbman->getDataset($id);
		if($obj){
			$obj->set_storage_base($this->getSystemSetting("path_to_collections", ""));
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $obj;
	}
	
	function getCollection($id){
		$obj = $this->dbman->getCollection($id);
		if($obj){
			return $obj;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getCollectionList(){
		$obj = $this->dbman->getCollectionList();
		if($obj){
			return $obj;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function loadContextConfiguration(){
		$this->loadServerConfiguration();
		if($this->cid() != "all"){
			$this->loadCollectionConfiguration($this->cid());
			if($this->did() != "all"){
				$this->loadDatasetConfiguration($this->did());
			}
		}
	}
	
	function loadServerConfiguration(){
		$this->config = array();		
	}
	
	function loadCollectionConfiguration($id){
		$col = $this->getCollection($id);
		if($col){
			foreach($col->config as $k => $v){
				$this->config[$k] = $v;
			}
			if($this->did() != "all"){
				return $this->loadDatasetConfiguration($this->did());
			}
			else return true;
		}
		return false;
	}
	
	function loadDatasetConfiguration($id){
		$ds = $this->getDataset($id);
		if($ds){
			foreach($ds->config as $k => $v){
				$this->config[$k] = $v;
			}
			return true;
		}
		return false;
	}
	
	/*
	 * Shorthand methods to access context details..
	 */
	
	function cid(){
		return $this->ucontext->getCollectionID();
	}

	function did(){
		return $this->ucontext->getDatasetID();
	}
	
	function sname(){
		return $this->ucontext->name();
	}
	
	function contextStr(){
		return "[".$this->cid()."|".$this->did()."]";
	}
	
	/*
	 * User related functions
	 */
	
	function isLoggedIn(){
		return $this->userman->isLoggedIn();
	}
	
	function addUser($u, $p, $n, $status){
		$u = $this->userman->adduser($u, $n, $p, $status);
		return ($u) ? $u : $this->failure_result("Failed to create user ".$this->userman->errmsg, 401);
	}
	
	function getUser($id=0){
		$u = $this->userman->getUser($id);
		return ($u) ? $u : $this->failure_result("Failed to retrieve user $id: ".$this->userman->errmsg, 404);
	}
	
	function deleteUser($id){
		if(!$id){
			return $this->failure_result("User ID not supplied, cannot delete", 400);
		}
		else {
			return ($this->userman->deleteUser($id)) ? "$id Deleted" : $this->failure_result($this->userman->errmsg, 404);
		}
	}
	
	function updateUser($u){
		return $this->userman->saveUser($u);
	}
	
	function getusers(){
		$u =  $this->userman->getUsers();
		return ($u) ? $u : $this->failure_result("Failed to retrieve user list: ".$this->userman->errmsg, 404);
	}
	
	/*
	 * Returns a data structure describing the collection / dataset context available to the user 
	 * that are at least as senior as the role argument. 
	 */
	function getUserAvailableContexts($role=false){
		$u = $this->getUser();
		$cols = $this->getCollectionList();
		$choices = array();
		if($u->isGod() or $u->hasCollectionRole("all", $role)){
			$choices["all"] = array("title" => "All collections", "datasets" => array("all" => "All Datasets"));
		}	
		foreach($cols as $colid => $col){
			if($col->status == "deleted"){
				continue;
			}
			elseif($u->hasCollectionRole($colid, $role) or $u->isGod() or $u->hasCollectionRole("all", $role)){
				$choices[$colid] = array("title" => $col->name, "datasets" => array("all" => "All Datasets"));
				foreach($col->datasets as $datid => $ds){
					$choices[$colid]["datasets"][$datid] = $ds->name;
				}
			}
			else {
				$datasets = array();
				foreach($col->datasets as $datid => $ds){
					if($ds->status != "deleted" && $u->hasDatasetRole($colid, $datid, $role)){
						$datasets[$datid] = $ds->name;
					}
				}
				$choices[$colid]['datasets'] = $datasets;
			}
		}
		return $choices;
	}
	
	/*
	 * Returns the user's home context (i.e. which collection they belong to) 
	 * all indicates that they are a dacura user and their home context is the user's home...
	 */
	function getUserHomeContext($u){
		if(!$u){
			return false;
		}
		if($u->isGod() or $u->hasCollectionRole("all")){
			return "all";
		}
		if(isset($u->roles[0])){
			return $u->roles[0]->collectionID();
		}
		return $this->failure_result("User $u->email has no roles", 403);
	}
	
	
	function userHasRole($role, $cid = false, $did = false){
		$u = $this->getUser();
		if(!$u)	return $this->failure_result("Access Denied! User is not logged in.", 401);
		if($cid === false) $cid = $this->ucontext->collection_id;
		if($did === false) $did = $this->ucontext->dataset_id;
		if($u->hasSufficientRole($role, $cid, $did)){
			return true;
		}
		return $this->failure_result("User ".$u->getName()." does not have the required role $role for $cid | $did", 401);
	}
	
	function contextIsValid(){
		if($this->cid() != "all"){
			$col = $this->getCollection($this->cid());
			if(!$col or $col->status == "deleted"){
				return false;
			}
		}
		if($this->did() != "all"){
			$ds = $this->getDataset($this->did());
			if(!$ds or $ds->status == "deleted"){
				return false;
			}
		}
		return true;
	}
	
	function userHasViewPagePermission(){
		if(!$this->contextIsValid()){
			return $this->failure_result("Invalid context ".$this->contextStr(), 404);
		}
		if($this->ucontext->isPublicScreen()){
			return true;
		}
		$u = $this->getUser(0);
		if(!$u) {
			return $this->failure_result("User must be logged in to view this page", 401);
		}
		if($this->ucontext->userCanViewScreen($u)){
			return true;
		}
		else {
			return $this->failure_result($this->ucontext->errmsg, $this->ucontext->errcode);
		}
	}
	
	/*
	 * Just shims to allow more convenient addressing of logging functions
	 */
	function logEvent($level, $code, $msg){
		$this->ucontext->logger->logEvent($level, $code, $msg);
	}
	
	function timeEvent($a, $b){
		$this->ucontext->logger->timeEvent($a, $b);
	}
	
	function failure_result($msg = false, $code = false, $loglevel = ""){
		if($msg === false && $code === false){
			$msg = $this->errmsg;
			$code = $this->errcode;
		}
		if($loglevel) {
			$this->logEvent($loglevel, $code, $msg);
		}
		return parent::failure_result($msg, $code);
	}
	
/*	function write_error($msg = "", $code = 0){
		$msg = $msg ? $msg : $this->errmsg;
		$code = $code ? $code : $this->errcode;
		$this->ucontext->logger->setResult($code, $msg);
		http_response_code($code);	
		echo $msg;
		return false;
	}
*/	
	function init($action, $object=""){
		$this->ucontext->logger->setEvent($action, $object);
		$user = $this->getUser();
		if($user) $this->ucontext->logger->user_name = $user->getName();
	}
	
	/**
	 * 
	 * @param unknown $ting : the thing to be json-ified and returned to the user
	 * @param string $note : the note to add to the request log
	 * @return boolean always true (for using as return $x->write_json_result to indicate success result)
	 */
	function write_json_result($ting, $note = "Result returned"){
		//header("Content-Type: application/json");
		echo json_encode($ting);
		$this->ucontext->logger->setResult(200, $note);
		return true;
	}


	function write_http_result($code = 0, $msg = "", $log = "debug"){
		$msg = $msg ? $msg : $this->errmsg;
		$code = $code ? $code : $this->errcode;
		$code = $code ? $code : 400;
		$this->ucontext->logger->setResult($code, $msg);
		$this->ucontext->logger->logEvent($log, $code, $msg);
		http_response_code($code);
		echo $msg;
	}
	
	function write_http_error($code = 0, $msg = ""){
		$this->write_http_result($code, $msg, "error");
	}
	
	function start_comet_output(){
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		ob_flush();
		flush();
	}
	
	function write_comet_update($type, $ting){
		$struct = array(
				"message_type" => "comet_update",
				"status" => $type,
				"payload" => $ting
		);
		echo json_encode($struct)."\n";
		ob_flush();
		flush();
	}
	
	function write_comet_error($msg = "", $code = 0){
		$msg = $msg ? $msg : $this->errmsg;
		$code = $code ? $code : $this->errcode;
		$this->ucontext->logger->setResult($code, $msg);
		$this->end_comet_output("error", "$code: $msg");
	}
	
	function end_comet_output($rtype, $result){
		$struct = array(
				"message_type" => "comet_result",
				"status" => $rtype,
				"payload" => $result
		);
		echo json_encode($struct);
		//echo '{ "message_type": "comet_result", "status": "'.$rtype.'", "payload": '.json_encode($result)."}\n";
		ob_end_flush();
	}
	
	function getSystemSetting($cname, $def){
		return $this->ucontext->getSystemSetting($cname, $def);
	}
	
	function getServiceSetting($cname, $def){
		return $this->ucontext->getServiceSetting($cname, $def);		
	}
	

	
	function isDacuraBannedWord($word){
		return strtolower($word) == "dacura";
	}
	
	function isDacuraBannedPhrase($title){
		return false;
	}
}
