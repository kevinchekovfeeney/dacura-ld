<?php 
include_once "phplib/services/ld/ld_settings.php";

$settings['fail_on_missing_dependency'] = true;
$settings['fail_on_bad_predicate'] = true;
$settings['fail_on_ontology_hijack'] = false;
$settings['required_meta_properties'] = array("url");
$settings["collapse_blank_nodes_for_dependencies"] = false;
	
$settings['create_dqs_schema_tests'] = "all";
$settings['create_dqs_instance_tests'] = "all";
//$settings['two_tier_schemas'] = false;

$settings["update_options"]  = array("show_dqs_triples" => 1, "show_ld_triples" => 1, "fail_on_id_denied" => 1,
		"show_update_triples" => 1, "show_meta_triples" => 1, "show_result" => 1,
		"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1);

$settings["create_ldo_fields"] = array(
		"id" => array("label" => "ID", "length" => "short", "help" => "The id of the linked data object - must be all lowercase with no spaces or punctuation. Choose carefully - the id appears in all urls that reference the object and cannot be easily changed!"),
		//"ldtype" => array("label" => "Linked Data Type", "input_type" => "select", "help" => "The full title of the object - may include spaces and punctuation."),
		"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		//"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		"url" => array("label" => "Canonical URL", "type" => "url", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		//"meta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		"ldsource" => array("label" => "Contents Source", "type" => "choice", "options" => array("text" => "Textbox", "url" => "Import from URL", "file" => "Upload File"), "input_type" => "radio", "help" => "You can choose to import the contents of the linked data object from a url, a local file, or by inputting the text directly into the textbox here"),
		"format" => array("label" => "Contents Format", "type" => "choice", "help" => "The contents of the object (in RDF - Linked Data format)"),
		"ldurl" => array("label" => "Import URL", "type" => "url", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("download" => array("title" => "Load URL"))),
		"ldfile" => array("label" => "Import File", "type" => "file", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("upload" => array("title" => "Upload File"))),
		"contents" => array("label" => "Contents", "type" => "complex", "input_type" => "custom", "help" => "The contents of the object (in RDF - Linked Data format)"),
);

$settings["messages"] = array(
		"list_page_title" => "Manage your Ontologies",
		"list_page_subtitle" => "View and manage your ontologies",
		"ld_list_title" => "Ontologies",
		"ld_create_title" => "Create New Ontology",
		"ld_updates_title" => "Updates to Ontologies",		
		"create_ldo_intro" => "Add a new ontology to the system by filling in the form below and hitting 'create'",
		"list_updates_intro" => "View updates (pending, rejected, accepted) to Ontologies",
		"view_history_intro" => "View previous versions of this Ontology",
		"view_updates_intro" => "View updates (pending, rejected, accepted) to this Ontology",
		//"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "View the Ontology's meta-data",
		"create_button_text" => "Create New Ontology",
		"testcreate_button_text" => "Test Creation",
		"raw_edit_text" => "Update Ontology",
		"testraw_edit_text" => "Test Ontology Update",
		"view_update_contents_intro" => "The update to the Ontology's contents",
		"view_update_meta_intro" => "The Ontology update's meta data",
		"view_update_analysis_intro" => "The Ontology update's analysis",
		"update_raw_intro_msg" => "The Ontology update's raw editing interface",
		"view_update_after_msg" => "The Ontology after the update has taken place",
		"view_update_before_msg" => "The Ontology before the update has taken place",
		"update_meta_button" => "Save Updated Metadata",
		"test_update_meta_button" => "Test Metadata Update"
		
);

