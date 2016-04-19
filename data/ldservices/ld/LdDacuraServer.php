<?php
include_once("phplib/PolicyEngine.php");
include_once("lib/EntityCreateRequest.php");
include_once("lib/EntityUpdate.php");
include_once("lib/LDEntity.php");
include_once("lib/Schema.php");
include_once("lib/GraphManager.php");
require_once("lib/AnalysisResults.php");
require_once("lib/NSResolver.php");
require_once("LdService.php");
include_once("LDDBManager.php");


/*
* This class implements the basic processing pipeline of dacura linked data objects
* Particular entity types can override whichever parts they want
* It provides defered updates and version management / linked data conformance
 */

class LdDacuraServer extends DacuraServer {

	var $dbclass = "LDDBManager";
	var $policy; //policy engine to decide what to do with incoming requests
	var $graphman; //graph manager object
	var $nsres; //the namespace resolving service - system wide
	var $cwurlbase = false;
	var $graphbase = false;
	var $schema = false;

	function __construct($service){
		parent::__construct($service);
		$this->policy = new PolicyEngine();
		$this->graphman = new GraphManager($this->settings);
		$this->loadNamespaces();
	}
	
	function loadNamespaces(){
		$onts = $this->getEntities(array("type" => "ontology"));
		//$onts = $this->getEntities(array("type" => "ontology", "status" => "accept"));//introduces bug of missing url in dependency listing.
		$this->nsres = new NSResolver();
		foreach($onts as $i => $ont){
			$ont['meta'] = json_decode($ont['meta'], true);
			if(isset($ont['id']) && $ont['id'] && isset($ont['meta']['url']) && $ont['meta']['url']){
				$this->nsres->prefixes[$ont['id']] = $ont['meta']['url'];
			}
		}
	}
	
	function getEntityTypeFromClassname(){
		$cname = get_class($this);
		return substr($cname, -(strlen("DacuraServer")));
	}
	
	
	function createNewEntityObject($id, $type){
	 	$this->update_type = $type;
	 	$cname = ucfirst($type)."CreateRequest";
	 	$obj = new $cname($id);
	 	$obj->setNamespaces($this->nsres);
	 	return $obj; 
	}
	
	function createNewEntityUpdateObject($oent, $type){
	 	$this->update_type = $type;
		$uclass = ucfirst($type)."UpdateRequest";
		$uent = new $uclass(false, $oent);
		return $uent;
	}
	
	function loadSchemaFromContext(){
		$filter = array("type" => "graph", "collectionid" => $this->cid(), "include_all" => true);
		$ents = $this->getEntities($filter);
		$sc = new Schema($this->cid(), $this->durl());
		if($ents){
			$sc->load($ents);
		}
		elseif($this->errcode != 404){
			return false;
		}
		$sc->nsres = $this->nsres;
		return $sc;
	}
	
	function getPolicyDecision($action, $args){
		return $this->policy->getPolicyDecision($action, $this->update_type, $args);
	}
	
	
	/*
	 * This is the LD Quality control interface
	 * All of these methods return a graph analysis results object
	 */
	/*
	 * Called when an entity's state changes to 'accept' 
	 * It is then 'published' (what that means exactly depends on the type of the entity)
	 * Takes a LdEntity object
	 */
	function publishEntityToGraph($ent, $is_test=false){
		$ar = new GraphAnalysisResults("entity $ent->id published (test: $is_test)");
		return $ar->accept();
	}
	
	function deleteEntityFromGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("entity $ent->id removed (test: $is_test)");
		return $ar->accept();
	}
	
	/*
	 * Take EntityUpdateRequest Object
	 */
	
	/*
	 * Called when an entity is updated 
	 * The updates are then published 
	 */
	function updateEntityInGraph($uent, $is_test = false){
		$ar = new GraphAnalysisResults("entity " . $uent->original->id." updated (test: $is_test)");
		return $ar->accept();
	}
	
	function undoEntityUpdate($uent, $is_test = false){
		$ar = new GraphAnalysisResults("rolling back update $ent->id to entity ".$uent->original->id." (test: $is_test)");
		return $ar->accept();		
	}
	
	/*
	 * Called to update an existing update (e.g. to make minor corrections to an update without creating a new revision.
	 */
	function updatePublishedUpdate($uenta, $uentb, $is_test = false){
		$ar = new GraphAnalysisResults("update update $uenta->id to entity ".$uenta->original->id." (test: $is_test)");
		return $ar->accept();		
	}
	

	/*
	 * Loading lists of entities and updates
	 */
	function getEntities($filter){
		$data = $this->dbman->loadEntityList($filter);
		if(!$data){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $data;
	}
	
	function getUpdates($filter){
		$data = $this->dbman->loadUpdatesList($filter);
		if($data){
			return $data;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}

	/*
	 * the getEntity version returns a AR object to support direct API access
	 * also loads things like history and updates -> for UI 
	 */
	function getEntity($entity_id, $type, $fragment_id = false, $version = false, $options = array()){
		$action = "Fetching " . ($fragment_id ? "fragment $fragment_id from " : "");
		$this->update_type = $type;
		$action .= "$type $entity_id". ($version ? " version $version" : "");
		$ar = new SimpleRequestResults($action);
		$ent = $this->loadEntity($entity_id, $type, $this->cid(), $fragment_id, $version, $options);
		if(!$ent){
			return $ar->failure($this->errcode, "Error loading $type $entity_id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view", $ent));
		if($ar->is_accept()){
			$ar->set_result($ent);
		}
		return $ar;
	}
	
	/*
	 * the load Entity version returns the normal dacura error codes...
	 */
	function loadEntity($entity_id, $type, $cid, $fragment_id = false, $version = false, $options = array()){
		$ent = $this->dbman->loadEntity($entity_id, $type, $cid, $options);
		if(!$ent){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if($options && in_array('history', $options)){
			$ent->history = $this->loadHistoricalRecord($ent);
			$updopts = array("include_all" => true, 'type' => $type, "collectionid" => $this->cid(), "entityid" => $entity_id);
			$ent->updates = $this->getUpdates($updopts);
		}
		$ent->nsres = $this->nsres;
		if($version && $ent->version() > $version){
			if(!$this->rollBackEntity($ent, $version)){
				return false;
			}
		}
		if($fragment_id){
			$show_context = true;//should be in options
			$ent->buildIndex();
			if($this->cwurlbase){
				$fid = $this->cwurlbase."/".$entity_id."/".$fragment_id;
			}
			else {
				$fid = $fragment_id;
			}
			$frag = $ent->getFragment($fid);
			$ent->fragment_id = $fid;
			if($frag && $show_context){
				$ent->setContentsToFragment($fid);
				$types = array();
				foreach($frag as $fobj){
					if(isset($fobj['rdf:type'])){
						$types[] = $fobj['rdf:type'];
					}
				}
				$ent->fragment_paths = $ent->getFragmentPaths($fid);
				$ent->fragment_details = count($types) == 0 ? "Undefined Type" : "Types: ".implode(", ", $types);
			}
			else {
				if($frag){
					$ent->ldprops = $frag;
				}
				else {
					return $this->failure_result("Failed to load fragment $fid", 404);
				}
			}
		}
		return $ent;
	}

	function loadHistoricalRecord($oent, $from_version = 0, $to_version = 1){
		$ent = clone $oent;
		$histrecord = array(array(
			'status' => $ent->status,
			'version' => $ent->version,
			'version_replaced' => 0				
		));
		$history = $this->getEntityHistory($ent, $to_version);
		foreach($history as $i => $old){
			$histrecord[count($histrecord) -1]['created_by'] = $old['eurid'];
			$histrecord[count($histrecord) -1]['forward'] = $old['forward'];
			$histrecord[count($histrecord) -1]['backward'] = $old['backward'];
			$back_command = json_decode($old['backward'], true);
			if(!$ent->update($back_command, true)){
				return $this->failure_result($ent->errmsg, $ent->errcode);
			}
			$histrecord[count($histrecord) -1]['createtime'] = $old['modtime'];
			$histrecord[] = array(
				'status' => isset($ent->meta['status']) ? $ent->meta['status'] : $ent->status, 
				"version" => $old['from_version'],
				"version_replaced" => $old['modtime']
			);
		}
		$histrecord[count($histrecord) -1]['forward'] = json_encode($ent->ldprops);
		$histrecord[count($histrecord) -1]['backward'] = json_encode(array());
		$histrecord[count($histrecord) -1]['createtime'] = $ent->created;
		$histrecord[count($histrecord) -1]['created_by'] = 0;
		//$histrecord[] 
		return $histrecord;
	}
	
	/*
	 * Rolls an entity back to some particular version
	 */
	function rollBackEntity(&$ent, $version){
		$history = $this->getEntityHistory($ent, $version);
		foreach($history as $i => $old){
			if($old['from_version'] < $version){
				continue;
			}
			$back_command = json_decode($old['backward'], true);
			if(!$ent->update($back_command, true)){
				return $this->failure_result($ent->errmsg, $ent->errcode);
			}
			$ent->status = isset($ent->meta['status']) ? $ent->meta['status'] : $ent->status;
			$ent->version = $old['from_version'];
			$ent->version_created = $old['modtime'];
			if($i == 0){
				$ent->version_replaced = $ent->modified;
			}
			else {
				$ent->version_replaced = $history[$i-1]['modtime'];
			}
		}
		return $ent;
	}
	
	/*
	 * Returns a list of all the updates to an entity that have been accepted, organised in order of last to first...
	 */
	function getEntityHistory($ent, $version){
		$history = $this->dbman->loadEntityUpdateHistory($ent, $version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $history;
	}
	
	
	/*
	 * Same pattern applies for retreiving updates
	 */
	function getUpdate($id, $options = array()){
		$ar = new SimpleRequestResults("Loading Update Request $id from DB", false);
		$ur = $this->loadUpdate($id, $options);
		if(!$ur){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view update", $ur));
		if($ar->is_accept()){
			$ar->set_result($ur);
		}
		return $ar;
	}	
	
	function loadUpdate($id, $options = array(), $vfrom = false, $vto = false){
		$eur = $this->dbman->loadEntityUpdateRequest($id, $options);
		$vto = $vto ? $vto : $eur->to_version();
		$vfrom = $vfrom ? $vfrom : $eur->from_version();
		$orig = $this->loadEntity($eur->targetid, $eur->type, $eur->cid, $eur->did, false, $vfrom, $options);
		if(!$orig){
			return $this->failure_result("Failed to load Update $id - could not load original " .$this->errmsg, $this->errcode);
		}
		$eur->setOriginal($orig);
		$changed = false;
		if($vto > 0){
			$changed = $this->loadEntity($eur->targetid, $eur->type, $eur->cid, $eur->did, false, $vto, $options);
			if(!$changed){
				return $this->failure_result("Loading of $this->entity_type update $id failed - could not load changed ".$this->errmsg, $this->errcode);
			}
		}
		if(!$eur->calculate($changed)){
			return $this->failure_result($eur->errmsg, $eur->errcode);
		}
		return $eur;
	}
		
	function createEntity($type, $create_obj, $demand_id, $options, $test_flag = false){
		$ar = new UpdateAnalysisResults("Creating $type", $test_flag);
		if($demand_id){
			if($this->demandIDValid($demand_id, $type)){
				$id = $this->generateNewEntityID($type, $demand_id);
			}
			else {
				$id = $this->generateNewEntityID($type);
			}
			if($id != $demand_id){
				$this->addIDAllocationWarning($ar, $type, $test_flag, $id);
			}
		}
		else {
			$id = $this->generateNewEntityID($type);
		}
		$nent = $this->createNewEntityObject($id, $type);
		if(!$nent){
			return $ar->failure($this->errcode, "Request Create Error", "New $type object sent to API had formatting errors. ".$this->errmsg);				
		}
		$nent->setContext($this->cid());
		if(!$nent->loadFromAPI($create_obj)){
			return $ar->failure($nent->errcode, "Protocol Error", "New $type object sent to API had formatting errors. ".$nent->errmsg);
		}
		elseif(!$nent->validate()){
			return $ar->failure($nent->errcode, "Invalid create $type request", "The create request contained errors: ".$nent->errmsg);
		}
		elseif(!$nent->expand($this->policy->demandIDAllowed("create", $type, $nent))){
			return $ar->failure($nent->errcode, "Invalid Create Request", $nent->errmsg);
		}
		$nent->expandNS();//use fully expanded urls internally - support prefixes in input
		$ar->add($this->getPolicyDecision("create", $nent));
		if($ar->is_reject()){
			$nent->set_status($ar->decision);
			if($this->policy->storeRejected($type, $nent) && !$test_flag){
				if(!$this->dbman->createEntity($nent, $type)){
					$ar->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected create of $type.", $this->dbman->errmsg);
				}
			}
			return $ar;
		}
		$dont_publish = $ar->decision != "accept" || $test_flag;
		$gu = $this->publishEntityToGraph($nent, $dont_publish);
		if($gu->is_reject() && $ar->is_accept() && $this->policy->rollbackToPending("create", $type, $nent)){
			$ar->addWarning("Publication", "Rejected by Graph Management Service", "State changed from accept to pending");
			$nent->set_status("pending");
			$gu = $this->publishEntityToGraph($nent, "pending", $test_flag);
			$ar->setReportGraphResult($gu, true);
			$ar->decision = "pending";
		}
		else {
			$ar->setReportGraphResult($gu);
		}
		$nent->set_status($ar->decision);
		//$ar->setCandidateGraphResult($nent->internalTriples());
		$ar->setCandidateGraphResult($nent->triples());
		if(!($test_flag || $ar->is_confirm())){
			if(!$this->dbman->createEntity($nent, $type)){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to create database candidate record ". $this->dbman->errmsg);
				$ar->add($disaster);
				if($ar->includesGraphChanges()){
					$recovery = $this->deleteEntityFromGraph($nent);
					$ar->undoReportGraphResult($recovery);
				}
			}
		}
		$ar->set_result($nent->getDisplayFormat());
		return $ar;
	}

	/*
	 * Methods dealing with Entity ID generation
	 */	
	function generateNewEntityID($type, $demand = false){
		if($demand){
			return $demand;
		}
		return uniqid_base36(true);
	}
	
	function addIDAllocationWarning(&$ar, $type, $test_flag, $id){
		$txt = "Requested ID could not be granted (".$this->errmsg.").";
		$extra = "";
		if($test_flag){
			$txt = "An ID will be randomly generated when the $type is created.";
			$extra = "$id is an example of a randomly generated ID, it will be replaced by another if the $type is created";
		}
		else {
			$txt = "The $type was allocated a randomly generated ID: $id";
		}
		$ar->addWarning("Generating id", $txt, $extra);
	}
	
	function updateEntity($target_id, $type, $cnt, $meta, $fragment_id, $options = array(), $test_flag = false){
		$ar = new UpdateAnalysisResults("Update $target_id", $test_flag);
		$oent = $this->loadEntity($target_id, $type, $this->cid(), $fragment_id);
		if(!$oent){
			if($this->errcode){
				return $ar->failure($this->errcode, "Failed to load $target_id", $this->errmsg);
			}
			else {
				return $ar->failure(404, "No such $this->entity_type", "$target_id does not exist.");
			}
		}
		$uent = $this->createNewEntityUpdateObject($oent, $type);
		if(!$uent){
			return $ar->failure(403, "Update Failed", "Cant create update object for $oent->id");				
		}
		$uent->setNamespaces($this->nsres);	
		$form = isset($options['format']) ? $options['format'] : "json";
		//is this entity being accessed through a legal collection context?
		if(!$uent->isLegalContext($this->cid())){
			return $ar->failure(403, "Access Denied", "Cannot update $oent->id through context ".$this->cid());
		}
		elseif(!$uent->loadFromAPI($cnt, $meta, $form)){
			return $ar->failure($uent->errcode, "Protocol Error", "Failed to load the update command from the API. ", $uent->errmsg);
		}
		if($uent->nodelta()){
			return $ar->reject("No Changes", "The submitted version is identical to the current version.");
		}
		$ar->add($this->getPolicyDecision("update", $uent));
		if($ar->is_reject()){
			$uent->set_status($ar->decision);
			if($this->policy->storeRejected("update ".$type, $uent) && !$test_flag){
				if(!$this->dbman->updateEntity($uent, $ar->decision)){
					$ar->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected update.", $this->dbman->errmsg);
				}
			}
			return $ar;
		}
		
		$this->checkUpdate($ar, $uent, $test_flag);
		if(($ar->is_accept() or $ar->is_pending()) && !$test_flag){
			if(!$this->dbman->updateEntity($uent, $ar->decision)){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to update database candidate record ". $this->dbman->errmsg);
				$ar->add($disaster);
				$this->rollBackUpdate($ar, $uent);
			}
		}
		//get stuff out of options...
		$format = isset($options['format']) ? $options['format'] : false;
		$ar->set_result($uent->showUpdateResult($format, $this));
		return $ar;
	}
	
	function rollbackUpdate(&$ar, &$uent){
		if($ar->includesGraphChanges()){
			$recovery = $this->undoUpdatesToGraph($uent);
			$ar->undoReportGraphResult($recovery);
		}
	}
	
	function checkUpdate(&$ar, &$uent, $test_flag){
		if($ar->is_accept() or $ar->is_confirm()){
			//unless the status of the candidate was accept, before or after, the change to the report graph is hypothetical
			$hypo = !($uent->changedPublished() || $uent->originalPublished());
			$gu = $this->publishUpdateToGraph($uent, $ar->decision, $hypo || $test_flag);
			if($ar->is_accept() && $uent->changedPublished() && $gu->is_reject()
					&& $this->policy->rollbackToPending("update", $uent)){
				$ar->addWarning("Update Publication", "Rejected by Quality Service", "Update state changed from accept to pending");
				$uent->set_status("pending");
				$gu = $this->publishUpdateToGraph($uent, "pending", true);
				$hypo = true;
				$ar->decision = "pending";
			}
			$ar->setReportGraphResult($gu, $hypo);
		}
		elseif($ar->is_pending()){
			$gu = $this->publishUpdateToGraph($uent, "pending", true);
			$ar->setReportGraphResult($gu, true);
		}
		$uent->set_status($ar->decision);
		$ar->setUpdateGraphResult($uent->compare());
		$meta_delta = $uent->getMetaUpdates();
		$ar->setCandidateGraphResult($uent->addedCandidateTriples(), $uent->deletedCandidateTriples(), !($ar->is_accept() || $ar->is_confirm()), $meta_delta);
	}
	
	function updateUpdate($id, $obj, $meta, $options, $test_flag = false){
		$ar = new UpdateAnalysisResults("Update $this->update_type $id", $test_flag);
		$orig_upd = $this->loadUpdate($id);
		if(!$orig_upd){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$new_upd = $this->loadUpdatedUpdate($orig_upd, $obj, $meta, $options);
		if(!$new_upd){
			return $ar->failure($this->errcode, "Failed to load updated version of $id", $this->errmsg);
		}
		if($new_upd->sameAs($orig_upd)){
			return $ar->reject("No Changes", "The new update is identical to the existing update - it will be ignored.");
		}
		if(!$new_upd->isLegalContext($this->cid())){
			return $ar->failure(403, "Access Denied", "Cannot update candidate $new_upd->targetid through context ".$this->cid());
		}
		if($new_upd->nodelta()){
			return $ar->reject("No changes", "The submitted version removes all changes from the update - it has no effect.");
		}
		$ar->add($this->getPolicyDecision("update update", array($orig_upd, $new_upd)));
		if($ar->is_reject()){
			return $ar;
		}
		//3 types of changes can be caused by updates to updates
		//1. Changes to the update itself (if ar->is_accept() and it is legal...)
		//2. Changes to a candidate (if either new or old update == accept -> there will be changes to the candidate graph
		//3. Changes to a report -> if updated candidate = accept or old candidate = accept
		//if the update is unpublished in both new and old, the update to both graphs is hypothetical
	
		$meta_delta = $new_upd->getMetaUpdates();
		$chypo = false;
		$umode = "normal";
		if($new_upd->published() && $orig_upd->published()){ //live edit
			$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			$trips = $new_upd->deltaAsTriples($orig_upd);
			$ar->setCandidateGraphResult($trips['add'], $trips['del'], $chypo, $meta_delta);
			$umode = "live";
		}
		elseif($new_upd->published()){ //publish new update
			$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			$ar->setCandidateGraphResult($new_upd->addedCandidateTriples(), $new_upd->deletedCandidateTriples(), $chypo, $meta_delta);
		}
		elseif($orig_upd->published()){ //unpublish update
			$umode = "rollback";
			//check here to see if there are any pending updates that are hanging off the latest version....
			if($this->dbman->pendingUpdatesExist($orig_upd->targetid, $this->update_type, $this->cid(), $orig_upd->to_version()) || $this->dbman->errcode){
				if($this->dbman->errcode){
					return $ar->failure($this->dbman->errcode, "Unpublishing of update $orig_upd->id failed", "Failed to check for pending updates to current version of candidate");
				}
				return $ar->failure(400, "Unpublishing of update $orig_upd->id not allowed", "There are pending updates on version ".$orig_upd->to_version()." of candidate $orig_upd->targetid");
			}
			$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			$ar->setCandidateGraphResult($orig_upd->deletedCandidateTriples(), $orig_upd->addedCandidateTriples(), $chypo, $meta_delta);
		}
		else { //edit unpublished
			$chypo = true;
			$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			$ar->setCandidateGraphResult($new_upd->addedCandidateTriples(), $new_upd->deletedCandidateTriples(), $chypo, $meta_delta);
		}
		if($umode == "rollback"){
			$hypo = $chypo || !($orig_upd->changedPublished() || $orig_upd->originalPublished());
		}
		else {
			$hypo = $chypo || !($new_upd->changedPublished() || $new_upd->originalPublished());
		}
		if($hypo or $test_flag or $ar->is_confirm()){
			$gu = $this->testUpdatedUpdate($new_upd, $orig_upd, $umode);
		}
		else {
			$gu = $this->saveUpdatedUpdate($new_upd, $orig_upd, $umode);
		}
		$ar->setReportGraphResult($gu, $hypo);
		if(!($ar->is_confirm() || $test_flag)){
			if($umode == "rollback"){
				$worked = $this->dbman->rollbackUpdate($orig_upd, $new_upd);
			}
			else {
				$worked = $this->dbman->updateUpdate($new_upd, $orig_upd->get_status());
			}
			if(!$worked){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to update database update record ".$orig_upd->id.". ". $this->dbman->errmsg);
				$ar->add($disaster);
				if($ar->includesGraphChanges()){
					if($umode == "rollback"){
						$recovery = $this->undoUpdatedUpdate($orig_upd, $new_upd);
					}
					else {
						$recovery = $this->undoUpdatedUpdate($new_upd, $orig_upd);
					}
					$ar->undoReportGraphResult($recovery);
				}
			}
		}
		$format = isset($options['format']) ? $options['format'] : false;
		$flags = isset($options['display']) ? $this->parseDisplayFlags($options['display']) : array();
		$version = isset($options['version']) ? $options['version'] : false;
		$ar->set_result($new_upd->showUpdateResult($format, $flags, $version, $this));
		return $ar;
	}
	
	function loadUpdatedUpdate($orig_upd, $obj, $meta, $options = array()){
		if(isset($meta['from_version']) && $meta['from_version'] && $meta['from_version'] != $orig_upd->original->get_version()){
			$norig = $this->loadEntity($orig_upd->targetid, $this->update_type, $this->cid(), false, $meta['version']);
			if(!$norig)	return false;
		}
		else {
			$norig = clone $orig_upd->original;
		}
		$ncur = $this->createNewEntityUpdateObject($norig, $this->update_type);
		$ncur->to_version = $orig_upd->to_version;
		if(isset($meta['status'])){
			$ncur->set_status($meta['status']);
		}
		else {
			$ncur->set_status($orig_upd->get_status());
		}
		$form = isset($options['format']) ? $options['format'] : "json";
		$opts = array(
				"demand_id_allowed" => $this->policy->demandIDAllowed("update $this->update_type", $ncur),
				"force_inserts" => true,
				"calculate_delta" => true,
				"validate_delta" => true
		);
		if(!$ncur->loadFromAPI($obj, $meta, $form, $opts)){
			return $this->failure_result($ncur->errmsg, $ncur->errcode);
		}
		return $ncur;
	}

	function testUpdatedUpdate($ncur, $ocur, $umode){
		if($umode == "rollback"){
			return $this->updatedUpdate($ocur, $umode, true);
		}
		elseif($umode == "live"){
			return $this->updatePublishedUpdate($ncur, $ocur, true);
		}
		else {
			return $this->updatedUpdate($ncur, $umode, true);
		}
	}
	
	function saveUpdatedUpdate($ncur, $ocur, $umode){
		if($umode == "rollback"){
			return $this->updatedUpdate($ocur, $umode);
		}
		elseif($umode == "live"){
			return $this->updatePublishedUpdate($ncur, $ocur);
		}
		else {
			return $this->updatedUpdate($ncur, $umode);
		}
	}
	
	function updatedUpdate($cur, $umode, $testflag = false){
		if($cur->bothPublished()){
			if($umode == "rollback"){
				return $this->undoEntityUpdate($cur, $testflag);
			}
			else {
				return $this->updateEntityInGraph($cur, $testflag);
			}
		}
		elseif($cur->originalPublished()){
			if($umode == "rollback"){
				return $this->deleteEntityFromGraph($cur->original, $testflag);
			}
			else {
				return $this->deleteEntityFromGraph($cur->changed, $testflag);
			}
		}
		elseif($cur->changedPublished() or $testflag) {
			if($umode == "rollback"){
				$dont_publish = ($testflag || $cur->original->status != "accept");
				return $this->publishEntityToGraph($cur->original, $dont_publish);
			}
			else {
				$dont_publish = ($testflag || $cur->changed->status != "accept");
				return $this->publishEntityToGraph($cur->changed, $dont_publish);
			}
		}
		else {
			$ar = new GraphAnalysisResults("Nothing to save to report graph");
			return $ar;
		}
	}
	
	/*
	 * Methods for interactions with the Quality Service / Graph Manager
	 */
	function publishUpdateToGraph($uent, $decision, $testflag ){
		if($uent->bothPublished()){
			$gu = $this->updateEntityInGraph($uent, $testflag );
		}
		elseif($uent->originalPublished()){
			$gu = $this->deleteEntityFromGraph($uent->original, $testflag);
		}
		else {
			if($testflag || $uent->changedPublished()){
				$dont_publish = ($testflag || $decision != "accept");
				$gu = $this->publishEntityToGraph($uent->changed, $dont_publish);
			}
			else {
				$gu = new GraphAnalysisResults("Nothing to save to graph");
			}
		}
		return $gu;
	}
		
	function undoUpdatesToGraph($uent){
		if($uent->bothPublished()){
			return $this->undoEntityUpdate($uent, false);
		}
		elseif($uent->originalPublished()){
			return $this->publishEntityToGraph($uent->original);//dr
		}
		elseif($uent->changedPublished()){
			return $this->deleteEntityFromGraph($uent->changed);//wr
		}
		$ar = new GraphAnalysisResults("Nothing to undo in report graph");
		return $ar;
	}
	
	
	function getInstanceGraph($graphname){
		if($this->graphbase) return $this->graphbase ."/". $graphname."_instance";
		return $graphname."_instance";
	}
	
	function getGraphSchemaGraph($graphname){
		if($this->graphbase) return $this->graphbase ."/". $graphname."_schema";
		return $graphname."_schema";
	}
	
	function getPendingUpdates($ent){
		$updates = $this->dbman->get_relevant_updates($ent, $this->entity_type);
		return $updates ? $updates : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	

	/*
	 * Output
	 */
	
	function sendRetrievedUpdate($ar, $format, $display, $options, $version){
		if($ar->is_error() or $ar->is_reject() or $ar->is_confirm() or $ar->is_pending()){
			$this->writeDecision($ar);
		}
		else {
			$this->sendUpdate($ar->result, $format, $display, $version);
		}
	}
	
	function sendRetrievedEntity($ar, $format, $display, $options, $version){
		//opr($ar);
		if($ar->is_error() or $ar->is_reject() or $ar->is_pending()){
			$this->writeDecision($ar);
		}
		else {
			if(!$this->sendEntity($ar->result, $format, $display, $version)){
				$ar = new AnalysisResults("export entity");
				$ar->failure($this->errcode, "Failed to export data to $format", $this->errmsg);
				$this->writeDecision($ar);
			}
		}
	}
	
	function sendUpdate($update, $format, $display, $version = false){
		$flags = $this->parseDisplayFlags($display);
		$ar = $update->showUpdateResult($format, $flags, $display, $this);
		return $this->write_json_result($ar, "update ".$update->id." dispatched");		
	}
	
	function isNativeFormat($format){
		return $format == "" or in_array($format, array("json", "html", "triples", "quads", "jsonld"));
	}
	
	function sendEntity($ent, $format, $display, $version){
		$vstr = "?version=".$version."&format=".$format."&display=".$display;
		$opts = $this->parseDisplayFlags($display);
		if($this->isNativeFormat($format)){
			if(in_array('ns', $opts)) {
				$ent->compressNS();
			}
			if($format == "html"){
				$ent->displayHTML($opts, $vstr, $this);
			}
			elseif($format == "triples"){
				$ent->displayTriples($opts, $vstr, $this);
			}
			elseif($format == "quads"){
				$ent->displayQuads($opts, $vstr, $this);				
			}
			else{
				$ent->displayJSON($opts, $vstr, $this, $format=="jsonld");
			}
		}
		else {
			$ent->displayExport($format, $opts, $vstr, $this);
		}
		return $this->write_json_result($ent->forAPI(), "Sent the candidate");
	}
	
	/*
	 * Methods for sending results to client
	 */
	function writeDecision($ar){
		if($ar->is_error()){
			http_response_code($ar->errcode);
			$this->logResult($ar->errcode, $ar->decision." : ".$ar->action);
		}
		elseif($ar->is_reject()){
			http_response_code(401);
			$this->logResult(401, $ar->decision." : ".$ar->action);
		}
		elseif($ar->is_confirm()){
			http_response_code(428);
			$this->logResult(428, $ar->decision." : ".$ar->action);
		}
		elseif($ar->is_pending()){
			http_response_code(202);
			$this->logResult(202, $ar->decision." : ".$ar->action);
		}
		else {
			$this->logResult(200, $ar->decision, $ar->action);
		}
		$json = json_encode($ar);
		if($json){
			echo $json;
			return true;
		}
		else {
			http_response_code(500);
			echo "JSON error: ".json_last_error() . " " . json_last_error_msg();
		}
	}
	
	/*
	 * Helper methods for dealing with display stuff
	 */
	function parseDisplayFlags($display){
		$opts = explode("_", $display);
		return $opts;
	}
	
	function demandIDValid($demand, $type){
		if(!$this->policy->demandIDAllowed("create", $type)){
			return $this->failure_result("Policy does not allow specification of candidate IDs", 400);
		}
		if(!(ctype_alnum($demand) && strlen($demand) > 1 && strlen($demand) <= 40 )){
			return $this->failure_result("Candidate IDs must be between 2 and 40 alphanumeric characters", 400);
		}
		if($this->dbman->hasEntity($demand, $type, $this->cid())){
			return $this->failure_result("Candidate ID $demand exists already in the dataset", 400);
		}
		elseif($this->dbman->errcode){
			return $this->failure_result("Failed to check for duplicate ID ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		return true;
	}
}