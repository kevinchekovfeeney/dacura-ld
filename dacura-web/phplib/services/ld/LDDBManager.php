<?php
include_once("phplib/DBManager.php");
/**
 * This class provides the linked data services with access to the database
 * This is based upon a very simple database structure where 
 *
 * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL V2
 */
class LDDBManager extends DBManager {
	
	/**
	 * Loads a list of Linked data objects
	 * @param array $filter a ldo filter structure - to be passed to getListSQL
	 * @return array - an array of rows. 
	 */
	function loadLDOList($filter){
		//first work out the where clause
		$params = array();
		$sql = $this->getListSQL($filter, $params);
		try {
			$stmt = $this->link->prepare($sql);
			$stmt->execute($params);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				foreach($rows as $i => $row){
					if(isset($row['meta'])){
						$rows[$i]['meta'] = json_decode($row['meta'], true);						
					}					
					if(isset($row['contents'])){
						$rows[$i]['contents'] = json_decode($row['contents'], true);						
					}					
				}
				return $rows;
			}
			else {
				return array();//no entries found for filter
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Loads a list of Linked data update objects
	 * @param array $filter a ldo filter structure - to be passed to getListSQL
	 * @return array - an array of rows. 
	 */
	function loadUpdatesList($filter){
		$params = array();
		$sql = $this->getListSQL($filter, $params, true);
		try {
			$stmt = $this->link->prepare($sql);
			$stmt->execute($params);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				return $rows;
			}
			else {
				return array();//no entries found for filter
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}	
	
	/* helper functions to load options and turn them into sql */
	
	/**
	 * Generates the sql from the filter structure to fetch the desired objects
	 * @param array $filter the structure {include_all, type, collectionid, status, version, createtime, status, targetid, from_version, to_version]
	 * @param array $params parameters array that will be populated with values to be passed into sql placeholders
	 * @param boolean $view_updates is this a view update request (as against view ldos)
	 * @return string the sql statement
	 */
	function getListSQL($filter, &$params, $view_updates = false){
		$fields = "";
		if($view_updates){
			if(isset($filter['include_all'])){
				$fields = ", meta, forward, backward";
			}
			$sql = "SELECT eurid, targetid, type, collectionid, status, from_version, to_version,
				createtime, modtime, length(forward) + length(backward) as size".$fields." FROM ldo_update_requests";		
		}
		else {
			if(isset($filter['include_all'])){
				$fields = ", contents";
			}
			$sql = "SELECT id, collectionid, version, type, status, createtime, modtime, meta, length(contents) as size". $fields." FROM ld_objects";				
		}
		$wheres = array();
		if(isset($filter['type']) && strtolower($filter['type']) != "ldo"){
			$wheres['type'] = $filter['type'];
		}
		if(isset($filter['collectionid'])){
			$wheres['collectionid'] = $filter['collectionid'];
		}
		if(isset($filter['status'])){
			$wheres['status'] = $filter['status'];
		}
		if(isset($filter['version'])){
			$wheres['version'] = $filter['version'];
		}
		if(isset($filter['createtime'])){
			$wheres['createtime'] = $filter['createtime'];
		}
		if($view_updates){
			if(isset($filter['targetid'])){
				$wheres['targetid'] = $filter['targetid'];
			}
			if(isset($filter['from_version'])){
				$wheres['from_version'] = $filter['from_version'];
			}
			if(isset($filter['to_version'])){
				$wheres['to_version'] = $filter['to_version'];
			}
		}		
		$wsql = "";
		if(count($wheres) > 0){
			$i = 0;
			foreach($wheres as $p => $v){
				if($i++ > 0) $wsql .= " AND ";
				$wsql .= "$p=?";
				$params[] = $v;
			}
		}
		if($wsql != ""){
			$sql .= " WHERE $wsql";
		}
		return $sql;
	}
	
	/**
	 * Loads a linked data object from the database
	 * @param LDO $ldo
	 * @param string $ldoid the id of the ldo
	 * @param string $type the ldtype of the ldo
	 * @param string $cid the ldo collection id
	 * @return boolean
	 */
	function loadLDO(LDO &$ldo, $ldoid, $type, $cid){
		try {
			$stmt = $this->link->prepare("SELECT collectionid, version, type, contents, meta, status, createtime, modtime FROM ld_objects where id=? and collectionid=? and type=?");
			$stmt->execute(array($ldoid, $cid, $type));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				if(isset($row['meta'])){
					$row['meta'] = json_decode($row['meta'], true);
				}
				if(isset($row['contents'])){
					$row['contents'] = json_decode($row['contents'], true);
				}
				$ldo->loadFromDBRow($row);
			}
			else {
				return $this->failure_result("No $type with id $ldoid in $cid", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
		return true;
	}
	
	/**
	 * Loads the update history of a linked data object
	 * @param LDO $ldo the linked data object in question
	 * @param integer $version the version number to go back to
	 * @return multitype:|boolean
	 */
	function loadLDOUpdateHistory(LDO $ldo, $version){
		try {
			$stmt = $this->link->prepare("SELECT * FROM ldo_update_requests
				WHERE targetid=? AND to_version <= ? AND from_version >= ? AND status='accept' ORDER BY from_version DESC");
			$stmt->execute(array($ldo->id, $ldo->version(), $version));
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $rows;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Loads a LDO Update object from the database
	 * @param string $id the id of the object to load
	 * @return LDOUpdate|boolean loaded update object or false if not there.
	 */
	function loadLDOUpdateRequest($id){
		try {
			$stmt = $this->link->prepare("SELECT type, eurid, targetid, from_version, to_version, forward, backward, meta, collectionid, status, createtime, modtime FROM ldo_update_requests where eurid=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$eur = new LDOUpdate($id);
				$eur->loadFromDBRow($row);			
				return $eur;
			}
			else {
				return $this->failure_result("No update with id ".$id." found in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Adds a new linked data object to the ld object store db
	 * @param LDO $ldo the object to be stored
	 * @param string $type the ld type of the object
	 * @return boolean
	 */	
	function createLDO($ldo, $type){
		try {
			$stmt = $this->link->prepare("INSERT INTO ld_objects
				(id, collectionid, type, version, contents, meta, status, createtime, modtime)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$ld = json_encode($ldo->ldprops);
			if(!$ld){
				return $this->failure_result("JSON encoding error: ".json_last_error() . " " . json_last_error_msg(), 500);
			}
			$x = array(
					$ldo->id,
					$ldo->cid,
					$type,
					$ldo->version(),
					$ld,
					json_encode($ldo->meta),
					$ldo->get_status(),
					time(),
					time()
			);
			$res = $stmt->execute($x);
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Executes a LDO Update by updating both the ldo update table and the ldo table
	 * @param LDOUpdate $uldo the update object describing the change
	 * @param string $status - the status that the update has
	 * @return boolean|Ambigous <boolean, string>
	 */
	function updateLDO(LDOUpdate $uldo){
		if($uldo->is_accept()) {
			$uldo->to_version = $uldo->changed->version;
			return ($this->insertLDOUpdate($uldo) && $this->updateLDORecord($uldo->changed));
		}
		elseif($uldo->is_pending()){
			$uldo->changed->status("pending");
			$uldo->changed->version = 0;
			$uldo->to_version = 0;
			return $this->insertLDOUpdate($uldo);
		}
		elseif($uldo->is_reject()){
			$uldo->changed->status("reject");
			$uldo->changed->version = 0;
			$uldo->to_version = 0;
			return $this->insertLDOUpdate($uldo);
		}
	}
	
	/**
	 * Updates the DB Record of a particular LDO
	 * @param LDO $ldo the linked data object to be updated
	 * @return boolean true if update was successful
	 */
	function updateLDORecord(LDO $ldo){
		try {
			$stmt = $this->link->prepare("UPDATE ld_objects SET
					version = ?, contents = ?, meta = ?, status = ?, modtime = ? WHERE id = ? and collectionid = ? and type = ?");
			$res = $stmt->execute(array(
					$ldo->version(),
					json_encode($ldo->ldprops),
					json_encode($ldo->meta),
					$ldo->get_status(),
					time(),
					$ldo->id,
					$ldo->cid,
					$ldo->ldtype()
			));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error ".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Inserts a new update into the linked data database
	 * @param LDOUpdate $uldo the update object
	 * @return string|boolean - the id of the new update object, false if unsuccessful. 
	 */
	function insertLDOUpdate(LDOUpdate $uldo){
		try {
			$stmt = $this->link->prepare("INSERT INTO ldo_update_requests
					(targetid, from_version, to_version, forward, backward, meta, createtime, modtime, status, type, collectionid)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$res = $stmt->execute(array(
					$uldo->targetid,
					$uldo->from_version(),
					$uldo->to_version(),
					$uldo->get_forward_json(),
					$uldo->get_backward_json(),
					$uldo->get_meta_json(),
					$uldo->created,
					$uldo->modified,
					$uldo->status(),
					$uldo->ldtype(),
					$uldo->cid
			));
			$id = $this->link->lastInsertId();
			return $id;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on create update ".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Updates an existing update - takes care of updating both the update table and the 
	 * @param LDOUpdate $uldo the ldo update object
	 * @param string $ostatus - the original status of the update before it was updated
	 * @return boolean true if the update is successful
	 */
	function updateUpdate(LDOUpdate $uldo, $ostatus = false){
		if($uldo->published()) {
			$uldo->to_version = $uldo->changed->version;
			return ($this->updateLDOUpdate($uldo) && $this->updateLDORecord($uldo->changed));
		}
		elseif($ostatus == "accept"){//this is a rollback - we have to update the original to set it back to what it was
			return ($this->updateLDOUpdate($uldo) && $this->updateLDORecord($uldo->original));				
		}
		else {
			return $this->updateLDOUpdate($uldo);
		}
	}
	
	/**
	 * Carries out an update to an LDO Update object on the database itself
	 * @param LDOUpdate $uldo the update object
	 * @return boolean - true if it worked
	 */
	function updateLDOUpdate(LDOUpdate $uldo){
		try {
			$stmt = $this->link->prepare("UPDATE ldo_update_requests SET from_version = ?, to_version = ?, forward = ?,
					backward = ?, meta=?, status=?, createtime=?, modtime=? WHERE eurid=?");
			$args = array(
					$uldo->from_version(),
					$uldo->to_version(),
					$uldo->get_forward_json(),
					$uldo->get_backward_json(),
					$uldo->get_meta_json(),
					$uldo->status(),
					$uldo->created,
					$uldo->modified,
					$uldo->id
			);
			$stmt->execute($args);
			if($stmt->rowCount() == 0){
				return $this->failure_result("Failed to update update ".$uldo->id, 404);
			}
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on update update ".$e->getMessage(), 500);
		}
	}

	/** 
	 * Rolls back an update from oldupd to newupd
	 * @param LDOUpdate $oldupd
	 * @param LDOUpdate $newupd
	 * @return boolean true if it works
	 */
	function rollbackUpdate($oldupd, $newupd){
		$newupd->from_version = $oldupd->original->version;
		$newupd->to_version = 0;
		return ($this->updateLDOUpdate($newupd) && $this->updateLDORecord($oldupd->original));
	}
	
	/** 
	 * Checks to see whether there are any pending updates in the queue for particular ldo
	 * @param string $targetid the id of the object in question
	 * @param string $type the ld type of the object
	 * @param string $cid the collection id of the object
	 * @param int $ldoversion the version number of the object
	 * @return boolean - true if there are pending updates for the object
	 */
	function pendingUpdatesExist($targetid, $type, $cid, $ldoversion){
		try {
			$stmt = $this->link->prepare("SELECT eurid FROM ld_objects, ldo_update_requests where ld_objects.id = ? AND ld_objects.id = ldo_update_requests.targetid AND 
					ldo_update_request.status = 'pending' AND ldo_update_requests.from_version = ? AND ld_objects.collectionid = ? AND
					AND ld_objects.collectionid = ldo_update_requests.collectionid AND ld_objects.type = ?");
			$stmt->execute(array($targetid, $ldoversion, $cid, $type));
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows && count($rows) > 0){
				return $rows;
			}
			else {
				return false;
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	/**
	 * Returns a list of the updates that are considered relevant to a particular version of an ldo (i.e. were not created after it existed)
	 * @param LDO $ldo the ldo in question
	 * @return array of updates records deemed relevant
	 */
	function getRelevantUpdates($ldo){
		try {
			if($ldo->isLatestVersion()){
				$stmt = $this->link->prepare("SELECT * FROM ldo_update_requests WHERE targetid=? ORDER BY createtime DESC");
				$stmt->execute(array($ldo->id));
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $rows;
			}
			else {
				$stmt = $this->link->prepare("SELECT * FROM ldo_update_requests 
					WHERE targetid=? AND from_version <= ? AND (to_version = 0 OR to_version = null OR to_version > ?)");
				$stmt->execute(array($ldo->id, $ldo->version(), $ldo->version()));
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $rows;
			}
		}		
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}	
	}
	
	/**
	 * Does a particular ldo exist in the system? 
	 * @param string $id ldo id
	 * @param string $type ld type
	 * @param string $cid collection id
	 * @return boolean
	 */
	function hasLDO($id, $type, $cid){
		try {
			$stmt = $this->link->prepare("SELECT id FROM ld_objects where id=? and type=? and collectionid=?");
			$stmt->execute(array($id, $type, $cid));
			if($stmt->rowCount()) {
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				//opr($rows);
				return true;
			}		
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving LDO $id ".$e->getMessage(), 500);
		}
	}
}



