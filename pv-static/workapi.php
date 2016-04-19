<?php

include_once("phplib/SessionManager.php");
include_once("phplib/DacuraWidgetAjaxServer.php");
include_once("settings.php");
/**
 *  Mailine of logic
*/
//$sm = new SessionManager();
$action = isset($_POST['action']) ? $_POST['action'] : false;
$dwas = new DacuraWidgetAjaxServer($dacura_settings);
if($action == 'login'){
	if(isset($_POST['login-email']) && isset($_POST['login-password'])){
		echo $dwas->login($_POST['login-email'], $_POST['login-password']);
	}
	else {
		$dwas->write_error("Missing required login fields.");
	}
}
elseif($action == 'adduser'){
	if(isset($_POST['adduser-email']) && isset($_POST['adduser-password'])){
		echo $dwas->adduser($_POST['adduser-email'], $_POST['adduser-password']);
	}
	else {
		$dwas->write_error("Missing required user fields.");
	}
}
elseif($action == 'allocate'){
	if(isset($_POST['allocate-email']) && isset($_POST['allocate-year'])){
		echo $dwas->allocate($_POST['allocate-email'], $_POST['allocate-year']);
	}
	else {
		$dwas->write_error("Missing required allocate fields.");
	}
}
else {
	$dwas->write_error("$action is not a valid action");
}