<?php
getRoute()->get('/nga', 'getngas');
getRoute()->get('/status', 'getstatus');
getRoute()->get('/schema', 'schema');
getRoute()->post('/status', 'updatestatus');
getRoute()->post('/polities', 'getpolities');
getRoute()->post('/dump', 'dump');
getRoute()->get('/view/(.+)', 'viewReport');
getRoute()->get('/grabscript', 'getGrabScript');
getRoute()->get('/history', 'getHistory');
getRoute()->post('/parse', 'parseVariable');
getRoute()->post('/validate', 'parseVariables');
getRoute()->post('/parsepage', 'parsePage');

function updatestatus(){
	global $dacura_server;
	set_time_limit(0);
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("updatestatus")){
		$id = isset($_POST['nga']) ? $_POST['nga'] : false;
		$suppress_cache = true;
		$dacura_server->updateStatus($id);
	}
	else {
		$dacura_server->write_comet_error();
	}
}
	

function getstatus(){
	global $dacura_server;
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("getstatus")){
		$id = isset($_GET['id']) ? $_GET['id'] : false;
		$suppress_cache = isset($_GET['refresh']);
		$status = $dacura_server->getStatus($id);
		if($status){
			$dacura_server->write_json_result($status, "Returned status for $id");
		}
		else {
			$dacura_server->write_http_error();		
		}
	}
	else {
		$dacura_server->write_http_error();
	}
}

/*
 * The api calls which access the seshat wiki data are accesss controlled
 */
function getngas(){
	global $dacura_server;
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("getngas")){
		$suppress_cache = isset($_GET['refresh']);
		$x = $dacura_server->getNGAList($suppress_cache);
		if($x){
			$dacura_server->write_json_result($x, "Returned list of ".count($x)." NGAs");
		}
		else {
			$dacura_server->write_http_error();		
		}
	}
	else {
		$dacura_server->write_http_error();
	}
}


/*
 * Generate a RDFS schema from the Seshat Codebook page
 */
function schema(){
	global $dacura_server;
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("schema")){
		$x = $dacura_server->generateSchema();
		if($x){
			echo $x;
		}
		else {
			$dacura_server->write_http_error();
		}
	}
	else {
		$dacura_server->write_http_error();
	}
	
}

function getpolities(){
	global $dacura_server;
	$nga = $_POST['nga'];
	$suppress_cache = isset($_POST['refresh']);
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("getpolities", $nga)){
		$x = $dacura_server->getPolities($nga, $suppress_cache);
		if($x){
			$dacura_server->write_json_result($x, "Returned list of ".count($x)." Polities");
		}
	}
	else {
		$dacura_server->write_http_error();
	}
}

/*
 * returns a historical dump of the wiki as it was at a given date
 */
function getHistory(){
	global $dacura_server;
	set_time_limit(0);
	$date_info = (isset($_POST['date_info'])) ? json_decode($_POST["date_info"]) : false;
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("history")){
		if($date_info){
			$dacura_server->getHistory($date_info);
			$dacura_server->logResult(200, "Created Seshat for ". count($data)." NGAs");
		}
		else {
			//$dacura_server->write_http_error(400, "No date information included in call");
			$dacura_server->write_json_result($dacura_server->getHistory(), "Returned history of wiki");
		}	
	}
	else {
		$dacura_server->write_comet_error();
	}
	
}


function dump(){
	global $dacura_server;
	set_time_limit(0);
	$data = json_decode($_POST["polities"]);
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("dump")){
		$dacura_server->getDump($data, false);
		$dacura_server->logResult(200, "Created Seshat for ". count($data)." NGAs");
	}
	else {
		$dacura_server->write_comet_error();
	}
}

function viewReport($rep){
	global $dacura_server;
	$dacura_server->init("viewreport", $rep);
	set_time_limit(0);
	if($dacura_server->userHasRole("admin")){
		if($dacura_server->getReport($rep)){
			$dacura_server->logResult(200, "Returned Report $rep");
		}
		else {
			$dacura_server->write_http_error();				
		}
	}
	else {
		$dacura_server->write_http_error();
	}
}

function parsePage(){
	global $dacura_server;
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("parsepage")){
		$suppress_cache = isset($_POST['refresh']);
		$url = $_POST["url"];
		$facts = $dacura_server->getFactsFromURL($url, $suppress_cache);
		if($facts){
			$rows = $dacura_server->factListToRows("NA", $dacura_server->formatNGAName($url), $facts, true);
			$dacura_server->write_json_result($rows, "Returned list of ".count($facts)." Facts");				
		}
		else {
			$dacura_server->write_http_error();
		}
	}
	else {
		$dacura_server->write_http_error();
	}
}

/*
 * The api calls below are world accessible
 */

function getGrabScript(){
	global $dacura_server, $service;
	$dacura_server->init("grabscript");
	ob_start();
	if($dacura_server->getServiceSetting('grabScriptFiles', false)){
		foreach($dacura_server->getServiceSetting('grabScriptFiles') as $f){
			if(file_exists($f)){
				include($f);
			}
			else {
				ob_end_clean();	
				return $dacura_server->write_http_error(500, "File $f not found");
			}
		}
	}	
	$f = $dacura_server->service->mydir."screens/grab.js";
	if(file_exists($f)){
		include_once($f);
		$page = ob_get_contents();
		ob_end_clean();	
		echo $page;	
		$dacura_server->service->logger->setResult(200, "Served grab script to ".$_SERVER['REMOTE_ADDR']);
	}
	else {
		ob_end_clean();	
		$dacura_server->write_http_error(500, "grab javascript file $f not found");
	}
}

/*
 * Input: string in $_POST['data']
 * { "value": string, "result_code": ["empty"|"simple"|"complex"|"error"], "result_msg", "datapoints": [{expanded content}]
 */
function parseVariable(){
	global $dacura_server;
	header("Access-Control-Allow-Origin: *");
	$dacura_server->init("parseVariable");
	if(isset($_POST["data"]) && $_POST["data"]){
		$var = $_POST["data"];
		$one_result = $dacura_server->parseVariableValue($var);
		$dacura_server->write_json_result($one_result, "Parsed value: $var: ".$one_result["result_code"]);				
	}
	else {
		$dacura_server->write_http_result(400, "No data included in post request for parsing", "notice");
	}
}


/*
 * Input: json-encoded array of values in $_POST['data']
 * [ "{variable value string}", ....]
 * Output: array of json objects:
 * [
 *
 */
function parseVariables(){
	global $dacura_server;
	header("Access-Control-Allow-Origin: *");
	
	$dacura_server->init("parseVariables");
	if(isset($_POST["data"]) && $_POST["data"] && ($vars = json_decode($_POST['data'], true))){
		$results = array();
		foreach($vars as $var){
			$results[] = $dacura_server->parseVariableValue($var);
		}
		$dacura_server->write_json_result($results, "Parsed ". count($results). " variables");				
	}
	else {
		$dacura_server->write_http_result(400, "No parseable data found in post request", "notice");
	}	
}

