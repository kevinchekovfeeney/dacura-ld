<?php
/*
 * This is an example service designed to showcase the various features provided to services by the dacura platform
 */

include_once("WidgetDacuraServer.php");
require_once("Widget.php");
require_once("WidgetCreateRequest.php");
require_once("WidgetUpdateRequest.php");

class WidgetService extends LdService {
	
	function getParamsForScreen($screen, &$dacura_server){
		$params = array("image" => $this->url("image", "buttons/widget.png"));
		if($screen == "list"){
			$params["title"] = "User Interface Widgets";
			$params["subtitle"] = "Forms which allow people to view and update the data";
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
			$params["id"] = $id;
			$params["title"] = "Entity Data";
			$params["subtitle"] = "Entity View";
			$params["breadcrumbs"] = array(array(), array());
			$params["description"] = "Navigate, view and update your managed entities";
			if($this->screen == 'update'){
				$params['status_options'] = $this->getUpdateStatusOptions();
			}
			else {
				$params['status_options'] = $this->getEntityStatusOptions();
			}
			if(isset($_GET['mode'])) $params['mode'] = $_GET['mode'];
			if(isset($_GET['version'])) $params['version'] = $_GET['version'];
			if(isset($_GET['format'])) $params['format'] = $_GET['format'];
			if(isset($_GET['display'])) $params['display'] = $_GET['display'];				
		}
		return $params;
	}
	
		
}