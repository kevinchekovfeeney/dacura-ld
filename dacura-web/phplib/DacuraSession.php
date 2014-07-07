<?php
class DacuraSession {

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
	
/*	
	function createNewLocalSession($sess_chunk){
		$this->session_chunk = $sess_chunk;
		$this->current_candidate = null;
		$this->current_chunk = null;
		$t = time();
		$this->local_state = array(
				'start' => $t,
				'current_state' => 'active',
				'events' => array($t => array("action" => 'start_local'))
		);
	}

	function addRemoteInvocation($url){
		$this->registerEvent( array("action" => "invoke", "url" => $url), true);
		$this->remote_state['current_state'] = 'active';
	}

	function addRemoteReport($id){
		$this->registerEvent( array("action" => "submit", "id" => $id), true);
	}
	
	function addRemoteFetch($type, $id){
		$this->registerEvent(array("action" => "fetch", "id" => $id, 'type' => $type), true);
	}

	function pauseRemoteSession(){
		$this->registerEvent(array("action" => "pause"), true);
		$this->remote_state['current_state'] = 'pause';
	}

	function endRemoteSession(){
		$this->registerEvent(array("action" => "end"), true);
		$this->remote_state['current_state'] = 'end';
	}

	function abortRemoteSession(){
		$this->registerEvent(array("action" => "abort"), true);
		$this->remote_state['current_state'] = 'abort';
	}
	
	function getCurrentCandidate(){
		return $this->current_candidate;
	}
	
	function setSessionChunk($yr){
		$this->session_chunk = $yr;
	}
	
	
	function setCurrentChunk($yr){
		$this->current_chunk = $yr;
	}

	function getCurrentChunk(){
		return isset( $this->current_chunk ) ? $this->current_chunk : false;
	}

	function abortLocalSession(){
		$this->registerEvent(array("action" => "abort"), false);
		$this->local_state = null;
		$this->current_candidate = null;
		$this->current_chunk = null;
		$this->session_chunk = null;
	}

	function endLocalSession(){
		$this->registerEvent(array("action" => "end"), false);
		$this->current_candidate = null;
		$this->local_state = null;
		$this->current_chunk = null;
		$this->session_chunk = null;
	}

	function pauseLocalSession(){
		$this->registerEvent(array("action" => "pause"), false);
		$this->local_state['current_state'] = 'pause';
	}


	function remoteSessionPaused(){
		return isset($this->remote_state['current_state']) && $this->remote_state['current_state'] == 'pause';
	}

	function localSessionPaused(){
		return isset($this->local_state['current_state']) && $this->local_state['current_state'] == 'pause';
	}

	function unpauseRemoteSession(){
		$this->registerEvent(array("action" => "unpause"), true);
		$this->remote_state['current_state'] = 'active';
	}

	function unpauseLocalSession(){
		$this->registerEvent(array("action" => "unpause"), false);
		$this->local_state['current_state'] = 'active';
	}
	


	function addLocalDecision($id, $decision){
		$this->registerEvent( array("action" => $decision, "id" => $id), false);
		if($decision == "assign"){
			$this->current_candidate = $id;
		}
		elseif($decision == 'accept' or $decision == 'reject' or $decision == 'skip'){
			$this->current_candidate = null;
		}
		$this->local_state['current_state'] = 'active';
	}

	function getLocalSessionRecord(){
		return json_encode($this->local_state['events']);
	}

	function getRemoteSessionRecord(){
		return json_encode($this->remote_state['events']);
	}

	function getRemoteSubmissionCount(){
		$i = 0;
		foreach($this->remote_state['events'] as $t => $event){
			if($event['action'] == 'submit'){
				$i++;
			}
		}
		return $i;
	}

	function getRemoteSessionStartTime(){
		return $this->remote_state['start'] ? $this->remote_state['start'] : 0;
	}

	function getRemoteSessionDuration(){
		$total_span = time() - $this->remote_state['start'];
		$paused = false;
		foreach($this->remote_state['events'] as $t => $event){
			if(!$paused && $event['action'] == 'pause'){
				$paused = $t;
			}
			elseif($paused && $event['action'] != 'pause'){
				$total_span = $total_span - ($t - $paused);
				$paused = false;
			}
		}
		if($paused){
			$total_span = $total_span - (time() - $paused);
		}
		return $total_span;
	}

	function getRemoteSessionState(){
		return $this->remote_state['current_state'];
	}
	
	function getLocalSessionState(){
		return $this->local_state['current_state'];
	}
	
	function getLocalDecisionCounts(){
		$counts = array();
		if(isset($this->local_state['events']) && is_array($this->local_state['events'])){
			foreach($this->local_state['events'] as $t => $event){
				if(isset($event['id'])){
					if(!isset($counts[$event['action']])) $counts[$event['action']] = 1;
					else $counts[$event['action']]++;
				}
			}				
		}
		return $counts;
	}

	function getLocalSessionStartTime(){
		return $this->local_state['start'] ? $this->local_state['start'] : 0;
	}

	function getLocalSessionDuration(){
		$now = time();
		$paused_total = 0;
		$paused_time = 0;
		foreach($this->local_state['events'] as $t => $event){
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
		return $now - $this->local_state['start'] - $paused_total;
	}

	function staleLocalSession(){
		$last_activity_time = max(array_keys($this->local_state['events']));
		return ((time() - $last_activity_time) < $this->local_session_timeout);
	}

	function staleRemoteSession(){
		$last_activity_time = max(array_keys($this->remote_state['events']));
		return time() - $last_activity_time < $this->remote_session_timeout;
	}

	function setLocalToolID($id){
		$this->local_state['tool_id'] = $id;
	}
	
	function setRemoteToolID($id){
		$this->remote_state['tool_id'] = $id;
	}
	
	function getCurrentRemoteToolID(){
		return ($this->remote_state && isset($this->remote_state['tool_id'])) ? $this->remote_state['tool_id'] : false;
	}
	function getCurrentLocalToolID(){
		return ($this->local_state && isset($this->local_state['tool_id'])) ? $this->local_state['tool_id'] : false;
	}
	
	
*/	

