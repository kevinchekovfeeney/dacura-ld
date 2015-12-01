<?php
include_once("CandidateDacuraServer.php");

class CandidateService extends LdService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	

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
	
	function getScreenForCall($dacura_server){
		if($this->screen == "list") return "list";
		return "view";		
	}
	
	function getParamsForScreen($screen, $dacura_server){
		$params = array();
		$params['topbreadcrumb'] = "All Instance Data";
		$params['collectionbreadcrumb'] = "instance data";
		$params["title"] = "Instance Data Viewer";
		$params["image"] = $this->url("image", "buttons/candidate.png");
		$params['status_options'] = $this->getCreateStatusOptions();
		$params['args'] = $this->getOptionalArgs();
		$params["entity_type"] = "Candidate";
		if($screen == "list"){
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
		}
		else {
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
		}
		return $params;
	}	
}