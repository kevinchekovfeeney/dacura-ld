<?php
require_once("LDDocument.php");

/*
 * A candidate is basically an ld document with a bunch of dacura state management information tagged on
 */

class Candidate extends LDDocument {
	//maps to candidates db structure
	var $cid;
	var $did;
	var $type;
	var $version;
	var $latest_version;
	var $type_version;
	var $status;
	var $report_id;
	var $created;
	var $modified;
	var $schema;
	
	function __construct($id){
		parent::__construct($id);
		$this->created = time();
		$this->modified = time();
	}
	
	function setContext($cid, $did){
		$this->cid = $cid;
		$this->did = $did;
	}
	
	function loadSchema($base_url){
		$this->schema = new Schema($this->cid, $this->did, $base_url);
	}
	
	function expandNS(){
		$this->expandNamespaces($this->contents);
	}

	function compressNS(){
		$this->applyNamespaces($this->contents);
	}
	
	
	function expandNamespaces(&$props){
		foreach($props as $p => $v){
			$pv = new LDPropertyValue($v);
			if($pv->link() && isNamespacedURL($v) && ($expanded = $this->schema->expand($v))){
				$nv = $expanded;
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isNamespacedURL($val) && ($expanded = $this->schema->expand($val))){
						$nv[] = $expanded;
					}
					else {
						$nv[] = $val;
					}
				}
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					if(isNamespacedURL($id) && ($expanded = $this->schema->expand($id))){
						$nv[$expanded] = $obj;
						$this->expandNamespaces($nv[$expanded]);
					}
					else {
						$this->expandNamespaces($nv[$id]);
					}
				}
			}
			else {
				$nv = $v;
			}
			if(isNamespacedURL($p) && ($expanded = $this->schema->expand($p))){
				unset($props[$p]);
				//echo "expanding $p $"
				$props[$expanded] = $nv;
			}
			else {
				$props[$p] = $nv;
			}
		}
	}
	
	function applyNamespaces(&$props){
		foreach($props as $p => $v){
			//first compress property values
			$pv = new LDPropertyValue($v);
			if($pv->link() && ($compressed = $this->schema->compress($v))){
				$nv = $compressed;
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isURL($val) && ($compressed = $this->schema->compress($val))){
						$nv[] = $compressed;
					}
					else {
						$nv[] = $val;
					}
				}
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					if(isURL($id) && ($compressed = $this->schema->compress($p))){
						$nv[$compressed] =& $obj;
					}
					else {
						$nv[$id] =& $obj;
					}
					$this->applyNamespaces($obj);
				}
			}
			else {
				$nv = $v;
			}
			//then compress properties
			if(isURL($p) && ($compressed = $this->schema->compress($p))){
				unset($props[$p]);
				$props[$compressed] = $nv;
			}
			else {
				$props[$p] = $nv;
			}
		}
	}
	
	function setSchema($schema){
		$this->schema = $schema;
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
	
	function set_version($v, $is_latest = false){
		$this->version = $v;
		if($is_latest){
			$this->latest_version = $v;
		}
	}
	
	function set_class($c, $v){
		$this->type = $c;
		$this->type_version = $v;
	}
	
	function set_report($r){
		$this->report = $r;
	}

	function asTriples($expand_ns = false){
		if($expand_ns){
			$this->expandNS();
		}
		return $this->triples();
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

