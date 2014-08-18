<?php

//Job is to load service descriptions  
require_once("ServiceCall.php");
require_once("DacuraService.php");
require_once("DacuraServer.php");


class ServiceManager {

	var $settings;
	var $errmsg;
	var $errcode;
	
	function __construct($settings){
		$this->settings = $settings;
	}
	
	
	
	function getScreenPath($app, $screen){
		return "phplib/services/$app/screens/$screen.php";
	}
	
	function isLoggedIn(){
		return (isset($_SESSION['dacurauser']) && $_SESSION['dacurauser']);
	}
	
	function getUser(){
		return $_SESSION['dacurauser'];
	}
	
	function serviceExists($servicename){
		return file_exists($this->settings['path_to_services']."$servicename/".ucfirst(strtolower($servicename))."Service.php");
	}
	
	function loadService($sc){
		$type = ucfirst(strtolower($sc->servicename))."Service";
		$fname = $this->settings['path_to_services']."$sc->servicename/".ucfirst(strtolower($sc->servicename))."Service.php";
		if(file_exists($fname)){
			//print_r($sc);
			include_once($fname);
			$ns = new $type($this->settings);
			$ns->load($sc);
			return $ns;
		}
		else {
			$this->errmsg = "Service $sc->servicename does not exist or could not be loaded";
			return false;
		}
	}
	
	function renderServiceScreen($servicen, $screen, $params, $scorig = false){
		$sc = new ServiceCall();
		$sc->servicename = $servicen;
		$sc->args = $params;
		if($scorig){
			$sc->collection_id = $scorig->collection_id;
			$sc->dataset_id= $scorig->dataset_id;
		}
		else {
			$sc->collection_id = "0";
			$sc->dataset_id = "0";
		}
		$service = $this->loadService($sc);
		$service->renderScreen($screen, $params);
	}
	
	function renderScreen($sc){
		if(!$this->serviceExists($sc->servicename)){
			return $sc->set_report_error("Whoops!", "You have arrived at a non-existant page. The service $sc->servicename does not exist");	
		}
		$service = $this->loadService($sc);
		$params = array();
		for($i = 0; $i <= count($sc->args); $i+=2){
			$params[$sc->args[$i]] = (isset($sc->args[$i + 1]) ? $sc->args[$i + 1] : "");
		}
		$service->renderScreen($sc->screen(), $params);
	}
	
	function parseServiceCall(){
		$sc = new ServiceCall();
		$sc->parseURLInput();
		if($sc->servicename){
			if($this->serviceExists($sc->servicename) || $sc->servicename == 'render'){
				return $sc;
			}
			else {
				$sc->set_report_error("Whoops!", "You have arrived at a non-existant page. The service in URL $sc->rawpath does not exist");	
			}
		}
		return $sc;			
	}
	
	function serviceCallIsValid($sc){
		if(!$sc->servicename){
			$this->errmsg = "No servicename included in URL";
			return false;
		}
		return true;
	}
	
	function hasPermissions($sc){
		//pull the user object out of the session....
		
		$this->errmsg = "Not today bozo....";
		return true;
	}
	
}
