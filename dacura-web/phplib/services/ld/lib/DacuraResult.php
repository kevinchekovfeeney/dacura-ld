<?php
/**
 * A data structure that reports the result of an action in Dacura
 * @author chekov
 * @license GPL V2
 */
class DacuraResult extends DacuraObject {
	/** @var string the action the user attempted */
	var $action;
	/** @var boolean whether the action was attempted in test mode */
	var $test = false;
	/** @var string the title of the message for error messages or special messages */
	var $msg_title = "";
	/** @var string the body of the message for complex messages */
	var $msg_body = "";
	/** @var array an array of errors that were encountered while calculating the result */
	var $errors = array();
	/** @var array an array of warning that were encountered while calculating the result */
	var $warnings = array();
	
	var $decision;

	/**
	 * Object constructor
	 * @param string $action the action being carried out
	 * @param boolean $test if true, the action is being carried out in test mode
	 */
	function __construct($action, $test = false){
		$this->action = $action;
		$this->test = $test;
	}
	
	/**
	 * Returns true if there are no errors encountered
	 * @return boolean 
	 */
	function ok(){
		return !($this->errcode > 0);
	}

	/**
	 * Add a warning to the result
	 * @param string $action the action that provoked the warning
	 * @param string $prompt the title of the warning message
	 * @param string $txt the body of the warning message
	 * @return void
	 */
	function addWarning($action, $prompt, $txt){
		$this->warnings[] = array("action" => $action, "msg_title" => $prompt, "msg_body" => $txt);
	}			

	/**
	 * Add an incidental error to the result
	 * 
	 * @param number $errcode the http error code
	 * @param string $action the action that provoked the warning
	 * @param string $prompt the title of the warning message
	 * @param string $txt the body of the warning message
	 * @param string $type the type of the error (internal, permission, addressing, input, policy, unspecified)
	 * @return void
	 */
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
	
	/**
	 * Sets the result to 'accept' returns the object to allow chaining of such things
	 * @return DacuraResult
	 */	
	function accept(){
		$this->status("accept");
		return $this;
	}
	
	/**
	 * Sets the result to $decision and returns the object to allow chaining of such things
	 * @param string $decision a valid status @see DacuraObject::valid_statuses
	 * @return DacuraResult
	 */	
	function success($decision){
		$this->status($decision);
		if($this->is_pending()){
			$this->msg_title == $this->action ." requires approval";
		}
		return $this;
	}	
	
	/**
	 * Sets the result to pending and sets the message title and body 
	 * @param string $msg_title the message title
	 * @param string $msg_body the message body
	 * @return DacuraResult
	 */
	function pending($msg_title, $msg_body = false){
		$this->status("pending");
		$this->msg_title = $msg_title;
		if($msg_body){
			$this->msg_body = $msg_body;
		}
		return $this;
	}

	/**
	 * Sets the result to reject and sets messages
	 * @param string $msg_title
	 * @param string $msg_body
	 * @return DacuraResult
	 */
	function reject($msg_title, $msg_body = false){
		$this->status("reject");
		$this->msg_title = $msg_title;
		if($msg_body){
			$this->msg_body = $msg_body;
		}
		return $this;
	}
	
	/**
	 * Records a failure result
	 * @param number $errcode
	 * @param string $msg_title
	 * @param string $msg_body
	 * @param string $type
	 * @return DacuraResult
	 */
	function failure($errcode, $msg_title, $msg_body = false){
		$this->status("reject");
		$this->errcode = $errcode;
		$this->msg_title = $msg_title;
		if($msg_body){
			$this->msg_body = $msg_body;
		}
		return $this;
	}
	
	/**
	 * Adds a sub-result to this result
	 * 
	 * Results can be composite and consist of several results from different sub-systems.
	 * This function allows a result to incorporate another result
	 * @param DacuraResult $sub the sub-result object that will be added to this one
	 * @param boolean $chain should the results be merged?
	 */
	function add($sub, $chain = true){
		if($chain){
			$this->errors = array_merge($this->errors, $sub->errors);
			$this->warnings = array_merge($this->warnings, $sub->warnings);
		}
		switch($sub->status()) {
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
				$this->status("reject");
			break;
			case "pending" :
				$this->addWarning($sub->action, $sub->msg_title." requires approval.", $sub->msg_body);
				if($this->is_accept()){
					$this->status("pending");
					$this->msg_title = $sub->action . " requires approval";
				}
			break;
			case "accept" :
				if($this->is_accept()){
					$this->status("accept");
				}
			break;
		}
	}	
}

