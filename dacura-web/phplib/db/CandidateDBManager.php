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


class CandidateDBManager extends UsersDBManager {
	
	function createCandidate($cand, $res){
		$cand->status = $res;
		return $this->insert_candidate($cand);
	}
	
	function deferCandidateUpdate($cand, $res){
		$ucand->delta->status = "pending";
		$ucand->delta->version = 0;
		$this->insert_candidate_update($ucand);		
	}
	
	function updateCandidate($ucand, $res){
		$ucand->status = "accept";
		$this->insert_candidate_update($ucand);
		$this->update_candidate($ucand->delta);
		//$this->insert_rollback($ucand);
		//write candidate update to disk at same time as writing candidate update
	}
	
	function insert_candidate_update($ucand){
		$stmt = $this->link->prepare("INSERT INTO candidate_update_requests
					(candid, from_version, to_version, forward, backward, status, createtime, modtime, schema_version)
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$res = $stmt->execute(array(
				$ucand->id,
				$ucand->from_version(),
				$ucand->to_version(),
				$ucand->get_forward_json(),
				$ucand->get_backward_json(),
				$ucand->get_status(),
				time(),
				time(),
				$ucand->schema_version()				
		));
		$id = $this->link->lastInsertId();
		return $id;
	}
	
	function get_candidate_update_history($cand, $to_version = 1){
		try {
			$stmt = $this->link->prepare("SELECT * FROM candidate_update_requests 
					WHERE candid=? AND to_version <= ? AND from_version >= ? ORDER BY from_version DESC");
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
			//GET ALL updates where $cand.version >= $update.from_version && $cand.to_version == null or $cand.to_version > $cand.version;
			$stmt = $this->link->prepare("SELECT * FROM candidate_update_requests
						WHERE candid=? AND from_version <= ? AND (to_version = null OR to_version > ?)");
			$stmt->execute(array($cand->id, $cand->version(), $cand->version()));
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			return $rows;
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
			$stmt = $this->link->prepare("INSERT INTO candidates 
					(id, collectionid, datasetid, version, candidate_class, schema_version, 
					candidate, provenance, annotation, status, createtime, modtime, report) 
					VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			$res = $stmt->execute(array(
					$cand->id,
					$cand->cid, 
					$cand->did, 
					$cand->version(), 
					$cand->get_class(), 
					$cand->get_class_version(), 
					$cand->get_json("candidate"),
					$cand->get_json("provenance"),
					$cand->get_json("annotation"),
					$cand->get_status(),
					time(),
					time(),
					$cand->get_report()
			));
			$id = $this->link->lastInsertId();
			return $id;
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}

	function update_candidate($cand){
		try {
			$stmt = $this->link->prepare("UPDATE candidates SET
					collectionid = ?, datasetid = ?, version = ?, candidate_class = ?, schema_version = ?,
					candidate = ?, provenance = ?, annotation = ?, status = ?, modtime = ?, report = ? WHERE id = ?");
			$res = $stmt->execute(array(
					$cand->cid,
					$cand->did,
					$cand->version(),
					$cand->get_class(),
					$cand->get_class_version(),
					$cand->get_json("candidate"),
					$cand->get_json("provenance"),
					$cand->get_json("annotation"),
					$cand->get_status(),
					time(),
					$cand->get_report(),
					$cand->id						
			));
			
		}
		catch(PDOException $e){
			return $this->failure_result("PDO Error".$e->getMessage(), 500);
		}
	}
	
	function load_candidate($cand, $version = false){
		try {
			$stmt = $this->link->prepare("SELECT collectionid, datasetid, version, candidate_class, schema_version, candidate, provenance, annotation, status, createtime, modtime, report FROM candidates where id=?");
			$stmt->execute(array($cand->id));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if($row){
				$cand->setContext($row['collectionid'], $row['datasetid']);
				$cand->set_version($row['version'], true);
				$cand->set_class($row['candidate_class'], $row['schema_version']);
				if(!$cand->loadFromJSON($row['candidate'], $row['provenance'], $row['annotation'])){
					return $this->failure_result("Failed to load candidate from stored json", 500);
				}
				$cand->created = $row['createtime'];
				$cand->status = $row['status'];
				$cand->modified = $row['modtime'];
				$cand->set_report($row['report']);	
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
			$stmt = $this->link->prepare("SELECT id, collectionid, datasetid, version, candidate_class, schema_version, status, createtime, modtime, report FROM candidates");
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
			$stmt = $this->link->prepare("SELECT * FROM candidates where id=?");
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
			$stmt = $this->link->prepare("SELECT count(distinct candid) as countid FROM candidate_state where candchunk=? AND (decision='accept' OR decision='reject') AND userid=?");
			$stmt->execute(array($cid, $uid));
		}
		else {
			$stmt = $this->link->prepare("SELECT count(candid) as countid FROM candidate_state where candchunk=? AND decision='accept' OR decision='reject' group by candid");
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
 				WHERE r1.candidateid >= r2.id AND r1.candidateid NOT IN (select candid FROM candidate_state)
 				ORDER BY r1.candidateid ASC
 				LIMIT 1");
		$stmt->execute();
		$row = $stmt->fetch();
		if($row){
			$cid = $row['candidateid'];
			$chunk = $row['sourcechunkid'];
			$stmt = $this->link->prepare("INSERT INTO candidate_state(candid, candchunk, decision, stime, updatecode, userid) VALUES(?, ?, ?, ?, ?, ?)");
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
		$stmt = $this->link->prepare("SELECT count(distinct candid) as countid FROM candidates, candidate_state where id=candid AND candchunk=? AND chunk=? AND decision=?");
		foreach(array("accept", "reject", "skip", "assign") as $act){
			$stmt->execute(array($chunkid, $chunkid, $act));
			$row = $stmt->fetch();
			$totes[$act] = ($row['countid']) ? $row['countid'] : 0;
		}
		$stmt = $this->link->prepare("SELECT count(distinct candid) as countid FROM candidates, candidate_state where id=candid AND chunk=? AND chunk=candchunk AND (decision='accept' OR decision='reject')");
		$stmt->execute(array($chunkid));
		$row = $stmt->fetch();
		$totes['remaining'] = $totes['total'] - $row['countid'];
		return $totes;
	}
	
	function getUserChunkDetails($uid, $chunkid){
		$totes = array("total" => 0, "accept" => 0, "reject" => 0, "assign" => 0, "skip" => 0, "remaining" => 0, "done" => 0);
		$stmt = $this->link->prepare("SELECT count(distinct candid) as countid FROM candidates, candidate_state where id=candid AND chunk=? AND chunk=candchunk AND decision=? AND userid=?");
		foreach(array("accept", "reject", "skip", "assign") as $act){
			$stmt->execute(array($chunkid, $act, $uid));
			$row = $stmt->fetch();
			$totes[$act] = ($row['countid']) ? $row['countid'] : 0;
			if($act == "accept" or $act == "reject") $totes['done'] += $totes[$act];
			if($act == "assign") $totes['total'] = $totes[$act];
		}
		$stmt = $this->link->prepare("SELECT count(distinct candid) as countid FROM candidates, candidate_state where id=candid AND chunk=? AND chunk=candchunk AND (decision='accept' OR decision='reject')");
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
		$stmt = $this->link->prepare("INSERT INTO candidate_state(candchunk, candid, decision, stime, updatecode, userid) VALUES('X', ?, ?, ?, ?, ?)");
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
					$candid = $contents['citation']['articleid'];
				}
				else {
					$candid = "";
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
					$report = array($candid, $row['reportid'], "remote", $row["userid"], date('Y-m-d H:i:s', $row['stime']), $yr, $cnt);
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
				$where[] = "candid=?";
				$args[] = $candidate_id;
			}
			if(count($where) >= 1){
				$where_clause = " where " . implode(" AND ", $where). " AND decision='accept'";
			}
			else {
				$where_clause = " where decision='accept'";
			}
			$stmt = $this->link->prepare("SELECT stateid, stime, userid, candchunk, candid, updatecode FROM candidate_state".$where_clause);				
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
				$report = array($row['candid'], $row['stateid'], "local", $row["userid"], date('Y-m-d H:i:s', $row['stime']), $row['candchunk'], $cnt);
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



