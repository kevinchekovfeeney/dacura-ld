<?php
/*
 * Class representing common functionality that is available to all objects
 *
 * Created By: Chekov
 * Creation Date: 25/12/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

class DacuraObject {
	var $errmsg;
	var $errcode;
	
	static $statuses = array("accept" => "Active", "reject" => "Rejected", "pending" => "Pending Approval", "delete" => "Deleted");
	
	function failure_result($msg, $code = 500){
		$this->errmsg = $msg;
		$this->errcode = $code;
		return false;
	}	
}