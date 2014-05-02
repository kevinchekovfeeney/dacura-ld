<?php
include_once("phplib/DacuraServer.php");
include_once("UsersSystemManager.php");

class UsersDacuraServer extends DacuraServer {
	
	function __construct($dacura_settings){
		$this->settings = $dacura_settings;
		try {
			$this->sysman = new UsersSystemManager($this->settings['db_host'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_name']);
		}
		catch (PDOException $e) {
			return $this->failure_result('Connection failed: ' . $e->getMessage(), 500);
		}
		$this->sm = new UserManager($this->sysman, $this->settings);
	}
	
	function getUsersInContext($cid, $did){
		return $this->getusers();
	}
	
	function getUserRoleOptionsInContext($uid, $t, $cid, $did){
		if($t == "0" or !$t or $t == 'collection'){
			return $this->getRoleCollectionOptions($uid);
		}
		else {
			return $this->getRoleDatasetOptions($t, $uid);
		}
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
			$dss = $this->sysman->getCollectionDatasets($cid);
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
				if(!$this->sysman->updateUserRoles($u)){
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
		if(!$this->sysman->updateUserRoles($u)){
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