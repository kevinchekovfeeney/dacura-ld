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
	 * Just a shortcut function to fill in some standard data for some form fields
	 * @param string $formid the id of the html form being prepared
	 * @param DacuraServer $dacura_server - the currently active server object. 
	 */
	function prepareFormFields($formid, DacuraServer $dacura_server){
		$fields = $this->sform($formid);
		if(isset($fields['password'])){
			$fields['password']['help'] .= " " .  $this->smsg("password_rule").".";
		}
		if(isset($fields['role'])){
			$fields['role']["options"] = $dacura_server->userman->getAvailableRoles($this->cid());						
		}
		return $fields;
	}
	
	/**
	 * Just a shortcut function to fill in the standard fields for a user
	 * @param DacuraUser $ub the user being viewed / updated
	 * @param array $ff an array of fields that are to be filled in
	 */
	function fillInFormFields(DacuraUser $ub, &$ff){
		if(isset($ff['id'])) $ff['id']['value'] = $ub->id;
		if(isset($ff['email'])) $ff['email']['value'] = $ub->email;
		if(isset($ff['name'])) $ff['name']['value'] = $ub->handle;
		if(isset($ff['status'])) $ff['status']['value'] = $ub->status;
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
		$prule = $this->smsg("password_rule");
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
		$params['image'] = $this->furl("images", "services/users.png");
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
				$params["breadcrumbs"] = true;
			}
			elseif($screen == "view"){
				$params["breadcrumbs"] = true;				
			}			
		}
		if($screen == "list"){		
			$params['selection_options'] = json_encode(DacuraObject::$valid_statuses);
			$params['subscreens'] = array("list-users");
			if($dacura_server->userHasFacet("admin") && $u){
				$params['admin_table_settings'] = ($this->cid() == "all") ? $this->getDatatableSetting("users"): $this->getDatatableSetting("cusers");
				$params['subscreens'][] = "add-user";
				$params['admin'] = true;
				if($dacura_server->cid() != "all") {
					$cform = $this->prepareFormFields("ccu", $dacura_server);
					$params['subscreens'][] = "invite-users";
					$iform = $this->prepareFormFields("icu", $dacura_server);
					$params["invite_intro_msg"] = $this->smsg("invite_intro");
					$params['invite_email_template'] = $this->smsg("invite_email");
					$iform['message']['value'] = $params['invite_email_template'];					
					$params['invite_users_fields'] = array_values($iform);
					$params['add_intro_msg'] = $this->smsg("collection_add");
				}
				else {
					$cform = $this->prepareFormFields("csu", $dacura_server);						
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
			$params['profile'] = true;
			$params['subscreens'] = array("user-password", "user-details");				
			$params['update_details_fields'] = array_values($this->sform('upu'));
			$params["details_intro_msg"] = $this->smsg("profile_intro");
			$params['showdelete'] = false;
			$params['showupdate'] = true;
			$params['update_button_text'] = "Save Updated Profile";
			$params['update_form_type'] = "update";
			$params['update_password_fields'] = array_values($this->prepareFormFields("upp", $dacura_server));
			$params["password_intro_msg"] = $this->smsg("profile_password_intro");		
		}
		else {//view screen
			$ub = $dacura_server->getUser($params["userid"]);
			$params['subscreens'] = array();
			if(!$ub){return $params;}
			elseif($u && $u->id == $ub->id){
				$params['self_update'] = true;
			}
			$params[] = "user-details";
			if($dacura_server->userHasFacet("inspect")){
				$params['roles_table_settings'] = $this->getDatatableSetting("roles");
				$params['subscreens'][] = "user-history";
				if($this->cid() == 'all'){
					$params['history_table_settings'] = $this->getDatatableSetting("system_history");					
					$params['subscreens'][] = "user-roles";
					$params["roles_intro_msg"] = $this->smsg("roles_intro");
				}
				else {
					$params['history_table_settings'] = $this->getDatatableSetting("collection_history");					
					$params['subscreens'][] = "collection-roles";
					$params["roles_intro_msg"] = $this->smsg("roles_intro");
				}				
			}		
			if($dacura_server->userHasFacet("admin") && $dacura_server->canUpdatePassword($ub)){
				$params['subscreens'][] = "user-password";
				$params["details_intro_msg"] = $this->smsg("update_details_intro");
				$params["showupdate"] = true;
				$params['update_form_type'] = "update";
				$params['update_password_fields'] = array_values($this->prepareFormFields("uxp", $dacura_server));
				$params["password_intro_msg"] = $this->smsg("password_intro");
			}
			elseif($u && $dacura_server->userHasFacet("admin")){
				$params["showupdate"] = true;
				$params['update_form_type'] = "view";
				$params["details_intro_msg"] = $this->smsg("view_details_intro");						
			}
			else {
				$params["showupdate"] = false;
				$params['update_form_type'] = "view";
				$params["details_intro_msg"] = $this->smsg("view_details_intro");						
			}
			$udf = $this->prepareFormFields("uxu", $dacura_server);
			$this->fillInFormFields($ub,$udf);
			$params['update_details_fields'] = array_values($udf);
			
			$params["showdelete"] = $u && $dacura_server->canUpdateUserStatus($u, $ub);				
			$params["history_intro_msg"] = $this->smsg("history_intro");
			$params['update_button_text'] = "Update User Details";
		}
		return $params;
	}
}