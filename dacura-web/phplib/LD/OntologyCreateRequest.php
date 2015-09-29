<?php
require_once("EntityCreateRequest.php");

class OntologyCreateRequest extends EntityCreateRequest {
	function isOntology(){
		return true;
	}

}