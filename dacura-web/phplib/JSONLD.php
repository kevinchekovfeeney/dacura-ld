<?php

/*
 * Class representing a json-ld style associative array
 *
 * Created By: Chekov
 * Creation Date: 13/03/2015
 * Contributors:
 * Modified:
 * Licence: GPL v2
 */


class JSONLD extends DacuraObject {
	var $json_ld_mapping; //JSON-LD mapping
	var $json_schema; //Json schema

	function loadJSONSchema($file){
		$this->json_schema = json_decode(file_get_contents($file));
	}

	function genid($prefix = "") {
		return uniqid($prefix);
	}

	function checkJSONSchema($js){
		$schema_errors = Jsv4::validate($js, $this->json_schema);
		foreach($schema_errors as $se){
			$se = $se[0];
			opr($se);
		}
	}

	function findObjectWithKey($k, $arr){
		if(!is_array($arr)) return false;
		foreach($arr as $id => $obj){
			if(is_array($obj)){
				if($id == $k){
					return $obj;
				}
				else {
					return $this->findObjectWithKey($k, $obj);
				}
			}
		}
		return false;
	}

	function applyIDMap($map){}

}
