<?php 
require_once("phplib/services/ld/LdService.php");
require_once("GraphDacuraServer.php");
class GraphService extends LdService {
	
	function loadParamsForViewScreen($id, &$params, &$dacura_server){
		$params['dqsurl'] = $this->getSystemSetting('dqs_url');
		$params["view_page_options"] = json_encode(array("format" => "json", "options" => $this->getServiceSetting("view_page_options")));
		parent::loadParamsForViewScreen($id, $params, $dacura_server);
		return $params;
	}
}