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
	function setLDRules(&$srvr){
		parent::setLDRules($srvr);
		$req = $srvr->getServiceSetting("require_candidate_type", false);
		//$this->rules->setRule("import", "transform_import", false);
		$this->rules->setRule("update", 'expand_embedded_objects', false);		
		$this->rules->setRule("validate", "require_candidate_type", $req);
		if($req){ 
			if($srvr->valid_candidate_types){		
				$this->rules->setRule("validate", "valid_candidate_types", array_keys($srvr->valid_candidate_types)); 
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
	function importLD($mode, $srvr){
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
			return true;
		}
		else {
			return false;
		}
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
			if($this->is_multigraph() && isset($this->ldprops[$default_graph])){
				$target =& $this->ldprops[$default_graph];
				if(!(count($target) == 1 && isset($target[$this->cwurl]))){
					$target = array($this->cwurl => $target);
				}
			}
			elseif(!$this->is_multigraph()) {
				if(!(count($this->ldprops) == 1 && isset($this->ldprops[$this->cwurl]))){
					$this->ldprops = array($this->cwurl => $this->ldprops);
				}
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
	function validate($mode, LdDacuraServer $srvr){
		if(!parent::validate($mode, $srvr)){
			return false;
		}
		if(!$this->fragment_id){
			if($this->ldprops || ($mode != "replace" && $mode != "update")){
				$t = $this->getRDFType(false);
				if((!$t && $mode != "update") && $this->rule($mode, "validate", 'require_candidate_type')){
					return $this->failure_result("No rdf:type specified in input candidate", 400);
				}
				if($t && ($valids = $this->rule($mode, "validate", 'valid_candidate_types')) && !in_array($t, $valids)){
					return $this->failure_result("$t is not a valid rdf:type for candidates in ".$srvr->cid(), 400);
				}
			}
		}
		return true;
	}
	
	/**
	 * The meta-data fields that can be set in candidates 
	 * @see LDO::getValidMetaProperties()
	 */
	function getValidMetaProperties(){
		return array("status", "title", "image", "type");
	}
	
	/**
	 * The standard meta-data properties that cannot be set in candidates (ignored in updates) 
	 * type is added to the regular ld properties (as type is set in the ldprops with rdf:type declarations)
	 * @see LDO::getStandardProperties()
	 */
	function getStandardProperties(){
		$props = parent::getStandardProperties();
		$props[] = "type";
		return $props;
	}
	
	/**
	 * Retrieves the rdf:type of the candidate from its contents and copies it into its meta field
	 * @return string - url of the rdf:type or false if non-existent
	 */
	function getRDFType($store = true){
		$clsurls = $this->getPredicateValues($this->cwurl, "type", "rdf", $this->getDefaultGraphURL());
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
	function getContentInFormat($format, $options, $srvr, $for){
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