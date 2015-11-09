<?php

require_once("LDEntity.php");

class Ontology extends LDEntity {
	//var $url; //the id is the local id, this is the official id
	//var $title;
	//var $description;
	//var $real_version;
	//var $filename;
	
	function __construct($id, $logger = false){
		parent::__construct($id, false, $logger);
	}
	
	function isOntology(){
		return true;
	}
	
	function import($type, $arg, $gurl = false, $format = false, $novel = false){
		$this->imported = false;
		$egraph = $this->importERDF($type, $arg, $gurl, $format);
		$this->logger->timeEvent("Imported", "debug");
		if(!$egraph){
			return false;
		}
		if($type == "url"){
			$this->url = $arg;
		}
		$op = $egraph->toRdfPhp();//$this->egraph->serialise("php");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->meta = array();//$this->extractMeta($egraph, $novel, $this->nsres);
		$errs = validLD($this->ldprops);
		if(count($errs) > 0){
			$msg = "<ul><li>".implode("<li>", $errs)."</ul>";
			return $this->failure_result("Graph had ". count($errs)." errors. $msg", 400);
		}
		return true;
	}
	
	function getIncludedOntologies($nsres){
		$incs = array();
		$nsutil = array();
		foreach($this->ldprops[$this->id] as $subj => $props){
			getDeepNamespaceUtilisation($subj, $props, $nsres, false, $nsutil);			
		}
		foreach($nsutil as $sh => $contents){
			if(count($contents["properties"]) > 0 or count($contents["structural"]) > 0){
				$incs[] = $sh;
			}
		}
		return $incs;
	}
	
	function generateDependencies($nsres){
		//$nslist = $this->getERDFSupportedNamespaces();
		//$nsres = new NSResolver(false, false, true);
		//$nsres->setPrefixMap($nslist);
		$nsutil = array();
		foreach($this->ldprops[$this->id] as $subj => $props){
			getDeepNamespaceUtilisation($subj, $props, $nsres, false, $nsutil);			
		}
		if(isset($nsutil["_"])){
			foreach($nsutil["_"]["properties"] as $prop => $c){
				if(isset($nsutil[$this->id]["properties"][$prop])){
					$nsutil[$this->id]["properties"][$prop] += $c;
				}
				else {
					$nsutil[$this->id]["properties"][$prop] = $c;						
				}
			}
			foreach($nsutil["_"]["subject"] as $prop => $c){
				if(isset($nsutil[$this->id]["subject"][$prop])){
					$nsutil[$this->id]["subject"][$prop] += $c;
				}
				else {
					$nsutil[$this->id]["subject"][$prop] = $c;						
				}
			}
			if(isset($nsutil[$this->id]["structural"]) && isset($nsutil["_"]["structural"])){
				$nsutil[$this->id]["structural"] = array_merge($nsutil[$this->id]["structural"], $nsutil["_"]["structural"]);
			}
			if(isset($nsutil[$this->id]["object"]) && isset($nsutil["_"]["object"])){
				$nsutil[$this->id]["object"] = array_merge($nsutil[$this->id]["object"], $nsutil["_"]["object"]);
			}
			unset($nsutil["_"]);
		}
		$deps = array();
		foreach($nsutil as $sh => $contents){
			$deps[$sh] = array(
					"url" => $nsres->getURL($sh), 
					"properties" => isset($contents["properties"]) ? $contents["properties"] : array(), 
					"subject" => isset($contents["subject"]) ? $contents["subject"] : array(), 
					"structural" => isset($contents["structural"])? $contents["structural"]: array(), 
					"object" => isset($contents["object"]) ? $contents["object"] : array(), 
					"distinct_properties" => isset($contents["properties"]) ? count($contents["properties"]) : 0, 
					"distinct_subjects" => isset($contents["subject"]) ? count($contents["subject"]) : 0, 
					"structural_links" => isset($contents["structural"])? count($contents['structural']) : 0,
					"values_used" => isset($contents["object"]) ? count($contents['object']) : 0,
					"properties_used" => 0,
					"subjects_used" => 0
			); 
			if(isset($contents["properties"] )){
				foreach($contents["properties"] as $p => $c){
					$deps[$sh]["properties_used"] += $c;
				}
			}
			if(isset($contents["subject"])){
				foreach($contents["subject"] as $p => $c){
					$deps[$sh]["subjects_used"] += $c;
				}			
			}
		}
		return $deps;
	}
	
	function extractMeta($egraph, $novel = false, $nsres){
		$meta = array("dependencies" => $this->generateDependencies($nsres));
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