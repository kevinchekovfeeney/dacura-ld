<?php 
//create candidate
$settings["console_create_candidate_user_options"] = array(
	"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
	"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
/* options that are fixed to a particular value for every create command (allows us to limit api selectively) */
$settings["console_create_candidate_fixed_options"] = array();
$settings["console_create_candidate_default_options"] = array("fail_on_id_denied" => 1);

$settings["console_test_create_candidate_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
$settings["console_test_create_candidate_fixed_options"] = array("ns" => 1, "plain" => 1);
$settings["console_test_create_candidate_default_options"] = array("show_result" => 1);

/* update candidate */
$settings["console_update_candidate_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
/* options that are fixed to a particular value for every update command (allows us to limit api selectively) */
$settings["console_update_candidate_fixed_options"] = array();
$settings["console_update_candidate_default_options"] = array("fail_on_id_denied" => 1);

$settings["console_test_update_candidate_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
$settings["console_test_update_candidate_fixed_options"] = array("ns" => 1, "plain" => 1);
$settings["console_test_update_candidate_default_options"] = array("show_result" => 1);

/* update ontology */
$settings["console_update_ontology_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
$settings["console_update_ontology_fixed_options"] = array();
$settings["console_update_ontology_default_options"] = array("fail_on_id_denied" => 1);
$settings["console_test_update_ontology_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
$settings["console_test_update_ontology_fixed_options"] = array("ns" => 1, "plain" => 1);
$settings["console_test_update_ontology_default_options"] = array("show_result" => 1);

/* update graph */
$settings["console_update_graph_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
$settings["console_update_graph_fixed_options"] = array();
$settings["console_update_graph_default_options"] = array("fail_on_id_denied" => 1);

$settings["console_test_update_graph_user_options"] = array(
		"ns", "addressable", "plain", "analysis", "fail_on_id_denied", "show_dqs_triples",
		"rollback_ldo_to_pending_on_dqs_reject", "show_ld_triples", "show_result", "show_meta_triples"
);
$settings["console_test_update_graph_fixed_options"] = array("ns" => 1, "plain" => 1);
$settings["console_test_update_graph_default_options"] = array("show_result" => 1);


/* options for retrieving things from api */
$settings["console_view_user_args"] = array("version", "ldtype", "format");
/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
$settings["console_view_fixed_args"] = array("format" => "json");
$settings["console_view_default_args"] = array("format" => "json");
$settings["console_view_user_options"] = array("ns", "addressable", "plain");
/* fill in to set some filters to fixed - they are always a particular value, irrespective of user input */
$settings["console_view_fixed_options"] = array("plain" => 1, "ns" => 1);
$settings["console_view_default_options"] = array("plain" => 1, "ns" => 1);

