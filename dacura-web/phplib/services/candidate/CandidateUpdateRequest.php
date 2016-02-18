<?php

class CandidateUpdateRequest extends LDOUpdate {
	
	function schema_version(){
		return ($this->changed)? $this->changed->get_class_version() : $this->original->get_class_version();
	}
	
	function prepareRestore($dacura_server, $version){
		$changed = $dacura_server->getCandidate($this->targetid, false, $version);
		if(!$changed){
			return $this->failure_result($dacura_server->errmsg, $dacura_server->errcode);
		}
		$this->changed = $changed;
		$this->changed->version = $this->to_version ? $this->to_version : 1 + $this->changed->latest_version;
		if($this->changed->version > $this->changed->latest_version){
			$this->changed->latest_version = $this->changed->version;
		}
		$this->calculateDelta(true);
		return true;
	}
	
	function updateImportedProps($contents){
		$this->changed->ldprops = array("main" => $contents);
	}	
}
