<?php
include_once("phplib/DacuraServer.php");
include_once("phplib/db/CandidateDBManager.php");
include_once("phplib/LD/Schema.php");
include_once("phplib/LD/Candidate.php");
include_once("phplib/LD/CandidateCreateRequest.php");
include_once("phplib/LD/CandidateUpdateRequest.php");

class CandidateDacuraServer extends DacuraServer {

	var $dbclass = "CandidateDBManager";
	var $schema; //the schema in use is defined by the context.
	
	function getCandidate($candidate_id, $fragment_id = false, $version = false){
		$cand = new Candidate($candidate_id);
		if($this->dbman->load_candidate($cand)){
			if($version && $cand->version() > $version){
				if(!$this->rollBackCandidate($cand, $version)){
					return false;
				}
			}
			$cand->loadSchema($this->settings['install_url']);
			$cand->buildIndex();				
			if($fragment_id){
				//opr($cand->index);
				$frag = $cand->getFragment($fragment_id);
				if($frag){
					$cand->contents = $frag;
					//$cand->fragment = $fragment_id;
				}
				else {
					return $this->failure_result("Failed to load fragment $fragment_id", 404);						
				}				
			}
			return $cand;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getCandidateHistory($cand, $to_version = 1){
		$history = $this->dbman->get_candidate_update_history($cand, $to_version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if($to_version == 1 && count($history) > 0){
			//$initial_cand = $this->rollBackCandidate($cand, 1);
			$history[] = array(
				'from_version' => 0,
				"to_version" => 1,
				"modtime" => $cand->created,
				"createtime" => $cand->created,
				"schema_version" => $cand->type_version,
				"backward" => "{}",
				"forward" => "create"						
			);
		}
		return $history;
	}
	
	function getCandidatePending($cand){
		$updates = $this->dbman->get_relevant_updates($cand);
		return $updates;
	}
	
	function getCandidates(){
		return ($data = $this->dbman->get_candidate_list()) ? $data : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function rollBackCandidate(&$cand, $version){
		$history = $this->getCandidateHistory($cand, $version);
		foreach($history as $i => $old){
			if($old['from_version'] < $version){
				continue;
			}
			$cand->version = $old['from_version'];
			$cand->type_version = $old['schema_version'];
			$cand->modified = $old['modtime'];
			$update = json_decode($old['backward'], true);
			if(!$cand->update($update, true)){
				return $this->failure_result($cand->errmsg, $cand->errcode);
			}
		}
		return true;
	}
	
	function createCandidate($obj, $test_flag){
		$id = $this->generateNewCandidateID();
		$ccand = new CandidateCreateRequest($id, $this->schema);
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
		$ucand = new CandidateUpdateRequest($target_id, $this->schema);
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
		$ret = array("Changes" => $cand->changes);
		return $ret;				
	}
		
	function send_candidate_schema($cand){
		return $this->write_json_result($cand, "Sent the candidate schema");
	}
	
	function send_candidate($cand, $format, $display){
		if($format == "triples"){
			$cand->contents = $cand->asTriples();		
		}
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
