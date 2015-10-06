<?php

include_once("LDDacuraServer.php");

class LDService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.ld.js");
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
			"candidate", "report", "interpretation", "ontology", "graph"
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
				"title" => "Entity Data",
				"subtitle" => "A list of the managed entities in the system",
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
			$entity = "entity";
			$this->renderScreen("list", $params);
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
					"title" => "Entity Data",
					"subtitle" => "Entity View",
					"breadcrumbs" => array(array(), array()),
					"description" => "Navigate, view and update your managed entities"
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