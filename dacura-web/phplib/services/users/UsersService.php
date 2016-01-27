<?php
include_once("UsersDacuraServer.php");
/**
 * Users Service - provides access to updating / editing / viewing users and roles, etc. 
 *
 * * Creation Date: 15/01/2015
 * @package users
 * @author Chekov
 * @license: GPL v2
 */
class UsersService extends DacuraService {
	/** @var string the list page is the default screen - the front page of the service */
	var $default_screen = "list";
	
	function compareFacets($a, $b){
		if($b == 'list' && $a == "view"){
			return true;
		}
		return parent::compareFacets($a, $b);
	}
	/**
	 * There are only two possible arguments - user id or profile
	 * 
	 * Thus we simplify the argument handling
	 * @see DacuraService::loadArgsFromBrowserURL()
	 * @param array $sections an array of the url sections between slashes, the first of which will be our screen
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
	
	/**
	 * We override the render screen function to swap in the view page for the profile page
	 * @param string screen the name of the screen to render
	 * @param array name value array of variabls to be subbed into screen
	 */
	function renderScreen($screen, $params, $other_service = false){
		if($screen == "profile"){
			$screen = "view";
		}
		return parent::renderScreen($screen, $params, $other_service);
	}
	
	/**
	 * Populates the parameters for the html screen subtitutions, etc
	 * (non-PHPdoc)
	 * @see DacuraService::getParamsForScreen()
	 * @param $screen the name of the screen to render
	 * @param UsersDacuraServer $dacura_server server object
	 * @return array the parameter array
	 */
	function getParamsForScreen($screen, UsersDacuraServer &$dacura_server){
		$params = array("contexts" => $dacura_server->getUserAvailableContexts("admin", true));
		$u = $dacura_server->getUser();
		$col = $dacura_server->getCollection();		
		if($screen == "profile"){
			$params["userid"] = $u->id;			
		}
		else {
			$params["userid"] = $this->args['userid'];
		}
		$params['all_roles'] = UserRole::$dacura_roles;
		$params["dt"] = true;
		$params['image'] = $this->furl("image", "buttons/users.png");
		$params['collectionbreadcrumb'] = "users";
		if($screen == 'profile'){
			$params["title"] = "User Profile";
			$params["subtitle"] = "Manage your account details";
		}
		else {
			$params['role_options']	= $dacura_server->getRoleCreateOptions($params["userid"]);	
			$params["title"] = "User Management Tool";
			if($this->cid() == "all"){
				$params["subtitle"] = "Manage users and their roles in the system";
			}
			else {
				$params["subtitle"] = "Manage the users and roles of ".$col->name." collection";				
			}				
			if($u && $u->rolesSpanCollections()){
				$params['topbreadcrumb'] = "All Users";
				$params["breadcrumbs"] = array(array(), array());
			}
			elseif($screen == "view"){
				$params["breadcrumbs"] = array(array(), array());				
			}			
		}
		if($screen == "list"){		
			$roles = $dacura_server->userman->getAvailableRoles($dacura_server->cid());
			$params['selection_options'] = json_encode(DacuraObject::$valid_statuses);
			$params['subscreens'] = array("list-users");
			if($dacura_server->userHasFacet("admin")){
				$params['admin_table_settings'] = ($this->cid() == "all") ? $this->getDatatableSetting("users"): $this->getDatatableSetting("cusers");
				$params['subscreens'][] = "add-user";
				$params['admin'] = true;
				if($dacura_server->cid() != "all") {
					$cform = $this->sform("ccu");
					$cform['role']["options"] = $roles;
					$params['subscreens'][] = "invite-users";
					$iform = $this->sform("icu");
					$iform['role']["options"] = $roles;					
					$params["invite_intro_msg"] = $this->smsg("invite_intro");
					$params['invite_email_template'] = $this->smsg("invite_email");
					$iform['message']['value'] = $params['invite_email_template'];					
					$params['invite_users_fields'] = array_values($iform);
					$params['add_intro_msg'] = $this->smsg("collection_add");
				}
				else {
					$cform = $this->sform("csu");						
					$params['add_intro_msg'] = $this->smsg("system_add");						
				}
				$params['create_user_fields'] = array_values($cform);				
			}
			else {
				$params['list_table_settings'] = ($this->cid() == "all") ? $this->getDatatableSetting("susers"): $this->getDatatableSetting("scusers");
				$params['clickable_users'] = $dacura_server->userHasFacet("view");				
			}
		}
		elseif($screen == 'profile') {
			$params['subscreens'] = array("user-password", "user-details");				
			$params['update_details_fields'] = array_values($this->sform('upu'));
			$params["details_intro_msg"] = $this->smsg("profile_intro");
			$params['showdelete'] = false;
			$params['showupdate'] = true;
			$params['update_button_text'] = "Save Updated Profile";
			$params['update_form_type'] = "update";
			$params['update_password_fields'] = array_values($this->sform('uxp'));
			$params["password_intro_msg"] = $this->smsg("password_intro");		
		}
		else {//view screen
			$ub = $dacura_server->getUser($params["userid"]);
			if(!$ub){return $params;}
			$params['subscreens'] = array("user-details");
			if($dacura_server->userHasFacet("inspect")){
				$params['roles_table_settings'] = $this->getDatatableSetting("roles");
				$params['history_table_settings'] = $this->getDatatableSetting("history");					
				$params['subscreens'][] = "user-history";
				if($this->cid() == 'all'){
					$params['subscreens'][] = "user-roles";
					$params["roles_intro_msg"] = $this->smsg("roles_intro");
				}
				else {
					$params['subscreens'][] = "collection-roles";
					$params["roles_intro_msg"] = $this->smsg("roles_intro");
				}				
			}		
			if($dacura_server->userHasFacet("admin") && $dacura_server->canUpdatePassword($ub)){
				$params['subscreens'][] = "user-password";
				$params["details_intro_msg"] = $this->smsg("update_details_intro");
				$params["showupdate"] = true;
				$params['update_form_type'] = "update";
				$params['update_password_fields'] = array_values($this->sform('uxp'));
				$params["password_intro_msg"] = $this->smsg("password_intro");
			}
			elseif($dacura_server->userHasFacet("admin")){
				$params["showupdate"] = true;
				$params['update_form_type'] = "view";
				$params["details_intro_msg"] = $this->smsg("view_details_intro");						
			}
			else {
				$params["showupdate"] = false;
				$params['update_form_type'] = "view";
				$params["details_intro_msg"] = $this->smsg("view_details_intro");						
			}
			$params['update_details_fields'] = array_values($this->sform('uxu'));					
			$params["showdelete"] = $u && $dacura_server->canUpdateUserStatus($u, $ub);				
			$params["history_intro_msg"] = $this->smsg("history_intro");
			$params['update_button_text'] = "Update User Details";
		}
		return $params;
	}
}