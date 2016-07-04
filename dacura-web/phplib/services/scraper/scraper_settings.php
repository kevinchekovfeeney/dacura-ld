<?php
$settings = array(
	"facet-list" => array(
			"list" => "View lists of imported data",
			"view" => "View imported data",
			"inspect" => "inspect imported data",
			"admin" => "Administer imports",
			"import" => "Import data",
			"export" => "Export data in batches",
			"manage" => "Update data"
	),
	"service-title" => "Scraper",
	"service-button-title" => "Scraper",
	"service-description" => "Scraping data from the web into Dacura",
	"use_cache" => true,
	"username" => 'Gavin',
	"password" => 'cheguevara',
	"loginUrl" => 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin',
	"mainPage" => 'http://seshat.info/Main_Page',
	"codeBook" => 'http://seshat.info/Code_book',
		"grabScriptFiles" => array(
			$dacura_settings['path_to_files']."js/jquery.js", 
			$dacura_settings['path_to_files']."js/jquery-ui.js"	
	),
	"dump_format" => "csv",
	"cache_config" => array( "type" => "time", "value" => 10000),
	"ngacache_config" => array( "type" => "constant"),
	"indexcache_config" => array( "type" => "constant")
);

$settings["cookiejar"] = "cookiejar.txt";
