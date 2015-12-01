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
 * God -> can do everything....
 * Dacura admin -> can do everything
 * Collection admin -> can do everything related to their admin-ed collections
 * Otherwise users can view and update themselves, nothing else. 
 */

if($dacura_server->userHasRole("admin", false, "all")){
	getRoute()->get('/', 'listUsers');
	getRoute()->post('/', 'createUser');	
	getRoute()->post('/invite', 'inviteUsers');
}
//we need to load stuff to check permissions for the below - done in the functions below
getRoute()->get('/(\w+)', 'viewUser');
getRoute()->post('/(\w+)', 'updateUser');
getRoute()->post('/(\w+)/password', 'updateUserPassword');
getRoute()->delete('/(\w+)', 'deleteUser');
getRoute()->post('/(\w+)/role', 'createRole');
getRoute()->delete('/(\w+)/role/(\w+)', 'deleteRole');

/*
 * Changes the current user to the user with id $id
 * Just for testing different users - not for production!!!
 */
getRoute()->get('/load/(\w+)', 'switchUser'); //must be turned off - only for testing
function switchUser($id){
	global $dacura_server;
	$dacura_server->write_json_result($dacura_server->userman->switchToUser($id), "Switched to user $id");
}

function listUsers(){
	global $dacura_server;
	$dacura_server->init("listusers");
	$collobj = $dacura_server->getUsersInContext();
	if($collobj){
		$dacura_server->write_json_result(array_values($collobj), "Retrieved user listing for ".$dacura_server->contextStr());
	}
	else {
		$dacura_server->write_http_error();
	}
}

function viewUser($id){
	global $dacura_server;
	$dacura_server->init("getuser", $id);
	$object_user = $dacura_server->getUserPrunedForContext($id);
	if($object_user && $dacura_server->canUpdateUserB($object_user)){ //only those users who can update another user can view them
		return $dacura_server->write_json_result($object_user, "Viewing user $id");
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
	$json = json_decode(file_get_contents('php://input'), true);
	if(!$json || !isset($json['email']) or !$json['email'] or !isset($json['password']) or !$json['password']){
		return $dacura_server->write_http_error(400, "Missing parameters: new users must have password and email");
	}
	if(isset($json['role']) && !isset($json['roles'])){
		$json['roles'] = array(array("collection_id" => $dacura_server->cid(), "dataset_id" => $dacura_server->did(), "role" => $json['role'], "level" => 0));
	}
	$uobj = $dacura_server->addUser($json);
	if($uobj){
		return $dacura_server->write_json_result($uobj, "User $uobj->id has been created");
	}
	return $dacura_server->write_http_error();
}

function updateUser($id){
	global $dacura_server;
	$dacura_server->init("updateuser", $id);
	$json = json_decode(file_get_contents('php://input'), true);
	if(!$json){
		return $dacura_server->write_http_error(400, "failed to read user object from input");
	}
	$object = $dacura_server->getUser($id);
	if($object){
		$changes = array();
		if(isset($json['email'])) $object->email = $json['email'];
		if(isset($json['name'])) $object->name = $json['name'];
		if(isset($json['status'])) $object->status = $json['status'];
		if(isset($json['profile'])) $object->profile = $json['profile'];
		if($dacura_server->canUpdateUserB($object)){
			if($dacura_server->updateUser($object)){
				return $dacura_server->write_json_result($object, "User $id has been updated");
			}
		}
	}
	$dacura_server->write_http_error();		
}

function inviteUsers(){
	global $dacura_server;
	$json = json_decode(file_get_contents('php://input'), true);
	if(!$json){
		return $dacura_server->write_http_error(400, "failed to read invitation json from input");
	}
	if(!isset($json['emails']) || !$json['emails']){
		return $dacura_server->write_http_error(400, "No emails specified for invitation");		
	}
	if(!isset($json['role']) || !$json['role']){
		return $dacura_server->write_http_error(400, "No role specified in invitation");
	}
	if(!isset($json['message']) || !$json['message']){
		return $dacura_server->write_http_error(400, "No message specified in invitation");
	}
	$invite_list = $dacura_server->parseInviteList($json['emails'], $json['role']);
	if($dacura_server->inviteListContainsValidEntries($invite_list)){
		$invite_report = $dacura_server->processInviteList($invite_list, $json['role'], $json['message']);
		return $dacura_server->write_json_result($invite_report, "issued ".count($invite_report['issued'])." invitations, ".count($invite_report['failed'])." failures");		
	}
	else {
		$invite_report = $dacura_server->getInviteErrorReport($invite_list);
		$dacura_server->write_json_error($invite_report, 400, "Invitations: all ".count($invite_report['failed'])." failed");
	}
}

function updateUserPassword($id){
	global $dacura_server;
	$dacura_server->init("updatepassword", $id);
	$json = json_decode(file_get_contents('php://input'), true);
	if(!$json){
		return $dacura_server->write_http_error(400, "failed to read password update json object from input");
	}
	$uobj = $dacura_server->getUser($id);
	if($dacura_server->canUpdateUserB($uobj)){
		if($dacura_server->userman->updatePassword($id, $json['password'])){
			return $dacura_server->write_json_result("OK", "User $id password updated");				
		}
		else {
			return $dacura_server->write_http_error($dacura_server->userman->errcode, $dacura_server->userman->errmsg);	
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
	$role_obj = json_decode(file_get_contents('php://input'), true);
	if(!isset($role_obj['dataset'])){
		$role_obj['dataset'] = "all";
	}
	if(!$role_obj){
		return $dacura_server->write_http_error(400, "Bad parameters: could not decipher json object for new role");
	}
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
	return $dacura_server->write_http_error();
}	


