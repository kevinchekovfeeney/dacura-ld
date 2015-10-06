<?php

include_once("UsersDBManager.php");

/*
 * Here goes the db access functions that are only used by this service
 */

class StatisticsDBManager extends UsersDBManager {
	
	function getCandidateStatsSql() {
		try {
			$candidate_info = array();
			$sql = "SELECT sum(accepted), sum(rejected), sum(skipped) FROM candidate_stats";
			$stmt = $this->link->prepare($sql);
			$stmt->execute();
			$result = $stmt->fetchAll();
			
			return $result;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
	}
	
	function getUserCandidateStatsSql($id) {
		try {
			$candidate_info = array();
			$sql = "SELECT accepted, rejected, skipped FROM candidate_stats WHERE id = " . $id;
			$stmt = $this->link->prepare($sql);
			$stmt->execute();
			$result = $stmt->fetchAll();
				
			return $result;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
	}
	
	function getCandidateStatsSqlDated($startDate, $endDate) {
		try {
			$candidate_info = array();
			$sql = "SELECT
				sum(case when dacura.candidate_state.decision = 'accept' then 1 else 0 end) accepted,
				sum(case when dacura.candidate_state.decision = 'reject' then 1 else 0 end) rejected,
				sum(case when dacura.candidate_state.decision = 'skip' then 1 else 0 end) skipped
				FROM dacura.candidate_state
				WHERE dacura.candidate_state.stime > " . $startDate . " AND dacura.candidate_state.stime < " . $endDate;
			$stmt = $this->link->prepare($sql);
			$stmt->execute();
			$result = $stmt->fetchAll();
				
			return $result;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
	}
	
	function getUserCandidateStatsSqlDated($startDate, $endDate, $id, $username) {
		try {
			$candidate_info = array();
			$sql = "SELECT
						sum(case when dacura.candidate_state.decision = 'accept' then 1 else 0 end) accepted,
						sum(case when dacura.candidate_state.decision = 'reject' then 1 else 0 end) rejected,
						sum(case when dacura.candidate_state.decision = 'skip' then 1 else 0 end) skipped
					FROM dacura.candidate_state
					WHERE (dacura.candidate_state.stime > " . $startDate . " AND dacura.candidate_state.stime < " . $endDate . ") AND
					(dacura.candidate_state.userid = " . $id . " OR dacura.candidate_state.userid = '" . $username . "')";
			$stmt = $this->link->prepare($sql);
			$stmt->execute();
			$result = $stmt->fetchAll();
		
			return $result;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
	}
	
	
	function getTotalCandidatesSql() {
		try {
			$candidate_info = array();
			$sql = "SELECT count(id) FROM candidates";
			$stmt = $this->link->prepare($sql);
			$stmt->execute();
			$result = $stmt->fetchAll();
				
			return $result;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
	}
	
	
}

