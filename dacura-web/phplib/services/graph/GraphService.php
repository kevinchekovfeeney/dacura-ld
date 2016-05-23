<?php 
require_once("phplib/services/ld/LdService.php");
require_once("GraphDacuraServer.php");
class GraphService extends LdService {
	
	function loadParamsForViewScreen($id, &$params, &$dacura_server){
		parent::loadParamsForViewScreen($id, $params, $dacura_server);
		$params['dqsurl'] = $this->getSystemSetting('dqs_url');
		$params["view_page_options"] = json_encode(array("format" => "json", "options" => $this->getServiceSetting("view_page_options")));
		$params['default_dqs_tests'] = json_encode(RVO::getSchemaTests(false));
		$params['dqs_schema_tests'] = json_encode(RVO::getSchemaTests(true));
		$params['default_instance_dqs_tests'] = json_encode(RVO::getInstanceTests(false));
		$params['dqs_instance_tests'] = json_encode(RVO::getInstanceTests(true));
		$avs = $dacura_server->ontversions;
		$params['available_ontologies'] = json_encode($avs);
		$params['update_options'] = json_encode($this->getLDOptions("ldo_update"));
		$params['test_update_options'] = json_encode($this->getLDOptions("ldo_test_update"));
		return $params;
	}
	
	function getViewSubscreens(LdDacuraServer &$dacura_server, &$u){
		return array("ldo-history", "ldo-updates");
	}
	
	function loadParamsForCreateTab(&$params, &$dacura_server){
		parent::loadParamsForCreateTab($params, $dacura_server);
		$avs = $dacura_server->ontversions;
		$params['available_ontologies'] = json_encode($avs);
		return $params;
	}

}