<?php

/*
 * Traffic controller page where all api requests are routed
 * Uses the epiphany library for routing https://github.com/jmathai/epiphany
 * This page orchestrates some security checks and sets up routing to the correct service 
 *
 * The general pattern of context is
 * /collection_id/dataset_id/service/service_parameters/etc
 *
 * Based on the service identified in the context, this file loads (with include) the service/api.php 
 * file which creates the server object to handle the request
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */

include_once("phplib/settings.php");
include_once("phplib/RequestLog.php");
//start request log at once to capture as much of the performance data as possible...
$request_log = new RequestLog($dacura_settings, "api");

require_once("phplib/http_response_code.php");
require_once("phplib/libs/epiphany/src/Epi.php");
include_once("phplib/DacuraObject.php");
include_once("phplib/ServiceLoader.php");
include_once("phplib/DacuraUser.php");

session_start();

function write_error($str, $code = 400){
	http_response_code($code);
	echo $str;
}

$servman = new ServiceLoader($dacura_settings);
$service = $servman->loadServiceFromAPI($request_log);
if($service){
	$dacura_server = $service->loadServer();
	if($dacura_server->contextIsValid() || $service->name() == "config" && isset($service->args[0]) && $service->args[0] == "create"){
		$api_path = $service->settings['path_to_services'].$service->name()."/api.php";
		if(file_exists($api_path)){
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
				write_error($msg, 404);
			}
		}
		else {
			$msg = "Service ".$service->name()." does not have an API";
			$request_log->setResult(404, $msg);				
			write_error($msg, 404);
		}
	}
	else {
		$msg = "Invalid Context ".$dacura_server->contextStr();
		$request_log->setResult($dacura_server->errcode, $dacura_server->errmsg);
		write_error($dacura_server->errmsg, $dacura_server->errcode);
	}
}
else {
	$msg = "Unknown Service: ".$servman->errmsg;
	$request_log->setResult(404, $msg);				
	write_error($msg, 404);
}


