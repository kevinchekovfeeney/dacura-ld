<?php 
/**
 * Graph Server
 */
require_once("phplib/services/ontology/OntologyDacuraServer.php");
class GraphDacuraServer extends LdDacuraServer {
	
	function getNewLDOContentRules($nldo){
		$x = parent::getNewLDOContentRules($nldo);
		$x["replace_blank_ids"] = false;
		$x['load_dependencies'] = true;
		return $x;
	}
	
	function getUpdateLDOContentRules($nldo){
		$x = parent::getUpdateLDOContentRules($nldo);
		$x["replace_blank_ids"] = false;
		$x['load_dependencies'] = false;
		return $x;
	}
	
	function getReplaceLDOContentRules($nldo){
		return $this->getNewLDOContentRules($nldo);
	}
	
	
	
	/**
	 * Called when graph is moved into 'accept' state -> only allowed when it passes dqs tests
	 * @param array $graph the new graph to be published
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectPublished(Graph $graph, $test_flag = false){
		$nopr = new DQSResult("Validating Graph $graph->id", $test_flag);
		if($graph->is_empty()){
			return $nopr->failure(400, "Graph schema is empty", "A schema must be added to the Graph before data can be published to it.");
		}
		if(!$graph->validateDependencies($this)){
			return $nopr->reject("Failed dependency validation", "The dependencies defined in the graph's configuration cannot be loaded for validation. ".$graph->errmsg." [$graph->errcode]");
		}
		$rules=array();
		if($quads = $this->getGraphSchemaAsQuads($graph, $rules)){
			if($graph->hasTwoTierSchema()){
				$squads = $this->getGraphSchemaSchemaAsQuads($graph, $rules);
				if($squads === false){
					return $nopr->failure($this->errcode, "Failed to serialise schema schema graph ".$graph->id, $this->errmsg);						
				}
				$quads = array_merge($quads, $squads);
			} 
			$gr = $this->graphman->publishGraphSchema($graph, $quads, $test_flag);
			$nopr->add($gr);
		}
		elseif($quads === false){
			return $nopr->failure($this->errcode, "Failed to serialise graph ".$graph->id, $this->errmsg);
		}
		else {
			$nopr->msg("Empty schema graph", "no tests run as no triples were produced for graph");
		}
		return $nopr;
	}
	

	function objectDeleted(Graph $graph, $test_flag = false){
		$nopr = new DQSResult("Deleting Graph $graph->id", $test_flag);
		if($graph->is_empty()){
			$nopr->body("Graph schema is empty");
			return $nopr->accept();
		}
		$rules=array();
		if($quads = $this->getGraphSchemaAsQuads($graph, $rules)){
			if($graph->hasTwoTierSchema()){
				$squads = $this->getGraphSchemaSchemaAsQuads($graph, $rules);
				if($squads === false){
					return $nopr->failure($this->errcode, "Failed to serialise schema schema graph ".$graph->id, $this->errmsg);						
				}
				$quads = array_merge($quads, $squads);
			}
			return $this->graphman->unpublishGraphSchema($graph, $quads, $test_flag);
		}
		elseif($quads === false) {
			return $nopr->failure(400, "Failed graph serialisation", "The system could not produce the quads needed to delete the graph configuration");
		}
		else {
			return $nopr->msg("Empty schema graph", "no tests run as no triples were produced for graph");				
		}
	}
	
	/**
	 * Called when graph is updated while in 'accept' state - causes the updates to be written to the graph (possibly as a test)
	 * @see OntologyDacuraServer::objectUpdated()
	 */
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		// if we are updating an unpublished object, it is the same as publishing a new object from the graph pov
		if(!$uldo->originalPublished()){
			return $this->objectPublished($uldo->changed, $test_flag);
		}
		elseif(!$uldo->changedPublished()){
			return $this->objectDeleted($uldo->original, $test_flag);				
		}
		//live graph schema update
		$nopr = new DQSResult("Validating Graph ".$uldo->original->id." update", $test_flag);
		$del_onts = array();
		$add_onts = array();
		$oimports = $uldo->original->getSchemaImports($this->durl());
		//opr($oimports);
		$nimports = $uldo->changed->getSchemaImports($this->durl());
		foreach($oimports as $id => $rec){
			if(isset($nimports[$id]) && $nimports[$id]['version'] != $rec['version']){
				$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $nimports[$id]['version']);
				$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version']);
			}
			elseif(!isset($nimports[$id])){
				$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version']);
			}
		}
		foreach($nimports as $id => $rec){
			if(!isset($oimports[$id])){
				$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version']);
			}				
		}
		$iquads = array();
		$dquads = array();
		foreach($add_onts as $ont){
			$iquads = array_merge($iquads, $ont->typedQuads($uldo->changed->schemaGname()));
			if(count($iquads) == 0){
				$x = $ont->typedQuads("x");
				opr($ont);
				echo $uldo->changed->schemaGname();
			}				
			echo "<P>$ont->id (".count($iquads).")";
				
		}
		foreach($del_onts as $ont){
			$dquads = array_merge($dquads, $ont->typedQuads($uldo->original->schemaGname()));
		}
		if($uldo->changed->hasTwoTierSchema()){
			$del_onts = array();
			$add_onts = array();
			$oimports = $uldo->original->getSchemaSchemaImports();
			$nimports = $uldo->changed->getSchemaSchemaImports();
			foreach($oimports as $id => $rec){
				if(isset($nimports[$id]) && $nimports[$id]['version'] != $rec['version']){
					$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $nimports[$id]['version']);
					$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version']);
				}
				elseif(!isset($nimports[$id])){
					$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version']);
				}
			}
			foreach($nimports as $id => $rec){
				if(!isset($oimports[$id])){
					$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version']);
				}
			}
			foreach($add_onts as $ont){
				$iquads = array_merge($iquads, $ont->typedQuads($uldo->changed->schemaGname()));
			}
			foreach($del_onts as $ont){
				$dquads = array_merge($dquads, $ont->typedQuads($uldo->original->schemaGname()));
			}
		}
		if(count($iquads) > 0 || count($dquads) > 0){
			$gr = $this->graphman->updateGraphSchema($uldo->changed, $iquads, $dquads, $test_flag);
			$nopr->add($gr);
		}
		else {
			$nopr->msg("Empty schema graph", "no tests run as no triples were produced for graph update");				
		}
		return $nopr;
	}
	
	
	function getGraphSchemaAsQuads(Graph $graph, $rules){
		$quads = array();
		$imports = $graph->getSchemaImports($this->durl());
		foreach($imports as $id => $rec){
			if(!$ont = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version'])){
				return false;
			}	
			$quads = array_merge($quads, $ont->typedQuads($graph->schemaGname()));				
		}
		return $quads;
	}

	function getGraphSchemaSchemaAsQuads(Graph $graph, $rules){
		$quads = array();
		$imports = $graph->getSchemaSchemaImports($this->durl());
		foreach($imports as $id => $rec){
			if(!$ont = $this->loadLDO($id, "ontology", $rec['collection'], $rec['version'])){
				return false;
			}	
			$quads = array_merge($quads, $ont->typedQuads($graph->schemaGname()));				
		}
		return $quads;
	}
	
	
	function loadOntologyFromURL($url){
		if(!($parsed_url = Ontology::parseOntologyURL($url))){
			return $this->failure_result(htmlspecialchars($url)." is not a valid dacura ontology url", 404);
		}
		return ($ont = $this->loadLDO($parsed_url['id'], "ontology", $parsed_url['collection'], $parsed_url['fragment'], $parsed_url['version']));
	}
	
}
