
<?php
//getRoute()->get('/datatable', 'datatable');
//getRoute()->get('/datatable/(\w+)', 'datatable_record');
//getRoute()->get('/', 'get_candidates');
//getRoute()->post('/', 'update_candidates');
getRoute()->post('/(\w+)', 'create_candidate');
getRoute()->get('/(\w+)', 'get_candidate');
getRoute()->post('/(\w+)', 'update_candidate');
getRoute()->delete('/(\w+)', 'delete_candidate');


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

function get_candidate($id){

}

function update_candidate($target_type, $target_id){
	global $dacura_server;
	$c_id = $dacura_server->ucontext->getCollectionID();
	$d_id = $dacura_server->ucontext->getDatasetID();
	//for the moment all candidates must be sent to a specific dataset
	if($c_id == "all" or $d_id == "all"){
		$dacura_server->write_http_error(400, "candidate updates must be sent to a specific dataset");
	}
	$target = isset($_POST['target']) ? json_decode($_POST['target'], true) : array();
	$source = isset($_POST['source']) ? json_decode($_POST['source'], true) : array();
	$candidate = isset($_POST['candidate']) ? json_decode($_POST['candidate'], true) : array();
	$dacura_server->init("update_candidate", $target_type, $target_id);
	if($target_type == "report"){
		if($target_id == "create"){
				
		}
	}
	else { //"candidate"

	}
}

function datatable(){
	global $service;
	$cds = new CandidatesDacuraServer($service);
	$dto = $cds->getDataTablesOutput();
	if($dto) echo json_encode($dto);
	else $cds->write_error("Failed to fetch table of candidates.".$cds->errmsg, 400);

}

function datatable_record($x){
	global $service;
	$cds = new CandidatesDacuraServer($service);
	$sto = $cds->getStateTablesOutput($x);
	if($sto) echo json_encode($sto);
	else $cds->write_error("Failed to fetch table of candidate $x state changes.".$cds->errmsg, 400);

}