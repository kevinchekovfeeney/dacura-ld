<?php 
/* 
 * Traffic controller page where all browser page requests are routed
 * 
 * This page orchestrates some security checks, then creates the service and server objects to handle the call based on the context
 * 
 * The general pattern of context is
 * /collection_id/dataset_id/service/service_parameters/etc
 * 
 * Based on the service identified in the context, this file loads (with include) the service class file which draws the page
 * 
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors: Chekov 15/12/2014 - 9/1/2015
 * Licence: GPL v2
 */

include_once("phplib/settings.php");
include_once("phplib/RequestLog.php");
//start request log at once to capture as much of the performance data as possible...
$request_log = new RequestLog($dacura_settings, "web");
include_once("phplib/DacuraObject.php");
include_once("phplib/ServiceLoader.php");
include_once("phplib/DacuraUser.php");
session_start();

$servman = new ServiceLoader($dacura_settings);

$service = $servman->loadServiceFromURL($request_log);
if($service){
	$dacura_server = $service->loadServer();
	if(!$dacura_server){
		$servman->renderErrorPage("error", $service->errcode, $service->errmsg );
		$request_log->setResult($service->errmsg , "Failed to load Dacura Server ");
	}
	elseif($dacura_server->userHasViewPagePermission()){
		$service->renderFullPage($dacura_server);
		$request_log->setResult(200, "Page rendered");
	}
	else {
		$servman->renderErrorPage("denied", $dacura_server->errcode, $dacura_server->errmsg );
		$request_log->setResult(401, "Access Denied: $dacura_server->errcode | $dacura_server->errmsg");
	}
}
else {
	$servman->renderErrorPage("error", $servman->errcode, $servman->errmsg );
	$request_log->setResult( 400, "Failed to load service: $servman->errcode|$servman->errmsg" );
}

?>
