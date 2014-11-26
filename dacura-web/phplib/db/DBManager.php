<?php
/*
 * Class responsible for common interactions with the Dacura SQL database. 
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


include_once('phplib/Collection.php');
include_once('phplib/Dataset.php');

class DBManager {
	
	var $link;
	var $errmsg;
	
	function __construct($h, $u, $p, $n){
		$dsn = "mysql:host=$h;dbname=$n;charset=utf8";
		$this->link = new PDO($dsn, $u, $p, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT));
		//mysql_connect($h, $u, $p);
	}
	
	function hasLink(){
		return $this->link;
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
			$this->errmsg = "error retrieving user $email" . $e->getMessage();
			return false;
		}		
	}

	function loadUser($id){
		try {
			$stmt = $this->link->prepare("SELECT name, email, status, profile FROM users where id=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch();
			if(!$row){
				$this->errmsg = "User with id $id does not exist in this system";
				return false;
			}
			echo $row['profile'] . "is the profile";
			$du = new DacuraUser($id, $row['email'], $row['name'], $row['status'], json_decode($row['profile'], true));
			$roles = $this->loadUserRoles($id);
			$du->roles = $roles;
			return $du;
	
		}
		catch(PDOException $e){
			$this->errmsg = "error loading user $id " . $e->getMessage();
			return false;
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
			$this->errmsg = "error loading user $id " . $e->getMessage();
			return false;
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
			$this->errmsg = "error loading roles for user $id " . $e->getMessage();
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
			$this->errmsg = "error saving $u->email " . $e->getMessage();
			return false;
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
			$this->errmsg = "error update roles " . $e->getMessage();
			return false;
		}
	}
	
	function loadUserByEmail($email){
		try {
			$stmt = $this->link->prepare("SELECT id, name, status, profile FROM users where email=?");
			$stmt->execute(array($email));
			$row = $stmt->fetch();
			if(!$row || !$row['id']){
				$this->errmsg = "User with email $email does not exist in this system";
				return false;
			}
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status'], json_decode($row['profile'], true));
			return $du;
				
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	
	function addUser($email, $name, $p, $status, $prof = "{}"){
		if($email && $this->hasUser($email)){
			$this->errmsg = "User with email $email already exists";
			return false;
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
				$this->errmsg = "PDO Error".$e->getMessage();
				return false;
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
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
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
			$this->errmsg = "No valid confirmation code entry found for $type $code";
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
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
			$this->errmsg = "No valid confirmation code entry found for $type $uid";
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
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
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
		
	}
	
	function testLogin($email, $pword){
		try {
			$stmt = $this->link->prepare("SELECT id, name, status, profile FROM users where email=? AND password=PASSWORD(?)");
			$stmt->execute(array($email, $pword));
			$row = $stmt->fetch();
			if(!$row || !$row['id']){
				$this->errmsg = "Incorrect Username / Password combination";
				return false;
			}
			if($row['status'] == "unconfirmed"){
				$this->errmsg = "User $email has been registered but has not yet confirmed their email address.";
				return false;
			}
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status'], json_decode($row['profile'], true));
			$roles = $this->loadUserRoles($row['id']);
			$du->roles = $roles;				
			return $du;
		
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	
	/*
	 * Collection / Dataset Config related functions
	 */
	
	function hasCollection($id){
		try {
			$stmt = $this->link->prepare("SELECT * FROM collections where collection_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving collection $id" . $e->getMessage();
			return false;
		}
	}
	
	function getCollection($id, $load_ds = true){
		try {
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections where collection_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['collection_id']){
					$this->errmsg = "Error in collection data $id";
					return false;
				}
				$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents']), $row['status']);
				if($load_ds){
					$ds = $this->getCollectionDatasets($id);
					if($ds !== false){
						$x->setDatasets($ds);
					}
					else {
						return false;
					}
				}
				return $x;
			}
			$this->errmsg = "No such collection $id";
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving collection $id" . $e->getMessage();
			return false;
		}
	}
	
	function getCollectionList($load_ds = true){
		try {
			$cols = array();
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections");
			$stmt->execute(array());
			if($stmt->rowCount()) {
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						$this->errmsg = "Error in collection data $id";
						return false;
					}
					$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents']), $row['status']);
					if($load_ds){
						$ds = $this->getCollectionDatasets($row['collection_id']);
						if($ds !== false){
							$x->setDatasets($ds);
						}
						else {
							return false;
						}
					}
					$cols[$row['collection_id']] = $x;
					//array("name" => $row['collection_name'], "config" => json_decode($row['contents']));					
				}
				return $cols;
			}
			$this->errmsg = "Failed to retrieve collectiong listing";
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving collection $id" . $e->getMessage();
			return false;
		}
	}
	
	
	function getCollectionDatasets($cid){
		try {
			$stmt = $this->link->prepare("SELECT dataset_id, dataset_name, collection_id, status, contents FROM datasets where collection_id=?");
			$stmt->execute(array($cid));
			if($stmt->rowCount()) {
				$dss = array();
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						$this->errmsg = "Error in collection $id dataset";
						return false;
					}
					$dss[$row['dataset_id']] = new Dataset($row['dataset_id'],  $row['dataset_name'], json_decode($row['contents']), $row['status'], $row['collection_id']);
				}
				return $dss;
			}
			else return array();
		}
		catch(PDOException $e){
			$this->errmsg = "error fetching datasets for collection $cid" . $e->getMessage();
			return false;
		}
	}
	
	function updateCollection($id, $new_title, $new_obj) {
		if(!$this->hasCollection($id)){
			$this->errmsg = "Collection with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET collection_name = ?, contents = ? WHERE collection_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	

	function hasDataset($id){
		try {
			$stmt = $this->link->prepare("SELECT * FROM datasets where dataset_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving dataset $id" . $e->getMessage();
			return false;
		}
	}	

	function getDataset($id){
		try {
			$stmt = $this->link->prepare("SELECT dataset_id, dataset_name, collection_id, status, contents FROM datasets where dataset_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['dataset_id']){
					$this->errmsg = "Error in dataset data $id";
					return false;
				}
				return new Dataset($row['dataset_id'], $row['dataset_name'], json_decode($row['contents']), $row['status'], $row['collection_id']);
			}
			$this->errmsg = "No such dataset $id";
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving collection $id" . $e->getMessage();
			return false;
		}
	}
	
	function updateDataset($id, $new_title, $new_obj) {
		if(!$this->hasDataset($id)){
			$this->errmsg = "Dataset with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET dataset_name = ?, contents = ? WHERE dataset_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error updating dataset $id " . $e->getMessage();
			return false;
		}
	}
	
	
	
	/**
	 * generic call.  
	 */
	function doSelect($dummy, $vars){
		try {
			$stmt = $this->link->prepare($dummy);
			$stmt->execute($vars);
			$results = array();
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$results[] = $row;
			}
			return $results;
		
		}
		catch(PDOException $e){
			$this->errmsg = "error selecting: $dummy" . $e->getMessage();
			return false;
		}
	}
	
	
	
}