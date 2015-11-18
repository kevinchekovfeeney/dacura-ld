<?php

include_once("LdDacuraServer.php");

class LdService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		//use explicit path as this is called in multiple contexts
		$ldscript = $this->settings['path_to_services']."ld/dacura.ld.js";
		$this->writeIncludedInterpolatedScripts($ldscript);
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css', "ld").'">';
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
	
	function getEntityStatusOptions(){
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
	
	function getOptionalArgs(){
		$args = array();
		if(isset($_GET['version'])) $args['version'] = $_GET['version'];
		if(isset($_GET['mode'])){
			$args['mode'] = $_GET['mode'];
		}
		else {
			$args['mode'] = "view";
		}
		if(isset($_GET['format'])){
			$args['format'] = $_GET['format'];
		}
		else {
			$args['format'] = "json";
		}
		if(isset($_GET['display'])) {
			$args['display'] = $_GET['display'];
		}
		else {
			$args['display'] = "ns_links_typed_problems";
		}
		$args['options'] = array("history");
		return $args;		
	}
	
	
	function handlePageLoad($dacura_server){
		$params = array("image" => $this->url("image", "buttons/knowledge.png"));
		if($this->screen == "list"){
			$params["breadcrumbs"] = array(array(), array());
			$params["title"] = "Entity Data";
			$params["subtitle"] = "A list of the managed entities in the system";
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
				$params['status_options'] = $this->getEntityStatusOptions();				
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
	
}
