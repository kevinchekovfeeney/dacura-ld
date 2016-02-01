<?php
/**
 * Class representing a collection of data in the Dacura System
 * 
 * Collections are the basic unit of dacura administration. 
 * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL V2
 */

class Collection extends DacuraObject {
	/** @var string the human readable name of the collection */
	var $name;
	/** @var array an associative array containing the collections configuration variables */
	var $config;
	
	/**
	 * Create a collection object
	 * @param string $i collection id (alphanumeric_-)
	 * @param string $n collection name (human readable)
	 * @param array $c configuration name-value array
	 * @param string $s collection status
	 */
	function __construct($i, $n, $c, $s='pending'){
		$this->id = $i;
		$this->name = $n;
		$this->config = $c;
		$this->status = $s;
	}
	
	function getDefaultSettings(DacuraServer &$srvr){
		$cid = $srvr->cid();
		$settings = array(
			"collection_url" => $srvr->getSystemSetting("install_url").$cid."/",
			"collection_path" => $srvr->getSystemSetting("path_to_collections").$cid."/",
			"status" => $this->status,
			"name" => $this->name,
			"image" => $srvr->service->furl("images", "system/collection_bg.jpg"),
			"background" => $srvr->service->furl("images", "system/background.jpg"),
			"icon" => $srvr->service->furl("images", "system/candidate_icon.png"),
			"description" => ""
		);
		return $settings;
	}
	
	/**
	 * Fetch the value of a configuration variable
	 * @param string $var the name of the configuration variable whose value is required
	 * @return boolean|mixed the value of the configuration variable or false (with errcode set) if not existant
	 */
	function getConfig($varpath){
		$parts = explode(".", $varpath);
		$x = $this->config;
		foreach($parts as $p){
			if(!isset($x[$p])){
				return $this->failure_result("No variable $varpath found in $this->id collection configuration (search ended at $p)", 404);
			}
			$x = $x[$p];	
		}
		return $x;
	}
	
	/**
	 * Does the configuration have a variable with a particular path
	 * @param string $varpath '.' separated path through the json array structure
	 * @return boolean true if there is a variable at the varpath
	 */
	function hasConfig($varpath){
		$parts = explode(".", $varpath);
		$x = $this->config;
		foreach($parts as $p){
			if(!isset($x[$p])){
				return $this->failure_result("No variable $varpath found in $this->id collection configuration (search ended at $p)", 404);
			}
			$x = $x[$p];
		}
		return true;
	}

	/**
	 * Sets a variable, along a particular path ('.' separated) in a json array
	 * @param string $varpath '.' separated path through the json array structure
	 * @param mixed $varval the value to set on the variable. 
	 */
	function setConfig($varpath, $varval){
		$parts = explode(".", $varpath);
		$x =& $this->config;
		foreach($parts as $p){
			if(!isset($x[$p])){
				$x[$p] = array();
				$x =& $x[$p];
			}
		}
		$x = $varval;
	}
	
	/**
	 * Applies an update to the collection as specified in the passed update object
	 * 
	 * 
	 * @param array $updobj a name value array of configs to set
	 * @param string $target if present the update applies from this array offset
	 */
	function applyUpdate($updobj, $target = ""){
		$oname = false;
		$ostat = false;
		if($target == ""){
			foreach($updobj as $k => $v){
				if($k == "services"){
					foreach($v as $sid => $sobj){
						$this->config["services"][$sid] = $sobj;
					}	
				}
				elseif($k == "settings"){					
					if(isset($v['name'])){
						$this->name = $v['name'];
						unset($v['name']);
					}
					if(isset($updobj[$k]['status'])){
						$this->status = $updobj[$k]['status'];
						unset($v['status']);
					}
					if(isset($v['id'])){
						unset($v['id']);							
					}
					foreach($v as $id => $f){		
						$this->config["settings"][$id] = $f;
					}
				}				
				else {
					$this->config[$k] = $v;
				}
			}
		}
		else {
			$this->setConfig($target, $updobj);
		}		
	}
	
}

