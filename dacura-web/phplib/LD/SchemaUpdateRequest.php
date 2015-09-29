<?php
require_once("EntityUpdate.php");

class SchemaUpdateRequest extends EntityUpdate {
	function isSchema(){
		return true;
	}

}
