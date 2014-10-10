<?php

class SessionManager {
	
	var $user_file;
	var $userlist = array();
	
	var $errormsg = "";
	
	function __construct($uf = "/var/dacura/users.dac"){
		$this->user_file = $uf;
		$this->_loadUserFile();		
	}
	
	function addUser($n, $p){
		if(!$n or !$p){
			$this->errormsg = "User is missing name / password";
			return false;
		}
		if(isset($this->userlist[$n])){
			$this->errormsg = "User $n already exists";
			return false;
		}
		$u = new User($n, $p);
		$this->userlist[$n] = $u;
		$this->_saveUserFile();	
		return $u;
	}
	
	function getErrorMessage(){
		return $this->errormsg;
	}
	
	function login($n, $p){
		if(isset($this->userlist[$n])){
			$u = $this->userlist[$n];
			return $u;
		}
		return false;
	}
	
	function allocateChunk($user, $yr_from, $yr_to){
		
	}
	
	function isLoggedIn(){
		return (isset($_SESSION['user']) && $_SESSION['user']);
	}
	
	function getUser(){
		return (isset($_SESSION['user'])) ? $_SESSION['user'] : false;
	}
	
	
	function _saveUserFile(){
		file_put_contents($this->user_file, serialize($this->userlist));
	}
	
	function _loadUserFile(){
		if(file_exists($this->user_file)){
			$this->userlist = unserialize(file_get_contents($this->user_file));
		}
	}
	
	
}

class User {
	var $email;
	var $pword;
	
	function __construct($e, $p){
		$this->email = $e;
		$this->pword = $p;
	}
}


class UserSession {
	
	var $user;
	var $current_chunks;
	var $history;
	
	
	function __construct($u){
		$this->user = $u;
	}
	
	
	
	
}

