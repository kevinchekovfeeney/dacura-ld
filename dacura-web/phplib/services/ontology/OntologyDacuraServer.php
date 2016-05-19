<?php 
require_once("phplib/services/ld/LdDacuraServer.php");
/**
 * This class extends the basic processing pipeline of the LD server to handle ontology publishing
 * 
 * Provides concrete implementations of the objectPublished, objectDeleted and objectUpdated methods and some helper functions
 * 
 * @author Chekov
 * @license GPL V2
 */
class OntologyDacuraServer extends LdDacuraServer {
	
	/**
	 * Called when ontology is moved into 'accept' state -> only allowed when it passes dqs tests
	 * @param array $ont the new object to be published
	 * @param boolean $test_flag if true, this is just a test, no graph updates will take place
	 * @return GraphResult
	 */
	function objectPublished(Ontology $ont, $test_flag = false){
		$nopr = new DQSResult("Validating Ontology", $test_flag);
		if($ont->isEmpty()){
			return $nopr->failure(400, "Ontology is empty", "Content must be added to the ontology before it can be published");
		}
		$nopr->add($ont->validateDependencies($this, $test_flag), true, true);
		if($nopr->is_accept() || $this->getServiceSetting("test_unpublished", true)){
			$imports = array();
			if($quads = $this->getOntologyAsQuads($ont, true, $imports)){
				$dqs = $this->graphman->validateOntology($ont, $quads, $this->getSchemaTests($ont), $this->getInstanceTests($ont));
				$dqs->setImports($imports);
				$nopr->add($dqs);
			}
			else {
				$nopr->failure($this->errcode, "Failed to generate ontology dependency triples", $this->errmsg);
			}
		}
		return $nopr;
	}
	
	/**
	 * Retrieves the set of tests that are to be run against the ontology when used with schema tests
	 * @param Ontology $ont the ontology in question
	 * @return array|string - either 'all' or an array of tests to use
	 */
	function getSchemaTests(Ontology $ont){
		if($tests = $ont->getCreateSchemaTests()) return $tests;
		$p = array_keys(RVO::getSchemaTests(false)); 
		$x = $this->getServiceSetting("create_dqs_schema_tests", $p);
		return $x;
	}
	
	/**
	 * Retrieves the set of tests that are to be run against the ontology when used with instance tests
	 * @param Ontology $ont the ontology in question
	 * @return array|string - either 'all' or an array of tests to use
	 */
	function getInstanceTests(Ontology $ont){
		if($tests = $ont->getCreateInstanceTests()) return $tests;
		$p = array_keys(RVO::getInstanceTests(false)); 
		return $this->getServiceSetting("create_dqs_instance_tests", $p);		
	}
	
	/**
	 * Generates the quads to represent the passed ontology
	 * @param Ontology $ont the ontology in question
	 * @param boolean $include_deps if true, ontology dependencies will be included
	 * @return Ambigous <multitype:, unknown, multitype:multitype:string Ambigous <string, mixed>  >
	 */
	function getOntologyAsQuads(Ontology $ont, $include_deps = true, &$imports){
		$quads = $ont->typedQuads($ont->schemaGname());
		if($include_deps){
			$deps = $ont->getDependentOntologies($this, "schema");
			foreach($deps as $id => $dont){
				$imports[$id] = array("id" => $id, "collection" => $dont->cid(), "version" => $dont->version);
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
	

	/**
	 * As Ontologies are never permanently published to their own graph, 
	 * updates and publications are the same..
	 * @see LdDacuraServer::objectUpdated()
	 */	
	function objectUpdated(LDOUpdate $uldo, $test_flag = false){
		return $this->objectPublished($uldo->changed, $test_flag);
	}
}