<?php
$dacura_settings = array();
$dacura_settings['install_url'] = "http://localhost/dacura/";

//Internal Dacura URLs - offset from install URL
$dacura_settings['ajaxurl'] = $dacura_settings['install_url'] ."api/";
$dacura_settings['files_url'] = $dacura_settings['install_url'] . "media/";
$dacura_settings['path_to_collections'] = "collections/";
$dacura_settings['collections_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_collections'];
$dacura_settings['path_to_services'] = "phplib/services/";
$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];

//SPARQL Endpoints
$dacura_settings['sparql_source'] = "http://tcdfame.cs.tcd.ie:3030/politicalviolence/query";
//$dacura_settings['id_prefix'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv/";
//$dacura_settings['schema_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence";
//$dacura_settings['base_class'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence#Report";
//$dacura_settings['data_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv";

//Storage for session information
$dacura_settings['candidate_store'] = 'C:\\xampp\\htdocs\\dacura\\candidate_store\\';
$dacura_settings['dacura_sessions'] = 'dacura_sessions/'; 

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
