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
getRoute()->get('/(\w+)', 'get_candidate');
getRoute()->post('/(\w+)', 'update_candidate');
getRoute()->delete('/(\w+)', 'delete_candidate');

/*
 * post requests take input as a application/x-www-form-urlencoded 
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
function update_candidate($target_id){
	global $dacura_server;
	/*
	 * Source and candidate are required for update
	 */
	if(!(isset($_POST['source'])) || !($source = json_decode($_POST['source'], true))){
		$dacura_server->write_http_error(400, "candidate create must have a valid json source");
	}
	if(!isset($_POST['candidate']) || !($candidate = json_decode($_POST['candidate'], true))){
		$dacura_server->write_http_error(400, "candidate create must have a valid json candidate object");
	}
	$annotations = isset($_POST['annotations']) ? json_decode($_POST['annotations'], true) : array();
	$test = isset($_POST['test']) ? true : false;
	//runs the request through the dacura update analyser
	$cand = $dacura_server->updateCandidate($target_id, $source, $candidate, $annotations, isset($_POST['test']));
	if($cand){
		//apply workflow
		$submission_result = $dacura_server->processCandidate($cand);
		if($submission_result){
			return $dacura_server->write_json_result($submission_result, "Updated candidate ".$cand->reportString());
		}
	}
	$dacura_server->write_http_error();
}

function create_candidate(){
	global $dacura_server;
	/*
	 * Source and candidate are required for create
	 */
	if(!(isset($_POST['source'])) || !($source = json_decode($_POST['source'], true))){
		return $dacura_server->write_http_error(400, "candidate create must have a valid json source");
	}
	if(!isset($_POST['candidate']) || !($candidate = json_decode($_POST['candidate'], true))){
		return $dacura_server->write_http_error(400, "candidate create must have a valid json candidate object");
	}
	/*
	 * create also requires a candidate class
	 */
	if(!isset($candidate['class']) or !$candidate['class']){
		$dacura_server->write_http_error(400, "candidate create must have a valid candidate class");
	}
	/*
	 * annotations are optional
	 */
	$annotations = isset($_POST['annotations']) ? json_decode($_POST['annotations'], true) : array();
	$dacura_server->init("create_candidate");
	//runs the request through the dacura update analyser
	$cand = $dacura_server->createCandidate($source, $candidate, $annotations, isset($_POST['test']));
	if($cand){
		//apply workflow
		$submission_result = $dacura_server->processCandidate($cand);
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

function get_candidate($candidate_id){
	global $dacura_server;
	$facet = isset($_GET['facet']) ? $_GET['facet'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$dacura_server->init("get_candidate", $candidate_id, $facet);
	$cand = $dacura_server->getCandidate($candidate_id, $facet, $format);
	if($cand){
		return $dacura_server->send_candidate($cand);
	}
	$dacura_server->write_http_error();
}

/**
 * Batch operations...???
function get_candidates(){
	global $dacura_server;
	$dacura_server->init("get_candidates");
	$c_id = $dacura_server->ucontext->getCollectionID();
	$d_id = $dacura_server->ucontext->getDatasetID();
	$format = isset($_GET['format']) ? $_GET['format'] : "json";
	if($dacura_server->userHasRole("admin")){
		$collobj = $dacura_server->getCandidates($c_id, $d_id, $format);
		if($format == "json"){
			return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
		}
	}
	$dacura_server->write_http_error();
}

function update_candidates(){
	global $dacura_server;
	$dacura_server->init("get_candidates");
	$c_id = $dacura_server->ucontext->getCollectionID();
	$d_id = $dacura_server->ucontext->getDatasetID();
	$format = isset($_GET['format']) ? $_GET['format'] : "json";
	$candidates_update_list = json_decode($_POST['candidates'], true);

	if($dacura_server->userHasRole("admin")){
		$collobj = $dacura_server->updateCandidates($c_id, $d_id, $format);
		if($format == "json"){
			return $dacura_server->write_json_result($collobj, "Retrieved configuration listing for ".$dacura_server->contextStr());
		}
	}
	$dacura_server->write_http_error();
}

*/