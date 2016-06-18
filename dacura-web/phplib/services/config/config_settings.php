<?php
/**
 * The settings for the config Service
 * @author chekov
 * @package users
 * @license GPL V2
 */
$settings = array(
	/* standard fields for services */
	"service-title" => "Configuration Manager",
	"service-button-title" => "Configuration",
	"service-description" => "View and update the settings of the platform and its collections",
	/* the list of all facets supported by the service */
	"facet-list" => array("admin" => "Administer Collection", "manage" => "Manage collection", "inspect" => "Auditing", "view" => "view details"),
	"default_status" => "pending",
	"default_profile" => array(),
	"default_collection_config" => array(),
	"collection_paths_to_create" => array("logs", "cache", "dumps", "files", "sessions"),
	"messages" => array(
		"system-view-files-intro" => "Upload and manage images and other server files",
		"system-view-logs-intro" => "Keep track of all the activity on the platform",
		"system-system-configuration-intro" => "The basic settings that define the platform's installation context",
		"system-view-services-intro" => "Configure the various services provided to users by the platform",
		"system-list-collections-intro" => "Access to the configurations of the individual collections hosted by the platform",
		"system-add-collection-intro" => "Choose an id (a short, lowercase, word, which will appear in the collection's URL and title for the new collection), then click the button below",
		"collection-view-files-intro" => "Upload and manage your collection's images and other files",
		"collection-view-logs-intro" => "Keep track of all the activity within your collection with real time logging",
		"collection-system-configuration-intro" => "The basic settings that define the collection",
		"collection-view-services-intro" => "Configure the various services provided to users by the collection",
	),
	"tables" => array(
		"collections" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 25)),
		"services" => array("datatable_options" => array("jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 25)),
		"logs" => array("datatable_options" => array("order"=> array(0, "desc"), "jQueryUI" => true, "searching" => false, "info" => true, "pageLength" => 50))
	),
	"create_collection_fields" => array(
		"id" => array("id" => "id", "label" => "ID", "length" => "short", "help" =>"The collection id is a short (minimum 3 characters, maximum 40 characters) alphanumeric string [a-z_0-9] which will identify the collection. It will appear in URLs so brevity is key."),
		"title"	=> array("id" => "title", "label" => "Title", "help" => "The full title of the data collection - may include spaces and punctuation."),				
	),
	/* collection configuration fields that will appear on the collection configuration form */	
	"update_collection_fields" => array(
		"name" => array("label" => "Title", "help" => "The full title of the data collection - may include spaces and punctuation."),
		"status" =>	array("label" => "Status", "help" => "The current status of the collection", "type" => "status"),
		"image" => array("type" => "image", "label" => "Image", "help" => "An image which will represent the collection on pages."),
		"background" => array("type" => "image", "label" => "Background Image", "help" => "An image which will be the background of the collection's pages."),		
		"icon" => array("type" => "image", "label" => "Icon", "help" => "An icon (16 x 16 pixels) which will represent the collection in menus, etc."),
		"collection_url" => array("label" => "Collection Home", "type" => "url", "help" => "The Dacura URL for the collection"),		
		"collection_path" => array("label" => "Collection Storage", "help" => "The directory in which the collection's files are stored"),		
	),
	/* System configuration fields that will appear on the collection / system configuration form */
	"sysconfig_form_fields" => array(
		"install_url" => array("disabled" => true, "label" => "Dacura Base URL", "type" => "url", "help" => "The URL that Dacura is installed at. All Dacura URLs will be relative to this base."),
		"storage_base" => array("disabled" => true, "label" => "Storage Base", "help" => "The directory where the server stores its data."),
		"dqs_url" => array("label" => "Dacura Quality Service URL", "type" => "url", "help" => "The Dacura Quality Service has an independent endpoint from the rest of Dacura"),
		"dacurauser" => array("label" => "Dacura Session User", "type" => "text", "length" => "short", "help" => "The name of the dacura user object in the user's php session - it only has to  be changed to allow multiple dacuras to be installed on the same server."),				
		"db" => array("label" => "Database Settings", "disabled" => true, "type" => "section", "help" => "Access information for the database where Dacura will store its information."),				
		"host" => array("label" => "Hostname", "type" => "text", "length" => "short"),				
		"dbname" => array("id" => "name", "disabled" => true, "label" => "Name", "type" => "text", "length" => "short"),				
		"user" => array("disabled" => true, "label" => "Username", "type" => "text", "length" => "short"),				
		"pass" => array("disabled" => true, "label" => "Password", "type" => "text", "length" => "short"),	
		"apistr" => array("label" => "API url offset", "type" => "text", "length" => "tiny", "help"=>"The URL path used to route requests to the ajax api rather than the html page."),			
		"path_to_files" => array("label" => "Path to media files", "type" => "text", "length" => "tiny", "help"=>"The URL directory used to store built-in Dacura media files, etc."),			
		"path_to_services" => array("label" => "Path to service files", "type" => "text", "length" => "short", "help"=>"The URL directory where services live."),			
		"dump_directory" => array("label" => "Dump Directory", "type" => "text", "length" => "short", "help"=>"The directory in which raw output files will be dumped."),			
		"files_directory" => array("label" => "Files Directory", "type" => "text", "length" => "short", "help"=>"The directory in which files will be stored."),			
		"cache_directory" => array("label" => "Cache Directory", "type" => "text", "length" => "short", "help"=>"The directory in which cache files will be stored."),			
		"default_cache_config" => array("label" => "Default Cache Configuration", "type" => "complex", "help"=>"The default configuration of new cache stores."),
		"performance_timing" => array("label" => "Measure Performance", "type" => "choice", "options" => array( "0"=> "off", "1" => "measure request", "2" => "fine-tuning"), "help"=>"Turn on to create a measure of performance in the server logs"),				
		"request_log_level" => array("label" => "Request Log Level", "type" => "choice", "options" => RequestLog::$log_levels, "help"=>"Controls the volume of logs that dacura records"),				
		"system_log_level" => array("label" => "Event Log Level", "type" => "choice", "options" => RequestLog::$log_levels, "help"=>"Controls the volume of logs that dacura records"),				
		"filebrowser" => array("label" => "Filebroswer directory", "type" => "text", "help"=>"Path to the file browser plugin."),				
		"dacura_logbase" => array("label" => "Log Directory", "help" => "Absolute path of directory where dacura keeps its logs", "type" => "text"),				
		"dacura_request_log" => array("label" => "Request Logfile", "help" => "Absolute path of file where dacura keeps its request logs", "type" => "text"),				
		"dacura_system_log" => array("label" => "Event Log", "help" => "Absolute path of file where dacura keeps its event logs", "type" => "text"),				
		"services_url" => array("label" => "URL of dacura services", "help" => "URL for accessing dacura's services", "type" => "url"),				
		"dqs_service" => array("label" => "URL of DQS methods", "hidden" => true, "type" => "section"),				
		"instance" => array("label" => "Instance API", "type" => "url"),				
		"schema" => array("label" => "Schema API", "type" => "url"),				
		"schema_validate" => array("label" => "Schema Validate API", "type" => "url"),				
		"validate" => array("label" => "Validate API", "type" => "url"),				
		"stub" => array("label" => "Frame API", "type" => "url"),				
		"logfile" => array("label" => "DQS Logfile", "type" => "text"),				
		"fakets" => array("label" => "Fake Triplsetore path", "type" => "text", "help" => "A path to a file that will be used as a fake triplestore (just a file)."),				
		"dumplast" => array("label" => "Last Request Dump File", "type" => "text", "help" => "A path to a file that will be used to dump the last request sent to the DQS."),				
		"mail_headers" => array("label" => "Mail Headers", "type" => "text", "input_type" => "textarea", "help" => "Mailheaders that will be sent with outward mail."),				
		"dacura_sessions" => array("label" => "Sessions Directory", "type" => "text", "help" => "Directory in which PHP will store its sessions."),				
		"ajaxurl" => array("label" => "Ajax URL", "type" => "url", "help" => "Base URL for all Dacura AJAX / API calls."),
		"files_url" => array("label" => "Media Files URL", "type" => "url", "help" => "URL where Dacura serves its media files."),
		"path_to_collections" => array("label" => "Path to collections' file storage area", "type" => "text"),
		"url_mappings" => array("type" => "complex", "label" => "URL Mappings", "help" => "A list of urls that will always be mapped to alternatives."),
		"collections_urlbase" => array("label" => "URL of collections' web-files", "type" => "text"),
	),
	/* field settings that apply to all services */
	"service_form_fields" => array(
		"status" =>	array("label" => "Status", "help" => "Is the service currently enabled?", "type" => "choice", "options" => array("enable" => "Enabled", "disable" => "Disabled")),			
		"facets" =>	array("label" => "Access Control", "input_type" => "custom", "extras" => array(), "help" => "What facets of the service are available to which users", "type" => "complex"),			
		"facet-list" =>	array("label" => "Access Control Facets", "hidden" => true, "disabled" => true),
		"service-title" => array("hidden" => true),
		"service-button-title" => array("hidden" => true),
		"status_locked" => array("hidden" => true),
		"service-description" => array("hidden" => true),				
		"config_form_fields" => array("hidden" => true, "type" => "complex", "label" => "This array!"),
	),
	/* field settings that will appear on configuration form of this service */
	"config_form_fields" => array(
		"default_status" => array("label" => "Default status of new collections", "type" => "status"),
		"service_form_fields" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"create_collection_fields" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"update_collection_fields" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"sysconfig_form_fields" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"forms" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"tables" => array("hidden" => true, "type" => "complex", "label" => "config stuff"),
		"default_profile" => array("type" => "complex", "label" => "Default User Profile"),
		"default_collection_config" => array("type" => "complex", "label" => "Default Collection Configuration"),
		"collection_paths_to_create" => array("type" => "complex", "label" => "Paths to create for new collections"),
		"messages" => array("type" => "section", "label" => "Text messages that will be reported to the user"),
		"settings_intro" => array("type" => "text", "label" => "Configuration Welcome Message", "help" => "Text that appears on the top of the configuration front page"),		
	),		
);
