<?php

/*
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors: Chekov
 * Modifications: 20/11/2014 - 07/12/2014
 * Licence: GPL v2
 */



class HomeDacuraServer extends DacuraServer {
	
	function userHasViewPagePermission(){
		return true;
	}
	
	function getUserHomeService(){
		$u = $this->getUser(0);
		if($u){
			return "browse";
		}
		return "login";	
	}	
}

