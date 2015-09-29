<?php

require_once("EntityCreateRequest.php");

class CandidateCreateRequest extends EntityCreateRequest {
	
	function __construct($id, $schema){
		$this->version = 1;
		parent::__construct($id);
		$this->schema = $schema;
		$this->cwurl = $this->schema->instance_prefix."/".$id;
	}
	
	function isCandidate(){
		return true;
	}
}

