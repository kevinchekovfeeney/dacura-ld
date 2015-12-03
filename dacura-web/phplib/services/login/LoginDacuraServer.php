<?php
/*
 * Login service - supports lost password and registration interface too
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */



include_once("phplib/DacuraServer.php");

class LoginDacuraServer extends DacuraServer {
	
	/**
	 * Login function
	 * @param string $u username
	 * @param string $p password
	 * @return boolean - login successful
	 */
	function login($u, $p){
		$u = $this->userman->login($u, $p);
		return ($u) ? $u : $this->failure_result($this->userman->errmsg, 401);
	}
	
	/**
	 * Registration function
	 * @param string $u username
	 * @param string $p password
	 * @return boolean - login successful
	 */
	function register($u, $p){
		$code = $this->userman->register($u, $p);
		if($code){
			$address =  $this->durl()."login/register/code/".$code;
			$name = $u;
			ob_start();
			include_once("screens/register_email.php");
			$output = ob_get_contents();
			ob_clean();
			$htmloutput = $this->getRegisterUnderwayHTML($name);
			sendemail($u, $this->getServiceSetting('register_email_subject', "Dacura registration"), $output, $this->getSystemSetting('mail_headers',""));
			return $htmloutput;
		}
		else {
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
	}
	
	/*
	 * Password Reseting
	*/
	function lostpassword($u){
		$code = $this->userman->requestResetPassword($u);
		if($code){
			$address =  $this->durl()."login/lost/code/".$code;
			$name = $u;
			ob_start();
			include_once("screens/lost_password_email.php");
			$output = ob_get_contents();
			ob_clean();
			$htmloutput = $this->getLostUnderwayHTML($name);
			sendemail($u, $this->getServiceSetting('lost_email_subject', "Lost password notification"), $output, $this->getSystemSetting('mail_headers',""));
			return $htmloutput;
		}
		else {
			return $this->failure_result("Failed to generate new password: " . $this->userman->errmsg, 401);
		}
	}
	
	function resetpassword($uid, $p){
		if($this->userman->resetPassword($uid, $p)){
			return "Your password has been successfully reset. You may now log into the system.";
		}
		else {
			return $this->failure_result("Failed to reset password: " . $this->userman->errmsg, 401);
		}
	}
	
	function logout(){
		if($this->userman->isLoggedIn()){
			$this->userman->logout();
			return true;
		}
		else {
			return $this->failure_result("Not logged in - Failed to logout", 401);
		}
	}
	
	function getLostUnderwayHTML($name){
		$html ="<p>An email has been sent to <strong>$name</strong>. It contains instructions on how to complete the resetting of your password.</p>
						<p>Complete the steps outlined in the email within the next 24 hours to reset your password.</p>";
		return $html;
	}
	function getRegisterUnderwayHTML($name){
		$html = '<P>An email has been sent to <strong>'.$name.'</strong></P>';
		$html .= '<P>Please follow the instructions in that email to complete your registration. </P>';
		return $html;	
	}
	
}

