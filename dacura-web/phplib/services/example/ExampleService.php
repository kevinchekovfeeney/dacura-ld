<?php
/*
 * This is an example service designed to showcase the various features provided to services by the dacura platform
 */

include_once("ExampleDacuraServer.php");

class ExampleService extends DacuraService {
	
	//Arrays that define the security configuration of each screen
	var $public_screens = array("hello");
	var $default_screen = "hello";
	var $protected_screens = array("test" => array("admin"));
	
	/*
	 * Methods which allow you to specify common html headers and footers that will
	 * apply to every screen in the service
	 */
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		//interpolated scripts are interpreted by PHP and concatenated into the body of the page in script tags.
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.example.js");
		//service specific style sheet - all services also include master.css 
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
				"withdrawn", "pending", "accept", "reject", "deleted"
		);
		$html = "";
		foreach($opts as $o){
			$html .= "<option value='$o'>$o</option>";
		}
		return $html;	
	}
	
	function getCandidateStatusOptions(){
		$opts = array(
			"withdrawn", "pending", "accept", "reject", "deleted"
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
				//"breadcrumbs" => array(array(array("", "Candidate Queue")), array()),
				"title" => "Candidates",
				"subtitle" => "View the candidates and updates submitted to the Dacura API");
			$this->renderToolHeader($params);	
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
			$params = array(
				"breadcrumbs" => array(array("", "API Test Tool"), array()),
				"title" => "Candidates API Test Interface",
				"subtitle" => "Direct low-level access to the candidate API");
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
					"title" => "Candidates",
					"breadcrumbs" => array(array(), array()),
					"subtitle" => "View candidates in the Dacura system"
			);
			if($this->screen == 'update'){
				$params['update_view'] = true;	
			}
			$this->renderToolHeader($params);
			if(isset($_GET['version'])) $params['version'] = $_GET['version']; 
			if(isset($_GET['format'])) $params['format'] = $_GET['format']; 
			if(isset($_GET['display'])) $params['display'] = $_GET['display']; 
			$this->renderScreen("nview", $params);
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