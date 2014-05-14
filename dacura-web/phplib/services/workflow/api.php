<?php
//getRoute()->post('/', 'create');
getRoute()->get('/', 'listing');
getRoute()->get('/(\w+)', 'view');
getRoute()->post('/', 'create');
getRoute()->post('/(\w+)', 'update');
getRoute()->delete('/(\w+)', 'delete');

include_once("WorkflowDacuraServer.php");

function view($id){
	global $service;
	$dwas = new WorkflowDacuraAjaxServer($service->settings);
	$collobj = $dwas->getWorkflow($id);
	if($collobj){
		echo json_encode($collobj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function listing(){
	global $service;
	$dwas = new WorkflowDacuraAjaxServer($service->settings);
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	$collobj = $dwas->getWorkflowInContext($c_id, $d_id);
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function create(){
	global $service;
	$dwas = new WorkflowDacuraAjaxServer($service->settings);
	$uobj = $dwas->addWorkflow();
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function update($id){
	global $service;
	$dwas = new WorkflowDacuraAjaxServer($service->settings);
	$uobj = $dwas->updateWorkflow($workflow_obj);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}


function delete($id){
	global $service;
	$dwas = new WorkflowDacuraAjaxServer($service->settings);
	$uobj = $dwas->deleteWorkflow($id);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function deleterole($uid, $rid){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service->settings);
	$uobj = $dwas->deleteUserRole($uid, $rid);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}
