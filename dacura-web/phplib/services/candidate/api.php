<?php
getRoute()->get('/ngskeleton', 'get_ngskeleton');

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

include_once "phplib/services/Ld/api.php";




