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

include_once("CandidateAction.php");

class thingWithSchema extends DacuraObject {
	var $json_ld_mapping; //JSON-LD mapping
	var $json_schema; //Json schema
	
	function loadJSONSchema($file){
		$this->json_schema = json_decode(file_get_contents($file));
	}
	
	function checkJSONSchema($js){
		$schema_errors = Jsv4::validate($js, $this->json_schema);
		foreach($schema_errors as $se){
			$se = $se[0];
			opr($se);
		}
	}
	
}

class Candidate extends thingWithSchema {
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
		$ag = $this->prov->getAgent("dacuraAgent");
		if(!$ag)
		{
			return false;
		}
		return true;
		//return ($ag = $this->prov->getAgent("dacuraAgent") && isset($ag['key'])) ? $ag['key'] : false;
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
	function loadFromAPI($source, $cand, $note){
		$this->prov = new Provenance();
		$this->annotation = new Annotation();
		$this->prov->load($source);
		if($note) $this->annotation->load($note);
		$this->load($cand);
		$this->type = $cand['class'];
		unset($this->contents['class']);
		return true;
	}
	
	function loadFromJSON($cand, $source = false, $note = false){
		if($source){
			$this->prov = new Provenance();
			if(!$this->prov->load_json($source)){
				//return false;
			}
		}
		if($note){
			$this->annotation = new Annotation();
			if(!$this->annotation->load_json($note)){
				//return false;
			}
		}
		return ($this->contents = json_decode($cand));
	}
	
	function hasExternalReferences($follow_internals = false){
		if(!$this->provenanceRefersToCandidate()){
			//rejected
			return $this->failure_result("Provenance statements must only refer to candidates being created ", 400);
		}
		if(!$this->annotationsReferToCandidate()){
		//rejected
			return $this->failure_result("Provenance statements must only refer to candidates being created ", 400);
		}
		return false;
	}
	
	function annotationsReferToCandidate($follow_internals){
		return true;
	}

	function provenanceRefersToCandidate($follow_internals){
		return true;
	}
}

class CandidateUpdateRequest extends Candidate {
	var $original; //the current state of the target candidate 
	var $delta;	//the changed state of the target candidate (if the update request is accepted)
	var $update; //the state update 
	
	function setOriginal($cur){
		$this->original = $cur;
	}
	
	function generateDelta(){
		$this->delta = $original;
		//need to take my graph
		//merge it into original's graph
		//generate transform from original to new
	}
	
	function get_delta(){
		return $this->delta;
	}
	
	function get_update(){
		return $this->update;
	}
	
	//function getUpdateAsDBIndexArray(
	
}

class CandidateCreateRequest extends Candidate {
	function __construct(){
		$this->version = 1;
	}
}

class CandidateSchema extends Candidate {
	function __construct(){}
}

/*
{
	"entity": { // Map of entities by entities' IDs
},
"activity": { // Map of activities by IDs
},
"agent": { // Map of agents by IDs
},
<relationName>: { // A map of relations of type relationName by their IDs
},
...
"bundle": { // Map of named bundles by IDs
}
}*/
class Provenance extends thingWithSchema {
	var $entities = array();
	var $activities = array();
	var $agents = array();
	var $relations = array();
	var $bundles = array();
	//var $relation
	function checkProvenanceSchema($json){
		$this->loadJSONSchema("phplib/schema/prov.json.schema");
		$this->checkJSONSchema($json);
	}
	
	function load($arr){
		//$this->checkProvenanceSchema($arr);
		if(isset($arr['entity'])){
			$this->entities = $arr['entity'];
		}
		if(isset($arr['activity'])){
			$this->activities = $arr['activity'];
		}
		if(isset($arr['agent'])){
			$this->agents = $arr['agent'];
		}
		if(isset($arr['bundle'])){
			$this->bundles = $arr['bundle'];
		}
		foreach($arr as $k => $v){
			if(!in_array($k, array("entity", "activity", "agent", "bundle"))){
				$this->relations[$k] = $v;
			}
		}		
	}
	
	function load_json($json){
		$json = json_decode($json, true);
		if(!$json or !is_array($json)){
			return false;
		}
		$this->load($json);
		return true;
	}
	
	function get_json(){
		$prov_obj = array();
		if(isset($this->entities) && $this->entities){
			$prov_obj["entity"] = $this->entities;
		}
		if(isset($this->activitives) && $this->activitives){
			$prov_obj["activity"] = $this->activitives;
		}
		if(isset($this->agents) && $this->agents){
			$prov_obj["agent"] = $this->agents;
		}
		if(isset($this->bundles) && $this->bundles){
			$prov_obj["bundle"] = $this->bundles;
		}
		foreach($this->relations as $k => $v){
			$prov_obj[$k] = $v;
		}
		return json_encode($prov_obj);
	}
	
	function getAgent($id){
		return isset($this->agents[$id]) ? $this->agents[$id] : false;
	}
	
}

class Annotation extends thingWithSchema {
	var $contents;
	
	function load($arr){
		$this->contents = $arr;
	}
	
	function load_json($json){
		return ($this->contents = json_decode($json, true));
	}
	
	function get_json(){
		if(isset($this->contents) && $this->contents){
			return json_encode($this->contents);
		}
		return "{}";
	}
	
}
