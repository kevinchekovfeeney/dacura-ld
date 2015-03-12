<?php

include_once("CandidateDacuraServer.php");

class CandidateService extends DacuraService {
	
	var $public_screens = array("test");
	var $default_screen = "test";
	
	function handlePageLoad($dacura_server){
		if($this->screen == "test"){
			$this->renderScreen("test", array());
		}
		else {
			if($this->args){
				$id = $this->screen."/".implode("/", $this->args);
			}
			else {
				$id = $this->screen;
			}
			$this->renderScreen("view", array("id" => $id));
		}
	}
	
	function loadArgsFromBrowserURL($sections){
		if(count($sections) > 0){
			$this->screen = array_shift($sections);
			$this->args = $sections;
		}
		else {
			$this->screen = $this->default_screen;
		}
	}
	function isPublicScreen(){
		return true;
	}
}