<?php
/*
 * A service that redirects a user to their appropriate home page
 * Depending on the context and the user's state...
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Licence: GPL v2
 */

include_once("HomeDacuraServer.php");

class HomeService extends DacuraService {

	function renderFullPage($server){
		/* This will give an error. Note the output
		 * above, which is before the header() call */
		$url = $this->get_service_url($server->getUserHomeService(), array(), "html", $this->collection_id, $this->dataset_id);
		header("Location: $url");
	}
	
	
}
