<?php
/*
 * This is Dacura's generic, general purpose, Linked Data API
 * this module is not for normal access, only for administrators directly accessing linked data objects to repair them....
 */
$x = @$entity_type;
if(!$x && !$dacura_server->userHasRole("admin", "all")){//meaning that this API is being accessed directly 
	$dacura_server->write_http_error(403, "No permission to directly access linked data API");	
}
else {
	getRoute()->get('/', 'list_entities');//list the entities of a certain type (or updates to them)
	getRoute()->get('/(\w+)/update/(\w+)', 'get_update');
	getRoute()->get('/(\w+)/(\w+)', 'get_entity');//with fragment id
	getRoute()->get('/(\w+)', 'get_entity');//no fragment id
	getRoute()->post('/(\w+)/update/(\w+)', 'update_update');
	getRoute()->post('/(\w+)/(\w+)', 'update_entity');//with frag id
	getRoute()->post('/', 'create_entity');//create a new entity of a given type
	getRoute()->post('/(\w+)', 'update_entity');//no frag id
	getRoute()->delete('/(\w+)/(\w+)', 'delete_entity');//with fragment id
	getRoute()->delete('/(\w+)', 'delete_entity');//no fragment id
	getRoute()->delete('/(\w+)/update/(\w+)', 'delete_update');//no fragment id	
}

/*
 * Returns a list of either entities, or updates to entities
 * Designed to accept filter requests from data tables module
 */
function list_entities(){
	global $dacura_server, $entity_type;
	$dt_options = array();
	if($entity_type){
		$dt_options['type'] = $entity_type;
		$type = $entity_type;
	}
	else {
		$type = "entity";
	}
	if($dacura_server->cid() != "all"){
		$dt_options['collectionid'] = $dacura_server->cid();
	}
	if($dacura_server->did() != "all"){
		$dt_options['datasetid'] = $dacura_server->did();
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
			foreach($ents as $i => $row){
				if(isset($row['meta']) && $row['meta']) $ents[$i]['meta'] = json_decode($row['meta'], true);
				if(isset($row['contents']) && $row['contents']) $ents[$i]['contents'] = json_decode($row['contents'], true);
			}
			return $dacura_server->write_json_result($ents, "Returned " . count($ents) . " " . $type. "s");
		}
	}
	$dacura_server->write_http_error();
}

function get_entity($entity_id, $fragment_id = false){
	global $dacura_server, $entity_type;
	$options = isset($_GET['options']) ? $_GET['options'] : false;
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get", $entity_id, $fragment_id);
	$ar = $dacura_server->getEntity($entity_id, $entity_type,$fragment_id, $version, $options);
	return $dacura_server->sendRetrievedEntity($ar, $format, $display, $options, $version);
}

function get_update($entity_id, $update_id){
	global $dacura_server, $entity_type;
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
	set_time_limit (0);	
	global $dacura_server, $entity_type;
	$dacura_server->init("create");
	$ar = new AnalysisResults("Create");
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "create request does not have a valid json encoded body");
		return $dacura_server->writeDecision($ar);
	}
	$type = (isset($obj['type'])) ? strtolower($obj['type']) : $entity_type;
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
	set_time_limit (0);
	global $dacura_server, $entity_type;
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
			$cnt = isset($obj['contents']) ? $obj['contents'] : "";
			$meta = isset($obj['meta']) ? $obj['meta'] : "";
			$options = (isset($obj['options'])) ? $obj['options'] : array();
			$ar = $dacura_server->updateEntity($target_id, $entity_type, $cnt, $meta, $fragment_id, $options, isset($obj['test']));
		}
	}
	return $dacura_server->writeDecision($ar);
}

function update_update($entity_id, $upd_id){
	set_time_limit (0);
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

function delete_update($entity_id, $updid){
	$dacura_server->init("delete update $updid");
	$ar = new AnalysisResults("Delete update $updid");
	$options = (isset($_GET['options'])) ? $_GET['options'] : array();
	$ar = $dacura_server->deleteUpdate($updid, $options, isset($_GET['test']));
	return $dacura_server->write_decision($ar);
}


