<?php
include_once("HomeDacuraServer.php");
/**
 * A service that redirects a user to their appropriate home page
 * 
 * Depending on the context and the user's state...
 *
 * @package home
 * @author chekov
 * @license GPL v2
 */
class HomeService extends DacuraService {
	
	/**
	 * Figures out the url of the user's home page in dacura and redirects them to it
	 * 
	 * @param HomeDacuraServer $server the dacura server object
	 * @see DacuraService::renderFullPage()
	 */
	function renderFullPage(HomeDacuraServer $server){
		$u = $server->getUser();
		$active_facets = $this->getActiveFacets($u);
		if(!$active_facets){
			if($u){
				$colname = ($this->cid() == "all") ? "platform" : $this->cid();
				$this->renderScreen("denied", array("title" => "Access Denied", "message" => "You do not have permission to view the $colname home page."), "core");				
			}
			else {
				$url = $this->get_service_url("login");				
			}
		}	
		else {
			$cid = ($server->cid() == "all" && $u) ? $server->getUserHomeContext($u) : $server->cid();
			$url = $this->get_service_url($server->getUserHomeService($this->getServiceSetting("home")), array(), "html", $cid);				
		}	
		header("Location: $url");
	}
}
