<?php
/**
 * Array definining the configuration of this Dacura Server Instance.  
 * 
 * None of these settings should need to be changed to install the server in a new place
 * The settings in localsettings.php are what needs to be changed
 *
 * @author Chekov
 * @license GPL v2
 */

$dacura_settings = array();
include("localsettings.php");

/* The rest is just offsets and defaults - it should work fine with them */

//Internal Dacura URLs - offset from install URL
$dacura_settings['apistr'] = "rest";
//the url used to access the api for service ajax calls 
$dacura_settings['path_to_files'] = "media/";

$dacura_settings['path_to_services'] = "phplib/services/";

/* The file system paths where the various types of output produced by dacura are stored... */

/* Dumping and caching...*/
$dacura_settings['dump_directory'] = "dumps/";
$dacura_settings['files_directory'] = "files/";
$dacura_settings['cache_directory'] = "cache/";
$dacura_settings['default_cache_config'] = array( "type" => "time", "value" => 10000);
$dacura_settings['performance_timing'] = 2;
$dacura_settings['request_log_level'] = "debug";
$dacura_settings['system_log_level'] = "debug";
$dacura_settings['filebrowser'] = "phplib/libs/kcfinder/";
$dacura_settings['dacura_logbase'] = $dacura_settings['storage_base'].'logs/';
$dacura_settings['dacura_request_log'] = $dacura_settings['dacura_logbase'].'request.log';
$dacura_settings['dacura_system_log'] = $dacura_settings['dacura_logbase'].'event.log';
$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];
$dacura_settings['files_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_files'];


/* Quality Service API */
$dacura_settings['dqs_service'] = array();

/* Mail */
$dacura_settings['mail_headers'] = 	
		'From: dacura@scss.tcd.ie' . "\r\n" .
		'Reply-To: dacura@scss.tcd.ie' . "\r\n" .
		'X-Mailer: Dacura PHP/' . phpversion();


/**
 * Sets up the default system settings by building various other settings from the ones defined in localsettings.php
 * @param array $dacura_settings - a settings array
 */

if (!function_exists('default_settings')) {

	function default_settings(&$dacura_settings){
		if(!isset($dacura_settings['dacura_sessions'])){
			$dacura_settings['dacura_sessions'] = $dacura_settings['storage_base'].'sessions/';
		}
		if(!isset($dacura_settings['ajaxurl'])){
			$dacura_settings['ajaxurl'] = $dacura_settings['install_url'].$dacura_settings['apistr'] . "/";
		}
		if(!isset($dacura_settings['services_url'])){
			$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];
		}
		if(!isset($dacura_settings['path_to_collections'])){
			$dacura_settings['path_to_collections'] = $dacura_settings['storage_base']."collections/";
		}
		if(!isset($dacura_settings['collections_urlbase'])){
			$dacura_settings['collections_urlbase'] = $dacura_settings['install_url'] . "cfiles/";
		}
		if(!isset($dacura_settings['dacura_sessions'])){
			$dacura_settings['dacura_sessions'] = $dacura_settings['storage_base'].'sessions/';
		}
		if(!isset($dacura_settings['dqs_service'])){
			$dacura_settings['dqs_service'] = array();
		}
		if(!isset($dacura_settings['dqs_service']['instance'])){
			$dacura_settings['dqs_service']["instance"] = $dacura_settings['dqs_url']."instance";
		}
		if(!isset($dacura_settings['dqs_service']['schema'])){
			$dacura_settings['dqs_service']["schema"] = $dacura_settings['dqs_url']."schema";
		}
		if(!isset($dacura_settings['dqs_service']['schema_validate'])){
			$dacura_settings['dqs_service']["schema_validate"] = $dacura_settings['dqs_url']."schema_validate";
		}
		if(!isset($dacura_settings['dqs_service']['validate'])){
			$dacura_settings['dqs_service']["validate"] = $dacura_settings['dqs_url']."validate";
		}
		if(!isset($dacura_settings['dqs_service']['stub'])){
			$dacura_settings['dqs_service']["stub"] = $dacura_settings['dqs_url']."stub";
		}
		if(!isset($dacura_settings['dqs_service']['entity_frame'])){
			$dacura_settings['dqs_service']["entity_frame"] = $dacura_settings['dqs_url']."entity_frame";
		}
		if(!isset($dacura_settings['dqs_service']['class_frame'])){
			$dacura_settings['dqs_service']["class_frame"] = $dacura_settings['dqs_url']."class_frame";
		}
		if(!isset($dacura_settings['dqs_service']['entity'])){
			$dacura_settings['dqs_service']["entity"] = $dacura_settings['dqs_url']."entity";
		}
		if(!isset($dacura_settings['dqs_service']['logfile'])){
			$dacura_settings['dqs_service']["logfile"] = false;
		}
		if(!isset($dacura_settings['dqs_service']['fake'])){
			$dacura_settings['dqs_service']["fake"] = $dacura_settings['dacura_logbase'].'fakets.json';
		}
		if(!isset($dacura_settings['dqs_service']['dumplast'])){
			$dacura_settings['dqs_service']["dumplast"] = $dacura_settings['dacura_logbase'].'lastdqs.log';
		}
	}
}
