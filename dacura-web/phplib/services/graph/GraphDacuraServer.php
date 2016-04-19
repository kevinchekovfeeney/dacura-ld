<?php 
/**
 * Graph Server
 */
require_once("phplib/services/ontology/OntologyDacuraServer.php");
class GraphDacuraServer extends LdDacuraServer {
	
	
	
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
		if($quads = $this->getGraphSchemaAsQuads($graph)){
			if($graph->hasTwoTierSchema()){
				$squads = $this->getGraphSchemaSchemaAsQuads($graph);
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
		//if(!$graph->validateDependencies($this)){
		//	return $nopr->reject("Failed dependency validation", "The dependencies defined in the graph's configuration cannot be loaded for validation. ".$graph->errmsg." [$graph->errcode]");
		//}
		return $nopr;
	}
	

	function objectDeleted(Graph $graph, $test_flag = false){
		$nopr = new DQSResult("Deleting Graph $graph->id", $test_flag);
		if($graph->is_empty()){
			return $nopr->msg("Graph schema is empty");
		}
		if($quads = $this->getGraphSchemaAsQuads($graph)){
			if($graph->hasTwoTierSchema()){
				$squads = $this->getGraphSchemaSchemaAsQuads($graph);
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
		//live graph schema update
		$nopr = new DQSResult("Validating Graph ".$uldo->original->id." update", $test_flag);
		$del_onts = array();
		$add_onts = array();
		$oimports = $uldo->original->getSchemaImports($this->durl());
		//opr($oimports);
		$nimports = $uldo->changed->getSchemaImports($this->durl());
		//opr($uldo->changed->ldprops);
		foreach($oimports as $id => $rec){
			if(isset($nimports[$id]) && $nimports[$id]['version'] != $rec['version']){
				$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $nimports[$id]['version']);
				$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
			}
			elseif(!isset($nimports[$id])){
				$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
			}
		}
		foreach($nimports as $id => $rec){
			if(!isset($oimports[$id])){
				$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
			}				
		}
		$iquads = array();
		$dquads = array();
		//opr(array_keys($add_onts));
		foreach($add_onts as $ont){
			$iquads = array_merge($iquads, $ont->typedQuads($uldo->changed->schemaGname()));
			if(count($iquads) == 0){
				//opr($ont);
				echo "Failed for ".$ont->id;
			}				
			//echo "<P>$ont->id (".count($iquads).")";
				
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
					$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $nimports[$id]['version']);
					$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
				}
				elseif(!isset($nimports[$id])){
					$del_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
				}
			}
			foreach($nimports as $id => $rec){
				if(!isset($oimports[$id])){
					$add_onts[$id] = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version']);
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
			//opr($gr);
			$nopr->add($gr);
		}
		else {
			$nopr->msg("Empty schema graph", "no tests run as no triples were produced for graph update");				
		}
		return $nopr;
	}
	
		
	function getGraphSchemaAsQuads(Graph &$graph){
		$quads = array();
		//echo "<P>doing it ".$this->durl();
		//opr($graph->ldprops);
		$imports = $graph->getSchemaImports($this->durl());
		//echo "<P>done it ".$this->durl();
		//opr($imports);
		
		//return $quads;
		foreach($imports as $id => $rec){
			if(!$ont = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version'])){
				return false;
			}	
			$quads = array_merge($quads, $ont->typedQuads($graph->schemaGname()));				
		}
		return $quads;
	}

	function getGraphSchemaSchemaAsQuads(Graph $graph){
		$quads = array();
		$imports = $graph->getSchemaSchemaImports($this->durl());
		foreach($imports as $id => $rec){
			if(!$ont = $this->loadLDO($id, "ontology", $rec['collection'], false, $rec['version'])){
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
