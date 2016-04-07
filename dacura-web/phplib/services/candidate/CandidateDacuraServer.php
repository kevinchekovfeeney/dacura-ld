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
	
	function init($action = false, $object = ""){
		$this->readGraphConfiguration();
		//$this->loadNamespaces();
		return parent::init($action, $object);
	}
	
	function getMainGraph(){
		return isset($this->graphs['main']) ? $this->graphs['main'] : $this->failure_result("No default graph found in collection configuration.", 500);
	}
	

	
	
	function objectPublished(Candidate $cand, $test_flag = false){
		$nopr = new DQSResult("Validating Candidate $cand->id", $test_flag);
		if($cand->is_empty()){
			return $nopr->failure(400, "Candidate is empty", "Data must be added to the candidate before it can be published.");
		}
		if($cand->is_multigraph()){
			foreach($cand->ldprops as $gurl => $gcontents){
				if(!$gid = $this->graphURLToID($gurl)){
					return $nopr->reject("Unknown graph $gurl", "The new object contains a url of an unknown graph $gurl");
				}
				if(!isset($this->graphs[$gid])){
					return $nopr->reject("Unknown graph $gid", "The new object contains data that is associated with an unknown graph $gid");
				}
				$graph = $this->graphs[$gid];
				//opr($graph);
				$quads = $cand->typedQuads($gurl);
				opr($quads);
				$nopr->addGraphResult($gid, $this->graphman->createInstance($graph, $quads, $test_flag));				
			}
		}
		else {
			if(!$graph = $this->getMainGraph()){
				return $nopr->failure("Failed to load main graph", "Every collection must have a main graph for instance data ");				
			}
			$quads = $cand->typedQuads($graph->instanceGname());
			$nopr->add($this->graphman->createInstance($graph, $quads, $test_flag));				
		}
		return $nopr;	
	}
	
	function objectDeleted(Candidate $cand, $test_flag = false){
		$nopr = new DQSResult("Unpublishing Candidate $cand->id", $test_flag);
		if($cand->is_empty()){
			return $nopr->failure(400, "Candidate is empty", "Data must be added to the candidate before it can be unpublished.");
		}
		if($cand->is_multigraph()){
			foreach($this->ldprops as $gid => $gcontents){
				if(!isset($this->graphs[$gid])){
					continue;
					//return $nopr->reject("Unknown graph $gid", "The new object contains data that is associated with an unknown graph $gid");
				}
				$graph = $this->graphs[$gid];
				$quads = $cand->typedQuads($graph->schemaGname());
				$nopr->add($this->graphman->deleteInstance($graph, $quads, $test_flag));
			}
		}
		else {
			if(!$graph = $this->getMainGraph()){
				return $nopr->failure("Failed to load main graph", "Every collection must have a main graph for instance data ");
			}
			$quads = $cand->typedQuads($graph->schemaGname());
			$nopr->add($this->graphman->createInstance($graph, $quads, $test_flag));
		}
		return $nopr;
	}
	

	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		//return $this->objectPublished($uldo->changed, $test_flag);
	}
	
}
