<?php
/**
 * Class responsible for interacting with the Dacura SQL database. 
 * 
 * This and the classes that extend it is the one and only place where SQL interactions are kept 
 * * Creation Date: 20/11/2014
 * 
 * @author chekov
 * @license GPL v2
 */
class DBManager extends DacuraController {
	/** @var PDO the PHP Data Object that stores the connection to the database */
	var $link;
	/** @var an array of clauses that are added to the where clause of all select statements */
	var $extra_wheres = array();//a portion of sql that will be added to every where clause.
		
	/**
	 * Creates a connectoin to the Mysql database
	 * @param string $h hostname
	 * @param string $u username
	 * @param string $p password
	 * @param string $n database name
	 * @param array $config array of configuration variables (e.g. include_deleted)
	 */
	function connect($h, $u, $p, $n, $config = false){
		$dsn = "mysql:host=$h;dbname=$n;charset=utf8";
		$this->link = new PDO($dsn, $u, $p, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
		if(!($config && isset($config['include_deleted']) && $config['include_deleted'])){
			$this->extra_wheres['deleted.status'] = "TABLE.status != 'deleted'";			
		}
	}
	
	/**
	 * Has this object connected to the DB
	 * @return PDO
	 */
	function hasLink(){
		return $this->link;
	}
	
	/**
	 * Embellish the where clause by adding further clauses
	 * @param boolean $is_only true if there are no other where sub-clauses
	 * @param string $table if is set this table name will be added to the name of columns 
	 * Supports situation where there are columns in the select with the same name and different tables
	 * @return string the extra where clause
	 */
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
	
	/* Collection Config related functions */
	
	/**
	 * Does the collection with the given id exist?
	 * @param string $id
	 * @param boolean $supress_where suppress the normal where filters (for checking against deleted primary keys)
	 * @return boolean
	 */
	function hasCollection($id, $supress_where = false){
		try {
			if($supress_where){
				$stmt = $this->link->prepare("SELECT * FROM collections where collection_id=?");				
			}
			else {
				$stmt = $this->link->prepare("SELECT * FROM collections where collection_id=?".$this->where());				
			}
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

	/**
	 * Get the collection object from db
	 * @param string $id collection id
	 * @return Collection
	 */
	function getCollection($id){
		try {
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections where collection_id=?".$this->where());
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				if(!$row || !$row['collection_id']){
					return $this->failure_result("Error in collection data $id ", 500);
				}
				$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents'], true), $row['status']);
				return $x;
			}
			return $this->failure_result("Collection $id does not exist", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error retrieving collection $id ".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Returns the list of all collections in the system
	 * @return array<string:Collection> array of collections, indexed by their ids
	 */
	function getCollectionList(){
		try {
			$cols = array();
			$stmt = $this->link->prepare("SELECT collection_id, collection_name, status, contents FROM collections".$this->where(true));
			$stmt->execute(array());
			if($stmt->rowCount()) {
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					if(!$row || !$row['collection_id']){
						return $this->failure_result("Error in collection list", 500);
					}
					$x = new Collection($row['collection_id'], $row['collection_name'], json_decode($row['contents'], true), $row['status']);
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

	/**
	 * Update the stored version of a collection
	 * @param string $id collection id
	 * @param string $name collection title
	 * @param string $status one of @see DacuraObject::valid_statuses
	 * @param array $new_obj the contents of the collection object (name-value array)
	 * @return boolean|Collection the updated collection id, or false if failure
	 */
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
	
	/**
	 * Deletes a collection (sets its status to deleted, does not actually delete it)
	 * @param string $id
	 * @return boolean true if successful
	 */
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
	
	/**
	 * Creates a new collection in the system
	 * @param string $id collection id
	 * @param string $title collection title
	 * @param array $obj configuration name-value array
	 * @param string $status dacura status code @see DacuraObject::valid_statuses
	 * @return boolean|Collection
	 */
	function createNewCollection($id, $title, $obj, $status = "accept"){
		if($this->hasCollection($id, true)){
			return $this->failure_result("Collection with ID $id already exists", 400);
		}
		try {
			$stmt = $this->link->prepare("INSERT INTO collections VALUES(?, ?, ?, ?)");
			$conf = (is_array($obj) && count($obj) > 0) ? json_encode($obj) : "{}";
			$res = $stmt->execute(array($id, $title, $conf, $status));
			$col = new Collection($id, $title, $obj, $status);
			return $col;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving $email " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * When we create a new collection we also need to create its default (main) graph
	 * @param string $id collection id
	 * @return boolean true if successful
	 */
	function createCollectionInitialEntities($id){
		try {
			$stmt = $this->link->prepare("INSERT INTO ld_entities
				(id, collectionid, type, version, contents, meta, status, createtime, modtime)
				VALUES('main', '$id', 'graph', 1, ?, ?, 'pending', ?, ?)");
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
	
/* User management functions */	
	
	/**
	 * Get the list of users who have roles in the given collection context
	 * @param string[] $cids an array of collection ids
	 * @return string[] an array of user ids of users who have roles in the collections
	 */
	function getUsersInContext(array $cids){
		try {
			$uids = array();
			$sql = "SELECT distinct userid AS uid from user_roles";
			if(count($cids) > 0){
				$inQuery = implode(',', array_fill(0, count($cids), '?'));
				$stmt = $this->link->prepare($sql. " WHERE collectionid IN($inQuery)");
				$stmt->execute($cids);
				$uids = $stmt->fetchAll(PDO::FETCH_COLUMN);
			}
			$users = array();
			foreach($uids as $uid){
				$u = $this->loadUser($uid);
				if($u){
					$users[$uid] = $u;
				}				
			}
			return $users;
		}
		catch(PDOException $e){
			return $this->failure_result("error getting users ".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Does a user with the given email exist?
	 * @param string $email
	 * @return boolean
	 */	
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
	
	/**
	 * Load a user object 
	 * @param string $id user id
	 * @return boolean|DacuraUser
	 */
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
			if($roles === false){
				return false;
			}
			$du->roles = $roles;
			return $du;
	
		}
		catch(PDOException $e){
			return $this->failure_result("Error loading user $id " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * Loads the full list of users in the system
	 * @return array<string:DacuraUser> an array of users indexed by their ids
	 */
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
	
	/**
	 * Load the roles for the given user
	 * @param string $id
	 * @return UserRole[] an array of roles 
	 */
	function loadUserRoles($id){
		try {
			$stmt = $this->link->prepare("SELECT roleid, collectionid, role FROM user_roles where userid=?");
			$stmt->execute(array($id));
			$roles = array();
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$roles[] = new UserRole($row['roleid'], $row['collectionid'], $row['role']);
			}
			return $roles;
		}
		catch(PDOException $e){
			return $this->failure_result("Error loading roles for user $id " . $e->getMessage(), 500);
		}
	}	
	
	/**
	 * Save user to db
	 * @param DacuraUser $u
	 * @return boolean true on success
	 */
	function saveUser(DacuraUser $u){
		try {
			$stmt = $this->link->prepare("UPDATE users set name=?, email=?, status=?, profile=? where id=?");
			$stmt->execute(array($u->name, $u->email, $u->status, json_encode($u->profile), $u->id));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("Error saving $u->handle " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * Saves a user's roles to db
	 * 
	 * Deletes all existing roles in DB and creates new roles for each role in user object
	 * @param DacuraUser $u
	 * @return boolean true on success
	 */
	function updateUserRoles(DacuraUser &$u){
		try {
			$stmt = $this->link->prepare("DELETE FROM user_roles where userid=?");
			$stmt->execute(array($u->id));
			foreach($u->roles as $r){
				$stmt = $this->link->prepare("INSERT INTO user_roles VALUES(0, ?, ?, ?)");
				$stmt->execute(array($u->id, $r->role, $r->collection_id));
			}
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("Error updating roles for user $u->email " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * Deletes a specific role 
	 * @param number $rid the role id
	 * @return boolean true on success
	 */
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
	
	/**
	 * Loads a user with a given email address
	 * @param string $email
	 * @return boolean|DacuraUser the user object
	 */
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
			if($roles === false){ return false; }
			$du->roles = $roles;
			return $du;
		}
		catch(PDOException $e){
			return $this->failure_result("Error loading user $email  " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * Creates a new Dacura User in DB
	 * @param string $email users email address
	 * @param string $name user handle
	 * @param string $p password 
	 * @param string $status status @see DacuraObject::valid_statuses
	 * @param string $prof jsonified string of user profile
	 * @return boolean|DacuraUser the new user object
	 */
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
	
	/**
	 * Updates a user's password in the DB
	 * @param number $uid user id
	 * @param string $p password
	 * @param boolean $purge if true, all existing confirm codes for user will be purged from DB
	 * @return boolean true if success
	 */
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
	
	/**
	 * Tests username and password to see if a login is valid
	 * @param string $email
	 * @param string $pword
	 * @return boolean|DacuraUser user object or false if failure
	 */
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
			if($roles === false){ return false; }				
			$du->roles = $roles;
			return $du;
	
		}
		catch(PDOException $e){
			return $this->failure_result("Error logging in $email " .$e->getMessage(), 500);
		}
	}
	
	/**
	 * Generates a new confirm code for the user and stores it in the DB
	 * @param number $uid the user id
	 * @param string $type the type of the confirm (invite, register, lost)
	 * @param boolean $purge if set to true all existing confirm codes will be deleted
	 * @return string|boolean the confirm code or false if failure
	 */
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
	
	/**
	 * Retrieve the user id associated with a given confirm code
	 * 
	 * Used to ensure that the confirmation comes from the correct user
	 * @param string $code the confirm code
	 * @param string $type the type of the confirm (invite, register, lost)
	 * @param number $tlimit the time limit that applies to this confirm code - older confirm codes are ignored
	 * @return number|boolean the id of the user or false if the code does not exist
	 */
	function getConfirmCodeUid($code, $type, $tlimit = 0){
		try {
			$stmt = $this->link->prepare("SELECT uid FROM user_confirms where code=? AND type=? AND issued > ?");
			$stmt->execute(array($code, $type, $tlimit));
			if($stmt->rowCount()) {
				$row = $stmt->fetch();
				return $row['uid'];
			}
			return $this->failure_result("The confirmation code in the link is invalid", 404);
		}
		catch(PDOException $e){
			return $this->failure_result("Error getting user confirm code " . $e->getMessage(), 500);
		}
	}
	
	/**
	 * Get the confirm code associated with a user and type from the db
	 * @param number $uid user id
	 * @param string $type the type of the confirm (invite, register, lost)
	 * @param number $tlimit the time limit that applies to this confirm code - older confirm codes are ignored
	 * @return string|boolean the confirm code or false if it does not exist
	 */
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
}