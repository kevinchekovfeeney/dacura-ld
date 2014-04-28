<?php
getRoute()->post('/', 'create');
getRoute()->get('/(\w+)', 'view');
getRoute()->post('/(\w+)', 'update');
getRoute()->delete('/(\w+)', 'delete');

include_once("DatasetDacuraServer.php");




function create(){
	global $dacura_settings;
	$dwas = new DatasetDacuraAjaxServer($dacura_settings);
	if(isset($_POST['payload']) && isset($_POST['id']) && isset($_POST['collection_id'])){
		$dataset_obj = json_decode($_POST['payload']);
		if(!$dataset_obj){
			return $dwas->write_error("Payload in create dataset message would not parse", 400);
		}
		$dataset_id = $_POST['id'];
		$cid = $_POST['collection_id'];
		$cobj = $dwas->createNewDataset($dataset_id, $cid, $dataset_obj);
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
	$dwas = new DatasetDacuraAjaxServer($dacura_settings);
	$collobj = $dwas->getDataset($id);
	if($collobj){
		echo json_encode($collobj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function update($id){
	global $dacura_settings;
	$dwas = new DatasetDacuraAjaxServer($dacura_settings);
	if(!isset($_POST['payload'])){
		return $dwas->write_error("Missing required create data fields.", 400);
	}
	$new_dataset_obj = json_decode($_POST['payload']);
	if(!$new_dataset_obj){
		return $dwas->write_error("Payload dataset update would not parse.", 400);
	}
	$collobj = $dwas->getDataset($id);
	if(!$collobj){
		return $dwas->write_error($dwas->errmsg, $dwas->errcode);
	}
	$updated_obj = $dwas->updateDataset($new_dataset_obj, $collobj);
	if($updated_obj){
		echo json_encode($updated_obj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}


function delete($id){
	global $dacura_settings;
	$dwas = new DatasetDacuraAjaxServer($dacura_settings);
	$collobj = $dwas->deleteDataset($id);
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}
