<?php
include_once("CandidateDacuraServer.php");

class CandidateService extends LdService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.candidate.js");
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
		$params["image"] = $this->url("image", "buttons/reports.png");
		
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
			$params['args'] = $this->getOptionalArgs();
			$params["entity_type"] = "Candidate";
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
				$params['status_options'] = $this->getEntityStatusOptions();				
			}
			$this->renderToolHeader($params);
			$params['args'] = $this->getOptionalArgs();
			$params["entity_type"] = "Candidate";
			$this->renderScreen("view", $params);
		}
		$this->renderToolFooter($params);
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