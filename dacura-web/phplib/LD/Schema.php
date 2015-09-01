<?php
/*
*/

require_once("NSResolver.php");
require_once("LDEntity.php");

class Schema extends LDEntity {
	var $idbase;
	var $instance_prefix;
	var $ns_prefix;
	var $mydir;
	var $default_prefixes = array( 
			"_:_rdf" => array("shorthand" => "rdf", "url" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#"),
			"_:_rdfs" => array("shorthand" => "rdfs", "url" => "http://www.w3.org/2000/01/rdf-schema#"),
			"_:_xsd" => array("shorthand" => "xsd", "url" => "http://www.w3.org/2001/XMLSchema#"),
			"_:_owl" =>  array("shorthand" => "owl", "url" => "http://www.w3.org/2002/07/owl"),
			"_:_prov" => array("shorthand" => "prov", "url" => "http://www.w3.org/ns/prov#"),
			"_:_oa" => array("shorthand" => "oa", "url" => "http://www.w3.org/ns/oa#")
	);
	var $graphnames = array("candidate" => "candidate", "provenance" => "prov", "annotation" => "ao");
	var $schemagraphnames = array("candidate" => "schema", "provenance" => "provenance", "annotation" => "annotation");
	
	
	function __construct($cid, $did, $base_url, $mydir = false){
		parent::__construct(false);
		$this->mydir = $mydir;
		$this->setContext($cid, $did);
		if($cid == "all"){
			$this->idbase = $base_url;
		}
		elseif($did == "all"){
			$this->idbase = $base_url.$cid."/";
		}
		else {
			$this->idbase = $base_url.$cid."/".$did."/";
		}
		$this->cwurl = $this->idbase."schema";
		$this->instance_prefix = $this->idbase."report";
		$this->ns_prefix = $this->idbase."ns#";
	}
	
	function getOntologyFullID($local_id){
		return $this->cwurl."/ontology/".$local_id;
	}
	
	function getNSResolver(){
		$ns = new NSResolver($this->ns_prefix, false, true);
		return $ns;
	}
	
	function loadOntology($ontid, $version = false){//uh-oh with the version -> gonna have to roll back schema...
		if(!isset($this->ldprops['ontologies'])){ 
			return $this->failure_result("No ontologies found inside schema ($ontid)", false);
		}
		foreach($this->ldprops['ontologies'] as $ont){
			if($ont['id'] == $ontid ){
				$ontology = new Ontology($ontid);
				$ontology->nsres = $this->getNSResolver();
				if(!$ontology->loadFromStorage($ont)){
					return $this->failure_result("Failed to load $ontid ontology from storage");
				}
				return $ontology;
			}
		}
		return $this->failure_result("Ontology $ontid does not exist in this schema", 404);		
	}
	
	function getOntology($ontid){
		if(!isset($this->ldprops['ontologies'])) return false;
		if(!isset($this->ldprops['ontologies'][$ontid])) return false;
		return $this->ldprops['ontologies'][$ontid];
	}
	
	function hasOntology($ontid){
		return (isset($this->ldprops['ontologies'][$ontid]));
	}
	
	function getTypeVersion($t){
		return "0.1.0";
		return isset($this->types[$t]) ? $this->types[$t]['version'] : "0.1.0";
	}
	
	function getGraphname($n){
		return isset($this->graphnames[$n]) ? $this->graphnames[$n] : $n;
	}
	
	function getSchemaGraphname($n){
		return isset($this->schemagraphnames[$n]) ? $this->schemagraphnames[$n] : $n."schema";
	}
	
	function addOntology($ont, $internalise = false){
		$fn = $ont->genFname();
		$fname = $this->mydir."$fn.ttl";
		$ont->setFilename($fname);
		$this->ldprops['ontologies'][$ont->id] = $ont->store($internalise);		
	}
	
	function loadDefaults(){
		$this->status = "undefined";
		$this->version = 1;
		$this->type_version = "0.1.0";
		$graph_descriptions = array("instance" => "The graph where instance data (the actual data being collected) will be stored",
			"provenance" => "Provenance information about the instance data - where it came from and what we have done to it.",
			"annotation" => "Annotations on the data, containing any interesting information that is worth noting but is outside of the scope of the main dataset",
		);
		$graphs = array( array("id" => "all", 
			"@id" => "all",
			"instance_graph" => $this->instance_prefix,
			"schema_graph" => $this->cwurl,
			"schema" => $this->ns_prefix."#",
			"description" => "All the instance data, including provenance and annotations concerning the data that has been collected.",
			"contents" => ""
				
		));
		foreach($graph_descriptions as $g => $d){
			$graphs[] = array(
				"@id" => $g,
				"local_id" => $g,
				"instance_graph" => $this->instance_prefix."/".$g,
				"schema_graph" => $this->cwurl."/".$g,
				"schema" => $this->ns_prefix ."/".$g."#",
				"description" => $d, 
				"contents" => ""
			);
		}
		$ldprops = array();
		$ldprops["graphs"] = $graphs;
		$prefixes = $this->default_prefixes;
		$prefixes["_:_local"] = array("shorthand" => "local", "url" => $this->instance_prefix);
		$prefixes["_:_dacura"] = array("shorthand" => "dacura", "url" => "fill me in");
		if($this->cid != "all" && $this->did == "all"){
			$prefixes["_:_".$this->cid] = array("shorthand" => $this->cid, "url" => $this->ns_prefix."/ns#");
		}
		elseif($this->cid != "all"){
			$prefixes["_:_".$this->did] =  array("shorthand" => $this->did, "url" => $this->ns_prefix."/ns#");
		}
		$ldprops["namespaces"] = $prefixes;	
		$this->ldprops = $ldprops;
		parent::expand(true);
	}
	
	function get_json_contents(){
		return json_encode($this->ldprops);
	}
	
	function loadFromJSON($json){
		$this->ldprops = json_decode($json, true);
		if(!$this->ldprops){
			return $this->failure_result("Failed to read saved state of schema from json", 500);
		}
		return true;
	}
	
	function expandNS(){
		expandNamespaces($this->ldprops, $this, $this->cwurl);
	}
	
	
	function getDisplayFormat(){
		return $this->ldprops;
	}
	
	function loadFromAPI($obj){
		$this->loadDefaults();
	}
	
	function validate(){
		return true;
	}
	
	
	function getGraph($id){
		if(isset($this->graphs[$id])){
			return $this->graphs[$id];
		}
		return $this->failure_result("No graph with id $id in schema", 404);
	}
	
	/*
	 * Namespace related stuff

	function getURL($shorthand){
		return isset($this->prefixes[$shorthand]) ? $this->prefixes[$shorthand] : false;
	}
	
	function getShorthand($url){
		foreach($this->prefixes as $shorthand => $id){
			if($url == $id){
				return $shorthand;
			}
		}
		return false;
	}
	
	function match($value, $prefix, $id){
		if($value == $prefix.":".$id) return true;
		if($value == $this->getURL($prefix).$id) return true;
		return false;
	}
	
	function compress($url){
		foreach($this->prefixes as $shorthand => $id){
			if(substr($url, 0, strlen($id)) == $id){
				$urlid = substr($url, strlen($id));
				return $shorthand.":".$urlid;
			}
		}
		return false;
	}
	
	function expand($prefixed_url){
		if(isNamespacedURL($prefixed_url) && ($shorthand = getNamespacePortion($prefixed_url))){
			$url = $this->getURL($shorthand);
			if($url){
				return $url . substr($prefixed_url, strlen($shorthand) + 1);
			}
		}
		return false;
	}	 */

}
