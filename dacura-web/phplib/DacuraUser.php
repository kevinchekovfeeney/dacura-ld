<?php

require_once("DacuraSession.php");
require_once("UserRole.php");

class DacuraUser {
	var $id;
	var $email;
	var $name;
	var $status;
	var $session_dump;	//directory where my sessions live.
	//var $chunks = array();
	var $sessions = array();
	var $roles = array();

	function __construct($id, $e, $n, $status){
		$this->id = $id;
		$this->email = $e;
		$this->name = $n;
		$this->status = $status;
	}
	
	/*
	 * Basic Identity Stuff
	 */	
	function getName(){
		return $this->email;
	}
	
	function setSessionDirectory($dir){
		$this->session_dump = $dir."/";
		if (!file_exists($dir)) {
			if(!mkdir($dir, 0777, true)){
				return $this->return_error("User directory does not exist and could not be created", 500);
			}
		}
		return true;
	}
	
	function isGod(){
		foreach($this->roles as $r){
			if($r->isGod()) return true;
		}
		return false;
	}

	function rolesSpanCollections(){
		if(count($this->roles) <= 1) return false;
		$r1 = $this->roles[0];
		$r1c = $r1->collection_id;
		foreach($this->roles as $r){
			if($r->collection_id != $r1c) return true;
		}
		return false;
	}
	
	function getRoleCollectionId(){
		if(count($this->roles) < 1) return false;
		$r1 = $this->roles[0];
		return $r1->collection_id;
	}

	function isCollectionAdmin($cid){
		foreach($this->roles as $r){
			if($r->collection_id == $cid && $r->role == "admin" && $r->dataset_id == 0) return true;
		}
		return false;
	}
	
	function rolesSpanDatasets($cid){
		if(count($this->roles) <= 1) return false;
		$datasets = array();
		foreach($this->roles as $r){
			if($r->collection_id == $cid){
				if(count($datasets) > 0 && !in_array($r->dataset_id, $datasets)) return true;
			}
			$datasets[] = $r->dataset_id;
		}
		return false;
	}
	
	function getRoleDatasetId(){
		if(count($this->roles) < 1) return false;
		$r1 = $this->roles[0];
		return $r1->dataset_id;
	}
	
	function setStatus($s){
		$this->status = $s;
	}
	
	/*
	 * Session Management
	 */
	
	function createSession($id, $autostart=true){
		if(!isset($this->sessions[$id])){
			$this->sessions[$id] = new DacuraSession($id, $autostart);			
		}
		else {
			$this->sessions[$id]->registerEvent(array("action" => "abort"));
			$this->dumpSession($id);
			$this->sessions[$id] = new DacuraSession($id, $autostart);
		}
		return true;
	}
	
	function endSession($id, $action = "end"){
		if(isset($this->sessions[$id])){
			$this->sessions[$id]->registerEvent(array("action" => $action));
			$this->dumpSession($id);
			unset($this->sessions[$id]);
			return true;
		}
		return false;
	}
	
	function dumpSession($id){
		//make sure directory is there...
		if(isset($this->sessions[$id])){
			$record = json_encode($this->sessions[$id]->events);
			file_put_contents($this->session_dump."$id.session", $record."\n", FILE_APPEND | LOCK_EX);				
		}
		return $this->return_error("No session $id to pause", 404);		
	}
	
	function pauseSession($id){
		if(isset($this->sessions[$id])){
			$this->sessions[$id]->pause();
			return true;
		}
		return $this->return_error("No session $id to pause", 404);
	}

	function unpauseSession($id){
		if(isset($this->sessions[$id])){
			$this->sessions[$id]->unpause();
			return true;
		}
		return $this->return_error("No session $id to unpause", 404);
	}
	
	function getSessionDetails($id){
		if(isset($this->sessions[$id])){
			$s = $this->sessions[$id];
			$res = array("duration" => gmdate("H:i:s", $s->activeDuration()),
					"assigned" => $s->eventCount("assign"), 
					"accepted" => $s->eventCount("accept"), 
					"rejected"=> $s->eventCount("reject"));
			return $res;
		}
		return $this->return_error("session $id does not exist", 404);
	}
	
	
	function recordAction($id, $type, $dump = false){
		if(!isset($this->sessions[$id])){
			$this->sessions[$id] = new DacuraSession($id);
		}
		$this->sessions[$id]->registerEvent(array("action" => $type));
		if($dump){
			$this->dumpSession($id);
			unset($this->sessions[$id]);
		}
	}	
	
	function setSessionEvent($id, $ev){
		if(!isset($this->sessions[$id])){
			$this->sessions[$id] = new DacuraSession($id);
		}
		$this->sessions[$id]->registerEvent($ev);
	}
	
	
	function return_error($errmsg, $errcode){
		$this->errmsg = $errmsg;
		$this->errcode = $errcode;
		return false;
	}
	
	function hasLiveSession($id){
		return (isset($this->sessions[$id]) ? $this->sessions[$id]->hasLiveSession() : false); 
	}
	
	function endLiveSessions($action){
		foreach($this->sessions as $sid => $sess){
			$this->endSession($sid, $action);
			$this->dumpSession($sid);
			unset($this->sessions[$sid]);	
		}
	}
	
	function unsetCurrentCandidate($id){
		if(isset($this->sessions[$id])){
			$this->sessions[$id]->current_candidate = null;
		}
	}
	

	
}




