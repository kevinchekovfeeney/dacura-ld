<?php

class UserRole {
	var $id;
	var $collection_id;
	var $dataset_id;
	var $role;
	var $level;
	
	function __construct($id, $cid, $dsid, $role, $level){
		$this->id = $id;
		$this->collection_id = $cid;
		$this->dataset_id = $dsid;
		$this->role = $role;
		$this->level = $level;
	}
	
	function isGod(){
		return ($this->collection_id == 0 && $this->dataset_id == 0 && $this->role == "god"); 
	}
}