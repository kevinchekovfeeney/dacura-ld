<?php
/*
 * The structure of json ld objects is best modeled as a normal associative array
 * This file creates functions that operate on arrays to 
 *
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

require_once "LDPropertyValue.php";

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

/*
 * Expand the structure, generate real ids for blank nodes and update any references to the blank nodes to the new ids
 */
function expandLD($idbase, &$props){
	$idmap = array();
	generateBNIDS($idbase, $props, $idmap);
	$missing_refs = updateBNReferences($props, $idmap);
	if($missing_refs === false){
		return false;
	}
	elseif(is_array($missing_refs) && count($missing_refs) > 1){
		return array("missing" => $missing_refs, "idmap" => $idmap);
	}
	return array("idmap" => $idmap);
}

/*
 * Generates ids for blank nodes and alters the structure
 * to expand embedded objects and object lists with ld structure
 */
function generateBNIDs($idbase, &$props, &$idmap, $cwurl){
	$nprops = array();
	foreach($props as $p => $v){
		//opr($v);
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embedded()){
			echo "embedded object $p\n";
			$new_id = addAnonObj($idbase, $v, $nprops, $p, $idmap, $cwurl);
			generateBNIDs($idbase, $nprops[$p][$new_id], $idmap, $cwurl);
		}
		elseif($pv->objectlist()){
			echo "object list $p\n";
			foreach($props[$p] as $obj){
				$new_id = addAnonObj($idbase, $obj, $nprops, $p, $idmap, $cwurl);
				//echo "new id $new_id\n";
				generateBNIDs($idbase, $nprops[$p][$new_id], $idmap, $cwurl);
			}
		}
		elseif($pv->embeddedlist()){
			echo "embedded list $p\n";
			foreach($props[$p] as $id => $obj){
				if(isBlankNode($id)){
					$nid = addAnonObj($idbase, $obj, $nprops, $p, $idmap, $cwurl, $id);
					generateBNIDs($idbase, $nprops[$p][$nid], $idmap, $cwurl);
				}
				else {
					generateBNIDs($idbase, $nprops[$p][$id], $idmap, $cwurl);
				}
			}
		}
		else {
			echo "not object $p\n";
			$nprops[$p] = $v;
		}
	}
	$props = $nprops;
}



/**
 * update internal references by replacing blank node values with newly generated ids..
 */
function updateBNReferences(&$props, $idmap, $cwurl){
	$unresolved = array();
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->bn()){
			if(isset($idmap[$v])){
				$props[$p] = $idmap[$v];
			}
			else {
				$unresolved[] = $v;
			}
		}
		elseif($pv->valuelist()){
			unset($props[$p]);
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
				$props[$p] = $nvals;
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				updateBNReferences($props[$p][$id], $idmap);
			}
		}
		elseif($pv->embedded() or $pv->objectlist()){
			//return $this->failure_result("Failed to map references for $p - Cannot expand blank node references on an object with anonymous nodes", 500);
		}
	}
	return $unresolved;
}

function validLD($props, $cwurl){
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->illegal()){
			return false;//$this->failure_result("Property $p has illegal value ".json_encode($v), 400);
		}
		elseif($pv->embedded()){
			return validLD($props[$p], $cwurl);
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				if(!validLD($obj, $cwurl)){
					return false;
				}
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if(!validLD($obj, $cwurl)){
					return false;
				}
			}
		}
	}
	return true;
}


function genid($idbase, $bn = false){
	if($bn == "_:candidate"){
		return "local:".$idbase."/candidate";
	}
	elseif($bn == "_:provenance"){
		return "local:".$idbase."/provenance";
	}
	elseif($bn == "_:annotation"){
		return "local:".$idbase."/annotation";
	}
	return "local:".$idbase . "/" . uniqid_base36(false);
}

function indexLD($props, &$index, $cwurl){
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if(!isset($index[$id])){
					$index[$id] = array($props[$p][$id]);
				}
				else {
					$index[$id][] = $props[$p][$id];
				}
				indexLD($obj, $index, $cwurl);
			}
		}
	}
}


function getObjectAsTurtle($id, $obj, $cwurl){
	$lines = array();
	if($obj && is_array($obj)){
		if(isset($obj["rdf:type"])){
			if(is_array($obj["rdf:type"])){
				$lines[] = array($id, "a", $obj["rdf:type"][0]);				
				array_shift($obj["rdf:type"]);
			}
			else {
				$lines[] = array($id, "a", $obj["rdf:type"]);
				unset($obj["rdf:type"]);
			}
		}	
		$sublines = array();
		foreach($obj as $p => $v){
			if(count($lines) > 0){
				$nline= array("", $p);
			}
			else {
				$nline= array($id, $p);
			}
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->literal()){
				$nline[] = '"'.$v.'"';
				$lines[] = $nline;
			}
			elseif($pv->embeddedlist()){
				$llines = 0;
				foreach($v as $oid => $eobj){
					if($llines == 0){
						if(count($lines) > 0){
							$eline= array("", $p);
						}
						else {
							$eline= array($id, $p);
						}
						$eline[] = $oid;
						$llines = 1;
					}
					else {
						$eline = array("", "", $oid);
					}
					$lines[] = $eline;
					$sublines = array_merge($sublines, getObjectAsTurtle($oid, $eobj, $cwurl));			
				}
			}
		}
		foreach($lines as $i => $line){
			if($i == count($lines) - 1){
				$lines[$i][]=".";				
			}
			elseif($lines[$i+1][0] == "" && $lines[$i+1][1] == ""){
				$lines[$i][]=",";
			}
			elseif($lines[$i+1][0] == ""){
				$lines[$i][]=";";				
			}
			else {
				$lines[$i][]=".";
			}
		}
		$lines = array_merge($lines, $sublines);		
	}
	return $lines;
}

function getObjectAsTriples($id, $obj, $cwurl){
	$triples = array();
	if($obj && is_array($obj)){
		foreach($obj as $p => $v){
			$nt = getValueAsTriples($id, $p, $v, $cwurl);
			if($nt === false){
				return false;
			}
			else {
				$triples = array_merge($triples, $nt);
			}
		}
	}
	return $triples;
}

function getValueAsTriples($id, $p, $v, $cwurl){
	$pv = new LDPropertyValue($v, $cwurl);
	$anon = 0;
	$triples = array();
	if($pv->literal()){
		$triples[] = array($id, $p, $v);
	}
	elseif($pv->valuelist()){
		foreach($v as $val){
			$triples[] = array($id, $p, $val);
		}
	}
	elseif($pv->embedded()){
		$aid = "_:".(++$anon) . "xx"; //need to generate something to allow us to triplify it
		$triples[] = array($id, $p, $aid);
		$triples = array_merge($triples, getObjectAsTriples($aid, $v, $cwurl));
	}
	elseif($pv->objectlist()){
		foreach($v as $obj){
			//see if we have an id
			$aid = "_:".(++$anon);
			$triples[] = array($id, $p, $aid);
			$triples = array_merge($triples, getObjectAsTriples($aid, $obj, $cwurl));
		}
	}
	elseif($pv->embeddedlist()){
		foreach($v as $oid => $obj){
			$triples[] = array($id, $p, $oid);
			$triples = array_merge($triples, getObjectAsTriples($oid, $obj, $cwurl));
		}
	}
	else {
		return false;
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

function getFragment($f, $props, $cwurl){
	foreach($props as $p => $v){
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

function getFragmentContext($f, $props, $cwurl){
	$paths = array();
	foreach($props as $p => $v){
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

function getFragmentInContext($f, $props, $cwurl){
	$nprops = array();
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				//$nprops[$p][$id] = array();
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
function addAnonObj($idbase, $obj, &$prop, $p, &$idmap, $cwurl, $bnid = false){
	if(!isset($prop[$p]) or !is_array($prop[$p])){
		$prop[$p] = array();
	}
	if($bnid === false && isset($obj['@id']) && $obj['@id']){
		$bnid = $obj['@id'];
		unset($obj['@id']);
	}
	if($bnid && isset($idmap[$bnid])){
		$new_id = $idmap[$bnid];
	}
	else {
		$new_id = genid($idbase, $bnid);
	}
	if($bnid){
		$idmap[$bnid] = $new_id;
	}

	$prop[$p][$new_id] = $obj;
	return $new_id;
}

