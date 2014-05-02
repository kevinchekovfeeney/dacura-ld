<?php

class DacuraService{
	var $settings;
	var $errcode;
	var $errmsg;
	var $connection_type;
	var $servicename = "abstract_base_class";
	var $mydir;
	var $myname;
	var $servicecall;
	var $collection_id;
	var $dataset_id;
	var $collection_context;
	var $dataset_context;
	var $default_screen = "view";

	var $html_screens = array();

	function __construct($settings){
		$this->settings = $settings;
	}

	function getIndexPath(){
		return $this->mydir."index.php";
	}
	
	function getCollectionID(){
		return ($this->collection_id == "0" ? "" : $this->collection_id);
	}

	function getDatasetID(){
		return ($this->dataset_id == "0" ? "" : $this->dataset_id);
	}
	
	
	/*
	 * to provide url services to html files...
	*/
	
/*
 * Service calls are more complex as they include the collection/dataset id and may include parameters and may come through multiple interfaces
 */
	function get_service_url($servicen = false, $args = array(), $interface="html", $col_id = false, $ds_id = false){
		$args_ext = (count($args) > 0) ? "/".implode("/", $args) : "";
		$servicen = ($servicen ? $servicen : $this->servicename);
		if($servicen == 'login'){
			return $this->settings['install_url']."login".$args_ext;
		}
		else {
			$api_bit = ($interface == "api" ? "api/" : "");
			$col_bit = ($col_id ? $col_id : $this->collection_id)."/";
			$ds_bit = ($ds_id ? $ds_id : $this->dataset_id)."/";
			return $this->settings['install_url'].$api_bit.$col_bit.$ds_bit.$servicen.$args_ext;
		}
	}

	//url associated with a file in a particular collection or dataset (http)
	function get_cds_url($fname, $col_id = false, $ds_id = false){
		$col_bit = ($col_id ? $col_id : $this->collection_id)."/";
		$ds_bit = ($ds_id ? $ds_id : $this->dataset_id)."/";
		return $this->settings['collections_url'].$col_bit.$ds_bit.$fname;		
	}
	
	//url associated with a file in the local service (http)
	function get_service_file_url($fname, $servicen = false){
		$servicen = ($servicen ? $servicen : $this->servicename);
		return $this->settings['services_url'].$servicen."files/".$fname;	
	}
	
	function url($type, $name, $c = false, $d = false){
		if($type == 'service'){
			return $this->get_service_file_url($name, $c);
		}
		elseif($type == "collection"){
			return $this->get_cds_url($name, $c, $d);			
		}
		else return $this->get_system_file_url($type, $name);
	}
	

	function my_url($interface = "html"){
		$api_bit = ($interface == "api" ? "api/" : "");
		return $this->settings['install_url'].$api_bit.$this->collection_id."/".$this->dataset_id."/".$this->servicename;
	}
	
	//these are all html -> all api access is via services.
	/*
	 * System calls are for files (css, img, etc)
	*/
	function get_system_file_url($type, $name){
		if($type == "js"){
			$ext_bit = $this->settings['files_url']."js/";
		}
		elseif($type == "css"){
			$ext_bit = $this->settings['files_url']."css/";
		}
		else {
			$ext_bit = $this->settings['files_url']."images/";
		}
		return $ext_bit.$name;
	}
	
	
	function load($sc){
		$this->servicename = $sc->servicename;
		$this->servicecall = $sc;
		$this->collection_id = $sc->collection_id;
		$this->dataset_id = $sc->dataset_id;
		$this->connection_type = $sc->provenance;
		$this->mydir = $this->settings['path_to_services'].$this->servicename."/";
	}

	function hasScreen($screen){
		return file_exists($this->mydir."screens/$screen.php");
	}

	function renderScreen($screen, $params, $other_service = false){
		global $dacura_settings;
		$service =& $this;
		if($other_service){
			$f = $this->settings['path_to_services'].$other_service."/screens/$screen.php";
			if(file_exists($f)){				
				include_once($f);
			}
			else {
				return $this->renderScreen("error", array("title" => "Navigation Error", "message" => "No '$screen' page found in $other_service service"), "core");
			}
		}
		else {
			$f = $this->mydir."screens/$screen.php";
			if(file_exists($f)){
				include_once($f);
			}
			else {
				return $this->renderScreen("error", array("title" => "Navigation Error", "message" => "No '$screen' page found"), "core");
			}
		}
	}

	function handlePageLoad($sc = false){
		if(!$sc) $sc = $this->servicecall;
		if(count($sc->args) > 0){
			$screen = array_shift($sc->args);
		}
		else {
			$screen = $this->default_screen;
		}
		$params = array();
		for($i = 0; $i < count($sc->args); $i+=2){
			$params[$sc->args[$i]] = (isset($sc->args[$i + 1]) ? $sc->args[$i + 1] : "");
		}
		$this->renderScreen($screen, $params);
	}
}
