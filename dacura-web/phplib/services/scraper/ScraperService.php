<?php
include_once("ScraperDacuraServer.php");

class ScraperService extends DacuraService {

	var $default_screen = "scraper";

	function handlePageLoad($sc = false){
		echo "<div id='fullscreen-tool'>";
		
		parent::handlePageLoad($sc);
		echo "</div>";
	}
}
