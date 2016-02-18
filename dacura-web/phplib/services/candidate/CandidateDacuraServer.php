<?php
include_once("phplib/services/ld/LdService.php");
include_once("phplib/services/ld/LdDacuraServer.php");
include_once("Candidate.php");
include_once("CandidateUpdateRequest.php");
include_once("CandidateCreateRequest.php");


class CandidateDacuraServer extends LdDacuraServer {
	

	function __construct($service){
		parent::__construct($service);
		$this->cwurlbase = $service->my_url();
		if($this->cid() != "all"){
			$this->schema = $this->loadSchemaFromContext();
			$this->graphbase = $this->service->get_service_url("schema");		
		}
	}
	
	function getNGSkeleton(){
		if($this->schema){
			return $this->schema->getNGSkeleton();
		}
		return $this->failure_result("Attempted to load a skeleton with no schema loaded", 400);
	}
	
	function createNewLDObject($id, $type){
		$obj = parent::createNewLDObject($id, $type);
		if(!$this->schema){
			return $this->failure_result("Cannot create new candidates outside of a schema", 400);
		}
		$obj->cwurl = $this->schema->instance_prefix."/".$obj->id;
		$obj->schema = $this->schema;
		return $obj;
	}
	
	function loadLDO($ldo_id, $type, $cid, $fragment_id = false, $version = false, $options = array()){
		$ldo = parent::loadLDO($ldo_id, $type, $cid, $fragment_id, $version, $options);
		if($ldo){
			$ldo->cwurl = $this->schema->instance_prefix."/".$ldo->id;
			$this->nsres->prefixes[$ldo->id] = $ldo->cwurl;
		}
		return $ldo;
	}
	
	function publishLDOToGraph($nldo, $is_test=false){
		$ar = new GraphAnalysisResults("Publishing to Graph");
		foreach($nldo->ldprops as $k => $props){
			$quads = $nldo->getPropertyAsQuads($k, $this->getInstanceGraph($k));
			if($quads){
				$gobj = $this->loadLDO($k, "graph", $this->cid());
				$tests = isset($gobj->meta['instance_dqs']) ? $gobj->meta['instance_dqs'] : array();
				$errs = $this->graphman->create($quads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test, $tests);
				if($errs === false){
					$ar->addOneGraphTestFail($k, $quads, array(), $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $quads, array(), $errs);
				}
			}			
		}
		return $ar;
	}
	
	function updateLDOInGraph($ldo, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($ldo->changed->ldprops as $k => $props){
			$gobj = $this->loadLDO($k, "graph", $this->cid());
			if($gobj){
				$tests = $gobj->meta['instance_dqs'];
				$iquads = $ldo->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
				$dquads = $ldo->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
				if(count($iquads) > 0 or count($dquads) > 0){
					$errs = $this->graphman->update($iquads, $dquads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test, $tests);
					if($errs === false){
						$ar->addOneGraphTestFail($k, $iquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
					}
					else {
						$ar->addOneGraphTestResult($k, $iquads, $dquads, $errs);
					}
				}
			}
			else {
				$ar->addOneGraphTestFail($k, array(), array(), $this->errcode, "Failed to load graph $k".$this->errmsg);
			}
		}
		return $ar;
	}
	
	function deleteLDOFromGraph($ldo, $is_test = false){
		$ar = new GraphAnalysisResults("Removing LDO from Graph", $is_test);
		foreach($ldo->ldprops as $k => $props){
			$quads = $ldo->getPropertyAsQuads($k, $this->getInstanceGraph($k));
			if($quads){
				$errs = $this->graphman->delete($quads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, array(), $quads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, array(), $quads, $errs);
				}
			}
		}
		return $ar;
	}
	
	function undoLDOUpdate($ldo, $is_test = false){
		$ar = new GraphAnalysisResults("Undoing Report Update in Graph");
		foreach($ldo->original->ldprops as $k => $props){
			$dquads = $ldo->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
			$iquads = $ldo->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
			if(count($iquads) > 0 or count($dquads) > 0){
				$errs = $this->graphman->update($iquads, $dquads, $this->getInstanceGraph($k),$this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, $iquads, $dquads, $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $iquads, $dquads, $errs);
				}
			}
		}
		return $ar;
	}
	
	function updatePublishedUpdate($cand, $ocand, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($cand->original->ldprops as $k){
			$quads = $cand->deltaAsNGQuads($ocand, $this->getInstanceGraph($k));
			if(count($quads['add']) > 0 or count($quads['del']) > 0){
				$errs = $this->graphman->update($quads['add'], $quads['del'], $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test);
				if($errs === false){
					$ar->addOneGraphTestFail($k, $quads['add'], $quads['del'], $this->graphman->errcode, $this->graphman->errmsg);
				}
				else {
					$ar->addOneGraphTestResult($k, $quads['add'], $quads['del'], $errs);
				}
			}
		}
		return $ar;
	}
	
	function getCandidateLDOClasses(){
		return $this->graphman->getGraphLDOClasses("seshat");
	}
	
	function getClassFrame($cls){
		$graphs = $this->getMainGraphForLDOId($ldoid);
		$frame = $this->graphman->getClassFrame($cls, $graphs[1]);		
	}
	
	function getLDOClassFrame($ldoid){
		$cls = $this->getClassFromLDO($ldoid);
		$frame = $this->getClassFrame($cls);
		$filled_frame = $this->fillFrame($ldoid, $frame);		
	}
	
	function getMainGraphForLDOId($ldoid){
		return array("main_instance", "main_schema");
	}
	
}

