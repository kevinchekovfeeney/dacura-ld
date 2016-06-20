<?php 
require_once("phplib/services/ld/LdService.php");
require_once("GraphDacuraServer.php");
/**
 * Graph Service - provides named graph management services.
 *
 * @package graph
 * @author Chekov
 * @license: GPL v2
 */
class GraphService extends LdService {
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the view screen
	 * @see LdService::loadParamsForViewScreen()
	 */
	function loadParamsForViewScreen($id, &$params, LdDacuraServer &$dacura_server){
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
		$params['can_update'] = $dacura_server->userHasFacet("approve");
		$params['test_update_options'] = json_encode($this->getLDOptions("ldo_test_update"));
		return $params;
	}
	
	/**
	 * Fetches the list of subscreens of the view screen
	 * @see LdService::getViewSubscreens()
	 */
	function getViewSubscreens(LdDacuraServer &$dacura_server, &$u){
		return array("ldo-history", "ldo-updates");
	}
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the create tab
	 * @see LdService::loadParamsForCreateTab()
	 */
	function loadParamsForCreateTab(&$params, LdDacuraServer &$dacura_server){
		parent::loadParamsForCreateTab($params, $dacura_server);
		$avs = $dacura_server->ontversions;
		$params['available_ontologies'] = json_encode($avs);
		return $params;
	}
}