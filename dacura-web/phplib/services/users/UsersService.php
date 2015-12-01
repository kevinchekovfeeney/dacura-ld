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
	
	var $protected_screens = array("profile" => array("user"), "list" => array("admin"), "view" => array("admin"));
	var $default_screen = "list";
	
	/*
	 * The only argument is userid
	 */
	function loadArgsFromBrowserURL($sections){
		if(count($sections)){
			if($sections[0] == 'profile'){
				$this->screen = 'profile';
			}
			else {
				$this->screen = "view";
				$this->args['userid'] = $sections[0];				
			}
		}
		else {
			$this->screen = "list";
			$this->args['userid'] = "";
		}
	}
	
	function getRolenameOptions($ds){
		$html = "";
		foreach($ds->userman->getAvailableRoles($ds->cid()) as $rv => $rname){
			$html .= "<option value='$rv'>$rname</option>\n";
		}
		return $html;
	}
	
	/*
	 * We override the render screen function to swap in the view page for the profile page
	 */
	function renderScreen($screen, $params, $other_service = false){
		if($screen == "profile"){
			$screen = "view";
		}
		return parent::renderScreen($screen, $params, $other_service);
	}
	
	function getParamsForScreen($screen, &$dacura_server){
		$params = array("contexts" => $dacura_server->getUserAvailableContexts("admin", true));
		$u = $dacura_server->getUser();
		if($screen == "profile"){
			$params["userid"] = $u->id;			
		}
		else {
			$params["userid"] = $this->args['userid'];
		}
		$params['role_options']	= $dacura_server->getRoleContextOptions($params["userid"]);	
		$params["dt"] = true;
		$params['image'] = $this->url("image", "buttons/users.png");
		$params['collectionbreadcrumb'] = "users";
		if($screen == 'profile'){
			$params["title"] = "User Profile";
			$params["subtitle"] = "Manage your account details";
		}
		else {
			$params["title"] = "User Management Tool";
			$params["subtitle"] = "Manage users and their roles in the system";				
			if($u->rolesSpanCollections()){
				$params['topbreadcrumb'] = "All Users";
				$params["breadcrumbs"] = array(array(), array());
			}
			elseif($screen == "view"){
				$params["breadcrumbs"] = array(array(), array());				
			}			
		}
		$params['role_name_options'] = $this->getRolenameOptions($dacura_server);
		if($screen == "list"){
			$roles = $dacura_server->userman->getAvailableRoles($dacura_server->cid());
			if($dacura_server->cid() != "all"){
				$params['subscreens'] = array("list-users", "add-user", "invite-users");				
				$iform = $this->sform("icu");
				$cform = $this->sform("ccu");
				$cform['role']["options"] = $roles;
				$iform['role']["options"] = $roles;
				$params["invite_intro_msg"] = $this->smsg("invite_intro");
				$params['add_intro_msg'] = $this->smsg("system_add");
				$params['invite_email_template'] = $this->smsg("invite_email");
				$iform['message']['value'] = $params['invite_email_template'];					
				$params['invite_users_fields'] = array_values($iform);
			}
			else {
				$params['add_intro_msg'] = $this->smsg("collection_add");
				$params['subscreens'] = array("list-users", "add-user");				
				$cform = $this->dacura_forms["csu"];
			}
			$params['create_user_fields'] = array_values($cform);
			$params['dacura_table_settings'] = $this->getDatatableSetting("users");
		}
		else { //view / profile screeens
			$params['roles_table_settings'] = $this->getDatatableSetting("roles");
			$params['history_table_settings'] = $this->getDatatableSetting("history");
			$params['update_password_fields'] = array_values($this->sform('uxp'));
			$params["password_intro_msg"] = $this->smsg("password_intro");
			if($screen == "profile"){ //&& !$dacura_server->userHasRole("admin")
				$params['subscreens'] = array("user-password", "user-details");				
				$params['update_details_fields'] = array_values($this->sform('upu'));
				$params["details_intro_msg"] = $this->smsg("profile_intro");
				$params['showdelete'] = false;
				$params['update_button_text'] = "Save Updated Profile";
			}
			else {
				$params['showdelete'] = true;
				$params['subscreens'] = array("user-history", "user-password", "user-roles", "user-details");				
				$params["history_intro_msg"] = $this->smsg("history_intro");
				$params["roles_intro_msg"] = $this->smsg("roles_intro");
				$params['update_details_fields'] = array_values($this->sform('uxu'));
				$params["details_intro_msg"] = $this->smsg("details_intro");
				$params['update_button_text'] = "Update User Details";
			}
		}
		return $params;
	}
}