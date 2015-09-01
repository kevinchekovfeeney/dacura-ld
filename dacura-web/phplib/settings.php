<?php
/*
 * Array definining the configuration of this Dacura Server Instance.  
 * This is the one and only place where you need to make configuration changes to install the server in a new place
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 20/11/2014
 * Licence: GPL v2
 */
$dacura_settings = array();
include_once("localsettings.php");



/*
 * The rest is just offsets and defaults - it should work fine with them
 */

//Internal Dacura URLs - offset from install URL
$dacura_settings['apistr'] = "rest";
//the url used to access the api for service ajax calls 
$dacura_settings['ajaxurl'] = $dacura_settings['install_url'] .$dacura_settings['apistr'] . "/";
$dacura_settings['path_to_files'] = "media/";
$dacura_settings['files_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_files'];
$dacura_settings['path_to_collections'] = $dacura_settings['storage_base'] . "collections/";
//$dacura_settings['collections_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_collections'];
$dacura_settings['path_to_services'] = "phplib/services/";
$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];
$dacura_settings['dqs_service'] = array(
		"instance" => $dacura_settings['dqs_url']."instance", 
		"schema" => $dacura_settings['dqs_url']."schema", 
		"schema_validate" => $dacura_settings['dqs_url']."schema_validate", 
		"validate" => $dacura_settings['dqs_url']."validate"
);

/*
 * The file system paths where the various types of output produced by dacura are stored...
 */
$dacura_settings['dacura_sessions'] = $dacura_settings['storage_base'].'sessions/';
$dacura_settings['dacura_logbase'] = $dacura_settings['storage_base'].'logs/';
$dacura_settings['dacura_request_log'] = $dacura_settings['dacura_logbase'].'request.log';
$dacura_settings['dacura_system_log'] = $dacura_settings['dacura_logbase'].'event.log';
$dacura_settings['collections_base'] = $dacura_settings['storage_base']."collections/";

/*
 * Dumping and caching...
 */
$dacura_settings['dump_directory'] = "dumps/";
$dacura_settings['cache_directory'] = "cache/";
$dacura_settings['default_cache_config'] = array( "type" => "time", "value" => 10000);
$dacura_settings['performance_timing'] = 2;
$dacura_settings['request_log_level'] = "debug";
$dacura_settings['system_log_level'] = "debug";


/*
 * Users Service

$dacura_settings['users'] = array(
		"default_status" => "new",
		"show_deleted_users" => true,
		"default_profile" => array("dacura_home" => $dacura_settings['install_url']."seshat/all/welcome")
);
 */

/*
 * Scraper Service
 
$dacura_settings['scraper'] = array(
		"cookiejar" => "C:\\Temp\\dacura\\cookiejar.txt",
		"use_cache" => true, 
		"username" => 'gavin',
		"password" => 'cheguevara',
		"loginUrl" => 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin',
		"mainPage" => 'http://seshat.info/Main_Page', 
		"grabScriptFiles" => array(
				$dacura_settings['path_to_files']."js/jquery-ui-1.10.2.custom.min.js"
		)				
);
*/
//Miscellaneous stuff that does not belong here...
$dacura_settings['candidate_images'] = "http://tcdfame.cs.tcd.ie/dacura/web/candidate_images/";
//$dacura_settings['register_email_subject'] = "Registration for Dacura System";
//$dacura_settings['lost_email_subject'] = "Password Reset for Dacura System";
$dacura_settings['tool_id'] = "simple";
$dacura_settings['candidate_store'] = 'C:\\xampp\\htdocs\\dacura\\candidate_store\\';
//SPARQL Endpoints
//$dacura_settings['sparql_source'] = "http://tcdfame.cs.tcd.ie/sparql/politicalviolence/query";
//$dacura_settings['id_prefix'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv/";
//$dacura_settings['schema_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence";
//$dacura_settings['base_class'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence#Report";
//$dacura_settings['data_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv";

//Storage for session information, etc
//$dacura_settings['candidate_store'] = '/storage/ukipv/candidate_images';
//$dacura_settings['dacura_sessions'] = '/var/dacura/sessions/';
//$dacura_settings['dacura_logbase'] = '/var/dacura/logs/';
//Storage for session information, etc
