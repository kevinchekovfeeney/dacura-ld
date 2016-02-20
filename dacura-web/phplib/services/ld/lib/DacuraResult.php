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
	/** @var mixed result - the linked data object returned as the result of the request */
	var $result;

	/**
	 * Object constructor
	 * @param string $action the action being carried out
	 * @param boolean $test if true, the action is being carried out in test mode
	 */
	function __construct($action, $test = false){
		$this->action = $action;
		$this->test = $test;
	}
	
	function forAPI($format, $options, $srvr){
		$apiobj = array("status" => $this->status(), "test" => $this->test);
		if($this->msg_title || $this->msg_body){
			if($this->msg_title && $this->msg_body){
				$apiobj['message'] = array("title" => $this->msg_title, "body" => $this->msg_body);
			}
			elseif($this->msg_title){
				$apiobj['message'] = $this->msg_title;
			} 
			else {
				$apiobj['message'] = $this->msg_body;
			}
		}
		if(count($this->errors) > 0){
			$apiobj['errors'] = $this->errors;
		}
		if(count($this->warnings) > 0){
			$apiobj['warnings'] = $this->warnings;
		}
		if($this->result){
			if($this->result->display($format, $options, $srvr)){
				$apiobj['result'] = $this->result->forAPI($format, $options);
			}
			else {
				$apiobj['status'] = 'reject';
				$apiobj['message'] = array("title" => "Failed to create $format display for ".$this->result->ldtype." ".$this->result->id, "body" => $this->result->errcode . ": ".$this->result->errmsg);
			}
		}
		return $apiobj;
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
	function accept($res = false){
		if($res){
			$this->set_result($res);
		}
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
			if($sub->errors) $this->errors = array_merge($this->errors, $sub->errors);
			if($sub->warnings) $this->warnings = array_merge($this->warnings, $sub->warnings);
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
		
	function set_result($obj){
		$this->result = $obj;
		return $this;
	}
	

	function is_pending(){
		return ($this->status() == "pending");
	}
	
	function is_accept(){
		return ($this->status() == "accept" or !$this->status());
	}
	
	function is_reject(){
		return ($this->status() == "reject");
	}
	
	function is_error(){
		return $this->errcode > 0;
	}
	
	function is_confirm(){
		return $this->status() == "confirm";
	}

	function setGraphResult($gu, $hypo = false){
		$gu->setHypothetical($hypo);
		$action = "Dacura Quality Service";
		if(!$hypo){
			switch($gu->status()) {
				case "reject" :
					if($gu->is_error()){
						if(!$this->is_error()){
							$this->errcode = $gu->errcode;
							$this->msg_title = "Rejected by ".$action;
						}
						$this->addError($gu->errcode, $action, $gu->getErrorsSummary(), $gu->getErrorsDetails(), "graph");
					}
					else {
						$this->addError(200, $action, $gu->getErrorsSummary(), $gu->getErrorsDetails(),  "graph");
						if(!$this->is_reject()){
							$this->msg_title = "Rejected by ".$action;
						}
					}
					$this->status("reject");
					break;
				case "accept" :
					if($this->is_accept()){
						$this->status("accept");
					}
					break;
			}
		}
		elseif($gu->is_reject()) {
			$txt = "This candidate cannot be published as a report";
			$body = $gu->getErrorsSummary();
			$this->addWarning("Data Quality Service", $txt, $body);
		}
		if(!isset($this->report_graph_update)){
			$this->report_graph_update = $gu;
		}
		else {
			$this->report_graph_update->addGraphResult($gu);
		}
	}
	
	function undoGraphResult($gu){
		unset($this->candidate_graph_update);
		$this->report_graph_update->addGraphResult($gu);
	}
	
	function setUpdateGraphResult($udelta){
		$this->update_graph_update = new GraphAnalysisResults("Analysing Updates to Update Graph");
		$this->update_graph_update->setInserts($udelta['add']);
		$this->update_graph_update->setDeletes($udelta['del']);
		$this->update_graph_update->setMeta($udelta['meta']);
	}
	
	function setGraphResults($itrips, $dtrips, $is_hypo = false, $meta = false, $which = "candidate"){
		
	}
	
	function setLDGraphTriples($itrips = array(), $dtrips = array(), $is_hypo = false, $meta = false){
		$this->ld_graph_update = new GraphAnalysisResults("Updating Candidate Graph");
		$this->candidate_graph_update->setInserts($itrips);
		$this->candidate_graph_update->setDeletes($dtrips);
		$this->candidate_graph_update->setHypothetical($is_hypo);
		if($meta){
			$this->candidate_graph_update->setMeta($meta);
		}
	}
}

/**
 * Represents the state changes that are caused by an update to the dacura api for a particular graph....
 * @author chekov
 *
 */
class GraphResult extends DacuraResult {
	var $inserts = array();
	var $deletes = array();
	var $meta = array();//state changes {variable: [old, new])
	var $hypothetical = false;
	
	
	function setMeta($meta){
		$this->meta = $meta;
	}
	
	function setMetaChange($var, $oval, $nval){
		$this->meta[$var] = array($oval, $nval);
	}
	
	function includesGraphChanges(){
		return !$this->hypothetical &&
		(count($this->inserts) > 0 || count($this->deletes) > 0);
	}
	
	function getErrorsSummary() {
		$cnt = 0;
		foreach($this->errors as $gname => $errs){
			$cnt += count($errs);
		}
		$tex = $cnt . " Quality Control Error";
		if($cnt != 1) $tex .= "s";
		return $tex;
	}
	
	
	function getErrorsDetails() {
		$txt = "";
		foreach($this->errors as $gname => $errs){
			$txt .= count($errs) . " errors in $gname graph. ";
		}
		return $txt;
	}
	
	function setHypothetical($ishypo){
		$this->hypothetical = $ishypo;
	}
	
	function setInserts($q){
		$this->inserts = $q;
	}
	
	function setDeletes($q){
		$this->deletes = $q;
	}
	
	function addGraphResult($other, $hypo = false){
		$this->add($other);
		$this->inserts = array_merge($this->inserts, $other->inserts);
		$this->deletes = array_merge($this->deletes, $other->deletes);
		if($hypo){
			$this->hypothetical = true;
		}
	}
	
	function addOneGraphTestResult($gname, $iquads, $dquads, $errs){
		if(count($errs) > 0){
			if(!$this->errcode){
				$this->errcode = 400;
			}
			$this->status('reject');
			if(isset($this->errors[$gname])){
				$this->errors[$gname] = array_merge($this->errors[$gname], $errs);
			}
			else {
				$this->errors[$gname] = $errs;
			}
		}
		$this->inserts = array_merge($this->inserts, $iquads);
		$this->deletes = array_merge($this->deletes, $dquads);
	}
	
	function addOneGraphTestFail($gname, $iquads, $dquads, $errcode, $errmsg){
		$this->errcode = $errcode;
		$this->status('reject');
		$err = array("type" => "test fail",
				"action" => "write to graph",
				"errcode" => $errcode,
				"msg_title" => "Failed data quality checks when writing to graph.",
				"msg_body" => $errmsg);
		if(isset($this->errors[$gname])){
			$this->errors[$gname][] = $err;
		}
		else {
			$this->errors[$gname] = $err;
		}
		$this->inserts = array_merge($this->inserts, $iquads);
		$this->deletes = array_merge($this->deletes, $dquads);
	}
	
	function graph_fail($gname, $errcode){
		$this->status("reject");
		$this->errcode = $errcode;
	}
}