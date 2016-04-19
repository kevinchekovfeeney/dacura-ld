<?php
/**
 * A service that redirects a user to their appropriate home page
 *
 * Creation Date: 20/11/2014
 *
 * @package home
 * @author chekov
 * @license GPL v2
 */
class HomeDacuraServer extends DacuraServer {

	/**
	 * Always returns true
	 * 
	 * non-logged in users are redirected to login, everybody else is sent to the collection home page
	 */
	function userHasViewPagePermission(){
		return true;
	}
	
	/**
	 * Different users might have different services that are their home pages
	 * 
	 * Currently everybody goes to the browse service
	 * @return string the name of the service to load as the user's home page
	 */
	function getUserHomeService($s = "browse"){
		return $s;
	}	
}

