<?php

/*
 * Manages the state of linked data entities in the DB.
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

include_once("UsersDBManager.php");

class LDDBManager extends UsersDBManager {
	
	function loadEntityList($filter){
		//first work out the where clause
		$wheres = array();
		$params = array();
		if(isset($filter['type'])){
			$wheres['type'] = $filter['type'];
		}
		if(isset($filter['collectionid'])){
			$wheres['collectionid'] = $filter['collectionid'];				
		}
		if(isset($filter['datasetid'])){
			$wheres['datasetid'] = $filter['datasetid'];				
		}
		if(isset($filter['status'])){
			$wheres['status'] = $filter['status'];				
		}
		if(isset($filter['include_all'])){
			$fields = ", contents";
		}
		else {
			$fields = "";
		}
		
		$sql = "SELECT id, collectionid, datasetid, version, type, status, createtime, modtime, meta". $fields." FROM ld_entities";
		if(count($wheres) > 0){
			$i = 0;
			$sql .= " WHERE ";
			foreach($wheres as $p => $v){
				if($i++ > 0) $sql .= " AND ";
				$sql .= "$p=?"; 
				$params[] = $v;
			}
		}
		try {
			$stmt = $this->link->prepare($sql);
			$stmt->execute($params);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				return $rows;
			}
			else {
				return $this->failure_result("No entities found in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function loadUpdatesList($filter){
		$wheres = array();
		$params = array();
		if(isset($filter['type'])){
			$wheres['type'] = $filter['type'];
		}
		if(isset($filter['collectionid'])){
			$wheres['collectionid'] = $filter['collectionid'];
		}
		if(isset($filter['entityid'])){
			$wheres['targetid'] = $filter['entityid'];
		}
		if(isset($filter['datasetid'])){
			$wheres['datasetid'] = $filter['datasetid'];
		}
		if(isset($filter['status'])){
			$wheres['status'] = $filter['status'];
		}
		if(isset($filter['include_all'])){
			$fields = ", meta, forward, backward";
		}
		else {
			$fields = "";
		}
		$sql = "SELECT eurid, targetid, type, collectionid, datasetid, status, from_version, to_version,
				createtime, modtime	$fields FROM entity_update_requests";
		if(count($wheres) > 0){
			$first = true;
			foreach($wheres as $p => $v){
				if($first){
					$sql .= " WHERE ";
					$first = false;
				}
				else {
					$sql .= " AND ";						
				}
				$sql .= "$p=?";
				$params[] = $v;
			}
		}	
		try {
			$stmt = $this->link->prepare($sql);
			$stmt->execute($params);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				return $rows;
			}
			else {
				return $this->failure_result("No entity updates found in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function loadEntity($entid, $type, $cid, $did, $options){
		try {
			$stmt = $this->link->prepare("SELECT collectionid, datasetid, version, type, contents, meta, status, createtime, modtime FROM ld_entities where id=? and collectionid=? and datasetid=? and type=?");
			$stmt->execute(array($entid, $cid, $did, $type));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$ctype = ucfirst($row['type']);
				$ent = new $ctype($entid);
				$ent->loadFromDBRow($row);
			}
			else {
				return $this->failure_result("No $type with id $entid in $cid/$did", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
		return $ent;
	}
	
	function loadEntityUpdateHistory($ent, $version){
		try {
			$stmt = $this->link->prepare("SELECT * FROM entity_update_requests
				WHERE targetid=? AND to_version <= ? AND from_version >= ? AND status='accept' ORDER BY from_version DESC");
			$stmt->execute(array($ent->id, $ent->version(), $version));
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $rows;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function loadEntityUpdateRequest($id, $options){
		try {
			$stmt = $this->link->prepare("SELECT type, eurid, targetid, from_version, to_version, forward, backward, meta, collectionid, datasetid, status, createtime, modtime FROM entity_update_requests where eurid=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$cname = $row['type']."UpdateRequest";
				$eur = new $cname(id);
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
	
	/*
	 * load Entity
	 * eur->load from db row
	 */
	
	
	function createEntity($ent, $type){
		try {
			$stmt = $this->link->prepare("INSERT INTO ld_entities
				(id, collectionid, datasetid, type, version, contents, meta, status, createtime, modtime)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$ld = json_encode($ent->ldprops);
			if(!$ld){
				return $this->failure_result("JSON encoding error: ".json_last_error() . " " . json_last_error_msg(), 500);
			}
			$x = array(
					$ent->id,
					$ent->cid,
					$ent->did,
					$type,
					$ent->version(),
					$ld,
					json_encode($ent->meta),
					$ent->get_status(),
					time(),
					time()
			);
			$res = $stmt->execute($x);
			//opr(strlen($x[5]));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function updateEntity($uent, $res){
		if($res == "accept") {
			if($uent->to_version == 0){
				$uent->to_version = $uent->changed->version;
			}
			return ($this->insertEntityUpdate($uent) && $this->updateEntityRecord($uent->changed));
		}
		elseif($res == "pending"){
			return $this->deferEntityUpdate($uent, $res);
		}
		elseif($res == "reject"){
			$uent->status = "reject";
			$uent->changed->status = "reject";
			$uent->changed->version = 0;
			return $this->insertEntityUpdate($uent);
		}
	}

	function deferEntityUpdate($uent, $res){
		$uent->status = "pending";
		$uent->changed->version = 0;
		return $this->insertEntityUpdate($uent);
	}
	
	function updateEntityRecord($ent){
		try {
			$stmt = $this->link->prepare("UPDATE ld_entities SET
					collectionid = ?, datasetid = ?, version = ?, contents = ?, meta = ?, status = ?, modtime = ? WHERE id = ?");
			$res = $stmt->execute(array(
					$ent->cid,
					$ent->did,
					$ent->version(),
					json_encode($ent->ldprops),
					json_encode($ent->meta),
					$ent->get_status(),
					time(),
					$ent->id
			));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error ".$e->getMessage(), 500);
		}
	}
	
	function insertEntityUpdate($uent){
		try {
			$stmt = $this->link->prepare("INSERT INTO entity_update_requests
						(targetid, from_version, to_version, forward, backward, meta, createtime, modtime, status, type, collectionid, datasetid)
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$res = $stmt->execute(array(
					$uent->targetid,
					$uent->from_version(),
					$uent->to_version(),
					$uent->get_forward_json(),
					$uent->get_backward_json(),
					$uent->get_meta_json(),
					$uent->created,
					$uent->modified,
					$uent->get_status(),
					$uent->getEntityType(),
					$uent->cid,
					$uent->did
			));
			$id = $this->link->lastInsertId();
			return $id;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on create update ".$e->getMessage(), 500);
		}
	}
	
	function updateUpdate($uent, $ostatus = false){
		if($uent->published()) {
			if($uent->to_version == 0){
				$uent->to_version = $uent->changed->version;
			}
			return ($this->updateEntityUpdate($uent) && $this->updateEntityRecord($uent->changed));
		}
		elseif($ostatus == "accept"){
			return ($this->updateEntityUpdate($uent) && $this->updateEntityRecord($uent->original));				
		}
		else {
			return $this->updateEntityUpdate($uent);
		}
	}
	
	function updateEntityUpdate($uent){
		try {
			$stmt = $this->link->prepare("UPDATE entity_update_requests SET from_version = ?, to_version = ?, forward = ?,
					backward = ?, meta=?, status=?, createtime=?, modtime=? WHERE eurid=?");
			$args = array(
					$uent->from_version(),
					$uent->to_version(),
					$uent->get_forward_json(),
					$uent->get_backward_json(),
					$uent->get_meta_json(),
					$uent->get_status(),
					$uent->created,
					$uent->modified,
					$uent->id
			);
			$stmt->execute($args);
			if($stmt->rowCount() == 0){
				return $this->failure_result("Failed to update update ".$uent->id, 404);
			}
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on update update ".$e->getMessage(), 500);
		}
	}

	function rollbackUpdate($ocur, $ncur){
		$ncur->from_version = $ocur->original->version;
		$ncur->to_version = 0;
		return ($this->updateEntityUpdate($ncur) && $this->updateEntityRecord($ocur->original));
	}
	

	function pendingUpdatesExist($targetid, $type, $cid, $did, $entversion){
		try {
			$stmt = $this->link->prepare("SELECT eurid FROM ld_entities, entity_update_requests where ld_entities.id = ? AND ld_entities.id = entity_update_requests.targetid AND 
					entity_update_request.status = 'pending' AND entity_update_requests.from_version = ? AND ld_entities.collectionid = ? AND
					ld_entities.datasetid = ? AND ld_entities.collectionid = entity_update_requests.collectionid AND ld_entities.type = ?");
			$stmt->execute(array($targetid, $entversion, $cid, $did, $type));
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

	function getRelevantUpdates($ent){
		try {
			if($ent->isLatestVersion()){
				$stmt = $this->link->prepare("SELECT * FROM entity_update_requests WHERE targetid=? ORDER BY createtime DESC");
				$stmt->execute(array($ent->id));
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $rows;
			}
			else {
				$stmt = $this->link->prepare("SELECT * FROM entity_update_requests 
							WHERE targetid=? AND from_version <= ? AND (to_version = 0 OR to_version = null OR to_version > ?)");
				$stmt->execute(array($ent->id, $ent->version(), $ent->version()));
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $rows;
			}
		}		
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}	
	}
	
	function hasEntity($id, $type, $cid, $did){
		try {
			$stmt = $this->link->prepare("SELECT id FROM ld_entities where id=? and type=? and collectionid=? and datasetid=?");
			$stmt->execute(array($id, $type, $cid, $did));
			if($stmt->rowCount()) {
				return true;
			}		
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving entity $id ".$e->getMessage(), 500);
		}
	}
}



