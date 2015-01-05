<?php

/*
 * Class representing a candidate in the Dacura DB
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

include_once("CandidateAction.php");

class Candidate extends DacuraObject {
	var $id;
	var $chunkid;
	var $permid;
	var $history = array(); //all the CandidateAction events that have been recorded against this candidate
	var $contents;

	function __construct($id, $chunkid, $permid){
		$this->id = $id;
		$this->chunkid = $chunkid;
		$this->permid = $permid;
	}

	function setContents($co){
		$this->contents = $co;
	}

	function addHistoryEvent($h){
		$this->history[] = $h;
	}

	function setHistory($h){
		$this->history = $history;
	}
}