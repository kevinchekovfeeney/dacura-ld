<?php 
require_once("phplib/services/ld/LdService.php");
require_once("CandidateDacuraServer.php");
class CandidateService extends LdService {
	
	function init(){
		parent::init();
		$this->included_scripts[] = $this->get_service_script_url("dacura.frame.js");
		
	}
	
}