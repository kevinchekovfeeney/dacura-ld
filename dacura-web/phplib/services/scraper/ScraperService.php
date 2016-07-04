<?php
/*
 * Scraper Service
 * scrapes data from seshat wiki and dumps it in a tsv
 *
 * Created By: Chekov
 * Creation Date: 20/11/2014
 * Contributors:
 * Licence: GPL v2
 */

include_once("ScraperDacuraServer.php");

class ScraperService extends DacuraService {

	var $default_screen = "main";
	var $public_screens = array("test", "syntax");
	var $protected_screens = array("export" => array("admin"), "status" => array("admin"), "main" => array("admin"));

	function getConsoleScript($dacura_server){
		$str = "<script>";
		$u = $dacura_server->getUser();
		ob_start();
		$files_to_load = array();//do we need any libraries$dacura_server->getServiceSetting('grabScriptFiles', array());
		foreach($files_to_load as $f){
			if(file_exists($f)){
				include($f);
			}
			else {
				ob_end_clean();
				return $dacura_server->write_http_error(500, "File $f not found");
			}
		}
		$f = $dacura_server->service->mydir."screens/console.js";
		$service = $this;
		if(file_exists($f)){
			$params = $u ? $this->getConsoleParams($dacura_server, $u) : $this->getLoginConsoleParams($dacura_server, $u);
			include($f);
			$page = ob_get_contents();
			ob_end_clean();
			$str.= $page;
		}
		$str .= "\n$(document).ready(function() {";
		$str .= "dconsole.initPane();";
		if($u){
			$str .= "dconsole.showUserOptions();";
			$str .= "dconsole.getEntityClasses();";
		}
		else {
			$str .= "dconsole.showLoginBox();";
		}		
		$str .= "});";
		$str .= "</script>";
		
		return $str;
	}
	
	function getLoginConsoleParams($dacura_server){
		$params = array();
		$params["loginurl"] = $this->get_service_url("login", array(), "api");
		$params['reloadurl'] = $this->my_url("rest")."/console";
		return $params;		
	}
	
	function getConsoleParams($dacura_server, DacuraUser $u){
		$params = array();
		$params['logouturl'] = $this->get_service_url("login", array("logout"));
		$params['dacuraurl'] = $this->durl();
		$params["username"] = $u->handle;
		$params["usericon"] = $this->furl("image", "icons/user_icon.png");
		if($u->rolesSpanCollections()){
			$params["profileurl"] = $this->get_service_url("users", array(), "html", "all", "all")."/profile";
		}
		else {
			$cid = $u->getRoleCollectionId();
			$params["profileurl"] = $this->get_service_url("users", array(), "html", $cid, "all")."/profile";
		}
		$pc = $dacura_server->getUserAvailableContexts();
		if(count($pc) > 0){
			if(isset($pc['seshat'])){ $c = 'seshat'; }
			else {
				$c = array_keys($pc)[0];
			}
			$params['current_collection'] = $c;
			$params['apiurl'] = $this->durl(true) . ($c == "all" ? "" : $c ."/");
			$params['context_title'] = $pc[$c]['title'];
			$params['collection_contents'] = $this->getCollectionContents($c, $dacura_server, $u);
		}
		else {
			$params['context_title'] = "You are not a member of any dacura collections";
		}
		$params['view_property_icon'] = "<a href='javascript:dconsole.showProperty()'><img src='" . $this->furl("images", "icons/sort_desc.png") . "'></a>";
		$params['new_entity_icon'] = "<a href='javascript:dconsole.createEntity()'><img src='" . $this->furl("images", "icons/add.png") . "'>";
		$params['collection_choices'] = $pc;
		return $params;
	}
	
	function getCollectionContents($cid, DacuraServer $dacura_server, DacuraUser $u){
		$this->collection_id = $cid;
		$cs = $dacura_server->createDependantService("candidate");
		$cands = $dacura_server->createDependantServer("candidate", $cs); 
		$cands->init();
		$contents = array();
		$ents = $cands->getValidCandidateTypes();
		$filter = array("type" => "candidate", "status" => array("accept", "pending"));
		$instances = $cands->getLDOs($filter);
		$entities = array();
		foreach($instances as $inst){
			if(isset($inst['meta']) && isset($inst['meta']['type']) && $inst['meta']['type']){
				if(!isset($entities[$inst['meta']['type']])){
					$entities[$inst['meta']['type']] = array();
				}
				$entities[$inst['meta']['type']][] = $inst['id'];
			}
		}
		$contents['entities'] = $entities;
		$contents['entity_classes'] = $cands->getValidCandidateTypes();
		return $contents;
	}
	/*function renderFullPageHeader(DacuraServer $dacura_server){
		parent::renderFullPageHeader($dacura_server);
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css').'">';
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent-nopad'>";
	}*/
	
	/*function handlePageLoad($server){
		$params = array(
				"title" => "Wiki Scraper",
				"subtitle" => "A Tool for extracting structured data from the Seshat Wiki");
		$params['screen'] = $this->screen;
		//if($this->screen == "syntax"){
		//	$params["examples"] = $server->parseCanonicalExamples();
		//}
		$this->renderToolHeader($params);
		$this->renderScreen($this->screen, $params);
		$this->renderToolFooter($params);
	}*/
	
	/*function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}*/
	
}
