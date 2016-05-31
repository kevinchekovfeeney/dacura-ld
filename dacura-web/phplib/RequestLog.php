<?php
/**
 * Class which stores a log of what happens to a single request as it journeys through the system...
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */
class RequestLog {
	/** @var string path to the request log file */
	var $log_file;
	/** @var string path the the event log */
	var $event_file;
	/** @var array false (off) or an associative array of microtime() to event name */
	var $timer = false;
	/** @var number http response code for request. 200 = ok */
	var $result_code;
	/** @var string a message that will be written to log */
	var $result_message;
	/** @var string the currently logged in user */
	var $user_name;
	/** @var string the id of the current collection */
	var $collection_id;
	/** @var string [api|html] */
	var $access_mode = "api";
	/** @var string the service name */
	var $service;
	/** @var string the action that was carred out */
	var $action;
	/** @var string the object of the action */
	var $object;
	/** @var array the arguments passed to the service */
	var $args;
	/** @var string one of $log_levels - logs have to have a level >= this to be logged */
	var $request_log_level = "debug";
	/** @var string one of $log_levels - logs have to have a level >= this to be logged */
	var $system_log_level = "debug";
	/** @var string[] list of legal log levels */
	static $log_levels = array("debug", "info", "notice", "warning", "error", "critical", "alert", "emergency");
	/** @var string[] list of fields that are included in a particular log message */
	static $log_fields = array("time", "access", "result", "service", "context", "user", "arguments", "timings", "message");

	/**
	 * Object constructor
	 * @param array $settings name-value server configuration settings 
	 * @param string $mode html | api
	 */
	function __construct($settings, $mode){
		$this->log_file = $settings['dacura_request_log'];
		$this->request_log_level = $settings['request_log_level'];
		$this->system_log_level = $settings['system_log_level'];
		$this->access_mode = $mode;
		$this->event_file = $settings['dacura_system_log'];
		$this->timer = array("start" =>  microtime(true));
	}
	
	/**
	 * Loads the request details from the service context 
	 * @param DacuraService $service
	 */
	function loadFromService(DacuraService $service){
		$this->collection_id = $service->collection_id;
		$this->args = $service->args;
		$this->service = $service->servicename;
		$this->access_mode = $service->connection_type;
		$this->setResult($service->errcode, $service->errmsg);
	}
	
	/**
	 * Sets the collection context...
	 * @param string $cid collection id
	 */
	function setContext($cid){
		$this->collection_id = $cid;
	}
	
	/**
	 * Sets result code and message
	 * @param number $c http result code
	 * @param string $m result message
	 */
	function setResult($c, $m){
		$this->result_code = $c;
		$this->result_message = $m;
	}
	
	/**
	 * set the service name of the request
	 * @param string $n service name
	 */
	function setServiceName($n){
		$this->service = $n;	
	}
	
	/**
	 * Defines the request action
	 * @param string $a action 
	 */
	function setAction($a){
		$this->action = $a;
	}
	
	/**
	 * Set the requests action and object fields
	 * @param string $a action
	 * @param string $o object
	 */
	function setEvent($a, $o){
		$this->action = $a;
		$this->object = $o;
	}
	/**
	 * Set request object
	 * @param string $o object
	 */
	function setObject($o){
		$this->object = $o;
	}
	/**
	 * Set request arguments 
	 * @param array $args
	 */
	function setArgs($args){
		$this->args = $args;
	}
	
	/**
	 * Set the username of the request
	 * @param string $u user name
	 */
	function setUser($u){
		$this->user_name = $u;
	}
	
	/**
	 * Add timing information to a log for a particular event
	 * @param string $e event name
	 * @param string $level log level for this event
	 */
	function timeEvent($e, $level = "debug"){
		$my_level = array_search($this->request_log_level, RequestLog::$log_levels);
		$this_level = array_search($level, RequestLog::$log_levels);
		if($this_level >= $my_level){
			$this->timer[$e] = microtime(true);
		}
	}
	/**
	 * Create log for a particular request
	 * @param string $level log level 
	 * @param number $code http response code
	 * @param string $msg user message
	 */
	function logEvent($level, $code, $msg){
		$my_level = array_search($this->system_log_level, RequestLog::$log_levels);
		$this_level = array_search($level, RequestLog::$log_levels);
		if($this_level >= $my_level){
			$data = "$level\t$code\t$msg\n";
			file_put_contents($this->event_file, $data, FILE_APPEND | LOCK_EX);
		}
	}
	
	/**
	 * Writes the log to the end of the request log file
	 */
	function dumpToLog(){
		$data = $this->asString();
		file_put_contents($this->log_file, $data, FILE_APPEND | LOCK_EX);
	}
	
	/**
	 * Generates a string / text version of the log - for writing to file
	 * @return string the tab-delimited encoding of the log
	 */	
	function asString(){
		$str = time()."\t[$this->access_mode]\t[$this->result_code]\t[$this->service|$this->action|$this->object]\t";
		$str .= "[$this->collection_id]\t";
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
	
	/**
	 * Generate a dacura listing table view of the log
	 * @param string $id the html id of the table
	 * @return string table html
	 */
	static function getAsListingTable($id){
		$html = "<table id='$id' class='dacura-api-listing'>\n<thead>\n<tr>";
		foreach(RequestLog::$log_fields as $th){
			$html .= "\n<th id='log-".$th."' title='$th'>$th</th>";
		}
		$html .= "\n</tr>\n</thead>\n<tbody>\n</tbody>\n</table>";
		return $html;
	}
	
	/**
	 * Takes the last num rows of the log file and turns them into an array of listing rows
	 * @param number $num the number of lines to snag
	 * @return array<array> an array of listing associative arrays
	 */
	function lastRowsAsListingObjects($num = 50){
		$chunk = tailCustom($this->log_file, $num);
		$lines = explode("\n", $chunk);
		$objs = array();
		foreach($lines as $line){
			$objs[] = $this->rowToListingObject($line);
		}
		return $objs;
	}
	
	/**
	 * Turn a row of the request log into a Table listing object
	 * @param string $row a single row of the request log file
	 * @return array<fieldid:value> 
	 */
	function rowToListingObject($row){
		$lobj = array();
		$parts = array_map('xstrim', explode("\t", $row));
		foreach(RequestLog::$log_fields as $i => $f){
			$lobj[$f] = isset($parts[$i]) ? $parts[$i] : "";
		}
		return $lobj;
	}
	
	/**
	 * Add end timer and dump log to file before end
	 */
	function __destruct(){
		if($this->timer){
			$this->timer["end"] = microtime(true);
		}
		$this->dumpToLog();
	}	
}