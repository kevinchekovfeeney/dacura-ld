<?php

/*
 * Class representing a Linked Data Document (LD object + state) in the Dacura DB
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

include_once("LD.php");

/*
 * maintains state about a particular LD object
 */

class LDDocument extends LD {
	var $id;
	var $contents;
	var $index = false; //obj_id => &$obj
	var $bad_links = array(); //different types of bad links in the document
	var $idmap = array(); //blank nodes that have been mapped to new names

	
	function __construct($id, $cwurl){
		$this->id = $id;
		parent::__construct($cwurl);
	}
	
	function __clone(){
		$this->contents = deepArrCopy($this->contents);
		$this->index = false;
		$this->bad_links = deepArrCopy($this->bad_links);
	}
	
	
	/*
	 * Generates a globally unique id for a fragment within the json ld object
	 * by concatenating the object id and a unique id thingie
	 */
	function genid($bn = false) {
		if($bn == "_:candidate"){
			return "local:".$this->id."/candidate";
		}
		return "local:".$this->id . "/" . uniqid_base36(false);
	}
	
	function get_json($key = false){
		if($key){
			return json_encode($this->contents[$key]);
		}
		return json_encode($this->contents);
	}

	function getFragment($fid){
		if($this->index === false){
			$this->buildIndex();
		}
		return isset($this->index[$fid]) ? $this->index[$fid] : false;
	}

	function hasFragment($frag_id){
		if($this->index === false){
			$this->buildIndex($this->contents, $this->index);
		}
		return isset($this->index[$frag_id]);
	}

	function load($arr){
		$this->contents = $arr;
	}

	function loadFromAPI($obj){
		$this->contents = $obj;
	}

	function buildIndex(){
		return parent::buildIndex($this->contents, $this->index);
	}

	function get_json_ld(){
		$ld = $this->contents;
		$ld["@id"] = $this->id;
		return $ld;
	}

	function expand(){
		$rep = parent::expand($this->contents);
		if($rep === false){
			return false;
		}
		if(isset($rep["missing"])){
			$this->bad_links['unresolved_local'] = $rep["missing"];
		}
		$this->idmap = $rep['idmap'];
		return true;
	}
	
	function problems(){
		if(count($this->bad_links) > 0){
			return $this->bad_links;
		}
		return false;
	}
	
	function triples(){
		return $this->getObjectAsTriples($this->id, $this->contents);
	}
	
	function compliant(){
		//return true;
		return parent::validate($this->contents);
	}
	
	function isDocumentLocalLink($v){
		return (substr($v, 0, 6) == "local:" && substr($v, 6) == $this->id) && (substr($v, 0, strlen($this->id)) == $this->id);
	}

	function update($update_obj){
		$idmap = array();
		$this->applyUpdates($update_obj, $this->contents, $idmap);
		if(count($idmap) > 0){
			$unresolved = $this->updateBNReferences($this->contents, $idmap);
			if($unresolved === false){
				return false;
			}
			elseif(count($unresolved) > 0){
				$this->bad_links['unresolved_local'] = $unresolved;
			}
			$this->idmap = array_merge($this->idmap, $idmap);
		}
		$this->buildIndex();
		return true;
	}
	
	function findMissingLinks(){
		if($this->index === false){
			$this->buildIndex($this->contents, $this->index);
		}
		$ml = parent::findInternalMissingLinks($this->contents, array_keys($this->index), $this->id);
		$x = count($ml);
		if($x > 0){ 
			$this->bad_links['unresolved_local'] = $ml;
		}
		return $x;
	}
	
	/*
	 * Calculates the transforms necessary to get other from current
	 */
	function compare($other){
		return $this->analyseUpdate($this->id, $this->contents, $other->contents);
	}
	
}
