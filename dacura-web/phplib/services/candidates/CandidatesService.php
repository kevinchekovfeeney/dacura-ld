<?php

include_once("CandidatesDacuraServer.php");
include_once("phplib/services/browse/BrowseDacuraServer.php");


class CandidatesService extends DacuraService {
	
	function handlePageLoad(){
		$bds = new CandidatesDacuraServer($this);
		echo "<div id='fullscreen-container'>";
		echo "<div id='fullscreen-menu'>";
		echo "</div>";
		
		//$this->renderScreen("menu", $bds->getMenuPanelParams(array()), "browse");
		echo "<div id='fullscreen-content'>";
		if(count($this->servicecall->args) == 0){
			$this->servicecall->args[] = "home";
			parent::handlePageLoad();
		}
		else {
			$record_id = $this->servicecall->args[0];
			$record_details = $bds->getRecordDetails($record_id);
			if($record_details){
				$record_details['record_id'] = $record_id;
				$this->renderScreen("record", $record_details);
			}
			else {
				$this->renderScreen("error", array("title" => "Record Retrieval Failure", "message" => $bds->errmsg));
			}
		}				
		echo "</div></div>";
	}
	
}