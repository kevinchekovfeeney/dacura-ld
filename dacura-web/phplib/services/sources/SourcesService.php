<?php
//include_once("SourcesDacuraServer.php");

class SourcesService extends DacuraService {
	//cid/did/users/userid
	function handlePageLoad(){
		if(count($this->servicecall->args)> 0) {
			$sourcesid = array_shift($this->servicecall->args);
			$this->renderScreen("view", array("userid" => $sourcesid));						
		}
		else {
			$this->renderScreen("view", array());
		}
		//parent::handlePageLoad();
	}
}
