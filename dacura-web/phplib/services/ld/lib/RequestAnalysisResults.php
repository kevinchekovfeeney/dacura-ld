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

class UpdateAnalysisResults extends SimpleRequestResults {
	var $candidate_graph_update;
	var $report_graph_update;
	var $update_graph_update;
	var $sub_analyses = array();//only for testing...
	var $result;//candidate object / update request


	function add($other, $chain = true){
		$this->sub_analyses[] = $other;
		parent::add($other, $chain);
	}

	function includesGraphChanges(){
		return isset($this->report_graph_update) && $this->report_graph_update->includesGraphChanges();
	}
}
