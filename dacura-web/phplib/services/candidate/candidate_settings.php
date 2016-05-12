<?php 
include_once "phplib/services/ld/ld_settings.php";
//$settings['create_dqs_instance_tests'] = array("notInverseFunctionalPropertyIC");
$settings['require_candidate_type'] = true;
$settings['ignore_graph_fail'] = false;
$settings['rollback_on_graph_fail'] = true;

//$settings["create_options"] = array("show_dqs_triples" => 1, "ns" => 1, "addressable" => 1, "analysis" => 1,
//		"show_ld_triples" => 1, "fail_on_id_denied" => 1, "show_result" => 1);
//$settings["update_options"] = array("show_dqs_triples" => 1, "show_ld_triples" => 1, "fail_on_id_denied" => 1,
//				"show_update_triples" => 1, "show_meta_triples" => 1, "show_result" => 2,
//				"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1);

$settings["create_ldo_fields"] = array(
		"id" => array("label" => "ID", "length" => "short", "help" => "The id of the linked data object - must be all lowercase with no spaces or punctuation. Choose carefully - the id appears in all urls that reference the object and cannot be easily changed!"),
		//"ldtype" => array("label" => "Linked Data Type", "input_type" => "select", "help" => "The full title of the object - may include spaces and punctuation."),
		//"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		"candtype" =>	array("label" => "Available Types", "help" => "The rdf:type of the candidate (must be present in the contents)", "type" => "choice", "options" => array()),
		//"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		//"url" => array("label" => "Canonical URL", "type" => "url", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		//"meta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		"ldsource" => array("label" => "Contents Source", "type" => "choice", "options" => array("text" => "Textbox", "url" => "Import from URL", "file" => "Upload File"), "input_type" => "radio", "help" => "You can choose to import the contents of the linked data object from a url, a local file, or by inputting the text directly into the textbox here"),
		"format" => array("label" => "Contents Format", "type" => "choice", "help" => "The contents of the object (in RDF - Linked Data format)"),
		"ldurl" => array("label" => "Import URL", "type" => "url", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("download" => array("title" => "Load URL"))),
		"ldfile" => array("label" => "Import File", "type" => "file", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("upload" => array("title" => "Upload File"))),
		"contents" => array("label" => "Contents", "type" => "complex", "input_type" => "custom", "help" => "The contents of the object (in RDF - Linked Data format)"),
);

$settings["messages"] = array(
		"view_frame_intro" => "This is the frame-based view of the Candidate",
		"list_page_title" => "Manage your Instance Data",
		"list_page_subtitle" => "View and manage your candidates - units of instance data",
		"ld_list_title" => "Candidates",
		"ld_create_title" => "Create New Candidate",
		"ld_updates_title" => "Updates to Candidates",
		"create_ldo_intro" => "Add a new candidate to the system by filling in the form below and hitting 'create'",
		"list_updates_intro" => "View updates (pending, rejected, accepted) to candidates",
		"view_history_intro" => "View previous versions of this candidate",
		"view_updates_intro" => "View updates (pending, rejected, accepted) to this candidate",
		//"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "View the candidate's meta-data",
		"create_button_text" => "Create New Candidate",
		"testcreate_button_text" => "Test Candidate Creation",
		"raw_edit_text" => "Update Candidate",
		"testraw_edit_text" => "Test Candidate Update",
		"view_update_contents_intro" => "The update to the Candidate's contents",
		"view_update_meta_intro" => "The Candidate update's meta data",
		"view_update_analysis_intro" => "The Candidate update's analysis",
		"update_raw_intro_msg" => "The Candidate update's raw editing interface",
		"view_update_after_msg" => "The Candidate after the update has taken place",
		"view_update_before_msg" => "The Candidate before the update has taken place",
		"update_meta_button" => "Save Updated Metadata",
		"test_update_meta_button" => "Test Metadata Update",		
);