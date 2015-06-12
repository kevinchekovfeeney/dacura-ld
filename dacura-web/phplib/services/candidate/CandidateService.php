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
		$params = array();
		if($this->screen == "list"){
			if($this->collection_id == "all"){
				$params['show_collection'] = true;
				$params['show_dataset'] = true;
			}
			elseif($this->dataset_id == "all"){
				$params['show_dataset'] = true;				
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
			$params = array("id" => $id);
			if(isset($_GET['version'])) $params['version'] = $_GET['version']; 
			if(isset($_GET['format'])) $params['format'] = $_GET['format']; 
			if(isset($_GET['display'])) $params['display'] = $_GET['display']; 
			$this->renderScreen("view", $params);
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