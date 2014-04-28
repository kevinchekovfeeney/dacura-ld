<?php
getRoute()->post('/', 'create');
getRoute()->get('/(\w+)', 'view');
getRoute()->post('/(\w+)', 'update');
getRoute()->delete('/(\w+)', 'delete');

include_once("CollectionDacuraServer.php");




function create(){
	global $dacura_settings;
	$dwas = new CollectionDacuraAjaxServer($dacura_settings);
	if(isset($_POST['payload']) && isset($_POST['id'])){
		$collection_obj = json_decode($_POST['payload']);
		if(!$collection_obj){
			return $dwas->write_error("Payload in create collection message would not parse", 400);
		}
		$collection_id = $_POST['id'];
		$cobj = $dwas->createNewCollection($collection_id, $collection_obj);
		if($cobj){
			echo json_encode($cobj);
		}
		else $dwas->write_error($dwas->errmsg, $dwas->errcode);
	}
	else {
		$dwas->write_error("Missing required create data fields.", 400);
	}
}

function view($id){
	global $dacura_settings;
	$dwas = new CollectionDacuraAjaxServer($dacura_settings);
	$collobj = $dwas->getCollection($id);
	if($collobj){
		echo json_encode($collobj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function update($id){
	global $dacura_settings;
	$dwas = new CollectionDacuraAjaxServer($dacura_settings);
	if(!isset($_POST['payload'])){
		return $dwas->write_error("Missing required create data fields.", 400);
	}
	$new_collection_obj = json_decode($_POST['payload']);
	if(!$new_collection_obj){
		return $dwas->write_error("Payload collection update would not parse.", 400);
	}
	$collobj = $dwas->getCollection($id);
	if(!$collobj){
		return $dwas->write_error($dwas->errmsg, $dwas->errcode);
	}
	$updated_obj = $dwas->updateCollection($new_collection_obj, $collobj);
	if($updated_obj){
		echo json_encode($updated_obj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}


function delete($id){
	global $dacura_settings;
	$dwas = new CollectionDacuraAjaxServer($dacura_settings);
	$collobj = $dwas->deleteCollection($id);
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}
