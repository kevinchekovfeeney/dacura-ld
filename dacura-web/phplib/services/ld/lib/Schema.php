<?php
/*
*/

require_once("NSResolver.php");
require_once("LDEntity.php");
require_once("phplib/services/schema/Graph.php");
require_once("phplib/services/schema/Ontology.php");

class Schema extends DacuraObject {
	var $cid;
	var $did;
	var $idbase;
	var $instance_prefix;
	var $ns_prefix;
	var $graphs = array();
	var $ontologies = array();
	var $nsres;
	
	function __construct($cid, $did, $base_url){
		$this->cid = $cid;
		$this->did = $did;
		if($cid == "all"){
			$this->idbase = $base_url;
		}
		elseif($did == "all"){
			$this->idbase = $base_url.$cid."/";
		}
		else {
			$this->idbase = $base_url.$cid."/".$did."/";
		}
		$this->instance_prefix = $this->idbase."candidate";
		$this->ns_prefix = $this->idbase."ns#";
	}
	
	function load($graphrecords){
		if($graphrecords && is_array($graphrecords)){
			foreach($graphrecords as $row){
				$graph = new Graph($row["id"]);
				$graph->loadFromDBRow($row);
				$this->graphs[$row["id"]] = $graph;
			}
		}
	}
	
	function loadOntologies(&$srvr, $status = false, $graphid= false){
		$ontids = array();
		if($graphid){
			$graph = $this->getGraph($graphid);
			if(!$graph){
				return false;
			}				
			$ontids = $graph->getImportedOntologies();
			if($graph->hasLocalOntology()){
				$ont = $graph->getLocalOntology($srvr);
				$this->ontologies[$ont->id] = $ont;				
			}
		}
		else {
			$graphs = $this->getGraphs($status);
			foreach($graphs as $graphid => $graph){
				$ontids = array_merge($ontids, $graph->getImportedOntologies());
				if($graph->hasLocalOntology()){
					$ont = $graph->getLocalOntology($srvr);
					$this->ontologies[$ont->id] = $ont;
				}				
			}
		}
		foreach($ontids as $id){
			$ont = $srvr->loadEntity($id, "ontology", "all", "all");
			if($ont){
				$this->ontologies[$id] = $ont;
			}
			else {
				return $this->failure_result("Failed loading ontology $id ".$srvr->errmsg, $srvr->errcode);
			}
		}
		return true;		
	}
	/*
	 * graph => (classname, classname...)
	 */
	function adornGraphClasses($entclasses){
		$results = array();
		foreach($entclasses as $gid => $classes){
			$results[$gid] = array();
			foreach($classes as $cls){
				$res = $this->adornClass($cls);
				if($res){
					$results[$gid][$cls] = $res;
				}
				else {
					//return false;
				}
			}
		}
		$simple = array();
		foreach($results as $gid => $clist){
			$simple[$gid] = array();
			foreach($clist as $cls => $frags){
				$scls = array("name" => $cls);
				foreach($frags as $i => $frag){
					if(isset($frag['rdfs:label'])){
						$scls['label'] = decodeScalar($frag['rdfs:label']);
					}
					if(isset($frag['rdf:type'])){
						$scls['type'] = $frag['rdf:type'];
					}
					if(isset($frag['rdfs:subClassOf'])){
						$scls['subclass'] = $frag['rdfs:subClassOf'];
					}
					if(isset($frag['rdfs:comment'])){
						$scls['comment'] = decodeScalar($frag['rdfs:comment']);
					}
				}
				$simple[$gid][] = $scls;
			}
		}
		return $simple;		
	}
	
	function adornClass($cls){
		if(!isNamespacedURL($cls)){
			$nsurl = $this->nsres->compress($cls);
			if(!$nsurl){
				return $this->failure_result("No ontology found for class $cls", 400);
			}
			$cls = $nsurl;
		}
		$ns = getNamespacePortion($cls);
		if(!isset($this->ontologies[$ns])){
			//return $this->failure_result("Ontology $ns not loaded for class $cls", 400);				
		}
		else {
			$ont = $this->ontologies[$ns];
			$ont->compressNS();
			$expanded = $this->nsres->expand($cls);
			if($expanded){
				$fragment = $ont->getFragment($cls);
				if($fragment) return $fragment;
				return $this->failure_result("Class $cls not found in ".$ont->id, 404); 				
			}
		}
		//return $this->failure_result("Unknown $cls not known by system", 400);
		//get the ontology, then ask the ontology what is in it...
	}
	
	function hasGraph($id){
		return isset($this->graphs[$id]);
	}
	
	function getGraph($id){
		if(isset($this->graphs[$id])){
			return $this->graphs[$id];
		}
		return $this->failure_result("Graph $id does not exist", 404);
	}
	
	function getGraphs($status = false){
		if($status == false){
			return $this->graphs;
		}
		else {
			$graphs = array();
			foreach($this->graphs as $id => $graph){
				if($graph->status == $status){
					$graphs[$id] = $graph;
				}
			}
			return $graphs;
		}
	}
	
	function getNGSkeleton(){
		$skel = array("ldprops" => array(), "meta" => array());
		foreach($this->graphs as $ent){
			$skel["ldprops"][$ent["id"]] = array();
		}
		return $skel;
	}
	
	
}
