<?php
include_once("phplib/DacuraServer.php");
include_once("phplib/db/CandidatesDBManager.php");

class Candidate_viewerDacuraServer extends DacuraServer {

	var $dbclass = "CandidatesDBManager";
	


	//session management
	function startSession(){
		$u = $this->userman->getUser();
		if($u){
			if($u->createSession("candidate_viewer")){
				return $u->getSessionDetails("candidate_viewer");
			}
			else {
				return $this->failure_result("Failed to start work session. " . $u->errmsg , 400);				
			}
		}
		else {
			return $this->failure_result("Failed to start work session. " . $this->userman->errmsg, 400);				
		}
		return false;
	}
	
	function pauseSession(){
		$u = $this->userman->getUser();
		$sess =& $u->sessions["candidate_viewer"];
		$sess->pause();
		return $u->getSessionDetails("candidate_viewer");
	}
	
	function resumeSession(){
		$u = $this->userman->getUser();
		$sess =& $u->sessions["candidate_viewer"];
		$sess->unpause();
		return $u->getSessionDetails("candidate_viewer");
	}
	

	function endSession(){
		$u = $this->userman->getUser();
		$x = $u->getSessionDetails("candidate_viewer");
		$u->endSession("candidate_viewer");
		return $x;
	}
	
	
	function continueSession(){
		$u = $this->userman->getUser();
		return $u->getSessionDetails("candidate_viewer");
	}
	

	function candidateDecision($id, $dec, $upd){
		$u = $this->userman->getUser();
		$ev = array("action" => $dec, "id" => $id);
		$u->setSessionEvent("candidate_viewer", $ev);
		$u->unsetCurrentCandidate("candidate_viewer");
		return $this->dbman->process_candidate($id, $u->id, $dec, $upd);
	}
	
	function getNextCandidate(){
		$u = $this->userman->getUser();
		$sess = $u->sessions['candidate_viewer'];
		$cid = $sess->getOpenAssignedCandidate();
		if(!$cid){
			//echo $current_chunk . " is the year";
			$cid = $this->dbman->assignNextCandidate($u->id);
			if($cid){
				$sess->assignCandidate($cid);
				$u->sessions['candidate_viewer'] = $sess;
				$cand = $this->dbman->loadCandidate($cid, false);
				if(!$cand){
					$this->errmsg = $this->dbman->errmsg;
					return false;
				}
			}
			else {
				$this->errmsg = $this->dbman->errmsg;
				return false;
			}
		}
		else {
			$cand = $this->dbman->loadCandidate($cid, false);
		}
		if($cand){
			$x = $this->loadCandidateImages($cand);
			if($x) $cand->image = $x;
			return $cand;
		}
		$this->errmsg = $this->userman->errmsg;
		return false;
	}
	

	function loadCandidateImages($cand){
		$big = array();
		$small = array();
		//$cand_file = $this->candidate_store . $cand->chunkid . "/".$cand->id.".jpg";
		$cand_url = $this->settings['candidate_images'] . $cand->chunkid . "/".$cand->permid.".jpg";
		$preview_url = $this->settings['candidate_images']. $cand->chunkid . "/preview/".$cand->permid.".jpg";
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
	}
	
	

	function getTool(){
		if($this->userman->isLoggedIn()){
			$u = $this->userman->getUser();
			$tool_id = $u->session->getCurrentLocalToolID();
		}
		if(!$tool_id) $tool_id = 'candidates';
		$wzer = new Widgetizer($this->schema_graph, $this->source);
		//$wdetails = array("width" => 450, "title" => "Political Violence Event Report");
		//$wzer->setWidgetDetails($wdetails);
		$widget_html = $wzer->getToolHTML($tool_id, $this->base_class);
		echo $widget_html;
	}
	
	

}

class Candidate_viewerDacuraAjaxServer extends Candidate_viewerDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}