<?php
include_once("phplib/db/StatisticsDBManager.php");

class StatisticsDacuraServer extends DacuraServer {
	
	var $dbclass = "StatisticsDBManager";

	function getUsersInContext($cid, $did){
		//first figure out which cids to use for the given active user...
		$u = $this->getUser(0);
		$cids = array();
		$dids = array();
	
		if(!$cid && !$did){
			//we are in top level context....
			//1. get all collections where u has admin rights
			//2. get all datasets where u has admin rights...
			//all users with roles in either 1) or 2) are returned....
			if($u->isGod()){
				return $this->getusers();
			}
			else {
				$cids = $u->getAdministeredCollections();
				$dids = $u->getAdministeredDatasets();
				if(count($cids) == 0 && count($dids) == 0){
					//false
				}
				$uids  = $this->dbman->getUsersInContext($cids, $dids);
			}
		}
		elseif(!$did){
			//we are in a collection level context
			//if u has admin rights...
			if($u->isGod() || $u->isCollectionAdmin($cid)){
				$uids  = $this->dbman->getUsersInContext(array($cid), array());
			}
			else {
				$dids = $u->getAdministeredDatasets($cid);
				if(count($dids) == 0){
					//false;
				}
				$uids  = $this->dbman->getUsersInContext(array(), $dids);
			}
		}
		else {
			if($u->isGod() or $u->isCollectionAdmin($cid) or $u->isDatasetAdmin($cid, $did)){
				$uids =  $this->dbman->getUsersInContext(array(), array($did));
			}
			else {
				return false;//error
			}
			//we are in a dataset level context
		}
		$users = array();
		foreach($uids as $id){
			$users[] = $this->getUser($id);
		}
		return $users;
	}
	
	function getCandidatesSQL () {
		$result = $this->dbman->getCandidateStatsSql();
		return $result;
	}
	
	function getUserCandidatesSQL ($id) {
		$result = $this->dbman->getUserCandidateStatsSql($id);
		return $result;
	}
	
	function getTotalCandidatesNumber () {
		$result = $this->dbman->getTotalCandidatesSql();
		return $result;
	}
	
	function getCandidatesSQLDated ($startDate, $endDate) {
		$result = $this->dbman->getCandidateStatsSqlDated($startDate, $endDate);
		return $result;
	}
	
	function getUserCandidatesSQLDated ($startDate, $endDate, $id, $username) {
		$result = $this->dbman->getUserCandidateStatsSqlDated($startDate, $endDate, $id, $username);
		return $result;
	}
	
}

class StatisticsDacuraAjaxServer extends StatisticsDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}