<?php

class SimpleRequestResults extends AnalysisResults {
	var $result;//candidate object / update request
	function accept($res = false){
		if($res){
			$this->set_result($res);
		}
		$this->decision = "accept";
		return $this;
	}
	
	function set_result($obj){
		$this->result = $obj;
		return $this;
	}
	
}

class RequestAnalysisResults extends SimpleRequestResults {
	var $candidate_graph_update;
	var $report_graph_update;
	var $update_graph_update;
	var $sub_analyses = array();//only for testing...
	var $result;//candidate object / update request


	function add($other, $chain = true){
		$this->sub_analyses[] = $other;
		parent::add($other, $chain);
	}

	function setReportGraphResult($gu, $hypo = false){
		$gu->setHypothetical($hypo);
		$action = "Data Quality Service";
		if(!$hypo){
			switch($gu->decision) {
				case "reject" :
					if($gu->is_error()){
						if(!$this->is_error()){
							$this->errcode = $gu->errcode;
							$this->msg_title = "Rejected by ".$action;
						}
						$this->addError($gu->errcode, $action, $gu->getErrorsSummary(), $gu->getErrorsDetails(), "graph");
					}
					else {
						$this->addError(200, $action, $gu->getErrorsSummary(), $gu->getErrorsDetails(),  "graph");
						if(!$this->is_reject()){
							$this->msg_title = "Rejected by ".$action;
						}
					}
					$this->decision = "reject";
					break;
				case "accept" :
					if($this->is_accept()){
						$this->decision = "accept";
					}
					break;
			}
		}
		elseif($gu->is_reject()) {
			$txt = "This candidate cannot be published as a report";
			$body = $gu->getErrorsSummary();
			$this->addWarning("Data Quality Service", $txt, $body);
		}
		if(!isset($this->report_graph_update)){
			$this->report_graph_update = $gu;
		}
		else {
			$this->report_graph_update->addGraphResult($gu);
		}
	}

	function undoReportGraphResult($gu){
		unset($this->candidate_graph_update);
		$this->report_graph_update->addGraphResult($gu);
	}

	function setUpdateGraphResult($udelta){
		$this->update_graph_update = new GraphAnalysisResults("Analysing Updates to Update Graph");
		$this->update_graph_update->setInserts($udelta['add']);
		$this->update_graph_update->setDeletes($udelta['del']);
		$this->update_graph_update->setMeta($udelta['meta']);
	}

	function setCandidateGraphResult($itrips = array(), $dtrips = array(), $is_hypo = false, $meta = false){
		$this->candidate_graph_update = new GraphAnalysisResults("Updating Candidate Graph");
		$this->candidate_graph_update->setInserts($itrips);
		$this->candidate_graph_update->setDeletes($dtrips);
		$this->candidate_graph_update->setHypothetical($is_hypo);
		if($meta){
			$this->candidate_graph_update->setMeta($meta);
		}
	}

	function includesGraphChanges(){
		return isset($this->report_graph_update) && $this->report_graph_update->includesGraphChanges();
	}
}
