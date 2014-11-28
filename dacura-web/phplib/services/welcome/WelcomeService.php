<?php

class WelcomeService extends DacuraService {

	function handlePageLoad($sc=false){
		$ds = new DacuraServer($this);
		$u = $bds->userman->getUser();
		$params['user'] = $u->email;
		$this->renderScreen("view", $params);
		
	}
}