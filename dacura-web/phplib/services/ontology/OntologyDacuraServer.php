<?php 
require_once("phplib/services/ld/LdDacuraServer.php");
class OntologyDacuraServer extends LdDacuraServer {
	
	/**
	 * Called when ontology is moved into 'accept' state -> only allowed when it passes dqs tests
	 * @param array $ont the new object to be published
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectPublished(Ontology $ont, $test_flag = false){
		$nopr = new DQSResult("Validating Ontology", $test_flag);
		if($ont->is_empty()){
			return $nopr->failure(400, "Ontology is empty", "Content must be added to the ontology before it can be published");
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
	
	function getOntologyDependenciesAsTriples(Ontology $ont){
		$sdeps = $ont->getSchemaDependencies($this);
		$ideps = $ont->getSchemaSchemaDependencies($this);
		$trips = array("instance" => $ont->typedQuads(), "schema" => array());
		return $trips;
		foreach($ideps as $sh => $struct){
			$iont = $this->loadLDO($sh, "ontology", $struct['collection'], false, $struct['version']);
			if(!$iont){
				return $this->failure_result("Failed to load ontology $sh", 400);
			}
			$trips['schema'] = array_merge($trips['schema'], $iont->typedQuads());
			if(isset($ideps[$sh])){
				$trips['instance'] = array_merge($trips['instance'], $iont->typedQuads());
			}
		}
		return $trips;
	}
	
	function getOntologyCollection($id){
		if($this->dbman->hasLDO($id, "ontology", "all")){
			return "all";
		}
		return $this->cid();
	}
	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		return $this->objectPublished($uldo->changed, $test_flag);
	}
	
	
}