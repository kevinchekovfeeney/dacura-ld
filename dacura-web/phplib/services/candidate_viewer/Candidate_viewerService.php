<?php

include_once("Candidate_viewerDacuraServer.php");

class Candidate_viewerService extends DacuraService {
	
	function handleServiceCall(){
		$bds = new Candidate_viewerDacuraServer($this->settings);
		$u = $bds->sm->getUser();
		$params = array();
		$params['user'] = $u->email;
		$this->renderScreen("work", $params);
	}
	
}