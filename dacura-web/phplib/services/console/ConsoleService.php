<?php 

require_once("phplib/services/ld/LdService.php");
require_once("ConsoleDacuraServer.php");
/**
 * Console Service - The object that describes the Console Service.
 * @package candidate
 * @author Chekov
 * @license: GPL v2
*/
class ConsoleService extends LdService {
	
	function getMinimumFacetForAccess(){
		return true;
	}
	
	function renderFullPage(DacuraServer &$dacura_server){
		$service = $this;
		$url = $this->getOriginOfLoad();
		if(!$url || !$dacura_server->isDacuraURL($url)){
			$this->writeCORSHeaders($url);
		}
		if($this->screen == "load"){
			//spit out the javascript which makes the call to the console passing the url context and credentials 
			$params = array();
			$params['homeurl'] = $this->my_url();
			$params['jquery_body_selector'] = "body";
			include_once("screens/load.js");			
		}
		else if($this->screen == "test"){
			$params = array();
			$params['homeurl'] = $this->my_url();
			include_once("screens/test.php");
		}
		else if($this->screen == "init"){
			$params = array();
			include_once("screens/dacura.init.js");
		}
		else {
			if(!$u = $dacura_server->getUser()){
				$params = array();
				$params["loginurl"] = $this->get_service_url("login", array(), "api");
				$params['homeurl'] = $this->my_url();
				include_once("screens/login.php");
			}
			else {
				//depending on the passed context (ORIGIN, credentials), spit out the javascript which loads the appropriate console context
				$params = $this->getConsoleParams($dacura_server, $u);
				echo "<script>";
				include_once("screens/dacura.init.js");				
				echo "</script>";
				include_once("screens/console.php");				
			}
		}
	}
	
	function getOriginOfLoad(){
		if(isset($_SERVER['HTTP_ORIGIN']) && $_SERVER['HTTP_ORIGIN']){
			return $_SERVER['HTTP_ORIGIN'];
		}
		if(isset($_GET['source']) && $_GET['source']){
			$urlstruct = parse_url($_GET['source']);
			$url = $urlstruct['scheme']."://".$urlstruct['host'];
			return $url;
		}
		return "";
	}
	
	function getURLofLoad(){
		if(isset($_GET['source']) && $_GET['source']){
			return $_GET['source'];
		}
		return $_SERVER['HTTP_REFERER'];	
	}
	
	function writeCORSHeaders($url){
		header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
		header("Access-Control-Max-Age: 1728000");
		header('Access-Control-Allow-Headers: Accept, Accept-Encoding, Accept-Language, Host, Origin, Referer, Content-Type, Content-Length, Content-Range, Content-Disposition, Content-Description');
		header("Access-Control-Allow-Credentials: true");
		if($url){
			header("Access-Control-Allow-Origin: $url");	
		}
		else {
			header("Access-Control-Allow-Origin: null");
		}
	}
	
	function getConsoleLoadMode($cid, LdDacuraServer &$dacura_server, DacuraUser $u){
		//need to check to see if there is a registered connection with a page and a codebook...
		$url = isset($_GET['source']) ? $_GET['source'] : $_SERVER['HTTP_REFERER'];
		if(strpos($url, "Code_book") !== false){
			return "model";
		}
		return "data";
	}
	
	function loadModelContext(&$context, LdDacuraServer &$dacura_server, DacuraUser $u){
		if($context['collection'] == "seshat"){
			$ont = "seshat";
			$context['ontology'] = $this->get_service_url("ontology", array($ont), "api", $context['collection']);	
		}
		else if($context['collection'] == "aligned"){
			$ont = "aligned";
			$context['ontology'] = $this->get_service_url("ontology", array($ont), "api", $context['collection']);	
		}
		else if($context['collection'] == "all"){
			$ont = "utv";
			$context['ontology'] = $this->get_service_url("ontology", array($ont), "api", $context['collection']);	
		}
		else {
			
			//$ont = "seshatarch";
		}	
	}
	
	function loadDataContext(&$context, LdDacuraServer &$dacura_server, DacuraUser $u){
		if($context['collection'] == "seshat"){
			//$context['type'] = $dacura_server->expandNS("seshat:Polity");
		}		
	}
	
	function getCollectionIDForURL($url, $available_contexts, $u){
		foreach(array_keys($available_contexts) as $k){
			if($k == "all" && !$u) continue;
			if(strpos($url, $k) !== false){
				return $k;
			} 
		}
		return false;
	}

	function getConsoleLoadCollection($available_contexts, LdDacuraServer &$dacura_server, DacuraUser $u){
		$url = $this->getOriginOfLoad();
		if($cid = $this->getCollectionIDForURL($url, $available_contexts, $u)){
			return $cid;
		}
		return array_keys($available_contexts)[0];		
	}
	
	function getConsoleUserContext($available_contexts, LdDacuraServer &$dacura_server, DacuraUser $u, $reload=false){
		$context = array();
		if($this->cid() != "all" || $reload){
			$context['collection'] = $this->cid();
		}
		else {
			$context['collection'] = $this->getConsoleLoadCollection($available_contexts, $dacura_server, $u);				
		}
		$context['title'] = $available_contexts[$context['collection']]['title'];
		if(isset($available_contexts[$context['collection']]['icon'])){
			$context['title'] = "<img class='dacura-icon' src='" .$available_contexts[$context['collection']]['icon']."'> ".$context['title'] ;
		}
		else {
			$context['title'] = "<img class='dacura-icon' src='" .$this->furl("images", "system/collection_icon.png") . "'> ".$context['title'] ;
		}
		
		$context['mode'] = $this->getConsoleLoadMode($context['collection'], $dacura_server, $u);
		if($context['mode'] == "model"){
			$this->loadModelContext($context, $dacura_server, $u);
		}
		else {
			$this->loadDataContext($context, $dacura_server, $u);				
		}
		return $context;
	}
	
	function getConsoleParams($dacura_server, DacuraUser $u){
		$params = array();
		$params['jslibs'] = array();
		$params['jslibs'][] = $this->get_service_script_url("dacura.utils.js", "core");
		$params['jslibs'][] = $this->get_service_script_url("jslib/ldlibs.js", "ld");
		$params['jslibs'][] = $this->get_service_script_url("dontology.js");
		$params['jslibs'][] = $this->get_service_script_url("dpagescanner.js");
		$params['jslibs'][] = $this->get_service_script_url("dconsole.js");
		$params['jslibs'][] = $this->get_service_script_url("dacura.frame.js", "candidate");
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
			$params['context'] = $this->getConsoleUserContext($pc, $dacura_server, $u);
			$params['apiurl'] = $this->durl(true) . ($params['context']['collection'] == "all" ? "" : $params['context']['collection'] ."/");
			$params['baseapiurl'] = $this->durl(true);
			$params['collection_contents'] = $this->getCollectionContents($params['context']['collection'], $dacura_server, $u);
		}
		else {
			$params['context'] = array("title" => "You are not a member of any dacura collections");
		}
		$params['create_candidate_options'] = $this->getLDOptions("console_create_candidate");
		$params['test_create_candidate_options'] = $this->getLDOptions("console_test_create_candidate");
		$params['update_candidate_options'] = $this->getLDOptions("console_update_candidate");
		$params['test_update_candidate_options'] = $this->getLDOptions("console_test_update_candidate");
		$params['update_ontology_options'] = $this->getLDOptions("console_update_ontology");
		$params['test_update_ontology_options'] = $this->getLDOptions("console_test_update_ontology");
		$params['update_graph_options'] = $this->getLDOptions("console_update_graph");
		$params['test_update_graph_options'] = $this->getLDOptions("console_test_update_graph");
		$params['view_args'] = $this->getLDArgs("console_view");
		$xcs = $dacura_server->createDependantService("candidate");
		$params['demand_id_token'] = $xcs->getServiceSetting("demand_id_token");
		$params['change_mode_icon'] = "<img class='console-icon' src='" . $this->furl("images", "roles/architect_icon.png") . "'></a>";
		$params['view_property_icon'] = "<img class='console-icon' src='" . $this->furl("images", "icons/sort_desc.png") . "'>";
		$params['new_thing_icon'] = "<img class='console-icon' height='16' src='" . $this->furl("images", "icons/create.png") . "'>";
		$params['collection_choices'] = $pc;
		$params['helptexts'] = array();
		$params['ontology_config'] = array(
				"boxtypes" => $this->getBoxedTypes($dacura_server),
				"capabilities" => array("update", "test_update", "delete", "deploy"),
				"entity_tag" => "dacura:Entity",
				"request_id_token" => $params['demand_id_token'],
				"helptexts" => array()				
		);
		$params['jquery_body_selector'] = 'body';
		//$params['scan'] = file_get_contents(dirname(__DIR__)."/console/screens/scan.js");
		return $params;
	}
	
	function getBoxedTypes($dacura_server){
		$cs = $dacura_server->createDependantService("candidate");
		$cands = $dacura_server->createDependantServer("candidate", $cs);
		$cands->init();
		if(!($dont = $cands->loadLDO("dacura", "ontology", "all"))){
			return array();
		}
		return $dont->getBoxedClasses();
	}
	
	function getCollectionContents($cid, DacuraServer $dacura_server, DacuraUser $u, $reload=false){
		$this->collection_id = $cid;
		$cs = $dacura_server->createDependantService("candidate");
		$cands = $dacura_server->createDependantServer("candidate", $cs);
		$cands->init();
		$contents = array();
		$ents = $cands->getValidCandidateTypes();
		$filter = array("type" => "candidate", 'collectionid' => $cid, "status" => array("accept", "pending"));
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
		$contents['graphs'] = $this->getGraphsForConsole($cands);
		$filter['type'] = "ontology";
		$onts = $cands->getLDOs($filter);
		$nonts = array();
		foreach($onts as $ont){
			$nont = array("id" => $ont['id']);
			if(isset($ont['meta']['title']) && $ont['meta']['title']){
				$nont['title'] = $ont['meta']['title'];
			}
			else {
				$nont['title'] = $ont['meta']['url'];
			}
			$nont['url'] = $this->get_service_url("ontology", array(), "api", $ont['collectionid'])."/".$ont['id'];
			$nonts[] = $nont;
		}
		$contents['ontologies'] = $nonts;
		$contents['scanner_config'] = $this->getPageScanConfig($dacura_server);
		return $contents;
	}
	
	function getPageScanConfig($dacura_server){
		//connectors
		//locators
		$scon = $dacura_server->getURLConnections($this->getURLofLoad());
		$scon['parser_url'] = $this->get_service_url("scraper", array("validate"), "api");
		$scon['factoid_config'] = array("iconbase" => $this->get_system_file_url("images", "icons/"));
		$scon['jquery_body_selector'] = "body";		
		return $scon;
		//parser_url
		//factoid_config
		//iconbase
	}
	
	
	function getGraphsForConsole($cands){
		$glist = array();
		foreach($cands->graphs as $gid => $graph){
			$glist[$gid] = $graph->getConsoleData($cands);	
		}
		return $glist;
	}
	
	function getReloadScript($dacura_server){
		if(!$u = $dacura_server->getUser()){
			return $this->failure_result("Must be logged in to reload console", 409);
		}
		$params = array();
		$pc = $dacura_server->getUserAvailableContexts();
		if(isset($pc[$this->cid()])){
			$params['context'] = $this->getConsoleUserContext($pc, $dacura_server, $u, true);
			$params['collection_contents'] = $this->getCollectionContents($params['context']['collection'], $dacura_server, $u, true);
		}
		else {
			$params['context'] = array("title" => "You are not a member of this dacura collection");
		}
		$params['create_options'] = $this->getLDOptions("console_create");
		$xcs = $dacura_server->createDependantService("candidate");
		$params['demand_id_token'] = $xcs->getServiceSetting("demand_id_token");
		$params['collection_choices'] = $pc;
		$f = $dacura_server->service->mydir."screens/reload.php";
		$service = $this;
		if(file_exists($f)){
			ob_start();
			include($f);
			$page = ob_get_contents();
			ob_end_clean();
			return $page;				
		}
		return $this->failure_result("No such screen $f", 404);
	}
}