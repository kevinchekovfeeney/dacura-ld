<?php

class OntologyCreateRequest extends EntityCreateRequest {
	
	function validate($obj = false){
		return true;
	}
	
	/*
	 * We don't expand ontologies - they can contain blank nodes, etc
	 */
	function expand($allow_demand_id = false){
		return true;
	}

}