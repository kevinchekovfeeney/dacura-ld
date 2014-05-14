<?php
include_once("ConfigSystemManager.php");

class ConfigDacuraServer extends DacuraServer {
	
	function __construct($dacura_settings){
		$this->settings = $dacura_settings;
		try {
			$this->sysman = new ConfigSystemManager($this->settings['db_host'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_name']);
		}
		catch (PDOException $e) {
			return $this->failure_result('Connection failed: ' . $e->getMessage(), 500);
		}
		$this->sm = new UserManager($this->sysman, $this->settings);
	}
	
	function createNewCollection($id, $title, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);				
		if($this->sysman->createNewCollection($id, $title, $obj)){
			$u->addRole(new UserRole(0, $id, 0, 'admin', 99));
			//$u->roles[] = new UserRole(0, $id, 0, 'admin', 99);
			if(!$this->sysman->updateUserRoles($u)){
				return $this->failure_result("Failed to create new roles for $id collection", 500);
			}
			if(mkdir($this->settings['path_to_collections'].$id)){
				$u->setSessionEvent("system", array("action" => "create_collection", "service" => "collection", "action" => "create", "id" => $id));
			}
			else {
				return $this->failure_result("Failed to create directory for $id collection", 500);				
			}
		}
		else {
			return $this->failure_result($this->sysman->errmsg, 400);
		}
	}
		
	function deleteCollection($id){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->sysman->deleteCollection($id, true)){
			return true;
		}
	}
	
	function deleteDataset($id){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->sysman->deleteDataset($id, true)){
			return true;
		}
	}
	
	function createNewDataset($id, $cid, $ctit, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->sysman->createNewDataset($id, $cid, $ctit, $obj)){
			$u->roles[] = new UserRole(0, $cid, $id, 'admin', 99);
			if(!$this->sysman->updateUserRoles($u)){
				return $this->failure_result("Failed to create new roles for $id dataset", 500);
			}
			if(mkdir($this->settings['path_to_collections'].$cid."/".$id)){
				$u->setSessionEvent("system", array("action" => "create_dataset", "service" => "dataset", "action" => "create", "id" => $id));
			}
			else {
				return $this->failure_result("Failed to create directory for $id dataset", 500);
			}
		}
		else {
			return $this->failure_result($this->sysman->errmsg, 400);
		}
	}

}

class ConfigDacuraAjaxServer extends ConfigDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}