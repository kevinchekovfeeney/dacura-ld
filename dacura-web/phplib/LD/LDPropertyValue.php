<?php

/*
 * Class representing the value of a linked data property
 * At the highest level, values are broken down into:
 * 1. literal: v
 * 2. value list [v, v, ]
 * 3. embedded object {}
 * 4. object list [{}, {}]
 * 5. embedded object list { id: {}, id: {} }
 * This depends on strongly typed property ranges and (somewhat) on the allocation of ids to all nodes in the dataset.
 *
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */

class LDPropertyValue extends DacuraObject {
	var $json_type;//empty, literal, array, object
	var $literal_type;//BN, CW, OW (URL), L
	var $array_type;//literal, object
	var $array_literal_types;//array of(BN, CN, OW, L) for types of
	var $object_type;//EO, EOL
	var $cw_base;

	function __construct($val, $cw_base){
		$this->cw_base = $cw_base;
		$this->load($val);
	}

	function getLiteralType($val){
		if(isBlankNode($val)){
			return "BN";
		}
		elseif($this->isCWLink($val)){
			return "CW";
		}
		elseif(isURL($val)){
			return "OW";
		}
		else {
			return "L";
		}
	}

	function isCWLink($v){
		return substr($v, 0, 6) == "local:" or substr($v, 0, strlen($this->cw_base)) == $this->cw_base;
	}

	function sameLDType($other){
		return $this->ldtype() == $other->ldtype();
	}

	function loadArrayDetails($arr){
		$vtypes = array("l" => 0, "a" => 0, "o" => 0);
		foreach($arr as $wun){
			if(!is_array($wun)){
				$vtypes["l"]++;
			}
			elseif(isAssoc($wun)){
				$vtypes['o']++;
			}
			else {
				$vtypes['a']++;
			}
		}
		if($vtypes["a"] > 0){
			return $this->failure_result("Arrays cannot directly contain other arrays in JSON LD", 400);
		}
		if($vtypes["l"] > 0 && $vtypes["o"] > 0){
			return $this->failure_result("Arrays cannot contain a mix of strings and embedded objects in JSON LD", 400);
		}
		if($vtypes['o'] > 0){
			$this->array_type = "object";
		}
		elseif($vtypes['l'] > 0){
			$this->array_type = "literal";
			$this->array_literal_types = array();
			foreach($arr as $str){
				$this->array_literal_types[] = $this->getLiteralType($str);
			}
		}
		else {
			return $this->failure_result("No valid types found in LD property array - strange", 500);
		}
		return true;
	}

	function loadObjectDetails($obj){
		//big requirement to be able to distinguish between closed world ids and BN ids and property names -
		//they can't share the same url base
		//this is needed to distinguish embedded object lists and embedded objects.
		$p = 0;
		$ids = array("BN" => array(), "CW" => array(), "DEL" => array(), "UPD" => array());
		foreach($obj as $id => $val){
			//note: if it is embeded object list id can't point to [] -> not allowed in JSON LD
			if(isBlankNode($id)){
				$ids["BN"][] = $id;
				if(!is_array($val) or count($val) == 0){
					return $this->failure_result("Blank node $id has empty value - it must contain an embedded object", 400);
				}
			}
			elseif($this->isCWLink($id)){
				$ids["CW"][] = $id;
				if(!is_array($val) or count($val) == 0){
					$ids["DEL"][] = $id;
				}
				else {
					$ids["UPD"][] = $id;
				}
			}
			else {
				$p++;
			}
		}
		if($p > 0 && (count($ids["BN"]) > 0 or count($ids["CW"]) > 0)){
			return $this->failure_result("Illegal construct - contains both properties and internal ids - embedded object  or list?", 400);
		}
		elseif($p > 0){
			$this->object_type = "EO";
		}
		else {
			$this->object_type = "EOL";
			$this->obj_id_types = $ids;
		}
		return true;
	}

	function load($val){
		if(!is_array($val)) {
			$this->json_type = "literal";
			$this->literal_type = $this->getLiteralType($val);
		}
		else {
			if(count($val) == 0){
				$this->json_type = "empty";
			}
			elseif(isAssoc($val)){
				$this->json_type = "object";
				$this->loadObjectDetails($val);
			}
			else {
				$this->json_type = "array";
				$this->loadArrayDetails($val);
			}
		}
	}

	function legal(){
		return $this->errcode <= 0;
	}

	function illegal(){
		return $this->errcode > 0;
	}

	function cwlink(){
		return $this->json_type == "literal" && $this->literal_type == "CW";
	}

	function owlink(){
		return $this->json_type == "literal" && $this->literal_type == "OW";
	}

	function literal(){
		return $this->json_type == "literal";
	}
	
	function isempty(){
		return $this->json_type == "empty";
	}

	function bn(){
		return $this->json_type == "literal" && $this->literal_type == "BN";
	}

	function valuelist(){
		return $this->json_type == "array" && $this->array_type == "literal";
	}

	function objectlist(){
		return $this->json_type == "array" && $this->array_type == "object";
	}

	function getupdates(){
		return $this->obj_id_types["UPD"];
	}

	function getbnids(){
		return $this->obj_id_types['BN'];
	}

	function getdelids(){
		return $this->obj_id_types['DEL'];
	}

	function ldtype(){
		if($this->literal()) return "literal";
		if($this->valuelist()) return "valuelist";
		if($this->embedded()) return "embedded";
		if($this->objectlist()) return "objectlist";
		if($this->embeddedlist()) return "embeddedobjectlist";
	}

	//is it an embedded object?
	function embedded(){
		return $this->json_type == "object" && $this->object_type == "EO";
	}

	//is it an embedded object list? { id => {p => v}, id2 => {p => v2} }
	function embeddedlist(){
		return $this->json_type == "object" && $this->object_type == "EOL";
	}
}