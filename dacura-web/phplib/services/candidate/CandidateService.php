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
			$cands = $dacura_server->getValidCandidateTypes();
			$choices = array();
			foreach($cands as $cand){
				if($compressed = $dacura_server->nsres->compress($cand)){
					if($compressed == "owl:Nothing") continue;
					$choices[$cand] = $compressed;
				}
				else {
					$choices[$cand] = $cand;
				}
			}
			if($choices){
				$params['create_ldo_fields']['candtype']['options'] = $choices;
			}
			else {
				unset($params['create_ldo_fields']['candtype']);
				unset($params['create_ldo_fields']['imptype']);
			}
			
		}
		
	}
		
}