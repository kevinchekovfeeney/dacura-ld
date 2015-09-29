<?php
class PolicyEngine extends DacuraObject {

	var $demand_id_allowed = true;
	var $store_rejected = true;


	function getPolicyDecision($action, $type, $context){
		$ar = new AnalysisResults("System Policy for $action");
		$ar->decision = "accept";
		if($action == "create"){
			//$ar->reject("Candidate Create Not Allowed", "You are a dirtbird and I'm not letting you");
			$ar->accept();
			//$ar->decision = 'pending';
		}
		elseif($action == "update"){
			//$ar->reject("Update Candidate Not Allowed", "You are a dirtbird and I'm not letting you");
			//$ar->accept();
			$ar->decision = 'accept';
		}
		elseif($action == "update update"){
			return $this->updateUpdate($context[0], $context[1], $ar);
		}
		elseif($action == "view candidate"){
			$ar->accept();
			//$ar->decision = 'pending';
			//$ar->reject("View Candidate not allowed", "You are a dirtbird and I'm not letting you");
		}
		elseif($action == "view update"){
			$ar->accept();
			//$ar->decision = 'confirm';
			//$ar->reject("View update Not Allowed", "You are a dirtbird and I'm not letting you");
		}
		return $ar;
	}

	function updateUpdate($orig, $upd, &$ar){
		if($orig->get_status() == "accept" && $orig->to_version() && ($orig->to_version() != $orig->original->latest_version)){
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

	function storeRejected($type, $cand){
		return $this->store_rejected;
	}

	function rollbackToPending($type, $cand){
		if($type == "update") return true;
		return true;
	}

	function demandIDAllowed($type = false, $obj = false){
		return $this->demand_id_allowed;
	}

}

