<?php 
require_once("phplib/services/ld/LdDacuraServer.php");
class CandidateDacuraServer extends LdDacuraServer {

	// Talk to DQS here...
	function getFrame($cls){
		global $dacura_server;

		$ar = new DacuraResult("Creating Frame");
		
		if($expanded = $dacura_server->nsres->expand($cls)){
			$ar = $this->graphman->invokeDCS("seshat", $expanded);
			return $ar;
		}else{
			return $ar->failure(404, "Class Not Found","Class $cls does not exist in the specified triplestore.");
		}
	}
	
}
