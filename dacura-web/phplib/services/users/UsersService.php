<?php
/*
 * Users Service - provides access to updating / editing / viewing users and roles, etc. 
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 15/01/2015
 * Licence: GPL v2
 */


include_once("UsersDacuraServer.php");

class UsersService extends DacuraService {
	
	var $protected_screens = array("list" => array("admin"), "view" => array("admin"));
	var $default_screen = "list";
	//cid/did/users/userid
	
	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.users.js");
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent'>";
	}
	
	/*
	 * The only argument is userid
	 * users/[userid]
	 */
	function loadArgsFromBrowserURL($sections){
		if(count($sections)){
			$this->screen = "view";
			$this->args['userid'] = $sections[0];
		}
		else {
			$this->screen = "list";
			$this->args['userid'] = "";
		}
	}
	
	function handlePageLoad($server){
		$params = array("userid" => $this->args['userid'], "contexts" => $server->getUserAvailableContexts("admin", true));
		//if($this->screen == "view"){
			$params['role_options']	= $server->getRoleContextOptions($this->args['userid']);	
		//}
		$this->renderScreen($this->screen, $params);						
	}
	
	function getBreadCrumbsHTML($id = false, $append = ""){
		$paths = $this->get_service_breadcrumbs("All Users");
		$html = "<ul class='service-breadcrumbs'>";
		foreach($paths as $i => $path){
			$n = (count($path) - $i) + 1;
			if($i == 0){
				$html .= "<li class='first'><a href='".$path['url']."' style='z-index:$n;'><span></span>".$path['title']."</a></li>";
			}
			elseif(!$id && $i+1 == count($path)){
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
			else {
				$html .= "<li><a href='".$path['url']."' style='z-index:$n;'>".$path['title']."</a></li>";
			}
		}
		if($id){
			$html .= "<li><a href='#' style='z-index:0;'>User ".$id."</a></li>";
		}
		if($append){
			$html .= "<li>$append</li>";	
		}
		$html .= "</ul>";
		return $html;
	}
	
}