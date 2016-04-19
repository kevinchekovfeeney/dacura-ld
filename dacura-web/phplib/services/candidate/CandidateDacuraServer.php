<?php 
require_once("phplib/services/ld/LdDacuraServer.php");
class CandidateDacuraServer extends LdDacuraServer {

	var $valid_candidate_types;
	
	// Talk to DQS here...
	function getFrame($cls){
		$ar = new DacuraResult("Creating Frame $cls");
		$cls = ($expanded = $this->nsres->expand($cls)) ? $expanded : $cls;
		$mg = $this->getMainGraph();
		return $this->graphman->invokeDCS($mg->schemaGname(), $cls);
	}
	
	function getValidCandidateTypes(){
		$mg = $this->getMainGraph();
		$ar = $this->graphman->invokeDCS($mg->schemaGname());
		//opr($ar);
		if($ar->is_accept()){
			return json_decode($ar->result, true);
		}
		else {
			return $this->failure_result($ar->msg_title." ".$ar->msb_body, $ar->errcode);
		}		
	}
	
	function init($action = false, $object = ""){
		parent::init($action, $object);
		$this->valid_candidate_types = $this->getValidCandidateTypes();
	}
	
	function getGraph($id){
		return isset($this->graphs[$id]) ? $this->graphs[$id] : false; 
	}
	
	function getMainGraph(){
		return ($graph = $this->getGraph('main')) ? $graph : $this->failure_result("No default graph found in collection configuration.", 500);
	}
		
	function objectPublished(Candidate $cand, $test_flag = false){
		$dr = new DQSResult("Validating Candidate $cand->id", $test_flag);
		if($cand->is_empty()){
			return $dr->failure(400, "Candidate is empty", "Data must be added to the candidate before it can be published.");
		}
		if($cand->is_multigraph()){
			$exit_on_fail = !$this->getServiceSetting("ignore_graph_fail", false);
			$rollback_on_fail = !$test_flag && $this->getServiceSetting("rollback_on_graph_fail", true);
			$written_data = array();
			foreach($cand->getGraphIDs() as $gid){
				if(!isset($this->graphs[$gid])){
					$mtitle = "Unknown graph id";
					$mbody = "Candidate $cand->id contains data that is associated with an unknown graph $gid";
					if($exit_on_fail){
						if($rollback_on_fail && count($written_data) > 0){
							$dr->add($this->rollbackPartialObjectUpdate($written_data));
						}
						return $dr->failure(404, $mbtitle, $mbody);
					}
					else {
						$dr->addError(404, $mbtitle, $mbody);
					}
				}
				$graph = $this->graphs[$gid];
				$quads = $cand->typedNGQuads($graph->instanceGname());
				$gr = $this->graphman->createInstance($graph, $quads, $test_flag);
				$dr->addGraphResult($gid, $gr);
				if(!$gr->is_accept() && $exit_on_fail){
					if($rollback_on_fail && count($written_data) > 0){
						$dr->add($this->rollbackPartialObjectUpdate($written_data));
					}
					return $dr->failure(500, "Create candidate graph failure", "Failed to create graph $gid instance data for candidate ".$cand->id." in graph $gid");
				}
				elseif($gr->is_accept() && $rollback_on_fail){
					$written_data[$gid] = array("graph" => $graph, "insert" => $quads, "delete" => false);
				}
				elseif(!$gr->is_accept()) {
					$dr->addError(404, "Create candidate graph failure", "Failed to create graph $gid instance data for candidate ".$cand->id);
				}
			}
		}
		else {
			if(!$graph = $this->getMainGraph()){
				return $dr->failure("Failed to load main graph", "Every collection must have a main graph for instance data ");				
			}
			$quads = $cand->typedQuads($graph->instanceGname());
			$dr = $this->graphman->createInstance($graph, $quads, $test_flag);				
		}
		return $dr;	
	}
	
	function objectDeleted(Candidate $cand, $test_flag = false){
		$dr = new DQSResult("Unpublishing Candidate $cand->id", $test_flag);
		if($cand->is_empty()){
			return $dr->failure(400, "Candidate is empty", "Data must be added to the candidate before it can be unpublished.");
		}
		if($cand->is_multigraph()){
			$exit_on_fail = !$this->getServiceSetting("ignore_graph_fail", false);
			$rollback_on_fail = !$test_flag && $this->getServiceSetting("rollback_on_graph_fail", true);
			$written_data = array();				
			foreach($cand->getGraphIDs() as $gid){
				if(!isset($this->graphs[$gid])){
					$mtitle = "Unknown graph id";
					$mbody = "Candidate $cand->id contains data that is associated with an unknown graph $gid";
					if($exit_on_fail){
						if($rollback_on_fail && count($written_data) > 0){
							$dr->add($this->rollbackPartialObjectUpdate($written_data));
						}
						return $dr->failure(404, $mbtitle, $mbody);
					}
					else {
						$dr->addError(404, $mbtitle, $mbody);
					}
				}
				$graph = $this->graphs[$gid];
				$quads = $cand->typedQuads($graph->instanceGname());
				$gr = $this->graphman->deleteInstance($graph, $quads, $test_flag);
				$dr->addGraphResult($gid, $gr);
				if(!$gr->is_accept() && $exit_on_fail){
					if($rollback_on_fail && count($written_data) > 0){
						$dr->add($this->rollbackPartialObjectUpdate($written_data));
					}
					return $dr->failure(500, "Create candidate graph failure", "Failed to create graph $gid instance data for candidate ".$cand->id." in graph $gid");
				}
				elseif($gr->is_accept() && $rollback_on_fail){
					$written_data[$gid] = array("graph" => $graph, "insert" => false, "delete" => $quads);
				}
				elseif(!$gr->is_accept()) {
					$dr->addError(404, "Create candidate graph failure", "Failed to create graph $gid instance data for candidate ".$cand->id);
				}
			}
		}
		else {
			if(!$graph = $this->getMainGraph()){
				return $dr->failure("Failed to load main graph", "Every collection must have a main graph for instance data ");
			}
			$quads = $cand->typedQuads($graph->instanceGname());
			$dr->add($this->graphman->createInstance($graph, $quads, $test_flag));
		}
		return $dr;
	}
	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		$dr = new DQSResult("Updating Candidate ". $uldo->original->id, $test_flag);
		$ngs = $uldo->getUpdatedNamedGraphs($this->getMainGraph()->instanceGname());
		$exit_on_fail = !$this->getServiceSetting("ignore_graph_fail", false);
		$rollback_on_fail = !$test_flag && $this->getServiceSetting("rollback_on_graph_fail", true);
		$written_data = array();
		foreach($ngs as $ngurl){
			$gid = $this->graphURLToID($ngurl);
			if(!$graph = $this->getGraph($gid)){
				if($exit_on_fail){
					if($rollback_on_fail && count($written_data) > 0){
						$dr->add($this->rollbackPartialObjectUpdate($written_data));
					}
					return $dr->failure(404, "Graph not found", "Graph $ngurl refrenced in the instance data does not exist");
				}
				else {
					$dr->addError(404, "Graph not found", "Graph $gid refrenced in the instance data does not exist");
				}
			}
			elseif(!$graph->is_accept()){
				if($exit_on_fail){
					if($rollback_on_fail && count($written_data) > 0){
						$dr->add($this->rollbackPartialObjectUpdate($written_data));
					}
					return $dr->failure(404, "Graph not available", "Graph $gid refrenced in the instance data is not active (".$graph->status().")");
				}
				else {
					$dr->addError(404, "Graph not available", "Graph $gid refrenced in the instance data is not active (".$graph->status().")");
				}
			}
			else {
				$quads = $uldo->getNGQuads($ngurl);
				$gr = $this->graphman->updateInstance($graph, $quads['insert'], $quads['delete'], $test_flag);
				$dr->add($gr);
				if(!$gr->is_accept() && $exit_on_fail){
					if($rollback_on_fail && count($written_data) > 0){
						$dr->add($this->rollbackPartialObjectUpdate($written_data));
					}
					return $dr->failure(404, "Graph update failure", "Failed to update instance data in graph $gid");
				}
				elseif($gr->is_accept() && $rollback_on_fail){
					$written_data[$gid] = array("graph" => $graph, "insert" => $quads['insert'], "delete" => $quads['delete']);				
				}
				else {
					$dr->addError(404, "Graph update failure", "Failed to update instance data in graph $gid");
				}
			}
		}
		return $dr;
	}
	
	function rollbackPartialObjectUpdate($gquads){
		$dr = new DQSResult("Rolling back partial update");
		foreach($gquads as $gid => $gstruct){
			$dr->add($this->graphman->undoInstanceUpdate($gstruct['graph'], $gstruct['insert'], $gstruct['delete']));
		}
		return $dr;
	}	
	

	
}
