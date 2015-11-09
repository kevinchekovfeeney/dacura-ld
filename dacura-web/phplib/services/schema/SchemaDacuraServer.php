<?php
include_once("phplib/services/ld/LdDacuraServer.php");


class SchemaDacuraServer extends LdDacuraServer {
	
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
	
	function importOntology($format, $payload, $entid, $title = "", $url = "", $make_internal = false, $test_flag = false){
		//check to see if entid is taken... if it is return a failure...
		if($entid && $this->dbman->hasEntity($entid, "ontology", $this->cid(), $this->did())){
			return $this->failure_result("Dacura already has an ontology with id $entid", 400);
		}
		else {
			$entid = $this->generateNewEntityID("ontology", $entid);
		}
		if($format == "url"){
			//some data validation here -> ensure its a real url, etc, 
			$ont = $this->downloadOntology($url, $entid);
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
		$ont->meta['title'] = $title;
		$ont->meta['url'] = $url;
		$create_obj = array("meta" => $ont->meta, "contents" => $ont->ldprops);
		//opr($create_obj);
		$ar = $this->createEntity("ontology", $create_obj, $entid, array(), $test_flag);
		//opr($ar);
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
		
		if(!file_put_contents($fname, $payload)){
			return $this->failure_result("Failed to save to $fname", 500);
		}
		$this->ucontext->logger->timeEvent("Upload", "debug");
		
		$ontology = new Ontology($entid, $this->ucontext->logger);
		$ontology->nsres = $this->nsres;
		if(!$ontology->import("file", $fname, $entid)){
			return $this->failure_result($ontology->errmsg, $ontology->errcode);
		}
		return $ontology;
	}
	
	function createOntologyFromString($string, $entid){
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
		$ent = $this->loadEntity($id, "ontology", $this->cid(), $this->did());
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
			$ont = $this->loadEntity($id, "ontology", $this->cid(), $this->did());
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
	
}