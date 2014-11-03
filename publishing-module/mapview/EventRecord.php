<?php

require_once("SparqlBridge.php");
require_once("NSURI.php");


class EventRecord {

	var $id;

	var $base_class;
	var $motivation = array('values' => array());
	var $category = array('values' => array());
	var $location = array('values' => array());
	var $edate = array('values' => array());
	var $source = array('values' => array());
	var $description = array('values' => array());
	var $fatalities = array('values' => array());
	var $duplicate = array('values' => array());

	var $ep;
	var $sg;
	var $dg;
	var $ns;
	var $pv;
	var $sb;
	
	var $err_str = "";	


	function __construct($id){
		$this->ns = new NSURI();
		$this->pv = $this->ns->getnsuri("pv");
		$this->id = $id;
	}

	function getErrorString(){
		return "Record Error: ".$this->err_str;
	}
	
	function setDataSource($endpoint, $schema_graph, $data_graph){
		$this->ep = $endpoint;
		$this->sg = $schema_graph;
		$this->dg = $data_graph;
		$this->sb = new SparqlBridge($endpoint);
	}

	function loadFromDB($load_meta = false){
		$this->loadMotivation($load_meta);
		$this->loadCategory($load_meta);
		$this->loadFatalities($load_meta);
		$this->loadDescription($load_meta);
		$this->loadLocation($load_meta);
		$this->loadEdate($load_meta);
		$this->loadSource($load_meta);
		//$this->loadDuplicate();
		return true;
	}


	function getPrefixString(){
		$str = "";
		foreach(array("rdf", "rdfs") as $p){
			$str .= "prefix $p: <".$this->ns->getnsuri($p).">\n";
		}
		return $str;
	}

	function show(){
		echo "<PRE>";
		print_r($this->getAsArray());
		echo "</PRE>";
	}

	function getAsArray(){
		$arr = array(
				"motivation" => $this->motivation,
				"category" => $this->category,
				"fatalities" => $this->fatalities,
				"description" => $this->description,
				"location" => $this->location,
				"edate" => $this->edate,
				"source"	=> $this->source,
		);
		return $arr;
	}


	function loadMotivation($load_meta = false){
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?motivation_url ?motivation_label ?motivation_comment {
			GRAPH <$this->dg> {
				<$this->id>  <".$this->pv."motivation> ?motivation_url.
			}
			GRAPH <$this->sg> {
				?motivation_url rdfs:label ?motivation_label;
				rdfs:comment ?motivation_comment.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		while( $row = $res->fetch_array() ) {
			if(!isset($this->motivation['values'][$row['motivation_url']])){
				$this->motivation['values'][$row['motivation_url']] = array(
				"type" => "select",
				"label" => $row['motivation_label'],
					"comment" => $row['motivation_comment']
				);
			}
		}
		if($load_meta){
			$this->motivation['meta'] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?lab ?com {
			GRAPH <$this->sg> {
				<".$this->pv."Motivation> rdfs:label ?lab;
							rdfs:comment ?com;
				}
			}";
			$res = $this->sb->askSparql($sparql);
			if($row = $res->fetch_array()){
				$this->motivation['meta']['label'] = $row['lab'];
				$this->motivation['meta']['comment'] = $row['com'];
			}
		}
	}

	function loadCategory($load_meta = false){
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?category_url ?category_label ?category_comment {
			GRAPH <$this->dg> {
				<$this->id>  <".$this->pv."category> ?category_url.
			}
			GRAPH <$this->sg> {
				?category_url rdfs:label ?category_label;
				rdfs:comment ?category_comment.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		while( $row = $res->fetch_array() ) {
			if(!isset($this->category['values'][$row['category_url']])){
				$this->category['values'][$row['category_url']] = array(
					"type" => "select",
					"label" => $row['category_label'],
					"comment" => $row['category_comment']
				);
			}
		}
		if($load_meta){
			$this->category['meta'] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?lab ?com {
			GRAPH <$this->sg> {
				<".$this->pv."Category> rdfs:label ?lab;
				rdfs:comment ?com;
				}
			}";
			$res = $this->sb->askSparql($sparql);
			if($row = $res->fetch_array()){
				$this->category['meta']['label'] = $row['lab'];
				$this->category['meta']['comment'] = $row['com'];
			}
		}
	}

	function loadDescription($load_meta = false){
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?descr {
			GRAPH <$this->dg> {
				<$this->id>  <".$this->pv."description> ?descr.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		while($row = $res->fetch_array()){
			$this->description['values'][] = $row["descr"];
		}
	}

	function loadFatalities($load_meta = false){
		//Next fatalities
		$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?fatalities_url  {
			GRAPH <$this->dg> {
				<$this->id>  <".$this->pv."fatalities> ?fatalities_url.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		$row = $res->fetch_array();
		if($row){
			$fat_url = $row['fatalities_url'];
			if($fat_url == $this->pv."unknownFatalities"){
				$this->fatalities['values'][$fat_url] = "unknown";
			}
			else {
				$this->fatalities['values'][$fat_url] = array();
				$sparql = $this->getPrefixString();
				$sparql .= "SELECT ?fatalities_unstruct {
					GRAPH <$this->dg> {
						<$fat_url> <".$this->pv."unstructuredFatalities> ?fatalities_unstruct.
					}
				}";
				$res = $this->sb->askSparql($sparql);
				$row = $res->fetch_array();
				if($row){
					$this->fatalities['values'][$fat_url]["unstructured"] = $row["fatalities_unstruct"];
				}
				$sparql = $this->getPrefixString();
				$sparql .= "SELECT ?val_fatalities {
					GRAPH <$this->dg> {
						<$fat_url>  <".$this->pv."fatalitiesValue> ?val_fatalities;
					}
				}";
				$res = $this->sb->askSparql($sparql);
				$row = $res->fetch_array();
				if($row){
					$this->fatalities['values'][$fat_url]["value"] = $row["val_fatalities"];
				}
				$sparql = $this->getPrefixString();
				$sparql .= "SELECT ?max_fatalities ?min_fatalities {
					GRAPH <$this->dg> {
						<$fat_url>  <".$this->pv."maximumFatalities> ?max_fatalities;
						<".$this->pv."minimumFatalities> ?min_fatalities;
					}
				}";
				$res = $this->sb->askSparql($sparql);
				$row = $res->fetch_array();
				if($row){
					$this->fatalities['values'][$fat_url]["min"] = $row["min_fatalities"];
					$this->fatalities['values'][$fat_url]["max"] = $row["max_fatalities"];
				}
			}
		}
		if($load_meta){
			$this->fatalities['meta'] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?lab ?com {
				GRAPH <$this->sg> {
					<".$this->pv."Fatalities> rdfs:label ?lab;
					rdfs:comment ?com;
				}
			}";
			$res = $this->sb->askSparql($sparql);
			if($row = $res->fetch_array()){
				$this->fatalities['meta']['label'] = $row['lab'];
				$this->fatalities['meta']['comment'] = $row['com'];
			}
		}
	}

	function loadLocation($load_meta = false){
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?loc_id {
			GRAPH <$this->dg> {
				<$this->id>  <".$this->pv."location> ?loc_id.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		$row = $res->fetch_array();
		if($row){
			$loc_id = $row["loc_id"];
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?unstruc_loc ?lat ?long {
				GRAPH <$this->dg> {
					<$loc_id>  <".$this->pv."unstructuredLocation> ?unstruc_loc.
					<$loc_id>  <http://www.w3.org/2003/01/geo/wgs84_pos#lat> ?lat.
					<$loc_id>  <http://www.w3.org/2003/01/geo/wgs84_pos#long> ?long
				}
			}";
			$res = $this->sb->askSparql($sparql);
			$row = $res->fetch_array();
			if($row){
				$this->location['values'][$loc_id]["unstructured"] = $row["unstruc_loc"];
				$this->location['values'][$loc_id]["lat"] = $row["lat"];
				$this->location['values'][$loc_id]["long"] = $row["long"];
			}
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?dbp_loc {
				GRAPH <$this->dg> {
					<$loc_id>  <".$this->pv."dbpediaLocation> ?dbp_loc.
				}
			}";
			$res = $this->sb->askSparql($sparql);
			while($row = $res->fetch_array()){
				if(!isset($this->location['values'][$loc_id]["dbpedia"])){
					$this->location['values'][$loc_id]["dbpedia"] = array();
				}
				$this->location['values'][$loc_id]["dbpedia"][] = $row["dbp_loc"];
			}
		}
		if($load_meta){
			$this->location['meta'] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?lab ?com {
			GRAPH <$this->sg> {
				<".$this->pv."Location> rdfs:label ?lab;
				rdfs:comment ?com;
				}
			}";
			$res = $this->sb->askSparql($sparql);
			if($row = $res->fetch_array()){
				$this->location['meta']['label'] = $row['lab'];
				$this->location['meta']['comment'] = $row['com'];
			}
		}
	}

	function loadEdate($load_meta = false){
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?attime_id ?attime_descr_id {
		GRAPH <$this->dg> {
			<$this->id>  <".$this->pv."atTime> ?attime_id.
			?attime_id  <".$this->ns->getnsuri("time")."hasDateTimeDescription> ?attime_descr_id.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		$row = $res->fetch_array();
		if($row){
			$time_id = $row['attime_id'];
			$this->edate['values'][$time_id] = array();
			$time_descr_id = $row['attime_descr_id'];
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?prop ?val {
			GRAPH <$this->dg> {
				<$time_descr_id>  ?prop ?val.
				}
			}";
			$res = $this->sb->askSparql($sparql);
			while($row = $res->fetch_array()){
				if($row['prop'] == $this->ns->getnsuri("time")."unitType"){
					$this->edate['values'][$time_id]['granularity'] = strtolower(substr($row['val'], strlen($this->ns->getnsuri("time")) + 4));
				}
				elseif($row['prop'] == $this->ns->getnsuri("time")."year"){
					$this->edate['values'][$time_id]['year'] = $row['val'];
				}
				elseif($row['prop'] == $this->ns->getnsuri("time")."month"){
					$this->edate['values'][$time_id]['month'] = substr($row['val'], 2, 2);
				}
				elseif($row['prop'] == $this->ns->getnsuri("time")."day"){
					$this->edate['values'][$time_id]['day'] = substr($row['val'], 2, 2);
				}
				$this->edate['values'][$time_id][$row['prop']] = $row['val'];
				$this->edate['values'][$time_id]['type'] = "Simple";
				$this->edate['values'][$time_id]['iptype'] = "Partial";
			}
		}
		if($load_meta){
			$this->edate['meta'] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?lab ?com {
			GRAPH <$this->sg> {
				<".$this->pv."atTime> rdfs:label ?lab;
				rdfs:comment ?com;
				}
			}";
			$res = $this->sb->askSparql($sparql);
			if($row = $res->fetch_array()){
				$this->edate['meta']['label'] = $row['lab'];
				$this->edate['meta']['comment'] = $row['com'];
			}
		}
	}

	function loadSource($load_meta = false){
		$sparql = $this->getPrefixString();
		$sparql .= "SELECT ?src_id {
			GRAPH <$this->dg> {
			<$this->id>  <".$this->pv."source> ?src_id.
			}
		}";
		$res = $this->sb->askSparql($sparql);
		$row = $res->fetch_array();
		if($row){
			$src_id = $row['src_id'];
			$this->source['values'][$src_id] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?val {
				GRAPH <$this->dg> {
				<$src_id>  <".$this->pv."unstructuredSource> ?val.
				}
			}";
			$res = $this->sb->askSparql($sparql);
			$row = $res->fetch_array();
			if($row){
				$this->source['values'][$src_id]["unstructured"] = $row["val"];
			}
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?dbp_src {
				GRAPH <$this->dg> {
					<$src_id>  <".$this->pv."dbpediaSource> ?dbp_src.
				}
			}";
			$res = $this->sb->askSparql($sparql);
			while($row = $res->fetch_array()){
				if(!isset($this->source['values'][$src_id]["dbpedia"])){
					$this->source['values'][$src_id]["dbpedia"] = array();
				}
				$this->source['values'][$src_id]["dbpedia"][] = $row["dbp_src"];
			}
		}
		if($load_meta){
			$this->source['meta'] = array();
			$sparql = $this->getPrefixString();
			$sparql .= "SELECT ?lab ?com {
				GRAPH <$this->sg> {
				<".$this->pv."Source> rdfs:label ?lab;
				rdfs:comment ?com;
				}
			}";
			$res = $this->sb->askSparql($sparql);
			if($row = $res->fetch_array()){
				$this->source['meta']['label'] = $row['lab'];
				$this->source['meta']['comment'] = $row['com'];
			}
		}
	}
}


