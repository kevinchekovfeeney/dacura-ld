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
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.config.js");
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent'>";
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}
	
	
}