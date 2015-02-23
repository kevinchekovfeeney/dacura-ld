<?php

include_once("LoginDacuraServer.php");

class LoginService extends DacuraService {

	var $default_screen = "login";
	var $public_screens = array("login", "home", "register", "lost");
	var $protected_screens = array("logout" => array("nobody", "any", "any"));
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.login.js");
		echo "<div id='maincontrol'>";
	}
	
	function renderFullPageFooter(){
		echo "</div>";
		parent::renderFullPageFooter();
	}
	
	function handlePageLoad(){
		$lds = new LoginDacuraServer($this);
		if(!$lds->userman){
			$this->renderScreen("error", array("title" => "Error in Server Creation", "message" => $lds->errmsg));
			return false;	
		}
		$dcuser = $lds->getUser(0);
		if($dcuser){
			$params = array("username" => $dcuser->name, "execute" => false);
			if($this->screen == "logout"){
				$params['execute'] = true;	
			}
			$this->renderScreen("logout", $params);
		}
		else{
			if($this->screen == "home" or $this->screen == "" or $this->screen == 'login'){
				$params = array("active_function" => "login");
				$this->renderScreen("login", $params);
			}
			elseif(($this->screen == "register" or $this->screen == 'lost') && (!isset($this->args["code"]))){
				$params = array("active_function" => $this->screen);
				$this->renderScreen("login", $params);				
			}
			elseif($this->screen == 'register'){
				$code = $this->args["code"];
				$user = $lds->userman->confirmRegistration($code);
				if(!$user){
					$this->renderScreen("error", array("title" => "Error in registration confirmation", "message" => $lds->userman->errmsg));
				}
				else {
					$this->renderScreen("register_success", array("message" => "Good to have you on board ".$user->email));
				}
			}
			elseif($this->screen == 'lost'){
				$code = $this->args["code"];
				$user = $lds->userman->confirmLostPassword($code);
				if(!$user){
					$this->renderScreen("error", array("title" => "Error in password reset attempt", "message" => $lds->userman->errmsg));
				}
				else {
					$this->renderScreen("lost", array("greeting"=>"<strong>Hi $user->email</strong><br>", "userid" => $user->id));
				}			
			}
			else {
				$this->renderScreen("error", array("title" => "Unknown Screen", "message" => "The login service does not have a $this->screen page"));
			}
		}
	}
	
}