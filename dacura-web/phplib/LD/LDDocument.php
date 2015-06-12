<?php

/*
 * Class representing a Linked Data Document (LD object + state) in the Dacura DB
 * This class is generic - it makes no assumptions about the content of the Linked Data Document
 * It contains functionality to build indexes and maps of documents and compare them to one another
 * The candidate class contains the mapping to system state (object type, version, etc)
 * 
 * Perhaps this is one class of indirection too much
 * 
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

include_once("LDUtils.php");

/*
 * maintains state about a particular LD object 
 * LD document has a schema
 */

class LDDocument extends DacuraObject {
	var $id;
	var $contents; //associative array conforming to dacura internal object spec
	var $index = false; //obj_id => &$obj
	var $bad_links = array(); //different types of bad links in the document
	var $idmap = array(); //blank nodes that have been mapped to new names in the document
	var $cwurl = "";
	
	function __construct($id){
		$this->id = $id;
	}
	
	function __clone(){
		$this->contents = deepArrCopy($this->contents);
		$this->index = false;
		$this->bad_links = deepArrCopy($this->bad_links);
	}

	function get_json($key = false){
		if($key){
			return json_encode($this->contents[$key]);
		}
		return json_encode($this->contents);
	}

	function getFragment($fid){
		if($this->index === false){
			$this->buildIndex();
		}
		return isset($this->index[$fid]) ? $this->index[$fid] : false;
	}

	function hasFragment($frag_id){
		if($this->index === false){
			$this->buildIndex($this->contents, $this->index);
		}
		return isset($this->index[$frag_id]);
	}

	function load($arr){
		$this->contents = $arr;
	}

	function buildIndex(){
		$this->index = array();
		indexLD($this->contents, $this->index, $this->cwurl);
	}

	function get_json_ld(){
		$ld = $this->contents;
		$ld["@id"] = $this->id;
		return $ld;
	}

	function expand(){
		$rep = expandLD($this->id, $this->contents, $this->cwurl);
		if($rep === false){
			return false;
		}
		if(isset($rep["missing"])){
			$this->bad_links['unresolved_local'] = $rep["missing"];
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
	
	function triples(){
		return getObjectAsTriples($this->id, $this->contents, $this->cwurl);
	}
	
	function turtle(){
		return getObjectAsTurtle($this->id, $this->contents, $this->cwurl);
	}
	
	function compliant(){
		//return true;
		return validLD($this->contents);
	}
	
	function isDocumentLocalLink($v){
		return (substr($v, 0, 6) == "local:" && substr($v, 6, strlen($this->id)) == $this->id) || (substr($v, 0, strlen($this->id)) == $this->id) || $this->cwurl.$this->id == substr($v, 0, strlen($this->cwurl.$this->id));
	}
	
	function findMissingLinks(){
		if($this->index === false){
			$this->buildIndex($this->contents, $this->index, $this->cwurl);
		}
		$ml = $this->findInternalMissingLinks($this->contents, array_keys($this->index), $this->id);
		$x = count($ml);
		if($x > 0){ 
			$this->bad_links['unresolved_local'] = $ml;
		}
		return $x;
	}
	
	/*
	 * Calculates the transforms necessary to get other from current
	 */
	function compare($other){
		$forward = array();
		$backward = array();
		$changes = $this->analyseUpdate($this->id, $this->contents, $other->contents, $forward, $backward);
		if($changes){
			$changes['back'] = $backward;
			$changes['forward'] = $forward;
			if(isset($changes['tc'])){
				foreach($changes['tc'] as $tc){
					$changes['back'] = array_merge($changes['back'], $tc['back'][$this->id]);
					$changes['forward'] = array_merge($changes['forward'], $tc['forward'][$this->id]);
				}
			}	
			$this->changes = $changes;
			return true;
		}
		else {
			return false;	
		}
	}
	
	/*
	 * Functions for reverse engingeering embedded objects from triples
	 * Not a general purpose solution!
	 */
	
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
	
	function update($update_obj, $is_force=false){
		//opr($update_obj);
		$this->applyUpdates($update_obj, $this->contents, $this->idmap, $is_force);
		if(count($this->idmap) > 0){
			$unresolved = updateBNReferences($this->contents, $this->idmap, $this->cwurl);
			if($unresolved === false){
				return false;
			}
			elseif(count($unresolved) > 0){
				$this->bad_links['unresolved_local'] = $unresolved;
			}
		}
		$this->buildIndex();
		return true;
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
	function applyUpdates($uprops, &$dprops, &$idmap, $id_set_allowed = false){
		foreach($uprops as $prop => $v){
			if(!is_array($dprops)){
				$dprops = array();
			}
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
					addAnonObj($obj, $dprops, $prop, $idmap, $this->cwurl);
				}
			}
			elseif($pv->embedded()){ //new object to add to the list - give him an id and insert him
				//opr($v);
				addAnonObj($v, $dprops, $prop, $idmap, $this->cwurl);
			}
			elseif($pv->embeddedlist()){
				//if(!isset($drops[$prop]) or !is_array($dprops[$prop])){
				//	$dprops[$prop] = array();
				//}
				$bnids = $pv->getbnids();//new nodes
				foreach($bnids as $bnid){
					addAnonObj($v[$bnid], $dprops, $prop, $idmap, $this->cwurl, $bnid);
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
						if($id_set_allowed){
							$dprops[$prop] = array($uid => array());
						}
						else {
							return $this->failure_result("Attempted to update non-existant property value $uid", 404);
						}
					}
					if(!$this->applyUpdates($uprops[$prop][$uid], $dprops[$prop][$uid], $idmap, $id_set_allowed)){
						return false;
					}
					if(isset($dprops[$prop][$uid]) && is_array($dprops[$prop][$uid]) and count($dprops[$prop][$uid]) == 0){
						unset($dprops[$prop][$uid]);
					}
				}
			}
		}
		if(isset($dprops[$prop]) && is_array($dprops[$prop]) && count($dprops[$prop])==0) {
			unset($dprops[$prop]);
		}
		return true;
	}
	
	function analyseUpdate($frag_id, $orig, $upd, &$forward, &$back){
		$st = array("add" => array(), "del" => array(), "upd" => array(), "rem" => array());
		foreach($upd as $p => $v){
			if(!isset($orig[$p])){
				$forward[$p] = $v;
				$back[$p] = array();
				$st['add'] = array_merge($st['add'], getValueAsTriples($frag_id, $p, $v, $this->cwurl));
			}
		}
		//now we go through the original properties and see which ones we need to update or delete..
		foreach($orig as $p => $v){
			if(!isset($upd[$p])){
				$st['del'] = array_merge($st['del'], getValueAsTriples($frag_id, $p, $v, $this->cwurl));
				$back[$p] = $v;
				$forward[$p] = array();
			}
			else { //property exists in both new and old
				$porig = new LDPropertyValue($orig[$p], $this->cwurl);
				$pupd = new LDPropertyValue($upd[$p], $this->cwurl);
				if($porig->sameLDType($pupd)){
					if($porig->literal() && $v != $upd[$p]){
						$st['upd'][] = array($frag_id, $p, $v, $upd[$p]);
						$forward[$p] = $upd[$p];
						$back[$p] = $v;
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
							$forward[$p] = $upd[$p];
							$back[$p] = $v;
						}
					}
					elseif($porig->embeddedlist()){
						foreach($v as $id => $obj){
							if(!isset($upd[$p][$id])){ //delete
								$forward[$p] = array($id => array());
								$back[$p] = array($id => $obj);
								$st['del'] = array_merge($st['del'], getObjectAsTriples($id, $obj, $this->cwurl));
							}
							else {
								$nforward = array();
								$nback = array();
								$embst = $this->analyseUpdate($id, $obj, $upd[$p][$id], $nforward, $nback);
								if(!$embst){
									return false;
								}
								if(count($nforward) > 0 or count($nback) > 0){
									if(!isset($forward[$p])){
										$forward[$p] = array();
										$back[$p] = array();
									}
									$forward[$p][$id] = $nforward;
									$back[$p][$id] = $nback;
								}
								$this->incorporateAnalysisResults($st, $embst, $p, $frag_id);
							}
						}
						foreach($upd[$p] as $id => $obj){
							if(!isset($orig[$p][$id])){
								if(!isset($forward[$p])){
									$forward[$p] = array();
									$back[$p] = array();
								}
								$st['add'] = array_merge($st['add'], getObjectAsTriples($id, $obj, $this->cwurl));
								$forward[$p][$id] = $obj;
								$back[$p][$id] = array();
							}
						}
					}
					elseif(!$porig->literal()) {
						return $this->failure_result("illegal update type", 400);
					}
				}
				else {
					$tc = $this->analyseValueTypeChange($frag_id, $p, $v, $porig, $upd[$p], $pupd);
					$tc['forward'][$frag_id] = array($p => $upd[$p]);
					$tc['back'][$frag_id] = array($p => $v);
					$st['tc'][] = $tc;
				}
			}
		}
		$st["rem"] = array_merge($st["rem"], $this->removeOverwrites($st["add"], $st["del"]));
		return $st;
	}
	
	function incorporateAnalysisResults(&$container, $sub, $p, $f){
		//figure out if there are any changes....
		$container['add'] = array_merge($container['add'], $sub['add']);
		$container['del'] = array_merge($container['del'], $sub['del']);
		$container['upd'] = array_merge($container['upd'], $sub['upd']);
		$container['rem'] = array_merge($container['rem'], $sub['rem']);
		//$container['forward'] = array_merge($container['forward'], $sub['forward']);
		//$container['back'] = array_merge($container['back'], $sub['back']);
		if(isset($sub['tc']) && count($sub['tc']) > 0){
			if(!isset($container['tc'])){
				$container['tc'] = array();
			}
			foreach($sub['tc'] as $s){
				$s['forward'] = array($f => array($p => $s['forward']));
				$s['back'] = array($f => array($p => $s['back']));
				$container['tc'][] = $s;
			}
			//$container['tc'] = array_merge($container['tc'], $sub['tc']);
		}
	}
	
	/*
	 * remove any triples where we are adding and deleting the same triple
	 * (when we have list overwrites...)
	 */
	function removeOverwrites(&$add, &$del){
		$removed = array();
		if(count($del) > 0){
			foreach($del as $i => $d){
				if(count($add) > 0){
					foreach($add as $j => $a){
						if($a[0] == $d[0] && $a[1] == $d[1] && $a[2] == $d[2]){
							$removed[] = $a;
							unset($add[$j]);
							unset($del[$i]);
						}
					}
				}
			}
			if(count($removed) > 0){
				$add = array_values($add);
				$del = array_values($del);
			}
		}
		return $removed;
	}
	
	function analyseValueTypeChange($frag_id, $p, $v, $t, $v2, $t2){
		//$st['del'] = array($frag_id, $p, $val);
		$del = $this->getValueAsTriples($frag_id, $p, $v, $this->cwurl);
		$add = $this->getValueAsTriples($frag_id, $p, $v2, $this->cwurl);
		//$this->getValueAsTriples($frag_id, $p, $v2);
		if(isset($del) && isset($add)){
			$rem = $this->removeOverwrites($del, $add);
		}
		return array("from" => $t->ldtype(), "to" => $t2->ldtype(), "del" => $del, "add" => $add, "rem" => $rem);
	}
	
}
