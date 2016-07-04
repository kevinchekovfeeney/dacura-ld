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
getRoute()->post('/propertyframe/(\w+)', 'getFilledPropertyFrame');
getRoute()->post('/propertyframe', 'getPropertyFrame');
getRoute()->get('/frame/(\w+)', 'getFilledFrame');
getRoute()->post('/frame', 'getEmptyFrame');

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
	if($ar = $dacura_server->getFilledFrame($cid)){
		$dacura_server->writeDecision($ar, "json", array());
	}
	else {
		$dacura_server->write_http_error();
	}
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
		if($ar = $dacura_server->getFrame($cls)){
			$dacura_server->writeDecision($ar, "json", array());
		}
		else {
			$dacura_server->write_http_error();
		}		
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
	global $dacura_server;
	$dacura_server->init("get property frame");
	if(!($cls = isset($_POST['class']) ? $_POST['class'] : false)){
		$dacura_server->write_http_error(400, "No class present in property frame request");
	}
	if(!($prop = isset($_POST['property']) ? $_POST['property'] : false)){
		$dacura_server->write_http_error(400, "No property id present in property frame request");
	}
	if($ar = $dacura_server->getPropertyFrame($cls, $prop)){
		$dacura_server->writeDecision($ar, "json", array());	
	}
	else {
		$dacura_server->write_http_error();
	}
}

/**
 * Returns a frame for a single property -
 *
 * POST /propertyframe
 * arguments: class, property, fragid, context
 */
function getFilledPropertyFrame($candid){
	global $dacura_server;
	$dacura_server->init("fill property frame for $candid");
	if(!($prop = isset($_POST['property']) ? $_POST['property'] : false)){
		$dacura_server->write_http_error(400, "No property id present in property frame request");
	}
	if($ar = $dacura_server->getFilledPropertyFrame($candid, $prop)){
		$dacura_server->writeDecision($ar, "json", array());
	}
	else {
		$dacura_server->write_http_error();
	}
}


include_once "phplib/services/ld/api.php";