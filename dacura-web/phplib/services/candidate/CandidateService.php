<?php 
require_once("phplib/services/ld/LdService.php");
require_once("CandidateDacuraServer.php");
/**
 * Candidate Service - describes candidate management service.
 * @package candidate
 * @author Chekov
 * @license: GPL v2
 */
class CandidateService extends LdService {
	/**
	 * Adds the frame javascsript to the page
	 * @see LdService::init()
	 */
	function init(){
		parent::init();
		$this->included_scripts[] = $this->get_service_script_url("dacura.frame.js");	
	}
	
	/**
	 * Extends function by updating various options from the ldo create form
	 * @see LdService::loadParamsForCreateTab()
	 */
	function loadParamsForCreateTab(&$params, &$dacura_server){
		parent::loadParamsForCreateTab($params, $dacura_server);
		if(isset($params['create_ldo_fields']['candtype'])){
			$choices = array();
			if($cands = $dacura_server->getValidCandidateTypes()){
				foreach($cands as $i => $cdata){
					$cid = $cdata['class'];
					if($compressed = $dacura_server->nsres->compress($cid)){
						if($compressed == "owl:Nothing") continue;
					}
					if(isset($cdata['label'])){
						$label = $cdata['label']['data'];							
					}
					else {
						$label = ($compressed) ? $compressed : $cdata['class'];				
					}
					$choices[$cid] = $label;
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