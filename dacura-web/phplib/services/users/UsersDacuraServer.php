<?php
/*
 * Users Server - provides access to updating / editing / viewing users and roles, etc.
 * This class is just an interface which adds context to the UserManager object included in all dacura server objects 
 * (as user maninpulation / access is common to most services.
 *
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 15/01/2015
 * Licence: GPL v2
 */


class UsersDacuraServer extends DacuraServer {
	
	function getUsersInContext(){
		$x = $this->userman->getUsersInContext($this->cid(), $this->did());
		if($x) return $x;
		return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
	}
	
	//prunes the users roles so that only the ones that are relevant to the context remain
	function getUserPrunedForContext($uid){
		$x = $this->userman->getUserPrunedForContext($uid, $this->cid(), $this->did());
		if($x) return $x;
		return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
	}
	
	function addUser($params){
		$ou = $this->userman->loadUserByEmail($params['email']);
		if($ou){
			if($this->cid() == "all" || !isset($params['roles']) || count($params['roles']) == 0){
				return $this->failure_result("A user with the email address $email is already registered on the system", 400);
			}
			foreach($params['roles'] as $role){
				$x = $this->userman->createUserRole($ou->id, $role['collection_id'], $role['dataset_id'], $role['role'], $role['level']);
				if(!$x){
					$this->logEvent("warning", $this->userman->errcode, "Failed to create user role ".$role['role']." ".$this->userman->errmsg);
				}
				else {
					$ou->addRole($x);
				}						
			}
			return $ou;
		}
		if(!isset($params['name'])){
			$email_bits = explode("@", $params['email']);
			$params['name'] = $email_bits[0];
		}
		if(!isset($params['status'])){
			$params['status'] = $this->getServiceSetting("add_user_status", "accept");
		}
		if(!isset($params['profile'])){
			$params['profile'] = $this->getServiceSetting('default_profile', array());
		}		
		$u = $this->userman->addUser($params['email'], $params['name'], $params['password'], $params['status'], json_encode($params['profile'], true));
		if($u){
			if(isset($params['roles'])){
				foreach($params['roles'] as $role){
					$x = $this->userman->createUserRole($u->id, $role['collection_id'], $role['dataset_id'], $role['role'], $role['level']);
					if(!$x){
						$this->logEvent("warning", $this->userman->errcode, "Failed to create user role ".$role['role']." ".$this->userman->errmsg);
					}
					else {
						$u->addRole($x);
					}
				}
			}
			return $u;
		}	
		return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
	}
	
	function canUpdateUserB($ub){
		$ua = $this->getUser();
		if(!$ua){
			return $this->failure_result("User not logged in", 401);
		}
		if($ua->id == $ub->id) return true;
		if($ua->isGod() || $ua->hasCollectionRole("all", "admin")) return true;
		if($ua->hasCollectionRole($this->cid(), "admin") && $this->userIsOwnedByCollection($ub, $this->cid())) return true;
		return $this->failure_result($ua->id." does not have a role that will allow updating of user $ub->id", 401);
		//1 a is god / system admin
		//2 a is collection x admin 
		//	 & b is "owned by" collection x
		//3 a is b
	}
	
	function userIsOwnedByCollection($u, $cid){
		return (isset($u->profile['owned_by_collection']) && $u->profile['owned_by_collection'] == $cid);
	}
	/*
	 * Returns a datastructure representing the datasets and contexts that the user has at least the given role in  
	 * (or any role if false) 
	 * collection_id: {title: "title", datasets: {dataset_id => "dataset title"}}
	 */
	function getContextsWithUserRole($role=false){
		$u = $this->getUser();
		if($u->hasCollectionRole("all", $role)){
			$choices["all"] = array("title" => "All collections", "datasets" => array("all" => "All Datasets"));				
		}
		$cols = $this->getCollectionList();
		foreach($cols as $colid => $col){
			if($col->status == "deleted"){ continue;}
			if($u->hasCollectionRole($colid, $role)){
				$choices[$colid] = array("title" => $col->name, "datasets" => array("all" => "All Datasets"));
				foreach($col->datasets as $datid => $ds){
					$choices[$colid]["datasets"][$datid] = $ds->name;
				}
			}
			else {
				//opr($u);
				$datasets = array();
				foreach($col->datasets as $datid => $ds){
					if($ds->status != "deleted" && $u->hasDatasetRole($colid, $datid, $role)){
						$datasets[$datid] = $ds->name;
					}
				}
				$choices[$colid] = array("title" => $col->name, "datasets" => $datasets);
			}
		}
		return $choices;
	}
	
	
	/*
	 * Returns an a structure representing datasets and contexts that can be given to a user in a certain context..
	 */
	function getRoleContextOptions($uid, $cid = false, $did = false){
		$cid = ($cid === false) ? $this->ucontext->getCollectionID() : $cid;
		$did = ($did === false) ? $this->ucontext->getDatasetID() : $did;
		$choices = $this->getContextsWithUserRole("admin");
		//opr($choices);
		if($cid == "all"){
			return $choices;
		}
		else {
			if(!isset($choices[$cid])){
				return $this->failure_result("User does not possess permission to create roles for $uid in [$cid / $did] context", 401);
			}
			if($did == "all"){
				return array($cid => $choices[$cid]);
			}
			else {
				if(!isset($choices[$cid]["datasets"][$did])){
					return array($cid => array());
					$this->failure_result("User does not possess permission to create roles for $uid in [$cid / $did] context", 401);
				}
				$choices[$cid]["datasets"] = array($did => $choices[$cid]["datasets"][$did]);
				return array($cid => $choices[$cid]);
			}
		}
	}
	
	function getRoleCollectionOptions($uid){
		$admin = $this->getUser(0);
		$colls = array();
		if($admin->isGod()){
			$colls = $this->getCollectionList(false);
			$colls[0] = array("id" => "all", "name" => "All Collections");
		}
		else {
			foreach($admin->roles as $role){
				if($role->isAdmin() && !isset($colls[$role->collection_id])){
					$colls[$role->collection_id] = $this->getCollection($role->collection_id);
				}
			}
		}
		return $colls;
	}
	
	
	function getRoleDatasetOptions($cid, $uid){
		$admin = $this->getUser(0);
		$dss = array();
		if($admin->isCollectionAdmin($cid)){
			$dss = $this->dbman->getCollectionDatasets($cid);
			$dss[0] = array("id" => "all", "name" => "All datasets");
		}
		else {
			foreach($admin->roles as $role){
				if($role->isAdmin() && $role->collection_id == $cid && !isset($dss[$role->dataset_id])){
					$dss[$role->dataset_id] = $this->getDataset($role->dataset_id);
				}
			}
		}
		return $dss;
	}
	
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
					elseif($ou->hasSufficientRole($role, $this->cid(), $this->did())){
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
	
	function inviteUser($user, $role, $message){
		if($confirm_code = $this->userman->invite($user, $role, $this->cid(), $this->did())){
			$address =  $this->durl()."system/login/invite/code/".$confirm_code;
			ob_start();
			include_once("screens/invite_email.php");
			$output = ob_get_contents();
			ob_clean();
			$content = $message . $output;
			sendemail($user, $this->getServiceSetting('invite_email_subject', "Invitation to join Data Curation Project"), $content, $this->getSystemSetting('mail_headers',""));
			return true;
		}
		else {
			return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
		}
	}

	function processInviteList($invite_list, $role, $message){
		$results = $this->getInviteErrorReport($invite_list);
		foreach(array("unknown", "pending", "active") as $itype){
			foreach($invite_list[$itype] as $new_user){
				if($itype == "active"){
					if($this->userman->inviteToCollection($new_user, $role, $this->cid(),$this->did())){
						$results['issued'][$new_user] = "Existing Dacura user invited to join collection";
					}
					else {
						$results['failed'][$new_user] = $this->userman->errcode.": ".$this->userman->errmsg;						
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
	
	function inviteListContainsValidEntries($invite_list){
		foreach(array("unknown", "active", "pending") as $itype){
			if(count($invite_list[$itype]) > 0) return true;
		}
		return false;
	}
	
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
