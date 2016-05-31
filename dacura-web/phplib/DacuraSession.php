<?php
/**
 * Class representing the session of a user of the Dacura System
 * 
 * The basic model is simple - events are registered with the session by a service
 * The class provides basic session control (start, stop, pause, register event)
 * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */
class DacuraSession extends DacuraObject {
	/** @var string the id of the service that owns the session (one session active per service) */
	var $id; 
	/** @var string the id of the collection in which the session takes place (collection context) */
	var $collection_id;
	/** @var string[] listing the valid states of sessions */
	static $valid_states = array("active", "pause", "end");
	/** @var string the current session state - must be one of DacuraSession::$valid_states */
	var $state; 
	/** @var the length of time in seconds that it will take a session to timeout due to inactivity */
	var $session_timeout = 3600;
	/** @var array an array of events that have occured during this session */
	var $events;
	/** @var number the timestamp of the session's start */
	var $start;
	/** @var number the timestamp of the session's end */
	var $end = false;
	/** @var string the id of the current entity being processed in the session (if any exists)*/
	var $current_entity;
	/** @var string the type of the current entity */
	var $current_entity_type;

	/**
	 * 
	 * @param string $session_id the id of the session (the same id as the service that owns it)
	 * @param boolean $autostart set to false if you do not want the session to start immediately
	 */
	function __construct($session_id, $cid = "all", $autostart = true){
		$this->id = $session_id;
		$this->collection_id = $cid;
		if($autostart){$this->start();}
	}
	
	/**
	 * Loads a session object from its saved json state
	 * @param string $json the json encoded string of the object
	 * @return boolean true if the session loaded ok
	 */
	function loadFromJSON($json){
		$jassoc = json_decode($json, true);
		$last = 0;
		if($jassoc){
			foreach($jassoc as $ts => $event){	
				$this->events[$ts] = $event;//array("action" => $event['action']);
				if($event['action'] == "start") $this->start = $ts;
				if($event['action'] == "end") $this->end = $ts;
				if($ts > $last) $last = $ts;
			}
				
			if(!$this->end){
				$this->end = $last;
			}
			return true;
		}
		else {
			return $this->failure_result("Failed to decode session from json $json", 500);
		}
	}
	
	/**
	 * Which collection is the session associated with
	 * @return string collection id
	 */
	function cid(){
		return $this->collection_id;
	}
	
/* Session control functions */
	
	/**
	 * Start the session
	 */
	function start(){
		$this->start = time();
		$this->state = 'active';
		$this->events[$this->start] = array("action" => 'start');		
	}
	
	/**
	 * End the session
	 */
	function end(){
		$this->state = 'end';
		$this->end = time();
	}
	
	/** 
	 * set session state to pause
	 */
	function pause(){
		$this->registerEvent(array("action" => "pause"));
		$this->state = 'pause';
	}
	
	/**
	 * set session state to active 
	 */
	function unpause(){
		$this->registerEvent(array("action" => "unpause"));
		$this->state = 'active';
	}	
	
	/**
	 * Return a structure containing a summary of the sessions duration 
	 * @return array with fields [duration, start, end, event_count]
	 */
	function summary(){
		$a = array();
		$a['duration'] = $this->activeDuration();
		$a['start'] = $this->start;
		$a['end'] = $this->end;
		$a['event_count'] = count($this->events) - 2;
		return $a;
	}

	/**
	 * Register an event with the session
	 * @param array $settings the specific settings for the event
	 */
	function registerEvent($settings){
		$t_index = time();
		while(isset($this->events[$t_index])) $t_index++;
			$this->events[$t_index] = $settings;
	}
	
	/**
	 * Has the session expired
	 * @return boolean returns true if the session has expired
	 */
	function expired(){
		return (time() - $this->getMostRecentEvent()) > $this->session_timeout; 
	}
	
	/**
	 * Returns the time stamp of the most recent event in the session
	 * @return number the most recent time stamp
	 */
	function getMostRecentEvent(){
		return max(array_keys($this->events));
	}
	
	/**
	 * Return a count of the number of events of a particular type that have been registered during the session
	 * @param string $evt the particular event to look for (the action field of the registered event)
	 * @return number the number of events with action = $evt found
	 */
	function eventCount($evt){
		$count = 0;
		foreach($this->events as $t => $ev){
			if($ev['action'] == $evt){
				$count++;
			}
		}
		return $count;
	}
	
	/**
	 * The number of seconds for which the session has been active
	 * 
	 * Ignores periods when the session was paused
	 * @return number session active duration seconds
	 */
	function activeDuration(){
		$end = $this->end ? $this->end : time();
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
			$paused_total += $end - $paused_time;
		}
		return $end - $this->start - $paused_total;
	}
	
	/**
	 * Assign an entity (e.g. candidate, schema, etc) to the session
	 * @param string $id the entity id
	 */
	function assignEntity($id){
		$this->registerEvent(array("action" => "assign", "id" => $id));
		$this->current_entity = $id;
	}
	
	/**
	 * Get the id of the entity that is currently assigned to the session
	 * @return string | boolean - the id of the entity
	 */
	function getAssignedEntity(){
		return (isset($this->current_entity) && $this->current_entity) ? $this->current_entity : false;		
	}
	
	/**
	 * Does the session currently have an entity to process?
	 * @return boolean true if there is a current_entity set
	 */
	function hasLiveSession(){
		return isset($this->current_entity) && $this->current_entity ? $this->current_entity : false;
	}
}	