<?php

include_once("Candidate_viewerDacuraServer.php");

class Candidate_viewerService extends DacuraService {
	
	function handlePageLoad(){
		$bds = new Candidate_viewerDacuraServer($this);
		$u = $bds->userman->getUser();
		$params = array();
		$params['user'] = $u->email;
		$this->renderScreen("work", $params);
	}
	
}