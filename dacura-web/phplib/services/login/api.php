<?php

/*
 * API for login service - supports lost password and registration interface too
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */


getRoute()->get("/", "hello");
getRoute()->post('/', 'login');
getRoute()->post('/register', 'register');
getRoute()->post('/lost', 'lost');
getRoute()->post('/reset', 'resetpassword');
getRoute()->delete('/', 'logout');


function login(){
	global $dacura_server;
	$dacura_server->init("login");
	$u = $dacura_server->getUser(0);
	if($u){
		return $dacura_server->write_http_result(400, "User is logged in - cannot log in again", "notice");
	}
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		$u = $dacura_server->login($_POST['login-email'], $_POST['login-password']);
		if($u) {
			if(isset($u->profile['dacurahome']) && $u->profile['dacurahome']){
				$dacura_server->write_json_result($u->profile['dacurahome'], "Login Successful");			
			}
			else {
				$dacura_server->write_json_result($dacura_server->settings['install_url'], "Login Successful");
			}
		}
		else {
			$dacura_server->write_http_result(false, false, "notice");
		}
	}
	else {
		$dacura_server->write_http_result(400, "Missing required login fields.", "notice");
	}
}

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


function resetpassword(){
	global $dacura_server;
	$dacura_server->init("resetpassword");
	$u = $dacura_server->getUser(0);
	if($u){
		return $dacura_server->write_http_result(401, "User is logged in - cannot reset password", "notice");
	}
	if(isset($_POST['userid']) &&  isset($_POST['login-password'])){
		$u = $dacura_server->resetpassword($_POST['userid'], $_POST['login-password']);
		if($u) $dacura_server->write_json_result($u, "Password Reset Successfully");
		else $dacura_server->write_http_result(false, false, "notice");
	}
	else {
		$dacura_server->write_http_result(400, "Missing required fields.",  "notice");
	}
}

function logout(){
	global $dacura_server;
	$dacura_server->init("logout");
	$u = $dacura_server->getUser();
	if(!$u){
		return $dacura_server->write_http_result(401, "User is not logged in - cannot log out", "notice");
	}
	$dacura_server->write_json_result($dacura_server->logout(), "Logged out successfully");
}

function hello(){
	global $dacura_server;
	$dacura_server->init("hello");
	$dacura_server->write_json_result("Hello World. This is Dacura speaking.", "Hello world");
}
