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


class ServiceLoader extends DacuraObject {

	var $settings;
	
	function __construct($settings){
		$this->settings = $settings;
	}
		
	/*
	 * Creates a service object from a browser page load 
	 */
	function loadServiceFromURL(&$logger){
		$service_call = $this->parseServiceCall("html", $this->settings);		
		if($service_call){
			return $this->loadServiceFromCall($service_call, $logger);
		}
		return false;
	}
	
	/*
	 * Creates a service object from an API invocation
	 */
	function loadServiceFromAPI(&$logger){
		$service_call = $this->parseServiceCall("api", $this->settings);
		if($service_call){
			return $this->loadServiceFromCall($service_call, $logger);	
		}
		return false;
	}
	
	/*
	 * Load a service
	 * Arguments: $sc ServiceCall object
	 * $logger: the logger object tracking this request
	 * Returns: service object
	 */
	function loadServiceFromCall($sc, &$logger = null){
		global $dacura_settings;
		$type = ucfirst(strtolower($sc->servicename))."Service";
		$fname = $this->settings['path_to_services']."$sc->servicename/".ucfirst(strtolower($sc->servicename))."Service.php";
		$sname = $this->settings['path_to_services']."$sc->servicename/".strtolower($sc->servicename)."_settings.php";
		if(file_exists($fname)){
			include_once($fname);
			if(file_exists($sname)){
				include_once($sname);
				$this->settings[strtolower($sc->servicename)] = $settings; 	
			}
			$ns = new $type($this->settings);
			$ns->load($sc, $logger);
			return $ns;
		}
		else {
			return $this->failure_result("Service $sc->servicename does not exist or could not be loaded", 404);
		}
	}
	
	/*
	 * Used by index.php to draw access denied pages, etc from the core service
	 */

	function renderErrorPage($type, $title, $message){
		$screens_path = $this->settings['path_to_services']."core/screens/";
		if($type == "denied"){
			$screens_path .= "denied.php";
		}
		else {
			$screens_path .= "error.php";				
		}
		$params = array("title" => $title, "message" => $message);
		$service = new DacuraService($this->settings);
		include_once("phplib/snippets/header.php");
		include_once("phplib/snippets/topbar.php");
		include_once($screens_path);
		include_once("phplib/snippets/footer.php");
	}

	/*
	 * Creates a serviceCall object...
	 */
	function parseServiceCall($provenance, $settings){
		$sc = new ServiceCall();
		$sc->parseURLInput($settings);
		$sc->provenance = $provenance;
		if($provenance == "html" && !$sc->servicename){
			//the home page (for the user or for collection_id, $dataset_id...
			$sc->servicename = "home";
		}
		if($this->serviceExists($sc->servicename)){
			return $sc;
		}
		else {
			return $this->failure_result("You have arrived at a non-existant URL. The service $sc->servicename in URL $sc->rawpath does not exist", 404);
		}
		$sc->provenance = $provenance;
		return $sc;
	}
	
	
	
	/*
	 * Checks to ensure that there is a servicenamme included in the service call
	 */
	function serviceCallIsValid($sc){
		if(!$sc->servicename){
			return $this->failure_result("No servicename included in URL", 404);
		}
		return true;
	}
	
	function serviceExists($servicename){
		return file_exists($this->settings['path_to_services']."$servicename/".ucfirst(strtolower($servicename))."Service.php");
	}
	
}
