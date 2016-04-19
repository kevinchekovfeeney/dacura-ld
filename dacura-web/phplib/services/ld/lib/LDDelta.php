<?php
/**
 * Class representing a delta between two linked data objects
 * 
 * Instances of this class are produced by comparisons between linked data objects ldutils -> compareLD()
 * This class is just a place to store the results of a comparison operation between two ldos
 * In all cases, the delta describes the changes needed to transform a into b (when a->compare(b))
 * 
 * @author Chekov
 * @license GPL V2
 *
 */
class LDDelta extends DacuraObject {
	/** @var string the url of the object being compared */
	var $cwurl;
	/** @var string the graph id of the comparison - ld delta objects can be particular to a graph in multi-graph objects */
	var $gname;
	/** @var boolean is the delta a multi-graph comparison - i.e. are the results indexed by graph */
	/*variables below hold state describing the differences */
	/** @var array Update Request Expression in LDOUpdate format - the forward change */ 
	var $forward = array(); //
	/** @var array undo update request expression in LDOUpdate format  */
	var $backward = array(); 
	/** @var array triples added to subject to transform it into object (indexed by graph id in multi-graph setups) */
	var $triples_added = array();
	/** @var array triples removed from subject to transform it into object (indexed by graph id in multi-graph setups) */
	var $triples_removed = array();
	/** @var triples that have been cancelled out by a remove and an add. state retained fyi */
	var $triples_overwritten = array();
	/** @var records instances of different json structures between two ldos */
	var $type_changes = array();
		
	/**
	 * Sets up the url of the object and the specific graph id that it applies to (if any)
	 * 
	 * @param string $cwurl the url of the object
	 * @param string $gname the url/id of the graph that it applies to - only set for top level graph deltas
	 */
	function __construct($cwurl, $gname = false){
		$this->cwurl = $cwurl;
		$this->gname = $gname;
	}
	
	/**
	 * Is this a multigraph comparison?
	 * @return boolean true if there are multiple graphs involved in the comparison
	 */
	function is_multigraph(){
		return false;
	}
	
	/**
	 * Flips the delta so that it appears in reverse - useful for rollbacks
	 */
	function flip(){
		$x = $this->triples_added;
		$this->triples_added = $this->triples_removed;
		$this->triples_removed = $x;
		$x = $this->forward;
		$this->forward = $this->backward;
	}

	/**
	 * Indicate that a ld fragment has been added to b in comparison to a
	 * @param string $frag_id the parent fragment id
	 * @param string $p the predicate that points to the added fragment
	 * @param mixed $v the added fragment value
	 */
	function add($frag_id, $p, $v){
		$this->forward[$p] = $v;
		$this->backward[$p] = array();
		$trips = getValueAsTypedTriples($frag_id, $p, $v, $this->cwurl);
		$this->triples_added = array_merge($this->triples_added, $trips);
	}
	
	/** 
	 * Indicate that a ld fragment has been removed from a to get to b
	 * @param string $frag_id the parent fragment id
	 * @param string $p the predicate that points to the fragment 
	 * @param array $v the ld fragment that has been removed
	 */
	function del($frag_id, $p, $v){
		$this->backward[$p] = $v;
		$this->forward[$p] = array();
		$trips = getValueAsTypedTriples($frag_id, $p, $v, $this->cwurl);
		$this->removeTriples($trips);
	}
	
	/**
	 * Indicate that an ldo {p: o, p2: o2} must be added to a to get to b
	 * @param string $frag_id the parent fragment id
	 * @param string $p the predicate
	 * @param string $id the bn id of the new embedded object
	 * @param array $obj the ldo to be added
	 */
	function addObject($frag_id, $p, $id, $obj){
		if($p === false){
			if(!isset($this->forward)){
				$this->forward = array();
				$this->backward = array();
			}
			$this->forward[$id] = $obj;
			$this->backward[$id] = array();
		}
		else {
			if(!isset($this->forward[$p])){
				$this->forward[$p] = array();
				$this->backward[$p] = array();
			}
			$this->forward[$p][$id] = $obj;
			$this->backward[$p][$id] = array();
		}
		//create triples that connect this embedded object with its parent
		$trips = ($p === false || $frag_id === false) ? array() : array(array($frag_id, $p, $id));
		$trips = array_merge($trips, getObjectAsTypedTriples($id, $obj, $this->cwurl));
		$this->addTriples($trips);
	}
	
	/**
	 * Indicates that an ldo has been removed from a to get to b
	 * @param string $frag_id the parent fragment id
	 * @param string $p the predicate
	 * @param string $id the bn id of the removed embedded object
	 * @param array $obj the ldo to be removed
	 */
	function delObject($frag_id, $p, $id, $obj){
		if($p === false){
			if(!isset($this->forward)){
				$this->forward = array();
				$this->backward = array();
			}
			$this->backward[$id] = $obj;
			$this->forward[$id] = array();
		}
		else {
			if(!isset($this->forward[$p])){
				$this->forward[$p] = array();
				$this->backward[$p] = array();
			}
			$this->backward[$p][$id] = $obj;
			$this->forward[$p][$id] = array();
		}
		//create triples that connect this embedded object with its parent
		$trips = ($p === false || $frag_id === false) ? array() : array(array($frag_id, $p, $id));
		$trips = array_merge($trips, getObjectAsTypedTriples($id, $obj, $this->cwurl));
		$this->removeTriples($trips);
	}
	
	/**
	 * Indicates that the value of a predicate has changed to get from a to b
	 * @param string $frag_id the parent id
	 * @param string $p the predicate
	 * @param mixed $ov old value
	 * @param mixed $updv old value
	 */
	function updValue($frag_id, $p, $ov, $updv){
		$this->forward[$p] = $updv;
		$this->backward[$p] = $ov;
		$this->updateTriple($frag_id, $p, $ov, $updv);
	}
	
	/**
	 * Called to compare two object literal lists to identify the delta between them. 
	 * @param string $frag_id the parent id
	 * @param string $p the predicate
	 * @param mixed $ovals old value list
	 * @param mixed $nvals new value list
	 */
	function updObjLiteralList($frag_id, $p, $ovals, $nvals){
		$unchanged = false;
		$rems = array();
		$adds = array();
		foreach($ovals as $oval){
			foreach($nvals as $i => $nval){
				if(compareObjLiterals($oval, $nval)){
					$unchanged = true;
					break;
				}
			}
			if(!$unchanged){
				$this->removeTriple(array($frag_id, $p, $oval));
				$rems[] = $oval;
			}
		}
		$unchanged = false;
		foreach($nvals as $nval){
			foreach($ovals as $i => $oval){
				if(compareObjLiterals($oval, $nval)){
					$unchanged = true;
					break;
				}
			}
			if(!$unchanged){
				$this->addTriple(array($frag_id, $p, $oval));
				$adds[] = $nval;
			}
		}
		if(count($adds) > 0 or count($rems) > 0){
			$this->forward[$p] = $adds;
			$this->backward[$p] = $rems;
		}
	}
	
	/**
	 * Called to compare two literal lists to identify the delta between them. 
	 * @param string $frag_id the parent id
	 * @param string $p the predicate
	 * @param mixed $ovals old value list
	 * @param mixed $nvals new value list
	 */
	function updValueList($frag_id, $p, $ovals, $nvals){
		$change = false;
		foreach($ovals as $oval){
			if(!in_array($oval, $nvals)){
				$this->removeTriple(array($frag_id, $p, encodeScalar($oval)));
				$change = true;
			}
		}
		foreach($nvals as $nval){
			if(!in_array($nval, $ovals)){
				$this->addTriple(array($frag_id, $p, encodeScalar($nval)));
				$change = true;
			}
		}
		if($change){
			$this->forward[$p] = $nvals;
			$this->backward[$p] = $ovals;
		}
	}
	
	
	/**
	 * Indicates that there has been a change of structure (ld type to get from a to b)
	 * @param string $frag_id - the parent fragment id
	 * @param string $p - the predicate
	 * @param mixed $v - the old value - (a) ld fragment
	 * @param LDPropertyValue $t - the old ld type object (a)
	 * @param mixed $v2 - the new value (b) ld fragment
	 * @param LDPropertyValue $t2 - the new ld type object (b)
	 */
	function addTypeChange($frag_id, $p, $v, LDPropertyValue $t, $v2, LDPropertyValue $t2){
		$this->forward[$frag_id] = array($p => $v2);
		$this->backward[$frag_id] = array($p => $v);
		if($t2->complex() || $t1->complex()){
			$this->failure_result("Type change includes complex (non-ld) change - not recorded as triples", 400);
		}
		$del = $t->isempty() || $t->complex() ? array() : getValueAsTypedTriples($frag_id, $p, $v, $this->cwurl);
		$add = $t2->isempty() || $t2->complex() ? array(): getValueAsTriples($frag_id, $p, $v2, $this->cwurl);
		if(count($del) > 0 && count($add) > 0){
			$rem = removeOverwrites($del, $add);
		}
		else $rem = array();
		if(count($add) > 0) {
			$this->addTriples($add);
		}
		if(count($del) > 0){
			$this->removeTriples($del);
		}
		if(count($rem) > 0){
			$this->triples_overwritten = array_merge($this->triples_overwritten, $rem);
		}
		$this->type_changes[] = array(
				"subject" => $frag_id,
				"predicate" => $p,
				"from" => $t->ldtype(),
				"to" => $t2->ldtype(),
				"del" => $del,
				"add" => $add,
				"rem" => $rem
		);
	}
	
	/**
	 * Adds a delta from a sub-fragment of an LDO to the overall LDO delta - used in recursive delta building
	 * @param string $p the predicate of the delta
	 * @param string $id the fragment id of the LDO that is being compared
	 * @param LDDelta $sub the delta object containing the comparison results for the 
	 */
	function addSubDelta($p, $id, $sub){
		if($sub->containsChanges()){
			if($p === false){
				if(!isset($this->forward)){
					$this->forward = array();
					$this->backward = array();
				}
				$this->forward[$id] = $sub->forward;
				$this->backward[$id] = $sub->backward;
	
			}
			else {
				if(!isset($this->forward[$p])){
					$this->forward[$p] = array();
					$this->backward[$p] = array();
				}
				$this->forward[$p][$id] = $sub->forward;
				$this->backward[$p][$id] = $sub->backward;
			}
			$this->addTriples($sub->triples_added);
			$this->removeTriples($sub->triples_removed);
			$this->triples_overwritten = array_merge($this->triples_overwritten, $sub->triples_overwritten);
			$this->type_changes = array_merge($this->type_changes, $sub->type_changes);
		}
	}
	
	/**
	 * Adds a delta of a json fragment to the delta - for integrating meta-updates.
	 * JSON deltas do not change the triples in the delta.
	 * @param LDDelta $other - the json delta being added.
	 */
	function addJSONDelta(LDDelta &$other){
		if($other->forward) $this->forward[$other->gname] = $other->forward;
		if($other->backward) $this->backward[$other->gname] = $other->backward;
	}
	
	/** 
	 * Are there any changes indicated in the delta?
	 * @return boolean true if there are differences between a and b
	 */
	function containsChanges(){
		return count($this->forward) > 0 or count($this->backward) > 0;
	}

	/**
	 * Indicate that a list of triples has been removed from a to get to b
	 * @param array $trip_array [[s, p, o],...] 
	 */
	function removeTriples($trip_array){
		$this->triples_removed = array_merge($this->triples_removed, $trip_array);
	}

	/**
	 * Indicate that a list of triples must be added to a to get to b
	 * @param array $trip_array [[s, p, o],...] 
	 */
	function addTriples($trip_array){
		$this->triples_added = array_merge($this->triples_added, $trip_array);
	}

	/**
	 * Indicate that a triple must be removed from a to get to b
	 * @param array $trip [s, p, o] 
	 */
	function removeTriple($trip){
		$this->triples_removed[] = $trip;
	}
	
	/**
	 * Indicate that a triple must be added to a to get to b
	 * @param array $trip [s,p,o]
	 */
	function addTriple($trip){
		$this->triples_added[] = $trip;
	}
	
	function added($gname = false){
		return $this->triples_added;
	}

	function removed($gname = false){
		return $this->triples_removed;
	}
	
	
	/**
	 * indicate that a triple has been updated from [s,p,o] to [s,p,n] 
	 * 
	 * The function is written so that it will serialise the values for any passed ld data structure - 
	 * it will work for any number of triples, not just a single one, but it is designed for single use
	 * @param string $frag_id the fragment id that has an updated value
	 * @param string $p the predicate in question
	 * @param mixed $ov the old value of the frag_id:p
	 * @param mixed $updv the updated value of frag_id:p
	 */
	function updateTriple($frag_id, $p, $ov, $updv){
		$this->addTriples(getValueAsTypedTriples($frag_id, $p, $updv, $this->cwurl));
		$this->removeTriples(getValueAsTypedTriples($frag_id, $p, $ov, $this->cwurl));
	}
	
	/**
	 * removes any triples that appear in both the add and delete buckets
	 * saves removed triples fyi
	 */
	function removeOverwrites(){
		$this->triples_overwritten = array_merge($this->triples_overwritten, removeOverwrites($this->triples_added, $this->triples_removed));
	}
	
	/**
	 * Translates the delta into quads for insertion into triplestore graph
	 * @param string $gname the graphname the quads are to be inserted into
	 * @param string $ogname the original graph name of the quads (stored under - if different)
	 * @return array simple array of [s,o,p,g] quads
	 */
	function getInsertQuads($gname = false, $ogname = false){
		$ogname = $ogname ? $ogname : $gname;
		$gname = $gname ? $gname : $this->gname;
		$trips = $this->triples_added;
		$quads = quadify($trips, $gname);
		return $quads;
	}

	/**
	 * Translates the delta into quads for deletion from triplestore graph
	 * @param string $gname the graphname the quads are to be deleted from 
	 * @param string $ogname the original graph name of the quads (stored under - if different)
	 * @return array simple array of [s,o,p,g] quads
	 */
	function getDeleteQuads($gname = false, $ogname = false){
		$gname = $gname ? $gname : $this->gname;
		$trips = $this->triples_removed;
		$quads = quadify($trips, $gname);
		return $quads;
	}
	
	/**
	 * Expands the urls to be full urls (not prefix:id forms) of all the contents of the delta
	 * @param NSResolver $nsres namespace resolver object
	 */
	function expandNS(NSResolver $nsres){
		$nsres->expandNamespaces($this->forward, $this->cwurl, $this->is_multigraph());
		$nsres->expandNamespaces($this->backward, $this->cwurl, $this->is_multigraph());
		$nsres->expandTriples($this->triples_added, $this->cwurl, $this->is_multigraph());
		$nsres->expandTriples($this->triples_removed, $this->cwurl, $this->is_multigraph());
		$nsres->expandNSTriples($this->triples_overwritten, $this->cwurl, $this->is_multigraph());
	}
	
	/**
	 * Compress all urls to use prefix:id forms not full urls wherever possible
	 * @param NSResolver $nsres namespace resolver object
	 */
	function compressNS(NSResolver $nsres){
		$nsres->compressNamespaces($this->forward, $this->cwurl, $this->is_multigraph());
		$nsres->compressNamespaces($this->backward, $this->cwurl, $this->is_multigraph());
		$nsres->compressTriples($this->triples_added, $this->cwurl, $this->is_multigraph());
		$nsres->compressTriples($this->triples_removed, $this->cwurl, $this->is_multigraph());
		$nsres->compressTriples($this->triples_overwritten, $this->cwurl, $this->is_multigraph());
	}
	
	function forAPI($format, $opts){
		return $this;
	}
	
}

/**
 * Class which represents a delta across multiple graphs - i.e. two candidates which span multiple graphs, or a meta delta and a ld delta
 * @author chekov
 *
 */
class MultiGraphLDDelta extends LDDelta {
	var $named_graphs = array();

	/**
	 * @see LDDelta::is_multigraph()
	 */
	function is_multigraph(){
		return true;
	}
	
	/**
	 * @see LDDelta::getDeleteQuads()
	 */
	function getDeleteQuads($gname = false, $ogname = false){
		if(!$ogname) $ogname = $gname;
		if(!$ogname){
			$quads = array();
			foreach(array_keys($this->triples_removed) as $gid){
				$quads = array_merge($quads, $this->getDeleteQuads($gid));
			}
			return $quads;
		}
		else {
			if(isset($this->triples_removed[$ogname]) && $this->triples_removed[$ogname]){
				return quadify($this->triples_removed[$ogname], $gname);
			}
		}	
		return array();
	}
	
	/**
	 * @see LDDelta::getInsertQuads()
	 */	
	function getInsertQuads($gname = false, $ogname = false){
		if(!$ogname) $ogname = $gname;
		if(!$ogname){
			$quads = array();
			foreach(array_keys($this->triples_added) as $gid){
				$quads = array_merge($quads, $this->getInsertQuads($gid));
			}
			return $quads;
		}
		else {
			if(isset($this->triples_added[$ogname]) && $this->triples_added[$ogname]){
				return quadify($this->triples_added[$ogname], $gname);
			}
		}	
	}
	
	/**
	 * Adds a delta for a specific named graph to this one
	 * @param LDDelta $other
	 */
	function addNamedGraphDelta(LDDelta $other){
		$this->named_graphs[$other->gname] = $other;
		$this->forward[$other->gname] = $other->forward;
		$this->backward[$other->gname] = $other->backward;
		$this->triples_added[$other->gname] = $other->triples_added;
		$this->triples_removed[$other->gname] = $other->triples_removed;
		$this->triples_overwritten[$other->gname] = $other->triples_overwritten;
		$this->triples_overwritten[$other->gname] = $other->triples_overwritten;
	}

	/**
	 * Returns a list of the named graphs that have been updated as part of this multi-graph delta
	 * @return array<string> - list of named graph urls affected by delta
	 */
	function getUpdatedNGIDs(){
		$gs = array_keys($this->triples_added);
		$ds = array_keys($this->triples_removed);
		if($ds){
			foreach($ds as $gid){
				if(!in_array($gid, $gs)){
					$gs[] = $gid;
				}
			}
		}
		return $gs;
	}
	
}







