<?php 
/**
 * A class which controller classes inherit from 
 * 
 * It includes a common constructor and a few functions for access service context information 
 * @author chekov
 * @license GPL V2
 */
class DacuraController extends DacuraObject {
	/** @var DacuraService the service that is invoking this controller */
	var $service;
	
	/** 
	 * Object constructor
	 * @param DacuraService $service The service that is invoking this controller
	 */
	function __construct(DacuraService &$service){
		$this->service = $service;
	}
	
	/**
	 * Logs an event to the system's event log
	 * @param string $level must be one of RequestLog::$log_levels
	 * @param number $code the http return code (200 for ok, 400+ -> error)
	 * @param string $msg the message to log
	 */
	function logEvent($level, $code, $msg){
		$this->service->logger->logEvent($level, $code, $msg);
	}
	
	/**
	 * Logs the result of a request to the request log
	 * @param number $code http response code (200 = ok)
	 * @param string $note human readable note to log
	 */
	function logResult($code = 200, $note = ""){
		$this->service->logger->setResult($code, $note);
	}
	
	/**
	 * Adds a timestamp for an event [action, object] to the request log
 * 
	 * @param string $event the name of the event
	 * @param string $level must be one of RequestLog::debug_levels
	 */
	function timeEvent($event, $level){
		$this->service->logger->timeEvent($event, $level);
	}
	
	
	/**
	 * Get Current Collection ID
	 * @return string collection id
	 */
	function cid(){
		return $this->service->cid();
	}
	
	/**
	 * Returns the base url of the dacura server
	 * @param string $ajax if true, the ajax url is returned, otherwise the html url
	 * @return string the url
	 */
	function durl($ajax = false){
		return (!$ajax) ? $this->getSystemSetting('install_url') : $this->getSystemSetting('ajaxurl');
	}
	
	/**
	 * The name of the current service
	 * @return string service name
	 */
	function sname(){
		return $this->service->name();
	}
	
	/**
	 * A human readable string describing the current context
	 * @return string
	 */
	function contextStr(){
		return "[".$this->cid()."]";
	}
	
	/**
	 * Fetch a system-wide configuration setting
	 * @param string $cname the name of the configuration setting required
	 * @param string $def a default value to be returned in case the configuration setting is unset
	 * @param array(string) $fillers an associative array of strings available to be subsituted into
	 * the configuration setting templates
	 */
	function getSystemSetting($cname, $def = "", $fillers = array()){
		return $this->service->getSystemSetting($cname, $def, $fillers);
	}
	
	/**
	 * Fetch a configuration setting for the current service context
	 * @param string $cname the name of the configuration setting required
	 * @param string $def a default value to be returned in case the configuration setting is unset
	 * @param array(string) $fillers an associative array of strings available to be subsituted into
	 * the configuration setting templates
	 */
	function getServiceSetting($cname, $def = "", $fillers = array()){
		return $this->service->getServiceSetting($cname, $def, $fillers);
	}
		
	/**
	 * Dacura server functions signal failure by calling this function. It returns false,
	 * sets the object's errcode and errmsg and and optionally logs the failure.
	 */
	
	/**
	 * Overrides the regular failure result to add in logging (also can be called with state and no arguments)
	 * (non-PHPdoc)
	 * @see DacuraObject::failure_result()
	 * @param string $msg the human-readable failure message
	 * @param number $code the http response code associated
	 * $loglevel string one of 
	 * @see RequestLog::log_levels
	 */
	function failure_result($msg = false, $code = false, $loglevel = ""){
		if($msg === false && $code === false){
			$msg = $this->errmsg;
			$code = $this->errcode;
		}
		if($loglevel) {
			$this->logEvent($loglevel, $code, $msg);
		}
		return parent::failure_result($msg, $code);
	}
	
}