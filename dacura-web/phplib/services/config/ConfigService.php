<?php
/*
 * Config Service - provides access to updating / editing / viewing users and roles, etc.
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 30/01/2015
 * Licence: GPL v2
 */

include_once("ConfigDacuraServer.php");

class ConfigService extends DacuraService {
	var $protected_screens = array("list" => array("admin"), "create" => array("admin"), "view" => array("admin"));
	var $default_screen = "list";
	
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir.".config.js");
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent'>";
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}
	
	/*
	 * if collections = all -> list collections (SYSTEM)
	 * if datasets = all -> view collection
	 * else => view dataset
	 */
	function handlePageLoad($dacura_server){
		if($this->getCollectionID() == "all"){
			$this->renderScreen("system", array());
		}
		elseif($this->getDatasetID() == "all"){
			$this->renderScreen("collection", array("cid" => $this->getCollectionID()));
		}
		else {
			$this->renderScreen("dataset", array("cid" => $this->getCollectionID(), "did" => $this->getDatasetID()));	
		}
	}
	
	
}