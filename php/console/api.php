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

getRoute()->get('/(\w*)(/?)(\w*)', 'loadClientCapabilities');


function loadClientCapabilities($key = false, $nowt = false, $value = false){
	global $dacura_server;
	//return $dacura_server->write_http_error(402, "Failed to do something");
	
	if($caps = $dacura_server->getClientCapabilities($key, $value)){
		return $dacura_server->write_json_result($caps, "Fetched capabilities for client.");
	}
	return $dacura_server->write_http_error();
}


