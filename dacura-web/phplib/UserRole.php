<?php
/**
 * Class representing a role of a user of the Dacura System
 * 
 * Roles are given scope by collection id. 
 * * Creation Date: 20/11/2014
 * @author Chekov
 * @license GPL v2
 */
class UserRole extends DacuraObject {
	/** @var string the collection for which the role holds */
	var $collection_id;
	/** @var string the name of the role (must be one of $dacura_roles) */
	var $role;
	/** @var array name value array of rolename: role title (human readable) */
	public static $dacura_roles = array(
		"user" => "Data Consumer",
		"harvester" => "Data Harvester", 
		"expert" => "Domain Expert", 
		"architect" => "Schema Architect",  
		"admin" => "Administrator", 
		"nobody" => "Slave",
	);

	public static $extended_dacura_roles = array(
			"user" => "Data Consumer",
			"harvester" => "Data Harvester",
			"expert" => "Domain Expert",
			"architect" => "Schema Architect",
			"admin" => "Administrator",
			"nobody" => "Slave",
			"dacurauser" => "Any Registered Dacura User",
			"public" => "Any member of the public"
	);
	
	
	/**
	 * @param string $id role id
	 * @param string $cid collection id
	 * @param string $role role name
	 */
	function __construct($id, $cid, $role){
		$this->id = $id;
		$this->collection_id = $cid;
		$this->role = $role;
	}
	
	function forapi(){
		unset($this->status);
		return parent::forapi();
	}

	/**
	 * Get the role's collection id
	 * @return string collection id
	 */
	function collectionID(){
		return $this->collection_id;
	}
	
	/**
	 * Get the role's collection id
	 * @return string collection id
	 */
	function cid(){
		return $this->collection_id;
	}
	
	/**
	 * Get the name of the role
	 * @return string the name of the role (one of UserRole::$dacura_roles)
	 */
	function role(){
		return $this->role;
	}
	
	/* role hierarchy..	 */
	/**
	 * Compares two roles according to the role hierarchy
	 * 
	 * admin > [other roles] > user > nobody
	 * 
	 * @param string $r1 first role name
	 * @param string $r2 second role name
	 * @return number code indicating result of comparison:
	 * * 0 if roles are equal 
	 * * 1 if role 1 > role 2
	 * * -1 if role1 < role 2
	 */
	function roleCompare($r1, $r2){
		if($r1 == $r2) return 0;
		elseif($r1 == "admin") return 1;
		elseif($r2 == "nobody") return 1;
		elseif($r2 == "user" && $r1 != "nobody") return 1;
		else return -1;
	}
	
	/**
	 * Returns true if this role covers the passed requirements
	 * @param string $r role name
	 * @param string $cid collection id
	 * @return boolean if this role covers $r, $cid
	 */
	function covers($r, $cid){
		if($this->roleCompare($this->role, $r) < 0){
			return false;
		}
		if(($this->cid() == "all" or $cid == 'any')){
			return true;
		}
		elseif($this->cid() != $cid){
			return false;
		}
		return true;
	}
	
	/**
	 * Does this role cover a second role 
	 * @param UserRole $r2
	 * @return boolean true if this role covers r2
	 */
	function coversRole(UserRole $r2){
		return $this->covers($r2->role, $r2->collection_id);		
	}
	
	/**
	 * Is this an admin role?
	 * @return boolean
	 */
	function isAdmin(){
		return ($this->role() == "admin");
	}
}