<?php 
/* 
 * Traffic controller page where all browser page requests are routed
 * 
 * This page orchestrates some security checks, then creates the service and server objects to handle the call based on the context
 * 
 * The general pattern of context is
 * /collection_id/dataset_id/service/service_parameters/etc
 * 
 * Based on the service identified in the context, this file loads (with include) the service/index.php file which draws the page
 * 
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors: Chekov 15/12/2014
 * Modified On:
 * Licence: GPL v2
 */

include_once("phplib/settings.php");
include_once("phplib/DacuraObject.php");
include_once("phplib/ServiceLoader.php");
include_once("phplib/DacuraUser.php");
session_start();



$servman = new ServiceLoader($dacura_settings);
$service = $servman->loadServiceFromURL();
if($service && $service->hasPermission()){
	$service->renderFullPage(); 	
}
elseif(!$service) {
	$servman->renderFullServicePage("core", array(
			"screen" => "error", 
			'title' => "Error loading service", 
			'message' => $servman->errmsg));
}
else {
	$servman->renderFullServicePage("core", array(
			"screen" => "denied", 
			'title' => "Permission Denied", 
			'message' => $service->errmsg ), 
		$service);
}
?>
