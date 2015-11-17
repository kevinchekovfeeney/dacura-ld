<?php

class GraphCreateRequest extends EntityCreateRequest {

	function loadFromAPI($create_obj){
		if(!isset($create_obj['contents']) && !isset($create_obj['meta'])){
			return $this->failure_result("Graph create request was malformed : both meta and contents fields were missing", 400);
		}
		$this->meta = isset($create_obj['meta']) ? $create_obj['meta'] : array();
		$props = isset($create_obj['contents']) && $create_obj['contents'] ? $create_obj['contents'] : array();			
		$this->ldprops = array($this->id => $props);
		return true;
	}
}