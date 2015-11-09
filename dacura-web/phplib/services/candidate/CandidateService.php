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
		//echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css').'">';
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
		$params = array();
		$params['topbreadcrumb'] = "All Instance Data";
		$params['collectionbreadcrumb'] = "instance data";
		$params["title"] = "Instance Data Viewer";
		$params["image"] = $this->url("image", "buttons/publishing.png");
		
		if($this->screen == "list"){		
			if($this->collection_id == "all"){
				$params['show_collection'] = true;
				$params['show_dataset'] = false;
			}
			else {
				if($this->dataset_id == "all"){			
					$params['show_dataset'] = false;			
				}
				$params["breadcrumbs"] = array(array(), array());				
			}
			$this->renderToolHeader($params);	
			$params['status_options'] = $this->getCreateStatusOptions();
			$this->renderScreen("list", $params);
		}
		elseif($this->screen == "test"){
			$params["breadcrumbs"] = array(array(array("", "API Test Tool")), array());
			$params["title"] = "Candidate API";
			$params["subtitle"] = "Test Interface";
			$this->renderScreen("test", $params);				
		}
		else { //view page
			$params["breadcrumbs"] = array(array(), array());
			if($this->args && $this->screen == 'update'){
				$id = "update/".implode("/", $this->args);
			}
			elseif($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$params["id"] = $id; 

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
	
	/*
	 * The screens are the ids of ontologies
	 * There are no candidate level access control rules
	 * This function saves the context and ensures that the user has view candidate permission.
	 */
	function userCanViewScreen($user){
		$sc = $this->screen;
		$this->screen = "view";
		$ans = parent::userCanViewScreen($user);
		$this->screen = $sc;
		return $ans;
	}

}