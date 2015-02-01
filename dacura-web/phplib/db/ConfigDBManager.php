<?php
/*
 * server for config updates and viewing configuration details
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

include_once("UsersDBManager.php");

/*
 * Here goes the db access functions that are only used by this service
 */

class ConfigDBManager extends UsersDBManager {
	function deleteCollection($id) {
		if(!$this->hasCollection($id)){
			return $this->failure_result("Collection with ID $id does not exist", 404);
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET status = 'deleted' WHERE collection_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("error deleting collection $id" . $e->getMessage(), 500);
		}
	}
	
	
	function createNewCollection($id, $title, $obj){
		if($this->hasCollection($id)){
			return $this->failure_result("Collection with ID $id already exists", 400);
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO collections VALUES(?, ?, ?, 'active')");
			$conf = (is_array($obj) && count($obj) > 0) ? json_encode($obj) : "{}";
			$res = $stmt->execute(array($id, $title, $conf));
			return $obj;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving $email " . $e->getMessage(), 500);
		}
	}
	
	function deleteDataset($id) {
		if(!$this->hasDataset($id)){
			return $this->failure_result("Dataset with ID $id does not exist", 404);
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET status = 'deleted' WHERE dataset_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("error deleting dataset $id" . $e->getMessage(), 500);
		}
	}
	
	
	function createNewDataset($id, $cid, $dtitle, $obj){
		if($this->hasDataset($id)){
			return $this->failure_result("Dataset with ID $id already exists", 400);
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO datasets VALUES(?, ?, ?, ?, 'active')");
			$res = $stmt->execute(array($id, $dtitle, $cid, json_encode($obj)));
			return $obj;
		}
		catch(PDOException $e){
			return $this->failure_result("Failed to create dataset $id" . $e->getMessage(), 500);
		}
	}
	
}

