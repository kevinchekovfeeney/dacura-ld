<?php
include_once("phplib/DacuraServer.php");
include_once("phplib/db/CandidateDBManager.php");
include_once("phplib/LD/Candidate.php");
include_once("phplib/LD/CandidateCreateRequest.php");
include_once("phplib/LD/CandidateUpdateRequest.php");

class CandidateDacuraServer extends DacuraServer {

	var $dbclass = "CandidateDBManager";
	
	function getCandidate($candidate_id, $fragment_id = false, $format = false){
		$cand = new Candidate($candidate_id, $this->ucontext->my_url());
		if($this->dbman->load_candidate($cand)){
			if($fragment_id){
				$frag = $cand->getFragment($fragment_id);
				if($frag){
					return $frag;
				}
				else {
					return $this->failure_result("Failed to load facet $fragment_id", 404);						
				}				
			}
			return $cand;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getCandidates(){
		return ($data = $this->dbman->get_candidate_list()) ? $data : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getCandidateSchema($candidate_type, $facet, $format){
		return $this->failure_result("Testing", 500);
	}
	
	function createCandidate($obj, $test_flag){
		$id = $this->generateNewCandidateID();
		$ccand = new CandidateCreateRequest($id, $this->ucontext->my_url());
		$ccand->setContext($this->cid(), $this->did());
		$ccand->loadFromAPI($obj);
		//opr($ccand);
		/*$dacura_agent_id = $ccand->getAgentKey();
		if(!$dacura_agent_id){
			return $this->failure_result("no dacura user agent id in source", 400);
			//rejected!
		}*/
		//check endpoint permissions
		if(!$this->endpointCreateAllowed(0, $ccand->type)){
			//rejected
			return $this->failure_result("not permitted to create that", 403);
		}
		//now we do our fine-grained permissions stuff with all the objects formed..
		if(!$this->createCandidatePermitted($ccand)){
			return $this->failure_result("Not permitted to create that candidate", 400);				
		}
		if(!$ccand->expand()){
			return $this->failure_result($ccand->errmsg, $ccand->errcode);
		}
		//opr($ccand);
		//run something on ccand to make it do all of its schema checking stuff
		return $ccand;
	}
	
	function generateUpdateCandidateID(){
		return uniqid_base36(true);
	}
	
	function generateNewCandidateID(){
		return uniqid_base36(true);
	}
	
	function processCreateCandidate($cand, $is_test=false){
		$sa = new SemanticAnalysis();
		$res = $sa->create_consequences($cand);
		if($is_test){
			return $res;
		}
		if($res == "accept" || $res == "pending"){
			$this->dbman->createCandidate($cand, $res);
			if($res == "accept"){
				//spit it into the graph!
			}
		}
		else {
			return $this->failure_result("sss", 400);
		}
		$ret = $cand->get_json_ld();
		return $ret;
	}
	
	/**
	 *
	 * @param string $target_id
	 */
	function createUpdateCandidate($target_id, $obj, $fragment_id, $is_test = false){
		$ucand = new CandidateUpdateRequest($target_id, $this->ucontext->my_url());
		$ocand = $this->getCandidate($target_id, $fragment_id);
		if(!$ocand){
			return $this->failure_result("Failed to load Candidate to be updated", 403);
		}
		$ucand->setContext($this->cid(), $this->did());
		//check context mismatch
		if(!$ucand->contextEncompasses($ocand->cid, $ocand->did)){
			return $this->failure_result("Cannot update candidate through context ", 403);				
		}
		$ucand->loadFromAPI($obj);
		/*$dacura_agent_id = $ucand->getAgentKey();
		if(!$dacura_agent_id){
			return $this->failure_result("no dacura user agent id in source", 400);
			//rejected!
		}*/
		//check endpoint permissions
		if(!$this->endpointCreateAllowed(false, $ucand->type)){
			//rejected
			return $this->failure_result("not permitted to create that", 403);
		}
		$ucand->setOriginal($ocand);	
		if(!$ucand->applyUpdates()){
			return $this->failure_result($ucand->errmsg, $ucand->errcode);
		}
		if(!$ucand->analyse()){
			return $this->failure_result($ucand->errmsg, $ucand->errcode);
		}
		if(!$this->updateCandidatePermitted($ucand)){
			return $this->failure_result("Not permitted to update that candidate", 400);
		}
		return $ucand;
	}
	
	
	function endpointCreateAllowed($dacura_user_agent, $candidate_class){
		$this->loadContextConfiguration();
		return true;
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
	function processUpdateCandidate($cand, $fragment_id = false, $is_test = false){
		$sa = new SemanticAnalysis();
		$res = $sa->update_consequences($cand);
		if($is_test){
			return $res;
		}
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
		$ret = array("Changes" => $cand->changes, "Before" => $cand->original->get_json_ld(), "After " => $cand->delta->get_json_ld());
		return $ret;				
	}
		
	function send_candidate_schema($cand){
		return $this->write_json_result($cand, "Sent the candidate schema");
	}
	
	function send_candidate($cand){
		if(is_object($cand)){
			$cand_jsonld = $cand->get_json_ld();				
		}
		else {
			$cand_jsonld = $cand;				
		}
		return $this->write_json_result($cand_jsonld, "Sent the candidate");
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
