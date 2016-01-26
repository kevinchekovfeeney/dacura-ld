<?php

/**
 * API for config service - viewing and updating user details
 *
 * Creation Date: 12/01/2015
 * @author chekov
 * @package config/api
 * @license GPL v2
 */

/** We only pulish the api endpoints that are relevant to the user's permissions */
if($dacura_server->userHasRole("admin", "all")){
	getRoute()->post('/create', 'create');
	getRoute()->delete('/', 'delete');
}
if($dacura_server->userHasFacet("inspect")){
	getRoute()->get('/logs', 'showLogs');
}
if($dacura_server->userHasFacet("manage")){
	getRoute()->post('/', 'update');
//	getRoute()->post('/files', 'upload');
}	
if($dacura_server->userHasFacet("view")){
	getRoute()->get('/(\w*)', 'view');
}

/**
 * POST /create
 *
 * requires $json[id] 
 * optional: $json[title]
 * Creates a new collection with the passed id (and title if present)
 * @return a JSON version of a Collection object [id, status, name, profile]
 * @api
 */
function create(){
	global $dacura_server;
	$title = isset($_POST['title']) ? $_POST['title'] : "";
	$dacura_server->init("create.collection");
	if($colobj = $dacura_server->createNewCollection($_POST['id'], $title)){
		return $dacura_server->write_json_result($colobj, "Created Collection ".$dacura_server->cid()." ($title)");
	}
	return $dacura_server->write_http_error();
}

/**
 * GET /[$part]
 *
 * Fetches the configuration settings for the current collection context
 * @param $part optional 'part' of the configuration - can be list for a list of collections (only in the 'all' context)
 * @return an associative array with the fields: settings, services, collection (and meta & servicemeta for all)
 * or a simple array with elements having (status, id, name) when the argument is 'list'
 *
 * This function does two things to get around the problem that all requests are to the root resource normally (because the arguments are contained in the preceding parts of the url)
 * @api
 */
function view($part = ""){
	global $dacura_server;
	if($part == "list"){
		$dacura_server->init("view.collections");
		$ls = $dacura_server->getCollectionList();
		$collobj = array();
		foreach($ls as $c => $col){
			if($c == "all") continue;
			$collobj[] = array("id" => $c , "status" => $col->status, "name" => $col->name);
		}
	}
	else {
		$collobj = $dacura_server->getCollectionConfig();
	}
	if($collobj){
		return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
	}
	$dacura_server->write_http_error();
}


/**
 *
 * requires $json[settings] and/or $json[services] (or, if all, $json[meta] and/or $json[servicemeta]
 * 
 * Updates the collection configuration 
 * @return a JSON version of the current settings [1 or more of settings, service, collection, meta, servicemeta]
 * @api
 */
function update(){
	global $dacura_server;
	$json = file_get_contents('php://input');
	if(!($obj = json_decode($json, true))){
		return $dacura_server->write_http_error(400, "Submitted update object was not valid json");
	}
	if(isset($obj['settings']['status']) && $obj['settings']['status'] == "deleted"){
		$dacura_server->init("delete.collection");
		if(!$dacura_server->userHasRole("admin", "all")){
			return $dacura_server->write_http_error(401, "You do not have permission to delete collections");
		}
		if($collobj = $dacura_server->deleteCollection($dacura_server->cid())){
			return $dacura_server-> write_http_result(0, "Successfully Deleted Collection ".$dacura_server->cid());
		}
	}
	else {
		$dacura_server->init("update.collection");
		//need to do some checks on the update -> status = deleted...also the facet stuff - some need admin....
		if($cobj = $dacura_server->updateCollectionConfig($dacura_server->cid(), $obj)){
			return $dacura_server->write_json_result($cobj, "Updated Collection ".$dacura_server->cid());
		}
	}
	$dacura_server->write_http_error();
}

/**
 * DELETE /
 *
 * Deletes the collection from the system
 * @return a JSON version of the last version of the Collection object
 * @api
 */
function delete(){
	global $dacura_server;
	if($dacura_server->cid() == "all"){
		return $dacura_server->write_http_error(400, "Deleting system configuration is not permitted");		
	}
	$dacura_server->init("delete.collection");
	if($dacura_server->deleteCollection($dacura_server->cid())){
		return $dacura_server-> write_http_result(200, "Successfully Deleted Collection ".$dacura_server->cid());
	}
	$dacura_server->write_http_error();
}

/**
 * GET /logs
 *
 * Retrieves the most recent entries from the context's request log
 * @return an array representing the 50 most recent log entries...
 * @api
 */
function showLogs(){
	global $dacura_server;
	$dacura_server->init("show.logs");
	if($logobj = $dacura_server->getLogsAsListingObject()){
		return $dacura_server->write_json_result($logobj, "Log listing issued");
	}
	else {
		return $dacura_server->write_http_error();
	}
}

/*
 * Kcfinder library replaced this, kept here in case we revert
 * function upload(){
 global $dacura_server;
 if(!isset($_GET['filename'])){
 return $dacura_server->write_http_error(400, "No filename included in file upload request");
 }
 $payload = file_get_contents('php://input');
 if(!$payload){
 return $dacura_server->write_http_error(400, "No file included in file upload request");
 }
 if($furl = $dacura_server->saveUploadedFile($_GET['filename'], $payload)){
 return $dacura_server->write_json_result($furl, "Uploaded File $furl for ".$dacura_server->contextStr());
 }
 $dacura_server->write_http_error();
 }*/