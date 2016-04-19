<?php
/**
 * Class providing access to information about users of the Dacura System
 *
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */
class UserManager extends DacuraController {
	/** @var string the directory in which user sessions are kept */
	var $user_dir;
	/** @var DBManager a database manager object for accessing database */
	var $dbman;
	/** @var string the name of the session user (can be changed in settings to support multiple side-by-side installs of dacura */
	var $uservar; 
	
	/**
	 * Object constructor
	 * @param DacuraService $service
	 * @param DBManager $dbman
	 */
	function __construct(DacuraService &$service, DBManager &$dbman){
		parent::__construct($service);
		$this->user_var = $this->getSystemSetting('dacurauser');
		$this->dbman = $dbman;
	}
	
	/**
	 * Is the proposed password valid according to the password rules
	 * 
	 * Currently this will accept any password >= 6 characters long
	 * We probably need to make this stronger...
	 * @param string $pword
	 * @return boolean true indicates that the chosen password is ok
	 */
	function isValidPassword($pword){
		return strlen($pword) > 5;
	}
	
	/**
	 * Fetch a user object 
	 * @param number $n the id of the user to load (default is to load currently logged in user)
	 * @return boolean|DacuraUser the user object or false on failure
	 */
	function getUser($n = ""){
		if($n === "" || $n === 0 || $n === "0") {
			if (isset($_SESSION[$this->user_var])) {
				$u =& $_SESSION[$this->user_var];
				return $u;
			}
			else {
				return $this->failure_result("not logged in", 401);
			}
		}
		else {
			return $this->loadUser($n);
		}
	}
	
	/**
	 * Loads the user with the given id
	 * @param number $id the user id
	 * @return DacuraUser|boolean
	 */
	function loadUser($id){
		if($u = $this->dbman->loadUser($id)){
			return $u;
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
	
	/**
	 * Loads the user with the given email
	 * @param string $email email address
	 * @return DacuraUser|boolean
	 */
	function loadUserByEmail($email){
		if($u = $this->dbman->loadUserByEmail($email)){
			return $u;
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
	
	/**
	 * Returns a list of all users on the system
	 * @return array<id:DacuraUser> an array of users indexed by their ids
	 */
	function getUsers(){
		if($u = $this->dbman->loadUsers()){
			return $u;
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}
			
	/**
	 * Save user to storage
	 * @param DacuraUser $u user object to update
	 * @return DacuraUser|boolean updated users object
	 */
	function saveUser($u){
		if($this->dbman->saveUser($u)){
			//if the user being updated is the currently logged in user, update the user object in the session 
			if(isset($_SESSION[$this->user_var]) && $_SESSION[$this->user_var]->id == $u->id){
				$this->refreshCurrentUser();
			}
			return $u;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
		
	/**
	 * Adds new user to Dacura
	 * @param string $email user's email address
	 * @param string $n user's handle
	 * @param string $p user's password
	 * @param string $status users status (@see:DacuraObject::valid_statuses)
	 * @param array $prof profile name-value configuration array for user 
	 * @return boolean|DacuraUser added user object
	 */
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
			$nu->recordAction("system", "all", "created", true);				
			return $nu;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/**
	 * Delete user with specified ID
	 * @param number $id user id to be deleted
	 * @return boolean|DacuraUser deleted user object or false if failure
	 */
	function deleteUser($id){
		$du = $this->loadUser($id);
		if(!$du){
			return false;
		}
		$du->setStatus("deleted");
		$du->recordAction("system", "all", "deleted", true);
		return $this->saveUser($du);
	}
	
	/**
	 * Called when the state of the user has changed on disk and we want to refresh the session user
	 * @return DacuraUser|boolean the refreshed user
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
	
	/**
	 * Switches the current user to become the user with the passed id
	 * 
	 * This is only used for testing purposes to facilitate switching between accounts and is a huge security hole!
	 * It should be turned off in the users service api before deployment
	 * @param number $id the id of the user to switch to
	 * @return DacuraUser|boolean
	 */
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

	/* Methods for managing user roles*/
	
	/**
	 * Fetch the role object with id $rid for user $uid
	 * 
	 * @param number $uid the id of the user who owns the role
	 * @param number $rid the id of the role
	 * @return UserRole|boolean the role object of false if it does not exist
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
	
	/**
	 * Deletes the specified role from the specified user
	 * @param number $uid User ID
	 * @param number $rid Role ID
	 * @return boolean|DacuraUser update user object
	 */
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
				$u->recordAction("system", $role->cid(), "deleted role ".$role->role(), true);		
				return $u;
			}
		}
		return $this->failure_result('User $uid Role $rid did not exist', 500);
	}
	
	/**
	 * Creates a new role for the specified user
	 * @param number $uid user id
	 * @param string $cid collection id
	 * @param string $role role name (@see UserRole::dacura_roles)
	 * @return boolean|DacuraUser the user object with the role added
	 */
	function createUserRole($uid, $cid, $role){
		$u = $this->getUser($uid);
		if($u){
			$u->addRole(new UserRole(0, $cid, $role));
			if(!$this->dbman->updateUserRoles($u)){
				return $this->failure_result("Failed to create new roles for $id collection ".$this->dbman->errmsg, $this->dbman->errcode);
			}
			$roles = $this->dbman->loadUserRoles($uid);
			if($roles === false){ return false; }
			$u->roles = $roles;	
			$u->recordAction("system", $cid, "created role ".$role);
			return $u;
		}
		return $this->failure_result("Could not create role for user $uid." . " " . $this->errmsg, $this->errcode);
	}	
	
	/**
	 * returns an array of users that appear (i.e. have a role) in the given collection
	 * @param string $cid collection id
	 * @return array<DacuraUser> array of user objects
	 */
	function getUsersInContext($cid){
		//first figure out which cids to use for the given active user...
		$cids = array();
		if($cid == "all"){
			return $this->getUsers();
		}
		if(!($users  = $this->dbman->getUsersInContext(array($cid), array()))){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $users;
	}
	
	/**
	 * Returns the list of roles that a use may delegate in a given context
	 * 
	 * Only admin can give out access roles.
	 * All users can give out nobody roles
	 * 
	 * @param string $cid collection id
	 * @param number $uid user id
	 * @return string[] array of roles availbe to user in the context
	 */
	function getAvailableRoles($cid = "all", $uid = 0){
		$u = $this->getUser($uid);
		if(!$u){
			return false;
		}
		$top_role = "nobody";
		$covered_role = new UserRole(0, $cid, "nobody");
		$all_roles = UserRole::$dacura_roles;
		foreach($u->roles as $i => $r){
			if($r->coversRole($covered_role)){
				if($r->role == "admin"){
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
			return $all_roles;
		}
	}
	
	/**
	 * Loads a users session history from the user session directory 
	 * @param DacuraUser $u the relevant user object
	 * @param cid the relevant user object
	 * @return boolean true is successful
	 */
	function loadUserHistory(DacuraUser &$u, $cid){
		$u->loadHistory($cid);
		return true;		
	}
	
	/* Login / registration / invitation / lost password functions */	
	
	/**
	 * Login to the system
	 * @param string $email email address
	 * @param string $p password
	 * @param string $cid collection id through which the login is being accessed
	 * @return DacuraUser on success
	 */
	function login($email, $p, $cid){
		if($this->isLoggedIn()){
			return $this->failure_result("user is already logged in - cannot log in again ", 401);
		}
		else {
			$u = $this->dbman->testLogin($email, $p);
			if($u){
				$_SESSION[$this->user_var]=& $u;
				$u->recordAction("system", $cid, "login");
				return $u;
			}
			else {
				return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
			}
		}
	}
	
	/**
	 * Is the current user logged in?
	 * @return boolean true if user is logged in
	 */
	function isLoggedIn(){
		return (isset($_SESSION[$this->user_var]) && $_SESSION[$this->user_var]);
	}
	
	/**
	 * Logout of the system
	 */
	function logout(){
		$u = $this->getUser();
		//opr($u);
		$u->endLiveSessions("logout");
		unset($_SESSION[$this->user_var]);
	}

	/**
	 * Register a new user with the system
	 * @param string $email email address
	 * @param string $p password
	 * @param string $cid collection id through which the registration is being accessed
	 * @return string|boolean if successful, returns a confirm code for the user to use to complete registration
	 */
	function register($email, $p, $cid){
		//if the user is pending, send them a fresh notification...
		$eu = $this->loadUserbyEmail($email);
		if($eu){
			if($eu->status == "pending"){
				if($code = $this->dbman->getUserConfirmCode($eu->id, "register")){
					$eu->recordAction("system", $cid, "reregister", true);
					return $code;
				}
				elseif($code = $this->dbman->generateUserConfirmCode($eu->id, "register")){
					$eu->recordAction("system", $cid, "regenerate_confirm", true);
					return $code;
				}
				else {
					return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
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
				if($code = $this->dbman->generateUserConfirmCode($nu->id, "register")){
					$nu->recordAction("system", $cid, "register", true);
					return $code;
				}
				else {
					return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);				
				}
			}
		}
		return false;
	}
	
	/**
	 * Called when a user clicks on a confirm registration email link with a code
	 * @param string $code the confirm code
	 * @return DacuraUser the confirmed user
	 */
	function confirmRegistration($code, $cid){
		$uid = $this->dbman->getConfirmCodeUid($code, "register");
		if(!$uid){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if(!($du = $this->loadUser($uid))){
			return false;				
		}
		if(!$du->is_pending()){
			return $this->failure_result("This confirmation code is no longer valid", 401);
		}
		$du->setStatus("accept");
		$du->recordAction("system", $cid, "confirm register", true);
		if($this->saveUser($du)){
			return $du;	
		}
		return false;		
	}
	
	/**
	 * Invite a user to a collection
	 * @param string $email
	 * @param string $role @see UserRole::dacura_roles
	 * @param string $cid collection id
	 * @return string|boolean if successful, returns a confirm code
	 */
	function invite($email, $role, $cid){
		//if the user is pending, send them a fresh notification...
		$eu = $this->loadUserbyEmail($email);
		if($eu){
			if($eu->status == "pending"){
				$code = $this->dbman->getUserConfirmCode($eu->id, "invite");
				if($code){
					$eu->recordAction("system", $cid, "reissue invite", true);
					return $code;
				}
				else {
					$code = $this->dbman->generateUserConfirmCode($eu->id, "invite");
					if($code){
						$eu->recordAction("system", $cid, "regenerate invite", true);
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
			$nu = $this->addUser($email, "", "user". uniqid_base36(""), "pending");
			if($nu){
				$role = $this->createUserRole($nu->id, $cid, $role, 0);
				if(!$role){
					return false;
				}
				$code = $this->dbman->generateUserConfirmCode($nu->id, "invite");
				if($code){
					$nu->recordAction("system", $cid, "invite", true);
					return $code;
				}
				else {
					return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
				}
			}
		}
		return false;
	}
	
	/**
	 * Called when an already existing user that is not a member of the collection is invited to the collection
	 * 
	 * Gives them a role
	 * @param string $email email address
	 * @param string $role the role to be given to the user
	 * @param string $cid the collection id
	 * @return DacuraUser the invited user object
	 */
	function inviteToCollection($email, $role, $cid){
		$eu = $this->loadUserbyEmail($email);
		if(!$eu){
			return $this->failure_result("user $email is unknown cannot be invited to collection", 404);
		}
		return $this->createUserRole($eu->id, $cid, $role, 0);
		$eu->recordAction("system", $cid, "invite to collection", true);
	}
	
	/**
	 * Called when a user clicks on a link with an invite confirmation code in it
	 * @param string $code
	 * @return DacuraUser the invited user object
	 */
	function confirmInvite($code, $cid){
		if(!($uid = $this->dbman->getConfirmCodeUid($code, "invite"))){
			return $this->failure_result($this->dbman->errmsg .$uid . " is the uid ($code)", $this->dbman->errcode);
		}
		if(!($du = $this->loadUser($uid))){
			return false;
		}
		if($du->status != "pending"){
			return $this->failure_result("This invitation code is no longer valid", 401);
		}
		if(!($rcid = $du->getRoleCollectionId())){
			return $this->failure_result("This invitation code is invalid it is not associated with a role in any collection", 401);				
		}
		if($rcid != $cid && $cid != "all"){
			return $this->failure_result("Mismatch between the collection that issued the invitation and the confirmation url", 401);
		}
		$du->recordAction("system", $rcid, "confirm invite", true);
		return $du;
	}
	
	/**
	 * Updates the user's password
	 * @param number $uid the user id
	 * @param string $p password
	 * @return boolean true on success
	 */
	function updatePassword($uid, $p){
		if(!$this->isValidPassword($p)){
			return $this->failure_result("Password is invalid: passwords must be at least six characters long", 400);
		}
		if($this->dbman->updatePassword($uid, $p)){
			$u = $this->loadUser($uid);
			if(!$u){
				return false;
			}
			$u->recordAction("system", "all", "updated password", true);
			return true;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/**
	 * Reset the user's password
	 * @param number $uid user id
	 * @param string $p password 
	 * @param string $action the user action that triggered the reset: lost|invite
	 * @return boolean true on success
	 */
	function resetPassword($uid, $p, $action){
		if(!$this->isValidPassword($p)){
			return $this->failure_result("Password is invalid: passwords must be at least six characters long", 400);
		}
		$u = $this->loadUser($uid);
		if(!$u){
			return false;
		}
		if($action == "invite" && $u->status() != 'pending'){
			return $this->failure_result("The invitation has been aborted: ".$u->handle." has status ".$u->status(), 400);
		}
		if($action != "invite" && $u->status() != "accept"){
			return $this->failure_result("Cannot reset password of user ".$u->handle." not an active user: ".$u->status(), 400);
		}
		$code = $this->dbman->getUserConfirmCode($uid, $action);
		if($code){
			if($this->dbman->updatePassword($uid, $p, true)){
				if($action == 'invite'){
					$u->setStatus("accept");
					if(!$this->saveUser($u)){
						return false;
					}
				}
				$u->recordAction("system", "all", "$action password reset", true);
				return true;
			}
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $this->failure_result("No confirm code found for password reset", 401);
	}
	
	/**
	 * Called to request a password reset for a particular user
	 * @param string $email the user's email address
	 * @return boolean|string if successful, returns a confirm code
	 */
	function requestResetPassword($email){
		if(!($u = $this->loadUserByEmail($email))){
			return false;
		}
		if($u->status == "pending"){
			$u->recordAction("system", "all", "pending user failed password reset", true);
			return $this->failure_result("You must confirm your account before you can reset the password.", 401);
		}
		elseif($u->status == "reject"){
			$u->recordAction("system", "all", "rejected user failed password reset", true);
			return $this->failure_result("The account $u has been suspended, you cannot reset the password while suspended.", 401);
		}
		elseif($u->status == "deleted"){
			$u->recordAction("system", "all", "deleted user failed password reset", true);
			return $this->failure_result("The account $u has been deleted, you cannot reset the password of a deleted account.", 401);
		}
		$u->recordAction("register", "all", "password reset initiated", true);
		if(!($code = $this->dbman->generateUserConfirmCode($u->id, "lost", true))){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $code;
	}	
	
	/**
	 * Called when a user clicks on a lost password confirm link
	 * @param string $code the confirm code in the link
	 * @return DacuraUser the confirmed user object
	 */
	function confirmLostPassword($code){
		$uid = $this->dbman->getConfirmCodeUid($code, "lost");
		if(!$uid){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if(!($du = $this->loadUser($uid))){
			return false;
		}
		if($du->status != "accept"){
			return $this->failure_result("This confirmation code is invalid $du->email is not an active user", 401);
		}
		$du->recordAction("system", "all", "confirm password reset", true);
		return $du;
	}
}

