<?php
include_once("CandidateDacuraServer.php");

class CandidateService extends LdService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("user"), "view" => array("user"));
	

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

	function init(){
		parent::init();
		$ldscript = $this->get_service_script_url("dacura.ld.js", "ld");
		$this->included_scripts[] = $ldscript;
	}
	
	function getParamsForScreen($screen, $dacura_server){
		$params = array();
		$params['topbreadcrumb'] = "All Instance Data";
		$params['collectionbreadcrumb'] = "instance data";
		$params["title"] = "Instance Data Viewer";
		$params['dt'] = true;
		$params["image"] = $this->furl("images", "services/candidate.png");
		$params['status_options'] = $this->getCreateStatusOptions();
		$params['args'] = $this->getOptionalArgs();
		$params["ldo_type"] = "Candidate";
		if($this->getCollectionID() != "all"){
			//$this->dacura_tables['candidate']['datatable_options']['aoColumns'][2] = array("bVisible" => false);
			//$this->dacura_tables['updates']['datatable_options']['aoColumns'][3] = array("bVisible" => false);
		}
		$params['candidate_datatable'] = $this->getDatatableSetting("candidate");
		$params['update_datatable'] = $this->getDatatableSetting("updates");				
		$params["breadcrumbs"] = array(array(), array());
		if($screen == "view"){
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
				$params['status_options'] = $this->getLDOStatusOptions();
			}				
		}
		return $params;
	}	
}