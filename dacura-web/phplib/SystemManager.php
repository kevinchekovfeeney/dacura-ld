<?php


class SystemManager {
	
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
			$stmt = $this->link->prepare("SELECT name, email, status FROM users where id=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch();
			if(!$row){
				$this->errmsg = "User with id $id does not exist in this system";
				return false;
			}
			$du = new DacuraUser($id, $row['email'], $row['name'], $row['status']);
			$roles = $this->loadUserRoles($id);
			$du->roles = $roles;
			return $du;
	
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
			$stmt = $this->link->prepare("UPDATE users set name=?, email=?, status=? where id=?");
			$stmt->execute(array($u->name, $u->email, $u->status, $u->id));
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
			$stmt = $this->link->prepare("SELECT id, name, status FROM users where email=?");
			$stmt->execute(array($email));
			$row = $stmt->fetch();
			if(!$row || !$row['id']){
				$this->errmsg = "User with email $email does not exist in this system";
				return false;
			}
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status']);
			return $du;
				
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	
	function addUser($email, $name, $p, $status){
		if($email && $this->hasUser($email)){
			$this->errmsg = "User with email $email already exists";
			return false;
		}
		else {
			try {
				$stmt = $this->link->prepare("INSERT INTO users VALUES(0, ?, ?, PASSWORD(?), ?)");
				$res = $stmt->execute(array($email, $name, $p, $status));
				$id = $this->link->lastInsertId();
				$du = new DacuraUser($id, $email, $name, $p, $status);
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
			$stmt = $this->link->prepare("SELECT id, name, status FROM users where email=? AND password=PASSWORD(?)");
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
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status']);
			$roles = $this->loadUserRoles($row['id']);
			$du->roles = $roles;				
			return $du;
		
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	
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
	
	function getCollection($id){
		try {
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, contents FROM collections where collection_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['collection_id']){
					$this->errmsg = "Error in collection data $id";
					return false;
				}
				return json_decode($row[contents]);
			}
			$this->errmsg = "No such collection $id";
			return false;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving collection $id" . $e->getMessage();
			return false;
		}
	}
	
	function updateCollection($id, $new_title, $new_obj) {
		if(!$this->hasCollection($id)){
			$this->errmsg = "Collection with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET collection_title = ?, contents = ? WHERE collection_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error retrieving $email " . $e->getMessage();
			return false;
		}
	}
	
	function deleteCollection($id) {
		if(!$this->hasCollection($id)){
			$this->errmsg = "Collection with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET status = 'deleted' WHERE collection_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error deleting collection $id" . $e->getMessage();
			return false;
		}
	}
	

	function createNewCollection($id, $obj){
		if($this->hasCollection($id)){
			$this->errmsg = "Collection with ID $id already exists";
			return false;			
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO collections VALUES(?, 'title', ?, 'active')");
			$res = $stmt->execute(array($id, json_encode($obj)));
			return $obj;	
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
			$stmt = $this->link->prepare("SELECT dataset_id, dataset_name, collection_id, contents FROM collections where dataset_id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['dataset_id']){
					$this->errmsg = "Error in dataset data $id";
					return false;
				}
				return json_decode($row[contents]);
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
			$stmt = $this->link->prepare("UPDATE datasets SET dataset_title = ?, contents = ? WHERE dataset_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error updating dataset $id " . $e->getMessage();
			return false;
		}
	}
	
	function deleteDataset($id) {
		if(!$this->hasDataset($id)){
			$this->errmsg = "Dataset with ID $id does not exist";
			return false;
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET status = 'deleted' WHERE dataset_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			$this->errmsg = "error deleting dataset $id" . $e->getMessage();
			return false;
		}
	}
	
	
	function createNewDataset($id, $cid, $obj){
		if($this->hasDataset($id)){
			$this->errmsg = "Dataset with ID $id already exists";
			return false;
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO datasets VALUES(?, 'title', ?, ?, 'active')");
			$res = $stmt->execute(array($id, $cid, json_encode($obj)));
			return $obj;
		}
		catch(PDOException $e){
			$this->errmsg = "Failed to create dataset $id" . $e->getMessage();
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