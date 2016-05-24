<?php
/**
 * Settings for ld service
 * 
 * These are inherited by all ld services unless they are explicitly overwritten or unset.  Settings specified here 
 * can easily cascade inadvertently 
 */
$settings = array(
	"facet-list" => array("view" => "Browse the data on the system"),
	"service-title" => "Linked Data Service",
	"service-button-title" => "Raw Data",		
	"service-description" => "The Linked Data service provides access to raw-data management",
	/*settings for ld services  - first the general purpose ones, then the ones associated with particular screens */
	"demand_id_token" => "@id",//if @id clashes with json ld @id then this can be changed 
	/* are we allowed to create empty linked data objects? */
	"ldo_allow_empty_create" => 1,
	/* are we allowed to request that our linked data objects have externally defined ids */
	"ldo_allow_demand_id" => true,
	/* the minimum length of the id of a linked data object */
	"ldo_mimimum_id_length" => 2,
	/* the maximum length of the id of a linked data object */
	"ldo_maximum_id_length" => 80,
	/* should auto generated ids have more entropy (longer, uglier)  to avoid clashes */
	"ldo_extra_entropy" => true,
	/* a {old: new} mapping of urls, which are to apply universally whenever those urls are encountered */
	"url_mappings" => array(),
	/* an array of {ns: predicates} describing 'problem' banned predicates */
	//these should only really be uncommented if we want to do something funny with rdf interpretation
	//"problem_predicates" => false,
	//"structural_predicates" => array(),
	//"prefixes" => false,
	//should we store rejected requests (array of actions: "create", "update", "update update")
	"store_rejected" => array(),
	//uncomment to allow overriding of policy engine stuff
	//"decisions" => array...
	/* if the dqs rejects a new object, to we want to roll it back and let it be pending in the object store? */
	"rollback_ldo_to_pending_on_dqs_reject"	=> true,
	/* if the dqs rejects a request that is in pending state, do we retain the pending state or go to reject? */
	"retain_pending_on_dqs_reject"	=> true,
	/* if there are updates pending on the current version of an object, we may not want to allow us to roll it back to an older version */
	"pending_updates_prevent_rollback" => false,
	/* if dqs rejects an update because there has been a version clash, do we want to save it in the object store as a deferred update? */	
	"rollback_update_to_pending_on_version_reject" => true,
	"rollback_update_to_pending_on_dqs_reject"	=> false,
	/* are two tier schemas in operation */
	"two_tier_schemas" => false,
	/* should we apply graph tests to objects even when they are unpublished (hypotethical tests). */		
	"test_unpublished" => true,
	/* should we cache the results of an object's analysis? (analysis can be slow - we do this for speed */
	"cache_analysis" => true,
	/* the configuration of the analysis cache - by default set so that the cache is never marked stale - have to explicitly update it */
	"analysis_cache_config" => array("type" => "constant"),
	/* the set of tests that will apply when a create schema request is sent to the dqs - uncomment to change default test set 
	 * (otherwise all non-best practice schema tests will be used */
	//"create_dqs_schema_tests" => array(),
	/* the set of tests that will apply when a create instance request is sent to the dqs - default is to send all 
	 * Non-best practice tests - set this to change default (can be overwritten be graphs or ontologies */
	//"create_dqs_instance_tests" => array(),
	"allow_updates_against_old_versions" => true,
	/* List Screen */	
	/* ldolist tab related */
		
	/* what filters is the user allowed to specify in the url string ?a=b&c=d etc */
	"ldolist_user_filters" => array("status", "type", 'collectionid', 'createtime', "include_all"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"ldolist_fixed_filters" => array(),		

	/* updatelist tab related */
		
	/* what filters is the user allowed to specify in the url string ?a=b&c=d etc */
	"updatelist_user_filters" => array(
		"type", "targetid", "to_version", "from_version", 'collectionid', 'status', 'version', 'createtime', "include_all"
	),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"updatelist_fixed_filters" => array(),
		
	/* create screen related */
		
	/* the options to pass to the LDOViewer object which manages the create form */
	"create_ldoviewer_config" => array(
		"result_options" => array(),
		"view_options" => array(),
		"graph_options" => array(),
		'emode' => "import",
		"show_cancel" => false,
		"show_buttons" => false
		//"options_target" => "#row-ldo-details-ldcontents th",
		//"ldimport_header" => "Import Contents"
	),
		
	/* the list of options that can be sent to api for create command */
	"create_user_options" => array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
	),
	/* options that are fixed to a particular value for every create command (allows us to limit api selectively) */	
	"create_fixed_options" => array(),
	"create_default_options" => array(
		"fail_on_id_denied" => 1
	),
	"test_create_fixed_options" => array(),
	"test_create_default_options" => array(
		"show_dqs_triples" => 1, 
		"ns" => 1, 
		"addressable" => 1, 
		"analysis" => 1,
		"show_ld_triples" => 1, 
		"fail_on_id_denied" => 0, 
		"show_result" => 1, 
		"show_meta_triples" => 1
	),		
	/* The fields that populate the create form */
	"create_ldo_fields" => array(
		"id" => array("label" => "ID", "length" => "short", "help" => "The id of the linked data object - must be all lowercase with no spaces or punctuation. Choose carefully - the id appears in all urls that reference the object and cannot be easily changed!"),
		"ldtype" => array("label" => "Linked Data Type", "input_type" => "select", "help" => "The full title of the object - may include spaces and punctuation."),
		"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		"meta" => array("label" => "Metadata", "type" => "complex", "input_type" => "custom", "help" => "Structured json meta-data that is associated with the object"),
		"ldcontents" => array("type" => "placeholder", "label" => "Import Contents")
	),
	/* view screen */
	"ldo_viewer_config" => array(),
	"show_contents_options" => array("show_options" => true, "show_buttons" => true),
 	"ldoview_user_options" => array("ns", "addressable", "plain", "history", "updates", "analysis"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"ldoview_fixed_options" => array(),		
	"ldoview_default_options" => array("ns" => 1, "history" => 1, "updates" => 1, "analysis" => 1),

	"ldoview_user_args" => array("version", "mode", "ldtype", "format"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"ldoview_fixed_args" => array(),
	"ldoview_default_args" => array("format" => "json"),
	
	/* contents tab */
	"ldo_viewer_config" => array(
	),		
	/* default options to be sent to api for update command */
	"ldo_update_user_options" => array(
		"ns", "addressable", "plain", "rollback_update_to_pending_on_dqs_reject", "show_dqs_triples", "show_ld_triples", 
		"fail_on_id_denied", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"ldo_test_update_user_options" => array(
		"ns", "addressable", "plain", "rollback_update_to_pending_on_dqs_reject", "show_dqs_triples", "show_ld_triples", 
		"fail_on_id_denied", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"ldo_update_fixed_options" => array(),
	"ldo_update_default_options" => array("fail_on_id_denied" => 1),
	"ldo_test_update_fixed_options" => array(),
	"ldo_test_update_default_options" => array(
		"show_dqs_triples" => 1, 
		"show_ld_triples" => 1, 
		"fail_on_id_denied" => 0, 
		"show_update_triples" => 1, 
		"show_meta_triples" => 1, 
		"show_result" => 1, 
		"show_changed" => 1, 
		"show_original" => 1, 
		"ns" => 1
	),
	"ldo_update_user_args" => array("version", "editmode", "mode", "ldtype", "format"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"ldo_update_fixed_args" => array(),
	"ldo_update_default_args" => array(),
	"ldo_test_update_user_args" => array("version", "editmode", "mode", "ldtype", "format"),
	"ldo_test_update_fixed_args" => array(),
	"ldo_test_update_default_args" => array(),
	
	/* Meta Tab */
	"update_meta_fields" => array(
		"meta" => array("label" => "Object Metadata", "type" => "complex", "input_type" => "custom", "help" => "Json meta-data that is associated with the object"),
	),
	"ldo_meta_user_options" => array(
			"ns", "plain", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"ldo_test_meta_user_options" => array(
			"ns", "plain", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"ldo_meta_fixed_options" => array(),
	"ldo_meta_default_options" => array("ns" => 1),
	"ldo_test_meta_fixed_options" => array(),
	"ldo_test_meta_default_options" => array(
		"show_update_triples" => 1,
		"show_meta_triples" => 1,
		"show_result" => 1,
		"show_changed" => 1,
		"show_original" => 1,
		"ns" => 1
	),
		
	/* View Update Page */	
	"ldoupdate_viewer_config" => array(),
		
	"update_update_user_args" => array("version", "editmode", "mode", "ldtype", "format"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"update_update_fixed_args" => array(),
	"update_update_default_args" => array(),
	"test_update_update_user_args" => array("version", "editmode", "mode", "ldtype", "format"),
	"test_update_update_fixed_args" => array(),
	"test_update_update_default_args" => array(),

	"update_update_user_options" => array(
			"ns", "addressable", "plain", "rollback_update_to_pending_on_dqs_reject", "show_dqs_triples", "show_ld_triples",
			"fail_on_id_denied", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"test_update_update_user_options" => array(
			"ns", "addressable", "plain", "rollback_update_to_pending_on_dqs_reject", "show_dqs_triples", "show_ld_triples",
			"fail_on_id_denied", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"update_update_fixed_options" => array(),
	"update_update_default_options" => array("fail_on_id_denied" => 1),
	"test_update_update_fixed_options" => array(),
	"test_update_update_default_options" => array(
			"show_dqs_triples" => 1,
			"show_delta" => 1,
			"show_ld_triples" => 1,
			"fail_on_id_denied" => 0,
			"show_update_triples" => 1,
			"show_meta_triples" => 1,
			"show_result" => 1,
			"show_changed" => 1,
			"show_original" => 1,
			"ns" => 1
	),		
		
	"update_meta_user_options" => array(
		"ns", "plain", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"update_test_meta_user_options" => array(
		"ns", "plain", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"update_meta_fixed_options" => array(),
	"update_meta_default_options" => array("ns" => 1),
	"update_test_meta_fixed_options" => array(),
	"update_test_meta_default_options" => array(
		"show_update_triples" => 1,
		"show_meta_triples" => 1,
		"show_result" => 1,
		"show_changed" => 1,
		"show_original" => 1,
		"ns" => 1
	),
		
	"show_update_contents_options" => array("show_options" => true, "show_buttons" => false),
		
	"update_view_user_options" => array("ns", "addressable", "plain", "show_changed", "show_original", "show_contents", "show_delta"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"update_view_fixed_options" => array(),
	"update_view_default_options" => array("ns" => 1, "show_changed" => 1, "show_original" => 1, "show_delta" => 1, "show_contents" => 1),
	"update_view_user_args" => array("version", "mode", "ldtype", "format"),
	/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
	"update_view_fixed_args" => array(),
	"update_view_default_args" => array("format" => "json"),		
		
	/* the tables - all sreens together */	
	"tables" => array(
		"history" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "info" => true, "order" => array(0, "desc"),
			"aoColumns" => array(null, null,  array("bVisible" => false), array("iDataSort" => 2), array("bVisible" => false), array("iDataSort" => 4), null, null))				
		),
		"ldoupdates" => array("datatable_options" => array(
			"jQueryUI" => true, "scrollX" => false, "searching" => false, "info" => true, "pageLength" => 50, "order" => array(8, "desc"), 
			"aoColumns" => array(null, null, array("bVisible" => false), null, null, array("bVisible" => false), array("iDataSort" => 5), array("bVisible" => false), array("iDataSort" => 7), null, array("orderable" => false)))				
		),
		"ld" => array("datatable_options" => array(
			"jQueryUI" => true, "searching" => false, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(8, "desc"), 
				"aoColumns" => array(null, array("bVisible" => false), null, null, null, null, array("bVisible" => true, "iDataSort" => 7), array("bVisible" => false), array("iDataSort" => 9), array("bVisible" => false), null, array("orderable" => false)))
		), 
		"updates" => array("datatable_options" => array(
			"jQueryUI" => true, "searching" => false, "scrollX" => false, "pageLength" => 20, "lengthMenu" => array(10, 20, 50, 75, 100), 
			"info" => true, "order" => array(9, "desc"), 
				"aoColumns" => array(null, null, null, null, null, null, null, array("iDataSort" => 8), array("bVisible" => false), array("iDataSort" => 10), array("bVisible" => false), null, array("orderable" => false)))
		)
	),
	/* All the configurable text messages */
	"messages" => array(
		"list_page_title" => "Manage your Linked Data Objects",
		"list_page_subtitle" => "View and manage all of the linked data objects in the system",
		"ld_list_title" => "Linked Data Objects",
		"ld_create_title" => "Create New Linked Data Object",
		"ld_updates_title" => "Updates to Linked Data Objects",
		"ldlist_multiselect_button_text" => "Set selected linked data objects to status: ",			
		"ldlist_multiselect_text" => "Update",			
		"updates_multiselect_button_text" => "Set selected updates to status: ",			
		"updates_multiselect_text" => "Update",			
		"create_ldo_intro" => "",
		"view_page_title" => "Linked Data Object",	
		"view_page_subtitle" => "",
		"view_page_description" => "",
				
		"list_objects_intro" => "",
		"list_updates_intro" => "",
		"view_history_intro" => "",
		"view_updates_intro" => "",
		"updates_screen_title" => "Updates",
		"history_screen_title" => "History",
		"contents_screen_title" => "Contents",
		"analysis_screen_title" => "Analysis",
		"meta_screen_title" => "Metadata",
		"view_contents_intro" => "",
		"view_meta_intro" => "",
		"create_button_text" => "Create New LDO",
		"test_create_button_text" => "Test LDO Creation",
		"update_meta_button" => "Save Updated Metadata",
		"test_update_meta_button" => "Test Metadata Update",
		"test_create_button_text" => "Test Creation",

		"update_screen_title" => "View Update",	
		"update_screen_subtitle" => "",
		"update_contents_screen_title" => "update contents",
		"update_commands_screen_title" => "Update Command",	
		"update_meta_screen_title" => "Update Metadata",
		"update_after_screen_title" => "after the update",
		"update_before_screen_title" => "before the update",
		"update_update_button_text" => "Save Updated Update",
		"test_update_update_button_text" => "Test Updated Update",
		"view_update_contents_intro_msg" => "",
		"view_update_meta_intro" => "",
		"view_after_intro_msg" => "",
		"update_before_intro_msg" => ""
	),

	/* field settings that will appear on configuration form of this service */
	"config_form_fields" => array(
		"demand_id_token" => array("label" => "Demand ID Token", "type" => "text", "help" => "The token that will be used to indicate the required id of the new element"),
		"ldo_allow_empty_create" => array("label" => "Allow Empty Objects", "type" => "choice", "options" => array(1 => "Allow", 0 => "Forbid"), "help" => "Can users create new linked data objects with empty contents?"),
		"ldo_mimimum_id_length" => array("label" => "Minimum ID length", "type" => "text", "help" => "What is the minimum length of an id that users can create?"),
		"create_ldo_fields" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"tables" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"messages" => array("type" => "section", "label" => "Text messages that will be reported to the user"),
	),
);