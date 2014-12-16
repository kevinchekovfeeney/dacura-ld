<?php

/*
 * Class representing an invocation of a service
 * This is extended by actual service classes
 * The base class contains common functionality for producing service paths and urls for the current context
 *
 * Also where the access control will be inserted...
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */

class DacuraService{
	var $settings;  // the server wide settings that the service has been invoked in.
	var $mydir;
	var $servicename = "abstract_base_class";
	//var $servicecall;
	//Variables representing the context in which the service is invoked
	var $collection_id;
	var $dataset_id;
	var $args;	//associative array of the arguments that have been passed
	var $screen = ""; //special value passed to indicate the screen in html mode....
	var $connection_type; //html / api -> what mode has the service been invoked in..

	var $default_screen = "view";

	//Normal way of signalling errors
	var $errcode;
	var $errmsg;
	
	
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
	
	function name(){
		return $this->servicename;
	}
	
	
	/*
	 * to provide url services to html files...
	*/
	
/*
 * Service calls include the collection/dataset id and may include parameters and may come through multiple interfaces
 */
	function get_service_url($servicen = false, $args = array(), $interface="html", $col_id = false, $ds_id = false){
		$args_ext = (count($args) > 0) ? "/".implode("/", $args) : "";
		$servicen = ($servicen ? $servicen : $this->servicename);
		if($servicen == 'login'){
			return $this->settings['install_url']."login".$args_ext;
		}
		else {
			$api_bit = ($interface == $this->settings['apistr'] ? $this->settings['apistr']."/" : "");
			$col_bit = ($col_id ? $col_id : $this->collection_id)."/";
			$ds_bit = ($ds_id ? $ds_id : $this->dataset_id)."/";
			return $this->settings['install_url'].$api_bit.$col_bit.$ds_bit.$servicen.$args_ext;
		}
	}
	
	function get_service_breadcrumbs(){
		$url = $this->settings['install_url'];
		$path = array();
		$path[] = array("url" => $this->settings['install_url']."0/0/".$this->servicename, "title" => "All Collections");
		if($this->getCollectionID()){
			$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/0/".$this->servicename, "title" => $this->getCollectionID());
			if($this->getDatasetID()){
				$path[] = array("url" => $this->settings['install_url'].$this->getCollectionID()."/". $this->getDatasetID()."/".$this->servicename, "title" => $this->getDatasetID());
			}
		}
		return $path;
	}

	function getBreadCrumbsHTML(){
		$paths = $this->get_service_breadcrumbs();
		$html = "<ul class='service-breadcrumbs'>";
		foreach($paths as $i => $path){
			$n = count($path) - $i;
			if($i == 0){
				$html .= "<li class='first'><a href='".$path['url']."' style='z-index:$n;'><span></span>".$path['title']."</a></li>";
			}
			else {
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
		}
		$html .= "</ul>";
		return $html;		
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
		return $this->settings['services_url'].$servicen."/files/".$fname;	
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
		$api_bit = ($interface == $this->settings['apistr'] ? $this->settings['apistr']."/" : "");
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
	
	
	/*
	 * Loads the service context from the service call object passed in
	 */
	function load($sc){
		$this->servicename = $sc->servicename;
		//$this->servicecall = $sc;
		$this->collection_id = $sc->collection_id;
		$this->dataset_id = $sc->dataset_id;
		$this->connection_type = $sc->provenance;
		if($sc->provenance == "html"){ //if it is html, all the arguments are in the URL
			if(count($sc->args) > 0){
				$this->screen = array_shift($sc->args);
				$this->args = array();
				for($i = 0; $i < count($sc->args); $i+=2){
					$this->args[$sc->args[$i]] = (isset($sc->args[$i + 1]) ? $sc->args[$i + 1] : "");
				}
				$this->args['screen'] = $this->screen;
				//$this->args = 
			}
			else {
				$this->screen = $this->default_screen;
			}
		}
		else {
			$this->args = $sc->args;
		}
		$this->mydir = $this->settings['path_to_services'].$this->servicename."/";
		
	}

	function hasScreen($screen){
		return file_exists($this->mydir."screens/$screen.php");
	}

	function renderScreen($screen, $params, $other_service = false){
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
	
	function writeIncludedInterpolatedScripts($path){
		$service = &$this;
		echo "<script>"; 
		include_once($path);
		echo "</script>";
	}
	
	
	function renderFullPageHeader(){
		$service = &$this;
		include_once("phplib/snippets/header.php");
		include_once("phplib/snippets/topbar.php");		
	}
	
	function renderFullPageFooter(){
		$service = &$this;
		include_once("phplib/snippets/footer.php");
	}
	
	function hasPermission(){
		return true;
		//need an algorithmic way of seeing if the user has the required roles	
	}
	
	
	
	function renderFullPage(){
		$this->renderFullPageHeader();
		$this->handlePageLoad();
		$this->renderFullPageFooter();	
	}

	function handlePageLoad(){
		$this->renderScreen($this->screen, $this->args);
	}
	
}
