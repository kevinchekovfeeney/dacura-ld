<?php
getRoute()->get('/', 'listing');
getRoute()->get('/(\w+)', 'view');

include_once("SourcesDacuraServer.php");

function view($id){
	global $service;
	//$dwas = new UsersDacuraAjaxServer($service->settings);
	//$collobj = $dwas->getUser($id);
	//if($collobj){
	//	echo json_encode($collobj);	
	//}
	//else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function listing(){
	global $service;
	/*$dwas = new UsersDacuraAjaxServer($service->settings);
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	$collobj = $dwas->getUsersInContext($c_id, $d_id);
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);*/
}

