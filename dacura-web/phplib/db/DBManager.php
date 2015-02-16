<?php
/*
 * Class responsible for common interactions with the Dacura SQL database. 
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


include_once('phplib/Collection.php');
include_once('phplib/Dataset.php');

class DBManager extends DacuraObject {
	
	var $link;
	
	function __construct($h, $u, $p, $n){
		$dsn = "mysql:host=$h;dbname=$n;charset=utf8";
		$this->link = new PDO($dsn, $u, $p, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
		//mysql_connect($h, $u, $p);
	}
	
	function hasLink(){
		return $this->link;
	}
	
	
	/*
	 * Collection / Dataset Config related functions
	 */
	
	function hasCollection($id){
		try {
			$stmt = $this->link->prepare("SELECT * FROM collections where collection_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $id " .$e->getMessage(), 500);
		}
	}
	
	function getCollection($id, $load_ds = true){
		try {
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections where collection_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['collection_id']){
					return $this->failure_result("Error in collection data $id ", 500);
				}
				$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents'], true), $row['status']);
				if($load_ds){
					$ds = $this->getCollectionDatasets($id);
					if($ds !== false){
						$x->setDatasets($ds);
					}
					else {
						return false;
					}
				}
				return $x;
			}
			return $this->failure_result("Collection $id does not exist", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $id ".$e->getMessage(), 500);
		}
	}
	
	function getCollectionList($load_ds = true){
		try {
			$cols = array();
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections");
			$stmt->execute(array());
			if($stmt->rowCount()) {
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						return $this->failure_result("Error in collection list", 500);
					}
					$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents']), $row['status']);
					if($load_ds){
						$ds = $this->getCollectionDatasets($row['collection_id']);
						if($ds !== false){
							$x->setDatasets($ds);
						}
						else {
							return false;
						}
					}
					$cols[$row['collection_id']] = $x;
				}
				return $cols;
			}
			return $this->failure_result("No Collections found in list", 500);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection list ".$e->getMessage(), 500);
		}
	}
	
	
	function getCollectionDatasets($cid){
		try {
			$stmt = $this->link->prepare("SELECT dataset_id, dataset_name, collection_id, status, contents FROM datasets where collection_id=?");
			$stmt->execute(array($cid));
			if($stmt->rowCount()) {
				$dss = array();
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						return $this->failure_result("Error in collection $cid dataset list", 500);
					}
					$dss[$row['dataset_id']] = new Dataset($row['dataset_id'],  $row['dataset_name'], json_decode($row['contents']), $row['status'], $row['collection_id']);
				}
				return $dss;
			}
			else return array();
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $cid dataset list ".$e->getMessage(), 500);
		}
	}
	
	function updateCollection($id, $new_title, $new_obj) {
		if(!$this->hasCollection($id)){
			return $this->failure_result("update collection - Collection with ID $id does not exist", 500);
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET collection_name = ?, contents = ? WHERE collection_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return $this->getCollection($id);
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating collection $id ".$e->getMessage(), 500);
		}
	}
	

	function hasDataset($id){
		try {
			$stmt = $this->link->prepare("SELECT * FROM datasets where dataset_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("Error checking for dataset $id ".$e->getMessage(), 500);
		}
	}	

	function getDataset($id){
		try {
			$stmt = $this->link->prepare("SELECT dataset_id, dataset_name, collection_id, status, contents FROM datasets where dataset_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['dataset_id']){
					return $this->failure_result("Error retrieving dataset $id - data error", 500);
				}
				return new Dataset($row['dataset_id'], $row['dataset_name'], json_decode($row['contents'], true), $row['status'], $row['collection_id']);
			}
			return $this->failure_result("Error retrieving dataset $id - no such dataset", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving dataset $id ".$e->getMessage(), 500);
		}
	}
	
	function updateDataset($id, $new_title, $new_obj) {
		if(!$this->hasDataset($id)){
			return $this->failure_result("update dataset: $id does not exist", 404);
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET dataset_name = ?, contents = ? WHERE dataset_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating dataset $id ".$e->getMessage(), 500);
		}
	}
	
	
	
	/**
	 * generic call.  
	 */
	function doSelect($dummy, $vars){
		try {
			$stmt = $this->link->prepare($dummy);
			$stmt->execute($vars);
			$results = array();
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$results[] = $row;
			}
			return $results;
		
		}
		catch(PDOException $e){
			return $this->failure_result("Error selecting $dummy ".$e->getMessage(), 500);
		}
	}
	
	
	
}