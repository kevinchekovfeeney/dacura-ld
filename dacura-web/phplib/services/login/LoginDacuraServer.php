<?php
include_once("phplib/DacuraServer.php");

class LoginDacuraServer extends DacuraServer {
	
	function login($u, $p){
		$u = $this->sm->login($u, $p);
		return ($u) ? $u : $this->failure_result($this->sm->errmsg, 401);
	}
	
	
	function register($u, $p){
		$code = $this->sm->register($u, $p);
		if($code){
			$address =  $this->settings['install_url']."login/register/".$code;
			$name = $u;
			ob_start();
			include_once("screens/register_email.php");
			$output = ob_get_contents();
			ob_clean();
			$params = array();
			include_once("screens/register_underway.php");
			$htmloutput = ob_get_contents();
			ob_end_clean();
			sendemail($u, $this->settings['register_email_subject'], $output);
			return $htmloutput;
		}
		else {
			return $this->failure_result($this->sm->errmsg, 401);
		}
	}
	
	/*
	 * Password Reseting
	*/
	function lostpassword($u){
		$code = $this->sm->requestResetPassword($u);
		if($code){
			$address =  $this->settings['install_url']."login/lost/".$code;
			$name = $u;
			ob_start();
			include_once("screens/lost_password.php");
			$output = ob_get_contents();
			ob_clean();
			include_once("screens/reset_success.php");
			$htmloutput = ob_get_contents();
			ob_end_clean();
			sendemail($u, $this->settings['lost_email_subject'], $output);
			return $htmloutput;
		}
		else {
			return $this->failure_result("Failed to generate new password: " . $this->sm->errmsg, 401);
		}
	}
	
	function resetpassword($uid, $p){
		if($this->sm->resetPassword($uid, $p)){
			return "Your password has been successfully reset";
		}
		else {
			return $this->failure_result("Failed to reset password: " . $this->sm->errmsg, 401);
		}
	}
	
	function logout(){
		if($this->sm->isLoggedIn()){
			$this->sm->logout();
			return true;
		}
		else {
			return $this->failure_result("Not logged in - Failed to logout", 401);
		}
	}
	
}

class LoginDacuraAjaxServer extends LoginDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}