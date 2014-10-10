<?php

require_once("phplib/DacuraWidgetAjaxServer.php");
require_once("settings.php");

header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type');

$action = isset($_POST['action']) ? $_POST['action'] : false;
$dwas = new DacuraWidgetAjaxServer($dacura_settings);
if(!$action){
	$dwas->write_error("ERROR - NO ACTION PASSED");
}
elseif($action == "get_record") {
	$id = $_POST['id'];
	if(!$id){
		$dwas->write_error("ERROR - NO ID GIVEN");
	}
	$dwas->getEventRecord($id);
}
elseif($action == "get_widget"){
	$options = isset($_POST['options']) ? $_POST['options'] : "{}";
	$dwas->getWidgetStructure($options);
}
elseif($action == "add_candidate"){
	if($dwas->fileCandidate()){
		echo json_encode("Success");
	}
}
else {
	$dwas->write_error("$action is not a valid action");
}