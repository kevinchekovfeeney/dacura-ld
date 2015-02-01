<?php
/*
 * Users Service
 */
$settings = array(
		"default_status" => "new",
		"show_deleted_collections" => true,
		"show_deleted_datasets" => true,
		"default_profile" => array("dacura_home" => $dacura_settings['install_url']."seshat/all/welcome"),
		"collections_datatable_init_string" => '{ "searching": false }',
		"default_collection_config" => array("a" => true, "b" => false),
		"default_dataset_config" => array("a" => true, "b" => false),
		"collection_paths_to_create" => array("datasets", "logs", "cache", "dumps"),
		"dataset_paths_to_create" => array("schema", "logs", "cache", "dumps"),
);
