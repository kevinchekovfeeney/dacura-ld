<?php
include_once("phplib/db/UsersDBManager.php");

class UsersDacuraServer extends DacuraServer {
	
	var $dbclass = "UsersDBManager";
	
	function getUsersInContext($cid, $did){
		//first figure out which cids to use for the given active user...
		$u = $this->getUser(0);
		$cids = array();
		$dids = array();
		
		if(!$cid && !$did){
			//we are in top level context....
			//1. get all collections where u has admin rights
			//2. get all datasets where u has admin rights...
			//all users with roles in either 1) or 2) are returned....
			if($u->isGod()){
				return $this->getusers();
			}
			else {
				$cids = $u->getAdministeredCollections();
				$dids = $u->getAdministeredDatasets();
				if(count($cids) == 0 && count($dids) == 0){
					//false
				}
				$uids  = $this->dbman->getUsersInContext($cids, $dids);
			}
		}
		elseif(!$did){
			//we are in a collection level context
			//if u has admin rights...
			if($u->isGod() || $u->isCollectionAdmin($cid)){
				$uids  = $this->dbman->getUsersInContext(array($cid), array());
			}
			else {
				$dids = $u->getAdministeredDatasets($cid);
				if(count($dids) == 0){
					//false;
				}
				$uids  = $this->dbman->getUsersInContext(array(), $dids);
			}
		}
		else {
			if($u->isGod() or $u->isCollectionAdmin($cid) or $u->isDatasetAdmin($did)){
				$uids =  $this->dbman->getUsersInContext(array(), array($did));
			}
			else {
				return false;//error
			}
			//we are in a dataset level context			
		}
		$users = array();
		foreach($uids as $id){
			$users[] = $this->getUser($id);
		}
		return $users;
	}
	
	function getRoleContextOptions($uid, $cid, $did){
		$choices = $this->getUserAvailableContexts("admin", true);
		if($cid && $did){
			if(isset($choices[$cid]) && isset($choices[$cid]['datasets'][$did])){
				$choices = array($cid => array("title" => $choices[$cid], "datasets" => array($did => $choices[$cid]['datasets'][$did])));
			}
			else {
				return $this->failure_result("User $u->id does not possess permission to create roles for $uid in [$cid / $did] context", 401);
			} 
		}
		elseif($cid){
			if(isset($choices[$cid])){
				$choices = array($cid => $choices[$cid]);
			}
			else {
				return $this->failure_result("User $u->id does not possess permission to create roles for $uid in [$cid] context", 401);
			}
		}
		return $choices;
	}
	
	function getRoleCollectionOptions($uid){
		$admin = $this->getUser(0);
		$colls = array();
		if($admin->isGod()){
			$colls = $this->getCollectionList(false);
			$colls[0] = array("id" => "0", "name" => "All Collections");
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
			$dss[0] = array("id" => "0", "name" => "All datasets");
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
	/*
	
	function getUserAdminContext($uid, $cid, $did){
		$admin = $this->getUser(0);
		$cols = array();
		if($cid){
			$col = $this->getCollection($cid);
			if($did){
				if(isset($col->datasets[$did])){
					$col->datasets = array($col->datasets[$did]);
					$cols[$cid] = $col;
				}	
			}
			else {
				if(!$admin->isCollectionAdmin($cid)){
					foreach($col->datasets as $dsid => $ds){
						if(!$admin->isDatasetAdmin($dsid)){
							unset($col->datasets[$dsid]);
						}		
					}
					$cols[$cid] = $col;			
				}
			}
		}
		else {
				$all_colls = $this->getCollectionList(false);
				foreach($all_colls as $colid => $col){
					if($admin->hasAdminRoleinCollection($colid)){
						$cols[$colid] = $col;
				}
			}
			else {
				foreach($admin->roles as $role){
					if($role->isAdmin() && !isset($cols[$role->collection_id])){
						$cols[$role->collection_id] = $this->getCollection($role->collection_id);
					}
				}
			}
			//cols = getAllCollectionsWhereImAnAdmin();
		}
		if($t == "collection"){
		
		}
		$options = array();
		if(!$cid || $cid == "0"){
			//get all of the collections that the admin has rights over. 
			
		}
		else {
			$options[$cid] = array();
		}
		if(!$did or $did == "0"){
			foreach($options as $c => $val){
				//get all the datasets that the admin has rights over
				$options[$c][] = false;
			}
		}
		else {
			$options[$cid] = array($did => array());
		}
		foreach($options as $cid => $ds){
			foreach($ds as $did => $val){
				$options[$cid][$did] = $this->getAvailableRoles($uid, $cid, $did);
			}
		}
	}*/
	
	function getAvailableRoles($uid, $cid, $did){
		return array("admin", "architect", "harvester", "expert", "user");		
	}
	
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
		foreach($u->roles as $i => $role){
			if($role->id == $rid){
				unset($u->roles[$i]);
				if(!$this->dbman->updateUserRoles($u)){
					return $this->failure_result("Failed to delete $rid role for $uid", 500);
				}
				return $role;
			}
		}
		return $this->failure_result('User $uid Role $rid did not exist' . $e->getMessage(), 500);
	}

	function createUserRole($uid, $cid, $did, $role, $level){
		$u = $this->getUser($uid);
		$u->addRole(new UserRole(0, $cid, $did, $role, $level));
		//$u->roles[] = new UserRole(0, $id, 0, 'admin', 99);
		if(!$this->dbman->updateUserRoles($u)){
			return $this->failure_result("Failed to create new roles for $id collection", 500);
		}
		return $u;
	}
	
}

class UsersDacuraAjaxServer extends UsersDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}