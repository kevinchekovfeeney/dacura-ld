<?php
include_once("ScraperDacuraServer.php");

class ScraperService extends DacuraService {
	//cid/did/users/userid

	function handlePageLoad(){
		$sds = new ScraperDacuraServer($this);
		echo "<div id='fullscreen-container'>";
		echo "<div id='fullscreen-menu'>";
		echo "</div>";
		
		//$this->renderScreen("menu", $bds->getMenuPanelParams(array()), "browse");
		echo "<div id='fullscreen-content'>";
		if(count($this->servicecall->args) > 0) {
			$firstarg = array_shift($this->servicecall->args);
			$secondarg = array_shift($this->servicecall->args);
			$thirdarg = array_shift($this->servicecall->args);
			$this->renderScreen("scraper", array("userid" => "x", "sessionid" => "y"));				
		}
		else {
			$this->renderScreen("scraper", array("userid" => "x", "sessionid" => "y"));				
		}
	
	}

}
