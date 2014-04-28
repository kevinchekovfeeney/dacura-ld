<?php
include_once("phplib/DacuraServer.php");

class CollectionDacuraServer extends DacuraServer {
	
	function createNewCollection($id, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);				
		if($this->sysman->createNewCollection($id, $obj)){
			$u->roles[] = new UserRole(0, $id, 0, 'admin', 99);
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
	
	
	function getCollection($id){
		
	}
	
	function updateCollection($newc, $oldc){
		
	}
	
	function deleteCollection($id){
	
	}
	
	
}

class CollectionDacuraAjaxServer extends CollectionDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}