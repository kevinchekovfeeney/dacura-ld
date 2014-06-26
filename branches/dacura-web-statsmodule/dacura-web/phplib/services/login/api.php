<?php
getRoute()->get("/hello", "hello");
getRoute()->post('/', 'login');
getRoute()->post('/register', 'register');
getRoute()->post('/lost', 'lost');
getRoute()->post('/reset', 'resetpassword');
getRoute()->delete('/', 'logout');

include_once("LoginDacuraServer.php");


function login(){
	global $dacura_settings;
	$dwas = new LoginDacuraAjaxServer($dacura_settings);
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		$u = $dwas->login($_POST['login-email'], $_POST['login-password']);
		if($u) echo "OK";
	}
	else {
		$dwas->write_error("Missing required login fields.", 400);
	}
}

function register(){
	global $dacura_settings;
	$dwas = new LoginDacuraAjaxServer($dacura_settings);
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		$u = $dwas->register($_POST['login-email'], $_POST['login-password']);
		if($u) echo $u;
		else $dwas->write_error($dwas->errmsg, $dwas->errcode);
	}
	else {
		$dwas->write_error("Missing required registration fields.");
	}
}

function lost(){
	global $dacura_settings;
	$dwas = new LoginDacuraServer($dacura_settings);
	if(isset($_POST['login-email'])){
		$u = $dwas->lostpassword($_POST['login-email']);
		if($u) echo $u;
		else $dwas->write_error($dwas->errmsg, $dwas->errcode);
	}
	else {
		$dwas->write_error("Missing required email fields.");
	}
}

function resetpassword(){
	global $dacura_settings;
	$dwas = new LoginDacuraAjaxServer($dacura_settings);
	if(isset($_POST['userid']) &&  isset($_POST['login-password'])){
		$u = $dwas->resetpassword($_POST['userid'], $_POST['login-password']);
		if($u) echo $u;
		else $dwas->write_error($dwas->errmsg, $dwas->errcode);
	}
	else {
		$dwas->write_error("Missing required email fields.");
	}
}
function logout(){
	global $dacura_settings;
	$dwas = new LoginDacuraAjaxServer($dacura_settings);
	$x = $dwas->sm->getUser();
	echo $dwas->logout();
}

function migrate(){
	echo $dwas->migrate();
}
