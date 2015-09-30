<?php
require_once("EntityCreateRequest.php");

class GraphCreateRequest extends EntityCreateRequest {

	function loadFromAPI($create_obj){
		$this->meta = $create_obj['meta'];
		$props = isset($create_obj['contents']) && $create_obj['contents'] ? $create_obj['contents'] : array();			
		$this->ldprops = array($this->id => $props);
		return true;
	}
}