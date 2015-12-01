<?php
class NSResolver extends DacuraObject {
	var $prefixes = array();//prefix => full
	/*
	 *
	*/
	var $default_prefixes = array(
		"rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
		"rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
		"xsd" => "http://www.w3.org/2001/XMLSchema#",
		"owl" => "http://www.w3.org/2002/07/owl#"
	);
	
	var $structural_predicates = array(
		"rdf" => array("type"),
		"rdfs" => array("range", "domain", "subPropertyOf", "subClassOf", "member"),
		"owl" => array("inverseOf", "unionOf", "complementOf", 
					"intersectionOf", "oneOf", "dataRange", "disjointWith", "imports", "sameAs", "differentFrom",
				"allValuesFrom", "someValuesFrom")  
			
	);
	
	var $url_mappings = array(
		"http://www.lehigh.edu/~zhp2/2004/0401/univ-bench.owl#" => "http://swat.cse.lehigh.edu/onto/univ-bench.owl#",
		"http://www.w3.org/2008/05/skos#" => "http://www.w3.org/2004/02/skos/core#",
		"http://web.resource.org/cc/" => "http://creativecommons.org/ns#"	
	);
	
	function mapURL($url){
		foreach($this->url_mappings as $uk => $uv){
			if(substr($url, 0, strlen($uk)) == $uk){
				return $uv.substr($url, strlen($uk));
			}
		}
		return $url;
	}
	
	function isStructuralNamespace($ns){
		if(in_array($ns, array_keys($this->default_prefixes))) return true;
		if($ns == "_") return true;
		if($ns == "unknown") return true;
		return false;
	}

	function __construct($dacura_url = false, $local_url = false, $set_defaults = true){
		if($set_defaults){
			$this->prefixes = $this->default_prefixes;
		}
		if($dacura_url){
			$this->prefixes["dacura"] = $dacura_url;
		}
		if($local_url){
			$this->prefixes['local'] = $local_url;
		}
	}
	
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
	
	function setPrefixMap($pmap){
		$this->prefixes = $pmap;
	}

	function load($p){
		$this->prefixes = $p;
		return true;
	}

	function getURL($shorthand){
		return isset($this->prefixes[$shorthand]) ? $this->prefixes[$shorthand] : false;
	}

	function getShorthand($url){
		foreach($this->prefixes as $shorthand => $id){
			if($url == $id){
				return $shorthand;
			}
		}
		return false;
	}

	function match($value, $prefix, $id){
		if($value == $prefix.":".$id) return true;
		if($value == $this->getURL($prefix).$id) return true;
		return false;
	}

	function compress($url){
		foreach($this->prefixes as $shorthand => $id){
			if(substr($url, 0, strlen($id)) == $id){
				$urlid = substr($url, strlen($id));
				return $shorthand.":".$urlid;
			}
		}
		return false;
	}

	function expand($prefixed_url){
		if(isNamespacedURL($prefixed_url) && ($shorthand = getNamespacePortion($prefixed_url))){
			$url = $this->getURL($shorthand);
			if($url){
				return $url . substr($prefixed_url, strlen($shorthand) + 1);
			}
		}
		return false;
	}
}
