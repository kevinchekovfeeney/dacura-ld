<?php
//getRoute()->post('/', 'create');
getRoute()->get('/', 'listing');
getRoute()->get('/(\w+)/roleoptions', 'roleoptions');
getRoute()->get('/(\w+)', 'view');
getRoute()->post('/', 'create');
getRoute()->post('/(\w+)', 'update');
getRoute()->delete('/(\w+)', 'delete');
getRoute()->get('/(\w+)/role/(\w+)', 'viewrole');
getRoute()->post('/(\w+)/role', 'createrole');
getRoute()->delete('/(\w+)/role/(\w+)', 'deleterole');

include_once("UsersDacuraServer.php");

function view($id){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service);
	$collobj = $dwas->getUser($id);
	if($collobj){
		$cid = $service->getCollectionID();
		$did = $service->getDatasetID();
		foreach($collobj->roles as $i => $r){
			if($cid && $r->collectionID() && ($r->collectionID() != $cid)){
				unset($collobj->roles[$i]);
			}
			elseif($did && $r->datasetID() && ($r->datasetID() != $did)){
				unset($collobj->roles[$i]);
			}
		}
		echo json_encode($collobj);	
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function roleoptions($uid){
	//need to return what options are avaiable in: 
	//collection_id
	//dataset_id (connected to above)
	//role
	//depending on both context and uid...
	global $service;
	$dwas = new UsersDacuraAjaxServer($service->settings);
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	$collobj = $dwas->getRoleContextOptions($uid, $c_id, $d_id);
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function viewrole($uid, $rid){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service->settings);
	$x = $dwas->getUserRole($uid, $rid);
	if($x){
		echo json_encode($x);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function listing(){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service->settings);
	$c_id = $service->getCollectionID();
	$d_id = $service->getDatasetID();
	$collobj = $dwas->getUsersInContext($c_id, $d_id);
	if($collobj){
		echo json_encode($collobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function create(){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service->settings);
	$uobj = $dwas->sm->addUser($_POST['email'], $_POST['name'],  $_POST["password"], $_POST['status'], $_POST['profile']);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function createrole($uid){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service->settings);
	$role_obj = json_decode($_POST['payload'], true);
	$uobj = $dwas->createUserRole($uid, $role_obj["collection"], $role_obj["dataset"], $role_obj["role"], $role_obj["level"]);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function update($id){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service);
	if(!isset($_POST['email']) || !isset($_POST['name']) || !isset($_POST['profile']) || !isset($_POST['status'])){
		return $dwas->write_error("Missing required field for update user $id", 400);
	}
	$user_obj = new DacuraUser($id, $_POST['email'], $_POST['name'], $_POST['status'], json_decode($_POST['profile']));
	if(isset($_POST['password']) && $_POST['password']){
		if(!$dwas->dbman->updatePassword($id, $_POST['password'])){
			return $dwas->write_error("Failed to update password for user $id: ".$dwas->dbman->errmsg, 400);
		}
	}
	$uobj = $dwas->updateUser($user_obj);
	if($uobj){
		echo json_encode($uobj);
	}
}


function delete($id){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service);
	$uobj = $dwas->deleteUser($id);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}

function deleterole($uid, $rid){
	global $service;
	$dwas = new UsersDacuraAjaxServer($service);
	$uobj = $dwas->deleteUserRole($uid, $rid);
	if($uobj){
		echo json_encode($uobj);
	}
	else $dwas->write_error($dwas->errmsg, $dwas->errcode);
}
