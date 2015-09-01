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
			"owl" => "http://www.w3.org/2002/07/owl#",
			"prov" => "http://www.w3.org/ns/prov#",
			"oa" => "http://www.w3.org/ns/oa#",
	);

	function __construct($dacura_url, $local_url = false, $set_defaults = true){
		if($set_defaults){
			$this->prefixes = $this->default_prefixes;
		}
		$this->prefixes["dacura"] = $dacura_url;
		if($local_url){
			$this->prefixes['local'] = $local_url;
		}
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
