<?php

/*
 * Manages the state of linked data objects in the DB.
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

include_once("phplib/DBManager.php");

class LDDBManager extends DBManager {
	
	function loadLDOList($filter){
		//first work out the where clause
		$params = array();
		$sql = $this->getListSQL($filter, $params);
		try {
			$stmt = $this->link->prepare($sql);
			$stmt->execute($params);
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				return $rows;
			}
			else {
				return $this->failure_result("No linked data objects found in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
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
				return $this->failure_result("No LDO updates found in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}	
	
	//helper functions to load options and turn them into sql
	function getListSQL($filter, &$params, $view_updates = false){
		$fields = "";
		if($view_updates){
			if(isset($filter['include_all'])){
				$fields = ", meta, forward, backward";
			}
			$sql = "SELECT eurid, targetid, type, collectionid, status, from_version, to_version,
				createtime, modtime".$fields." FROM ldo_update_requests";		
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
		if(isset($filter['ldoid'])){
			$wheres['targetid'] = $filter['ldoid'];
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
	
	function loadLDO($ldoid, $type, $cid, $options){
		try {
			$stmt = $this->link->prepare("SELECT collectionid, version, type, contents, meta, status, createtime, modtime FROM ld_objects where id=? and collectionid=? and type=?");
			$stmt->execute(array($ldoid, $cid, $type));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$cwbase = $this->getSystemSetting("install_url").$cid."/".$type."/";
				$ctype = ucfirst($row['type']);
				if(class_exists($ctype)){
					$ldo = new $ctype($ldoid, $cwbase, $this->service->logger);
				}
				else {
					$ldo = new LDO($ldoid, $cwbase, $this->service->logger);
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
		return $ldo;
	}
	
	function loadLDOUpdateHistory($ldo, $version){
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
	
	function loadLDOUpdateRequest($id, $options){
		try {
			$stmt = $this->link->prepare("SELECT type, eurid, targetid, from_version, to_version, forward, backward, meta, collectionid, status, createtime, modtime FROM ldo_update_requests where eurid=?");
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
	 * load ldo
	 * eur->load from db row
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
			//opr(strlen($x[5]));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function updateLDO($uldo, $res){
		if($res == "accept") {
			if($uldo->to_version == 0){
				$uldo->to_version = $uldo->changed->version;
			}
			return ($this->insertLDOUpdate($uldo) && $this->updateLDORecord($uldo->changed));
		}
		elseif($res == "pending"){
			return $this->deferLDOUpdate($uldo, $res);
		}
		elseif($res == "reject"){
			$uldo->status = "reject";
			$uldo->changed->status = "reject";
			$uldo->changed->version = 0;
			return $this->insertLDOUpdate($uldo);
		}
	}

	function deferLDOUpdate($uldo, $res){
		$uldo->status = "pending";
		$uldo->changed->version = 0;
		return $this->insertLDOUpdate($uldo);
	}
	
	function updateLDORecord($ldo){
		try {
			$stmt = $this->link->prepare("UPDATE ld_objects SET
					collectionid = ?, version = ?, contents = ?, meta = ?, status = ?, modtime = ? WHERE id = ?");
			$res = $stmt->execute(array(
					$ldo->cid,
					$ldo->version(),
					json_encode($ldo->ldprops),
					json_encode($ldo->meta),
					$ldo->get_status(),
					time(),
					$ldo->id
			));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error ".$e->getMessage(), 500);
		}
	}
	
	function insertLDOUpdate($uldo){
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
					$uldo->get_status(),
					$uldo->getLDOType(),
					$uldo->cid
			));
			$id = $this->link->lastInsertId();
			return $id;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on create update ".$e->getMessage(), 500);
		}
	}
	
	function updateUpdate($uldo, $ostatus = false){
		if($uldo->published()) {
			if($uldo->to_version == 0){
				$uldo->to_version = $uldo->changed->version;
			}
			return ($this->updateLDOUpdate($uldo) && $this->updateLDORecord($uldo->changed));
		}
		elseif($ostatus == "accept"){
			return ($this->updateLDOUpdate($uldo) && $this->updateLDORecord($uldo->original));				
		}
		else {
			return $this->updateLDOUpdate($uldo);
		}
	}
	
	function updateLDOUpdate($uldo){
		try {
			$stmt = $this->link->prepare("UPDATE ldo_update_requests SET from_version = ?, to_version = ?, forward = ?,
					backward = ?, meta=?, status=?, createtime=?, modtime=? WHERE eurid=?");
			$args = array(
					$uldo->from_version(),
					$uldo->to_version(),
					$uldo->get_forward_json(),
					$uldo->get_backward_json(),
					$uldo->get_meta_json(),
					$uldo->get_status(),
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

	function rollbackUpdate($ocur, $ncur){
		$ncur->from_version = $ocur->original->version;
		$ncur->to_version = 0;
		return ($this->updateLDOUpdate($ncur) && $this->updateLDORecord($ocur->original));
	}
	
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
	
	function hasLDO($id, $type, $cid){
		try {
			$stmt = $this->link->prepare("SELECT id FROM ld_objects where id=? and type=? and collectionid=?");
			$stmt->execute(array($id, $type, $cid));
			if($stmt->rowCount()) {
				return true;
			}		
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving LDO $id ".$e->getMessage(), 500);
		}
	}
}



