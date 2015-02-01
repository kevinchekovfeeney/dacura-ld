<?php

/*
 * Config Server
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

include_once("phplib/db/ConfigDBManager.php");

class ConfigDacuraServer extends DacuraServer {
	
	var $dbclass = "ConfigDBManager";
		
	function createNewCollection($id, $title){
		$obj = $this->getServiceSetting("default_collection_config", "{}");
		if($this->dbman->createNewCollection($id, $title, $obj)){
			if($this->createCollectionPaths($id)){
				return $id;
			}
			else {
				return false;				
			}
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}

	function createCollectionPaths($id){
		$colbase = $this->getSystemSetting("path_to_collections", "").$id;
		if(!mkdir($colbase)){
			return $this->failure_result("Failed to create collection directory $colbase for collection $id", 500);
		}
		$paths_to_create = $this->getServiceSetting("collection_paths_to_create", array());
		foreach($paths_to_create as $p){
			if(!mkdir($colbase."/".$p)){
				return $this->failure_result("Failed to create collection directory $colbase/$p", 500);
			}
		}
		return true;
		
	}
	
	function createNewDataset($cid, $id, $ctit){
		$obj = $this->getServiceSetting("default_dataset_config", "{}");
		if($this->dbman->createNewDataset($id, $cid, $ctit, $obj)){
			if($this->createDatasetPaths($cid, $id)){
				return $id;
			}
			else {
				return false;
			}
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
	
	function createDatasetPaths($cid, $did){
		$dsbase = $this->getSystemSetting("path_to_collections", "").$cid."/datasets/".$did;
		if(!mkdir($dsbase)){
			return $this->failure_result("Failed to create collection directory $dsbase for dataset $did", 500);
		}
		$paths_to_create = $this->getServiceSetting("dataset_paths_to_create", array());
		foreach($paths_to_create as $p){
			if(!mkdir($dsbase."/".$p)){
				return $this->failure_result("Failed to create dataset directory $dsbase/$p", 500);
			}
		}
		return true;
	}
	
	
	function updateCollection($id, $ctit, $obj){
		if($x = $this->dbman->updateCollection($id, $ctit, $obj)){
			return $x;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function updateDatasetConfig($cid, $did, $fields){
		$d_orig = $this->getDataset($did);
		$update_db = false;
		// -> multiple different types of update...
		// 1. config update...
		if(isset($fields['config'])){
			$d_orig->config = $fields['config'];
			$update_db = true;
		}		
		// 2. title update...
		if(isset($fields['title'])){
			$d_orig->name = $fields['title'];
			$update_db = true;
		}
		// 3. schema update
		if(isset($fields['schema'])){
			if($fields['schema']['version'] == "0"){//new schema
				$version = "0.1";
				if($d_orig->updateSchema($version, $fields['schema']['contents'])){
					$update_db = true;
				}
			}
			elseif($fields['schema']['version'] != $d_orig->config['schema_version']){
				return $this->failure_result("Attempt to update schema version ".$fields['schema']['version'] . " current version is $d_orig->config['schema_version']");
			}
			else {
				$version = $d_orig->config['schema_version'];
				$v_bits = explode(".", $version);
				if(isset($fields['schema']['update_type']) && $fields['schema']['update_type'] == "major"){
					$nversion = ($v_bits[0] + 1).".0";
				}
				else {
					$nversion = $v_bits[0] . ".".($v_bits[1] + 1);						
				}
				if($d_orig->updateSchema($nversion, $fields['schema']['contents'])){
					$update_db = true;
				}
			}	
		}
		// 4. json update
		if(isset($fields['json'])){
			if($fields['json']['version'] == "0"){//new schema
				$version = "0.1";
				if($d_orig->updateJSON($version, $fields['json']['contents'])){
					$update_db = true;
				}
			}
			elseif($fields['json']['version'] != $d_orig->config['json_version']){
				return $this->failure_result("Attempt to update json version ".$fields['json']['version'] . " current version is $d_orig->config['json_version']");
			}
			else {
				$version = $d_orig->config['json_version'];
				$v_bits = explode(".", $version);
				if(isset($fields['json']['update_type']) && $fields['json']['update_type'] == "major"){
					$nversion = ($v_bits[0] + 1).".0";
				}
				else {
					$nversion = $v_bits[0] . ".".($v_bits[1] + 1);
				}
				if($d_orig->updateJSON($nversion, $fields['json']['contents'])){
					$update_db = true;
				}
			}
		}
		if($update_db){
			if($this->dbman->updateDataset($d_orig->id, $d_orig->name, $d_orig->config)){
				return $this->getDatasetConfig($d_orig->collection_id, $d_orig->id);
			}
			else {
				return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
			}
		}
		return $this->failure_result("Nothing changed", 400);
	}
	
		
	function deleteCollection($id){
		if($this->dbman->deleteCollection($id, true)){
			return true;
		}
	}
	
	function deleteDataset($id){
		if($this->dbman->deleteDataset($id, true)){
			return true;
		}
	}
	
	function getDatasetConfig($cid, $did){
		$d_obj = $this->getDataset($did);
		if(isset($d_obj->config['schema_version'])){
			$d_obj->loadSchema();				
		}
		if(isset($d_obj->config['json_version'])){
			$d_obj->loadJSON();				
		}
		unset($d_obj->storage_base);
		return $d_obj;
	}
}
