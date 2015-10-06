<?php
/*
 * These API calls have candidate ids as targets
 * this is just an api - it has no associated pages. 
 */
getRoute()->get('/', 'list_entities');//list the entities of a certain type
getRoute()->get('/update/(\w+)', 'get_update');
getRoute()->get('/(\w+)/(\w+)', 'get_entity');//with fragment id
getRoute()->get('/(\w+)', 'get_entity');//no fragment id
getRoute()->post('/update/(\w+)', 'update_update');
getRoute()->post('/(\w+)/(\w+)', 'update_entity');//with frag id
getRoute()->post('/', 'create_entity');//create a new entity of a given type
getRoute()->post('/(\w+)', 'update_entity');//no frag id
getRoute()->delete('/(\w+)/(\w+)', 'delete_entity');//with fragment id
getRoute()->delete('/(\w+)', 'delete_entity');//no fragment id
getRoute()->delete('/update/(\w+)', 'delete_update');//no fragment id
set_time_limit (0);


function list_entities(){
	//probably want to do a bunch of lookups to 'get variables etc, but for now we're gonna do a quick and dirty one.
	global $dacura_server;
	$dt_options = array();
	if(isset($_GET['entity_type'])){
		$dt_options["type"] = $_GET['entity_type'];
		$type = $dt_options['type'];
	}
	else {
		$type = "entity";
	}
	$options = isset($_GET['options']) ? $_GET['options'] : false;
	isset($_GET['draw']) && $dt_options['draw'] = $_GET['draw'];
	$dt_options['start'] = isset($_GET['start']) ? $_GET['start'] : 0;
	$dt_options['length'] = isset($_GET['length']) ? $_GET['length'] : 0;	
	$dt_options['search'] = isset($_GET['search']) ? $_GET['search'] : false;
	$dt_options['columns'] = isset($_GET['columns']) ? $_GET['columns'] : false;
	$dt_options['order'] = isset($_GET['order']) ? $_GET['order'] : false;
	if(isset($_GET['type']) && $_GET['type'] == "updates"){
		$dacura_server->init("list_".$type."_updates");
		$ents = $dacura_server->getUpdates($dt_options);
		if($ents){
			return $dacura_server->write_json_result($ents, "Returned " . count($ents) . " updates");
		}
	}
	else {
		$dacura_server->init("list_".$type);
		$ents = $dacura_server->getEntities($dt_options);
		if($ents){
			return $dacura_server->write_json_result($ents, "Returned " . count($ents) . " " . $type. "s");
		}
	}
	$dacura_server->write_http_error();
}

function get_entity($entity_id, $fragment_id = false){
	global $dacura_server;
	$options = isset($_GET['options']) ? $_GET['options'] : false;
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get", $entity_id, $fragment_id);
	$ar = $dacura_server->getEntity($entity_id, $fragment_id, $version, $options);
	return $dacura_server->sendRetrievedEntity($ar, $format, $display, $options, $version);
}

function get_update($update_id){
	global $dacura_server;
	$options = isset($_GET['options']) ? $_GET['options'] : array();
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get_update", $update_id);
	$ar = $dacura_server->getUpdate($update_id, $version, $options);
	return $dacura_server->sendRetrievedUpdate($ar, $format, $display, $options, $version);
}

/*
 * post requests take input as a application/json
 */
function create_entity(){
	global $dacura_server;
	$dacura_server->init("create");
	$ar = new AnalysisResults("Create");
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "create request does not have a valid json encoded body");
		return $dacura_server->writeDecision($ar);
	}
	$type = (isset($obj['type'])) ? strtolower($obj['type']) : "candidate";
	$options = (isset($obj['options'])) ? $obj['options'] : array();
	$create_obj = array();
	if(isset($obj['contents'])) $create_obj['contents'] = $obj['contents'];
	if(isset($obj['meta'])) $create_obj['meta'] = $obj['meta'];
	$demand_id = isset($create_obj['meta']['@id']) ? $create_obj['meta']['@id'] : false;
	if($demand_id) {
		unset($create_obj['meta']['@id']);
	}
	else {
		$demand_id = isset($create_obj['contents']['@id']) ? $create_obj['contents']['@id'] : false;
		if($demand_id) {
			unset($create_obj['contents']['@id']);
		}		
	}
	$ar = $dacura_server->createEntity($type, $create_obj, $demand_id, $options, isset($obj['test']));
	return $dacura_server->writeDecision($ar);
}

/**
 *
 * @param string $target_id the id of the entity that is being updated
 *
 */
function update_entity($target_id, $fragment_id = false){
	global $dacura_server;
	$ar = new AnalysisResults("Update $target_id $fragment_id");
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "Update Request lacks a json encoded body");
	}
	elseif($fragment_id){
		$ar->failure(403, "Illegal Update", "Attempt to directly update fragment $fragment_id. Fragments must be updated in context.");		
	}
	else {
		$upd_obj = array();
		if(!isset($obj['contents']) && !isset($obj['meta'])){
			$ar->failure(400, "Format Error", "Update Request must have at least one of a meta or a contents property");				
		}
		else {
			if(isset($obj['contents'])) $upd_obj = $obj['contents'];
			if(isset($obj['meta'])) $upd_obj['meta'] = $obj['meta'];
			$options = (isset($obj['options'])) ? $obj['options'] : array();
			$ar = $dacura_server->updateEntity($target_id, $upd_obj, $fragment_id, $options, isset($obj['test']));
		}
	}
	return $dacura_server->writeDecision($ar);
}

function update_update($upd_id){
	global $dacura_server;
	$json = file_get_contents('php://input');
	$ar = new AnalysisResults("Update Update");
	$obj = json_decode($json, true);
	$dacura_server->init("update update", $upd_id);
	if(!$obj){
		$ar->failure(400, "Communication Error", "Update Update lacks a json encoded body");
	}
	else {
		$umeta = isset($obj['updatemeta']) ? $obj['updatemeta'] : array();
		$entmeta = isset($obj['meta']) ? $obj['meta'] : array();
		$entcontents = isset($obj['contents']) ? $obj['contents'] : array();
		if(count($umeta) == 0 && count($entmeta) == 0 && count($entcontents) == 0 ){
			$ar->failure(400, "Format Error", "Update Request must have at least one of a meta, a contents or an updatemeta property");				
		}
		else {
			$options = (isset($obj['options'])) ? $obj['options'] : array();
			$ar = $dacura_server->updateUpdate($upd_id, $entcontents, $entmeta, $umeta, $options, isset($obj['test']));				
		}
	}
	return $dacura_server->writeDecision($ar);
}

function delete_entity($entid, $fragment_id = false){
	$dacura_server->init("delete entity $entid");
	$ar = new AnalysisResults("Delete entity $entid");
	$options = (isset($_GET['options'])) ? $_GET['options'] : array();
	$ar = $dacura_server->deleteEntity($entid, $fragment_id, $options, isset($_GET['test']));
	return $dacura_server->write_decision($ar);
}

function delete_update($updid){
	$dacura_server->init("delete update $updid");
	$ar = new AnalysisResults("Delete update $updid");
	$options = (isset($_GET['options'])) ? $_GET['options'] : array();
	$ar = $dacura_server->deleteUpdate($updid, $options, isset($_GET['test']));
	return $dacura_server->write_decision($ar);
}


