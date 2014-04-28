<?php
include_once("phplib/DacuraServer.php");

class BrowseDacuraServer extends DacuraServer {
	
	function getTaskPanelParams($params){
		
	}
	
	function getStatsPanelParams($params){
		
	}
	
	function getGraphPanelParams($params){
		
	}
	function getMenuPanelParams($params){
		
	}
	function getToolPanelParams($params){
		
	}
	
	function getDataPanelParams($params){
		return false;
		//need to get a list of the collections and datasets that exist...
		if($params['collection'] && $params['dataset']){
			return false;			
		}
		if($params['collection']){
			return $this->sysman->getDatasetsWithManagementRoles($params['collection']);			
		}
		else return $this->sysman->getCollectionsWithManagementRoles();
	}
	
	
}

class BrowseDacuraAjaxServer extends BrowseDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}