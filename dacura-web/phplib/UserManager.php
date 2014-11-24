<?php

/*
 * Class providing access to information about users of the Dacura System
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


require_once("DacuraUser.php");

class UserManager {
	
	var $user_dir;
	var $dbman;
	var $errmsg = "";
	
	function __construct($dbman, $settings){
		$this->user_dir = $settings['dacura_sessions'];
		$this->dbman = $dbman;
	}
	
	function saveUser($u){
		if($this->dbman->saveUser($u)){
			return $u;
		}
		$this->errmsg = $this->dbman->errmsg;
		return false;
	}
	
	function updateUserRoles(&$u){
		if($this->dbman->updateUserRoles($u)){
			//$_SESSION['dacurauser'] =& $u;
			return true;
		}
		$this->errmsg = $this->dbman->errmsg;
		return false;
	}

	function refreshCurrentUser(){
		$x = isset($_SESSION['dacurauser']) ? $_SESSION['dacurauser']->id : false;
		if($x){
			$u = $this->loadUser($x);
			if($u) {
				$_SESSION['dacurauser'] =& $u;
				return $u;
			}
			return false;
		}
		$this->errmsg = "Failed to load existing user from session!";
		return false;
	}
	
	function getUser($n = 0){
		if($n === 0) {
			if (isset($_SESSION['dacurauser'])) $u =& $_SESSION['dacurauser'];
			else $u = 0;
			return $u;
		}
		else {
			return $this->loadUser($n);
		}
	}

	function getUsers(){
		$u = $this->dbman->loadUsers();
		if($u){
			return $u;
		}
		else {
			$this->errmsg = $this->dbman->errmsg;
			return false;
		}
	}
	
	function loadUser($id){
		$u = $this->dbman->loadUser($id);
		if($u){
			$u->setSessionDirectory($this->user_dir.$u->id);
			return $u;
		}
		else {
			$this->errmsg = $this->dbman->errmsg;
			return false;		
		}
	}

	function loadUserByEmail($email){
		$u = $this->dbman->loadUserByEmail($email);
		if($u){
			$u->setSessionDirectory($this->user_dir.$u->id);
			return $u;
		}
		else {
			$this->errmsg = $this->dbman->errmsg;
			return false;
		}
	}
	
	
	function addUser($email, $n, $p, $status){
		if(!$email or !$p){
			$this->errmsg = "Attempt to add user with no email and password";
			return false;
		}
		$nu = $this->dbman->addUser($email, $n, $p, $status);
		if($nu){
			$nu->setSessionDirectory($this->user_dir.$nu->id);
			return $nu;
		}
		$this->errmsg = $this->dbman->errmsg;
		return false;
	}
	
	function deleteUser($id){
		$du = $this->loadUser($id);
		if(!$du){
			return false;
		}
		$du->setStatus("deleted");
		$du->recordAction("system", "deleted");
		return $this->saveUser($du);
	}
	
	function login($email, $p){
		if($this->isLoggedIn()){
		 	$this->errmsg = "User is already logged in";
			return false;
		}
		else {
			$u = $this->dbman->testLogin($email, $p);
			if($u){
				$u->setSessionDirectory($this->user_dir.$u->id);
				$_SESSION['dacurauser']=& $u;
				$u->recordAction("system", "login");
				return $u;
			}
			else {
				$this->errmsg = $this->dbman->errmsg;
				return false;
			}
		}
		return false;
	}
	
	function isLoggedIn(){
		return (isset($_SESSION['dacurauser']) && $_SESSION['dacurauser']);
	}
	
	function logout(){
		//this is where lots of the work goes vis a vis session management
		$u = $this->getUser();
		$u->endLiveSessions("logout");
		unset($_SESSION['dacurauser']);
	}
	
	

	function register($email, $p){
		//if the user is unconfirmed, send them a fresh notification...
		$eu = $this->loadUserbyEmail($email);
		if($eu){
			if($eu->status == "unconfirmed"){
				$code = $this->dbman->getUserConfirmCode($eu->id, "register");
				if($code){
					$eu->recordAction("register", "reregister", true);
					return $code;
				}
				else {
					$code = $this->dbman->generateUserConfirmCode($eu->id, "register");
					if($code){
						$eu->recordAction("register", "regenerate_confirm", true);
						return $code;
					}
					else {
						$this->errmsg = $this->dbman->errmsg;
						return false;
					}
				}
			}
			else {
				$this->errmsg = "User with email $email already exists on the system (status: $eu->status)";
				return false;
			}
		}
		else {
			$nu = $this->addUser($email, "", $p, "unconfirmed");
			if($nu){
				$code = $this->dbman->generateUserConfirmCode($nu->id, "register");
				if($code){
					$nu->recordAction("register", "register", true);
					return $code;
				}
				else {
					$this->errmsg = $this->dbman->errmsg;
					return false;
				}
			}
		}
		return false;
	}
	
	function confirmRegistration($code){
		$uid = $this->dbman->getConfirmCodeUid($code, "register");
		if(!$uid){
			$this->errmsg = $this->dbman->errmsg;
			return false;
		}
		//$this->dbman->updateUserState($uid, "new");
		$du = $this->loadUser($uid);
		if(!$du){
			return false;				
		}
		if($du->status != "unconfirmed"){
			$this->errmsg = "This confirmation code is no longer valid";
			return false;				
		}
		$du->setStatus("new");
		$du->recordAction("register", "confirm_register", true);
		if($this->saveUser($du)){
			return $du;	
		}
		$this->errmsg = "Failed to save user $du->email";
		return false;		
	}
	
	function confirmLostPassword($code){
		$uid = $this->dbman->getConfirmCodeUid($code, "lost");
		if(!$uid){
			$this->errmsg = $this->dbman->errmsg;
			return false;
		}
		$du = $this->loadUser($uid);	
		if(!$du){
			return false;
		}
		if($du->status == "unconfirmed" or $du->status == "suspended" or $du->status == "deleted"){
			$this->errmsg = "This confirmation code is no longer valid - $du->email is no longer active";
			return false;
		}
		$du->recordAction("register", "confirm_lost", true);
		return $du;
	}
	
	
	function requestResetPassword($email){
		$u = $this->loadUserByEmail($email);
		if(!$u){
			return false;
		}
		if($u->status == "unconfirmed"){
			$u->recordAction("register", "lost_password_failed", true);
			$this->errmsg = "You must confirm your account before you can reset the password.";
			return false;
		}
		elseif($u->status == "suspended"){
			$u->recordAction("register", "lost_password_failed", true);
			$this->errmsg = "The account $u has been suspended, you cannot reset the password while suspended.";
			return false;
		}
		elseif($u->status == "deleted"){
			$u->recordAction("register", "lost_password_failed", true);
			$this->errmsg = "The account $u has been deleted, you cannot reset the password of a deleted account.";
			return false;
		}
		$u->recordAction("register", "lost_password", true);
		$code = $this->dbman->generateUserConfirmCode($u->id, "lost", true);
		if($code) return $code;
		$this->errmsg = $this->dbman->errmsg;
		return false;
	}
	
	function resetPassword($uid, $p){
		$code = $this->dbman->getUserConfirmCode($uid, "lost");
		if($code){
			if($this->dbman->updatePassword($uid, $p, true)){
				$u = $this->loadUser($uid);
				if(!$u){
					return false;
				}
				$u->recordAction("register", "updated_password", true);
				return true;
			}
			$this->errmsg = $this->dbman->errmsg;
			return false;
		}
		$this->errmsg = "No confirm code found for password reset";
		return false;		
	}

	
	
	function getErrorMessage(){
		return $this->errmsg;
	}

	
}

