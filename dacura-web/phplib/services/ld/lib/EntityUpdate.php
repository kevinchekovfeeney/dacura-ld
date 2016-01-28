<?php

include_once("phplib/libs/easyrdf-0.9.0/lib/EasyRdf.php");
include_once("LDUtils.php");

class EntityUpdate extends DacuraObject{
	var $id;
	var $cid;
	var $type;
	var $targetid;
	var $status;
	var $created;
	var $modified;
	var $forward;
	var $backward;
	var $from_version;
	var $to_version = 0;
	var $meta; //meta data about the update itself

	var $cwurl;//closed world url of the entity being updated
	var $nsres;//name space resolver
	//complex objects - any of them can be calculated from 1.5. (original + delta.forward = changed) (changed + delta.backward = original)
	var $original; //the original state of the target candidate
	var $changed;	//the changed state of the target candidate (if the update request was to be accepted)
	var $delta; // LDDelta object describing changes from old to new

	function __construct($id = false, &$original = false, $cwurl = false){
		//parent::__construct($original->id);
		$this->id = $id;
		if($original) $this->setOriginal($original);
		$this->cwurl = $cwurl;
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
	
	function getEntityType(){
		if($this->type) return $this->type;
		return strtolower(substr(get_class($this), 0, strlen(get_class($this)) - strlen("UpdateRequest")));
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
			$fdelta = compareLD($this->targetid, $this->forward, $other->forward, $this->cwurl);
			$bdelta = compareLD($this->targetid, $this->backward, $other->backward, $this->cwurl);
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

	function deltaAsTriples($other){
		$added = $this->addedCandidateTriples();
		$deleted = $this->deletedCandidateTriples();
		$oadded = $other->addedCandidateTriples();
		$odeleted = $other->deletedCandidateTriples();
		return $this->consolidateTrips($added, $deleted, $oadded, $odeleted);
	}

	function getMetaUpdates(){
		$meta = array();
		if($this->original->id != $this->changed->id) $meta['id'] = array($this->original->id, $this->changed->id);
		if($this->original->status != $this->changed->status) $meta['status'] = array($this->original->status, $this->changed->status);
		//if($this->original->type != $this->changed->type) $meta['type'] = array($this->original->type, $this->changed->type);
		if($this->original->cid != $this->changed->cid) $meta['cid'] = array($this->original->cid, $this->changed->cid);
		if($this->original->did != $this->changed->did) $meta['did'] = array($this->original->did, $this->changed->did);
		return $meta;
	}

	function published(){
		return $this->get_status() == "accept";
	}

	function originalPublished(){
		if(!$this->original) return false;
		return $this->original->get_status() == "accept";
	}

	function changedPublished(){
		if(!$this->changed) return false;
		return $this->changed->get_status() == "accept";
	}

	function bothPublished(){
		return $this->changedPublished() && $this->originalPublished();
	}

	function setOriginal(&$original){
		$this->original = $original;
		if(!(isset($this->targetid) and $this->targetid)){
			$this->targetid = $original->id;
		}
		if(!(isset($this->cwurl) and $this->cwurl)){
			$this->cwurl = $original->cwurl;
		}
		if(!(isset($this->nsres) and $this->nsres)){
			$this->nsres = $original->nsres;
		}
		$this->from_version = $this->original->version();
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
			$pv = new LDPropertyValue($v, $this->cwurl);
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

	function calculateChanged($opts = array()){
		//options
		$backward = (isset($opts['direction']) && $opts['direction'] == "backward") ? true : false;
		$demand_id_allowed = isset($opts['demand_id_allowed']) && $opts['demand_id_allowed'];
		$force_inserts = isset($opts['force_inserts']) && $opts['force_inserts'];
		$calc_delta = isset($opts['calculate_delta']) && $opts['calculate_delta'];
		$val_delta = isset($opts['validate_delta']) && $opts['validate_delta'];

		$this->changed = clone $this->original;
		$this->changed->version = $this->to_version ? $this->to_version : 1 + $this->changed->latest_version;
		if($this->changed->version > $this->changed->latest_version){
			$this->changed->latest_version = $this->changed->version;
		}
		$contents = ($backward) ? $this->backward : $this->forward;
		if(!$this->changed->update($contents, $force_inserts, $demand_id_allowed) && $this->changed->compliant()){
			return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
		}
		$this->changed->readStateFromMeta();
		if($calc_delta){
			return $this->calculateDelta($val_delta);
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
	
	/*
	 * Called when a candidate update request is sent to the API
	 * $obj is a LD property structure
	 */
	function loadFromAPI($cnt, $meta, $format, $opts = false){
		if($opts == false) $opts = $this->getUpdateOptions();
		$this->initFromOriginal();
		$cmd = array();
		if($format == "json"){ //native format
			$cmd = $cnt;
			if($meta){
				$cmd['meta'] = $meta;
			}
			$this->forward = $cmd;
			$this->expandNS();
			$opts = $this->getUpdateOptions();
			if(!$this->calculateChanged($opts)){
				return false;
			}
		}
		else {
			$imported = $this->import($format, $cnt);
			if(!$this->calculateImported($meta, $imported)){
				return false;
			}
		}
		return true;
	}
	
	function import($format, $txt){
		$graph = new EasyRdf_Graph($this->original->id, $txt, $format, $this->original->id);
		$op = $graph->serialise("php");
		$ld = importEasyRDFPHP($op);
		return $ld;
	}

	function calculateDelta($validate = false){
		$this->delta = $this->original->compare($this->changed);
		if($validate && $this->FBLoaded()){
			$fdelta = compareLD($this->targetid, $this->delta->forward, $this->forward, $this->cwurl);
			if($fdelta->containsChanges()){
				//opr($fdelta);
				//opr($this);
				return $this->failure_result("Update $this->id to $this->targetid: Mismatch between calculated changes and stored forward transition.", 400);
			}
			$bdelta = compareLD($this->targetid, $this->delta->backward, $this->backward, $this->cwurl);
			if($bdelta->containsChanges()){
				return $this->failure_result("Update $this->id to $this->targetid: Mismatch between calculated changes and stored backward transitions", 400);
			}
		}
		$this->forward = $this->delta->forward;
		$this->backward = $this->delta->backward;
		return true;
	}

	function compressNS(){
		compressNamespaces($this->forward, $this->nsres, $this->cwurl);
		compressNamespaces($this->backward, $this->nsres, $this->cwurl);
		if($this->delta){
			$this->delta->compressNS($this->nsres);
		}
		$this->original && $this->original->compressNS();
		$this->changed && $this->changed->compressNS();
	}

	function expandNS(){
		expandNamespaces($this->forward, $this->nsres, $this->cwurl);
		expandNamespaces($this->backward, $this->nsres, $this->cwurl);
		if($this->delta){
			$this->delta->expandNS($this->nsres);
		}
		$this->original && $this->original->expandNS();
		$this->changed && $this->changed->expandNS();
	}

	function get_status(){
		return $this->status;
	}

	/**
	 * Does the update request come from a context that has authority for the candidate?
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
			$pv = new LDPropertyValue($v, $this->cwurl);
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
				$dpv = new LDPropertyValue($nv, $this->cwurl);
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
			$dpv = new LDPropertyValue($dv, $this->cwurl);
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
					$pv2 = new LDPropertyValue($val2, $this->cwurl);
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


	function deletedCandidateTriples(){
		$ndelta = compareLD($this->targetid, $this->original->ldprops, $this->changed->ldprops, $this->cwurl);
		return $ndelta->candidateDeletes(array($this->changed, "showTriples"));
	}

	function addedCandidateTriples(){
		$ndelta = compareLD($this->targetid, $this->original->ldprops, $this->changed->ldprops, $this->cwurl);
		return $ndelta->candidateInserts(array($this->changed, "showTriples"));
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

	function set_status($v, $is_latest = false){
		$this->status = $v;
	}

	function showUpdateResult($format, $dacura_server) {
		if($dacura_server->isNativeFormat($format)){
			if($format == "html"){
				$this->displayHTML($dacura_server);
			}
			elseif($format == "triples"){
				$this->displayTriples($dacura_server);
			}
			elseif($format == "quads"){
				$this->displayQuads($dacura_server);
			}
			else{
				$this->displayJSON($dacura_server);
			}
		}
		else {
			$this->displayExport($format, $dacura_server);
		}
		return $this->getDisplayFormat();
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

