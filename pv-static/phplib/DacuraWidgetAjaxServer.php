<?php
require_once("EventRecord.php");
require_once("Widgetizer.php");
require_once("SessionManager.php");

class DacuraWidgetAjaxServer{
	var $source;
	var $id_prefix;
	var $schema_graph;
	var $data_graph;
	var $base_class;
	var $candidate_store;
	var $dacura_sessions;
	var $sm;
	
	function __construct($settings = array()){
		$this->source = isset($settings['sparql_source']) ? $settings['sparql_source'] : "http://tcdfame.cs.tcd.ie:3030/politicalviolence/query";
		$this->id_prefix = isset($settings['id_prefix']) ? $settings['id_prefix'] : "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv/";
		$this->schema_graph = isset($settings['schema_graph']) ? $settings['schema_graph'] : "http://tcdfame.cs.tcd.ie/data/politicalviolence";
		$this->data_graph = isset($settings['data_graph']) ? $settings['data_graph'] : "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv";
		$this->base_class = isset($settings['base_class']) ? $settings['base_class'] : "http://tcdfame.cs.tcd.ie/data/politicalviolence#Report";
		$this->candidate_store = isset($settings['candidate_store']) ? $settings['candidate_store'] : "/var/ukipvdata/"; 
		$this->dacura_sessions = isset($settings['dacura_sessions']) ? $settings['dacura_sessions'] : "/var/dacura/"; 
		$this->sm = new SessionManager($this->dacura_sessions."users.dac");
	}

	function getEventRecord($id) {
		$fullid = $this->id_prefix.$id;
		$rec = new EventRecord($fullid);
		$rec->setDataSource($this->source, $this->schema_graph, $this->data_graph);
		if($rec->loadFromDB(true)){
			$data = $rec->getAsArray();
			echo json_encode($data);
		}
		else {
			$this->write_error("Failed to load record $id. ".$rec->getErrorString());
		}
	}

	function getWidgetStructure($options){
		$opts = json_decode($options);
		$wzer = new Widgetizer($this->schema_graph, $this->source);
		$wzer->setWidgetDetails($opts);
		$widget_html = $wzer->getClassWidget($this->base_class);
		echo $widget_html;
	}

	function fileCandidate() {
		$cand_descr = isset($_POST['candidate']) ? json_decode($_POST['candidate'], true) : false;
		if($cand_descr){
			$yr = isset($cand_descr['year']) ? $cand_descr['year'] : false;
			$id = isset($cand_descr['id']) ? $cand_descr['id'] : false;
			if($yr && $id){
				if(!file_exists($this->candidate_store.$yr)){
					if(!mkdir($this->candidate_store.$yr)){
						$this->write_error("Failed to create candidate directory for $yr", 500);
						return false;
					}
				}
				if(!file_exists($this->candidate_store.$yr."/".$id)){
					$cand_descr['processed'] = false;
					if(file_put_contents($this->candidate_store.$yr."/".$id, serialize($cand_descr))){
						return true;
					}
				}
				else {
					$this->write_error("$yr $id already captured", 202);
				}
			}
			else {
				$this->write_error("Missing required fields: year, id", 400);
				return false;
			}
		}
		else {
			$this->write_error("Missing candidate data field", 400);
			return false;
		}
	}
	
	function login($u, $p){
		$u = $this->sm->login($u, $p);
		if($u){
			return json_encode($u);
		}
		else {
			$this->write_error("Failed to login", 401);
		}
	}
	
	function adduser($u, $p){
		$u = $this->sm->adduser($u, $p);
		if($u){
			return json_encode($u);
		}
		else {
			$this->write_error("Failed to create user ".$this->sm->errormsg, 401);
		}
	}
	
	function allocate($u, $y){
		
	}

	function write_error($str, $code = 400){
		http_response_code($code);
		echo json_encode($str);
	}
}

if (!function_exists('http_response_code')) {
	function http_response_code($code = NULL) {

		if ($code !== NULL) {

			switch ($code) {
				case 100: $text = 'Continue'; break;
				case 101: $text = 'Switching Protocols'; break;
				case 200: $text = 'OK'; break;
				case 201: $text = 'Created'; break;
				case 202: $text = 'Accepted'; break;
				case 203: $text = 'Non-Authoritative Information'; break;
				case 204: $text = 'No Content'; break;
				case 205: $text = 'Reset Content'; break;
				case 206: $text = 'Partial Content'; break;
				case 300: $text = 'Multiple Choices'; break;
				case 301: $text = 'Moved Permanently'; break;
				case 302: $text = 'Moved Temporarily'; break;
				case 303: $text = 'See Other'; break;
				case 304: $text = 'Not Modified'; break;
				case 305: $text = 'Use Proxy'; break;
				case 400: $text = 'Bad Request'; break;
				case 401: $text = 'Unauthorized'; break;
				case 402: $text = 'Payment Required'; break;
				case 403: $text = 'Forbidden'; break;
				case 404: $text = 'Not Found'; break;
				case 405: $text = 'Method Not Allowed'; break;
				case 406: $text = 'Not Acceptable'; break;
				case 407: $text = 'Proxy Authentication Required'; break;
				case 408: $text = 'Request Time-out'; break;
				case 409: $text = 'Conflict'; break;
				case 410: $text = 'Gone'; break;
				case 411: $text = 'Length Required'; break;
				case 412: $text = 'Precondition Failed'; break;
				case 413: $text = 'Request Entity Too Large'; break;
				case 414: $text = 'Request-URI Too Large'; break;
				case 415: $text = 'Unsupported Media Type'; break;
				case 500: $text = 'Internal Server Error'; break;
				case 501: $text = 'Not Implemented'; break;
				case 502: $text = 'Bad Gateway'; break;
				case 503: $text = 'Service Unavailable'; break;
				case 504: $text = 'Gateway Time-out'; break;
				case 505: $text = 'HTTP Version not supported'; break;
				default:
					exit('Unknown http status code "' . htmlentities($code) . '"');
					break;
			}

			$protocol = (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0');

			header($protocol . ' ' . $code . ' ' . $text);

			$GLOBALS['http_response_code'] = $code;

		} else {

			$code = (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);

		}

		return $code;

	}
}

