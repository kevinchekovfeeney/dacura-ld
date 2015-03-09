<?php
include_once("phplib/DacuraServer.php");
include_once("phplib/db/CandidateDBManager.php");
include_once("phplib/Candidate.php");
include_once("phplib/libs/jsv4.php");


class CandidateDacuraServer extends DacuraServer {

	var $dbclass = "CandidateDBManager";
	
	function getCandidate($candidate_id, $facet, $format){
		$cand = new Candidate($candidate_id);
		if($this->dbman->load_candidate($cand)){
			return $cand;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getCandidateSchema($candidate_type, $facet, $format){
		return $this->failure_result("Testing", 500);
	}
	
	function createCandidate($source, $candidate, $annotations, $test_flag){
		$ccand = new CandidateCreateRequest();
		$ccand->setContext($this->cid(), $this->did());
		if(!$ccand->loadFromAPI($source, $candidate, $annotations)){
			return $this->failure_result("failed to load candidate create request from input ".$ccand->errmsg, 400);
		}
		$dacura_agent_id = $ccand->getAgentKey();
		if(!$dacura_agent_id){
			return $this->failure_result("no dacura user agent id in source", 400);
			//rejected!
		}
		//check endpoint permissions
		if(!$this->endpointCreateAllowed($dacura_agent_id, $candidate['class'])){
			//rejected
			return $this->failure_result("not permitted to create that", 403);
		}
		//now we do our fine-grained permissions stuff with all the objects formed..
		if(!$this->createCandidatePermitted($ccand)){
			return $this->failure_result("Not permitted to create that candidate", 400);				
		}
		//run something on ccand to make it do all of its schema checking stuff
		return $ccand;
	}
	
	/**
	 *
	 * @param string $target_id
	 * @param array $source
	 * @param array $candidate
	 * @param array $annotations
	 */
	function updateCandidate($target_id, $source, $candidate, $annotations, $test_flag){
		$ucand = new CandidateUpdateRequest($target_id);
		$ocand = $this->loadCandidate($target_id);
		if(!$ocand){
			return $this->failure_result("Failed to load Candidate to be updated", 403);
		}
		$ucand->setOriginal($ocand);
		$ucand->setContext($this->cid(), $this->did());
		if(!$ucand->loadFromAPI($source, $candidate, $annotations)){
			return $this->failure_result("failed to load candidate create request from input", 400);
		}
		$dacura_agent_id = $ccand->getAgentKey();
		if(!$dacura_agent_id){
			return $this->failure_result("no dacura user agent id in source", 400);
			//rejected!
		}
		//check endpoint permissions
		if(!$this->endpointCreateAllowed($dacura_agent_id, $candidate['class'])){
			//rejected
			return $this->failure_result("not permitted to create that", 403);
		}
		//check internal referential integrity (targets of 
		if(!$this->updateCandidatePermitted($ucand)){
			return $this->failure_result("Not permitted to update that candidate", 400);
		}
		return $ucand;
	}
	
	function loadCandidate($candid){
		$cand = new Candidate($candid);
		return $this->dbman->load_candidate($cand);
	}
	
	function endpointCreateAllowed($dacura_user_agent, $candidate_class){
		$this->loadContextConfiguration();
		return true;
	}
	
	function extractDacuraAgentFromProvenance($source){
		return "testing";
	}
	
	function createCandidatePermitted($cand){
		return true;
	}
	
	function updateCandidatePermitted($cand){
		return true;
	}
	

	
	/**
	 * 
	 * @param Candidate $cand
	 */
	function processCandidate($cand){
		if(isset($cand->original)){
			if(!$cand->generateDelta()){
				return $this->failure_result("x", 400);
			}
			/*
			 * Now we need our semantic validation....
			 * and generation of upgrade formulae
			 * Followed by our routing / accepted / rejected / pending
			 * 
			 */
			$sa = new SemanticAnalysis();
			$res = $sa->update_consequences($cand);
			//if pending or accepted -> write update request to database...
			if($res == "accept"){
				$this->dbman->updateCandidate($cand, $res);				
			}
			elseif($res == "pending"){
				$this->dbman->deferCandidateUpdate($cand, $res);				
			}
			else {
				return $this->failure_result("sss", 400);
			}			
		}
		else {
			$sa = new SemanticAnalysis();
			/*
			 * Now we need our semantic validation....
			 * Followed by our routing / accepted / rejected / pending
			 * if pending or accepted -> write candidate to table
			 * if accepted -> update instance graph
			 */
			$res = $sa->create_consequences($cand);
			if($res == "accept" || $res == "pending"){
				$this->dbman->createCandidate($cand, $res);
				if($res == "accept"){
					//spit it into the graph!
						
				}
			}
			else {
				return $this->failure_result("sss", 400);
			}		
		}
		return $this->failure_result("Testing", 500);
	}
	
	function send_candidate_schema($cand){
		return $this->write_json_result($cand, "Sent the candidate schema");
	}
	
	function send_candidate($cand){
		return $this->write_json_result($cand, "Sent the candidate");
	}
	
}

class SemanticAnalysis {
	function update_consequences($cand){
		return "accept";
	}
	
	function create_consequences($cand){
		return "accept";
	}
}
