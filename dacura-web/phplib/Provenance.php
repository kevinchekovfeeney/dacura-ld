<?php
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
 },
 
 "prefix"
 }*/
class Provenance extends thingWithSchema {
	var $entities = array();
	var $activities = array();
	var $agents = array();
	var $relations = array();
	var $bundles = array();
	var $prefix = array();
	var $cruft = array();
	/*
	 * Prov built-in relations
	 */
	var $prov_relations = array(
			"wasGeneratedBy" => array("id", "entity", "activity", "time", "attributes" => array("prov:role")), 
			"used" => array("id", "entity", "activity", "time", "attributes"), 
			"wasInformedBy" => array("id", "informed", "informant", "attributes"), 
			"wasDerivedFrom"=> array(), 
			"wasAttributedTo"=> array(), 
			"wasAssociatedWith"=> array(), 
			"actedOnBehalfOf"=> array(),
			"wasStartedBy"=> array(),
			"wasEndedBy"=> array()
	);
	
	
	/*
	 * Prov built-in activites
	 */
	
	function checkProvenanceSchema($json){
		$this->loadJSONSchema("phplib/schema/prov.json.schema");
		$this->checkJSONSchema($json);
	}

	function load($arr){
		//$this->checkProvenanceSchema($arr);
		if(isset($arr['prefix'])){
			$this->prefix = $arr['prefix'];
		}
		if(isset($arr['entity'])){
			foreach($arr['entity'] as $id => $vals){
				$this->entities[$id] = new ProvEntity($id, $vals);
			}
		}
		if(isset($arr['activity'])){
			foreach($arr['activity'] as $id => $vals){
				$this->activities[$id] = new ProvActivity($id, $vals);
			}
		}
		if(isset($arr['agent'])){
			foreach($arr['agent'] as $id => $vals){
				$this->agents[$id] = new ProvAgent($id, $vals);
			}
		}
		if(isset($arr['bundle'])){
			$this->bundles = $arr['bundle'];
		}
		foreach($arr as $k => $v){
			if(!in_array($k, array("entity", "activity", "agent", "bundle"))){
				if(isset($this->prov_relations[$k])){
					$this->relations[] = new ProvRelation($k, $v);
				}
				else{
					$this->cruft[$k] = $v;
				}
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
		return json_encode($this->get_json_ld());
	}
	
	function get_json_ld(){
		$prov_obj = array();
		if(isset($this->prefix) && $this->prefix){
			$prov_obj["prefix"] = $this->prefix;
		}
		if(isset($this->entities) && $this->entities){
			$prov_obj["entity"] = array();
			foreach($this->entities as $k => $v){
				$prov_obj["entity"][$k] = $v->get_json_ld();
			}
		}
		if(isset($this->activities) && $this->activities){
			$prov_obj["activity"] = array();
			foreach($this->activities as $k => $v){
				$prov_obj["activity"][$k] = $v->get_json_ld();
			}
		}
		if(isset($this->agents) && $this->agents){
			$prov_obj["agent"] = array();
			foreach($this->agents as $k => $v){
				$prov_obj["agent"][$k] = $v->get_json_ld();
			}
		}
		if(isset($this->bundles) && $this->bundles){
			$prov_obj["bundle"] = $this->bundles;
		}
		foreach($this->relations as $r){
			$prov_obj[$r->relationType()] = $r->get_json_ld();
		}
		return $prov_obj;
	}

	function getAgent($id){
		return isset($this->agents[$id]) ? $this->agents[$id] : false;
	}
	
	function recordAgentActivityUsingEntity($agent_id, $activity_id, $entity_id = false){
		$this->relations["wasAssociatedWith"][] = array($activity_id, $agent_id);
		if($entity_id){
			$this->relations["used"][] = array($activity_id, $entity_id);
		}
	}
	
	function expand(&$map, $prefix=""){
		foreach($this->activities as $i => $v){
			if(isBlankNode($i)){
				$ni = $this->genid($prefix);
				$this->activities[$ni] = $v;
				unset($this->activities[$i]);
				$map[$i] = $ni;
			}
		}
		foreach($this->agents as $i => $v){
			if(isBlankNode($i)){
				$ni = $this->genid($prefix);
				$this->agents[$ni] = $v;
				unset($this->agents[$i]);
				$map[$i] = $ni;
			}
		}
		foreach($this->entities as $i => $v){
			if(isBlankNode($i)){
				$ni = $this->genid($prefix);
				$this->entities[$ni] = $v;
				unset($this->entities[$i]);
				$map[$i] = $ni;
			}
		}
	}
	
	function getFragment($frag_id){
		foreach($this->agents as $id => $agent){
			if($id == $frag_id) return $agent->get_json_ld();
		}
		foreach($this->entities as $id => $ent){
			if($id == $frag_id) return $ent->get_json_ld();
		}
		foreach($this->activities as $id => $ent){
			if($id == $frag_id) return $ent->get_json_ld();
		}
		return false;
	}
	
	function applyIDMap($map){
		foreach($this->activities as $i => $v){
			$v->applyIDMap($map);
			$this->activities[$i] = $v; 
		}
		foreach($this->agents as $i => $v){
			$v->applyIDMap($map);
			$this->agents[$i] = $v;
		}
		foreach($this->entities as $i => $v){
			$v->applyIDMap($map);
			$this->entities[$i] = $v;
		}
		foreach($this->relations as $i => $r){
			$r->applyIDMap($map);
			$this->relations[$i] = $r;
		}
	}

}

class ProvThing {
	var $id;
	var $attributes;
	var $prov_type;
	
	function __construct($id, $attributes = false){
		$this->id = $id;
		if($attributes && is_array($attributes) && isset($attributes["prov:type"])){
			$this->prov_type = $attributes["prov:type"];
		}
		$this->attributes = $attributes;
	}
	
	function get_json_ld(){
		$arr = $this->attributes;
		return $arr;
	}
	
	function applymaptoarray($arr, $map){
		foreach($arr as $i => $v){
			if(!is_array($v)){
				if(isset($map[$v])){
					$arr[$i] = $map[$v];
				}
			}
			else {
				$arr[$i] = $this->applymaptoarray($v, $map);
			}
		}
		return $arr;
	}
	
	function applyIDMap($map){
		$this->attributes = $this->applymaptoarray($this->attributes, $map);
	}
}

class ProvEntity extends ProvThing{
		
}

class ProvAgent extends ProvThing {
	
}

class ProvBundle extends ProvThing {
	
}

class ProvRelation extends ProvThing{
	function relationType(){
		return $this->id;
	}
}

class ProvActivity extends ProvThing {
	var $startTime;
	var $endTime;
	function __construct($id, $vals){
		$this->id = $id;
		if(isset($vals['startTime'])){
			$this->startTime = $vals['startTime'];
		}
		if(isset($vals['endTime'])){
			$this->endTime = $vals['endTime'];
		}
		$this->attributes = $vals;
	}
	
	function get_json_ld(){
		return $this->attributes;
		//return array("startTime" => $this->startTime, "endTime" => $this->endTime);
	}
}
