<?php
/** 
 * Settings for Browse Service
 * 
 * You must look at the file to see what the settings are.  It's easier that way, trust me. 
 * @package browse
 * @author chekov
 * @license GPL V2
 */
$settings = array(
	"facet-list" => array("view" => "Browse the collection"),
	"service-title" => "Collection browsing service",
	"service-description" => "The browse service provides users with a means of browsing data and services in the collection",
	"services" => array (
		"internal" => array(
			"config" => array(
				"role" => array("admin"),
				"title" => "settings",
				"help" => "View and update the configuration of the ENTITY"								
			),
			"widget" => array(
				"role" => array("admin"),
				"title" => "widgets",
				"help" => "Create and manage user interfaces, tools and forms for managing your data"								
			),
			"users" => array(
				"role" => array("admin"),
				"title" => "users",
				"help" => "Manage the users of the ENTITY"								
			),
			"task" => array(
				"role" => array("admin", "all"),
				"title" => "tasks",
				"help" => "Manage the tasks to be carried out on your data"								
			),				
		),
		"data" => array(
			"import" => array(
				"role" => array("admin", "all"),
				"title" => "import",
				"help" => "Import data into your dataset from elsewhere"
			),
			"candidate" => array(
				"role" => array("admin"),
				"title" => "data",
				"help" => "View and update the data in your dataset."
			),
			"schema" => array(
				"role" => array("user"),
				"title" => "schema",
				"help" => "Manage the structure and organisation of your dataset"
			),
			"publish" => array(
				"role" => array("admin", "all"),
				"title" => "publish",
				"help" => "Publish and share your data in a wide range of ways"
			)
		),
		"tool" => array(
			"ld" => array(
				"title" => "Linked Data Browser",
				"help" => "Direct, low-level access to all of the Linked Data Objects in the ENTITY",								
				"role" => array("admin", "all")
			),
			"scraper" => array(
				"title" => "Seshat Scraper",
				"help" => "A tool for extracting data from the Seshat wiki and converting it into a ",								
				"role" => array("user", "seshat")
			)
		)
	),
	"config_form_fields" => array(
		"services" => array("hidden" => true, "type" => "complex", "label" => "Configuration of services - needs to be redone"),
		"config_form_fields" => array("hidden" => true, "type" => "complex", "label" => "This array!"),
				
	),	
		
);
