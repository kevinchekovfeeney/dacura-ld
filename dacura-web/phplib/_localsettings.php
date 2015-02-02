<?php
/*
 * Upon installation, set the configuration values below to reflect the installation environment
 * And save this file as localsettings.php
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 15/01/2015
 * Licence: GPL v2
 */


//URL at which the dacura system is accessible
$dacura_settings['install_url'] = "http://localhost/dacura/";
//URL of the system's triple store...
$dacura_settings['sparql_url'] = "http://localhost/sparql/";
//directory under which dacura will store its data...
$dacura_settings['storage_base'] = "/var/dacura/";
//DB credentials
$dacura_settings['db_host'] = 'localhost';
$dacura_settings['db_name'] = 'dacura';
$dacura_settings['db_user'] = 'dacura';
$dacura_settings['db_pass'] = 'dacura';
//HTTP proxy
//$dacura_settings['http_proxy'] = "http://proxy.cs.tcd.ie:8080";
//change this to something distinct if multiple dacuras are running at the same url in different sub-dirs
$dacura_settings['dacurauser'] = 'dacurauser';
