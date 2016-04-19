<?php

require_once("SparqlBridge.php");


class Displayer {
	var $sparql;
	var $schema_graph_name;
	var $endpoint_url;

	function __construct($gname, $endp){
		$this->schema_graph_name = $gname;
		$this->endpoint_url = $endp;
		$this->sparql = new SparqlBridge($endp);
	}

	function displayInstances($sparql){
		$this->sparql->print_result_fields($this->sparql->askSparql($sparql));
	}

	function getClassProperties($cls){
		print_r($this->sparql->getEntityProperties($cls, $this->schema_graph_name));
	}


}

