<?php
/*
 * Scraper Service
 * scrapes data from seshat wiki and dumps it in a tsv
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Licence: GPL v2
 */

include_once("ScraperDacuraServer.php");

class ScraperService extends DacuraService {

	var $default_screen = "scraper";
	var $public_screens = array();
	var $protected_screens = array("scraper" => array("admin"));

	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.scraper.js");
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent'>";
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}
	
}
