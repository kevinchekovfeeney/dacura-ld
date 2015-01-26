<?php
/*
 * Class which parses the URL input and turns it into a structured service call
 * It does not validate the call by checking the ids, just parses it
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class ServiceCall extends DacuraObject {
	var $collection_id;
	var $dataset_id;
	var $servicename;
	var $args = array();
	var $provenance;
	var $rawpath;

	//pattern of input is...
	//collection_id/dataset_id/service/screen/args...
	//          or
	//system/service/screen/args -> for services accessed via no dataset / collection id
	function parseURLInput(){
		$this->rawpath = isset($_GET['path']) ? $_GET['path'] : "";
		$path = (isset($_GET['path']) && $_GET['path']) ? explode("/", $_GET['path']) : array();
		if(count($path) > 0 && $path[count($path) -1] == "") {
			array_pop($path);
		}
		if(count($path) == 0){	//omit collection & dataset
			$this->collection_id = "all";
			$this->dataset_id = "all";
			$this->servicename = "";
			$this->args = array();
		}
		elseif($path[0] == 'system' or $path[0] == 'login'){
			if($path[0] == 'system') array_shift($path);
			$this->collection_id = "all";
			$this->dataset_id = "all";
			$this->servicename = array_shift($path);
			$this->args = $path;
		}			
		elseif(count($path) == 1){
			$this->collection_id = array_shift($path);
			$this->dataset_id = "all";
			$this->servicename = '';
			$this->args = array();
		}
		else {
			$this->collection_id = array_shift($path);
			$this->dataset_id = array_shift($path);
			$this->servicename = isset($path[0]) ? array_shift($path) : "";
			$this->args = $path;
		}
	}

	function inHomeContext(){
		return ($this->collection_id == "all" && $this->dataset_id == "all" && $this->servicename == "");
	}
	
	function name(){
		return $this->servicename;
	}

	function getArg($n=0){
		return isset($this->args[$n]) ? $this->args[$n] : false;
	}
	
	function getCollectionID(){
		return $this->collection_id;
	}

	function getDatasetID(){
		return $this->dataset_id;
	}
	
	function setProvenance($x){
		$this->provenance = $x;
	}

}