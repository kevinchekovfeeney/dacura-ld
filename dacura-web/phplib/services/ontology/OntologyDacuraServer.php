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
			if($trips = $this->getOntologyDependenciesAsTriples($ont, $rules)){
				$schema_tests = $this->getSchemaTests($ont);
				$instance_tests = $this->getInstanceTests($ont);
				$vo = $this->graphman->validateOntology($ont, $trips['schema_schema'], $trips['schema'], $schema_tests, $instance_tests);
				$nopr->add($vo);
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
	
	
	function getOntologyDependenciesAsTriples(Ontology $ont, $rules){
		$deps = $ont->getSchemaDependencies($this, $rules);
		//opr(array_keys($deps));
		$trips = array("schema" => $ont->typedQuads($ont->cwurl."/schema"), "schema_schema" => array());				
		if($this->getServiceSetting("two_tier_schemas", true)){
			foreach($deps as $sh => $iont){
				$trips['schema'] = array_merge($trips['schema'], $iont->typedQuads($ont->schemaGname()));
				$trips['schema_schema'] = array_merge($trips['schema_schema'], $iont->typedQuads($ont->schemaSchemaGname()));
			}		
			$sdeps = $ont->getSchemaSchemaDependencies($this, $rules, array_keys($deps));
			//opr(array_keys($sdeps));				
			foreach($sdeps as $sh => $sont){
				$trips['schema_schema'] = array_merge($trips['schema_schema'], $sont->typedQuads($ont->schemaSchemaGname()));
			}
		}
		else {
			foreach($deps as $sh => $iont){
				//echo "<P>$sh";
				$xtrips = $iont->typedQuads($ont->schemaGname());
				$trips['schema'] = array_merge($trips['schema'], $xtrips);
				//echo count($trips['schema'])." triples in the schema and ".count($xtrips)." in $sh";
			}		
		}
		return $trips;
	}
	
	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		return $this->objectPublished($uldo->changed, $test_flag);
	}
	
	
}