<?php
//set_time_limit (1800);



/**
 * API for ld service - Dacura's generic, general purpose, Linked Data API
 *
 * this service is not for normal access, only for administrators directly accessing linked data objects to repair them....
 * @author chekov
 * @package ld/api
 * @license GPL v2
 */
$x = @$ldo_type;
//if(!$x && !$dacura_server->userHasRole("admin", "all")){//meaning that this API is being accessed directly 
//	$dacura_server->write_http_error(403, "No permission to directly access linked data API");	
//}
//else {
	if(!$x){
		$ldo_type = isset($_GET['ldtype']) ? $_GET['ldtype'] : "";
	}
	getRoute()->get('/', 'list_ldos');//list the linked data objects of a certain type (or updates to them)
	getRoute()->get('/update', 'list_updates');//list the linked data objects of a certain type (or updates to them)
	getRoute()->get('/update/(\w+)', 'get_update');
	getRoute()->get('/(\w+)/(\w+)', 'get_ldo');//with fragment id
	getRoute()->get('/(\w+)', 'get_ldo');//no fragment id
	getRoute()->post('/update/(\w+)', 'update_update');
	getRoute()->post('/(\w+)/(\w+)', 'update_ldo');//with frag id
	getRoute()->post('/', 'create_ldo');//create a new ldo of a given type
	getRoute()->post('/(\w+)', 'update_ldo');//no frag id
	getRoute()->delete('/(\w+)/(\w+)', 'delete_ldo');//with fragment id
	getRoute()->delete('/(\w+)', 'delete_ldo');//no fragment id
	getRoute()->delete('//update/(\w+)', 'delete_update');//no fragment id	
//}


/**
 * Create a new Linked Data Object and return the result to the user
 * 
 * POST /
 * 
 * Takes input as a json object with the following parameters
 * * [demand_id_token] - the requested id of the object
 * * options | fail_if_id_denied, include_graph_results
 * * format
 * * ldtype 
 * * contents
 * * ldfile
 * * ldurl
 *  
 */

set_time_limit (1800);
	
	
function create_ldo(){
	global $dacura_server, $ldo_type;
	$dacura_server->init("create");
	$ar = new DacuraResult("Create");
	$json = file_get_contents('php://input');
	$obj = json_decode($json, true);
	if(!$obj){
		$ar->failure(400, "Communication Error", "create request does not have a valid json encoded body");
		return $dacura_server->writeDecision($ar);
	}
	if(!$ldo_type){
		if(isset($obj['ldtype']) && $obj['ldtype'] && isset(LDO::$ldo_types[$obj['ldtype']])){
			$ldo_type = $obj['ldtype'];
		}
		else {
			$ar->failure(400, "Request Error", "create request does not have a valid linked data type associated with it");
			return $dacura_server->writeDecision($ar);				
		}
	}
	$demand_id = false;
	if(isset($obj[$dacura_server->getServiceSetting("demand_id_token", "@id")])){
		$demand_id = $obj[$dacura_server->getServiceSetting("demand_id_token", "@id")];
	}
	$format = (isset($obj['format'])) ? strtolower($obj['format']) : "";
	$options = (isset($obj['options'])) ? $obj['options'] : array();
	$create_obj = array();
	if(isset($obj['contents'])){
		 $create_obj['contents'] = $obj['contents'];
	}
	elseif(isset($obj['ldfile'])){
		$create_obj['ldfile'] = $obj['ldfile'];
	}
	elseif(isset($obj['ldurl'])){ 
		$create_obj['ldurl'] = $obj['ldurl'];
	}
	if(isset($obj['meta'])) $create_obj['meta'] = $obj['meta'];
	$ar = $dacura_server->createLDO($ldo_type, $create_obj, $demand_id, $format, $options, isset($obj['test']) && $obj['test']);
	return $dacura_server->writeDecision($ar, $format, $options);
}



/**
 * GET /
 *
 * accepts datatable ajax options accepted https://datatables.net/manual/server-side
 * @return a list of linked data objects suitable for tabular viewing
 * @api
 */
function list_ldos(){
	global $dacura_server, $ldo_type;
	//read datatable options from $_GET
	$dt_options = get_dtoptions($dacura_server->cid());
	if($ldo_type){
		$dt_options['type'] = $ldo_type;
	}
	//read dacura options from $_GET
	$dcoptions = isset($_GET['options']) ? $_GET['options'] : false;
	$dacura_server->init(($ldo_type ? $ldo_type : "ldo")."list");
	$ldos = $dacura_server->getLDOs($dt_options, $dcoptions);
	if(is_array($ldos)){
		$dacura_server->recordUserAction("get.$ldo_type.list");	
		return $dacura_server->write_json_result($ldos, "Returned " . count($ldos) . " " . ($ldo_type ? $ldo_type : "ld objects"). "s");
	}
	$dacura_server->write_http_error();
}

/**
 * GET /
 *
 * accepts datatable ajax options accepted https://datatables.net/manual/server-side
 * @return a list of linked data update objects suitable for tabular viewing
 * @api
 */
function list_updates(){
	global $dacura_server, $ldo_type;
	$dt_options = get_dtoptions($dacura_server->cid());
	if($ldo_type){
		$dt_options['type'] = $ldo_type;
	}
	$dcoptions = isset($_GET['options']) ? $_GET['options'] : false;
	$dacura_server->init(($ldo_type ? $ldo_type : "ldo")."list_updates");
	$ldos = $dacura_server->getUpdates($dt_options, $dcoptions);
	if(is_array($ldos)){
		$dacura_server->recordUserAction("get.$ldo_type.updates");		
		return $dacura_server->write_json_result($ldos, "Returned " . count($ldos) . " updates");
	}
	$dacura_server->write_http_error();	
}

/**
 * Helper function: reads datatable options from GET requests
 * @param string $cid the current collection id
 * @return array<string:mixed> name value settings for datatable ajax filters
 */
function get_dtoptions($cid){
	$dt_options = array();
	isset($_GET['draw']) && $dt_options['draw'] = $_GET['draw'];
	$dt_options['start'] = isset($_GET['start']) ? $_GET['start'] : 0;
	$dt_options['length'] = isset($_GET['length']) ? $_GET['length'] : 0;
	$dt_options['search'] = isset($_GET['search']) ? $_GET['search'] : false;
	$dt_options['columns'] = isset($_GET['columns']) ? $_GET['columns'] : false;
	$dt_options['order'] = isset($_GET['order']) ? $_GET['order'] : false;
	if($cid != "all"){
		$dt_options['collectionid'] = $cid;
	}
	return $dt_options;
}

/**
 * GET /$ldo_id/[$fragment_id]
 * 
 * optional arguments: [options, version, format]
 * Retrieve a json representation of a linked data object or a fragment of the object from the api
 * @param string $ldo_id the id of the object
 * @param string $fragment_id - the fragment id desired (only supported for dacura managed node ids)
 * @return string json LDO / DacuraResult on error
 */
function get_ldo($ldo_id, $fragment_id = false){
	global $dacura_server, $ldo_type;
	$options = isset($_GET['options']) ? $_GET['options'] : false;
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$dacura_server->init("get".($ldo_type ? $ldo_type : "ldo"), $ldo_id, $fragment_id);
	$ar = $dacura_server->getLDO($ldo_id, $ldo_type, $fragment_id, $version, $options);
	$params = array("id" => $ldo_id);
	if($version){
		$params['version'] = $version;
	}
	if($fragment_id){
		$params['fragment'] = $fragment_id;
	}
	$dacura_server->recordUserAction("get.$ldo_type", $params);
	return $dacura_server->sendRetrievedLdo($ar, $format, $options);
}

/**
 * GET /update/$update_id
 * 
 * optional arguments: [options, version, format]
 * version represents the version of the associated linked data object that the update should be applied to to create a before and after display
 * 
 * Retrieve a json representation of a linked data object update from the api
 * @param string $ldo_id the id of the object
 * @return string json LDOUpdate / DacuraResult on error
 */
function get_update($update_id){
	global $dacura_server, $ldo_type;
	$options = isset($_GET['options']) ? $_GET['options'] : array();
	$version = isset($_GET['version']) ? $_GET['version'] : false;
	$format = isset($_GET['format']) ? $_GET['format'] : false;
	$display = isset($_GET['display']) ? $_GET['display'] : false;
	$dacura_server->init("get_update", $update_id);
	$ar = $dacura_server->getUpdate($update_id, $version, $options);
	$params = array("type" => $ldo_type);
	if($version){
		$params['version'] = $version;
	}
	$dacura_server->recordUserAction("get.update.$update_id", $params);
	return $dacura_server->sendRetrievedUpdate($ar, $format, $display, $options, $version);
}

/**
 *
 * @param string $target_id the id of the ldo that is being updated
 *
 */
function update_ldo($target_id, $fragment_id = false){
	set_time_limit (0);
	global $dacura_server, $ldo_type;
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
			$ar = $dacura_server->updateLDO($target_id, $ldo_type, $cnt, $meta, $fragment_id, $options, isset($obj['test']));
		}
	}
	return $dacura_server->writeDecision($ar);
}

function update_update($ldo_id, $upd_id){
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
		$ldometa = isset($obj['meta']) ? $obj['meta'] : array();
		$ldocontents = isset($obj['contents']) ? $obj['contents'] : array();
		if(count($umeta) == 0 && count($ldometa) == 0 && count($ldocontents) == 0 ){
			$ar->failure(400, "Format Error", "Update Request must have at least one of a meta, a contents or an updatemeta property");				
		}
		else {
			$options = (isset($obj['options'])) ? $obj['options'] : array();
			$ar = $dacura_server->updateUpdate($upd_id, $ldocontents, $ldometa, $umeta, $options, isset($obj['test']));				
		}
	}
	return $dacura_server->writeDecision($ar);
}

function delete_ldo($ldoid, $fragment_id = false){
	$dacura_server->init("delete ldo $ldoid");
	$ar = new AnalysisResults("Delete ldo $ldoid");
	$options = (isset($_GET['options'])) ? $_GET['options'] : array();
	$ar = $dacura_server->deleteLDO($ldoid, $fragment_id, $options, isset($_GET['test']));
	return $dacura_server->write_decision($ar);
}

function delete_update($ldo_id, $updid){
	$dacura_server->init("delete update $updid");
	$ar = new AnalysisResults("Delete update $updid");
	$options = (isset($_GET['options'])) ? $_GET['options'] : array();
	$ar = $dacura_server->deleteUpdate($updid, $options, isset($_GET['test']));
	return $dacura_server->write_decision($ar);
}


