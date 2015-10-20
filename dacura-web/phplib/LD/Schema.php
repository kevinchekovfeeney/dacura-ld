<?php
/*
*/

require_once("NSResolver.php");
require_once("LDEntity.php");

class Schema extends DacuraObject {
	var $cid;
	var $did;
	var $idbase;
	var $instance_prefix;
	var $ns_prefix;
	var $graphs = array();
	
	function __construct($cid, $did, $base_url){
		$this->cid = $cid;
		$this->did = $did;
		if($cid == "all"){
			$this->idbase = $base_url;
		}
		elseif($did == "all"){
			$this->idbase = $base_url.$cid."/";
		}
		else {
			$this->idbase = $base_url.$cid."/".$did."/";
		}
		$this->instance_prefix = $this->idbase."report";
		$this->ns_prefix = $this->idbase."ns#";
	}
	
	function load($graphs){
		$this->graphs = $graphs;
	}
	
	function getNGSkeleton(){
		$skel = array("ldprops" => array(), "meta" => array());
		foreach($this->graphs as $ent){
			$skel["ldprops"][$ent["id"]] = array();
		}
		return $skel;
	}
	
	
}
