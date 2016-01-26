<?php 
/**
 * Class representing a dacura settings object as an array of dacura form element initialisation arrays
 *
 * Creation Date: 25/01/2016
 * @author Chekov
 * @license GPL V2
 */

class ConfigForm extends DacuraObject {
	/** @var $sys the system level configuration object */
	var $sys;
	/** @var $col the collection level configuration object */
	var $col;
	
	/**
	 * Constructor: loads the current configuration objects from the passed server object
	 * @param DacuraServer $srvr the controlling dacura server object
	 */
	function __construct(DacuraServer &$srvr){
		$this->sys = $srvr->getCollection("all");
		if($srvr->cid() != "all"){
			$this->col = $srvr->getCollection($srvr->cid());	
		}
	}
	
	/**
	 * Current collection id
	 * @return string the current contextual collection id (all for platform level)
	 */
	function cid(){
		return ($this->col) ? $this->col->id : "all"; 
	}
	
	/**
	 * Generates an array of DacuraFormElement configurations corresponding with the passed settings 
	 * and field configuration objects 
	 * 
	 * @param array $settings an arbitrary json array - represents a setting for something
	 * @param array $sfields an array of fields indexed by ids (corresponding to setting names) each field 
	 * is the input values to a DacuraFormElement
	 * @return array list of DacuraFormElement initialisation arrays ready to be passed to drawInputTable function
	 */
	function generateFieldsFromSettings($settings, $sfields, $depth = 0, $sname = ""){
		$fields = array();
		if(!is_array($settings)){
			return $fields;
		}
		foreach($settings as $key => $v){
			if(isset($sfields[$key])){
				$field = $sfields[$key];
			}
			else {
				$field = array("label" => $key);
			}
			$field['id'] = $key;
			$field['value'] = $v;
			$field['depth'] = $depth;
			if(is_array($v) && (!isset($field['type']) || $field['type'] == "section")){
				if(!isset($field['type'])) $field['type'] = "section";
				$field['fields'] = $this->generateFieldsFromSettings($v, $sfields, $depth++, $sname);
			}
			$fields[] = $field;				
		}
		return $fields;
	}
	
	/**
	 * Generates an array of DacuraFormElement configurations corresponding with the passed system configuration settings
	 * 
	 * Adds meta data settings to platform level fields and removed hidden fields from input fields
	 *
	 * @param array $ds an arbitrary json array - represents a system configuration settings array
	 * @param array $sfields an array of fields indexed by ids (corresponding to setting names) each field
	 * is the input values to a DacuraFormElement
	 * @return array list of DacuraFormElement initialisation arrays ready to be passed to drawInputTable function
	 */
	function getSystemConfigFields($ds, $sfields = array()){
		$fields = $this->generateFieldsFromSettings($ds, $sfields);
		$nfields = array();
		foreach($fields as $onef){
			if($this->cid() != "all"){
				$fmeta = $this->sys->getConfig("meta");
				$nfield = $this->getConfigField($onef, $fmeta);
			}
			else {
				$nfield = $onef;
			}
			if($nfield && !isset($nfield['hidden'])){
				$nfields[] = $nfield;
			}
		}
		return $nfields;
	}
	
	/**
	 * Generates an array of DacuraFormElement configurations corresponding with the passed service configuration settings
	 *
	 * adds meta data settings to platform level fields and removed hidden fields from input fields
	 * also sets up special facets input type element
	 *
	 * @param string $sname the name of the service in question
	 * @param array $settings an arbitrary json array - represents the setting for a service 
	 * @param array $sfields an array of fields indexed by ids (corresponding to setting names) each field
	 * @return array list of DacuraFormElement initialisation arrays ready to be passed to drawInputTable function
	 */
	function getServiceConfigFields($sname, $settings, $sfields){
		if(!isset($settings['status'])){
			$settings['status'] = "enable";
		}
		if($this->cid() !== "all" && !isset($settings['facets'])){
			$settings['facets'] = array();
		}
		//if($sname == "config") opr ($sfields);
		$fields = $this->generateFieldsFromSettings($settings, $sfields, 0);
		$nfields = array();
		foreach($fields as $onef){
			if($this->cid() != "all"){
				$fmeta = $this->sys->getConfig("servicesmeta.".$sname);
				$nfield = $this->getConfigField($onef, $fmeta);
			}
			else {
				$nfield = $onef;
			}
			if(isset($nfield['id']) && $nfield['id']  == "facets"){
				$nfield['options'] = isset($settings['facet-list']) ? $settings['facet-list'] : array("view");
			}
			if($nfield && !isset($nfield['hidden'])){
				if($nfield['id'] == "status" || $nfield['id'] == "facets"){
					array_unshift($nfields, $nfield);
				}
				else {
					$nfields[] = $nfield;
				}
			}
		}
		return $nfields;
	}

	/**
	 * Generates a DacuraFormElement configuration corresponding with the passed field specification
	 * includes embedded fields in the field property
	 * 
	 * Sets various meta-data about fields: fixed, disabled, changeable, hidden
	 *
	 * @param string $onef the DacuraFormElement configuration initialisation setting
	 * @param array $fmeta an array of metadata about the fields - from system configuration settings
	 * @return array list of DacuraFormElement initialisation arrays ready to be passed to drawInputTable function
	 */
	function getConfigField($onef, $fmeta, $fixed = false){
		if($fixed) $onef['disabled'] = true;
		if(isset($fmeta[$onef['id']]) && isset($fmeta[$onef['id']]['changeable'])){
			if($fmeta[$onef['id']]['changeable'] == "hidden") {
				$onef['hidden'] = true;
			}
			elseif($fmeta[$onef['id']]['changeable'] == "fixed") {
				$onef['disabled'] = true;
				$fixed = true;
			}
		}
		if(isset($onef['type']) && $onef['type'] == "section"){
			$nfields = array();
			foreach($onef['fields'] as $i => $f){
				$oneres = $this->getConfigField($f, $fmeta, $fixed);
				if($oneres && !isset($oneres['hidden'])) $nfields[] = $oneres;
			}
			$onef['fields'] = $nfields;
			return $onef;
		}
		if(!isset($onef['type'])){
			$onef['type'] = "text";
		}
		return $onef;
	}	
}
