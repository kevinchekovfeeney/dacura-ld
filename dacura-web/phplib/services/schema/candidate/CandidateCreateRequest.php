<?php

class CandidateCreateRequest extends LDOCreateRequest {
	var $schema;
	/*
	 * We need more stuff here : check for type?
	 */
	function loadFromAPI($obj){
		if(!isset($obj['contents']) && !isset($obj['meta'])){
			return $this->failure_result("Create Object was malformed : both meta and contents are missing", 400);
		}
		$this->ldprops = isset($obj['contents']) ? $obj['contents'] : array();
		foreach($this->ldprops as $g => $ldos){
			if(!$this->schema->hasGraph($g)){
				return $this->failure_result("No such graph as $g", 400);
			}
		}
		if(!isset($this->ldprops['main'])){
			return $this->failure_result("The main graph must be present in an ldo create request", 400);
		}
		$type = $this->getObjectType($this->ldprops['main']);
		if(!$type){
			return $this->failure_result("Instance data must specify the class of the ldo being created (in the main graph)", 400);
		}
		$this->meta = isset($obj['meta']) ? $obj['meta'] : array();
		$this->meta["type"] = $type;
		$this->version = 1;
		return true;
	}	
}
