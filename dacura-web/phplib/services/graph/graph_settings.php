<?php 
include_once "phplib/services/ld/ld_settings.php";

$settings['validate_on_create'] = true;
$settings['create_dqs_schema_tests'] = array();
$settings['create_dqs_instance_tests'] = array();
$settings['dqs_invalid_status'] = "pending";
$settings['two_tier_schemas'] = false;
$settings['ldo_allow_demand_id'] = true;
$settings["replace_blank_ids"] = false;
$settings["create_options"] = array("show_dqs_triples" => 0,
		"ns" => 1, "addressable" => 1, "analysis" => 1,
		"show_ld_triples" => 1, "fail_on_id_denied" => 1, "show_result" => 1);
$settings["update_options"] = array("show_dqs_triples" => 1, "show_ld_triples" => 0, "fail_on_id_denied" => 1,
		"show_update_triples" => 1, "show_meta_triples" => 0, "show_result" => 2,
		"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1);
