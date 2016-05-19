<?php
/**
 * Settings for ld service
 * 
 * These are inherited by all ld services unless they are explicitly overwritten or unset.  Settings specified here 
 * can easily cascade inadvertently 
 */
$settings = array(
	/*settings for ld services  - first the general purpose ones, then the ones associated with particular screens */
	"demand_id_token" => "@id",//if @id clashes with json ld @id then this can be changed 
	/* are we allowed to create empty linked data objects? */
	"ldo_allow_empty_create" => true,
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
	"rollback_new_to_pending_on_dqs_reject"	=> true,
	/* if the dqs rejects a request that is in pending state, do we retain the pending state or go to reject? */
	"retain_pending_on_dqs_reject"	=> true,
	/* if there are updates pending on the current version of an object, we may not want to allow us to roll it back to an older version */
	"pending_updates_prevent_rollback" => false,
	/* if dqs rejects an update because there has been a version clash, do we want to save it in the object store as a deferred update? */	
	"rollback_updates_to_pending_on_version_reject" => true,
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
		//"options_target" => "#row-ldo-details-ldcontents th",
		//"ldimport_header" => "Import Contents"
	),
	/* the list of options that can be sent to api for create command */
	"create_user_options" => array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", 
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
		"meta" => array("label" => "Metadata", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
		"ldcontents" => array("type" => "placeholder", "label" => "Import Contents")
	),
		
	/* view screen * /
	
	/* contents tab */
	/* default options to be sent to api for update command */
	"update_user_options" => array(
		"ns", "addressable", "plain", "rollback_update_to_pending_on_dqs_reject", "show_dqs_triples", "show_ld_triples", 
		"fail_on_id_denied", "show_update_triples", "show_meta_triples", "show_result", "show_changed", "show_original"
	),
	"update_fixed_options" => array(),
	"update_default_options" => array("fail_on_id_denied" => 1),
	"test_update_fixed_options" => array(),
	"test_update_default_options" => array(
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

	/* Meta Tab */
	"update_meta_fields" => array(
		//"status" =>	array("label" => "Status", "help" => "The current status of the object", "type" => "status"),
		//"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the object on pages."),
		//"title" => array("label" => "Title", "length" => "long", "help" => "The full title of the object - may include spaces and punctuation."),
		//"url" => array("label" => "Canonical URL", "type" => "url", "help" => "The External URL which represents the 'canonical' id of this object (to support purls, etc)."),
		"meta" => array("label" => "Object Meta-data", "type" => "complex", "input_type" => "custom", "help" => "Arbitrary json meta-data that is associated with the object"),
	),
	
	/* Analysis Tab */
	"ldo_analysis_fields" => array(
		"imports" => array("label" => "Imported Ontologies", "type" => "complex", "input_type" => "custom", "help" => "The ontologies that will be imported by this ontology"),
		"schema_imports" => array("label" => "Used Ontologies", "type" => "complex", "input_type" => "custom", "help" => "The ontologies used by this ontology"),
		"dqs_tests" => array("label" => "DQS Tests", "type" => "complex", "input_type" => "custom", "help" => "The DQS tests that will be opened.")
	),
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
		"list_objects_intro" => "",
		"list_updates_intro" => "",
		"view_history_intro" => "",
		"view_updates_intro" => "",
		"updates_screen_title" => "Updates",
		"history_screen_title" => "History",
		"contents_screen_title" => "Contents",
		"analysis_screen_title" => "Analysis",
		"meta_screen_title" => "Metadata",
		"view_contents_intro" => "View the RDF contents of this LDO",
		"view_meta_intro" => "View the LDO's meta-data",
		"create_button_text" => "Create New LDO",
		"test_create_button_text" => "Test LDO Creation",
		"update_meta_button" => "Save Updated Metadata",
		"test_update_meta_button" => "Test Metadata Update",
		"test_create_button_text" => "Test Creation",
		"testraw_edit_text" => "Test LDO Update",
		"view_update_contents_intro" => "The contents of the update to the LDO",
		"view_update_meta_intro" => "The LDO update's meta data",
		"view_update_analysis_intro" => "The LDO update's analysis",
		"update_raw_intro_msg" => "The LDO update's raw editing interface",
		"view_update_after_msg" => "The LDO after the update has taken place",
		"view_update_before_msg" => "The LDO before the update has taken place",
	),
);