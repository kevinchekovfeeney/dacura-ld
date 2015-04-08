<?php

include_once("CandidateDacuraServer.php");

class CandidateService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.candidate.js");
	}
	
	function handlePageLoad($dacura_server){
		if($this->screen == "list"){
			if($this->collection_id == "all"){
				$this->params['show_collection'] = true;
				$this->params['show_dataset'] = true;
			}
			elseif($this->dataset_id == "all"){
				$this->params['show_dataset'] = true;				
			}
			$this->renderScreen("list", $params);
		}
		elseif($this->screen == "test"){
			$this->renderScreen("test", $params);				
		}
		else {
			if($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$this->renderScreen("view", array("id" => $id));
		}
	}
	
	function loadArgsFromBrowserURL($sections){
		if(count($sections) > 0){
			$this->screen = array_shift($sections);
			$this->args = $sections;
		}
		else {
			$this->screen = $this->default_screen;
		}
	}
	function isPublicScreen(){
		return true;
	}
}