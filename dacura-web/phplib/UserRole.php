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

class UserRole extends DacuraObject {
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
		return $this->collection_id;
	}
	
	/* role hierarchy..
	 * god > admin > [other roles] > user > nobody
	 */
	function roleCompare($r1, $r2){
		if($r1 == $r2) return 0;
		elseif($r1 == "god") return 1;
		elseif($r1 == "admin" && $r2 != "god") return 1;
		elseif($r2 == "nobody") return 1;
		elseif($r2 == "user" && $r1 != "nobody") return 1;
		else return -1;
	}
	
	/*
	 * Returns true if this role covers the passed requirements
	 */
	function covers($r, $cid, $did){
		if($this->roleCompare($this->role, $r) < 0){
			return false;
		}
		if(($this->collection_id == "all")){
			return true;
		}
		elseif($this->collection_id != $cid && $cid != "all"){
			return false;
		}
		elseif($this->dataset_id == "all"){
			return true;
		}
		elseif($this->dataset_id == $did or $did == "all"){
			return true;
		}
		return false;
	}
	
	function coversRole($r2){
		return $this->covers($r2->role, $r2->collection_id, $r2->dataset_id);		
	}
	
	function datasetID(){
		return $this->dataset_id;
	}
	
	
	function isAdmin(){
		return ($this->role == "admin");
	}
	
	
	function isGod(){
		return ($this->collection_id == "all" && $this->dataset_id == "all" && $this->role == "god"); 
	}
}