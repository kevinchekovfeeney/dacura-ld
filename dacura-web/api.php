<?php
/**
 * Traffic controller page where all api requests are routed
 * Uses the epiphany library for routing https://github.com/jmathai/epiphany
 * This page orchestrates some security checks and sets up routing to the correct service 
 *
 * The general pattern of context is
 * /collection_id/service/service_parameters/etc
 *
 * Based on the service identified in the context, this file loads (with include) the service/api.php 
 * file which creates the server object to handle the request
 *
 * * Creation Date: 20/11/2014 
 * @author Chekov
 * @license GPL v2
 */
include_once("phplib/settings.php");
include_once("phplib/RequestLog.php");
//start request log at once to capture as much of the performance data as possible...
$request_log = new RequestLog($dacura_settings, "api");
require_once("phplib/utilities.php");
require_once("phplib/libs/epiphany/src/Epi.php");
include_once("phplib/DacuraObject.php");
include_once("phplib/ServiceLoader.php");
include_once("phplib/DacuraUser.php");

/**
 * In case we fail before we load our libraries for sending errors...
 * @param string $str message
 * @param number $code http response code
 */
function write_error($str, $code = 400){
	http_response_code($code);
	echo $str;
	return false;
}
session_start();
$servman = new ServiceLoader($dacura_settings);
/** @global DacuraService $service the globally accessible service object */
if(!($service = $servman->loadServiceFromAPI($request_log))){
	$msg = "Invalid Context ".$dacura_server->contextStr();
	$request_log->setResult($dacura_server->errcode, $dacura_server->errmsg);
	return write_error($dacura_server->errmsg, $dacura_server->errcode);
}
/** @global DacuraServer $dacura_server the globally accessible server object */
if(!($dacura_server = $service->loadServer())){
	$msg = "Error loading server: ".$service->errmsg;
	$request_log->setResult($service->errcode, $msg);
	return write_error($msg, $service->errcode);
}
if(!($dacura_server->contextIsValid() || $service->name() == "config" && isset($service->args[0]) && $service->args[0] == "create")){
	$msg = "Unknown Service: ".$servman->errmsg;
	$request_log->setResult(404, $msg);
	return write_error($msg, 404);	
}
$api_path = $service->settings['path_to_services'].$service->name()."/api.php";
if(!file_exists($api_path)){
	$msg = "Service ".$service->name()." does not have an API";
	$request_log->setResult(404, $msg);
	return write_error($msg, 404);
}
$rt = (count($service->args) > 0) ? "/".implode("/", $service->args) : "/";
$_GET['__route__'] = $rt;
Epi::init('route');
Epi::setSetting('exceptions', true);
include_once($api_path);
try {
	getRoute()->run();
}
catch(Exception $e){
	$msg = "Unknown API: ".$e->getMessage();
	$request_log->setResult(404, $msg);				
	return write_error($msg, 404);
}

