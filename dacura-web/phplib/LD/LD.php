<?php
/*
 * Class representing a thing that has a linked data style associative array
 * [property => value, property => value...] (values defined in LDPropertyValue class)
 * This depends on strongly typed property ranges and (somewhat) on the allocation of ids to all nodes in the dataset.
 *
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

require_once "LDPropertyValue.php";


class LD extends DacuraObject {
	var $cwurl;//the url (id prefix) that defines the closed world of this object
	
	function __construct($cwurl){
		$this->cwurl = $cwurl;
	}

	function genid($x){
		if(!isset($this->i)){
			$this->i = 0;
		}
		return ++$this->i;
	}
	
	/*
	 * Functions for reverse engingeering embedded objects from triples
	 * Not a general purpose solution!
	 */
	function embedObjects(&$objs, $id){
		$obj = $objs[$id];
		unset($objs[$id]);
		foreach($obj as $prop => $vals){
			$expandable = true;
			foreach($vals as $val){
				$pv = new LDPropertyValue($val, $this->cwurl);
				if(!($pv->bn() || $pv->cwlink())){
					$expandable = false;
					continue;
				}
			}
			if($expandable){
				foreach($vals as $val){
					if(isset($objs[$val])){
						$obj[$prop][$val] = $this->embedObject($objs, $val);
					}
				}
			}
		}
		return $obj;
	}
	
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

	/*
	 * Expand the structure, generate real ids for blank nodes and update any references to the blank nodes to the new ids
	 */
	function expand(&$props){
		$idmap = array();
		$this->generateBNIDS($props, $idmap);
		$missing_refs = $this->updateBNReferences($props, $idmap);
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
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->embedded()){
				$new_id = $this->addAnonObj($v, $nprops, $p, $idmap);
				$this->generateBNIDs($nprops[$p][$new_id], $idmap);
			}
			elseif($pv->objectlist()){
				foreach($props[$p] as $obj){
					$new_id = $this->addAnonObj($obj, $nprops, $p, $idmap);						
					$this->generateBNIDs($nprops[$p][$new_id], $idmap);
				}
			}
			elseif($pv->embeddedlist()){
				foreach($props[$p] as $id => $obj){
					if(isBlankNode($id)){
						$nid = $this->addAnonObj($obj, $nprops, $p, $idmap, $id);
						$this->generateBNIDs($nprops[$p][$nid], $idmap);
					}
					else {
						$this->generateBNIDs($nprops[$p][$id], $idmap);	
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
	function updateBNReferences(&$props, $idmap){
		$unresolved = array();
		foreach($props as $p => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
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
					$this->updateBNReferences($props[$p][$id], $idmap);
				}
			}
			elseif($pv->embedded() or $pv->objectlist()){
				return $this->failure_result("Failed to map references for $p - Cannot expand blank node references on an object with anonymous nodes", 500);
			}
		}
		return $unresolved;
	}
	
	
	/*
	 * Adds a new object as a value of propert p and generates a non anonymous id for it
	 */
	function addAnonObj($obj, &$prop, $p, &$idmap, $bnid = false){
		if(!isset($prop[$p])){
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
			$new_id = $this->genid($bnid);
		}
		if($bnid){
			$idmap[$bnid] = $new_id;
		}
		$prop[$p][$new_id] = $obj;
		return $new_id;
	}
	
	/**
	 * Apply changes specified in props to properties in dprops
	 * Generates new ids for each blank node and returns mapping in idmap. 
	 * 
	 * @param array $props - the update properties
	 * @param array $dprops - the properties to be updated
	 * @param array $idmap - map of local ids to newly generated IDs
	 * @return boolean
	 */
	function applyUpdates($uprops, &$dprops, &$idmap){
		foreach($uprops as $prop => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()){
				return $this->failure_result($pv->errmsg, $pv->errcode);
			}
			elseif($pv->literal()){
				$dprops[$prop] = $v;				
			}
			elseif($pv->valuelist()){
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
					$this->addAnonObj($obj, $dprops, $prop, $idmap);
				}
			}
			elseif($pv->embedded()){ //new object to add to the list - give him an id and insert him
				$this->addAnonObj($v, $dprops, $prop, $idmap);
			}
			elseif($pv->embeddedlist()){
				$bnids = $pv->getbnids();//new nodes
				foreach($bnids as $bnid){
					$this->addAnonObj($v[$bnid], $dprops, $prop, $idmap, $bnid);						
				}
				$delids = $pv->getdelids();//delete nodes
				foreach($delids as $did){
					if(isset($dprops[$prop][$did])){
						unset($dprops[$prop][$did]);
					}
					else {
						return $this->failure_result("Attempted to remove non-existant property value", 404);
					}						
				}
				$update_ids = $pv->getupdates();
				foreach($update_ids as $uid){
					if(!isset($dprops[$prop][$uid])){
						return $this->failure_result("Attempted to update non-existant property value $uid", 404);						
					}
					if(!$this->applyUpdates($uprops[$prop][$uid], $dprops[$prop][$uid], $idmap)){
						return false;
					}
				}
			}
		}
		return true;
	}
	
	function validate($props){
		foreach($props as $p => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()){
				return $this->failure_result("Property $p has illegal value ".json_encode($v), 400);
			}
			elseif($pv->embedded()){
				return $this->validate($props[$p]);
			}
			elseif($pv->objectlist()){
				foreach($v as $obj){
					if(!$this->validate($obj)){
						return false;
					}
				}
			}
			elseif($pv->embeddedlist()){
				foreach($v as $id => $obj){
					if(!$this->validate($obj)){
						return false;
					}
				}
			}	
		}
		return true;
	}
	
	function findInternalMissingLinks($props, $legal_vals, $id){
		$missing = array();
		foreach($props as $prop => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($this->isDocumentLocalLink($v) && !in_array($v, $legal_vals)){
				$missing[] = array($id, $prop, $v);
			}
			elseif($pv->valuelist()){
				foreach($v as $val){
					if($this->isDocumentLocalLink($val) && !in_array($val, $legal_vals)){
						$missing[] = array($id, $prop, $val);
					}
				}
			}
			elseif($pv->embedded()){
				$id = isset($v['@id']) ? $v['@id'] : "_:";
				$missing = array_merge($missing, $this->findInternalMissingLinks($props, $legal_vals, $id));
			}
			elseif($pv->objectlist()){
				foreach($v as $obj){
					$id = isset($obj['@id']) ? $obj['@id'] : "_:";
					$missing = array_merge($missing, $this->findInternalMissingLinks($obj, $legal_vals, $id));
				}
			}
			elseif($pv->embeddedlist()){
				foreach($v as $id => $obj){
					$missing = array_merge($missing, $this->findInternalMissingLinks($obj, $legal_vals, $id));
				}
			}
		}
		return $missing;
	}
	
	function getFragment($f, $props){
		foreach($props as $p => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->embeddedlist()){
				foreach($v as $id => $obj){
					if($id == $k){
						return $obj;
					}
					else {
						return $this->getFragment($k, $obj);
					}
				}
			}
		}
		return false;
	}
	
	function buildIndex(&$props, &$index){
		foreach($props as $p => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->embeddedlist()){
				foreach($v as $id => $obj){
					if(!isset($index[$id])){
						$index[$id] =& $props[$p][$id];
						$this->buildIndex($props[$p][$id], $index);
					}
				}
			}
		}
	}

	function getObjectAsTriples($id, $obj){
		$triples = array();
		foreach($obj as $p => $v){
			$nt = $this->getValueAsTriples($id, $p, $v);
			if($nt === false){
				return false;
			}
			else {
				$triples = array_merge($triples, $nt);
			}
		}
		return $triples;
	}

	function getValueAsTriples($id, $p, $v){
		$pv = new LDPropertyValue($v, $this->cwurl);
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
			$triples = array_merge($triples, $this->getObjectAsTriples($aid, $v));
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
				$triples = array_merge($triples, $this->getObjectAsTriples($aid, $obj));
			}	
		}
		elseif($pv->embeddedlist()){
			foreach($v as $oid => $obj){
				$triples = array_merge($triples, $this->getObjectAsTriples($oid, $obj));				
			}
		}
		else {
			return false;
		}
		return $triples;
	}
	
	
	function analyseUpdate($frag_id, $orig, $upd){
		$st = array("add" => array(), "del" => array(), "upd" => array(), "forward" => array($frag_id => array()), "back" => array($frag_id => array()), "rem" => array());
		foreach($upd as $p => $v){
			if(!isset($orig[$p])){
				$st['add'] = array_merge($st['add'], $this->getValueAsTriples($frag_id, $p, $v));
				$st['forward'][$frag_id][$p] = $v;
				$st['back'][$frag_id][$p] = [];
			}
		}
		//now we go through the original properties and see which ones we need to update or delete..
		foreach($orig as $p => $v){
			if(!isset($upd[$p])){
				$st['del'] = array_merge($st['del'], $this->getValueAsTriples($frag_id, $p, $v));
				$st['back'][$frag_id][$p] = $v;		
				$st['forward'][$frag_id][$p] = array();
			}
			else { //property exists in both new and old
				$porig = new LDPropertyValue($orig[$p], $this->cwurl);
				$pupd = new LDPropertyValue($upd[$p], $this->cwurl);
				if($porig->sameLDType($pupd)){
					if($porig->literal() && $v != $upd[$p]){
						$st['upd'][] = array($frag_id, $p, $v, $upd[$p]);
						$st['forward'][$frag_id][$p] = $upd[$p];
						$st['back'][$frag_id][$p] = $v;		
					}
					elseif($porig->valuelist()){
						$change = false;
						foreach($v as $val){
							if(!in_array($val, $upd[$p])){
								$st['del'] = array($frag_id, $p, $val);
								$change = true;
							}
						}
						foreach($upd as $val2){
							if(!in_array($val2, $v)){
								$st['add'] = array($frag_id, $p, $val2);
								$change = true;
							}
						}
						if($change){
							$st['forward'][$frag_id][$p] = $upd[$p];
							$st['back'][$frag_id][$p] = $v;
						}
					}
					elseif($porig->embeddedlist()){
						foreach($v as $id => $obj){
							if(!isset($upd[$p][$id])){ //delete
								if(!isset($st['forward'][$frag_id][$p])){
									$st['forward'][$frag_id][$p] = array();	
									$st['back'][$frag_id][$p] = array();
								}
								$st['forward'][$frag_id][$p][$id] = array();
								$st['back'][$frag_id][$p][$id] = $obj;
								$st['del'] = array_merge($st['del'], $this->getObjectAsTriples($id, $obj));
							}
							else {
								$embst = $this->analyseUpdate($id, $obj, $upd[$p][$id]);
								if(!$embst){
									return false;
								}
								$this->incorporateAnalysisResults($st, $embst);
							}
						}
						foreach($upd[$p] as $id => $obj){
							if(!isset($orig[$p][$id])){
								if(!isset($st['forward'][$frag_id][$p])){
									$st['forward'][$frag_id][$p] = array();
									$st['back'][$frag_id][$p] = array();
								}
								$st['add'] = array_merge($st['add'], $this->getObjectAsTriples($id, $obj));
								$st['forward'][$frag_id][$p][$id] = $obj;
								$st['back'][$frag_id][$p][$id] = array();
							}
						}
					}
					elseif(!$porig->literal()) {
						return $this->failure_result("illegal update type", 400);
					}
				}
				else {
					$tc = $this->analyseValueTypeChange($frag_id, $p, $v, $porig, $upd[$p], $pupd, $st);
					$tc['forward'][$frag_id] = array($p => $upd[$p]);
					$tc['back'][$frag_id] = array($p => $v);
					$st['tc'][] = $tc;
				}
			}
		}
		if(count($st['forward'][$frag_id]) == 0){
			unset($st['forward'][$frag_id]);
			unset($st['back'][$frag_id]);
		}
		else {
			$st["rem"] = array_merge($st["rem"], $this->removeOverwrites($st["add"], $st["del"]));
		}
		return $st;
	}
	
	function incorporateAnalysisResults(&$container, $sub){
		//figure out if there are any changes....
		$container['add'] = array_merge($container['add'], $sub['add']);
		$container['del'] = array_merge($container['del'], $sub['del']);
		$container['upd'] = array_merge($container['upd'], $sub['upd']);
		$container['rem'] = array_merge($container['rem'], $sub['rem']);
		$container['forward'] = array_merge($container['forward'], $sub['forward']);
		$container['back'] = array_merge($container['back'], $sub['back']);
		if(isset($sub['tc']) && count($sub['tc']) > 0){
			if(!isset($container['tc'])){
				$container['tc'] = array();
			}
			$container['tc'] = array_merge($container['tc'], $sub['tc']);
		}
	}
	
	/*
	 * remove any triples where we are adding and deleting the same triple
	 * (when we have list overwrites...)
	 */
	function removeOverwrites(&$add, &$del){
		$removed = array();
		foreach($del as $i => $d){
			foreach($add as $j => $a){
				if($a[0] == $d[0] && $a[1] == $d[1] && $a[2] == $d[2]){
					$removed[] = $a;
					unset($add[$j]);
					unset($del[$i]);	
				}
			}
		}
		if(count($removed) > 0){
			$add = array_values($add);
			$del = array_values($del);
		}
		return $removed;
	}
	
	function analyseValueTypeChange($frag_id, $p, $v, $t, $v2, $t2){
		$del = $this->getValueAsTriples($frag_id, $p, $v);
		$add = $this->getValueAsTriples($frag_id, $p, $v2);
		$rem = $this->removeOverwrites($del, $add);
		return array("from" => $t->ldtype(), "to" => $t2->ldtype(), "del" => $del, "add" => $add, "rem" => $rem);
	}	
}
