<?php
/*
 * API for config service - viewing and updating configuration details
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */
if($dacura_server->userHasRole("admin", "all")){
	getRoute()->post('/create', 'create');
	getRoute()->delete('/', 'delete');
	getRoute()->get('/logs', 'showLogs');
}
if($dacura_server->userHasRole("admin")){//only publish the API endpoints that the user has permission to see
	getRoute()->get('/', 'view');
	getRoute()->post('/files', 'upload');
	getRoute()->post('/', 'update');
}

function view(){
	global $dacura_server;
	$dacura_server->init("viewconfig");
	if($dacura_server->cid() == "all"){
		$collobj = array_values($dacura_server->getCollectionList());	
	}
	elseif($dacura_server->did() == "all"){
		$collobj = $dacura_server->getCollection();
	}		
	else {
		return $dacura_server->write_http_error(400, "Dataset management API is not operational");
	}
	if($collobj){
		return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
	}
	$dacura_server->write_http_error();
}

function create(){
	global $dacura_server;
	$title = isset($_POST['title']) ? $_POST['title'] : "";
	if($dacura_server->did()== "all"){ //new collection
		$dacura_server->init("create.collection");
		$colobj = $dacura_server->createNewCollection($dacura_server->cid(), $title);
		//opr($colobj);
		if($colobj){
			return $dacura_server->write_json_result($colobj, "Created Collection ".$dacura_server->cid()." ($title)");
		}
		else {
			echo "<P>$title collection";
		}
	}
	else { //new dataset
		return $dacura_server->write_http_error(400, "Dataset management API is not operational");
	}
	return $dacura_server->write_http_error();
}

function update(){
	global $dacura_server;
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		return $dacura_server->write_http_error(400, "Submitted update object was not valid json");
	}
	if($dacura_server->did() == "all"){ //update collection
		$dacura_server->init("update.collection");
		$cobj = $dacura_server->updateCollection($dacura_server->cid(), $obj);
		if($cobj){
			return $dacura_server->write_json_result($cobj, "Updated Collection ".$dacura_server->cid());
		}
	}
	else{ //update dataset
		return $dacura_server->write_http_error(400, "Dataset management API is not operational");		
	}
	$dacura_server->write_http_error();
}

function delete(){
	global $dacura_server;
	if($dacura_server->did()== "all"){ //delete collection
		$dacura_server->init("delete.collection");
		$collobj = $dacura_server->deleteCollection($dacura_server->cid());
		if($collobj){
			return $dacura_server->write_json_result($collobj, "Deleted Collection ".$dacura_server->cid());
		}
	}
	else {
		return $dacura_server->write_http_error(400, "Dataset management API is not operational");
	}
	$dacura_server->write_http_error();
}

function upload(){
	global $dacura_server;
	if(!isset($_GET['filename'])){
		return $dacura_server->write_http_error(400, "No filename included in file upload request");
	}
	$payload = file_get_contents('php://input');
	if(!$payload){
		return $dacura_server->write_http_error(400, "No file included in file upload request");
	}
	$furl = $dacura_server->saveUploadedFile($_GET['filename'], $payload);
	if($furl){
		return $dacura_server->write_json_result($furl, "Uploaded File $furl for ".$dacura_server->contextStr());
	}
	$dacura_server->write_http_error();
}


function showLogs(){
	global $dacura_server;
	$dacura_server->init("show.logs");
	$logobj = $dacura_server->getLogsAsListingObject();
	if($logobj){
		return $dacura_server->write_json_result($logobj, "Log listing issued");
	}
	else {
		return $dacura_server->write_http_error();
	}
}

