<?php

include_once("Ontology.php");

class Graph extends Ontology {
	
	function getLocalOntology(&$srvr) {
		$id = isset($this->meta['prefix'])? $this->meta['prefix'] : $this->id;
		$url = isset($this->meta['url'])? $this->meta['url'] : $srvr->getGraphSchemaGraph($this->id); 
		$title = isset($this->meta['title']) ? $this->meta['title'] : "Graph $this->id local ontology";
		$local_ont = new Ontology($id);
		$local_ont->ldprops = array($id => $this->ldprops[$this->id]);
		$local_ont->meta = array("url" => $url, "title" => $title);
		$local_ont->copyBasics($this);
		$local_ont->nsres = $this->nsres;
		return $local_ont;
	}

	function getImportedOntologies(){
		if(isset($this->meta['imports'])){
			return $this->meta['imports'];
		}
		return array();
	}
	
	function hasLocalOntology(){
		return (isset($this->ldprops[$this->id]) && count($this->ldprops[$this->id]) > 0);
	}
	
}
