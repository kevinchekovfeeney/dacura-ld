<?php
require_once("ConfigForm.php");
require_once("phplib/UserRole.php");

/**
 * Config Server - provides access to updating / editing / viewing configurations of Dacura collections
 * 
 * Creation Date: 15/01/2015
 *
 * @package config
 * @author chekov
 * @license GPL V2
 */
class ConfigDacuraServer extends DacuraServer {
	
	/**
	 * Creates a new collection .
	 *
	 * @param string $id the requested collection id
	 * @param string $title the requested collection title
	 * @return string|boolean $id the id of the new collection if successful, false otherwise
	 */	
	function createNewCollection($id, $title){
		if(!$this->isValidCollectionID($id, $title)){
			return false;
		}
		$obj = $this->getServiceSetting("default_collection_config", "{}");
		$status = $this->getServiceSetting("default_status", "pending");
		if($this->dbman->createNewCollection($id, $title, $obj, $status)){
			if($this->createCollectionPaths($id)){
				$this->recordUserAction("create", array("collection" => $id));
				return $id;
			}
			else {
				return false;				
			}
		}
		else {
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
	}

	/**
	 * Creates the directories required by the collection when it is first created. 
	 * @param string $id the collection id
	 * @return boolean true on success
	 */
	function createCollectionPaths($id){
		$colbase = $this->getSystemSetting("path_to_collections", "").$id;
		if(file_exists($colbase)){
			return $this->failure_result("Collection directory $colbase for collection $id already exists", 400);				
		}
		if(!mkdir($colbase)){
			return $this->failure_result("Failed to create collection directory $colbase for collection $id", 500);
		}
		$paths_to_create = $this->getServiceSetting("collection_paths_to_create", array());
		foreach($paths_to_create as $p){
			if(!mkdir($colbase."/".$p)){
				return $this->failure_result("Failed to create collection directory $colbase/$p", 500);
			}
		}
		if(!$this->dbman->createCollectionInitialEntities($id)){
			return $this->failure_result("Failed to create collection default linked data entities", 500);				
		}
		//finally have to create main graph
		return true;
	}
	
	/**
	 * Used to check that the collection's id and password are valid.
	 *
	 * @param string $id the collection id
	 * @param string $title the collection title
	 * @return boolean - true if passed details are valid
	 */
	function isValidCollectionID($id, $title){
		if(!$this->isValidDacuraID($id)){
			return false;
		}
		elseif($this->dbman->hasCollection($id)){
			return $this->failure_result("$id is already taken. Two dacura collections cannot share the same ID.", 400);
		}
		return true;
	}
	
	/**
	 * Assembles the structure for sending the configuration information to the client
	 * @return array (settings, services, collection) = name value arrays with settings for each element
	 */
	function getCollectionConfig(){
		$resp['settings'] = $this->getSettings();
		$resp['services'] = $this->getServicesConfig();
		$resp['collection'] = $this->getCollection();
		return $resp;
	}
	
	/**
	 * Gets the current state of the system settings, 
	 * filtered to remove 'hidden' values so that only those settings that the user is permitted to view are included.
	 * @return array - a json array representing the current system configuration in this context
	 */
	function getSettings(){
		$s = $this->service->settings;
		unset($s['config']); //current service's configuration settings are in here
		if($this->cid() != "all"){
			$sys = $this->getCollection("all");
			$this->filterHiddenValues($s, $sys->getConfig("meta"));
		}
		return $s;
	}
	
	/**
	 * Gets the current state of the service settings for all services 
	 * filtered to remove 'hidden' values so that only those settings that the user is permitted to view are included.
	 * @return array - a json array representing the current services configurations in this context
	 */
	function getServicesConfig(){
		$services = $this->getServiceList();
		$configs = array();
		$sys = false;
		foreach($services as $s){
			$configs[$s] = $this->getServiceConfig($s);
			if(!isset($configs[$s]['status'])){
				$configs[$s]['status'] = "enable";
			}
			if(!isset($configs[$s]['facets'])){
				$configs[$s]['facets'] = array();
			}
			if($this->cid() != "all"){
				if($sys == false) $sys = $this->getCollection("all");
				$this->filterHiddenValues($configs[$s], $sys->getConfig("servicesmeta.".$s));
				if($sconf = $sys->getConfig('services.'.$s)){
					if(isset($sconf['status']) && $sconf['status'] == "disable"){
						if($this->userHasFacet("admin")){
							$configs[$s]['status'] = 'disable';
						}
						else {
							//if the user is not admin, blank out disabled services
							unset($configs[$s]);
						}
					}
				}
			}
		}
		return $configs;
	}

	/**
	 * Returns an array containing only system level variables (with their default values)
	 * 
	 * Used for distinguishing between collection and system level settings as they are all mixed up in the normal settings variable
	 * @return array the default system configuration settings array (arbitrary json shape)
	 */
	function getSystemLevelSettingsOnly(){
		include("phplib/settings.php");
		default_settings($dacura_settings);
		return $dacura_settings;
	}

	/**
	 * Returns the latest log entries as an array 
	 * @return array<logs> an array of logs
	 */
	function getLogsAsListingObject(){
		$this->recordUserAction("view logs");
		return $this->service->logger->lastRowsAsListingObjects();
	}
	
	/**
	 * Filters the current system settings to remove those that have been hidden at a system level
	 * and to remove system level settings from users who do not have the admin facet
	 *
	 * @param array $settings - the name-value settings array
	 * @param array $meta array of meta data about settings, indexed by settingid
	 */
	function filterHiddenValues(&$settings, $meta){
		$sysconf = $this->getSystemLevelSettingsOnly();
		$can_view_sysconf = $this->userHasFacet("admin") || $this->userHasFacet("inspect");
		foreach($settings as $sid => $set){
			if(isset($meta[$sid]['changeable']) && $meta[$sid]['changeable'] == 'hidden'){
				unset($settings[$sid]);
			}
			elseif(!$can_view_sysconf && isset($sysconf[$sid])){
				unset($settings[$sid]);
			}
			elseif(is_array($set)){
				$this->filterHiddenValues($settings[$sid], $meta);
			}
		}
	}
	
	/**
	 * Updates the configuration of the collection
	 * 
	 * The collection configuration is described in $obj['settings'] 
	 * while the configuration for each service is in $obj['services'][service_id]
	 * Updates can include settings and/or one or more services - only the included services are updated, the settings is updated
	 * all or nothing.
	 * 
	 * @param string $id the collection id
	 * @param array $obj json version of the configuration settings to be updated
	 * @return array $obj json version of the configuration 
	 */
	function updateCollectionConfig($id, $obj){
		if(!$col = $this->getCollection($id)){
			return false;
		}
		$upds = array();
		if(isset($obj["settings"])){
			$uset = $this->updateSettingsConfig($obj["settings"]);
			if(count($uset) > 0){
				$upds['settings'] = $uset;
			}
		}
		if(isset($obj["services"])){
			$usrv = $this->updateServicesConfig($obj["services"]);
			if(count($usrv) > 0){
				$upds['services'] = $usrv;
			}	
		}
		if(isset($obj['meta'])){
			$upds['meta'] = $obj['meta'];
		}
		if(isset($obj['servicesmeta'])){
			$upds['servicesmeta'] = $obj['servicesmeta'];
		}
		if(count($upds) == 0){
			return $this->failure_result("Submitted update request did not contain any changes to the configuration", 400);
		}
		if(!$this->containsOnlyPermittedUpdates($upds)){
			return false;
		}
		$col->applyUpdate($upds);
		if($ucol = $this->dbman->updateCollection($col->id, $col->name, $col->status, $col->config)){
			$this->loaded_configs[$id] = $ucol;//refresh cache				
			$resobj = array("collection" => $ucol);
			if(isset($upds['services'])){
				$resobj['services'] = $this->getServicesConfig(); 
			}
			if(isset($upds['settings'])){
				$resobj['settings'] = $this->getUpdatedSettings();
			}
			$this->recordUserAction("update configuration");				
			return $resobj;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);				
	}
	
	/**
	 * Checks to see whether there are any illegal updates in the object
	 * 
	 * Checks for the following illegal updates:
	 * * config service cannot be disabled
	 * * only platform admin can change /all settings and status of collections
	 * * manage facet is required for all updates
	 * * admin facet is required for overwrites to system settings 
	 * (this is a bit arbitrary, probably not the best way to divide stuff up, but it'll do for the moment)
	 * @param array $upds the update object as filtered by locked and hidden fields....
	 * @return boolean true if there are no illegal updates
	 */
	function containsOnlyPermittedUpdates($upds){
		//config service cannot be disabled
		if(isset($upds['services']['config']) && isset($upds['services']['config']) 
				&& isset($upds['services']['config']['status']) && $upds['services']['config']['status'] != "enable"){
			return $this->failure_result("Configuration service cannot be disabled.", 400);
		}
		//only platform admin can change /all settings and status of collections
		if($this->cid() == "all" || isset($upds['settings']['status'])){
			$u = $this->getUser();
			if(!$u or !$u->isPlatformAdmin()) return $this->failure_result("Only platform administrators are permitted to carry out that update", 401);
		}
		//manage facet is required 
		if(!$this->userHasFacet("manage")){
			return $this->failure_result("Manage facet is required at a minimum to update collection configuration settings", 401);
		}	
		//admin facet is required for system settings.
		if(!$this->userHasFacet("admin") && isset($upds['settings'])){
			foreach($upds['settings'] as $sid => $sval){
				if($this->getSystemSetting($sid)){
					return $this->failure_result("Manage facet is required at a minimum to update collection configuration setting $sid", 401);						
				}
			}
		}
		return true;
	}

	/**
	 * Filters the settings aspect of the configuration to only include the actual bits that are being updated
	 * @param array $sets a settings array
	 * @return a settings array, filtered to remove all but the updates
	 */
	function updateSettingsConfig($sets){
		$usets = array();
		foreach($sets as $sid => $v){
			$current = $this->getSystemSetting($sid);
			if(is_array($v) && is_array($current) && isAssoc($v) && count($v) > 0 && isAssoc($current)){
				if(!arrayRecursiveCompare($v, $current)){
					$usets[$sid] = $v;
				}
			}
			elseif(is_array($v) && is_array($current)){
				if($v != $current){
					$usets[$sid] = $v;
				}
			}
			elseif(!is_array($v) && !is_array($current)){
				if($current != $v){
					$usets[$sid] = $v;
				}
			}
			else {
				$usets[$sid] = $v;
			}
		}
		if(isset($col->config['settings'])){
			foreach($col->config['settings'] as $sid => $csetting){
				if(!isset($usets[$sid])){
					$usets[$sid] = $csetting;
				}
			}
		}
		return $usets;
	}

	/**
	 * Filters the services configurations to only include the actual bits that are being updated
	 * @param array $sets a services settings array including the data to be updated (services that are not included are ignored)
	 * @return a settings array, filtered to remove all but the updates
	 */
	function updateServicesConfig($srvcs){
		$usets = array();
		foreach($srvcs as $sid => $ssets){
			$current = $this->getServiceConfig($sid);
			$usets[$sid] = array();
			foreach($ssets as $k => $v){
				if(!isset($current[$k])){
					$usets[$sid][$k] = $v;
				}
				else {
					$cval = $current[$k];
					if(is_array($v) && is_array($cval) && isAssoc($v) && count($v) > 0 && isAssoc($cval)){
						if(!arrayRecursiveCompare($v, $cval)){
							$usets[$sid][$k] = $v;
						}
					}
					elseif(is_array($v) && is_array($cval)){
						if($v != $cval){
							$usets[$sid][$k] = $v;
						}
					}
					elseif(!is_array($v) && !is_array($cval)){
						if($cval != $v){
							$usets[$sid][$k] = $v;
						}
					}
					else {
						$usets[$sid][$k] = $v;
					}
				}
			}
			if(isset($col->config['services'][$sid])){
				foreach($col->config['services'][$sid] as $cid => $csetting){
					if(!isset($usets[$sid][$cid])){
						$usets[$sid][$cid] = $csetting;
					}
				}
			}
		}
		return $usets;	
	}
	
	/**
	 * Gets the state of the settings for reporting back immediately after a successful update
	 * The new settings won't take hold until the next call, so we have to load them from file.
	 * @return array a settings array representing the updated state of the settings
	 */
	function getUpdatedSettings(){
		include("phplib/settings.php");
		$this->service->loadContextSettings($dacura_settings, $this);
		if($this->cid() != "all"){
			$sys = $this->getCollection("all");
			$this->filterHiddenValues($dacura_settings, $sys->getConfig("meta"));
		}
		return $dacura_settings;
	}
	
	/**
	 * Deletes the collection from the system
	 * @param string $id the collection id
	 * @return boolean if true, the deletion worked
	 */
	function deleteCollection($id){
		if($this->dbman->deleteCollection($id, true)){
			$this->recordUserAction("delete");				
			return true;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/**
	 * Retrieves the system configuration as an array of DacuraFormElement object initialisation arrays 
	 * 
	 * Uses the passed form element tempaltes where available, otherwise it generates a new form element
	 * This means that whenever a new setting is added, it automatically shows up on the form (albeit in an ugly way)
	 * @param array $sfields the form element templates indexed by arrays 
	 */
	function getSysConfigFields($sfields = array()){
		$ds = $this->service->settings;
		unset($ds['config']); //current service's configuration settings are in here
		$cf = new ConfigForm($this);
		return $cf->getSystemConfigFields($ds, $sfields);		
	}
	

	/**
	 * Retrieves an array of all services configurations each being an array of DacuraFormElement object initialisation arrays
	 *
	 * This means that whenever a new setting is added, it automatically shows up on the form (albeit in an ugly way)
	 * @param array $sfields the form element templates indexed by arrays
	 */	
	function getServicesConfigFields($sfields = array()){
		$sconfigs = array();
		$services = $this->getServiceList();
		foreach($services as $s){
			$sconfigs[$s] = $this->getServiceConfigFields($s, isset($sfields[$s]) ? $sfields[$s] : array());
		}
		return $sconfigs;
	}
	
	
	/**
	 * Retrieves a service's configuration as an array of DacuraFormElement object initialisation arrays
	 *
	 * Uses the passed form element templates where available, otherwise it uses the standard elements defined in the services
	 * where available and where not it generates a new form element
	 * This means that whenever a new setting is added, it automatically shows up on the form (albeit in an ugly way)
	 * @param array $sfields the form element templates indexed by arrays
	 */
	function getServiceConfigFields($s, $sfields = array()){
		$configs = $this->getServiceConfig($s);
		if(isset($configs['config_form_fields'])){
			$nsfields = array_merge($sfields, $configs['config_form_fields']);
		}
		else {
			$nsfields = $sfields;
		}
		$cf = new ConfigForm($this);
		return $cf->getServiceConfigFields($s, $configs, $nsfields);
	}
}

