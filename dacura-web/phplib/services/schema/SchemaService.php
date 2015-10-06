<?php
include_once("SchemaDacuraServer.php");
include_once("phplib/services/ld/LdService.php");


class SchemaService extends DacuraService {
	
	var $default_screen = "view";
	//var $protected_screens = array("view" => array("admin"));
	var $public_screens = array("view", "test");
	var $dqs_options = array(
			"classCycles" => array(
					"title" => "Class Cycle", 
					"explanation" => "A cycle in the class inheritance hierarchy.", 
					"category" => "class", 
					"type" => array("schema", "schema-instance")
			),
			"duplicateClasses" => array(
					"title" => "Duplicate Classes", 
					"explanation" => "Two classes with the same ID in the schema",
					"category" => "class", 
					"type" => array("schema", "schema-instance")
			),
			"orphanSubClasses" => array(
					"title" => "Orphan Class", 
					"explanation" => "A class derived from a non-existant class",
					"category" => "class", 
					"type" => array("schema", "schema-instance")
			),
			"orphanSubProperties" => array(
					"title" => "Orphan Sub-property",
					"explanation" => "A property derived from a non-existant property",
					"category" => "property",
					"type" => array("schema", "schema-instance")						
			),
			"propertyCycles" => array(
					"title" => "Property Cycle",
					"explanation" => "A cycle in the property inheritance hierarchy",
					"category" => "property",
					"type" => array("schema", "schema-instance")						
			),
			"duplicateProperties" => array(
					"title" => "Duplicate Properties", 
					"explanation" => "Two properties with the same ID in the schema",
					"category" => "property", 
					"type" => array("schema", "schema-instance")
			),
			"orphanProperties" => array(
					"title" => "Orphan Property", 
					"explanation" => "A property that has been used without being defined in the schema",
					"category" => "property", 
					"type" => array("schema-instance")
			),
			"blankNode" => array(
					"title" => "Blank Node", 
					"explanation" => "An anonymous node in the graph",
					"category" => "general", 
					"type" => array("schema-instance")
			),
			"schemaBlankNodes" => array(
					"title" => "Blank Node", 
					"explanation" => "An anonymous node in the schema",
					"category" => "general", 
					"type" => array("schema")
			),
			"invalidRange" => array(
				"title" => "Invalid Range",
				"explanation" => "An invalid range specified for a property",
				"category" => "general", 
				"type" => array("schema", "schema-instance")
			),  
			"invalidDomain" => array(
				"title" => "Invalid Domain",
				"explanation" => "An invalid domain specified for a property",
				"category" => "general", 
				"type" => array("schema", "schema-instance")
			),  
			"invalidInstanceDomain" => array(
				"title" => "Invalid Domain",
				"explanation" => "Instance data using an invalid range for a property",
				"category" => "general", 
				"type" => array("instance")
			),  
			"invalidInstanceRange" => array(
				"title" => "Invalid Range",
				"explanation" => "Instance data using an invalid range for a property",
				"category" => "general", 
				"type" => array("instance")
			),  
			"orphanInstance" => array(
				"title" => "Orphan Instance",
				"explanation" => "An instance of a class that does not exist in the schema",
				"category" => "general", 
				"type" => array("instance")
			)			
	);

	function renderFullPageHeader(){
		parent::renderFullPageHeader();
		$this->writeIncludedInterpolatedScripts($this->mydir."dacura.schema.js");
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css', "ld").'">';
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_service_file_url('style.css').'">';
		echo "<div id='pagecontent-container'>";
		echo "<div id='pagecontent-nopad'>";
	}
	
	function renderFullPageFooter(){
		echo "</div></div>";
		parent::renderFullPageFooter();
	}
	
	function handlePageLoad($dacura_server){
		//$this->renderScreen("system", array());
		//opr($this);	
		if($this->getCollectionID() == "all"){
			if($this->screen && $this->screen != "view"){
				$params["breadcrumbs"] = array(array(array("", ucfirst($this->screen)." Ontology")), array());
				$params["title"] = "$this->screen Ontology Configuration";
				$params["subtitle"] = "Analyse and manage your imported ontology";
				$this->renderToolHeader($params);
				if(isset($_GET['mode'])) $params['mode'] = $_GET['mode'];
				if(isset($_GET['version'])) $params['version'] = $_GET['version'];
				if(isset($_GET['format'])) $params['format'] = $_GET['format'];
				if(isset($_GET['display'])) $params['display'] = $_GET['display'];
				$this->renderScreen("ontology", array("id" => $this->screen));								
			}
			else {
				$params["breadcrumbs"] = array(array(), array());
				$params["title"] = "Imported Ontologies";
				$params["subtitle"] = "Manage the set of external ontologies supported by the system.";
				$this->renderToolHeader($params);
				$this->renderScreen("system", array());
			}
		}
		else {
			if($this->screen && $this->screen != "view"){
				$params["title"] = "Graph Management Service";				
				$params["subtitle"] = "Manage the graphs schema";
				$this->renderToolHeader($params);
				$params['id'] = $this->screen;
				$params['ontologies'] = $dacura_server->loadImportedOntologyList();
				$this->renderScreen("graph", $params);
			}
			else {
				$params["title"] = "Schema Management Service";
				$params["subtitle"] = "Manage the structure of your dataset";
				$this->renderToolHeader($params);
				$this->renderScreen("schema", array());				
			}
		}
		$this->renderToolFooter($params);		
	}
		
/*	function handlePageLoad($dacura_server){
		$params = array();
		if($this->screen == "test"){
			$this->renderScreen("test", array());				
		}
		elseif($this->screen == 'ontology'){
			$params["breadcrumbs"] = array(array(array("", ucfirst($this->args[0])." Ontology")), array());
			$params["title"] = "Ontology Configuration";
			$params["subtitle"] = "Analyse and manage external ontologies";
			$this->renderToolHeader($params);
			if(isset($_GET['mode'])) $params['mode'] = $_GET['mode'];
			if(isset($_GET['version'])) $params['version'] = $_GET['version'];
			if(isset($_GET['format'])) $params['format'] = $_GET['format'];
			if(isset($_GET['display'])) $params['display'] = $_GET['display'];
			$this->renderScreen("ontology", array("id" => $this->args[0]));				
		}
		elseif($this->screen != "view") {
			$params["breadcrumbs"] = array(array(array("", ucfirst($this->screen)." Graph")), array());
			$params["title"] = "Graph Management";				
			$params["subtitle"] = "Define the structure of the data in your graphs.";
			$this->renderToolHeader($params);
			$this->renderScreen("graph", array("graphid" => $this->screen));
		}
		else {
			$params["breadcrumbs"] = array(array(), array());
			$params["title"] = "Schema Management Service";
			if($this->getCollectionID() == "all"){
				$params["subtitle"] = "Manage ontologies that are available system wide";
				$this->renderToolHeader($params);
				$this->renderScreen("system", array("scope" => "system"));
			}
			elseif($this->getDatasetID() == "all") {
				$params["subtitle"] = "Manage the structure of your datasets";
				$this->renderToolHeader($params);
				$this->renderScreen("schema", array("scope" => "collection"));
			}
			else {
				$params["subtitle"] = "Manage the structure of your dataset";
				$this->renderToolHeader($params);
				$this->renderScreen("schema", array("scope" => "dataset"));				
			}
		}
		$this->renderToolFooter($params);
	}
	*/
	function getDQSOptions($type){
		$options = array();
		foreach($this->dqs_options as $id => $props){
			if(in_array($type, $props['type'])){
				$options[$id] = $props;
			}
		}
		return $options;
		//�checkInstanceClass�, �checkPropertyRange�, �checkPropertyDomain�
		//"classCycles", "propertyCycles",  "duplicateClasses",  "duplicateProperties",  "orphanSubClasses",  "orphanSubProperties",  "orphanInstance",  "orphanProperties",  "blankNode",  "invalidRange",  "invalidDomain",  "invalidInstanceRange",  "invalidInstanceDomain"
		
	}
	
	function getDQSCheckboxes($type){
		$boxes = array();
		$options = $this->getDQSOptions($type);
		foreach($options as $id => $props){
			if(!isset($boxes[$props['category']])){
				$boxes[$props['category']] = array();
			}
			$html = "<input type='checkbox' class='dqsoption' id='$id' value='$id' title='" . $props['explanation'] . "'><label for='$id'>".$props['title']."</label>";
			$boxes[$props['category']][] = $html;
		}
		$html = "";
		foreach($boxes as $id => $entries){
			$html .= "<div class='ontology-type ontology-$id'><div class='dqs-category-title'>".ucfirst($id)." Constraints</div>".implode(" ", $entries)."</div>";
		}
		return $html;
	}
	
	function getCheckingOptions(){
		$opts = array("dqs" => "Dacura Quality Service", "simple" => "Simple Triplestore (Testing)", "none" => "Not Published to Graph");
		$html = "";
		foreach($opts as $o => $v){
			$html .= "<option value='$o'>$v</option>";
		}
		return $html;
	}
	
	function userCanViewScreen($user){
		return true;
	}
	
	
	
	
	
	
}