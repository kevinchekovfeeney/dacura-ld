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
		$nopr->add($ont->validateDependencies($this, $test_flag));
		if($nopr->is_accept()){
			if($quads = $this->getOntologyAsQuads($ont)){
				$nopr->add($this->graphman->validateOntology($ont, $quads, $this->getSchemaTests($ont), $this->getInstanceTests($ont)));
			}
			else {
				$nopr->failure($this->errcode, "Failed to generate ontology dependency triples", $this->errmsg);
			}
		}
		return $nopr;
	}
	
	function getSchemaTests(Ontology $ont){
		if($tests = $ont->getCreateSchemaTests()) return $tests;
		$p = array_keys(RVO::getSchemaTests(false)); 
		$x = $this->getServiceSetting("create_dqs_schema_tests", $p);
		return $x;
	}
	
	function getInstanceTests(Ontology $ont){
		if($tests = $ont->getCreateInstanceTests()) return $tests;
		$p = array_keys(RVO::getInstanceTests(false)); 
		return $this->getServiceSetting("create_dqs_instance_tests", $p);		
	}
	
	
	function getOntologyAsQuads(Ontology $ont, $include_deps = true){
		$quads = $ont->typedQuads($ont->schemaGname());
		if($include_deps){
			$deps = $ont->getDependentOntologies($this, "schema");
			foreach($deps as $id => $dont){
				$quads = array_merge($quads, $dont->typedQuads($ont->schemaGname()));				
			}		
		}
		if($this->getServiceSetting("two_tier_schemas", true)){
			$quads = array_merge($quads, $ont->typedQuads($ont->schemaGnameGname()));
			if($include_deps){
				$deps = $ont->getDependentOntologies($this, "schema_schema");
				foreach($deps as $id => $dont){
					$quads = array_merge($quads, $dont->typedQuads($ont->schemaSchemaGname()));				
				}		
			}
		}
		return $quads;		
	}
	
	function updatePublishedUpdate(LDOUpdate $uldoa, LDOUpdate $uldob, $is_test = false){
		return $this->objectPublished($uldob->changed, $is_test);
	}
	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		return $this->objectPublished($uldo->changed, $test_flag);
	}
	
	
}