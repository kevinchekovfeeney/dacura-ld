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
include_once("phplib/LD/Candidate.php");
include_once("phplib/LD/Schema.php");
include_once("phplib/LD/Ontology.php");

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
		
		$sql = "SELECT id, collectionid, datasetid, version, type, status, createtime, modtime, meta FROM ld_entities";
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
				foreach($rows as $i => $row){
					if($row['meta']) $rows[$i]['meta'] = json_decode($row['meta'], true);
				}
				return $rows;
			}
			else {
				return $this->failure_result("No entityis found in system", 404);
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
			$wheres['ld_entities.id'] = $filter['entityid'];
		}
		if(isset($filter['datasetid'])){
			$wheres['datasetid'] = $filter['datasetid'];
		}
		if(isset($filter['status'])){
			$wheres['status'] = $filter['status'];
		}
		$sql = "SELECT eurid, targetid, collectionid, datasetid, entity_update_requests.status, from_version, to_version,
				entity_update_requests.createtime, entity_update_requests.modtime
				FROM entity_update_requests, ld_entities WHERE entity_update_requests.targetid = ld_entities.id";
		if(count($wheres) > 0){
			foreach($wheres as $p => $v){
				$sql .= " AND ";
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
	
	function loadEntity($entid, $options){
		try {
			$stmt = $this->link->prepare("SELECT collectionid, datasetid, version, type, contents, meta, status, createtime, modtime FROM ld_entities where id=?");
			$stmt->execute(array($entid));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$ctype = ucfirst($row['type']);
				$ent = new $ctype($entid);
				$ent->loadFromDBRow($row);
			}
			else {
				return $this->failure_result("No entity with id $entid in system", 404);
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
			$stmt = $this->link->prepare("SELECT ld_entities.type, eurid, targetid, from_version, to_version, forward, backward, entity_update_requests.meta, entity_update_requests.status, entity_update_requests.createtime, entity_update_requests.modtime FROM entity_update_requests, ld_entities where targetid = ld_entities.id AND eurid=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				if($row['type'] == "ontology"){
					$eur = new OntologyUpdateRequest($id);
				}
				elseif($row['type'] == "schema"){
					$eur = new SchemaUpdateRequest($id);
				}
				else {
					$eur = new CandidateUpdateRequest($id);
				}
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
	
	function createEntity($ent, $type){
		try {
			$stmt = $this->link->prepare("INSERT INTO ld_entities
				(id, collectionid, datasetid, type, version, contents, meta, status, createtime, modtime)
				VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$x = array(
					$ent->id,
					$ent->cid,
					$ent->did,
					$type,
					$ent->version(),
					json_encode($ent->ldprops),
					json_encode($ent->meta),
					$ent->get_status(),
					time(),
					time()
			);
			$res = $stmt->execute($x);
			//opr($x);
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
						(targetid, from_version, to_version, forward, backward, meta, createtime, modtime, status)
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$res = $stmt->execute(array(
					$uent->targetid,
					$uent->from_version(),
					$uent->to_version(),
					$uent->get_forward_json(),
					$uent->get_backward_json(),
					$uent->get_meta_json(),
					$uent->created,
					$uent->modified,
					$uent->get_status()
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
	

	function pendingUpdatesExist($targetid, $entversion){
		try {
			$stmt = $this->link->prepare("SELECT eurid FROM ld_entities, entity_update_requests where ld_entities.id = ? AND ld_entities.id = entity_update_requests.targetid AND entity_update_request.status = 'pending' and entity_update_requests.from_version = ?");
			$stmt->execute(array($targetid, $entversion));
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
	
	function hasEntity($id){
		try {
			$stmt = $this->link->prepare("SELECT id FROM ld_entities where id=?");
			$stmt->execute(array($id));
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



