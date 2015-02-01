<?php
/*
 * API for config service - viewing and updating configuration details
 * The arguments are passed as context.
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

getRoute()->get('/', 'view');
getRoute()->post('/create', 'create');
getRoute()->post('/', 'update');
getRoute()->delete('/', 'delete');

function view(){
	global $dacura_server;
	$dacura_server->init("viewconfig");
	$c_id = $dacura_server->ucontext->getCollectionID(); 
	$d_id = $dacura_server->ucontext->getDatasetID();
	if($dacura_server->userHasRole("admin")){
		if($c_id == "all"){
			$collobj = $dacura_server->getCollectionList();				
		}
		elseif($d_id == "all"){
			$collobj = $dacura_server->getCollection($c_id);
		}		
		else {
			$collobj = $dacura_server->getDatasetConfig($c_id, $d_id);
		}
		if($collobj){
			return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
		}
	}
	$dacura_server->write_http_error();
}

function create(){
	global $dacura_server;
	$c_id = $dacura_server->ucontext->getCollectionID(); 
	$d_id = $dacura_server->ucontext->getDatasetID();
	$title = isset($_POST['title']) ? $_POST['title'] : "";
	if($d_id == "all"){ //new collection
		if($dacura_server->userHasRole("admin", "all")){
			$dacura_server->init("create.collection");
			$colobj = $dacura_server->createNewCollection($c_id, $title);
			if($colobj){
				return $dacura_server->write_json_result($colobj, "Created Collection $c_id ($title)");
			}
		}
	}
	else { //new dataset
		if($dacura_server->userHasRole("admin", false, "all")){
			$dacura_server->init("create.dataset");
			$dobj = $dacura_server->createNewDataset($c_id, $d_id, $title);
			if($dobj){
				return $dacura_server->write_json_result($dobj, "Created Collection $c_id ($title)");
			}
		}	
	}
	return $dacura_server->write_http_error();
}

function update(){
	global $dacura_server;
	$c_id = $dacura_server->ucontext->getCollectionID();
	$d_id = $dacura_server->ucontext->getDatasetID();
	$title = isset($_POST['title']) ? $_POST['title'] : "";
	if($dacura_server->userHasRole("admin")){
		if($d_id == "all"){ //update collection
			$payload = isset($_POST['payload']) ? json_decode($_POST['payload'], true) : "";
			$dacura_server->init("update.collection");
			$cobj = $dacura_server->updateCollection($c_id, $title, $payload);
			if($cobj){
				return $dacura_server->write_json_result($cobj, "Updated Collection $c_id ($title)");
			}
		}
		else{ //update dataset
			$update_fields = array();
			if($title){
				$update_fields["title"] = $title;
			}
			if(isset($_POST["json"])){
				$update_fields["json"] =  json_decode($_POST['json'], true);				
			}
			if(isset($_POST["schema"])){
				$update_fields["schema"] =  json_decode($_POST['schema'], true);				
			}
			if(isset($_POST["config"])){
				$update_fields["config"] =  json_decode($_POST['config'], true);
			}
			$dacura_server->init("update.dataset", implode("|", array_keys($update_fields)));
				
			$dobj = $dacura_server->updateDatasetConfig($c_id, $d_id, $update_fields);
			if($dobj){
				return $dacura_server->write_json_result($dobj, "Updated Dataset $d_id ($title)");
			}
		}
	}
	$dacura_server->write_http_error();
}


function delete(){
	global $dacura_server;
	$c_id = $dacura_server->ucontext->getCollectionID();
	$d_id = $dacura_server->ucontext->getDatasetID();
	if($d_id == "all"){ //delete collection
		$dacura_server->init("delete.collection");
		if($dacura_server->userHasRole("admin", "all")){
			$collobj = $dacura_server->deleteCollection($c_id);
			if($collobj){
				return $dacura_server->write_json_result($collobj, "Deleted Collection $c_id");
			}
		}
	}
	else {
		$dacura_server->init("delete.dataset");
		if($dacura_server->userHasRole("admin", "all")){
			$collobj = $dacura_server->deleteDataset($d_id);
			if($collobj){
				return $dacura_server->write_json_result($collobj, "Deleted Dataset $d_id");
			}
		}
	}
	$dacura_server->write_http_error();
}

