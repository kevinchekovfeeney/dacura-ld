<?php
include_once("phplib/DacuraServer.php");
include_once("phplib/db/CandidateDBManager.php");
include_once("phplib/LD/Schema.php");
include_once("phplib/LD/Candidate.php");
include_once("phplib/LD/CandidateCreateRequest.php");
include_once("phplib/LD/CandidateUpdateRequest.php");
include_once("GraphManager.php");
require_once("AnalysisResults.php");
include_once("PolicyEngine.php");


class CandidateDacuraServer extends DacuraServer {

	var $dbclass = "CandidateDBManager";
	var $schema; //the schema in use is defined by the context.
	var $policy; //policy engine to decide what to do with incoming requests
	
	function __construct($service){
		parent::__construct($service);
		$this->policy = new PolicyEngine();
	}
	
	/*
	 * There are five major interfaces to the world
	 * Create Candidate -> create a new entity of some class
	 * Update Candidate -> update the value of an entity of some class
	 * Update Update -> update an update to a candidate
	 * Get Candidate -> retrieve a candidate record
	 * Get Update -> retrieve an update record
	 */
	
	function createCandidate($obj, $demand_id, $options, $test_flag = false){
		$ar = new RequestAnalysisResults("Creating Candidate");
		if($demand_id){
			if($this->demandIDValid($demand_id)){
				$id = $this->generateNewCandidateID($demand_id);				
			}
			else {
				$id = $this->generateNewCandidateID();
				$txt = "Requested ID $demand_id could not be granted (".$this->errmsg.").";
				$extra = "";
				if($test_flag){
					$txt = "An ID will be randomly generated when the candidate is created.";
					$extra = "$id is an example of a randomly generated ID, it will be replaced by another if the candidate is created";
				}
				else {
					$txt = "The candidate was allocated a randomly generated ID: $id";						
				}
				$ar->addWarning("Generating Candidate ID", $txt, $extra);
			}
		}
		else {
			$id = $this->generateNewCandidateID();				
		}
		$this->schema = new Schema($this->cid(), $this->did(), $this->settings['install_url']);
		$ccand = new CandidateCreateRequest($id, $this->schema);
		$ccand->setContext($this->cid(), $this->did());
		if(!$ccand->loadFromAPI($obj)){
			return $ar->failure($ccand->errcode, "Protocol Error", "New candidate object sent to API had formatting errors. ".$ccand->errmsg);
		}
		elseif(!$ccand->validate()){
			return $ar->failure($ccand->errcode, "Invalid Create Request", "The create request contained errors: ".$ccand->errmsg);
		}
		elseif(!$ccand->expand($this->policy->demandIDAllowed("create", $ccand))){
			return $ar->failure($ccand->errcode, "Invalid Create Request", $ccand->errmsg);
		}
		$ccand->expandNS();//use fully expanded urls internally - support prefixes in input
		$ar->add($this->getPolicyDecision("create candidate", $ccand));
		if($ar->is_accept() or $ar->is_confirm()) {
			$gu = $this->writeReport($ccand, $test_flag || $ar->is_confirm());
			if($gu->is_reject() && $ar->is_accept() && $this->policy->rollbackToPending("create", $ccand)){
				$ar->addWarning("Report Publication", "Rejected by Quality Service", "State changed from accept to pending");
				$ccand->set_status("pending");
				$gu = $this->writeReport($ccand, true);
				$ar->setReportGraphResult($gu, true);
				$ar->decision = "pending";
			}
			else {
				$ar->setReportGraphResult($gu);
			}
		}
		elseif($ar->is_pending()) {
			$ar->setReportGraphResult($this->writeReport($ccand, true), true);
		}
		$ccand->set_status($ar->decision);
		if(!$ar->is_reject()){
			$ar->setCandidateGraphResult($ccand->internalTriples());
		}
		if(!$test_flag && ($ar->is_accept() or $ar->is_pending() or ($ar->is_reject() && $this->policy->storeRejected("candidate", $ccand)))){
			if(!$this->dbman->createCandidate($ccand)){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to create database candidate record ". $this->dbman->errmsg);
				$ar->add($disaster);
				if($ar->includesGraphChanges()){
					$recovery = $this->deleteReport($ccand);
					$ar->undoReportGraphResult($recovery);
				}
			}
		}
		if(!$ar->is_reject()){
			$ar->set_result($ccand->getDisplayFormat());
		}
		return $ar;
	}
	
	function updateCandidate($target_id, $obj, $fragment_id, $options = array(), $test_flag = false){
		$ar = new RequestAnalysisResults("Update Candidate");
		$ocand = $this->loadCandidateFromDB($target_id, $fragment_id);
		if(!$ocand){
			if($this->errcode){
				return $ar->failure($this->errcode, "Failed to load $target_id", $this->errmsg);				
			}
			else {
				return $ar->failure(404, "Candidate $target_id does not exist.");
			}			
		}
		$ucand = new CandidateUpdateRequest(false, $ocand);
		//is this candidate being accessed through a legal collection / dataset context?
		if(!$ucand->isLegalContext($this->cid(), $this->did())){
			return $ar->failure(403, "Access Denied", "Cannot update candidate $ocand->id through context ".$this->cid()."/".$this->did());
		}
		elseif(!$ucand->loadFromAPI($obj)){
			return $ar->failure($ucand->errcode, "Protocol Error", "Failed to load the update candidate from the API. ".$ucand->errmsg);
		}
		elseif(isset($options['restore'])){
			if(!$ucand->prepareRestore($this, $options['restore'])){
				return $ar->failure($ucand->errcode, "Data Error", "Failed to create candidate delta for restore. ".$ucand->errmsg);
			}
			if(!$ucand->nodelta()){
				return $ar->reject("No Changes", "The candidate has not been updated as the version submitted is identical to the current version.");
			}
		}
		else {
			if(!$ucand->calculateChanged(array("demand_id_allowed" => $this->policy->demandIDAllowed("update", $ucand)))){
				return $ar->failure($ucand->errcode, "Update Error", "Failed to apply updates ".$ucand->errmsg);
			}
		}
		if($ucand->calculateDelta() && $ucand->nodelta()){
			return $ar->reject("No Changes", "The submitted version is identical to the current version.");
		}
		$ar->add($this->getPolicyDecision("update candidate", $ucand));
		if($ar->is_accept() or $ar->is_confirm()){
			//unless the status of the candidate is accept, the change to the report graph is hypothetical
			$hypo = !($ucand->changedPublished() || $ucand->originalPublished());
			if($hypo or $test_flag or $ar->is_confirm()){
				$gu = $this->testUpdates($ucand);				
			}
			else {
				$gu = $this->saveUpdates($ucand);
			}
			if($ar->is_accept() && $ucand->changedPublished() && $gu->is_reject() 
					&& $this->policy->rollbackToPending("update", $ucand)){
				$ar->addWarning("Update Publication", "Rejected by Quality Service", "Update state changed from accept to pending");
				$ucand->set_status("pending");
				$gu = $this->testUpdates($ucand);
				$hypo = true;
				$ar->decision = "pending";					
			}		
			$ar->setReportGraphResult($gu, $hypo);
		}
		elseif($ar->is_pending()){
			$gu = $this->testUpdates($ucand);	
			$ar->setReportGraphResult($gu, true);
		}
		$ucand->set_status($ar->decision);
		if($ar->is_reject()){
			if($this->policy->storeRejected("update", $ucand) && !$test_flag){
				if(!$this->dbman->updateCandidate($ucand, $ar->decision)){
					$ar->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected update.", $this->dbman->errmsg);
				}
			}
			return $ar;
		}
		$ar->setUpdateGraphResult($ucand->compare());
		$meta_delta = $ucand->getMetaUpdates();
		$ar->setCandidateGraphResult($ucand->addedCandidateTriples(), $ucand->deletedCandidateTriples(), !($ar->is_accept() || $ar->is_confirm()), $meta_delta);
		if(($ar->is_accept() or $ar->is_pending()) && !$test_flag){
			if(!$this->dbman->updateCandidate($ucand, $ar->decision)){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to update database candidate record ". $this->dbman->errmsg);
				$ar->add($disaster);
				if($ar->includesGraphChanges()){
					$recovery = $this->undoUpdates($ucand);
					$ar->undoReportGraphResult($recovery);
				}
			}
		}
		$ar->set_result($ucand->showUpdateResult($options, $this));						
		return $ar;
	}
	
	function updateUpdate($id, $obj, $meta, $options, $test_flag = false){
		$ar = new RequestAnalysisResults("Update Update $id");
		$orig_upd = $this->loadCURFromDB($id);
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
			return $ar->failure(403, "Access Denied", "Cannot update candidate $new_upd->candid through context ".$this->cid()."/".$this->did());
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
			if($this->dbman->pendingUpdatesExist($orig_upd->candid, $orig_upd->to_version()) || $this->dbman->errcode){
				if($this->dbman->errcode){
					return $ar->failure($this->dbman->errcode, "Unpublishing of update $orig_upd->id failed", "Failed to check for pending updates to current version of candidate");						
				}
				return $ar->failure(400, "Unpublishing of update $orig_upd->id not allowed", "There are pending updates on version ".$orig_upd->to_version()." of candidate $orig_upd->candid");
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
		$ar->set_result($new_upd->showUpdateResult($options, $this));
		return $ar;
	}
	
	function getCandidate($candidate_id, $fragment_id = false, $version = false, $options = array()){
		$ar = new RequestAnalysisResults("Loading $candidate_id $fragment_id");
		$cand = $this->loadCandidateFromDB($candidate_id, $fragment_id, $version, !isset($options['no_context']));
		if(!$cand){
			return $ar->failure($this->errcode, "Error loading candidate", $this->errmsg);
		}
		if(isset($options['history']) && !$fragment_id){
			$cand->history = $dacura_server->getCandidateHistory($cand);
		}
		if(isset($options['pending']) && !$fragment_id){
			$cand->pending = $dacura_server->getCandidatePending($cand);
		}
		$ar->add($this->getPolicyDecision("view candidate", $cand));
		if($ar->is_accept()){
			$ar->set_result($cand);
		}
		return $ar;
	}
	
	function getUpdate($id, $options = array()){
		$ar = new RequestAnalysisResults("Loading Update Request $id from DB", false);
		$cur = $this->loadCURFromDB($id);
		if(!$cur){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);			
		}
		$ar->add($this->getPolicyDecision("view update", $cur));
		if($ar->is_accept()){
			$ar->set_result($cur);
		}
		return $ar;
	}
	
	/*
	 * Methods for loading candidates and candidate update requests from db
	 * allow overriding of version from and to fields to allow updates to be applied to different versions than they were made with
	 */
	function loadCURFromDB($id, $vfrom = false, $vto = false){ 
		$cur = new CandidateUpdateRequest($id);
		if(!$this->dbman->loadCandidateUpdateRequest($cur)){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$vto = $vto ? $vto : $cur->to_version();
		$vfrom = $vfrom ? $vfrom : $cur->from_version();
		$orig = $this->loadCandidateFromDB($cur->candid, false, $vfrom);
		if(!$orig){
			return $this->failure_result("Failed to load Update $id - could not load original " .$this->errmsg, $this->errcode);
		}
		$cur->setOriginal($orig);
		$changed = false;
		if($vto > 0){
			$changed = $this->loadCandidateFromDB($cur->candid, false, $vto);			
			if(!$changed){
				return $this->failure_result("Loading of Update $id failed - could not load changed ".$this->errmsg, $this->errcode);
			}
		}
		if(!$cur->calculate($changed)){
			return $this->failure_result($cur->errmsg, $cur->errcode);
		}
		return $cur;
	}
		
	function loadCandidateFromDB($candidate_id, $fragment_id = false, $version = false, $show_context = true){
		$cand = new Candidate($candidate_id);
		if(!$this->dbman->load_candidate($cand)){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$cand->loadSchema($this->settings['install_url']);
		$cand->expandNS();
		if($version && $cand->version() > $version){
			if(!$this->rollBackCandidate($cand, $version)){
				return false;
			}
			$cand->expandNS();
		}
		$cand->readStateFromMeta();
		if($fragment_id){
			$cand->buildIndex();
			$fid = $cand->schema->instance_prefix.$candidate_id."/".$fragment_id;
			$frag = $cand->getFragment($fid);
			$cand->fragment_id = $fid;
			if($frag && $show_context){
				$cand->setContentsToFragment($fid);
				$types = array();
				foreach($frag as $fobj){
					if(isset($fobj['rdf:type'])){
						$types[] = $fobj['rdf:type'];
					}
				}
				$cand->fragment_paths = $cand->getFragmentPaths($fid);
				$cand->fragment_details = count($types) == 0 ? "Undefined Type" : "Types: ".implode(", ", $types);
			}
			else {
				if($frag){
					$cand->ldprops = $frag;
				}
				else {
					return $this->failure_result("Failed to load fragment $fid", 404);
				}
			}
		}
		return $cand;		
	}
		
	function loadUpdatedUpdate($orig_upd, $obj, $meta){
		//if(isset($obj['from_version'])) && $obj['from_version']
		if(isset($meta['from_version']) && $meta['from_version'] && $meta['from_version'] != $orig_upd->original->get_version()){
			$norig = $this->loadCandidateFromDB($orig_upd->candid, false, $meta['version']);
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

	/*
	 * Methods for interactions with the Quality Service / Graph Manager
	 */
		
	function testUpdates($ucand){
		if($ucand->bothPublished()){
			$gu = $this->updateReport($ucand, true);
		}
		elseif($ucand->originalPublished()){
			$gu = $this->deleteReport($ucand->original, true);
		}
		else { 
			$gu = $this->writeReport($ucand->changed, true);
		}
		return $gu;		
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
	
	function saveUpdates($ucand){
		if($ucand->bothPublished()){
			return $this->updateReport($ucand);
		}
		elseif($ucand->originalPublished()){
			return $this->deleteReport($ucand->original);//dr
		}
		elseif($ucand->changedPublished()){
			return $this->writeReport($ucand->changed);//wr
		}
		$ar = new GraphAnalysisResults("Nothing to save to report graph");
		return $ar;
	}
	
	
	function undoUpdates($ucand){
		if($ucand->bothPublished()){
			return $this->undoReportUpdate($ucand);
		}
		elseif($ucand->originalPublished()){
			return $this->writeReport($ucand->original);//dr
		}
		elseif($ucand->changedPublished()){
			return $this->deleteReport($ucand->changed);//wr
		}
		$ar = new GraphAnalysisResults("Nothing to undo in report graph");
		return $ar;
	}
	
	function undoUpdatedUpdate($ucand, $ocand, $mode){
		if($ucand->bothPublished()){
			return $this->undoReportUpdate($ucand);
		}
		elseif($ucand->originalPublished()){
			return $this->writeReport($ucand->original);//dr
		}
		elseif($ucand->changedPublished()){
			return $this->deleteReport($ucand->changed);//wr
		}
		$ar = new GraphAnalysisResults("Nothing to undo in report graph");
		return $ar;		
	}
	
	
	function writeReport($cand, $is_test=false){
		$ar = new GraphAnalysisResults("Writing Report to Graph", $is_test);
		$sa = new GraphManager($this->getSemanticAnalysisConfig());
		foreach($cand->dacura_props as $k){
			if($k == "meta") continue;
			$quads = $cand->getPropertyAsQuads($k, $cand->schema->getGraphname($k));
			if($quads){
				$errs = $sa->create($quads, $cand->schema->getGraphname($k), $cand->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($cand->schema->getGraphname($k), $quads, array(), $sa->errcode, $sa->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($cand->schema->getGraphname($k), $quads, array(), $errs);
				}
			}	
		}
		return $ar;
	}

	function deleteReport($cand, $is_test = false){
		$ar = new GraphAnalysisResults("Removing Report from Graph", $is_test);
		$sa = new GraphManager($this->getSemanticAnalysisConfig());
		foreach($cand->dacura_props as $k){
			if($k == "meta") continue;
			$quads = $cand->getPropertyAsQuads($k, $cand->schema->getGraphname($k));
			if($quads){
				$errs = $sa->delete($quads, $cand->schema->getGraphname($k), $cand->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($cand->schema->getGraphname($k), array(), $quads, $sa->errcode, $sa->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($cand->schema->getGraphname($k), array(), $quads, $errs);
				}
			}
		}
		return $ar;
	}

	function updatePublishedUpdate($cand, $ocand, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		$sa = new GraphManager($this->getSemanticAnalysisConfig());
		foreach($cand->original->dacura_props as $k){
			if($k == "meta") continue;
			$x = $cand->schema->getGraphname($k);
			$quads = $cand->deltaAsNGQuads($ocand, $x);
			if(count($quads['add']) > 0 or count($quads['del']) > 0){
				$errs = $sa->update($quads['add'], $quads['del'], $cand->schema->getGraphname($k), $cand->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($cand->schema->getGraphname($k), $quads['add'], $quads['del'], $sa->errcode, $sa->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($cand->schema->getGraphname($k), $quads['add'], $quads['del'], $errs);
				}
			}
		}
		return $ar;			
	}	
	
	function updateReport($cand, $is_test=false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		$sa = new GraphManager($this->getSemanticAnalysisConfig());
		foreach($cand->original->dacura_props as $k){
			if($k == "meta") continue;
			$x = $cand->schema->getGraphname($k);
			$iquads = $cand->delta->getNamedGraphInsertQuads($x);
			$dquads = $cand->delta->getNamedGraphDeleteQuads($x);
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $sa->update($iquads, $dquads, $cand->schema->getGraphname($k), $cand->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($cand->schema->getGraphname($k), $iquads, $dquads, $sa->errcode, $sa->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($cand->schema->getGraphname($k), $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;
	}
	
	function undoReportUpdate($cand, $is_test = false){
		$ar = new GraphAnalysisResults("Undoing Report Update in Graph");
		$sa = new GraphManager($this->getSemanticAnalysisConfig());
		foreach($cand->original->dacura_props as $k){
			if($k == "meta") continue;
			$dquads = $cand->delta->getNamedGraphInsertQuads($cand->schema->getGraphname($k));
			$iquads = $cand->delta->getNamedGraphDeleteQuads($cand->schema->getGraphname($k));
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $sa->update($iquads, $dquads, $cand->schema->getGraphname($k), $cand->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($cand->schema->getGraphname($k), $iquads, $dquads, $sa->errcode, $sa->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($cand->schema->getGraphname($k), $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;		
	}

	/*
	 * Methods dealing with candidate history
	 */
	
	function rollBackCandidate(&$cand, $version){
		$history = $this->getCandidateHistory($cand, $version);
		foreach($history as $i => $old){
			if($old['from_version'] < $version){
				continue;
			}
			$back_command = json_decode($old['backward'], true);
			if(!$cand->update($back_command, true)){
				return $this->failure_result($cand->errmsg, $cand->errcode);
			}
			$cand->version = $old['from_version'];
			$cand->type_version = $old['schema_version'];
			$cand->modified = $old['modtime'];
			if($i == 0){
				$cand->replaced = 0;
			}
			else {
				$cand->replaced = $history[$i-1]['modtime'];				
			}
		}
		return $cand;
	}
	
	
	function getCandidateHistory($cand, $to_version = 1){
		$history = $this->dbman->get_candidate_update_history($cand, $to_version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if($to_version == 1 && count($history) > 0){
			//$initial_cand = $this->rollBackCandidate($cand, 1);
			$history[] = array(
					'from_version' => 0,
					"to_version" => 1,
					"modtime" => $cand->created,
					"createtime" => $cand->created,
					"schema_version" => $cand->type_version,
					"backward" => "{}",
					"forward" => "create"
			);
		}
		return $history;
	}
	
	function getCandidatePending($cand){
		$updates = $this->dbman->get_relevant_updates($cand);
		return $updates ? $updates : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}	
	
	/*
	 * Methods dealing with lists of candidates (very primitive)
	 */
	function getCandidates(){
		return ($data = $this->dbman->get_candidate_list()) ? $data : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getCandidateUpdates(){
		$data = $this->dbman->get_candidate_updates_list();
		if($data){
			return $data;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}

	/*
	 * Methods dealing with Candidate ID generation
	 */
	
	function generateNewCandidateID($demand = false){
		if($demand && ctype_alnum($demand) && strlen($demand) > 1 && strlen($demand) < 40 && $this->policy->demandIDAllowed("create")){
			if(!$this->dbman->has_candidate($demand) && !$this->dbman->errcode){
				return $demand;
			}
		}
		return uniqid_base36(true);
	}
	
	function demandIDValid($demand){
		if(!$this->policy->demandIDAllowed("create")){
			return $this->failure_result("Policy does not allow specification of candidate IDs", 400);
		}
		if(!(ctype_alnum($demand) && strlen($demand) > 1 && strlen($demand) <= 40 )){
			return $this->failure_result("Candidate IDs must be between 2 and 40 alphanumeric characters", 400);
		}
		if($this->dbman->has_candidate($demand)){
			return $this->failure_result("Candidate ID $demand exists already in the dataset", 400);
		}
		elseif($this->dbman->errcode){
			return $this->failure_result("Failed to check for duplicate ID ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		return true;
	}
	
	/*
	 * Helper for policy object
	 */
	function getPolicyDecision($action, $args){
		return $this->policy->getPolicyDecision($action, $args);
	}
	
	
	/* 
	 * Random method that needs to be incorporated into settings
	 */
	function getSemanticAnalysisConfig(){
		$x = array(
			"service_url" => "http://192.168.2.104:3020/dacura/instance",
			//"service_url" => "http://dacura.scss.tcd.ie/dqs/dacura/instance",
			"tests" => "all"
		);
		return $x;
	}
	
	/*
	 * Methods for sending results to client
	 */
	
	function write_decision($ar){
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
	
	function send_retrieved_update($ar, $format, $display, $options, $version){
		if($ar->is_error() or $ar->is_reject() or $ar->is_confirm() or $ar->is_pending()){
			$this->write_decision($ar);
		}
		else {
			$this->send_update($ar->result, $format, $display, $version);
		}
	}
	
	function send_retrieved_candidate($ar, $format, $display, $options, $version){
		//opr($ar);
		if($ar->is_error() or $ar->is_reject() or $ar->is_pending()){
			$this->write_decision($ar);
		}
		else {
			$this->send_candidate($ar->result, $format, $display, $version);
		}
	}
	
	function send_update($update, $format, $display, $version){
		$vstr = "?version=".$version."&format=".$format."&display=".$display;
		$opts = $this->parseDisplayOptions($display);
		if(in_array("ns", $opts)){
			$update->compressNS(true);
		}
		if($format == "triples"){
			$update->displayTriples($display);
		}
		elseif($format == "typed_triples") {				
			$update->displayTypedTriples($display);
		}
		elseif($format == "quads"){
			if(in_array(opts, 'datatypes')) {
				$update->displayTypedQuads($display);
			}
			else {
				$update->displayQuads($display);				
			}
		}
		elseif($format == "turtle"){
			$update->displayTurtle($display);				
		}
		elseif($format == "html"){
			$update->displayHTML($display);				
		}
		else {
			$update->displayJSON($display);
		}
		return $this->write_json_result($update->getDisplayFormat(), "Candidate Update Retrieved ".$update->id);
	}
		
	function send_candidate($cand, $format, $display, $version){
		$vstr = "?version=".$version."&format=".$format."&display=".$display;
		if(!$this->should_display("ns", $format, $display)){
			$cand->expandNS();				
		}
		else {
			$cand->compressNS();
		}
		$ns = $this->should_display("ns", $format, $display);
		if($format == "triples"){
			$ttl = $cand->triples($ns);
			$cand->linkifyTriples($ttl, $this->should_display("links", $format, $display), $vstr);
			$cand->display = $ttl;
		}
		elseif($format == "typed_triples"){
			$ttl = $cand->typedTriples($ns);
			$cand->linkifyTriples($ttl, $this->should_display("links", $format, $display), $vstr);
			$cand->display = $ttl;
		}
		elseif($format == "turtle"){
			$ttl = $cand->turtle($ns);
			$cand->linkifyTurtle($ttl, $this->should_display("links", $format, $display), $vstr);
			$cand->display = $ttl;
		}
		elseif($format == "html"){
			$cand->display = $cand->html($this->ucontext, $vstr);				
		}
		else {
			if($this->should_display("links", $format, $display)){
				$cand->display = $cand->linkify($vstr);
			}
			else {
				$cand->display = $cand->ldprops;			
			}
		}
		$rcand = $cand->getDisplayFormat();
		return $this->write_json_result($rcand, "Sent the candidate");
	}

	/*
	 * Helper methods for dealing with display stuff
	 */
	function parseDisplayOptions($display){
		$opts = explode("_", $display);
		return $opts;
	}
	
	function should_display($option, $format, $display){
		if($option == 'links' && $format == "html") return false;
		$display_options = explode("_", $display);
		return in_array($option, $display_options);
	}
}

