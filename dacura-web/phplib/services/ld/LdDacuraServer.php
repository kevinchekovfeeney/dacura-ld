<?php
include_once("lib/PolicyEngine.php");
include_once("lib/LDOUpdate.php");
include_once("lib/LDO.php");
include_once("lib/Schema.php");
include_once("lib/DacuraResult.php");
include_once("lib/GraphManager.php");
require_once("lib/NSResolver.php");
include_once("lib/Ontology.php");
require_once("lib/RVO.php");
include_once("lib/Graph.php");
include_once("lib/Candidate.php");
require_once("LdService.php");
include_once("LDDBManager.php");
/**
* This class implements the basic processing pipeline of dacura linked data objects
* It does all the linked data bits - and implements hooks for derived classes to implement interactions with graphs / reasoning
* Particular ldo types can override whichever parts they want
* It provides defered updates and version management / linked data conformance
* 
* @author Chekov
* @license GPL V2
*/
class LdDacuraServer extends DacuraServer {
	/** @var string the class name of the database manager in operation */
	var $dbclass = "LDDBManager";
	/** @var PolicyEngine policy engine to decide what to do with incoming requests*/
	var $policy; 
	/** @var GraphManager object for interfacing with DQS graph manager services */
	var $graphman; 
	/** @var NSResolver for resolving namespaces the namespace resolver object */
	var $nsres; 
	/** @var the base url from which closed world urls are composed (by adding object id/fragment id... */
	var $cwurlbase = false;
	/** @var the base url from which graph ids are composed (by adding /graphid_schema etc */
	var $graphbase = false;
	var $graphs = array();
	

	/**
	 * Constructor creates helper controller classes - for policy engine and graph connections
	 * @param DacuraService $service - the dacura service that is creating this controller
	 */
	function __construct($service){
		parent::__construct($service);
		$this->policy = new PolicyEngine($this->service);
		$this->graphman = new GraphManager($this->service);
	}
	
	/**
	 * Initialises the server - called by api - used to do any post constructor initialisation
	 * (non-PHPdoc)
	 * @see DacuraServer::init()
	 */
	function init($action = false, $object = ""){
		$this->loadNamespaces();
		return parent::init($action, $object);
	}

	/**
	 * The ontologies / namespace prefixes available in any context are the universal ontologies (with status = accept) 
	 * and all the ontologies in the collection
	 * 
	 * This function loads the appropriate ontologies into the servers namespace resolver object
	 */
	function loadNamespaces(){
		$universal_onts = array(
			"type" => "ontology",
			"collectionid" => "all",
		);
		$onts = $this->getLDOs($universal_onts);
		if($this->cid() != "all"){
			$local_onts = array(
				"type" => "ontology",
				"collectionid" => $this->cid()
			);
			$onts = array_merge($onts, $this->getLDOs($local_onts));
		}
		$this->nsres = new NSResolver($this->service);
		foreach($onts as $i => $ont){
			if(isset($ont['id']) && $ont['id'] && isset($ont['meta']['url']) && $ont['meta']['url']){
				$this->nsres->prefixes[$ont['id']] = $ont['meta']['url'];
			}
		}
	}
	

	/**
	 * Create a new instance of a LDO type
	 * @param string $type the type of the object
	 * @param unknown $create_obj the initial skeleton object to be created
	 * @param unknown $demand_id the id that the client is requesting
	 * @param unknown $format the format of the input
	 * @param unknown $options options for creation
	 * @param boolean $test_flag if true do not actually create the object, just simulate it
	 * @return CreateResult
	 */
	function createLDO($type, $create_obj, $demand_id, &$format, $options, $test_flag = false){
		$cr = new DacuraResult("Creating $type", $test_flag);
		if($format && !isset(LDO::$valid_input_formats[$format])){
			return $cr->failure(400, "Invalid format: $format is not a supported input format");				
		}
		$id = $this->getNewLDOLocalID($demand_id, $type);
		if($demand_id && $demand_id != $id){
			if(isset($options['fail_on_id_denied']) && $options['fail_on_id_denied']){
				return $cr->failure(400, "Failed to allocate requested id ".htmlspecialchars($demand_id)." ".$this->errmsg);
			}
			$this->addIDAllocationWarning($cr, $type, $test_flag, $id);
		}		
		if(!($nldo = $this->createNewLDObject($id, $type))){
			return $cr->failure($this->errcode, "Request Create Error", "Failed to create $type object ".$this->errmsg);
		}
		$nldo->setContext($this->cid());
		$nldo->setNamespaces($this->nsres);
		$rules = $this->getNewLDOContentRules($nldo);
		if(!($format = $nldo->loadNewObjectFromAPI($create_obj, $format, $options, $rules, $this))){
			return $cr->failure($nldo->errcode, "Protocol Error", "New $type object sent to API had formatting errors. ".$nldo->errmsg);
		}
		$cr->add($this->policy->getPolicyDecision("create", $nldo));
		if($cr->is_reject()){
			$nldo->status($cr->status());
			if($this->policy->storeRejected("create", $nldo) && !$test_flag){
				if(!$this->dbman->createLDO($nldo, $type)){
					$cr->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected create of $type.", $this->dbman->errmsg);
				}
			}
			return $cr;
		}
		if(!$cr->is_accept()){
			if(!$this->getServiceSetting("test_unpublished", true)){
				if(isset($options['show_result']) && $options['show_result']){
					if($options['show_result'] == 1){
						$cr->set_result($nldo);
					}
					else {
						$cr->set_result($nldo->cwurl);				
					}
				}
				return $cr->set_result($nldo);
			}
		}
		$dont_publish = !$cr->is_accept() || $test_flag;
		$gur = $this->objectPublished($nldo, $dont_publish);
		if($cr->is_accept() && $gur->is_reject() && $this->getServiceSetting("rollback_new_to_pending_on_dqs_reject", true)){
			$cr->setWarning("Publication", "Rejected by Graph Management Service", "State changed from accept to pending");
			$cr->status("pending");
			$nldo->status($cr->status());
			$cr->addGraphResult("dqs", $gur, true);				
		}
		else {
			$nldo->status($cr->status());				
			$cr->addGraphResult("dqs", $gur, $dont_publish);				
		}
		if(isset($options['show_ld_triples']) && $options['show_ld_triples']){
			$cr->createGraphResult("ld", $cr->status(), $nldo->triples(), array(), $test_flag, $dont_publish);
		}
		if(!$test_flag && !$this->dbman->createLDO($nldo, $type)){
			$disaster = new DacuraResult("Database Synchronisation");
			$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to create database candidate record ". $this->dbman->errmsg);
			$cr->add($disaster);
			if($cr->includesGraphChanges("dqs")){
				$recovery = $this->objectDeleted($nldo);
				$cr->undoGraphResult($recovery);
			}
		}
		if(isset($options['show_result']) && $options['show_result']){
			if($options['show_result'] == 1){
				$cr->set_result($nldo);
			}
			else {
				$cr->set_result($nldo->cwurl);				
			}
		}
		return $cr;
	}
	
	/**
	 * Adds a warning when a demand id fails for an ld object 
	 * @param DacuraResult $ar - the dacura result object the warning is being added to
	 * @param string $type - linked data type being created
	 * @param boolean $test_flag - if true it is only a test create
	 * @param string $id - the randomly generated Id that will be used. 
	 */
	function addIDAllocationWarning(&$ar, $type, $test_flag, $id){
		$txt = "Requested ID could not be granted (".$this->errmsg.").";
		$extra = "";
		if($test_flag){
			$txt = "An ID will be randomly generated when the $type is created.";
			$extra = "$id is an example of a randomly generated ID, it will be replaced by another if the $type is created";
		}
		else {
			$txt = "The $type was allocated a randomly generated ID: $id";
		}
		$ar->addWarning("Generating id", $txt, $extra);
	}
	
	/** 
	 * Called to create a php object of the appropriate type
	 * @param string $id the object's id
	 * @param string $type the object' type
	 * @return an instance of the object 
	 */
	function createNewLDObject($id, $type){
		$cwbase = $this->service->get_service_url($type);
		$uclass = ucfirst($type);
		if(class_exists($uclass)){
			$ldo = new $uclass($id, $cwbase, $this->service->logger);
		}
		else {
			$ldo = new LDO($id, $cwbase, $this->service->logger);
		}
		$ldo->ldtype = $type;
		return $ldo;
	}
		
	/**
	 * Generates a new id for a new ld object
	 * @param string $demand_id the requested id 
	 * @param string $type the type of the ld object
	 * @return string the id of the new object
	 */
	function getNewLDOLocalID($demand_id, $type){
		if($demand_id && $this->dbman->hasLDO($demand_id, $type, $this->cid())){
			return $this->failure_result("$type object with ID $demand_id exists already in the dataset", 400);
		}
		elseif($demand_id && $this->dbman->errcode){
			return $this->failure_result("Failed to check for duplicate ID ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		$rules = $this->getNewLDORules();
		return genid($demand_id, $rules);
	}
	
	/**
	 * Default rules for processing a new linked data object
	 * @return array<rules> an array of settings to be passed to linked data processing
	 */
	function getNewLDORules(){
		$x = array();
		$x["allow_demand_id"] = $this->getServiceSetting("ldo_allow_demand_id", true); 
		$x["mimimum_id_length"] = $this->getServiceSetting("ldo_mimimum_id_length", 1); 
		$x["maximum_id_length"] = $this->getServiceSetting("ldo_mimimum_id_length", 80); 
		$x["extra_entropy"] = $this->getServiceSetting("ldo_extra_entropy", false); 
		return $x;
	}
	
	/**
	 * Default rules for processing content of new linked data objects
	 * @param $nldo LDO - the linked data object being created
	 * @return array<rules> an array of settings to be passed to linked data processing
	 */
	function getNewLDOContentRules($nldo){
		$x = array();
		$x['cwurl'] = $nldo->cwurl;
		$x['id_generator'] = array($nldo, $this->getServiceSetting("internal_generate_id", "generateInternalID"));
		$x["allow_demand_id"] = $this->getServiceSetting("internal_allow_demand_id", true); 
		$x["mimimum_id_length"] = $this->getServiceSetting("internal_mimimum_id_length", 1); 
		$x["maximum_id_length"] = $this->getServiceSetting("internal_mimimum_id_length", 80); 
		$x["extra_entropy"] = $this->getServiceSetting("internal_extra_entropy", false); 
		$x["expand_embedded_objects"] = $this->getServiceSetting("expand_embedded_objects", true); 
		$x["replace_blank_ids"] = $this->getServiceSetting("replace_blank_ids", true); 
		$x["require_blank_nodes"] = $this->getServiceSetting("require_blank_nodes", false); 
		$x["forbid_blank_nodes"] = $this->getServiceSetting("forbid_blank_nodes", false); 
		$x["allow_blanknode_predicates"] = $this->getServiceSetting("allow_blanknode_predicates", true); 
		$x["require_subject_urls"] = $this->getServiceSetting("require_subject_urls", true); 
		$x["require_predicate_urls"] = $this->getServiceSetting("require_predicate_urls", true); 
		$x["forbid_unknown_prefixes"] = $this->getServiceSetting("forbid_unknown_prefixes", true); 
		$x["unique_subject_ids"] = $this->getServiceSetting("unique_subject_ids", false); 
		$x["allow_invalid_ld"] = $this->getServiceSetting("allow_invalid_ld", false); 
		$x["require_object_literals"] = $this->getServiceSetting("require_object_literals", true); 
		$x["regularise_object_literals"] = $this->getServiceSetting("regularise_object_literals", true); 
		$x["forbid_empty"] = $this->getServiceSetting("forbid_empty", true); 
		$x["allow_arbitrary_metadata"] = $this->getServiceSetting("allow_arbitrary_metadata", false); 
		return $x;
	}
	
	/**
	 * Return a list of linked data objects 
	 * @param array $filter a filter on the objects
	 * @return boolean|array the linked data objects in an array
	 */
	function getLDOs($filter, $options = array()){
		if(isset($options['include_all'])){
			$filter['include_all'] = $options['include_all'];
		}
		if(isset($options['status'])){
			$filter['status'] = $options['status'];
		}
		if(isset($options['version'])){
			$filter['version'] = $options['version'];
		}
		$data = $this->dbman->loadLDOList($filter);
		if(!is_array($data)){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $data;
	}
	
	/**
	 * Return a list of updates to linked data objects 
	 * @param array $filter a filter on the objects
	 * @return boolean|array the linked data objects in an array
	 */
	 function getUpdates($filter, $options = array()){
	 	if(isset($options['include_all'])){
	 		$filter['include_all'] = $options['include_all'];
	 	}
	 	if(isset($options['status'])){
	 		$filter['status'] = $options['status'];
	 	}
	 	if(isset($options['to_version'])){
	 		$filter['to_version'] = $options['to_version'];
	 	}
	 	if(isset($options['from_version'])){
	 		$filter['from_version'] = $options['from_version'];
	 	}
 	 	if(isset($options['targetid'])){
	 		$filter['targetid'] = $options['targetid'];
	 	}
	 	$data = $this->dbman->loadUpdatesList($filter);
	 	if(is_array($data)){
			return $data;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/**
	/*
	 * the getLDO version returns a LD object wrapped in an AR object to support direct API access
	 * also loads things like history and updates -> for UI
	 * @param string $ldo_id object id
	 * @param string $type object type
	 * @param string $fragment_id node id within the object being sought
	 * @param integer $version the version of the object
	 * @param array $options 
	 * @return DacuraResult
	 */
	function getLDO($ldo_id, $type, $fragment_id = false, $version = false, $options = array()){
		$action = "Fetching " . ($fragment_id ? "fragment $fragment_id from " : "");
		$this->update_type = $type;
		$action .= "$type $ldo_id". ($version ? " version $version" : "");
		$dr = new DacuraResult($action);
		$ldo = $this->loadLDO($ldo_id, $type, $this->cid(), $fragment_id, $version, $options);
		if(!$ldo){
			return $dr->failure($this->errcode, "Error loading $type $ldo_id", $this->errmsg);
		}
		$dr->add($this->policy->getPolicyDecision("view", $ldo));
		if($dr->is_accept()){
			$dr->set_result($ldo);
		}
		return $dr;
	}
	
	/**
	 * retrieves a linked data object from the database
	 * 
	 * the load ldo version returns the normal dacura error codes...
	 * 
	 * @param string $ldo_id the id of the object
	 * @param string $type the object ld type
	 * @param string $cid collection id of object owner
	 * @param string $fragment_id internal node id wanted instead of whole object
	 * @param integer $version version required
	 * @param array $options options 
	 * @return boolean|LDO - on success this will return the loaded linked data object
	 */
	function loadLDO($ldo_id, $type, $cid, $fragment_id = false, $version = false, $options = array()){
		if(!($ldo = $this->createNewLDObject($ldo_id, $type))){
			return false;
		}
		if(!$this->dbman->loadLDO($ldo, $ldo_id, $type, $cid, $options)){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		$ldo->deserialise($this);
		if($options && isset($options['history']) && $options['history']){
			$ldo->history = $this->loadHistoricalRecord($ldo);
		}
		if($options && isset($options['updates']) && $options['updates']){
			$updopts = array("include_all" => true, 'type' => $type, "collectionid" => $this->cid(), "targetid" => $ldo_id);
			$ldo->updates = $this->getUpdates($updopts);
		}
		$ldo->nsres = $this->nsres; 
		if($version && $ldo->version() > $version){
			if(!$this->rollBackLDO($ldo, $version)){
				return false;
			}
		}
		if($fragment_id && !$ldo->loadFragment($fragment_id)){
			return $this->failure_result("Failed to load fragment ".htmlspecialchars($fragment_id). " " .$ldo->errmsg, $ldo->errcode);
		}
		if($options && isset($options['analysis']) && $options['analysis']){
			$ldo->analyse();
		}		
		return $ldo;
	}
	
	/**
	 * Loads an array of historical records about the LDO detailing its previous changes
	 * 
	 * @param unknown $oldo the ldo under investigation
	 * @param number $from_version what version are we interested in history from
	 * @param number $to_version what version are we interested in history to
	 * @return boolean|array 
	 */
	function loadHistoricalRecord($oldo, $from_version = 0, $to_version = 1){
		$ldo = clone $oldo;
		$histrecord = array(array(
				'status' => $ldo->status,
				'version' => $ldo->version,
				'version_replaced' => 0
		));
		$history = $this->getLDOHistory($ldo, $to_version);
		foreach($history as $i => $old){
			$histrecord[count($histrecord) -1]['created_by'] = $old['eurid'];
			$histrecord[count($histrecord) -1]['forward'] = $old['forward'];
			$histrecord[count($histrecord) -1]['backward'] = $old['backward'];
			$back_command = json_decode($old['backward'], true);
			if(!$ldo->update($back_command, true)){
				return $this->failure_result($ldo->errmsg, $ldo->errcode);
			}
			$histrecord[count($histrecord) -1]['createtime'] = $old['modtime'];
			$histrecord[] = array(
					'status' => isset($ldo->meta['status']) ? $ldo->meta['status'] : $ldo->status,
					"version" => $old['from_version'],
					"version_replaced" => $old['modtime']
			);
		}
		$histrecord[count($histrecord) -1]['forward'] = json_encode($ldo->ldprops);
		$histrecord[count($histrecord) -1]['backward'] = json_encode(array());
		$histrecord[count($histrecord) -1]['createtime'] = $ldo->created;
		$histrecord[count($histrecord) -1]['created_by'] = 0;
		//$histrecord[]
		return $histrecord;
	}
	
	/**
	 * Rolls an ldo back to some particular version
	 * @param LDO $ldo linked data object to be rolled back
	 * @param integer $version the version to which it will be rolled back
	 * @return boolean|LDO - updated ldo is returned, or false on failure
	 */
	function rollBackLDO(&$ldo, $version){
		$history = $this->getLDOHistory($ldo, $version);
		foreach($history as $i => $old){
			if($old['from_version'] < $version){
				continue;
			}
			$back_command = json_decode($old['backward'], true);
			if(!$ldo->update($back_command, true)){
				return $this->failure_result($ldo->errmsg, $ldo->errcode);
			}
			$ldo->status = isset($ldo->meta['status']) ? $ldo->meta['status'] : $ldo->status;
			$ldo->version = $old['from_version'];
			$ldo->version_created = $old['modtime'];
			if($i == 0){
				$ldo->version_replaced = $ldo->modified;
			}
			else {
				$ldo->version_replaced = $history[$i-1]['modtime'];
			}
		}
		return $ldo;
	}
	
	/**
	 * Returns a list of all the updates to an ldo that have been accepted, organised in order of last to first...
	 * @param LDO $ldo linked data object in question
	 * @param integer $version the version to go back as far as 
	 * @return boolean|array history array, with one update per entry
	 */
	function getLDOHistory(LDO $ldo, $version){
		$history = $this->dbman->loadLDOUpdateHistory($ldo, $version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $history;
	}
	
	/* Same pattern applies for retreiving updates */
	
	/**
	 * Returns a representation of a particular update to a ldo
	 * @param string $id update id
	 * @param array $options options array
	 * @return DacuraResult with the result containing the update representation 
	 */
	function getUpdate($id, $options = array()){
		$ar = new DacuraResult("Loading Update Request $id from DB", false);
		$ur = $this->loadUpdate($id, $options);
		if(!$ur){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view update", $ur));
		if($ar->is_accept()){
			$ar->set_result($ur);
		}
		return $ar;
	}	
	
	
	
	/**
	 * Loads update from database and applies it to the required ldo version 
	 * @param string $id update id
	 * @param array $options
	 * @param integer $vfrom the version of the object to apply the update to (default is current version)
	 * @param integer $vto the version of the object that the update created in the db (for accepted updates)
	 * @return boolean|LDOUpdate the ldo update object describing the update
	 */
	function loadUpdate($id, $options = array(), $vfrom = false, $vto = false){
		$eur = $this->dbman->loadLDOUpdateRequest($id, $options);
		$vto = $vto ? $vto : $eur->to_version();
		$vfrom = $vfrom ? $vfrom : $eur->from_version();
		$orig = $this->loadLDO($eur->targetid, $eur->type, $eur->cid, $eur->did, false, $vfrom, $options);
		if(!$orig){
			return $this->failure_result("Failed to load Update $id - could not load original " .$this->errmsg, $this->errcode);
		}
		$eur->setOriginal($orig);
		$changed = false;
		if($vto > 0){
			$changed = $this->loadLDO($eur->targetid, $eur->type, $eur->cid, $eur->did, false, $vto, $options);
			if(!$changed){
				return $this->failure_result("Loading of $this->ldo_type update $id failed - could not load changed ".$this->errmsg, $this->errcode);
			}
		}
		if(!$eur->calculate($changed)){
			return $this->failure_result($eur->errmsg, $eur->errcode);
		}
		return $eur;
	}
	
	/**
	 * Called to trigger any necessary DQS tests when a new object is published (status = accept)
	 * @param array $nobj the new object to be published
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectPublished($nobj, $test_flag = false){
		$nopr = new GraphResult("No graph validation or publication configured for object publication.", $test_flag);
		return $nopr->accept();
	}
	
	/**
	 * Called to trigger any necessary DQS tests when an object is deleted (status = deleted)
	 * @param array $nobj the object to be deleted
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectDeleted($nobj, $test_flag = false){
		$nopr = new GraphResult("No graph validation or publication configured for object deletion.");
		return $nopr->accept();
	}
	
	/**
	 * Called when an ldo is to be updated 
	 * @param LDOUpdate $uldo the update linked data object
	 * @param string $is_test true if this is just a test
	 */
	function objectUpdated(LDOUpdate $uldo, $is_test = false){
		$ar = new GraphResult("ldo " . $uldo->original->id." updated (test: $is_test)");
		return $ar->accept();
	}
	
	/**
	 * Called to undo an update to an ldo
	 * @param LDOUpdate $uldo the update linked data object
	 * @param string $is_test true if this is just a test
	 */
	function undoLDOUpdate(LDOUpdate $uldo, $is_test = false){
		$ar = new GraphResult("rolling back update $uldo->id to ldo ".$uldo->original->id." (test: $is_test)");
		return $ar->accept();		
	}

	/**
	 * Called when an existing (accepted) update has been updated live 
	 * 
	 * Called to update an existing update (e.g. to make minor corrections to an update without creating a new revision.
	 * @param LDOUpdate $uldoa original LDO update object
	 * @param LDOUpdate $uldob modified LDO update object
	 * @param boolean $is_test true if this is just a test
	 * @return DacuraResult containing information on the outcome of the effort
	 */
	function updatePublishedUpdate(LDOUpdate $uldoa, LDOUpdate $uldob, $is_test = false){
		$ar = new GraphResult("update update $uldoa->id to ldo ".$uldoa->original->id." (test: $is_test)");
		return $ar->accept();		
	}
	
	/**
	 * Update a linked data object
	 * @param string $target_id the id of the ldo being updated 
	 * @param string $fragment_id the particular node id within the ldo that is being updated
	 * @param string $ldo_type the ld type of the ldo
	 * @param array $update_obj the contents of the update 
	 * @param string $format the format of the contents (if known)
	 * @param string $editmode is this an update or a replace...
	 * @param array $options options array 
	 * @param boolean $test_flag if true, this is just a test
	 * @return DacuraResult
	 */
	function updateLDO($target_id, $fragment_id, $ldo_type, $update_obj, $format, $editmode, $version, $options, $test_flag){
		$ar = new DacuraResult("Update $target_id", $test_flag);
		$oldo = $this->loadLDO($target_id, $ldo_type, $this->cid(), $fragment_id, $version);
		if(!$oldo){
			if($this->errcode){
				return $ar->failure($this->errcode, "Failed to load $target_id", $this->errmsg);
			}
			else {
				return $ar->failure(404, "No such $this->ldo_type", "$target_id does not exist.");
			}
		}
		$oldo->setNamespaces($this->nsres);
		if(!($nldo = $this->createNewLDObject($target_id, $ldo_type))){
			return $ar->failure($this->errcode, "Request Create Error", "Failed to create $ldo_type object ".$this->errmsg);
		}
		$nldo->setContext($oldo->cid());
		$nldo->setNamespaces($this->nsres);
		if($editmode == "update"){
			$rules = $this->getUpdateLDOContentRules($nldo);				
		}
		else {
			$rules = $this->getReplaceLDOContentRules($nldo);
		}
		if(!($format = $nldo->loadNewObjectFromAPI($update_obj, $format, $options, $rules, $this))){
			return $ar->failure($nldo->errcode, "Protocol Error", "New $ldo_type object sent to API had formatting errors. ".$nldo->errmsg);
		}
		$ldoupdate = new LDOUpdate(false, $oldo);
		if(!$ldoupdate->isLegalContext($this->cid())){
			return $ar->failure(403, "Access Denied", "Cannot update $oldo->id through context ".$this->cid());
		}
		elseif(!$ldoupdate->apply($nldo, $editmode, $rules, $this)){
			return $ar->failure($ldoupdate->errcode, "Protocol Error", "Failed to load the update command from the API. ", $ldoupdate->errmsg);
		}
		if($ldoupdate->nodelta()){
			return $ar->reject("No Changes", "The submitted version is identical to the current version.");
		}
		$ar->add($this->policy->getPolicyDecision("update", $ldoupdate));
		if($ar->is_reject()){
			$ldoupdate->status($ar->status());
			if($this->policy->storeRejected("update", $ldoupdate) && !$test_flag){
				if(!$this->dbman->updateLDO($ldoupdate, $ar->status())){
					$ar->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected update.", $this->dbman->errmsg);
				}
			}
			return $ar;
		}
		$this->checkUpdate($ar, $ldoupdate, $options, $rules, $test_flag);
		if(($ar->is_accept() or $ar->is_pending()) && !$test_flag){
			if(!$this->dbman->updateLDO($ldoupdate, $ar->status())){
				$disaster = new AnalysisResults("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to update database candidate record ". $this->dbman->errmsg);
				$ar->add($disaster);
				$this->rollBackUpdate($ar, $ldoupdate);
			}
		}
		if(isset($options['show_result']) && $options['show_result']){
			if($options['show_result'] == 1){
				$ar->set_result($ldoupdate->changed);
			}
			else {
				$ar->set_result($ldoupdate);
			}
		}
		return $ar;
	}
	
	function getUpdateLDOContentRules($nldo){
		$x = array();
		$x['cwurl'] = $nldo->cwurl;
		$x['default_graph_url'] = $this->getDefaultGraphURL();
		$x["set_id_allowed"] = $this->getServiceSetting("set_id_allowed", true);
		$x["fail_on_bad_update"] = $this->getServiceSetting("fail_on_bad_update", true);
		$x["fail_on_bad_deletes"] = $this->getServiceSetting("fail_on_bad_deletes", false);
		$x['id_generator'] = array($nldo, $this->getServiceSetting("internal_generate_id", "generateInternalID"));
		$x["allow_demand_id"] = $this->getServiceSetting("internal_allow_demand_id", true);
		$x["mimimum_id_length"] = $this->getServiceSetting("internal_mimimum_id_length", 1);
		$x["maximum_id_length"] = $this->getServiceSetting("internal_mimimum_id_length", 80);
		$x["extra_entropy"] = $this->getServiceSetting("internal_extra_entropy", false);
		$x["expand_embedded_objects"] = $this->getServiceSetting("expand_embedded_objects", true);
		$x["replace_blank_ids"] = $this->getServiceSetting("replace_blank_ids", false);
		$x["require_blank_nodes"] = $this->getServiceSetting("require_blank_nodes", false);
		$x["forbid_blank_nodes"] = $this->getServiceSetting("forbid_blank_nodes", false);
		$x["allow_blanknode_predicates"] = $this->getServiceSetting("allow_blanknode_predicates", false);
		$x["require_subject_urls"] = $this->getServiceSetting("require_subject_urls", true);
		$x["require_predicate_urls"] = $this->getServiceSetting("require_predicate_urls", true);
		$x["forbid_unknown_prefixes"] = $this->getServiceSetting("forbid_unknown_prefixes", true);
		$x["unique_subject_ids"] = $this->getServiceSetting("unique_subject_ids", false);
		$x["allow_invalid_ld"] = $this->getServiceSetting("allow_invalid_ld", false);
		$x["require_object_literals"] = $this->getServiceSetting("require_object_literals", true);
		$x["regularise_object_literals"] = $this->getServiceSetting("regularise_object_literals", true);
		$x["forbid_empty"] = $this->getServiceSetting("forbid_empty", false);
		$x["allow_arbitrary_metadata"] = $this->getServiceSetting("allow_arbitrary_metadata", false);
		return $x;			
	}
	
	function getReplaceLDOContentRules($nldo){
		$x = array();
		$x['cwurl'] = $nldo->cwurl;
		$x["set_id_allowed"] = $this->getServiceSetting("set_id_allowed", true);
		$x["fail_on_bad_update"] = $this->getServiceSetting("fail_on_bad_update", true);
		$x["fail_on_bad_deletes"] = $this->getServiceSetting("fail_on_bad_deletes", false);
		$x['id_generator'] = array($nldo, $this->getServiceSetting("internal_generate_id", "generateInternalID"));
		$x["allow_demand_id"] = $this->getServiceSetting("internal_allow_demand_id", true);
		$x["mimimum_id_length"] = $this->getServiceSetting("internal_mimimum_id_length", 1);
		$x["maximum_id_length"] = $this->getServiceSetting("internal_mimimum_id_length", 80);
		$x["extra_entropy"] = $this->getServiceSetting("internal_extra_entropy", false);
		$x["expand_embedded_objects"] = $this->getServiceSetting("expand_embedded_objects", true);
		$x["replace_blank_ids"] = $this->getServiceSetting("replace_blank_ids", false);
		$x["require_blank_nodes"] = $this->getServiceSetting("require_blank_nodes", false);
		$x["forbid_blank_nodes"] = $this->getServiceSetting("forbid_blank_nodes", false);
		$x["allow_blanknode_predicates"] = $this->getServiceSetting("allow_blanknode_predicates", false);
		$x["require_subject_urls"] = $this->getServiceSetting("require_subject_urls", true);
		$x["require_predicate_urls"] = $this->getServiceSetting("require_predicate_urls", true);
		$x["forbid_unknown_prefixes"] = $this->getServiceSetting("forbid_unknown_prefixes", true);
		$x["unique_subject_ids"] = $this->getServiceSetting("unique_subject_ids", false);
		$x["allow_invalid_ld"] = $this->getServiceSetting("allow_invalid_ld", false);
		$x["require_object_literals"] = $this->getServiceSetting("require_object_literals", true);
		$x["regularise_object_literals"] = $this->getServiceSetting("regularise_object_literals", true);
		$x["forbid_empty"] = $this->getServiceSetting("forbid_empty", false);
		$x["allow_arbitrary_metadata"] = $this->getServiceSetting("allow_arbitrary_metadata", false);
		return $x;		
	}
	
	/**
	 * Called to roll back an update because of db failure 
	 * @param DacuraResult $ar the dacura result message containing the information about the changes to be rolled back
	 * @param LDOUpdate $uldo the update object to be rolled back
	 */
	function rollbackUpdate(DacuraResult &$ar, LDOUpdate &$uldo){
		if($ar->includesGraphChanges()){
			$recovery = $this->undoUpdatesToGraph($uldo);
			$ar->undoGraphResult($recovery);
		}
	}
	
	/**
	 * Checks an update to an LDO with DQS
	 * @param DacuraResult $ar the result of the update policy check
	 * @param LDOUpdate $uldo the update object itself
	 * @param array $options the options for checking
	 * @param boolean $test_flag if true, this is just a test
	 */
	function checkUpdate(DacuraResult &$ar, LDOUpdate &$uldo, $options = array(), $rules = array(), $test_flag = false){
		//check version information
		if(!$uldo->original->isLatestVersion()){
			if($ar->is_accept() && $this->getServiceSetting("rollback_updates_to_pending_on_version_reject")){
				$ar->status("pending");
				$ar->setWarning("Update check", "Update version clash", "The object you are updating has been updated to version ".$uldo->original->latest_version." since the version that you saw (".$uldo->original->version.")");
			}
			else {
				return $ar->failure(400, "Update version clash", "The object you are updating has been updated to version ".$uldo->original->latest_version." since the version that you saw (".$uldo->original->version.")");
			}			
		}
		if($ar->is_accept() or $ar->is_confirm()){
			//unless the status of the candidate was accept, before or after, the change to the report graph is hypothetical
			$hypo = !($uldo->changedPublished() || $uldo->originalPublished());
			if($hypo && (!$this->getServiceSetting("test_unpublished", true))){
				return $ar;
			}
			$gu = $this->publishUpdateToGraph($uldo, $ar->status(), $hypo || $test_flag);
			if($ar->is_accept() && !$hypo && $gu->is_reject() && $this->getServiceSetting("rollback_updates_to_pending_on_dqs_reject", true)){
				$ar->setWarning("Publication", "Rejected by DQS Service", "State changed from accept to pending");
				$ar->status("pending");
				$ar->addGraphResult("dqs", $gu, true);
			}
			else {
				$ar->addGraphResult("dqs", $gu, $hypo || $test_flag);
			}
		}
		elseif($ar->is_pending() && $this->getServiceSetting("test_unpublished", true)){
			$hypo = true;
			$gu = $this->publishUpdateToGraph($uldo, "pending", $hypo);
			$ar->addGraphResult("dqs", $gu, $hypo);
		}
		else {
			$hypo = $test_flag || !$ar->is_accept();
		}
		$uldo->status($ar->status());
		if(isset($options['show_meta_triples']) && $options['show_meta_triples']){
			$ar->createMetaResult($uldo->getMetaUpdates(), $ar->status(), $test_flag, $test_flag || !$ar->is_accept());
		}
		if(isset($options['show_update_triples']) && $options['show_update_triples']){
			$ar->createGraphResult("update", $ar->status(), $uldo->forward, $uldo->backward, $test_flag, $test_flag || $ar->is_reject());
		}
		if(isset($options['show_ld_triples']) && $options['show_ld_triples']){
			$delta = $uldo->compareLDTriples($rules);
			$ar->createGraphResult("ld", $ar->status(), $delta->triples_added, $delta->triples_removed, $test_flag, $test_flag || $ar->is_reject());
		}
	}
	
	/**
	 * Called when an update causes an update to an entities published form
	 * @param LDOUpdate $uldo - the ldo update in question
	 * @param string $decision - the status of the update
	 * @param boolean $testflag - true if this is just a test
	 * @return GraphResult
	 */
	function publishUpdateToGraph($uldo, $decision, $testflag ){
		if($uldo->bothPublished()){
			$gu = $this->objectUpdated($uldo, $testflag );
		}
		elseif($uldo->originalPublished()){
			$gu = $this->objectDeleted($uldo->original, $testflag);
		}
		else {
			if($testflag || $uldo->changedPublished()){
				$dont_publish = ($testflag || $decision != "accept");
				$gu = $this->objectPublished($uldo->changed, $dont_publish);
			}
			else {
				$gu = new GraphResult("Nothing to save to graph");
			}
		}
		return $gu;
	}
	
	/**
	 * Called to roll back the updates to the dqs graph 
	 * @param LDOUpdate $uldo the LDO Update object in question
	 * @return GraphResult
	 */
	function undoUpdatesToGraph($uldo){
		if($uldo->bothPublished()){
			return $this->undoLDOUpdate($uldo, false);
		}
		elseif($uldo->originalPublished()){
			return $this->objectPublished($uldo->original);//dr
		}
		elseif($uldo->changedPublished()){
			return $this->objectDeleted($uldo->changed);//wr
		}
		$ar = new GraphResult("Nothing to undo in report graph");
		return $ar;
	}
	
	/**
	 * Update a LDO Update
	 * 
	 * Generally should be just called to change LDO Update Status during approval
	 * But supports all changes to updates 
	 * @param string $id - the id of the update to be updated
	 * @param array $obj - json object received by api representing update contents
	 * @param array $meta - json object representing update meta
	 * @param array $options - options array 
	 * @param boolean $test_flag - if true this is only a test
	 * @return DacuraResult
	 */
	function updateUpdate($id, $obj, $meta, $options, $test_flag = false){
		$ar = new GraphResult("Update $this->update_type $id", $test_flag);
		if(!$orig_upd = $this->loadUpdate($id)){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		if(!$new_upd = $this->loadUpdatedUpdate($orig_upd, $obj, $meta, $options)){
			return $ar->failure($this->errcode, "Failed to load updated version of $id", $this->errmsg);
		}
		if($new_upd->sameAs($orig_upd)){
			return $ar->reject("No Changes", "The new update is identical to the existing update - it will be ignored.");
		}
		if(!$new_upd->isLegalContext($this->cid())){
			return $ar->failure(403, "Access Denied", "Cannot update candidate $new_upd->targetid through context ".$this->cid());
		}
		if($new_upd->nodelta()){
			return $ar->reject("No changes", "The submitted version removes all changes from the update - it has no effect.");
		}
		$ar->add($this->getPolicyDecision("update update", array($orig_upd, $new_upd)));
		if($ar->is_reject()){
			return $ar;
		}
		$umode = $this->checkUpdatedUpdate($ar, $new_upd, $orig_upd, $options, $test_flag);
		if($ar->is_accept() && !$test_flag){
			if($umode == "rollback"){
				$worked = $this->dbman->rollbackUpdate($orig_upd, $new_upd);
			}
			else {
				$worked = $this->dbman->updateUpdate($new_upd, $orig_upd->status());
			}
			if(!$worked){
				$disaster = new DacuraResult("Database Synchronisation");
				$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to update database update record ".$orig_upd->id.". ". $this->dbman->errmsg);
				$ar->add($disaster);
				if($ar->includesGraphChanges()){
					if($umode == "rollback"){
						$recovery = $this->undoUpdatedUpdate($orig_upd, $new_upd);
					}
					else {
						$recovery = $this->undoUpdatedUpdate($new_upd, $orig_upd);
					}
					$ar->undoReportGraphResult($recovery);
				}
			}
		}
		$format = isset($options['format']) ? $options['format'] : false;
		$flags = isset($options['options']) ? $options['options'] : array();
		$version = isset($options['version']) ? $options['version'] : false;
		$ar->set_result($new_upd->showUpdateResult($format, $flags, $version, $this));
		return $ar;
	}
	
	/**
	 * Loads an update LDO Update from the database
	 * @param LDOUpdate $orig_upd the original update object
	 * @param array $obj json update object contents received from api
	 * @param array $meta json update meta received from api
	 * @param array $options options array
	 * @return boolean|LDOUpdate update objecct 
	 */
	function loadUpdatedUpdate($orig_upd, $obj, $meta, $options = array()){
		if(isset($meta['from_version']) && $meta['from_version'] && $meta['from_version'] != $orig_upd->original->get_version()){
			$norig = $this->loadLDO($orig_upd->targetid, $this->update_type, $this->cid(), false, $meta['version']);
			if(!$norig)	return false;
		}
		else {
			$norig = clone $orig_upd->original;
		}
		$ncur = $this->createNewLDOUpdateObject($norig, $this->update_type);
		$ncur->to_version = $orig_upd->to_version;
		if(isset($meta['status'])){
			$ncur->status($meta['status']);
		}
		else {
			$ncur->status($orig_upd->status());
		}
		$form = isset($options['format']) ? $options['format'] : "json";
		/*$opts = array(
				"demand_id_allowed" => $this->policy->demandIDAllowed("update $this->update_type", $ncur),
				"force_inserts" => true,
				"calculate_delta" => true,
				"validate_delta" => true
		);*/
		if(!$ncur->loadFromAPI($obj, $meta, $form, $options)){
			return $this->failure_result($ncur->errmsg, $ncur->errcode);
		}
		return $ncur;
	}
	
	
	
	/**
	 * Checks an update to an update for conformance with rules in place
	 * @param DacuraResult $ar - the current state of the request
	 * @param LDOUpdate $new_upd - modified update object
	 * @param LDOUpdate $orig_upd - original update object
	 * @param array $options - options in place
	 * @param boolean $test_flag - if true it is just a test
	 * @return string - the mode "normal", "live" or "rollback"
	 */
	function checkUpdatedUpdate(&$ar, $new_upd, $orig_upd, $options, $test_flag){
		
		//3 types of changes can be caused by updates to updates
		//1. Changes to the update itself (if ar->is_accept() and it is legal...)
		//2. Changes to a candidate (if either new or old update == accept -> there will be changes to the candidate graph
		//3. Changes to a report -> if updated candidate = accept or old candidate = accept
		//if the update is unpublished in both new and old, the update to both graphs is hypothetical

		$meta_delta = $new_upd->getMetaUpdates();
		$chypo = false;
		$umode = "normal";
		$capture_ld = isset($options['show_ld_triples']) && $options['show_ld_triples'];
		$capture_update = isset($options['show_update_triples']) && $options['show_update_triples'];
		$capture_meta = isset($options['show_meta_delta']) && $options['show_meta_delta'];
		$test_unpublished = $this->getServiceSetting("test_unpublished", true);
		
		$md = $capture_meta ? $meta_delta : false;
		if($new_upd->published() && $orig_upd->published()){ //live edit
			if($capture_update){		
				$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			}
			if($capture_ld){
				$trips = $new_upd->deltaAsTriples($orig_upd);
				$ar->setLDGraphResult($trips['add'], $trips['del'], $chypo, $md);
			}
			$umode = "live";
		}
		elseif($new_upd->published()){ //publish new update
			if($capture_update){
				$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			}
			if($capture_ld){
				$ar->setLDGraphResult($new_upd->addedLDTriples(), $new_upd->deletedLDTriples(), $chypo, $md);
			}
		}
		elseif($orig_upd->published()){ //unpublish update
			$umode = "rollback";
			if(isset($options['pending_updates_prevent_rollback']) && $options['pending_updates_prevent_rollback']){
				//check here to see if there are any pending updates that are hanging off the latest version....
				if($this->dbman->pendingUpdatesExist($orig_upd->targetid, $this->update_type, $this->cid(), $orig_upd->to_version()) || $this->dbman->errcode){
					if($this->dbman->errcode){
						return $ar->failure($this->dbman->errcode, "Unpublishing of update $orig_upd->id failed", "Failed to check for pending updates to current version of candidate");
					}
					return $ar->failure(400, "Unpublishing of update $orig_upd->id not allowed", "There are pending updates on version ".$orig_upd->to_version()." of candidate $orig_upd->targetid");
				}
			}
			if($capture_update){
				$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
			}
			if($capture_ld){
				$ar->setLDGraphResult($orig_upd->deletedLDTriples(), $orig_upd->addedLDTriples(), $chypo, $md);
			}
		}
		else { //edit unpublished
			if($test_unpublished){
				$chypo = true;
				if($capture_update){
					$ar->setUpdateGraphResult($orig_upd->compare($new_upd));
				}
				if($capture_ld){
					$ar->setLDGraphResult($new_upd->addedLDTriples(), $new_upd->deletedLDTriples(), $chypo, $md);
				}
			}
		}
		if($umode == "rollback"){
			$hypo = $chypo || !($orig_upd->changedPublished() || $orig_upd->originalPublished());
		}
		else {
			$hypo = $chypo || !($new_upd->changedPublished() || $new_upd->originalPublished());
		}
		if($test_flag or $ar->is_confirm() or ($hypo and $test_unpublished)){
			$gu = $this->testUpdatedUpdate($new_upd, $orig_upd, $umode);
		}
		elseif(!$hypo) {
			$gu = $this->saveUpdatedUpdate($new_upd, $orig_upd, $umode);
		}
		else {
			$gu = new GraphResult("no dqs tests");
			$gu->accept();
		}
		$ar->setGraphResult($gu, $hypo);
		return $umode;
	}
	
	/**
	 * Translates testing an update to an update into calls to underlying functions 
	 * @param LDOUpdate $ncur new update object
	 * @param LDOUpdate $ocur old update object
	 * @param string $umode update mode - rollback, live, normal
	 * @return GraphResult
	 */
	function testUpdatedUpdate($ncur, $ocur, $umode){
		if($umode == "rollback"){
			return $this->updatedUpdate($ocur, $umode, true);
		}
		elseif($umode == "live"){
			return $this->updatePublishedUpdate($ncur, $ocur, true);
		}
		else {
			return $this->updatedUpdate($ncur, $umode, true);
		}
	}
	
	/**
	 * Translates an update to an update into calls to underlying functions 
	 * @param LDOUpdate $ncur new update object
	 * @param LDOUpdate $ocur old update object
	 * @param string $umode update mode - rollback, live, normal
	 * @return GraphResult
	 */
	function saveUpdatedUpdate($ncur, $ocur, $umode){
		if($umode == "rollback"){
			return $this->updatedUpdate($ocur, $umode);
		}
		elseif($umode == "live"){
			return $this->updatePublishedUpdate($ncur, $ocur);
		}
		else {
			return $this->updatedUpdate($ncur, $umode);
		}
	}
	
	/**
	 * Called when an update has been updated - when update is not live
	 * @param LDOUpdate $cur the update object
	 * @param string $umode the mode: rollback or normal
	 * @param string $testflag
	 * @return GraphResult
	 */
	function updatedUpdate($cur, $umode, $testflag = false){
		if($cur->bothPublished()){
			if($umode == "rollback"){
				return $this->undoLDOUpdate($cur, $testflag);
			}
			else {
				return $this->objectUpdated($cur, $testflag);
			}
		}
		elseif($cur->originalPublished()){
			if($umode == "rollback"){
				return $this->objectDeleted($cur->original, $testflag);
			}
			else {
				return $this->objectDeleted($cur->changed, $testflag);
			}
		}
		elseif($cur->changedPublished() or $testflag) {
			if($umode == "rollback"){
				$dont_publish = ($testflag || $cur->original->status != "accept");
				return $this->objectPublished($cur->original, $dont_publish);
			}
			else {
				$dont_publish = ($testflag || $cur->changed->status != "accept");
				return $this->objectPublished($cur->changed, $dont_publish);
			}
		}
		else {
			$ar = new GraphResult("Nothing to save to report graph");
			return $ar;
		}
	}

	/**
	 * Returns the full id of the instance graph associated with the graph id 
	 * 
	 * @param string $graphname the local id of the graph
	 */
	function getInstanceGraph($graphname){
		if($this->graphbase) return $this->graphbase ."/". $graphname."_instance";
		return $graphname."_instance";
	}
	
	/**
	 * Returns the full id of the schema graph associated with the graph id 
	 * 
	 * @param string $graphname the local id of the graph
	 */
	function getGraphSchemaGraph($graphname){
		if($this->graphbase) return $this->graphbase ."/". $graphname."_schema";
		return $graphname."_schema";
	}

	/**
	 * Returns the full id of the schema's schema graph associated with the graph id
	 *
	 * @param string $graphname the local id of the graph
	 */
	function getSchemaSchemaGraph($graphname){
		if($this->graphbase) return $this->graphbase ."/". $graphname."_schema_schema";
		return $graphname."_schema_schema";
	}
	
	
	/**
	 * Retrieves pending updates from DB
	 * @param LDO $ldo linked data object
	 * @return array|boolean - array of pending updates or false on failure
	 */
	function getPendingUpdates($ldo){
		$updates = $this->dbman->get_relevant_updates($ldo, $this->ldo_type);
		return $updates ? $updates : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	
	/* Methods for sending results to client */

	/**
	 * Marshalls a dacura LDO through http
	 * @param DacuraResult $ar
	 * @param string $format the format for display
	 * @param string $display a string containing display options
	 * @param array $options options
	 * @param integer $version the version to be viewed
	 * @return boolean true if successfully sent
	 */
	function sendRetrievedLDO($ar, $format, $options){
		if(!$format) $format = "json";
		if($ar->is_error() or $ar->is_reject() or $ar->is_pending()){
			$this->writeDecision($ar, $options);
		}
		else {
			if(!$this->sendLDO($ar->result, $format, $options)){
				$ar = new DacuraResult("export ldo");
				$ar->failure($this->errcode, "Failed to export data to $format", $this->errmsg);
				$this->writeDecision($ar, $options);
			}
		}
	}
		
	
	/**
	 * prepares a linked data object for transmission to the client
	 * @param LDO $ldo linked data object
	 * @param string $format the format for display
	 * @param string $display a string containing display options
	 * @param integer $version the version to be viewed
	 * @return boolean true if successfully sent
	 */
	function sendLDO($ldo, $format, $opts){
		if(!isset(LDO::$valid_display_formats[$format])){
			return $this->failure_result("$format is not a valid display type", 400);				
		}
		if($ldo->getContentInFormat($format, $opts, $this, "display")){
			return $this->write_json_result($ldo->forAPI($format, $opts), "Sent the object");
		}
		else {
			return $this->failure_result($ldo->errmsg, $ldo->errcode);
		}
	}
	
	/**
	 * Marshalls a dacura LDO Update through http
	 * @param DacuraResult $ar
	 * @param string $format the format for display
	 * @param string $display a string containing display options
	 * @param array $options options
	 * @param integer $version the version to be viewed
	 * @return boolean true if successfully sent
	 */
	function sendRetrievedUpdate($ar, $format, $options, $version){
		if($ar->is_error() or $ar->is_reject() or $ar->is_confirm() or $ar->is_pending()){
			$this->writeDecision($ar, $options);
		}
		else {
			$this->sendUpdate($ar->result, $format, $options, $version);
		}
	}

	/**
	 * prepares LDO Update for transmission over http
	 * @param DacuraResult $ar
	 * @param string $format the format for display
	 * @param string $display a string containing display options
	 * @param array $osptions options
	 * @param integer $version the version to be viewed
	 * @return boolean true if successfully sent
	 */
	function sendUpdate($update, $format, $flags, $version = false){
		$ar = $update->showUpdateResult($format, $flags, $display, $this);
		return $this->write_json_result($ar, "update ".$update->id." dispatched");		
	}
	
	/**
	 * Transforms a dacura result object into an appropriate http communication
	 * @param DacuraResult $ar the result object
	 * @return boolean true if successfully sent
	 */
	function writeDecision(DacuraResult $ar, $format = "json", $options = array()){
		if($ar->is_error()){
			http_response_code($ar->errcode);
			$this->logResult($ar->errcode, $ar->status()." : ".$ar->action);
		}
		elseif($ar->is_reject()){
			http_response_code(401);
			$this->logResult(401, $ar->status()." : ".$ar->action);
		}
		elseif($ar->is_confirm()){
			http_response_code(428);
			$this->logResult(428, $ar->status()." : ".$ar->action);
		}
		elseif($ar->is_pending()){
			http_response_code(202);
			$this->logResult(202, $ar->status()." : ".$ar->action);
		}
		else {
			$this->logResult(200, $ar->status(), $ar->action);
		}
		$json = json_encode($ar->forAPI($format, $options, $this));
		if($json){
			echo $json;
			return true;
		}
		else {
			http_response_code(500);
			echo "JSON error: ".json_last_error() . " " . json_last_error_msg();
		}
	}
	
	function supportsFormat($format, $ftype = false){
		if($ftype == "input"){
			return isset(LDO::$valid_input_formats[$format]);				
		}
		return isset(LDO::$valid_display_formats[$format]);
	}
	
	function getAvailableMimeTypes(){
		return array_values(LDO::$format_mimetypes);
	}
	
	/**
	 * Returns the mimetype string for the given format
	 * @param string $format an id of a dacura format 
	 * @return mimetype associated with the format or false if none is found
	 */
	function getMimeTypeForFormat($format){
		return isset(LDO::$format_mimetypes[$format]) ? LDO::$format_mimetypes[$format] : false;
	}

	function getFormatForMimeType($mtype){
		foreach(LDO::$format_mimetypes as $f => $fmtype){
			if($mtype == $fmtype) return $f;
		}
		return false;
	}
	
	
	function display_content_directly(LDO $ldo, $format, $options){
		$mime = $this->getMimeTypeForFormat($format);
		$content = $ldo->getContentInFormat($format, $options, $this, "api");
		if($mime && $content){
			header("Content-Type: ".$mime);
			echo $content;				
			$this->logResult(200, "delivered linked data object $ldo->id in format $format");
			return true;				
		}
		else {
			http_response_code(500);
			$msg = "LDO output error: failed to produce data object $ldo->id in format $format";
			$this->logResult(500, $msg);
			echo $msg;
			return false;
		}
	}
	
	/**
	 * Checks to see whether a demand id is valid
	 * @param string $demand the demand id
	 * @param string $type the ld type of the object
	 * @return boolean true if valid
	 */
	function demandIDValid($demand, $type){
		if(!$this->policy->demandIDAllowed("create", $type)){
			return $this->failure_result("Policy does not allow specification of candidate IDs", 400);
		}
		if(!(ctype_alnum($demand) && strlen($demand) > 1 && strlen($demand) <= 40 )){
			return $this->failure_result("Candidate IDs must be between 2 and 40 alphanumeric characters", 400);
		}
		if($this->dbman->hasLDO($demand, $type, $this->cid())){
			return $this->failure_result("Candidate ID $demand exists already in the dataset", 400);
		}
		elseif($this->dbman->errcode){
			return $this->failure_result("Failed to check for duplicate ID ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		return true;
	}
	
	function getLDOTypeFromClassname(){
		$cname = get_class($this);
		return substr($cname, -(strlen("DacuraServer")));
	}
	
	private function readGraphConfiguration(){
		$filter = array(
			"type" => "graph",
			"collectionid" => $this->cid(),
			"include_all" => true,
			"status" => "accept"	
		);
		if($active_graphs = $this->getLDOs($filter)){
			foreach($active_graphs as $gr){
				if($graph = $this->loadLDO($gr['id'], "graph", $this->cid())){
					$this->graphs[$graph->id] = $graph;						
				}
				else {
					return false;
				}
			}
		}
		return true;
	}
	
	function getValidGraphURLs(){
		if(!$this->graphs){
			$this->readGraphConfiguration();
		}
		$urls = array();
		foreach(array_keys($this->graphs) as $gid){
			$urls[] = $this->service->get_service_url("graph")."/$gid";
		}
		return $urls;
	}
		
	function getDefaultGraphURL(){
		return $this->service->get_service_url("graph")."/main";
	}
	
	function isDefaultGraphURL($url){
		return $url == $this->getDefaultGraphURL();
	}
	
	function validGraph($gid){
		return isset($this->graphs[$gid]);
	}
	
	function graphURLToID($gurl){
		if(substr($gurl, 0, strlen($this->service->get_service_url("graph"))) == $this->service->get_service_url("graph")){
			return substr($gurl, strlen($this->service->get_service_url("graph"))+1);
		}
		return false;
	}
	
	function getOntologyCollection($id){
		if($this->dbman->hasLDO($id, "ontology", "all")){
			return "all";
		}
		return $this->cid();
	}
	
	
}
