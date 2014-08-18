<?php 
/* 
 * Traffic controller page where all requests are routed
 * The general pattern of context is
 * /[application]|collection_id/dataset_id|[application]/[application]/application/parameters/etc
 */

include_once("phplib/settings.php");
include_once("phplib/ServiceManager.php");
include_once("phplib/DacuraUser.php");
session_start();




$servman = new ServiceManager($dacura_settings);
$service_call = $servman->parseServiceCall();
$service_call->setProvenance("html");
if($service_call->inHomeContext()){
	if($servman->isLoggedIn()){
		$service_call->servicename = "browse";
	} 
	else {
		$service_call->servicename = "core";
		$service_call->args[] = "welcome";
	}
}
if($servman->serviceCallIsValid($service_call)){
	if($servman->hasPermissions($service_call)){
		$service = $servman->loadService($service_call);
		if($service){
			$service_include_path = $service->getIndexPath($service_call);
			include_once($service_include_path);
		}
		else {
			$servman->renderServiceScreen("core", "error", array('message' => $servman->errmsg), $service_call);				
		}		
	}
	else {
		$servman->renderServiceScreen("core", "denied", array('message' => $servman->errmsg), $service_call);
	}
}
else {
	$servman->renderServiceScreen("core", "error", array("title" => "The page does not exist", 'message' => $servman->errmsg), $service_call);
}
?>
