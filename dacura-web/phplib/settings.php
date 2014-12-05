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
$dacura_settings['install_url'] = "http://localhost/dacura/";
$dacura_settings['log_url'] = "http://localhost/logs/";

//Internal Dacura URLs - offset from install URL
$dacura_settings['apistr'] = "rest";
$dacura_settings['ajaxurl'] = $dacura_settings['install_url'] .$dacura_settings['apistr'] . "/";
$dacura_settings['path_to_files'] = "media/";
$dacura_settings['files_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_files'];
$dacura_settings['path_to_collections'] = "collections/";
$dacura_settings['collections_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_collections'];
$dacura_settings['path_to_services'] = "phplib/services/";
$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];

//SPARQL Endpoints
$dacura_settings['sparql_source'] = "http://tcdfame.cs.tcd.ie/sparql/politicalviolence/query";
//$dacura_settings['id_prefix'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv/";
//$dacura_settings['schema_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence";
//$dacura_settings['base_class'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence#Report";
//$dacura_settings['data_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv";

//Storage for session information, etc
//$dacura_settings['candidate_store'] = '/storage/ukipv/candidate_images';
//$dacura_settings['dacura_sessions'] = '/var/dacura/sessions/'; 
//$dacura_settings['dacura_logbase'] = '/var/dacura/logs/';
//Storage for session information, etc
$dacura_settings['candidate_store'] = 'C:\\xampp\\htdocs\\dacura\\candidate_store\\';
$dacura_settings['dacura_sessions'] = 'C:\\Temp\\dacura\\sessions\\';
$dacura_settings['dacura_logbase'] = 'C:\\Temp\\dacura\\logs\\';


//DB credentials
$dacura_settings['db_host'] = 'localhost';
$dacura_settings['db_name'] = 'dacura';
$dacura_settings['db_user'] = 'dacura';
$dacura_settings['db_pass'] = 'dacura';
//Miscellaneous...
$dacura_settings['candidate_images'] = "http://tcdfame.cs.tcd.ie/dacura/web/candidate_images/";
//$dacura_settings['http_proxy'] = "http://proxy.cs.tcd.ie:8080";
$dacura_settings['register_email_subject'] = "Registration for Dacura System";
$dacura_settings['lost_email_subject'] = "Password Reset for Dacura System";
$dacura_settings['tool_id'] = "simple";
