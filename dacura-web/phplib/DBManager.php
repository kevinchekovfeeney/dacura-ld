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
class DBManager extends DacuraObject {
	
	var $link;
	var $extra_wheres = array();//a portion of sql that will be added to every where clause.
		
	function __construct($h, $u, $p, $n, $config = false){
		$dsn = "mysql:host=$h;dbname=$n;charset=utf8";
		$this->link = new PDO($dsn, $u, $p, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
		//mysql_connect($h, $u, $p);
		if($config && isset($config['include_deleted'])){
			
		}
		else {
			$this->extra_wheres['deleted.status'] = "TABLE.status != 'deleted'";			
		}
	}
	
	function hasLink(){
		return $this->link;
	}
	
	function where($is_only = false, $table = ""){
		$first = true;
		$clause = "";
		foreach($this->extra_wheres as $id => $ew){
			if($is_only && $first){
				$clause .= " WHERE ";
			}
			else {
				$clause .= " AND ";
			}
			$clause .= str_replace("TABLE.", $table, $ew);
			$first = false;
		}
		return $clause;
	}
	/*
	 * Collection / Dataset Config related functions
	 */
	
	function hasCollection($id){
		try {
			$stmt = $this->link->prepare("SELECT * FROM collections where collection_id=?".$this->where());
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $id " .$e->getMessage(), 500);
		}
	}

	
	function getCollection($id, $load_ds = true){
		try {
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections where collection_id=?".$this->where());
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['collection_id']){
					return $this->failure_result("Error in collection data $id ", 500);
				}
				$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents'], true), $row['status']);
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
			return $this->failure_result("Collection $id does not exist", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $id ".$e->getMessage(), 500);
		}
	}
	
	function getCollectionList($load_ds = true){
		try {
			$cols = array();
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections".$this->where(true));
			$stmt->execute(array());
			if($stmt->rowCount()) {
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						return $this->failure_result("Error in collection list", 500);
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
				}
				return $cols;
			}
			return $this->failure_result("No Collections found in list", 500);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection list ".$e->getMessage(), 500);
		}
	}
	
	
	function getCollectionDatasets($cid = false, $include_contents = true){
		$sql = "SELECT dataset_id, dataset_name, collection_id, status";
		if($include_contents) $sql .= ", contents";
		$sql .= " FROM datasets";
		$params = array();
		if($cid !== false){
			$params[] = $cid;
			$sql .= " WHERE collection_id=?".$this->where();
		}
		else {
			$sql .= $this->where(true);
		}
		try {
			$stmt = $this->link->prepare($sql);
			$stmt->execute($params);
			if($stmt->rowCount()) {
				$dss = array();
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						return $this->failure_result("Error in collection $cid dataset list", 500);
					}
					$dss[$row['dataset_id']] = new Dataset($row['dataset_id'],  $row['dataset_name'], json_decode($row['contents']), $row['status'], $row['collection_id']);
				}
				return $dss;
			}
			else return array();
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $cid dataset list ".$e->getMessage(), 500);
		}
	}
	
	function updateCollection($id, $name = false, $status = false, $new_obj = false) {
		if(!$this->hasCollection($id)){
			return $this->failure_result("update collection - Collection with ID $id does not exist", 500);
		}
		$params = array();
		if($name !== false){
			$params['collection_name'] = $name;
		}
		if($status){
			$params['status'] = $status;
		}
		if($new_obj !== false){
			$params['contents'] = json_encode($new_obj);
		}
		if(count($params) == 0){
			return $this->failure_result("Update must change something: collection $id is unchanged", 400);
		}
		$sql = "UPDATE collections SET ".implode("=?, ", array_keys($params))."=? WHERE collection_id=?";	
		$vals = array_values($params);
		$vals[] = $id;	
		try {
			$stmt = $this->link->prepare($sql);
			$res = $stmt->execute($vals);
			return $this->getCollection($id);
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating collection $id ".$e->getMessage(), 500);
		}
	}
	

	function hasDataset($id){
		try {
			$stmt = $this->link->prepare("SELECT * FROM datasets where dataset_id=?".$this->where());
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("Error checking for dataset $id ".$e->getMessage(), 500);
		}
	}	

	function getDataset($id){
		try {
			$stmt = $this->link->prepare("SELECT dataset_id, dataset_name, collection_id, status, contents FROM datasets where dataset_id=?".$this->where());
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['dataset_id']){
					return $this->failure_result("Error retrieving dataset $id - data error", 500);
				}
				return new Dataset($row['dataset_id'], $row['dataset_name'], json_decode($row['contents'], true), $row['status'], $row['collection_id']);
			}
			return $this->failure_result("Error retrieving dataset $id - no such dataset", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving dataset $id ".$e->getMessage(), 500);
		}
	}
	
	function updateDataset($id, $new_title, $new_obj) {
		if(!$this->hasDataset($id)){
			return $this->failure_result("update dataset: $id does not exist", 404);
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET dataset_name = ?, contents = ? WHERE dataset_id=?");
			$res = $stmt->execute(array($new_title, json_encode($new_obj), $id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating dataset $id ".$e->getMessage(), 500);
		}
	}
	
	function deleteCollection($id) {
		if(!$this->hasCollection($id)){
			return $this->failure_result("Collection with ID $id does not exist", 404);
		}
		try {
			$stmt = $this->link->prepare("UPDATE collections SET status = 'deleted' WHERE collection_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("error deleting collection $id" . $e->getMessage(), 500);
		}
	}
	
	function createNewCollection($id, $title, $obj, $status = "accept"){
		if($this->hasCollection($id)){
			return $this->failure_result("Collection with ID $id already exists", 400);
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO collections VALUES(?, ?, ?, ?)");
			$conf = (is_array($obj) && count($obj) > 0) ? json_encode($obj) : "{}";
			$res = $stmt->execute(array($id, $title, $conf, $status));
			return $obj;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving $email " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * When we create a new collection we also need to create its default (main) graph
	 */
	function createCollectionInitialEntities($id){
		try {
			$stmt = $this->link->prepare("INSERT INTO ld_entities
				(id, collectionid, datasetid, type, version, contents, meta, status, createtime, modtime)
				VALUES('main', '$id', 'all', 'graph', 1, ?, ?, 'pending', ?, ?)");
			$ld = json_encode(array("main" => array()));
			if(!$ld){
				return $this->failure_result("JSON encoding error: ".json_last_error() . " " . json_last_error_msg(), 500);
			}
			$meta = json_encode(array("status" => "pending"));
			if(!$meta){
				return $this->failure_result("JSON encoding error: ".json_last_error() . " " . json_last_error_msg(), 500);
			}
			$params = array($ld, $meta, time(), time());
			$res = $stmt->execute($params);
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function deleteDataset($id) {
		if(!$this->hasDataset($id)){
			return $this->failure_result("Dataset with ID $id does not exist", 404);
		}
		try {
			$stmt = $this->link->prepare("UPDATE datasets SET status = 'deleted' WHERE dataset_id=?");
			$res = $stmt->execute(array($id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("error deleting dataset $id" . $e->getMessage(), 500);
		}
	}
	
	
	function createNewDataset($id, $cid, $dtitle, $obj){
		if($this->hasDataset($id)){
			return $this->failure_result("Dataset with ID $id already exists", 400);
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO datasets VALUES(?, ?, ?, ?, 'active')");
			$res = $stmt->execute(array($id, $dtitle, $cid, json_encode($obj)));
			return $obj;
		}
		catch(PDOException $e){
			return $this->failure_result("Failed to create dataset $id" . $e->getMessage(), 500);
		}
	}
	
	
/*
 * User management
 * 
 */	

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
			$stmt = $this->link->prepare("SELECT * FROM users where email=?".$this->where());
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
			$stmt = $this->link->prepare("SELECT name, email, status, profile FROM users where id=?".$this->where());
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
			$stmt = $this->link->prepare("SELECT id FROM users".$this->where(true));
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
			$stmt = $this->link->prepare("SELECT id, name, status, profile FROM users where email=?".$this->where());
			$stmt->execute(array($email));
			$row = $stmt->fetch();
			if(!$row || !$row['id']){
				return $this->failure_result("Error loading user: User with email $email does not exist in this system", 404);
			}
			$du = new DacuraUser($row['id'], $email, $row['name'], $row['status'], json_decode($row['profile'], true));
			$roles = $this->loadUserRoles($row['id']);
			$du->roles = $roles;
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