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
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css').'">';
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.users.js");
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent-nopad'>";
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
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
		$params['role_options']	= $server->getRoleContextOptions($this->args['userid']);	
		$params["breadcrumbs"] = array(array(), array());
		$params["title"] = "User Management Tool";
		$params["subtitle"] = "Manage users and their roles in the system";
			
		$this->renderToolHeader($params);
		$this->renderScreen($this->screen, $params);						
		$this->renderToolFooter($params);		
	}
	
	
	
}