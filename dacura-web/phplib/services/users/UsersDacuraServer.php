<?php
/**
 * Users Server - provides access to updating / editing / viewing users and roles, etc.
 * 
 * This class is just an interface which adds context to the UserManager object included in all dacura server objects 
 * (as user maninpulation / access is common to most services.
 * Creation Date: 15/01/2015
 *
 * @package users
 * @author chekov
 * @license GPL V2
 */
class UsersDacuraServer extends DacuraServer {
	
	/**
	 * Returns an array of users who have a role in the current collection context
	 * @return array<DacuraUser>|boolean
	 */
	function getUsersInContext(){
		if(!($users = $this->userman->getUsersInContext($this->cid()))){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);				
		}
		$ua = $this->getUser();
		foreach($users as $i => $u){
			if(!$ua || !$this->canUpdateUserStatus($ua, $u)){
				$users[$i]->selectable = false;
			}
			$users[$i]->category = $users[$i]->rcategory();
				
			if($this->cid() == "all"){
				$users[$i]->collections = $users[$i]->collectionSummary();
				$rs = $users[$i]->roleSummary();
				$users[$i]->roles = $rs;
			}
			else {
				$nroles = array();
				$croles = $u->getRolesInCollection($this->cid());
				foreach($croles as $rname){
					$nroles[$rname] = UserRole::$dacura_roles[$rname];
				}
				$users[$i]->roles = $nroles;				
			}			
			$users[$i]->forapi();				
		}
		$this->recordUserAction("view users", array());				
		return $users;
	}
			

	/**
	 * Does the current user have permission to add a new user with the specified email
	 * @param array $params an array of parameters about the user, [email: "", roles:[]]
	 * @return boolean false if the current user is not permitted to add the passed user
	 */
	function canAddUser($params){
		if(!($ua = $this->getUser())){
			return false;
		}
		if(!($ou = $this->userman->loadUserByEmail($params['email']))){//brand spanking new user
			if(isset($params['roles']) && count($params['roles']) > 0){
				foreach($params['roles'] as $role){
					if($role['collection_id'] == "all" && !$ua->isPlatformAdmin()){
						return $this->failure_result("Only platform administrators can add platform roles", 401);
					}
					if(!$ua->isCollectionAdmin($role['collection_id'])){
						return $this->failure_result("Only the admin of collection ".$role['collection_id']." can add roles", 401);
					}
				}
			}		
			return true;
		}
		else {
			if($this->cid() == "all" || !isset($params['roles']) || count($params['roles']) == 0){
				return $this->failure_result("A user with the email address $email is already registered on the system", 400);
			}
			foreach($params['roles'] as $role){
				if(!$this->canCreateRole($ou, $role['collection_id'], $role['role'])) return false;
			}
			return true;
		}		
	}
	
	/**
	 * Add the user to the given system. 
	 * @param array $params an array of parameters about the user, [email: "", roles:[]]
	 * @return boolean|DacuraUser the added user object or false on failure
	 */
	function addUser($params){
		$ou = $this->userman->loadUserByEmail($params['email']);
		if($ou){
			$roles = isset($params['roles']) ? $params['roles'] : array();
			return $this->addExistingUser($ou, $roles);
		}
		$params['name'] = (isset($params['name'])) ? $params['name'] : "";
		$params['status'] = (isset($params['status']))? $params['status'] : $this->getServiceSetting("add_user_status", "accept");
		$params['profile'] = (isset($params['profile'])) ? $params['profile'] : $this->getServiceSetting('default_profile', array());
		if(!($u = $this->userman->addUser($params['email'], $params['name'], $params['password'], $params['status'], json_encode($params['profile'], true)))){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		$this->recordUserAction("create user", array("user" => $u->id, "email" => $params['email']));
		if(isset($params['roles'])){
			foreach($params['roles'] as $role){
				if($role['collection_id'] == "all" && $role['role'] != "admin"){
					return $this->failure_result("Administrator is the only role availabe at the system level", 401);
				}
			}
			if(!$this->dbman->updateUserRoles($u)){
				return $this->failure_result("Failed to create new roles for user $u->handle in $id collection ".$this->dbman->errmsg, $this->dbman->errcode);
			}
		}
		return $u;
	}
	
	/**
	 * Add an existing user to a collection by means of giving the user roles in that collection
	 * @param DacuraUser $u
	 * @param array $roles an array of name-value roles
	 * @return boolean|DacuraUser the user with the new roles added
	 */
	private function addExistingUser(DacuraUser &$u, $roles){
		if($this->cid() == "all" || count($roles) == 0){
			return $this->failure_result("A user with the email address ".$u->email." is already registered on the system", 400);
		}
		$rstr = [];
		foreach($roles as $role){
			$u->addRole(new UserRole(0, $role['collection_id'], $role['role']));
			$rstr[] =  $role['collection_id']. ":" . $role['role'];
		}
		if(!$this->dbman->updateUserRoles($u)){
			return $this->failure_result("Failed to create new roles for user $u->handle in $id collection ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		$u->recordAction("system", $this->cid(), "created roles :".implode(", ", $rstr));
		return $u;
	}
	
	/**
	 * Prunes a user object to remove roles that are not relevant to the current context
	 *
	 * Based on the principle that roles in other collections should be invisible in api calls within other collections
	 * @param Number $uid the user id
	 * @return DacuraUser|boolean
	 */
	function getUserPrunedForContext($uid){
		if(!($u = $this->getUser($uid))){
			return false;
		}
		$covering_role = new UserRole(0, $this->cid(), "admin");
		$nroles = array();
		foreach($u->roles as $i => $r){
			if(!(!$covering_role->coversRole($r) || ($this->cid() !="all" && $r->cid() == "all"))){
				if(!$this->canDeleteRole($u, $r)){
					$r->selectable = false;
				}
				$nroles[] = $r->forapi();
			}
		}
		if(count($nroles) == 0 && $this->cid() != "all"){
			return $this->failure_result("User $u->handle (id: $uid) is not a member of collection ".$this->cid()." and cannot be managed through it", 401);
		}
		$u->roles = $nroles;
		return $u->forapi();
	}
	
	/**
	 * Decides which users can update another user object
	 *
	 * Has the same rules as updating status, with the exception that users can update themselves
	 * @param DacuraUser $object the user object to be updated
	 * @param array $json a name value array of attributes to be updated in the user object
	 * @return boolean if true, the update is allowed by the current users
	 */
	function canUpdateUser(DacuraUser $object, $json){
		if(!($ua = $this->getUser())){
			return false;
		}
		if(isset($json['status']) && !$this->canUpdateUserStatus($ua, $object)){
			return false;
		}
		else if($ua->id == $object->id || $this->canUpdateUserStatus($ua, $object)){ //same rules for status, except the user can update themselves
			return true;
		}
		return false;
	}
	
	/**
	 * Updates the user object by changing one or more of status, name, email, profile
	 * @see DacuraServer::updateUser()
	 * @param DacuraUser $uobj the user object to be updated
	 * @param array<string:string> name value array of parameters (email, status, profile, name)
	 * @return DacuraUser the updated user object
	 */
	function updateUser(DacuraUser &$uobj, $params){
		foreach($params as $n => $v){
			if($n == "email") $uobj->email = $v;
			elseif($n == "name") $uobj->name = $v;
			elseif($n == "status") $uobj->status($v);
			elseif($n == "profile"){
				$uobj->profile = $v;
				$params['profile'] = "updated";//don't save the entire profile in session
			}
			else unset($params[$n]);//don't save random stuff into session
		}
		if(!(parent::updateUser($uobj, $params))){
			return false;
		}
		$nobj = $this->getUserPrunedForContext($uobj->id);
		if($nobj){
			return $nobj;
		}
		return $uobj->forapi();
	}

	/**
	 * Decides which users can update the status of which other users
	 *
	 * Only platform administrators can update the status of platform or multi-collection users
	 * Collection admins can update the status of collection users but not other collection admins
	 * @param DacuraUser $ua the user carrying out the update
	 * @param DacuraUser $ub the user being updated
	 * @return boolean true if the update is permitted
	 */
	function canUpdateUserStatus(DacuraUser $ua, DacuraUser $ub){
		if($ua->isPlatformAdmin()) return true;
		if($ub->rolesSpanCollections()) return $this->failure_result("Only platform administrators can update users who have roles in multiple collections $ub->id", 401);
		if($ua->isCollectionAdmin($ub->getRoleCollectionId()) && !($ub->isCollectionAdmin($ub->getRoleCollectionId()))) return true;
		return $this->failure_result("Administrator of collection ".$ub->getRoleCollectionId()." role is needed to update $ub->id status", 401);
	}
	
	/**
	 * Is the current user allowed delete the passed user?
	 *
	 * Only permitted if the user is not the current user and has permission to update the user's status
	 * @param DacuraUser $ub
	 * @return boolean
	 */
	function canDeleteUser(DacuraUser $ub){
		return (($ua = $this->getUser()) && ($ua->id != $ub->id ) && $this->canUpdateUserStatus($ua, $ub));
	}
	
	/**
	 * Deletes the passed user
	 * @param DacuraUser $u the user to be deleted
	 * @return boolean true if the user is successfully deleted
	 */
	function deleteUser(DacuraUser &$u){
		if(!$this->userman->deleteUser($u->id)){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		$this->recordUserAction("delete user", array("user" => $u->id));
		return true;
	}
	
	/**
	 * Is the current user allowed to update the password of the passed user?
	 * @param DacuraUser $ub the user whose password is to be updated
	 * @return boolean if true the current user is allowed to update the passed user
	 */
	function canUpdatePassword(DacuraUser $ub){
		return (($ua = $this->getUser()) && (($ua->id == $ub->id) || $this->canUpdateUserStatus($ua, $ub)));
	}
	
	/**
	 * Updates the passed user object to set the password to the passed password.
	 * @param DacuraUser $uobj the user object to be updated
	 * @param string $np the new password
	 * @return boolean true on success
	 */
	function updatePassword(DacuraUser &$uobj, $np){
		if(!$this->userman->updatePassword($uobj->id, $np)){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		$this->recordUserAction("update password", array("user" => $uobj->id));
		return true;
	}
		
	/**
	 * Decides which users can create which roles for which other users
	 * 
	 * Platform administrators can create whatever roles they like
	 * collection administrators can create any roles that are not already possessed by the user for their collection
	 * Otherwise no. 
	 * @param DacuraUser $uobj the object user - the one that is getting the role added
	 * @param string $cid the collection id for the new role
	 * @param string $rname the name of the role
	 * @return boolean if true, the current user is allowed to create this role.
	 */
	function canCreateRole(DacuraUser $uobj, $cid, $rname){
		if($cid == "all" && $rname != "admin") return $this->failure_result("Administrators are the only sort of users that can be created at the system level", 401);
		if(!($ua = $this->getUser())) return false;
		if($ua->isPlatformAdmin()) return true;
		if(!$ua->isCollectionAdmin($cid)) return $this->failure_result("Only collection admins can create roles in their collections", 401);
		if($uobj->hasSufficientRole($rname, $cid)) return $this->failure_result("User $uobj->id has a role covering collection $cid that is greater or equal to the $rname role", 400);
		return true;
	}
	
	/**
	 * Does the current user have permission to delete the passed role from the passed user
	 * @param DacuraUser $uobj the user object that will have the role removed
	 * @param UserRole $robj the role object that will be removed
	 * @return boolean true if the role deletion is permitted
	 */
	function canDeleteRole(DacuraUser $uobj, UserRole $robj){
		if(!($ua = $this->getUser())) return false;
		if($ua->isPlatformAdmin()) return true;
		if(!$ua->isCollectionAdmin($robj->cid())) return $this->failure_result("Only collection admins can remove roles in their collections", 401);
		if($uobj->isCollectionAdmin($robj->cid())) return $this->failure_result("Collection admins cannot update the roles of other collection admins", 401);
		return true;
	}
	
	/**
	 * Create a new role and give it to the passed user object
	 * @param DacuraUser $uobj the user object which will be given the new role
	 * @param string $cid the collection id that the role applies to
	 * @param string $rname the name of the role (one of UserRole::$dacura_roles
	 * @return boolean|DacuraUser the user who has had the role added or false if fail
	 */
	function createRole(DacuraUser &$uobj, $cid, $rname){
		$uobj = $this->userman->createUserRole($uobj->id, $cid, $rname);
		if(!$uobj){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		$uobj->endSession("system", $cid);
		$this->recordUserAction("add role", array("role" => $rname, "user" => $uobj->id, "collection" => $cid));
		return $this->getUserPrunedForContext($uobj->id);
	}		
	
	/**
	 * Deletes the passed role from the passed user object
	 * @param DacuraUser $uobj the user from whom the role will be deleted
	 * @param UserRole $robj the role that will be deleted
	 * @return boolean|DacuraUser the updated user object or false if the role deletion fails
	 */
	function deleteRole(DacuraUser &$uobj, UserRole $robj){
		if(!($uobj = $this->userman->deleteUserRole($uobj->id, $robj->id))){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		$this->recordUserAction("delete role", array("role" => $robj->role(), "user" => $uobj->id, "collection" => $robj->cid()));
		$nobj = $this->getUserPrunedForContext($uobj->id);
		if($nobj){
			return $nobj;
		}
		return $uobj->forapi();
	}

	/**
	 * Can the current user view another user's history? 
	 * 
	 * The answer depends upon the context in which it is being view (which collection) and the 
	 * category of user - platform admins can do anything, 
	 * collection admins can view the history only of a user who has a role in the current collection
	 * nobody else is allowed to view a user's history 
	 * not even the user themselves although this may change...
	 * @param DacuraUser $ub the user who is the object of the action (i.e. whose history is being viewed)
	 * @return boolean if true, the current user is allowed to view the object user's history
	 */
	function canViewUserHistory(DacuraUser $ub){
		if($this->cid() != "all" && !$ub->hasCollectionRole($this->cid())){
			return $this->failure_result("You do not have permission to view the history of user ".$ub->handle, 401);				
		}
		return $this->userHasFacet("inspect");
		//if($ua->isCollectionAdmin($this->cid()) && $this->cid() == 'all' || $ub->hasCollectionRole($this->cid())) return true;
	}
	
	/**
	 * Fetches a user's session history for the current context
	 * @param DacuraUser $ub the user whose history is being requested
	 * @return array<session> an array of session associative arrays
	 */
	function getUserHistory(DacuraUser &$ub){
		if(!$this->userman->loadUserHistory($ub, $this->cid())){
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
		return $ub->history;
	}	

	/**
	 * Is the user purely a member of the collection and has no other dacura role?
	 * @param DacuraUser $u
	 * @param string $cid
	 * @return boolean returns true if the user is owned by the collection
	 */
	function userIsOwnedByCollection(DacuraUser $u, $cid){
		if($u->rolesSpanCollections()){
			return false;
		}
		return ($u->getRoleCollectionId() == $cid);
	}

	/**
	 * Returns a structure roles that can be given to a user in a certain context..
	 * @param string $uid user id
	 * @param string $cid collection id
	 * @return array<collection_id:array<options>> an array, indexed by collection ids, with values that include the roles available to be added
	 */
	function getRoleCreateOptions($uid, $cid = false){
		if(!($ub = $this->getUser($uid))){
			return false;
		}
		$cid = ($cid === false) ? $this->cid() : $cid;
		$contexts = $this->getContextsWithUserRole("admin");
		if($cid != "all" && (!isset($contexts[$cid]))){
			return $this->failure_result("User does not possess permission to create roles for $uid in [$cid] context", 401);
		}
		$choices = array();
		foreach($contexts as $colid => $txt){
			if($colid == $cid || $cid == "all"){
				$options = array();
				foreach(UserRole::$dacura_roles as $rn => $rtitle){
					if($this->canCreateRole($ub, $colid, $rn)){
						$options[$rn] = $rtitle;
					}
				}
				if(count($options) > 0){
					$choices[$colid] = $txt;
					$choices[$colid]["options"] = $options;
				}
			}
		}
		return $choices;
	}
	
	/**
	 * Returns a datastructure representing the datasets and contexts that the user has at least the given role in  
	 * @param string $role the name of the role to use as a lower bound
	 * @return array an array, indexed by collection ids, with array<title:".."> being the values of the array
	 */
	private function getContextsWithUserRole($role=false){
		$u = $this->getUser();
		$choices = array();
		if($u && $u->hasCollectionRole("all", $role)){
			$choices["all"] = array("title" => "All collections");				
		}
		$cols = $this->getCollectionList();
		foreach($cols as $colid => $col){
			if($col->status == "deleted"){ continue;}
			if($u && $u->hasCollectionRole($colid, $role)){
				$choices[$colid] = array("title" => $col->name);
			}
		}
		return $choices;
	}
	
	/**
	 * Issues an emailed invitation to a user to join the collection 
	 * @param string $user the user's email address
	 * @param string $role the role that they will be given if they accept the invitation
	 * @param string $message the message that will be sent to the user in the email
	 * @return boolean true if invitation is issued okay
	 */
	function inviteUser($user, $role, $message){
		if($confirm_code = $this->userman->invite($user, $role, $this->cid())){
			$address =  $this->durl()."login/invite/code/".$confirm_code;
			ob_start();
			include("screens/invite_email.php");
			$output = ob_get_contents();
			ob_clean();
			$content = $message . $output;
			sendemail($user, $this->getServiceSetting('invite_email_subject', "Invitation to join Data Curation Project"), $content, $this->getSystemSetting('mail_headers',""));
			$this->recordUserAction("invite user", array("email" => $user, "role" => $role));
			return true;
		}
		else {
			$this->recordUserAction("failed to invite user", array("email" => $user, "role" => $role, "error" => $this->userman->errcode));
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
	}
	
	/**
	 * Parses a list of email addresses, separated by commas and/or whitespaces
	 * 
	 * Classifies the addresses into categories:
	 * * unknown - the user is unkown to dacura and will be invited for the first time
	 * * pending - the user has not confirmed their account (or responded to a previous invitation, the invite will be reissued
	 * * active - the user has an active dacura account
	 * * invalid - the address was not a valid email address
	 * * inactive - the user is registered on dacura but is no longer active
	 * * redundant - the user already has a role that allows them to do what they were invite to do
	 * @param string $text the string of email addresses that are being invited
	 * @param string $role the role that will be given to invited users
	 * @return array<usertype:<emails>> an array of the users who fall into each category (indexed by category)
	 */
	function parseInviteList($text, $role){
		$emails = preg_split('/[,\s]+/', $text);
		$invite_list = array("unknown" => array(), "pending" => array(), "active" => array(), "inactive" => array(), "invalid" => array(), "redundant" => array());
		foreach($emails as $email){
			if(!isValidEmail($email)){
				$invite_list['invalid'][] = $email;
			}
			else {
				$ou = $this->userman->loadUserByEmail($email);
				if($ou){
					if($ou->status == "pending"){
						$invite_list['pending'][] = $email;
					}
					elseif($ou->status != "accept"){
						$invite_list['inactive'][] = $email;
					}
					elseif($ou->hasSufficientRole($role, $this->cid())){
						$invite_list['redundant'][] = $email;
					}
					else {
						$invite_list['active'][] = $email;
					}
				}
				else {
					$invite_list['unknown'][] = $email;
				}
			}
		}
		return $invite_list;
	}
	
	/**
	 * Processes a previously parsed list of invitees to issue invites or add roles where needed 
	 * @param array<category:emails> $invite_list a parse invitation list as returned by $this->parseInviteList
	 * @param string $role the role that will be given to invitees
	 * @param string $message the email invitation message
	 * @return array<string:<emails>> an array "issued": [emails], "failed": [emails] showing the results of the invitations
	 */
	function processInviteList($invite_list, $role, $message){
		$results = $this->getInviteErrorReport($invite_list);
		foreach(array("unknown", "pending", "active") as $itype){
			foreach($invite_list[$itype] as $new_user){
				if($itype == "active"){
					if($this->userman->inviteToCollection($new_user, $role, $this->cid())){
						$results['issued'][$new_user] = "Existing Dacura user invited to join collection";
						$this->recordUserAction("add role", array("email" => $new_user, "role" => $role));						
					}
					else {
						$results['failed'][$new_user] = $this->userman->errcode.": ".$this->userman->errmsg;						
						$this->recordUserAction("failed to add role", array("email" => $new_user, "role" => $role, "error" => $this->userman->errcode));						
					}
				}
				else {
					if($this->inviteUser($new_user, $role, $message)){
						$results['issued'][$new_user] = ($itype == "unknown") ? "New user invited to join" : "Invitation re-issued to pending user";
					}
					else {
						$results['failed'][$new_user] = $this->errcode.": ".$this->errmsg;
					}
				}
			}
		}
		return $results;
	}
	
	/**
	 * Does the parse invite list contain valid invitations(those that are in category uknown, active or pending)
	 * @param array $invite_list the previously parsed invitation list
	 * @return boolean true if there are valid entries
	 */
	function inviteListContainsValidEntries($invite_list){
		foreach(array("unknown", "active", "pending") as $itype){
			if(count($invite_list[$itype]) > 0) return true;
		}
		return false;
	}
	
	/**
	 * produces a report [failed: [emails], issued: [emails]] of the outcome of a failed invitation
	 * @param array $invite_list the previously parsed list of invitations
	 * @param boolean $show_empty if true, the error report will be shown even when empty
	 * @return array<failed|issued:<emails>> the result report of the invitations
	 */
	function getInviteErrorReport($invite_list, $show_empty = true){
		$results = array("issued" => array(), "failed" => array());
		foreach(array("invalid", "inactive", "redundant") as $itype){
			foreach($invite_list[$itype] as $email){
				$results['failed'][$email] = $itype.": ";	
			}
		}
		return $results;
	}
	
}
