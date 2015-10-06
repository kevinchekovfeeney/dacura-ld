<?php
getRoute()->get('/', 'get_schema');
getRoute()->post('/import', 'import_ontology');
getRoute()->post('/(\w+)/dependencies', 'calculate_dependencies');
getRoute()->post('/ontology/(\w+)', 'update_ontology');
getRoute()->get('/ontology/(\w+)', 'get_ontology');
getRoute()->post('/ontology/(\w+)', 'update_ontology');
getRoute()->get('/validate/(\w+)', 'validate_ontology');
getRoute()->post('/validate_ontologies', 'validate_ontologies');
getRoute()->get('/(\w+)', 'get_graph');
getRoute()->post('/(\w+)', 'update_graph');

set_time_limit (0);

function import_ontology(){
	global $dacura_server;
	$dacura_server->init("import_ontology");
	if(!isset($_POST['format'])){
		$payload = file_get_contents('php://input');
		$format = "upload";
		$entid = (isset($_GET['id']) && $_GET['id']) ? $_GET['id']: "";
	}
	else {
		$payload = isset($_POST['payload']) ? $_POST['payload'] : "";
		$format = isset($_POST['format']) ? $_POST['format'] : "";
		$entid = isset($_POST['id']) ? $_POST['id'] : "";
	}
	$ar = $dacura_server->importOntology($format, $payload, $entid);
	if($ar){
		return $dacura_server->writeDecision($ar);
		//return $dacura_server->write_json_result($ont, "Imported Ontology $ont->id");
	}
	$dacura_server->write_http_error();
}

function calculate_dependencies($ontid){
	global $dacura_server;
	$ar = $dacura_server->calculateOntologyDependencies($ontid);
	if($ar){
		//return $dacura_server->writeDecision($ar);
		return $dacura_server->write_json_result($ar, "Calculated dependencies of $ontid");
	}
	$dacura_server->write_http_error();	
}

/*
 * post requests take input as a application/json
 */
function get_graph($id){
	global $dacura_server;
	$dacura_server->init("get_schema");
	$graph = $dacura_server->getGraph($id);
	if($graph){
		return $dacura_server->write_json_result($graph, "Retrieved Graph $id");
	}
	$dacura_server->write_http_error();
}

function validate_ontology($ontid){
	global $dacura_server;
	set_time_limit(0);
	
	$res = $dacura_server->validateOntology($ontid);
	if($res === true){
		return $dacura_server->write_json_result("Ontology validated OK", "Validated Ontology $ontid");
	}
	else if(is_array($res)){
		return $dacura_server->write_json_result($res, "Validated Ontology $ontid");
	}
	$dacura_server->write_http_error();
}

/*
 * ontology stuff talks to the ld editor
 */
function get_ontology($ontid){
	global $dacura_server;
	set_time_limit(0);
	
	$options = isset($_GET['options']) ? $_GET['options'] : false;
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get_ontology", $ontid);
	$ar = $dacura_server->getOntology($ontid, $version, $format, $display);
	return $dacura_server->send_retrieved_entity($ar, $format, $display, $options, $version);
}

function get_ontology_update($update_id){
	global $dacura_server;
	$options = isset($_GET['options']) ? $_GET['options'] : array();
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get_ontology_update", $update_id);
	$ar = $dacura_server->getUpdate($update_id, $version, $options);
	return $dacura_server->send_retrieved_update($ar, $format, $display, $options, $version);
}

function validate_ontologies(){
	global $dacura_server;
	set_time_limit(0);
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj or !is_array($obj)){
		$dacura_server->write_http_error(400, "Communication Error: Update Request lacks a json encoded body");
	}
	elseif(count($obj) == 0){
		$dacura_server->write_http_error(400, "No Input: No ontologies have been selected for validation.");
	}
	elseif(!isset($obj['ontologies']) or !isset($obj['tests'])){
		$dacura_server->write_http_error(400, "Input Error: both ontologies and tests must be specified.");		
	}
	else {
		$res = $dacura_server->validateOntologies($obj['ontologies'], $obj['tests']);
		if($res === true){
			return $dacura_server->write_json_result("Ontologies validated OK", "Validated Ontologies");
		}
		else if(is_array($res)){
			return $dacura_server->write_json_result($res, "Validated Ontologies");
		}
		else {
			$dacura_server->write_http_error();				
		}
	}
}

function update_ontology($target_id,  $fragment_id = false){
	global $dacura_server;
	$ar = new AnalysisResults("Update Ontology");
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "Update Request lacks a json encoded body");
	}
	elseif($fragment_id){
		//$fragment_id = "local:".$target_id."/".$fragment_id;
		$ar->failure(403, "Illegal Update", "Attempt to directly update fragment $fragment_id. Fragments must be updated in context.");
	}
	else {
		$options = (isset($obj['options'])) ? $obj['options'] : array();
		$test_flag = isset($obj['test']);
		unset($obj['test']);
		unset($obj['options']);
		$ar = $dacura_server->updateOntology($target_id, $obj, $fragment_id, $options, $test_flag);
	}
	return $dacura_server->write_decision($ar);
	
}


function update_schema(){
	global $dacura_server;
	$dacura_server->init("update_schema");
	$json = file_get_contents('php://input');
	$ar = new AnalysisResults("Update Update");
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "Schema update lacks a json encoded body");
	}
	else {
		$ar = $dacura_server->updateSchema($obj, isset($obj['test']));
	}
	return $dacura_server->write_decision($ar);
}


