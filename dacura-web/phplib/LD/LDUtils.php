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

function embedLDObjects(&$objs, $id){
	$obj = $objs[$id];
	unset($objs[$id]);
	foreach($obj as $prop => $vals){
		$expandable = true;
		foreach($vals as $val){
			$pv = new LDPropertyValue($val);
			if(!($pv->bn() || $pv->cwlink())){
				$expandable = false;
				continue;
			}
		}
		if($expandable){
			foreach($vals as $val){
				if(isset($objs[$val])){
					$obj[$prop][$val] = embedLDObjects($objs, $val);
				}
			}
		}
	}
	return $obj;
}

/*
 * Expand the structure, generate real ids for blank nodes and update any references to the blank nodes to the new ids
 */
function expandLD(&$props){
	$idmap = array();
	generateBNIDS($props, $idmap);
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
function generateBNIDs(&$props, &$idmap){
	$nprops = array();
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v);
		if($pv->embedded()){
			$new_id = addAnonObj($v, $nprops, $p, $idmap);
			generateBNIDs($nprops[$p][$new_id], $idmap);
		}
		elseif($pv->objectlist()){
			foreach($props[$p] as $obj){
				$new_id = addAnonObj($obj, $nprops, $p, $idmap);
				generateBNIDs($nprops[$p][$new_id], $idmap);
			}
		}
		elseif($pv->embeddedlist()){
			foreach($props[$p] as $id => $obj){
				if(isBlankNode($id)){
					$nid = addAnonObj($obj, $nprops, $p, $idmap, $id);
					generateBNIDs($nprops[$p][$nid], $idmap);
				}
				else {
					generateBNIDs($nprops[$p][$id], $idmap);
				}
			}
		}
		else {
			$nprops[$p] = $v;
		}
	}
	$props = $nprops;
}



/**
 * update internal references by replacing blank node values with newly generated ids..
 */
function updateBNReferences(&$props, $idma){
	$unresolved = array();
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v);
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

function validLD($props){
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v);
		if($pv->illegal()){
			return false;//$this->failure_result("Property $p has illegal value ".json_encode($v), 400);
		}
		elseif($pv->embedded()){
			return validLD($props[$p]);
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				if(!validLD($obj)){
					return false;
				}
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if(!validLD($obj)){
					return false;
				}
			}
		}
	}
	return true;
}


function genid($x){
	return uniqid_base36(true);
	static $i = 0;
	return ++$i;
}

function indexLD($props, &$index){
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if(!isset($index[$id])){
					$index[$id] = array($props[$p][$id]);
				}
				else {
					$index[$id][] = $props[$p][$id];
				}
				indexLD($obj, $index);
			}
		}
	}
}


function getObjectAsTriples($id, $obj){
	$triples = array();
	if($obj && is_array($obj)){
		foreach($obj as $p => $v){
			$nt = getValueAsTriples($id, $p, $v);
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

function getValueAsTriples($id, $p, $v){
	$pv = new LDPropertyValue($v);
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
		if(isset($v['@id'])){
			$aid = $v['@id'];
		}
		else {
			$aid = "_:".(++$anon); //need to generate something to allow us to triplify it
		}
		$triples[] = array($id, $p, $aid);
		$triples = array_merge($triples, getObjectAsTriples($aid, $v));
	}
	elseif($pv->objectlist()){
		foreach($v as $obj){
			//see if we have an id
			if(isset($obj['@id'])){
				$aid = $obj['@id'];
			}
			else {
				$aid = "_:".(++$anon);
			}
			$triples[] = array($id, $p, $aid);
			$triples = array_merge($triples, getObjectAsTriples($aid, $obj));
		}
	}
	elseif($pv->embeddedlist()){
		foreach($v as $oid => $obj){
			$triples[] = array($id, $p, $oid);
			$triples = array_merge($triples, getObjectAsTriples($oid, $obj));
		}
	}
	else {
		return false;
	}
	return $triples;
}
	
function setFragment($f, &$dprops, $nprops){
	foreach($dprops as $p => $v){
		$pv = new LDPropertyValue($v);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					$dprops[$p][$id] = $nprops;
					return true;
				}
				else {
					if(setFragment($f, $dprops[$p][$id], $nprops)){
						return true;
					}
				}
			}
		}
	}
	return false;
}

function getFragment($f, $props){
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					return $obj;
				}
				else {
					return getFragment($f, $obj);
				}
			}
		}
	}
	return false;
}

function getFragmentInContext($f, $props, $nobj){
	$nprops = array();
	foreach($props as $p => $v){
		$pv = new LDPropertyValue($v);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				//$nprops[$p][$id] = array();
				if($id == $f){
					$nprops[$p] = array();
					$nprops[$p][$id] = $nobj;
					return $nprops;
				}
				else {
					$cprops = getFragmentInContext($f, $obj, $nobj);
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
function addAnonObj($obj, &$prop, $p, &$idmap, $bnid = false){
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
		$new_id = genid();
	}
	if($bnid){
		$idmap[$bnid] = $new_id;
	}

	$prop[$p][$new_id] = $obj;
	return $new_id;
}

