<?php
require_once("MultiGraphLDO.php");
/**
 * An object that represents a chunk of instance data in Dacura
 * @author chekov
 * @license GPL V2
 */
class Candidate extends MultiGraphLDO {
	/** array<gid:Graph> an array of the active graphs that are available to candidates **/
	var $graphs;
	/** array<string> an array of all of the valid rdf:types that a candidate may take **/
	var $valid_types;

	/**
	 * Adds two new rules to the standard linked data validation rules
	 * * require_candidate_type - if true, a rdf:type must be present in all candidates
	 * * valid_candidate_types - an array of valid rdf:types for candidates
	 * @see LDO::setLDRules()
	 */
	function setLDRules(LdDacuraServer &$srvr){
		parent::setLDRules($srvr);
		$req = $srvr->getServiceSetting("require_candidate_type", false);
		//$this->rules->setRule("import", "transform_import", false);
		$this->rules->setRule("update", 'regularise_literals', true);
		$this->rules->setRule("import", 'regularise_literals', true);
		$this->rules->setRule("import", 'require_object_literals', true);
		$this->rules->setRule("import", 'expand_embedded_objects', true);
		$this->rules->setRule("validate", "require_candidate_type", $req);
		if($req){
			if($srvr->valid_candidate_types){
				if(count($srvr->graphs) > 1){
					$valids = array();
					foreach($srvr->valid_candidate_types as $gid => $valid){
						$valids = array_merge($valids, array_keys($valid));
					}
					$this->rules->setRule("validate", "valid_candidate_types", $valids);
				}
				else {
					$this->rules->setRule("validate", "valid_candidate_types", array_keys($srvr->valid_candidate_types));
				}
			}
		}
	}

	/**
	 * Called immediately after loading the candidate from the api prior to validation
	 * Loads the candidates graph configuration and valid types
	 * Also sets the type of the candidate from the imported content
	 * (non-PHPdoc)
	 * @see LDO::importLD()
	 */
	function importLD($mode, LdDacuraServer &$srvr){
		if(!$srvr->graphs){
			$srvr->readGraphConfiguration();
		}
		if(isset($srvr->valid_candidate_types) && $srvr->valid_candidate_types) {
			$this->valid_types =& $srvr->valid_candidate_types;
		}
		if(parent::importLD($mode, $srvr)){
			//we want to fetch the rdf type when it is present in updates and when it is
			if($this->meta || $mode == "create"){
				$this->getRDFType();
			}
			if($this->meta || $mode == "create"){
				if($lab = $this->getRDFSLabel()){
					$this->meta['label'] = $lab;
				}
			}
			return true;
		}
		else {
			return false;
		}
	}

	function getChangesToCopyToMeta(LDDelta $delt, $multidefault = false){
		if($multidefault){
			if(!isset($delt->forward[$multidefault]) || !isset($delt->forward[$multidefault][$this->cwurl])){
				return false;
			}
			$cnts = $delt->forward[$multidefault][$this->cwurl];
		}
		else {
			if(!isset($delt->forward[$this->cwurl])){
				return false;
			}
			$cnts = $delt->forward[$this->cwurl];
		}
		$cmets = array();
		if(isset($cnts[$this->nsres->expand("rdf:type")])){
			$type = $cnts[$this->nsres->expand("rdf:type")];
			if(is_array($type) && !isAssoc($type)){
				$type = $type[0];
			}
			if(!is_array($type)){
				$cmets['type'] = $cnts[$this->nsres->expand("rdf:type")];
			}
		}
		if(isset($cnts[$this->nsres->expand("rdfs:label")])){
			$lab = $cnts[$this->nsres->expand("rdfs:label")];
			if(is_array($lab) && !isAssoc($lab)){
				$lab = $lab[0];
			}
			if(isset($lab['data'])){
				$cmets['label'] = $lab['data'];
			}
		}
		return $cmets;
	}


	/**
	 * Fixes the incoming dacura json to allow the submission of object without id to default graph
	 *
	 * This function wraps the default graph content into an object indexed by the candidate cwurl / id
	 * (non-PHPdoc)
	 * @see LDO::importFromDacuraJSON()
	 */
	function importFromDacuraJSON($json, $default_graph, $graph_urls = false){
		parent::importFromDacuraJSON($json, $default_graph, $graph_urls);
		if(!$this->fragment_id){
			$target = false;
			if($this->is_multigraph() && isset($this->ldprops[$default_graph]) && $this->ldprops[$default_graph]){
				$target =& $this->ldprops[$default_graph];
			}
			elseif(!$this->is_multigraph() && $this->ldprops) {
				$target =& $this->ldprops;
			}
			if($target && isset($target["_:"])){
				if(isset($target[$this->cwurl])){
					$target[$this->cwurl] = array_merge($target[$this->cwurl], $target["_:"]);
				}
				else {
					$target[$this->cwurl] = $target["_:"];
				}
				unset($target["_:"]);
			}
			if($target && !(count($target) == 1 && isset($target[$this->cwurl]))){
				$target = array($this->cwurl => $target);
			}
		}
	}

	/**
	 * Called immediately after a candidate is loaded from the DB
	 *
	 * Transforms the ldprops array to ensure that the representation shown to the user is
	 * consistently single graph or multi-graph
	 * (non-PHPdoc)
	 * @see LDO::deserialise()
	 */
	function deserialise(LdDacuraServer &$srvr){
		parent::deserialise($srvr);
		if(isset($srvr->valid_candidate_types) && $srvr->valid_candidate_types) {
			$this->valid_types =& $srvr->valid_candidate_types;
		}
		if(!$srvr->graphs){
			$srvr->readGraphConfiguration();
		}
		return true;
	}

	/**
	 * Validates that the candidate meets the Linked data rules that apply to it.
	 *
	 * Adds rules requiring a candidate type and a valid candidate type to ld validation rules
	 * @param $mode the mode in which it is validated (may be update, replace, create, view)
	 * @param LdDacuraServer $srvr the server object for access to its services
	 * @see LDO::validate()
	 */
	function validate($mode, LdDacuraServer &$srvr){
		if(!parent::validate($mode, $srvr)){
			return false;
		}
		if(!$this->fragment_id){
			if($this->hasContents() || ($mode != "replace" && $mode != "update")){
				$t = $this->getRDFType(false);
				if((!$t && $mode != "update") && $this->rule($mode, "validate", 'require_candidate_type')){
					return $this->failure_result("No rdf:type specified in input candidate $mode", 400);
				}
				if($t && ($valids = $this->rule($mode, "validate", 'valid_candidate_types')) && !in_array($t, $valids)){
					return $this->failure_result("$t is not a valid rdf:type for candidates in ".$srvr->cid(), 400);
				}
			}
		}
		return true;
	}

	function hasContents(){
		return !$this->isEmpty();
	}

	/**
	 * The meta-data fields that can be set in candidates
	 * @see LDO::getValidMetaProperties()
	 */
	function getValidMetaProperties(){
		return array("status", "label", "image", "type");
	}

	/**
	 * The standard meta-data properties that cannot be set in candidates (ignored in updates)
	 * type and label added to the regular ld properties (as type is set in the ldprops with rdf:type / rdfs:label declarations)
	 * @see LDO::getStandardProperties()
	 */
	function getStandardProperties(){
		$props = parent::getStandardProperties();
		$props[] = "type";
		$props[] = "label";
		return $props;
	}

	/**
	 * Retrieves the rdf:type of the candidate from its contents and copies it into its meta field
	 * @return string - url of the rdf:type or false if non-existent
	 */
	function getRDFType($store = true){
		$clsurls = $this->getPredicateValues("rdf:type", $this->cwurl, $this->getDefaultGraphURL());
		if($clsurls){
			if(is_array($clsurls) && !isAssoc($clsurls)){
				$clsurls = $clsurls[0];
			}
			elseif(is_array($clsurls)){
				return false;
			}
			//$clsurls = $this->nsres->compress($clsurls) ? $this->nsres->compress($clsurls) : $clsurls;
			if($store) $this->meta['type'] = $clsurls;
		}
		return $clsurls;
	}

	/**
	 * Retrieves the rdfs:label of the candidate from its contents
	 * @return string - label text or false if non-existent
	 */
	function getRDFSLabel(){
		$labs = $this->getPredicateValues("rdfs:label", $this->cwurl, $this->getDefaultGraphURL());
		if($labs){
			if(is_array($labs) && !isAssoc($labs)){
				$labs = $labs[0];
			}
			if(is_array($labs) && isset($labs['data'])){
				return $labs['data'];
			}
		}
		return false;
	}


	/**
	 * The list of properties that candidates must have in their meta-data
	 * @see LDO::getRequiredMetaProperties()
	 */
	function getRequiredMetaProperties(){
		return array();
	}

	/**
	 * Retrieves the list of urls of valid graphs that are available for instance data to be written to
	 * (non-PHPdoc)
	 * @see LDO::getValidGraphURLs()
	 */
	function getValidGraphURLs(){
		$urls = array();
		if(isset($this->graphs)){
			foreach($this->graphs as $gid => $graph){
				$urls[] = $graph->instanceGname();
			}
		}
		return $urls;
	}

	/**
	 * Returns the candidate as a set of quads, with the appropriate graphnames for the candidate context
	 * @param string $graphid - the url of the graph for which quads are required - false for all graphs
	 *
	 * @see LDO::typedQuads()
	 */
	function typedQuads($graphid = false){
		if($this->is_multigraph()){
			if($graphid && isset($this->ldprops[$graphid])){
				return getPropsAsTypedQuads($graphid, $this->ldprops[$graphid], $this->cwurl);
			}
			elseif($graphid === false){
				$quads = array();
				foreach($this->ldprops as $gid => $gbits){
					$quads = array_merge($quads, getPropsAsTypedQuads($gid, $gbits, $this->cwurl));
				}
				return $quads;
			}
		}
		else {
			return parent::typedQuads($graphid);
		}
	}

	/**
	 * Returns the content in a particular format - if the format is html, a frame representation is returned
	 * @see LDO::getContentInFormat()
	 */
	function getContentInFormat($format, $options, $srvr = NULL, $for = 'internal'){
		if($format == 'html' && $this->meta['type'] && !$srvr->isBaseLDServer()){
			$ar = $srvr->getFrame($this->meta['type']);
			if($ar->is_accept()){
				$this->display = $ar->result;
				return $this->display;
			}
		}
		return parent::getContentInFormat($format, $options, $srvr, $for);
	}

}