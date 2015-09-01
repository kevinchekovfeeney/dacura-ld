<?php
include_once("phplib/db/LDDBManager.php");
include_once("phplib/LD/Schema.php");
include_once("phplib/LD/Candidate.php");
include_once("phplib/LD/CandidateCreateRequest.php");
include_once("phplib/LD/CandidateUpdateRequest.php");
include_once("phplib/LD/GraphManager.php");
require_once("phplib/LD/AnalysisResults.php");
require_once("phplib/LD/NSResolver.php");
include_once("phplib/PolicyEngine.php");



class LDDacuraServer extends DacuraServer {

	var $dbclass = "LDDBManager";
	var $schema; //the schema in use is defined by the context.
	var $policy; //policy engine to decide what to do with incoming requests
	var $graphman; //graph manager object
	var $entity_type; //candidate, schema, graph, ontology
	var $cwurlbase = false;

	function __construct($service){
		parent::__construct($service);
		$this->policy = new PolicyEngine();
		$this->graphman = new GraphManager($this->settings);
	}
	
	function loadSchema(){
		$this->schema = new Schema($this->cid(), $this->did(), $this->settings['install_url']);
		if($this->dbman->load_schema($this->schema)){
			return true;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getEntity($entity_id, $fragment_id = false, $version = false, $options = array()){
		$ar = new RequestAnalysisResults("Fetching $this->entity_type $entity_id $fragment_id");
		$ent = $this->loadEntity($entity_id, $fragment_id, $version, $options);
		if(!$ent){
			return $ar->failure($this->errcode, "Error loading entity ", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view", $this->entity_type, $ent));
		if($ar->is_accept()){
			$ar->set_result($ent);
		}
		return $ar;
	}
	
	function getUpdate($id, $options = array()){
		$ar = new RequestAnalysisResults("Loading Update Request $id from DB", false);
		$ur = $this->loadUpdateFromDB($id);
		if(!$ur){
			return $ar->failure($this->errcode, "Failed to load Update $id", $this->errmsg);
		}
		$ar->add($this->getPolicyDecision("view update", $this->entity_type, $ur));
		if($ar->is_accept()){
			$ar->set_result($ur);
		}
		return $ar;
	}
	
	/*
	 * Does basic validity and policy checking of LD create requests
	 */
	function checkCreateRequest($obj, $demand_id){
		$ar = new RequestAnalysisResults("Creating $this->entity_type");
		if($demand_id){
			if($this->demandIDValid($demand_id)){
				$id = $this->generateNewEntityID($demand_id);
			}
			else {
				$id = $this->generateNewEntityID();
				$txt = "Requested ID $demand_id could not be granted (".$this->errmsg.").";
				$extra = "";
				if($test_flag){
					$txt = "An ID will be randomly generated when the $this->entity_type is created.";
					$extra = "$id is an example of a randomly generated ID, it will be replaced by another if the $this->entity_type is created";
				}
				else {
					$txt = "The $this->entity_type was allocated a randomly generated ID: $id";
				}
				$ar->addWarning("Generating $this->entity_type id", $txt, $extra);
			}
		}
		else {
			$id = $this->generateNewEntityID();
		}
		$nent = $this->createNewEntityObject($id);
		$nent->setContext($this->cid(), $this->did());
		if(!$nent->loadFromAPI($obj)){
			return $ar->failure($nent->errcode, "Protocol Error", "New $this->entity_type object sent to API had formatting errors. ".$nent->errmsg);
		}
		elseif(!$nent->validate()){
			return $ar->failure($nent->errcode, "Invalid create $this->entity_type request", "The create request contained errors: ".$nent->errmsg);
		}
		elseif(!$nent->expand($this->policy->demandIDAllowed("create", $this->entity_type, $nent))){
			return $ar->failure($nent->errcode, "Invalid Create Request", $nent->errmsg);
		}
		$nent->expandNS();//use fully expanded urls internally - support prefixes in input
		return $ar->add($this->getPolicyDecision("create", $this->entity_type, $nent));
	}
	
	
	/*
	 * Does basic validity and policy checking of LD update requests
	 * and creates and loads the necessary objects (returned in result field of RAR object)
	 */
	function checkUpdateRequest($target_id, $obj, $fragment_id){
		$ar = new RequestAnalysisResults("Update $this->entity_type");
		$oent = $this->loadEntity($target_id, $fragment_id);
		if(!$oent){
			if($this->errcode){
				return $ar->failure($this->errcode, "Failed to load $this->entity_type $target_id", $this->errmsg);
			}
			else {
				return $ar->failure(404, "No such $this->entity_type", "$target_id does not exist.");
			}
		}
		$uclass = ucfirst($this->entity_type)."UpdateRequest";
		$uent = new $uclass(false, $oent);
		//is this entity being accessed through a legal collection / dataset context?
		if(!$uent->isLegalContext($this->cid(), $this->did())){
			return $ar->failure(403, "Access Denied", "Cannot update $this->entity_type $oent->id through context ".$this->cid()."/".$this->did());
		}
		elseif(!$uent->loadFromAPI($obj)){
			return $ar->failure($uent->errcode, "Protocol Error", "Failed to load the update candidate from the API. ".$uent->errmsg);
		}
		else {
			if(!$uent->calculateChanged(array("demand_id_allowed" => $this->policy->demandIDAllowed("update", $this->entity_type, $uent)))){
				return $ar->failure($uent->errcode, "Update Error", "Failed to apply updates ".$uent->errmsg);
			}				
		}
		if($uent->calculateDelta()){
			$ar->set_result($uent);			
			if($uent->nodelta()){
				return $ar->reject("No Changes", "The submitted $this->entity_type version is identical to the current version.");
			}
		}
		else {
			return $ar->reject($uent->errcode, "Error in calculating change", $uent->errmsg);
		}
		return $ar;		
	}
	
	function updateUpdate(){}
	
	function loadEntity($entity_id, $fragment_id = false, $version = false, $options = array()){
		if($this->entity_type == "ontology"){
			$ent = $this->schema->loadOntology($entity_id, $version);
			if(!$ent){
				return $this->failure_result("Failed to load ontology $entity_id. ".$this->schema->errmsg, $this->schema->errcode);
			}	
		}
		else {
			$ctype = ucfirst($this->entity_type);
			$ent = new $ctype($entity_id, $this->cwurlbase);
			if(!$this->dbman->load_entity($ent, $this->entity_type, $options)){
				return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
			}
			if($version && $ent->version() > $version){
				if(!$this->rollBackEntity($ent, $version)){
					return false;
				}
			}
		}
		$show_context = true;//should be in options
		$ent->readStateFromMeta();
		$ent->setNamespaces($this->schema->getNSResolver());
		if($fragment_id){
			$ent->buildIndex();
			if($this->cwurlbase){
				$fid = $this->cwurlbase.$entity_id."/".$fragment_id;
			}
			else {
				$fid = $fragment_id;
			}
			$frag = $ent->getFragment($fid);
			$ent->fragment_id = $fid;
			if($frag && $show_context){
				$ent->setContentsToFragment($fid);
				$types = array();
				foreach($frag as $fobj){
					if(isset($fobj['rdf:type'])){
						$types[] = $fobj['rdf:type'];
					}
				}
				$ent->fragment_paths = $ent->getFragmentPaths($fid);
				$ent->fragment_details = count($types) == 0 ? "Undefined Type" : "Types: ".implode(", ", $types);
			}
			else {
				if($frag){
					$ent->ldprops = $frag;
				}
				else {
					return $this->failure_result("Failed to load fragment $fid", 404);
				}
			}
		}
		return $ent;
	}
	
	function loadUpdateFromDB($id, $vfrom = false, $vto = false){
		if($this->entity_type == "candidate"){
			$cur = new CandidateUpdateRequest($id);
			if(!$this->dbman->loadCandidateUpdateRequest($cur)){
				return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
			}
		}
		else {
		}
		$vto = $vto ? $vto : $cur->to_version();
		$vfrom = $vfrom ? $vfrom : $cur->from_version();
		$orig = $this->loadEntity($cur->targetid, false, $vfrom);
		if(!$orig){
			return $this->failure_result("Failed to load Update $id - could not load original " .$this->errmsg, $this->errcode);
		}
		$cur->setOriginal($orig);
		$changed = false;
		if($vto > 0){
			$changed = $this->loadEntity($cur->targetid, false, $vto);
			if(!$changed){
				return $this->failure_result("Loading of $this->entity_type update $id failed - could not load changed ".$this->errmsg, $this->errcode);
			}
		}
		if(!$cur->calculate($changed)){
			//opr($cur);
			return $this->failure_result($cur->errmsg, $cur->errcode);
		}
		return $cur;				
	}
	
	function store_new($nent, $ar){
		//this has to be implemented by the underlying server -> its where the complexity is!
	}
	
	
	function store_update($uent, $ar){
		return $ar;
		//this has to be implemented by the underlying server -> its where the complexity is!
	}
	
	function rollBackEntity(&$ent, $version){
		$history = $this->getEntityHistory($ent, $version);
		foreach($history as $i => $old){
			if($old['from_version'] < $version){
				continue;
			}
			$back_command = json_decode($old['backward'], true);
			if(!$ent->update($back_command, true)){
				return $this->failure_result($ent->errmsg, $ent->errcode);
			}
			$ent->version = $old['from_version'];
			$ent->modified = $old['modtime'];
			if($i == 0){
				$ent->replaced = 0;
			}
			else {
				$ent->replaced = $history[$i-1]['modtime'];
			}
		}
		return $ent;
	}
	
	function getPendingUpdates($ent){
		$updates = $this->dbman->get_relevant_updates($ent, $this->entity_type);
		return $updates ? $updates : $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	function getEntities($filter){
		$data = $this->dbman->get_entity_list($filter, $this->entity_type);
		if(!$data){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		return $data;
	}
	
	function getUpdates($filter){
		$data = $this->dbman->get_entity_updates_list($filter, $this->entity_type);
		if($data){
			return $data;
		}
		return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
	}
	
	/*
	 * Helper for policy object
	 */
	function getPolicyDecision($action, $ent_type, $args){
		return $this->policy->getPolicyDecision($action, $ent_type, $args);
	}
	
	/*
	 * Output
	 */
	
	function send_retrieved_update($ar, $format, $display, $options, $version){
		if($ar->is_error() or $ar->is_reject() or $ar->is_confirm() or $ar->is_pending()){
			$this->write_decision($ar);
		}
		else {
			$this->send_update($ar->result, $format, $display, $version);
		}
	}
	
	function send_retrieved_entity($ar, $format, $display, $options, $version){
		//opr($ar);
		if($ar->is_error() or $ar->is_reject() or $ar->is_pending()){
			$this->write_decision($ar);
		}
		else {
			if(!$this->send_entity($ar->result, $format, $display, $version)){
				$ar = new AnalysisResults("export candidate");
				$ar->failure($this->errcode, "Failed to export data to $format", $this->errmsg);
				$this->write_decision($ar);
			}
		}
	}
	
	function send_update($update, $format, $display, $version = false){
		$flags = $this->parseDisplayFlags($display);
		return $this->write_json_result($update->showUpdateResult($format, $flags, $display, $this), "$this->entity_type update ".$update->id." dispatched");		
	}
	
	function isNativeFormat($format){
		return $format == "" or in_array($format, array("json", "html", "triples", "quads"));
	}
	
	function send_entity($ent, $format, $display, $version){
		$vstr = "?version=".$version."&format=".$format."&display=".$display;
		$opts = $this->parseDisplayFlags($display);
		if($this->isNativeFormat($format)){
			if(in_array('ns', $opts)) {
				$ent->compressNS();
			}
			if($format == "html"){
				$ent->displayHTML($opts, $vstr, $this);
			}
			elseif($format == "triples"){
				$ent->displayTriples($opts, $vstr, $this);
			}
			elseif($format == "quads"){
				$ent->displayQuads($opts, $vstr, $this);				
			}
			else{
				$ent->displayJSON($opts, $vstr, $this);
			}
		}
		else {
			$ent->displayExport($format, $opts, $vstr, $this);
		}
		return $this->write_json_result($ent, "Sent the candidate");
	}
	
	/*
	 * Helper methods for dealing with display stuff
	 */
	function parseDisplayFlags($display){
		$opts = explode("_", $display);
		return $opts;
	}
	
	function getEntityHistory($ent, $version){
		$history = $this->dbman->get_candidate_update_history($ent, $version);
		if($history === false){
			return $this->failure_result($this->dbman->errmsg, $this->dbman->errcode);
		}
		if($version == 1 && count($history) > 0){
			//$initial_cand = $this->rollBackCandidate($cand, 1);
			$history[] = array(
					'from_version' => 0,
					"to_version" => 1,
					"modtime" => $ent->created,
					"createtime" => $ent->created,
					"schema_version" => $ent->type_version,
					"backward" => "{}",
					"forward" => "create"
			);
		}
		return $history;
		
		if($this->entity_type == "candidate"){
			return $this->dbman->get_candidate_update_history($ent, $version);
		}
	}

	
	//loadEntityFromDB
	//loadUpdateFromDB($id)
	//getPolicyDecision
	//getEntityHistory
}