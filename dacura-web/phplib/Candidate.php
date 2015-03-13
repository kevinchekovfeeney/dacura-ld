<?php

/*
 * Class representing a candidate in the Dacura DB
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

include_once("Provenance.php");
include_once("Annotation.php");
include_once("JSONLD.php");

class Candidate extends JSONLD {
	//maps to candidates db structure
	var $id;
	var $cid;
	var $did;
	var $type;
	var $version;
	var $type_version;
	var $status;
	var $report_id;
	var $annotation;
	var $contents;
	var $prov;
	var $created;
	var $modified;
	
	function __construct($id){
		$this->id = $id;
		$this->created = time();
		$this->modified = time();
	}
	
	function setContext($cid, $did){
		$this->cid = $cid;
		$this->did = $did;
	}
	
	function load($arr){
		$this->contents = $arr;
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
	
	function get_json(){
		return json_encode($this->contents);
	}
	
	function setAnnotation($an){
		$this->annotations = $an;
	}

	function setProvenance($s){
		$this->source = $s;
	}
	
	function reportString(){
		return "Not yet implemented";
	}
	
	function getAgentKey(){
		$ag = $this->prov->getAgent("dacura:dacuraAgent");
		if(!$ag)
		{
			return false;
		}
		return true;
	}
	
	function getFacet($fid){
		//return $this->findObjectWithKey($this->id."/".$fid, $this->contents);
		//go through object and see if there is a particular id there....
		$frag_id = $this->id."/".$fid;
		foreach($this->contents as $prop => $vals){
			$obj = $this->findObjectWithKey($frag_id, $vals);
			if($obj) return $obj;
		}
		$obj = $this->prov->getFragment($frag_id);
		if($obj) return $obj;
		$obj = $this->annotation->getFragment($frag_id);
		if($obj) return $obj;
		return false;
	}
	
	function candidateHasFragmentWithID($frag_id){
		foreach($this->contents as $prop => $vals){
			$obj = $this->findObjectWithKey($frag_id, $vals);
			if($obj) return true;
		}
		return false;
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
	/*
	 * PHP objects for our main players...
	 */
	function loadFromAPI($obj){
		$this->prov = new Provenance();
		$this->annotation = new Annotation();
		$this->prov->load($obj['provenance']);
		if(isset($obj['annotation'])){
			$this->annotation->load($obj['annotation']);
		}
		$this->load($obj['candidate']);
		$this->type = $obj['candidate']['rdf:type'];
		return true;
	}
	
	function loadFromJSON($cand, $source = false, $note = false){
		if($source){
			$this->prov = new Provenance();
			if(!$this->prov->load_json($source)){
				return $this->failure_result("Failed to load provenance record of $cand->id from db: ".$this->prov->errmsg, $this->prov->errcode);
			}
		}
		if($note){
			$this->annotation = new Annotation();
			if(!$this->annotation->load_json($note)){
				return false;
			}
		}
		return ($this->contents = json_decode($cand, true));
	}
	
	
	
	function get_json_ld(){
		$ld = array("id" => $this->id);
		$ld['candidate'] = $this->contents;
		$ld['provenance'] = $this->prov->get_json_ld();
		$ld['annotation'] = $this->annotation->get_json_ld();
		return $ld;
	}
}


class CandidateSchema extends Candidate {
	function __construct(){}
}


