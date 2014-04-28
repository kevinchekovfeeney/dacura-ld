<?php

include_once("DatasetDacuraServer.php");

class DatasetService extends DacuraService {
	
	function handleServiceCall($sc = false){
		if(!$sc) $sc = $this->servicecall;
		if($this->collection_id == ""){
			$this->renderScreen("error", array("title" => "Error: No Collection ID defined", "message" => "Datasets only exist within collections, to keep them tidy"));	
		}
		elseif($this->dataset_id == ""){
			if($sc->args[0] == 'create'){
				$this->renderScreen("create", array());
			}
			else {
				$this->renderScreen("error", array("title" => "Error: No Dataset ID defined", "message" => "You must specify the id of a dataset to access it"));
				
			}
		}						
		else {
			parent::handleServiceCall($sc);				
		}
	}
	
}