<?php

include_once("LdDacuraServer.php");

class LdService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "list";
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	
	function init(){
		$this->included_css[] = $this->get_service_file_url('style.css', "ld");
	}
		
	function getUpdateStatusOptions(){
		return $this->getEntityStatusOptions();
	}
	
	function getEntityStatusOptions(){
		$opts = DacuraObject::$valid_statuses;
		$html = "";
		foreach($opts as $v => $l){
			$html .= "<option value='$v'>$l</option>";
		}
		return $html;			
	}

	function getCreateStatusOptions(){
		$opts = LDEntity::$entity_types;
		$html = "";
		foreach($opts as $v => $l){
			$html .= "<option value='$v'>$l</option>";
		}
		return $html;
	}
	
	/*
	 * The various optional arguments supported by the linked data api
	 */
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
	
	function getScreenForCall(){
		if($this->screen == "list"){
			return "list";
		}
		return "view";
	}
	
	function getParamsForScreen($screen, &$dacura_server){
		$params = array("image" => $this->furl("image", "buttons/ld.png"));
		$params['dt'] = true;
		if($screen == "list"){
			$params["breadcrumbs"] = array(array(), array());
			if($this->getCollectionID() != "all"){
				$this->dacura_tables['ld']['datatable_options']['aoColumns'][2] = array("bVisible" => false);
				$this->dacura_tables['updates']['datatable_options']['aoColumns'][3] = array("bVisible" => false);
			}
			$params['entity_datatable'] = $this->getDatatableSetting("ld");
			$params['update_datatable'] = $this->getDatatableSetting("updates");				
			$params["title"] = "Linked Data Entities";
			$params["subtitle"] = "A list of the linked data entities managed in the system";
			if($this->collection_id == "all"){
				$params['show_collection'] = true;
			}
			$params['status_options'] = $this->getCreateStatusOptions();
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
			//$this->renderToolHeader($params);
			if(isset($_GET['mode'])) $params['mode'] = $_GET['mode'];
			if(isset($_GET['version'])) $params['version'] = $_GET['version'];
			if(isset($_GET['format'])) $params['format'] = $_GET['format'];
			if(isset($_GET['display'])) $params['display'] = $_GET['display'];
		}
		return $params;
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
