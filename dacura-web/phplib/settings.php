<?php
$dacura_settings = array();
$dacura_settings['sparql_source'] = "http://tcdfame.cs.tcd.ie:3030/politicalviolence/query";
$dacura_settings['id_prefix'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv/";
$dacura_settings['schema_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence";
$dacura_settings['base_class'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence#Report";
$dacura_settings['data_graph'] = "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv";
$dacura_settings['candidate_store'] = 'C:\\Temp\\dacura\\candidates\\'; 
$dacura_settings['dacura_sessions'] = 'C:\\Temp\dacura\\sessions\\'; 
$dacura_settings['db_host'] = 'localhost';
$dacura_settings['db_name'] = 'dacura';
$dacura_settings['db_user'] = 'root';
$dacura_settings['db_pass'] = 'badman';
$dacura_settings['ajaxurl'] = "http://localhost/fame/api/";
$dacura_settings['candidate_images'] = "http://tcdfame.cs.tcd.ie/dacura/web/candidate_images/";
$dacura_settings['http_proxy'] = "http://proxy.cs.tcd.ie:8080";
$dacura_settings['install_url'] = "http://localhost/fame/";
$dacura_settings['files_url'] = $dacura_settings['install_url'] . "media/";
$dacura_settings['path_to_collections'] = "collections/";
$dacura_settings['collections_url'] = $dacura_settings['install_url'] . $dacura_settings['path_to_collections'];
$dacura_settings['path_to_services'] = "phplib/services/";
$dacura_settings['services_url'] = $dacura_settings['install_url'].$dacura_settings['path_to_services'];
$dacura_settings['register_email_subject'] = "Registration for Dacura System";
$dacura_settings['lost_email_subject'] = "Password Reset for Dacura System";
$dacura_settings['tool_id'] = "simple";
