<?php
//getRoute()->get('/ngskeleton', 'get_ngskeleton');
//getRoute()->get('/ldo_classes', 'get_ldo_classes');
//getRoute()->get('/ldo_frame/(\w+)', 'get_ldo_frame');

$ldo_type = "candidate";

getRoute()->post('/frame', 'getEmptyFrame');

function getEmptyFrame(){
	global $dacura_server;

	$dacura_server->init("create");
	
	$cls = $_POST['class'];
	$ar = $dacura_server->getFrame($cls);
	$dacura_server->writeDecision($ar, "json", array());
	
}

include_once "phplib/services/ld/api.php";
