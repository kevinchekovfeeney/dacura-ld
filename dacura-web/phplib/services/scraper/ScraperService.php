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

	var $default_screen = "main";
	var $public_screens = array("test", "syntax");
	var $protected_screens = array("export" => array("admin"), "main" => array("admin"));

	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.scraper.js");
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css').'">';
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent'>";
	}
	
	function handlePageLoad($server){
		$params = array(
				"title" => "Wiki Scraper",
				"subtitle" => "A Tool for extracting structured data from the Seshat Wiki");
		$params['screen'] = $this->screen;
		//if($this->screen == "syntax"){
		//	$params["examples"] = $server->parseCanonicalExamples();
		//}
		$this->renderToolHeader($params);
		$this->renderScreen($this->screen, $params);
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}
	
}
