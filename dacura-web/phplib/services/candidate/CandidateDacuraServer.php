<?php 
require_once("phplib/services/ld/LdDacuraServer.php");
/**
 * This class extends the basic processing pipeline of the LD server to handle candidate (instance data) publishing
 *
 * Provides concrete implementations of the objectPublished, objectDeleted and objectUpdated methods and some helper functions
 * Candidates may be multi-graph which makes them a bit more complicated than the others. Much of the difference is found in the 
 * MultigraphLDO class
 * 
 * Also provides the interface to the DCS functions (get frame, get valid candidate types)
 *
 * @author Chekov
 * @license GPL V2
 */
class CandidateDacuraServer extends LdDacuraServer {
	/** @var array - the valid rdf:type values that candidates can take up */
	var $valid_candidate_types;
	
	/**
	 * Extends initialisation function to load valid candidate types
	 * @see LdDacuraServer::init()
	 */
	function init($action = false, $object = ""){
		parent::init($action, $object);
		$this->valid_candidate_types = $this->getValidCandidateTypes();
	}
	
	/**
	 * fetches the graph object with the specified id (assuming it exists in this context)
	 * @param string $id the local id of the graph
	 * @return boolean|Graph - either the graph object or false;
	 */
	function getGraph($id){
		return isset($this->graphs[$id]) ? $this->graphs[$id] : false;
	}
	
	/**
	 * fetches the default graph object for this context
	 * @return boolean|Graph - either the graph object or false if it does not exist;
	 */
	function getMainGraph(){
		return ($graph = $this->getGraph('main')) ? $graph : $this->failure_result("No default graph found in collection configuration.", 500);
	}
	
	/**
	 * Creates a frame for the given class by calling the DCS frame function
	 * @param string $cls the name of the class in question
	 * @return DacuraResult the Dacura Result object incorporating the frame
	 */
	function getFrame($cls){
		$cls = ($expanded = $this->nsres->expand($cls)) ? $expanded : $cls;
		if(!($mg = $this->getMainGraph())){
			return false;
		}
		return $this->graphman->invokeDCS($mg->schemaGname(), $cls);
	}

	/**
	 * Creates a frame for the given class by calling the DCS frame function
	 * @param string $cls the name of the class in question
	 * @return DacuraResult the Dacura Result object incorporating the frame
	 */
	function getFilledFrame($candid){
		$mg = $this->getMainGraph();
		if(!($mg = $this->getMainGraph())){
			return false;
		}
		if(!$cand = $this->loadLDO($candid, "candidate", $this->cid())){
			return false;	
		}
		$cls = $cand->getRDFType();
		//$candurl = $this->service->my_url()."/$candid";
		$ar = $this->graphman->invokeDCS($mg->schemaGname(), $cls);
		if($ar->result){
			if(!is_array($ar->result)){
				$ar->result = json_decode($ar->result, true);
			}
			if($this->fillFrame($ar->result, $cand)){
				return $ar->success("accept");				
			}
			return $ar->failure($this->errcode, "failed to generate filled frame for candidate $candid", $this->errmsg);
		}
		return $ar;		
	}
	
	/**
	 * Fills a frame with data from a candidate object
	 * @param array $frames an array of frames
	 * @param Candidate $cand the candidate to be used to fill the frame
	 * @param string $frag_id the fragment id in question 
	 * @return boolean true - always true...
	 */
	function fillFrame(&$frames, Candidate $cand, $frag_id = false){
		if($frag_id == false){
			$frag_id = $cand->cwurl;
		}
		foreach($frames as $i => $f){
			if($f['type'] == "datatypeProperty"){
				if(($vals = $cand->getPredicateValues($frag_id, $f['property'])) !== false){
					$frames[$i]['value'] = $vals;
				}
			}
			elseif($f['type'] == "objectProperty"){
				if(($objs = $cand->getPredicateValues($frag_id, $f['property'])) !== false){
					foreach(array_keys($objs) as $oid){
						$this->fillFrame($frames[$i]['frame'], $cand, $oid);	
					}
					$frames[$i]['value'] = json_encode(array_keys($objs));
				}
			}
		}
		return true;
	}
	
	/**
	 * Asks the DCS service for the set of valid types as specified in the context schema for candidates
	 * @see LdDacuraServer::getValidCandidateTypes()
	 */
	function getValidCandidateTypes(){
		$mg = $this->getMainGraph();
		if(!$mg){
			return false;
		}
		$ar = $this->graphman->invokeDCS($mg->schemaGname());
		//opr($ar->result);
		if($ar->is_accept()){
			return json_decode($ar->result, true);
		}
		else {
			return $this->failure_result($ar->msg_title." ".$ar->msg_body, $ar->errcode);
		}		
	}
	
	/**
	 * Extends the ld server to do graph publication and testing
	 * 
	 * This is rendered somewhat more complex as we have to try each graph in turn. 
	 * @see LdDacuraServer::objectPublished()
	 */
	function objectPublished(Candidate $cand, $test_flag = false){
		$dr = new DQSResult("Validating Candidate $cand->id", $test_flag);
		if($cand->isEmpty()){
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
				$quads = $cand->typedQuads($graph->instanceGname());
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
				return $dr->failure(400, "Failed to load main graph", "No main graph found for " . $this->cid() . " context - every collection must have a main graph before instance data can be added to it");				
			}
			$quads = $cand->typedQuads($graph->instanceGname());
			$dr = $this->graphman->createInstance($graph, $quads, $test_flag);				
		}
		return $dr;	
	}
	
	/**
	 * Invokes the DQS service to delete a candidate from the graph
	 * @see LdDacuraServer::objectDeleted()
	 */
	function objectDeleted(Candidate $cand, $test_flag = false){
		$dr = new DQSResult("Unpublishing Candidate $cand->id", $test_flag);
		if($cand->isEmpty()){
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
				return $dr->failure(400, "Failed to load main graph", "No main graph found for " . $this->cid() . " context - every collection must have a main graph before instance data can be added to it");
			}
			$quads = $cand->typedQuads($graph->instanceGname());
			$dr->add($this->graphman->deleteInstance($graph, $quads, $test_flag));
		}
		return $dr;
	}
	
	/**
	 * Invokes the DQS to update a candidate in the graph
	 * @see LdDacuraServer::objectUpdated()
	 */
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
	
	/**
	 * Called to rollback an update when it fails on a single graph - rollsback all the written to graphs
	 * @param array $gquads array of quads to rollback referenced by graph id
	 * @return DQSResult
	 */
	function rollbackPartialObjectUpdate($gquads){
		$dr = new DQSResult("Rolling back partial update");
		foreach($gquads as $gid => $gstruct){
			$dr->add($this->graphman->undoInstanceUpdate($gstruct['graph'], $gstruct['insert'], $gstruct['delete']));
		}
		return $dr;
	}	
}
