<?php
/**
 * Class representing functionality and properties that are useful for all Dacura Objects
 * 
 * Other classes inherit from this class so that they can utilise the common functions
 * Creation Date: 25/12/2014
 * @author Chekov
 * @license GPL V2
 */

class DacuraObject {
	/** @var string a human-readable error message  */
	var $errmsg;
	/** @var integer a non-zero number indicates an error condition */
	var $errcode;
	/** @var string either accept, reject, pending or deleted */
	var $status;
	/** @var string Dacura IDs are between 2 and 40 characters long [a-z0-9-_]*/
	var $id;
	/** @var array the set of valid statuses and their human readable forms */
	static $valid_statuses = array("accept" => "Accepted", "pending" => "Pending Approval", "reject" => "Rejected", "deleted" => "Deleted");
	/** @var array the list of Dacura Reserved Words */
	static $reserved_words = array("all", "dacura", "structure", "type", "schema");
	/** @var array a list of phrases that aren't allowed to appear in titles */
	static $banned_phrases = array();
	
	/**
	 * Set the status of the object
	 * @param string $s accept, reject, pending or deleted 
	 */
	function setStatus($s){
		$this->status = $s;
	}

	/**
	 * Get the status of the object
	 * @return string the status of the object
	 */
	function getStatus(){
		return $this->status;
	}
	
	/**
	 * Get or set the status of the object
	 * @param string|boolean $s if the argument is included, it will set the status.  
	 * @return string the object's status
	 */
	function status($s=false){
		if($s !== false){
			$this->status = $s;
		}
		return $this->status;
	}
	
	/**
	 * Sets the internal errorcode and message of the object and returns false
	 * @param string $msg A user-readable error message
	 * @param number $code A http error code
	 * @return boolean always returns false
	 */
	function failure_result($msg, $code = 500){
		$this->errmsg = $msg;
		$this->errcode = $code;
		return false;
	}

	/**
	 * removes internal fields from objects that are being sent over the api
	 */
	function forapi(){
		if($this->errcode == null){
			unset($this->errcode);
			unset($this->errmsg);
		}
		return $this;
	}
	
	/**
	 * Checks to ensure that requested ids for dacura objects are valid ids
	 * @param string $id the requested id
	 * @param int $maxlen the maximum permitted length of ids
	 * @param string[] $bwords an array of 'banned' words to extend the built-in dacura reserved words
	 * @param string[] $bphrases an array of banned phrases to extend the built-in dacura list of banned phrases
	 */
	function isValidDacuraID($id, $maxlen = 40, $bwords = array(), $bphrases = array()){
		$id = strtolower($id);
		$nid = filter_var($id, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		$nid = filter_var($nid, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
		if($nid != $id){
			return $this->failure_result("Illegal characters in requested id $id" , 400);
		}
		if(!(ctype_alnum($id) && strlen($id) > 1 && strlen($id) < $maxlen)){
			return $this->failure_result("Illegal ID, it must be between 2 and $maxlen alphanumeric [a-z0-9_-] characters (no spaces or punctuation).", 400);
		}
		$bwords = array_merge($bwords, DacuraObject::$reserved_words);
		if(in_array($id, $bwords)){
			return $this->failure_result("$id is a Dacura reserved word, it is not permitted to be used as an object id", 400);
		}
		return true;
	}
	
	/**
	 * does the object have 'pending' status
	 * @return boolean
	 */
	function is_pending(){
		return isset($this->status) && $this->status != null && $this->status && ($this->status == "pending");
	}
	
	/**
	 * does the object have 'accept' status
	 * @return boolean
	 */
	function is_accept(){
		return isset($this->status) && $this->status != null && $this->status && ($this->status == "accept");
	}
	
	/**
	 * does the object have 'reject' status
	 * @return boolean
	 */
	 function is_reject(){
		return isset($this->status) && $this->status != null && $this->status && ($this->status == "reject");
	}
	
	/**
	 * does the object have 'deleted' status
	 * @return boolean
	 */
	function is_deleted(){
		return isset($this->status) && $this->status != null && $this->status && ($this->status == "deleted");		
	}	
	
	/** 
	 * Has an error  been encountered by the object 
	 * @return boolean
	 */
	function is_error(){
		return $this->errcode > 0;
	}
}