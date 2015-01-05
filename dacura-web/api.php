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


require_once("phplib/http_response_code.php");
require_once("phplib/libs/epiphany/src/Epi.php");
include_once("phplib/settings.php");
include_once("phplib/DacuraObject.php");
include_once("phplib/ServiceLoader.php");
include_once("phplib/DacuraUser.php");

session_start();

function write_error($str, $code = 400){
	http_response_code($code);
	echo $str;
}

$servman = new ServiceLoader($dacura_settings);
$service = $servman->loadServiceFromAPI();
if($service && $service->hasPermission()){
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
			write_error("Unknown API: ".$e->getMessage(), 400);
		}
	}
	else {
		write_error("Service ".$service->name()." does not have an API", 400);
	}
}
elseif($service){
	write_error("Access Denied [".$service->name()."] ".$servman->errmsg, 400);				
	
}
else {
	write_error("Unknown Service", 400);
}


