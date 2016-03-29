<?php
include_once("DacuraResult.php");

/**
 * A class which produces policy responses to actions in the linked data api
 * 
 * Basically this class is supposed to tell us what is allowed when users do stuff
 * @author chekov
 * @license GPL V2
 */
class PolicyEngine extends DacuraController {
	/** @var array true for a given type if users are allowed to specify their own ids for ldos */
	/** @var array if this is true for a given ldo type then ldos and updates that are rejected will still be stored in the db */
	var $store_rejected = array("create", "update");
	/** @var array An array of decisions for actions - these are default decisions, they can be overridden by settings */
	var $decisions = array(
		"view" => "accept",	
		"update" => "accept",	
		"create" => "accept",	
		"delete" => "accept",	
		"view update" => "accept",	
		"delete update" => "accept"	
	);
	
	function __construct(LdService &$service){
		parent::__construct($service);
		$this->store_rejected = $this->getServiceSetting("store_rejected", $this->store_rejected);
		$this->decisions = $this->getServiceSetting("decisions", $this->decisions);
		if(!isset($this->decisions["update update"])){
			$this->decisions["update update"] = array($this, "updateUpdate");
		}
	}
	
	/**
	 * Returns a decision in response to an attempt to carry out an action 
	 * @param string $action create, update, delete, view, update update, view update, delete update
	 * @param string $type what type of thing is being actioned on 
	 * @param mixed $context any important contextual information goes in here
	 * @return DacuraResult
	 */
	function getPolicyDecision($action, $obj){
		$ar = new DacuraResult("System policy for $action");
		if(isset($this->decisions[$action])){
			if(is_string($this->decisions[$action])){
				$ar->status($this->decisions[$action]);
			}
			elseif(is_callable($this->decisions['action'])){
				call_user_func_array($this->decisions['action'], array($obj, $context));
			}
		}
		else {
			$ar->reject("No system policy specified for $action", "You need to update the service settings to specify a policy result for $action");
		}
		if($action == "update update"){
			return $this->updateUpdate($context[0], $context[1], $ar);
		}
		return $ar;
	}

	/**
	 * Updates to updates are special and can be processed generically ...
	 * @param ldoUpdate $orig the original update object
	 * @param ldoUpdate $upd the modified update object
	 * @param DacuraResult $ar the result object that this result will be added to
	 */
	function updateUpdate($context){
		$orig = $context[0];
		$upd = $context[1];
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
	function storeRejected($action, $ldo){
		if(is_array($this->store_rejected) && in_array($action, $this->store_rejected)){
			return true;
		}
		elseif(is_callable($this->store_rejected)){
			return $this->store_rejected($action, $ldo);
		}
		return false;
	}

}

