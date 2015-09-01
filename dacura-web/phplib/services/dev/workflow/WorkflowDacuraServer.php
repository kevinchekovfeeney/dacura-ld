<?php
class WorkflowDacuraServer extends DacuraServer {
	function getWorkflow($wid){}
	function getWorkflowInContext($cid, $did){}
	function addWorkflow($obj){}
	function updateWorkflow($id, $obj){}
	function deleteWorkflow($id){}
}

class WorkflowDacuraAjaxServer extends WorkflowDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}