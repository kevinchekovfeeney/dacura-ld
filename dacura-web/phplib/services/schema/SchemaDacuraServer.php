<?php
include_once("phplib/services/ld/LdService.php");
include_once("phplib/services/ld/LdDacuraServer.php");
include_once("OntologyCreateRequest.php");
include_once("OntologyUpdateRequest.php");
include_once("Ontology.php");
include_once("GraphUpdateRequest.php");
include_once("GraphCreateRequest.php");
include_once("Graph.php");


class SchemaDacuraServer extends LdDacuraServer {
	
	var $schemadir;
	var $schemaconfig = false;
	var $graphman;
	var $update_type = false;
	
	function __construct($s){
		parent::__construct($s);
		$this->schemadir = $this->settings['path_to_collections'].$this->cid();
		if($this->did() != "all") {
			$this->schemadir.= "/".$this->did();
		}
		$this->schemadir.= "/schema/";
		if($this->cid() != "all"){
			$this->graphbase = $this->ucontext->my_url();
			$this->schema = $this->loadSchemaFromContext();				
		}
		$this->graphman = new GraphManager($this->settings);
	}
	
	function createNewEntityObject($id, $type){
		$this->update_type = $type;
		if($type == "ontology"){
			$obj = new OntologyCreateRequest($id);
		}
		elseif($type == "graph"){
			$obj = new GraphCreateRequest($id);
		}
		else {
			return $this->failure_result("Dacura Linked Data API does not support creation of $type", 400);
		}
		$obj->setNamespaces($this->nsres);
		return $obj;
	}
	
	function createNewEntityUpdateObject($oent, $type){
		$this->update_type = $type;
		if($type == "ontology"){
			$obj = new OntologyUpdateRequest(false, $oent);
		}
		elseif($type == "graph"){
			$obj = new GraphUpdateRequest(false, $oent);
		}
		else {
			return $this->failure_result("Dacura API does not support creation of $type", 400);
		}
		return $obj;
	}

	/*
	 * Called when we want to add the entity "afresh" to the graph
	 * In the case of ontologies -> we test it with the schema testing service, but only if it has a set of dqs_tests and imports specified.
	 * In the case of graphs => we create the schema graph according to the rules in the dqs_tests... 
	 */
	function publishEntityToGraph($nent, $status, $is_test=false){
		if(!$nent->dqsSpecified()){
			$ar = new GraphAnalysisResults("Checking new $this->update_type with quality service");				
			return $ar->accept("Tests must be specified before ontology can be checked with quality service");
		}
		$tests = $nent->getDQSTests("schema");
		$imports = $nent->getImportedOntologies();
		if($this->update_type == "ontology"){
			$ar = new GraphAnalysisResults("Checking new ontology with quality service");				
			$test_result = $this->validateOntologies($ids, $tests, $nent);
			if($test_result === false){
				return $ar->failure(500, "System Error", "Error in generating data quality service connection");
			}
			else {
				$ar->addOneGraphTestResult($nent->id, array(), array(), $test_result);
			}
		}
		else if($this->update_type == "graph"){
			$ar = $this->publishFreshSchema($nent, $tests, $imports, $status, $is_test);				
		}
		else {
			return $ar->failure(500, "System Error", "$this->update_type is not a valid type for entity creation");
		}
		return $ar;
	}
	
	function publishFreshSchema($nent, $tests, $imports, $decision, $is_test){
		$gu = new GraphAnalysisResults("Checking new graph configuration with quality service");
		$aquads = $nent->getPropertyAsQuads($nent->id, $this->getGraphSchemaGraph($nent->id));
		$dont_publish = ($is_test || $decision != "accept");
		foreach($imports as $ontid){
			$ont = $this->loadEntity($ontid, "ontology", "all", "all");
			if($ont){
				$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($ontid));
				if($quads){
					$aquads = array_merge($aquads, $quads);
				}
			}
			else {
				return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
			}				
		}
		$errs = $this->graphman->updateSchema($aquads, array(), $this->getInstanceGraph($nent->id), $this->getGraphSchemaGraph($nent->id), $dont_publish, $tests);
		if($errs === false){
			return $gu->failure($this->graphman->errcode, "Quality Service Failure", "Failed to load schema with quality service. ".$this->graphman->errmsg);
		}
		else {
			$gu->addOneGraphTestResult($nent->id, $aquads, array(), $errs);
		}
		return $gu;
	}
	
	//should never be called for ontologies = they don't live in graphs
	//may be called for graphs -> when they change state we remove their ontologies from the schema...
	function deleteEntityFromGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Removing $this->update_type from Graph", $is_test);
		if($this->update_type == "ontology"){
			return $ar->failure(500, "Ontology deleted from graph", "$ent->id ontology was attempted to be deleted - this should not happen");
		}
		if(!$ent->dqsSpecified()){
			$ar = new GraphAnalysisResults("Checking new $this->update_type with quality service");
			return $ar->failure("Tests must be specified before graph can be unpublished");
		}
		$tests = $nent->getDQSTests("schema");
		$imports = $nent->getImportedOntologies();
		$aquads = $ent->getPropertyAsQuads($ent->id, $this->getGraphSchemaGraph($ent->id));
		foreach($imports as $ontid){
			$ont = $this->loadEntity($ontid, "ontology", "all", "all");
			if($ont){
				$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($ontid));
				if($quads){
					$aquads = array_merge($aquads, $quads);
				}
			}
			else {
				return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
			}
		}
		$errs = $this->graphman->updateSchema(array(), $aquads, $this->getInstanceGraph($nent->id), $this->getGraphSchemaGraph($nent->id), $is_test, $tests);
		if($errs === false){
			return $gu->failure($this->graphman->errcode, "Quality Service Failure", "Failed to load schema with quality service. ".$this->graphman->errmsg);
		}
		else {
			$gu->addOneGraphTestResult($nent->id, $aquads, array(), $errs);
		}
		return $gu;
	}
	
	function checkUpdate(&$ar, &$uent, $test_flag){
		if(!$uent->changed->dqsSpecified()){
			return $ar->accept("Tests must be specified before $this->update_type can be checked with quality service");
		}
		if($this->update_type == "ontology"){
			$gu = new GraphAnalysisResults("Updating ontology", $is_test);
			$tests = $uent->getDQSTests("schema");
			$ids = $uent->changed->getImportedOntologies();		
			$test_result = $this->validateOntologies($ids, $tests, $uent->changed);
			if($test_result === false){
				return $ar->failure(500, "System Error", "Error in generating data quality service connection");
			}
			else {
				$gu->addOneGraphTestResult($uent->targetid, array(), array(), $test_result);
			}
			$ar->setReportGraphResult($gu, true);//for now don't prevent updates even with errors from graph tests		
		}
		else {
			parent::checkUpdate($ar, $uent, $test_flag);
		}
	}
	
	function updateEntityInGraph($uent, $is_test){
		$gu = new GraphAnalysisResults("Publishing Update to Graph $uent->targetid Schema");
		$aquads = $uent->delta->getNamedGraphInsertQuads($uent->targetid, $this->getGraphSchemaGraph($uent->targetid));
		$dquads = $uent->delta->getNamedGraphDeleteQuads($uent->targetid, $this->getGraphSchemaGraph($uent->targetid));
		if($uent->importsChanged()){
			$adds = $uent->importsAdded();
			foreach($adds as $ontid){
				$ont = $this->loadEntity($ontid, "ontology", "all", "all");
				if($ont){
					$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($uent->targetid));
					if($quads){
						$aquads = array_merge($aquads, $quads);
					}
				}
				else {
					return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
				}
			}
			$dels = $uent->importsDeleted();
			foreach($dels as $ontid){
				$ont = $this->loadEntity($ontid, "ontology", "all", "all");
				if($ont){
					$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($uent->targetid));
					if($quads){
						$dquads = array_merge($dquads, $quads);
					}
				}
				else {
					return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
				}
			}
		}
		
		$tests = $uent->getDQSTests();
		$errs = $this->graphman->updateSchema($aquads, $dquads, $this->getInstanceGraph($uent->targetid), $this->getGraphSchemaGraph($uent->targetid), $is_test, $tests);
		if($errs === false){
			$gu->addOneGraphTestFail($uent->targetid, $aquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
		}
		else {
			$gu->addOneGraphTestResult($uent->targetid, $aquads, $dquads, $errs);
		}
		return $gu;
	}
	
	function undoEntityUpdate($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Undoing Graph Update in Graph");
		if($this->update_type == "ontology"){
			return $ar->failure(500, "System Error", "Ontology is not saved in graph - no need for undo update");
		}
		$aquads = $uent->delta->getNamedGraphDeleteQuads($uent->targetid, $this->getInstanceGraph($uent->targetid));
		$dquads = $uent->delta->getNamedGraphInsertQuads($uent->targetid, $this->getInstanceGraph($uent->targetid));
		if($uent->importsChanged()){
			$adds = $uent->importsDeleted();
			foreach($adds as $ontid){
				$ont = $this->loadEntity($ontid, "ontology", "all", "all");
				if($ont){
					$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($uent->targetid));
					if($quads){
						$aquads = array_merge($aquads, $quads);
					}
				}
				else {
					return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
				}
			}
			$dels = $uent->importsAdded();
			foreach($dels as $ontid){
				$ont = $this->loadEntity($ontid, "ontology", "all", "all");
				if($ont){
					$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($uent->targetid));
					if($quads){
						$dquads = array_merge($dquads, $quads);
					}
				}
				else {
					return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
				}
			}
		}
		$tests = $uent->original->getDQSTests("schema");
		$errs = $this->graphman->updateSchema($aquads, $dquads, $this->getInstanceGraph($uent->targetid), $this->getGraphSchemaGraph($uent->targetid), $is_test, $tests);
		if($errs === false){
			$gu->addOneGraphTestFail($uent->targetid, $aquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
		}
		else {
			$gu->addOneGraphTestResult($uent->targetid, $aquads, $dquads, $errs);
		}
		return $gu;
	}
	
	function updatePublishedUpdate($nupd, $oupd, $is_test = false){
		$ar = new GraphAnalysisResults("Updating published $update_type", $is_test);
		if($this->update_type == "ontology"){
			return $this->publishEntityToGraph($nupd->changed, "accept", $is_test);
		}
		$quads = $cand->deltaAsNGQuads($oupd, $this->getGraphSchemaGraph($nupd->targetid));
		//add and delete the imports...
		$nadds = $nupd->importsAdded();
		$ndels = $nupd->importsDeleted();
		$oadds = $oupd->importsAdded();
		$odels = $oupd->importsDeleted();
		$dels = array();
		$adds = array();
		foreach($nadds as $nadd){
			if(!in_array($nadd, $oadds)){
				$adds[] = $nadd;
			}
		}
		foreach($odels as $odel){
			if(!in_array($odel, $ndels)){
				$adds[] = $odel;
			}
		}
		foreach($adds as $ontid){
			$ont = $this->loadEntity($ontid, "ontology", "all", "all");
			if($ont){
				$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($nupd->targetid));
				if($quads){
					$aquads = array_merge($aquads, $quads);
				}
			}
			else {
				return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
			}
		}
		foreach($oadds as $oadd){
			if(!in_array($oadd, $nadds)){
				$dels[] = $oadd;
			}
		}
		foreach($ndels as $ndel){
			if(!in_array($ndel, $odels)){
				$dels[] = $ndel;
			}
		}
		foreach($dels as $ontid){
			$ont = $this->loadEntity($ontid, "ontology", "all", "all");
			if($ont){
				$quads = $ont->getPropertyAsQuads($ontid, $this->getGraphSchemaGraph($nupd->targetid));
				if($quads){
					$dquads = array_merge($dquads, $quads);
				}
			}
			else {
				return $gu->failure($this->errcode, "Failed to load ontonlogy $ontid", $this->errmsg);
			}
		}
		$tests = $nupd->changed->getDQSTests("schema");
		$errs = $this->graphman->updateSchema($aquads, $dquads, $this->getInstanceGraph($nupd->targetid), $this->getGraphSchemaGraph($nupd->targetid), $is_test, $tests);
		if($errs === false){
			$gu->addOneGraphTestFail($uent->targetid, $aquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
		}
		else {
			$gu->addOneGraphTestResult($uent->targetid, $aquads, $dquads, $errs);
		}
		return $gu;
	}
	
	function updatedUpdate($cur, $umode, $testflag = false){
		if($this->update_type == "ontology"){
			return $this->publishEntityToGraph($cur->changed, "accept", $testflag);
		}
		else {
			return parent::updatedUpdate($cur,$umode, $testflag);
		}
	}
	
	/*
	 * Now the schema specific calls
	 */
	function importOntology($format, $payload, $entid, $title = "", $url = "", $make_internal = false, $test_flag = false){
		//check to see if entid is taken... if it is return a failure...
		$ar = new UpdateAnalysisResults("importing ontology");
		if($entid && $this->dbman->hasEntity($entid, "ontology", $this->cid(), $this->did())){
			return $ar->failure(400, "Ontology Already Exists", "Dacura already has an ontology with id $entid");
		}
		else {
			$entid = $this->generateNewEntityID("ontology", $entid);
		}
		if($format == "url"){
			$ont = $this->downloadOntology($url, $entid);
		}
		else {
			$ont = $this->createOntologyFromString($payload, $entid);
		}
		//		else {
		//			$ont = $this->uploadOntology($payload, $entid);
		//		}
		if(!$ont){
			return $ar->failure($this->errcode, "Failed Import", $this->errmsg);
		}
		$ont->meta['title'] = $title;
		$ont->meta['url'] = $url;
		$create_obj = array("meta" => $ont->meta, "contents" => $ont->ldprops);
		$ar = $this->createEntity("ontology", $create_obj, $entid, array(), $test_flag);
		return $ar;
	}
	
	function downloadOntology($url, $entid){
		$ontology = new Ontology($entid, $this->ucontext->logger);
		$ontology->nsres = $this->nsres;
		if(!$ontology->import("url", $url)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function uploadOntology($payload, $entid){
		$fname = $this->schemadir.$entid.".ont";
		$this->ucontext->logger->timeEvent("Start Upload", "debug");
		$xx = json_encode($payload);
		if(!$xx){
			return $this->failure_result("JSON error: ".json_last_error() . " " . json_last_error_msg(), 400);
		}
		if(!file_put_contents($fname, $payload)){
			return $this->failure_result("Failed to save to $fname", 500);
		}
		$this->ucontext->logger->timeEvent("Upload", "debug");
	
		$ontology = new Ontology($entid, $this->ucontext->logger);
		$ontology->nsres = $this->nsres;
		if(!$ontology->import("file", $fname, $entid)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		$xx = json_encode($ontology);
		if(!$xx){
			return $this->failure_result("JSON error 2: ".json_last_error() . " " . json_last_error_msg(), 400);
		}
		return $ontology;
	}
	
	function createOntologyFromString($string, $entid){
		$string = utf8_encode($string);
		$xx = json_encode($string);
		if(!$xx){
			return $this->failure_result("JSON error: ".json_last_error() . " " . json_last_error_msg(), 400);
		}
		$ontology = new Ontology($entid, $this->ucontext->logger);
		$ontology->nsres = $this->nsres;
		if(!$ontology->import("text", $string, $entid)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function loadImportedOntologyList(){
		$filter = array("type" => "ontology");
		$onts = $this->getEntities($filter);
		return $onts;
	}
	
	function calculateOntologyDependencies($id){
		//opr($this->nsres);
		$ent = $this->loadEntity($id, "ontology", $this->cid(), $this->did());
		if(!$ent){
			return false;
		}
		$deps = $ent->generateDependencies($this->nsres);
		$incs = array($id, "fix");
		$deps['include_tree'] = array($id => $this->getOntologyIncludes($id, $incs));
		$deps['includes'] = $incs;
		return $deps;
	}
	
	function getOntologyIncludes($id, &$included){
		$tree = array();
		$ent = $this->loadEntity($id, "ontology", $this->cid(), $this->did());
		if(!$ent){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$incs = $ent->getIncludedOntologies($this->nsres);
		$onwards = array();
		foreach($incs as $inc){
			if(!in_array($inc, $included) && $inc != $id && !$this->nsres->isStructuralNamespace($inc)){
				$onwards[] = $inc;
				$included[] = $inc;
			}
		}
		foreach($onwards as $onw){
			$tree[$onw] = $this->getOntologyIncludes($onw, $included);
		}
		return $tree;
	}
	
	function getEntityClasses($graphid = false){
		$entclasses = array();
		if($graphid){
			$graph = $this->schema->getGraph($graphid);
			if($graph){
				$this->schema->loadOntologies($this, "accept", $graphid);
				$classes = $this->graphman->getGraphEntityClasses($this->getGraphSchemaGraph($graphid));
				if($classes !== false){
					$entclasses[$graphid] = $classes;						
				}
				else {
					return $this->failure_result("Graph ID $graphid ".$this->graphman->errmsg, $this->graphman->errcode);
				}
			}
		}
		else {
			$graphs = $this->schema->getGraphs("accept");
			$this->schema->loadOntologies($this, "accept");
			foreach($graphs as $id => $graph){
				$classes = $this->graphman->getGraphEntityClasses($this->getGraphSchemaGraph($id));				
				if($classes !== false){
					$entclasses[$id] = $classes;						
				}
				else {
					return $this->failure_result("Graph ID $id ".$this->graphman->errmsg, $this->graphman->errcode);
				}
			}			 		
		}
		$res = $this->schema->adornGraphClasses($entclasses);
		if(!$res){
			return $this->failure_result($this->schema->errmsg, $this->schema->errcode);
		}
		return $res;
		//return $entclasses;
	}
	
	//get class hierarchy...
	//go through entire graphs ontology and pull out all subclass of relationships
	
	function getClassTemplate($graphid, $classname){
		$graph = $this->schema->getGraph($graphid);
		if($graph){
			$this->schema->loadOntologies($this, "accept", $graphid);
			$ch = array();
			foreach($this->schema->ontologies as $oid => $ont){
				$ch[$oid] = $ont->getClassHierarchy();
			}
			return $ch;
		}
		return $this->failure_result("failed to load graph ".$this->schema->errmsg, $this->schema->errcode);
	}
	
	
	
	//extra ontology is for when we want to use a 'live' version of an ontology for checking rather than the stored one...
	function validateOntologies($ids, $tests, $extra_ontology = false){
		$temp_graph_id = genid("", false, false);
		$aquads = array();
		foreach($ids as $id){
			$ont = $this->loadEntity($id, "ontology", "all", "all");
			if($ont){
				$quads = $ont->getPropertyAsQuads($id, $temp_graph_id);
				if($quads){
					$aquads = array_merge($aquads, $quads);
				}
			}
			else {
				return false;
			}
		}
		if($extra_ontology){
			$quads = $extra_ontology->getPropertyAsQuads($extra_ontology->id, $temp_graph_id);
			if($quads){
				$aquads = array_merge($aquads, $quads);
			}				
		}
		$x = $this->graphman->validateSchema($temp_graph_id, $aquads, $tests);
		if($x === false){
			return $this->failure_result($this->graphman->errmsg, $this->graphman->errcode);
		}
		elseif(is_array($x) && count($x) == 0){
			return true;
		}
		return $x;
	}	
	
}