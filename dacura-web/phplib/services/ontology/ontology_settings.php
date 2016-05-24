<?php 
include_once "phplib/services/ld/ld_settings.php";

$settings["facet-list"] = array("view" => "Browse the data on the system");
$settings["service-title"] = "Ontology Management Service";
$settings["service-button-title"] = "Ontologies";


$settings['fail_on_missing_dependency'] = true;
$settings['fail_on_bad_predicate'] = true;
$settings['fail_on_ontology_hijack'] = false;
$settings["collapse_blank_nodes_for_dependencies"] = true;

$settings["update_options"]  = array("show_dqs_triples" => 1, "show_ld_triples" => 1, "fail_on_id_denied" => 1,
		"show_update_triples" => 1, "show_meta_triples" => 1, "show_result" => 1,
		"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1);

$settings["update_dqs_user_options"] = array(
	"ns", "plain", "show_update_triples", "show_dqs_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
);
$settings["test_update_dqs_user_options"] = array(
	"ns", "plain", "show_update_triples", "show_dqs_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
);
$settings["update_dqs_fixed_options"] = array();
$settings["update_dqs_default_options"] = array("ns" => 1, "plain" => 1);
$settings["test_update_dqs_fixed_options"] = array();
$settings["test_update_dqs_default_options"] = array(
		"show_update_triples" => 1,
		"show_meta_triples" => 1,
		"show_dqs_triples" => 1,
		"show_result" => 1,
		"ns" => 1,
		"plain" => 1
);

$settings["create_ldo_fields"] = array(
		"id" => array("label" => "Prefix", "length" => "short", "help" => "The ontology's namespace prefix, also the ontology's identifier in the system - must be all lowercase with no spaces or punctuation. Choose carefully - the prefix appears in all urls that reference the object and cannot be easily changed!"),
		"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		"url" => array("label" => "Namespace URL", "type" => "url", "length" => "long", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		"ldcontents" => array("type" => "placeholder", "label" => "Import Ontology Contents"),
);

/* Meta Tab */
$settings["update_meta_fields"] = array(
		//"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		"url" => array("label" => "Namespace URL", "length" => "long", "type" => "url", "help" => "The External URL which represents the 'canonical' namespace of this object (to support purls, etc)."),
		//"description" => array("label" => "Description", "help" => "A textual description of the ontology", "type" => "text", "input_type" => "textarea"),
);

$msg_extensions = array(
		"list_page_title" => "Manage your Ontologies",
		"list_page_subtitle" => "Ontologies define the structure of Dacura's data",
		"ld_list_title" => "Ontologies",
		"ld_create_title" => "Add New Ontology",
		"ld_updates_title" => "Updates to Ontologies",		
		"create_ldo_intro" => "Add a new ontology to the system by filling in the form below and hitting 'create'",
		"list_updates_intro" => "Below is a list of updates that have been requested or carried out to your ontologies",
		"view_history_intro" => "Below is a list of all the previous versions of this ontology - click on a row to view that version",
		"view_updates_intro" => "Below is a list of all the updates that have been requested for this ontology",
		//"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "",
		"create_button_text" => "Add Ontology",
		"test_create_button_text" => "Test Ontology",
		"raw_edit_text" => "Update Ontology",
		"testraw_edit_text" => "Test Ontology Update",
		"view_update_contents_intro" => "The update to the Ontology's contents",
		"view_update_meta_intro" => "The Ontology update's meta data",
		"view_update_analysis_intro" => "The Ontology update's analysis",
		"view_update_after_msg" => "The Ontology after the update has taken place",
		"view_update_before_msg" => "The Ontology before the update has taken place",
		"update_meta_button" => "Save Updated Metadata",
		"test_update_meta_button" => "Test Update"	
);


foreach($msg_extensions as $k => $v){
	$settings["messages"][$k] = $v;
}

$settings['tables']["ld"] = array("datatable_options" => 
	array("jQueryUI" => true, "searching" => false, "scrollX" => false, "pageLength" => 20, 
		"lengthMenu" => array(10, 20, 50, 75, 100),
		"info" => true, "order" => array(8, "desc"),
		"aoColumns" => array(null, null, null, null, null, null, array("bVisible" => true, "iDataSort" => 7), array("bVisible" => false), array("iDataSort" => 9), array("bVisible" => false), null, array("orderable" => false)))
);

