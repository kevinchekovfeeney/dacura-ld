<?php

require_once("LDEntity.php");

class Ontology extends LDEntity {
	//var $url; //the id is the local id, this is the official id
	//var $title;
	//var $description;
	//var $real_version;
	//var $filename;
	
	function __construct($id){
		parent::__construct($id);
	}
	
	function isOntology(){
		return true;
	}
	
	function import($type, $arg, $gurl = false, $format = false, $novel = false){
		$this->imported = false;
		$egraph = $this->importERDF($type, $arg, $gurl, $format);
		if(!$egraph){
			echo $arg;
			return false;
		}
		if($type == "url"){
			$this->url = $arg;
		}
		$op = $egraph->toRdfPhp();//$this->egraph->serialise("php");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->meta = $this->extractMeta($egraph, $novel);
		$errs = validLD($this->ldprops);
		if(count($errs) > 0){
			$msg = "<ul><li>".implode("<li>", $errs)."</ul>";
			return $this->failure_result("Graph had ". count($errs)." errors. $msg", 400);
		}
		return true;
	}
	
	function generateDependencies(){
		$nslist = $this->getERDFSupportedNamespaces();
		$nsres = new NSResolver(false, false, true);
		$nsres->setPrefixMap($nslist);
		$nsutil = getNamespaceUtilisation($this->ldprops, $nsres, false, "all");
		$deps = array();
		foreach($nsutil as $sh => $contents){
			$deps[$sh] = array("url" => $nsres->getURL($sh), "occurrences" => count($contents));
		}
		return $deps;
	}
	
	function extractMeta($egraph, $novel = false){
		$meta = array("dependencies" => $this->generateDependencies());
		return $meta;
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
	

	
}