<?php 
/**
 * Graph Server
 */
require_once("phplib/services/ld/LdDacuraServer.php");
class GraphDacuraServer extends LdDacuraServer {
	
	/**
	 * Called when graph is moved into 'accept' state -> only allowed when it passes dqs tests
	 * @param array $graph the new graph to be published
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectPublished(Graph $graph, $test_flag = false){
		$nopr = new DQSResult("Validating Graph", $test_flag);
		if($graph->is_empty()){
			return $nopr->failure(400, "Grahp is empty", "Content must be added to the Graph before it can be published");
		}
		$rules = $this->getServiceSetting("validation_rules", array());
		$nopr->add($ont->validateDependencies($rules, $test_flag));
		if($nopr->is_accept()){
			if($trips = $this->getOntologyDependenciesAsTriples($ont)){
				$nopr->add($this->graphman->validateOntology($ont, $trips['schema'], $trips['instance'], $this->getServiceSetting("create_dqs_schema_tests", array()), $this->getServiceSetting("create_dqs_instance_tests", array())));
			}
			else {
				$nopr->failure($this->errcode, "Failed to generate ontology dependency triples", $this->errmsg);
			}
		}
		return $nopr;
	}
	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		//return $this->objectPublished($uldo->changed, $test_flag);
	}
	
	
}