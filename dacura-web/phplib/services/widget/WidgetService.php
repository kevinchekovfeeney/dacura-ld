<?php
/*
 * This is an example service designed to showcase the various features provided to services by the dacura platform
 */

include_once("WidgetDacuraServer.php");
require_once("Widget.php");
require_once("WidgetCreateRequest.php");
require_once("WidgetUpdateRequest.php");

class WidgetService extends LdService {
	
	/*
	 * Methods which allow you to specify common html headers and footers that will
	 * apply to every screen in the service
	 */
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.widget.js");
	}
	
	function handlePageLoad($dacura_server){
		$params = array("image" => $this->url("image", "buttons/widget.png"));
		if($this->screen == "list"){
			$params["title"] = "User Interface Widgets";
			$params["subtitle"] = "Forms which allow people to view and update the data";
			$this->renderToolHeader($params);
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
			$this->renderToolHeader($params);
			if(isset($_GET['mode'])) $params['mode'] = $_GET['mode'];
			if(isset($_GET['version'])) $params['version'] = $_GET['version'];
			if(isset($_GET['format'])) $params['format'] = $_GET['format'];
			if(isset($_GET['display'])) $params['display'] = $_GET['display'];
			$this->renderScreen("view", $params);
		}
		$this->renderToolFooter($params);
	}
	
		
}