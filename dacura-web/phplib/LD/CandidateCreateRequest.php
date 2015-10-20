<?php

require_once("EntityCreateRequest.php");

class CandidateCreateRequest extends EntityCreateRequest {
	
	function __construct($id){
		$this->version = 1;
		parent::__construct($id);
	}
	
	function isCandidate(){
		return true;
	}
}

