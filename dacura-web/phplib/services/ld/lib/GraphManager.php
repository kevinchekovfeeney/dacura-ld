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
		$dqs_config = $this->getSystemSetting("dqs_service");
		if($fakets = $this->getSystemSetting("dqs_service.fake")){
			$fdqs = new FakeTripleStore($fakets);
			return $fdqs->update($itrips, $dtrips, $test);
		}
		$queries = array();
		$itrips = $itrips ? $itrips : array();
		$dtrips = $dtrips ? $dtrips : array();
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
			$dqsr->errors = $content;
			return $dqsr->failure($errcode, "DQS call to $service failed", "Service returned ".count($content)." errors");
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
		/*
		if(is_array($content) && count($content) == 0){
			return $dqsr->accept();
		}
		elseif(is_array($content)) {
			$dqsr->errors = $content;
			return $dqsr->failure($errcode, "DCS call to $srvc failed", "Service returned ".strlen($content)." bytes ".$content);
		}
		else {
			return $dqsr->failure(500, "DCS call to $srvc failed", "Dacura Quality Service returned illegal type (not an array): $content");
		}*/
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
	function validateOntology(Ontology $ont, $sstrips, $strips, $stests, $itests = false){
		//first we have to create the schema schema ontology
		$schema_gname = $ont->id;
		if($this->getServiceSetting("two_tier_schemas", true) && count($sstrips) > 0){
			$dqsr = new DQSResult("validate ontology");
			$schema_schema_gname = $ont->id."_schema";
			$dqsr->add($this->invokeDQS("schema", $schema_schema_gname, false, $sstrips, false, false, $stests));
			if($dqsr->is_accept()){
				$dqsr->add($this->invokeDQS("schema", $schema_gname, false, $strips, false, true, $stests));
				if($dqsr->is_accept() && $itests){
					$dqsr->add($this->invokeDQS("instance", $schema_schema_gname, $schema_gname, $strips, false, true, $itests));
				}
				$dqsr->add($this->invokeDQS("schema", $schema_schema_gname, false, false, $sstrips, false, $stests));				
			}
			return $dqsr;
		}
		else {
			return $this->invokeDQS("schema", $schema_gname, false, $strips, false, true, $stests);
		}
	}
	
	/**
	 * Creates the named graphs for a graphs schema and schema schema 
	 * 
	 * Schema schema graph regulates updates of schema graph as instance data
	 * Schema graph regulates updates of instance graph
	 * @param Graph $graph the graph object
	 * @param array $sstrips the triples to be added to the schema schema graph
	 * @param array $strips the triples to be added to the schema graph
	 * @param array $stests DQS tests to be run on schema validation
	 * @param array $itests DQS tests to be run on instance validation
	 * @return DQSResult Result of validation
	 */
	function createGraphSchema(Graph $graph, $sstrips, $strips, $test_flag, $stests, $itests = false){
		if($this->getServiceSetting("two_tier_schemas", true) && count($sstrips) > 0){
			$dqsr = new DQSResult("Create Graph Schema", $test_flag);
			//create schema schema graph regardless of test_flag
			$dqsr->add($this->invokeDQS("schema", $graph->schema_schema_gname(), false, $sstrips, false, false, $stests));
			if($dqsr->is_accept()){
				//write the schema to the schema graph as instance data constrained by schema schema graph
				$dqsr->add($this->invokeDQS("instance", $graph->schema_schema_gname(), $graph->schema_gname(), $strips, false, $test_flag, $itests));
				if($dqsr->is_accept()){
					if($test_flag){
						$dqsr->add($this->invokeDQS("schema", $graph->schema_gname(), false, $strips, false, $test_flag, $stests));
					}
					else {
						$dqsr->add($this->invokeDQS("schema_validate", $graph->schema_gname(), $stests));
					}
				}
				if(!$dqsr->is_accept() || $test_flag){
					//rollback update to schema schema graph
					$this->invokeDQS("schema", $graph->schema_schema_gname(), false, false, $sstrips, false, $stests);
				}				
			}
			return $dqsr;	
		}
		else {
			return $this->invokeDQS("schema", $graph->schema_gname(), false, $strips, false, $test_flag, $stests);				
		}
	}

	
	function updateGraphSchema(Graph $graph, $ssitrips, $ssdtrips, $sitrips, $sdtrips, $test_flag, $stests, $itests){
		$dqsr = new DQSResult("update graph", $test_flag);
		if($this->getServiceSetting("two_tier_schemas", true)){
			//first we have to create the schema schema ontology
			//updating schema schema graph
			if(count($ssitrips) > 0 || count($ssdtrips) > 0){
				$dqsr->add($this->invokeDQS("schema", $schema_schema_gname, false, $ssitrips, $ssdtrips, false, $stests));
				if(!$dqsr->is_accept()){
					return $dqsr;
				}			
			}
			//updating schema graph
			if(count($sitrips) > 0 || count($sdtrips) > 0){
				//update schema as instance data against schema schema 
				$dqsr->add($this->invokeDQS("instance", $graph->schema_schema_gname(), $graph->schema_gname(), $sitrips, $sdtrips, false, $itests));
				if($dqsr->is_accept()){
					//then validate the schema against instance data. 
					$dqsr->add($this->invokeDQS("validate", $graph->schema_gname(), $graph->instance_gname(), $itests));				
				}
				if(!$dqsr->is_accept() || $test_flag) {
					$dqsr->add($this->invokeDQS("instance", $graph->schema_schema_gname(), $graph->schema_gname(), $sdtrips, $sitrips, false, $itests));				
				}
			}
			if(!$dqsr->is_accept() || $test_flag){
				//rollback update to schema schema graph
				$dqsr->add($this->invokeDQS("schema", $graph->schema_schema_gname(), $graph->schema_gname(), $ssdtrips, $ssitrips, false, $stests));
			}				
		}
		else {
			//make changes to schema graph
			$dqsr->add($this->invokeDQS("schema", $graph->schema_gname(), false, $sitrips, $sdtrips, false, $stests));
			if($dqsr->is_accept()){
				//then validate the schema against instance data.
				$dqsr->add($this->invokeDQS("validate", $graph->schema_gname(), $graph->instance_gname(), $itests));
				if(!$dqsr->is_accept() || $test_flag) {
					//roll back changes to schema graph
					$dqsr->add($this->invokeDQS("schema", $graph->schema_gname(), false, $sdtrips, $sitrips, false, $stests));
				}
			}				
		}
		return $dqsr;		
	}
	
	function createInstance(Graph $graph, $trips, $test_flag, $tests){
		return $this->invokeDQS("instance", $graph->schema_gname(), $graph->instance_gname(), $trips, false, $test_flag, $tests);
	}
	
	function updateInstance(Graph $graph, $itrips, $dtrips, $test_flag, $tests){
		return $this->invokeDQS("instance", $graph->schema_gname(), $graph->instance_gname(), $itrips, $dtrips, $test_flag, $tests);
	}
	
	/* 
	 * all of these are just convenience interfaces to invoke DQS...
	 */
	function getGraphldoClasses($schema_gname){
		$classes = $this->invokeDCS($schema_gname);
		return $classes;
	}
	
	function getClassFrame($schema_gname, $classname){
		$classes = $this->invokeDCS($schema_gname, $classname);
		return $classes;
	}
		
	function dumpDQSRequest($fname, $service, $tests, $schema_gname, $gname, $itrips, $dtrips, $qstr){
		$dumpstr = "Service: $service\n";
		$dumpstr .= "Tests: ";
		if(is_array($tests)){
			$dumpstr .= implode(", ", $tests)."\n";
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
