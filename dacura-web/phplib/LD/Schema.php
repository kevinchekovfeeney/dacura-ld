<?php
/*
 * This class provides the mapping from internal dacura ids to urls for both external and internal access.
 * All dacura datasets have the same addressing scheme:
 * 	* The prefix "http://dacura.scss.tcd.ie/dacura/collection_id/dataset_id/candidate/" 
 * 	  is added to the internal id to give the address of the triple for internal access.
 * 	* The id (URL) of the schema itself is http://dacura.scss.tcd.ie/dacura/collection_id/dataset_id/schema
 * 	Note: this is non-configurable, these are the addresses of the dacura services that manage the relevant data. 
 *	* Both schema URL and the instance prefix can be changed by configuration, 
 *	  the default is that they point to /dacura/data/-> public data site...(with mappings) 
 *  * All schemas contain a minimum required set of prefixes
 *  * Includes all/all -> dacura
 *  * Includes cid/all (if dsid != all)
 *
 */

class Schema {
	var $cid; 
	var $did;
	var $id; // always dacura/cid/did/schema
	var $shorthand; //either "dacura", "colid", "dsid" (note we can't have clashes in colid / dsid
	var $public_id; // mapping to different url for external consumption
	var $instance_prefix; //the base url that all instance urls in the dataset are relative to
	var $instance_public_prefix; //the base url for public consumption of instance data
	var $prefixes;
	var $public_prefixes;
	
	/*
	 * 
	 */
	var $required_prefixes = array(
			"rdf" => "http://www.w3.org/1999/02/22-rdf-syntax-ns#",
			"rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
			"xsd" => "http://www.w3.org/2001/XMLSchema#",
			"owl" =>  "http://www.w3.org/2002/07/owl",
			"prov" => "http://www.w3.org/ns/prov#",
			"oa" => "http://www.w3.org/ns/oa#",
	);
	
	function __construct($cid, $did, $base_url){
		$this->cid = $cid;
		$this->did = $did;
		$idbase = $base_url . $this->cid."/".$this->did."/";
		$this->id = $idbase."schema/";
		$this->instance_prefix = $idbase."candidate/";
		$this->prefixes = $this->required_prefixes;
		$this->prefixes["local"] = $this->instance_prefix;
		$this->prefixes["dacura"] = $base_url."all/all/schema#";
		if($this->cid != "all" && $this->did == "all"){
			$this->prefixes[$this->cid] = $base_url.$this->cid."/all/schema#";
		}
		elseif($this->cid != "all"){
			$this->prefixes[$this->did] = $base_url.$this->cid."/".$this->did."/schema#";				
		}
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
	
	function compress($url){
		foreach($this->prefixes as $shorthand => $id){
			if(substr($url, strlen($id)) == $id){
				return $shorthand.":".substr($url, - (strlen($id) + 1));
			}
		}
		return false;
	}
	
	function expand($prefixed_url){
		//echo $prefixed_url . " is the url<br>";
		if(isNamespacedURL($prefixed_url) && ($shorthand = getNamespacePortion($prefixed_url))){
			$url = $this->getURL($shorthand); 
			if($url){
				return $url . substr($prefixed_url, strlen($shorthand) + 1);
			}
		}
		return false;
	}
}