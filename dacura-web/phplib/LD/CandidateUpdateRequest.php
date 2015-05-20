<?php

require_once("Candidate.php");

class CandidateUpdateRequest extends Candidate {
	var $original; //the current state of the target candidate
	var $delta;	//the changed state of the target candidate (if the update request is accepted)
	var $changes; // array describing changes from old to new

	function applyUpdates(){
		if($this->delta->update($this->contents) && $this->delta->compliant()){
			return true;
		}
		return $this->failure_result($this->delta->errmsg, $this->delta->errcode);			
	}
	
	/**
	 * Does the update request come from a context that has authority for the candidate?
	 * @param string $ocid - candidate collection id
	 * @param string $odid - candidate dataset id
	 * @return boolean
	 */
	function contextEncompasses($ocid, $odid){
		if($this->cid == "all" or ($this->did == "all" && $ocid != "all" && $ocid == $this->cid) 
				or ($this->cid == $ocid) && $this->did == $odid){
			return true;
		}
		return false;
	}
	
	function checkMissingLinks(){
		//check the internal consistency of the delta?
		$new_missing_links = $this->findInternalMissingLinks($this->delta->contents, array_keys($this->delta->index), $this->orig->id);
		$old_missing_links = $this->findInternalMissingLinks($this->orig->contents, array_keys($this->orig->index), $this->orig->id);
		foreach($new_missing_links as $i => $nml){
			foreach($old_missing_links as $j => $oml){
				if($oml[0] == $nml[0] && $oml[1] == $nml[1] && $oml[2] == $nml[2]){
					unset($new_missing_links[$i]);
					break;
				}
			}
		}
		return $new_missing_links;
	}
	
	function setOriginal($thing){
		$this->original = $thing;
		$this->delta = clone $this->original;
		$this->delta->version++;
		$this->delta->buildIndex();
	}
	
	function loadFromAPI($obj){
		$this->contents = $obj;
	}
	
	

	function analyse(){
		if($this->original->compare($this->delta)){
			$this->changes = $this->original->changes;
			return true;
		}
		return $this->failure_result($this->original->errmsg, $this->original->errcode);
		//return $this->failure_result("")
		//return false;
		//if(!$this->changes){
		//}
		//return true;
		/*$changes = array();
		if(parent::analyseUpdate($this->id, $this->original->contents, $this->delta->contents, $changes)){
			$changes["broken_links"] = $this->checkMissingLinks();
			return $changes;
		}
		return false;*/
	}
	
	/*
	 * DB serialisation
	 */
	function from_version(){
		return $this->original->version();
	}
	
	function to_version(){
		return $this->delta->version();	
	}
	
	function get_forward_json(){
		return json_encode($this->changes['forward']);
	}
	
	function get_backward_json(){
		return json_encode($this->changes['back']);
	}
	
	function schema_version(){
		return $this->original->get_class_version();
	}
}
