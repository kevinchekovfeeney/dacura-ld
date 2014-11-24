<?php
/*
 * Class representing a role of a user of the Dacura System
 * Roles are given scope by collection and dataset id and level - will be extended for sub data-set access control. 
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */

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

	function collectionID(){
		return ($this->collection_id == "0" ? "" : $this->collection_id);
	}
	
	function datasetID(){
		return ($this->dataset_id == "0" ? "" : $this->dataset_id);
	}
	
	
	function isAdmin(){
		return ($this->role == "admin");
	}
	
	
	function isGod(){
		return ($this->collection_id == 0 && $this->dataset_id == 0 && $this->role == "god"); 
	}
}