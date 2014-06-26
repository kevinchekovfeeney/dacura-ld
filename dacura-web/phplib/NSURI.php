<?php

class NSURI {
	var $ns = array(
			"rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
			"rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
			"pv" => "http://tcdfame.cs.tcd.ie/data/politicalviolence#",
			"xsd" => "http://www.w3.org/2001/XMLSchema#",
			"owl" =>  "http://www.w3.org/2002/07/owl",
			"dc" => "http://purl.org/dc/elements/1.1/",
			"foaf" => "http://xmlns.com/foaf/0.1/",
			"dbpedia-owl" => "http://dbpedia.org/ontology/",
			"vcard" => "http://www.w3.org/2006/vcard/ns#",
			"gn" =>  "http://www.geonames.org/ontology/ontology_v3.1.rdf#",
			"oa" => "http://www.w3.org/ns/oa",
			"time" => "http://www.w3.org/2006/time#"
	);
	
	function getnsuri($pref){
		return (isset($this->ns[$pref]) ? $this->ns[$pref] : false);
	}
}
