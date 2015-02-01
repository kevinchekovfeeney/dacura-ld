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