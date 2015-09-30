<?php
require_once("AnalysisResults.php");
require_once("FakeTripleStore.php");

class GraphManager extends DacuraObject {

	var $settings;
	var $tests = array();//"all";//array("domainNotSubsumedSC");
	var $errors;
	var $warnings;
	var $fake = false;

	function __construct($settings){
		$this->settings = $settings;
	}
	
	/* 
	 * all of these are just convenience interfaces to invoke DQS...
	 */
	
	function setTests($tests){
		$this->tests = $tests;
	}
	
	function test_update($itrips, $dtrips, $gname, $schema_gname){
		return $this->update($itrips, $dtrips, $gname, $schema_gname, true);		
	}

	function test_create($itrips, $gname, $schema_gname){
		return $this->create($itrips, $gname, $schema_gname, true);
	}
	
	function create($itrips, $gname, $schema_gname, $test = false){
		return $this->update($itrips, array(), $gname, $schema_gname, $test);
	}
	
	function test_delete($dtrips, $gname, $schema_gname){
		return $this->delete($dtrips, $gname, $schema_gname, true);
	}
	
	function delete($dtrips, $gname, $schema_gname, $test = false){
		return $this->update(array(), $dtrips, $gname, $schema_gname, $test);
	}
	
	function update($itrips, $dtrips, $gname, $schema_gname, $test = false){
		if($this->fake){
			$fakets = new FakeTripleStore("C:\\Temp\\fakets.json");
			return $fakets->update($itrips, $dtrips, $test);
		}
		else {
			return $this->invokeDQS("instance", $schema_gname, $gname, $itrips, $dtrips, $test);
		}
	}
		
	function updateSchema($itrips, $dtrips, $schema_gname, $gname, $test = false){
		return $this->invokeDQS("schema", $schema_gname, $gname, $itrips, $dtrips, $test);
	}
	
	function validateSchema($schema_gname, $itrips){
		if($this->fake){
			$fakets = new FakeTripleStore("C:\\Temp\\fakets.json");
			return $fakets->update($itrips, array(), false);				
		}
		else {
			return $this->invokeDQS("schema", $schema_gname, false, $itrips, false, false);
		}
	}
	
	function validateAll($gname, $schema_gname){
		return $this->invokeDQS("validate", $schema_gname, $gname);		
	}
	
	function invokeDQS($service, $schema_gname, $gname = false, $itrips = false, $dtrips = false, $test = false){
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
					"tests" 	=>	$this->tests,
					"commit" 	=> 	$commit,
					"schema" 	=> 	$schema_gname,
					"instance" 	=> 	$gname
			));
			$queries['pragma'] = $pragma_ip;
		}
		else {
			$prag = array(
					"tests" => $this->tests,
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
		//$qstr = str_replace(";", ".", $qstr);
		//$qstr = urlencode($qstr);
		$dumpstr = "Service: $service\n";
		$dumpstr .= "Tests: ";
		if(is_array($this->tests)){
			$dumpstr .= implode(", \t", $this->tests)."\n";
		}
		else {
			$dumpstr .= $this->tests."\n";
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
		file_put_contents("C:\\Temp\\lastdqs.json", $dumpstr);
		$ch = curl_init();
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
}
