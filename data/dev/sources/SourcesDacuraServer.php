<?php
include_once("phplib/DacuraServer.php");
//include_once("UsersSystemManager.php");

class SourcesDacuraServer extends DacuraServer {
	function getSourcesList($cid, $dsid){
				
	}
	
}

class SourcesDacuraAjaxServer extends SourcesDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}