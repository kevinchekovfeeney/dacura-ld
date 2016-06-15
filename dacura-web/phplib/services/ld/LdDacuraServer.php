<?php
include_once("lib/PolicyEngine.php");
include_once("lib/LDOUpdate.php");
include_once("lib/LDO.php");
include_once("lib/DacuraResult.php");
include_once("lib/GraphManager.php");
include_once("lib/NSResolver.php");
include_once("lib/Ontology.php");
include_once("lib/RVO.php");
include_once("lib/Graph.php");
include_once("lib/Candidate.php");
include_once("LdService.php");
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
	/** @var array of the graphs that are associated with the collection context of the server */
	var $graphs = array();
	/** @var array of the ontologies (along with their latests version of the latest ontologies available */	
	var $ontversions = array();//array of [id => [version, id, collection]] of latest ontologies in system....

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
		$this->readGraphConfiguration();
		return parent::init($action, $object);
	}

	/**
	 * The ontologies / namespace prefixes available in any context are the universal ontologies (with status = accept) 
	 * and all the ontologies in the collection
	 * 
	 * This function loads the appropriate ontologies into the server's namespace resolver object
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
		$ontvlist = array();
		$this->nsres = new NSResolver($this->service);
		foreach($onts as $i => $ont){
			if(isset($ont['id']) && $ont['id'] && isset($ont['meta']['url']) && $ont['meta']['url']){
				$this->nsres->prefixes[$ont['id']] = $ont['meta']['url'];
				$ourl = $ont['meta']['url'];				
			}
			else {
				$ourl = $this->durl().($ont['collectionid'] == "all" ? "" : $ont['collectionid']."/")."ontology/".$ont['id'];
			}
			$otitle = isset($ont['meta']['title']) ? $ont['meta']['title'] : "";
			$ontvlist[$ont['id']] = array("collection" => $ont['collectionid'], "url" => $ourl, "title" => $otitle, "version" => $ont['version'], "id" => $ont['id']);
		}
		$this->ontversions = $ontvlist;
	}

	/**
	 * Create a new instance of a LDO type
	 * @param string $type the type of the object
	 * @param array $create_obj the initial object to be created
	 * @param string $demand_id the id that the client is requesting
	 * @param string $format the format of the object contents one of LDO::$valid_input_types
	 * @param array $options options for creation
	 * @param boolean $test_flag if true do not actually create the object, just simulate it
	 * @return CreateResult
	 */
	function createLDO($type, $create_obj, $demand_id, &$format, $options, $test_flag = false){
		$cr = new DacuraResult("create $type", $test_flag);
		if($format && !isset(LDO::$valid_input_formats[$format])){
			return $cr->failure(400, "Invalid format for new $type", "$format is not a supported input format");				
		}
		$this->errmsg = "";//blank out any previous error code as we need to use it to get the reason back.
		$id = $this->getNewLDOLocalID($demand_id, $type);
		if(!$id){
			$id = genid(false, $this->getNewLDOIDRules());
		}
		if($demand_id && $demand_id != $id){
			$reason = $this->errmsg ? $this->errmsg : demandIDInvalid($demand_id, $this->getNewLDOIDRules());
			if(isset($options['fail_on_id_denied']) && $options['fail_on_id_denied']){
				return $cr->failure(412, "Failed to allocate requested id", $reason);
			}
			$this->addIDAllocationWarning($cr, $type, $test_flag, $id, $reason);
		}
		if(!($nldo = $this->createNewLDObject($id, $type, $this->cid()))){
			return $cr->failure($this->errcode, ucfirst($type) . " creation failed", $this->errmsg);
		}
		if(!($format = $nldo->loadNewObjectFromAPI($create_obj, $format, $options, $this, "create", "import"))){
			return $cr->failure($nldo->errcode, "Input ". ucfirst($type) . " has incorrect format", $nldo->errmsg);
		}
		if($nldo->isEmpty() && !$this->getServiceSetting("ldo_allow_empty_create", false)){
			return $cr->failure(400, " $type is empty", "You must add some content to the $type before it can be accepted by the system.");				
		}
		if(!($nldo->validate("create", $this))){
			return $cr->failure($nldo->errcode, "Linked Data Format Error", "New $type sent to API had formatting errors. ".$nldo->errmsg);
		}
		$cr->add($this->policy->getPolicyDecision("create", $nldo));
		if($cr->is_accept() || ($cr->is_pending() && $this->getServiceSetting("test_unpublished", true))){
			$gur = $this->objectPublished($nldo, !$cr->is_accept() || $test_flag);
			$gur->setHypothetical(!$cr->is_accept());
			$rb = (isset($options["rollback_ldo_to_pending_on_dqs_reject"]) && $options["rollback_ldo_to_pending_on_dqs_reject"]) || $this->getServiceSetting("rollback_ldo_to_pending_on_dqs_reject", false);				
			if($gur->is_reject() && $cr->is_accept() && ($nldo->isEmpty() || $rb)){
				$cr->msg_body = "New $type failed DQS tests";
				$cr->status("pending");
				$nldo->status($cr->status());
				$cr->addGraphResult("dqs", $gur, true, false);				
			}
			elseif($gur->is_reject() && $cr->is_pending() && ($nldo->isEmpty() || $this->getServiceSetting("retain_pending_on_dqs_reject", true))){
				$cr->msg_body = "New $type failed DQS tests";
				$cr->status("pending");
				$nldo->status($cr->status());
				$cr->addGraphResult("dqs", $gur, true, false);					
			}
			else {
				$nldo->status($cr->status());
				$cr->addGraphResult("dqs", $gur);
			}
		}
		if(!$test_flag && (!$cr->is_reject() || $this->policy->storeRejected("create", $nldo)) && !$this->dbman->createLDO($nldo, $type)){
			$disaster = new DacuraResult("Database Synchronisation");
			$disaster->failure($this->dbman->errcode, "Internal Error", "Failed to create database ldo record ". $this->dbman->errmsg);
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
		if(isset($options['show_ld_triples']) && $options['show_ld_triples']){
			$cr->createGraphResult("ld", "New $type's linked data contents", $cr->status(), $nldo->typedQuads(), array(), $test_flag);
		}
		if(isset($options['show_meta_triples']) && $options['show_meta_triples']){
			$mupdates = array_merge($nldo->meta, $nldo->getPropertiesAsArray());
			$cr->createGraphResult("meta", "New $type's metadata", $cr->status(), $mupdates, array(), $test_flag);
		}
		if($cr->is_accept()){
			if($nldo->isEmpty()){
				$cr->msg_title = "Empty ".ucfirst($type)." accepted.";
			}
			elseif($test_flag){
				$cr->msg_title = ucfirst($type)." passed and would be published";
			}
			else {
				$cr->msg_title = ucfirst($type)." ".$nldo->id. " Published";				
			}
		}
		elseif($cr->is_pending()){
			if($nldo->isEmpty()){
				$cr->msg_title = "Empty ".ucfirst($type)." accepted.";
			}
			elseif($test_flag){
				$cr->msg_title = ucfirst($type)." would be accepted but not published";				
			}
			else {
				$cr->msg_title = ucfirst($type)." ".$nldo->id. " accepted but not published";				
			}				
		}
		if(!$test_flag && ($cr->is_pending() || $cr->is_accept())){
			if($cr->msg_body){
				$cr->msg_body .= ". Available at: <a href='$nldo->cwurl'>$nldo->cwurl</a>";
			}
			else {
				$cr->msg_body = "Available at: <a href='$nldo->cwurl'>$nldo->cwurl</a>";
			}
		}
		return $cr;
	}
	
	/**
	 * Simple helper function to say whether the object passed into the api included contents (in url, text or file form)
	 * @param array $obj json object as passed into API
	 * @return boolean true if it contains "contents", "ldurl" or "ldfile"
	 */
	function APIObjectIncludesContents($obj){
		if(isset($obj['contents']) || isset($obj['ldurl']) || isset($obj['ldfile'])){
			return true;
		}
		return false;
	}
	
	/**
	 * Adds a warning when a demand id fails for an ld object 
	 * @param DacuraResult $ar - the dacura result object the warning is being added to
	 * @param string $type - linked data type being created
	 * @param boolean $test_flag - if true it is only a test create
	 * @param string $id - the randomly generated Id that will be used. 
	 */
	function addIDAllocationWarning(&$ar, $type, $test_flag, $id, $reason){
		$txt = "Requested ID could not be granted ($reason).";
		$extra = "";
		if($test_flag){
			$txt = "An ID will be randomly generated when the $type is created.";
			$extra = "$id is an example of a randomly generated ID, it will be replaced by another if the $type is created";
		}
		else {
			$txt = "The $type was allocated a randomly generated ID: $id";
		}
		$ar->addWarning("Generating id", $txt, $extra, "RequestIDRefusalWarning");
	}
	
	/** 
	 * Called to create a php object of the appropriate type and intialise it with some things from the server
	 * 
	 * Thsi should be the only way in which LDO objects are created. 
	 * @param string $id the object's id
	 * @param string $type the object' type
	 * @param string $cid the object's collection id
	 * @return an instance of the object 
	 */
	function createNewLDObject($id, $type, $cid = false){
		$cid = $cid ? $cid : $this->cid();
		$cwbase = $this->durl();
		$cwbase .= (($cid == "all" || !$cid) ? "" : $cid ."/") ;
		$cwbase .= $type."/";
		$uclass = ucfirst($type);
		if(class_exists($uclass)){
			$ldo = new $uclass($id, $cwbase, $this->service->logger);
		}
		else {
			$ldo = new LDO($id, $cwbase, $this->service->logger);
		}
		$ldo->cid = $cid;
		$ldo->ldtype = $type;
		$ldo->setLDRules($this);
		$ldo->setNamespaces($this->nsres);
		if(isset($this->graphs) && $this->graphs){
			$ldo->graphs =& $this->graphs;
		}
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
		return genid($demand_id, $this->getNewLDOIDRules());
	}
	
	/**
	 * Returns the config settings that are relevant to id generation
	 * @return array - rules for constraining new ids
	 */
	function getNewLDOIDRules(){
		$rules = array();
		$rules["allow_demand_id"] = $this->getServiceSetting("ldo_allow_demand_id", true);
		$rules["mimimum_id_length"] = $this->getServiceSetting("ldo_minimum_id_length", 2);
		$rules["maximum_id_length"] = $this->getServiceSetting("ldo_maximum_id_length", 80);
		$rules["extra_entropy"] = $this->getServiceSetting("ldo_extra_entropy", false);
		return $rules;
	}
	
	/**
	 * Return a list of linked data objects 
	 * @param array $filter a filter on the objects
	 * @return boolean|array the linked data objects in an array
	 */
	function getLDOs(&$filter, $options = array()){
		if(isset($options['include_all'])){
			$filter['include_all'] = $options['include_all'];
		}
		if(isset($options['status'])){
			$filter['status'] = $options['status'];
		}
		if(isset($options['version'])){
			$filter['version'] = $options['version'];
		}
		if(isset($options['createtime'])){
			$filter['createtime'] = $options['createtime'];
		}
		if($this->cid() == "all" && isset($options['collectionid'])){
			$filter['collectionid'] = $options['collectionid'];
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
	 function getUpdates(&$filter, $options = array()){
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
		if(!($ldo = $this->createNewLDObject($ldo_id, $type, $cid))){
			return false;
		}
		if(!$this->dbman->loadLDO($ldo, $ldo_id, $type, $cid)){
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
		if($version && $ldo->version() > $version){
			if(!$this->rollBackLDO($ldo, $version)){
				return false;
			}
		}
		if($fragment_id && !$ldo->loadFragment($fragment_id)){
			return $this->failure_result("Failed to load fragment ".htmlspecialchars($fragment_id). " " .$ldo->errmsg, $ldo->errcode);
		}
		if($options && isset($options['analysis']) && $options['analysis']){
			if(!($ldo->analysis = $this->loadLDOAnalysis($ldo, $options['analysis']))){
				return false;
			}
		}
		return $ldo;
	}
	
	/**
	 * Analyses the LDO and caches the result 
	 * @param LDO $ldo linked data object
	 * @param integer $lvl (if lvl > 1) then the analysis will be created afresh and not load from the cache
	 * @return array an analysis array, the structure of which varies depending on the type of the linked data object (analyse function)
	 */
	function loadLDOAnalysis(LDO $ldo, $lvl){
		$cache_path = array("ld", $ldo->ldtype(), $ldo->id);
		if($this->getServiceSetting('cache_analysis') && $lvl == 1){
			if($anal = $this->fileman->decache($cache_path, "analysis")){
				return $anal;
			}
		}
		$anal = $ldo->analyse($this);
		if($this->getServiceSetting('cache_analysis') && !$this->isBaseLDServer()){ //don't cache anything if it comes from ld
			if(!$this->fileman->cache($cache_path, "analysis", $anal, $this->getServiceSetting('analysis_cache_config'))){
				return $this->failure_result("Failed to cache $ldo->id analysis: ".$this->fileman->errmsg, $this->fileman->errcode);
			}
		}
		return $anal;
		
	}
	
	/**
	 * Check whether we are operating in the context of the base ld server class or one of the derived classes (generally we should always be in the later!).
	 * @return boolean
	 */
	function isBaseLDServer(){
		return get_class($this) == "LdDacuraServer";
	}
	
	/**
	 * Loads an array of historical records about the LDO detailing its previous changes
	 * 
	 * @param LDO $oldo the ldo under investigation
	 * @param number $from_version what version are we interested in history from
	 * @param number $to_version what version are we interested in history to
	 * @return boolean|array 
	 */
	function loadHistoricalRecord(LDO $oldo, $from_version = 0, $to_version = 1){
		$ldo = clone $oldo;
		$histrecord = array(array(
				'status' => $ldo->status,
				'version' => $ldo->version,
				'version_replaced' => 0
		));
		$history = $this->getLDOHistory($ldo, $to_version);
		if(count($history) == 0) return array();
		foreach($history as $i => $old){
			$histrecord[count($histrecord) -1]['created_by'] = $old['eurid'];
			$histrecord[count($histrecord) -1]['forward'] = $old['forward'];
			$histrecord[count($histrecord) -1]['backward'] = $old['backward'];
			$back_command = json_decode($old['backward'], true);
			if(!$ldo->update($back_command, "rollback", $ldo->isMultigraphUpdate($back_command))){
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
	function rollBackLDO(LDO &$ldo, $version){
		$history = $this->getLDOHistory($ldo, $version);
		foreach($history as $i => $old){
			if($old['from_version'] < $version){
				continue;
			}
			$back_command = json_decode($old['backward'], true);
			if(!$ldo->update($back_command, "rollback", $ldo->isMultigraphUpdate($back_command))){
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
	 * Rolls an ldo from whatever version it currently is to the specified version
	 * @param LDO $ldo linked data object to be rolled forward
	 * @param integer $version the version to which it will be rolled forward
	 * @return boolean|LDO - updated ldo is returned, or false on failure
	 */
	function rollForwardLDO(LDO &$ldo, $version = 0){
		$future = $this->getLDOFuture($ldo, $version);
		foreach($future as $i => $new){
			$forward_command = json_decode($new['forward'], true);
			if(!$ldo->update($forward_command, "update", $ldo->isMultigraphUpdate($forward_command))){
				return $this->failure_result($ldo->errmsg, $ldo->errcode);
			}
			$ldo->status = isset($ldo->meta['status']) ? $ldo->meta['status'] : $ldo->status;
			$ldo->version = $new['from_version'];
			$ldo->version_created = $new['modtime'];
			if($i == count($future) -1){
				$ldo->version_replaced = 0;
			}
			else {
				$ldo->version_replaced = $future[$i+1]['modtime'];
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
	
	/**
	 * Rolls an LDO forward from a particular version to the given version (or the latest version if omitted)
	 * @param LDO $ldo the linked data object (should be in an old version state
	 * @param number $version the version to roll forward to.
	 * @return boolean| false on failure or linked data object in its future state. 
	 */
	function getLDOFuture(LDO $ldo, $version = 0){
		if($version == 0) $version = $ldo->latest_version;
		$future = $this->dbman->loadLDOUpdateFuture($ldo, $version);
		if($future === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $future;		
	}
	
	/**
	 * Returns a particular ldo update object
	 * @param string $id update id
	 * @param array $options options array
	 * @return DacuraResult with the result containing the update representation 
	 */
	function getUpdate($id, $options = array()){
		$ar = new DacuraResult("Loading Update Request $id from DB", false);
		$ur = $this->loadUpdate($id, "view");
		if(!$ur){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$ar->add($this->policy->getPolicyDecision("view update", $ur));
		if($ar->is_accept()){
			$ar->set_result($ur);
		}
		return $ar;
	}	
	
	/**
	 * Loads update from database and applies it to the required ldo version 
	 * @param string $id update id
	 * @param string $editmode the mode in which this update is being loaded (update, rollback, replace, view)
	 * @param integer $vfrom the version of the object to apply the update to (default is current version)
	 * @param integer $vto the version of the object that the update created in the db (for accepted updates)
	 * @return boolean|LDOUpdate the ldo update object describing the update
	 */
	function loadUpdate($id, $editmode, $vfrom = false, $vto = false){
		if(!($eur = $this->dbman->loadLDOUpdateRequest($id))){
			return $this->failure_result("Failed to load update request id: ".$this->dbman->errmsg, $this->dbman->errcode);
		}
		$vto = $vto ? $vto : $eur->to_version();
		$vfrom = $vfrom ? $vfrom : $eur->from_version();
		if(!($orig = $this->loadLDO($eur->targetid, $eur->type, $eur->cid, false, $vfrom))){
			return $this->failure_result("Could not load original LDO: " .$this->errmsg, $this->errcode);
		}
		if(!$eur->calculate($orig)){
			return $this->failure_result($eur->errmsg, $eur->errcode);
		}
		return $eur;
	}
	
	/* 
	 * The next three functions must be overwritten by derived classes that want to provide graph-testing 
	 * into their publication pipeline
	 */
	
	/**
	 * Called to trigger any necessary Graph interactions when a new object is published (status = accept)
	 * @param array $nobj the new ldo to be published
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectPublished($nobj, $test_flag = false){
		$nopr = new GraphResult("Validating new object ". $nobj->id . " publication with DQS.", $test_flag);
		$nopr->msg("No DQS validation configured", "The linked data service does not use the DQS to validate updates");
		return $nopr;
	}
	
	/**
	 * Called to trigger any necessary Graph interactoins when an object is deleted (status != accept)
	 * @param array $nobj the object to be deleted
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectDeleted($nobj, $test_flag = false){
		$nopr = new GraphResult("Validating object ". $nobj->id . " deletion with DQS.", $test_flag);
		$nopr->msg("No DQS validation configured", "The linked data service does not use the DQS to validate updates");
		return $nopr->accept();
	}
	
	/**
	 * Called when an ldo is to be updated 
	 * @param LDOUpdate $uldo the update linked data object
	 * @param string $is_test true if this is just a test
	 */
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		$nopr = new GraphResult("Validating object ". $uldo->targetid. " update with DQS.", $test_flag);
		$nopr->msg("No DQS validation configured", "The linked data service does not use the DQS to validate updates");
		return $nopr->accept();
	}
	
	/**
	 * Called to undo an update to an ldo
	 * @param LDOUpdate $uldo the update linked data object
	 * @param string $is_test true if this is just a test
	 */
	function undoLDOUpdate(LDOUpdate $uldo, $is_test = false){
		$uldo->flip();
		return $this->objectUpdated($uldo, $is_test);		
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
		$uldob->original = $uldoa->changed;
		$uldob->calculateDelta();
		return $this->objectUpdated($uldob, $is_test);
	}
	
	/**
	 * Update a linked data object
	 * @param string $target_id the id of the ldo being updated 
	 * @param string $fragment_id the particular node id within the ldo that is being updated
	 * @param string $ldo_type the ld type of the ldo
	 * @param array $update_obj the contents of the update 
	 * @param array $update_meta meta-data about the update itself
	 * @param string $format the format of the contents (if known)
	 * @param string $editmode is this an update or a replace...
	 * @param number $version the version number that is being updated
	 * @param array $options options array 
	 * @param boolean $test_flag if true, this is just a test
	 * @return DacuraResult
	 */
	function updateLDO($target_id, $fragment_id, $ldo_type, $update_obj, $update_meta, $format, $editmode, $version, $options, $test_flag){
		$ar = new DacuraResult("Update $target_id", $test_flag);
		$oldo = $this->loadLDO($target_id, $ldo_type, $this->cid(), false, $version);
		if(!$oldo){
			if($this->errcode){
				return $ar->failure($this->errcode, "Failed to load $target_id", $this->errmsg);
			}
			else {
				return $ar->failure(404, "No such $this->ldo_type", "$target_id does not exist.");
			}
		}
		if(!($nldo = $this->createNewLDObject($target_id, $ldo_type, $oldo->cid()))){
			return $ar->failure($this->errcode, "Request Create Error", "Failed to create $ldo_type object ".$this->errmsg);
		}
		if($fragment_id){
			$nldo->fragment_id = $fragment_id;
		}
		if(!($format = $nldo->loadNewObjectFromAPI($update_obj, $format, $options, $this, $editmode))){
			return $ar->failure($nldo->errcode, "Protocol Error", "New $ldo_type object sent to API had formatting errors. ".$nldo->errmsg);
		}	
		if(!($nldo->validate($editmode, $this))){
			return $ar->failure($nldo->errcode, "Format Error", "$ldo_type $editmode sent to API had formatting errors. ".$nldo->errmsg);				
		}						
		$ldoupdate = new LDOUpdate(false, $oldo);
		if(!$ldoupdate->loadFromAPI($nldo, $update_meta, $editmode, $this)){
			return $ar->failure($ldoupdate->errcode, "Protocol Error", "Failed to load the update command from the API. ". $ldoupdate->errmsg);
		}
		if($ldoupdate->nodelta()){
			return $ar->failure(409, "No Changes", "The submitted version is identical to the current version.");
		}
		$ar->add($this->policy->getPolicyDecision("update", $ldoupdate));
		if($ar->is_reject()){
			$ldoupdate->status($ar->status());
			if($this->policy->storeRejected("update", $ldoupdate) && !$test_flag){
				if(!$this->dbman->updateLDO($ldoupdate)){
					$ar->addError($this->dbman->errcode, "Usage Monitoring", "Failed to store copy of rejected update.", $this->dbman->errmsg);
				}
			}
			return $ar;
		}
		$this->checkUpdate($ar, $ldoupdate, $options, $test_flag);
		if(($ar->is_accept() or $ar->is_pending()) && !$test_flag){
			if(!$this->dbman->updateLDO($ldoupdate)){
				$disaster = new DacuraResult("Database Synchronisation");
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
		if(($ar->is_accept() || $ar->is_pending()) && !$test_flag){
			//$cr->msg_body = "<b>".$cr->title()."</b> ".$cr->body();
			if(!$ar->is_accept()){
				$ar->msg_title = $ldoupdate->ldtype() ." $ldoupdate->targetid update accepted";
				$ar->msg_body = "Update has entered into deferred update queue, it must be approved before the ".$ldoupdate->ldtype()." will be updated. ". $ar->msg_body;
			}
			else {
				$ar->msg_title = $ldoupdate->ldtype() ." $ldoupdate->targetid updated";
				$ar->msg_body . "Update accepted and published. ".$ar->msg_body;
			}
		}
		elseif($ar->is_accept() && $test_flag){
			$ar->msg_title = $ldoupdate->ldtype() ." $ldoupdate->targetid update would be accepted and published";
		}
		elseif($ar->is_pending() && $test_flag && !$ar->msg_title){
			$ar->msg_title = $ldoupdate->ldtype() ." $ldoupdate->targetid update would enter update queue and must be approved before it is published";
		}
		return $ar;
	}
	
	/**
	 * Deletes an LDO by creating the appropriate update commands and calling the UpdateLDO function
	 * @param string $target_id id of the ldo to be deleted
	 * @param string $fragment_id the fragment id (for a fragment delete)
	 * @param string $ldo_type the ld type of the object 
	 * @param sring $format the format that the results should be returned in 
	 * @param array $options array of options which will be govern returned data
	 * @param boolean $test_flag if true this is only a test invocation
	 * @return DacuraResult
	 */
	function deleteLDO($target_id, $fragment_id, $ldo_type, $format, $options, $test_flag = false){
		if($fragment_id){
			$update_obj = array($fragment_id => array());
			return $this->updateLDO($target_id, $fragment_id, $ldo_type, $update_obj, array(), "json", "update", 0, $options, $test_flag);		
		}
		else {
			$update_obj = array("meta" => array("status" => "deleted"));
			return $this->updateLDO($target_id, $fragment_id, $ldo_type, $update_obj, array(), $format, "delete", 0, $options, $test_flag);
		}	
	}		
	
	/**
	 * Called to roll back a graph update because of db failure 
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
	function checkUpdate(DacuraResult &$ar, LDOUpdate &$uldo, $options = array(), $test_flag = false){
		//check version information
		if(!$uldo->original->isLatestVersion()){
			if($ar->is_accept() && $this->getServiceSetting("rollback_update_to_pending_on_version_reject")){
				$ar->status("pending");
				$ar->setWarning("Update check", "Update version clash", "The object you are updating has been updated to version ".$uldo->original->latest_version." since the version that you saw (".$uldo->original->version.")");
			}
			else {
				return $ar->failure(400, "Update version clash", "The object you are updating has been updated to version ".$uldo->original->latest_version." since the version that you saw (".$uldo->original->version.")");
			}			
		}
		if($ar->is_accept()){
			//unless the status of the candidate was accept, before or after, the change to the report graph is hypothetical
			$hypo = !($uldo->changedPublished() || $uldo->originalPublished());
			if($hypo && (!$this->getServiceSetting("test_unpublished", true))){
				return $ar;
			}
			$gu = $this->publishUpdateToGraph($uldo, $ar->status(), $hypo || $test_flag);
			$rb = (isset($options["rollback_update_to_pending_on_dqs_reject"]) && $options["rollback_update_to_pending_on_dqs_reject"]) || $this->getServiceSetting("rollback_update_to_pending_on_dqs_reject", false);
			if($ar->is_accept() && !$hypo && $gu->is_reject() && $rb){
				$ar->addGraphResult("dqs", $gu, $hypo || $test_flag, false);
				$ar->status("pending");
			}
			else {
				$ar->addGraphResult("dqs", $gu, $hypo || $test_flag, false);
			}
		}
		elseif($ar->is_pending() && $this->getServiceSetting("test_unpublished", true)){
			$hypo = true;
			$gu = $this->publishUpdateToGraph($uldo, "pending", $hypo);
			$ar->addGraphResult("dqs", $gu, $hypo, false);
		}
		else {
			$hypo = $test_flag || !$ar->is_accept();
		}
		$uldo->status($ar->status());
		if(isset($options['show_meta_triples']) && $options['show_meta_triples']){
			$ometau = array();
			$nmetau = array();
			foreach($uldo->original->meta as $k => $v){
				if(!isset($uldo->changed->meta[$k])){
					$ometau[$k] = $v;
					$nmetau[$k] = "";
				}
				elseif($v != $uldo->changed->meta[$k]){
					$ometau[$k] = $v;
					$nmetau[$k] = $uldo->changed->meta[$k];						
				}				
			}
			foreach($uldo->changed->meta as $k => $v){
				if(!isset($uldo->original->meta[$k])){
					$nmetau[$k] = $v;
					$ometau[$k] = "";
				}
			}			
			$ar->createGraphResult("meta", "Updates to " .$uldo->ldtype()."'s metadata", $ar->status(), $nmetau, $ometau, $test_flag);
		}
		if(isset($options['show_update_triples']) && $options['show_update_triples']){
			$msg = "Updates to update ".$uldo->id;
			$ar->createGraphResult("update", $msg, $ar->status(), $uldo->forward, $uldo->backward, $test_flag);
		}
		if(isset($options['show_ld_triples']) && $options['show_ld_triples']){
			$msg = "Updates to ".$uldo->ldtype()." ".$uldo->targetid;
			$delta = $uldo->compareLDTriples();
			$ar->createGraphResult("ld", $msg, $ar->status(), $delta->getInsertQuads(), $delta->getDeleteQuads(), $test_flag);
		}
	}
	
	/**
	 * Called when an update causes an update to an entities published form
	 * @param LDOUpdate $uldo - the ldo update in question
	 * @param string $decision - the status of the update
	 * @param boolean $testflag - true if this is just a test
	 * @return GraphResult
	 */
	function publishUpdateToGraph(LDOUpdate $uldo, $decision, $testflag ){
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
				$gu = new GraphResult("DQS Validation", $testflag);
				$gu->msg("No DQS validation", "As the ".$uldo->ldtype()." is not published, no dqs validation has taken place");
			}
		}
		return $gu;
	}
	
	/**
	 * Called to roll back the updates to the dqs graph 
	 * @param LDOUpdate $uldo the LDO Update object in question
	 * @return GraphResult
	 */
	function undoUpdatesToGraph(LDOUpdate $uldo){
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
	 * @param array $update_obj - json object received by api representing new update contents
	 * @param array $umeta - json object representing update meta
	 * @param string $format - format to return the contents
	 * @param string $editmode - replace|update
	 * @param array $options - options array 
	 * @param boolean $test_flag - if true this is only a test
	 * @return DacuraResult
	 */
	function updateUpdate($id, $update_obj, $umeta, $format, $editmode, $options, $test_flag = false){
		$ar = new GraphResult("Update update $id", $test_flag);
		if(!$orig_upd = $this->loadUpdate($id, $editmode)){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		if(!$new_upd = $this->loadUpdatedUpdate($orig_upd, $update_obj, $umeta, $format, $editmode, $options)){
			return $ar->failure($this->errcode, "Failed to load updated version of $id", $this->errmsg);
		}
		if($new_upd->sameAs($orig_upd)){
			return $ar->reject("No Changes", "The new update is identical to the existing update - it will be ignored.");
		}
		if(!$new_upd->isLegalContext($this->cid())){
			return $ar->failure(403, "Access Denied", "Cannot update candidate $new_upd->targetid through context ".$this->cid());
		}
		if($new_upd->nodelta()){
			return $ar->reject("No changes", "The update has no effect on the object.");
		}
		$ar->add($this->policy->getPolicyDecision("update update", array($orig_upd, $new_upd)));
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
		if(isset($options['show_result']) && $options['show_result']){
			if($options['show_result'] == 1){
				$ar->set_result($new_upd);
			}
			else {
				$ar->set_result($new_upd->changed);
			}
		}
		return $ar;
	}
	
	/**
	 * Deletes an LDO Update by creating the appropriate update command and calling the updateUpdate function
	 * @param string $updid update to be deleted
	 * @param string $format the format to return the results in.
	 * @param array $options the options array (for update)
	 * @param boolean $test_flag - if true, this is only a test.
	 * @return DacuraResult
	 */
	function deleteUpdate($updid, $format, $options, $test_flag = false){
		$umeta = array("status" => "deleted");
		return $this->updateUpdate($upid, false, $umeta, "json", "delete", $options, $test_flag);
	}
	
	/**
	 * Loads an update LDO Update from the database
	 * @param LDOUpdate $orig_upd the original update object
	 * @param array $obj json update object contents received from api
	 * @param array $umeta json update meta received from api
	 * @param array $options options array
	 * @return boolean|LDOUpdate update objecct 
	 */
	function loadUpdatedUpdate(LDOUpdate $orig_upd, $update_obj, $umeta, $format, $editmode, $options = array()){
		if(isset($umeta['from_version']) && $umeta['from_version'] && $umeta['from_version'] != $orig_upd->original->version){
			$norig = $this->loadLDO($orig_upd->targetid, $orig_upd->ldtype(), $this->cid(), false, $umeta['version']);
			if(!$norig)	return false;
		}
		elseif(!$this->APIObjectIncludesContents($update_obj) && !isset($update_obj["meta"])){ //no changes to update except its meta
			if(!$umeta){
				return $this->failure_result("Update to update " . $orig_upd->id." did not contain contents or metadata", 400);
			}
			$nupdate = clone $orig_upd;
			$nupdate->updateMeta($umeta, $editmode);
			$nupdate->modified = time();
			return $nupdate;
		}
		else {
			$norig = clone $orig_upd->original;
		}
		if(!($nldo = $this->createNewLDObject($orig_upd->targetid, $orig_upd->ldtype(), $norig->cid()))){
			return false;
		}
		if(!($format = $nldo->loadNewObjectFromAPI($update_obj, $format, $options, $this, $editmode))){
			return $this->failure_result("update to update sent to API had formatting errors. ".$nldo->errmsg, $nldo->errcode);
		}
		if(!($nldo->validate($editmode, $this))){
			return $this->failure_result("$editmode update sent to API had formatting errors. ".$nldo->errmsg, $nldo->errcode);
		}
		$ldoupdate = new LDOUpdate($orig_upd->id, $norig);
		if(!$ldoupdate->loadFromAPI($nldo, $umeta, $editmode, $this)){
			return $this->failure_result("Failed to load the update command from the API. ". $ldoupdate->errmsg, $ldoupdate->errcode);
		}
		return $ldoupdate;
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
	function checkUpdatedUpdate(DacuraResult &$ar, LDOUpdate $new_upd, LDOUpdate $orig_upd, $options, $test_flag){
		
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
		$capture_meta = isset($options['show_meta_triples']) && $options['show_meta_triples'];
		$capture_dqs = isset($options['show_dqs_triples']) && $options['show_dqs_triples'];
		$test_unpublished = $this->getServiceSetting("test_unpublished", true);
		
		$md = $capture_meta ? $meta_delta : false;
		if($capture_update){
			$msg = "Updates to update ".$new_upd->id;
			$mupdate = array("forward" => $new_upd->forward, "backward" => $new_upd->backward);
			$oupdate = array("forward" => $orig_upd->forward, "backward" => $orig_upd->backward);
			$ar->createGraphResult("update", $msg, $ar->status(), $mupdated, $oupdate, $test_flag);
		}
		if($new_upd->published() && $orig_upd->published()){ //live edit
			$umode = "live";
			if($capture_ld){
				$msg = "Updates to ".$new_upd->ldtype()." ".$new_upd->targetid;
				$trips = $new_upd->deltaAsTriples($orig_upd);
				$ar->createGraphResult("ld", $msg, $ar->status(), $trips['add'], $trips['del'], $test_flag);
			}
		}
		elseif($new_upd->published()){ //publish new update
			$umode = "publish";
			if($new_upd->from_version != $new_upd->original->latest_version){
				if($this->getServiceSetting("allow_updates_against_old_versions", true)){
					$casea = $new_upd->changed; //represents 
					$casea->version = $new_upd->from_version;
					if(!$this->rollForwardLDO($casea)){
						return $ar->failure(400, "Failed to apply the update to intervening updates", $casea->ldtype()." $casea->id was rolling forward from version $casea->version. ".$casea->errmsg);
						
					}
					$current = $this->loadLDO($new_upd->original->id, $new_upd->ldtype(), $new_upd->cid);
					if(!$current->update($new_upd->forward, "update", $current->isMultigraphUpdate($new_upd->forward))){
						return $ar->failure($current->errcode, "Failed to apply the update to the current version of ".$current->ldtype(), "$current->id is currently at version $current->version. ".$current->errmsg);
					}
					$delta = $casea->compare($current);
					if($delta->containsChanges()){
						return $ar->failure(400, "There are clashes between the update and intervening updates ", $current->ldtype() . " $current->id is currently at version $current->version. ".$current->errmsg);
					}
				}
				else { 
					return $ar->failure(400, "Publication of update $orig_upd->id failed", "The update was made to version ".$new_upd->from_version ." but the ".$orig_upd->ldtype()." is at version ".$new_upd->original->latest_version." you must update the update to the latest version to publish it");
				}				
			}
			if($capture_ld){
				$msg = "Updates to ".$new_upd->ldtype()." ".$new_upd->targetid;
				$ar->createGraphResult("ld", $msg, $ar->status(), $new_upd->addedLDTriples(), $new_upd->deletedLDTriples(), $test_flag);
			}
		}
		elseif($orig_upd->published()){ //unpublish update
			$umode = "rollback";
			if($this->getServiceSetting('pending_updates_prevent_rollback', false)){
				//check here to see if there are any pending updates that are hanging off the latest version....
				if($this->dbman->pendingUpdatesExist($orig_upd->targetid, $orig_upd->ldtype(), $this->cid(), $orig_upd->to_version()) || $this->dbman->errcode){
					if($this->dbman->errcode){
						return $ar->failure($this->dbman->errcode, "Unpublishing of update $orig_upd->id failed", "Failed to check for pending updates to current version of ".$orig_upd->ldtype());
					}
					return $ar->failure(400, "Unpublishing of update $orig_upd->id not allowed", "There are pending updates on version ".$orig_upd->to_version()." of ".$orig_upd->ldtype()." ".$orig_upd->targetid);
				}
			}
			if($capture_ld){
				$msg = "Updates to ".$new_upd->ldtype()." ".$new_upd->targetid;
				$ar->createGraphResult("ld", $msg, $ar->status(), $orig_upd->deletedQuads(), $orig_upd->addedQuads(), $test_flag);
			}
		}
		else { //edit unpublished
			if($capture_ld){
				$msg = "Updates to ".$new_upd->ldtype()." ".$new_upd->targetid. " (hypotethical - update is not published)";
				$ar->createGraphResult("ld", $msg, $ar->status(), $new_upd->addedLDTriples(), $new_upd->deletedLDTriples(), $test_flag, true);
			}
		}
		//in which cases do we call dqs?
		if($umode == "rollback"){
			$hypo = !($orig_upd->changedPublished() || $orig_upd->originalPublished());
		}
		else {
			$hypo = !($new_upd->changedPublished() || $new_upd->originalPublished());
		}
		if($test_flag or ($hypo and $test_unpublished)){
			$gu = $this->testUpdatedUpdate($new_upd, $orig_upd, $umode);
			$gu->setHypothetical($hypo);
		}
		elseif(!$hypo) {
			$gu = $this->saveUpdatedUpdate($new_upd, $orig_upd, $umode);
		}
		else {
			$gu = new GraphResult("no dqs tests");
			$gu->accept();
		}
		$ar->addGraphResult("dqs", $gu, $hypo);
		return $umode;
	}
	
	/**
	 * Translates testing an update to an update into calls to underlying functions 
	 * @param LDOUpdate $ncur new update object
	 * @param LDOUpdate $ocur old update object
	 * @param string $umode update mode - rollback, live, normal
	 * @return GraphResult
	 */
	function testUpdatedUpdate(LDOUpdate $ncur, LDOUpdate $ocur, $umode){
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
	function saveUpdatedUpdate(LDOUpdate $ncur, LDOUpdate $ocur, $umode){
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
	function updatedUpdate(LDOUpdate $cur, $umode, $testflag = false){
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
				$dont_publish = ($testflag || !$cur->originalPublished());
				return $this->objectPublished($cur->original, $dont_publish);
			}
			else {
				$dont_publish = ($testflag || !$cur->changedPublished());
				return $this->objectPublished($cur->changed, $dont_publish);
			}
		}
		else {
			$ar = new GraphResult("Nothing to save to report graph");
			return $ar;
		}
	}
	
	/* Methods for sending results to client */

	/**
	 * Marshalls a dacura LDO through http via a result object
	 * @param DacuraResult $ar
	 * @param string $format the format for display
	 * @param array $options options
	 * @return boolean true if successfully sent
	 */
	function sendRetrievedLDO(DacuraResult $ar, $format, $options){
		if(!$format) $format = "json";
		if($ar->is_error() or $ar->is_reject() or $ar->is_pending() or !$ar->result){
			$this->writeDecision($ar, $options);
		}
		else {
			if(!$this->sendLDO($ar->result, $format, $options)){
				$ar = new DacuraResult("export ldo");
				$ar->failure($this->errcode, "Failed to export data to $format ".$this->errmsg);
				$this->writeDecision($ar, $options);
			}
		}
	}

	/**
	 * Marshalls a dacura LDO Update through http
	 * @param DacuraResult $ar
	 * @param string $format the format for display
	 * @param array $options options
	 * @return boolean true if successfully sent
	 */
	function sendRetrievedUpdate(DacuraResult $ar, $format, $options){
		if(!$format) $format = "json";
		if($ar->is_error() or $ar->is_reject() or $ar->is_pending()){
			$this->writeDecision($ar, $options);
		}
		else {
			if(!$this->sendUpdate($ar->result, $format, $options)){
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
	 * @param array $opts options
	 * @return boolean true if successfully sent
	 */
	function sendLDO($ldo, $format, $opts){
		if(!isset(LDO::$valid_display_formats[$format])){
			return $this->failure_result("$format is not a valid display type", 400);				
		}
		if($ldo->getContentInFormat($format, $opts, $this, "display") !== false){
			$x = $ldo->forAPI($format, $opts);
			$x['type'] = "LDO";
			return $this->write_json_result($x, "Sent the object");
		}
		else {
			return $this->failure_result($ldo->errmsg, $ldo->errcode);
		}
	}
	
	/**
	 * prepares LDO Update for transmission over http
	 * @param DacuraResult $ar
	 * @param string $format the format for display
	 * @param array $opts options
	 * @return boolean true if successfully sent
	 */
	function sendUpdate($update, $format, $opts){
		if(!isset(LDO::$valid_display_formats[$format])){
			return $this->failure_result("$format is not a valid display type", 400);				
		}
		if($update->getContentInFormat($format, $opts, $this, "display")){
			$x = $update->forAPI($format, $opts);
			$x['type'] = "LDOUpdate";
			return $this->write_json_result($x, "Sent the update");
		}
		else {
			return $this->failure_result($update->errmsg, $update->errcode);
		}
	}
	
	/**
	 * Transforms a dacura result object into an appropriate http communication
	 * @param DacuraResult $ar the result object
	 * @param string $format the format for display
 	 * @param array $options options
	 * @return boolean true if successfully sent
	 */
	function writeDecision(DacuraResult $ar, $format = "json", $options = array()){
		if($ar->is_error()){
			http_response_code($ar->errcode);
			$this->logResult($ar->errcode, $ar->status()." : ".$ar->action);
		}
		elseif($ar->is_reject()){
			if($ar->errcode){
				http_response_code($ar->errcode);					
			}
			else {
				http_response_code(406);					
			}
			$this->logResult(406, $ar->status()." : ".$ar->action);
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
			opr($ar->forAPI($format, $options, $this));
			http_response_code(500);
			echo "JSON error in encoding dacura result: ".json_last_error() . " " . json_last_error_msg();
		}
	}
	
	/**
	 * Does the system support the given format?
	 * @param string $format the format?
	 * @param string $ftype "input" | display only search a particular type for context
	 */
	function supportsFormat($format, $ftype = false){
		if($ftype == "input"){
			return isset(LDO::$valid_input_formats[$format]);				
		}
		return isset(LDO::$valid_display_formats[$format]);
	}
	
	/**
	 * Retrieve a list of the available mimetypes supported by the system
	 * @return array - list of mime-types as strings
	 */
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

	/**
	 * Turns a mimetype into an internal dacura format id (rdfxml|turtle..) LDO::valid_display_types
	 * @param string $mtype the mimetype
	 * @return string|boolean the local id of the format if it exists
	 */
	function getFormatForMimeType($mtype){
		foreach(LDO::$format_mimetypes as $f => $fmtype){
			if($mtype == $fmtype) return $f;
		}
		return false;
	}
	
	/**
	 * Called to display the content of an LDO directly to the client, without html or any meta-data to clog it up:
	 * pure linked data
	 * @param LDO $ldo the LDO to be displayed
	 * @param string $format the format for it to be displayed in
	 * @param array $options the options for display
	 * @return boolean true on success
	 */
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
	 * 
	 * Checks db and policy to ensure that a given ldo id is valid
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
	
	
/*
 * These really belong to the candidate service 
 * They are required by the candidate class
 * They are put here instead of in CandidateDacuraServer to allow the ld service to work with candidates
 * If they weren't here, the ld service couldn't work with candidates - it needs a server with these functions
 * */
	
	/**
	 * Loads the graph configuration for the current context
	 * @return boolean true if success
	 */
	function readGraphConfiguration(){
		$filter = array(
			"type" => "graph",
			"collectionid" => $this->cid(),
			"include_all" => true,
		);
		if($active_graphs = $this->getLDOs($filter)){
			foreach($active_graphs as $gr){
				if($gr['status'] == "reject") continue;
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
	
	/**
	 * Returns a list of the named graph urls / ids that are valid in the current context
	 * @return array<string> array of graph urls
	 */
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

	/**
	 * Retrieve the URL of the default graph in this context
	 * @return string the graph url
	 */
	function getDefaultGraphURL(){
		return $this->service->get_service_url("graph")."/main";
	}
	
	/**
	 * Tests a passed string to see if it is the default graph url for the current context
	 * @param string $url the url in question
	 * @return boolean true if it is the default named graph url
	 */
	function isDefaultGraphURL($url){
		return $url == $this->getDefaultGraphURL();
	}
	
	/**
	 * Is the passed string the local id of a valid graph?
	 * @param string $gid the graph id (local id - not a url) 
	 */
	function validGraph($gid){
		return isset($this->graphs[$gid]);
	}
	
	/**
	 * Changes a graph url into a local graph id 
	 * @param string $gurl the url in question
	 * @return string|boolean the graph local id
	 */
	function graphURLToID($gurl){
		if(substr($gurl, 0, strlen($this->service->get_service_url("graph"))) == $this->service->get_service_url("graph")){
			return substr($gurl, strlen($this->service->get_service_url("graph"))+1);
		}
		return false;
	}
	
	/**
	 * The list of rdf:types that new candidate objects can be 
	 * @return array<string> list of all the valid rdf:types for new candidates
	 */
	function getValidCandidateTypes(){
		return array();
	}
	
	/**
	 * Figure out what collection a given ontology belongs to - the universal scope or the local collection?
	 * @param string $id the ontology id
	 * @return string the id of the collection 
	 */
	function getOntologyCollection($id){
		if($this->dbman->hasLDO($id, "ontology", "all")){
			return "all";
		}
		return $this->cid();
	}	
}
