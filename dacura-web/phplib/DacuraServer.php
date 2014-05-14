<?php

/*
 * The Core Dacura Server
 * It only includes functionality that is needed in multiple places
 * For service specific functionality, extend the class in the server...
 */

//require_once("EventRecord.php");
//require_once("Widgetizer.php");
require_once("UserManager.php");
//require_once("CandidateManager.php");
//require_once("InstanceURI.php");
require_once("utilities.php");

class DacuraServer {
	var $settings;
	var $sm;	//user/session manager
	var $cm;	//candidate manager
	var $sysman; //storage manager
	//var $appman; //mini-application manager
	var $ucontext; //user context
	
	var $errmsg;
	var $errcode;
	
	function __construct($dacura_settings){
		$this->settings = $dacura_settings;
		try {
			$this->sysman = new SystemManager($this->settings['db_host'], $this->settings['db_user'], $this->settings['db_pass'], $this->settings['db_name']);
		}
		catch (PDOException $e) {
			return $this->failure_result('Connection failed: ' . $e->getMessage(), 500);
		}
		$this->sm = new UserManager($this->sysman, $this->settings);
	}
	
	function failure_result($msg, $code = 500){
		$this->errmsg = $msg;
		$this->errcode = 500;
		return false;
	}
	
	/* 
	 * Config related functions
	 */
	function getDataset($id){
		$obj = $this->sysman->getDataset($id);
		return $obj;
	}
	
	function updateDataset($id, $ctit, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->sysman->updateDataset($id, $ctit, $obj)){
			return $obj;
		}
		return false;
	}
	
	function updateCollection($id, $ctit, $obj){
		$u = $this->getUser(0);
		if(!$u)	return $this->failure_result("Denied! Need logged in user", 401);
		if($this->sysman->updateCollection($id, $ctit, $obj)){
			return $obj;
		}
		return false;
	}
	
	function getCollection($id){
		$obj = $this->sysman->getCollection($id);
		return $obj;
	}
	
	function getCollectionList(){
		$obj = $this->sysman->getCollectionList();
		return $obj;
	}
	
	/*
	 * User related functions
	 */
	
	function isLoggedIn(){
		return $this->sm->isLoggedIn();
	}
	
	function addUser($u, $p, $n, $status){
		$u = $this->sm->adduser($u, $n, $p, $status);
		return ($u) ? $u : $this->failure_result("Failed to create user ".$this->sm->errmsg, 401);
	}
	
	function getUser($id=0){
		$u = $this->sm->getUser($id);
		return ($u) ? $u : $this->failure_result("Failed to retrieve user $id: ".$this->sm->errmsg, 404);
	}
	
	function deleteUser($id){
		if(!$id){
			return $this->failure_result("User ID not supplied, cannot delete", 400);
		}
		else {
			return ($this->sm->deleteUser($id)) ? "$id Deleted" : $this->failure_result($this->sm->errmsg, 404);
		}
	}
	
	function updateUser($u){
		return $this->sm->saveUser($u);
	}
	
	function getusers(){
		$u =  $this->sm->getUsers();
		return ($u) ? $u : $this->failure_result("Failed to retrieve user list: ".$this->sm->errmsg, 404);
	}
	
	/*
	 * Returns a data structure describing the collection / dataset context available to the user 
	 * given his or her roles. 
	 */
	function getUserAvailableContexts($role=false, $authority_based = false){
		$u = $this->getUser(0);
		$cols = $this->getCollectionList();
		$choices = array();
		if($authority_based){
			if($u->isGod()){
				$choices["0"] = array("title" => "All collections", "datasets" => array("0" => "All Datasets"));
			}	
		}
		else {
			if($u->rolesSpanCollections($role)){
				$choices["0"] = array("title" => "All collections", "datasets" => array("0" => "All Datasets"));
			}
		}
		foreach($cols as $colid => $col){
			if($col->status == "deleted"){
				
			}
			elseif($u->hasCollectionRole($colid, $role)){
				$choices[$colid] = array("title" => $col->name, "datasets" => array("0" => "All Datasets"));
				foreach($col->datasets as $datid => $ds){
					$choices[$colid]["datasets"][$datid] = $ds->name;
				}
			}
			else {
				$datasets = array();
				foreach($col->datasets as $datid => $ds){
					if($ds->status != "deleted" && $u->hasDatasetRole($datid, $role)){
						$datasets[$datid] = $ds->name;
					}
				}
				if(!$authority_based && count($datasets) > 0){
					if(count($datasets) > 1) $datasets["0"] = "All datasets";
					$choices[$colid] = array("title" => $col->name, "datasets" => $datasets);
				}
			}
		}
		return $choices;
	}
	
	function getUserHomeContext($u){
		$appcontext = new ApplicationContext();
		$appcontext->setName("browse");		
		if($u->isGod() or $u->rolesSpanCollections()){
		}
		else {
			$cid = $u->getRoleCollectionId();
			if($cid){
				$appcontext->setCollection($cid);
				if($u->isCollectionAdmin($cid) or $u->rolesSpanDatasets($cid)){
					return $appcontext;
				}
				else {
					$dsid = $u->getRoleDatasetId();
					if($dsid) $appcontext->setDataset($dsid);
				}
			}
		}
		return $appcontext;
		//is the user god?  has the user roles in multiple collections? 
		// => root
		//is the user an account admin? does the user have roles in multiple datasets? 
		// => account/x
		//no => dataset...
		//return "A";
	}
	
	//$app = $ds->setUserContext($dcuser, $path);
	function setUserContext(&$dcuser, $path){
		$this->appcontext = $this->appman->getAppcontext($path);
		$this->appman->acceptUser($this->appcontext, $dcuser);
		return $this->appcontext;
	}
	
	function renderUserActions($appcontext, $u){
		if($u->isGod()){
			echo "<a href='".$this->settings['install_url']."home/create/collection'><div class='dacura-dashboard-button' id='dacura-create-collection-button'>Create Collection</div></a>";				
		}
		echo "<div class='dacura-dashboard-button' id='dacura-users-button'></div>";
		echo "<div class='dacura-dashboard-button' id='dacura-datasets-button'></div>";
		echo "<div class='dacura-dashboard-button' id='dacura-report-button'></div>";				
	}
	
	function renderUserGraph($appcontext, $u){
		echo "<hr style='clear: both'>";
	}
	
	function renderUserStats($appcontext, $u){
	}
	
	function renderUserMenu($appcontext, $u){
		echo "<UL class='dashboard-menu'><li class='dashboard-menu-selected'></li><li>Not selected</li></UL>";
	}
	
	function renderUserErrorMessage($title, $msg){
		echo "<div id='pagecontent-container'><div id='pagecontent' class='pagecontent-failure'>\n";
		echo "<h1>$title</h1><p>$msg</p></div></div>";
	}
	
/*	function getStatus($id, $type){
		$details = array();
		if($type == 'collection'){
			$details = $this->cm->getCollectionDetails();
		}
		elseif($type == "sesssion"){
			$details = $this->getWorkSessionDetails();
		}
		else {
			$details = $this->sm->getUserCollectionDetails($id, $this->cm);
		}
		return $details;
	}
	
	function startSession($id){
		if($this->sm->startUserSession($id, $this->cm)){
			return true;
		}
		else {
			return $this->failure_result("Failed to start work session. " . $this->sm->errmsg , 400);
		}
	}
*/	
	/** 
	 * Reports and that jazz
	 */
/*	
	function getEventRecord($id) {
		$fullid = $this->id_prefix.$id;
		$rec = new EventRecord($fullid);
		$rec->setDataSource($this->source, $this->schema_graph, $this->data_graph);
		if($rec->loadFromDB(true)){
			return $rec;
		}
		else {
			return $this->failure_result("Failed to load record $id. ".$rec->getErrorString(), 404);
		}
	}
	
	function getReportFromDB($id){
		$rep = $this->sm->remoteSessionFetchReport($this->cm, $id);
		if(!$rep){
			return $this->failure_result("Failed to get report $id." .$this->sm->errmsg, 404);
		}
		return $rep;
	}
	
	function getReports($uid, $chunkid){
		$remote_reports = $this->cm->getRemoteReports($uid, $chunkid);
		$local_reports = $this->cm->getLocalReports($uid, $chunkid);
		$all_reports = array("remote" => $remote_reports, "local" => $local_reports);
		//foreach($remote_reports as array_merge($remote_reports, $local_reports);
		return $all_reports;
	}

	function copyReport($id, $uid, $rtype){
		if($rtype == 'remote'){
			if($this->cm->copyReport($id, $uid)){
				return "Successfully copied report $id to user $uid";
			}
			else {
				return $this->failure_result("Failed to copy report $id to $uid." .$this->cm->errmsg, 404);
			}
		}
		else {
			return $this->failure_result("Failed to copy report $id to $uid. not implemented for local reports", 500);
		}
	}
	
	function fileCandidate($cand_descr) {
		$fetch_cand = false;
		$yr = (isset($cand_descr['citation']) && isset($cand_descr['citation']['issuedate']) && isset($cand_descr['citation']['issuedate']['year'])) ? $cand_descr['citation']['issuedate']['year']: 0;
		$id = (isset($cand_descr['citation']) && isset($cand_descr['citation']['articleid'])) ? $cand_descr['citation']['articleid']: 0;
		if($yr && $id){
			if($this->cm->has_candidate($id)){
				return $this->failure_result("$yr $id already captured", 202);
			}
			else{
				$imgurl = (isset($cand_descr['citation']) && isset($cand_descr['citation']['articleimage'])) ? $cand_descr['citation']['articleimage'] : false;
				if($fetch_cand && $this->fetchCandidateImage($id, $yr, $imgurl)){
					$info = getimagesize($this->settings['candidate_store'] . $yr ."/" .$id . ".jpg");
					$cand_descr['cached_image'] = array("height" => $info[0], "width" => $info[1]);
				}
				elseif($fetch_cand) {
					$cand_descr['cached_image'] = "error: ".$this->errmsg;
				}
				if($this->cm->add_candidate($id, $yr, json_encode($cand_descr))){
					return true;
				}
				else {
					return $this->failure_result("Failed to store candidate: " . $this->cm->errmsg, 500);
				}
			}
		
		}
		else {
			return $this->failure_result("Missing required fields: year, id ($yr, $id)", 400);
		}
	}
	
	function fetchCandidateImage($id, $yr, $imgurl){
		$ch = curl_init();
		//$user_agent = $_SERVER['HTTP_USER_AGENT'];
		$user_agent = "Mozilla/5.0 (Windows NT 6.0; WOW64; rv:24.0) Gecko/20100101 Firefox/24.0";
		curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
		curl_setopt($ch, CURLOPT_URL, $imgurl);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		if($this->http_proxy){
			curl_setopt($ch, CURLOPT_PROXY, $this->http_proxy);
		}
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		//
		if(curl_errno($ch)){
			curl_close($ch);
			return $this->failure_result("Curl error $imgurl: " . curl_error($ch), 503);
		}
		elseif( $output === '' ){
			curl_close($ch);
			return $this->failure_result("URL returned no data for $id", 503);
		}
		elseif( $info['http_code'] != 200){
			curl_close($ch);
			return $this->failure_result($info['http_code'] . ': Bad response, '.$info['http_code'].": $id ".$output, 503);
		}
		else {
			curl_close($ch);
			if(!file_exists($this->settings['candidate_store'] . $yr)){
				if(!mkdir($this->settings['candidate_store'] . $yr)){
					return $this->failure_result($info['http_code'] . ': Bad response, '.$info['http_code'].": $id ".$output, 503);
				}
			}
			$opfile = $this->settings['candidate_store'] . $yr. "/" . $id . ".jpg";
			if(file_put_contents($opfile, $output) === false){
				return $this->failure_result("Failed to write candidate ID to $opfile", 500);
			}
			return $opfile;
		}
	}
	
	function getToolHTML($tool_id){
		$wzer = new Widgetizer($this->settings['schema_graph'], $this->settings['source']);
		$widget_html = $wzer->getToolHTML($tool_id, $this->settings['base_class']);
		return $widget_html;
	}
	

	function getchunkids($f){
		return $this->cm->getIncompleteChunkList();
	}
	
	function allocate($n, $y){
		if(!$y){
			return $this->failure_result("Chunk id missing", 400);
		}
		if(!$this->cm->chunkExists($y)){
			return $this->failure_result("Chunk $y does not exist", 400);
		}
		$u = $this->sm->allocateChunk($n, $y);
		if($u){
			return $u;
		}
		else {
			return $this->failure_result("Failed to allocate chunk $y to $n ".$this->sm->errmsg, 401);
		}
	}

	function publish($id, $type){
	
	}
	
	function getNewInstanceURI($prefix){
		$sparql = new SparqlBridge($this->settings['source']);
		$iuri = new InstanceURI($this->settings['id_prefix'], $this->settings['data_graph']);
		$url = $iuri->getURL($sparql, $prefix);
		if($url){
			return $url;
		}
		else {
			return $this->failure_result("Failed to get unique id for $prefix => ".$iuri->errmsg, 500);
		}
	}
	
*/
	
	/*
	 * Local Candidate Decisions
	*/
	
	/*
	function loadCandidateImages($cand){
		$big = array();
		$small = array();
		$cand_file = $this->settings['candidate_store'] . $cand->chunkid . "/".$cand->id.".jpg";
		$cand_url = $this->settings['candidate_images'] . $cand->chunkid . "/".$cand->id.".jpg";
		$preview_url = $this->settings['candidate_images'] . $cand->chunkid . "/preview/".$cand->id.".jpg";
		//$big['file'] = $cand_file;
		//if(file_exists($cand_file)){
		$info = @getimagesize($cand_url);
		if($info){
			$big['url'] = $cand_url;
			$big['width'] = $info[0];
			$big['height'] = $info[1];
			$big['local'] = true;
			$info = @getimagesize($preview_url);
			$small['url'] = $preview_url;
			$small['width'] = $info[0];
			$small['height'] = $info[1];
			$small['local'] = true;
			$imgs = array("full" => $big, "preview" => $small);
			return $imgs;
		}
	
		return false;
	
		//}
		//else {
		//		if(isset($cand->contents['citation']) and isset($cand->contents['citation']['articleimage']) && $cand->contents['citation']['articleimage']){
		//			$big['url'] = $cand->contents['citation']['articleimage'];
		//			$big['local'] = false;
		//		}
		//		else {
		//			return false;
		//		}
		//	}
		$imgs = array("full" => $big, "preview" => $small);
		return $imgs;
	}
	*/
	function write_error($str, $code = 400){
		http_response_code($code);
		echo $str;
		return false;
	}
	
}
