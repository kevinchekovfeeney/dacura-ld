<?php

class WorkflowService extends DacuraService {
	
	//cid/did/users/userid
	
	function handlePageLoad(){
		if(count($this->servicecall->args)> 0) {
			$workflowid = array_shift($this->servicecall->args);
			if($workflowid == "create"){
				$this->renderScreen("edit", array());
			}
			else {
				$this->renderScreen("edit", array("workflowid" => $workflowid));
			}
		}
		else {
			$this->renderScreen("view", array());
		}
		//parent::handlePageLoad();
	}
	
	
}