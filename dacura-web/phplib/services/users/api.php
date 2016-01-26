<?php
/**
 * API for users service - viewing and updating user details
 *
 * Creation Date: 12/01/2015
 * @author chekov
 * @package users/api
 * @license GPL v2
 */

if($dacura_server->userHasFacet("list")){
	getRoute()->get('/', 'listUsers');
}
if($dacura_server->userHasFacet("admin")){
	getRoute()->post('/', 'createUser');	
	getRoute()->post('/invite', 'inviteUsers');
	getRoute()->delete('/(\w+)', 'deleteUser');
	getRoute()->delete('/(\w+)/role/(\w+)', 'deleteRole');
	getRoute()->post('/(\w+)/role', 'createRole');
	getRoute()->post('/(\w+)', 'updateUser');
	getRoute()->post('/(\w+)/password', 'updateUserPassword');
}
if($dacura_server->userHasFacet("inspect")){
	getRoute()->get('/(\w+)/history', 'viewUserHistory');
}
//we need to load stuff to check permissions for the below - done in the functions below

if($dacura_server->userHasFacet("view")){
	getRoute()->get('/(\w+)', 'viewUser');
}
getRoute()->get('/load/(\w+)', 'switchUser'); //must be turned off - only for testing

/**
 * GET /load/$userid
 * 
 * Changes the current user to the user with id $userid
 * Just for testing different users - not for production!!!
 * @param $id the user id
 * @api
 */
function switchUser($id){
	global $dacura_server;
	$dacura_server->write_json_result($dacura_server->userman->switchToUser($id), "Switched to user $id");
}

/**
 * GET /
 * 
 * Returns an array of all the users in the context
 * @api
 */
function listUsers(){
	global $dacura_server;
	$dacura_server->init("list_users");
	$collobj = $dacura_server->getUsersInContext();
	if($collobj){
		$dacura_server->write_json_result(array_values($collobj), "Retrieved user listing for ".$dacura_server->contextStr());
	}
	else {
		$dacura_server->write_http_error();
	}
}


/**
 * Invite a list of users to join the collection
 * @api
 * POST /invite
 *
 * requires $json[emails] && json[role] && json[message]
 *
 * @return a parse invitation result report (issued:<array>, failed: array<>)
 */
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


/**
 * Create a new user object
 * 
 * POST /
 * 
 * requires $json[email] && json[password]
 * optional $json[roles], $json[status], $json[name], $json[profile]
 * 
 * Creates a new user object from the json object passed
 * Returns the new user object if it was successfully created
 * 
 * @api
 */
function createUser(){
	global $dacura_server;
	$dacura_server->init("create_user");
	$json = json_decode(file_get_contents('php://input'), true);
	if(!$json || !isset($json['email']) or !$json['email'] or !isset($json['password']) or !$json['password']){
		return $dacura_server->write_http_error(400, "Missing parameters: new users must have password and email");
	}
	if(isset($json['role']) && !isset($json['roles'])){
		$json['roles'] = array(array("collection_id" => $dacura_server->cid(),"role" => $json['role']));
	}
	if($dacura_server->canAddUser($json) && ($uobj = $dacura_server->addUser($json))){
		return $dacura_server->write_json_result($uobj, "User $uobj->id has been created");
	}
	return $dacura_server->write_http_error();
}

/**
 * Fetch a user object
 * GET /$id
 * @param string $id the id of the user to be viewed
 * @return JSON DacuraUser object
 * @api
 */
function viewUser($id){
	global $dacura_server;
	$dacura_server->init("get_user", $id);
	if($object_user = $dacura_server->getUserPrunedForContext($id)){
		return $dacura_server->write_json_result($object_user, "Viewing user $id");
	}
	else {
		$dacura_server->write_http_error();
	}
}

/**
 * Updates a user object
 * 
 * POST /$id
 * 
 * optional $json[email], $json[status], $json[name], $json[profile]
 * @param string $id the id of the user
 * @return JSON DacuraUser object
 * @api
 */
function updateUser($id){
	global $dacura_server;
	$dacura_server->init("update_user", $id);
	if(!($json = json_decode(file_get_contents('php://input'), true))){
		return $dacura_server->write_http_error(400, "failed to read user object from input");
	}
	if(($object = $dacura_server->getUser($id)) && $dacura_server->canUpdateUser($object, $json)){
		$nobj = $dacura_server->updateUser($object, $json);
		if($nobj) return $dacura_server->write_json_result($nobj, "User $id has been updated");
	}
	$dacura_server->write_http_error();		
}

/**
 * Deletes the user from the system
 * 
 * @api
 * DELETE /$id
 * @param string $id
 * @return true if successeful
 * @api
*/
function deleteUser($id){
	global $dacura_server;
	$dacura_server->init("delete_user", $id);
	if(!($u = $dacura_server->getUser($id))){
		return $dacura_server->write_http_error();
	}
	if($dacura_server->canDeleteUser($u) && ($dacura_server->deleteUser($u))){
		return $dacura_server->write_json_result(true, "User $id has been deleted");
	}
	$dacura_server->write_http_error();
}

/**
 * Updates the password of a user
 * POST /$id/password
 * 
 * requires $json[password] 
 * 
 * @param string $id the id of the user to be updated
 * @return "OK" on success
 * @api
 */
function updateUserPassword($id){
	global $dacura_server;
	$dacura_server->init("updatepassword", $id);
	$json = json_decode(file_get_contents('php://input'), true);
	if(!$json || !isset($json['password'])){
		return $dacura_server->write_http_error(400, "failed to read password update json object from input");
	}
	if(!($uobj = $dacura_server->getUser($id))){
		return $dacura_server->write_http_error();
	}
	if($dacura_server->canUpdatePassword($uobj) && $dacura_server->updatePassword($uobj, $json['password'])){
		return $dacura_server->write_json_result("OK", "User $id password updated");				
	}
	return $dacura_server->write_http_error();
}

/**
 * Creates a role in a user object
 *
 * @api
 * POST /$uid/role
 *
 * requires $json[collection] && json[role]
 *
 * @param string $uid a user id
 * @return a json dacura user object
 */
function createRole($uid){
	global $dacura_server;
	$dacura_server->init("createrole", $uid);
	$role_obj = json_decode(file_get_contents('php://input'), true);
	if(!$role_obj){
		return $dacura_server->write_http_error(400, "Bad parameters: could not decipher json object for new role");
	}
	if(($uobj = $dacura_server->getUser($uid)) &&  $dacura_server->canCreateRole($uobj, $role_obj["collection"], $role_obj["role"])){
		$nobj = $dacura_server->createRole($uobj, $role_obj["collection"], $role_obj["role"]);
		if($nobj) return $dacura_server->write_json_result($nobj, "Role has been added to user $uid");
	}
	return $dacura_server->write_http_error();
}

/**
 * Deletes a role from a user
 * 
 * @api
 * DELETE /$uid/role/$rid
 * @param string $uid the user id
 * @param string $rid the role id
 * @return the json dacura user object of the updated user
 */
function deleteRole($uid, $rid){
	global $dacura_server;
	$dacura_server->init("deleterole", "$rid");
	if(!(($uobj = $dacura_server->getUser($uid)) && ($role = $uobj->getRole($rid)))){
		return $dacura_server->write_http_error();
	}
	if($dacura_server->canDeleteRole($uobj, $role)){
		$nobj = $dacura_server->deleteRole($uobj, $role);
		if($nobj) return $dacura_server->write_json_result($nobj, "Role $rid has been removed from user $uid");
	}
	return $dacura_server->write_http_error();		
}

/**
 * View a users historical sessions
 * 
 * @api
 * GET /$id/history
 * @param string $uid a user id
 * @return an array of json session objects
 */
function viewUserHistory($id){
	global $dacura_server;
	$dacura_server->init("userhistory", $id);
	$uobj = $dacura_server->getUser($id);
	if($dacura_server->canViewUserHistory($uobj)){
		$user_history = $dacura_server->getUserHistory($uobj);
		if(is_array($user_history)){
			return $dacura_server->write_json_result($user_history, "Viewing user history $id");
		}
	}
	$dacura_server->write_http_error();
}

