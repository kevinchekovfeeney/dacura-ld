<?php
/** 
 * The Core Dacura Linked Data Processing Functions
 *
 * The structure of json ld objects is modeled as a normal associative array
 * This file contains functions that operate on arrays in LD format 
 * 
 * Creation Date: 13/03/2015
 * @author Chekov
 * @license GPL v2
 */

require_once "LDPropertyValue.php";
require_once "LDDelta.php";

/**
 * Called to import an input LD array into an internal LD array 
 * Expands the structure of the array by placing node ids as indexes to all embedded objects, 
 * Generates real ids for blank nodes and updates any references to the blank nodes to the new ids
 * 
 * @param array $ldprops 
 * @param array $rules an array of settings which specifies which transforms to execute on the properties
 * @return boolean|array<string:string> - an array which contains a mapping 
 * from the old ids to new ids of any nodes whose ids has been changed during expansion. 
 */
function importLD(&$ldprops, $rules, $multigraph = false){
	$idmap = array();
	if(!$ldprops || count($ldprops) == 0) return $idmap;
	if($multigraph){
		foreach($ldprops as $gid => $props){
			$subs = array_keys($props);
			foreach($subs as $s){
				if(isBlankNode($s) && isset($rules['replace_blank_ids']) && $rules['replace_blank_ids']){
					$s = addAnonSubject($ldprops[$gid][$s], $ldprops[$gid], $idmap, $rules, $s);
				}
				generateBNIDS($ldprops[$gid][$s], $idmap, $rules);
			}
		}	
		if(count($idmap) > 0){
			foreach($ldprops as $gid => $props){
				$subs = array_keys($props);
				foreach($subs as $s){
					$ldprops[$gid][$s] = updateLDOReferences($ldprops[$gid][$s], $idmap, $rules);
				}
			}		
		}
	}
	else {
		$subs = array_keys($ldprops);
		foreach($subs as $s){
			if(isBlankNode($s) && isset($rules['replace_blank_ids']) && $rules['replace_blank_ids']){
				$s = addAnonSubject($ldprops[$s], $ldprops, $idmap, $rules, $s);
			}
			generateBNIDS($ldprops[$s], $idmap, $rules);		
		}
		if(count($idmap) > 0){
			$subs = array_keys($ldprops);
			foreach($subs as $s){
				$ldprops[$s] = updateLDOReferences($ldprops[$s], $idmap, $rules);
			}		
		}
	}
	return $idmap;
}

/** 
 * Generates ids for blank nodes and alters the structure
 * We do not expand the meta field and do not generate BNIDs for the top level (graphname) indices.
 * to expand embedded objects and object lists with ld structure

 * @param array $ldprops linked data object
 * @param array $idmap mapping of old ids to new ids for generated ids
 * @param array $rules rules for how the ids are generated 
 * @param boolean $top_level true if this is a top-level object (indexed by graph)
 * @return boolean true if successful
 */
function generateBNIDs(&$ldobj, &$idmap, $rules){
	$nprops = array();
	if(!is_array($ldobj)){
		return false;
	}
	foreach($ldobj as $p => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->embedded() && isset($rules['expand_embedded_objects']) && $rules['expand_embedded_objects']){
			$new_id = addAnonObj($v, $nprops, $p, $idmap, $rules);
			generateBNIDs($nprops[$p][$new_id], $idmap, $rules);				
		}
		elseif($pv->objectlist() && isset($rules['expand_embedded_objects']) && $rules['expand_embedded_objects']){
			foreach($ldobj[$p] as $obj){
				$new_id = addAnonObj($obj, $nprops, $p, $idmap, $rules);
				generateBNIDs($nprops[$p][$new_id], $idmap, $rules);
			}
		}
		elseif($pv->embeddedlist()){
			$nprops[$p] = array();
			foreach($v as $id => $obj){
				if(isBlankNode($id) && isset($rules['replace_blank_ids']) && $rules['replace_blank_ids']){
					$nid = addAnonObj($obj, $nprops, $p, $idmap, $rules, $id);
					generateBNIDs($nprops[$p][$nid], $idmap, $rules);
				}
				else {
					$nprops[$p][$id] = $obj;
					generateBNIDs($nprops[$p][$id], $idmap, $rules);						
				}
			}
		}
		elseif($pv->objectliteral() && isset($rules['regularise_object_literals']) && $rules['regularise_object_literals']){
			$nprops[$p] = $pv->regulariseObjectLiteral($v); 
		}
		elseif($pv->objectliterallist() && isset($rules['regularise_object_literals']) && $rules['regularise_object_literals']){
			$nprops[$p] = $pv->regulariseObjectLiteralList($v);
		}
		else {
			$nprops[$p] = $v;
		}
	}
	$ldobj = $nprops;
}

function makeBNIDsAddressable(&$ldprops, $cwurl){
	if(!is_array($ldprops)) return;
	foreach($ldprops as $s => $ldobj){
		if(isBlankNode($s)){
			$nid = bnToAddressable($s, $cwurl);
			$ldprops[$nid] = $ldobj;
			unset($ldprops[$s]);
			$s = $nid;
		}
		if(!isAssoc($ldobj)){
			//echo "$s is the subject but the rest is not an object";
		}
		else {
			makeLDOBNIDsAddressable($ldprops[$s], $cwurl);
		}
	}	
}

function bnToAddressable($bnid, $cwurl){
	return $cwurl."/".substr($bnid, 2);
}

function makeLDOBNIDsAddressable(&$ldobj, $cwurl){
	foreach($ldobj as $p => $v){
		if(isBlankNode($p)){
			$nid = bnToAddressable($p, $cwurl);
			$ldobj[$nid] = $v;
			unset($ldobj[$p]);
			$p = $nid;
		}
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->bn()){
			$ldobj[$p] = bnToAddressable($v, $cwurl);
		}
		elseif($pv->valuelist()){
			$nv = array();
			foreach($v as $val){
				if(isBlankNode($val)){
					$nv[] = bnToAddressable($val, $cwurl);
				}
				else {
					$nv[] = $val;
				}
			}
			$ldobj[$p] = $nv;
		}
		elseif($pv->embeddedlist()){
			makeBNIDsAddressable($ldobj[$p], $cwurl);
		}
		elseif($pv->embedded()){
			makeLDOBNIDsAddressable($ldobj[$p], $cwurl);
		}
		elseif($pv->objectlist()){
			$nv = array();
			foreach($v as $i => $one_obj){
				makeLDOBNIDsAddressable($one_obj, $cwurl);
				$nv[] = $one_obj;
			}
			$ldobj[$p] = $nv;
		}
	}
}

/**
 * update internal references by replacing references to old values with newly ids..
 *
 * Called after blank nodes have been generated -
 * updates any internal references to the old blank node id to the new node id.
 *
 * @param array $ldobj - ld object array
 * @param array $idmap - mapping from old ids to new ids
 * @param array $rules - settings for this object
 * @return array of unresolved references in the properties after all transformations
 * empty array indicates success and no unresolved links
 */
function updateLDOReferences($ldobj, $idmap, $rules){
	$unresolved = array();
	$nprops = array();
	if(!is_array($ldobj)){
		return $nprops;
	}
	foreach($ldobj as $p => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->bn()){
			if(isset($idmap[$v])){
				$nprops[$p] = $idmap[$v];
			}
			else {
				$nprops[$p] = $v;
			}
		}
		elseif($pv->valuelist()){
			$nvals = array();
			foreach($v as $val){
				if(isBlankNode($val) && isset($idmap[$val])){
					$nvals[] = $idmap[$val];
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
				$nprops[$p][$id] = updateLDOReferences($obj, $idmap, $rules);
			}
		}
		elseif($pv->embedded()){
			$nprops[$p] = updateLDOBNReferences($v, $idmap, $rules);
		}
		elseif($pv->objectlist()){
			$nprops[$p] = array();
			foreach($v as $i => $obj){
				$nprops[$p][$i] = updateLDOReferences($obj, $idmap, $rules);
			}
		}
		else {
			$nprops[$p] = $v;
		}				
	}
	return $nprops;
}

/**
 * Adds a new anonymous embedded object as a value of propert p and generates an id for it
 * 
 * @param array $obj the json object 
 * @param array $prop the property array that the object will be added to 
 * @param string $p the id of the property that the object will be added to 
 * @param array $idmap a mapping of old node ids to new node ids after the ld object was imported
 * @param array $rules settings regarding how the id will be generated
 * @param string $bnid the blanknode id that the object had in the structure of the document
 * @return string the id of the added node
 */
function addAnonObj($obj, &$prop, $p, &$idmap, $rules, $oldid = false){
	if(!isset($prop[$p]) or !is_array($prop[$p])){
		$prop[$p] = array();
	}
	$new_id = getNewBNIDForLDObj($obj, $idmap, $rules, $oldid);
	$prop[$p][$new_id] = $obj;
	if($oldid && $oldid != $new_id){
		unset($prop[$p][$oldid]);
	}
	return $new_id;
}

function addAnonSubject($obj, &$prop, &$idmap, $rules, $oldid = false){
	$new_id = getNewBNIDForLDObj($obj, $idmap, $rules, $oldid);
	$prop[$new_id] = $obj;
	if($oldid && $oldid != $new_id){
		unset($prop[$oldid]);
	}
	return $new_id;
}

function getNewBNIDForLDObj(&$obj, &$idmap, $rules, $oldid){
	$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
	if(isset($obj[$demand_id_token]) && $obj[$demand_id_token]){
		$demid = $obj[$demand_id_token];
		unset($obj[$demand_id_token]);
	}
	else {
		$demid = false;
	}
	if($oldid && isset($idmap[$oldid])){
		$new_id = $idmap[$oldid];
	}
	else {
		$new_id = "_:" .genid($demid, $rules, getPrefixedURLLocalID($oldid));
	}
	if($oldid && $oldid != $new_id){
		$idmap[$oldid] = $new_id;
	}
	return $new_id;	
}

/**
 * Generate an ID for a new LD fragment
 * 
 * Follows passed rules and requested id to generate a new node id

 * @param string $bn the requested id / existing blank node id
 * @param string $rules rules for id generation
 * @return string the id of the new node.
 */
function genid($bn = false, $rules = false, $oldid = false){
	if($bn && substr($bn, 0, 2) == "_:"){
		$bn = substr($bn, 2);
	}
	$min_id_length = isset($rules['mimimum_id_length']) ? $rules['mimimum_id_length'] : 1;
	$max_id_length = isset($rules['maximum_id_length']) ? $rules['maximum_id_length'] : 40;
	if($bn && $rules && isset($rules['allow_demand_id']) && $rules['allow_demand_id']){
		if(ctype_alnum($bn) && strlen($bn) > $min_id_length && strlen($bn) < $max_id_length ){
			return $bn;
		}					
	}
	$idgenalgorithm = $rules && isset($rules['id_generator']) ? $rules['id_generator'] : "uniqid_base36";
	return call_user_func($idgenalgorithm , isset($rules['extra_entropy']) && $rules['extra_entropy'], $oldid);
}

/**
 * Performs Basic validity check on a passed LD structure according to the passed rules
 * 
 * @param array $ldprops linked data property array
 * @param array $rules settings for validation
 * @return boolean true if the property array has a valid structure according to the passed rules
 */
function validLD($ldprops, $rules){
	$errs = array();
	if(!$ldprops or !is_array($ldprops)) return true;
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->illegal()){
			$errs[] = array($p, "Illegal value ".$pv->errmsg);
		}
		elseif($pv->embedded()){
			$errs = array_merge($errs, validLD($ldprops[$p], $rules));
		}
		elseif($pv->objectlist()){
			foreach($v as $obj){
				$errs = array_merge($errs, validLD($obj, $rules));
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $id => $obj){
				$errs = array_merge($errs, validLD($obj, $rules));
			}
		}
	}
	return $errs;
}


/**
 * Build an index of a collection of LD objects: array {id => [nodes]}
 *
 * @param array $ldprops the array of linked data objects indexed by subject
 * @param array $index the index array which will be filled by this function
 * @param array $cwurl url of the ld object that owns these assertions
 */
function indexLDProps($ldprops, &$index, $cwurl){
	foreach($ldprops as $s => $ldobj){
		if(!isset($index[$s])){
			$index[$s] = array();
		}
		$index[$s][] =& $ldprops[$s];
		indexLDO($ldprops[$s], $index, $cwurl);
	}
}

/**
 * Build an index of a LD object's structure: array {id => [nodes]}
 * 
 * @param array $ldobj linked data object
 * @param array $index the index array which will be filled by this function
 * @param array $cwurl url of the ld object that owns these assertions
 */
function indexLDO($ldobj, &$index, $cwurl){
	if(!isAssoc($ldobj)) return false;
	foreach($ldobj as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if(!isset($index[$id])){
					$index[$id] = array($ldobj[$p][$id]);
				}
				else {
					$index[$id][] = $ldobj[$p][$id];
				}
				indexLDO($ldobj[$p][$id], $index, $cwurl);
			}
		}
		elseif($pv->embedded()){
			indexLDO($ldobj[$p], $index, $cwurl);				
		}
		elseif($pv->embeddedlist()){
			foreach($v as $i=>$obj){
				indexLDO($ldobj[$p][$i], $index, $cwurl);
			}				
		}
	}
}

function getPropsFromQuads($quads){
	$ldprops = array();
	foreach($quads as $quad){
		if(!isset($ldprops[$quad[3]])){
			$ldprops[$quad[3]] = array();
		}
		if(!isset($ldprops[$quad[3]][$quad[0]])){
			$ldprops[$quad[3]][$quad[0]] = array();
		}
		if(!isset($ldprops[$quad[3]][$quad[0]][$quad[1]])){
			$ldprops[$quad[3]][$quad[0]][$quad[1]] = $quad[2];
		}
		else {
			if(!is_array($ldprops[$quad[3]][$quad[0]][$quad[1]]) || isAssoc($ldprops[$quad[3]][$quad[0]][$quad[1]])){
				$ldprops[$quad[3]][$quad[0]][$quad[1]] = array($ldprops[$quad[3]][$quad[0]][$quad[1]]);
			}
			$ldprops[$quad[3]][$quad[0]][$quad[1]][] = $quad[2];
		}
	}
	return $ldprops;	
}

function getPropsFromTriples($trips){
	$ldprops = array();
	foreach($trips as $trip){
		if(!isset($ldprops[$trip[0]])){
			$ldprops[$trip[0]] = array();
		}
		if(!isset($ldprops[$trip[0]][$trip[1]])){
			$ldprops[$trip[0]][$trip[1]] = $trip[2];
		}
		else {
			if(!is_array($ldprops[$trip[0]][$trip[1]]) || isAssoc($ldprops[$trip[0]][$trip[1]])){
				$ldprops[$trip[0]][$trip[1]] = array($ldprops[$trip[0]][$trip[1]]);
			}
			$ldprops[$trip[0]][$trip[1]][] = $trip[2];
		}
	}
	return $ldprops;
}

/**
 * transforms an ld properties array into a flat array of triples / quads / whatever the callback produces
 * 
 * @param string $id the id of the subject node
 * @param array $ldprops the ld property array
 * @param array $rules settings for the transformation
 * @param function $callback a function that will be called to transform the value into an array of triples
 * @return boolean|array an array of triples representing the property value
 */
function getLDOAsArray($id, $ldprops, $rules, $callback, $callback_args=array()){
	$props = array();
	if($ldprops && is_array($ldprops)){
		foreach($ldprops as $p => $v){
			$nt = getValueAsArray($id, $p, $v, $rules, $callback, $callback_args);
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

/**
 * Transforms a property value into the equivalent array of triples
 * @param string $id the node id of the subject node
 * @param string $p the id of the property node
 * @param mixed $v a value - could be any json 
 * @param array $rules the rules in place for the transformation
 * @param function $callback - a callback function that will be used to process the values
 * @return array - an array of triples [s,p,o] 
 */
function getValueAsArray($id, $p, $v, $rules, Callable $callback, $args = array()){
	$pv = new LDPropertyValue($v, $rules['cwurl']);
	$anon = 0;
	$triples = array();
	if($pv->literal()){
		$triples = array_merge($triples, $callback($id, $p, $v, 'literal', $args));
	}
	elseif($pv->link()){
		$triples = array_merge($triples, $callback($id, $p, $v, 'link', $args));
	}
	elseif($pv->objectliteral()){
		$triples = array_merge($triples, $callback($id, $p, $v, 'objectliteral', $args));
	}
	elseif($pv->valuelist()){
		foreach($v as $val){
			if(isLiteral($val)){
				$triples = array_merge($triples, $callback($id, $p, $val, 'literal', $args));
			}
			else {
				$triples = array_merge($triples, $callback($id, $p, $val, 'link', $args));
			}
		}
	}
	elseif($pv->objectliterallist()){
		foreach($v as $obj){
			$triples = array_merge($triples, $callback($id, $p, $obj, 'objectliteral', $args));
		}
	}
	elseif($pv->embedded()){
		$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
		$did = false;
		if(isset($v[$demand_id_token])){
			$did = $v[$demand_id_token];
		}
		$aid = genid($did, $rules);
		$triples = array_merge($triples, $callback($id, $p, $aid, 'blank', $args));
		$triples = array_merge($triples, getLDOAsArray($aid, $v, $rules, $callback, $args));
	}
	elseif($pv->objectlist()){
		foreach($v as $obj){
			$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
			$did = false;
			if(isset($obj[$demand_id_token])){
				$did = $obj[$demand_id_token];
			}
			$aid = genid($did, $rules);
			$triples = array_merge($triples, $callback($id, $p, $aid, 'blank', $args));
			$triples = array_merge($triples, getLDOAsArray($aid, $obj, $rules, $callback, $args));
		}
	}
	elseif($pv->embeddedlist()){
		$triples = getEOLAsArray($v, $rules, $callback, $args, $id, $p);
	}
	return $triples;
}

/**
 * Transforms an embedded object list id: {object} into an array of triples
 * @param array $eol embedded object list
 * @param array $rules the settings which govern the transformation
 * @param function $callback the function that will be used to flatten the values into an array
 * @param string [$frag_id] optional current fragment identifier 
 * @param string [$p] optional current property
 * @return array<triples> an array of triples 
 */
function getEOLAsArray($eol, $rules, $callback, $callback_args = array(), $frag_id = false, $p = false){
	$props = array();
	foreach($eol as $oid => $obj){
		if($frag_id && $p){
			$props = array_merge($props, $callback($frag_id, $p, $oid, "cwid", $callback_args));
		}
		$props = array_merge($props, getLDOAsArray($oid, $obj, $rules, $callback, $callback_args));
	}
	return $props;
}

/* functions used to transform triples arrays into different formats by passing different callbacks */

/**
 * Returns an ld property value as an array of typed triples (i.e. with scalars typed as per dqs)
 * @param string $id the id of the current node
 * @param string $p the current property 
 * @param mixed $v a linked data property value
 * @param array $rules - settings governing the transformation
 * @return array<triples> an array of triples [s,p,o]
 */
function getValueAsTypedTriples($id, $p, $v, $rules){
	return getValueAsArray($id, $p, $v, $rules, "addTypes");
}

/**
 * Returns an ld property value as an array of untyped triples (i.e. with scalars typed as per dqs)
 * @param string $id the id of the current node
 * @param string $p the current property 
 * @param mixed $v a linked data property value
 * @param array $rules - settings governing the transformation
 * @return array<triples> an array of triples [s,p,o]
 */
function getValueAsTriples($id, $p, $v, $rules) {
	return getValueAsArray($id, $p, $v, $rules, "nop");
}

/**
 * Returns an ld properties array as an array of typed triples (i.e. with scalars typed as per dqs)
 * @param string $id the id of the current node
 * @param array $ldprops a linked data properties array
 * @param array $rules - settings governing the transformation
 * @return array<triples> an array of triples [s,p,o]
 */
function getObjectAsTypedTriples($id, $ldprops, $rules){
	return getLDOAsArray($id, $ldprops, $rules, "addTypes");
}

/**
 * Returns an ld properties array of untyped triples 
 * @param string $id the id of the current node
 * @param array $ldprops a linked data properties array
 * @param array $rules - settings governing the transformation
 * @return array<triples> an array of triples [s,p,o]
 */
function getObjectAsTriples($id, $ldprops, $rules){
	return getLDOAsArray($id, $ldprops, $rules, "nop");
}

/**
 * Returns an ld properties array of untyped triples
 * @param string $id the id of the current node
 * @param array $ldprops a linked data properties array
 * @param array $rules - settings governing the transformation
 * @return array<triples> an array of triples [s,p,o]
 */
function getPropsAsQuads($gid, $ldprops, $rules){
	$args = array("graphid" => $gid);
	$quadify = function($s, $p, $o, $t, $args){
		return array(array($s, $p, $o, $args['graphid']));
	};
	$quads = array();	
	foreach($ldprops as $nid => $nprops){
		$nquads = getLDOAsArray($nid, $nprops, $rules, $quadify, $args);
		$quads = array_merge($quads, $nquads);
	}
	return $quads;
}

function getPropsAsTypedQuads($gid, $ldprops, $rules){
	$args = array("graphid" => $gid);
	$quadify = function($s, $p, $o, $t, $args){
		$typed = addTypes($s, $p, $o, $t);
		foreach($typed as $i => $t){
			$typed[$i][] = $args['graphid'];	
		}
		return $typed;
	};
	$quads = array();
	foreach($ldprops as $nid => $props){
		$nquads = getLDOAsArray($nid, $props, $rules, $quadify, $args);
		$quads = array_merge($quads, $nquads);
	}
	return $quads;
}



/**
 * Returns an ld embedded object list as an array of typed triples 
 * @param array $eol the embedded object list
 * @param array $rules - settings governing the transformation
 * @param array [$frag_id] - current node id
 * @param array [$p] - current property
 * @return array<triples> an array of triples [s,p,o]
 */
function getEOLAsTypedTriples($eol, $rules, $frag_id = false, $p = false){
	return getEOLAsArray($eol, $rules, "addTypes", array(), $frag_id, $p);
}

/**
 * Returns an ld embedded object list as an array of untyped triples
 * @param array $eol the embedded object list
 * @param array $rules - settings governing the transformation
 * @param array [$frag_id] - current node id
 * @param array [$p] - current property
 * @return array<triples> an array of triples [s,p,o]
 */
function getEOLAsTriples($eol, $rules, $frag_id = false, $p = false){
	return getEOLAsArray($eol, $rules, "nop", array(), $frag_id, $p);
}

/* support for accessing fragment (LD Object internal ids) ids */


/**
 * Retrieves the context of an LD Object with fragment id $f
 * @param string $f the fragment id in question
 * @param array $ldprops the ld property array to be searched for the fragment id
 * @param array $rules transformation rules that will be used to find fragment
 * @return mixed|boolean an array of paths to the fragment if found, false otherwise
 */
function getFragmentContext($f, $ldprops, $cwurl){
	$context = array();
	if(!isset($ldprops[$f])){
		return $context;
	}
	foreach($ldprops as $s => $ldobj){
		$opath = getFragmentContextInLDO($f, $ldobj, $cwurl);
		if(is_array($opath)){
			$context[$s] = $opath;
			return $context;
		}
	}
	return false;
}

function getFragmentContextInLDO($f, $ldobj, $cwurl){
	foreach($ldobj as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			$opath = getFragmentContext($f, $v, $cwurl);
			if(is_array($opath)){
				return array($p => $opath);
			}			
		}
		elseif($pv->objectlist()){
			foreach($v as $i => $obj){
				$opath = getFragmentContextInLDO($f, $obj);
				if(is_array($opath)){
					return array($p => array($i => $opath));
				}
			}
		}
		elseif($pv->embedded()){
			$opath = getFragmentContextInLDO($f, $v);
			if(is_array($opath)){
				return array($p => $opath);
			}
		}
	}
	return false;
}

/**
 * Returns a fragment, within its full context back to the document root. this allows disambiguation of nodes which shared ids
 * @param string $f the fragment id
 * @param array $ldprops ld property array to search
 * @param array $rules rules governing the property values
 * @return array full ld property path value object
 */
function getFragmentInContext($f, $ldprops, $rules){
	$nprops = array();
	foreach($ldprops as $p => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					$nprops[$p] = array();
					$nprops[$p][$id] = $obj;
					return $nprops;
				}
				else {
					$cprops = getFragmentInContext($f, $obj, $rules);
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

/**
 * Sets the value of the fragment in property array to a new value
 * @param string $f fragment id
 * @param array $dprops the ld property array that will be updated
 * @param mixed $nval the new value of the fragment id
 * @param array $rules rules governing the transformations
 * @return boolean true if successful
 */
function setFragment($f, &$dprops, $nval, $rules){
	foreach($dprops as $p => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->embeddedlist()){
			foreach($v as $id => $obj){
				if($id == $f){
					$dprops[$p][$id] = $nval;
					return true;
				}
				else {
					if(setFragment($f, $dprops[$p][$id], $nval, $rules)){
						return true;
					}
				}
			}
		}
	}
	return false;
}

/**
 * Finds any broken internal links within an ld object
 * @param array $ldprops ld properties array
 * @param array $legal_vals a list of all the valid ids in the object
 * @param string $id the current node id
 * @param array $rules rules governing transformations
 * @return array<string> a list of all the non-existant internal ids that are used
 */
 function findInternalMissingLinks($ldprops, $legal_vals, $id, $rules){
	$missing = array();
	foreach($ldprops as $prop => $v){
		$pv = new LDPropertyValue($v, $rules['cwurl']);
		if($pv->link()){
			if(isInternalLink($v, $rules['cwurl']) && !in_array($v, $legal_vals)){
				$missing[] = array($id, $prop, $v);
			}
		}
		elseif($pv->valuelist()){
			foreach($v as $val){
				if(isInternalLink($val, $rules['cwurl']) && !in_array($val, $legal_vals)){
					$missing[] = array($id, $prop, $val);
				}
			}
		}
		elseif($pv->embedded()){
			$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
			$did = false;
			if(isset($v[$demand_id_token])){
				$did = $v[$demand_id_token];
			}
			$aid = genid($did, $rules);
			$missing = array_merge($missing, findInternalMissingLinks($v, $legal_vals, $aid, $rules));
		}
		elseif($pv->objectlist()){
			$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
			foreach($v as $obj){
				$did = false;
				if(isset($obj[$demand_id_token])){
					$did = $obj[$demand_id_token];
				}
				$aid = genid($did, $rules);
				$missing = array_merge($missing, findInternalMissingLinks($obj, $legal_vals, $aid, $rules));
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $aid => $obj){
				$missing = array_merge($missing, findInternalMissingLinks($obj, $legal_vals, $aid, $rules));
			}
		}
	}
	return $missing;
}


/**
 * Is the passed value the id of a node in this document?
 * 
 * Local ids are expressed either as 
 * * cwurl/id
 * * docid:id
 * * local:id (this one is depracated)
 * 
 * @param string $v the value
 * @param string $id the local id of the object
 * @param string $cwurl the closed world url of the object
 * @return boolean true if $v is an internal id
 */
function isInternalLink($v, $cwurl){
	if(isBlankNode($v)) return true;
	elseif(!$cwurl) return false;
	elseif(isNamespacedURL($v)){
		$id = substr($cwurl, strrpos($cwurl, "/") + 1);
		if(substr($v, 0, 6) == "local:" || (substr($v, 0, strlen($id) + 1)) == $id .":"){
			return true;
		}
	}
	else {
		return $cwurl.":" == substr($v, 0, strlen($cwurl) + 1);
	}
}

/* functions for comparing between two different ld structures */

/*
 */
/**
 * compares two sets of ld graphs 
 * assumes props are arranged as [graphname => [embedded object list]]
 * @param string $id the subject id 
 * @param array $aprops the left hand side of the comparison
 * @param array $bprops the right hand side of the comparison
 * @param array $rules rules governing the comparison
 * @param boolean $top_level is this the top-level invocation?
 * @return LDDelta a ld delta object describing the differences
 */
function compareLDGraphs($id, $aprops, $bprops, $rules, $top_level = false){
	$delta = new LDDelta($rules);	
	foreach($aprops as $gname => $eol){
		if(count($eol) > 0){
			if(!isset($bprops[$gname]) or count($bprops[$gname]) == 0 ){
				$ndelta = new LDDelta($rules, $gname);
				$ndelta->del($id, $gname, $eol, !$top_level);
				$delta->addNamedGraphDelta($ndelta);
			}
			else {
				if($gname == 'meta'){
					$ndd = compareLD($id, $eol, $bprops[$gname], $rules, $gname);
					if($ndd->containsChanges()){
						$delta->addNamedGraphDelta($ndd, $gname);
					}
				}
				else {
					$ndd = compareEOL($id, $gname, $eol, $bprops[$gname], $rules, $gname);
					$delta->addNamedGraphDelta($ndd);
				}
			}
		}
	}
	foreach($bprops as $gname => $eol){
		if(count($eol) > 0){
			if(!isset($aprops[$gname]) || count($aprops[$gname]) == 0){
				$ndelta = new LDDelta($rules, $gname);
				$ndelta->add($id, $gname, $eol, !$top_level);
				$delta->addNamedGraphDelta($ndelta);
			}
		}		
	}
	return $delta;
}

/**
 * Compares two ld property arrays and reports on the differences
 * 
 * Does a complex comparison of two ld structures and returns a LD delta object which contains a mapping between the two
 * @param string $frag_id id of the current node id
 * @param array $orig the left hand side of the comparison (unchanged version)
 * @param array $upd the right hand side of the comparison (changed version)
 * @param array $rules the rules governing the comparison
 * @param string [$gname] the id of the named graph currently under investigation
 * @return LDDelta description of the differences between $orig and $upd
 */
function compareLD($frag_id, $orig, $upd, $rules, $gname = false){
	$delta = new LDDelta($rules, $gname);
	if(!$upd or !is_array($upd)){ 
		return $delta;
	}
	//go through updated properties to pull out properties that are not in original which we will need to add
	foreach($upd as $p => $v){
		$pupd = new LDPropertyValue($v, $rules['cwurl']);
		if(isset($orig[$p])){
			$porig = new LDPropertyValue($orig[$p], $rules['cwurl']);
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
		$porig = new LDPropertyValue($vold, $rules['cwurl']);
		if(!isset($upd[$p]) or (is_array($upd[$p]) && count($upd[$p]) == 0)){
			if(!$porig->isempty()){//semantically equivalent, empty in original, don't do anything
				$delta->del($frag_id, $p, $vold);
			}
			continue;
		}
		$vnew = $upd[$p];
		$pupd = new LDPropertyValue($vnew, $rules['cwurl']);
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
						$delta->addSubDelta($frag_id, $p, $id, compareLD($id, $obj, $vnew[$id], $rules));
					}
				}
				foreach($vnew as $nid => $nobj){
					if(!isset($vold[$nid])){
						$delta->addObject($frag_id, $p, $nid, $nobj);
					}
				}
				break;
			case 'embedded' :
				$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
				$did = false;
				if(isset($vold[$demand_id_token])){
					$did = $vold[$demand_id_token];
				}
				$bnid = genid($did, $rules);
				$delta->addSubDelta($frag_id, $p, $bnid, compareLD($bnid, $vold, $vnew, $rules));
				break;
			case 'embeddedlist' : //hard
				$rems = array();
				$dels = array();
				foreach($vnew as $i => $nobj){
					$there = false;
					foreach($vold as $j => $oobj){
						$pdelta = compareLD("", $oobj, $nobj, $rules);
						if(!$pdelta->containsChanges()){
							$there = true;
							break;
						}						
					}
					if(!$there){
						$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
						$did = false;
						if(isset($vold[$demand_id_token])){
							$did = $vold[$demand_id_token];
						}
						$bnid = genid($did, $rules);
						$delta->addObject($frag_id, $p, $bnid, $nobj);
					}						
				}
				foreach($vold as $i => $oobj){
					$unchanged = false;
					foreach($vnew as $i =>$nobj){
						$pdelta = compareLD("", $oobj, $nobj, $rules);
						if(!$pdelta->containsChanges()){
							$unchanged = true;
						}
					}
					if(!$unchanged){
						$demand_id_token = isset($rules['demand_id_token']) ? $rules['demand_id_token'] : "@id";
						$did = false;
						if(isset($vold[$demand_id_token])){
							$did = $vold[$demand_id_token];
						}
						$bnid = genid($did, $rules);
						$delta->delObject($frag_id, $p, $bnid, $oobj);
					}
				}
				break;
		}
	}
	$delta->removeOverwrites();
	return $delta;
}

/**
 * Compares two ld embedded objects
 *
 * @param string $frag_id id of the current node id
 * @param array $vold the left hand side of the comparison (unchanged version)
 * @param array $vnew the right hand side of the comparison (changed version)
 * @param array $rules the rules governing the comparison
 * @param string [$gname] the id of the named graph currently under investigation
 * @return LDDelta description of the differences between $vold and $vnew 
 */
function compareEO($frag_id, $vold, $vnew, $rules, $gname = false){
	$delta = new LDDelta($rules, $gname);
	$delta->addNamedGraphDelta($ndd);	
}

/**
 * Compares two ld embedded object lists
 *
 * @param string $frag_id id of the current node id
 * @param string $p id of the current property
 * @param array $vold the left hand side of the comparison (unchanged version)
 * @param array $vnew the right hand side of the comparison (changed version)
 * @param array $rules the rules governing the comparison
 * @param string [$gname] the id of the named graph currently under investigation
 * @return LDDelta description of the differences between $vold and $vnew
 */
function compareEOL($frag_id, $p, $vold, $vnew, $rules, $gname = false){
	$delta = new LDDelta($rules, $gname);
	foreach($vold as $id => $obj){
		if(!isset($vnew[$id])){
			$delta->delObject($frag_id, $p, $id, $obj, $gname);
		}
		else {
			$delta->addSubDelta($frag_id, $p, $id, compareLD($id, $obj, $vnew[$id], $rules));
		}
	}
	foreach($vnew as $nid => $nobj){
		if(!isset($vold[$nid])){
			$delta->addObject($frag_id, $p, $nid, $nobj, $gname);
		}
	}
	return $delta;
}	

/**
 * remove any triples where we are adding and deleting the same triple
 * (when we have list overwrites...)
 * 
 * @param array $add an array of triples to be added
 * @param array $del an array of triples to be deleted
 * @return array<triples> an array of the triples that were removed because they appeared in both add and del
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

/* simple functions to package triple values */

/**
 * Encodes a scalar - string literal by setting its data and lang properties
 * @param string $s the string to be encoded
 */
function encodeScalar($s){
	if(isLiteral($s)){
		return array('data' => $s, "lang" => "en");
	}
	return $s;
}

/**
 * Decoes a scalar - retrieves value from object literal 
 * @param string $s the string to be decoded
 */
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

/**
 * Compares two object literals - only considered the same if they have same properties, same values
 * @param array $a object literal array lhs
 * @param array $b object literal array rhs
 * @return boolean true if they are the same
 */
function compareObjLiterals($a, $b){
	return $a == $b;
}

/**
 * Encodes an object of a triple
 * @param mixed $o the object
 * @param string $t the type of the value
 * @return string the encoded version of the object value
 */
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

/**
 * Add types to passed triples
 * @param string $s subject
 * @param string $p predicate
 * @param string $o object
 * @param string $t type 
 * @return array [$s, $p, {$o}]
 */
function addTypes($s, $p, $o, $t){
	return array(array($s, $p, encodeObject($o, $t)));
}

/**
 * Pass triples through unaltered
 * @param string $s subject
 * @param string $p predicate
 * @param string $o object
 * @param string $t type 
 * @return array [$s, $p, $o]
 */
function nop($s, $p, $o, $t){
	return array(array($s, $p, $o));
}

/**
 * Compares two arrays of triples to see if they are identical
 * @param array $a array of triples
 * @param array $b array of triples 2
 * @return boolean true if all triples are identical
 */
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

/* import export related functions */


/**
 * Transforms an internal array as used by the easy rdf library into a form as used by dacura ld property arrays
 * @param array $easy easy-rdf formated array 
 * @return array dacura ld format array
 */
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
						if($v['type'] == "string" && !isset($v['lang'])){
							$v['lang'] = "en";
						}
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


/**
 * Transforms an ld property array into a form as used by the easy rdf library 
 * @param string $id the id of the node
 * @param array $ldprops the ld property array
 * @return array easy rdf ld format array
 */
function exportEasyRDFPHP($ldprops, $cwurl){
	$exported = array();
	$easy = unembed($ldprops, $cwurl);
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

/**
 * Remove embedded objects from their parent so that it can be exported as easyrdf
 * @param array $ldprops the property array 
 * @param string $rid the id to be unembedded
 * @param array $rules the settings that will be used
 * @return object as unembedded object 
 */
function unembed($ldprops, $cwurl = false){
	$unem = array();
	if(!is_array($ldprops)) return $unem;
	foreach($ldprops as $s => $obj){
		$unem = array_merge($unem, unembedLDO($s, $obj, $cwurl));
	}
	return $unem;
}

function unembedLDO($id, $ldo, $cwurl){
	static $i = 0;
	$unem = array($id => array());
	foreach($ldo as $p => $v){
		$unem[$id][$p] = array();
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->embeddedlist()){
			$unem = array_merge_recursive($unem, unembed($v, $cwurl));
		}
		elseif($pv->embedded()){
			$bnid = "_:bn".++$i;
			$unem[$id][$p][] = $bnid;
			$unem = array_merge_recursive($unem, unembedLDO($bnid, $v, $cwurl));
		}
		elseif($pv->objectlist()){
			foreach($v as $i => $obj){
				$bnid = "_:bn".++$i;
				$unem[$id][$p][] = $bnid;
				$unem = array_merge_recursive($unem, unembedLDO($bnid, $obj, $cwurl));
			}
		}
		elseif($pv->valuelist() or $pv->objectliterallist()) {
			$unem[$id][$p] = $v;
		}
		else {
			$unem[$id][$p][] = $v;
		}
	}
	return $unem;
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
 		return $this->failure_result("Triples contained some values that could not be embedded in an ldo", 400);
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
 
 

function getNodesWithPredicate($id, $ldprops, $preds, $rules = false){
	$trips = array();
	$bni = 0;
	foreach($ldprops as $p => $v){
		if(in_array($p, $preds)){
			$pv = new PropertyValue($v, $rules);
			if($pv->scalar() or $pv->valuelist() or $pv->objectliteral() or $pv->objectliterallist()){
				$trips[] = array($id, $p, $v);
			}
			elseif($pv->embedded()){
				$trips = array_merge_recursive($trips, getNodesWithPredicate("_:BN_".$id."_".++$bni, $v, $preds, $rules['cwurl']));
			}
			elseif($pv->objectlist()){
				foreach($v as $i => $obj){
					$trips = array_merge_recursive($trips, getNodesWithPredicate("_:BN_".$id ."_".++$bni, $obj, $preds, $rules['cwurl']));
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

	

 
*/

