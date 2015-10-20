<?php
include_once("phplib/services/ld/LdDacuraServer.php");

class CandidateDacuraServer extends LdDacuraServer {
	
	var $schema;

	function __construct($service){
		parent::__construct($service);
		$this->cwurlbase = $service->my_url();
		$this->schema = $this->loadSchemaFromContext();
	}
	
	function getNGSkeleton(){
		return $this->schema->getNGSkeleton();
	}
	
	function loadSchemaFromContext(){
		$filter = array("type" => "graph", "collectionid" => $this->cid(), "datasetid" => $this->did());
		$ents = $this->getEntities($filter);
		$sc = new Schema($this->cid(), $this->did(), $this->settings['install_url']);
		$sc->load($ents);
		return $sc;
	}

	function createNewEntityObject($id, $type){
		$obj = new CandidateCreateRequest($id);
		$obj->cwurl = $this->schema->instance_prefix."/".$id;
		//$nsres = new NSResolver();
		//$obj->setNamespaces($nsres);
		$obj->type = $type;
		return $obj;
	}
	
	function loadEntity($entity_id, $fragment_id = false, $version = false, $options = array()){
		$ent = parent::loadEntity($entity_id, $fragment_id, $version, $options);
		if($ent){
			$ent->cwurl = $this->schema->instance_prefix."/".$ent->id;
		}
		return $ent;
	}
	/*
	 * Methods for interactions with the Quality Service / Graph Manager
	 */
	function publishUpdateToGraph($uent, $decision, $is_test){
		if($uent->bothPublished()){
			$gu = $this->updateEntityInGraph($uent, $is_test);
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
		return $gu;
	}
	
	function publishEntityToGraph($nent, $status, $is_test=false){
		$ar = new GraphAnalysisResults("Publishing to Graph");
		$dont_publish = ($is_test || $status != "accept");
		foreach($nent->ldprops as $k => $props){
			$quads = $nent->getPropertyAsQuads($k, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k));
			if($quads){
				$gobj = $this->loadEntity($k);
				$tests = $gobj->meta['instance_dqs'];
				$errs = $this->graphman->create($quads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $dont_publish, $tests);
			}
			if($errs === false){
				$ar->addOneGraphTestFail($k, $quads, array(), $this->graphman->errcode, $this->graphman->errmsg);
			}
			else {
				$ar->addOneGraphTestResult($k, $quads, array(), $errs);
			}
		}
		return $ar;
	}
	
	function updateEntityInGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($ent->changed->ldprops as $k => $props){
			$gobj = $this->loadEntity($k);
			$tests = $gobj->meta['instance_dqs'];
			$iquads = $ent->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
			$dquads = $ent->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $this->graphman->update($iquads, $dquads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test, $tests);
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
	
	
	/*
	 * There are five major interfaces to the world
	 * Create Candidate -> create a new entity of some class
	 * Update Candidate -> update the value of an entity of some class
	 * Update Update -> update an update to a candidate
	 * Get Candidate -> retrieve a candidate record
	 * Get Update -> retrieve an update record
	 */
	/*
	function createCandidate($obj, $demand_id, $options, $test_flag = false){
		$ar = $this->checkCreateRequest($obj, $demand_id);
		if($ar->is_reject()){
			return $ar;
		}
		$ccand = $ar->result;
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
		$ar = $this->checkUpdateRequest($target_id, $obj, $fragment_id);
		if($ar->is_reject()){
			return $ar;
		}
		$ucand = $ar->result;
		$ar->add($this->getPolicyDecision("update", "candidate", $ucand));
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
		//get stuff out of options...
		$format = isset($options['format']) ? $options['format'] : false;
		$flags = isset($options['display']) ? $this->parseDisplayFlags($options['display']) : array();
		$version = isset($options['version']) ? $options['version'] : false;
		$ar->set_result($ucand->showUpdateResult($format, $flags, $version, $this));						
		return $ar;
	}
	
	function updateUpdate($id, $obj, $meta, $options, $test_flag = false){
		$ar = new RequestAnalysisResults("Update Update $id");
		$orig_upd = $this->loadUpdateFromDB($id);
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
*/
	/*
	 * Methods for interactions with the Quality Service / Graph Manager
	 *
		
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
		foreach($cand->dacura_props as $k){
			if($k == "meta") continue;
			$quads = $cand->getPropertyAsQuads($k, $this->schema->getGraphname($k));
			if($quads){
				$errs = $this->graphman->create($quads, $this->schema->getGraphname($k), $this->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($this->schema->getGraphname($k), $quads, array(), $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($this->schema->getGraphname($k), $quads, array(), $errs);
				}
			}	
		}
		return $ar;
	}

	function deleteReport($cand, $is_test = false){
		$ar = new GraphAnalysisResults("Removing Report from Graph", $is_test);
		foreach($cand->dacura_props as $k){
			if($k == "meta") continue;
			$quads = $cand->getPropertyAsQuads($k, $this->schema->getGraphname($k));
			if($quads){
				$errs = $this->graphman->delete($quads, $this->schema->getGraphname($k), $this->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($this->schema->getGraphname($k), array(), $quads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($this->schema->getGraphname($k), array(), $quads, $errs);
				}
			}
		}
		return $ar;
	}

	function updatePublishedUpdate($cand, $ocand, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($cand->original->dacura_props as $k){
			if($k == "meta") continue;
			$x = $this->schema->getGraphname($k);
			$quads = $cand->deltaAsNGQuads($ocand, $x);
			if(count($quads['add']) > 0 or count($quads['del']) > 0){
				$errs = $this->graphman->update($quads['add'], $quads['del'], $this->schema->getGraphname($k), $this->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($this->schema->getGraphname($k), $quads['add'], $quads['del'], $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($this->schema->getGraphname($k), $quads['add'], $quads['del'], $errs);
				}
			}
		}
		return $ar;			
	}	
	
	function updateReport($cand, $is_test=false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($cand->original->dacura_props as $k){
			if($k == "meta") continue;
			$x = $this->schema->getGraphname($k);
			$iquads = $cand->delta->getNamedGraphInsertQuads($x);
			$dquads = $cand->delta->getNamedGraphDeleteQuads($x);
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $this->graphman->update($iquads, $dquads, $this->schema->getGraphname($k), $this->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($this->schema->getGraphname($k), $iquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($this->schema->getGraphname($k), $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;
	}
	
	function undoReportUpdate($cand, $is_test = false){
		$ar = new GraphAnalysisResults("Undoing Report Update in Graph");
		foreach($cand->original->dacura_props as $k){
			if($k == "meta") continue;
			$dquads = $cand->delta->getNamedGraphInsertQuads($cand->schema->getGraphname($k));
			$iquads = $cand->delta->getNamedGraphDeleteQuads($cand->schema->getGraphname($k));
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $this->graphman->update($iquads, $dquads, $cand->schema->getGraphname($k), $this->schema->getSchemaGraphname($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($this->schema->getGraphname($k), $iquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($this->schema->getGraphname($k), $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;		
	}

	function getCandidatePending($cand){
		$updates = $this->dbman->get_relevant_updates($cand);
		return $updates ? $updates : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}	
	
	/*
	 * Methods dealing with lists of candidates (very primitive)
	 *
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
	 *
	
	function generateNewEntityID($demand = false){
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
	
	function getCandidateHistory($ent, $version){
		$history = $this->dbman->get_candidate_update_history($ent, $version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if($version == 1 && count($history) > 0){
			//$initial_cand = $this->rollBackCandidate($cand, 1);
			$history[] = array(
					'from_version' => 0,
					"to_version" => 1,
					"modtime" => $ent->created,
					"createtime" => $ent->created,
					"schema_version" => $ent->type_version,
					"backward" => "{}",
					"forward" => "create"
			);
		}
	}*/
}

