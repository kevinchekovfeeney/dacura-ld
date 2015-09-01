<?php

/*
 * Manages the state of candidates in the DB.
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
	
	function load_entity($ent, $type, $options){
		if($type == "candidate"){
			return $this->load_candidate($ent);
		}
	}
	
	function createCandidate($cand){
		//return false;
		return $this->insert_candidate($cand);
	}
	
	function deferCandidateUpdate($ucand, $res){
		$ucand->status = "pending";
		//$ucand->changed->status = "preview";
		$ucand->changed->version = 0;
		return $this->insert_candidate_update($ucand);		
	}
	
	function updateCandidate($ucand, $res){
		if($res == "accept") {
			if($ucand->to_version == 0){
				$ucand->to_version = $ucand->changed->version;
			}
			return ($this->insert_candidate_update($ucand) && $this->update_candidate($ucand->changed));
		}
		elseif($res == "pending"){
			return $this->deferCandidateUpdate($ucand, $res);
		}
		elseif($res == "reject"){
			$ucand->status = "reject";
			$ucand->changed->status = "preview";
			$ucand->changed->version = 0;
			return $this->insert_candidate_update($ucand);
		}
	}

	function updateUpdate($ucand, $ostatus = false){
		if($ucand->published()) {
			if($ucand->to_version == 0){
				$ucand->to_version = $ucand->changed->version;
			}
			return ($this->update_candidate_update($ucand) && $this->update_candidate($ucand->changed));
		}
		elseif($ostatus == "accept"){
			return ($this->update_candidate_update($ucand) && $this->update_candidate($ucand->original));				
		}
		else {
			return $this->update_candidate_update($ucand);
		}
	}
	
	function rollbackUpdate($ocur, $ncur){
		$ncur->from_version = $ocur->original->version;
		$ncur->to_version = 0;
		return ($this->update_candidate_update($ncur) && $this->update_candidate($ocur->original));		
	}

	function pendingUpdatesExist($targetid, $candversion){
		try {
			$stmt = $this->link->prepare("SELECT curid FROM candidates, candidate_update_requests where candidates.id = ? AND candidates.id = candidate_update_requests.targetid AND candidate_update_requests.status = 'pending' and candidate_update_requests.from_version = ?");
			$stmt->execute(array($targetid, $candversion));
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
	
	function loadCandidateUpdateRequest(&$cur){
		$obj = $this->load_candidate_update($cur->id);
		if(!$obj){
			if(!$this->errcode) return $this->failure_result("Request $cur-id not found", 404);
		 	return false;
		}
		$cur->id = $obj['curid'];
		$cur->targetid = $obj['targetid'];
		$cur->forward = json_decode($obj['forward'], true);
		$cur->backward = json_decode($obj['backward'], true);
		$cur->meta = json_decode($obj['meta'], true);
		if(!$cur->meta){$this->meta = array();}
		$cur->modified = $obj['modtime'];
		$cur->created = $obj['createtime'];
		$cur->status = $obj['status'];
		$cur->from_version = $obj['from_version'];
		$cur->to_version = $obj['to_version'];
		return true;
	}
	
	function insert_candidate_update($ucand){
		$ucand->expandNS();//just in case
		try {
			$stmt = $this->link->prepare("INSERT INTO candidate_update_requests
						(targetid, from_version, to_version, forward, backward, meta, status, createtime, modtime, schema_version)
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$res = $stmt->execute(array(
					$ucand->targetid,
					$ucand->from_version(),
					$ucand->to_version(),
					$ucand->get_forward_json(),
					$ucand->get_backward_json(),
					$ucand->get_meta_json(),
					$ucand->get_status(),
					$ucand->created,
					$ucand->modified,
					$ucand->schema_version()				
			));
			$id = $this->link->lastInsertId();
			return $id;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on create update ".$e->getMessage(), 500);
		}
	}

	function update_candidate_update($ucand){
		$ucand->expandNS();//just in case
		try {
			$stmt = $this->link->prepare("UPDATE candidate_update_requests SET from_version = ?, to_version = ?, forward = ?, 
					backward = ?, meta=?, status=?, createtime=?, modtime=?, schema_version=? WHERE curid=?");
			$args = array(
					$ucand->from_version(),
					$ucand->to_version(),
					$ucand->get_forward_json(),
					$ucand->get_backward_json(),
					$ucand->get_meta_json(),
					$ucand->get_status(),
					$ucand->created,
					$ucand->modified,
					$ucand->schema_version(),
					$ucand->id
			);
			$stmt->execute($args);
			if($stmt->rowCount() == 0){
				return $this->failure_result("Failed to update update ".$ucand->id, 404);
			}
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on update update ".$e->getMessage(), 500);
		}
	}
	
	
	function load_candidate_update($id){
		try {
			$stmt = $this->link->prepare("SELECT curid, targetid, from_version, to_version, forward, backward, meta, status, createtime, modtime, schema_version FROM candidate_update_requests where curid=?");
			$stmt->execute(array($id));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				return $row;
			}
			else {
				return $this->failure_result("No update with id ".$id." found in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function get_candidate_update_history($cand, $to_version = 1){
		try {
			$stmt = $this->link->prepare("SELECT * FROM candidate_update_requests 
					WHERE targetid=? AND to_version <= ? AND from_version >= ? AND status='accept' ORDER BY from_version DESC");
			$stmt->execute(array($cand->id, $cand->version(), $to_version));
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $rows;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function get_relevant_updates($cand){
		try {
			if($cand->isLatestVersion()){
				$stmt = $this->link->prepare("SELECT * FROM candidate_update_requests WHERE targetid=? ORDER BY createtime DESC");
				$stmt->execute(array($cand->id));
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $rows;
			}
			else {
				$stmt = $this->link->prepare("SELECT * FROM candidate_update_requests
							WHERE targetid=? AND from_version <= ? AND (to_version = 0 OR to_version = null OR to_version > ?)");
				$stmt->execute(array($cand->id, $cand->version(), $cand->version()));
				$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
				return $rows;
			}
		}		
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}	
	}
	
	/**
	 * Low-level updates -> just write the passed objects to disk.
	 */
	function insert_candidate($cand){
		try {
			$cand->expandNS();
			$stmt = $this->link->prepare("INSERT INTO candidates 
					(id, collectionid, datasetid, version, candidate_class, schema_version, 
					candidate, provenance, annotation, meta, status, createtime, modtime, metagraph) 
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$x = array(
					$cand->id,
					$cand->cid, 
					$cand->did, 
					$cand->version(), 
					$cand->get_class(), 
					$cand->get_class_version(), 
					$cand->get_json("candidate"),
					$cand->get_json("provenance"),
					$cand->get_json("annotation"),
					$cand->get_json("meta"),
					$cand->get_status(),
					time(),
					time(),
					$cand->metagraph
			);
			$res = $stmt->execute($x);
			//opr($x);
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}

	function update_candidate($cand){
		try {
			$cand->expandNS();
			$stmt = $this->link->prepare("UPDATE candidates SET
					collectionid = ?, datasetid = ?, version = ?, candidate_class = ?, schema_version = ?,
					candidate = ?, provenance = ?, annotation = ?, meta = ?, status = ?, modtime = ?, metagraph = ? WHERE id = ?");
			$res = $stmt->execute(array(
					$cand->cid,
					$cand->did,
					$cand->version(),
					$cand->get_class(),
					$cand->get_class_version(),
					$cand->get_json("candidate"),
					$cand->get_json("provenance"),
					$cand->get_json("annotation"),
					$cand->get_json("meta"),
					$cand->get_status(),
					time(),
					$cand->metagraph,
					$cand->id						
			));
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error ".$e->getMessage(), 500);
		}
	}
	
	function load_candidate($cand, $version = false){
		try {
			$stmt = $this->link->prepare("SELECT collectionid, datasetid, version, candidate_class, schema_version, candidate, provenance, annotation, meta, status, createtime, modtime, metagraph FROM candidates where id=?");
			$stmt->execute(array($cand->id));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$cand->setContext($row['collectionid'], $row['datasetid']);
				$cand->set_version($row['version'], $version == false);
				$cand->set_class($row['candidate_class'], $row['schema_version']);
				if(!$cand->loadFromJSON($row['candidate'], $row['provenance'], $row['annotation'], $row['meta'])){
					return $this->failure_result("Failed to load candidate from stored json", 500);
				}
				$cand->created = $row['createtime'];
				$cand->set_status($row['status'], $version == false);
				$cand->modified = $row['modtime'];
				$cand->metagraph  = $row['metagraph'];
			}
			else {
				return $this->failure_result("No candidate with id ".$cand->id." in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
		return true;
	}
	
	function get_candidate_list(){
		try {
			$stmt = $this->link->prepare("SELECT id, collectionid, datasetid, version, candidate_class, schema_version, status, createtime, modtime, metagraph FROM candidates");
			$stmt->execute(array());
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				return $rows;
			}
			else {
				return $this->failure_result("No candidate with id ".$cand->id." in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}

	function get_candidate_updates_list(){
		try {
			$stmt = $this->link->prepare("SELECT curid, targetid, collectionid, datasetid, candidate_update_requests.status, from_version, to_version, 
					candidate_update_requests.schema_version, candidate_update_requests.createtime, candidate_update_requests.modtime 
					FROM candidate_update_requests, candidates WHERE candidate_update_requests.targetid = candidates.id");
			$stmt->execute(array());
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			if($rows){
				return $rows;
			}
			else {
				return $this->failure_result("No candidate with id ".$cand->id." in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}	
		
	function has_candidate($id){
		try {
			$stmt = $this->link->prepare("SELECT id FROM candidates where id=?");
			$stmt->execute(array($id));
			if($stmt->rowCount()) {
				return true;
			}		
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving candidate $id ".$e->getMessage(), 500);
		}
	}
	
	function has_schema($cid, $did){
		try {
			$stmt = $this->link->prepare("SELECT id FROM schemata where collectionid=? AND datasetid=?");
			$stmt->execute(array($cid, $did));
			if($stmt->rowCount()) {
				return true;
			}
			return false;
		}
		catch(PDOException $e){
			return $this->failure_result("error retrieving candidate $id ".$e->getMessage(), 500);
		}		
	}
	
	function updateSchema($changed, $delta, $decision){
		if($this->insert_schema_update($changed, $delta)){
			if($decision == "accept"){
				return $this->update_schema($changed);	
			}
		}
		opr($changed);
		return false;	
	}
	
	function load_schema(&$schema){
		try {
			$stmt = $this->link->prepare("SELECT id, collectionid, datasetid, version, version_string, contents, createtime, modtime, status FROM schemata where collectionid=? AND datasetid=?");
			$stmt->execute(array($schema->cid, $schema->did));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$schema->id = $row['id'];
				$schema->version = $row['version'];
				$schema->type_version = $row['version_string'];
				$schema->loadFromJSON($row['contents']);
				$schema->created = $row['createtime'];
				$schema->status = $row['status'];
				$schema->modified = $row['modtime'];
				return true;
			}
			else {
				return $this->failure_result("No schema for ".$schema->cid . "/". $schema->did ." in system", 404);
			}
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
		return true;
	}
	
	function insert_schema($schema){
		try {
			$schema->expandNS();
			$stmt = $this->link->prepare("INSERT INTO schemata 
					(collectionid, datasetid, version, version_string, contents, createtime, modtime, status) 
					VALUES(?, ?, ?, ?, ?, ?, ?, ?)");
			$x = array(
					$schema->cid,
					$schema->did, 
					$schema->version, 
					$schema->type_version, 
					$schema->get_json_contents(),
					time(),
					time(),
					$schema->get_status()					
			);
			$res = $stmt->execute($x);
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function update_schema($schema){
		$schema->version++;
		$schema->expandNS();
		try {
			$stmt = $this->link->prepare("UPDATE schemata SET
					version = ?, version_string = ?, contents = ?, modtime = ?, status = ? WHERE collectionid = ? AND datasetid = ?");
			$res = $stmt->execute(array(
					$schema->version,
					$schema->type_version,
					$schema->get_json_contents(),
					time(),
					$schema->status, 
					$schema->cid,
					$schema->did					
			));
			if($stmt->errorCode() == 0) {
				return true;
			} else {
				$errors = $stmt->errorInfo();
				return $this->failure_result($errors[2], 500);
			}
			return true;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error ".$e->getMessage(), 500);
		}
	}
	
	
	function insert_schema_update($schema, $delta){
		$schema->expandNS();//just in case
		try {
			$stmt = $this->link->prepare("INSERT INTO schema_update_requests
						(schemaid, from_version, to_version, forward, backward, meta, createtime, modtime, status)
						VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$res = $stmt->execute(array(
					$schema->id,
					$schema->version,
					$schema->version + 1,
					json_encode($delta->forward),
					json_encode($delta->backward),
					"{}",
					time(),
					time(),
					"accept"
			));
			$id = $this->link->lastInsertId();
			if(!$id){
				return $this->failure_result("Failed to insert schema update into DB", 500);
			}
			return $id;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error on create update ".$e->getMessage(), 500);
		}
	}
	
	
	/*
	 * Below here be dragons
	 */
	
	function get_uncached_candidates($yr, $fstore) {
		$hits = array();
		try {
			$stmt = $this->link->prepare("SELECT id FROM candidates where chunk = ?");
			$stmt->execute(array($yr));
			while ($row = $stmt->fetch()){
				$id = $row['id'];
				if(!file_exists($fstore.$yr. "/" . $id . ".jpg")){
					$cand = $this->loadCandidate($id, false);
					$hits[$id] = $cand;
				}
			}
			return $hits;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	
	
	function chunkNeedsWork($uid, $cid){
		$stmt = $this->link->prepare("SELECT count(id) as countid FROM candidates where chunk=?");
		$stmt->execute(array($cid));
		$row = $stmt->fetch();
		if(!$row || !$row['countid']){
			return false;
		}
		$totes = $row['countid'];
		if($this->catch_multiples($uid, $cid)){
			$stmt = $this->link->prepare("SELECT count(distinct targetid) as countid FROM candidate_state where candchunk=? AND (decision='accept' OR decision='reject') AND userid=?");
			$stmt->execute(array($cid, $uid));
		}
		else {
			$stmt = $this->link->prepare("SELECT count(targetid) as countid FROM candidate_state where candchunk=? AND decision='accept' OR decision='reject' group by targetid");
			$stmt->execute(array($cid));
		}
		$row = $stmt->fetch();
		return ($totes > $row['countid']);
	}
	
	function assignNextCandidate($uid){
		$stmt = $this->link->prepare("SELECT candidateid, sourcechunkid FROM create_candidates AS r1 JOIN
       		(SELECT (RAND() *
            	       (SELECT MAX(candidateid)
                        FROM create_candidates)) AS id)
        				AS r2
 				WHERE r1.candidateid >= r2.id AND r1.candidateid NOT IN (select targetid FROM candidate_state)
 				ORDER BY r1.candidateid ASC
 				LIMIT 1");
		$stmt->execute();
		$row = $stmt->fetch();
		if($row){
			$cid = $row['candidateid'];
			$chunk = $row['sourcechunkid'];
			$stmt = $this->link->prepare("INSERT INTO candidate_state(targetid, candchunk, decision, stime, updatecode, userid) VALUES(?, ?, ?, ?, ?, ?)");
			$stmt->execute(array($cid, $chunk, 'assign', time(), "", $uid));			
			return $cid;
		}
		$this->errmsg = "No candidates remaining";
		return false;
	}
	
	function catch_multiples($u, $c){
		return true;
		return ($c == "1831" or $c == "1982");
		
	}
	
	function getIncompleteChunkList(){
		return $this->getChunkIDs();
	}
	
	function getChunkIDs(){
		$ids = array();
		foreach($this->link->query('SELECT DISTINCT chunk FROM candidates order by chunk') as $row) {
			$ids[$row['chunk']] = array();
		}
		return $ids;
	}
	
	function chunkExists($chunkid){
		$stmt = $this->link->prepare("SELECT chunk FROM candidates where chunk=?");
		$stmt->execute(array($chunkid));
		if($stmt->rowCount()) {
			return true;
		}
		return false;
	}
	
	function getChunkdetails($chunkid){
		$totes = array("total" => 0, "accept" => 0, "reject" => 0, "assign" => 0, "skip" => 0, "remaining" => 0);
		$stmt = $this->link->prepare("SELECT count(id) as countid FROM candidates where chunk=?");
		$stmt->execute(array($chunkid));
		$row = $stmt->fetch();
		$totes['total'] = $row['countid'];
		$stmt = $this->link->prepare("SELECT count(distinct targetid) as countid FROM candidates, candidate_state where id=targetid AND candchunk=? AND chunk=? AND decision=?");
		foreach(array("accept", "reject", "skip", "assign") as $act){
			$stmt->execute(array($chunkid, $chunkid, $act));
			$row = $stmt->fetch();
			$totes[$act] = ($row['countid']) ? $row['countid'] : 0;
		}
		$stmt = $this->link->prepare("SELECT count(distinct targetid) as countid FROM candidates, candidate_state where id=targetid AND chunk=? AND chunk=candchunk AND (decision='accept' OR decision='reject')");
		$stmt->execute(array($chunkid));
		$row = $stmt->fetch();
		$totes['remaining'] = $totes['total'] - $row['countid'];
		return $totes;
	}
	
	function getUserChunkDetails($uid, $chunkid){
		$totes = array("total" => 0, "accept" => 0, "reject" => 0, "assign" => 0, "skip" => 0, "remaining" => 0, "done" => 0);
		$stmt = $this->link->prepare("SELECT count(distinct targetid) as countid FROM candidates, candidate_state where id=targetid AND chunk=? AND chunk=candchunk AND decision=? AND userid=?");
		foreach(array("accept", "reject", "skip", "assign") as $act){
			$stmt->execute(array($chunkid, $act, $uid));
			$row = $stmt->fetch();
			$totes[$act] = ($row['countid']) ? $row['countid'] : 0;
			if($act == "accept" or $act == "reject") $totes['done'] += $totes[$act];
			if($act == "assign") $totes['total'] = $totes[$act];
		}
		$stmt = $this->link->prepare("SELECT count(distinct targetid) as countid FROM candidates, candidate_state where id=targetid AND chunk=? AND chunk=candchunk AND (decision='accept' OR decision='reject')");
		$stmt->execute(array($chunkid));
		$row = $stmt->fetch();
		$totes['remaining'] = $totes['total'] - $row['countid'];
		return $totes;
	}

	function getUserReportDetails($uid){
		$dets = array();
		$stmt = $this->link->prepare("SELECT contents from reports where userid=?");
		$stmt->execute(array($uid));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$yid = 0;
			$contents = isset($row['contents']) ? json_decode($row['contents'], true) : false;
			if($contents){
			 	if(isset($contents['citation']) && isset($contents['citation']['issuedate']) &&
					isset($contents['citation']['issuedate']['year'])) {
					$yid = $contents['citation']['issuedate']['year'];
			 	}
			 	elseif(isset($contents['date']) && isset($contents['date']['from']) && isset($contents['date']['from']['year'])){
			 		$yid = $contents['date']['from']['year'];
			 	}
			 	if($yid){
			 		if(isset($dets[$yid])){
			 			$dets[$yid]++;
			 		}
			 		else $dets[$yid] = 1;
			 	}
			}
		}
		return $dets;		
	}
	
	function getCollectionDetails(){
		$dets = $this->getChunkIDs();
		$totes = array("total" => 0, "accept" => 0, "reject" => 0, "assign" => 0, "skip" => 0, "remaining" => 0);
		try{
			$stmt = $this->link->prepare("SELECT count(id) as countid FROM candidates where chunk=?");
			foreach(array_keys($dets) as $chunkid) {
				$dets[$chunkid] = $this->getChunkDetails($chunkid);
				foreach(array_keys($totes) as $b){
					$totes[$b] += $dets[$chunkid][$b];
				}				
			}
			$dets['collection'] = $totes;
			return $dets;
		}
		catch (PDOException $e){
 			$this->errmsg= 'SQL: ' . $e->getMessage();				
		}
	}
	
	function process_candidate($id, $uid, $decision, $contents){
		$stmt = $this->link->prepare("INSERT INTO candidate_state(candchunk, targetid, decision, stime, updatecode, userid) VALUES('X', ?, ?, ?, ?, ?)");
		$stmt->execute(array($id, $decision, time(), $contents, $uid));
		return true;
	}
	
	function addRemoteReport($uid, $contents){
		$stmt = $this->link->prepare("INSERT INTO reports(userid, stime, contents) VALUES(?, ?, ?)");
		$stmt->execute(array($uid, time(), $contents));
		return $this->link->lastInsertId();
	}
	
	function getRemoteReport($id){
		$stmt = $this->link->prepare("SELECT contents FROM reports where reportid=?");
		$stmt->execute(array($id));
		$row = $stmt->fetch();
		$contents = isset($row['contents']) ? $row['contents'] : false;
		return $contents;
	}
	
	function copyReport($id, $uid){
		$stmt = $this->link->prepare("SELECT contents FROM reports where reportid=?");
		$stmt->execute(array($id));
		$row = $stmt->fetch();
		$contents = isset($row['contents']) ? $row['contents'] : false;
		if($contents){
			$stmt = $this->link->prepare("INSERT INTO reports(userid, stime, contents) VALUES(?, ?, ?)");
			$stmt->execute(array($uid, time(), $contents));
			return $this->link->lastInsertId();
		}
		$this->errmsg = "Report with id $id not found";
		return false;
	}
	
	
	function getReportLists($summary=false, $uid = 0, $chunkid = 0, $type="", $stimeafter = 0, $stimebefore = 0, $candidate_id = 0){
		//$headers = array("id", "type", "user", "time", "year", "summary");
		$list = array();
		$args = array();
		$where = array();
		if($uid){
			$where[] = "userid=?";
			$args[] = $uid;
		}
		if($stimebefore){
			$where[] = "stime>?";
			$args[] = $stimebefore;
		}
		if($stimeafter){
			$where[] = "stime<?";
			$args[] = $stimeafter;
		}
		$where_clause = "";
		if(count($where) >= 1){
			$where_clause = " where " . implode(" AND ", $where);
		}
		if($type == 'remote' or $type == ""){
			$stmt = $this->link->prepare("SELECT reportid, stime, userid, contents FROM reports".$where_clause);
			$stmt->execute($args);//, $stimeafter, $stimebefore));
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$contents = isset($row['contents']) ? json_decode($row['contents'], true) : false;
				if($contents && isset($contents['citation']) && isset($contents['citation']['issuedate']) &&
					isset($contents['citation']['issuedate']['year'])) {
					$yr = $contents['citation']['issuedate']['year'];
				}
				else {
					$yr = "";
				}
				if($contents && isset($contents['citation']) && isset($contents['citation']['articleid'])) {
					$targetid = $contents['citation']['articleid'];
				}
				else {
					$targetid = "";
				}
				if(!($chunkid && ($chunkid != $yr))){
					if($summary){
						$cnt = (isset($contents['type'])) ? $contents['type'] : "[Unspecified Type]";
						$cnt = "<strong>$cnt</strong> ";
						$cnt .= (isset($contents['description'])) ? $contents['description'] : "[No description]";
					}
					else {
						$cnt = $row["contents"];
					}
					$report = array($targetid, $row['reportid'], "remote", $row["userid"], date('Y-m-d H:i:s', $row['stime']), $yr, $cnt);
					$list[] = $report;						
				}
			}	
		}
		if($type == 'local' or $type == ""){
			if($chunkid){
				$where[] = "candchunk=?";
				$args[] = $stimeafter;
			}
			if($candidate_id){
				$where[] = "targetid=?";
				$args[] = $candidate_id;
			}
			if(count($where) >= 1){
				$where_clause = " where " . implode(" AND ", $where). " AND decision='accept'";
			}
			else {
				$where_clause = " where decision='accept'";
			}
			$stmt = $this->link->prepare("SELECT stateid, stime, userid, candchunk, targetid, updatecode FROM candidate_state".$where_clause);				
			$stmt->execute($args);
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$contents = isset($row['updatecode']) ? json_decode($row['updatecode'], true) : false;
				if($contents){	
					if($summary){
						$cnt = (isset($contents['type'])) ? $contents['type'] : "[Unspecified Type]";
						$cnt = "<strong>$cnt</strong> ";
						$cnt .= (isset($contents['description'])) ? $contents['description'] : "[No description]";
					}
					else {
						$cnt = $row["updatecode"];
					}
				}
				$report = array($row['targetid'], $row['stateid'], "local", $row["userid"], date('Y-m-d H:i:s', $row['stime']), $row['candchunk'], $cnt);
				$list[] = $report;						
			}	
		}
		return $list;		
	}
	
	function getRemoteReports($uid, $chunkid){
		$reports = array();
		$stmt = $this->link->prepare("SELECT reportid, stime, contents FROM reports where userid=?");
		$stmt->execute(array($uid));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$contents = isset($row['contents']) ? json_decode($row['contents'], true) : false;
			if($contents && isset($contents['citation']) && isset($contents['citation']['issuedate']) && 
					isset($contents['citation']['issuedate']['year']) && $contents['citation']['issuedate']['year'] == $chunkid) {
				$reports[$row['reportid']] = $contents;
			}
		}
		return $reports;
	}

	function getLocalReports($uid, $chunkid){
		$reports = array();
		$stmt = $this->link->prepare("SELECT stateid, updatecode FROM candidate_state where userid=? AND candchunk=? AND decision='accept'");
		$stmt->execute(array($uid, $chunkid));
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$contents = isset($row['updatecode']) ? json_decode($row['updatecode'], true) : false;
			if($contents){
				$reports[$row['stateid']] = $contents;
			}
		}
		return $reports;
	}
}



