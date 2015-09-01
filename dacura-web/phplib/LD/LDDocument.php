<?php

/*
 * Class representing a Linked Data Document (LD object + state) in the Dacura DB
 * This class is generic - it makes no assumptions about the content of the Linked Data Document
 * It contains functionality to build indexes and maps of documents and compare them to one another
 * The candidate class contains the mapping to system state (object type, version, etc)
 * 
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */
include_once("phplib/libs/easyrdf-0.9.0/lib/EasyRdf.php");
include_once("LDUtils.php");

/*
 * maintains state about a particular LD object 
 */

class LDDocument extends DacuraObject {
	var $id = false;
	var $implicit_add_to_valuelist = false;//should we allow {p: scalar} to update {p: [scalar, array]} or overwrite it....
	var $ldprops; //associative array in Dacura LD format
	var $index = false; //obj_id => &$obj 
	var $bad_links = array(); //bad links in various categories in the document
	var $idmap = array(); //blank nodes that have been mapped to new names in the document
	var $cwurl = "";//closed world URL of the document. If present, encapsulated entities will have ids that start with this.
	var $compressed = false;
	
	function __construct($id){
		$this->id = $id;
	}
	
	function __clone(){
		$this->ldprops = deepArrCopy($this->ldprops);
		$this->index = false;
		$this->bad_links = deepArrCopy($this->bad_links);
	}

	function load($arr){
		$this->ldprops = $arr;
	}
	
	function get_json($key = false){
		if($key){
			if(!isset($this->ldprops[$key])){
				return "{}";
			}
			return json_encode($this->ldprops[$key]);
		}
		return json_encode($this->ldprops);
	}
	
	function get_json_ld(){
		$ld = $this->ldprops;
		$ld["@id"] = $this->id;
		return $ld;
	}

	function getFragment($fid){
		if($this->index === false){
			$this->buildIndex();
		}
		return isset($this->index[$fid]) ? $this->index[$fid] : false;
	}

	function hasFragment($frag_id){
		if($this->index === false){
			$this->buildIndex($this->ldprops, $this->index);
		}
		return isset($this->index[$frag_id]);
	}
	
	function isDocumentLocalLink($val){
		return isInternalLink($val, $this->id, $this->cwurl);
	}
	
	function getFragmentPaths($fid, $html = false){
		$paths = getFragmentContext($fid, $this->ldprops, $this->cwurl);
		return $paths;
	}
	
	function setContentsToFragment($fragment_id){
		$this->ldprops = getFragmentInContext($fragment_id, $this->ldprops, $this->cwurl);
	}
	
	function buildIndex(){
		$this->index = array();
		indexLD($this->ldprops, $this->index, $this->cwurl);
	}
	
	function typedTriples(){
		return getObjectAsTypedTriples($this->id, $this->ldprops, $this->cwurl);
	}
	
	function getNamespaces($nsobj){
		$x = getNamespaces($this->ldprops, $nsobj, $this->cwurl, $this->compressed);
		return $x;		
	}
	
	function expandNamespaces($nsobj){
		$this->compressed = false;
		expandNamespaces($this->ldprops, $nsobj, $this->cwurl);
	}
	
	function compressNamespaces($nsobj){
		$this->compressed = true;
		compressNamespaces($this->ldprops, $nsobj, $this->cwurl);
	}
	
	function triples(){
		return getObjectAsTriples($this->id, $this->ldprops, $this->cwurl);
	}
	
	function turtle(){
		return getObjectAsTurtle($this->id, $this->ldprops, $this->cwurl);
	}
	
	function importERDF($type, $arg, $gurl = false, $format = false){
		try {
			if($type == "url"){
				$graph = EasyRdf_Graph::newAndLoad($arg, $format);				
			}
			elseif($type == "text"){
				$graph = new EasyRdf_Graph($gurl, $arg, $format);
			}
			elseif($type == "file"){
				$graph = new EasyRdf_Graph($gurl);
				$graph->parseFile($arg, $format);
			}
			if($graph->isEmpty()){
				return $this->failure_result("Graph loaded from $type was empty.", 500);				
			}
			return $graph;
		}
		catch(Exception $e){
			return $this->failure_result("Failed to load graph from $type. ".$e->getMessage(), $e->getCode());
		}
	}
	
	function import($type, $arg, $gurl = false, $format = false){
		$graph = $this->importERDF($type, $arg, $gurl, $format);
		$op = $graph->serialise("php");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->expand();
		$errs = validLD($this->ldprops, $this->cwurl);
		if(count($errs) > 0){
			$msg = "<ul><li>".implode("<li>", $errs)."</ul>";
			return $this->failure_result("Graph had ". count($errs)." errors. $msg", 400);				
		}
		return true;	
	}
	
	function export($format, $nsobj = false){
		$easy = exportEasyRDFPHP($this->id, $this->ldprops);
		try{
			$graph = new EasyRdf_Graph($this->id, $easy, "php");
			if($graph->isEmpty()){
				return $this->failure_result("Graph was empty.", 400);				
			}
			if($nsobj){
				$nslist = $this->getNamespaces($nsobj);
				if($nslist){
				foreach($nslist as $prefix => $full){
						EasyRdf_Namespace::set($prefix, $full);
					}
				}
			}
			$res = $graph->serialise($format);
			if(!$res){
				return $this->failure_result("failed to serialise graph", 500);
			}
			return $res;				
		}
		catch(Exception $e){
			return $this->failure_result("Graph croaked on input. ".$e->getMessage(), $e->getCode());
		}
	}
	
	function expand($allow_demand_id = false){
		$rep = expandLD($this->id, $this->ldprops, $this->cwurl, $allow_demand_id);
		if($rep === false){
			return $this->failure_result("Failed to expand blank nodes", 400);;
		}
		if(isset($rep["missing"])){
			$this->bad_links = $rep["missing"];
		}
		$this->idmap = $rep['idmap'];
		return true;
	}
	
	function problems(){
		if(count($this->bad_links) > 0){
			return $this->bad_links;
		}
		return false;
	}
	
	function missingLinks(){
		if(isset($this->bad_links)){
			return $this->bad_links;				
		}
		return $this->findMissingLinks();
	}
	
	function findMissingLinks(){
		if($this->index === false){
			$this->buildIndex($this->ldprops, $this->index, $this->cwurl);
		}
		$ml = findInternalMissingLinks($this->ldprops, array_keys($this->index), $this->id, $this->cwurl);
		$x = count($ml);
		if($x > 0){ 
			$this->bad_links = $ml;
		}
		return $ml;
	}
	
	function compliant(){
		$errs = validLD($this->ldprops, $this->cwurl);
		if(count($errs) == 0){
			return true;
		}
		else {
			$errmsg = "Errors in input formatting:<ol> ";
			foreach($errs as $err){
				$errmsg .= "<li>".$err[0]." ".$err[1];
			}
			$errmsg .= "</ol>";
			return $this->failure_result($errmsg, 400);
		}
	}
		
	/*
	 * Calculates the transforms necessary to get to current from other
	 */
	function compare($other){
		$delta = compareLD($this->id, $this->ldprops, $other->ldprops, $this->cwurl);
		if($delta->containsChanges()){
			$delta->setMissingLinks($this->missingLinks(), $other->missingLinks());
		}
		return $delta; 
	}
	
	function update($update_obj, $is_force=false, $demand_id_allowed = false){
		if($this->applyUpdates($update_obj, $this->ldprops, $this->idmap, $is_force, $demand_id_allowed)){
			if(count($this->idmap) > 0){
				$unresolved = updateBNReferences($this->ldprops, $this->idmap, $this->cwurl);
				if($unresolved === false){
					return false;
				}
				elseif(count($unresolved) > 0){
					$this->bad_links = $unresolved;
				}
			}
			$this->buildIndex();
			return true;
		}
		return false;
	}
	
	function getPropertyAsQuads($prop, $gname){
		if(!isset($this->ldprops[$prop])) return array();
		$quads = array();
		$trips = getEOLAsTypedTriples($this->ldprops[$prop], $this->cwurl);
		foreach($trips as $trip){
			$trip[] = $gname;
			$quads[] = $trip;
		}
		return $quads;
	}
	
	
	/**
	 * Apply changes specified in props to properties in dprops
	 * Generates new ids for each blank node and returns mapping in idmap.
	 *
	 * @param array $uprops - the update instructions
	 * @param array $dprops - the properties to be updated (delta)
	 * @param array $idmap - map of local ids to newly generated IDs
	 * @return boolean
	 */
	function applyUpdates($uprops, &$dprops, &$idmap, $id_set_allowed = false, $demand_id_allowed = false, $implicit_add_to_valuelist = false){
		//opr($uprops['http://localhost/dacura/schema/ontology/ONTevgdhlvxsw']['http://dacura.cs.tcd.ie/data/seshat#']);
		//opr($dprops['http://localhost/dacura/schema/ontology/ONTevgdhlvxsw']['http://dacura.cs.tcd.ie/data/seshat#']);
		//if(isset($uprops[http://localhost/dacura/schema/ontology/ONTevgdhlvxsw'])){
		//	opr($uprops['http://localhost/dacura/schema/ontology/ONTevfuqe84sw']['http://www.w3.org/ns/oa#']['http://purl.org/dc/elements/1.1/title']);
			//opr($dprops['http://localhost/dacura/schema/ontology/ONTevfuqe84sw']['http://www.w3.org/ns/oa#']['http://purl.org/dc/elements/1.1/title']);
		//}
		
		foreach($uprops as $prop => $v){
			//if($prop == "contents"){
				//opr($v);
			//}
			if(!is_array($dprops)){
				$dprops = array();
			}
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()){
				return $this->failure_result($pv->errmsg, $pv->errcode);
			}
			elseif($pv->scalar() or $pv->objectliteral()){
				//question as to whether we support updates that don't specify the entire output state....
				if($implicit_add_to_valuelist && isset($dprops[$prop])){
					$upv = new LDPropertyValue($dprops[$prop], $this->cwurl);
					if($upv->scalar() or $upv->objectliteral()){
						$dprops[$prop] = $v;
					}
					elseif($upv->valuelist() or $upv->objectliterallist()){
						$dprops[$prop][] = $v;
					}
				}
				else {
					$dprops[$prop] = $v;						
				}
			}
			elseif($pv->valuelist() or $pv->objectliterallist()){
				$dprops[$prop] = $v;
			}
			elseif($pv->isempty()){ // delete property or complain
				if(isset($dprops[$prop])){
					unset($dprops[$prop]);
				}
				else {
					return $this->failure_result("Attempted to remove non-existant property $prop", 404);
				}
			}
			elseif($pv->objectlist()){ //list of new objects (may have @ids inside)
				foreach($v as $obj){
					addAnonObj($this->id, $obj, $dprops, $prop, $idmap, $this->cwurl, $demand_id_allowed);
				}
			}
			elseif($pv->embedded()){ //new object to add to the list - give him an id and insert him
				addAnonObj($this->id, $v, $dprops, $prop, $idmap, $this->cwurl, $demand_id_allowed);
			}
			elseif($pv->embeddedlist()){
				$bnids = $pv->getbnids();//new nodes
				foreach($bnids as $bnid){
					addAnonObj($this->id, $v[$bnid], $dprops, $prop, $idmap, $this->cwurl, $demand_id_allowed, $bnid);
				}
				$delids = $pv->getdelids();//delete nodes
				foreach($delids as $did){
					if(isset($dprops[$prop][$did])){
						unset($dprops[$prop][$did]);
					}
					else {
						return $this->failure_result("Attempted to remove non-existant embedded object $did from $prop", 404);
					}
				}
				$update_ids = $pv->getupdates();
				foreach($update_ids as $uid){
					if(!isset($dprops[$prop])){
						$dprops[$prop] = array();
					}
					//echo "<h5>$prop $uid</h5>";
					//opr($dprops[$prop]);	
					if(!isset($dprops[$prop][$uid])){
					//echo "<h1>$prop $uid</h1>";						
						if($id_set_allowed){
							$dprops[$prop][$uid] = array();
						}
						else {
							return $this->failure_result("Attempted to update property non existent $uid of property $prop", 404);
						}
					}
					//opr($dprops[$prop][$uid]);
					if(!$this->applyUpdates($uprops[$prop][$uid], $dprops[$prop][$uid], $idmap, $id_set_allowed, $demand_id_allowed)){
						return false;
					}
					//opr($dprops[$prop][$uid]);						
					if(isset($dprops[$prop][$uid]) && is_array($dprops[$prop][$uid]) and count($dprops[$prop][$uid]) == 0){
						unset($dprops[$prop][$uid]);
					}
				}
			}
			if(isset($dprops[$prop]) && is_array($dprops[$prop]) && count($dprops[$prop])==0) {
				unset($dprops[$prop]);
			}
		}
		//if(isset($uprops['http://localhost/dacura/schema/ontology/ONTevfuqe84sw'])){
		//	opr($uprops['http://localhost/dacura/schema/ontology/ONTevfuqe84sw']['http://www.w3.org/ns/oa#']['http://purl.org/dc/elements/1.1/title']);
		//	opr($dprops['http://localhost/dacura/schema/ontology/ONTevfuqe84sw']['http://www.w3.org/ns/oa#']['http://purl.org/dc/elements/1.1/title']);
		//}
		
		return true;
	}	
}
