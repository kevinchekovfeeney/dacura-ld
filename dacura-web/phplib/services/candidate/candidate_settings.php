<?php 
include_once "phplib/services/ld/ld_settings.php";
//$settings['create_dqs_instance_tests'] = array("notInverseFunctionalPropertyIC");
$settings['require_candidate_type'] = true;
$settings['ignore_graph_fail'] = false;
$settings['rollback_on_graph_fail'] = true;

$settings["messages"]["view_frame_intro"] = "This is the frame-based view of the Candidate";
//$settings["create_options"] = array("show_dqs_triples" => 1, "ns" => 1, "addressable" => 1, "analysis" => 1,
//		"show_ld_triples" => 1, "fail_on_id_denied" => 1, "show_result" => 1);
//$settings["update_options"] = array("show_dqs_triples" => 1, "show_ld_triples" => 1, "fail_on_id_denied" => 1,
//				"show_update_triples" => 1, "show_meta_triples" => 1, "show_result" => 2,
//				"show_changed" => 1, "show_original" => 1, "ns" => 1, "addressable" => 1);
