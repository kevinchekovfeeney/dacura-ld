<?php

class Collection {
	var $id;
	var $name;
	var $config;
	var $status;
	var $datasets = array();
	
	function __construct($i, $n, $c, $s='active'){
		$this->id = $i;
		$this->name = $n;
		$this->config = $c;
		$this->status = $s;
	}
	
	function setDatasets($ds){
		$this->datasets = $ds;
	}
	
	function addDataset($i, $ds){
		$this->datasets[$i] = $ds;
	}
}

class Dataset {
	var $id;
	var $name;
	var $config;
	var $collection_id;
	var $status;
	
	function __construct($i, $n, $c, $st, $ci){
		$this->id = $i;
		$this->name = $n;
		$this->config = $c;
		$this->status = $st;
		$this->collection_id = $ci;
	}
	
}