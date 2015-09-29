<?php

require_once("EntityUpdate.php");

class CandidateUpdateRequest extends EntityUpdate {
	function isCandidate(){
		return true;
	}

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

	/*
	 * Transforms the changed candidate into the desired format for the consumer
	 */
	function showUpdateResult($format, $flags, $v, $dacura_server) {
		$res = parent::showUpdateResult($format, $flags, $v, $dacura_server);
		$res->history = $dacura_server->getEntityHistory($res, $v);
		//$res->pending = $dacura_server->getEntityPending($res, $v);
		return $res;
	}
	
	function getMetaUpdates(){
		$meta = parent::getMetaUpdates();
		if($this->original->type_version != $this->changed->type_version) $meta['type_version'] = array($this->original->type_version, $this->changed->type_version);
		return $meta;
	}
	

}
