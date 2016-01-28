<?php
require_once("RequestAnalysisResults.php");
require_once("GraphAnalysisResults.php");

class AnalysisResults {
	var $action;
	var $test = false;
	var $msg_title = "";//for error messages or special messages
	var $msg_body = "";//same
	var $decision; //reject,confirm,pending,accept
	var $errcode = 0;
	var $errors = array();
	var $warnings = array();

	function __construct($action, $test = false){
		$this->action = $action;
		$this->test = $test;
	}
	
	function ok(){
		return !($this->errcode > 0);
	}

	function status(){
		return $this->decision;
	}
	
	function addWarning($action, $prompt, $txt){
		$this->warnings[] = array("action" => $action, "msg_title" => $prompt, "msg_body" => $txt);
	}			

	function addError($errcode, $action, $prompt, $txt = "", $type = false){
		if($type == false){
			switch($errcode){
				case 500:
					$type = "internal";
					break;
				case 403:
					$type = "permission";
					break;
				case 404:
					$type = "addressing";
					break;
				case 400:
					$type = "input";
					break;
				case 200:
					$type = "policy";
					break;
				default:
					$type = "unspecified";
					break;
			}
		}
		$this->errors[] = array("code" => $errcode, "type" => $type, "action" => $action, "msg_title" => $prompt, "msg_body" => $txt);
	}
	
	function is_pending(){
		return ($this->decision == "pending");
	}

	function is_accept(){
		return ($this->decision == "accept" or !$this->decision);
	}

	function is_reject(){
		return ($this->decision == "reject");
	}
	
	function is_error(){
		return $this->errcode > 0;
	}
	
	function is_confirm(){
		return $this->decision == "confirm";
	}
	
	function accept(){
		$this->decision = "accept";
		return $this;
	}
	
	function success($decision){
		$this->decision = $decision;
		if($this->decision == "pending"){
			$this->msg_title == $this->action ." requires approval";
		}
		return $this;
	}	
	
	function pending($msg_title, $msg_body = false){
		$this->decision = "pending";
		$this->msg_title = $msg_title;
		if($msg_body){
			$this->msg_body = $msg_body;
		}
		return $this;
	}

	function reject($msg_title, $msg_body){
		$this->decision = "reject";
		$this->msg_body = $msg_body;
		$this->msg_title = $msg_title;
		return $this;
	}
	
	function failure($errcode, $msg_title, $msg_body, $type = false){
		$this->decision = "reject";
		$this->errcode = $errcode;
		$this->msg_title = $msg_title;
		$this->msg_body = $msg_body;
		return $this;
	}
	
	function add($sub, $chain = true){
		if($chain){
			$this->errors = array_merge($this->errors, $sub->errors);
			$this->warnings = array_merge($this->warnings, $sub->warnings);
		}
		switch($sub->decision) {
			case "reject" :
				if($sub->is_error()){
					$this->addError($sub->errcode, $sub->action, $sub->msg_title, $sub->msg_body);
					if(!$this->is_error()){
						$this->errcode = $sub->errcode;
						$this->msg_title = "Error in ".$sub->action;						
					}
				}
				else { //if the sub is already in reject state, we save details of second reject
					$this->addError(200, $sub->action, $sub->msg_title, $sub->msg_body);
					if(!$this->is_reject()){
						$this->msg_title = "Rejected by ".$sub->action;
					}
				}
				$this->decision = "reject";
			break;
			case "confirm" :
				$this->addWarning($sub->action, $sub->msg_title." requires confirmation by user.", $sub->msg_body);
				if($this->is_accept()){
					$this->decision = "confirm";
					$this->msg_title = $sub->action. " requires confirm ";						
				}
			break;
			case "pending" :
				$this->addWarning($sub->action, $sub->msg_title." requires approval.", $sub->msg_body);
				if($this->is_accept()){
					$this->decision = "pending";
					$this->msg_title = $sub->action . " requires approval";
				}
			break;
			case "accept" :
				if($this->is_accept()){
					$this->decision = "accept";
				}
			break;
		}
	}	
}


