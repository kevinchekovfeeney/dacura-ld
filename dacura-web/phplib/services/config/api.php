<?php
//getRoute()->post('/', 'create');
getRoute()->get('/', 'view');
getRoute()->post('/', 'update');
getRoute()->delete('/', 'delete');

include_once("ConfigDacuraServer.php");

function view(){
	global $service;
	$dwas = new ConfigDacuraAjaxServer($service);
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	if(!$c_id) {
		$collobj = $dwas->getCollectionList();
	}
	elseif(!$d_id) {
		$collobj = $dwas->getCollection($c_id);
	}
	else {
		$collobj = $dwas->getDataset($d_id);
	}
	if($collobj){
		echo json_encode($collobj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}


function update(){
	global $service;
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	$dwas = new ConfigDacuraAjaxServer($service);
	//if no cid is specified it is a request to create a new collection 
	if(!$c_id){
		$collection_obj = json_decode($_POST['payload'], true);
		if(!$collection_obj){
			return $dwas->write_error("Payload in create collection message would not parse", 400);
		}
		$collection_id = $_POST['id'];
		$collection_title = $collection_obj['title'];
		$cobj = $dwas->createNewCollection($collection_id, $collection_title, $collection_obj);
		if($cobj){
			echo json_encode($cobj);
		}	
	}
	else if(!$d_id){
		//if no did is specified it is _either_ a request to create a new dataset or a request to update the collection 
		//the contents of the post include an id if it is a new 
		if(isset($_POST['id'])){
			$dataset_obj = json_decode($_POST['payload'], true);
			if(!$dataset_obj){
				return $dwas->write_error("Payload in create dataset message would not parse", 400);
			}
			$dataset_title = $dataset_obj['title'];
				
			$cobj = $dwas->createNewDataset($_POST['id'], $c_id, $dataset_title, $dataset_obj);	
			if($cobj){
				echo json_encode($cobj);
			}	
		}
		else {
			$ctitle = $_POST['title'];
			$collection_obj = json_decode($_POST['payload'], true);
			if(!$collection_obj){
				return $dwas->write_error("Payload in create dataset message would not parse", 400);
			}
			$collection_obj['title'] = $ctitle;
			$collection_obj['id'] = $c_id;
			$cobj = $dwas->updateCollection($c_id, $ctitle, $collection_obj);	
			if($cobj){
				echo json_encode($cobj);
			}	
		}
	}
	else {
		//it is an update to a dataset...
		$dtitle = $_POST['title'];
		$dataset_obj = json_decode($_POST['payload'], true);
		if(!$dataset_obj){
			return $dwas->write_error("Payload in create dataset message would not parse", 400);
		}
		$dataset_obj['title'] = $dtitle;
		$dataset_obj['id'] = $d_id;
		$cobj = $dwas->updateDataset($d_id, $dtitle, $dataset_obj);	
		if($cobj){
			echo json_encode($cobj);
		}	
	}
}


function delete(){
	global $service;
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();	
	$dwas = new ConfigDacuraAjaxServer($service);
	if($d_id){
		$collobj = $dwas->deleteDataset($d_id);
	}
	else {
		$collobj = $dwas->deleteCollection($c_id);
	}
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}
