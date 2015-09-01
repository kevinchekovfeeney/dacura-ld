<?php
//getRoute()->get('/datatable', 'datatable');
//getRoute()->get('/datatable/(\w+)', 'datatable_record');
//getRoute()->get('/', 'get_candidates');
//getRoute()->post('/', 'update_candidates');
/*
 * These API calls have candidate ids as targets
 * this is just an api - it has no associated pages. 
 */
getRoute()->get('/', 'list_candidates');//show usage information for root get access
getRoute()->post('/', 'create_candidate');//create a new candidate (type etc, are in payload)
getRoute()->get('/type/(\w+)', 'get_candidate_schema');
getRoute()->get('/update/(\w+)', 'get_update');
getRoute()->get('/(\w+)/state', 'get_candidate_state');
getRoute()->get('/(\w+)/(\w+)', 'get_candidate');//with fragment id
getRoute()->get('/(\w+)', 'get_candidate');
getRoute()->post('/(\w+)', 'update_candidate');
getRoute()->post('/update/(\w+)', 'update_update');
getRoute()->post('/(\w+)/(\w+)', 'update_candidate');//with fragment id
getRoute()->delete('/(\w+)', 'delete_candidate');
getRoute()->delete('/(\w+)/(\w+)', 'delete_candidate');//with fragment id

/*
 * post requests take input as a application/json
 */
function create_candidate(){
	global $dacura_server;
	$dacura_server->init("create_candidate");
	$ar = new AnalysisResults("Create Candidate");
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "Request lacks a json encoded body");
	}
	elseif($dacura_server->cid() == "all"){
		$ar->failure(401, "API Access Error", "Candidates cannot be submitted through the root context");		
	}
	else {
		if(!isset($obj['candidate'])){
			$ar->failure(400, "Syntax Error", "Request lacks the required candidate field");
		}
		else {
			$options = (isset($obj['options'])) ? $obj['options'] : array();
			$demand_id = isset($obj['@id']) ? $obj['@id'] : false;
			$create_obj = array();
			if(isset($obj['provenance'])) $create_obj['provenance'] = $obj['provenance'];
			if(isset($obj['candidate'])) $create_obj['candidate'] = $obj['candidate'];
			if(isset($obj['annotation'])) $create_obj['annotation'] = $obj['annotation'];
			if(isset($obj['meta'])) $create_obj['meta'] = $obj['meta'];
			$ar = $dacura_server->createCandidate($create_obj, $demand_id, $options, isset($obj['test']));
		}
	}
	return $dacura_server->write_decision($ar);
}

/**
 *
 * @param string $target_id the id of the candidate that is being updated
 * Takes a application/x-www-form-urlencoded post with the following parameters:
 * provenance => PROVjson object describing the provenance of the candidate update request
 * annotation => OA object describing the candidate annotations to be added or updated
 * candidate => dacura candidate object describing the properties to be updated or added
 *
 */
function update_candidate($target_id, $fragment_id = false){
	global $dacura_server;
	$ar = new AnalysisResults("Update Candidate");
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
		$upd_obj = array();
		if(isset($obj['provenance'])) $upd_obj['provenance'] = $obj['provenance'];
		if(isset($obj['candidate'])) $upd_obj['candidate'] = $obj['candidate'];
		if(isset($obj['annotation'])) $upd_obj['annotation'] = $obj['annotation'];
		if(isset($obj['meta'])) $upd_obj['meta'] = $obj['meta'];
		$options = (isset($obj['options'])) ? $obj['options'] : array();
		$ar = $dacura_server->updateCandidate($target_id, $upd_obj, $fragment_id, $options, isset($obj['test']));
	}
	return $dacura_server->write_decision($ar);
}

function update_update($upd_id){
	global $dacura_server;
	$json = file_get_contents('php://input');
	$ar = new AnalysisResults("Update Update");
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "Update Update lacks a json encoded body");
	}
	else {
		$meta = isset($obj['updatemeta']) ? $obj['updatemeta'] : array();
		$options = (isset($obj['options'])) ? $obj['options'] : array();
		$dacura_server->init("update update", $upd_id);
		$ar = $dacura_server->updateUpdate($upd_id, $obj, $meta, $options, isset($obj['test']));
	}
	return $dacura_server->write_decision($ar);
}

function get_candidate($candidate_id, $fragment_id = false){
	global $dacura_server;
	$options = isset($_GET['options']) ? $_GET['options'] : false;
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get_candidate", $candidate_id, $fragment_id);
	$ar = $dacura_server->getEntity($candidate_id, $fragment_id, $version, $options);
	return $dacura_server->send_retrieved_entity($ar, $format, $display, $options, $version);
}

function get_update($update_id){
	global $dacura_server;
	$options = isset($_GET['options']) ? $_GET['options'] : array();
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get_update", $update_id);
	$ar = $dacura_server->getUpdate($update_id, $version, $options);
	return $dacura_server->send_retrieved_update($ar, $format, $display, $options, $version);
}


function delete_candidate($candidate_id, $fragment_id = false){
/*	global $dacura_server;
	if($fragment_id){
		return $dacura_server->write_http_error(403, "Not allowed to delete fragment ids");
	}
	$update_obj = array("meta" => array("status" => "deleted"));
	$options = (isset($obj['options'])) ? $obj['options'] : array();
	$submission_result = $dacura_server->updateCandidate($candidate_id, $upd_obj, $fragment_id, $options, isset($obj['test']));	
	if($submission_result){
		return $dacura_server->write_decision($submission_result);
	}
	$dacura_server->write_http_error();*/
}

function list_candidates(){
	//probably want to do a bunch of lookups to 'get variables etc, but for now we're gonna do a quick and dirty one.
	global $dacura_server;
	if($_GET['type'] == "updates"){
		$dacura_server->init("list_candidate_updates");
		$cands = $dacura_server->getCandidateUpdates();
		if($cands){
			return $dacura_server->write_json_result($cands, "Returned " . count($cands) . " updates");
		}
	}
	else {
		$dacura_server->init("list_candidates");
		$cands = $dacura_server->getCandidates();
		if($cands){
			return $dacura_server->write_json_result($cands, "Returned " . count($cands) . " candidates");
		}
	}
	$dacura_server->write_http_error();
}




