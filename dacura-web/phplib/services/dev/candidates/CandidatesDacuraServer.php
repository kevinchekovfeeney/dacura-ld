<?php
include_once("phplib/DacuraServer.php");
include_once("phplib/db/CandidatesDBManager.php");

class CandidatesDacuraServer extends DacuraServer {

	var $dbclass = "CandidatesDBManager";
	
	function getDataTablesOutput(){
		$startRec = intval($_GET['iDisplayStart']);
		$numRec = intval($_GET['iDisplayLength']);
		$sortDirection = $_GET['sSortDir_0'];
		$sortby = $_GET['iSortCol_0'];
		
		$totalCount = $this->countOfRecords();
		$resultArray = $this->getRecords($numRec, 0);

		$output = array(
				"sEcho" => intval($_GET['sEcho']),
				"iTotalRecords" => $totalCount,
				"iTotalDisplayRecords" => $totalCount,
				"aaData" => $resultArray,
				"search" => ""
		);
		return $output;
	}
	
	function countOfRecords(){
		//first get the count of all records
		$sel = "SELECT count(*) as count from create_candidates";
		$ps = array();
		$res = $this->dbman->doSelect($sel, $ps);
		return $res[0]['count'];
	}
	
	function countOfStateRecords($id){
		//first get the count of all records
		$sel = "SELECT count(*) as count from candidate_state where candid=?";
		$ps = array($id);
		$res = $this->dbman->doSelect($sel, $ps);
		return $res[0]['count'];
	}
	
	function getStateTablesOutput($id){
		$startRec = intval($_GET['iDisplayStart']);
		$numRec = intval($_GET['iDisplayLength']);
		$sortDirection = $_GET['sSortDir_0'];
		$sortby = $_GET['iSortCol_0'];
	
		$totalCount = $this->countOfStateRecords($id);
		$resultArray = $this->getStateRecords($id, $numRec, $startRec);
		
		
		
		$output = array(
				"sEcho" => intval($_GET['sEcho']),
				"iTotalRecords" => $totalCount,
				"iTotalDisplayRecords" => $totalCount,
				"aaData" => $resultArray,
				"search" => ""
		);
		
		return $output;
	}
	
	function getStateRecords($id, $num, $start=0) {
		global $service;
		$sel = "SELECT stateid as sid, userid, processid, decision, stime, decision as action FROM candidate_state WHERE candid=2 LIMIT $start, $num";
		$ps = array();//$start, $num);
		$res = $this->dbman->doSelect($sel, $ps);
		/*foreach($res as $i => $r){
		$r2 = $this->dbman->doSelect("SELECT count(stateid) as count FROM candidate_state where candid=".$r['candid'], $ps);
				$res[$i]['actions'] = $r2[0]['count'];
			$href = $service->get_service_url("candidates", array($r['candid']));
						foreach($r as $j => $k){
						$res[$i][$j] = "<a href='$href'>".$k."</a>";
						}
						$res[$i]['DT_RowId'] = "dc_candidate_".$r['candid'];
	
		}*/

		return $res;
	
	}
	
	function getRecords($num, $start=0) {
		global $service;
		$sel = "SELECT candidateid as candid, createtime as submitted, sourceid as source, sourcedate as sourcedate, 
				clientid as client, sourcepermid as permid, sourceid as status, target as cached FROM create_candidates LIMIT $start, $num";
		$ps = array();
		$res = $this->dbman->doSelect($sel, $ps);
		foreach($res as $i => $r){
			$r2 = $this->dbman->doSelect("SELECT count(stateid) as count FROM candidate_state where candid=".$r['candid'], $ps);
			$res[$i]['actions'] = $r2[0]['count'];
			$href = $service->get_service_url("candidates", array($r['candid']));
			foreach($r as $j => $k){
				$res[$i][$j] = "<a href='$href'>".$k."</a>";
			}
			$res[$i]['DT_RowId'] = "dc_candidate_".$r['candid'];
						
		}
		return $res;
		
	}
	
	function getRecordDetails($id){
		$sel = "SELECT candidateid as candid, createtime as submitted, sourceid as source, sourcedate as sourcedate,
		clientid as client, sourcepermid as permid, sourceid as status, target as target, cacheable as cached, contents as contents FROM create_candidates WHERE candidateid=?";
		$ps = array($id);
		$res = $this->dbman->doSelect($sel, $ps);
		if(isset($res[0])) return $res[0];
		return array("x");
		//$this->errmsg = $this->dbman->errmsg;
		//return false;
	}
	
	
}

