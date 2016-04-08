<?php


class LDOUpdate extends DacuraObject{
	/** @var the id of the update object in the db */
	var $id;
	/** @var the collection id of the update object in the db */
	var $cid;
	/** @var the ld type of the object the update applies to (candidate, graph, ontology) */
	var $type;
	/** @var the id of the object being updated */
	var $targetid;
	/** @var the status of the update one of LDO::$valid_statuses */
	var $status;
	/** @var the timestamp for when the update was created */
	var $created;
	/** @var the timestamp for when the update was last modified */
	var $modified;
	/** @var the forward delta to carry out the update */
	var $forward;
	/** @var the backward delta to carry out the update */
	var $backward;
	/** @var the version of the object the update applied to */
	var $from_version;
	/** @var the version of the object the update created (0 if update has not been applied */
	var $to_version = 0;
	/** @var meta data about the update itself */
	var $meta = array(); 
	/** @var name space resolver object */
	var $nsres;
	//complex objects - any of them can be calculated from 1.5. (original + delta.forward = changed) (changed + delta.backward = original)
	var $original; //the original state of the target object 
	var $changed;	//the changed state of the target object (if the update request was to be accepted)
	var $delta; // LDDelta object describing changes from old to new

	/**
	 * Returns the properties of the object as an array - for copying into api datastructures without copying the whole object
	 * @return array of properties cid, ldtype, version, created, modified, latest_status, ...
	 */
	function getPropertiesAsArray(){
		$props = array();
		if($this->cid) $props['cid'] = $this->cid;
		if($this->type) $props['ldtype'] = $this->type;
		if($this->targetid) $props['targetid'] = $this->targetid;
		if($this->created) $props['created'] = $this->created;
		if($this->modified) $props['modified'] = $this->modified;
		if($this->from_version) $props['from_version'] = $this->from_version;
		if($this->to_version) $props['to_version'] = $this->to_version;
		if($this->status) $props['status'] = $this->status;
		//$props['compressed'] = $this->compressed;
		//$props['addressable_bnids'] = $this->addressable_bnids;
		return $props;
	}
	
	function getStandardProperties(){
		return array('cid', 'ldtype', 'from_version', 'created', 'modified',
				"status", 'targetid', 'to_version');
	}
	
	function __construct($id = false, &$original = false){
		$this->id = $id;
		if($original) $this->setOriginal($original);
		$this->created = time();
		$this->modified = time();
	}

	function setOriginal(&$original){
		$this->original = $original;
		if(!(isset($original->targetid) and $original->targetid)){
			$this->targetid = $original->id;
		}
		if(!(isset($this->nsres) and $original->nsres)){
			$this->nsres = $original->nsres;
		}
		$this->from_version = $original->version;
		$this->cid = $original->cid();
	}
	
	/**
	 * Applies the change specified in update to the original 
	 * @param LDO $update the update(d) ldo object
	 * @param string $mode replace|update - the mode that the update is taking place in..
	 * @param array $rules rules governing permissible transforms
	 */
	function apply(LDO &$update, $mode, $rules, $srvr){
		if($mode == "update"){
			$force_multi = false;
			if($this->original->is_multigraph()){
				if(!$update->is_multigraph()){
					$update->ldprops = array($rules['default_graph_url'] => $update->ldprops);
				}
				$force_multi = true;
			}
			elseif($update->is_multigraph()){
				$force_multi = true;
				//$this->original->ldprops = array($rules['default_graph_url'] => $this->original->ldprops);
			}
			$this->forward = $update->ldprops;
			if($update->meta){
				$this->forward['meta'] = $update->meta;
			}
				
			if(!$this->calculateChanged($rules, $force_multi)){
				return false;
			}
			$rules = $srvr->getReplaceLDOContentRules($this->original);
			if($this->original->validate($rules, $srvr) && !$this->changed->validate($rules, $srvr)){
				return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
			}
		}
		else {
			$this->changed = $update;
			$this->changed->version = $this->original->latest_version + 1;
			$this->from_version = $this->original->version;
			if($this->original->validate($rules, $srvr) && !$this->changed->validate($rules, $srvr)){
				return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
			}
			//$this->to_version = $this->changed->version
			//$this->changed->to_version
			$this->changed->readStateFromMeta();				
		}
		return $this->calculateDelta($rules);				
	}
	
	function calculateChanged($rules = array(), $force_multi = false){
		$this->changed = clone $this->original;
		$this->changed->version = $this->to_version ? $this->to_version : 1 + $this->original->latest_version;
		if($this->changed->version > $this->changed->latest_version){
			$this->changed->latest_version = $this->changed->version;
		}
		$contents = (isset($rules['direction']) && $rules['direction'] == "backward") ? $this->backward : $this->forward;
		if(!$this->changed->update($contents, $rules, $force_multi) && $this->changed->compliant($rules)){
			return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
		}
		$this->changed->readStateFromMeta();
		return true;
	}
	
	function calculateDelta($rules = array()){
		$this->delta = $this->original->compare($this->changed, $rules);
		
		if(isset($rules['validate']) && $rules['validate'] && $this->FBLoaded()){
			$fdelta = compareLD($this->targetid, $this->delta->forward, $this->forward, $rules);
			if($fdelta->containsChanges()){
				return $this->failure_result("Update $this->id to $this->targetid: Mismatch between calculated changes and stored forward transition.", 400);
			}
			$bdelta = compareLD($this->targetid, $this->delta->backward, $this->backward, $rules);
			if($bdelta->containsChanges()){
				return $this->failure_result("Update $this->id to $this->targetid: Mismatch between calculated changes and stored backward transitions", 400);
			}
		}
		$this->forward = $this->delta->forward;
		$this->backward = $this->delta->backward;
		//opr($this->changed->ldprops);
		//opr($this->delta);
		return true;
	}
	
	
	function __clone(){
		$this->original = $this->original;
		$this->changed = $this->changed;
		$this->delta = $this->delta;
		$this->meta = $this->meta;
		$this->forward = deepArrCopy($this->forward);
		$this->backward = deepArrCopy($this->backward);
	}
	
	function setNamespaces($nsres){
		$this->nsres = $nsres;
	}
	
	function loadFromDBRow($row){
		$this->targetid = $row['targetid'];
		$this->status = $row['status'];
		$this->type = $row['type'];
		$this->cid = $row['collectionid'];
		$this->created = $row['createtime'];
		$this->modified = $row['modtime'];
		$this->from_version = $row['from_version'];
		$this->to_version = $row['to_version'];
		$this->forward = json_decode($row['forward'], true);
		$this->backward = json_decode($row['backward'], true);
		$this->meta = json_decode($row['meta'], true);
	}
	
	function cwurl(){
		return $this->original->cwurl;
	}
	
	function getLDOType(){
		if($this->type) return $this->type;
		return strtolower(get_class($this->original));
	}


	//compare two update requests (for analysing updates to updates)

	function compare($other = false){
		$results = array("meta" => array(), "add" => array(), "del" => array());
		if(!$other){
			$results['meta']['id'] = $this->id;
			$results['meta']['type'] = $this->type;
			$results['meta']['targetid'] = $this->targetid;
			$results['meta']['status'] = $this->status;
			$results['meta']['from_version'] = $this->from_version;
			$results['meta']['to_version'] = $this->to_version;
			$results['add']['forward'] = $this->forward;
			$results['add']['backward'] = $this->backward;
		}
		else {
			if($this->id != $other->id) $results['meta']['id'] = array($this->id, $other->id);
			if($this->type != $other->type ) $results['meta']['type'] = array($this->type, $other->type);
			if($this->targetid != $other->targetid ) $results['meta']['targetid'] = array($this->targetid, $other->targetid);
			if($this->status != $other->status) $results['meta']['status'] = array($this->status, $other->status);
			if($this->from_version != $other->from_version) $results['meta']['from_version'] = array($this->from_version, $other->from_version);
			if($this->to_version != $other->to_version) $results['meta']['to_version'] = array($this->to_version, $other->to_version);
			$fdelta = compareLD($this->targetid, $this->forward, $other->forward, $this->cwurl());
			$bdelta = compareLD($this->targetid, $this->backward, $other->backward, $this->cwurl());
			$results['add']['forward'] = $fdelta->forward;
			$results['add']['backward'] = $bdelta->forward;
			$results['del']['forward'] = $fdelta->backward;
			$results['del']['backward'] = $bdelta->backward;
		}
		return $results;
	}

	function deltaAsNGQuads($other, $gname){
		$added = $this->delta->getNamedGraphInsertQuads($gname);
		$deleted = $this->delta->getNamedGraphDeleteQuads($gname);
		$oadded = $other->delta->getNamedGraphInsertQuads($gname);
		$odeleted = $other->delta->getNamedGraphDeleteQuads($gname);
		return $this->consolidateTrips($added, $deleted, $oadded, $odeleted);
	}

	function consolidateTrips($added, $deleted, $oadded, $odeleted){
		foreach($added as $i => $trip){
			foreach($oadded as $j => $otrip){
				if(compareTrips($trip, $otrip)){
					unset($added[$i]);
					unset($oadded[$j]);
					break;
				}
			}
		}
		foreach($deleted as $i => $trip){
			foreach($odeleted as $j => $otrip){
				if(compareTrips($trip, $otrip)){
					unset($deleted[$i]);
					unset($odeleted[$j]);
					break;
				}
			}
		}
		$deleted = array_merge(array_values($deleted), array_values($oadded));
		$added = array_merge(array_values($added), array_values($odeleted));
		return array("add" => $added, "del" => $deleted);
	}

	function deltaAsTriples($other, $rules = array()){
		$added = $this->addedLDTriples($rules);
		$deleted = $this->deletedLDTriples($rules);
		$oadded = $other->addedLDTriples($rules);
		$odeleted = $other->deletedLDTriples($rules);
		return $this->consolidateTrips($added, $deleted, $oadded, $odeleted);
	}

	function getMetaUpdates(){
		$meta = array();
		if($this->original->id != $this->changed->id) $meta['id'] = array($this->original->id, $this->changed->id);
		if($this->original->status != $this->changed->status) $meta['status'] = array($this->original->status, $this->changed->status);
		if($this->original->ldtype != $this->changed->ldtype) $meta['type'] = array($this->original->ldtype, $this->changed->ldtype);
		if($this->original->cid != $this->changed->cid) $meta['cid'] = array($this->original->cid, $this->changed->cid);
		return $meta;
	}

	function published(){
		return $this->is_accept();
	}

	function originalPublished(){
		if(!$this->original) return false;
		return $this->original->is_accept();
	}

	function changedPublished(){
		if(!$this->changed) return false;
		return $this->changed->is_accept();
	}

	function bothPublished(){
		return $this->changedPublished() && $this->originalPublished();
	}

	//whether the forward and backward fields of the object have been loaded
	function FBLoaded(){
		return isset($this->forward) && is_array($this->forward) && isset($this->backward) && is_array($this->backward);
	}

	function FBChanges(){
		return $this->FBLoaded() && (count($this->forward) > 0 && count($this->backward) > 0);
	}

	function initFromOriginal(){
		$this->created = time();
		$this->modified = time();
		$this->from_version = $this->original->version();
		$this->cid = $this->original->cid;
	}

	/*
	 * Called by the state management system when update records are pulled from the DB
	 */
	function calculate($stored = false, $load_all = true, $validate_delta = true){
		$this->expandNS();
		if($stored){
			$this->changed = $stored;
			return (!$load_all or $this->calculateDelta($validate_delta));
		}
		return (!$load_all or $this->calculateChanged(array("force_inserts" => true, "calculate_delta" => true)));
	}

	/*
	 * Checks for illegal structures in the update command
	 */
	function validateCommand($obj, $in_embedded = false){
		foreach($obj as $p => $v){
			//opr($v);
			$pv = new LDPropertyValue($v, $this->cwurl());
			if($pv->illegal()) return $this->failure_result("Update failed validation: ".$pv->errmsg, $pv->errcode);
			if($pv->embeddedlist()){
				$cwlinks = $pv->getupdates();
				if(count($cwlinks) > 0 && $in_embedded){
					return $this->failure_result("New embedded objects cannot have properties that update anything but themselves: $p "." closed world links ".$cwlinks[0], 400);
				}
				foreach($v as $id => $emb){
					if(!$this->validateCommand($emb, $in_embedded)){
						return false;
					}
				}
			}
			elseif($pv->embedded()){
				if(count($v) == 1 && isset($v['@id'])){
					return $this->failure_result("Embedded objects cannot have @id as their only property ($p).", 400);
				}
				if(!$this->validateCommand($v, true)){
					return false;
				}
			}
			elseif($pv->objectlist()){
				foreach($v as $emb){
					if(count($emb) == 1 && isset($emb['@id'])){
						return $this->failure_result("Embedded objects cannot have @id as their only property ($p).", 400);
					}
					if(!$this->validateCommand($emb, true)){
						return false;
					}
				}
			}
		}
		return true;
	}


	function getUpdateOptions(){
		$opts = array(
			"demand_id_allowed" => true,
			"force_inserts" => true,
			'calculate_delta' => true,
			'validate_delta' => false,				
		);
		return $opts;
	}
	
	function updateImportedProps($contents){
		$this->changed->ldprops = array($this->original->id => $contents);		
	}
	
	function calculateImported($meta, $contents){
		$this->changed = clone $this->original;
		$this->changed->version = $this->to_version ? $this->to_version : 1 + $this->changed->latest_version;
		if($this->changed->version > $this->changed->latest_version){
			$this->changed->latest_version = $this->changed->version;
		}
		$this->updateImportedProps($contents);
		if($meta){
			if(!$this->changed->update(array("meta" => $meta), true, true)){
				return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
			}				
		}
		$this->changed->readStateFromMeta();
		return $this->calculateDelta();
	}	
	
	/**
	 * Namespace resolution functions - called to compress all urls to prefix:id form
	 */
	function compressNS(){
		$this->nsres->compressNamespaces($this->forward, $this->cwurl());
		$this->nsres->compressNamespaces($this->backward, $this->cwurl());
		if($this->delta){
			$this->delta->compressNS($this->nsres);
		}
		$this->original && $this->original->compressNS();
		$this->changed && $this->changed->compressNS();
	}

	/**
	 *  Called to expand all prefix:id to full urls across the update
	 */
	function expandNS(){
		$this->nsres->expandNamespaces($this->forward, $this->cwurl());
		$this->nsres->expandNamespaces($this->backward, $this->cwurl());
		if($this->delta){
			$this->delta->expandNS($this->nsres);
		}
		$this->original && $this->original->expandNS();
		$this->changed && $this->changed->expandNS();
	}


	/**
	 * Does the update request come from a context that has authority for the ldo?
	 * @param string $ocid - candidate collection id
	 * @return boolean
	 */
	function isLegalContext($cid){
		if($cid == "all" or ($this->original->cid != "all" && $this->original->cid == $cid)){
			return true;
		}
		return false;
	}

	function getChangeViewHTML(){
		return $this->showChanges($this->changed->ldprops, $this->backward);
	}

	function showChanges($props, $dprops){
		$cprops = array();
		foreach($props as $prop => $v){
			$pv = new LDPropertyValue($v, $this->cwurl());
			if($pv->illegal()){
				return $this->failure_result($pv->errmsg, $pv->errcode);
			}
			if(!isset($dprops[$prop])){
				$xprop = $this->applyLinkHTML($prop, "unchanged", true);
				$cprops[$xprop] = $this->getUnchangedJSONHTML($v, $pv);
				//ignore.
			}
			else {
				$nv = $dprops[$prop];
				$dpv = new LDPropertyValue($nv, $this->cwurl());
				if($dpv->isempty()){
					$xprop = $this->applyLinkHTML($prop, "added", true);
					$cprops[$xprop] = $this->getAddedJSONHTML($v, $pv);
				}
				if(!$pv->sameLDType($dpv)){
					$xprop = $this->applyLinkHTML($prop, "structural", true);
					$cprops[$xprop] = $this->getAddedTypeJSONHTML($v, $pv);
					$cprops[$xprop] = array_merge($cprops[$xprop], $this->getDeletedTypeJSONHTML($nv, $dpv));
				}
				else {
					switch($pv->ldtype(true)){
						case 'scalar':
							if($v != $nv){
								$xprop = $this->applyLinkHTML($prop, "updated", true);
								$cprops[$xprop] = $this->getValueChangeHTML($v, $nv);
							}
							else {
								$xprop = $this->applyLinkHTML($prop, "unchanged", true);
								$cprops[$xprop] = $this->applyLiteralHTML($v, "unchanged");
							}
							break;
						case 'valuelist':
							$changed = false;
							$entries = array();
							foreach($v as $i => $val){
								if(in_array($val, $nv)){
									$entries[] = $this->applyLiteralHTML($val, "unchanged");
								}
								else {
									$changed = true;
									$entries[] = $this->applyLiteralHTML($val, "added");
								}
							}
							foreach($nv as $j => $val2){
								if(!in_array($val2, $v)){
									$changed = true;
									$entries[] = $this->applyLiteralHTML($val2, "deleted");
								}
							}
							if($changed){
								$xprop = $this->applyLinkHTML($prop, "changed", true);
							}
							else {
								$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							}
							$cprops[$xprop] = $entries;
							break;
						case 'embeddedobjectlist':
							$xprop = $this->applyLinkHTML($prop, "unchanged", true);
							$cprops[$xprop] = array();
							foreach($v as $id => $obj){
								if(!isset($nv[$id])){
									$tprop = $this->applyLinkHTML($id, "unchanged");
									$cprops[$xprop][$tprop] = $this->getUnchangedJSONHTML($v, $pv);
								}
								else {
									$tprop = $this->applyLinkHTML($id, "unchanged");
									$cprops[$xprop][$tprop]= $this->showChanges($obj, $nv[$id]);
								}
							}
							foreach($nv as $nid => $nobj){
								if(!isset($v[$nid])){
									$tprop = $this->applyLinkHTML($nid, "deleted");
									$cprops[$xprop][$tprop] = $this->getDeletedJSONHTML($v, $pv);
								}
							}
							break;
					}
				}
			}
		}
		foreach($dprops as $dprop => $dv){
			$dpv = new LDPropertyValue($dv, $this->cwurl());
			if(!isset($props[$dprop])){
				$xprop = $this->applyLinkHTML($dprop, "deleted");
				$cprops[$xprop] = $this->getDeletedJSONHTML($v, $pv);
			}
		}
		return $cprops;
	}

	function getValueChangeHTML($v, $nv){
		return array($this->applyLiteralHTML($v, "updated added"), $this->applyLiteralHTML($nv, "updated deleted"));
	}

	function getDeletedTypeJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "deleted structural", $pv);
	}

	function getAddedTypeJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "added structural", $pv);
	}

	function getAddedJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "added", $pv);
	}

	function getDeletedJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "deleted", $pv);
	}

	function getUnchangedJSONHTML($v, $pv){
		return $this->getJSONHTML($v, "unchanged", $pv);
	}

	function getJSONHTML($v, $t, $pv){
		$nv = array();
		if($pv->literal() or $pv->objectliteral()){
			$nv = $this->applyLiteralHTML($v, $t);
		}
		elseif($pv->link()){
			$nv = $this->applyLinkHTML($v, $t);
		}
		elseif($pv->objectliterallist()){
			$nv = array();
			foreach($v as $val){
				$nv[] = $this->applyLiteralHTML($val, $t);
			}
		}
		elseif($pv->valuelist()){
			$nv = array();
			foreach($v as $val){
				if(isURL($val) || isNamespacedURL($val)){
					$nv[] = $this->applyLinkHTML($val, $t);
				}
				else {
					$nv[] = $this->applyLiteralHTML($val, $t);
				}
			}
		}
		elseif($pv->embeddedlist()){
			$nv = array();
			foreach($v as $id => $obj){
				$nid = $this->applyLinkHTML($id, $t);
				$nv[$nid] = array();
				foreach($obj as $p2 => $val2){
					$pv2 = new LDPropertyValue($val2, $this->cwurl());
					$np2 = $this->applyLinkHTML($p2, $t, true);
					$nv[$nid][$np2] = $this->getJSONHTML($val2, $t, $pv2);
				}
			}
		}
		return $nv;
	}

	function applyLinkHTML($ln, $t, $is_prop = false){
		if($is_prop){
			$cls = "dacura-property $t";
		}
		else {
			$cls = "dacura-property-value $t";
		}
		if(isURL($ln)){
			if($this->original->isDocumentLocalLink($ln)){
				$cls .= " document_local_link";
				$lh = "<a class='$cls' href='$ln".$vstr."'>$ln</a>";
			}
			else {
				$lh = "<a class='$cls' href='$ln'>$ln</a>";
			}
		}
		elseif(isNamespacedURL($ln)){
			if($this->original->isDocumentLocalLink($ln)){
				$cls .= " document_local_link";
				$expanded = $this->nsres->expand($ln);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace' title='warning: unknown namespace'>$ln</span>";
				}
				else {
					$lh = "<a class='$cls' href='$expanded".$vstr."'>$ln</a>";
				}
			}
			else {
				$expanded = $this->nsres->expand($ln);
				if(!$expanded){
					$lh = "<span class='$cls unknown-namespace'>$ln</span>";
				}
				else {
					$lh = "<a class='$cls' href='$expanded'>$ln</a>";
				}
			}
		}
		else {
			$lh = "<span class='$cls not-a-url'>$ln</span>";
		}
		return $lh;
	}

	function applyLiteralHTML($ln, $tp){
		return "<span class='dacura-property-value $tp dacura-literal'>$ln</span>";
	}

	function compareLDTriples($rules){
		if($this->original->is_multigraph()){
			return compareLDGraphs($this->cwurl(), $this->original->ldprops, $this->changed->ldprops, $rules);				
		}
		else {
			return compareLDGraph($this->original->ldprops, $this->changed->ldprops, $rules);
		}
	}

	function sameAs($other){
		if($this->from_version != $other->from_version){
			return false;
		}
		if($this->status != $other->status){
			return false;
		}
		return json_encode($this->forward) == json_encode($other->forward);
	}

	function nodelta(){
		return !$this->delta or !$this->delta->containsChanges();
	}

	/*
	 * DB serialisation
	 */
	function from_version(){
		return $this->from_version;
	}

	function to_version(){
		return $this->to_version;
	}

	function get_forward_json(){
		$cmd = $this->forward ? $this->forward : $this->delta->forward;
		$x = json_encode($cmd);
		return ($x) ? $x : "{}";
	}

	function get_backward_json(){
		$cmd = $this->backward ? $this->backward : $this->delta->backward;
		$x = json_encode($cmd);
		return ($x) ? $x : "{}";
	}

	function get_meta_json(){
		return $this->meta ? json_encode($this->meta): "{}";
	}

	function reportString(){
		return $this->delta ? $this->delta->reportString() : "No delta calculated - nothing to report";
	}

	function forAPI($format, $opts){
		$meta = deepArrCopy($this->meta);
		$meta = array_merge($this->getPropertiesAsArray(), $meta);
		$apirep = array(
				"id" => $this->id,
				"meta" => $meta,
				"format" => $format,
				"options" => $opts,
				"insert" => $this->forward,
				"delete" => $this->backward
		);
		if(isset($opts['show_changed']) && $opts['show_changed'] && isset($this->changed) && $this->changed) {
			$apirep["changed"] = $this->changed->forAPI($format, $opts);
		}
		if(isset($opts['show_original']) && $opts['show_original'] && isset($this->original) && $this->original){
			$apirep["original"] = $this->original->forAPI($format, $opts);
		}
		if(isset($opts['analysis']) && $opts['analysis'] && isset($this->analysis) && $this->analysis){
			$apirep["analysis"] = $this->analysis;
		}
		return $apirep;
	}

	function display($format, $options, $srvr){
		$lddisp = new LDODisplay($this->id, $this->cwurl);
		if($format == "json"){
			$this->display = $lddisp->displayJSON($this->forward, $options);
		}
		elseif($format == "html"){
			$this->display = $lddisp->displayHTML($this->forward, $options);
		}
		elseif($format == "triples"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedTriples() : $this->triples();
			$this->display = $lddisp->displayTriples($payload, $options);
		}
		elseif($format == "quads"){
			$payload = isset($options['typed']) && $options['typed'] ? $this->typedQuads() : $this->quads();
			$this->display = $lddisp->displayQuads($payload, $options);
		}
		elseif($format == "jsonld"){
			require_once("JSONLD.php");
			$jsonld = toJSONLD($this->forward, $this->getNS(), array("cwurl" => $this->cwurl));
			$this->display = $lddisp->displayJSONLD($jsonld, $options);
		}
		elseif($format == "nquads"){
			$payload = $this->nQuads();
			$this->display = $lddisp->displayNQuads($payload, $options);
		}
		else {
			$exported = $this->export($format);
			if($exported === false){
				return false;
			}
			$this->display = $lddisp->displayExport($exported, $format, $options);
		}
		if($this->changed){
			$this->changed->display($format, $options, $srvr);
		}
		if($this->original){
			$this->original->display($format, $options, $srvr);
		}
		
		return true;
	}

	function displayExport($format, $srvr){
		$vstr = "?format=".$format;
		$flags = array("ns", "links", "problems", "typed");
		$this->changed->displayExport($format, $flags, $vstr, $srvr);
		$this->original->displayExport($format, $flags, $vstr, $srvr);
		//$temp = new LDDocument($this->id);
		//$temp->load($this->forward);
		//$exported = $temp->export($format, $this->nsres);
		//if($exported){
		//	if($format != "svg" && $format != "dot" && $format != "png" && $format != "gif"){
		//		$this->display = htmlspecialchars($exported);
		//	}
		//	else {
		//		if($format == "png" or $format == "gif"){
		//			$this->display = '<img src="data:image/png;base64,'.base64_encode ( $exported).'"/>';
			//	}
				//else {
				//	$this->display = $exported;
				//}
			//}
		//}
		//else {
		//	$this->display = $this->getChangeViewHTML();
		//}
	}

	function displayJSON($srvr){
		$vstr = "?format=json";
		$this->changed->display = $this->changed->ldprops;
		$this->original->display = $this->original->ldprops;
		$this->display = $this->forward;
	}

	function displayHTML($srvr){
		$vstr = "?format=html";
		$flags = array("ns", "links");
		$this->changed->displayHTML($flags, $vstr, $srvr);
		$this->original->displayHTML($flags, $vstr, $srvr);
		$this->display = $this->getChangeViewHTML();
	}

	function displayTriples($srvr){
		$vstr = "?format=triples";
		$flags = array("ns", "links");
		$this->changed->displayTriples($flags, $vstr, $srvr);
		$this->original->displayTriples($flags, $vstr, $srvr);
		$this->display = $this->deltaAsTriples($orig_upd);
	}

	function displayQuads($srvr){
		$vstr = "?format=quads";
		$flags = array("ns", "links");
		$this->changed->displayQuads($flags, $vstr, $srvr);
		$this->original->displayQuads($flags, $vstr, $srvr);
		$this->display = $this->deltaAsTriples();
	}

	function getDisplayFormat(){
		//$result = $this->changed;
		//$result->original = $this->original;
		$other = clone $this;
		unset($other->nsres);
		return $other;
	}

	function getDQSTests($type = false){
		$itests = array();
		if(isset($this->changed->meta['instance_dqs'])){
			$itests = $this->changed->meta['instance_dqs'];
			if($type == "instance"){
				return $itests;
			}
		}
		if(isset($this->changed->meta['schema_dqs'])){
			$stests = $this->changed->meta['schema_dqs'];
			if($type == "schema"){
				return $stests;
			}
			return array_merge($stests, $itests);
		}
		return false;
	}
	

	
}

