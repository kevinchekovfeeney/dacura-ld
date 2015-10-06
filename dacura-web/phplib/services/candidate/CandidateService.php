<?php

include_once("CandidateDacuraServer.php");

class CandidateService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.candidate.js");
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css', "ld").'">';
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css').'">';
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent-nopad'>";
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}
		
	function getUpdateStatusOptions(){
		$opts = array(
				"pending", "accept", "reject", "deleted"
		);
		$html = "";
		foreach($opts as $o){
			$html .= "<option value='$o'>$o</option>";
		}
		return $html;	
	}
	
	function getCandidateStatusOptions(){
		$opts = array(
			"pending", "accept", "reject", "deleted"
		);
		$html = "";
		foreach($opts as $o){
			$html .= "<option value='$o'>$o</option>";
		}
		return $html;
	}

	function getCreateStatusOptions(){
		$opts = array(
				"candidate", "report", "interpretation"
		);
		$html = "";
		foreach($opts as $o){
			$html .= "<option value='$o'>$o</option>";
		}
		return $html;
	}
	
	
	function handlePageLoad($dacura_server){
		if($this->screen == "list"){
			$params = array(
				"breadcrumbs" => array(array(), array()),
				"title" => "Instance Data",
				"subtitle" => "Incoming",
				"description" => "View the instance data flowing into the dataset",
			);
			$this->renderToolHeader($params);	
			if($this->collection_id == "all"){
				$params['show_collection'] = true;
				$params['show_dataset'] = false;
			}
			elseif($this->dataset_id == "all"){
				$params['show_dataset'] = false;				
			}
			$params['status_options'] = $this->getCreateStatusOptions();
			$this->renderScreen("list", $params);
		}
		elseif($this->screen == "test"){
			$params = array(
				"breadcrumbs" => array(array("", "API Test Tool"), array()),
				"title" => "Candidate API",
				"subtitle" => "Test Interface",
				"description" => "Direct low-level access to the candidate API");
			$this->renderScreen("test", $params);				
		}
		else {
			if($this->args && $this->screen == 'update'){
				$id = "update/".implode("/", $this->args);
			}
			elseif($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$params = array("id" => $id, 
					"title" => "Instance Data",
					"subtitle" => "Entity View",
					"breadcrumbs" => array(array(), array()),
					"description" => "Navigate, view and update your instance data entities"
			);
			if($this->screen == 'update'){
				$params['status_options'] = $this->getUpdateStatusOptions();
			}
			else {
				$params['status_options'] = $this->getCandidateStatusOptions();				
			}
			$this->renderToolHeader($params);
			if(isset($_GET['mode'])) $params['mode'] = $_GET['mode'];				
			if(isset($_GET['version'])) $params['version'] = $_GET['version']; 
			if(isset($_GET['format'])) $params['format'] = $_GET['format']; 
			if(isset($_GET['display'])) $params['display'] = $_GET['display']; 
			$params["entity"] = "Candidate";
			$this->renderScreen("view", $params);
		}
		$this->renderToolFooter($params);
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