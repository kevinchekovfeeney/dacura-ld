<?php
include_once("phplib/DacuraServer.php");

class DatasetDacuraServer extends DacuraServer {
	
	function createNewDataset($id, $cid, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);				
		if($this->sysman->createNewDataset($id, $cid, $obj)){
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
	
	
	function getDataset($id){
		
	}
	
	function updateDataset($newc, $oldc){
		
	}
	
	function deleteDataset($id){
	
	}
	
	
}

class DatasetDacuraAjaxServer extends DatasetDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}