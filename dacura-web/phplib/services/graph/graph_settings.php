<?php 
include "phplib/services/ld/ld_settings.php";
$settings["service-button-title"] = "Graphs";
$settings["service-title"] = "Graph Management Service";
$settings["service-description"] = "The graph management service allows you to manage your dataset's schema by adding ontologies to it";

//$settings['create_dqs_schema_tests'] = "all";
//$settings['create_dqs_instance_tests'] = "all";
$settings['two_tier_schemas'] = false;
$settings['ldo_allow_demand_id'] = true;
$settings["replace_blank_ids"] = false;
//$settings["view_page_options"] = array("ns" => 1, "addressable" => 0, "plain" => 1, "history" => 1, "updates" => 1, "analysis" => 1);
//$settings["create_options"] = array("show_dqs_triples" => 0,
//		"ns" => 1, "addressable" => 1, "analysis" => 1,
//		"show_ld_triples" => 1, "fail_on_id_denied" => 1, "show_result" => 1);
//$settings["update_options"] = array("show_dqs_triples" => 1, "show_ld_triples" => 0, "fail_on_id_denied" => 1,
//		"show_update_triples" => 1, "show_meta_triples" => 0, "show_result" => 1,
//		"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1);

$settings["ldoview_fixed_options"] = array("plain" => 1, "ns" => 1);		
$settings["ldo_update_fixed_options"] = array("plain" => 1, "ns" => 1);
$settings["ldo_test_update_fixed_options"] = array("plain" => 1, "ns" => 1);

$settings["create_ldo_fields"] = array(
		"id" => array("label" => "ID", "length" => "short", "help" => "The id of the linked data object - must be all lowercase with no spaces or punctuation. Choose carefully - the id appears in all urls that reference the object and cannot be easily changed!"),
		//"ldtype" => array("label" => "Linked Data Type", "input_type" => "select", "help" => "The full title of the object - may include spaces and punctuation."),
		//"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		//"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		//"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		//"url" => array("label" => "Canonical URL", "type" => "url", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		//"meta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		//"ldsource" => array("label" => "Contents Source", "type" => "choice", "options" => array("text" => "Textbox", "url" => "Import from URL", "file" => "Upload File"), "input_type" => "radio", "help" => "You can choose to import the contents of the linked data object from a url, a local file, or by inputting the text directly into the textbox here"),
		//"format" => array("label" => "Contents Format", "type" => "choice", "help" => "The contents of the object (in RDF - Linked Data format)"),
		///"ldurl" => array("label" => "Import URL", "type" => "url", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("download" => array("title" => "Load URL"))),
		//"ldfile" => array("label" => "Import File", "type" => "file", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("upload" => array("title" => "Upload File"))),
		"ontimports" => array("label" => "Imported Ontologies", "type" => "placeholder", "help" => "The ontologies that will form the schema of the graph"),
);

$msg_extensions = array(
		"list_page_title" => "Manage your Graphs",
		"list_page_subtitle" => "View and manage your graphs",
		"ld_list_title" => "Graphs",
		"ld_create_title" => "Create New Graph",
		"ld_updates_title" => "Updates to Graphs",
		"create_ldo_intro" => "Add a new graph to the system by filling in the form below and hitting 'create'",
		"list_updates_intro" => "View updates (pending, rejected, accepted) to graphs",
		"view_history_intro" => "View previous versions of this graph",
		"view_updates_intro" => "View updates (pending, rejected, accepted) to this graph",
		//"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "View the graph's meta-data",
		"create_button_text" => "Create New Graph",
		"test_create_button_text" => "Test Graph Creation",
		"raw_edit_text" => "Update Graph",
		"testraw_edit_text" => "Test Graph Update",
		"view_update_contents_intro" => "The update to the Graph's contents",
		"view_update_meta_intro" => "The Graph update's meta data",
		"view_update_analysis_intro" => "The Graph update's analysis",
		"update_raw_intro_msg" => "The Grap update's raw editing interface",
		"view_update_after_msg" => "The Graph after the update has taken place",
		"view_update_before_msg" => "The Graph before the update has taken place",
);

foreach($msg_extensions as $k => $v){
	$settings["messages"][$k] = $v;
}