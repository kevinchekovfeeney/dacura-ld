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

include_once("phplib/db/UsersDBManager.php");

class UsersDacuraServer extends DacuraServer {
	
	function getUsersInContext(){
		$x = $this->userman->getUsersInContext($this->cid(), $this->did());
		if($x) return $x;
		return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
	}
	
	function getUserPrunedForContext($uid){
		$x = $this->userman->getUserPrunedForContext($uid, $this->cid(), $this->did());
		if($x) return $x;
		return $this->failure_result($this->userman->errmsg, $this->userman->errcode);
	}
	
	function addUser($params){
		if(!isset($params['name'])){
			$email_bits = explode("@", $params['email']);
			$params['name'] = $email_bits[0];
		}
		if(!isset($params['status'])){
			$params['status'] = $this->settings['users']['default_status'];
		}
		if(!isset($params['profile'])){
			$params['profile'] = $this->settings['users']['default_profile'];
		}		
		$u = $this->userman->addUser($params['email'], $params['name'], $params['password'], $params['status'], json_encode($params['profile'], true));
		if($u){
			if(isset($params['roles'])){
				foreach($roles as $role){
					$x = $this->userman->createUserRole($u->id, $role['collection_id'], $role['dataset_id'], $role['role'], $role['level']);
					if($x){
						$u = $x;
					}
					else {
						$this->logEvent("warning", $this->userman->errcode, $this->userman->errmsg);
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
			if($col->status == "deleted"){
	
			}
			elseif($u->hasCollectionRole($colid, $role)){
				$choices[$colid] = array("title" => $col->name, "datasets" => array("all" => "All Datasets"));
				foreach($col->datasets as $datid => $ds){
					$choices[$colid]["datasets"][$datid] = $ds->name;
				}
			}
			else {
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
					return $this->failure_result("User does not possess permission to create roles for $uid in [$cid / $did] context", 401);
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

}
