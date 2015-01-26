<?php

include_once("UsersDBManager.php");

/*
 * Here goes the db access functions that are only used by this service
 */

class ConfigDBManager extends UsersDBManager {
	function deleteCollection($id) {
		if(!$this->hasCollection($id)){
			$this->errmsg = "Collection with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET status = 'deleted' WHERE collection_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error deleting collection $id" . $e->getMessage();
			return false;
		}
	}
	
	
	function createNewCollection($id, $title, $obj){
		if($this->hasCollection($id)){
			$this->errmsg = "Collection with ID $id already exists";
			return false;
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO collections VALUES(?, ?, ?, 'active')");
			$res = $stmt->execute(array($id, $title, json_encode($obj)));
			return $obj;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	
	function deleteDataset($id) {
		if(!$this->hasDataset($id)){
			$this->errmsg = "Dataset with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET status = 'deleted' WHERE dataset_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error deleting dataset $id" . $e->getMessage();
			return false;
		}
	}
	
	
	function createNewDataset($id, $cid, $dtitle, $obj){
		if($this->hasDataset($id)){
			$this->errmsg = "Dataset with ID $id already exists";
			return false;
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO datasets VALUES(?, ?, ?, ?, 'active')");
			$res = $stmt->execute(array($id, $dtitle, $cid, json_encode($obj)));
			return $obj;
		}
		catch(PDOException $e){
			$this->errmsg = "Failed to create dataset $id" . $e->getMessage();
			return false;
		}
	}
	
}

