<?php
require_once("DacuraServer.php");
require_once("http_response_code.php");

class DacuraAjaxServer extends DacuraServer {


	function suggestReport($rep){
		$info = array(
				"status" => "ok",
				"items" => array(),
		);
		return json_encode($info);
	}
	
	
	
	function candidateDecision($id, $dec, $upd){
		$u = $this->sm->getUser();
		if($this->sm->updateUserSession($id, $dec, $this->cm, $upd)){
			return json_encode($this->getWorkSessionDetails());
		}
		else {
			return $this->failure_result("Failed to update work session. " . $this->sm->errmsg , 400);
		}
	}

	function getNextCandidate(){
		$cand = $this->sm->getNextCandidate($this->cm);
		if($cand){
			$x = $this->loadCandidateImages($cand);
			if($x) $cand->image = $x;
			return json_encode($cand);
		}
		return false;
	}

	function getCandidate($id){
		$cand = $this->cm->loadCandidate($id);
		if($cand){
			$x = $this->loadCandidateImages($cand);
			if($x) $cand->image = $x;
			return json_encode($cand);
		}
		return $this->failure_result("Failed to load candidate $id: ".$this->cm->errmsg, 404);
	}


	function getReports($uid, $chunkid){
		$reps = parent::getReports($uid, $chunkid);
		if($reps) return json_enode($reps);
		return false;
	}

	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}

	function getEventRecord($id){
		$rec = parent::getEventRecord($id);
		if($rec){
			$data = $rec->getAsArray();
			echo json_encode($data);
			return true;
		}
		return false;
	}




	function selfTest(){
		if($this->cm->hasLink()){
			echo "DB Link OK\n";
		}
		else {
			echo "DB Link Down: ".$this->cm->errmsg;
		}
		//opr( $this->getstatus("1", "user"));
		opr( $this->cm->assignNextCandidate('abc', '1831'));
	}

	function getReportListing($stuff){
		$headers = array("id", "type", "user", "time", "year", "summary");
		$reports = $this->cm->getReportLists(true);
		$ret = array("aaData" => $reports);
		echo json_encode($ret);
	}

	function getReportArray($stuff = false){
		$reports = $this->cm->getReportLists(false);
		$arr = array();
		$arr[] = array("Candidate ID", "Published", "Report ID", "Source", "User",
				"Date Submitted", "Report Year", "Event Date (From)", "Event Date (to)", "Country", "County","Place",
				"Fatalities", "Type", "Description", "Motivation",
				"Actor 1 Type",  "Actor 1 Number",  "Actor 1 Group", "Actor 1 fatalities", "Actor 1 represents", "Actor 1 represents group",
				"Actor 2 Type",  "Actor 2 Number",  "Actor 2 Group", "Actor 2 fatalities", "Actor 2 represents", "Actor 2 represents group",
				"Actor 3 Type",  "Actor 3 Number",  "Actor 3 Group", "Actor 3 fatalities", "Actor 3 represents", "Actor 3 represents group",
				"Actor 4 Type",  "Actor 4 Number",  "Actor 4 Group", "Actor 4 fatalities", "Actor 4 represents", "Actor 4 represents group",
				"Citation (Publication)","Citation (Publication URL)", "Citation (Issue Title)", "Citation (Issue URL)", "Citation (Issue Date)",
				"Citation (Section Title)","Citation (Section URL)", "Citation (Article Title)", "Citation (Article URL)", "Citation (Article ID)",
				"Citation (Article Image)","Citation (Page from)", "Citation (Page to)",
				"Dubious");
		foreach($reports as $rep){
			$obj = json_decode($rep[6], true);
			$issuedate = "";
			if($obj){
				$issuedate = (isset($obj['citation']['issuedate']) && isset($obj['citation']['issuedate']['day']))? $obj['citation']['issuedate']['day']."/".$obj['citation']['issuedate']['month']."/".$obj['citation']['issuedate']['year'] : "";
				if($rep[0] == ""){
					$rep[0] = isset($obj['citation']['articleid']) ? $obj['citation']['articleid'] : "";
				}
			}

			$rec = array($rep[0], $issuedate, $rep[1], $rep[2], $rep[3], $rep[4], $rep[5]);
			if($obj){
				//date
				$v = "";
				if(isset($obj['date'])  && isset($obj['date']['from'])){
					$v = ($obj['date']['from']['day']) ? $obj['date']['from']['day'] . "/" : "";
					$v .= ($obj['date']['from']['month']) ? $obj['date']['from']['month'] . "/" : "";
					$v .= ($obj['date']['from']['year']) ? $obj['date']['from']['year'] : "";
				}
				else {
					$v = "";
				}
				$rec[] = $v;
				$v = "";
				if(isset($obj['date'])  && isset($obj['date']['to'])){
					$v = ($obj['date']['to']['day']) ? $obj['date']['to']['day'] . "/" : "";
					$v .= ($obj['date']['to']['month']) ? $obj['date']['to']['month'] . "/" : "";
					$v .= ($obj['date']['to']['year']) ? $obj['date']['to']['year'] : "";
				}
				else {
					$v = "";
				}
				$rec[] = $v;
				//location
				if(isset($obj['location']) && isset($obj['location']['country'])){
					$v = $obj['location']['country'];
				}
				else {
					$v = "";
				}
				$rec[] = $v;
				if(isset($obj['location']) && isset($obj['location']['county'])){
					$v = $obj['location']['county'];
				}
				else {
					$v = "";
				}
				$rec[] = $v;
				if(isset($obj['location']) && isset($obj['location']['place'])){
					$v = $obj['location']['place'];
				}
				else {
					$v = "";
				}
				$rec[] = $v;
				//fatalities
				if(isset($obj['fatalities']) && isset($obj['fatalities']['type'])){
					if($obj['fatalities']['type'] == "number"){
						$v = $obj['fatalities']['from'];
					}
					elseif($obj['fatalities']['type'] == "range"){
						$v = $obj['fatalities']['from']. "--" . $obj['fatalities']['to'];
					}
					else {
						$v = "unknown";
					}
				}
				else {
					$v = "";
				}
				$rec[] = $v;

				//$rec[] = isset($obj['fatalities']) ? json_encode($obj['fatalities']) : "";
				$rec[] = isset($obj['type']) ? $obj['type'] : "";
				$rec[] = isset($obj['description']) ? $obj['description'] : "";

				$rec[] = isset($obj['motivation']) ? implode(", ", ($obj['motivation'])) : "";
				if(isset($obj['actors'])){
					foreach($obj['actors'] as $actor){
						if($actor['actortype']){
							$rec[] = $actor['actortype'];
							if($actor['actormin'] && $actor['actormin'] != "min" && $actor['actormax'] && $actor['actormin'] != "max"){
								$rec[] = $actor['actormin'] ."--".$actor['actormax'];
							}
							elseif($actor['actormin'] && $actor['actormin'] != "min"){
								$rec[] = $actor['actormin'];
							}
							else {
								$rec[] ="";
							}
							if($actor['groupname'] && $actor['groupname'] != "group name"){
								$rec[] = $actor['groupname'];
							}
							else {
								$rec[] = "";
							}
							if(isset($actor['fatalitytype'])){
								if($actor['fatalitytype']== "number"){
									$v = $actor['fatalityfrom'];
								}
								elseif($actor['fatalitytype'] == "range"){
									$v = $actor['fatalityfrom']. "--" . $actor['fatalityto'];
								}
								else {
									$v = "unknown";
								}
							}
							else {
								$v = "";
							}
							$rec[] = $v;
							$rec[] = $actor['represents'];
							//$rec[] = $actor['representsgroup'];
							$rec[] = ($actor['representsgroup'] &&  ($actor['representsgroup'] != "name")) ? $actor['representsgroup'] : "";
						}
						else {
							for($i = 0; $i < 6; $i++){
								$rec[] = "";
							}
						}
					}
				}
				//$rec[] = isset($obj['actors']) ? json_encode($obj['actors']) : "";
				$rec[] = $obj['citation']['publicationtitle'];
				$rec[] = $obj['citation']['publicationurl'];
				$rec[] = $obj['citation']['issuetitle'];
				$rec[] = $obj['citation']['issueurl'];
				$rec[] = $obj['citation']['issuedate']['day']."/".$obj['citation']['issuedate']['month']."/".$obj['citation']['issuedate']['year'];
				$rec[] = $obj['citation']['sectiontitle'];
				$rec[] = $obj['citation']['sectionurl'];
				$rec[] = $obj['citation']['articletitle'];
				$rec[] = $obj['citation']['articleurl'];
				$rec[] = $obj['citation']['articleid'];
				$rec[] = $obj['citation']['articleimage'];
				$rec[] = $obj['citation']['articlepagefrom'];
				$rec[] = $obj['citation']['articlepageto'];

				$rec[] = isset($obj['dubious']) ? implode(", ", $obj['dubious']) : "";
			}
			else {
				for($i = 0; $i < 46; $i++){
					$rec[] = "";
				}
			}
			if($rec[4] != "abc")
				$arr[] = $rec;
		}
		return $arr;
	}


}

class DacuraLocalAjaxServer extends DacuraAjaxServer {

	function getTool(){
		if($this->sm->isLoggedIn()){
			$u = $this->sm->getUser();
			$tool_id = $u->session->getCurrentLocalToolID();
		}
		if(!$tool_id) $tool_id = 'candidates';
		$wzer = new Widgetizer($this->schema_graph, $this->source);
		//$wdetails = array("width" => 450, "title" => "Political Violence Event Report");
		//$wzer->setWidgetDetails($wdetails);
		$widget_html = $wzer->getToolHTML($tool_id, $this->base_class);
		echo $widget_html;
	}


	function startSession($id){
		if(parent::startSession($id)){
			return json_encode($this->getWorkSessionDetails());
		}
		else {
			return false;
		}
	}

	function getWorkSessionDetails(){
		$dets = $this->sm->getWorkSessionDetails($this->cm);
		$data = "<div class='dacura-session-status-header'>Session started <strong>".gmdate("d M Y, H:i", $dets['session']['started'])."</strong></div>";
		$data .= "<table class='dacura-session-status'>";
		$data .= "<tr><th>Viewed</th><th>Accepted</th><th>Rejected</th><th>Skipped</th>";
		$data .= "<tr><td>".(isset($dets['session']['decisions']) && isset($dets['session']['decisions']['assign']) ? $dets['session']['decisions']['assign'] : "0") . "</td>";
		$data .= "<td>".(isset($dets['session']['decisions']) && isset($dets['session']['decisions']['accept']) ? $dets['session']['decisions']['accept'] : "0" ). "</td>";
		$data .= "<td>".(isset($dets['session']['decisions']) && isset($dets['session']['decisions']['reject']) ? $dets['session']['decisions']['reject'] : "0" ). "</td>";
		$data .= "<td>".(isset($dets['session']['decisions']) && isset($dets['session']['decisions']['skip']) ? $dets['session']['decisions']['skip'] : "0"). "</td></tr>";
		$data .= "</table>";
		$dets['session']['html'] = $data;
		$dets['session']['duration'] = gmdate("H:i:s", $dets['session']['duration']);

		$data = "<div class='dacura-session-status-header'>Working on <strong><span class='current-chunkid'></span></strong></div>";
		$data .= "<table class='dacura-session-status'>";
		$data .= "<tr><th>Viewed</th><th>Accepted</th><th>Rejected</th><th>Remaining</th>";
		$data .= "<tr><td>". (isset($dets['chunk']['user']) && isset($dets['chunk']['user']['assign']) ? $dets['chunk']['user']['assign'] : "0"). "/".(isset($dets['chunk']['total']) && isset($dets['chunk']['total']['total']) ? $dets['chunk']['total']['total'] : "0") . "</td>";
		$data .= "<td>". (isset($dets['chunk']['user']) && isset($dets['chunk']['user']['accept']) ? $dets['chunk']['user']['accept'] : "0"). "/".(isset($dets['chunk']['total']) && isset($dets['chunk']['total']['accept']) ? $dets['chunk']['total']['accept'] : "0") . "</td>";
		$data .= "<td>". (isset($dets['chunk']['user']) && isset($dets['chunk']['user']['reject']) ? $dets['chunk']['user']['reject'] : "0"). "/".(isset($dets['chunk']['total']) && isset($dets['chunk']['total']['reject']) ? $dets['chunk']['total']['reject'] : "0") . "</td>";
		$data .= "<td>". (isset($dets['chunk']['total']) && isset($dets['chunk']['total']['remaining']) ? $dets['chunk']['total']['remaining'] : "0") . "</td>";
		$data .= "</tr></table>";
		$data .= "<div class='dacura-session-status-header'>Viewing candidate <span class='current-candidateid'></span></div>";
		$dets['chunk']['html'] = $data;
		return $dets;
	}

	function continueSession(){
		return json_encode($this->getWorkSessionDetails());
	}
}

class DacuraRemoteAjaxServer extends DacuraAjaxServer {

	function getTool(){
		$u = $this->sm->getUser();
		$tool_id = $u->session->getCurrentRemoteToolID();
		if(!$tool_id) $tool_id = 'display';

	}


	function startSession($id){
		if($this->sm->startRemoteSession(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "local")){
			echo $this->getRemoteSessionPane($this->sm->getUser());
			return true;
		}
		else {
			return $this->failure_result("Failed to start remote session. " . $this->sm->errmsg , 500);
		}
	}

	function continueSession(){
		return $this->getRemoteSessionHTML();
	}

	function getRemoteSessionHTML(){
		$dets = $this->sm->getRemoteSessionDetails();
		$data = "<table class='dacura-remote-session-status'><tr><th>Started</th><td>".gmdate("M d Y H:i:s", $dets['started']) ."</td><th>Status</th><td>".$dets['status'].
		"</td></tr><tr><th>Duration</th><td>" . gmdate("H:i:s", $dets['duration']) .
		"</td><th>Submitted</th><td>".$dets['submitted']."</td></tr></table>";
		return $data;
	}

	function getLoginBox(){
		$wzer = new Widgetizer($this->schema_graph, $this->source);
		$widget_html = $wzer->getLoginWidget();
		echo $widget_html;
		return false;

	}

	function getRemoteSessionScreen(){
		if(!$this->sm->isLoggedIn()){
			$wzer = new Widgetizer($this->schema_graph, $this->source);
			$widget_html = $wzer->getLoginWidget();
			echo $widget_html;
			return false;
		}
		else {
			if(!$this->sm->hasLiveRemoteSession()){
				return $this->startSession(false, 0);
			}
			else {
				$this->sm->addRemoteInvocation(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "local");
			}
			echo $this->getRemoteSessionPane($this->sm->getUser());
			//echo $this->getTool("remote");
			return true;
		}
	}

	function updateRemoteSession($upd){
		if(!$this->sm->isLoggedIn()){
			$this->write_error("Not logged in cannot get introduce new reports", 401);
			return false;
		}
		if($this->sm->updateRemoteSession($this->cm, $upd)){
			return $this->getRemoteSessionHTML();
		}
		else {
			$this->write_error("Failed to update work session. " . $this->sm->errmsg , 400);
		}
		return false;

	}


	function getRemoteSessionPane($u){
		$wzer = new Widgetizer($this->schema_graph, $this->source);
		$widget_html = $wzer->getRemoteSessionWidget($u, $this->getRemoteSessionHTML());
		echo $widget_html;
	}
}

