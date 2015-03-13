<?php
/**
 * 
 * @author chekov
 * @date 13/03/2015
 *
 */
require_once("JSONLD.php");

class Annotation extends JSONLD {
	var $contents;

	function expandinternal($arr, &$map, $prefix, $first=false){
		if(!is_array($arr)){
			return $arr;
		}
		if(!$first){
			$newid = $this->genid($prefix);
			$narr = array($newid => array());
			foreach($arr as $n => $v){
				$narr[$newid][$n] = $this->expandinternal($v,$map, $prefix);
			}
			return $narr;
		}
		else {
			foreach($arr as $n => $v){
				$arr[$n] = $this->expandinternal($v, $map, $prefix);
			}
			return $arr;
		}
	}

	function apply_map_to_array($arr, $map){
		$narr = array();
		foreach($arr as $k => $v){
			if(is_array($v)){
				$narr[$k] = $this->apply_map_to_array($v, $map);
			}
			else {
				if(isset($map[$v])){
					$narr[$k] = $map[$v];
				}
				else {
					$narr[$k] = $v;
				}
			}
		}
		return $narr;
	}

	function applyIDMap($map){
		foreach($this->contents as $ck => $cu){
			$this->contents[$ck] = $this->apply_map_to_array($cu, $map);
		}
	}
	
	function expand(&$map, $prefix = ""){
		foreach($this->contents as $ck => $cu){
			if(isBlankNode($ck)){
				unset($this->contents[$ck]);
				$x = $this->genid($prefix);
				$map[$ck] = $x;
				$ck = $x;
			}
			if($ck){
				$this->contents[$ck] = $this->expandinternal($cu, $map, $prefix, true);
			}
		}
	}

	function getFragment($frag_id){
		foreach($this->contents as $id => $obj){
			if($frag_id == $id){
				return $obj;
			}
			else {
				$nobj = $this->findObjectWithKey($frag_id, $obj);
				if($nobj) return $nobj;
			}
		}
		return false;
	}

	function load($arr){
		$this->contents = $arr;
	}

	function load_json($json){
		return ($this->contents = json_decode($json, true));
	}

	function get_json_ld(){
		return $this->contents;
	}

	function get_json(){
		if(isset($this->contents) && $this->contents){
			return json_encode($this->contents);
		}
		return "{}";
	}
}
