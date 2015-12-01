<?php

/*
 * Config Server
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

class ConfigDacuraServer extends DacuraServer {
	
	var $context_loaded = false;
	
	//to prevent failure on create...
	function loadContextConfiguration() {
		$this->context_loaded = parent::loadContextConfiguration();
		$this->errcode = false;
		return true;
	}
	
	function isValidCollectionID($id, $title){
		if(!$this->isValidDacuraID($id)){
			return $this->failure_result("fuck you $id", 405);
			//return false;
		}
		if($this->isDacuraBannedPhrase($title)){
			return $this->failure_result("$title is not permitted to be used as a collection title", 400);				
		}
		elseif($this->dbman->hasCollection($id)){
			return $this->failure_result("$id is already taken. Two dacura collections cannot share the same ID.", 400);				
		}
		return true;
	}
	
	function isValidDatasetID($id, $title){
		if(!$this->isValidDacuraID($id)){
			return false;
		}
		if($this->isDacuraBannedPhrase($title)){
			return $this->failure_result("$title is not permitted to be used as a dataset title", 400);
		}
		elseif($this->dbman->hasDataset($id)){
			return $this->failure_result("$id is already taken. Two dacura datasets cannot share the same ID.", 400);
		}
		return true;		
	}
	
	function createNewCollection($id, $title){
		if(!$this->isValidCollectionID($id, $title)){
			return false;
		}
		$obj = $this->getServiceSetting("default_collection_config", "{}");
		if($this->dbman->createNewCollection($id, $title, $obj, "pending")){
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
		if(file_exists($colbase)){
			return $this->failure_result("Collection directory $colbase for collection $id already exists", 400);				
		}
		if(!mkdir($colbase)){
			return $this->failure_result("Failed to create collection directory $colbase for collection $id", 500);
		}
		$paths_to_create = $this->getServiceSetting("collection_paths_to_create", array());
		foreach($paths_to_create as $p){
			if(!mkdir($colbase."/".$p)){
				return $this->failure_result("Failed to create collection directory $colbase/$p", 500);
			}
		}
		//finally have to create main graph
		if(!$this->dbman->createCollectionInitialEntities($id)){
			return $this->failure_result("Failed to create collection default linked data entities", 500);				
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
	
	function updateCollection($id, $obj){
		$oname = false;
		$ostat = false;
		if(isset($obj['name'])){
			$oname = $obj['name'];
			unset($obj['name']);
		}
		if(isset($obj['status'])){
			$ostat = $obj['status'];
			unset($obj['status']);
		}
		if($x = $this->dbman->updateCollection($id, $oname, $ostat, $obj)){
			return $x;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
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
	
	function flattenArray($arr){
		if(isAssoc($arr)) return false;
		foreach(array_values($arr) as $v){
			if(is_array($v)){
				return false;
			}
		}
		return "[".implode(", ", $arr)."]";
	}
	
	
	function sysconfig_to_form($arr){
		$fields = array();
		foreach($arr as $key => $v){
			if($key == 'config') continue; //skip the config of the service itself. 
			if(is_array($v)){
				if($flat = $this->flattenArray($v)){
					$fields[] = array("label" => $key, "value" => $flat, "id" => "sca-".$key);						
				}
				else {
					$section = array("label" => $key, "type" => "section", "id" => "sca-".$key);
					$section['fields'] = $this->sysconfig_to_form($v);
					$fields[] = $section;
				}
			}
			else {
				$fields[] = array("label" => $key, "value" => $v, "id" => "sca-".$key);				
			}
		}
		return $fields;
	}
	
	function getSysconfigFields(){
		$fields = $this->sysconfig_to_form($this->settings);
		return $fields;
	}
	
	function getServiceConfigFields($sname){
		$dacura_settings = $this->settings;
		include($this->settings['path_to_services'].$sname."/".$sname."_settings.php");
		$fields = $this->sysconfig_to_form($settings);
		return $fields;
	}
	
	function getServiceConfigTables(){
		$services = $this->getServiceList();
		$stables = array();
		foreach($services as $s){
			if(file_exists($this->settings['path_to_services'].$s."/".$s."_settings.php")){
				$stables[$s] = $this->getServiceConfigFields($s);
			}
		}
		return $stables;
	}
	
	function getLogsAsListingObject(){
		return $this->ucontext->logger->lastRowsAsListingObjects();
	}
	
	function saveUploadedFile($fname, $f){
		$fpath = $this->getSystemSetting('path_to_collections');
		if($this->cid() == "all"){
			$fpath .= "all/";
		}
		elseif($this->did() == "all"){
			$fpath .= $this->cid()."/";
		}
		else {
			$fpath .= $this->cid()."/datasets/".$this->did()."/";				
		}
		$fpath .= $this->getSystemSetting('files_directory')."/".$fname;
		
	}
}
