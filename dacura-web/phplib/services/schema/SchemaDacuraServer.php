<?php
include_once("phplib/services/ld/LDDacuraServer.php");


class SchemaDacuraServer extends LDDacuraServer {
	
	var $schemadir;
	var $schemaconfig = false;
	var $graphman;
	
	function __construct($s){
		parent::__construct($s);
		$this->schemadir = $this->settings['path_to_collections'].$this->cid();
		if($this->did() != "all") $this->schemadir.= "/".$this->did();
		$this->schemadir.= "/schema/";
		$this->graphman = new GraphManager($this->settings);
	}
	
	function getSchema($version = false){
		if($this->cid() == "all"){
			return $this->loadImportedOntologyList();
		}
		elseif($version !== false){
			$schema = new Schema($this->cid(), $this->did(), $this->settings['install_url'], $this->schemadir);
			if($this->dbman->load_schema($schema, $version)){
				return $schema;
			}
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		else {
			return $this->schema;
		}
	}
	
	function importOntology($format, $payload, $entid, $make_internal = false, $test_flag = false){
		//check to see if entid is taken... if it is return a failure...
		if($entid && $this->dbman->hasEntity($entid)){
			return $this->failure_result("Dacura already has an entity with id $entid", 400);
		}
		else {
			$entid = $this->generateNewEntityID("ontology", $entid);
		}
		if($format == "url"){
			//some data validation here -> ensure its a real url, etc, 
			$ont = $this->downloadOntology($payload, $entid);
		}
		elseif($format == "text"){
			$ont = $this->createOntologyFromString($payload, $entid);
		}
		else {
			$ont = $this->uploadOntology($payload, $entid);
		}
		if(!$ont){
			return false;
		}
		$create_obj = array("meta" => $ont->meta, "contents" => $ont->ldprops);
		$ar = $this->createEntity("ontology", $create_obj, $entid, array(), $test_flag);
		return $ar;		
	}
	
	function downloadOntology($url, $entid){
		$ontology = new Ontology($entid);
		$ontology->nsres = $this->nsres;
		if(!$ontology->import("url", $url)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function uploadOntology($payload, $entid){
		$fname = $this->schemadir.$entid.".ont";
		if(!file_put_contents($fname, $payload)){
			return $this->failure_result("Failed to save to $fname", 500);
		}
		$ontology = new Ontology($entid);
		$ontology->nsres = $this->nsres;
		if(!$ontology->import("file", $fname, $entid)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function createOntologyFromString($string, $entid){
		$ontology = new Ontology($this->schema->cwurl."/ontology/".$entid);
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
		$ent = $this->loadEntity($id);
		if(!$ent->isOntology()){
			return $this->failure_result("$id is not an ontology - cant calculate dependencies", 400);
		}
		else {
			$deps = $ent->generateDependencies($this->nsres);
			$incs = array($id, "fix");
			$deps['include_tree'] = array($id => $this->getOntologyIncludes($id, $incs));
			$deps['includes'] = $incs;
			return $deps;
		}
	}
	
	function getOntologyIncludes($id, &$included){
		$tree = array();
		$ent = $this->loadEntity($id);
		if(!$ent){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if(!$ent->isOntology()){
			return $this->failure_result("$id is not an ontology - cant calculate dependencies", 400);
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
	
	function validateOntologies($ids, $tests){
		$temp_graph_id = genid("", false, false);
		$aquads = array();
		foreach($ids as $id){
			$ont = $this->loadEntity($id);
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
		$x = $this->graphman->validateSchema($temp_graph_id, $aquads, $tests);
		if($x === false){
			return $this->failure_result($this->graphman->errmsg, $this->graphman->errcode);
		}
		elseif(is_array($x) && count($x) == 0){
			return true;
		}
		return $x;
	}
	
	function createNewEntityObject($id, $type){
		if($type == "ontology"){
			$obj = new OntologyCreateRequest($id);
		}
		elseif($type == "graph"){
			$obj = new GraphCreateRequest($id);
		}
		else {
			return $this->failure_result("Dacura API does not support creation of schema", 400);
		}
		//$nsres = new NSResolver();
		$obj->setNamespaces($this->nsres);
		$obj->type = $type;
		return $obj;
	}
	
	
	
/*	var $schemadir;
	var $schemafile;
	var $schema = false;
	var $schemaconfig = false;
	var $graphman; 

	function __construct($s){
		parent::__construct($s);
		$this->schemadir = $this->settings['path_to_collections'].$this->cid();
		if($this->did() != "all") $this->schemadir.= "/".$this->did();
		$this->schemadir.= "/schema/";
		$this->schemafile = $this->schemadir . "state.json";
		$this->graphman = new GraphManager($this->settings);
		$this->loadSchema();		
	}	
	
	function importOntology($format, $payload, $dqs, $make_internal = false){
		if(!$this->loadSchema()){
			return false;
		}
		if($format == "url"){
			$ont = $this->downloadOntology($payload);
		}
		elseif($format == "text"){
			$ont = $this->createOntologyFromString($payload);
		}
		else {
			$ont = $this->uploadOntology($payload);
		}
		if(!$ont){
			return false;
		}
		if($this->schema->hasOntology($ont)){
			return $this->failure_result("Ontology ".$ont->getTitle() . " has already been imported into Dacura", 400);
		}
		else {
			$ont->extractDetails();
			$ont->status = "new";
			$this->schema->addOntology($ont, $make_internal);
			if($this->saveSchema()){
				return $this->schema;
			}
			return false;
		}
		if($dqs){
			$ar = $this->checkOntology($ont);
		}
		return $ar;
	}	
	
	function downloadOntology($url){
		$fid = "ONT". randid();
		$ontology = new Ontology($this->schema->cwurl."/ontology/".$fid);
		if(!$ontology->import("url", $url)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function uploadOntology($payload){
		$fid = "ONT". randid();
		$fname = $this->schemadir.$fid.".tmp";
		if(!file_put_contents($fname, $payload)){
			return $this->failure_result("Failed to save to $fname", 500);
		}
		$ontology = new Ontology($this->schema->cwurl."/ontology/".$fid);
		if(!$ontology->import("file", $fname, $this->schema->cwurl."/ontology/".$fid)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function createOntologyFromString($string){
		$fid = "ONT". randid();
		$ontology = new Ontology($this->schema->cwurl."/ontology/".$fid);
		if(!$ontology->import("text", $string, $this->schema->cwurl."/ontology/".$fid)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function isNativeFormat($format){
		return $format == "" or in_array($format, array("json", "html", "triples", "quads"));
	}
	
	function validateOntology($local_id){
		if(!$this->schema){
			$this->loadSchema();
		}
		$id = $this->schema->getOntologyFullID($local_id);
		$ont = $this->schema->loadOntology($id);
		if($ont){
			$quads = $ont->getPropertyAsQuads($id, $local_id);
			if($quads){
				$x = $this->graphman->validateSchema($local_id, $quads);
				if($x === false){
					return $this->failure_result($this->graphman->errmsg, $this->graphman->errcode);
				}
				elseif(is_array($x) && count($x) == 0){
					return true;
				}
				return $x;
			}				
		}
		return $this->failure_result($this->schema->errmsg, $this->schema->errcode);
	}
	
	function validateOntologies($ids, $tests){
		if(!$this->schema){
			$this->loadSchema();
		}
		$temp_graph_id = genid("", false, false);
		$aquads = array();
		foreach($ids as $id){
			$ont = $this->schema->loadOntology($id);
			if($ont){
				$quads = $ont->getPropertyAsQuads($id, $temp_graph_id);
				if($quads){
					$aquads = array_merge($aquads, $quads);
				}
			}
			else {
				return $this->failure_result($this->schema->errmsg, $this->schema->errcode);				
			}
		}
		$this->graphman->setTests($tests);
		$x = $this->graphman->validateSchema($temp_graph_id, $aquads);
		if($x === false){
			return $this->failure_result($this->graphman->errmsg, $this->graphman->errcode);
		}
		elseif(is_array($x) && count($x) == 0){
			return true;
		}
		return $x;				
	}
	
	function getOntology($local_id, $version, $format, $display){
		$ar = new RequestAnalysisResults("Fetching ontology $local_id");
		$id = $this->schema->getOntologyFullID($local_id);
		$ont = $this->schema->loadOntology($id, $version);
		if(!$ont){
			return $ar->failure($this->errcode, "Error loading ontology $id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view", "ontology", $ont));
		if($ar->is_accept()){
			$ar->set_result($ont);
		}
		return $ar;
	}
	
	function updateOntology($local_id, $obj, $fragment_id, $options, $test_flag){
		$this->entity_type = "ontology";
		$objmeta = isset($obj['details']) ? $obj['details'] : array();
		unset($obj['details']);
		$id = $this->schema->getOntologyFullID($local_id);
		$ar = $this->checkUpdateRequest($id, $obj, $fragment_id);
		if($ar->is_error()){
			return $ar;				
		}
		$ontup = $ar->result;
		$changed_meta = $ontup->makeMetaChanges($objmeta);
		if($ar->is_reject() && count($changed_meta) == 0){ //no changes
			return $ar;
		}
		elseif($ar->is_reject()){
			$ar = new RequestAnalysisResults("Updating Ontology");
		}
		if(!$test_flag && !$ontup->original->imported){
			$ontup->changed->imported = true;
		}
		$ar->add($this->getPolicyDecision("update", "ontology", $ontup));
		if($ar->is_reject()){
			return $ar;
		}
		if($test_flag){
			$gu = $this->testOntologyUpdate($ontup, $ar->decision);				
		}
		else {
			$gu = $this->saveOntologyUpdate($ontup, $ar->decision);				
		}		
		$ar->setReportGraphResult($gu);
		return $ar->set_result($ontup);
	}
	
	function testOntologyUpdate($ontup, $decision){
		$ar = new GraphAnalysisResults("Testing Ontology Updates");
		return $ar->success($decision);
	}
	
	function saveOntologyUpdate($ontup, $decision){
		//opr($this->schema);
		$ar = new GraphAnalysisResults("Saving Ontology Updates");
		$ontupld = $ontup->getLDForm();
		//opr($ontupld);
		$change_statement = array("ontologies" => array($ontup->targetid => $ontupld));
		$changed = clone $this->schema;
		//if($ontup->original->imported == false){
			$changed->cwurl = false;//ontology is by default an open world thing.
		//}
		//opr($changed);
		//opr($change_statement);
		$changed->update($change_statement, true, true);
		//opr($changed);
		$delta = $this->schema->compare($changed);
		if(!$delta){
			return $ar->failure($this->schema->errcode, "Failed in comparison with of ontology within schema context.", $this->schema->errmsg);
		}
		if(!$delta->containsChanges()){
			return $ar->reject("No Changes", "Weird thing happened where the change disappeared between ontology and schema updates!");
		}
		//opr($changed);
		if(!$this->dbman->updateSchema($changed, $delta, $decision)){
			return $ar->failure($this->dbman->errcode, "Failed to save ontology update.", $this->dbman->errmsg);
		}
		return $ar->success($decision);		
	}
	
	function validateSchema($sname){
		return $this->graphman->validateSchema($sname);		
	}
	
	function validateGraph($sname, $gname){
		
	}	
	
	function getGraph($id){
		if(!$this->schema){
			$this->loadSchema();
		}
		return $this->schema;
	}
	
	function getSchema($version = false){
		if($version !== false){
			$schema = new Schema($this->cid(), $this->did(), $this->settings['install_url'], $this->schemadir);
			if($this->dbman->load_schema($schema, $version)){
				return $schema;
			}
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		else {
			return $this->schema;
		}
	}
	
	function createSchema($obj, $options, $test_flag = false){
		$ar = new RequestAnalysisResults("Creating Schema");
		$schema = new Schema($this->cid(), $this->did(), $this->settings['install_url'], $this->schemadir);
		if($this->dbman->has_schema($this->cid(), $this->did())){
			return $ar->failure(401, "Not permitted", "The dataset ".$this->cid().", ".$this->did()." already has a schema defined");				
		}
		elseif($this->dbman->errcode){
			return $ar->failure($this->dbman->errcode, "Failed check for existing schema", $this->dbman->errmsg);
		}
		if(!$schema->loadFromAPI($obj)){
			return $ar->failure($schema->errcode, "Protocol Error", "New candidate object sent to API had formatting errors. ".$schema->errmsg);
		}
		if(!$schema->validate()){
			return $ar->failure($schema->errcode, "Invalid Create Schema Request", "The create schema request contained errors: ".$schema->errmsg);				
		}
		if(!$schema->expand($this->policy->demandIDAllowed("create schema", $schema))){
			return $ar->failure($schema->errcode, "Invalid Create Request", $schema->errmsg);
		}
		$schema->expandNS();//use fully expanded urls internally - support prefixes in input
		$gu = $this->createSchemaGraphs($schema, $test_flag);
		if($gu->is_accept()){
			if(!$this->dbman->insert_schema($schema)){
				return $this->failure_result("Failed to save schema DB records. ".$this->dbman->errmsg, $this->dbman->errcode);
			}
			$this->schema = $schema;
			$ar->set_result($this->schema->getDisplayFormat());
		}
		return $ar;	
	}
	
	function createSchemaGraphs(){
		$gu = new GraphAnalysisResults("create schema graphs");
		return $gu->success("accept");
		//have to ask gavin about this....
	}
	
	function createBasics(){
		/*if(!file_exists($this->schemadir)){
			if(mkdir($this->schemadir)){
				return $this->failure_result("Failed to create schema directory $this->schemadir", 500);
			}
		}
		if(!file_exists($this->schemafile)){
			$this->schemaconfig = new SchemaConfig($this->cid(), $this->did(), $this->settings['install_url']);
			if(!$this->saveSchemaConfig()){
				return $this->failure_result("Failed to create new schema templates.", 500);
			}
		}
		if(!$this->dbman->has_schema($this->cid(), $this->did())){
			$schema = new Schema($this->cid(), $this->did(), $this->settings['install_url'], $this->schemadir);
			$schema->loadDefaults();
			$schema->expand(true);
			$schema->expandNS();
			if(!$this->dbman->insert_schema($schema)){
				return $this->failure_result("Failed to save schema DB records. ".$this->dbman->errmsg, $this->dbman->errcode);
			}
			$this->schema = $schema;
		}
		return true;	
	}
	
	function loadSchema($auto_make = true){
		$auto_make && $this->createBasics();	
		$this->schema = new Schema($this->cid(), $this->did(), $this->settings['install_url'], $this->schemadir);
		if($this->dbman->load_schema($this->schema)){
			return true;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function updateSchema($input){
		$ar = new RequestAnalysisResults("Update Schema for ".$this->cid()."/".$this->did());
		if(!$this->loadSchema()){
			return $ar->failure($this->errcode, "Failed to load schema.", $this->errmsg);
		}
		$changed = clone $this->schema;
		$changed->update($input, false, true);
		$delta = $this->schema->compare($changed);
		if(!$delta){
			return $ar->failure($this->schema->errcode, "Failed to compare schema update.", $this->schema->errmsg);
		}
		if(!$delta->containsChanges()){
			return $ar->reject("No Changes", "The submitted version is identical to the current version.");				
		}
		//opr($delta);
		if(!$this->dbman->updateSchema($changed, $delta)){
			return $ar->failure($this->dbman->errcode, "Failed to save schema update.", $this->dbman->errmsg);
		}
		$ar->set_result($changed->ldprops);
		return $ar;
	}
	
	function saveSchema(){
		$oschema = new Schema($this->cid(), $this->did(), $this->settings['install_url'], $this->schemadir);
		if(!$this->dbman->load_schema($oschema)){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$delta = $oschema->compare($this->schema);
		if(!$delta){
			return $this->failure_result("Failed to compare schema for save.". $oschema->errmsg, $oschema->errcode);
		}
		if(!$delta->containsChanges()){
			return $this->failure_result("The submitted version is identical to the current version.", 400);
		}
		if(!$this->dbman->updateSchema($this->schema, $delta, "accept")){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return true;		
	}
	
	function saveSchemaConfig(){
		$sfile = $this->schemadir . "state.json";
		$xf = json_encode($this->schemaconfig);
		return file_put_contents($sfile, $xf);
	}
	
	function testRDF(){
		$ld = new LDDocument("x");
		if($ld->import("file", "C:\\Temp\\dacura\\collections\\all\\schema\\oa.ttl", $this->settings['install_url'])){
			echo "<H2>Import Success</H2>";
		}
		else {
			echo "<H2>Import Failure</H2>";
		}
		//opr($ld);
		$ex = $ld->export("turtle");
		if(!$ex){
			return $this->failure_result($ld->errmsg, $ld->errcode);
		}
		opr($ex);		
		$quads = $ld->getPropertyAsQuads($ld->id, "hello world");
		opr($quads);
		return true;
	}
	
	
	
	
	function checkOntology($ont){
		return $ont;
	}*/
}