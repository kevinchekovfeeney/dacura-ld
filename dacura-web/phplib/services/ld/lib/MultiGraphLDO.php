<?php 
/**
 * Class extends Linked data objects to include dealing with objects that span multiple graphs
 * 
 * The general rule is that, if the object spans multiple graphs, the ldprops will take the form: 
 * {gid -> props, gid2: props }
 * If the object only exists on the default graph it is identical to a simple ldo
 * @author chekov
 *
 */
class MultiGraphLDO extends LDO {
	
	/**
	 * Called after db loading - extends ldo by calling importfromdacurajson to normalise graph structure
	 * @see LDO::deserialise()
	 */
	function deserialise(LdDacuraServer &$srvr){
		$this->importFromDacuraJSON($this->ldprops, $this->getDefaultGraphURL(), $this->getValidGraphURLs());
	}
	
	/**
	 * Builds an index which spans multiple graphs of the object id => [values]
	 */
	function buildIndex(){
		if(!$this->is_multigraph()){
			return parent::buildIndex();
		}
		$this->index = array();
		foreach($this->ldprops as $gid => $props){
			$this->index[$gid] = array();
			if($props){
				indexLDProps($this->ldprops[$gid], $this->index[$gid], $this->cwurl);
			}
		}
	}	
	
	/**
	 * Extends the set of standard ldo meta properties by adding in graph information for multi-graph objects
	 * @see LDO::getPropertiesAsArray()
	 */
	function getPropertiesAsArray(){
		$props = parent::getPropertiesAsArray();
		if($this->multigraph){
			$props['multigraph'] = 1;
		}
		$props['default_graph'] = $this->getDefaultGraphURL();
		$props['available_graphs'] = $this->getValidGraphURLs();
		return $props;
	}
	
	/**
	 * The set of 'special' metadata fields that can be ignored in input - not directly settable through the api
	 * @see LDO::getStandardProperties()
	 */
	function getStandardProperties(){
		$props = parent::getStandardProperties();
		$props[] = "multigraph";
		$props[] = "default_graph";
		$props[] = "available_graphs";
		return $props;
	}

	/**
	 * Returns a list of the bad links in the object
	 */
	function findMissingLinks(){
		if(!$this->is_multigraph()){
			return parent::findMissingLinks();
		}
		if($this->index === false){
			$this->buildIndex();
		}
		$missing = array();
		foreach($this->ldprops as $gid => $ldprops){
			foreach($ldprops as $s => $ldo){
				$missing = array_merge($missing, findInternalMissingLinks($ldo, array_keys($this->index), $this->id, $this->cwurl));
			}
		}
		$this->bad_links = $missing;
		return $missing;
	}
	
	/**
	 * ValidateLD is called before an object is accepted into the linked data object store to validate
	 * the basic structure of the linked data contents
	 *
	 * It should only ever catch errors that are catastrophic to the
	 * structure / function of the object and should never be stored
	 *
	 * In general, we want to catch validation errors at the graph / dqs analysis stage because when objects fail those tests,
	 * they can still be saved in the linked data object store and iteratively updated.
	 * @return boolean true if valid
	 */
	function validateLD($mode, &$srvr){
		if(!$this->is_multigraph()){
			return parent::validateLD($mode, $srvr);
		}
		foreach($this->ldprops as $gid => $props){
			if(!$this->validateLDProps($props, $mode, $srvr)){
				$this->errmsg = "Failed validation for graph $gid ".$this->errmsg;
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Does the obect have unique ids for internal subjects (i.e. do node ids appear only once as a subject)
	 * @return boolean
	 */
	function subjectIDsUnique($gid = false){
		if(!$this->is_multigraph()){
			return parent::subjectIDsUnique($gid);
		}
		if($this->index === false){
			$this->buildIndex();
		}
		foreach($this->index as $g => $props){
			if($gid && $g != $gid) continue;
			foreach($props as $nid => $vals){
				if(count($vals) != 1){
					return $this->failure_result("node $nid appears ".count($vals) ." times", 400);
				}
			}
		}
		return true;
	}

	/**
	 * Consolidates and normalises data fed to api
	 * @see LDO::importFromDacuraJSON()
	 */
	function importFromDacuraJSON($json, $default_graph, $graph_urls = false){
		$this->ldprops = $this->getConsolidatedMultiGraphContents($json, $default_graph, $graph_urls);
		$this->normaliseMultigraphImport($default_graph);
	}
	
	/**
	 * imports and normalises data fed to api
	 * @see LDO::importFromDacuraJSON()
	 */
	function importFromQuads($quads, $default_graph){
		$this->ldprops = getPropsFromQuads($quads);
		$this->normaliseMultigraphImport($default_graph);
	}
	
	/**
	 * Imports and normalises json ld input data
	 * @see LDO::importFromJSONLD()
	 */
	function importFromJSONLD($json, $default_graph){
		require_once("JSONLD.php");
		$this->ldprops = fromJSONLD($json);
		if(isset($this->ldprops['multigraph'])){
			$this->normaliseMultigraphImport($default_graph);
			unset($this->ldprops['multigraph']);
		}
	}
	
	/**
	 * Imports and normalises nquad input data
	 * @see LDO::importContentsfromNQuads()
	 */
	function importContentsfromNQuads($contents, $default_graph){
		require_once("JSONLD.php");
		$this->ldprops = fromNQuads($contents);
		if(!$this->ldprops){
			return false;
		}
		if(isset($this->ldprops['multigraph'])){
			$this->normaliseMultigraphImport($default_graph);
			unset($this->ldprops['multigraph']);
		}
	}

	/**
	 * checks the contents of a multi-graph ldo and gets a consolidated division of the data into different graphs (no data left in default root context)
	 * @param array $json the json array to consolidate
	 * @param string $defgraph the url of the default graph 
	 * @param string $graph_urls the urls of the various graphs avaliable to the object
	 * @return array an array of graphs indexed by ids
	 */
	function getConsolidatedMultiGraphContents($json, $defgraph, $graph_urls = false){
		$graphs = array();
		foreach($json as $k => $v){
			if(in_array($k, $graph_urls)){
				$graphs[$k] = $v;
			}
			else {
				if(!isset($graphs[$defgraph])){
					$graphs[$defgraph] = array();
				}
				$graphs[$defgraph][$k] = $v;
			}
		}
		return $graphs;
	}
	
	/**
	 * Normalises a multi-graph import by collapsing single default graph objects back to root / default context
	 * @param string $default_graph the default graph url
	 */
	function normaliseMultigraphImport($default_graph){
		if(count(array_keys($this->ldprops)) == 1 && isset($this->ldprops[$default_graph])){
			$this->ldprops = $this->ldprops[$default_graph];
			$this->multigraph = false;
		}
		else {
			$this->multigraph = true;
		}
	}
	
	/**
	 * Looks into the passed update object to see if it references multiple or specific graphs 
	 * @param array $upd the update object property array
	 * @see LDO::isMultigraphUpdate()
	 */
	function isMultigraphUpdate($upd){
		$gurls = $this->getValidGraphURLs();
		foreach($upd as $id => $up){
			if(in_array($id, $gurls)){
				return true;
			}
			else {
				return false;
			}
		}
	}
	
	/**
	 * Returns the ids of the available graphs in the current context
	 * @return array list of graph id strings [id1, id2, ..]
	 */
	function getGraphIDs(){
		return array_keys($this->graphs);
	}
	
	/**
	 * Calculates the transforms necessary to get to current from other
	 *
	 * @param LDO $other the object to be compared to this one
	 * @return MultiGraphLDDelta
	 */
	function compare(LDO $other){
		if(!$this->is_multigraph() && !$other->is_multigraph()){
			return compareLDGraph($this->ldprops, $other->ldprops, $this->cwurl, $this->getDefaultGraphURL());
		}
		if($this->is_multigraph() && !$other->is_multigraph()){
			$other->multigraph = true;
			$other->ldprops = array($other->getDefaultGraphURL() => $other->ldprops);
		}
		elseif($other->is_multigraph() && !$this->is_multigraph()){
			$this->multigraph = true;
			$gurl = $this->getDefaultGraphURL();
			$this->ldprops = array($gurl => $this->ldprops);
		}
		$cdelta = compareLDGraphs($this->ldprops, $other->ldprops, $this->cwurl);
		$ndd = compareJSON($this->id, $this->meta, $other->meta, $this->cwurl, "meta");
		if($ndd->containsChanges()){
			$cdelta->addJSONDelta($ndd);
		}
		return $cdelta;
	}
	
	/**
	 * Updates a ld property array according to the passed update object
	 * @param array $update_obj ld update object
	 * @param string $mode the mode in which the update is taking place (create, remove, update, rollback, view)
	 * @param boolean $ismulti true if the passed update object is in a multi-graph representation
	 * @return boolean true if no errors were encountered on update
	 */
	function update($update_obj, $mode){
		if(isset($update_obj['meta'])){
			if(!$this->updateJSON($update_obj['meta'], $this->meta, $mode)){
				return false;
			}
			unset($update_obj['meta']);
		}
		if(!($this->is_multigraph() || $this->isMultigraphUpdate($update_obj))){
			if(count($update_obj) > 0){
				if(!$this->updateLDProps($update_obj, $this->ldprops, $this->idmap, $mode)){
					return false;
				}				
			}
		}				
		elseif(!$this->isMultigraphUpdate($update_obj)){
			$update_obj = array($this->getDefaultGraphURL() => $update_obj);
		}
		elseif(!$this->is_multigraph()){
			$this->ldprops = array($this->getDefaultGraphURL() => $this->ldprops);
			$this->multigraph = true;
		}
		foreach($update_obj as $gid => $gprops){
			if(is_array($gprops) && count($gprops) == 0){
				if(isset($this->ldprops[$gid])){
					unset($this->ldprops[$gid]);
				}
				elseif($this->rule($mode, "update", 'fail_on_bad_delete')){
					return $this->failure_result("Attempted to remove non-existant property $gid", 404);
				}
			}
			elseif(isAssoc($gprops)){
				if(!isset($this->ldprops[$gid])){
					$this->ldprops[$gid] = $gprops;
				}
				else {
					if(!$this->updateLDProps($gprops, $this->ldprops[$gid], $this->idmap, $mode)){
						return false;
					}
				}
			}
		}
		if(count($this->ldprops) == 1 && isset($this->ldprops[$this->getDefaultGraphURL()])){
			$this->ldprops = $this->ldprops[$this->getDefaultGraphURL()];
			$this->multigraph = false;
		}
		if(count($this->idmap) > 0){
			$this->ldprops = updateLDReferences($this->ldprops, $this->idmap, $this->cwurl, $this->is_multigraph());
		}
		$this->buildIndex();
		return true;
	}
	
	/**
	 * Exports from the local ld format into an external format
	 * @param string $format the desired output format
	 * @param array $nsobj a ns resolver object to create the list of namespaces with
	 * @return boolean|string the serialised export of the object
	 */
	function export($format, $nsobj = false){
		if(!$this->is_multigraph()){
			return parent::export($format, $nsobj);
		}
		$easy = array();
		foreach($this->ldprops as $gid => $gprops){
			$neasy = exportEasyRDFPHP($gprops, $this->cwurl);
			foreach($neasy as $s => $eldo){
				if(!isset($easy[$s])){
					$easy[$s] = $eldo;
				}
				else {
					foreach($eldo as $p => $v){
						if(!isset($easy[$s][$p])){
							$easy[$s][$p] = $v;
						}
						elseif(is_array($easy[$s][$p])){
							$easy[$s][$p] += $v;
						}
						else {
							$easy[$s][$p] = array($easy[$s][$p]);
							$easy[$s][$p] += $v;
						}
					}
				}
			}
		}
		try{
			foreach($this->nsres->prefixes as $id => $url){
				EasyRdf_Namespace::set($id, $url);
			}
			$graph = new EasyRdf_Graph($this->cwurl, $easy, "php", $this->id);
			if($graph->isEmpty()){
				return "";//return $this->failure_result("exported graph was empty.", 400);
			}
			if($nsobj){
				$nslist = $this->getNS($nsobj);
				if($nslist){
					foreach($nslist as $prefix => $full){
						EasyRdf_Namespace::set($prefix, $full);
					}
				}
			}
			$res = $graph->serialise($format);
			if(!$res){
				return $this->failure_result("failed to serialise graph", 500);
			}
			return $res;
		}
		catch(Exception $e){
			return $this->failure_result("Graph croaked on input. ".$e->getMessage(), $e->getCode());
		}
	}
	
	/**
	 * Fragment ids can be repeated across different graphs - must check for them seperately
	 * Currently only set the first fragment encountered in multigraph sets where no graph id is specified
	 * @param string $fragid the fragment id to set
	 * @param mixed $ldo value to set fragment to
	 * @param array $gid the graph id to set the fragment
	 */
	function setFragment($fragid, $ldo, $gid = false){
		if(!$this->is_multigraph()){
			return parent::setFragment($fragid, $ldo, $gid);
		}
		if($this->addressable_bnids){
			$fid = $this->cwurl."/".$fragid;
		}
		else {
			$fid = "_:".$fragid;
		}
		$target =& $this->ldprops;
		if($gid){
			if(!setFragment($fid, $this->ldprops[$gid], $ldo, $this->cwurl)){
				return $this->failure_result("Failed to sed fragment $fragid to new value", 404);
			}
			return true;
		}
		else {
			foreach($this->ldprops as $gid => $lprops){
				if(setFragment($fid, $this->ldprops[$gid], $ldo, $this->cwurl)){
					return true;
				}
			}
			return $this->failure_result("Failed to sed fragment $fragid to new value", 404);
		}
		return true;
	}
	
	/**
	 * Does the object contain a fragment with the given id?
	 * @param string $frag_id the object's fragment id
	 * @param string $gid the graph id to check
	 * @return boolean true if the fragment with the given id exists in the object
	 */
	function hasFragment($frag_id, $gid = false){
		if(!$this->is_multigraph()){
			return parent::hasFragment($frag_id, $gid);
		}
		if($this->index === false){
			$this->buildIndex();
		}
		if($gid){
			return isset($this->index[$gid][$frag_id]);
		}
		else {
			foreach($this->index as $gid => $ind){
				if(isset($ind[$frag_id])){
					return true;
				}
			}
			return false;
		}
	}
	
	/**
	 * Retrieve a particular fragment by id
	 * @param string $fid fragment id
	 * @param string $gid graph id
	 * @return array - array of fragment values or false if not found
	 */
	function getFragment($fid, $gid = false){
		if(!$this->is_multigraph()){
			return parent::getFragment($fid, $gid);
		}
		if($this->index === false){
			$this->buildIndex();
		}
		$frag = array();
		if($gid){
			if(!isset($this->index[$gid]) || !isset($this->index[$gid][$fid])){
				return false;
			}
			foreach($this->index[$gid][$fid] as $i => $ldobj){
				$frag = array_merge($frag, $ldobj);
			}
		}
		else {
			foreach($this->ldprops as $gid => $ldprops){
				if(isset($this->index[$gid][$fid])){
					foreach($this->index[$gid][$fid] as $i => $ldobj){
						$frag = array_merge($frag, $ldobj);
					}
				}
			}
		}
		return $frag;
	}
	
	/**
	 * Sets the value of a particular subject predicate to the passed value in the ld object
	 * @param mixed $value - whatever the value is
	 * @param string $fix - the fragment id
	 * @param string $p - the predicate
	 * @param string $gid - the graph url
	 */
	function setFragmentPredicateValue($value, $fid, $p, $gid = false){
		if(!$this->is_multigraph()){
			return parent::setFragmentPredicateValue($value, $fid, $p, $gid);
		}
		if($this->index === false){
			$this->buildIndex();
		}
		if(!isset($this->index[$gid]) || !isset($this->index[$gid][$fid])){
			return $this->failure_result("Fragment $fid does not exist in graph $gid", 404);
		}
		setFragmentPredicate($fid, $p, $this->ldprops[$gid], $value, $this->cwurl);
		return true;
	}
	
	/**
	 * Returns the object embedding paths to the fragments with subjects of the passed
	 * @param string $fid the fragment id to find
	 * @param string $gid the graph id to check
	 * @param mixed $frag a value to set the path to
	 * @return array<array> an array of paths to the object in question
	 */
	function getFragmentPath($fid, $gid = false, $frag = false){
		if(!$this->is_multigraph()){
			return parent::getFragmentPath($fid, $gid, $frag);
		}
		$path = false;
		if($gid && isseet($this->ldprops[$gid])){
			$arr = getFragmentContext($fid, $this->ldprops[$gid], $this->cwurl, $frag);
			if($arr !== false){
				$path = array($gid => $arr);
			}
		}
		else {
			foreach($this->ldprops as $gid => $props){
				$arr = getFragmentContext($fid, $this->ldprops[$gid], $this->cwurl, $frag);
				if($arr !== false){
					if(!isset($path)){
						$path = array();
					}
					$path[$gid] = $arr;
				}
			}
		}
		return $path;
	}

	/**
	 * Expands the object's contained urls into full urls using the object's NSResolver property
	 *
	 * used for expanding urls with prefixes
	 */
	function expandNS(){
		if(!$this->is_multigraph()){
			return parent::expandNS();
		}
		if(!$this->nsres){
			return $this->failure_result("No name space resolver object set for LD object - cannot expand Namespaces", 500);
		}
		foreach($this->ldprops as $gid => $gprops){
			$res = $this->nsres->expandNamespaces($this->ldprops[$gid], $this->cwurl);
		}
		if($res){
			$this->compressed = false;
		}
		return $res;
	}
	
	/**
	 * Compresses the object's contained urls into prefixed urls using the object's NSResolver property
	 *
	 * used for compressing urls with prefixes
	 */
	function compressNS(){
		if(!$this->is_multigraph()){
			return parent::compressNS();
		}
		if(!$this->nsres){
			return $this->failure_result("No name space resolver object set for LD object - cannot compress Namespaces", 500);
		}
		$res = false;
		foreach($this->ldprops as $gid => $gprops){
			$res = $this->nsres->compressNamespaces($this->ldprops[$gid], $this->cwurl);
		}
		if($res){
			$this->compressed = true;
		}
		return $res;
	}

}