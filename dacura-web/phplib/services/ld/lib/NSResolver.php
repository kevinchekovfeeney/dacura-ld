<?php
/**
 * Namespace Resolver - a class where all functionality to do with namespaces and prefixes is maintained
 * 
 * Every LD Object is associated with a namespace resolver object that contains the context of namespaces that it has available
 *
 * @author Chekov
 * @license GPL V2
 */
class NSResolver extends DacuraObject {
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
	
	/** @var array a mapping of 'alternate' urls to be used in dependency analysis - the canonical url is on the right */
	var $url_mappings = array(
		"http://www.lehigh.edu/~zhp2/2004/0401/univ-bench.owl#" => "http://swat.cse.lehigh.edu/onto/univ-bench.owl#",
		"http://www.w3.org/2008/05/skos#" => "http://www.w3.org/2004/02/skos/core#",
		"http://web.resource.org/cc/" => "http://creativecommons.org/ns#"	
	);
	
	/** @var array an index of namespace utilisation in the document */
	var $ns_utilisation = false;
	
	/**
	 * Constructor initialises structural_predicates, url_mappings and prefixes of NSResolver object
	 * 
	 * @param array $settings an array which can contain one or more of the above indexes
	 */
	function __construct($settings = array()){
		if(isset($settings['structural_predicates'])){
			$this->structural_predicates = $settings['structural_predicates'];
		}
		if(isset($settings['url_mappings'])){
			$this->url_mappings = $settings['url_mappings'];
		}
		if(isset($settings['prefixes'])){
			$this->prefixes = $settings['prefixes'];
		}
		else {
			$this->prefixes = $this->default_prefixes;
		}
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
	 * Traverses a LD object associative array, expanding the namespaces from the prefixes of any ids found there
	 * @param array $ldprops an LD object's associative array contents,
	 * @param string|boolean $cwurl the closed world url of the object (or false if there is none)
	 */
	function expandNamespaces(&$ldprops, $cwurl = false){
		if(!is_array($ldprops)) return;
		foreach($ldprops as $p => $v){
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->link() && isNamespacedURL($v) && ($expanded = $this->expand($v))){
				$nv = $expanded;
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
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					if(isNamespacedURL($id) && ($expanded = $this->expand($id))){
						$nv[$expanded] = $obj;
						$this->expandNamespaces($nv[$expanded], $cwurl);
					}
					else {
						$nv[$id] = $obj;
						$this->expandNamespaces($nv[$id], $cwurl);
					}
				}
			}
			elseif($pv->embedded()){
				$this->expandNamespaces($v, $cwurl);
				$nv = $v;
			}
			elseif($pv->objectlist()){
				$nv = array();
				foreach($v as $one_obj){
					$this->expandNamespaces($one_obj, $cwurl);
					$nv[] = $one_obj;
				}
			}
			else {
				$nv = $v;
			}
			if(isNamespacedURL($p) && ($expanded = $this->expand($p))){
				unset($ldprops[$p]);
				$ldprops[$expanded] = $nv;
			}
			elseif(isNamespacedURL($p)){
				$ldprops[$p] = $nv;
			}
			else {
				$ldprops[$p] = $nv;
			}
		}
	}
	
	/**
	 * Traverses a LD object associative array, compressing the namespaces with the prefixes of any matching urls found 
	 * @param array $ldprops an LD object's associative array contents,
	 * @param string|boolean $cwurl the closed world url of the object (or false if there is none)
	 */
	function compressNamespaces(&$ldprops, $cwurl = false){
		if(!is_array($ldprops)){
			return;
		}
		foreach($ldprops as $p => $v){
			//first compress property values
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->link() && ($compressed = $this->compress($v))){
				$nv = $compressed;
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
			}
			elseif($pv->embeddedlist()){
				$nv = array();
				foreach($v as $id => $obj){
					$this->compressNamespaces($obj, $cwurl);
					if(isURL($id) && ($compressed = $this->compress($id))){
						$nv[$compressed] = $obj;
					}
					else {
						$nv[$id] = $obj;
					}
				}
			}
			else {
				$nv = $v;
			}
			//then compress predicates
			if(isURL($p) && ($compressed = $this->compress($p))){
				unset($ldprops[$p]);
				$ldprops[$compressed] = $nv;
			}
			else {
				$ldprops[$p] = $nv;
			}
		}
	}
	
	/**
	 * Returns a mapping of prefixes to full urls of namespaces used in the ld object array
	 * @param string $id the id of the linked data object (subject id)
	 * @param array $ldprops ld object property array
	 * @param string $cwurl the closed world url of the object
	 * @return array<string:string> map of prefixes to full urls
	 */
	function getNamespaces($id, $ldprops, $cwurl){
		$ns = array();
		$this->getNamespaceUtilisation($id, $ldprops, $cwurl, $ns);
		$op = array();
		foreach($ns as $pre => $urls){
			$exp = $this->getURL($pre);
			if($exp){
				$op[$pre] = $exp;
			}
			else {
				$op[$pre] = $urls[0];
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
	function getNamespaceUtilisation($eid, $ldprops, $cwurl, &$ns){
		$this->addSubjectToNSUtilisation($ns, $eid);
		foreach($ldprops as $p => $v){
			$pv = new LDPropertyValue($v, $cwurl);
			if($pv->embeddedlist()){
				foreach($v as $i => $obj){
					$this->addPredicateToNSUtilisation($ns, $p);
					$this->addObjectToNSUtilisation($ns, $i, $eid, $p);
				}
			}
			elseif($pv->embedded()){
				$this->addPredicateToNSUtilisation($ns, $p);
				$this->getNamespaceUtilisation($eid, $v, $cwurl, $ns);
			}
			elseif($pv->objectlist()){
				foreach($v as $i => $obj){
					$this->addPredicateToNSUtilisation($ns, $p);
					$this->getNamespaceUtilisation($eid, $obj, $cwurl, $ns);
				}
			}
			else{
				if($pv->valuelist() or $pv->objectliterallist()){
					foreach($v as $val){
						$this->addPredicateToNSUtilisation($ns, $p);
						if($pv->valuelist()){
							$this->addObjectToNSUtilisation($ns, $val, $eid, $p);
						}
						elseif(isset($val['type']) && $val['type'] == "uri"){
							$this->addObjectToNSUtilisation($ns, $val['value'], $eid, $p);
						}
					}
				}
				elseif($pv->link()){
					$this->addPredicateToNSUtilisation($ns, $p);
					$this->addObjectToNSUtilisation($ns, $v, $eid, $p);
				}
				elseif($pv->objectliteral()){
					$this->addPredicateToNSUtilisation($ns, $p);
					if(isset($v['type']) && $v['type'] == "uri"){
						$this->addObjectToNSUtilisation($ns, $v['value'], $eid, $p);
					}
				}
			}
		}
		return $ns;
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
			if(!isset($ns[$parts[0]]["predicates"][$p])){
				$ns[$parts[0]]["predicates"][$p] = 1;
			}
			else {
				$ns[$parts[0]]["predicates"][$p]++;
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
				$ns[$parts[0]] = array("predicates" => array(), "structural" => array(), "object" => array(), "subject" => array());
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
	 * Compresses the urls in a triple by using prefixed forms wherever possible
	 * @param string $s subject url
	 * @param string $p predicate url
	 * @param string $o object url
	 * @return array<$s, $p, $o> compressed triple array [s,p,o]
	 */
	function compressTriple($s, $p, $o){
		$ss = $this->compress($s);
		$ss = $ss ? $ss : $s;
		$sp = $this->compress($p);
		$sp = $sp ? $sp : $p;
		$so = $this->compress($o);
		$so = $so ? $so : $o;
		return array($ss, $sp, $so);
	}
	
	/**
	 * Expands the urls in a triple by using full forms wherever prefixed forms are used
	 * @param string $s subject url
	 * @param string $p predicate url
	 * @param string $o object url
	 * @return array<$s, $p, $o> expanded triple array [s,p,o]
	 */
	function expandTriple($s, $p, $o){
		$ss = $this->expand($s);
		$ss = $ss ? $ss : $s;
		$sp = $this->expand($p);
		$sp = $sp ? $sp : $p;
		$so = $this->expand($o);
		$so = $so ? $so : $o;
		return array($ss, $sp, $so);
	}

	/**
	 * Expands the urls from prefixes in a passed array of triples wherever possible
	 * @param array $trips the array of triples [s,p,o]
	 * @param string $cwurl the closed world url of the object, if it exists
	 * @param boolean $has_gnames if true, the passed triple array will be treated as if it is indexed by graph ids 
	 */
	function expandTriples(&$trips, $cwurl, $has_gnames = false){
		if($has_gnames){
			foreach($trips as $gname => $data){
				$this->expandTriples($trips[$gname], $cwurl);
			}
		}
		else {
			foreach($trips as $i => $v){
				$trips[$i] = $this->expandTriple($v[0], $v[1], $v[2]);
			}
		}		
	}
	
	/**
	 * Compresses the urls with prefixes in a passed array of triples wherever possible
	 * @param array $trips the array of triples [s,p,o]
	 * @param string $cwurl the closed world url of the object, if it exists
	 * @param boolean $has_gnames if true, the passed triple array will be treated as if it is indexed by graph ids
	 */
	function compressTriples(&$trips, $cwurl, $has_gnames = false){
		if($has_gnames){
			foreach($trips as $gname => $data){
				$this->compressTriples($trips[$gname], $cwurl);
			}
		}
		else {
			foreach($trips as $i => $v){
				$trips[$i] = $this->compressTriple($v[0], $v[1], $v[2]);
			}
		}
	}

}
