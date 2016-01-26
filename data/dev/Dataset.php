Excised from Dacura Server.php


/*
	function loadDatasetConfiguration($id){
		if($id === false) $id = $this->did();//current dataset is default
		$ds = $this->getDataset($id);
		if($ds){
			foreach($ds->config as $k => $v){
				$this->config[$k] = $v;
			}
			return true;
		}
		return false;
	}
	
	function getDataset($id = false){
		if($id === false) $id = $this->did();//current dataset is default
		$obj = $this->dbman->getDataset($id);
		if($obj){
			$obj->set_storage_base($this->getSystemSetting("path_to_collections", ""));
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $obj;
	}
	
		function getDatasetList($cid = false, $full = false){
		$obj = $this->dbman->getCollectionDatasets($cid, $full);
		if($obj){
			return $obj;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
*/

Excised from DacuraService.php

	function getDatasetID(){
		return $this->dataset_id;
	}
	
Excised from Collection.php

	function setDatasets($ds){
		$this->datasets = $ds;
	}
	
	function addDataset($i, $ds){
		$this->datasets[$i] = $ds;
	}
	
	<?php

/*
 * Class representing a dataset in the Dacura System
 * Datasets are the secondary level division of dacura context: datasets belong to collections.
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class Dataset extends DacuraObject {
	var $id;
	var $name;
	var $config;
	var $collection_id;
	var $status;
	var $schema;
	var $json;
	var $storage_base = "";

	function __construct($i, $n, $c, $st, $ci){
		$this->id = $i;
		$this->name = $n;
		$this->config = $c;
		$this->status = $st;
		$this->collection_id = $ci;
	}
	
	function set_storage_base($b){
		$this->storage_base = $b.$this->storage_path();
	}
	
	function sb(){
		return $this->storage_base;
	}
	
	function schema_filename($v){
		return $this->sb()."schema/schema_$v.rdf";
	}

	function json_filename($v){
		return $this->sb()."schema/schema_$v.json";
	}
	
	function storage_path(){
		return $this->collection_id."/datasets/".$this->id."/";
	}
	
	function loadSchema($version = false){
		$version = ($version) ? $version : $this->config['schema_version'];
		$this->schema = array(
			"version" => $version, 
			"contents" => file_exists($this->schema_filename($version)) ? file_get_contents($this->schema_filename($version)) : ""
		);
	}
	
	function loadJSON($version = false){
		$version = ($version) ? $version : $this->config['json_version'];
		$this->json = array(
			"version" => $version, 
			"contents" => file_exists($this->json_filename($version)) ? file_get_contents($this->json_filename($version)) : ""
		);
	}
	
	function updateSchema($v, $c){
		$this->config['schema_version'] = $v;
		return file_put_contents($this->schema_filename($v), $c);	
	}
	
	function updateJSON($v, $c){
		$this->config['json_version'] = $v;
		return file_put_contents($this->json_filename($v), $c);
	}
	
}