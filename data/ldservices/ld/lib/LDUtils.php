<?php
/*
 * The structure of json ld objects is modeled as a normal associative array
 * This file contains functions that operate on arrays in LD format 
 * 
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

require_once "LDPropertyValue.php";
require_once "LDDelta.php";

/*
 * Expand the structure, generate real ids for blank nodes and update any references to the blank nodes to the new ids
 */
function expandLD($idbase, &$ldprops, $cwurl, $allow_demand_id = false){
	$idmap = array();
	generateBNIDS($idbase, $ldprops, $idmap, $cwurl, $allow_demand_id, true);
	if(count($idmap) > 0){
		$missing_refs = updateBNReferences($ldprops, $idmap, $cwurl);
		if($missing_refs === false){
			return false;
		}
		elseif(is_array($missing_refs) && count($missing_refs) > 1){
			return array("missing" => $missing_refs, "idmap" => $idmap);
		}
	}
	return array("idmap" => $idmap);	
}

/*
 * Generates ids for blank nodes and alters the structure
 * We do not expand the meta field and do not generate BNIDs for the top level (graphname) indices.
 * to expand embedded objects and object lists with ld structure
 */
function generateBNIDs($idbase, &$ldprops, &$idmap, $cwurl, $allow_demand_id = false, $top_level = false){
	$nprops = array();
	if(!is_array($ldprops)){
		return false;
	}
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embedded()){
			if(!$top_level){
				$new_id = addAnonObj($idbase, $v, $nprops, $p, $idmap, $cwurl, $allow_demand_id);
				generateBNIDs($idbase, $nprops[$p][$new_id], $idmap, $cwurl, $allow_demand_id);				
			}
			else {
				if($p == "main"){
					$nprops[$p] = array($cwurl => $v);
					generateBNIDs($idbase, $nprops[$p][$cwurl], $idmap, $cwurl, $allow_demand_id);
				}
				else 
				{
					$nprops[$p] = $v;
					generateBNIDs($idbase, $nprops, $idmap, $cwurl, $allow_demand_id);
				}				
			}
		}
		elseif($pv->objectlist()){
			foreach($ldprops[$p] as $obj){
				$new_id = addAnonObj($idbase, $obj, $nprops, $p, $idmap, $cwurl, $allow_demand_id);
				generateBNIDs($idbase, $nprops[$p][$new_id], $idmap, $cwurl, $allow_demand_id);
			}
		}
		elseif($pv->embeddedlist()){
			$nprops[$p] = array();
			foreach($v as $id => $obj){
				if(isBlankNode($id)){
					$nid = addAnonObj($idbase, $obj, $nprops, $p, $idmap, $cwurl, false, $id);
					generateBNIDs($idbase, $nprops[$p][$nid], $idmap, $cwurl, $allow_demand_id);
				}
				else {
					$nprops[$p][$id] = $obj;
					generateBNIDs($idbase, $nprops[$p][$id], $idmap, $cwurl, $allow_demand_id);						
				}
			}
		}
		else {
			$nprops[$p] = $v;
		}
	}
	$ldprops = $nprops;
}

/**
 * update internal references by replacing blank node values with newly generated ids..
 */
function updateBNReferences(&$ldprops, $idmap, $cwurl){
	$unresolved = array();
	$nprops = array();
	if(!is_array($ldprops)){
		return $unresolved;
	}
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->bn()){
			if(isset($idmap[$v])){
				$nprops[$p] = $idmap[$v];
			}
			else {
				$unresolved[] = $v;
			}
		}
		elseif($pv->valuelist()){
			$nvals = array();
			foreach($v as $val){
				if(isBlankNode($val)){
					if(isset($idmap[$val])){
						$nvals[] = $idmap[$val];
					}
					else {
						$unresolved[] = $val;
					}
				}
				else{
					$nvals[] = $val;
				}
			}
			$nprops[$p] = $nvals;
		}
		elseif($pv->embeddedlist()){
			$nprops[$p] = array();
			foreach($v as $id => $obj){
				$nprops[$p][$id] = $obj;
				updateBNReferences($nprops[$p][$id], $idmap, $cwurl);
			}
		}
		elseif($pv->embedded()){
			$nprops[$p] = $v;
			if(!$cwurl){
				updateBNReferences($nprops, $idmap, $cwurl);
			}				
		}
		elseif($pv->objectlist()){
			if(!$cwurl){
				$nprops[$p] = array();
				foreach($v as $i => $obj){
					$nprops[$p][$i] = $obj;
					updateBNReferences($nprops[$p][$i], $idmap, $cwurl);
				}
			}
			else {
				$nprops[$p] = $v;
			}				
		}
		else {
			$nprops[$p] = $v;				
		}
	}
	$ldprops = $nprops;
	return $unresolved;
}

/*
 * Basic validity check on a LD structure
 */
function validLD($ldprops, $cwurl = false){
	$errs = array();
	if(!$ldprops or !is_array($ldprops)) return true;
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->illegal()){
			$errs[] = array($p, "Illegal value ".$pv->errmsg);
		}
		elseif($pv->embedded()){
			$errs = array_merge($errs, validLD($ldprops[$p], $cwurl));
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				$errs = array_merge($errs, validLD($obj, $cwurl));
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				$errs = array_merge($errs, validLD($obj, $cwurl));
			}
		}
	}
	return $errs;
}

function importEasyRDFPHP($easy){
	$imported = array();
	foreach($easy as $s => $ps){
		$imported[$s] = array();
		foreach($ps as $p => $vs){
			if(count($vs) > 1){
				$imported[$s][$p] = array();
				foreach($vs as $v){
					if($v['type'] == 'bnode' or $v['type'] == "uri"){
						$imported[$s][$p][] = $v['value'];
					}
					elseif($v['type'] == 'literal'){
						$v['type'] = isset($v['datatype']) ? $v['datatype'] : "string";
						$v['data'] = $v['value'];
						unset($v['value']);
						unset($v['datatype']);
						if(isset($v['lang'])){
							unset($v['type']);
						}
						$imported[$s][$p][] = $v;						
					}
					else {
						$imported[$s][$p][] = $v;						
					}				
				}
			}
			else {
				foreach($vs as $v){
					if($v['type'] == 'bnode' or $v['type'] == "uri"){
						$imported[$s][$p] = $v['value'];
					}
					else {
						$v['type'] = isset($v['datatype']) ? $v['datatype'] : "string";
						$v['data'] = $v['value'];
						unset($v['value']);
						unset($v['datatype']);
						if(isset($v['lang'])){
							unset($v['type']);
						}
						$imported[$s][$p] = $v;
					}
				}				
			}
		}	
	}
	return $imported;
}

function unembed($ldprops, $rid, $cwurl = false){
	$unem = array($rid => array());
	if(!is_array($ldprops)) return $unem;
	foreach($ldprops as $p => $v){
		$unem[$rid][$p] = array();
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $vid => $vobj){
				$unem[$rid][$p][] = $vid;
				$unem = array_merge_recursive($unem, unembed($vobj, $vid, $cwurl));
			}
		}
		elseif($pv->embedded()){
			$bnid = "_:$rid";
			$unem[$rid][$p][] = $bnid;
			$unem = array_merge($unem, unembed($v, $bnid, $cwurl));			
		}
		elseif($pv->objectlist()){
			foreach($v as $i => $obj){
				$bnid = "_:$rid_$i";
				$unem[$rid][$p][] = $bnid;
				$unem = array_merge($unem, unembed($v, $bnid, $cwurl));
			}
		}
		elseif($pv->valuelist() or $pv->objectliterallist()) {
			$unem[$rid][$p] = $v;
		}
		else {
			$unem[$rid][$p][] = $v;
		}
	}
	return $unem;
}

function exportEasyRDFPHP($id, $ldprops){
	$exported = array();
	$easy = unembed($ldprops, $id);
	unset($easy[$id]);
	foreach($easy as $s => $ps){
		$exported[$s] = array();
		foreach($ps as $p => $vs){
			$exported[$s][$p] = array();
			foreach($vs as $v){
				if(is_array($v)){
					if(!isset($v['type'])){
						$v['type'] = 'literal';
					}
					elseif($v['type'] != 'string' && $v['type'] != 'literal'){
						$v['datatype'] = $v['type'];
						$v['type'] = "literal";
					}
					if($v['type'] == 'string'){
						$v['type'] = 'literal';
					}
					if(isset($v['data'])){
						$v['value'] = $v['data'];
						unset($v['data']);
					}	
					if(!isset($v['value'])){
						$v['value'] = '';
					}	
					$exported[$s][$p][] = $v;						
				}
				else {
					if(isBlankNode($v)){
						$exported[$s][$p][] = array("type" => "bnode", "value" => $v);
					}
					elseif(isURL($v)){
						$exported[$s][$p][] = array("type" => "uri", "value" => $v);
					}
					else {
						$exported[$s][$p][] = array("type" => "literal", "value" => $v);
					}
				}
			}
		}
	}
	return $exported;
}

function toJSONLD($props, $cwurl){
	$nprops = array();
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embedded()){
			$nprops[$p] = toJSONLD($props[$p], $cwurl);
		}
		elseif($pv->objectlist()){
			$nprops[$p] = array();
			foreach($v as $obj){
				$nprops[$p][] = toJSONLD($obj, $cwurl);
			}
		}
		elseif($pv->embeddedlist()){
			$nprops[$p] = array();
			foreach($v as $id => $obj){
				$nobj = toJSONLD($obj, $cwurl);
				$nobj["@id"] = $id;
				$nprops[$p][] = $nobj;
			}
		}
		else {
			$nprops[$p] = $v;
		}
	}
	return $nprops;
}

/*
 * Generate an ID for a new LD fragment
 * We give the core dacura structures their own non randomly generated ids
 */
function genid($idbase, $bn = false, $allow_demand_id = false){
	if(!$idbase){ //open world mode
		if($bn){
			return $bn;
		}
		else {
			return "_:" .uniqid_base36(false);
		}
	}
	if($bn && substr($bn, 0, 3) == "_:_"){
		$bn = substr($bn, 3);
	}
	if($allow_demand_id && $bn && ctype_alnum($bn) && strlen($bn) > 1 && strlen($bn) < 40 ){
		return $idbase."/".$bn;
	}
	elseif($bn){
		if($bn == "_:entity"){
			return $idbase;
		}
		elseif($bn == "_:meta"){
			return $idbase."/meta";
		}
	}
	return $idbase . "/" . uniqid_base36(false);
}

/*
 * Build an index of a LD structure: id => [nodes]
 */
function indexLD($ldprops, &$index, $cwurl){
	if(!is_array($ldprops)) return;
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if(!isset($index[$id])){
					$index[$id] = array($ldprops[$p][$id]);
				}
				else {
					$index[$id][] = $ldprops[$p][$id];
				}
				indexLD($obj, $index, $cwurl);
			}
		}
	}
}



function getObjectAsTypedTriples($id, $ldprops, $cwurl){
	return getPropertiesAsArray($id, $ldprops, $cwurl, "addTypes");
}	

function getObjectAsTriples($id, $ldprops, $cwurl){
	return getPropertiesAsArray($id, $ldprops, $cwurl, "nop");
}

function getEOLAsTypedTriples($eol, $cwurl, $frag_id = false, $p = false){
	return getEOLAsArray($eol, $cwurl, "addTypes", $frag_id, $p);
}

function getEOLAsTriples($eol, $cwurl, $frag_id = false, $p = false){
	return getEOLAsArray($eol, $cwurl, "nop", $frag_id, $p);
}

function getValueAsTypedTriples($id, $p, $v, $cwurl){
	return getValueAsArray($id, $p, $v, $cwurl, "addTypes");
}

function getValueAsTriples($id, $p, $v, $cwurl) {
	return getValueAsArray($id, $p, $v, $cwurl, "nop");	
}

function getPropertiesAsArray($id, $ldprops, $cwurl, $callback){
	$props = array();
	if($ldprops && is_array($ldprops)){
		foreach($ldprops as $p => $v){
			$nt = getValueAsArray($id, $p, $v, $cwurl, $callback);
			if($nt === false){
				return false;
			}
			else {
				$props = array_merge($props, $nt);
			}
		}
	}
	return $props;
}

function getNodesWithPredicate($id, $ldprops, $preds, $cwurl = false){
	$trips = array();
	$bni = 0;
	foreach($ldprops as $p => $v){
		if(in_array($p, $preds)){
			$pv = new PropertyValue($v, $cwurl);
			if($pv->scalar() or $pv->valuelist() or $pv->objectliteral() or $pv->objectliterallist()){
				$trips[] = array($id, $p, $v);
			}
			elseif($pv->embedded()){
				$trips = array_merge_recursive($trips, getNodesWithPredicate("_:BN_".$id."_".++$bni, $v, $preds, $cwurl));
			}
			elseif($pv->objectlist()){
				foreach($v as $i => $obj){
					$trips = array_merge_recursive($trips, getNodesWithPredicate("_:BN_".$id ."_".++$bni, $obj, $preds, $cwurl));
				}
			}
			elseif($pv->embeddedlist()){
				foreach($v as $id => $obj){
					$trips = array_merge_recursive($trips, getNodesWithPredicate("_:BN_".$id ."_".++$bni, $obj, $preds, $cwurl));						
				}
			}		
		}
	}
	return $trips;
}

	


function getEOLAsArray($eol, $cwurl, $callback, $frag_id = false, $p = false){
	$props = array();
	foreach($eol as $oid => $obj){
		if($frag_id && $p){
			$props = array_merge($props, $callback($frag_id, $p, $oid, "cwid"));
		}
		$props = array_merge($props, getPropertiesAsArray($oid, $obj, $cwurl, $callback));
	}
	return $props;
}

function getValueAsArray($id, $p, $v, $cwurl, $callback){
	$pv = new LDPropertyValue($v, $cwurl);
	$anon = 0;
	$triples = array();
	if($pv->literal()){
		$triples = array_merge($triples, $callback($id, $p, $v, 'literal'));
	}
	elseif($pv->link()){
		$triples = array_merge($triples, $callback($id, $p, $v, 'link'));
	}
	elseif($pv->objectliteral()){
		$triples = array_merge($triples, $callback($id, $p, $v, 'objectliteral'));
	}
	elseif($pv->valuelist()){
		foreach($v as $val){
			if(isLiteral($val)){
				$triples = array_merge($triples, $callback($id, $p, $val, 'literal'));
			}
			else {
				$triples = array_merge($triples, $callback($id, $p, $val, 'link'));
			}
		}
	}
	elseif($pv->objectliterallist()){
		foreach($v as $obj){
			$triples = array_merge($triples, $callback($id, $p, $obj, 'objectliteral'));
		}
	}
	elseif($pv->embedded()){
		$aid = "_:BN".(++$anon); //need to generate an id to allow us to triplify it
		$triples = array_merge($triples, $callback($id, $p, $aid, 'blank'));
		$triples = array_merge($triples, getPropertiesAsArray($aid, $v, $cwurl, $callback));
	}
	elseif($pv->objectlist()){
		foreach($v as $obj){
			$aid = "_:".(++$anon);
			$triples = array_merge($triples, $callback($id, $p, $aid, 'blank'));
			$triples = array_merge($triples, getPropertiesAsArray($aid, $obj, $cwurl, $callback));
		}
	}
	elseif($pv->embeddedlist()){
		$triples = getEOLAsArray($v, $cwurl, $callback, $id, $p);
	}
	return $triples;
}

	
function setFragment($f, &$dprops, $nprops, $cwurl){
	foreach($dprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					$dprops[$p][$id] = $nprops;
					return true;
				}
				else {
					if(setFragment($f, $dprops[$p][$id], $nprops, $cwurl)){
						return true;
					}
				}
			}
		}
	}
	return false;
}

function getFragment($f, $ldprops, $cwurl){
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					return $obj;
				}
				else {
					return getFragment($f, $obj, $cwurl);
				}
			}
		}
	}
	return false;
}

function getFragmentContext($f, $ldprops, $cwurl){
	$paths = array();
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					$paths[] = array($p);
				}
				else {
					$cpaths = getFragmentContext($f, $obj, $cwurl);
					foreach($cpaths as $pat){
						$paths[] = array_merge(array($p, $id), $pat);
					}		
				}
			}
		}	
	}
	return $paths;
}

function getFragmentInContext($f, $ldprops, $cwurl){
	$nprops = array();
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					$nprops[$p] = array();
					$nprops[$p][$id] = $obj;
					return $nprops;
				}
				else {
					$cprops = getFragmentInContext($f, $obj, $cwurl);
					if($cprops){
						$nprops[$p] = array();
						$nprops[$p][$id] = $cprops;
						return $nprops;
					}
				}
			}
		}
	}
	return false;
}

/*
 * Adds a new object as a value of propert p and generates a non anonymous id for it
 */
function addAnonObj($idbase, $obj, &$prop, $p, &$idmap, $cwurl, $allow_demand_id = false, $bnid = false){
	if(!isset($prop[$p]) or !is_array($prop[$p])){
		$prop[$p] = array();
	}
	if(isset($obj['@id']) && $obj['@id']){
		$bnid = $obj['@id'];
		unset($obj['@id']);
	}
	elseif($bnid && isset($obj['@id'])){
		unset($obj['@id']);
	}
	if($bnid && isset($idmap[$bnid])){
		$new_id = $idmap[$bnid];
	}
	else {
		$new_id = genid($cwurl, $bnid, $allow_demand_id);
	}
	if($bnid && $bnid != $new_id){
		$idmap[$bnid] = $new_id;
	}
	
	$prop[$p][$new_id] = $obj;
	return $new_id;
}

/*
 * expanding / compressing namespaces according to a schema
 */
function expandNamespaces(&$ldprops, $schema, $cwurl){
	if(!is_array($ldprops)) return;
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->link() && isNamespacedURL($v) && ($expanded = $schema->expand($v))){
			$nv = $expanded;
		}
		elseif($pv->valuelist()){
			$nv = array();
			foreach($v as $val){
				if(isNamespacedURL($val) && ($expanded = $schema->expand($val))){
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
				if(isNamespacedURL($id) && ($expanded = $schema->expand($id))){
					$nv[$expanded] = $obj;
					expandNamespaces($nv[$expanded], $schema, $cwurl);
				}
				else {
					$nv[$id] = $obj;
					expandNamespaces($nv[$id], $schema, $cwurl);
				}
			}
		} 
		elseif($pv->embedded()){
			expandNamespaces($v, $schema, $cwurl);
			$nv = $v;
		}
		elseif($pv->objectlist()){
			$nv = array();
			foreach($v as $one_obj){
				expandNamespaces($one_obj, $schema, $cwurl);
				$nv[] = $one_obj;
			}
		}
		else {
			$nv = $v;
		}
		if(isNamespacedURL($p) && ($expanded = $schema->expand($p))){
			unset($ldprops[$p]);
			$ldprops[$expanded] = $nv;
		}
		elseif(isNamespacedURL($p)){
			$ldprops[$p] = $nv;
		}
		else {
			$ldprops[$p] = $nv;
		}
	}
}

function getNamespaces($ldprops, $schema, $cwurl){
	$ns = getNamespaceUtilisation($ldprops, $schema, $cwurl, "all");
	$op = array();
	foreach($ns as $pre => $urls){
		$exp = $schema->getURL($pre);
		if($exp){
			$op[$pre] = $exp;
		}
		else {
			$op[$pre] = $urls[0];
		}
	}
	return $op;
}

//returns
//prefix: [urls that use it]
//unknown: [urls] is used too.
function getNamespaceUtilisation($ldprops, $schema, $cwurl, $type = "all"){
	$ns = array();
	foreach($ldprops as $p => $v){
		if($type == "predicate" or $type == "all"){
			addToNSList($ns, $p, $schema);				
		}
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->objectlist() or $pv->embeddedlist()){
			foreach($v as $i => $obj){
				if($pv->embeddedlist() and ($type == "all" or $type == "subject")){
					addToNSList($ns, $i, $schema);
				}
				$ns = array_merge_recursive($ns, getNamespaceUtilisation($obj, $schema, $cwurl, $type));
			}
		}
		elseif($pv->embedded()){
			$ns = array_merge_recursive($ns, getNamespaceUtilisation($v, $schema, $cwurl, $type));				
		}
		elseif($type == "object" or $type == 'all'){
			if($pv->valuelist() or $pv->objectliterallist()){
				foreach($v as $val){
					if($pv->valuelist()){
						addToNSList($ns, $val, $schema);
					}
					elseif(isset($val['type']) && $val['type'] == "uri"){
							addToNSList($ns, $val['value'], $schema);								
					}
				}
			}
			elseif($pv->link()){
				addToNSList($ns, $v, $schema);
			}
			elseif($pv->objectliteral()){
				if(isset($v['type']) && $v['type'] == "uri"){
					addToNSList($ns, $v['value'], $schema);
				}
			}
		}
	}
	return $ns;
}

function addPropertyToNSUtilisation(&$ns, $prop, $schema){
	$parts = deconstructURL($prop, $schema);
	if(!$parts) return false;
	if(!isset($ns[$parts[0]])){
		$ns[$parts[0]] = array("properties" => array(), "structural" => array(), "object" => array(), "subject" => array());
	}
	$p = $schema->compress($parts[1]);
	$p = $p ? $p : $parts[1];
	if(!isset($ns[$parts[0]]["properties"][$p])){
		$ns[$parts[0]]["properties"][$p] = 1;
	}
	else {
		$ns[$parts[0]]["properties"][$p]++;
	}
	return $parts;	
}

function compressTriple($s, $p, $o, $schema){
	$ss = $schema->compress($s);
	$ss = $ss ? $ss : $s;
	$sp = $schema->compress($p);
	$sp = $sp ? $sp : $p;
	$so = $schema->compress($o);
	$so = $so ? $so : $o;
	return array($ss, $sp, $so);
}

function addSubjectToNSUtilisation(&$ns, $sid, $schema){
	$parts = deconstructURL($sid, $schema);
	if(!$parts) return false;
	if(!isset($ns[$parts[0]])){
		$ns[$parts[0]] = array("properties" => array(), "structural" => array(), "object" => array(), "subject" => array());
	}
	$s = $schema->compress($parts[1]);
	$s = $s ? $s : $parts[1];
	if(isset($ns[$parts[0]]["subject"][$s])){
		$ns[$parts[0]]["subject"][$s]++;
	}
	else {
		$ns[$parts[0]]["subject"][$s] = 1;
	}
	return $parts;
}

function addObjectToNSUtilisation(&$ns, $oid, $sid, $prop, $schema){
	$parts = deconstructURL($oid, $schema);
	if(!$parts) return false;
	if(!isset($ns[$parts[0]])){
		$ns[$parts[0]] = array("properties" => array(), "structural" => array(), "object" => array(), "subject" => array());
	}
	if($schema->isStructuralPredicate($prop)){
		$ns[$parts[0]]["structural"][] = compressTriple($sid, $prop, $parts[1], $schema);
	}
	else {
		$ns[$parts[0]]["object"][] = compressTriple($sid, $prop, $parts[1], $schema);
	}
	return $parts;	
}


function getDeepNamespaceUtilisation($eid, $ldprops, $schema, $cwurl, &$ns){
	addSubjectToNSUtilisation($ns, $eid, $schema);
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $i => $obj){
				addPropertyToNSUtilisation($ns, $p, $schema);
				addObjectToNSUtilisation($ns, $i, $eid, $p, $schema);
			}
		}
		elseif($pv->embedded()){
			addPropertyToNSUtilisation($ns, $p, $schema);
			getDeepNamespaceUtilisation($eid, $v, $schema, $cwurl, $ns);
		}
		elseif($pv->objectlist()){
			foreach($v as $i => $obj){
				addPropertyToNSUtilisation($ns, $p, $schema);
				getDeepNamespaceUtilisation($eid, $obj, $schema, $cwurl, $ns);
			}
		}
		else{
			if($pv->valuelist() or $pv->objectliterallist()){
				foreach($v as $val){
					addPropertyToNSUtilisation($ns, $p, $schema);
					if($pv->valuelist()){
						addObjectToNSUtilisation($ns, $val, $eid, $p, $schema);
					}
					elseif(isset($val['type']) && $val['type'] == "uri"){
						addObjectToNSUtilisation($ns, $val['value'], $eid, $p, $schema);
					}
				}
			}
			elseif($pv->link()){
				addPropertyToNSUtilisation($ns, $p, $schema);				
				addObjectToNSUtilisation($ns, $v, $eid, $p, $schema);
			}
			elseif($pv->objectliteral()){
				addPropertyToNSUtilisation($ns, $p, $schema);				
				if(isset($v['type']) && $v['type'] == "uri"){
					addObjectToNSUtilisation($ns, $v['value'], $eid, $p, $schema);
				}
			}
		}
	}
	return $ns;
}

/*
 * Helper for above
 */
function addToNSList(&$ns, $p, $schema){
	if(!$p) return;
	if(isBlankNode($p)){
		if(!isset($ns["_"])){
			$ns["_"] = array();
		}
		$ns["_"][] = $p;
	}
	elseif(isNamespacedURL($p)){
		$pre = getNamespacePortion($p);
		if($url = $schema->expand($p)){
			if(!isset($ns[$pre])){
				$ns[$pre] = array();
			}
			$ns[$pre][] = $url;
		}
		else {
			
			if(!isset($ns["unknown"])){
				$ns["unknown"] = array();
			}
			$ns["unknown"][] = $p;
		}
	}
	elseif(isURL($p)){
		if($short = $schema->compress($p)){
			$pre = getNamespacePortion($short);
			if(!isset($ns[$pre])){
				$ns[$pre] = array();
			}
			$ns[$pre][] = $p;
		}
		else {
			if(!isset($ns["unknown"])){
				$ns["unknown"] = array();
			}
			$ns["unknown"][] = $p;
		}	
	}
}

function deconstructURL($p, $schema){
	if(is_array($p)){
		//opr($p);
		return false;
	}
	$p = $schema->mapURL($p);
	if(isBlankNode($p)){
		return array("_", $p);
	}
	elseif(isNamespacedURL($p)){
		$pre = getNamespacePortion($p);
		if($url = $schema->expand($p)){
			return array($pre, $url);
		}
		else {
			return array("unknown", $p);
		}
	}
	elseif(isURL($p)){
		if($short = $schema->compress($p)){
			$pre = getNamespacePortion($short);
			return array($pre, $p);
		}
		else {
			return array("unknown", $p);
		}
	}
	else {
		return array("unknown", $p);
	}
}


function compressNamespaces(&$ldprops, $schema, $cwurl){
	if(!is_array($ldprops)){
		return;
	} 
	foreach($ldprops as $p => $v){
		//first compress property values
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->link() && ($compressed = $schema->compress($v))){
			$nv = $compressed;
		}
		elseif($pv->valuelist()){
			$nv = array();
			foreach($v as $val){
				if(isURL($val) && ($compressed = $schema->compress($val))){
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
				compressNamespaces($obj, $schema, $cwurl);
				if(isURL($id) && ($compressed = $schema->compress($id))){
					$nv[$compressed] = $obj;
				}
				else {
					$nv[$id] = $obj;
				}
			}
		}
		else {
			$nv = $v;
		}
		//then compress properties
		if(isURL($p) && ($compressed = $schema->compress($p))){
			unset($ldprops[$p]);
			$ldprops[$compressed] = $nv;
		}
		else {
			$ldprops[$p] = $nv;
		}
	}
}

function expandNSTriples(&$trips, $schema, $cwurl, $has_gnames = false){
	
}

function compressNSTriples(&$trips, $schema, $cwurl, $has_gnames = false){
	if($has_gnames){
		foreach($trips as $gname => $data){
			compressNSTriples($trips[$gname], $schema, $cwurl);
		}
	}
	else {
		foreach($trips as $i => $v){
			$changed = array();
			if(isURL($v[0]) && ($compressed = $schema->compress($v[0]))){
				$changed[] = $compressed;
			}
			else {
				$changed[] = $v[0];
			}	
			if(isURL($v[1]) && ($compressed = $schema->compress($v[1]))){
				$changed[] = $compressed;
			}
			else {
				$changed[] = $v[1];				
			}
			if(!is_array($v[2]) && isURL($v[1]) && ($compressed = $schema->compress($v[1]))){
				$changed[] = $compressed;
			}	
			else {
				$changed[] = $v[2];				
			}
			$trips[$i] = $changed;
		}
	}			
}


//internal cw links have the form $cwurl/$id/extra or local:id/extra
function isInternalLink($v, $id, $cwurl){
	if(!$cwurl) return false;
	return (substr($v, 0, 6) == "local:" && substr($v, 6, strlen($id)) == $id) 
		|| (substr($v, 0, strlen($id)) == $id) 
		|| $cwurl.$id == substr($v, 0, strlen($cwurl.$id));
}


function findInternalMissingLinks($ldprops, $legal_vals, $id, $cwurl){
	$missing = array();
	foreach($ldprops as $prop => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->link()){
			if(isInternalLink($v, $id, $cwurl) && !in_array($v, $legal_vals)){
				$missing[] = array($id, $prop, $v);
			}
		}
		elseif($pv->valuelist()){
			foreach($v as $val){
				if(isInternalLink($val, $id, $cwurl) && !in_array($val, $legal_vals)){
					$missing[] = array($id, $prop, $val);
				}
			}
		}
		elseif($pv->embedded()){
			$id = isset($v['@id']) ? $v['@id'] : "_:";
			$missing = array_merge($missing, findInternalMissingLinks($ldprops, $legal_vals, $id));
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				$id = isset($obj['@id']) ? $obj['@id'] : "_:";
				$missing = array_merge($missing, findInternalMissingLinks($obj, $legal_vals, $id));
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				$missing = array_merge($missing, findInternalMissingLinks($obj, $legal_vals, $id));
			}
		}
	}
	return $missing;
}

/*
 * Combining lists of properties about same subject into single property list
 */
function combineProperties(&$target, $nprops){
	foreach($nprops as $prop => $val){
		if(!isset($target[$prop])){
			$target[$prop] = $val;		
		}
		else {
			
		}
	}
}


/*
 * assumes props are arranged as [graphname => [embedded object list]]
 */
function compareLDGraphs($id, $aprops, $bprops, $cwurl, $top_level = false){
	$delta = new LDDelta($cwurl);	
	foreach($aprops as $gname => $eol){
		if(count($eol) > 0){
			if(!isset($bprops[$gname]) or count($bprops[$gname]) == 0 ){
				$ndelta = new LDDelta($cwurl, $gname);
				$ndelta->del($id, $gname, $eol, !$top_level);
				$delta->addNamedGraphDelta($ndelta);
			}
			else {
				if($gname == 'meta'){
					$ndd = compareLD($id, $eol, $bprops[$gname], $cwurl, $gname);
					if($ndd->containsChanges()){
						$delta->addNamedGraphDelta($ndd, $gname);
					}
				}
				else {
					$ndd = compareEOL($id, $gname, $eol, $bprops[$gname], $cwurl, $gname);
					$delta->addNamedGraphDelta($ndd);
				}
			}
		}
	}
	foreach($bprops as $gname => $eol){
		if(count($eol) > 0){
			if(!isset($aprops[$gname]) || count($aprops[$gname]) == 0){
				$ndelta = new LDDelta($cwurl, $gname);
				$ndelta->add($id, $gname, $eol, !$top_level);
				$delta->addNamedGraphDelta($ndelta);
			}
		}		
	}
	return $delta;
}

/*
 * Does a complex comparison of two ld structures and returns a LD delta object which contains a mapping between the two
 */
function compareLD($frag_id, $orig, $upd, $cwurl, $gname = false){
	$delta = new LDDelta($cwurl, $gname);
	if(!$upd or !is_array($upd)){ 
		return $delta;
	}
	//go through updated properties to pull out properties that are not in original which we will need to add
	foreach($upd as $p => $v){
		$pupd = new LDPropertyValue($v, $cwurl);
		if(isset($orig[$p])){
			$porig = new LDPropertyValue($orig[$p], $cwurl);
			if(!$porig->isempty()){
				continue;
			}
		}
		if(!$pupd->isempty()){
			$delta->add($frag_id, $p, $v);
		}
	}
	//now we go through the original properties to pull out those which are not in the delta - we need to remove them
	foreach($orig as $p => $vold){
		$porig = new LDPropertyValue($vold, $cwurl);
		if(!isset($upd[$p]) or (is_array($upd[$p]) && count($upd[$p]) == 0)){
			if(!$porig->isempty()){//semantically equivalent, empty in original, don't do anything
				$delta->del($frag_id, $p, $vold);
			}
			continue;
		}
		$vnew = $upd[$p];
		$pupd = new LDPropertyValue($vnew, $cwurl);
		if($porig->isempty()){
			continue; //we do nothing, we've caught this above
		}
		if(!$porig->sameLDType($pupd)){
			$delta->addTypeChange($frag_id, $p, $vold, $porig, $vnew, $pupd);
			continue;
		}
		//now we know that we have the same types on both sides
		switch($porig->ldtype(true)){
			case 'scalar':
				if($vold != $vnew){
					$delta->updValue($frag_id, $p, $vold, $vnew);
				}
				break;
			case 'objectliteral':
				if(!compareObjLiterals($vold, $vnew)){
					$delta->updValue($frag_id, $p, $vold, $vnew);
				}
				break;
			case 'valuelist':
				$delta->updValueList($frag_id, $p, $vold, $vnew);
				break;
			case 'objectliterallist':
				$delta->updObjLiteralList($frag_id, $p, $vold, $vnew);
				break;
			case 'embeddedobjectlist':
				foreach($vold as $id => $obj){
					if(!isset($vnew[$id])){ //delete
						$delta->delObject($frag_id, $p, $id, $obj);
					}
					else {
						$delta->addSubDelta($frag_id, $p, $id, compareLD($id, $obj, $vnew[$id], $cwurl));
					}
				}
				foreach($vnew as $nid => $nobj){
					if(!isset($vold[$nid])){
						$delta->addObject($frag_id, $p, $nid, $nobj);
					}
				}
				break;
			case 'embedded' :
				$bnid = "_:$frag_id";
				$delta->addSubDelta($frag_id, $p, $bnid, compareLD($bnid, $vold, $vnew, $cwurl));
				break;
			case 'embeddedlist' : //hard
				$rems = array();
				$dels = array();
				foreach($vnew as $i => $nobj){
					$there = false;
					foreach($vold as $j => $oobj){
						$pdelta = compareLD("", $oobj, $nobj, $cwurl);
						if(!$pdelta->containsChanges()){
							$there = true;
							break;
						}						
					}
					if(!$there){
						$bnid = "_:$frag_id"."_$i";
						$delta->addObject($frag_id, $p, $bnid, $nobj);
					}						
				}
				foreach($vold as $i => $oobj){
					$unchanged = false;
					foreach($vnew as $i =>$nobj){
						$pdelta = compareLD("", $oobj, $nobj, $cwurl);
						if(!$pdelta->containsChanges()){
							$unchanged = true;
						}
					}
					if(!$unchanged){
						$bnid = "_:".$frag_id."_$i";
						$delta->delObject($frag_id, $p, $bnid, $obj);
					}
				}
				break;
		}
	}
	$delta->removeOverwrites();
	return $delta;
}

function compareEO($frag_id, $vold, $vnew, $cwurl, $gname = false){
	$delta = new LDDelta($cwurl, $gname);
	$delta->addNamedGraphDelta($ndd);	
}

function compareEOL($frag_id, $p, $vold, $vnew, $cwurl, $gname = false){
	$delta = new LDDelta($cwurl, $gname);
	foreach($vold as $id => $obj){
		if(!isset($vnew[$id])){
			$delta->delObject($frag_id, $p, $id, $obj, $gname);
		}
		else {
			$delta->addSubDelta($frag_id, $p, $id, compareLD($id, $obj, $vnew[$id], $cwurl));
		}
	}
	foreach($vnew as $nid => $nobj){
		if(!isset($vold[$nid])){
			$delta->addObject($frag_id, $p, $nid, $nobj, $gname);
		}
	}
	return $delta;
}	

/*
 * remove any triples where we are adding and deleting the same triple
 * (when we have list overwrites...)
 */
function removeOverwrites(&$add, &$del){
	$removed = array();
	if(is_array($del) && count($del) > 0){
		foreach($del as $i => $d){
			if(count($add) > 0){
				foreach($add as $j => $a){
					if($a[0] == $d[0] && $a[1] == $d[1]){
						$hit = false;
						if(is_array($a[2]) && is_array($d[2])){
							$subhit = true;
							foreach($a[2] as $k => $an){
								if($d[2][$k] != $an){
									$subhit = false;
								}
							}
							$hit = $subhit;
						}
						else if($a[2] == $d[2]){
							$hit = true;
						}
						if($hit){
							$removed[] = $a;
							unset($add[$j]);
							unset($del[$i]);
						}	
					}
				}
			}
		}
		//reset array indexes
		if(count($removed) > 0){
			$add = array_values($add);
			$del = array_values($del);
		}
	}
	return $removed;
}

function encodeScalar($s){
	if(isLiteral($s)){
		return array('data' => $s, "lang" => "en");
	}
	return $s;
}

function decodeScalar($s){
	if(isLiteral($s)){
		return $s;
	}
	if(isset($s['data'])){
		return $s['data'];
	}
	if(isset($s['value'])){
		return $s['value'];
	}
	return "";
}

function compareObjLiterals($a, $b){
	return $a == $b;
}

function encodeObject($o, $t){
	if($t == "literal"){
		return $o;
	}
	elseif($t == "objectliteral"){
		return json_encode($o);
	}
	else {
		return $o;
	}
	
}

function addTypes($s, $p, $o, $t){
	return array(array($s, $p, encodeObject($o, $t)));
}

function nop($s, $p, $o, $t){
	return array(array($s, $p, $o));
}

function compareTrips($a, $b){
	foreach($a as $i => $onea){
		if(!isset($b[$i])) return false;
		if(is_array($a[$i])){
			if(!is_array($b[$i])) return false;
			if(json_encode($a[$i]) != json_encode($b[$i])) return false;
		}
		else {
			if($a[$i] != $b[$i]) return false;
		}
	}
	return true;
}

/*
 * Functions for reverse engingeering embedded objects from triples
 * Not a general purpose solution!


 function buildFromTriples($triples, $root_id){
 	$objects = array();
 	foreach($triples as $t){
 		if(!isset($objects[$t[0]])){
 			$objects[$t[0]] = array($t[1] => array($t[2]));
 		}
 		elseif(!isset($objects[$t[0]][$t[1]])){
 			$objects[$t[0]][$t[1]] = array($t[2]);
 		}
 		else {
 			$objects[$t[0]][$t[1]][] = $t[2];
 		}
 	}
 	if(!isset($objects[$root_id])){
 		return $this->failure_result("Root id $root_id did not exist in the triples", 400);
 	}
 	$contents = $this->embedObjects($root_id, $objects);
 	if(count($objects) > 0){
 		return $this->failure_result("Triples contained some values that could not be embedded in an entity", 400);
 	}
 	return $contents;
 }
 
 	/**
 * Embeds expanded objects
 *
 * @param LD structure $objs array of LD objects
 * @param string $id id of current object
 * @param string $cwurl closed world
 * @return LD structure

 function embedLDObjects(&$objs, $id, $cwurl){
 	$obj = $objs[$id];
 	unset($objs[$id]);
 	foreach($obj as $prop => $vals){
 		$expandable = true;
 		foreach($vals as $val){
 			$pv = new LDPropertyValue($val, $cwurl);
 			if(!($pv->bn() || $pv->cwlink())){
 				$expandable = false;
 				continue;
 			}
 		}
	 	if($expandable){
 			foreach($vals as $val){
 				if(isset($objs[$val])){
 					$obj[$prop][$val] = embedLDObjects($objs, $val, $cwurl);
 				}
 			}
 		}
 	}
 	return $obj;
 }
 
*/


