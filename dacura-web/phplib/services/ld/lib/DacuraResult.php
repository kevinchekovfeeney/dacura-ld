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
	/** @var array<GraphResult> an optional array of graph results generated as part of the request */
	var $graphs = array();
	var $violations;

	/**
	 * Object constructor
	 * @param string $action the action being carried out
	 * @param boolean $test if true, the action is being carried out in test mode
	 */
	function __construct($action, $test = false){
		$this->action = $action;
		$this->test = $test;
	}
	
	function title($x = false){
		if($x !== false) $this->msg_title = $x;
		return $this->msg_title;
	}

	function body($x = false){
		if($x !== false) $this->msg_body = $x;
		return $this->msg_body;
	}
	
	function msg($tit, $body = false){
		$this->title($tit);
		if($body !== false){
			$this->body($body);
		}
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
			if(is_object($this->result) && method_exists($this->result, "display")){
				if($this->result->display($format, $options, $srvr)){
					$apiobj['result'] = $this->result->forAPI($format, $options);
				}
				else {
					$apiobj['status'] = 'reject';
					$apiobj['message'] = array("title" => "Failed to create $format display for ".$this->result->ldtype." ".$this->result->id, "body" => $this->result->errcode . ": ".$this->result->errmsg);
				}
			}
			else {
				$apiobj['result'] = $this->result;
			}
		}
		foreach($this->graphs as $gid => $gr){
			if(isset($options['show_'.$gid.'_triples']) && $options['show_'.$gid.'_triples']){
				$apiobj['graph_'.$gid] = $gr->forAPI($format, $options, $srvr);
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
	
	function error($type, $args){
		$err = RVO::loadViolation($type, $args);
		if($err){
			$this->errors[] = $err;			
		}
		return $err;				
	}
	
	function warning($type, $args){
		$err = RVO::loadViolation($type, $args);
		if($err){
			$this->warnings[] = $err;
		}
		return $err;
		
	}

	/**
	 * Add a warning to the result
	 * @param string $action the action that provoked the warning
	 * @param string $prompt the title of the warning message
	 * @param string $txt the body of the warning message
	 * @return void
	 */
	function addWarning($action, $prompt, $txt){
		$this->warnings[] = new SystemWarning($action, $prompt, $txt);
	}	

	function setWarning($action, $prompt, $txt){
		$this->action = $action;
		$this->msg_body = $txt;
		$this->msg_title = $prompt;		
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
	function addError($errcode, $action, $prompt, $txt = ""){
		$this->errors[] = new SystemViolation($errcode, $action, $prompt, $txt);
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
		if($chain && is_object($sub)){
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
	
	function addGraphResult($gid, $gu, $hypo = false){
		$gu->setHypothetical($hypo);
		$action = "$gid sgraph result";
		if(!$hypo){
			$this->add($gu);
		}
		elseif($gu->is_reject()) {
			$this->addWarning("$gid graph", $gu->msg_title, $gu->msg_body);
			foreach($gu->errors as $err){
				$this->warnings[] = $err;
			}
			if($gu->warnings && count($gu->warnings) > 0){
				$this->warnings = array_merge($this->warnings, $gu->warnings);
			}
		}
		if(!isset($this->graphs[$gid])){
			$this->graphs[$gid] = $gu;//only one result per graph result				
		}
	}
	
	/**
	 * Creates a new graph result object and adds it to the index of graphs
	 * @param string $gid graph id
	 * @param string $status one of DacuraObject::$valid_statuses
	 * @param array $itrips array of triples / quads to be inserted
	 * @param array $dtrips array of triples / quads to be deleted
	 * @param string $is_test true if this is a test 
	 * @param string $is_hypo true if the graph invocation is hypothetical - does not determine result of request
	 */
	function createGraphResult($gid, $status, $itrips = array(), $dtrips = array(), $is_test = false, $is_hypo = false){
		$gu = new GraphResult("Graph $gid update", $is_test);
		$gu->status($status);
		$gu->setHypothetical($is_hypo);
		$gu->setInserts($itrips);
		$gu->setDeletes($dtrips);
		$this->graphs[$gid] = $gu;
	}
	
	function createMetaResult($metabox, $status, $is_test = false, $is_hypo = false){
		$this->createGraphResult("meta", $status, array_keys($metabox), array_values($metabox),$is_test, $is_hypo);
	}
	
	function undoGraphResult($gid, $gu){
		unset($this->graphs["ld"]);
		$this->graphs[$gid]->addGraphResult($gu);
	}
	
	function setUpdateGraphResult($udelta){
		$this->update_graph_update = new GraphAnalysisResults("Analysing Updates to Update Graph");
		$this->update_graph_update->setInserts($udelta['add']);
		$this->update_graph_update->setDeletes($udelta['del']);
		//$this->update_graph_update->setMeta($udelta['meta']);
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
	var $hypothetical = false;
	
	function includesGraphChanges(){
		return !$this->hypothetical &&
		(count($this->inserts) > 0 || count($this->deletes) > 0);
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
	
	function addGraphResult($gid, $other, $hypo = false){
		parent::addGraphResult($gid, $other, $hypo);
		$this->inserts = array_merge($this->inserts, $other->inserts);
		$this->deletes = array_merge($this->deletes, $other->deletes);
		if($hypo){
			$this->hypothetical = true;
		}
	}
		
	function forAPI($format, $options, $srvr){
		$apiobj = parent::forAPI($format, $options, $srvr);
		if(count($this->inserts) > 0){
			$apiobj['inserts'] = $this->inserts;
		}
		if(count($this->deletes) > 0){
			$apiobj['deletes'] = $this->deletes;
		}
		$apiobj['hypothetical'] = $this->hypothetical;
		return $apiobj;
	}
}

class DQSResult extends GraphResult {

	/**
	 * Parses the DQS response - an array of name-value error objects - and turns them into RVO objects 
	 * @param $array an array of json name-value objects with the information returned by DQS
	 */
	function parseErrors($array){
		foreach($array as $err){
			if(isset($err['rdf:type']) && $cls = $err['rdf:type']){
				if($nrvo = RVO::loadViolation($cls, $err)){
					if($nrvo->bp()){
						$this->warnings[] = $nrvo;
					}
					else {
						$this->errors[] = $nrvo;						
					}
				}
				else {
					return $this->reject("Dacura Quality Service Failure", "DQS returned unknown error class $cls");						
				}
			}
			else {
				return $this->reject("Dacura Quality Service Failure", "DQS returned error with no violation class specified");				
			}
		}
		if(count($this->errors) > 0){
			if(count($this->errors) == 1){
				return $this->reject("Dacura Quality Service Error", $this->errors[0]->msg().$this->errors[0]->info());
			}
			else {
				return $this->reject("Dacura Quality Service Errors", "DQS identified ".count($this->errors) . " in the input");				
			}
		}
		return $this->accept();
	}
	
}

/**
 * Simple class representing changes in the object's metadata due to a request.
 * @author chekov
 *
 */
class MetaResult {
	var $meta = array();//state changes {variable: [old, new])
	
	
	
	function setMeta($meta){
		$this->meta = $meta;
	}
	
	function setMetaChange($var, $oval, $nval){
		$this->meta[$var] = array($oval, $nval);
	}
	
}
