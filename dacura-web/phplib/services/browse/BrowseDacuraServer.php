<?php

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
			return $this->dbman->getDatasetsWithManagementRoles($params['collection']);			
		}
		else return $this->dbman->getCollectionsWithManagementRoles();
	}
	
	
}

class BrowseDacuraAjaxServer extends BrowseDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}