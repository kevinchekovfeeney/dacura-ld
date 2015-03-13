<?php
require_once("Candidate.php");

class CandidateCreateRequest extends Candidate {

	function __construct(){
		$this->version = 1;
	}

	function expandarray($arr, &$map, $prefix = ""){
		if(!is_array($arr)){
			return $arr;
		}
		$newarr = array();
		foreach($arr as $key => $val){
			if(is_array($val)){
				$newid = $this->genid($prefix);
				if(isset($val['@id'])){
					$map[$val['@id']] = $newid;
					unset($val['@id']);
				}
				$newarr[$newid] = $this->expandarray($val, $map, $prefix);
			}
			else {
				$newarr[$key] = $val;
			}
		}
		return $newarr;
	}

	function expand(){
		$this->id = $this->genid();
		$id_map = array("_:candidate" => $this->id);
		//add ids to everything, ensure that everything inter-relates
		//get_content id, then, get
		foreach($this->contents as $k => $v){
			if(!is_array($v)){
				continue;
			}
			$this->contents[$k] = $this->expandarray($v, $id_map, $this->id."/");
		}
		//now we deal with provenance and annotation references....
		$this->annotation->expand($id_map, $this->id."/");
		$this->prov->expand($id_map, $this->id."/");
		$this->annotation->applyIDMap($id_map);
		$this->prov->applyIDMap($id_map);
		return true;
	}
}
