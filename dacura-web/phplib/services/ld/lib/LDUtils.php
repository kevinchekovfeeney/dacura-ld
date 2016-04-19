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
 * @param array $ldprops linked data array {subject: {predicate: object}}
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
				elseif(isBlankNode($s) && isset($obj[$rules['demand_id_token']]) && isset($rules['allow_demand_id']) && $rules['allow_demand_id']){
					$old = $ldprops[$s];
					$s2 = addAnonSubject($ldprops[$s], $ldprops, $idmap, $rules, $s);
					if($s2 != "_:".$obj[$rules['demand_id_token']]){
						$ldprops[$s] = $old;
						unset($ldprops[$s2]);
					}
					else {
						$s = $s2;
					}
				}				
				generateBNIDS($ldprops[$gid][$s], $idmap, $rules);
			}
		}	
		if(count($idmap) > 0){
			foreach($ldprops as $gid => $props){
				$subs = array_keys($props);
				foreach($subs as $s){
					$ldprops[$gid][$s] = updateLDOReferences($ldprops[$gid][$s], $idmap, $rules['cwurl']);
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
			elseif(isBlankNode($s) && isset($obj[$rules['demand_id_token']]) && isset($rules['allow_demand_id']) && $rules['allow_demand_id']){
				$old = $ldprops[$s];
				$s2 = addAnonSubject($ldprops[$s], $ldprops, $idmap, $rules, $s);
				if($s2 != "_:".$obj[$rules['demand_id_token']]){
					$ldprops[$s] = $old;
					unset($ldprops[$s2]);
				}
				else {
					$s = $s2;
				}
			}			
			generateBNIDS($ldprops[$s], $idmap, $rules);		
		}
		if(count($idmap) > 0){
			$subs = array_keys($ldprops);
			foreach($subs as $s){
				$ldprops[$s] = updateLDOReferences($ldprops[$s], $idmap, $rules['cwurl']);
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
				if(isset($rules['replace_blank_ids']) && $rules['replace_blank_ids'] || isset($obj[$rules['demand_id_token']])){
					$nid = addAnonObj($obj, $nprops, $p, $idmap, $rules, $id);
					generateBNIDs($nprops[$p][$nid], $idmap, $rules);
				}
				else {
					$nprops[$p][$id] = $obj;
					generateBNIDs($nprops[$p][$id], $idmap, $rules);						
				}
			}
		}
		elseif($pv->literal() && isset($rules['regularise_literals']) && $rules['regularise_literals']){
			$nprops[$p] = literalToObjectLiteral($v);	
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

/**
 * Change blank node ids so that they are url extensions cwurl/bnid to the objects that own them
 * @param array $ldprops linked data array {subject: {p:v}}
 * @param string $cwurl the url of the object owning the properties
 */
function makeBNIDsAddressable(&$ldprops, $cwurl){
	if(!is_array($ldprops)) return;
	foreach($ldprops as $s => $ldobj){
		if(isBlankNode($s)){
			$nid = bnToAddressable($s, $cwurl);
			$ldprops[$nid] = $ldobj;
			unset($ldprops[$s]);
			$s = $nid;
		}
		if(isAssoc($ldobj)){
			makeLDOBNIDsAddressable($ldprops[$s], $cwurl);
		}
	}	
}

/**
 * Change blank node ids so that they are url extensions to the objects that own them 
 * @param array $ldobj the ldobject {property: vals, ...}
 * @param string $cwurl the url of the owning object
 */
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
 * Transforms a blank node id to its addressible cwurl/bnid form
 * @param string $bnid the blank node id
 * @param string $cwurl the object url
 * @return string the amalgamated url
 */
function bnToAddressable($bnid, $cwurl){
	return $cwurl."/".substr($bnid, 2);
}

/**
 * Updates references in a linked data object to take account of any blank node mapping that may have occured
 * @param array $ldprops the ld properties to be updated
 * @param array $idmap a old->new mapping of urls that have been transformed
 * @param string $cwurl the object url
 * @param boolean $is_multi true if this is a multi-graph property array (index by graph);
 * @return array the updated property references
 */
function updateLDReferences($ldprops, $idmap, $cwurl, $is_multi = false){
	$nprops = array();
	if($is_multi){
		foreach($ldprops as $gid => $props){
			$nprops[$gid] = array();
			foreach($props as $s => $ldo){
				if(isAssoc($ldo) && $ldu = updateLDOReferences($ldo, $idmap, $cwurl)){
					$nprops[$gid][$s] = $ldu;
				}
				else {
					$nprops[$gid][$s] = $ldo;
				}
			}
		}
	}
	else {
		foreach($ldprops as $s => $ldo){
			if(isAssoc($ldo)){
				$nprops[$s] = updateLDOReferences($ldo, $idmap, $cwurl);
			}
			else {
				$nprops[$s] = $ldo;
			}
		}
	}
	return $nprops;
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
 * @return array of updated properties
 */
function updateLDOReferences($ldobj, $idmap, $cwurl){
	$nprops = array();
	if(!is_array($ldobj)){
		return $nprops;
	}
	foreach($ldobj as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
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
				$nprops[$p][$id] = updateLDOReferences($obj, $idmap, $cwurl);
			}
		}
		elseif($pv->embedded()){
			$nprops[$p] = updateLDOReferences($v, $idmap, $cwurl);
		}
		elseif($pv->objectlist()){
			$nprops[$p] = array();
			foreach($v as $i => $obj){
				$nprops[$p][$i] = updateLDOReferences($obj, $idmap, $cwurl);
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

/**
 * Adds an anonymous node as the subject of a linked data assertion / triple
 * @param array $obj the linked data object that is the contents of the subject
 * @param string $prop the predicate 
 * @param array $idmap the mapping of old to new ids in place
 * @param array $rules rules for id generation
 * @param string $oldid the old id of this node (if any)
 * @return string the new id of the subject
 */
function addAnonSubject($obj, &$prop, &$idmap, $rules, $oldid = false){
	$new_id = getNewBNIDForLDObj($obj, $idmap, $rules, $oldid);
	$prop[$new_id] = $obj;
	if($oldid && $oldid != $new_id){
		unset($prop[$oldid]);
	}
	return $new_id;
}

/**
 * Returns a new blank node id for a linked data object
 * @param array $obj the object
 * @param array $idmap the id map in place
 * @param array $rules id generation rules
 * @param string $oldid the old blank node id (if any)
 * @return string the new id
 */
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
 * @param array $rules rules for id generation
 * @param string $oldid the old blank node id of the node
 * @return string the id of the new node.
 */
function genid($bn = false, $rules = false, $oldid = false){
	if($bn && substr($bn, 0, 2) == "_:"){
		$bn = substr($bn, 2);
	}
	if($bn && !demandIDInvalid($bn, $rules)){
		return $bn;
	}
	$idgenalgorithm = $rules && isset($rules['id_generator']) ? $rules['id_generator'] : "uniqid_base36";
	return call_user_func($idgenalgorithm , isset($rules['extra_entropy']) && $rules['extra_entropy'], $oldid);
}

/**
 * Returns the reason that a proposed demand id is deemed invalid
 * @param string $bn the proposed blank node id
 * @param array $rules rules for ids
 * @return string|boolean false means the id is valid, otherwise a string describing the problem is returned
 */
function demandIDInvalid($bn, $rules){
	if(!($rules && isset($rules['allow_demand_id']) && $rules['allow_demand_id'])){
		return "Configuration does not allow specification of entity ids by client";
	}
	$min_id_length = isset($rules['mimimum_id_length']) ? $rules['mimimum_id_length'] : 1;
	$max_id_length = isset($rules['maximum_id_length']) ? $rules['maximum_id_length'] : 40;
	if(!ctype_alnum($bn) && strlen($bn) < $max_id_length ){
		return "ID $bn contains invalid characters - only alphanumerics are allowed";
	}
	if(strlen($bn) < $min_id_length){
		return "ID $bn is too short. IDs must be at least $min_id_length characters long";
	}
	if(strlen($bn) > $max_id_length){
		return "ID $bn is too long. IDs must be no more than $max_id_length characters long";
	}
	return false;
	
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

/**
 * Turns a set of quads into a hierarchical {gid:{sid:{pid:{oid}}}} form  
 * @param array $quads
 * @return array hierarchical form of quads
 */
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

/**
 * Turns a set of triples into a hierarchical {sid:{pid:{oid}}} form  
 * @param array $trips
 * @return hierarchical ld property array
 */
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
 * @param array $cwurl url of the ld object that owns these assertions
 * @param function $callback a function that will be called to transform the value into an array of triples
 * @param array $callback_args array of arguments that will be passed to callback function
 * @return boolean|array an array of triples representing the property value
 */
function getLDOAsArray($id, $ldprops, $cwurl, $callback, $callback_args=array()){
	$props = array();
	if($ldprops && is_array($ldprops)){
		foreach($ldprops as $p => $v){
			$nt = getValueAsArray($id, $p, $v, $cwurl, $callback, $callback_args);
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
 * @param array $cwurl url of the ld object that owns these assertions
 * @param function $callback - a callback function that will be used to process the values
 * @return array - an array of triples [s,p,o] 
 */
function getValueAsArray($id, $p, $v, $cwurl, Callable $callback, $args = array()){
	$pv = new LDPropertyValue($v, $cwurl);
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
		$aid = "_:embedded";
		$triples = array_merge($triples, $callback($id, $p, $aid, 'blank', $args));
		$triples = array_merge($triples, getLDOAsArray($aid, $v, $cwurl, $callback, $args));
	}
	elseif($pv->objectlist()){
		foreach($v as $oid => $obj){
			$aid = "_:embeddedlist".$oid;
			$triples = array_merge($triples, $callback($id, $p, $aid, 'blank', $args));
			$triples = array_merge($triples, getLDOAsArray($aid, $obj, $cwurl, $callback, $args));
		}
	}
	elseif($pv->embeddedlist()){
		$triples = getEOLAsArray($v, $cwurl, $callback, $args, $id, $p);
	}
	return $triples;
}

/**
 * Transforms an embedded object list id: {object} into an array of triples
 * @param array $eol embedded object list
 * @param array $cwurl url of the ld object that owns these assertions
 * @param function $callback the function that will be used to flatten the values into an array
 * @param string [$frag_id] optional current fragment identifier 
 * @param string [$p] optional current property
 * @return array<triples> an array of triples 
 */
function getEOLAsArray($eol, $cwurl, $callback, $callback_args = array(), $frag_id = false, $p = false){
	$props = array();
	foreach($eol as $oid => $obj){
		if($frag_id && $p){
			$props = array_merge($props, $callback($frag_id, $p, $oid, "cwid", $callback_args));
		}
		$props = array_merge($props, getLDOAsArray($oid, $obj, $cwurl, $callback, $callback_args));
	}
	return $props;
}

/* functions used to transform triples arrays into different formats by passing different callbacks */

/**
 * Returns an ld property value as an array of typed triples (i.e. with scalars typed as per dqs)
 * @param string $id the id of the current node
 * @param string $p the current property 
 * @param mixed $v a linked data property value
 * @param array $cwurl url of the ld object that owns these assertions
 * @return array<triples> an array of triples [s,p,o]
 */
function getValueAsTypedTriples($id, $p, $v, $cwurl){
	return getValueAsArray($id, $p, $v, $cwurl, "addTypes");
}

/**
 * Returns an ld property value as an array of untyped triples (i.e. with scalars typed as per dqs)
 * @param string $id the id of the current node
 * @param string $p the current property 
 * @param mixed $v a linked data property value
 * @param array $cwurl url of the ld object that owns these assertions
 * @return array<triples> an array of triples [s,p,o]
 */
function getValueAsTriples($id, $p, $v, $cwurl) {
	return getValueAsArray($id, $p, $v, $cwurl, "nop");
}

/**
 * Returns an ld properties array as an array of typed triples (i.e. with scalars typed as per dqs)
 * @param string $id the id of the current node
 * @param array $ldprops a linked data properties array
 * @param array $cwurl url of the ld object that owns these assertions
 * @return array<triples> an array of triples [s,p,o]
 */
function getObjectAsTypedTriples($id, $ldprops, $cwurl){
	return getLDOAsArray($id, $ldprops, $cwurl, "addTypes");
}

/**
 * Returns an ld properties array of untyped triples 
 * @param string $id the id of the current node
 * @param array $ldprops a linked data properties array
 * @param array $cwurl url of the ld object that owns these assertions
 * @return array<triples> an array of triples [s,p,o]
 */
function getObjectAsTriples($id, $ldprops, $cwurl){
	return getLDOAsArray($id, $ldprops, $cwurl, "nop");
}

/**
 * Returns an ld properties array of untyped triples
 * @param string $gid the id of the graph
 * @param array $ldprops a linked data properties array
 * @param array $cwurl url of the ld object that owns these assertions
 * @return array<triples> an array of triples [s,p,o]
 */
function getPropsAsQuads($gid, $ldprops, $cwurl){
	$args = array("graphid" => $gid);
	$quadify = function($s, $p, $o, $t, $args){
		return array(array($s, $p, $o, $args['graphid']));
	};
	$quads = array();	
	foreach($ldprops as $nid => $nprops){
		$nquads = getLDOAsArray($nid, $nprops, $cwurl, $quadify, $args);
		$quads = array_merge($quads, $nquads);
	}
	return $quads;
}

/**
 * Translates a ld hierarchical property array into typed quads
 * @param string $gid the graph id
 * @param array $ldprops the props to translate
 * @param string $cwurl the url of the object that owns the props
 * @return array array of typed quads
 */
function getPropsAsTypedQuads($gid, $ldprops, $cwurl){
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
		$nquads = getLDOAsArray($nid, $props, $cwurl, $quadify, $args);
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
function getEOLAsTypedTriples($eol, $cwurl, $frag_id = false, $p = false){
	return getEOLAsArray($eol, $cwurl, "addTypes", array(), $frag_id, $p);
}

/**
 * Returns an ld embedded object list as an array of untyped triples
 * @param array $eol the embedded object list
 * @param array $rules - settings governing the transformation
 * @param array [$frag_id] - current node id
 * @param array [$p] - current property
 * @return array<triples> an array of triples [s,p,o]
 */
function getEOLAsTriples($eol, $cwurl, $frag_id = false, $p = false){
	return getEOLAsArray($eol, $cwurl, "nop", array(), $frag_id, $p);
}

/* support for accessing fragment (LD Object internal ids) ids */


/**
 * Retrieves the context of an LD Object with fragment id $f
 * @param string $f the fragment id in question
 * @param array $ldprops the ld property array to be searched for the fragment id
 * @param array $rules transformation rules that will be used to find fragment
 * @return mixed|boolean an array of paths to the fragment if found, false otherwise
 */
function getFragmentContext($f, $ldprops, $cwurl, $frag = false){
	$context = array();
	if(!isset($ldprops[$f])){
		return false;
	}
	foreach($ldprops as $s => $ldobj){
		if($f == $s){
			return array($s => $frag);
		}
		$opath = getFragmentContextInLDO($f, $ldobj, $cwurl);
		if(is_array($opath)){
			$context[$s] = $opath;
			return $context;
		}
	}
	return false;
}

/**
 * Find the context of a fragment within a ldo {property:object} object
 * @param string $f fragment
 * @param array $ldobj property ldo array
 * @param string $cwurl the url of the owning object 
 * @return array representing the path to the context
 */
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
 * @param string $cwurl the url of the owning object 
 * @return array full ld property path value object
 */
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

/**
 * Sets the value of the fragment in property array to a new value
 * @param string $f fragment id
 * @param array $dprops the ld property array that will be updated
 * @param mixed $nval the new value of the fragment id
 * @param string $cwurl the url of the owning object 
 * @return boolean true if successful
 */
function setFragment($f, &$dprops, $nval, $cwurl){
	foreach($dprops as $s => $ldo){
		if($s == $f){
			$dprops[$s] = $nval;
			return true;
		}
		foreach($ldo as $p => $v){
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->embeddedlist()){
				if(setFragment($f, $dprops[$s][$p], $nval, $cwurl)){
					return true;
				}
			}
		}
	}
	return false;
}

/** 
 * Set the value of a particular fragment's predicate to the passed value
 * @param string $f fragment id
 * @param string $p predicate
 * @param array $dprops the ld properties to be updated
 * @param mixed $nval the new value of the predicate
 * @param string $cwurl the url of the owning object
 * @return boolean true if success
 */
function setFragmentPredicate($f, $p, &$dprops, $nval, $cwurl){
	foreach($dprops as $s => $ldo){
		if($f == $s){
			$dprops[$f][$p] = $nval;
			return true;			
		}
		foreach($ldo as $p => $v){
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->embeddedlist()){
				if(setFragmentPredicate($f, $p, $dprops[$p][$id], $nval, $cwurl)){
					return true;
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
 * @param string $cwurl the url of the owning object
 * @return array<string> a list of all the non-existant internal ids that are used
 */
 function findInternalMissingLinks($ldprops, $legal_vals, $id, $cwurl){
	$missing = array();
	foreach($ldprops as $prop => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->link()){
			if(isInternalLink($v, $cwurl) && !in_array($v, $legal_vals)){
				$missing[] = array($id, $prop, $v);
			}
		}
		elseif($pv->valuelist()){
			foreach($v as $val){
				if(isInternalLink($val, $cwurl) && !in_array($val, $legal_vals)){
					$missing[] = array($id, $prop, $val);
				}
			}
		}
		elseif($pv->embedded()){
			$missing = array_merge($missing, findInternalMissingLinks($v, $legal_vals, "embedded", $cwurl));
		}
		elseif($pv->objectlist()){
			foreach($v as $aid => $obj){
				$missing = array_merge($missing, findInternalMissingLinks($obj, $legal_vals, $aid, $cwurl));
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $aid => $obj){
				$missing = array_merge($missing, findInternalMissingLinks($obj, $legal_vals, $aid, $cwurl));
			}
		}
	}
	return $missing;
}

/**
 * Is the passed value the id of a node in this document?
 * 
 * Local ids are expressed either as 
 * * cwurl/fid
 * * _:fid 
 * * id:fid
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
		if((substr($v, 0, strlen($id) + 1)) == $id .":"){
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
 * @param array $aprops the left hand side of the comparison
 * @param array $bprops the right hand side of the comparison
 * @param string $cwurl the closed world url of the object
 * @return LDDelta a ld delta object describing the differences
 */
function compareLDGraphs($aprops, $bprops, $cwurl){
	$delta = new MultiGraphLDDelta($cwurl);	
	if($aprops && is_array($aprops)){
		foreach($aprops as $gname => $eol){
			if(count($eol) > 0){
				if(!isset($bprops[$gname]) or count($bprops[$gname]) == 0 ){
					$ndelta = new LDDelta($cwurl, $gname);
					$ndelta->del(false, $gname, $eol, false);
					$delta->addNamedGraphDelta($ndelta);
				}
				else {
					$ndd = compareEOL(false, $gname, $eol, $bprops[$gname], $cwurl, $gname);
					$delta->addNamedGraphDelta($ndd);
				}
			}
		}
	}
	if($bprops && is_array($bprops)){
		foreach($bprops as $gname => $eol){
			if(count($eol) > 0){
				if(!isset($aprops[$gname]) || count($aprops[$gname]) == 0){
					$ndelta = new LDDelta($cwurl, $gname);
					$ndelta->add(false, $gname, $eol, false);
					$delta->addNamedGraphDelta($ndelta);
				}
			}		
		}
	}
	return $delta;
}

/**
 * Compares two LD property sets together
 * @param array $aprops ld properties array
 * @param array $bprops second array for comparison
 * @param string $cwurl object url
 * @param string $gname the named graph url that this comparison refers to
 * @return LDDelta
 */
function compareLDGraph($aprops, $bprops, $cwurl, $gname = false){
	return compareEOL(false, false, $aprops, $bprops, $cwurl, $gname);
}

/**
 * Compares two ld object arrays and reports on the differences
 * 
 * Does a complex comparison of two ld structures and returns a LD delta object which contains a mapping between the two
 * @param string $frag_id id of the current node id
 * @param array $orig the left hand side of the comparison (unchanged version)
 * @param array $upd the right hand side of the comparison (changed version)
 * @param string $cwurl object url
 * @param string [$gname] the id of the named graph - only set when this is the top level delta in the named graph
 * @return LDDelta description of the differences between $orig and $upd
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
						$delta->addSubDelta($p, $id, compareLD($id, $obj, $vnew[$id], $cwurl));
					}
				}
				foreach($vnew as $nid => $nobj){
					if(!isset($vold[$nid])){
						$delta->addObject($frag_id, $p, $nid, $nobj);
					}
				}
				break;
			case 'embedded' :
				$bnid = "_:embedded";
				$delta->addSubDelta($p, $bnid, compareLD($bnid, $vold, $vnew, $cwurl));
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
						$bnid = "_:embeddedlist".$i;
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
						$bnid = "_:embeddedlistO".$i;
						$delta->delObject($frag_id, $p, $bnid, $oobj);
					}
				}
				break;
			case 'complex' :
				$delta->failure_result("Fragment $frag_id has complex (non ld) format - delta ignores this", 400);
			break;
		}
	}
	$delta->removeOverwrites();
	return $delta;
}

/**
 * Compares two fragments of ordinary (non-ld) json objects to get a delta
 * @param string $frag_id the id of the fragment
 * @param array $jo the original json object
 * @param array $ju the updated json object 
 * @param string $cwurl the url of the object
 * @param string $gid the graph id of the delta
 * @return LDDelta
 */
function compareJSON($frag_id, $jo, $ju, $cwurl, $gid = false){
	$delta = new LDDelta($cwurl, $gid);
	foreach($ju as $p => $v){
		if(!isset($jo[$p])){
			$delta->add($frag_id, $p, $v);				
		}
	}
	foreach($jo as $p => $v){
		if(!isset($ju[$p])){
			$delta->del($frag_id, $p, $v);
		}
		else {
			if($ju[$p] == $v){
				continue;
			}
			if(isAssoc($ju[$p]) && isAssoc($v)){
				$xv = compareJSON($p, $v, $ju[$p], $cwurl);
				$delta->addSubDelta(false, $p, $xv);//add to object root - not ld structure so property is set to false	
			}
			else {
				$delta->updValue($frag_id, $p, $v, $ju[$p]);				
			}
		}		
	}
	return $delta;
}

/**
 * Compares two ld embedded object lists
 *
 * @param string $frag_id id of the current node id
 * @param string $p id of the current property
 * @param array $vold the left hand side of the comparison (unchanged version)
 * @param array $vnew the right hand side of the comparison (changed version)
 * @param string $cwurl the url of the object
 * @param string [$gname] the id of the named graph currently under investigation
 * @return LDDelta description of the differences between $vold and $vnew
 */
function compareEOL($frag_id, $p, $vold, $vnew, $cwurl, $gname = false){
	$delta = new LDDelta($cwurl, $gname);
	foreach($vold as $id => $obj){
		if(!isset($vnew[$id])){
			$delta->delObject($frag_id, $p, $id, $obj);
		}
		else {
			$delta->addSubDelta($p, $id, compareLD($id, $obj, $vnew[$id], $cwurl));
		}
	}
	foreach($vnew as $nid => $nobj){
		if(!isset($vold[$nid])){
			$delta->addObject($frag_id, $p, $nid, $nobj);
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
	if($t == "objectliteral"){
		if(isset($o['type']) && $o['type'] == "string"){
			$o['lang'] = "en";
			unset($o['type']);
		}
	}
	return $o;
}

/**
 * Transforms a simple literal into an object literal with a type
 * @param unknown $l literal 
 * @return object literal {type:...." data: ..."}
 */
function literalToObjectLiteral($l){
	$obj = array("data" => $l);
	if(is_bool($l)){
		$obj['type'] = "xsd:boolean";
	}
	elseif(is_integer($l)){
		$obj['type'] = "xsd:integer";
	}
	elseif(is_numeric($l)){
		$obj['type'] = "xsd:float";		
	}
	else {
		$obj['lang'] = "en";
	}
	return $obj;
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

function addgname(&$trip, $gname){
	$trip[] = $gname;
}

function quadify($trips, $gname){
	array_walk($trips, "addgname", $gname);
	return $trips;
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
 * @param array $ldprops the ld property array
 * @param string $cwurl the url of the object
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
 * @param string $cwurl the url of the object
 * @return object as unembedded object 
 */
function unembed($ldprops, $cwurl = false){
	$unem = array();
	if(!is_array($ldprops)) return $unem;
	foreach($ldprops as $s => $obj){
		if(isAssoc($obj)){
			$unem = array_merge($unem, unembedLDO($s, $obj, $cwurl));
		}
	}
	return $unem;
}

/**
 * Remove ldo from its id 
 * @param string $id the id that the ldo is attached to 
 * @param array $ldo ldo property object
 * @param string $cwurl url of the owning ldo
 * @return array an array of unembedded objects indexed by their ids
 */
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

/**
 * Re-embed blank nodes in the root context into the objects that refer to them
 * @param array $ldprops the ld properties to be transformed
 * @param string $cwurl the object's url
 */
function reembedBNodes(&$ldprops, $cwurl){
	$bnids = array();
	foreach($ldprops as $bnid => $ldo){
		if(isBlankNode($bnid)){
			$bnids[] = $bnid;
		}
	}
	//return;
	foreach($bnids as $bnid){
		foreach(array_keys($ldprops) as $nid){
			if($bnid == $nid) continue;
			echo "embedding $bnid in $nid";
			if(embedLDO($bnid, $ldprops[$bnid], $ldprops[$nid], $cwurl)){
				unset($ldprops[$bnid]);
				break;
			}
		}
	}
}

/**
 * Embed LDO property array in ld properties array
 * @param string $id id of the node to be embedded
 * @param array $bnode the contents of the blank node
 * @param array $ldo the ldo properties being updated
 * @param string $cwurl the url of the object that owns the properties
 * @return boolean true if successfully embedded
 */
function embedLDO($id, $bnode, &$ldo, $cwurl){
	if($id == $ldo){
		$ldo = $bnode;
		return true;
	}
	foreach($ldo as $p => $v){
		$pv = new LDPropertyValue($v, $cwurl);
		if($pv->link() && $v == $id){
			$ldo[$p] = array($id => $bnode);
			return true;
		}
		elseif($pv->valuelist()) {
			foreach($v as $xurl){
				if(isURL($xurl) && $xurl == $id){
					$ldo[$p] = array($id => $bnode);
					foreach($v as $yurl){
						$ldo[$yrl] = array();
					}
					return true;
				}
			}
		}
		elseif($pv->embeddedlist()){
			foreach($v as $eid => $eldo){
				if($eid == $id){
					$ldo[$p][$id] = array_merge($ldo[$p][$id], $bnode);
					return true;
				}
				if(embedLDO($id, $bnode, $ldo[$p][$eid], $collapse)){
					return true;
				}
			}
		}
		elseif($pv->embedded()){
			return embedLDO($id, $bnode, $ldo[$p], $collapse);
		}
		elseif($pv->objectlist()){
			foreach($v as $i => $obj){
				if(embedLDO($id, $bnode, $ldo[$p][$i], $collapse)){
					return true;
				}
			}
		}
	}
	return false;
}

/**
 * Merges the values of predicates into a proper linked data structure
 * @param unknown $v1 the value of predicate in ld 1
 * @param unknown $v2 the value of predicate in ld 2
 * @return Ambigous <multitype:unknown , unknown>
 */
function mergePredicateValues($v1, $v2){
	$merged = array();
	if(is_array($v1) && !isAssoc($v1) && $v1){
		if(is_array($v2) && !isAssoc($v2)){
			$merged = array_merge($v1, $v2);
		}
		else {
			$merged = $v1;
			if($v2){
				$merged[] = $v2;
			}
		}
	}
	elseif(is_array($v2) && !isAssoc($v2) && $v2){
		$merged = $v2;
		if($v1){
			$merged[] = $v1;
		}
	}
	elseif($v1 && $v2){
		$merged = array($v1, $v2);
	}
	elseif($v1){
		$merged = $v1;
	}
	else {
		$merged = $v2;
	}
	if(is_array($merged) && count($merged) == 1){
		$merged = $merged[0];
	}
	return $merged;
}



