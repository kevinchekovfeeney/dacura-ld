<?php
require_once("LDEntity.php");

/**
 * entity create request has form:
 * graphname: {
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


class EntityCreateRequest extends LDEntity {

	function __construct($id){
		$this->version = 1;
		parent::__construct($id);
	}

	function loadFromAPI($obj){
		if(!isset($obj['contents']) && !isset($obj['meta'])){
			return $this->failure_result("Create Object was malformed : both meta and contents are missing", 400);
		}
		$this->ldprops = isset($obj['contents']) ? $obj['contents'] : array();
		$this->meta = isset($obj['meta']) ? $obj['meta'] : array();
		$this->version = 1;
		return true;
	}

	function isCandidate(){
		return false;
	}
	function isSchema(){
		return false;
	}
	function isOntology(){
		return false;
	}

	/*
	 * Called to hide whatever internal parts of the object we do not wish to send as json through the api
	 */
	function getDisplayFormat() {
		$other = clone($this);
		unset($other->index);
		unset($other->implicit_add_to_valuelist);
		unset($other->errmsg);
		return $other;
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
			//	return $this->failure_result("Illegal JSON LD structure passed: $k has an empty value - not supported in create requests", 400);
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
