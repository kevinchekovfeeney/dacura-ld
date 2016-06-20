<?php
/**
 * API for candidate service - api functions added to ld api
 *
 * @author chekov
 * @package candidate/api
 * @license GPL v2
 */
$ldo_type = "candidate";

getRoute()->get('/entities', 'getEntityClasses');
getRoute()->post('/frame', 'getEmptyFrame');
getRoute()->get('/frame/(\w+)', 'getFilledFrame');
getRoute()->post('/propertyframe', 'getPropertyFrame');

/**
 * Returns a list of the valid classes of candidates supported by this collection
 */
function getEntityClasses(){
	global $dacura_server;
	$dacura_server->init("get entities");
	$dacura_server->write_json_result($dacura_server->valid_candidate_types, "returned entity classes");
}

/**
 * Returns a filled in frame to represent the candidate
 * @param string $cid candidate id
 */
function getFilledFrame($cid){
	global $dacura_server;
	$dacura_server->init("fill frame $cid");
	$ar = $dacura_server->getFilledFrame($cid);
	$dacura_server->writeDecision($ar, "json", array());
}

/**
 * Returns an empty frame to represent a particular candidate type
 * 
 * POST /frame
 * arguments: class => the rdf:type of the candidate
 */
function getEmptyFrame(){
	global $dacura_server;
	$dacura_server->init("create");
	$cls = isset($_POST['class']) ? $_POST['class'] : false;
	if($cls){
		$ar = $dacura_server->getFrame($cls);
		$dacura_server->writeDecision($ar, "json", array());
	}
	else {
		$dacura_server->write_http_error(400, "No class present in frame request");
	}
}

/**
 * Returns a frame for a single property - 
 * 
 * POST /propertyframe
 * arguments: class, property, fragid, context
 */
function getPropertyFrame(){	
}

include_once "phplib/services/ld/api.php";