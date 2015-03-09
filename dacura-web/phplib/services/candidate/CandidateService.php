<?php

include_once("CandidateDacuraServer.php");

class CandidateService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "test";
	
	function handlePageLoad($dacura_server){
		$this->renderScreen("test", array());
	}
		
}