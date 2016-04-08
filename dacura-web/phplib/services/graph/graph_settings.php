<?php 
include_once "phplib/services/ld/ld_settings.php";

$settings['validate_on_create'] = true;
$settings['create_dqs_schema_tests'] = array();
$settings['create_dqs_instance_tests'] = array();
$settings['dqs_invalid_status'] = "pending";
$settings['two_tier_schemas'] = false;
$settings['ldo_allow_demand_id'] = true;
$settings["internal_allow_demand_id"] = true;
$settings["replace_blank_ids"] = false;
