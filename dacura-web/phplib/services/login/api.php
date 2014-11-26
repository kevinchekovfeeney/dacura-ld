<?php
getRoute()->get("/", "hello");
getRoute()->post('/', 'login');
getRoute()->post('/register', 'register');
getRoute()->post('/lost', 'lost');
getRoute()->post('/reset', 'resetpassword');
getRoute()->delete('/', 'logout');

include_once("LoginDacuraServer.php");


function hello(){
	echo "Hello world";
}

function login(){
	global $service;
	$dwas = new LoginDacuraAjaxServer($service);
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		$u = $dwas->login($_POST['login-email'], $_POST['login-password']);
		if($u) {
			if(isset($u->profile['dacurahome']) && $u->profile['dacurahome']){
				echo $u->profile['dacurahome'];				
			}
			echo json_encode($u);
		}
	}
	else {
		$dwas->write_error("Missing required login fields.", 400);
	}
}

function register(){
	global $service;
	$dwas = new LoginDacuraAjaxServer($service);
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
	global $service;
	$dwas = new LoginDacuraServer($service);
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
	global $service;
	$dwas = new LoginDacuraAjaxServer($service);
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
	global $service;
	$dwas = new LoginDacuraAjaxServer($service);
	$x = $dwas->getUser();
	echo $dwas->logout();
}

function migrate(){
	echo $dwas->migrate();
}
