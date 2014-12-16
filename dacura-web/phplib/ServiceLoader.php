<?php
/*
 * Class which loads the appropriate service object to handle the call when a new request is first received - both by api and index
 * 
 * 
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */

//Job is to load service descriptions  
require_once("ServiceCall.php");
require_once("DacuraService.php");
require_once("DacuraServer.php");


class ServiceLoader {

	var $settings;
	var $errmsg;
	var $errcode;
	
	function __construct($settings){
		$this->settings = $settings;
	}
	

	/*
	 * Creates a serviceCall object...
	 */
	function parseServiceCall(){
		$sc = new ServiceCall();
		$sc->parseURLInput();
		if($sc->servicename){
			if($this->serviceExists($sc->servicename)){
				return $sc;
			}
			else {
				$sc->set_report_error("Whoops!", "You have arrived at a non-existant page. The service in URL $sc->rawpath does not exist");
			}
		}
		return $sc;
	}
	
	/*
	 * Checks to ensure that there is a servicenamme included in the service call
	 */
	function serviceCallIsValid($sc){
		if(!$sc->servicename){
			$this->errmsg = "No servicename included in URL";
			return false;
		}
		return true;
	}
	
	function serviceExists($servicename){
		return file_exists($this->settings['path_to_services']."$servicename/".ucfirst(strtolower($servicename))."Service.php");
	}
	
	/*
	 * Load a service
	 * Arguments: $sc ServiceCall object
	 * Returns: service object
	 */
	function loadServiceFromCall($sc){
		$type = ucfirst(strtolower($sc->servicename))."Service";
		$fname = $this->settings['path_to_services']."$sc->servicename/".ucfirst(strtolower($sc->servicename))."Service.php";
		if(file_exists($fname)){
			//print_r($sc);
			include_once($fname);
			$ns = new $type($this->settings);
			$ns->load($sc);
			//opr($sc);
			//opr($ns);
			return $ns;
		}
		else {
			$this->errmsg = "Service $sc->servicename does not exist or could not be loaded";
			return false;
		}
	}
	
	/*
	 * Loads a service directly from a browser page load 
	 */
	function loadServiceFromURL(){
		$service_call = $this->parseServiceCall();
		$service_call->setProvenance("html");
		return $this->loadServiceFromCall($service_call);
	}
	
	/*
	 * Loads a service from an API
	 */
	function loadServiceFromAPI(){
		$service_call = $this->parseServiceCall();
		$service_call->setProvenance("api");
		return $this->loadServiceFromCall($service_call);	
	}
	
	/*
	 * Loads a service explicitly (i.e. not from the url context)
	 * Used for loading secondary services from within primary services
	 * $servicen: name of the service
	 */
	
	function loadService($servicen, $params, $scorig = false){
		$sc = new ServiceCall();
		$sc->servicename = $servicen;
		$sc->args = $params;
		$sc->provenance = "internal";
		if($scorig){
			$sc->collection_id = $scorig->collection_id;
			$sc->dataset_id= $scorig->dataset_id;
		}
		else {
			$sc->collection_id = "0";
			$sc->dataset_id = "0";
		}
		$service = $this->loadServiceFromCall($sc);
		if(isset($params['screen'])){
			$service->screen = $params['screen'];
		}
		return $service;
	}
	
	
	function renderFullServicePage($servicen, $params, $scorig = false){
		$service = $this->loadService($servicen, $params, $scorig);
		$service->renderFullPage();
	}
	
	function renderServiceScreen($servicen, $screen, $params, $scorig = false){
		$service = $this->loadExplicitService($servicen, $params, $scorig );
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
	
	/*
	 * Below here have to go...
	 */
	
	function isLoggedIn(){
		return (isset($_SESSION['dacurauser']) && $_SESSION['dacurauser']);
	}
	
	function getUser(){
		return $_SESSION['dacurauser'];
	}
	
	function hasPermissions($sc, $is_api = false){
		return true;
		//pull the user object out of the session....
		if(!$is_api && $sc->servicename == 'scraper'){
			$u = $this->getUser();
			if(!$u->hasCollectionRole("seshat", "admin")){
				$this->errmsg = "This is a restricted function. You do not have permission to access this page. Please contact the administrator to get permission to view this function";
				return false;
			}
		}
		return true;
	}
	
}
