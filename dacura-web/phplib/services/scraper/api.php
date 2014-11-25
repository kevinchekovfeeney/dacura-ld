<?php
getRoute()->get('/', 'listing');
getRoute()->get('/nga', 'getngas');
getRoute()->post('/polities', 'getpolities');
getRoute()->post('/', 'getpolitydata');
getRoute()->post('/parse', 'parsePage');
getRoute()->post('/dump', 'dump');
getRoute()->get('/grabscript', 'getGrabScript');


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
	$data = json_decode($_POST["data"]);
	$x = $sdas->getDump($data);
	//$x = $sdas->getDump($_POST["data"]);
	if($x){
		echo json_encode($x);
	}
}

function getGrabScript(){
	global $service;
	$f = $service->settings['files_url']."js/jquery-ui-1.10.2.custom.min.js";
	if(file_exists($f)){
		include($f);
	}
	else {
		echo "<h1>error $f does not exist</h1>";
	}
	$f = $service->mydir."screens/grab.js";
	if(file_exists($f)){
		include_once($f);
	}
}
