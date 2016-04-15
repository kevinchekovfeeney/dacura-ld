<?php
/**
 * Namespace Resolver - a class where all functionality to do with namespaces and prefixes is maintained
 * 
 * Every LD Object is associated with a namespace resolver object that contains the context of namespaces that it has available
 *
 * @author Chekov
 * @license GPL V2
 */
class NSResolver extends DacuraController {
	/** @var array A mapping of prefix -> URL */ 
	var $prefixes = array();//prefix => full
	/** @var array the prefixes that will be loaded by default if no prefixes are specified in constructor settings */
	var $default_prefixes = array(
		"rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
		"rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
		"xsd" => "http://www.w3.org/2001/XMLSchema#",
		"owl" => "http://www.w3.org/2002/07/owl#"
	);
	/** @var array default settings for what is considered a 'structural' predicate in dependency analysis */
	var $structural_predicates = array(
		"rdf" => array("type"),
		"rdfs" => array("range", "domain", "subPropertyOf", "subClassOf", "member"),
		"owl" => array("inverseOf", "unionOf", "complementOf", 
					"intersectionOf", "oneOf", "dataRange", "disjointWith", "imports", "sameAs", "differentFrom",
				"allValuesFrom", "someValuesFrom")  			
	);
	/**
	 * An array of problem predicates that can be flagged by dacura
	 * @var unknown
	 */
	var $problem_predicates = array(
		"dc" => array("type"),
		"rdf" => array("List")				
	);
	
	/** @var array a mapping of 'alternate' urls to be used in dependency analysis - the canonical url is on the right */
	var $url_mappings = array(
		"http://www.lehigh.edu/~zhp2/2004/0401/univ-bench.owl#" => "http://swat.cse.lehigh.edu/onto/univ-bench.owl#",
		"http://www.w3.org/2008/05/skos#" => "http://www.w3.org/2004/02/skos/core#",
		"http://web.resource.org/cc/" => "http://creativecommons.org/ns#",
		"http://www.w3.org/2001/XMLSchema-datatypes#" => "http://www.w3.org/2001/XMLSchema#",	
	);
	
	/**
	 * Constructor initialises structural_predicates, url_mappings and prefixes of NSResolver object
	 * 
	 * @param array $settings an array which can contain one or more of the above indexes
	 */
	function __construct($service){
		parent::__construct($service);
		$this->prefixes = $this->getServiceSetting("prefixes", $this->default_prefixes);
		$this->structural_predicates = $this->getServiceSetting("structural_predicates", $this->structural_predicates);
		$this->problem_predicates = $this->getServiceSetting("problem_predicates", $this->problem_predicates);
		$this->url_mappings = $this->getServiceSetting("url_mappings", $this->url_mappings);
	}
	
	/**
	 * Adds a prefix / url pair to the name space resolver
	 * @param string $prefix the prefix to use
	 * @param string $url the url to use
	 */
	function addPrefix($prefix, $url){
		$this->prefixes[$prefix] = $url;
	}
	
	/**
	 * Is the prefix a dqs built-in ontology (rdf, owl, rdfs, xsd)
	 * @param string $sh the prefix
	 */
	function isBuiltInOntology($sh){
		return isset($this->default_prefixes[$sh]);
	}
	
	/**
	 * Uses the object's url_mappings to map the passed $url to its mapped version, if one exists, otherwise returns the url unchanged
	 * 
	 * @param  string $url the url to be mapped
	 * @return string the mapped url (which will be unchanged if there is no mapping specified for the url)
	 */
	function mapURL($url){
		foreach($this->url_mappings as $uk => $uv){
			if(substr($url, 0, strlen($uk)) == $uk){
				return $uv.substr($url, strlen($uk));
			}
		}
		return $url;
	}
	
	/**
	 * Load the resolver's map of prefixes - defining the namespaces that are available 
	 * 
	 * @param array $p associative array {prefix => full_url}
	 */
	function load($p){
		$this->prefixes = $p;
		return true;
	}

	/**
	 * Return the full url associated with a prefix
	 * @param string $prefix the prefix in question
	 * @return boolean|string if a url is found it is returned, otherwise false 
	 */
	function getURL($prefix){
		return isset($this->prefixes[$prefix]) ? $this->prefixes[$prefix] : false;
	}

	/**
	 * Return the shorthand version of the passed url - the 'prefix' portion of a compressed url
	 * @param string $url the url under examination
	 * @return string|boolean if found, the shorthand form will be returned, otherwise false
	 */
	function getShorthand($url){
		foreach($this->prefixes as $shorthand => $id){
			if($url == $id){
				return $shorthand;
			}
		}
		return false;
	}

	/**
	 * Does the passed url match the passed prefix and id 
	 * 
	 * Tries to match both compressed and non-compressed urls - prefix:id or [full_url]id
	 * @param string $url the url under examination
	 * @param string $prefix the prefix which we are looking for
	 * @param string $id the id (portion after the prefix) that we are looking for
	 * @return boolean - true if the passed url and the prefix:id combination resolve to the same url
	 */
	function match($url, $prefix, $id){
		if($url == $prefix.":".$id) return true;
		if($url == $this->getURL($prefix).$id) return true;
		return false;
	}

	/**
	 * Compresses a passed url by replacing full urls with namespace prefixes
	 * @param string $url the url to be compressed
	 * @return string|boolean the compressed url, if found, false otherwise
	 */
	function compress($url){
		foreach($this->prefixes as $shorthand => $id){
			if(substr($url, 0, strlen($id)) == $id){
				$urlid = substr($url, strlen($id));
				return $shorthand.":".$urlid;
			}
		}
		return false;
	}

	/**
	 * expands a passed url by replacing namespace prefixes with their corresponding full urls
	 * @param string $prefixed_url the url to be expanded
	 * @return string|boolean the expanded url, if found, false otherwise
	 */
	function expand($prefixed_url){
		if(isNamespacedURL($prefixed_url) && ($shorthand = getNamespacePortion($prefixed_url))){
			$url = $this->getURL($shorthand);
			if($url){
				return $url . substr($prefixed_url, strlen($shorthand) + 1);
			}
		}
		return false;
	}
	
	/**
	 * Traverses a LD props associative array, expanding the namespaces from the prefixes of any ids found there
	 * @param array $ldprops an LD object's associative array contents,
	 * @param string|boolean $cwurl the closed world url of the object (or false if there is none)
	 */
	function expandNamespaces(&$ldprops, $cwurl = false, $multigraph = false){
		if(!is_array($ldprops)) return;
		if($multigraph){
			foreach(array_keys($ldprops) as $gid){
				$ldprops[$gid] = expandNamespaces($ldprops[$gid], $cwurl);
			}
		}
		else {
			foreach($ldprops as $s => $ldobj){
				if(isNamespacedURL($s) && ($expanded = $this->expand($s))){
					$ldprops[$expanded] = $ldobj;
					unset($ldprops[$s]);
					$s = $expanded;
				}
				if(isAssoc($ldobj)){
					$this->expandLDONamespaces($ldprops[$s], $cwurl);
				}
			}
		}
	}
	
	/**
	 * Traverses an ldo array {property: value} and expands all the urls to not use prefixes
	 * @param array $ldobj the array to be expanded
	 * @param string $cwurl the url of the object that owns the property array
	 */
	function expandLDONamespaces(&$ldobj, $cwurl){
		foreach($ldobj as $p => $v){
			if(isNamespacedURL($p) && ($expanded = $this->expand($p))){
				$ldobj[$expanded] = $v;
				unset($ldobj[$p]);
				$p = $expanded;
			}
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->link() && isNamespacedURL($v)){
				if($expanded = $this->expand($v)){
					$ldobj[$p] = $expanded;
				}
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isNamespacedURL($val) && ($expanded = $this->expand($val))){
						$nv[] = $expanded;
					}
					else {
						$nv[] = $val;
					}
				}
				$ldobj[$p] = $nv;
			}
			elseif($pv->embeddedlist()){
				$this->expandNamespaces($ldobj[$p], $cwurl);
			}
			elseif($pv->embedded()){
				$this->expandLDONamespaces($ldobj[$p], $cwurl);
			}
			elseif($pv->objectlist()){
				$nv = array();
				foreach($v as $one_obj){
					$this->expandLDONamespaces($one_obj, $cwurl);
					$nv[] = $one_obj;
				}
				$ldobj[$p] = $nv;
			}
		}
	}
	
	/**
	 * Traverses a LD object associative array, compressing the namespaces with the prefixes of any matching urls found 
	 * @param array $ldprops an LD object's associative array contents,
	 * @param string|boolean $cwurl the closed world url of the object (or false if there is none)
	 * @param boolean - is this a multi-graph ld array (indexed by graph id)
	 */
	function compressNamespaces(&$ldprops, $cwurl = false, $multigraph=false){
		if(!is_array($ldprops)){return;}
		if($multigraph){
			foreach(array_keys($ldprops) as $gid){
				$ldprops[$gid] = compressNamespaces($ldprops[$gid], $cwurl);
			}
		}
		else {
			foreach($ldprops as $s => $ldobj){
				if(isURL($s) && ($compressed = $this->compress($s))){
					$ldprops[$compressed] = $ldobj;
					unset($ldprops[$s]);
					$s = $compressed;
				}
				if(isAssoc($ldobj)){
					$this->compressLDONamespaces($ldprops[$s], $cwurl);
				}
			}
		}
	}
	
	/**
	 * Traverses a ld object {property: value} array and compresses urls to use prefix:id form
	 * @param array $ldobj the ld object to be compressed
	 * @param string $cwurl the url of the objec that owns the property array
	 */
	private function compressLDONamespaces(&$ldobj, $cwurl = false){
		foreach($ldobj as $p => $v){
			//compress predicates
			if(isURL($p) && ($compressed = $this->compress($p))){
				$ldobj[$compressed] = $v;
				unset($ldobj[$p]);
				$p = $compressed;
			}				
			//then compress property values
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->link() && ($compressed = $this->compress($v))){
				$ldobj[$p] = $compressed;
			}
			elseif($pv->valuelist()){
				$nv = array();
				foreach($v as $val){
					if(isURL($val) && ($compressed = $this->compress($val))){
						$nv[] = $compressed;
					}
					else {
						$nv[] = $val;
					}
				}
				$ldobj[$p] = $nv;
			}
			elseif($pv->embeddedlist()){
				$this->compressNamespaces($ldobj[$p], $cwurl);
			}
			elseif($pv->embedded()){
				$this->compressLDONamespaces($ldobj[$p], $cwurl);
			}
			elseif($pv->objectlist()){
				$nv = array();
				foreach($v as $one_obj){
					$this->compressLDONamespaces($one_obj, $cwurl);
					$nv[] = $one_obj;
				}
				$ldobj[$p] = $nv;
			}
		}
	}
	
	/**
	 * Returns a mapping of prefixes to full urls of namespaces used in the ld object array
	 * @param array $ldprops ld object property array
	 * @param string $cwurl the closed world url of the object
	 * @return array<string:string> map of prefixes to full urls
	 */
	function getNamespaces($ldprops, $cwurl, $compressed = false){
		$ns = array();
		$this->getNamespaceUtilisation($ldprops, $cwurl, $ns);
		$op = array();
		foreach(array_keys($ns) as $pre ){
			$exp = $this->getURL($pre);
			if($exp){
				$op[$pre] = $exp;
			}
		}
		return $op;
	}

	/**
	 * Returns a data-structure containing information about the namespaces used in a ld property array 
	 * @param string $eid the id of the linked data object (subject id)
	 * @param array $ldprops ld object property array
	 * @param string $cwurl the closed world url of the object
	 * @param array $ns map that will be filled by the method with prefixes to information about their utilisation
	 */	
	function getNamespaceUtilisation($ldprops, $cwurl, &$ns){
		foreach($ldprops as $eid => $ldobj){
			$this->addSubjectToNSUtilisation($ns, $eid);
			$this->getLDONamespaceUtilisation($eid, $ldobj, $cwurl, $ns);
		}
	}
	
	/**
	 * Returns a data structure containing information about the namespaces used in the passed ldo
	 * @param string $lid id of the object
	 * @param array $ldo the linked data property array
	 * @param string $cwurl the url of the object 
	 * @param array $ns namespace utilisation record which is populated
	 */
	private function getLDONamespaceUtilisation($lid, $ldo, $cwurl, &$ns){
		foreach($ldo as $p => $v){				
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->embeddedlist()){
				foreach($v as $i => $obj){
					$this->addPredicateToNSUtilisation($ns, $p);
					$this->addObjectToNSUtilisation($ns, $i, $lid, $p);
				}
				$this->getNamespaceUtilisation($obj, $cwurl, $ns);
			}
			elseif($pv->embedded()){
				$this->addPredicateToNSUtilisation($ns, $p);
				$this->getLDONamespaceUtilisation("", $v, $cwurl, $ns);
			}
			elseif($pv->objectlist()){
				foreach($v as $i => $obj){
					$this->addPredicateToNSUtilisation($ns, $p);
					$this->getLDONamespaceUtilisation("", $obj, $cwurl, $ns);
				}
			}
			else{
				if($pv->valuelist() or $pv->objectliterallist()){
					foreach($v as $val){
						$this->addPredicateToNSUtilisation($ns, $p);
						if($pv->valuelist()){
							$this->addObjectToNSUtilisation($ns, $val, $lid, $p);
						}
						elseif(isset($val['type']) && $val['type'] == "uri"){
							$this->addObjectToNSUtilisation($ns, $val['value'], $lid, $p);
						}
					}
				}
				elseif($pv->link()){
					$this->addPredicateToNSUtilisation($ns, $p);
					$this->addObjectToNSUtilisation($ns, $v, $lid, $p);
				}
				elseif($pv->objectliteral()){
					$this->addPredicateToNSUtilisation($ns, $p);
					if(isset($v['type']) && $v['type'] == "uri"){
						$this->addObjectToNSUtilisation($ns, $v['value'], $lid, $p);
					}
				}
			}
		}
	}

	/**
	 * internal function called each time the subject of a triple is encountered to register its utilisation
	 * @param array $ns an array that will be populated, describing namespace utilisation of subjects, ojbects, predicates and structural predicates
	 * @param string $sid the subject id (url)
	 */
	private function addSubjectToNSUtilisation(&$ns, $sid){
		if($parts = $this->initUtilisation($ns, $sid)){
			$s = $this->compress($parts[1]);
			$s = $s ? $s : $parts[1];
			if(isset($ns[$parts[0]]["subject"][$s])){
				$ns[$parts[0]]["subject"][$s]++;
			}
			else {
				$ns[$parts[0]]["subject"][$s] = 1;
			}
		}
		return $parts;
	}

	/**
	 * internal function called each time the predicate of a triple is encountered to register its utilisation
	 * @param array $ns an array that will be populated, describing namespace utilisation of subjects, ojbects, predicates and structural predicates
	 * @param string $prop the subject id (url)  
	 */
	private function addPredicateToNSUtilisation(&$ns, $prop){
		if($parts = $this->initUtilisation($ns, $prop)){
			$p = $this->compress($parts[1]);
			$p = $p ? $p : $parts[1];
			if(!isset($ns[$parts[0]]["predicate"][$p])){
				$ns[$parts[0]]["predicate"][$p] = 1;
			}
			else {
				$ns[$parts[0]]["predicate"][$p]++;
			}
			if($this->isProblemPredicate($p)){
				$p = $p ? $p : $parts[1];
				if(!isset($ns[$parts[0]]["problem_predicates"][$p])){
					$ns[$parts[0]]["problem_predicates"][$p] = 1;
				}
				else {
					$ns[$parts[0]]["problem_predicates"][$p]++;
				}
			}
		}
		return $parts;
	}

	/**
	 * internal function called each time the object of a triple is encountered to register its utilisation
	 * @param array $ns an array that will be populated, describing namespace utilisation of subjects, ojbects, predicates and structural predicates
	 * @param string $oid the object id (url)
	 * @param string $sid the subject id (url)
	 * @param string $prop the predicate id (url)
	 */
	private function addObjectToNSUtilisation(&$ns, $oid, $sid, $prop){
		if($parts = $this->initUtilisation($ns, $oid)){
			if($this->isStructuralPredicate($prop)){
				$ns[$parts[0]]["structural"][] = $this->compressTriple($sid, $prop, $parts[1]);
			}
			else {
				$ns[$parts[0]]["object"][] = $this->compressTriple($sid, $prop, $parts[1]);
			}
		}
		return $parts;
	}
	
	/**
	 * internal function called to set up the initial structure of a namespace utilisation record
	 * @param array $ns an array that will be populated, describing namespace utilisation of subjects, objects, predicates and structural predicates
	 * @param string $url the url being registered
	 */
	private function initUtilisation(&$ns, $url){
		if($parts = $this->deconstructURL($url)){
			if(!isset($ns[$parts[0]])){
				$ns[$parts[0]] = array("predicate" => array(), "structural" => array(), "object" => array(), "subject" => array());
			}
		}
		return $parts;	
	}
	
	/**
	 * Deconstructs a url into a namespace portion and a full url [prefix, url]
	 * 
	 * If the url is a blank node, the prefix returned will be "_", if it has no known prefix, it will get "unknown"
	 * @param string $url the url to be deconstructed
	 * @return array<string, string> - prefix, url array
	 */
	function deconstructURL($url){
		if(is_array($url)){
			return false;
		}
		$url = $this->mapURL($url);
		if(isBlankNode($url)){
			return array("_", $url);
		}
		elseif(isNamespacedURL($url)){
			$pre = getNamespacePortion($url);
			if($xurl = $this->expand($url)){
				return array($pre, $xurl);
			}
			else {
				return array("unknown", $url);
			}
		}
		elseif(isURL($url)){
			if($short = $this->compress($url)){
				$pre = getNamespacePortion($short);
				return array($pre, $url);
			}
			else {
				return array("unknown", $url);
			}
		}
		else {
			return array("unknown", $url);
		}
	}
	
	/**
	 * Should this namespace be considered a 'structural' namespace for dependency analysis?
	 *
	 * Returns true if the namespace is a built in type (owl, rdf, rdfs, xsd) or blank or unknown.
	 * @param string $ns the namespace prefix under investigation
	 * @return boolean true if the prefix matches a structural prefix
	 */
	function isStructuralNamespace($ns){
		if(in_array($ns, array_keys($this->default_prefixes))) return true;
		if($ns == "_") return true;
		if($ns == "unknown") return true;
		return false;
	}

	/**
	 * Should this predicate be considered a 'structural' link in dependency analysis?
	 *
	 * Returns true if the namespace is in the structural_predicates object property
	 * @param string $url the predicate url 
	 * @return boolean true if the url represents a structural predicate
	 */
	function isStructuralPredicate($url){
		foreach($this->structural_predicates as $sh => $preds){
			foreach($preds as $pred){
				if($this->match($url, $sh, $pred)){
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Does the url match one of our known problem predicates? 
	 * @param string $url
	 * @return boolean
	 */
	function isProblemPredicate($url){
		foreach($this->problem_predicates as $sh => $preds){
			foreach($preds as $pred){
				if($this->match($url, $sh, $pred)){
					return true;
				}
			}
		}
		return false;
	}
	
	/**
	 * Compresses the urls in a triple by using prefixed forms wherever possible
	 * @param string $s subject url
	 * @param string $p predicate url
	 * @param string $o object url
	 * @param [string] $g named graph url
	 * @return array<$s, $p, $o> compressed triple array [s,p,o]
	 */
	function compressTriple($s, $p, $o, $g=false){
		$ss = $this->compress($s);
		$ss = $ss ? $ss : $s;
		$sp = $this->compress($p);
		$sp = $sp ? $sp : $p;
		$so = $this->compress($o);
		$so = $so ? $so : $o;
		$trip = array($ss, $sp, $so);
		if($g){
			$trip[] = ($comp = $this->compress[$g]) ? $comp : $g;
		}
		return trip;
	}
	
	/**
	 * Expands the urls in a triple / quad by using full forms wherever prefixed forms are used
	 * @param string $s subject url
	 * @param string $p predicate url
	 * @param string $o object url
	 * @param [string] $g named graph url
	 * @return array<$s, $p, $o, $g> expanded triple / quad array [s,p,o,g]
	 */
	function expandTriple($s, $p, $o, $g = false){
		$ss = $this->expand($s);
		$ss = $ss ? $ss : $s;
		$sp = $this->expand($p);
		$sp = $sp ? $sp : $p;
		$so = $this->expand($o);
		$so = $so ? $so : $o;
		$trip = array($ss, $sp, $so);
		if($g){
			$trip[] = ($comp = $this->expand[$g]) ? $comp : $g;
		}
		return $trip;
	}

	/**
	 * Expands the urls from prefixes in a passed array of triples wherever possible
	 * @param array $trips the array of triples [s,p,o] or quads [s,p,o,q]
	 * @param string $cwurl the closed world url of the object, if it exists
	 * @param boolean $has_gnames if true, the passed triple array will be treated as if it is indexed by graph ids 
	 * @param string $gid the graph url of the current graph (for adding to quads) 
	 */
	function expandTriples(&$trips, $cwurl, $has_gnames = false, $gid = false){
		if($has_gnames){
			if($gid){
				$this->expandTriples($trips[$gid], $cwurl, false, $gid);				
			}
			else {
				foreach($trips as $gname => $data){
					$this->expandTriples($trips[$gname], $cwurl, false, $gname);
				}
			}
		}
		else {
			foreach($trips as $i => $v){
				if(count($v) == 3){
					$trips[$i] = $this->expandTriple($v[0], $v[1], $v[2]);
				}
				else {
					$trips[$i] = $this->expandTriple($v[0], $v[1], $v[2], $v[3]);						
				}
			}
		}		
	}
	
	/**
	 * Compresses the urls with prefixes in a passed array of triples wherever possible
	 * @param array $trips the array of triples [s,p,o]
	 * @param string $cwurl the closed world url of the object, if it exists
	 * @param boolean $has_gnames if true, the passed triple array will be treated as if it is indexed by graph ids
	 */
	function compressTriples(&$trips, $cwurl, $has_gnames = false, $gid = false){
		if($has_gnames){
			if($gid){
				$this->compressTriples($trips[$gid], $cwurl, false, $gid);
			}
			else {
				foreach($trips as $gname => $data){
					$this->compressTriples($trips[$gname], $cwurl, false, $gname);
				}
			}
		}
		else {
			foreach($trips as $i => $v){
				if(count($v) == 3){
					$trips[$i] = $this->compressTriple($v[0], $v[1], $v[2]);
				}
				else {
					$trips[$i] = $this->compressTriple($v[0], $v[1], $v[2], $v[3]);
				}
			}
		}
	}

}
