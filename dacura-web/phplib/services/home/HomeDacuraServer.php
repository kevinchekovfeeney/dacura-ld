<?php

/*
 * The service for scraping datasets from the seshat wiki
 *
 * Created By: Odhran
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
			//if(isset($u->profile['dacurahome']) && $u->profile['dacurahome']){
			//	echo $u->profile['dacurahome'];
			//}
			//else {
				return "browse";
			//}
		}
		return "login";	
	}	
}

