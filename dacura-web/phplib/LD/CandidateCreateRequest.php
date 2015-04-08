<?php
require_once("Candidate.php");

/**
 * candidate create request has form:
 * candidate: {
 * 	property: string
 *  property: [string]
 *  property: [{@id=...},...] 
 *
 * }
 * 
 * Represents a request to create a new instance of an entity class with specified property values in the curated dataset
 * @author chekov
 *
 */

class CandidateCreateRequest extends Candidate {

	
	function __construct($id, $burl){
		$this->version = 1;
		parent::__construct($id, $burl);
	}
	
	function validate($obj=false){
		if($obj === false) $obj = $this->contents;
		//just check to see that its a valid ccr
		foreach($obj as $k => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()) return false;
			if($pv->embeddedlist()){
				$cwlinks = $pv->getupdates();
				if(count($cwlinks) > 0){
					return $this->failure_result("New candidates cannot have properties that update anything but themselves", 400);
				}
				foreach($v as $id => $emb){
					if(!$this->validate($emb)){
						return false;
					}
				}
			}
			elseif($pv->embedded()){
				if(!$this->validate($v)){
					return false;
				}
			}
			elseif($pv->objectlist()){
				foreach($v as $emb){
					if(!$this->validate($emb)){
						return false;
					}						
				}
			}
		}
		return true;			
	}

	
	function loadFromAPI($obj){
		parent::loadFromAPI($obj);
		if(!isset($obj['candidate']['rdf:type'])){
			return $this->failure_result("No type found in create candidate - create requests must include an rdf:type", 400);
		}
		$this->type = $obj['candidate']['rdf:type'];
		$this->type_version = "0.1.0";
		//massage the structure into the one that we want by adding in blank node ids for the parts of the message
		$this->contents["candidate"] = array("_:candidate" => $obj['candidate']);
	}	

	//add ids to everything, ensure that everything inter-relates
	function expand(){
		if($this->validate()){
			return parent::expand();
		}
		else {
			return false;
		}
	}
}
