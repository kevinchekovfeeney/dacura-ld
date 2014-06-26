<?php
//include_once("phplib/DacuraServer.php");
//include_once("phplib/settings.php");
require_once("phplib/http_response_code.php");
require_once("phplib/libs/epiphany/src/Epi.php");
include_once("phplib/settings.php");

include_once("phplib/ServiceManager.php");
include_once("phplib/DacuraUser.php");

session_start();

function write_error($str, $code = 400){
	http_response_code($code);
	echo $str;
}

$servman = new ServiceManager($dacura_settings);
$service_call = $servman->parseServiceCall();
//print_r($service_call);
$service_call->setProvenance("api");
if(!$service_call->name()){
	write_error("Empty request sent to dacura api - not addressed to any service", 400);
}
else {
	$service = $servman->loadService($service_call);
	$api_path = $dacura_settings['path_to_services'].$service_call->name()."/api.php";
	if(file_exists($api_path)){
		if($servman->hasPermissions($service_call)){
			//set up routing for the EPI router to use...
			$rt = (count($service_call->args) > 0) ? "/".implode("/", $service_call->args) : "/";
			$_GET['__route__'] = $rt;
			Epi::init('route');
			Epi::setSetting('exceptions', true);
			include_once($api_path);
			getRoute()->run();
		}
		else {
			write_error("Access Denied [".$service_call->name()."] ".$servman->errmsg, 400);				
		}
	}
	else {
		write_error("Unknown Service [".$service_call->name()."]", 400);
	}
}

