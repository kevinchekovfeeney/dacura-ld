<?php

require_once("Candidate.php");

class CandidateUpdateRequest extends Candidate {
	var $original; //the current state of the target candidate
	var $delta;	//the changed state of the target candidate (if the update request is accepted)
	var $changes; // array describing changes from old to new
	var $rollback; // array describing changes from old to new
	var $id_map; // array of blank node ids mapped into newly created urls...

	function generateDelta(){
		if($this->applyUpdates($this->contents, $this->delta->contents)){
			//should also do the same with provenance and annotations
			return true;
		}
		else return false;
	}

	function applyUpdates($props, &$dprops){
		foreach($props as $prop => $v){
			if(!is_array($v)){ // property => value
				$dprops[$prop] = $v;
			}
			elseif(count($v) == 0){ // delete property or complain 
				if(isset($dprops[$prop])){
					unset($dprops[$prop]);
				}
				else {
					return $this->failure_result("Attempted to remove non-existant property $prop", 404);
				}
			}
			else { // property => {id => embedded_object, ...} value is array
				foreach($v as $id => $obj){
					if(!$obj){ // delete fragment
						if(isset($dprops[$prop][$id])){
							unset($dprops[$prop][$id]);
						}
						else {
							return $this->failure_result("Attempted to remove non-existant property value $prop $id", 404);
						}
					}
					elseif(!is_array($obj)){
						return $this->failure_result("Update object format error - attempting to replace embedded object with non json-object", 404);
					}
					else { // correct - array of things
						if(isBlankNode($id)){
							//$plan['create'][$id] = array($p, $obj);//property, object
							$new_id = $this->genid($this->delta->id."/");
							$this->id_map[$id] = $new_id;
							$dprops[$prop][$new_id] = $obj;
							$id = $new_id;
						}
						elseif(!isset($dprops[$prop][$id])){
							return $this->failure_result("Attempting to update $prop with non existant id $id", 404);
						}
						else {
							if(!$this->applyUpdates($props[$prop][$id], $dprops[$prop][$id])){
								return false;
							}
						}
					}
				}
					
			}
		}
		return true;
	}
	//here is where we go through the update request and turn it into a list of updates
	function expand(){
		if($this->generateDelta()){
			$this->changes = array("add" => array(), "del" => array(), "update" => array());
			$this->candidateCompare($this->original->contents, $this->delta->contents, $this->original->id, $this->changes);//now generate changeset..
			$this->rollback = array("add" => array(), "del" => array(), "update" => array());
			$this->candidateCompare($this->delta->contents, $this->original->contents, $this->original->id, $this->rollback);//now generate changeset..
			//need to also do the annotations and provenance ... Later!
			return true;
		}
		return $this->failure_result($this->errmsg, $this->errcode);
	}

	//res -> add: (context, fragment)
	//res -> delete: (context, $property, fragment)
	//res -> update: (context, $property, fragment1, fragment2)
	function candidateCompare($ocand, $dcand, $context, &$res){
		foreach($ocand as $p => $v){
			if(!isset($dcand[$p]) || !$dcand[$p] || (is_array($dcand[$p]) && count($dcand[$p]) == 0)){
				$res['del'][] = array($context, $p, $v);
				continue;
			}
			$nv = $dcand[$p];
			if(!is_array($v)){
				if($nv != $v){
					$res['update'][] = array($context, $p, $nv, $v);
				}
			}
			else {
				//update to a compound object...
				$this->candidateCompare($ocand[$p], $dcand[$p], "$context.$p", $res);
			}
		}
		//now go through the delta cand to find new nodes...
		foreach($dcand as $p => $v){
			if(!isset($ocand[$p]) || !$ocand[$p]){
				$res['add'][] = array($context, $p, $v);
				continue;
			}
		}
		return true;
	}

	function get_delta(){
		return $this->delta;
	}

}
