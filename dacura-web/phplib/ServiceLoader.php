<?php
require_once("ServiceCall.php");
require_once("DacuraService.php");
require_once("DacuraServer.php");

/**
 * Class which loads the appropriate service object to handle the call when a new request is first received - 
 * 
 * both by api and index
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */

class ServiceLoader extends DacuraObject {
	/** @var string the path to the service directory */
	var $p2s;
	/** @var array system configuration settings array
	var $settings;
	
	/**
	 * @param array $settings system configuration settings
	 */
	function __construct($settings){
		$this->p2s = $settings['path_to_services'];
		$this->settings = $settings;
	}
	
	/**
	 * returns an array listing the ids of all the dacura services.
	 * @return string[] |boolean either the list of all the services, or false on error.
	 */
	function getServiceList(){
		$srvcs = array();
		if ($handle = opendir($this->p2s)) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != "." && $entry != "..") {
					if(is_dir($this->p2s.$entry)
							&& file_exists($this->p2s.$entry."/".ucfirst($entry)."Service.php")){
						$srvcs[] = $entry;
					}
				}
			}
			closedir($handle);
			return $srvcs;
		}
		return $this->failure_result("Failed to read services directory for service list", 500);
	}
		
	/**
	 * Creates a service object from a browser page load 
	 * @param RequestLog $logger 
	 * @return DacuraService|boolean
	 */
	function loadServiceFromURL(RequestLog &$logger){
		$service_call = $this->parseServiceCall("html");		
		if($service_call){
			return $this->loadServiceFromCall($service_call, $logger);
		}
		return false;
	}
	
	/**
	 * Creates a service object from an API invocation
	 * @param RequestLog $logger
	 * @return DacuraService|boolean
	 */
	function loadServiceFromAPI(&$logger){
		$service_call = $this->parseServiceCall("api");
		if($service_call){
			return $this->loadServiceFromCall($service_call, $logger);	
		}
		return false;
	}
	
	/**
	 * Creates a serviceCall object to parse the request
	 * @param string $provenance [html | rest]
	 * @return ServiceCall|boolean
	 */	
	 function parseServiceCall($provenance){
		$sc = new ServiceCall();
		$sc->parseURLInput($this->getServiceList());
		$sc->provenance = $provenance;
		if($provenance == "html" && !$sc->servicename){
			//the home page (for the user or for collection_id..
			$sc->servicename = "home";
		}
		if($sc->servicename == "report"){
			$sc->servicename = "candidate";
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
	

	/**
	 * Load a service object
	 * 
	 * @param ServiceCall $sc
	 * @param RequestLog $logger the logger object tracking this request
	 * @return DacuraService|boolean
	 */
	function loadServiceFromCall($sc, &$logger = null){
		global $dacura_settings;
		$type = ucfirst(strtolower($sc->servicename))."Service";
		$fname = $this->p2s ."$sc->servicename/".ucfirst(strtolower($sc->servicename))."Service.php";
		$sname = $this->p2s ."$sc->servicename/".strtolower($sc->servicename)."_settings.php";
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
	
	/**
	 * Checks to ensure that there is a servicenamme included in the service call
	 * 
	 * @param ServiceCall $sc
	 * @return boolean if the service name is set
	 */
	function serviceCallIsValid($sc){
		if(!$sc->servicename){
			return $this->failure_result("No servicename included in URL", 404);
		}
		return true;
	}
	
	/**
	 * Checks that a given service actually exists
	 * @param string $servicename
	 * @return boolean true if service exists
	 */
	function serviceExists($servicename){
		return file_exists($this->settings['path_to_services']."$servicename/".ucfirst(strtolower($servicename))."Service.php");
	}	
}
