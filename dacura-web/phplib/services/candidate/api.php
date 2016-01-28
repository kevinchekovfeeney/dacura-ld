<?php
getRoute()->get('/ngskeleton', 'get_ngskeleton');
getRoute()->get('/entity_classes', 'get_entity_classes');
getRoute()->get('/entity_frame/(\w+)', 'get_entity_frame');

$entity_type = "candidate";


function get_ngskeleton(){
	global $dacura_server;
	$dacura_server->init("getngskeleton");
	$skel = $dacura_server->getNGSkeleton();
	if($skel){
		return $dacura_server->write_json_result($skel, "Returned NG Skeleton");
	}
	$dacura_server->write_http_error();
}

function get_entity_classes(){
	global $dacura_server;
	$dacura_server->init("get.entity_classes");
	$ents = $dacura_server->getCandidateEntityClasses();
	if($ents){
		return $dacura_server->write_json_result($ents, "Returned " . count($ents) . " " . $type. "s");
	}
	$dacura_server->write_http_error();
}

function get_entity_frame($entid){
	global $dacura_server;
	$dacura_server->init("get.entity_stub");
	$frame = $dacura_server->getClassFrame($entid);
	if($frame){
		return $dacura_server->write_json_result($frame, "Returned $entid frame");
	}
	$dacura_server->write_http_error();
}

include_once "phplib/services/ld/api.php";




