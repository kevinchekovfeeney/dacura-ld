<?php
/*
 * API for users service - viewing and updating user details
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

/*
 * Tricky question: who has authority over users and what....
 * God -> can do everything....
 * Collection admin -> can do everything related to admin-ed collections
 * 					-> controls everything to do 
 * User -> 
 */

getRoute()->get('/', 'listUsers');
getRoute()->get('/(\w+)', 'viewUser');
getRoute()->post('/', 'createUser');
getRoute()->post('/(\w+)', 'updateUser');
getRoute()->post('/(\w+)/password', 'updateUserPassword');
getRoute()->delete('/(\w+)', 'deleteUser');
getRoute()->get('/load/(\w+)', 'switchUser'); //must be turned off - only for testing 
getRoute()->get('/(\w+)/role/(\w+)', 'viewRole');
getRoute()->post('/(\w+)/role', 'createRole');
getRoute()->delete('/(\w+)/role/(\w+)', 'deleteRole');

/*
 * Changes the current user to the user with id $id 
 * Just for testing different users - not for production!!!
 */
function switchUser($id){
	global $dacura_server;
	$dacura_server->write_json_result($dacura_server->userman->switchToUser($id), "Switched to user $id");
}

function listUsers(){
	global $dacura_server;
	$dacura_server->init("listusers");
	if($dacura_server->userHasRole("admin", false, "all")){
		$collobj = $dacura_server->getUsersInContext();
		if($collobj){
			echo "poo";
			$dacura_server->write_json_result($collobj, "Retrieved user listing for ".$dacura_server->contextStr());
		}
	}
	else $dacura_server->write_http_error();
}

function viewUser($id){
	global $dacura_server;
	$dacura_server->init("getuser", $id);
	if($dacura_server->userHasRole("admin", false, "all")){
		$object_user = $dacura_server->getUserPrunedForContext($id);
		if($object_user){
			return $dacura_server->write_json_result($object_user, "Viewing user $id");
		}
		else {
			$dacura_server->write_http_error();
		}
	}
	else {
		$dacura_server->write_http_error();		
	}
}

function deleteUser($id){
	global $dacura_server;
	$dacura_server->init("deleteuser", $id);
	$u = $dacura_server->getUser();
	if($dacura_server->userHasRole("admin", false, "all") || $u->id == $id){
		if($dacura_server->deleteUser($id)){
			$dacura_server->write_json_result(true, "User $id has been deleted");
		}
		else {
			$dacura_server->write_http_error();
		}
	}
	else {
		$dacura_server->write_http_error();		
	}
}

function createUser(){
	global $dacura_server;
	$dacura_server->init("createuser");
	if(!$dacura_server->userHasRole("admin", false, "all")){
		return $dacura_server->write_http_error();
	}
	if(!isset($_POST['email']) or !$_POST['email'] or !isset($_POST['password']) or !$_POST['password']){
		return $this->write_http_error(400, "Missing parameters: new users must have password and email");
	}
	$init_params = array("email" => $_POST['email'], "password" => $_POST['password']);
	if(isset($_POST['name'])) $init_params['name'] = $_POST['name'];
	if(isset($_POST['status'])) $init_params['status'] = $_POST['status'];
	if(isset($_POST['profile'])){
		$x = json_decode($_POST['profile']);
		if($x) $init_params['profile'] = $x;
	}
	if(isset($_POST['roles'])){
		$x = json_decode($_POST['roles']);
		if($x) $init_params['roles'] = $x;
	}
	$uobj = $dacura_server->addUser($init_params);
	if($uobj){
		return $dacura_server->write_json_result($uobj, "User $uobj->id has been created");
	}
	return $dacura_server->write_http_error();
}

function updateUser($id){
	global $dacura_server;
	$dacura_server->init("updateuser", $id);
	if(!$dacura_server->userHasRole("admin", false, "all")){
		return $dacura_server->write_http_error();
	}
	$object = $dacura_server->getUser($id);
	$changes = array();
	if(isset($_POST['email'])) $object->email = $_POST['email'];
	if(isset($_POST['name'])) $object->name = $_POST['name'];
	if(isset($_POST['status'])) $object->status = $_POST['status'];
	if(isset($_POST['profile'])) $object->profile = json_decode($_POST['profile'], true);
	if($dacura_server->canUpdateUserB($object)){
		if($dacura_server->updateUser($object)){
			$dacura_server->write_json_result($object, "User $id has been updated");
		}
		else {
			$dacura_server->write_http_error();
		}
	}
	else {
		$dacura_server->write_http_error();		
	}
}

function updateUserPassword($id){
	global $dacura_server;
	$dacura_server->init("updatepassword", $id);
	if(!isset($_POST['password']) || !$_POST['password']){
		return 	$dacura_server->write_http_error();
	}
	$uobj = $dacura_server->getUser($id);
	if($dacura_server->canUpdateUserB($uobj)){
		if($dacura_server->userman->updatePassword($id, $_POST['password'])){
			return $dacura_server->write_json_result("OK", "User $id password updated");				
		}
		else {
			return $dacura_server->write_http_error($dacura_server->userman->errmsg, $dacura_server->userman->errcode);	
		}	
	}	
	return $dacura_server->write_http_error();
}


function deleteRole($uid, $rid){
	global $dacura_server;
	$dacura_server->init("deleterole", "$rid");
	$uobj = $dacura_server->getUser($uid);
	if(!$uobj){
		return $dacura_server->write_http_error();
	}
	$role = $uobj->getRole($rid);
	if(!$role){
		return $dacura_server->write_http_error();		
	}
	
	if(!$dacura_server->userHasRole("admin", $role->collection_id, $role->dataset_id)){
		return $dacura_server->write_http_error();
	}
	$uobj = $dacura_server->userman->deleteUserRole($uid, $rid);
	if($uobj){
		return $dacura_server->write_json_result($uobj, "Role $rid has been removed from user $uid");
	}
	return $dacura_server->write_http_error();		
}

function createRole($uid){
	global $dacura_server;
	$dacura_server->init("createrole", $uid);
	$role_obj = json_decode($_POST['payload'], true);
	if($role_obj){
		if(!$dacura_server->userHasRole("admin", $role_obj["collection"], $role_obj["dataset"])){
			return $dacura_server->write_http_error();
		}
		
		$uobj = $dacura_server->userman->createUserRole($uid, $role_obj["collection"], $role_obj["dataset"], $role_obj["role"], $role_obj["level"]);
		if($uobj){
			return $dacura_server->write_json_result($uobj, "Role has been added to user $uid");
		}
		else {
			return $dacura_server->write_http_error($dacura_server->userman->errmsg, $dacura_server->userman->errcode);
		}
	}	
	return $dacura_server->write_http_error();
}	

