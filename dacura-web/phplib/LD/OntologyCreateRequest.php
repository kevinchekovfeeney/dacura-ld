<?php
require_once("EntityCreateRequest.php");

class OntologyCreateRequest extends EntityCreateRequest {
	function isOntology(){
		return true;
	}
	
	function validate($obj = false){
		return true;
	}
	
	function expand($allow_demand_id = false){
		return true;
	}

}