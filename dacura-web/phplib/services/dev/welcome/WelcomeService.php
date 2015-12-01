<?php

include "WelcomeDacuraServer.php";

class WelcomeService extends DacuraService {
	
	var $protected_screens = array("view" => array("user", "seshat", "all"));
	
	
	function handlePageLoad($server){
		$params = array();
		$u = $server->getUser();
		$params['user'] = $u->email;
		$params['grabscript'] = $this->get_service_url("scraper", array("grabscript"), "rest", "seshat", "all");
		$this->renderScreen("view", $params);
	}
}