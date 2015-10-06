<?php
//getRoute()->get('/datatable', 'datatable');
//getRoute()->get('/datatable/(\w+)', 'datatable_record');
//getRoute()->get('/', 'get_candidates');
//getRoute()->post('/', 'update_candidates');
/*
 * These API calls have candidate ids as targets
 * this is just an api - it has no associated pages. 
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
 */
getRoute()->get('/ngskeleton', 'get_ngskeleton');

function get_ngskeleton(){
	global $dacura_server;
	$dacura_server->init("getngskeleton");
	$skel = $dacura_server->getNGSkeleton();
	if($skel){
		return $dacura_server->write_json_result($skel, "Returned NG Skeleton");
	}
	$dacura_server->write_http_error();
}





