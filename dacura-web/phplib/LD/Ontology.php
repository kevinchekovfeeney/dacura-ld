<?php

require_once("LDEntity.php");

class Ontology extends LDEntity {
	var $url; //the id is the local id, this is the official id
	var $title;
	var $description;
	var $real_version;
	var $filename;
	var $imported;
	var $egraph;
	
	function __construct($id){
		parent::__construct($id);
		$this->egraph = new EasyRdf_Graph($id);
	}
	
	function genFname(){
		$fid = "ONT". randid();
		return $fid;
	}
	
	function setFilename($fname){
		$this->filename = $fname;
	}
	
	function getTitle(){
		return $this->title;
	}

	function getDescription(){
		return $this->description;
	}
	
	function store($internal = false){
		if(!$internal){
			$cnts = $this->export("turtle");
			if(!($cnts && file_put_contents($this->filename, $cnts))){
				return $this->failure_result("Failed to store ontology $this->id to $this->filename", 500);
			}
		}
		$obj = array(
			"id" => $this->id,
			"url" => $this->url,
			"title" => $this->getTitle(),
			"description" => $this->getDescription(),
			"real_version" => $this->real_version,
			"status" => $this->status,
			"version" => $this->version,
			"file" => $this->filename
		);
		if($internal && $this->ldprops){
			$obj['contents'] = $this->ldprops[$this->id];
		}
		return $obj;
	}
	
	function loadFromStorage($o){
		//$this->ldprops = $o['contents'];	
		$this->url = isset($o['url']) ? $o['url'] : "";
		$this->title = $o['title'];
		$this->description = $o['description'];
		$this->filename = $o['file'];
		$this->status = $o['status'];
		$this->version = (isset($o['version'])? $o['version'] : 1);
		$this->latest_version = $this->version;
		if(isset($o['contents']) && $o['contents']){
			$this->imported = true;
			$this->ldprops = array($this->id => $o['contents']);
			return true;
		}
		else {
			$this->imported = false;
			return $this->import("file", $this->filename, $this->id);
		}
	}
	
	function import($type, $arg, $gurl = false, $format = false, $novel = false){
		$this->imported = false;
		$this->egraph = $this->importERDF($type, $arg, $gurl, $format);
		if(!$this->egraph){
			return false;
		}
		if($type == "url"){
			$this->url = $arg;
		}
		$op = $this->egraph->toRdfPhp();//$this->egraph->serialise("php");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->expand();
		$this->extractDetails($novel);
		$errs = validLD($this->ldprops, $this->cwurl);
		if(count($errs) > 0){
			$msg = "<ul><li>".implode("<li>", $errs)."</ul>";
			return $this->failure_result("Graph had ". count($errs)." errors. $msg", 400);
		}
		return true;
	}
	
	function extractDetails($novel = false){
		$nsres = new NSResolver(false, false, true);
		$rdfst = array($nsres->expand("rdfs:type"));
		$classes = getNodesWithPredicate($this->id, $this->ldprops[$this->id], $rdfst);
		if(count($classes) > 0){
			$this->classes = array_keys($classes);
		}
	}
	
}