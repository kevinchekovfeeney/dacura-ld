<?php
include_once("LoginDacuraServer.php");
/** 
 * Dacura's Login Service 
 * 
 * Overrides the base service class for login functionality
 * @package login
 * @author chekov
 * @license GPL V2
 */
class LoginService extends DacuraService {
	/** @var string login @see DacuraService::default_screen */
	var $default_screen = "login";
	/** @var string[] @see DacuraService::public_screens */
	var $default_facets = array(array("facet" => "login", "role" => "public"), array("facet" => "logout", "role" => "dacurauser"));
	/**
	 * Loads the stylesheet
	 * @see DacuraService::init()
	 */
	function init(){
		$this->included_css[] = $this->get_service_file_url('style.css');
	}
	
	/**
	 * Wraps content in maincontrol div
	 * @see DacuraService::writeBodyHeader()
	 */
	protected function writeBodyHeader(){
		echo "<div id='maincontrol'>";
	}
	
	/**
	 * closes maincontrol div
	 * @see DacuraService::writeBodyFooter()
	 */
	protected function writeBodyFooter(){
		echo "</div>";
	}

	/**
	 * Supress drawing of topbar
	 * @see DacuraService::renderTopbarSnippet()
	 */
	protected function renderTopbarSnippet(){}	
	
	/**
	 * The page-drawing logic
	 * 
	 * Distinguishes between registrations, logins, logouts and the various type of confirm codes
	 * all of which are handled by the login service
	 * * login
	 * * logout
	 * * register
	 * * confirm register
	 * * reset password
	 * * confirm reset password
	 * * confirm invite (the generation of the invitation itself is handled by the users service). 
	 * @param LoginDacuraServer $lds the dacura server object
	 * @see DacuraService::handlePageLoad()
	 */
	function handlePageLoad(LoginDacuraServer &$lds){
		$dcuser = $lds->getUser(0);
		if($dcuser){
			$params = array("username" => $dcuser->handle, "execute" => false);
			if($this->screen == "logout"){
				$params['execute'] = true;	
			}
			$this->renderScreen("logout", $params);
		}
		else{
			if($this->screen == "home" or $this->screen == "" or $this->screen == 'login'){
				$params = array("active_function" => "login");
				$this->renderScreen("login", $params);
			}
			elseif(($this->screen == "register" or $this->screen == 'lost') && (!isset($this->args["code"]))){
				$params = array("active_function" => $this->screen);
				$this->renderScreen("login", $params);				
			}
			elseif($this->screen == 'register'){
				$code = $this->args["code"];
				$user = $lds->userman->confirmRegistration($code, $lds->cid());
				if(!$user){
					$this->renderScreen("error", array("title" => "Error in registration confirmation", 
							"message" => $lds->userman->errmsg, 
							"showlogin" => "If you have already confirmed this registration, please proceed to login"));
				}
				else {
					$this->renderScreen("register_success", array("message" => "Good to have you on board ".$user->handle));
				}
			}
			elseif($this->screen == 'invite'){
				$code = $this->args["code"];
				$user = $lds->userman->confirmInvite($code, $lds->cid());
				if(!$user){
					$this->renderScreen("error", array("title" => "Error in invitation confirmation", "message" => $lds->userman->errmsg));
				}
				else {
					$this->renderScreen("invite", array(
							"greeting"=>"<strong>Hi $user->handle</strong><br>",
							"userid" => $user->id,
							"set_instruction" => $this->getServiceSetting('set_instruction')
					));
				}
			}
			elseif($this->screen == 'lost'){
				$code = $this->args["code"];
				$user = $lds->userman->confirmLostPassword($code);
				if(!$user){
					$this->renderScreen("error", array("title" => "Password reset failed", "message" => $lds->userman->errmsg));
				}
				else {
					$this->renderScreen("lost", array(
							"greeting"=>"<strong>Hi $user->handle</strong><br>", 
							"userid" => $user->id,
							"reset_instruction" => $this->getServiceSetting('reset_instruction')
					));
				}			
			}
			else {
				$this->renderScreen("error", array("title" => "Unknown Screen", "message" => "The login service does not have a $this->screen page"));
			}
		}
	}
	
}