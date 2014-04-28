<?php
getRoute()->get('/', 'get_next_candidate');
getRoute()->post('/', 'candidate_decision');
getRoute()->put('/session', 'start_session');
getRoute()->post('/session', 'manage_session');
getRoute()->delete('/session', 'end_session');
getRoute()->get('/widget', 'get_widget');
getRoute()->post('/widget', 'candidate_decision');


include_once("Candidate_viewerDacuraServer.php");
include_once("phplib/Widgetizer.php");

/*
if($action=='start_work'){
	$n = isset($_POST['year']) ? $_POST['year'] : 0;
	echo $dwas->startWorkSession($n);
}
elseif($action=='get_candidate'){
	if(isset($_POST['id'])){
		echo $dwas->getCandidate($_POST['id']);
	}
	else {
		$dwas->write_error("Missing required id field");
	}
}
elseif($action=='candidate_decision'){
	if(isset($_POST['id']) && isset($_POST['decision'])){
		$update = isset($_POST['update']) ? $_POST['update'] : "";
		echo $dwas->updateWorkSession($_POST['id'], $_POST['decision'], $update);
	}
	else {
		$dwas->write_error("Missing required chunk field");
	}
}*/

function start_session(){
	global $dacura_settings;
	$ds = new Candidate_viewerDacuraAjaxServer($dacura_settings);
	echo json_encode($ds->startSession());	
}

function manage_session(){
	global $dacura_settings;
	$ds = new Candidate_viewerDacuraAjaxServer($dacura_settings);
	$act = isset($_POST['action']) ? $_POST['action'] : false;
	if($act == 'pause'){
		$x = $ds->pauseSession();		
	}
	elseif($act == 'resume'){
		$x = $ds->resumeSession();
	}
	elseif($act == 'continue'){
		$x = $ds->continueSession();
	}
	else {
		$x = false;
	}
	if($x){
		echo json_encode($x);
		$ds->write_error($ds->errmsg, $ds->errcode);
	}
}

function end_session(){
	global $dacura_settings;
	$ds = new Candidate_viewerDacuraAjaxServer($dacura_settings);
	echo json_encode($ds->endSession());
}

function get_widget(){
	global $dacura_settings;
	$ds = new Candidate_viewerDacuraAjaxServer($dacura_settings);
	$wzer = new Widgetizer($dacura_settings['schema_graph'], $dacura_settings['sparql_source']);
	//$wdetails = array("width" => 450, "title" => "Political Violence Event Report");
	//$wzer->setWidgetDetails($wdetails);
	$widget_html = $wzer->getToolHTML($dacura_settings['tool_id'], $dacura_settings['base_class']);
	echo $widget_html;
}

function get_next_candidate(){
	global $dacura_settings;
	$ds = new Candidate_viewerDacuraAjaxServer($dacura_settings);
	$cand = $ds->getNextCandidate();
	if($cand){
		echo json_encode($cand);
	}
	else $ds->write_error($ds->errmsg, $ds->errcode);
	
}

function candidate_decision(){
	global $dacura_settings;
	$ds = new Candidate_viewerDacuraAjaxServer($dacura_settings);
	$id = $_POST['id'];
	$payload = isset($_POST['dcpayload']) ? $_POST['dcpayload'] : "";
	$decision = $_POST['decision'];
	echo $ds->candidateDecision($id, $decision, $payload);	
}


