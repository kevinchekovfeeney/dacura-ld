<?php 
class ConsoleDacuraServer extends LdDacuraServer {

	function getConsoleParams(){
		$params = array();
		$u = $this->getUser();
		if($u){
			$params['logged_in'] = true;
		}
		else {
			$params['logged_in'] = false;
		}
		$params['homeurl'] = $this->service->my_url("rest")."/console";
		return $params;
	}
	
	function getConsoleScript(){
		ob_start();
		$files_to_load = $dacura_server->getServiceSetting('console_scripts', array());
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
}