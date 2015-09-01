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
	
	function __construct($id, $schema){
		$this->version = 1;
		parent::__construct($id);
		$this->schema = $schema;
		$this->cwurl = $this->schema->instance_prefix.$id;
	}
	
	function loadFromAPI($obj){
		$this->ldprops = array();
		$this->type = $this->getObjectType($obj['candidate']);
		if(!$this->type){
			return $this->failure_result("No type found in create candidate - create requests must include an rdf:type", 400);
		}
		$this->type_version = $this->schema->getTypeVersion($this->type);
		//massage the structure into the one that we want by adding in blank node ids for the parts of the message
		$this->ldprops["candidate"] = array("_:candidate" => $obj['candidate']);
		$met = isset($obj["meta"]) ? $obj["meta"] : array();
		if($met && isset($met['status'])){
			$this->metagraph = $met['status'];
		}
		else {
			$this->metagraph = "candidate";//default 
		}
		$this->ldprops["meta"] = array("_:meta" => $met);
		$this->ldprops["provenance"] = isset($obj['provenance']) ? $obj['provenance'] : array();
		$this->ldprops["annotation"] = isset($obj['annotation']) ? $obj['annotation'] : array();
		return true;
	}	
	
	function validate($obj=false){
		if($obj === false) $obj = $this->ldprops;
		if(!is_array($obj)){
			return $this->failure_result("Input is not an array object", 500);
		}	
		foreach($obj as $k => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()) {
				return $this->failure_result("Illegal JSON LD structure passed (property of $k) ".$pv->errmsg, $pv->errcode);
			}
			//elseif($pv->isempty()){
			//	return $this->failure_result("Illegal JSON LD structure passed $k has an empty value - not supported in create requests", 400);				
			//}
			if($pv->embeddedlist()){
				$cwlinks = $pv->getupdates();
				if(count($cwlinks) > 0){
					return $this->failure_result("New candidates cannot update anything but their own properties: $k has closed world links ".$cwlinks[0], 400);
				}
				foreach($v as $id => $emb){
					if(!$this->validate($emb)){
						return false;
					}
				}
			}
			elseif($pv->embedded()){
				if(count($v) == 1 && isset($v['@id'])){
					return $this->failure_result("Embedded objects cannot have @id as their only property ($k).", 400);						
				}
				if(!$this->validate($v)){
					return false;
				}
			}
			elseif($pv->objectlist()){
				foreach($v as $emb){
					if(count($emb) == 1 && isset($emb['@id'])){
						return $this->failure_result("Embedded objects cannot have @id as their only property ($k).", 400);						
					}
					if(!$this->validate($emb)){
						return false;
					}						
				}
			}
		}
		return true;			
	}
	
	function showCreateResult(){
		$other = clone($this);
		unset($other->idmap);
		return $other->getDisplayFormat();
	}
	
}
