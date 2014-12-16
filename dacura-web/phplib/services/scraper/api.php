<?php
getRoute()->get('/', 'listing');
getRoute()->get('/nga', 'getngas');
getRoute()->post('/polities', 'getpolities');
getRoute()->post('/', 'getpolitydata');
getRoute()->post('/parse', 'parsePage');
getRoute()->post('/dump', 'dump');
getRoute()->get('/grabscript', 'getGrabScript');
getRoute()->get('/facts', 'getFactsFromPage');


include_once("ScraperDacuraServer.php");

function getngas(){
	global $service;
	$sdas = new ScraperDacuraAjaxServer($service);
	if($sdas->init()){
		$x = $sdas->getNGAList();
		if($x){
			echo json_encode($x);
		}
	}
}

function getpolities(){
	global $service;
	$nga = $_POST['nga'];
	$sdas = new ScraperDacuraAjaxServer($service);
	if($sdas->init()){
		$x = $sdas->getPolities($nga);
		if($x){
			echo json_encode($x);
		}
	}
}

function getFactsFromPage(){
	global $service;
	$url = "http://seshat.info/Code_book";//$_POST['url'];
	$sdas = new ScraperDacuraAjaxServer($service);
	if($sdas->init()){
		$x = $sdas->getFactsFromPage($url);
		if($x){
			//header('Content-Type: text/html; charset=utf-8');
			opr($x);
			//echo json_encode($x);
		}
	}	
}

function getPolityData(){
	global $service;
	$sdas = new ScraperDacuraAjaxServer($service);
	if($sdas->init()){
		$nga = $_POST["nga"];
		$polity = $_POST["polity"];
		$x = $sdas->getData($nga, $polity);
		if($x){
			echo json_encode($x);
		}
	}
}

function parsePage(){
	global $service;
	header("Access-Control-Allow-Origin: *");
	$sdas = new ScraperDacuraAjaxServer($service);
	//$data = json_decode();
	$x = $sdas->parsePage($_POST["data"]);
	if($x){
		echo $x;
	}
}

function dump(){
	global $service;
	$sdas = new ScraperDacuraAjaxServer($service);
	$data = json_decode($_POST["polities"]);
	$x = $sdas->getDump($data);
	if($x){
		echo json_encode($x);
	}
}

function getGrabScript(){
	global $service;
	$f = $service->settings['path_to_files']."js/jquery-ui-1.10.2.custom.min.js";
	if(file_exists($f)){
		include($f);
	}
	$f = $service->mydir."screens/grab.js";
	if(file_exists($f)){
		include_once($f);
	}
}

function testComet(){
	global $service;
	$sdas = new ScraperDacuraAjaxServer($service);
	$sdas->start_comet_output();
	$i = 100;
	while($i-- > 0){
		$sdas->write_comet_update("success", "$i is the loop<br>");
		usleep(200000);
	}
	$sdas->end_comet_output();
}

