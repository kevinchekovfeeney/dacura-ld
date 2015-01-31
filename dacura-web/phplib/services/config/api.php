<?php
/*
 * API for users service - viewing and updating user details
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

//getRoute()->post('/', 'create');
getRoute()->get('/', 'view');
getRoute()->post('/', 'update');
getRoute()->delete('/', 'delete');

function view(){
	global $dacura_server;
	$dacura_server->init("viewconfig");
	if($dacura_server->userHasRole("admin")){
		if($dacura_server->ucontext->getCollectionID() == "all"){
			$collobj = $dacura_server->getCollectionList();				
		}
		elseif($dacura_server->ucontext->getDatasetID() == "all"){
			$collobj = $dacura_server->getCollection($dacura_server->ucontext->getCollectionID());
		}		
		else {
			$collobj = $dacura_server->getDataset($dacura_server->ucontext->getDatasetID());
		}
		if($collobj){
			return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
		}
	}
	$dacura_server->write_http_error();
}


function update(){
	global $dacura_server;
	$dacura_server->init("updateconfig");
	
	if($dacura_server->userHasRole("admin")){
		$c_id = $dacura_server->ucontext->getCollectionID(); 
		$d_id = $dacura_server->ucontext->getDatasetID();
		if($c_id == "all"){
			if(isset($_POST['payload'])&& isset($_POST['id']) && isset($_POST['title'])){
				$collection_obj = json_decode($_POST['payload'], true);
				$collection_id = $_POST['id'];
				$collection_title = $collection_obj['title'];
				if($collection_obj && $collection_id && $collection_title){
					return $dwas->write_error("Payload in create collection message would not parse", 400);
				}
			}	
			else {
				return $this->write_http_error(400, "Missing parameters: new collections must have id, title and contents");
			}
		}
		elseif($dacura_server->ucontext->getDatasetID() == "all"){
			$collobj = $dacura_server->getCollection($dacura_server->ucontext->getCollectionID());
		}		
		else {
			$collobj = $dacura_server->getDataset($dacura_server->ucontext->getDatasetID());
		}
		if($collobj){
			return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
		}
	}
	$dacura_server->write_http_error();
}
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
