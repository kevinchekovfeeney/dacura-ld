<?php 
require_once("phplib/services/ld/LdService.php");
require_once("OntologyDacuraServer.php");
class OntologyService extends LdService {
	
	function loadParamsForCreateTab(&$params, &$dacura_server){
		parent::loadParamsForCreateTab($params, $dacura_server);
		unset($params['create_ldo_fields']['ldtype']);
	}	

}