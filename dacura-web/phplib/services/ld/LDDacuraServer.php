<?php
include_once("phplib/db/LDDBManager.php");
include_once("phplib/LD/EntityCreateRequest.php");
include_once("phplib/LD/EntityUpdate.php");
include_once("phplib/LD/OntologyCreateRequest.php");
include_once("phplib/LD/OntologyUpdateRequest.php");
include_once("phplib/LD/GraphUpdateRequest.php");
include_once("phplib/LD/GraphCreateRequest.php");
include_once("phplib/LD/Graph.php");
include_once("phplib/LD/Candidate.php");
include_once("phplib/LD/CandidateCreateRequest.php");
include_once("phplib/LD/CandidateUpdateRequest.php");
include_once("phplib/LD/GraphManager.php");
require_once("phplib/LD/AnalysisResults.php");
require_once("phplib/LD/NSResolver.php");
include_once("phplib/PolicyEngine.php");


/*
 * There are three types of Linked Data Object Managed by the system
 * 1. Candidates 
 * 2. Ontologies
 * 3. Schemata
 * Each of them supports a common set of state management functionality 
 * This file contains those methods that they share
 */

class LDDacuraServer extends DacuraServer {

	var $dbclass = "LDDBManager";
	var $policy; //policy engine to decide what to do with incoming requests
	var $graphman; //graph manager object
	var $nsres; //the namespace resolving service - system wide
	var $cwurlbase = false;

	function __construct($service){
		parent::__construct($service);
		$this->policy = new PolicyEngine();
		$this->graphman = new GraphManager($this->settings);
		$this->loadNamespaces();
	}
	
	function loadNamespaces(){
		$onts = $this->getEntities(array("type" => "ontology", "status" => "accept"));
		$this->nsres = new NSResolver();
		foreach($onts as $i => $ont){
			if(isset($ont['id']) && $ont['id'] && isset($ont['meta']['url']) && $ont['meta']['url']){
				$this->nsres->prefixes[$ont['id']] = $ont['meta']['url'];
			}
		}
	}
	
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
	 */
	function getEntity($entity_id, $fragment_id = false, $version = false, $options = array()){
		$action = "Fetching " . ($fragment_id ? "fragment $fragment_id from " : "");
		$action .= "entity $entity_id". ($version ? " version $version" : "");
		$ar = new RequestAnalysisResults($action);
		$ent = $this->loadEntity($entity_id, $fragment_id, $version, $options);
		if(!$ent){
			return $ar->failure($this->errcode, "Error loading entity $entity_id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view", get_class($ent), $ent));
		if($ar->is_accept()){
			$ar->set_result($ent);
		}
		return $ar;
	}
	
	/*
	 * the load Entity version returns the normal dacura error codes...
	 */
	function loadEntity($entity_id, $fragment_id = false, $version = false, $options = array()){
		$ent = $this->dbman->loadEntity($entity_id, $options);
		if(!$ent){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
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
				$fid = $this->cwurlbase.$entity_id."/".$fragment_id;
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
			$ent->version = $old['from_version'];
			$ent->version_created = $old['modtime'];
			if($i == 0){
				$ent->version_replaced = 0;
			}
			else {
				$ent->version_replaced = $history[$i-1]['modtime'];
			}
		}
		return $ent;
	}
	
	function getEntityHistory($ent, $version){
		$history = $this->dbman->loadEntityUpdateHistory($ent, $version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
/*		if($version == 1 && count($history) > 0){
			//$initial_cand = $this->rollBackCandidate($cand, 1);
			$history[] = array(
					'from_version' => 0,
					"to_version" => 1,
					"modtime" => $ent->created,
					"createtime" => $ent->created,
					"backward" => "{}",
					"forward" => "create"
			);
		}*/
		return $history;
	}
	
	
	/*
	 * Same pattern applies for retreiving updates
	 */
	
	function getUpdate($id, $options = array()){
		$ar = new RequestAnalysisResults("Loading Update Request $id from DB", false);
		$ur = $this->loadUpdate($id, $options);
		if(!$ur){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view", get_class($ur), $ur));
		if($ar->is_accept()){
			$ar->set_result($ur);
		}
		return $ar;
	}	
	
	function loadUpdate($id, $options = array(), $vfrom = false, $vto = false){
		$eur = $this->dbman->loadEntityUpdateRequest($id, $options);
		$vto = $vto ? $vto : $eur->to_version();
		$vfrom = $vfrom ? $vfrom : $eur->from_version();
		$orig = $this->loadEntity($eur->targetid, false, $vfrom, $options);
		if(!$orig){
			return $this->failure_result("Failed to load Update $id - could not load original " .$this->errmsg, $this->errcode);
		}
		$eur->setOriginal($orig);
		$changed = false;
		if($vto > 0){
			$changed = $this->loadEntity($eur->targetid, false, $vto, $options);
			if(!$changed){
				return $this->failure_result("Loading of $this->entity_type update $id failed - could not load changed ".$this->errmsg, $this->errcode);
			}
		}
		if(!$eur->calculate($changed)){
			return $this->failure_result($cur->errmsg, $eur->errcode);
		}
		return $eur;
	}
	
	function createEntity($type, $create_obj, $demand_id, $options, $test_flag = false){
		$ar = new RequestAnalysisResults("Creating $type");
		if($demand_id){
			if($this->demandIDValid($demand_id, $type)){
				$id = $this->generateNewEntityID($type, $demand_id);
			}
			else {
				$id = $this->generateNewEntityID($type);
			}
			if($id != $demand_id){
				$txt = "Requested ID $demand_id could not be granted (".$this->errmsg.").";
				$extra = "";
				if($test_flag){
					$txt = "An ID will be randomly generated when the $type is created.";
					$extra = "$id is an example of a randomly generated ID, it will be replaced by another if the $type is created";
				}
				else {
					$txt = "The $this->entity_type was allocated a randomly generated ID: $id";
				}
				$ar->addWarning("Generating id", $txt, $extra);
			}
		}
		else {
			$id = $this->generateNewEntityID($type);
		}
		$nent = $this->createNewEntityObject($id, $type);
		if(!$nent){
			return $ar->failure($this->errcode, "Request Create Error", "New $type object sent to API had formatting errors. ".$this->errmsg);				
		}
		$nent->setContext($this->cid(), $this->did());
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
		$ar->add($this->getPolicyDecision("create", $type, $nent));
		if(!$ar->is_reject()){
			$gu = $this->publishEntityToGraph($nent, $ar->decision, $test_flag);
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
			//opr($nent);
			$ar->setCandidateGraphResult($nent->internalTriples());
		}
		else {
			$nent->set_status($ar->decision);
		}
		if($test_flag || $ar->is_confirm() || ($ar->is_reject() && !$this->policy->storeRejected($type, $nent))){
			return $ar;
		}
		if(!$this->dbman->createEntity($nent, $type)){
			$disaster = new AnalysisResults("Database Synchronisation");
			$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to create database candidate record ". $this->dbman->errmsg);
			$ar->add($disaster);
			if($ar->includesGraphChanges()){
				$recovery = $this->deleteEntityFromGraph($nent);
				$ar->undoReportGraphResult($recovery);
			}
		}
		if(!$ar->is_reject()){
			$ar->set_result($nent->getDisplayFormat());
		}
		return $ar;
	}

	/*
	 * Methods dealing with Candidate ID generation
	 */	
	function generateNewEntityID($type, $demand = false){
		if($demand){
			return $demand;
		}
		return uniqid_base36(true);
	}
	
/*	function createNewEntityObject($id, $type){
		if($type == "schema"){
			return $this->failure_result("Dacura API does not support creation of schema", 400);
		}
		elseif($type == "ontology"){
			$obj = new OntologyCreateRequest($id);		
		}
		elseif($type == "graph"){
			$obj = new GraphCreateRequest($id);		
		}
		else {
			$obj = new CandidateCreateRequest($id);
		}
		$nsres = new NSResolver();
		$obj->setNamespaces($nsres);
		$obj->type = $type;
		return $obj;
	}*/
	
	function updateEntity($target_id, $obj, $fragment_id, $options = array(), $test_flag = false){
		$ar = new RequestAnalysisResults("Update $target_id");
		$oent = $this->loadEntity($target_id, $fragment_id);
		if(!$oent){
			if($this->errcode){
				return $ar->failure($this->errcode, "Failed to load $this->entity_type $target_id", $this->errmsg);
			}
			else {
				return $ar->failure(404, "No such $this->entity_type", "$target_id does not exist.");
			}
		}
		$uclass = get_class($oent)."UpdateRequest";
		$uent = new $uclass(false, $oent);
		$nsres = new NSResolver();
		$uent->setNamespaces($nsres);
		
		//is this entity being accessed through a legal collection / dataset context?
		if(!$uent->isLegalContext($this->cid(), $this->did())){
			return $ar->failure(403, "Access Denied", "Cannot update $oent->id through context ".$this->cid()."/".$this->did());
		}
		elseif(!$uent->loadFromAPI($obj)){
			return $ar->failure($uent->errcode, "Protocol Error", "Failed to load the update candidate from the API. ".$uent->errmsg);
		}
		else {
			if($uent->isOntology()){
				$opts = array("force_inserts" => true, "demand_id_allowed" => $this->policy->demandIDAllowed("update", get_class($uent), $uent));
			}
			else {
				$opts = array("demand_id_allowed" => $this->policy->demandIDAllowed("update", get_class($uent), $uent));
			}
			if(!$uent->calculateChanged($opts)){
				return $ar->failure($uent->errcode, "Update Error", "Failed to apply updates ".$uent->errmsg);
			}
		}
		if($uent->calculateDelta()){
			if($uent->nodelta()){
				return $ar->reject("No Changes", "The submitted version is identical to the current version.");
			}
		}
		else {
			return $ar->reject($uent->errcode, "Error in calculating change", $uent->errmsg);
		}
		$ar->add($this->getPolicyDecision("update", get_class($uent), $uent));
		if($ar->is_accept() or $ar->is_confirm()){
			//unless the status of the candidate is accept, the change to the report graph is hypothetical
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
		if($ar->is_reject()){
			if($this->policy->storeRejected("update", $uent) && !$test_flag){
				if(!$this->dbman->updateEntity($uent, $ar->decision)){
					$ar->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected update.", $this->dbman->errmsg);
				}
			}
			return $ar;
		}
		$ar->setUpdateGraphResult($uent->compare());
		$meta_delta = $uent->getMetaUpdates();
		$ar->setCandidateGraphResult($uent->addedCandidateTriples(), $uent->deletedCandidateTriples(), !($ar->is_accept() || $ar->is_confirm()), $meta_delta);
		if(($ar->is_accept() or $ar->is_pending()) && !$test_flag){
			if(!$this->dbman->updateEntity($uent, $ar->decision)){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to update database candidate record ". $this->dbman->errmsg);
				$ar->add($disaster);
				if($ar->includesGraphChanges()){
					$recovery = $this->undoUpdatesToGraph($uent);
					$ar->undoReportGraphResult($recovery);
				}
			}
		}
		//get stuff out of options...
		$format = isset($options['format']) ? $options['format'] : false;
		$flags = isset($options['display']) ? $this->parseDisplayFlags($options['display']) : array();
		$version = isset($options['version']) ? $options['version'] : false;
		$ar->set_result($uent->showUpdateResult($format, $flags, $version, $this));
		return $ar;
	}
	
	function updateUpdate($id, $obj, $meta, $options, $test_flag = false){
		$ar = new RequestAnalysisResults("Update Update $id");
		$orig_upd = $this->loadUpdate($id);
		if(!$orig_upd){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$new_upd = $this->loadUpdatedUpdate($orig_upd, $obj, $meta);
		if(!$new_upd){
			return $ar->failure($this->errcode, "Failed to load updated version of $id", $this->errmsg);
		}
		if($new_upd->sameAs($orig_upd)){
			return $ar->reject("No Changes", "The new update is identical to the existing update - it will be ignored.");
		}
		if(!$new_upd->isLegalContext($this->cid(), $this->did())){
			return $ar->failure(403, "Access Denied", "Cannot update candidate $new_upd->targetid through context ".$this->cid()."/".$this->did());
		}
		if($new_upd->nodelta()){
			return $ar->reject("No changes", "The submitted version removes all changes from the update - it has no effect.");
		}
		$ar->add($this->getPolicyDecision("update update", "candidate", array($orig_upd, $new_upd)));
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
			if($this->dbman->pendingUpdatesExist($orig_upd->targetid, $orig_upd->to_version()) || $this->dbman->errcode){
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
	
	function loadUpdatedUpdate($orig_upd, $obj, $meta){
		if(isset($meta['from_version']) && $meta['from_version'] && $meta['from_version'] != $orig_upd->original->get_version()){
			$norig = $this->loadCandidateFromDB($orig_upd->targetid, false, $meta['version']);
			if(!$norig)	return false;
		}
		else {
			$norig = clone $orig_upd->original;
		}
		$ncur = new CandidateUpdateRequest($orig_upd->id, $norig);
		$ncur->to_version = $orig_upd->to_version;
		if(isset($meta['status'])){
			$ncur->set_status($meta['status']);
		}
		else {
			$ncur->set_status($orig_upd->get_status());
		}
		if(!$ncur->loadFromAPI($obj)){
			return $this->failure_result($ncur->errmsg, $ncur->errcode);
		}
		$opts = array(
				"demand_id_allowed" => $this->policy->demandIDAllowed("update", $ncur),
				"force_inserts" => true,
				"calculate_delta" => true,
				"validate_delta" => true
		);
		if(!$ncur->calculateChanged($opts)){
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
				return $this->undoReportUpdate($cur, $testflag);
			}
			else {
				return $this->updateReport($cur, $testflag);
			}
		}
		elseif($cur->originalPublished()){
			if($umode == "rollback"){
				return $this->deleteReport($cur->original, $testflag);
			}
			else {
				return $this->deleteReport($cur->changed, $testflag);
			}
		}
		elseif($cur->changedPublished() or $test_flag) {
			if($umode == "rollback"){
				return $this->writeReport($cur->original, $testflag);
			}
			else {
				return $this->writeReport($cur->changed, $testflag);
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
	function publishUpdateToGraph($uent, $decision, $is_test){
		if($uent->isCandidate()){
			if($uent->bothPublished()){
				$gu = $this->updateEntityInGraph($uent, $decision, $is_test);
			}
			elseif($uent->originalPublished()){
				$gu = $this->deleteEntityFromGraph($uent->original, $is_test);
			}
			else {
				if($is_test || $uent->changedPublished()){
					$gu = $this->publishEntityToGraph($uent->changed, $decision, $is_test);				
				}
				else {
					$gu = new GraphAnalysisResults("Nothing to save to graph");
				}
			}
		}
		elseif($uent->isOntology()){
			$gu = new GraphAnalysisResults("Ontology updates are not tested against graph...");				
		}
		elseif($uent->isGraph()){
			$gu = $this->publishSchemaUpdate($uent, $decision, $is_test);
		}
		return $gu;
	}
	
	

	function undoUpdatesToGraph($uent){
		if($uent->bothPublished()){
			return $this->undoEntityUpdate($uent, false);
		}
		elseif($uent->originalPublished()){
			return $this->publishEntityToGraph($uent->original, $uent->original->status, false);//dr
		}
		elseif($uent->changedPublished()){
			return $this->deleteEntityFromGraph($uent->changed, false);//wr
		}
		$ar = new GraphAnalysisResults("Nothing to undo in report graph");
		return $ar;
	}
	
	function publishEntityToGraph($nent, $status, $is_test=false){
		$ar = new GraphAnalysisResults("Publishing to Graph");
		$dont_publish = ($is_test || $status != "accept");
		foreach($nent->ldprops as $k => $props){
			$quads = $nent->getPropertyAsQuads($k, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k));
			if($quads){
				if($nent->isCandidate()){
					$gobj = $this->loadEntity($k);
					$tests = $gobj->meta['instance_dqs'];
					$errs = $this->graphman->create($quads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $dont_publish, $tests);
				}
				else {
					$errs = array();
				}
				if($errs === false){
					$ar->addOneGraphTestFail($k, $quads, array(), $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $quads, array(), $errs);
				}
			}
		}
		return $ar;
	}
	
	function updateEntityInGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($ent->original->ldprops as $k => $props){
			$iquads = $ent->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
			$dquads = $ent->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $this->graphman->update($iquads, $dquads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, $iquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;
	}	
	
	function deleteEntityFromGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Removing Entity from Graph", $is_test);
		foreach($ent->ldprops as $k => $props){
			$quads = $ent->getPropertyAsQuads($k, $this->getInstanceGraph($k));
			if($quads){
				$errs = $this->graphman->delete($quads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, array(), $quads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, array(), $quads, $errs);
				}
			}
		}
		return $ar;
	}

	function undoEntityUpdate($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Undoing Report Update in Graph");
		foreach($ent->original->ldprops as $k => $props){
			$dquads = $ent->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
			$iquads = $ent->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $this->graphman->update($iquads, $dquads, $this->getInstanceGraph($k),$this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, $iquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;
	}	
	
	function updatePublishedUpdate($cand, $ocand, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($cand->original->dacura_props as $k){
			$quads = $cand->deltaAsNGQuads($ocand, $this->getInstanceGraph($k));
			if(count($quads['add']) > 0 or count($quads['del']) > 0){
				$errs = $this->graphman->update($quads['add'], $quads['del'], $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, $quads['add'], $quads['del'], $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $quads['add'], $quads['del'], $errs);
				}
			}
		}
		return $ar;
	}
	
	function publishSchemaUpdate($uent, $decision, $is_test){
		$gu = new GraphAnalysisResults("Publishing Update to Graph $uent->targetid Schema");
		$sgname = $uent->targetid."_schema";
		$igname = $uent->targetid."_instance";
		$aquads = $uent->delta->getNamedGraphInsertQuads($uent->targetid, $igname);
		$dquads = $uent->delta->getNamedGraphDeleteQuads($uent->targetid, $igname);
		if($uent->importsChanged()){
			$adds = $uent->importsAdded();
			foreach($adds as $ontid){
				$ont = $this->loadEntity($ontid);
				if($ont){
					$quads = $ont->getPropertyAsQuads($ontid, $uent->targetid."_schema");
					if($quads){
						$aquads = array_merge($aquads, $quads);
					}
				}
				else {
					return false;
				}
			}
			$dels = $uent->importsDeleted();
			foreach($dels as $ontid){
				$ont = $this->loadEntity($ontid);
				if($ont){
					$quads = $ont->getPropertyAsQuads($ontid, $uent->targetid."_schema");
					if($quads){
						$dquads = array_merge($dquads, $quads);
					}
				}
				else {
					return false;
				}
			}
		}
		$tests = $uent->getDQSTests();
	
		$errs = $this->graphman->updateSchema($aquads, $dquads, $igname, $sgname, $is_test, $tests);
		if($errs === false){
			$gu->addOneGraphTestFail($uent->targetid, $aquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
		}
		else {
			$gu->addOneGraphTestResult($uent->targetid, $aquads, $dquads, $errs);
		}
		return $gu;
	}
	
	function getInstanceGraph($graphname){
		return $graphname."_instance";
	}
	
	function getGraphSchemaGraph($graphname){
		return $graphname."_schema";
	}
	
	function getPendingUpdates($ent){
		$updates = $this->dbman->get_relevant_updates($ent, $this->entity_type);
		return $updates ? $updates : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/*
	 * Helper for policy object
	 */
	function getPolicyDecision($action, $ent_type, $args){
		return $this->policy->getPolicyDecision($action, $ent_type, $args);
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
		return $this->write_json_result($update->showUpdateResult($format, $flags, $display, $this), "update ".$update->id." dispatched");		
	}
	
	function isNativeFormat($format){
		return $format == "" or in_array($format, array("json", "html", "triples", "quads"));
	}
	
	function sendEntity($ent, $format, $display, $version){
		$vstr = "?version=".$version."&format=".$format."&display=".$display;
		$opts = $this->parseDisplayFlags($display);
		if($this->isNativeFormat($format)){
			if(in_array('ns', $opts)) {
				//$ent->compressNS();
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
				$ent->displayJSON($opts, $vstr, $this);
			}
		}
		else {
			$ent->displayExport($format, $opts, $vstr, $this);
		}
		return $this->write_json_result($ent, "Sent the candidate");
	}
	
	/*
	 * Methods for sending results to client
	 */
	function writeDecision($ar){
		if($ar->is_error()){
			http_response_code($ar->errcode);
			$this->ucontext->logger->setResult($ar->errcode, $ar->decision." : ".$ar->action);
		}
		elseif($ar->is_reject()){
			http_response_code(401);
			$this->ucontext->logger->setResult(401, $ar->decision." : ".$ar->action);
		}
		elseif($ar->is_confirm()){
			http_response_code(428);
			$this->ucontext->logger->setResult(428, $ar->decision." : ".$ar->action);
		}
		elseif($ar->is_pending()){
			http_response_code(202);
			$this->ucontext->logger->setResult(202, $ar->decision." : ".$ar->action);
		}
		else {
			$this->ucontext->logger->setResult(200, $ar->decision, $ar->action);
		}
		echo json_encode($ar);
		return true;
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
		if($this->dbman->hasEntity($demand)){
			return $this->failure_result("Candidate ID $demand exists already in the dataset", 400);
		}
		elseif($this->dbman->errcode){
			return $this->failure_result("Failed to check for duplicate ID ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		return true;
	}
}