<?php
/**
* Class representing an update to an ldo
*
* As updates are persistent things that may apply at any time to an object, they are first order things
* 
* @author Chekov
* @license GPL V2
*
*/
class LDOUpdate extends DacuraObject{
	/** @var the id of the update object in the db */
	var $id;
	/** @var the collection id of the update object in the db */
	var $cid;
	/** @var the ld type of the object the update applies to (candidate, graph, ontology) */
	var $type;
	/** @var the id of the ld object being updated */
	var $targetid;
	/** @var the status of the update one of LDO::$valid_statuses */
	var $status;
	/** @var the timestamp for when the update was created */
	var $created;
	/** @var the timestamp for when the update was last modified */
	var $modified;
	/** @var the forward ld command to carry out the update */
	var $forward;
	/** @var the backward ld command to undo the update */
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
	/** @var LDO the ld object that the update applied to in its unchanged state */
	var $original; 
	/** @var LDO the ld object that was (or would be) created if this update was applied to the original above */
	var $changed;	
	/** @var LDDelta object containing details of the differences between the original and changed state of the object */
	var $delta; 
	
	/**
	 * Set the id (for saved updates) and optionally the original LDO object
	 * @param string $id the update id as in the db
	 * @param LDO $original reference to the original LDO object that the update is applied to
	 */
	function __construct($id = false, &$original = false){
		$this->id = $id;
		if($original) $this->setOriginal($original);
		$this->created = time();
		$this->modified = time();
	}
	
	/**
	 * Clones a copy of the update object 
	 * Copies across the basic objects and ld arrays into the new object. 
	 */
	function __clone(){
		$this->original = $this->original;
		$this->changed = $this->changed;
		$this->delta = $this->delta;
		$this->meta = deepArrCopy($this->meta);
		$this->forward = deepArrCopy($this->forward);
		$this->backward = deepArrCopy($this->backward);
	}
	
	function is_multigraph(){
		return $this->original->isMultigraphUpdate($this->forward);
	}
	
	function is_multigraph_undo(){
		if($this->changed){
			return $this->changed->isMultigraphUpdate($this->backward);
		}
		else {
			return $this->is_multigraph();
		}
	}
	
	
	
	/**
	 * Sets the Namespace Resolver object for the update methods to use
	 * @param NSResolver $nsres
	 */
	function setNamespaces($nsres){
		$this->nsres = $nsres;
	}
	
	/**
	 * Loads the update from the database record
	 * @param array $row - name value row as returned by db query
	 */
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

	/**
	 * Specifies the original LDO object that the update will apply to
	 * @param LDO $original
	 */
	function setOriginal(LDO &$original){
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
	 * Initialises some of the object's fields from the original ldo that is being acted upon..
	 */
	function initFromOriginal(){
		$this->from_version = $this->original->version();
		$this->cid = $this->original->cid;
		$this->type = $this->original->ldtype();
	}
	
	/**
	 * Load an update from an LDO object that was read from the API
	 * 
	 * We can assume that the original has already been loaded at this point - if it hasn't there's 
	 * something wrong with the code..
	 *
	 * @param LDO $update the update(d) ldo object
	 * @param array $update_meta meta-data about the update itself
	 * @param string $mode replace|update|rollback - the mode that the update is taking place in..
	 * @param LdDacuraServer - server object to supply access to services, etc
	 */
	function loadFromAPI(LDO &$update, $update_meta, $mode, LdDacuraServer &$srvr){
		if(!$this->isLegalContext($srvr->cid())){
			return $this->failure_result("Cannot update object through context ".$this->cid(), 403);
		}
		if($update_meta){
			$this->meta = $update_meta;
			if(isset($this->meta['status'])){
				$this->status($this->meta['status']);
			}
		}
		if($mode == "update"){
			if($update->fragment_id){
				$this->forward =& $this->original->getFragmentPath("_:".$update->fragment_id, false, $update->ldprops["_:".$update->fragment_id]);
			}
			else {
				$this->forward = $update->ldprops;
			}
			if($update->meta){
				$this->forward['meta'] = $update->meta;
			}
			if(!$this->calculateChanged($mode)){
				return false;
			}
		}
		else {
			if($update->fragment_id){
				$this->changed = clone($this->original);
				$this->changed->setFragment($update->fragment_id, $update->ldprops["_:".$update->fragment_id]);				
			}
			else {
				$this->changed = $update;				
			}		
			$this->changed->version = $this->original->latest_version + 1;
			$this->from_version = $this->original->version;
			//$this->changed->to_version
			$this->changed->readStateFromMeta();
		}		
		if($this->original->validate("view", $srvr) && !$this->changed->validate("view", $srvr)){
			return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
		}
		return $this->calculateDelta();
	}		

	/**
	 * Called after an update record is loaded from the database - stores the original,
	 * applies the update and calculates the delta
	 * @param LDO $original - the ldo object in the state that the update will be applied to
	 * @return boolean
	 */
	function calculate(LDO &$original){
		$this->setOriginal($original);
		if($this->calculateChanged("view")){
			return $this->calculateDelta();
		}		
	}
	
	/**
	 * Calculates the changed version of the LDO by copying the original and applying the forward transform specified in the update to it.
	 * @param string $mode the edit mode that the calculation is happening in
	 * @param boolean $is_multi true if the update is a multi-graph update command
	 * @return boolean true if no errors
	 */
	function calculateChanged($mode){
		$this->changed = clone $this->original;
		$this->changed->version = $this->to_version ? $this->to_version : 1 + $this->original->latest_version;
		if($this->changed->version > $this->changed->latest_version){
			$this->changed->latest_version = $this->changed->version;
		}
		if(!$this->changed->update($this->forward, $mode)){
			return $this->failure_result($this->changed->errmsg, $this->changed->errcode);
		}
		$this->changed->readStateFromMeta();
		return true;
	}
	
	/**
	 * Calculates the differences between the original and stored versions of the LDO and copies the results into the updates forward and backward commands
	 * @return boolean - always returns true...
	 */
	function calculateDelta(){
		$this->delta = $this->original->compare($this->changed);
		$this->forward = $this->delta->forward;
		$this->backward = $this->delta->backward;
		return true;
	}
	
	
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

	/**
	 * Has this update been accepted and published to the ld object store?
	 * @return boolean
	 */
	function published(){
		return $this->is_accept();
	}
	
	/**
	 * Has the original that the update applies to been published to the triplestore? 
	 * @return boolean
	 */
	function originalPublished(){
		if(!$this->original) return false;
		return $this->original->is_accept();
	}
	
	/**
	 * Is the changed version that the update creates in a published state? 
	 * @return boolean
	 */
	function changedPublished(){
		if(!$this->changed) return false;
		return $this->changed->is_accept();
	}
	
	/**
	 * Are both the original and the changed ldo versions published (live update of triplestore)?
	 * @return boolean
	 */
	function bothPublished(){
		return $this->changedPublished() && $this->originalPublished();
	}
	
	/**
	 * have the forward and backward fields of the object have been loaded?
	 * @return boolean
	 */
	function FBLoaded(){
		return isset($this->forward) && is_array($this->forward) && isset($this->backward) && is_array($this->backward);
	}
	
	/**
	 * are there any changes in the linked data part of the update?
	 * @return boolean
	 */
	function FBChanges(){
		return $this->FBLoaded() && (count($this->forward) > 0 && count($this->backward) > 0);
	}
	
	/**
	 * The url of the object being updated
	 * @return string
	 */
	function cwurl(){
		return $this->original->cwurl;
	}

	/**
	 * The url of the update itself
	 * @return string
	 */
	function url(){
		$url = $this->cwurl();
		if($url){
			$url = substr($base, 0, strrpos($base, "/"))."/update/".$this->id;
		}
		return $url;
	}
	
	/**
	 * Returns the ld type of the object that the update applies to (graph, ontology, candidate)
	 * @return string the typename (all lower case)
	 */
	function ldtype(){
		if($this->type) return $this->type;
		return $this->original->ldtype();
	}

	/**
	 * Are two updates the same as one another? 
	 * 
	 * If their forward transitions are the same, the update is considered the same
	 * @param LDOUpdate $other another LDO object for comparison
	 * @return boolean true if they are the same
	 */
	function sameAs(LDOUpdate &$other){
		return $this->forward == $other->forward;
	}
	
	/**
	 * Is there any differences between the original and changed versions of the ldo? 
	 * @return boolean - true if there are no changes
	 */
	function nodelta(){
		return !$this->delta or !$this->delta->containsChanges();
	}
	
	/* DB serialisation - functions called to populate db record from object */
	
	/**
	 * the version of the ldo that the update applies to
	 * @return integer
	 */
	function from_version(){
		return $this->from_version;
	}
	
	/**
	 * the version of the ldo that the update creates
	 * @return integer zero indicates that the update has not been applied
	 */
	function to_version(){
		return $this->to_version;
	}
	
	/**
	 * Json encode the forward command 
	 * @return string json encoded forward command
	 */
	function get_forward_json(){
		$cmd = $this->forward ? $this->forward : $this->delta->forward;
		$x = json_encode($cmd);
		return ($x) ? $x : "{}";
	}
	
	/**
	 * Json encode the backward 'undo' command 
	 * @return string
	 */
	function get_backward_json(){
		$cmd = $this->backward ? $this->backward : $this->delta->backward;
		$x = json_encode($cmd);
		return ($x) ? $x : "{}";
	}
	
	/**
	 * Get the update's meta-data as a json string
	 * @return string
	 */
	function get_meta_json(){
		return $this->meta ? json_encode($this->meta): "{}";
	}
	
	/**
	 * Does the update request come from a context that has authority for the ldo?
	 * @param string $ocid - user's collection context
	 * @return boolean
	 */
	function isLegalContext($cid){
		if($cid == "all" or ($this->original->cid != "all" && $this->original->cid == $cid)){
			return true;
		}
		return false;
	}
	
	/**
	 * Returns a list of the named graphs affected by the update
	 * @param string $mgurl the default graph url to be used if it is not a multi-graph update
	 * @return array urls of the updated named graphs
	 */
	function getUpdatedNamedGraphs($mgurl){
		if($this->delta->is_multigraph()){
			return $this->delta->getUpdatedNGIDs();				
		}
		return array($mgurl);
	}
	
	/**
	 * Get the insert and delete quads to execute the update on a particular named graph
	 * @param string $gname the graph url (id)
	 * @return array {insert: quad_array, delete: quad_array}
	 */
	function getNGQuads($gname){
		$added = $this->delta->getInsertQuads($gname);
		$deleted = $this->delta->getDeleteQuads($gname);				
		return array("insert" => $added, "delete" => $deleted);
	}
	
	/**
	 * Return an array of all the quads added across all graphs.
	 * @return array - array of [s,p,o,g] quads:
	 */
	function addedQuads(){
		return $this->delta->getInsertQuads();
	}

	/**
	 * Return an array of all the quads added across all graphs.
	 * @return array - array of [s,p,o,g] quads:
	 */
	function deletedQuads(){
		return $this->delta->getDeleteQuads();
	}
	
	/**
	 * Compares one LDOUpdate to another one for a single graph and returns the quads that would be needed to be changed
	 * to change this update into the other update. 
	 * @param LDOUpdate $other
	 * @param string $gname the graph id of the comparison
	 * @return array - array of [s,p,o,g] quads with g being = gname for all
	 */
	function deltaAsNGQuads(LDOUpdate &$other, $gname){
		$added = $this->delta->getInsertQuads($gname);
		$deleted = $this->delta->getDeleteQuads($gname);
		$oadded = $other->delta->getInsertQuads($gname);
		$odeleted = $other->delta->getInsertQuads($gname);
		return $this->consolidateTrips($added, $deleted, $oadded, $odeleted);
	}

	/**
	 * Checks through two parallel sets of deleted and added quads to remove any overlap, leaving only the delta-delta
	 * @param array $added added to this
	 * @param array $deleted deleted from this
	 * @param array $oadded original added triples
	 * @param array $odeleted original deleted triples
	 * @return array {add: [added_quads], "del": deleted_quads}
	 */
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

	
	/**
	 * Namespace resolution functions - called to compress all urls to prefix:id form
	 */
	function compressNS(){
		$this->nsres->compressNamespaces($this->forward, $this->cwurl(), $this->is_multigraph());
		$this->nsres->compressNamespaces($this->backward, $this->cwurl(), $this->is_multigraph_undo());
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
		$this->nsres->expandNamespaces($this->forward, $this->cwurl(), $this->is_multigraph());
		$this->nsres->expandNamespaces($this->backward, $this->cwurl(), $this->is_multigraph_undo());
		if($this->delta){
			$this->delta->expandNS($this->nsres);
		}
		$this->original && $this->original->expandNS();
		$this->changed && $this->changed->expandNS();
	}

	/**
	 * Produces a representation of the update for sending to the API
	 * @param string $format one of LDO::$valid_display_formats
	 * @param array $opts array of options / switches for display
	 * @return array {id, meta, format, options, insert, delete, changed, original, delta, analysis}
	 */
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
		if(isset($opts['show_delta']) && $opts['show_delta'] && isset($this->delta) && $this->delta){
			$apirep["delta"] = $this->delta->forAPI($format, $opts);
		}
		if(isset($opts['analysis']) && $opts['analysis'] && isset($this->analysis) && $this->analysis){
			$apirep["analysis"] = $this->analysis;
		}
		return $apirep;
	}
	
	/**
	 * Compares original and changed and returns a delta describing the differences
	 * @return LDDelta
	 */
	function compareLDTriples(){
		if($this->is_multigraph()){
			return compareLDGraphs($this->original->ldprops, $this->changed->ldprops, $this->cwurl());
		}
		else {
			return compareLDGraph($this->original->ldprops, $this->changed->ldprops, $this->cwurl());
		}
	}
	
	/** 
	 * @return array a name value array of meta values that have changed between old and new...
	 */
	function getMetaUpdates(){
		$meta = array();
		if($this->original->id != $this->changed->id) $meta['id'] = array($this->original->id, $this->changed->id);
		if($this->original->status != $this->changed->status) $meta['status'] = array($this->original->status, $this->changed->status);
		if($this->original->ldtype() != $this->changed->ldtype()) $meta['type'] = array($this->original->ldtype, $this->changed->ldtype);
		if($this->original->cid != $this->changed->cid) $meta['cid'] = array($this->original->cid, $this->changed->cid);
		foreach($this->original->meta as $k => $mv){
			if(!isset($this->changed->meta[$k])){
				$meta[$k] = array($mv, null);
			}
			elseif($mv != $this->changed->meta[$k]) {
				$meta[$k] = array($mv, $this->changed->meta[$k]);
			}		
		}
		foreach($this->changed->meta as $k => $mv){
			if(!isset($this->original->meta[$k])){
				$meta[$k] = array(null, $mv);
			}
		}
		return $meta;
	}
	
	/**
	 * Retrieves the content of the update in a particular format for display
	 * @param string $format format required - one of LDO::valid_display_formats
	 * @param array $options view options
	 * @param LdDacuraServer $srvr server object to access its functions
	 * @param string $for - display | internal
	 * @return boolean true if it worked
	 */
	function getContentInFormat($format, $options, LdDacuraServer &$srvr, $for = "internal"){
		$this->changed->display($format, $options, $srvr);
		$this->original->display($format, $options, $srvr);
		return true;
	}
	
}

