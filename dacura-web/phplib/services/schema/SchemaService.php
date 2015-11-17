<?php
include_once("SchemaDacuraServer.php");

class SchemaService extends LdService {
	
	var $default_screen = "view";
	var $protected_screens = array("view" => array("admin"));
	var $public_screens = array("test");
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
	}
	
	function handlePageLoad($dacura_server){
		$params = array();
		$params["image"] = $this->url("image", "buttons/schema.png");
		
		if($this->getCollectionID() == "all"){
			$params['topbreadcrumb'] = "All Ontologies";
			$params["title"] = "Ontology Viewer";
			if($this->screen && $this->screen != "view"){
				$params["breadcrumbs"] = array(array(), array());
				$this->renderToolHeader($params);
				$params['args'] = $this->getOptionalArgs();
				$params["entity_type"] = "ontology";
				$params['id'] = $this->screen;
				$this->renderScreen("ontology", $params);								
			}
			else {
				$params["title"] = "Imported Ontologies";
				$params["entity_type"] = "ontology";
				$this->renderToolHeader($params);
				$this->renderScreen("system", $params);
			}
		}
		else {
			if($this->screen && $this->screen != "view"){
				$params["title"] = "Named Graph Schema Management";	
				$params['collectionbreadcrumb'] = " named graph management";
				$params["breadcrumbs"] = array(array(), array());
				$this->renderToolHeader($params);
				$params['id'] = $this->screen;
				$params['ontologies'] = $dacura_server->loadImportedOntologyList();
				$params['args'] = $this->getOptionalArgs();
				$params["entity_type"] = "graph";
				$this->renderScreen("graph", $params);
			}
			else {
				$params["entity_type"] = "graph";
				$params["title"] = "Schema Management";
				$params["subtitle"] = "Manage the graphs where instance data is stored";
				$this->renderToolHeader($params);
				$this->renderScreen("schema", array());				
			}
		}
		$this->renderToolFooter($params);		
	}
	
	function getDQSOptions($type){
		$options = array();
		foreach($this->dqs_options as $id => $props){
			if(in_array($type, $props['type'])){
				$options[$id] = $props;
			}
		}
		return $options;	
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
	
	/*
	 * The screens are the ids of ontologies 
	 * There are no ontology level access control rules
	 * This function saves the context and ensures that the user has view ontology permission. 
	 */
	function userCanViewScreen($user){
		$sc = $this->screen;
		$this->screen = "view";
		$ans = parent::userCanViewScreen($user);
		$this->screen = $sc;
		return $ans;
	}
	
	
	
	
	
	
}
