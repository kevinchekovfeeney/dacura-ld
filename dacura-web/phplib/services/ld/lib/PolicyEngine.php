<?php
include_once("DacuraResult.php");

/**
 * A class which produces policy responses to actions in the linked data api
 * 
 * Basically this class is supposed to tell us what is allowed when users do stuff
 * @author chekov
 * @license GPL V2
 */
class PolicyEngine extends DacuraObject {
	/** @var array true for a given type if users are allowed to specify their own ids for ldos */
	var $demand_id_allowed = array("default" => true, "graph" => false);
	/** @var array if this is true for a given ldo type then ldos and updates that are rejected will still be stored in the db */
	var $store_rejected = array("default" => true, "ontology" => false);
	/** @var array An array of decisions for actions */
	var $decisions = array(
		"view" => array("default" => "accept"),	
		"update" => array("default" => "accept"),	
		"create" => array("default" => "accept"),	
		"delete" => array("default" => "accept"),	
		"view update" => array("default" => "accept"),	
		"update update" => array("default" => "accept"),	
		"delete update" => array("default" => "accept"),	
	);
	/** @var array An array of which types should be rolled back*/
	var $rollbacks = array("default" => true, "ontology" => false);
	
	var $decision;
	
	/**
	 * Loads the default decision for a particular action and object type
	 * @param string $action
	 * @param string $type
	 * @return string 
	 */
	function getPolicyDefaultDecision($action, $type){
		$decisions = $this->decision[$action];
		if(isset($decision[$type]))return $decisions[$type];
		return $decisions["default"];
	}
	
	/**
	 * Returns a decision in response to an attempt to carry out an action 
	 * @param string $action create, update, delete, view, update update, view update, delete update
	 * @param string $type what type of thing is being actioned on 
	 * @param mixed $context any important contextual information goes in here
	 * @return DacuraResult
	 */
	function getPolicyDecision($action, $type, $context){
		$ar = new DacuraResult("System policy for $action");
		$ar->status($this->getPolicyDefaultDecision($action, $type));
		if($action == "update update"){
			return $this->updateUpdate($context[0], $context[1], $ar);
		}
		return $ar;
	}

	/**
	 * Updates to updates are special and can beprocessed generically ...
	 * @param ldoUpdate $orig the original update object
	 * @param ldoUpdate $upd the modified update object
	 * @param DacuraResult $ar the result object that this result will be added to
	 */
	function updateUpdate($orig, $upd, &$ar){
		if($orig->is_accept() && $orig->to_version() && ($orig->to_version() != $orig->original->latest_version)){
			//old published version - disallow
			return $ar->failure(400, "Illegal Update", "Old updates that have been accepted cannot be changed.");
		}
		elseif($upd->published() && $upd->to_version()){
			if($upd->to_version() != $upd->original->latest_version){
				return $ar->failure(400, "Illegal Update", "Updates can only be enacted against the latest version.");
			}
		}
		return $ar->accept();
	}

	/**
	 * Should we store rejected ldos?
	 * @param string $type the type of thing 
	 * @param LDO $ldo the ldo itself
	 * @return boolean true if rejected should be stored
	 */
	function storeRejected($type, $ldo = false){
		if(isset($this->store_rejected[$type])) return $this->store_rejected[$type];
		return $this->store_rejected['default'];
	}

	/**
	 * Should we roll back to pending when a update is accepted by policy but rejected by graph analysis?
	 * @param string $type the type of thing
	 * @param LDldo $ldo the thing itself
	 * @return boolean true if it should be rolled back
	 */
	function rollbackToPending($type, $ldo = false){
		if(isset($this->rollbacks[$type])) return $this->rollbacks[$type];
		return $this->rollbacks['default'];
	}
	
	/**
	 * Should we allow the specification of ids in input?
	 * @param string $type the type of thing
	 * @param LDldo $ldo the thing itself
	 * @return boolean true if it should be rolled back
	 */
	function demandIDAllowed($type = false, $ldo = false){
		if(isset($this->demand_id_allowed[$type])) return $this->demand_id_allowed[$type];
		return $this->demand_id_allowed['default'];
	}
}

