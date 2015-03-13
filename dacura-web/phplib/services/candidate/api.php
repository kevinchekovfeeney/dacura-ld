<?php
//getRoute()->get('/datatable', 'datatable');
//getRoute()->get('/datatable/(\w+)', 'datatable_record');
//getRoute()->get('/', 'get_candidates');
//getRoute()->post('/', 'update_candidates');
/*
 * These API calls have candidate ids as targets
 * this is just an api - it has no associated pages. 
 */
getRoute()->get('/', 'usage');//show usage information for root get access
getRoute()->post('/', 'create_candidate');//create a new candidate (type etc, are in payload)
getRoute()->get('/type/(\w+)', 'get_candidate_schema');
getRoute()->get('/(\w+)/(\w+)', 'get_candidate');//with fragment id
getRoute()->get('/(\w+)', 'get_candidate');
getRoute()->post('/(\w+)', 'update_candidate');
getRoute()->post('/(\w+)/(\w+)', 'update_candidate');//with fragment id
getRoute()->delete('/(\w+)', 'delete_candidate');


function get_candidate($candidate_id, $facet_id = false){
	global $dacura_server;
	//$facet = isset($_GET['facet']) ? $_GET['facet'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$dacura_server->init("get_candidate", $candidate_id, $facet_id);
	$cand = $dacura_server->getCandidate($candidate_id, $facet_id, $format);
	if($cand){
		return $dacura_server->send_candidate($cand);
	}
	$dacura_server->write_http_error();
}

/*
 * post requests take input as a application/json
 */

/**
 * 
 * @param string $target_id the id of the candidate that is being updated
 * Takes a application/x-www-form-urlencoded post with the following parameters:
 * source => PROVjson object describing the provenance of the candidate update request
 * annotations => OA object describing the candidate annotations to be added or updated 
 * candidate => dacura candidate object describing the properties to be updated or added 
 * 
 */
function update_candidate($target_id, $fragment_id = false){
	global $dacura_server;
	global $dacura_server;
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		return $dacura_server->write_http_error(400, "candidate update must have a valid body");
	}
	/*
	 * Source and candidate are required 
	 */
	if(!(isset($obj['provenance'])) || !(isset($obj['candidate']))){
		return $dacura_server->write_http_error(400, "candidate create requires both provenance and candidate");
	}
	//runs the request through the dacura update analyser
	$cand = $dacura_server->createUpdateCandidate($target_id, $obj, $fragment_id, isset($obj['test']));
	if($cand){
		//apply workflow
		$submission_result = $dacura_server->processUpdateCandidate($cand, $fragment_id, isset($obj['test']));
		if($submission_result){
			return $dacura_server->write_json_result($submission_result, "Updated candidate ".$cand->reportString());
		}
	}
	$dacura_server->write_http_error();
}

function create_candidate(){
	global $dacura_server;
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		return $dacura_server->write_http_error(400, "candidate create must have a valid body");
	}
	/*
	 * Source and candidate are required for create
	 */
	if(!(isset($obj['provenance'])) || !(isset($obj['candidate']))){
		return $dacura_server->write_http_error(400, "candidate create requires both provenance and candidate");
	}
	/*
	 * create also requires a candidate class
	 */
	if(!isset($obj['candidate']['rdf:type']) or ! $obj['candidate']['rdf:type']){
		$dacura_server->write_http_error(400, "candidate create must have a valid candidate class");
	}
	/*
	 * annotations are optional
	 */
	$dacura_server->init("create_candidate");
	//runs the request through the dacura update analyser
	$cand = $dacura_server->createCandidate($obj, isset($obj['test']));
	if($cand){
		//apply workflow
		$submission_result = $dacura_server->processCreateCandidate($cand, isset($obj['test']));
		if($submission_result){
			return $dacura_server->write_json_result($submission_result, "Created candidate ".$cand->reportString());		
		}	
	}
	$dacura_server->write_http_error();
}

function get_candidate_schema($candidate_type){
	$facet = isset($_GET['facet']) ? $_GET['facet'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	global $dacura_server;
	$dacura_server->init("get_candidate_schema", $candidate_type, $facet);
	$cand = $dacura_server->getCandidateSchema($candidate_type, $facet, $format);
	if($cand){
		return $dacura_server->send_candidate_schema($cand);
	}
	$dacura_server->write_http_error();
}
