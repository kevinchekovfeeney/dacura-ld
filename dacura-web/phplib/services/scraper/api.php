<?php
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Max-Age: 1728000");
header('Access-Control-Allow-Headers: Accept, Accept-Encoding, Accept-Language, Host, Origin, Referer, Content-Type, Content-Length, Content-Range, Content-Disposition, Content-Description');
if(isset($_SERVER['HTTP_ORIGIN'])){
	header("Access-Control-Allow-Credentials: true");
	header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
}
else {
	header("Access-Control-Allow-Origin: null");
}

if($dacura_server->userHasFacet("export")){
	getRoute()->get('/nga', 'getngas');
	getRoute()->post('/polities', 'getpolities');
	getRoute()->post('/dump', 'dump');
}
if($dacura_server->userHasFacet("inspect")){
	getRoute()->get('/status', 'getstatus');
}
if($dacura_server->userHasFacet("view")){
	getRoute()->get('/view/(.+)', 'viewReport');
}
if($dacura_server->userHasFacet("manage")){
	getRoute()->post('/status', 'updatestatus');	
}
//if($dacura_server->userHasFacet("import")){
	getRoute()->post('/parse', 'parseVariable');
	getRoute()->post('/validate', 'parseVariables');
	getRoute()->post('/parsepage', 'parsePage');
//}
getRoute()->get('/grabscript', 'loadGrabScript');
getRoute()->get('/consolescript', 'loadRemoteAccessConsole');
getRoute()->get('/console', 'loadConsole');


/**
 * Recalculates the status of the nga / entire system
 */
function updatestatus(){
	global $dacura_server;
	set_time_limit(0);
	if($dacura_server->seshatInit("updatestatus")){
		$id = isset($_POST['nga']) ? $_POST['nga'] : false;
		$suppress_cache = true;
		$dacura_server->updateStatus($id);
	}
	else {
		$dacura_server->write_comet_error();
	}
}
	
/**
 * Calculates the status of the system
 */
function getstatus(){
	global $dacura_server;
	if($dacura_server->seshatInit("getstatus")){
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
 * retrieves the list of ngas from the wiki
 */
function getngas(){
	global $dacura_server;
	if($dacura_server->seshatInit("getngas")){
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
 * Retrieves the list of polities from the wiki
 */
function getpolities(){
	global $dacura_server;
	$nga = $_POST['nga'];
	$suppress_cache = isset($_POST['refresh']);
	if($dacura_server->seshatInit("getpolities", $nga)){
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
 * Dumps the 
 */
function dump(){
	global $dacura_server;
	set_time_limit(0);
	$data = json_decode($_POST["polities"]);
	if($dacura_server->seshatInit("dump")){
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
	if($dacura_server->getReport($rep)){
		$dacura_server->logResult(200, "Returned Report $rep");
	}
	else {
		$dacura_server->write_http_error();				
	}
}

function parsePage(){
	global $dacura_server;
	header("Access-Control-Allow-Origin: *");
	if($dacura_server->seshatInit("parsepage")){
		$suppress_cache = isset($_POST['refresh']);
		$url = $_POST["url"];
		$facts = $dacura_server->getFactsFromURL($url, $suppress_cache);
		if($facts){
			$op = $dacura_server->factListToAPIOutput($facts);
			$dacura_server->write_json_result($op, "Returned list of ".count($facts)." Facts");	
			//$dacura_server->write_json_result($facts, "Returned list of ".count($facts)." Facts");
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


function loadGrabScript(){
	global $dacura_server, $service;
	$dacura_server->init("remote.access");
	ob_start();
	$files_to_load = $dacura_server->getServiceSetting('grabScriptFiles', array());
	foreach($files_to_load as $f){
		if(file_exists($f)){
			include($f);
		}
		else {
			ob_end_clean();
			return $dacura_server->write_http_error(500, "File $f not found");
		}
	}
	$f = $dacura_server->service->mydir."screens/codebook.js";
	if(file_exists($f)){
		$params = $dacura_server->getConsoleParams();
		include($f);
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


function loadRemoteAccessConsole(){
	global $dacura_server, $service;
	$dacura_server->init("remote.access");
	ob_start();
	$files_to_load = $dacura_server->getServiceSetting('grabScriptFiles', array());
	foreach($files_to_load as $f){
		if(file_exists($f)){
			include($f);
		}
		else {
			ob_end_clean();
			return $dacura_server->write_http_error(500, "File $f not found");
		}
	}
	$f = $dacura_server->service->mydir."screens/load_console.js";
	if(file_exists($f)){
		$params = $dacura_server->getConsoleParams();
		include($f);
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

function loadConsole(){
	global $dacura_server, $service;
	$html = $service->getConsoleScript($dacura_server);
	if($html){
		$dacura_server->write_http_result(200, $html);		
	}
	else {
		$dacura_server->write_http_error(500, "failed to load console Script");		
	}
}


