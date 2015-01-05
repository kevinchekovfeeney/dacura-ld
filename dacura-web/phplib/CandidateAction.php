<?php

/*
 * Class representing an action that has been taken on a Dacura Candidate
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */


class CandidateAction extends DacuraObject {
	var $id;
	var $candid;
	var $chunkid;
	var $acttime;
	var $userid;
	var $decision;
	var $update;

	function __construct($i, $can, $chu, $t, $u, $d, $upd){
		$this->id = $i;
		$this->candid = $can;
		$this->chunkid = $chu;
		$this->acttime = $t;
		$this->userid = $u;
		$this->decision = $d;
		$this->update = $upd;
	}
}
