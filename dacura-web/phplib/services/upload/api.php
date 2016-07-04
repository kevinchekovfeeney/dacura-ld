<?php 
if($dacura_server->userHasFacet("manage")){
  getRoute()->post('/files', 'upload');
}
/**
 * POST /files
 *
 * uploads a file to the system's temporary storage area and returns a reference
 * Used for form submissions that include files 
 * @return a string which is an internal dacura reference to the file, the string must be passed back in a subsequent call
 * @api
 */
function upload(){
	global $dacura_server;
	$payload = file_get_contents('php://input');
	if(!$payload){
		return $dacura_server->write_http_error(400, "Failed to load the file from input");
	}
	$fname = uniqid().".tmp";
	$x = $dacura_server->getSystemSetting('path_to_collections') . $dacura_server->cid() ."/" . $dacura_server->getSystemSetting('files_directory'); 
	$pname = $x . $fname;
	if(!file_put_contents($pname, $payload)){
		return $dacura_server->write_http_error(400, "Failed to save file to $pname");
	}
	return $dacura_server->write_json_result($pname, "Temporary file $pname created");	
}
