<?php
/*
 * Users Service
 */
$settings = array(
	"default_status" => "pending",
	"default_profile" => array(),
	"default_collection_config" => array("a" => "b"),
	"default_dataset_config" => array(),
	"collection_paths_to_create" => array("logs", "cache", "dumps", "files"),
	"dataset_paths_to_create" => array("logs", "cache", "dumps", "files"),
	"messages" => array(
		"settings_intro" => "Update the settings of the collection",
		"dataset_intro" => "Add a dataset to the collection (a dataset is a sub-division of a collection that can have its own settings)" 			 
	),
	"tables" => array(
		"collections" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50, 
			"aoColumns" => array(null, null, null, array("bVisible" => false)))),
		"logs" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50))
	),
	"forms" => array(
		"sys" => array("install_url", "storage_base"),
		"ccf" => array("id", "title"),
		"ucf" => array("did", "name", "image", "background", "description", "icon", "status"),
		"cdf" => array("collection_url", "collection_path", "instance_idbase", "graph_idbase", "dqs_url")
	),
	"form_fields" => array(
		"install_url" => array("label" => "Dacura Base URL", "type" => "url", "disabled" => true, "help" => "The URL that Dacura is installed at. All Dacura URLs will be relative to this base."),
		"storage_base" => array("label" => "Storage Base", "disabled" => true, "help" => "The directory where the server stores its data."),
		"id" => array("label" => "ID", "length" => "short", "help" =>"The collection id is a short (minimum 3 characters, maximum 40 characters) alphanumeric string [a-z_0-9] which will identify the collection. It will appear in URLs so brevity is key."),
		"did" => array("label" => "ID", "id" => "id", "disabled" => true, "length" => "short", "help" =>"The collection id is a short (minimum 3 characters, maximum 40 characters) alphanumeric string [a-z_0-9] which will identify the collection. It will appear in URLs so brevity is key."),
		"title"	=> array("label" => "Title", "help" => "The full title of the data collection - may include spaces and punctuation."),	
		"name" => array("label" => "Title", "help" => "The full title of the data collection - may include spaces and punctuation."),	
		"image" => array("label" => "Image", "help" => "An image which will represent the collection on pages."),	
		"background" => array("label" => "Background Image", "help" => "An image which will be the background of the collection's pages."),	
		"description" => array("label" => "Description", "help" => "A brief description of the collection - what it is for, who it is.  This will appear on pages where collections are listed"),	
		"icon" => array("label" => "Icon", "help" => "An icon (16 x 16 pixels) which will represent the collection in menus, etc."),
		"status" =>	array("label" => "Status", "help" => "The current status of the collection", "type" => "status"),
		"collection_url" => array("label" => "Collection Home", "type" => "url", "help" => "The Dacura URL for the collection"),		
		"collection_path" => array("label" => "Collection Storage", "help" => "The directory in which the collection's files are stored"),		
		"instance_idbase" => array("label" => "Instance Data ID prefix", "type" => "url", "help" => "This URL will be prepended to the internal IDs of all collection instance data to make a global id"),		
		"graph_idbase" => array("label" => "Named Graph ID prefix", "type" => "url", "help" => "This URL will be prepended to the internal IDs of all collection named graphs to make a global id"),		
		"dqs_url" => array("label" => "Dacura Quality Service URL", "type" => "url", "help" => "The Dacura Quality Service has an independent endpoint from the rest of Dacura"),		
	)
);
