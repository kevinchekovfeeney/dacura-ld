<?php

class Ontology extends LDO {
	//var $url; //the id is the local id, this is the official id
	//var $title;
	//var $description;
	//var $real_version;
	//var $filename;
	
	function __construct($id, $logger = false){
		parent::__construct($id, false, $logger);
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
		$this->logger->timeEvent("Exported to php form", "debug");
		$this->ldprops[$this->id] = importEasyRDFPHP($op);
		$this->logger->timeEvent("Reimported for us", "debug");
		$this->meta = array();
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
			$nsres->getNamespaceUtilisation($subj, $props, false, $nsutil);			
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
			$nsres->getNamespaceUtilisation($subj, $props, false, $nsutil);			
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
	
	function getClassHierarchy(){
		$subtypes = array();
		$parents = array();
		$children = array();
		$this->compressNS();
		foreach($this->ldprops[$this->id] as $id => $props){
			if(isset($props['rdfs:subClassOf'])){
				if(!in_array($id, $children)) $children[] = $id;
				$pts = is_array($props['rdfs:subClassOf']) ? $props['rdfs:subClassOf'] : array($props['rdfs:subClassOf']);
				foreach($pts as $parent){
					if(!in_array($parent, $parents)){
						$parents[] = $parent;
					}
					if(!isset($subtypes[$parent])){
						$subtypes[$parent] = array($id);
					}
					else {
						if(!in_array($id, $subtypes[$parent])){
							$subtypes[$parent][] = $id;
						}
					}						
				}
			}
		}
		$hierarchy = array();
		foreach($parents as $parent){
			if(!in_array($parent, $children)){
				$hierarchy[$parent] = array("children" => array());
				foreach($subtypes[$parent] as $st){
					$hierarchy[$parent]['children'] = $this->getHierarchy($st, $subtypes);
				}				
			}			
		}
		return $hierarchy;		
	}
	
	function getHierarchy($cls, $subtypes){
		$hierarchy = array($cls => array());
		if(isset($subtypes[$cls])){
			foreach($subtypes[$cls] as $st){
				$hierarchy[$cls]['children'] = $this->getHierarchy($st, $subtypes);
			}				
		}
		return $hierarchy;
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
	
	function displayQuads($flags, $vstr, $srvr){
		$this->display = $this->getPropertyAsQuads($this->id, $this->id);
	}
	
}