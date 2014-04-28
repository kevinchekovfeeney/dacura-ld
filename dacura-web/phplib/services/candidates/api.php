<?php
getRoute()->get('/datatable', 'datatable');
getRoute()->get('/datatable/(\w+)', 'datatable_record');


include_once("CandidatesDacuraServer.php");




function datatable(){
	global $dacura_settings;
	$cds = new CandidatesDacuraServer($dacura_settings);
	$dto = $cds->getDataTablesOutput();
	if($dto) echo json_encode($dto);
	else $cds->write_error("Failed to fetch table of candidates.".$cds->errmsg, 400);
	
}

function datatable_record($x){
	global $dacura_settings;
	$cds = new CandidatesDacuraServer($dacura_settings);
	$sto = $cds->getStateTablesOutput($x);
	if($sto) echo json_encode($sto);
	else $cds->write_error("Failed to fetch table of candidate $x state changes.".$cds->errmsg, 400);	
	
}