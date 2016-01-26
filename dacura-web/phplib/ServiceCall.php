<?php
/**
 * Class which parses the URL input and turns it into a structured service call
 * 
 * It does not validate the call by checking the ids, just parses it
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */
class ServiceCall extends DacuraObject {
	/** @var string the collection id in the call */
	var $collection_id;
	/** @var string the service named in the call */
	var $servicename;
	/** @var array the arguments passed in the call */
	var $args = array();
	/** @var string a code indicating where the call came from (html | api) */
	var $provenance;
	/** @var string the raw URL path that was used to invoke the service */
	var $rawpath;

	/**
	 * Parses the input url into its components
	 * 
	 * pattern of input urls is /[collection_id]/service_id/screen/args...
	 * @param string[] a list of all the service names on the system
	 */
	function parseURLInput($snames){
		$this->rawpath = isset($_GET['path']) ? $_GET['path'] : "";
		$path = (isset($_GET['path']) && $_GET['path']) ? explode("/", $_GET['path']) : array();
		if(count($path) > 0 && $path[count($path) -1] == "") {
			array_pop($path);
		}
		if(count($path) == 0){	//no collection id specified - set to root context
			$this->collection_id = "all";
			$this->servicename = "";
			$this->args = array();
		}
		elseif($path[0] == 'system' or in_array($path[0], $snames)){
			if($path[0] == 'system') array_shift($path);
			$this->collection_id = "all";
			$this->servicename = array_shift($path);
			$this->args = $path;
		}			
		elseif(count($path) == 1){
			$cs_id = array_shift($path);
			if(in_array($cs_id, $snames)){
				$this->collection_id = "all";
				$this->servicename = $cs_id;
			}
			else {
				$this->collection_id = $cs_id;
				$this->servicename = "";
			}		
			$this->args = array();
		}
		else {
			$cs_id = array_shift($path);
			if(in_array($cs_id, $snames)){
				$this->collection_id = "all";
				$this->servicename = $cs_id;
			}
			else {
				$this->collection_id = $cs_id;					
				$this->servicename = isset($path[0]) ? array_shift($path) : "";
			}
			$this->args = $path;
		}
	}

	/**
	 * Is the user in their home context?
	 * @return boolean true if they are
	 */
	function inHomeContext(){
		return ($this->collection_id == "all" && $this->servicename == "");
	}
	
	/**
	 * Return the name of the service in the call
	 */
	function name(){
		return $this->servicename;
	}
	/**
	 * Get argument number n
	 * @param number $n
	 * @return string the argument's value
	 */
	function getArg($n=0){
		return isset($this->args[$n]) ? $this->args[$n] : false;
	}
	
	/**
	 * The collection id passed in the call
	 * @return string collection id
	 */
	function getCollectionID(){
		return $this->collection_id;
	}
	/**
	 * Where did the call come from?
	 * @param string $x html | rest
	 */
	function setProvenance($x){
		$this->provenance = $x;
	}
}