<?php
/** 
 * API for login service - supports lost password and registration interface too
 *
 * * Creation Date: 12/01/2015
 * @package login/api
 * @author Chekov
 * @license GPL v2
 */
getRoute()->get("/", "hello");
getRoute()->post('/', 'login');
getRoute()->post('/register', 'register');
getRoute()->post('/lost', 'lost');
getRoute()->post('/reset', 'resetpassword');
getRoute()->delete('/', 'logout');

header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 1728000");
header('Access-Control-Allow-Headers: Accept, Accept-Encoding, Accept-Language, Host, Origin, Referer, Content-Type, Content-Length, Content-Range, Content-Disposition, Content-Description');
if(isset($_SERVER['HTTP_ORIGIN'])){
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}
else {
	header("Access-Control-Allow-Origin: *");
}


/**
 * Login API
 * 
 * POST login/
 * Requires $_POST['login-email'] and $_POST['login-password']
 * @api
 */
function login(){
	global $dacura_server;
	$dacura_server->init("login");
	$u = $dacura_server->getUser();
	if($u){
		return $dacura_server->write_http_result(400, "User is logged in - cannot log in again", "notice");
	}
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		$u = $dacura_server->login($_POST['login-email'], $_POST['login-password']);
		if($u) {
			$dacura_server->write_json_result($dacura_server->durl(), "Login Successful");
		}
		else {
			$dacura_server->write_http_result(false, false, "notice");
		}
	}
	else {
		$dacura_server->write_http_result(400, "Missing required login fields.", "notice");
	}
}

/**
 * Register API
 * 
 * POST login/register
 * Requires $_POST['login-email'] and $_POST['login-password']
 * @api
 */
function register(){
	global $dacura_server;
	$dacura_server->init("register");
	$u = $dacura_server->getUser(0);
	if($u){
		return $dacura_server->write_http_result(401, "User is logged in - cannot register", "notice");
	}
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		$u = $dacura_server->register($_POST['login-email'], $_POST['login-password']);
		if($u) $dacura_server->write_json_result($u, "Registration Successful");
		else $dacura_server->write_http_result(false, false, "notice");
	}
	else {
		$dacura_server->write_http_result(400, "Missing required registration fields.", "notice");
	}
}

/**
 * Lost Password API
 *
 * POST login/lost
 * Requires $_POST['login-email']
 * @api
 */
function lost(){
	global $dacura_server;
	$dacura_server->init("lost");
	$u = $dacura_server->getUser(0);
	if($u){
		return $dacura_server->write_http_result(401, "User is logged in -cannot start lost password process", "notice");
	}
	if(isset($_POST['login-email'])){
		$u = $dacura_server->lostpassword($_POST['login-email']);
		if($u) $dacura_server->write_json_result($u, "Lost Password Process Initiated");
		else $dacura_server->write_http_result(false, false, "notice");
	}
	else {
		$dacura_server->write_http_result(400, "Missing required email fields.", "notice");
	}
}

/**
 * Reset Password API
 * 
 * This is called when a user updates their password after following the link...
 *
 * POST login/reset
 * Requires $_POST['login-password'] && $_POST['userid'] && $_POST['action']
 * @api
 */
function resetpassword(){
	global $dacura_server;
	$dacura_server->init("resetpassword");
	$u = $dacura_server->getUser(0);
	if($u){
		return $dacura_server->write_http_result(401, "User is logged in - cannot reset password", "notice");
	}
	if(isset($_POST['userid']) &&  isset($_POST['login-password']) && isset($_POST['action'])){
		$u = $dacura_server->resetPassword($_POST['userid'], $_POST['login-password'], $_POST['action']);
		if($u) $dacura_server->write_json_result($u, "Password Reset Successfully");
		else $dacura_server->write_http_result(false, false, "notice");
	}
	else {
		$dacura_server->write_http_result(400, "Missing required fields.",  "notice");
	}
}

/**
 * Logout API
 *
 * DELETE login/
 * @api
 */
function logout(){
	global $dacura_server;
	$dacura_server->init("logout");
	$u = $dacura_server->getUser();
	if(!$u){
		return $dacura_server->write_http_result(401, "User is not logged in - cannot log out", "notice");
	}
	$dacura_server->write_json_result($dacura_server->logout(), "Logged out successfully");
}

/**
 * Hello API
 * 
 * GET login/hello
 * Just says hello to let us know the server is there
 * @api
 */
function hello(){
	global $dacura_server;
	$dacura_server->init("hello");
	$dacura_server->write_json_result("Hello World. This is Dacura speaking.", "Hello world");
}
