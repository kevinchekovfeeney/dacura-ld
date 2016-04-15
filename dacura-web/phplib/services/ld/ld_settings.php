<?php
$settings = array(
	"service-button-title" => "Linked Data",
	"demand_id_token" => "@id",
	"url_mappings" => false,
	"problem_predicates" => false,
	"rollback_new_to_pending_on_dqs_reject"	=> true,
	"pending_updates_prevent_rollback" => false,
	"rollback_updates_to_pending_on_dqs_reject" => true,
	"rollback_updates_to_pending_on_version_reject" => true,
	"two_tier_schemas" => true,		
	"test_unpublished" => true,
	"create_dqs_schema_tests" => array(),
	"create_dqs_instance_tests" => array(),	
	
	"create_options" => array("show_dqs_triples" => 1, 
			"ns" => 1, "addressable" => 1, "analysis" => 1, 
			"show_ld_triples" => 1, "fail_on_id_denied" => 1, "show_result" => 1
	),
	"update_options" => array("show_dqs_triples" => 1, "show_ld_triples" => 1, "fail_on_id_denied" => 1, 
			"show_update_triples" => 1, "show_meta_triples" => 1, "show_result" => 1, 
			"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1
	),
	"tables" => array(
		"history" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "info" => true, "order" => array(0, "desc"),
			"aoColumns" => array(null, null,  array("bVisible" => false), array("iDataSort" => 2), array("bVisible" => false), array("iDataSort" => 4), null, null))				
		),
		"ldoupdates" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "searching" => false, "info" => true, "pageLength" => 50, "order" => array(0, "desc"), 
			"aoColumns" => array(null, null, null, null, null, array("bVisible" => false), array("iDataSort" => 5), array("bVisible" => false), array("iDataSort" => 7)))				
		),
		"ld" => array("datatable_options" => array(
			"jQueryUI" => true, "searching" => false, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(8, "desc"), 
				"aoColumns" => array(null, null, null, null, null, null, array("bVisible" => false, "iDataSort" => 6), array("bVisible" => false), array("iDataSort" => 8), array("bVisible" => false), null, array("orderable" => false)))
		), 
		"updates" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(10, "desc"), 
				"aoColumns" => array(null, null, null, null, null, null, null, array("iDataSort" => 8), array("bVisible" => false), array("iDataSort" => 10), array("bVisible" => false), null, array("orderable" => false)))
		)
	),
	"messages" => array(
		"create_ldo_intro" => "Add a new LDO to the system by filling in the form below and hitting 'create'",
		"raw_intro_msg" => "Update the raw underlying data",
		"list_objects_intro" => "View all LDOs in the system",
		"list_updates_intro" => "View updates (pending, rejected, accepted) to LDOs",
		"view_history_intro" => "View previous versions of this LDO",
		"view_updates_intro" => "View updates (pending, rejected, accepted) to this LDO",
		"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "View the LDO's meta-data",
		"create_button_text" => "Create New LDO",
		"testcreate_button_text" => "Test Creation",
		"raw_edit_text" => "Update LDO",
		"testraw_edit_text" => "Test LDO Update",
		"view_update_contents_intro" => "The contents of the update to the LDO",
		"view_update_meta_intro" => "The LDO update's meta data",
		"view_update_analysis_intro" => "The LDO update's analysis",
		"update_raw_intro_msg" => "The LDO update's raw editing interface",
		"view_update_after_msg" => "The LDO after the update has taken place",
		"view_update_before_msg" => "The LDO before the update has taken place",
	),
	"create_ldo_fields" => array(
		"id" => array("label" => "ID", "length" => "short", "help" => "The id of the linked data object - must be all lowercase with no spaces or punctuation. Choose carefully - the id appears in all urls that reference the object and cannot be easily changed!"),
		"ldtype" => array("label" => "Linked Data Type", "input_type" => "select", "help" => "The full title of the object - may include spaces and punctuation."),
		"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		"url" => array("label" => "Canonical URL", "type" => "url", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		"meta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		"ldsource" => array("label" => "Contents Source", "type" => "choice", "options" => array("text" => "Textbox", "url" => "Import from URL", "file" => "Upload File"), "input_type" => "radio", "help" => "You can choose to import the contents of the linked data object from a url, a local file, or by inputting the text directly into the textbox here"),
		"format" => array("label" => "Contents Format", "type" => "choice", "help" => "The contents of the object (in RDF - Linked Data format)"),
		"ldurl" => array("label" => "Import URL", "type" => "url", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("download" => array("title" => "Load URL"))),
		"ldfile" => array("label" => "Import File", "type" => "file", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("upload" => array("title" => "Upload File"))),
		"contents" => array("label" => "Contents", "type" => "complex", "input_type" => "custom", "help" => "The contents of the object (in RDF - Linked Data format)"),
	),
		
	"raw_edit_fields" => array(
		"meta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		"format" => array("label" => "Contents Format", "type" => "choice", "help" => "The contents of the object (in RDF - Linked Data format)"),
		"contents" => array("label" => "Contents", "type" => "complex", "input_type" => "custom", "help" => "The contents of the object (in RDF - Linked Data format)"),				
		"editmode" => array("label" => "Edit Mode", "type" => "choice", "help" => "The edit mode - replace or update", "options" => array("replace" => "Replace", "update" => "Update")),
	)
		
);