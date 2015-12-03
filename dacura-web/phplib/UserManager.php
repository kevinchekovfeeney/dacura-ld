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

class UserManager extends DacuraObject {
	
	var $user_dir;
	var $service;
	var $dbman;
	var $uservar; 
	
	function __construct($dbman, &$service){
		$this->service = $service;
		$this->user_dir = $service->settings['dacura_sessions'];
		$this->user_var = $service->settings['dacurauser'];
		$this->dbman = $dbman;
	}
	
	/*
	 * Methods for retrieving user information
	 */
	function getUser($n = 0){
		if($n === 0) {
			if (isset($_SESSION[$this->user_var])) {
				$u =& $_SESSION[$this->user_var];
				return $u;
			}
			else {
				return $this->failure_result("not logged in", 401);
			}
			return $u;
		}
		else {
			return $this->loadUser($n);
		}
	}
	
	function getUsers(){
		$u = $this->dbman->loadUsers();
		if($u){
			$this->logEvent("debug", 200, "returning list of users: " .json_encode($u, true));
			return $u;
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
	
	function loadUser($id){
		$u = $this->dbman->loadUser($id);
		if($u){
			$u->setSessionDirectory($this->user_dir.$u->id);
			return $u;
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
	
	function loadUserByEmail($email){
		$u = $this->dbman->loadUserByEmail($email);
		if($u){
			$u->setSessionDirectory($this->user_dir.$u->id);
			return $u;
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
	
	/*
	 * Methods for storing / updating user information
	 */
	function saveUser($u){
		if($this->dbman->saveUser($u)){
			return $u;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function updateUserRoles(&$u){
		if($this->dbman->updateUserRoles($u)){
			$_SESSION[$this->user_var] =& $u;
			return true;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function addUser($email, $n, $p, $status, $prof = false){
		if(!$email or !$p){
			return $this->failure_result("Attempt to add user with no email and password", 400);
		}
		if(!isValidEmail($email)){
			return $this->failure_result("Invalid email address: you must enter a working email address to allow communication with the user.", 401);
		}
		if(!$this->isValidPassword($p)){
			return $this->failure_result("Password is invalid: passwords must be at least six characters long", 400);
		}
		!$prof && $prof = '{}';
		$nu = $this->dbman->addUser($email, $n, $p, $status, $prof);
		if($nu){
			$nu->setSessionDirectory($this->user_dir.$nu->id);
			return $nu;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
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
	
	/*
	 * Login / registration / lost password functions
	 */	
	
	function login($email, $p){
		if($this->isLoggedIn()){
			return $this->failure_result("user is already logged in - cannot log in again ", 401);
		}
		else {
			$u = $this->dbman->testLogin($email, $p);
			if($u){
				$u->setSessionDirectory($this->user_dir.$u->id);
				$_SESSION[$this->user_var]=& $u;
				$u->recordAction("system", "login");
				return $u;
			}
			else {
				return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
			}
		}
	}
	
	function isLoggedIn(){
		return (isset($_SESSION[$this->user_var]) && $_SESSION[$this->user_var]);
	}
	
	function logout(){
		//this is where lots of the work goes vis a vis session management
		$u = $this->getUser();
		$u->endLiveSessions("logout");
		unset($_SESSION[$this->user_var]);
	}

	function inviteToCollection($email, $role, $cid, $did){
		$eu = $this->loadUserbyEmail($email);
		if(!$eu){
			return $this->failure_result("user $email is unknown cannot be invited to collection", 404);
		}
		return $this->createUserRole($eu->id, $cid, $did, $role, 0);
	}	
	
	function invite($email, $role, $cid, $did){
		//if the user is pending, send them a fresh notification...
		$eu = $this->loadUserbyEmail($email);
		if($eu){
			if($eu->status == "pending"){
				$code = $this->dbman->getUserConfirmCode($eu->id, "invite");
				if($code){
					$eu->recordAction("invite", "reinvite", true);
					return $code;
				}
				else {
					$code = $this->dbman->generateUserConfirmCode($eu->id, "invite");
					if($code){
						$eu->recordAction("invite", "regenerate_confirm", true);
						return $code;
					}
					else {
						return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
					}
				}
			}
			else {
				return $this->failure_result("User with email $email already exists on the system (status: $eu->status) - cannot register", 401);
			}
		}
		else {
			if(!isValidEmail($email)){
				return $this->failure_result("Invalid email address: you must enter a working email address to receive account confirmation", 401);
			}
			//give them a randomly generated password - will need to change it when they accept the invite...
			//$nu = $this->addUser($email, "", "user". uniqid_base36(""), "pending");
			$nu = $this->addUser($email, "", "aligned", "pending");				
			if($nu){
				$role = $this->createUserRole($nu->id, $cid, $did, $role, 0);
				if(!$role){
					return false;
				}
				$code = $this->dbman->generateUserConfirmCode($nu->id, "invite");
				if($code){
					$nu->recordAction("invite", "invite", true);
					return $code;
				}
				else {
					return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
				}
			}
		}
		return false;	
	}	

	function register($email, $p){
		//if the user is pending, send them a fresh notification...
		$eu = $this->loadUserbyEmail($email);
		if($eu){
			if($eu->status == "pending"){
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
						return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
					}
				}
			}
			else {
				return $this->failure_result("User with email $email already exists on the system (status: $eu->status) - cannot register", 401);
			}
		}
		else {
			if(!isValidEmail($email)){
				return $this->failure_result("Invalid email address: you must enter a working email address to receive account confirmation", 401);				
			}
			if(!$this->isValidPassword($p)){
				return $this->failure_result("Password is invalid: passwords must be at least six characters long", 400);
			}
			$nu = $this->addUser($email, "", $p, "pending");
			if($nu){
				$code = $this->dbman->generateUserConfirmCode($nu->id, "register");
				if($code){
					$nu->recordAction("register", "register", true);
					return $code;
				}
				else {
					return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);				
				}
			}
		}
		return false;
	}

	function confirmInvite($code){
		$uid = $this->dbman->getConfirmCodeUid($code, "invite");
		if(!$uid){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		//$this->dbman->updateUserState($uid, "new");
		$du = $this->loadUser($uid);
	
		if(!$du){
			return false;
		}
		if($du->status != "pending"){
			return $this->failure_result("This invitation code is no longer valid", 401);
		}
		$du->setStatus("accept");
		$du->recordAction("invite", "confirm_invite", true);
		if($this->saveUser($du)){
			return $du;
		}
		return false;
	}
	
	
	
	function confirmRegistration($code){
		$uid = $this->dbman->getConfirmCodeUid($code, "register");
		if(!$uid){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		//$this->dbman->updateUserState($uid, "new");
		$du = $this->loadUser($uid);

		if(!$du){
			return false;				
		}
		if($du->status != "pending"){
			return $this->failure_result("This confirmation code is no longer valid", 401);
		}
		$du->setStatus("accept");
		$du->recordAction("register", "confirm_register", true);
		if($this->saveUser($du)){
			return $du;	
		}
		return false;		
	}


	function updatePassword($uid, $p){
		if(!$this->isValidPassword($p)){
			return $this->failure_result("Password is invalid: passwords must be at least six characters long", 400);
		}
		if($this->dbman->updatePassword($uid, $p)){
			$u = $this->loadUser($uid);
			if(!$u){
				return false;
			}
			$u->recordAction("users", "updated_password", true);
			return true;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	
	function resetPassword($uid, $p){
		if(!$this->isValidPassword($p)){
			return $this->failure_result("Password is invalid: passwords must be at least six characters long", 400);
		}
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
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $this->failure_result("No confirm code found for password reset", 401);
	}
	
	function confirmLostPassword($code){
		$uid = $this->dbman->getConfirmCodeUid($code, "lost");
		if(!$uid){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$du = $this->loadUser($uid);	
		if(!$du){
			return false;
		}
		if($du->status != "accept"){
			return $this->failure_result("This confirmation code is no longer valid $du->email is no longer active", 401);
		}
		$du->recordAction("register", "confirm_lost", true);
		return $du;
	}
	
	function requestResetPassword($email){
		$u = $this->loadUserByEmail($email);
		if(!$u){
			return false;
		}
		if($u->status == "pending"){
			$u->recordAction("register", "lost_password_failed", true);
			return $this->failure_result("You must confirm your account before you can reset the password.", 401);
		}
		elseif($u->status == "reject"){
			$u->recordAction("register", "lost_password_failed", true);
			return $this->failure_result("The account $u has been suspended, you cannot reset the password while suspended.", 401);
		}
		elseif($u->status == "deleted"){
			$u->recordAction("register", "lost_password_failed", true);
			return $this->failure_result("The account $u has been deleted, you cannot reset the password of a deleted account.", 401);
		}
		$u->recordAction("register", "lost_password", true);
		$code = $this->dbman->generateUserConfirmCode($u->id, "lost", true);
		if($code) return $code;
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/*
	 * Methods for updating current logged in user
	 */
	
	function refreshCurrentUser(){
		$x = isset($_SESSION[$this->user_var]) ? $_SESSION[$this->user_var]->id : false;
		if($x){
			$u = $this->loadUser($x);
			if($u) {
				$_SESSION[$this->user_var] =& $u;
				return $u;
			}
			return false;
		}
		return $this->failure_result("Failed to load existing user from session!", 500);
	}
	
	function switchToUser($id){
		if($this->isLoggedIn()){
			$this->logout();
		}
		$u = $this->loadUser($id);
		if($u) {
			$_SESSION[$this->user_var] =& $u;
			return $u;
		}
		return false;
	}
	
	/*
	 * Methods for dealing with roles
	 */
	
	/*
	 * return role object with id $rid for user $uid
	 */
	function getUserRole($uid, $rid){
		$u = $this->getUser($uid);
		foreach($u->roles as $role){
			if($role->id == $rid){
				return $role;
			}
		}
		return $this->failure_result('User $uid Role $rid did not exist' . $e->getMessage(), 500);
	}
	
	function deleteUserRole($uid, $rid){
		$u = $this->getUser($uid);
		if(!$u){
			return $this->failure_result("Could not delete role $rid for user $uid." . " " . $this->errmsg, $this->errcode);				
		}
		foreach($u->roles as $i => $role){
			if($role->id == $rid){
				if(!$this->dbman->deleteRole($rid)){
					return $this->failure_result("Failed to delete $rid role for $uid", 500);
				}
				unset($u->roles[$i]);
				return $u;
			}
		}
		return $this->failure_result('User $uid Role $rid did not exist', 500);
	}
	
	function createUserRole($uid, $cid, $did, $role, $level){
		$u = $this->getUser($uid);
		if($u){
			$u->addRole(new UserRole(0, $cid, $did, $role, $level));
			//$u->roles[] = new UserRole(0, $id, 0, 'admin', 99);
			if(!$this->dbman->updateUserRoles($u)){
				return $this->failure_result("Failed to create new roles for $id collection", 500);
			}
			$u->roles = $this->dbman->loadUserRoles($uid);
			return $u;
		}
		return $this->failure_result("Could not create role for user $uid." . " " . $this->errmsg, $this->errcode);
	}
	
	/*
	 * Intersection between cid/did context and users...
	 */
	/*
	 * returns an array of userids that appear (i.e. have a role) in the given context
	 */
	function getUsersInContext($cid, $did){
		//first figure out which cids to use for the given active user...
		$cids = array();
		$dids = array();
		if($cid == "all"){
			//we are in top level context - all users are returned....
			return $this->getUsers();
		}
		elseif($did == "all"){
			//we are in a collection level context
			$uids  = $this->dbman->getUsersInContext(array($cid), array());
		}
		else {
			$uids  = $this->dbman->getUsersInContext(array(), array($did));
		}
		if(!$uids){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$this->logEvent("debug", 200, "returning list of users: " .json_encode($uids, true));
		$users = array();
		foreach($uids as $id){
			$u = $this->getUser($id);
			if(!$u){
				$this->logEvent("warning", $this->errcode, $this->errmsg);
			}
			else {
				$users[] = $u;				
			}
		}
		return $users;
	}
	
	/*
	 * Returns a user object pruned so that it only has roles within the given context...
	 */
	function getUserPrunedForContext($uid, $cid, $did){
		$u = $this->getUser($uid);
		if(!$u){
			return $this->failure_result("User $uid does not exist", 404);
		}
		$covering_role = new UserRole(0, $cid, $did, "god", "");
		$nroles = array();
		foreach($u->roles as $i => $r){
			if(!$covering_role->coversRole($r) || ($cid !="all" && $r->collection_id == "all") ||
					($did != "all" && $r->dataset_id == "all")){
				//unset($u->roles[$i]);
			}
			else {
				$nroles[] = $r;
			}
		}
		$u->roles = $nroles;
		$this->loadUserHistory($u);
		unset($u->session_dump);
		//unset($u->sessions);
		return $u;
	}
	
	/*
	 * Returns the list of roles that a use may delegate in a given context
	 * Only admin and god can give out access roles.
	 * All users can give out nobody roles
	 */
	function getAvailableRoles($cid = "all", $did = "all", $uid = 0){
		$u = $this->getUser($uid);
		if(!$u){
			return false;
		}
		$top_role = "nobody";
		$covered_role = new UserRole(0, $cid, $did, "nobody", "");
		$all_roles = UserRole::$dacura_roles;
		foreach($u->roles as $i => $r){
			if($r->coversRole($covered_role)){
				if($r->role == "god"){
					return $all_roles;
				}
				elseif($r->role == "admin"){
					$top_role = "admin";
				}
				elseif($r->role != "nobody" && $top_role != "admin"){
					$top_role = "user";
				}		
			}
		}
		if($top_role == 'nobody') return array();
		if($top_role == 'user') return array("nobody" => $all_roles['nobody']);
		if($top_role == 'admin'){
			unset($all_roles['god']);
			return $all_roles;
		}
	}
	
	function loadUserHistory(&$u){
		if ($handle = opendir($u->session_dump)) {
			/* This is the correct way to loop over the directory. */
			while (false !== ($entry = readdir($handle))) {
				$ext = substr($entry, strrpos($entry, '.') +1);
				if ($ext == "session"){
					$sess_id = substr($entry, 0, strrpos($entry, '.'));
					$fhandle = fopen($u->session_dump.$entry, "r");
					if ($fhandle) {
						while (($line = fgets($fhandle)) !== false) {
							$ds = new DacuraSession($sess_id);
							if($ds->loadFromJSON($line)){
								$one_history = $ds->summary();
								$one_history['service'] = $sess_id;
								$one_history['events'] = $ds->events;
								$u->history[] = $one_history;
							}
							else {
								$this->logEvent("notice", 500, "Failed to parse session dump [$line]");
							}
						}	
						fclose($fhandle);
					} 
					else {
						$this->logEvent("notice", 500, "Failed to open file $entry in $this->session_dump");
					}
				}
				else {
					if($entry != "." && $entry != ".."){
						$this->logEvent("notice", 500, "Non session file $entry in session directory ($ext)");
					}
				}
			}
			closedir($handle);
		}
		else {
			return $this->failure_result("Failed to open user session directory $u->session_dump", 500, "warning");
		}
	}
	
	function logEvent($level, $code, $msg){
		$this->service->logger->logEvent($level, $code, $msg);
	}

	
	function isValidPassword($pword){
		return strlen($pword) > 5;
	}
	
}

