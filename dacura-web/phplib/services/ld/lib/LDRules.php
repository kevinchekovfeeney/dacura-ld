<?php 
/**
 * Class representing the processing rules that apply to linked data objects 
 * 
 * Rules vary depending on mode: [create, view, update, replace, rollback, delete]
 * And action: [generate, update, import, validate]
 * where rules are used
 * 
 * "import" -> importLD() :  [demand_id_token, replace_blank_ids,	allow_demand_id]
 * 					-> addAnonSubject() -> getNewBNIDForLDObj() -> "generate"
 * 			
 * "validate" LDPropertyValue->legal() : [allow_invalid_ld, demand_id_token, require_object_literals, forbid_empty, expand_embedded_objects]
 * 			  LDO->validate(): [unique_subject_ids, allow_invalid_ld, forbid_blank_nodes, require_blank_nodes, forbid_unknown_prefixes, require_subject_urls, 
 * 						  allow_blanknode_predicates, forbid_unknown_prefixes, require_predicate_urls]
 *
 * "update" updateLDProps() : [fail_on_bad_delete, demand_id_token, replace_blank_ids]
 * 
 * "generate" LDO->updateLDProps() -> addAnonSubject() -> getNewBNIDForLDObj() [demand_id_token] 
 * 				-> genid() [mimimum_id_length, maximum_id_length, allow_demand_id, id_generator, extra_entropy]
 *				-> generateBNIDS() -> [cwurl, expand_embedded_objects, regularise_literals, regularise_object_literals]
 * 
 * 
 * Extensions for particular ldo types: 
 *	Ontology -> validateMeta [required_meta_properties, unavailable_urls] 
 *	Ontology -> generateDependencies() [collapse_blank_nodes]
 *	Candidate->validate() [require_candidate_type]
 *
 *
 * 
 * This is a place to gather together configuration logic about linked data processing
 * @author chekov
 *
 */
class LDRules extends DacuraObject {
	
	/* @var array of rules - just simple configuration objects */
	var $rules = array();
	/* @var string - the url of the object that owns the rules */
	var $cwurl;

	/**
	 * Initialises rules - called after object creation. 
	 * @param LDO $ldo
	 * @param LdDacuraServer $srvr
	 */
	function init(LDO $ldo, LdDacuraServer &$srvr){
		$this->cwurl = $ldo->cwurl;
		//id generation
		$this->rules['generate'] = array(
			//'id_generator' => array($ldo, $srvr->getServiceSetting("internal_generate_id", "generateInternalID")),
			"mimimum_id_length" => $srvr->getServiceSetting("internal_mimimum_id_length", 1),
			"maximum_id_length" => $srvr->getServiceSetting("internal_maximum_id_length", 80),
			"extra_entropy" => $srvr->getServiceSetting("internal_extra_entropy", false),
			"allow_demand_id" => $srvr->getServiceSetting("internal_allow_demand_id", true),
			"demand_id_token" => $srvr->getServiceSetting("demand_id_token", "@id")
		);
		//Validation rules for LDO->validate
		$this->rules['validate'] = array_merge($this->rules['generate'], array(
			//should embedded anonymous objects be expanded into embedded object lists with bn ids
			"expand_embedded_objects" => $srvr->getServiceSetting("expand_embedded_objects", true),
			//the set of meta properties that may be set in the ldo 
			'allowed_meta_properties' => $ldo->getValidMetaProperties(),
			//the set of properties that must be present in meta-data
			'required_meta_properties' => $srvr->getServiceSetting("required_meta_properties", false),
			//if set, plain literals: 'literal', 232, 43.2 are banned and {type: xsd:int, data: 12} are required 
			'require_object_literals' => $srvr->getServiceSetting("require_object_literals", true),
			//forbid empty arrays {} [] as property values
			'forbid_empty' => $srvr->getServiceSetting("forbid_empty", true),
			//check that ld subjects are valid urls
			"require_subject_urls" => $srvr->getServiceSetting("require_subject_urls", true),
			//check that ld predicates are valid urls
			"require_predicate_urls" => $srvr->getServiceSetting("require_predicate_urls", true),
			//check that ld predicates are not blank nodes
			"allow_blanknode_predicates" => $srvr->getServiceSetting("allow_blanknode_predicates", false),
			//forbid the usage of prefixed urls that are not prefixes of known ontologies
			"forbid_unknown_prefixes" => $srvr->getServiceSetting("forbid_unknown_prefixes", true),
			//forbid the usage of prefixed urls that are not prefixes of known ontologies
			"allow_invalid_ld" => $srvr->getServiceSetting("allow_invalid_ld", false),
			//require ld objects be expressed as { data: ..., type|lang: "" } dqs format
			"require_object_literals" => $srvr->getServiceSetting("require_object_literals", true),
			//suppress checks for meta-data validity..
			"allow_arbitrary_metadata" => $srvr->getServiceSetting("allow_arbitrary_metadata", false),
			//all internal embedded nodes must be blank nodes - can't have a non-blank node id inside an object
			"require_blank_nodes" => $srvr->getServiceSetting("require_blank_nodes", true),
			//forbid the presence of blank nodes within an object
			"forbid_blank_nodes" => $srvr->getServiceSetting("forbid_blank_nodes", false),
			//check to ensure that each subject id exists exactly once (can't appear as a subject in multiple places in embedded object)
			"unique_subject_ids" => $srvr->getServiceSetting("unique_subject_ids", true)
		));
		//rules that apply on ldo import from api
		$this->rules['import'] = array_merge($this->rules['generate'], array(
			//should the input structure be transform to make it all compliant 
			'transform_import' => $srvr->getServiceSetting("transform_import", true),
			//should the embedded objects be expanded with blank node ids
			"expand_embedded_objects" => $srvr->getServiceSetting("import_expand_embedded_objects", true),
			//should imported object literals be regularised for the dqs format 
			"regularise_object_literals" => $srvr->getServiceSetting("import_regularise_object_literals", true),
			//should simple literals be regularised into dqs object literals
			'regularise_literals' => $srvr->getServiceSetting("import_regularise_literals", true),
			//should pre-existing blank ids in the input data be replaced with dacura generated ids
			"replace_blank_ids" => $srvr->getServiceSetting("import_replace_blank_ids", true)
		));
		//rules that apply when an update is applied to a ldo
		$this->rules['update'] = array_merge($this->rules['generate'], array(
			"expand_embedded_objects" => $srvr->getServiceSetting("update_expand_embedded_objects", true),
			"regularise_object_literals" => $srvr->getServiceSetting("update_regularise_object_literals", false),
			'regularise_literals' => $srvr->getServiceSetting("update_regularise_literals", false),
			//if we encounter a delete of a non-existant fragment
			"fail_on_bad_delete" => $srvr->getServiceSetting("fail_on_bad_delete", true),
			"replace_blank_ids" => $srvr->getServiceSetting("update_replace_blank_ids", true)
		));
	}
	
	/**
	 * Specifies that a certain rule will produce a certain outcome for a particular action
	 * 
	 * This is the simple way in which particular types of ldo change the processing rules
	 * @param string $action the action taking place for the rule
	 * @param string $varname the name of the variable
	 * @param mixed $def the value to give the variable
	 */
	function setRule($action, $varname, $def){
		$this->rules[$action][$varname] = $def;
	}
	
	/**
	 * Returns the value of a rule for the given context - basic way in which rules are evaluated
	 * @param string $mode - replace, update, create, delete, view, rollback
	 * @param string $action - import, update, validate, 
	 * @param string $rname - the rule name
	 * @param string $obj - the type of object: [update|fragment]
	 * @return mixed - false if rule does not exist, otherwise the rule value
	 */
	function getRule($mode, $action, $rname, $obj = false){
		$pass = array();
		if($mode == "update"){
			$pass = array("forbid_empty", "required_meta_properties");
			if($action == 'import' && $rname == "replace_blank_ids" ){
				return false;
			} 
		}
		//general purpose over-rides for updates in view mode - they don't change anything
		if($mode == "view" && $action == "update"){
			$pass = array("expand_embedded_objects", "regularise_object_literals",'regularise_literals',
					"replace_blank_ids", "forbid_empty");
		}
		//general purpose over-rides for rollback mode
		if($mode == "rollback") {
			$pass = array("expand_embedded_objects", "regularise_object_literals",'regularise_literals', 
				"replace_blank_ids", "fail_on_bad_delete", "forbid_empty");
		}
		if(in_array($rname, $pass)){
			return false;
		}
		if(isset($this->rules[$action][$rname])){
			return $this->rules[$action][$rname];
		}
		//echo "<P>failed to find $mode -> $action -> $rname";
		//opr($this->rules);
		return false;
	}
	
	/**
	 * Returns an array of configuration setting rules to be passed to particular functions
	 * 
	 * Used by importLD(), LDPropertyValue->legal(), genid() 
	 * @param string $mode
	 * @param string $action
	 * @param string $obj
	 * @return array of rules
	 */
	function rulesFor($mode, $action, $obj = false){
		$fors = array("cwurl" => $this->cwurl);
		foreach($this->rules[$action] as $rname => $v){
			$fors[$rname] = $this->getRule($mode, $action, $rname, $obj);
		}
		return $fors;
	}	
}