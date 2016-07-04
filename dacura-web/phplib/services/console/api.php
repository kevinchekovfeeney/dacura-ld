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

getRoute()->get('/reload', 'reloadConsole');


function reloadConsole(){
	global $dacura_server, $service;
	$html = $service->getReloadScript($dacura_server);
	if($html){
		$dacura_server->write_http_result(200, $html);		
	}
	else {
		$dacura_server->write_http_error(500, "failed to reload console script");		
	}
}


