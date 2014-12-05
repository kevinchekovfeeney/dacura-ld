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

require_once("db/DBManager.php");
require_once("UserManager.php");
require_once("utilities.php");

class DacuraServer {
	var $settings;
	var $userman;	//user & session manager
	var $dbman; //storage manager
	var $ucontext; //user context
	var $dbclass = "DBManager";
	
	var $errmsg;
	var $errcode;
	
	function __construct($service){
		$this->settings = $service->settings;
		$this->ucontext = $service;
		try {
			$this->dbman = new $this->dbclass($this->settings['db_host'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_name']);
		}
		catch (PDOException $e) {
			return $this->failure_result('Connection failed: ' . $e->getMessage(), 500);
		}
		$this->userman = new UserManager($this->dbman, $service);
	}
	
	function failure_result($msg, $code = 500){
		$this->errmsg = $msg;
		$this->errcode = $code;
		return false;
	}
	
	
	/*
	 * Logging function
	 */
	function log($type, $data){
		if($type == "server" || $type == "error"){
			$fpath = $this->settings['dacura_logbase']."server.log";
			return (file_put_contents($fpath, $data, FILE_APPEND)) ? $fpath : false;
		}
		else if($type == "dump" || $type == "dumperrors"){
			$fpath = $this->settings['dacura_logbase'];
			if($this->ucontext->getCollectionID()) $fpath .= $this->ucontext->getCollectionID()."/";
			if($this->ucontext->getDatasetID()) $fpath .= $this->ucontext->getDatasetID()."/";
			$errorFile = 'logs\errors-'.date("dmY").'T'.date("His").'Z.html';
			$fpath .= ($type == "dump") ? 'polityParse-'.date("dmY").'T'.date("His").'Z.tsv' : 'errors-'.date("dmY").'T'.date("His").'Z.html';
			return (file_put_contents($fpath, $data)) ? $fpath : false;
		}
		else if($type == "service"){
			$fpath = $this->settings['dacura_logbase']."services/".$this->ucontext->servicename.".log";
			return (file_put_contents($fpath, $data, FILE_APPEND)) ? $fpath : false;
		}
		//here we have collection dependant logging
		//finally dataset dependant logging ?
	}
	
	function getURLofLogfile($fpath){
		$f_ext = substr($fpath, strlen($this->settings['dacura_logbase']));
		$url = $this->settings['log_url'].$f_ext;
		return $url; 
	}
	
	/* 
	 * Config related functions
	 */
	function getDataset($id){
		$obj = $this->dbman->getDataset($id);
		return $obj;
	}
	
	function updateDataset($id, $ctit, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->dbman->updateDataset($id, $ctit, $obj)){
			return $obj;
		}
		return false;
	}
	
	
	
	function updateCollection($id, $ctit, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->dbman->updateCollection($id, $ctit, $obj)){
			return $obj;
		}
		return false;
	}
	
	function getCollection($id){
		$obj = $this->dbman->getCollection($id);
		return $obj;
	}
	
	function getCollectionList(){
		$obj = $this->dbman->getCollectionList();
		return $obj;
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
	 * given his or her roles. 
	 */
	function getUserAvailableContexts($role=false, $authority_based = false){
		$u = $this->getUser(0);
		$cols = $this->getCollectionList();
		$choices = array();
		if($authority_based){
			if($u->isGod()){
				$choices["0"] = array("title" => "All collections", "datasets" => array("0" => "All Datasets"));
			}	
		}
		else {
			if($u->rolesSpanCollections($role)){
				$choices["0"] = array("title" => "All collections", "datasets" => array("0" => "All Datasets"));
			}
		}
		foreach($cols as $colid => $col){
			if($col->status == "deleted"){
				
			}
			elseif($u->hasCollectionRole($colid, $role)){
				$choices[$colid] = array("title" => $col->name, "datasets" => array("0" => "All Datasets"));
				foreach($col->datasets as $datid => $ds){
					$choices[$colid]["datasets"][$datid] = $ds->name;
				}
			}
			else {
				$datasets = array();
				foreach($col->datasets as $datid => $ds){
					if($ds->status != "deleted" && $u->hasDatasetRole($datid, $role)){
						$datasets[$datid] = $ds->name;
					}
				}
				if(!$authority_based && count($datasets) > 0){
					if(count($datasets) > 1) $datasets["0"] = "All datasets";
					$choices[$colid] = array("title" => $col->name, "datasets" => $datasets);
				}
			}
		}
		return $choices;
	}
	
	function getUserHomeContext($u){
		$appcontext = new ApplicationContext();
		$appcontext->setName("browse");		
		if($u->isGod() or $u->rolesSpanCollections()){
		}
		else {
			$cid = $u->getRoleCollectionId();
			if($cid){
				$appcontext->setCollection($cid);
				if($u->isCollectionAdmin($cid) or $u->rolesSpanDatasets($cid)){
					return $appcontext;
				}
				else {
					$dsid = $u->getRoleDatasetId();
					if($dsid) $appcontext->setDataset($dsid);
				}
			}
		}
		return $appcontext;
		//is the user god?  has the user roles in multiple collections? 
		// => root
		//is the user an account admin? does the user have roles in multiple datasets? 
		// => account/x
		//no => dataset...
		//return "A";
	}
	
	//$app = $ds->setUserContext($dcuser, $path);
	function setUserContext(&$dcuser, $path){
		$this->appcontext = $this->appman->getAppcontext($path);
		$this->appman->acceptUser($this->appcontext, $dcuser);
		return $this->appcontext;
	}
	
	function renderUserActions($appcontext, $u){
		if($u->isGod()){
			echo "<a href='".$this->settings['install_url']."home/create/collection'><div class='dacura-dashboard-button' id='dacura-create-collection-button'>Create Collection</div></a>";				
		}
		echo "<div class='dacura-dashboard-button' id='dacura-users-button'></div>";
		echo "<div class='dacura-dashboard-button' id='dacura-datasets-button'></div>";
		echo "<div class='dacura-dashboard-button' id='dacura-report-button'></div>";				
	}
	
	function renderUserGraph($appcontext, $u){
		echo "<hr style='clear: both'>";
	}
	
	function renderUserStats($appcontext, $u){
	}
	
	function renderUserMenu($appcontext, $u){
		echo "<UL class='dashboard-menu'><li class='dashboard-menu-selected'></li><li>Not selected</li></UL>";
	}
	
	function renderUserErrorMessage($title, $msg){
		echo "<div id='pagecontent-container'><div id='pagecontent' class='pagecontent-failure'>\n";
		echo "<h1>$title</h1><p>$msg</p></div></div>";
	}
	
	function write_error($str, $code = 400){
		http_response_code($code);
		echo $str;
		return false;
	}
	
	function start_comet_output(){
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		ob_flush();
		flush();
	}
	
	function write_comet_update($type, $ting){
		echo '{ message_type: "comet_update", status: "'.$type.'" payload: '.json_encode($ting)."}\n";
		//echo str_pad('',4096)."\n";
		ob_flush();
		flush();
	}
	
	function end_comet_output(){
		ob_end_flush();
	}
	
}
