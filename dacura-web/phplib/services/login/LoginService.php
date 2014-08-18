<?php

include_once("LoginDacuraServer.php");

class LoginService extends DacuraService {
	
	function handlePageLoad($sc){
		$lds = new LoginDacuraServer($this->settings);
		$dcuser = $lds->getUser(0);
		if($dcuser){
			$params = array("username" => $dcuser->name);
			$this->renderScreen("logout", $params);
		}
		else{
			$sc->screen = $sc->getArg(0);
			if($sc->screen == "" or $sc->screen == 'login'){
				$params = array("active_function" => "login");
				$this->renderScreen("login", $params);
			}
			elseif(($sc->screen == "register" or $sc->screen == 'lost') && count($sc->args) == 0 || !$sc->args[0]){
				$params = array("active_function" => $sc->screen);
				$this->renderScreen("login", $params);				
			}
			elseif($sc->screen == 'register'){
				$code = $sc->args[0];
				$user = $lds->sm->confirmRegistration($code);
				if(!$user){
					$this->renderScreen("error", array("title" => "Error in registration confirmation", "message" => $lds->sm->errmsg));
				}
				else {
					$this->renderScreen("register_success", array("message" => "Good to have you on board ".$user->email));
				}
				
			}
			elseif($sc->screen == 'lost'){
				$code = $sc->args[0];
				$user = $lds->sm->confirmLostPassword($code);
				if(!$user){
					$this->renderScreen("error", array("title" => "Error in password reset attempt", "message" => $lds->sm->errmsg));
				}
				else {
					$this->renderScreen("lost", array("greeting"=>"<strong>Hi $user->email</strong><br>", "userid" => $user->id));
				}			
			}
			else {
				$this->renderScreen("error", array("title" => "Unknown Screen", "message" => "The login service does not have a $sc->screen page"));
			}
		}
	}
	
}