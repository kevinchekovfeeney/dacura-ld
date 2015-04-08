<?php
require_once("LDDocument.php");

class Candidate extends LDDocument {
	//maps to candidates db structure
	var $cid;
	var $did;
	var $type;
	var $version;
	var $type_version;
	var $status;
	var $report_id;
	var $created;
	var $modified;
	
	function __construct($id, $urlbase){
		parent::__construct($id, $urlbase);
		$this->created = time();
		$this->modified = time();
	}
	
	function setContext($cid, $did){
		$this->cid = $cid;
		$this->did = $did;
	}
	
	function version(){
		return $this->version;
	}
	
	function get_class(){
		return $this->type;
	}
		
	function get_class_version(){
		return $this->type_version;
	}
	
	function get_status(){
		return $this->status;
	}
	
	function get_report(){
		return $this->report_id;
	}
	
	function setAnnotation($an){
		$this->contents['annotation'] = $an;
	}

	function setProvenance($s){
		$this->contents['provenance'] = $s;
	}
	
	function reportString(){
		return "Not yet implemented";
	}
	
	function getAgentKey(){
		return true;
		//$ag = $this->prov->getAgent("dacura:dacuraAgent");
		//if(!$ag)
		//{
		//	return false;
		//}
		//return true;
	}
	
	function set_version($v){
		$this->version = $v;
	}
	
	function set_class($c, $v){
		$this->type = $c;
		$this->type_version = $v;
	}
	
	function set_report($r){
		$this->report = $r;
	}

	
	/**
	 * Called when the object is loaded from the database
	 * @param unknown $cand
	 * @param string $source
	 * @param string $note
	 * @return mixed
	 */
	function loadFromJSON($cand, $source = false, $note = false){
		if($source){
			$this->contents['provenance'] = json_decode($source, true);
		}
		if($note){
			$this->contents['annotation'] = json_decode($note, true);
		}
		$this->contents['candidate'] = json_decode($cand, true);
		$this->buildIndex();
		return ($this->contents['provenance'] && $this->contents['candidate']);
	}
}


class CandidateSchema extends Candidate {
	function __construct(){}
}


