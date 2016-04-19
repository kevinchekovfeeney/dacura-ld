<?php
require_once("DacuraSession.php");
require_once("UserRole.php");

/**
 * Class representing user of the Dacura System
 * 
 * User Object contains user roles and sessions
 * * Creation Date: 20/11/2014
 * 
 * @author Chekov
 * @License GPL v2
 */
class DacuraUser extends DacuraObject {
	/** @var string the users email address */
	var $email;
	/** @var string the users name */
	var $name;
	/** @var string the users handle is the user's name if it is set, email address otherwise*/
	var $handle;
	/** @var array name-value profile settings for the user */
	var $profile;
	/** @var array an array of DacuraSession objects, indexed by collection ids, representing current user sessions */
	var $sessions = array(); 
	/** @var array an array of DacuraSession objects representing historical user sessions */
	var $history = array(); 
	/** @var array the list of roles that this user possesses */
	var $roles = array();

	/**
	 * 
	 * @param number $id the id of the user
	 * @param string $e the user's email address
	 * @param string $n the user's name 
	 * @param string $status one of DacuraObject::$valid_statuses
	 * @param array $prof a name-value array of profile settings 
	 */
	function __construct($id, $e, $n, $status, $prof = ""){
		$this->id = $id;
		$this->email = $e;
		$this->name = $n;
		$this->status = $status;
		$this->profile = $prof;
		$this->handle = ($this->name) ? $this->name : $this->email;
	}
	
	/**
	 * Returns the name of the user 
	 * @return string username
	 */
	function getName(){
		return $this->handle;
	}
	
	/**
	 * Removes fields of the object that are not to be sent over the api 
	 * @see DacuraObject::forapi()
	 */
	function forapi(){
		parent::forapi();
		unset($this->sessions);
		unset($this->history);
		return $this; 
	}	

	/**
	 * Is the user an admin of the collection?
	 * @param string $cid the collection id
	 * @return boolean true if the user is an admin
	 */
	function isCollectionAdmin($cid){
		return $this->hasCollectionRole($cid, "admin");
	}
	
	/**
	 * Does the user have a role in the collection
	 * @param string $cid collection id
	 * @param string $role minimum role required (defaults to nobody)
	 * @return boolean true if the user has a collection role that is equal or greater to the passed role
	 */
	function hasCollectionRole($cid, $role = false){
		$role = $role ? $role : "nobody";
		foreach($this->roles as $r){
			if($r->covers($role, $cid)){
				return true;				
			}
		}
		return false;
	}
	
	/**
	 * Has the user got the minimum role (or greater) in the collection 
	 * 
	 * This is just a synonym of hasCollectionRole
	 * @param string $minimum_role
	 * @param string $collection_id
	 * @return boolean
	 */
	function hasSufficientRole($minimum_role, $collection_id){
		return $this->hasCollectionRole($collection_id, $minimum_role);
	}
	
	/**
	 * Fetches a list of all the collection ids in which the user has 'admin' role
	 * @return string[] a list of the administered collection ids
	 */
	function getAdministeredCollections(){
		$cids = array();
		foreach($this->roles as $r){
			if(($r->isAdmin()) && $r->collection_id != "" && $r->collection_id != "all"){
				if(!in_array($r->collection_id, $cids)) $cids[] = $r->collection_id;
			}
		}
		return $cids;
	}

	/**
	 * Fetches a list of all the collection ids in which the user has a role
	 * @return string[] a list of the collection ids that they user has a role in
	 */
	function getCollectionsWithRole(){
		$cids = array();
		foreach($this->roles as $r){
			if($r->collection_id != "" && $r->collection_id != "all"){
				if(!in_array($r->collection_id, $cids)) $cids[] = $r->collection_id;
			}
		}
		return $cids;
	}
	
	/**
	 * Add a new role to the user
	 * @param UserRole $r
	 */
	function addRole(UserRole $r){
		$this->roles[] = $r;
	}

	/**
	 * Return a particular role object
	 * @param string $rid the role id
	 * @return UserRole|boolean
	 */
	function getRole($rid){
		foreach($this->roles as $i => $role){
			if($role->id == $rid){
				return $role;
			}
		}
		return $this->failure_result("User $this->id does not have a role with id $rid", 404);
	}
	
	/**
	 * Does the user have roles in more than one collection?
	 * @param string $role the minimum role required
	 * @return boolean true if the user has the minimum role or greater in more than one collection
	 */
	function rolesSpanCollections($role = false){
		if(count($this->roles) == 0) return false;
		$r1 = $this->roles[0];
		$r1c = $r1->cid();
		foreach($this->roles as $r){
			if($r->cid() == "all") return true;
			if($r->cid() != $r1c && (!$role or $role == $r->role)) return true;
		}
		return false;
	}
	
	/**
	 * Which is the collection id of the user's primary role?
	 * @return string the collection id
	 */
	function getRoleCollectionId(){
		if(count($this->roles) < 1) return false;
		$r1 = $this->roles[0];
		return $r1->cid();
	}
	
	/**
	 * Returns an array of the roles that the user has in the given collection id
	 * @param string $cid
	 * @return array<string> the role names that the user has in the collections
	 */
	function getRolesInCollection($cid){
		$roles = array();
		foreach($this->roles as $i => $role){
			if($role->cid() == $cid && !in_array($role->role(), $roles)){
				$roles[] = $role->role();
			}
		}
		return $roles;
	}
	
	/**
	 * Returns a structure summarising the set of roles possessed by the user
	 * 
	 * @return array<string:string> rolename to role title associative array
	 */
	function roleSummary(){
		$roles = array();
		foreach($this->roles as $i => $role){
			if(!isset($roles[$role->role()])){
				if(isset(UserRole::$dacura_roles[$role->role()])){
					if($role->cid() == "all" && $role->role() == "admin"){
						$roles["god"] = "Platform Administrator";
					}
					else {
						$roles[$role->role()] = UserRole::$dacura_roles[$role->role()] . " in ".$role->cid();
					}
				}
			}
			else {
				$roles[$role->role()] .= ", ".$role->cid();
			}
		}
		return $roles;
	}
	
	/**
	 * Returns a structure representing the set of roles that the user has on a per collection basis
	 * @return array<string:array<string:string>> map of collection ids to roles roleid:roleTitle
	 */
	function collectionSummary(){
		$collections = array();
		foreach($this->roles as $id => $role){
			if($role->cid() == "all") continue;
			if(!isset($collections[$role->cid()])){
				$collections[$role->cid()] = array();
			}
			if(!isset($collections[$role->cid()][$role->role()])){
				if(isset(UserRole::$dacura_roles[$role->role()])){
					$collections[$role->cid()][] = UserRole::$dacura_roles[$role->role()];
				}
			}				
		}
		return $collections;		
	}
	
	
	/**
	 * What category is the user
	 * 
	 * Users are divided into categories depending on the distribution of roles that they have
	 * @return a string representing the category of the user 
	 */
	function rcategory(){
		if($this->isCollectionAdmin("all")) return "platform administrator";
		if($this->hasCollectionRole("all", "user")) return "platform user";
		if($this->rolesSpanCollections("user")) return "multi-collection user";
		$cid = $this->getRoleCollectionId();
		if(!$cid){
			return "zombie";
		}
		if($cid == "all"){//role must be nobody
			return "platform slave";
		}
		if($this->isCollectionAdmin($cid)){
			return "collection administrator";
		}
		if($this->hasCollectionRole($cid, "user")){
			return "collection user";
		}
		if($this->rolesSpanCollections()){ //must be nobody
			return "multi-collection slave";
		}
		if($this->hasCollectionRole($cid, "nobody")){
			return "collection slave";
		}
		return "banjaxed";		
	}
	
	/**
	 * returns true if the user has server admin rights
	 * @return boolean
	 */
	function isPlatformAdmin(){
		$x = $this->rcategory();
		return ($x == "platform administrator");
	}

	/**
	 * Returns true if the user has a role >= user in the server admin scope
	 * 
	 * This is currently not allowed - only admin is allowed at server scope
	 * @return boolean true if the user has a role in all
	 */
	function isPlatformUser(){
		$x = $this->rcategory();
		return ($x == "platform user");
	}	
	
	/* Session Management Functions */

	/**
	 * Creates a new session and optionally starts it
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @param boolean $autostart if set to true, the session will start immediately
	 * @return boolean true if the session was created ok
	 */
	function createSession($sid, $cid, $autostart=true){
		if(!isset($this->sessions[$cid])){
			$this->sessions[$cid] = array();					
		}
		if(!isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid] = new DacuraSession($sid, $cid, $autostart);
		}
		else {
			$this->sessions[$cid][$sid]->registerEvent(array("action" => "abort"));
			$this->dumpSession($sid, $cid);
			$this->sessions[$cid][$sid] = new DacuraSession($sid, $cid, $autostart);
		}
		return true;
	}
	
	/**
	 * Ends a session
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @param string $action the action which is terminating the session (abort | end)
	 * @return boolean true if session ended ok
	 */
	public function endSession($sid, $cid, $action = "end"){
		if(isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid]->registerEvent(array("action" => $action));
			$this->dumpSession($sid, $cid);
			unset($this->sessions[$cid][$sid]);
			return true;
		}
		return false;
	}
	
	/**
	 * Writes the session to file (normally because it is complete)
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @return boolean true on success
	 */
	private function dumpSession($sid, $cid){
		if(!isset($this->sessions[$cid][$sid])){
			return $this->failure_result("No session to dump session $sid in collection $cid does not exist", 404);
		}
		$sdir = $this->session_directory($cid);
		if(!file_exists($sdir)){
			if(!mkdir($sdir, 0777, true)){
				return $this->failure_result("User session dump directory $sdir does not exist and could not be created - check file permissions to ensure that the webserver can write to that directory", 500);
			}			
		}
		//now we just append to the service session file, creating the file if it does not exist
		$sfile = $this->session_file($sid, $cid);
		$srecord = json_encode($this->sessions[$cid][$sid]->events);
		if(!file_put_contents($sfile, $srecord."\n", FILE_APPEND | LOCK_EX)){
			return $this->failure_result("Failed to write session $sid in collection $cid to dump file", 500);
		}
		return true;				
	}
	
	/**
	 * Loads the history of the user from their session directories
	 * @param string $cid the collection id of sessions to load
	 * @param string $sid the id of the session itself
	 * @return array<session array> an array of sessions arrays
	 */
	function loadHistory($cid, $sid = false){
		$sessdirs = array();
		$history = array();
		if($cid == "all"){ //find all the collections in which the user has sessions...
			global $dacura_server;
			$cdir = $dacura_server->getSystemSetting("path_to_collections");
			if ($handle = opendir($cdir)) {
				while (false !== ($entry = readdir($handle))) {
					if(is_dir($cdir.$entry) && $entry != "." && $entry != ".."){
						$sdir = $cdir.$entry."/sessions/".$this->id;
						if(file_exists($sdir)){
							if($sid == false || file_exists($sdir."/".$sid.".session")){
								$sessdirs[$entry] = $sdir;
							}
						}
					}
				}
			}			
		}
		else {
			$sdir = $this->session_directory($cid);
			if($sid == false || file_exists($sdir."/".$sid.".session")){
				$sessdirs[$cid] = $sdir;
			}
		}
		$hco = 0;
		foreach($sessdirs as $colid => $sdir){
			if (file_exists($sdir) && $handle = opendir($sdir)) {
				while (false !== ($entry = readdir($handle))) {
					$ext = substr($entry, strrpos($entry, '.') +1);
					if ($ext == "session"){
						$sess_id = substr($entry, 0, strrpos($entry, '.'));
						$sfile = $sdir."/".$entry;
						if(file_exists($sfile) && ($fhandle = fopen($sfile, "r"))){
							while (($line = fgets($fhandle)) !== false) {
								$ds = new DacuraSession($sess_id, $colid, false);
								if($ds->loadFromJSON($line)){
									$hco++;
									$one_history = $ds->summary();
									$one_history['id'] = "history_".($hco + 1);
									$one_history['service'] = $sess_id;
									$one_history['collection'] = $colid;
									$one_history['events'] = $ds->events;
									$history[] = $one_history;
								}
							}
							fclose($fhandle);
						}
					}
				}
				closedir($handle);
			}
		}
		$this->history = $history;
		return $history;
	}
	
	
	/**
	 * Returns the path to the session file
	 * @param string $sid the session id
	 * @param string $cid the collection id in which the session takes place
	 * @return string the full path to the session file
	 */
	function session_file($sid, $cid){
		$sdir = $this->session_directory($cid);
		$sfile = $sdir."/".$sid.".session";
		return $sfile;
	}
	
	/**
	 * Returns the path to the session directory for the given collection ids
	 * @param collection $cid the collection id
	 * @return string the full path to the sessio directory
	 */
	function session_directory($cid){
		//this is bad - we use the global dacura-server object to get our path to the dump file
		//this is so that we don't have to pass the server object all the way along to the user object
		global $dacura_server;
		$sdir = $dacura_server->getSystemSetting("path_to_collections").$cid."/sessions/".$this->id;
		return $sdir;		
	}
		
	/**
	 * Pauses a session
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @return boolean true on success
	 */
	public function pauseSession($sid, $cid){
		if(isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid]->pause();
			return true;
		}
		return $this->failure_result("No session $sid in collection $cid to pause", 404);
	}

	/**
	 * Unpause the session - make it active
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @return boolean true on success
	 */
	public function unpauseSession($sid, $cid){
		if(isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid]->unpause();
			return true;
		}
		return $this->failure_result("No session $sid in collection $cid to unpause", 404);
	}
	
	/**
	 * Gets the details of the session 
	 * 
	 * This assumes a session model where entities are assigned to the user, then rejected or accepted.
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @return array with [duration: secs, assigned: n, accepted: n, rejected: n] where n is the count of entities
	 */
	public function getSessionDetails($sid, $cid){
		if(isset($this->sessions[$cid][$sid])){
			$s = $this->sessions[$cid][$sid];
			$res = array("duration" => gmdate("H:i:s", $s->activeDuration()),
					"assigned" => $s->eventCount("assign"), 
					"accepted" => $s->eventCount("accept"), 
					"rejected"=> $s->eventCount("reject"));
			return $res;
		}
		return $this->failure_result("session $sid in collection $cid does not exist - no details available", 404);
	}
	
	/**
	 * Records a particular user action to the session
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @param string $type the type of action 
	 * @param boolean $dump if true, the session will be saved to file
	 */
	public function recordAction($sid, $cid, $type, $dump = false){
		if(!isset($this->sessions[$cid])){
			$this->sessions[$cid] = array();
		}
		if(!isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid] = new DacuraSession($sid, $cid);
		}
		$this->sessions[$cid][$sid]->registerEvent(array("action" => $type));
		if($dump){
			$this->dumpSession($sid, $cid);
			unset($this->sessions[$cid][$sid]);
		}
	}	
	
	/**
	 * Register a particular event with a session
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @param array $ev an event description array [action: myaction...]
	 */
	public function registerSessionEvent($sid, $cid, $ev){
		if(!isset($this->sessions[$cid])){
			$this->sessions[$cid] = array();
		}
		if(!isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid] = new DacuraSession($sid, $cid);
		}
		elseif($this->sessions[$cid][$sid]->expired()){
			$this->endSession($sid, $cid, "expired");
			$this->sessions[$cid][$sid] = new DacuraSession($sid, $cid);
		}
		$this->sessions[$cid][$sid]->registerEvent($ev);
	}
	
	/**
	 * Does the user have any live sessions?
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 * @return boolean
	 */
	function hasLiveSession($sid, $cid){
		return (isset($this->sessions[$cid]) && isset($this->sessions[$cid][$sid]) ? $this->sessions[$cid][$sid]->hasLiveSession() : false); 
	}
	
	/**
	 * Terminates all a user's live sessions
	 * @param string $action the action that cause the session termination
	 */
	function endLiveSessions($action){
		foreach($this->sessions as $cid => $sesses){
			foreach($sesses as $sid => $sess){
				$this->endSession($sid, $cid, $action);
				$this->dumpSession($sid, $cid);
				unset($this->sessions[$cid][$sid]);				
			}
		}
	}
	/**
	 * Unsets the entity that is currently assigned to the user's session
	 * @param string $sid session id
	 * @param string $cid the collection id in which the session is taking place
	 */
	function unsetCurrentEntity($sid, $cid){
		if(isset($this->sessions[$cid][$sid])){
			$this->sessions[$cid][$sid]->current_entity = null;
		}
	}
}




