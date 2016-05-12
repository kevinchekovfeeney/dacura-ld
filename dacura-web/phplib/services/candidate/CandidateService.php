<?php 
require_once("phplib/services/ld/LdService.php");
require_once("CandidateDacuraServer.php");
class CandidateService extends LdService {
	
	function init(){
		parent::init();
		$this->included_scripts[] = $this->get_service_script_url("dacura.frame.js");	
	}
	
	function loadParamsForCreateTab(&$params, &$dacura_server){
		parent::loadParamsForCreateTab($params, $dacura_server);
		if(isset($params['create_ldo_fields']['candtype'])){
			$params['create_ldo_fields']['candtype']['options'] = $dacura_server->getValidCandidateTypes();
			if(!$params['create_ldo_fields']['candtype']['options'] ){
				$params['create_ldo_fields']['candtype']['options'] = array("owl:Nothing" => "None");
			}
		}
		
	}
	
	function getViewSubscreens(){
		$x = parent::getViewSubscreens();
		$x[] = "ldo-frame"; 
		return $x;
	}
	
}