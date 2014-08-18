<?php

include_once("phplib/SystemManager.php");

/*
 * Here goes the db access functions that are only used by this service
 */

class UsersSystemManager extends SystemManager {
	
	function getUsersInContext($cids, $dids){
		try {
			$uids = array();
			$sql = "SELECT distinct userid AS uid from user_roles";				
			if(count($cids) > 0){
				$inQuery = implode(',', array_fill(0, count($cids), '?'));
				$stmt = $this->link->prepare($sql. " WHERE collectionid IN($inQuery)");
				$stmt->execute($cids);
				$uids = $stmt->fetchAll(PDO::FETCH_COLUMN);
			}
			if(count($dids) > 0){
				$inQuery = implode(',', array_fill(0, count($dids), '?'));
				$stmt = $this->link->prepare($sql. " WHERE datasetid IN($inQuery)");
				$stmt->execute($dids);
				$duids = $stmt->fetchAll(PDO::FETCH_COLUMN);
				foreach($duids as $duid){
					if(!in_array($duid, $uids)){
						$uids[] = $duid;
					}
				}
			}
			return $uids;
		}
		catch(PDOException $e){
			$this->errmsg = "PDO Error".$e->getMessage();
			return false;
		}
	}
	
}

