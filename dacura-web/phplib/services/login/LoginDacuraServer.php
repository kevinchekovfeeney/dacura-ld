<?php
/** 
 * Login server 
 * 
 * supports lost password and registration interface too
 * @package login
 * @author Chekov
 * @license GPL v2
 */

class LoginDacuraServer extends DacuraServer {
	
	/**
	 * Login function
	 * @param string $u user email
	 * @param string $p password
	 * @return DacuraUser|boolean - if login successful returns user object
	 */
	function login($email, $p){
		$u = $this->userman->login($email, $p, $this->cid());
		return ($u) ? $u : $this->failure_result($this->userman->errmsg, 401);
	}
	
	/**
	 * Registration function
	 * 
	 * Creates a new user account on the system and generates a confirm email
	 * @param string $email email 
	 * @param string $p password
	 * @return boolean - registration successfully initiated
	 */
	function register($email, $p){
		$code = $this->userman->register($email, $p, $this->cid());
		if($code){
			$address =  $this->durl()."login/register/code/".$code;
			ob_start();
			include_once("screens/register_email.php");
			$output = ob_get_contents();
			ob_clean();
			$htmloutput = $this->getRegisterUnderwayHTML($email);
			sendemail($email, $this->getServiceSetting('register_email_subject', "Dacura registration"), $output, $this->getSystemSetting('mail_headers',""));
			return $htmloutput;
		}
		else {
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
	}
	
	/**
	 * Lost Password Functionality
	 * 
	 * Generates an email with a link in it which allows the user to change their password
	 * @param string $email user email
	 * @return string|boolean - html string with message for user or false on failure
	 */
	function lostpassword($email){
		$code = $this->userman->requestResetPassword($email);
		if($code){
			$address =  $this->durl()."login/lost/code/".$code;
			ob_start();
			include_once("screens/lost_password_email.php");
			$output = ob_get_contents();
			ob_clean();
			$htmloutput = $this->getLostUnderwayHTML($email);
			sendemail($email, $this->getServiceSetting('lost_email_subject', "Lost password notification"), $output, $this->getSystemSetting('mail_headers',""));
			return $htmloutput;
		}
		else {
			return $this->failure_result("Failed to generate new password: " . $this->userman->errmsg, 401);
		}
	}
	
	/**
	 * Reset Password function 
	 * 
	 * Resets the user's password when they fill in the form after following the confirm link
	 * @param number $uid user id
	 * @param string $p password
	 * @param string $action the user-action that triggered the reset: lost|invite
	 * @return string|boolean confirmation string on success, false on failure
	 */
	function resetpassword($uid, $p, $action){
		if($this->userman->resetPassword($uid, $p, $action)){
			return "Your password has been successfully reset. You may now log into the system.";
		}
		else {
			return $this->failure_result("Failed to reset password: " . $this->userman->errmsg, 401);
		}
	}
	
	/**
	 * Logout of the system
	 * @return boolean true on success
	 */
	function logout(){
		if($this->userman->isLoggedIn()){
			$this->userman->logout();
			return true;
		}
		else {
			return $this->failure_result("Not logged in - Failed to logout", 401);
		}
	}
	
	/**
	 * Get html to communicate instructions to user upon lost password
	 * @param string $email
	 * @return string html string
	 */
	private function getLostUnderwayHTML($email){
		$html ="<p>An email has been sent to <strong>$email</strong>. It contains instructions on how to complete the resetting of your password.</p>
						<p>Complete the steps outlined in the email within the next 24 hours to reset your password.</p>";
		return $html;
	}
	
	/**
	 * Get html to communicate instructions to user upon registration
	 * @param string $email
	 * @return string html string
	 */	
	private function getRegisterUnderwayHTML($email){
		$html = '<P>An email has been sent to <strong>'.$email.'</strong></P>';
		$html .= '<P>Please follow the instructions in that email to complete your registration. </P>';
		return $html;	
	}	
}

