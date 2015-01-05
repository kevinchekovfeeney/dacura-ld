<?php
/*
 * Class representing a session of a user of the Dacura System
 * The basic model is simple - events are registered with the session 
 * The class provides basic session control (start, stop, pause, register event)
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class DacuraSession extends DacuraObject {

	var $session_id;
	var $state_vars;
	var $state; 
	var $session_timeout = 3600;
	var $events;
	var $start;
	var $current_candidate;
	//var $local_session_timeout = 3600;

	function __construct($session_id, $autostart = true){
		$this->start = time();
		$this->session_id = $session_id;
		if($autostart){$this->start();}
	}
	
	function start(){
		$this->start = time();
		$this->state = 'active';
		$this->events[$this->start] = array("action" => 'start');		
	}

	function registerEvent($settings){
		$t_index = time();
		while(isset($this->events[$t_index])) $t_index++;
			$this->events[$t_index] = $settings;
	}
	
	function end(){
		$this->state = 'end';
	}

	function pause(){
		$this->registerEvent(array("action" => "pause"));
		$this->state = 'pause';
	}
	

	function unpause(){
		$this->registerEvent(array("action" => "unpause"));
		$this->state = 'active';
	}
	
	function eventCount($evt){
		$count = 0;
		foreach($this->events as $t => $ev){
			if($ev['action'] == $evt){
				$count++;
			}
		}
		return $count;
	}
	
	function activeDuration(){
		$now = time();
		$paused_total = 0;
		$paused_time = 0;
		foreach($this->events as $t => $event){
			if($event['action'] == 'pause'){
				if($paused_time == 0) $paused_time = $t;
			}
			elseif($paused_time){
				$paused_total += $t - $paused_time;
				$paused_time = 0;
			}
		}
		if($paused_time){
			$paused_total += $now - $paused_time;
		}
		return $now - $this->start - $paused_total;
	}
	
	function assignCandidate($id){
		$this->registerEvent(array("action" => "assign", "id" => $id));
		$this->current_candidate = $id;
	}
	
	function getOpenAssignedCandidate(){
		return (isset($this->current_candidate) && $this->current_candidate) ? $this->current_candidate : false;		
	}
	
	function hasLiveSession(){
		return isset($this->current_candidate) && $this->current_candidate ? $this->current_candidate : false;
	}
	
}	
	


