<?php
/*
 * Class which stores a log of what happens to a single request as it journeys through the system...
 * This class defines the structure of Dacura's access log.
 *
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */


class RequestLog {
	var $log_file;
	var $event_file;
	var $timer = false;//false (of) or an associative array of microtime() to event name
	var $result_code;
	var $result_message;
	var $user_name;
	var $collection_id;
	var $dataset_id;
	var $access_mode = "api";
	var $service;
	var $action;
	var $object;
	var $args;
	//var $sublogs = array();
	var $request_log_level = "debug";
	var $system_log_level = "debug";
	var $log_levels = array("debug", "info", "notice", "warning", "error", "critical", "alert", "emergency");

	function __construct($settings, $mode){
		$this->log_file = $settings['dacura_request_log'];
		$this->request_log_level = $settings['request_log_level'];
		$this->system_log_level = $settings['system_log_level'];
		$this->access_mode = $mode;
		$this->event_file = $settings['dacura_system_log'];
		$this->timer = array("start" =>  microtime(true));
	}
	
	function loadFromService($service){
		$this->collection_id = $service->collection_id;
		$this->dataset_id = $service->dataset_id;
		$this->args = $service->args;
		$this->service = $service->servicename;
		$this->access_mode = $service->connection_type;
		$this->setResult($service->errcode, $service->errmsg);
	}
	
	function setContext($cid, $did){
		$this->collection_id = $cid;
		$this->dataset_id = $did;
	}
	
	function setResult($c, $m){
		$this->result_code = $c;
		$this->result_message = $m;
	}
	
	function setServiceName($n){
		$this->service = $n;	
	}
	
	function setAction($a){
		$this->action = $a;
	}
	
	function setEvent($a, $o){
		$this->action = $a;
		$this->object = $o;
	}

	function setObject($o){
		$this->object = $o;
	}
	
	function setArgs($args){
		$this->args = $args;
	}
	
	function setUser($u){
		$this->user_name = $u;
	}
	
	function timeEvent($e, $level = "debug"){
		$my_level = array_search($this->request_log_level, $this->log_levels);
		$this_level = array_search($level, $this->log_levels);
		if($this_level >= $my_level){
			$this->timer[$e] = microtime(true);
		}
	}
	
	function logEvent($level, $code, $msg){
		$my_level = array_search($this->system_log_level, $this->log_levels);
		$this_level = array_search($level, $this->log_levels);
		if($this_level >= $my_level){
			$data = "$level\t$code\t$msg\n";
			file_put_contents($this->event_file, $data, FILE_APPEND | LOCK_EX);
		}
	}
	
	function asString(){
		$str = time()."\t[$this->access_mode]\t[$this->result_code]\t[$this->service|$this->action|$this->object]\t";
		$str .= "[$this->collection_id:$this->dataset_id]\t";
		$str .= "[$this->user_name]\t[";
		$str .= is_array($this->args) ? implode("|", $this->args) : "";
		$str .= "]\t[";
		if($this->timer){
			$timestrs = array();
			$prev = false;
			$first = false;
			$total = 0;
			foreach($this->timer as $e => $t){
				if(!$first){
					$first = $t;
					$prev = $t;
				}
				else {
					if(count($this->timer) == 2){
						$timestrs[] = ((float)$t - (float)$first);
					}
					else {
						$timestrs[] = "$e: ". ((float)$t - (float)$prev);
						$prev = $t;
					}	
				}
			}
			if(count($this->timer) > 2){
				array_unshift($timestrs, "total: ".((float)$prev - (float)$first));
			}
			$str .= implode(" | ", $timestrs);
		}
		$str .= "]\t$this->result_message\n";	
		return $str;	
	}
	
	function dumpToLog(){
		$data = $this->asString();
		file_put_contents($this->log_file, $data, FILE_APPEND | LOCK_EX);
	}
	
	
	function __destruct(){
		if($this->timer){
			$this->timer["end"] = microtime(true);
		}
		$this->dumpToLog();
	}	
}