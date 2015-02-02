<?php
require_once("phplib/libs/epiphany/src/Epi.php");

getRoute()->get('/nga', 'getngas');
getRoute()->post('/polities', 'getpolities');
getRoute()->post('/dump', 'dump');
getRoute()->get('/view/(.+)', 'viewReport');
getRoute()->get('/grabscript', 'getGrabScript');
getRoute()->get('/test', 'testParser');
getRoute()->post('/parse', 'parseData');
getRoute()->post('/', 'getpolitydata');


function getngas(){
	global $dacura_server;
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("getngas")){
		$x = $dacura_server->getNGAList();
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

function getpolities(){
	global $dacura_server;
	$nga = $_POST['nga'];
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("getpolities", $nga)){
		$x = $dacura_server->getPolities($nga);
		if($x){
			$dacura_server->write_json_result($x, "Returned list of ".count($x)." Polities");
		}
	}
	else {
		$dacura_server->write_http_error();
	}
}

function dump(){
	global $dacura_server;
	set_time_limit(0);
	$data = json_decode($_POST["polities"]);
	if($dacura_server->userHasRole("admin") && $dacura_server->seshatInit("dump")){
		$dacura_server->getDump($data);
		$dacura_server->ucontext->logger->setResult(200, "Created Seshat for ". count($data)." NGAs");
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
			$dacura_server->ucontext->logger->setResult(200, "Returned Report $rep");
		}
		else {
			$dacura_server->write_http_error();				
		}
	}
	else {
		$dacura_server->write_http_error();
	}
	
}

function getGrabScript(){
	global $dacura_server, $service;
	$dacura_server->init("grabscript");
	ob_start();
	if(isset($dacura_server->settings['scraper']['grabScriptFiles'])){}
	foreach($dacura_server->settings['scraper']['grabScriptFiles'] as $f){
		if(file_exists($f)){
			include($f);
		}
		else {
			ob_end_clean();	
			return $dacura_server->write_http_error(500, "File $f not found");
		}
	}
	$f = $dacura_server->ucontext->mydir."screens/grab.js";
	if(file_exists($f)){
		include_once($f);
		$page = ob_get_contents();
		ob_end_clean();	
		echo $page;	
		$dacura_server->ucontext->logger->setResult(200, "Served grab script to ".$_SERVER['REMOTE_ADDR']);
	}
	else {
		ob_end_clean();	
		$dacura_server->write_http_error(500, "grab javascript file $f not found");
	}
}

function testParser(){
	global $dacura_server;
	$dacura_server->init("testParser");
	if($dacura_server->userHasRole("admin")){
		opr($dacura_server->testParser());
	}
	else {
		$dacura_server->write_http_error();
	}	
}

/*
 * Needs to be re-written to take a more sensible format of input data. 
 */
function parseData(){
	global $dacura_server;
	header("Access-Control-Allow-Origin: *");
	$dacura_server->init("parsePage");
	if(isset($_POST["data"]) && $_POST["data"]){
		$parsed_data = json_decode($_POST["data"], true);
		if($parsed_data){
			for($i = 0; $i < count($parsed_data['data']); $i++){
				$one_result = $dacura_server->parseFactsFromString($parsed_data['data'][$i]['contents']);
				if(count($one_result['errors']) > 0){
					$parsed_data['data'][$i]['state'] = "error";
					$parsed_data['data'][$i]['errorMessage'] = $one_result['errors'][0]['comment'];
				}
				elseif($one_result['empty'] > 0) {
					$parsed_data['data'][$i]['state'] = "empty";						
				}
				elseif($one_result['lines'] > 0){
					$parsed_data['data'][$i]['state'] = "valid";						
				}
				else {
					$parsed_data['data'][$i]['state'] = "error";
					$parsed_data['data'][$i]['errorMessage'] = "Parser failed - not empty, error or valid";
				}
			}
			$dacura_server->write_json_result($parsed_data, "Returned list of ".count($parsed_data['data'])." facts");				
		}
		else {
			$dacura_server->write_http_result(400, "Data in post request does not have proper json format", "notice");				
		}
	}
	else {
		$dacura_server->write_http_result(400, "No data included in post request for parsing", "notice");
	}
}


