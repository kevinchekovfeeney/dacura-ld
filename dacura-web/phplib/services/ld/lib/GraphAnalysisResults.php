<?php
class GraphAnalysisResults extends AnalysisResults {
	var $inserts = array();
	var $deletes = array();
	var $meta = array();//state changes {variable: [old, new])
	var $hypothetical = false;


	function setMeta($meta){
		$this->meta = $meta;
	}

	function setMetaChange($var, $oval, $nval){
		$this->meta[$var] = array($oval, $nval);
	}

	function includesGraphChanges(){
		return !$this->hypothetical &&
		(count($this->inserts) > 0 || count($this->deletes) > 0);
	}

	function getErrorsSummary() {
		$cnt = 0;
		foreach($this->errors as $gname => $errs){
			$cnt += count($errs);
		}
		$tex = $cnt . " Quality Control Error";
		if($cnt != 1) $tex .= "s";
		return $tex;
	}


	function getErrorsDetails() {
		$txt = "";
		foreach($this->errors as $gname => $errs){
			$txt .= count($errs) . " errors in $gname graph. ";
		}
		return $txt;
	}

	function setHypothetical($ishypo){
		$this->hypothetical = $ishypo;
	}

	function setInserts($q){
		$this->inserts = $q;
	}

	function setDeletes($q){
		$this->deletes = $q;
	}

	function addGraphResult($other, $hypo = false){
		$this->add($other);
		$this->inserts = array_merge($this->inserts, $other->inserts);
		$this->deletes = array_merge($this->deletes, $other->deletes);
		if($hypo){
			$this->hypothetical = true;
		}
	}

	function addOneGraphTestResult($gname, $iquads, $dquads, $errs){
		if(count($errs) > 0){
			if(!$this->errcode){
				$this->errcode = 400;
			}
			$this->decision = 'reject';
			if(isset($this->errors[$gname])){
				$this->errors[$gname] = array_merge($this->errors[$gname], $errs);
			}
			else {
				$this->errors[$gname] = $errs;
			}
		}
		$this->inserts = array_merge($this->inserts, $iquads);
		$this->deletes = array_merge($this->deletes, $dquads);
	}

	function addOneGraphTestFail($gname, $iquads, $dquads, $errcode, $errmsg){
		$this->errcode = $errcode;
		$this->decision = 'reject';
		$err = array("type" => "test fail",
				"action" => "write to graph",
				"errcode" => $errcode,
				"msg_title" => "Failed data quality checks when writing to graph.",
				"msg_body" => $errmsg);
		if(isset($this->errors[$gname])){
			$this->errors[$gname][] = $err;
		}
		else {
			$this->errors[$gname] = $err;
		}
		$this->inserts = array_merge($this->inserts, $iquads);
		$this->deletes = array_merge($this->deletes, $dquads);
	}

	function graph_fail($gname, $errcode){
		$this->decision = "reject";
		$this->errcode = $errcode;
	}

}





