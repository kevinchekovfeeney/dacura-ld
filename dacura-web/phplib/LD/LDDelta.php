<?php

class LDDelta extends DacuraObject {
	var $make_updates_adds_rems = true;
	var $cwurl;
	var $gname;//the graphname that the triples belong to
	//variables below hold state describing the change
	var $forward = array(); //Candidate Update Request Expression in LD format
	var $backward = array(); //Candidate Update Request Undo Expression in LD format
	var $triples_added = array();
	var $triples_removed = array();
	var $triples_updated = array();
	var $triples_overwritten = array();//cancelled out by a remove and an add. state retained fyi
	var $type_changes = array();
	var $bad_links = array();
	
	function __construct($cwurl, $gname = false){
		$this->cwurl = $cwurl;
		$this->gname = $gname;
		if($gname){
			$this->triples_added[$gname] = array();
			$this->triples_removed[$gname] = array();
			$this->triples_updated[$gname] = array();
			$this->triples_overwritten[$gname] = array();
		}
	}
		
	function expandNS($schema){
		expandNamespaces($this->forward, $schema, $this->cwurl);
		expandNamespaces($this->backward, $schema, $this->cwurl);
		expandNSTriples($this->triples_added, $schema, $this->cwurl, true);
		expandNSTriples($this->triples_removed, $schema, $this->cwurl, true);
		expandNSTriples($this->triples_updated, $schema, $this->cwurl, true);
		expandNSTriples($this->triples_overwritten, $schema, $this->cwurl, true);
	}
	
	function compressNS($schema){
		compressNamespaces($this->forward, $schema, $this->cwurl);
		compressNamespaces($this->backward, $schema, $this->cwurl);
		compressNSTriples($this->triples_added, $schema, $this->cwurl, true);
		compressNSTriples($this->triples_removed, $schema, $this->cwurl, true);
		compressNSTriples($this->triples_updated, $schema, $this->cwurl, true);
		compressNSTriples($this->triples_overwritten, $schema, $this->cwurl, true);
	}
	
	function getDisplayFormat(){
		$other = clone($this);
		unset($other->make_updates_adds_rems);
		return $other;
	}
	
	function reportString(){
		if($this->containsChanges()){
			return count($this->triples_added). " added, ".count($this->triples_removed)." removed, ".count($this->triples_updated);
		}
		return "No changes";
	}
	
	function add($frag_id, $p, $v, $add_top_level_triples = true){
		$this->forward[$p] = $v;
		$this->backward[$p] = array();
		$trips = getValueAsTypedTriples($frag_id, $p, $v, $this->cwurl);
		if(!$add_top_level_triples ){
			foreach($trips as $i => $trip){
				if($trip[0] == $frag_id){
					unset($trips[$i]);
				}
			}
			$trips = array_values($trips);//rebuild index 	
		}
		if($this->gname){
			$this->triples_added[$this->gname] = array_merge($this->triples_added[$this->gname], $trips);				
		}
		else {
			$this->triples_added = array_merge($this->triples_added, $trips);				
		}
	}

	function del($frag_id, $p, $v, $del_top_level_triples = true){
		$this->backward[$p] = $v;
		$this->forward[$p] = array();
		$trips = getValueAsTypedTriples($frag_id, $p, $v, $this->cwurl);
		if(!$del_top_level_triples ){
			foreach($trips as $i => $trip){
				if($trip[0] == $frag_id){
					unset($trips[$i]);
				}
			}
			$trips = array_values($trips);//rebuild index
		}
		$this->removeTriples($trips);
	}
	
	function addObject($frag_id, $p, $id, $obj, $ignore_root = false){
		if(!isset($this->forward[$p])){
			$this->forward[$p] = array();
			$this->backward[$p] = array();
		}
		$this->forward[$p][$id] = $obj;
		$this->backward[$p][$id] = array();
		$trips = $ignore_root ? array() : array(array($frag_id, $p, $id));
		$trips = array_merge($trips, getObjectAsTypedTriples($id, $obj, $this->cwurl));
		$this->addTriples($trips);
	}
	
	function delObject($frag_id, $p, $id, $obj, $ignore_root = false){
		if(!isset($this->forward[$p])){
			$this->forward[$p] = array();
			$this->backward[$p] = array();
		}
		$this->backward[$p][$id] = $obj;
		$this->forward[$p][$id] = array();
		$trips = $ignore_root ? array() : array(array($frag_id, $p, $id));
		$trips = array_merge($trips, getObjectAsTypedTriples($id, $obj, $this->cwurl));
		$this->removeTriples($trips);
	}
	
	function removeOverwrites(){
		if($this->gname){
			$this->triples_overwritten[$this->gname] = array_merge($this->triples_overwritten[$this->gname], removeOverwrites($this->triples_added[$this->gname], $this->triples_removed[$this->gname]));				
		}
		else {
			$this->triples_overwritten = array_merge($this->triples_overwritten, removeOverwrites($this->triples_added, $this->triples_removed));				
		}
	}

	function addTypeChange($frag_id, $p, $v, $t, $v2, $t2){
		$this->forward[$frag_id] = array($p => $v2);
		$this->backward[$frag_id] = array($p => $v);
		$del = $t->isempty() ? array() : getValueAsTypedTriples($frag_id, $p, $v, $this->cwurl);
		$add = $t2->isempty() ? array(): getValueAsTriples($frag_id, $p, $v2, $this->cwurl);
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
			if($this->gname){
				$this->triples_overwritten[$this->gname] = array_merge($this->triples_overwritten[$this->gname], $rem);
			}
			else {
				$this->triples_overwritten = array_merge($this->triples_overwritten, $rem);
			}
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
	
	function containsChanges(){
		return count($this->forward) > 0 or count($this->backward) > 0;
	}
		
	function addSubDelta($frag_id, $p, $id, $sub, $t = false){
		if($sub->containsChanges()){
			if(!isset($this->forward[$p])){
				$this->forward[$p] = array();
				$this->backward[$p] = array();
			}
			$this->forward[$p][$id] = $sub->forward;
			$this->backward[$p][$id] = $sub->backward;
			$this->addTriples($sub->triples_added);
			$this->removeTriples($sub->triples_removed);
			if($this->gname){
				$this->triples_updated[$this->gname] = array_merge($this->triples_updated[$this->gname], $sub->triples_updated);
				$this->triples_overwritten[$this->gname] = array_merge($this->triples_overwritten[$this->gname], $sub->triples_overwritten);		
			}
			else {
				$this->triples_updated = array_merge($this->triples_updated, $sub->triples_updated);
				$this->triples_overwritten = array_merge($this->triples_overwritten, $sub->triples_overwritten);
			}
			$this->type_changes = array_merge($this->type_changes, $sub->type_changes);
			$this->bad_links = array_merge($this->bad_links, $sub->bad_links);
		}
	}
	
	function addNamedGraphDelta($other){
		$this->forward = array_merge($this->forward, $other->forward);
		$this->backward = array_merge($this->backward, $other->backward);
		$this->triples_added = array_merge($this->triples_added, $other->triples_added);
		$this->triples_removed = array_merge($this->triples_removed, $other->triples_removed);
		$this->triples_updated = array_merge($this->triples_updated, $other->triples_updated);
		$this->triples_overwritten = array_merge($this->triples_overwritten, $other->triples_overwritten);
		$this->type_changes = array_merge($this->type_changes, $other->type_changes);
		$this->bad_links = array_merge($this->bad_links, $other->bad_links);
	}

	function updValue($frag_id, $p, $ov, $updv){
		$this->forward[$p] = $updv;
		$this->backward[$p] = $ov;
		$this->updateTriple($frag_id, $p, $ov, $updv);
	}

	function updEmbeddedList($frag_id, $p, $vold, $vnew) {
		//this is the hard one.....
		//not supported for cwurls - only can occur in ccr or curs, not in candidates.
	}
	
	function removeTriple($trip_array){
		$this->removeTriples(array($trip_array));
	}
	
	function addTriple($trip_array){
		$this->addTriples(array($trip_array));
	}
	
	function removeTriples($trip_array){
		if($this->gname){
			$this->triples_removed[$this->gname] = array_merge($this->triples_removed[$this->gname], $trip_array);
		}
		else {
			$this->triples_removed = array_merge($this->triples_removed, $trip_array);
		}
	}

	function addTriples($trip_array){
		if($this->gname){
			$this->triples_added[$this->gname] = array_merge($this->triples_added[$this->gname], $trip_array);
		}
		else {
			$this->triples_added = array_merge($this->triples_added, $trip_array);
		}
	}
	
	function updateTriple($frag_id, $p, $ov, $updv){
		if($this->make_updates_adds_rems){
			$this->addTriples(getValueAsTypedTriples($frag_id, $p, $updv, $this->cwurl));
			$this->removeTriples(getValueAsTypedTriples($frag_id, $p, $ov, $this->cwurl));
		}
		else {
			if($this->gname){
				$this->triples_updated[$this->gname][] = array($frag_id, $p, encodeScalar($ov), encodeScalar($updv));
			}
			else {
				$this->triples_updated[] = array($frag_id, $p, encodeScalar($ov), encodeScalar($updv));
			}
		}
	}
	
	
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
	
	function setMissingLinks($old, $new){
		//check the internal consistency of the delta?
		$this->bad_links['old'] = $old;
		$this->bad_links['new'] = $new;
		$this->bad_links['add'] = array();
		foreach($new as $i => $nml){
			$link_existed = false;
			foreach($old as $j => $oml){
				if($oml[0] == $nml[0] && $oml[1] == $nml[1] && $oml[2] == $nml[2]){
					$link_existed = true;
					break;
				}
			}
			if(!$link_existed){
				$this->bad_links['add'] = $nml;
			}
		}
	}

	function getNamedGraphDeleteQuads($gname){
		$dels = array();
		if(isset($this->triples_removed[$gname])){
			foreach($this->triples_removed[$gname] as $trip){
				$trip[] = $gname;
				$dels[] = $trip;
			}
		}
		return $dels;
	}
	
	
	function getNamedGraphInsertQuads($gname){
		$adds = array();
		if(isset($this->triples_added[$gname])){
			foreach($this->triples_added[$gname] as $trip){
				$trip[] = $gname;
				$adds[] = $trip;
			}
		}
		return $adds;		
	}
	
	function candidateInserts($callable = false){
		$inserts = array();
		foreach($this->triples_added as $trip){
			if(is_array($trip[2])){
				$o = $trip[2]['data'];
				$t = 'literal';
			}
			else {
				$o = $trip[2];
				$t = 'link';
			}
			if($callable){
				$inserts = array_merge($inserts, $callable($trip[0], $trip[1], $o, $t));
			}
			else {
				$inserts[] = array($trip[0], $trip[1], $o);
			}
		}
		return $inserts;
	}

	function candidateDeletes($callable = false){
		$deletes = array();
		foreach($this->triples_removed as $trip){
			if(is_array($trip[2])){
				$o = $trip[2]['data'];
				$t = 'literal';
			}
			else {
				$o = $trip[2];
				$t = 'link';
			}
			if($callable){
				$deletes = array_merge($deletes, $callable($trip[0], $trip[1], $o, $t));
			}
			else {
				$deletes[] = array($trip[0], $trip[1], $o);
			}
		}
		return $deletes;
	}
	
	
}