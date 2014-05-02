<?php
include_once("UsersDacuraServer.php");

class UsersService extends DacuraService {
	
	//cid/did/users/userid
	
	function handlePageLoad(){
		if(count($this->servicecall->args)> 0) {
			$userid = array_shift($this->servicecall->args);
			$this->renderScreen("view", array("userid" => $userid));						
		}
		else {
			$this->renderScreen("view", array());
		}
		//parent::handlePageLoad();
	}
	
}