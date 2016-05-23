<?php 
require_once("phplib/services/ld/LdService.php");
require_once("OntologyDacuraServer.php");
class OntologyService extends LdService {

	/**
	 * What subscreens should we load for the view screen? - adds the analysis subscreen
	 * @return array<string> the ids of the subscreens (tabs) to load
	 */
	function getViewSubscreens(LdDacuraServer &$dacura_server, &$u){
		$x = parent::getViewSubscreens($dacura_server, $u);
		$x[] = "ldo-analysis";
		return $x;
	}
	
	/**
	 * Loads the necessary parameters from php -> html / js for drawing the analysis subscreen
	 * @param string $id the id of the ld object
	 * @param array $params the parameters to be interpolated into the analysis html subscreen
	 * @param LdDacuraServer $dacura_server
	 */
	function loadParamsForAnalysisTab($id, &$params, LdDacuraServer &$dacura_server){
		$params['analysis_screen_title'] = $this->smsg('analysis_screen_title');
		$params['analysis_intro_msg'] = $this->smsg('view_analysis_intro');
		$avs = $dacura_server->ontversions;
		//an ontology is not available to itself...
		if(isset($avs[$id])) unset($avs[$id]);
		$params['available_ontologies'] = json_encode($avs);
		$params['default_dqs_tests'] = json_encode(RVO::getSchemaTests(false));		
		$params['dqs_schema_tests'] = json_encode(RVO::getSchemaTests(true));
		$params['update_dqs_options'] = json_encode($this->getLDOptions("update_dqs"));
		$params['test_update_dqs_options'] = json_encode($this->getLDOptions("test_update_dqs"));
	}
}