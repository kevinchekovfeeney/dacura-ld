<?php 
/** 
 * Traffic controller page where all browser page requests are routed
 * 
 * This page orchestrates some security checks, then creates the service and server objects to handle the call based on the context
 * 
 * The general pattern of context is
 * /collection_id/service/service_parameters/etc
 * 
 * Based on the service identified in the context, this file loads (with include) the service class file which draws the page
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */

include_once("phplib/settings.php");
include_once("phplib/RequestLog.php");
//start request log at once to capture as much of the performance data as possible...
$request_log = new RequestLog($dacura_settings, "web");
include_once("phplib/DacuraObject.php");
include_once("phplib/ServiceLoader.php");
include_once("phplib/DacuraUser.php");
include_once("phplib/DacuraForm.php");
session_start();

$servman = new ServiceLoader($dacura_settings);
/** @global DacuraService $service the globally accessible service object */
$service = $servman->loadServiceFromURL($request_log);
if($service){
	/** @global DacuraServer $dacura_server the globally accessible server object */
	$dacura_server = $service->loadServer();
	if(!$dacura_server){
		$service->renderScreen("error", array("title" => $service->errcode, "message" => $service->errmsg ), "core");
		$request_log->setResult($service->errmsg , "Failed to load Dacura Server ");
	}
	elseif($dacura_server->userHasViewPagePermission()){
		$service->renderFullPage($dacura_server);
		$request_log->setResult(200, "Page rendered");
	}
	else {
		$service->renderScreen("denied", array("title" => "Access Denied " .$dacura_server->errcode, "message" => $dacura_server->errmsg ), "core");
		$request_log->setResult(401, "Access Denied: $dacura_server->errcode | $dacura_server->errmsg");
	}
}
else {
	$service = new DacuraService($dacura_settings);
	$service->renderScreen("error", array("title" => "Error retrieving page" .$servman->errcode, "message" => $servman->errmsg ), "core");
	$request_log->setResult( 400, "Failed to load service: $servman->errcode|$servman->errmsg" );
}

?>
