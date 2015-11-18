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
			$this->graphbase = $this->ucontext->get_service_url("schema");		
		}
	}
	
	function getNGSkeleton(){
		if($this->schema){
			return $this->schema->getNGSkeleton();
		}
		return $this->failure_result("Attempted to load a skeleton with no schema loaded", 400);
	}
	
	function createNewEntityObject($id, $type){
		$obj = parent::createNewEntityObject($id, $type);
		if(!$this->schema){
			return $this->failure_result("Cannot create new candidates outside of a schema", 400);
		}
		$obj->cwurl = $this->schema->instance_prefix."/".$obj->id;
		return $obj;
	}
	
	function loadEntity($entity_id, $type, $cid, $did, $fragment_id = false, $version = false, $options = array()){
		$ent = parent::loadEntity($entity_id, $type, $cid, $did, $fragment_id, $version, $options);
		if($ent){
			$ent->cwurl = $this->schema->instance_prefix."/".$ent->id;
			$this->nsres->prefixes[$ent->id] = $ent->cwurl;
		}
		return $ent;
	}
	
	function publishEntityToGraph($nent, $is_test=false){
		$ar = new GraphAnalysisResults("Publishing to Graph");
		foreach($nent->ldprops as $k => $props){
			$quads = $nent->getPropertyAsQuads($k, $this->getInstanceGraph($k));
			if($quads){
				$gobj = $this->loadEntity($k, "graph", $this->cid(), $this->did());
				$tests = isset($gobj->meta['instance_dqs']) ? $gobj->meta['instance_dqs'] : array();
				$errs = $this->graphman->create($quads, $this->getInstanceGraph($k), $this->getGraphSchemaGraph($k), $is_test, $tests);
			}
			if($errs === false){
				$ar->addOneGraphTestFail($k, $quads, array(), $this->graphman->errcode, $this->graphman->errmsg);
			}
			else {
				$ar->addOneGraphTestResult($k, $quads, array(), $errs);
			}
		}
		return $ar;
	}
	
	function updateEntityInGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Updating Report in Graph", $is_test);
		foreach($ent->changed->ldprops as $k => $props){
			$gobj = $this->loadEntity($k, "graph", $this->cid(), $this->did());
			if($gobj){
				$tests = $gobj->meta['instance_dqs'];
				$iquads = $ent->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
				$dquads = $ent->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
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
	
	function deleteEntityFromGraph($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Removing Entity from Graph", $is_test);
		foreach($ent->ldprops as $k => $props){
			$quads = $ent->getPropertyAsQuads($k, $this->getInstanceGraph($k));
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
	
	function undoEntityUpdate($ent, $is_test = false){
		$ar = new GraphAnalysisResults("Undoing Report Update in Graph");
		foreach($ent->original->ldprops as $k => $props){
			$dquads = $ent->delta->getNamedGraphInsertQuads($k, $this->getInstanceGraph($k));
			$iquads = $ent->delta->getNamedGraphDeleteQuads($k, $this->getInstanceGraph($k));
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
}

