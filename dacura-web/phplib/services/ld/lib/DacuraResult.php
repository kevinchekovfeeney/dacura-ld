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
		$this->status("accept");
	}
	
	/**
	 * Gets / sets the message title
	 * @param string $x the new title or omit to just get the current title
	 * @return string the title
	 */
	function title($x = false){
		if($x !== false) $this->msg_title = $x;
		return $this->msg_title;
	}
	
	/**
	 * Gets / sets the message body
	 * @param string $x the new body or omit to just get the current body
	 * @return string the body
	 */
	function body($x = false){
		if($x !== false) $this->msg_body = $x;
		return $this->msg_body;
	}
	
	/**
	 * Sets the message title and body 
	 * @param string $tit the message title 
	 * @param [string] $body the body
	 * @return DacuraResult $this
	 */
	function msg($tit, $body = false){
		$this->title($tit);
		if($body !== false){
			$this->body($body);
		}
		return $this;
	}
	
	
	/**
	 * Returns true if there are no errors encountered
	 * @return boolean 
	 */
	function ok(){
		return !($this->errcode > 0);
	}
	
	/**
	 * Loads a new error into the error list as an RVO object
	 * @param string $type - the RVO violation type of the error
	 * @param array $args - the extra arguments to populate the error
	 * @return RVO the rvo violation object. 
	 */
	function error($type, $args){
		$err = RVO::loadViolation($type, $args);
		if($err){
			$this->errors[] = $err;			
		}
		return $err;				
	}
	
	/**
	 * Loads a new warning into the warning list as an RVO object
	 * @param string $type - the RVO violation type of the error
	 * @param array $args - the extra arguments to populate the error
	 * @return RVO the rvo violation object.
	 */
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

	/**
	 * Set the result messages for a warning
	 * @param unknown $action
	 * @param unknown $prompt
	 * @param unknown $txt
	 */
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

	function set_result($obj){
		$this->result = $obj;
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
	function add($sub, $chain = true, $force_msg = false){
		switch($sub->status()) {
			case "reject" :
				if($sub->is_error()){
					if(!$this->is_error()){
						$this->errcode = $sub->errcode;
						$this->msg_title = $sub->msg_title;//"Error in ".$sub->action;
						$this->msg_body = $sub->msg_body;						
					}
					else {
						$this->addError($sub->errcode, $sub->action, $sub->msg_title, $sub->msg_body);						
					}
				}
				else { 
					if(!$this->is_reject()){
						$this->msg_title = $sub->msg_title;//"Error in ".$sub->action;
						$this->msg_body = $sub->msg_body;						
					}
					else {
						//if the result is already in reject state, we save details of second reject
						$this->addError(200, $sub->action, $sub->msg_title, $sub->msg_body);
					}
				}
				$this->status("reject");
			break;
			case "pending" :
				if($this->is_accept()){
					$this->status("pending");
					$this->msg_title = $sub->msg_title;
					$this->msg_body = $sub->msg_body;
				}
				else {
					$this->addWarning($sub->action, $sub->msg_title, $sub->msg_body);						
				}
			break;
			case "accept" :
				if($force_msg || !($this->is_accept() && ($sub->is_accept() && count($sub->warnings) == 0))){
					if(!$sub->is_accept() && !$this->msg_title && $sub->msg_title){
						$this->msg_title = $sub->msg_title;
						if(!$this->msg_body && $sub->msg_body){
							$this->msg_body = $sub->msg_body;
						}
					}
					elseif($sub->is_accept() && $this->is_accept() && count($sub->warnings) > 0 && count($this->warnings) > 0){
						$this->msg_body = count($sub->warnings) + count($this->warnings) . " warnings generated";	
						$this->msg_title = $this->test ? "Accepted for publication with warnings" : "Successfully Published to DQS Triplestore";
					}		
					elseif($sub->is_accept() && count($sub->warnings) > 0){
						if($sub->msg_title && !$sub->msg_body){
							$this->msg_title = $this->test ? "Accepted for publication with warnings" : "Successfully Published to DQS Triplestore";
							$this->msg_body = $sub->msg_title;
						}
						elseif($sub->msg_title && $sub->msg_body){
							$this->msg_title = $sub->msg_title;								
							$this->msg_body = $sub->msg_body;								
						}
					}
					elseif($force_msg){
						$this->msg_title = $sub->msg_title;
						$this->msg_body = $sub->msg_body;						
					}
				}
				break;
		}
		if($chain && is_object($sub)){
			if($sub->errors) $this->errors = array_merge($this->errors, $sub->errors);
			if($sub->warnings) $this->warnings = array_merge($this->warnings, $sub->warnings);
			if($sub->graphs) $this->graphs = array_merge($this->graphs, $sub->graphs);
			if($sub->result && !$this->result) $this->result = $sub->result;
		}		
	}
		
	/**
	 * Adds a graph test result to this result - 
	 * 
	 * to support multiple different graph tests being reported in a single result
	 * @param string $gid - the id of the graph result (dqs|ld|meta|update)
	 * @param GraphResult $gu the graph result to be added to this one
	 * @param boolean $hypo - true if this is just a hypotethical test result, not an actual update
	 */
	function addGraphResult($gid, GraphResult $gu, $hypo = false, $add_err = true){
		$action = "$gid sgraph result";
		if(!$hypo){
			$this->add($gu);
		}
		elseif($gu->is_reject()) { //graph errors and warnings are copied into the warnings of this result
			if(count($gu->errors) > 0 || count($gu->warnings) > 0){
				foreach($gu->errors as $err){
					$this->warnings[] = $err;
				}
				if($gu->warnings && count($gu->warnings) > 0){
					$this->warnings = array_merge($this->warnings, $gu->warnings);
				}
			}
			else {
				$this->warnings[] = new GraphTestFailure($gu->action, $gu->msg_title, $gu->msg_body);
			}
			if($add_err){
				$this->errors[] = new GraphTestFailure($gu->action, $gu->msg_title, $gu->msg_body);
			}
		}
		$this->graphs[$gid] = $gu;//only one result per graph result id				
	}
	
	/**
	 * Creates a new graph2 result object and adds it to the index of graphs
	 * @param string $gid graph id
	 * @param string $status one of DacuraObject::$valid_statuses
	 * @param array $itrips array of triples / quads to be inserted
	 * @param array $dtrips array of triples / quads to be deleted
	 * @param string $is_test true if this is a test 
	 * @param string $is_hypo true if the graph invocation is hypothetical - does not determine result of request
	 */
	function createGraphResult($gid, $msg, $status, $itrips = array(), $dtrips = array(), $is_test = false, $is_hypo = false){
		$gu = new GraphResult("Updates to graph $gid", $is_test);
		if(count($itrips) > 0 || count($dtrips) > 0){
			$gu->setInserts($itrips);
			$gu->setDeletes($dtrips);
			if($gid == "meta"){
				$gu->msg($msg, count($itrips)." values inserted, ".count($dtrips)." values deleted");				
			}
			else {
				$gu->msg($msg, count($itrips)." quads inserted, ".count($dtrips)." quads deleted");
			}
		}
		else {
			$gu->msg($msg, "No updates");				
		}	
		$gu->setHypothetical($is_hypo);
		$gu->status($status);
		$this->graphs[$gid] = $gu;
	}
	
	/**
	 * Called to rollback a graph result when things go wrong.
	 * @param string $gid the graph id
	 * @param GraphResult $gu the graph result of the undo comment
	 */
	function undoGraphResult($gid, $gu){
		unset($this->graphs["ld"]);
		$this->graphs[$gid]->addGraphResult($gu);
	}
	
	/**
	 * Adds a meta result (detailing updates to object's meta-data) to the result
	 * @param unknown $metabox - output of LDOUpdate->getMetaUpdates()
	 * @param string $status - the status of the result (is the update okay)
	 * @param boolean $is_test - is this a test invocation
	 * @param boolean $is_hypo - is this a hypothetical result
	 */
	//function createMetaResult($metabox, $msg, $status, $is_test = false, $is_hypo = false){
	//	$this->createGraphResult("meta", $status, array_keys($metabox), array_values($metabox),$is_test, $is_hypo);
	//}
	
	/**
	 * Returns a representation of the result for the api. 
	 * @param string $format one of LDO::$valid_display_formats
	 * @param array $options - options as submitted to api
	 * @param LdDacuraServer $srvr
	 * @return array a json array of the result ready for sending to the client
	 */
	function forAPI($format, $options, LdDacuraServer &$srvr){
		$apiobj = array("status" => $this->status(), "action" => $this->action, "test" => $this->test);
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
}

/**
 * Represents the state changes that are caused by an update to the dacura api for a particular graph....
 * There are 4 graphs considered:
 * * ld graph - changes to linked data objects
 * * meta graph - changes to object meta data
 * * dqs graph - changes to triplestore
 * * update graph - changes to update store
 *
 */
class GraphResult extends DacuraResult {
	/** @var array What has been inserted into the graph */
	var $inserts = array();
	/** @var array What has been removed from the graph */
	var $deletes = array();
	/** @var boolean if true, this is a hypotethical result which should not cause the overall result to fail */
	var $hypothetical = false;
	
	/** 
	 * Does this result include changes to the graph (could be just errors, warnings, null)
	 * @return boolean - true if this graph has changed
	 */
	function includesGraphChanges(){
		return !$this->hypothetical &&
		(count($this->inserts) > 0 || count($this->deletes) > 0);
	}

	/**
	 * Specify that this result is hypothetical 
	 * @param boolean $ishypo if true the result is hypotethical 
	 */
	function setHypothetical($ishypo){
		$this->hypothetical = $ishypo;
	}
	
	/**
	 * Specify the set of quads / json / triples that has been inserted into the graph
	 * @param array $q - quads / json / triples added
	 */
	function setInserts($q){
		$this->inserts = $q;
	}
	
	/**
	 * Specify the set of quads / json / triples that has been removed from the graph
	 * @param array $q quads / json / triples removed
	 */
	function setDeletes($q){
		$this->deletes = $q;
	}
	
	/**
	 * Extends the method to include amalgamation of inserts and deletes from sub-graph result
	 * @see DacuraResult::add()
	 */
	function add($sub, $chain = true, $force_msg = false){
		if($chain && is_object($sub)){
			if(isset($sub->inserts) && $sub->inserts) $this->inserts = array_merge($this->inserts, $sub->inserts);
			if(isset($sub->deletes) && $sub->deletes) $this->deletes = array_merge($this->deletes, $sub->deletes);
		}
		return parent::add($sub, $chain, $force_msg);
	}

	/**
	 * Adds inserts and deletes into api result
	 * @see DacuraResult::forAPI()
	 */
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

/**
 * Represents the result of a DQS graph update / test
 *
 */
class DQSResult extends GraphResult {
	/** @var string|array an array of all the configured tests for the dqs test or the string "all" */
	var $tests = array();
	var $imports = array();
	
	/** 
	 * Store the set of tests that this result corresponds to 
	 * @param array|string $tests an array of tests or the string "all"
	 */
	function setTests($tests){
		$this->tests = $tests;
	}
	
	function setImports($imports){
		$this->imports = $imports;
	}

	/**
	 * Extends method to include the tests property in api 
	 * @see GraphResult::forAPI()
	 */
	function forAPI($format, $options, $srvr){
		$apiobj = parent::forAPI($format, $options, $srvr);
		$apiobj['tests'] = $this->tests;
		$apiobj['imports'] = $this->imports;
		return $apiobj;
	}
	
	/**
	 * Extends method to include the tests property in result combination 
	 * @see GraphResult::add()
	 */
	function add($sub, $chain = true, $force_msg = false){
		if($chain && is_object($sub)){
			if($this->tests != "all" && isset($sub->tests) && $sub->tests) $this->tests = is_array($sub->tests) ? array_merge($this->tests, $sub->tests) : $sub->tests;
			if(isset($sub->imports) && $this->imports && $sub->imports){
				foreach($sub->imports as $i => $imp){
					if(!isset($this->imports[$i])){
						$this->imports[$i] = $imp;
					}
				}
			}
			elseif(isset($sub->imports) && $sub->imports){
				$this->imports = $sub->imports;
			}
		}
		return parent::add($sub, $chain, $force_msg);
	}
	
	/**
	 * Parses the DQS response - an array of name-value error objects - and turns them into RVO objects 
	 * 
	 * Adds the violations to the result's errors and warnings and sets the result to reject or accept depending on the contents
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
				return $this->reject("Dacura Quality Service Tests Failed", $this->errors[0]->msg().$this->errors[0]->info());
			}
			else {
				return $this->reject("Dacura Quality Service Errors", "DQS identified ".count($this->errors) . " errors in the input data");				
			}
		}
		return $this->accept();
	}
}


