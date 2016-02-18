<?php
$settings = array(
	"service-button-title" => "Linked Data",
	"demand_id_token" => "request_id",	
	"tables" => array(
		"history" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "info" => true, "order" => array(0, "desc"),
			"aoColumns" => array(null, null,  array("bVisible" => false), array("iDataSort" => 2), array("bVisible" => false), array("iDataSort" => 4), null, null))				
		),
		"ldoupdates" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "info" => true, "order" => array(0, "desc"), 
			"aoColumns" => array(null, null, null, null, null, array("bVisible" => false), array("iDataSort" => 5), array("bVisible" => false), array("iDataSort" => 7)))				
		),
		"ld" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(8, "desc"), 
				"aoColumns" => array(null, null, null, null, null, array("iDataSort" => 6), array("bVisible" => false), array("iDataSort" => 8), array("bVisible" => false)))
		), 
		"updates" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(10, "desc"), 
				"aoColumns" => array(null, null, null, null, null, null, null, array("iDataSort" => 8), array("bVisible" => false), array("iDataSort" => 10), array("bVisible" => false)))
		)
	),
	"messages" => array(
		"create_ldo_intro" => "Add a new LDO to the system by filling in the form below and hitting 'create'",
		"list_objects_intro" => "View all LDOs in the system",
		"list_updates_intro" => "View updates (pending, rejected, accepted) to LDOs",
		"view_history_intro" => "View previous versions of this LDO",
		"view_updates_intro" => "View updates (pending, rejected, accepted) to this LDO",
		"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "View the LDO's meta-data",
		"create_button_text" => "Create New LDO",
		"testcreate_button_text" => "Test Creation",
	),
	"create_ldo_fields" => array(
		"id" => array("label" => "ID", "length" => "short", "help" => "The id of the linked data object - must be all lowercase with no spaces or punctuation. Choose carefully - the id appears in all urls that reference the object and cannot be easily changed!"),
		"ldtype" => array("label" => "Linked Data Type", "input_type" => "select", "help" => "The full title of the object - may include spaces and punctuation."),
		"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		"url" => array("label" => "Canonical URL", "type" => "url", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		"ldmeta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		"ldsource" => array("label" => "Contents Source", "type" => "choice", "options" => array("text" => "Textbox", "url" => "Import from URL", "file" => "Upload File"), "input_type" => "radio", "help" => "You can choose to import the contents of the linked data object from a url, a local file, or by inputting the text directly into the textbox here"),
		"ldformat" => array("label" => "Contents Format", "type" => "choice", "help" => "The contents of the object (in RDF - Linked Data format)"),
		"ldurl" => array("label" => "Import URL", "type" => "url", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("download" => array("title" => "Load URL"))),
		"ldfile" => array("label" => "Import File", "type" => "file", "help" => "The contents of the object (in RDF - Linked Data format)", "actions" => array("upload" => array("title" => "Upload File"))),
		"ldprops" => array("label" => "Contents", "type" => "complex", "input_type" => "custom", "help" => "The contents of the object (in RDF - Linked Data format)"),
	),
		
);