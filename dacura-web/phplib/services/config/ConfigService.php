<?php

include_once("configDacuraServer.php");

class ConfigService extends DacuraService {
	
	
	function handlePageLoad(){
		if(count($this->servicecall->args)> 0) {
			$screen = array_shift($this->servicecall->args);
			if($screen == 'create'){
				$this->renderScreen("create", array("id" => $c_id));						
			}
			elseif($screen == "" or $screen == "view"){
				$this->renderScreen("view", array());
			}
			else {
				$this->renderScreen("error", array("title" => "Unknown URL", "message" => "The $screen page does not exist in the collection service."), "core");
			}
		}
		else {
			$this->renderScreen("view", array());
		}
		//parent::handlePageLoad();
	}
	
}