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
	/** @var array if this is true for a given ldo type then ldos and updates that are rejected will still be stored in the db */
	var $store_rejected = array();
	/** @var array An array of decisions for actions - these are default decisions, they can be overridden by settings */
	var $decisions = array(
		"view" => "accept",	
		//"update" => "accept",	
		"create" => "accept",	
		"delete" => "accept",	
		"view update" => "accept",	
		"delete update" => "accept"	
	);
	
	/**
	 * Loads some basic settings 
	 * @param LdService $service
	 */
	function __construct(DacuraService &$service){
		parent::__construct($service);
		$this->store_rejected = $this->getServiceSetting("store_rejected", $this->store_rejected);
		$this->decisions = $this->getServiceSetting("decisions", $this->decisions);
		if(!isset($this->decisions["update update"])){
			$this->decisions["update update"] = array($this, "updateUpdate");
		}
		$x = function($obj){ 
			//if(strtolower(get_class($obj->original)) == "candidate") {
			//	return "pending"; 
			//}
			return "accept";
		};
		$this->decisions['update'] = $x;
	}
	
	/**
	 * Returns a decision in response to an attempt to carry out an action 
	 * @param string $action create, update, delete, view, update update, view update, delete update
	 * @param object $obj the object / update being decided upon
	 * @return DacuraResult
	 */
	function getPolicyDecision($action, $obj){
		$ar = new DacuraResult("Checking system policy for $action");
		if($action == "update update"){
			if($this->updateUpdate($obj)){
				return $ar->accept();
			}
			else {
				return $ar->reject("Failed policy check", "Update to update rejected by policy ".$this->errmsg."[".$this->errcode."]");
			}
		}
		elseif(isset($this->decisions[$action])){
			if(is_string($this->decisions[$action])){
				$res = $this->decisions[$action];
			}
			elseif(is_callable($this->decisions[$action])){
				$res = call_user_func_array($this->decisions[$action], array($obj));
			}
			$ar->status($res);
			if($res == "reject"){
				$ar->title("Failed system policy check");
			}
			elseif($res == "pending"){
				$ar->msg("Approval required", "System policy requires that this action ($action) is approved before it will be published.");				
			}
			if($action == 'update' || $action == 'create'){
				if($ar->is_accept() && $obj->status() == "pending"){
					$ar->pending("Submission specified pending", "Request sent to api was marked as being in pending state");
				}
				if(($ar->is_accept() || $ar->is_pending()) && $obj->status() == "reject"){
					$ar->reject("Submission specified reject", "Request sent to api was marked as being in reject state");
				}
			}
		}
		else {
			$ar->reject("No system policy specified for $action", "You need to update the service settings to specify a policy result for $action");
		}
		return $ar;
	}

	/**
	 * Updates to updates are special and can be processed generically ...
	 * @param array $obj [original update object, the modified update object] 
	 * @param DacuraResult $ar the result object that this result will be added to
	 */
	function updateUpdate($obj){
		$orig = $obj[0];
		$upd = $obj[1];
		if($orig->is_accept() && $orig->to_version() && ($orig->to_version() != $orig->original->latest_version)){
			//old published version - disallow
			return $this->failure_result("Old updates that have been accepted cannot be changed.", 403);
		}
		elseif($upd->published() && $upd->to_version()){
			if($upd->to_version() != $upd->original->latest_version){
				return $this->failure_result("Updates can only be enacted against the latest version.", 403);
			}
		}
		return true;
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

