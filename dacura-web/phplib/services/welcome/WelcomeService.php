<?php

class WelcomeService extends DacuraService {

	function handlePageLoad($sc=false){
		$this->renderScreen("view", array());
		
	}
}