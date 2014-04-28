<?php

class ServiceCall{
	var $collection_id;
	var $dataset_id;
	var $servicename;
	var $args;
	var $provenance;
	var $rawpath;

	var $errcode = 0;
	var $errmsg = "";

	function set_report_error($title, $message){
		$this->args = array("error", "title", $title, "message", $message);
		//$this->args["title"] = $title;
		//$this->args["message"] = $message;
		$this->servicename = "core";
		
	}

	//pattern of input is...
	//collection_id//dataset_id//service//screen//args...
	function parseURLInput(){
		$this->rawpath = isset($_GET['path']) ? $_GET['path'] : "";
		$path = (isset($_GET['path']) && $_GET['path']) ? explode("/", $_GET['path']) : array();
		if(count($path) == 0){	//omit collection & dataset
			$this->collection_id = "0";
			$this->dataset_id = "0";
			$this->servicename = "";
			$this->args = array();
		}
		elseif($path[0] == 'login'){
			$this->collection_id = "0";
			$this->dataset_id = "0";
			$this->servicename = array_shift($path);
			$this->args = $path;
		}			
		elseif(count($path) == 1){
			$this->collection_id = array_shift($path);
			$this->dataset_id = "0";
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
		return ($this->collection_id == "0" && $this->dataset_id == "0" && $this->servicename == "");
	}
	
	function name(){
		return $this->servicename;
	}

	
	function setProvenance($x){
		$this->provenance = $x;
	}

	function report_error($msg, $code){
		$this->errcode = $code;
		$this->errmsg = $msg;
		return false;
	}

}