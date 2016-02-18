<?php
getRoute()->get('/ngskeleton', 'get_ngskeleton');
getRoute()->get('/ldo_classes', 'get_ldo_classes');
getRoute()->get('/ldo_frame/(\w+)', 'get_ldo_frame');

$ldo_type = "candidate";


function get_ngskeleton(){
	global $dacura_server;
	$dacura_server->init("getngskeleton");
	$skel = $dacura_server->getNGSkeleton();
	if($skel){
		return $dacura_server->write_json_result($skel, "Returned NG Skeleton");
	}
	$dacura_server->write_http_error();
}

function get_ldo_classes(){
	global $dacura_server;
	$dacura_server->init("get.ldo_classes");
	$ents = $dacura_server->getCandidateldoClasses();
	if($ents){
		return $dacura_server->write_json_result($ents, "Returned " . count($ents) . " " . $type. "s");
	}
	$dacura_server->write_http_error();
}

function get_ldo_frame($entid){
	global $dacura_server;
	$dacura_server->init("get.ldo_stub");
	$frame = $dacura_server->getClassFrame($entid);
	if($frame){
		return $dacura_server->write_json_result($frame, "Returned $entid frame");
	}
	$dacura_server->write_http_error();
}

include_once "phplib/services/ld/api.php";




