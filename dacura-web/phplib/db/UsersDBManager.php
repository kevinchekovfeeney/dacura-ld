<?php
/*
 * Class providing DB interface to User-related state information
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */

include_once("DBManager.php");

class UsersDBManager extends DBManager {
	
	function getUsersInContext($cids, $dids){
		try {
			$uids = array();
			$sql = "SELECT distinct userid AS uid from user_roles";				
			if(count($cids) > 0){
				$inQuery = implode(',', array_fill(0, count($cids), '?'));
				$stmt = $this->link->prepare($sql. " WHERE collectionid IN($inQuery)");
				$stmt->execute($cids);
				$uids = $stmt->fetchAll(PDO::FETCH_COLUMN);
			}
			if(count($dids) > 0){
				$inQuery = implode(',', array_fill(0, count($dids), '?'));
				$stmt = $this->link->prepare($sql. " WHERE datasetid IN($inQuery)");
				$stmt->execute($dids);
				$duids = $stmt->fetchAll(PDO::FETCH_COLUMN);
				foreach($duids as $duid){
					if(!in_array($duid, $uids)){
						$uids[] = $duid;
					}
				}
			}
			return $uids;
		}
		catch(PDOException $e){
			return $this->failure_result("error getting users ".$e->getMessage(), 500);
		}
	}
	
	function hasUser($email){
		try {
			$stmt = $this->link->prepare("SELECT * FROM users where email=?");
			$stmt->execute(array($email));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("Error searching for user $email" . $e->getMessage(), 500);
		}
	}
	
	function loadUser($id){
		try {
			$stmt = $this->link->prepare("SELECT name, email, status, profile FROM users where id=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch();
			if(!$row){
				return $this->failure_result("User with id $id does not exist in this system", 404);
			}
			$prof = json_decode($row['profile'], true);
			if($prof === NULL){
				return $this->failure_result("Failed to parse profile JSON of user $id", 500);
			}
			$du = new DacuraUser($id, $row['email'], $row['name'], $row['status'], $prof);
			$roles = $this->loadUserRoles($id);
			$du->roles = $roles;
			return $du;
	
		}
		catch(PDOException $e){
			return $this->failure_result("Error loading user $id " . $e->getMessage(), 500);
		}
	}
	
	function loadUsers(){
		$users = array();
		try {
			$stmt = $this->link->prepare("SELECT id FROM users");
			$stmt->execute(array());
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$u = $this->loadUser($row['id']);
				if($u){
					$users[$row['id']] = $u;
				}
			}
			return $users;
		}
		catch(PDOException $e){
			return $this->failure_result("Error loading user list" . $e->getMessage(), 500);
		}
	}
	
	function loadUserRoles($id){
		try {
			$stmt = $this->link->prepare("SELECT roleid, datasetid, collectionid, role, level FROM user_roles where userid=?");
			$stmt->execute(array($id));
			$roles = array();
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$roles[] = new UserRole($row['roleid'], $row['collectionid'], $row['datasetid'], $row['role'], $row['level']);
			}
			return $roles;
	
		}
		catch(PDOException $e){
			$this->failure_result("Error loading roles for user $id " . $e->getMessage(), 500);
			return array();
		}
	}
	
	
	function saveUser($u){
		try {
			$stmt = $this->link->prepare("UPDATE users set name=?, email=?, status=?, profile=? where id=?");
			$stmt->execute(array($u->name, $u->email, $u->status, json_encode($u->profile), $u->id));
			return true;
	
		}
		catch(PDOException $e){
			return $this->failure_result("Error saving $u->email " . $e->getMessage(), 500);
		}
	}
	
	function updateUserRoles(&$u){
		try {
			$stmt = $this->link->prepare("DELETE FROM user_roles where userid=?");
			$stmt->execute(array($u->id));
			foreach($u->roles as $r){
				$stmt = $this->link->prepare("INSERT INTO user_roles VALUES(0, ?, ?, ?, ?, ?)");
				$stmt->execute(array($u->id, $r->dataset_id, $r->role, $r->level, $r->collection_id));
			}
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating roles for user $u->email " . $e->getMessage(), 500);
		}
	}
	
	function deleteRole($rid){
		try {
			$stmt = $this->link->prepare("DELETE FROM user_roles where roleid=?");
			$stmt->execute(array($rid));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating roles for user $u->email " . $e->getMessage(), 500);
		}		
	}
	
	function loadUserByEmail($email){
		try {
			$stmt = $this->link->prepare("SELECT id, name, status, profile FROM users where email=?");
			$stmt->execute(array($email));
			$row = $stmt->fetch();
			if(!$row || !$row['id']){
				return $this->failure_result("Error loading user: User with email $email does not exist in this system", 404);
			}
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status'], json_decode($row['profile'], true));
			return $du;
		}
		catch(PDOException $e){
			return $this->failure_result("Error loading user $email  " . $e->getMessage(), 500);
		}
	}
	
	function addUser($email, $name, $p, $status, $prof = "{}"){
		if($email && $this->hasUser($email)){
			return $this->failure_result("Cannot add user: User with email $email already exists", 400);
		}
		else {
			try {
				$stmt = $this->link->prepare("INSERT INTO users VALUES(0, ?, ?, PASSWORD(?), ?, ?)");
				$res = $stmt->execute(array($email, $name, $p, $status, $prof));
				$id = $this->link->lastInsertId();
				$du = new DacuraUser($id, $email, $name, $status, json_decode($prof, true));
				return $du;
			}
			catch(PDOException $e){
				return $this->failure_result("Error adding user $email  " . $e->getMessage(), 500);
			}
		}
	}
	
	
	function generateUserConfirmCode($uid, $type, $purge = false){
		try {
			if($purge){
				$stmt = $this->link->prepare("DELETE FROM user_confirms WHERE type=? AND uid=?");
				$stmt->execute(array($type, $uid));
			}
			$code = createRandomKey(50);
			$stmt = $this->link->prepare("INSERT INTO user_confirms VALUES(0, ?, ?, ?, NOW())");
			$res = $stmt->execute(array($uid, $type, $code));
			return $code;
		}
		catch(PDOException $e){
			return $this->failure_result("Error generate user confirm code " . $e->getMessage(), 500);
		}
	}
	
	function getConfirmCodeUid($code, $type, $tlimit = 0){
		try {
			$stmt = $this->link->prepare("SELECT uid FROM user_confirms where code=? AND type=? AND issued > ?");
			$stmt->execute(array($code, $type, $tlimit));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				return $row['uid'];
			}
			return $this->failure_result("No valid confirmation code entry found for $type $code", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error getting user confirm code user " . $e->getMessage(), 500);
		}
	}
	
	function getUserConfirmCode($uid, $type, $tlimit = 0){
		try {
			$stmt = $this->link->prepare("SELECT code FROM user_confirms where uid=? AND type=? AND issued > ?");
			$stmt->execute(array($uid, $type, $tlimit));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				return $row['code'];
			}
			return $this->failure_result("No valid confirmation code entry found for $type $uid", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error getting user confirm code for user $uid " . $e->getMessage(), 500);
		}
	}
	
	function updatePassword($uid, $p, $purge = false){
		try {
			if($purge){
				$stmt = $this->link->prepare("DELETE FROM user_confirms WHERE type='lost' AND uid=?");
				$stmt->execute(array($uid));
			}
			$stmt = $this->link->prepare("UPDATE users set password=PASSWORD(?) WHERE id=?");
			$res = $stmt->execute(array($p, $uid));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function testLogin($email, $pword){
		try {
			$stmt = $this->link->prepare("SELECT id, name, status, profile FROM users where email=? AND password=PASSWORD(?)");
			$stmt->execute(array($email, $pword));
			$row = $stmt->fetch();
			if(!$row || !$row['id']){
				return $this->failure_result("Incorrect Username / Password combination", 401);
			}
			if($row['status'] == "unconfirmed"){
				return $this->failure_result("User $email has been registered but has not yet confirmed their email address.", 400);
			}
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status'], json_decode($row['profile'], true));
			$roles = $this->loadUserRoles($row['id']);
			$du->roles = $roles;
			return $du;
	
		}
		catch(PDOException $e){
			return $this->failure_result("Error logging in $email " .$e->getMessage(), 500);
		}
	}
	
	
}

