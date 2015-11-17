<?php
require_once("AnalysisResults.php");
require_once("FakeTripleStore.php");

class GraphManager extends DacuraObject {

	var $settings;
	var $tests = "all";
	var $errors;
	var $warnings;
	var $fake = false;

	function __construct($settings){
		$this->settings = $settings;
	}
	
	/* 
	 * all of these are just convenience interfaces to invoke DQS...
	 */
	function getGraphEntityClasses($schema_gname){
		if(substr($schema_gname, -strlen("main_schema")) == "main_schema"){
			$classes = array("seshat:Polity", "seshat:SupraculturalEntity", "seshat:SubPolity", "seshat:QuasiPolity", 
					"seshat:InterestGroup", "seshat:Language", "seshat:Building", "seshat:Territory", 					
					"seshat:CollectionOfTerritories", "seshat:FreeFormArea", "seshat:NGA", "seshat:Event", 
					"seshat:War", "seshat:Battle", "seshat:NavalEngagement", "seshat:LandBattle", "seshat:Siege",	
			);
		}
		elseif(substr($schema_gname, -strlen("provenance_schema")) == "provenance_schema"){
			$classes = array("prov:Activity", "prov:AgentInfluence", "prov:Association", 
				"prov:Attribution", "prov:Bundle", "prov:Collection", "prov:Delegation", "prov:Derivation", "prov:EmptyCollection", 
				"prov:End", "prov:Entity", "prov:EntityInfluence", "prov:Generation", "prov:Influence", 
				"prov:InstantaneousEvent", "prov:Invalidation", "prov:Location", "prov:Organization", "prov:Person", "prov:Plan", 
				"prov:PrimarySource", "prov:Quotation", "prov:Revision", "prov:Role", "prov:SoftwareAgent", "prov:Start", 
				"prov:Usage"
			);
		}
		elseif(substr($schema_gname, -strlen("annotation_schema")) == "annotation_schema"){
			$classes = array("oa:Annotation", "oa:Choice", "oa:Motivation", "oa:Tag");
		}
		else {
			$classes = array();
		}
		return $classes;
	}
	
	function getClassStub($schema_gname, $classname){
		
	}
	
	function getClassAncestry($schema_gname, $classname){}
	
	function test_update($itrips, $dtrips, $gname, $schema_gname, $tests = "all"){
		return $this->update($itrips, $dtrips, $gname, $schema_gname, true);		
	}

	function test_create($itrips, $gname, $schema_gname, $tests = all){
		return $this->create($itrips, $gname, $schema_gname, true, $tests);
	}
	
	function create($itrips, $gname, $schema_gname, $test = false, $tests = "all"){
		return $this->update($itrips, array(), $gname, $schema_gname, $test, $tests);
	}
	
	function test_delete($dtrips, $gname, $schema_gname, $tests = "all"){
		return $this->delete($dtrips, $gname, $schema_gname, true, $tests);
	}
	
	function delete($dtrips, $gname, $schema_gname, $test = false, $tests = "all"){
		return $this->update(array(), $dtrips, $gname, $schema_gname, $test, $tests);
	}
	
	function update($itrips, $dtrips, $gname, $schema_gname, $test = false, $tests = "all"){
		if($this->fake&& $this->settings['dqs_service']['fakets']){
			$fakets = new FakeTripleStore($this->settings['dqs_service']['fakets']);
			return $fakets->update($itrips, $dtrips, $test);
		}
		else {
			return $this->invokeDQS("instance", $schema_gname, $gname, $itrips, $dtrips, $test, $tests);
		}
	}
		
	function updateSchema($itrips, $dtrips, $gname, $schema_gname, $test = false, $tests = "all"){
		if($this->fake){
			return $fakets->update($itrips, $dtrips, false);				
		}
		else {
			return $this->invokeDQS("schema", $schema_gname, $gname, $itrips, $dtrips, $test, $tests);
		}
	}

	function validateSchema($schema_gname, $itrips, $tests){
		if($this->fake && $this->settings['dqs_service']['fakets']){
			$fakets = new FakeTripleStore($this->settings['dqs_service']['fakets']);
			return $fakets->update($itrips, array(), false);
		}
		else {
			return $this->invokeDQS("schema", $schema_gname, false, $itrips, false, true, $tests);
		}
	}
	
	
	function validateAll($gname, $schema_gname){
		return $this->invokeDQS("validate", $schema_gname, $gname);		
	}
	
	function invokeDQS($service, $schema_gname, $gname = false, $itrips = false, $dtrips = false, $test = false, $tests = "all"){
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
			//$qstr .= "$k=$v";
			$qstr .= $k."=".urlencode($v);
		}
		if($this->settings['dqs_service']['dumplast']){
			$this->dumpDQSRequest($this->settings['dqs_service']['dumplast'], $service, $tests, $schema_gname, $gname, $itrips, $dtrips, $qstr);
		}
		$ch = curl_init();
		//if(isset ($this->settings['http_proxy']) && $this->settings['http_proxy']){
		//	curl_setopt($ch, CURLOPT_PROXY, $this->settings['http_proxy']);
		//} TCD's proxy doesnt see the dqs
		curl_setopt($ch, CURLOPT_URL, $this->settings["dqs_service"][$service]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$content = curl_exec($ch);
		if(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			$errcode = (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200) ? 500 : curl_getinfo($ch, CURLINFO_HTTP_CODE);
			return $this->failure_result("Failed to analyse $service - service call failed: $content", $errcode);
		}
		$content = json_decode($content, true);
		if(is_array($content)){
			return $content;
		}
		else {
			return $this->failure_result("Dacura Quality Service returned illegal type (not an array): $content", 500);
		}		
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
