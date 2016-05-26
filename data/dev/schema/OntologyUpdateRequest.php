<?php

class OntologyUpdateRequest extends LDOUpdate {
	
	function makeMetaChanges($stuff){
		$changes = array();
		if(isset($stuff['url']) && $stuff['url'] != $this->original->url){
			$changes['url'] = array($this->original->url => $stuff['url']);
			$this->changed->url = $stuff['url'];
		}
		if(isset($stuff['title']) && $stuff['title'] != $this->original->title){
			$changes['title'] = array($this->original->title => $stuff['title']);
			$this->changed->title = $stuff['title'];
		}
		if(isset($stuff['description']) && $stuff['description'] != $this->original->description){
			$changes['description'] = array($this->original->description => $stuff['description']);
			$this->changed->description = $stuff['description'];
		}
		if(isset($stuff['real_version']) && $stuff['real_version'] != $this->original->real_version){
			$changes['real_version'] = array($this->original->real_version => $stuff['real_version']);
			$this->changed->real_version = $stuff['real_version'];
		}
		if(isset($stuff['status']) && $stuff['status'] != $this->original->status){
			$changes['status'] = array($this->original->status => $stuff['status']);
			$this->changed->status = $stuff['status'];
		}
		$this->changed->version++;
		$this->changed->latest_version = $this->changed->version;
		//opr($changes);
		return $changes;
	}
	
	function validateCommand($obj, $in_embedded = false){
		foreach($obj as $p => $v){
			$pv = new LDPropertyValue($v, $this->cwurl);
			if($pv->illegal()) return $this->failure_result("Update failed validation: ".$pv->errmsg, $pv->errcode);
		}
		return true;
	}
	

	function getLDForm(){
		return $this->changed->store(true);
	}
}