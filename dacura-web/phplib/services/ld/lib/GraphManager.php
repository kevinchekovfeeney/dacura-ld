<?php
require_once("FakeTripleStore.php");

/**
 * Class representing the graph manager for talking to the DQS curated graph...
 *
 * Simple interface class for all invocations of DQS - always returns a DQSResult object...
 * @author Chekov
 * @license GPL V2
 */
class GraphManager extends DacuraController {

	/**
	 * Invokes the dacura quality service functions (the original validation functions)
	 * @param string $service - the name of the DQS service (schema, schema_validate, validate, instance)
	 * @param string $schema_gname - the id (url) of the schema graph for this invocation...
	 * @param string $gname - the id (url) of the instance data graph for this invocation...
	 * @param array $itrips - array of triples to be inserted into graph 
	 * @param array $dtrips - array of triples to be deleted from graph
	 * @param boolean $test - if true this is a test invocation, no triples will actually be written.. 
	 * @param array|string $tests - array of dqs tests to perform for this invocation or "all" for all tests
	 * @return DQSResult - a result object representing the outcome of the invocation. 
	 */
	function invokeDQS($service, $schema_gname, $gname = false, $itrips = false, $dtrips = false, $test = false, $tests = "all"){
		$dqsr = new DQSResult("DQS test", $test);
		$dqsr->setTests($tests);
		$dqs_config = $this->getSystemSetting("dqs_service");
		if($fakets = $this->getSystemSetting("dqs_service.fake")){
			$fdqs = new FakeTripleStore($fakets);
			return $fdqs->update($itrips, $dtrips, $test);
		}
		$queries = array();
		$itrips = $itrips ? $itrips : array();
		$dtrips = $dtrips ? $dtrips : array();
		$dqsr->inserts = $itrips;
		$dqsr->deletes = $dtrips;
		if($service == "schema" or $service == "instance"){
			$update_ip = json_encode(array(
					"inserts" 	=> 	$itrips,
					"deletes" 	=> 	$dtrips
			));
			$queries['update'] = $update_ip;
			$commit = $test ? "false" : "true";
			$pragma_ip = json_encode(array(
					"tests" 	=>	$tests,
					"commit" 	=> 	$commit,
					"schema" 	=> 	$schema_gname,
					"instance" 	=> 	$gname
			));
			$queries['pragma'] = $pragma_ip;
		}
		else {
			$prag = array(
					"tests" => $tests,
					"schema" => $schema_gname
			);
			if($gname != false){
				$prag['instance'] = $gname;
			}
			$queries['pragma'] = json_encode($prag);
		}
		$qstr = "";
		foreach($queries as $k => $v){
			if(strlen($qstr) > 0) $qstr.= "&";
			$qstr .= $k."=".urlencode($v);
		}
		if($dqs_config['dumplast']){
			$this->dumpDQSRequest($dqs_config['dumplast'], $service, $tests, $schema_gname, $gname, $itrips, $dtrips, $qstr);
		}
		$ch = curl_init();
		if($proxy = ($this->getSystemSetting('dqs_http_proxy', ""))){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}

		curl_setopt($ch, CURLOPT_URL, $dqs_config[$service]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$content = curl_exec($ch);
		if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			$errcode = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) ? 500 : curl_getinfo($ch, CURLINFO_HTTP_CODE);
			return $dqsr->failure($errcode, "DQS call to $service failed", "Service returned ".strlen($content)." bytes ".$content);
		}
		$content = json_decode($content, true);
		if(is_array($content) && count($content) == 0){
			return $dqsr->accept();
		}
		elseif(is_array($content)){
			return $dqsr->parseErrors($content);
		}					
		else {
			return $dqsr->failure(500, "DQS call to $service failed", "Dacura Quality Service returned illegal type (not an array): $content");
		}
	}
	
	/**
	 * Invokes the dacura class service functions (the ones about entities etc.)
	 * 
	 * These have a separate function because they were added later and the above function is already complex enough. 
	 * @param string $graphid - the id (url) of the schema graph to use
	 * @param string [$clsname] - the name of the class that we are interested in retrieving the frame of. If this is ommited, 
	 * we call the entity function to retrieve the list of entity classes. 
	 * @return DQSResult
	 */
	function invokeDCS($graphid, $clsname = false, $entid = false){
		$args = array("schema" => $graphid);
		$dqs_config = $this->getSystemSetting("dqs_service");
		$dqsr = new DQSResult("invoking DQS class analysis");
		if($entid){
			$srvc = $dqs_config['entity_frame'];
			$args['entity'] = $entid;			
		}elseif($clsname){
			$srvc = $dqs_config['class_frame'];
			$args['class'] = $clsname;
		}
		else {
			$srvc = $dqs_config['entity'];
		}
		$qstr = "";
		foreach($args as $k => $v){
			if(strlen($qstr) > 0) $qstr.= "&";
			$qstr .= $k."=".urlencode($v);
		}
		$ch = curl_init();
		if($proxy = ($this->getSystemSetting('dqs_http_proxy', ""))){
			curl_setopt($ch, CURLOPT_PROXY, $proxy);
		}

		curl_setopt($ch, CURLOPT_URL, $srvc);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$content = curl_exec($ch);		
		if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			$errcode = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) ? 500 : curl_getinfo($ch, CURLINFO_HTTP_CODE);
			return $dqsr->failure($errcode, "DCS call to $srvc failed", "Service returned ".strlen($content)." bytes ".$content);
		}
		$dqsr->result = $content;
		return $dqsr;
	}
	
	
	/**
	 * Called to validate a created or updated ontology.  
	 * 
	 * Ontologies are not associated with any particular graph until they are manually added to a graph, so when we validate them,
	 * we do so in the context of a 
	 * @param Ontology $ont the ontology in question..
	 * @param array $sstrips the triples to be added to the schema schema graph
	 * @param array $strips the triples to be added to the schema graph
	 * @param array $stests DQS tests to be run on schema validation
	 * @param array $itests DQS tests to be run on instance validation
	 * @return DQSResult Result of validation
	 */
	function validateOntology(Ontology $ont, $quads, $stests, $itests = false){
		$dqsr = new DQSResult("Validating ontology $ont->id");
		if(count($quads) == 0){
			$dqsr->setWarning("Publish Schema", "Validating empty ontology", $ont->id." graph has an empty published schema");
			return $dqsr;	
		}
		if($this->getServiceSetting("two_tier_schemas", true)){
			$dqsr = $this->invokeDQS("instance", $ont->schemaSchemaGname(), $ont->schemaGname(), $quads, false, true, $itests);
			if($dqsr->is_accept()){
				$dqsr->add($this->invokeDQS("schema", $ont->schemaGname(), false, $quads, false, true, $stests));
			}
			return $dqsr;
		}
		else {
			return $this->invokeDQS("schema", $ont->schemaGname(), false, $quads, false, true, $stests);
		}
	}
	
	/**
	 * Creates the named graphs for a graphs schema and schema schema 
	 * 
	 * Schema schema graph regulates updates of schema graph as instance data
	 * Schema graph regulates updates of instance graph
	 * @param Graph $graph the graph object
	 * @param array $quads the triples to be added to the schema graph(s)
	 * @return DQSResult Result of validation
	 */
	function publishGraphSchema(Graph $graph, $quads, $test_flag){
		$dqsr = new DQSResult("Create Graph $graph->id schema", $test_flag);
		if(count($quads) == 0){
			$dqsr->setWarning("Publish Schema", "Published empty schema", $graph->id ." graph has an empty published schema");
			return $dqsr;
		}		
		if($graph->hasTwoTierSchema()){
			$sr = $this->invokeDQS("instance", $graph->schemaSchemaGname(), $graph->schemaGname(), $quads, false, $test_flag, $graph->getCreateInstanceTests());
			$dqsr->add($sr);
			if($sr->is_accept() || $this->getServiceSetting("continue_multitests_on_fail", false)){
				if($sr->is_accept() && !$test_flag){
					$sr2 = $this->invokeDQS("validate", $graph->schemaGname(), $graph->instanceGname(), false, false, false, $graph->getCreateInstanceTests());
					if(!$sr2->is_accept()){
						//rollback schema change
						$sr2->add($this->invokeDQS("instance", $graph->schemaSchemaGname(), $graph->schemaGname(), false, $quads, false, $graph->getDeleteInstanceTests()));
					}						
				}
				else {
					$ntf = $test_flag || !$sr->is_accept();
					$sr2 = $this->invokeDQS("instance", $graph->schemaGname(), $graph->instanceGname(), $quads, false, $ntf, $graph->getCreateInstanceTests());
				}
				$dqsr->add($sr2);
			}
		}
		else {
			$dqsr = $this->invokeDQS("schema", $graph->schemaGname(), $graph->instanceGname(), $quads, false, $test_flag, $graph->getCreateSchemaTests());
		}		
		return $dqsr;	
	}
	
	/**
	 * Called to remove a graph's schema from the triple-store
	 * @param Graph $graph graph object for which the schema is being unpublished
	 * @param array $quads quads to be removed 
	 * @param boolean $test_flag - is this a test
	 * @return DQSResult
	 */
	function unpublishGraphSchema($graph, $quads, $test_flag){
		if($graph->hasTwoTierSchema() && (count($quads) > 0)){
			return $this->invokeDQS("instance", $graph->schemaSchemaGname(), $graph->schemaGname(), false, $quads, $test_flag, $graph->getDeleteSchemaTests());
		}
		elseif(count($quads) > 0){
			return $this->invokeDQS("schema", $graph->schemaGname(), false, false, $quads, $test_flag, $graph->getDeleteSchemaTests());			
		}	
		else {
			$dqsr = new DQSResult("unpublish graph schema", $test_flag);
			$dqsr->setWarning("Unpublish Schema", "Unpublished empty schema", $graph->id ." graph had an empty published schema");
			return $dqsr;
		}
	}

	/**
	 * Called to update the schema of a live graph 
	 * @param Graph $graph graph object
	 * @param array $iquads insert quads - to be added to graph
	 * @param array $dquads delete quads - to be deleted from graph
	 * @param boolean $test_flag - is this a test invocation
	 * @return DQSResult
	 */
	function updateGraphSchema(Graph $graph, $iquads, $dquads, $test_flag){
		$dqsr = new DQSResult("update graph", $test_flag);
		if($graph->hasTwoTierSchema()){
			//first we have to update the schema schema ontology
			//updating schema schema graph
			$sr = $this->invokeDQS("instance", $graph->schemaSchemaGname(), $graph->schemaGname(), $iquads, $dquads, $test_flag, $graph->getUpdateInstanceTests());
			$dqsr->add($sr);
			if($sr->is_accept() || $this->getServiceSetting("continue_multitests_on_fail", false)){
				if($sr->is_accept() && !$test_flag){
					$sr2 = $this->invokeDQS("validate", $graph->schemaGname(), $graph->instanceGname(), false, false, false, $graph->getCreateInstanceTests());
					if(!$sr2->is_accept()){
						//rollback schema change
						$sr2->add($this->invokeDQS("instance", $graph->schemaSchemaGname(), $graph->schemaGname(), false, $iquads, false, $graph->getDeleteInstanceTests()));
					}
				}
				else {
					$ntf = $test_flag || !$sr->is_accept();
					$sr2 = $this->invokeDQS("schema", $graph->schemaGname(), $graph->instanceGname(), $iquads, false, $ntf, $graph->getCreateInstanceTests());
				}
				$dqsr->add($sr2);
			}
		}
		elseif(count($iquads) > 0 || count($dquads) > 0){
			//make changes to schema graph
			return $this->invokeDQS("schema", $graph->schemaGname(), $graph->instanceGname(), $iquads, $dquads, $test_flag, $graph->getUpdateInstanceTests());
		}
		else {
			$dqsr = new DQSResult("unpublish graph schema", $test_flag);
			$dqsr->setWarning("Unpublish Schema", "Unpublished empty schema", $graph->id ." graph had an empty published schema");
		}
		return $dqsr;
	}
	
	/**
	 * Called to create an instance in the graph's instance named graph
	 * @param Graph $graph graph object 
	 * @param array $quads quads to be added to graph
	 * @param boolean $test_flag
	 * @return DQSResult
	 */
	function createInstance(Graph $graph, $quads, $test_flag){
		return $this->invokeDQS("instance", $graph->schemaGname(), $graph->instanceGname(), $quads, false, $test_flag, $graph->getCreateInstanceTests());
	}

	/**
	 * Called to delete an instance in the graph's instance named graph
	 * @param Graph $graph graph object 
	 * @param array $quads quads to be removed from graph
	 * @param boolean $test_flag
	 * @return DQSResult
	 */
	function deleteInstance(Graph $graph, $quads, $test_flag){
		return $this->invokeDQS("instance", $graph->schemaGname(), $graph->instanceGname(), false, $quads, $test_flag, $graph->getDeleteInstanceTests());
	}
	
	/**
	 * Called to update an instance's data in the graph's instance named graph
	 * @param Graph $graph graph object
	 * @param array $iquads quads to be added to graph
	 * @param array $dquads quads to be removed from graph
	 * @param boolean $test_flag
	 * @return DQSResult
	 */
	function updateInstance(Graph $graph, $iquads, $dquads, $test_flag){
		return $this->invokeDQS("instance", $graph->schemaGname(), $graph->instanceGname(), $iquads, $dquads, $test_flag, $graph->getUpdateInstanceTests());
	}

	/**
	 * Called to reverse an update of an object's instance data 
	 * @param Graph $graph graph object
	 * @param array $iquads quads that were added
	 * @param array $dquads quads that were removed
	 * @param boolean $test_flag
	 * @return DQSResult
	 */
	function undoInstanceUpdate(Graph $graph, $iquads, $dquads){
		return $this->invokeDQS("instance", $graph->schemaGname(), $graph->instanceGname(), $dquads, $iquads, $test_flag, $graph->getUndoInstanceUpdateTests());
	}

	/**
	 * Called to dump a dqs request to file for debugging
	 * @param string $fname filepath to file to be used for debugging
	 * @param string $service the dqs service being called
	 * @param array|string $tests the dqs tests being requested
	 * @param string $schema_gname the name of the schema graph
	 * @param string $gname the name of the instance graph
	 * @param array $iquads the quads that are to be inserted
	 * @param array $iquads the quads that are to be deleted
	 * @param string $qstr the query string
	 */
	function dumpDQSRequest($fname, $service, $tests, $schema_gname, $gname, $itrips, $dtrips, $qstr){
		$dumpstr = "Service: $service\n";
		$dumpstr .= "Tests: ";
		if(is_array($tests)){
			$dumpstr .= "[".implode(", ", $tests)."]\n";
		}
		else {
			$dumpstr .= $tests."\n";
		}
		$dumpstr .= "Schema: ".$schema_gname." Instance: ".$gname."\n";
		$dumpstr .= "Triples Added:\n";
		foreach($itrips as $itrip){
			$dumpstr .= json_encode($itrip)."\n";
		}
		$dumpstr .= "Triples Deleted:\n";
		foreach($dtrips as $itrip){
			$dumpstr .= json_encode($itrip)."\n";
		}
		$dumpstr .= "Query: $qstr";
		file_put_contents($fname, $dumpstr);
	}
}
