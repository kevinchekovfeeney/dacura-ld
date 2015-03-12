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
include_once("Provenance.php");

class thingWithSchema extends DacuraObject {
	var $json_ld_mapping; //JSON-LD mapping
	var $json_schema; //Json schema
	
	function loadJSONSchema($file){
		$this->json_schema = json_decode(file_get_contents($file));
	}
	
	function genid($prefix = "") {
		return uniqid($prefix);
	}
	
	function checkJSONSchema($js){
		$schema_errors = Jsv4::validate($js, $this->json_schema);
		foreach($schema_errors as $se){
			$se = $se[0];
			opr($se);
		}
	}
	
	function findObjectWithKey($k, $arr){
		if(!is_array($arr)) return false;
		foreach($arr as $id => $obj){
			if(is_array($obj)){
				if($id == $k){
					return $obj;
				}
				else {
					return $this->findObjectWithKey($k, $obj);
				}
			}
		}
		return false;
	}
	
	function applyIDMap($map){}
	
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
		$ag = $this->prov->getAgent("dacura:dacuraAgent");
		if(!$ag)
		{
			return false;
		}
		return true;
		//return ($ag = $this->prov->getAgent("dacuraAgent") && isset($ag['key'])) ? $ag['key'] : false;
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
	
	function get_json_ld(){
		$ld = array("id" => $this->id);
		$ld['candidate'] = $this->contents;
		$ld['provenance'] = $this->prov->get_json_ld();
		$ld['annotation'] = $this->annotation->get_json_ld();
		return $ld;
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
		return ($this->contents = json_decode($cand, true));
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
	var $changes; // array describing changes from old to new
	var $rollback; // array describing changes from old to new
	var $id_map; // array of blank node ids mapped into urls...

	function generateDelta(){
		if($this->applyUpdates($this->contents, $this->delta->contents)){
			//should also do the same with provenance and annotations
			return true;
		}
		else return false;
		//need to take my graph
		//merge it into original's graph
		//generate transform from original to new
	}
	
	function applyUpdates($props, &$dprops){
		foreach($props as $prop => $v){
			if(!is_array($v) or count($v) == 0){ // property => value
				if($v){
					$dprops[$prop] = $v;
				}
				else {
					if(isset($dprops[$prop])){
						unset($dprops[$prop]);
					}
					else {
						return $this->failure_result("Attempted to remove non-existant property $prop", 404);
					}
				}
			}
			else { // property => {id => embedded_object, ...} value is array 
				foreach($v as $id => $obj){
					if(!$obj){ // delete fragment
						if(isset($dprops[$prop][$id])){
							unset($dprops[$prop][$id]);
						}
						else {
							return $this->failure_result("Attempted to remove non-existant property value $prop $id", 404);
						}
					}
					elseif(!is_array($obj)){
						return $this->failure_result("Update object format error - attempting to replace embedded object with non json-object", 404);						
					}
					else { // correct - array of things
						if(isBlankNode($id)){
							//$plan['create'][$id] = array($p, $obj);//property, object
							$new_id = $this->genid($this->delta->id."/");
							$this->id_map[$id] = $new_id;
							$dprops[$prop][$new_id] = $obj;
							$id = $new_id;
						}
						elseif(!isset($dprops[$prop][$id])){
							return $this->failure_result("Attempting to update $prop with non existant id $id", 404);
						}
						else {
							if(!$this->applyUpdates($props[$prop][$id], $dprops[$prop][$id])){
								return false;
							}
						}
					}
				}
			
			}
		}
		return true;
	}
	//here is where we go through the update request and turn it into a list of updates
	function expand(){
		if($this->generateDelta()){
			$this->changes = array("add" => array(), "del" => array(), "update" => array());
			$this->candidateCompare($this->original->contents, $this->delta->contents, $this->original->id, $this->changes);//now generate changeset..
			$this->rollback = array("add" => array(), "del" => array(), "update" => array());
			$this->candidateCompare($this->delta->contents, $this->original->contents, $this->original->id, $this->rollback);//now generate changeset..
			//need to also do the annotations and provenance ... Later!
			return true;
		}
		return $this->failure_result($this->errmsg, $this->errcode);		
	}
	
	//res -> add: (context, fragment)
	//res -> delete: (context, $property, fragment)
	//res -> update: (context, $property, fragment1, fragment2)
	function candidateCompare($ocand, $dcand, $context, &$res){
		foreach($ocand as $p => $v){
			if(!isset($dcand[$p]) || !$dcand[$p] || (is_array($dcand[$p]) && count($dcand[$p]) == 0)){
				$res['del'][] = array($context, $p, $v);
				continue;
			}
			$nv = $dcand[$p];
			if(!is_array($v)){
				if($nv != $v){
					$res['update'][] = array($context, $p, $nv, $v);
				}
			}
			else {
				//update to a compound object...
				$this->candidateCompare($ocand[$p], $dcand[$p], "$context.$p", $res);
			}
		}
		//now go through the delta cand to find new nodes...
		foreach($dcand as $p => $v){
			if(!isset($ocand[$p]) || !$ocand[$p]){
				$res['add'][] = array($context, $p, $v);
				continue;
			}	
		}
		return true;
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
	
	public static $ix = 0;
	
	
	function __construct(){
		$this->version = 1;
	}
	
	function expandarray($arr, &$map, $prefix = ""){
		if(!is_array($arr)){
			return $arr;
		}
		$newarr = array();
		foreach($arr as $key => $val){
			if(is_array($val)){
				$newid = $this->genid($prefix);
				if(isset($val['@id'])){
					$map[$val['@id']] = $newid;
					unset($val['@id']);
				}
				$newarr[$newid] = $this->expandarray($val, $map, $prefix);
			}
			else {
				$newarr[$key] = $val;
			}
		}	
		return $newarr;
	}
	
	function expand(){
		$this->id = $this->genid();
		$id_map = array("_:candidate" => $this->id);
		//add ids to everything, ensure that everything inter-relates
		//get_content id, then, get
		foreach($this->contents as $k => $v){
			if(!is_array($v)){
				continue;
			}
			$this->contents[$k] = $this->expandarray($v, $id_map, $this->id."/");
		}
		//now we deal with provenance and annotation references....
		$this->annotation->expand($id_map, $this->id."/");
		$this->prov->expand($id_map, $this->id."/");
		$this->annotation->applyIDMap($id_map);
		$this->prov->applyIDMap($id_map);
		return true;
	}
}

class CandidateSchema extends Candidate {
	function __construct(){}
}


class Annotation extends thingWithSchema {
	var $contents;
	
	function expandinternal($arr, &$map, $prefix, $first=false){
		if(!is_array($arr)){
			return $arr;
		}
		if(!$first){
			$newid = $this->genid($prefix);
			$narr = array($newid => array());
			foreach($arr as $n => $v){
				$narr[$newid][$n] = $this->expandinternal($v,$map, $prefix);
			}
			return $narr;
		}
		else {
			foreach($arr as $n => $v){
				$arr[$n] = $this->expandinternal($v, $map, $prefix);
			}
			return $arr;
		}
	}
	
	function apply_map_to_array($arr, $map){
		$narr = array();
		foreach($arr as $k => $v){
			if(is_array($v)){
				$narr[$k] = $this->apply_map_to_array($v, $map);
			}
			else {
				if(isset($map[$v])){
					$narr[$k] = $map[$v];
				}
				else {
					$narr[$k] = $v;
				}
			}
		}
		return $narr;
	}
	
	function applyIDMap($map){
		foreach($this->contents as $ck => $cu){
			$this->contents[$ck] = $this->apply_map_to_array($cu, $map);
		}
	}
	function expand(&$map, $prefix = ""){
		foreach($this->contents as $ck => $cu){
			if(isBlankNode($ck)){
				unset($this->contents[$ck]);
				$x = $this->genid($prefix);
				$map[$ck] = $x;
				$ck = $x;
			}
			if($ck){
				$this->contents[$ck] = $this->expandinternal($cu, $map, $prefix, true);
			}
		}
	}
	
	function getFragment($frag_id){
		foreach($this->contents as $id => $obj){
			if($frag_id == $id){
				return $obj;
			}
			else {
				$nobj = $this->findObjectWithKey($frag_id, $obj);
				if($nobj) return $nobj;
			}
		}
		return false;
	}
	
	function load($arr){
		$this->contents = $arr;
	}
	
	function load_json($json){
		return ($this->contents = json_decode($json, true));
	}
	
	function get_json_ld(){
		return $this->contents;
	}
	
	function get_json(){
		if(isset($this->contents) && $this->contents){
			return json_encode($this->contents);
		}
		return "{}";
	}
	
}
