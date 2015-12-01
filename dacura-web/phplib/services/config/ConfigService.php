<?php
/*
 * Config Service - provides access to updating / editing / viewing users and roles, etc.
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 30/01/2015
 * Licence: GPL v2
 */

include_once("ConfigDacuraServer.php");

class ConfigService extends DacuraService {
	var $protected_screens = array("view" => array("admin"));
	var $default_screen = "view";
	var $title = "Configuration Management";
	
	/*
	 * if collections = all -> list collections (SYSTEM)
	 * if datasets = all -> view collection
	 * else => view dataset
	 */

	function getScreenForAC(&$dacura_server){
		return "view";
	}
	
	
	function getScreenForCall(){
		if($this->getCollectionID() == "all"){
			return "system";
		}
		return "collection";
	}
	
	function getCollectionConfigVals(&$form){
		$cid = $this->getCollectionID();
		$form["collection_url"]['value'] = $this->getSystemSetting("install_url").$cid;
		$form["collection_path"]['value'] = $this->getSystemSetting("path_to_collections").$cid;
		$form[ "instance_idbase"]['value'] = $this->get_service_url("candidate");
		$form["graph_idbase"]['value'] = $this->get_service_url("schema");
		$form["dqs_url"]['value'] = $this->getSystemSetting("dqs_url");
	}

	function getParamsForScreen($screen, &$dacura_server){
		$params = array();
		$u = $dacura_server->getUser();
		$params['image'] = $this->url("image", "buttons/config.png");
		$params['dt'] = true;
		if($screen == "system"){
			$params['sysconfig_fields'] = $dacura_server->getSysconfigFields();//$this->dacura_forms["sys"];
			$params['service_tables'] = $dacura_server->getServiceConfigTables();//$this->dacura_forms["sys"];
			$params['create_collection_fields'] = $this->sform("ccf");
			$params['dacura_table_settings'] = $this->getDatatableSetting("collections");
			$params['log_table_settings'] = $this->getDatatableSetting("logs");
			$params["title"] = "Configuration Management Tool";
			$params["subtitle"] = "Manage the configuration of the collections on this server";				
			$params['subscreens'] = array("system-configuration", "list-collections", "add-collection", "view-logs");
		}
		else {
			$params["cid"] = $this->getCollectionID();
			$params["did"] = $this->getDatasetID();
			$params['create_dataset_fields'] =  $this->sform("cdf");
			$params['update_collection_fields'] =  $this->sform("ucf");
			$cdf = $this->sform("cdf");
			$this->getCollectionConfigVals($cdf);//$cdf['']
			$params['cconfig_fields'] =  array_values($cdf);
			//opr($params['cconfig_fields']);
			$params['settings_intro_msg'] = $this->smsg("settings_intro");
			$params['add_dataset_intro_msg'] = $this->smsg("dataset_intro");
			if($u->rolesSpanCollections()){
				$params['topbreadcrumb'] = "System Configuration";
				$params["breadcrumbs"] = array(array(), array());
			}
			$params['collectionbreadcrumb'] = "configuration";
			$col = $dacura_server->getCollection();
			$params["title"] = $col->name." settings";
			$params["subtitle"] = "Manage the settings of the ".$col->name . " collection";
			$params['subscreens'] = array("collection-settings", "colconfig");				
		}		
		return $params;
	}
			
}