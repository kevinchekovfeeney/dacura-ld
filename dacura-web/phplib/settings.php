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

//$dacura_settings['collections_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_collections'];
$dacura_settings['path_to_services'] = "phplib/services/";
$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];

$dacura_settings['path_to_collections'] = $dacura_settings['storage_base'] . "collections/";


/*
 * The file system paths where the various types of output produced by dacura are stored...
 */
$dacura_settings['dacura_sessions'] = $dacura_settings['storage_base'].'sessions/';
$dacura_settings['dacura_logbase'] = $dacura_settings['storage_base'].'logs/';
$dacura_settings['dacura_request_log'] = $dacura_settings['dacura_logbase'].'request.log';
$dacura_settings['dacura_system_log'] = $dacura_settings['dacura_logbase'].'event.log';

/*
 * Dumping and caching...
 */
$dacura_settings['dump_directory'] = "dumps/";
$dacura_settings['files_directory'] = "files/";
$dacura_settings['cache_directory'] = "cache/";
$dacura_settings['default_cache_config'] = array( "type" => "time", "value" => 10000);
$dacura_settings['performance_timing'] = 2;
$dacura_settings['request_log_level'] = "debug";
$dacura_settings['system_log_level'] = "debug";

/*
 * Quality Service API
 */

$dacura_settings['dqs_service'] = array(
		"instance" => $dacura_settings['dqs_url']."instance",
		"schema" => $dacura_settings['dqs_url']."schema",
		"schema_validate" => $dacura_settings['dqs_url']."schema_validate",
		"validate" => $dacura_settings['dqs_url']."validate",
		"stub" => "http://192.168.1.14:3020/dacura/stub",
		"entity" => "http://192.168.1.14:3020/dacura/entity",
		"logfile" => false,
		"fakets" => $dacura_settings['dacura_logbase'].'fakets.json',
		"dumplast" => $dacura_settings['dacura_logbase'].'lastdqs.log'
);

/*
 * Mail
 */
$dacura_settings['mail_headers'] = 	
		'From: dacura@scss.tcd.ie' . "\r\n" .
		'Reply-To: dacura@scss.tcd.ie' . "\r\n" .
		'X-Mailer: Dacura PHP/' . phpversion();