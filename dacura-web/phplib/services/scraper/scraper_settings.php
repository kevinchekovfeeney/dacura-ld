<?php
$settings = array(
	"cookiejar" => $dacura_settings['storage_base']."cookiejar.txt",
	"use_cache" => true,
	"username" => 'Gavin',
	"password" => 'cheguevara',
	"loginUrl" => 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin',
	"mainPage" => 'http://seshat.info/Main_Page',
	"codeBook" => 'http://seshat.info/Code_book',
		"grabScriptFiles" => array(
			//$dacura_settings['path_to_files']."js/jquery.js", 
			//$dacura_settings['path_to_files']."js/jquery-ui.js"	
	),
	"dump_format" => "csv",
	"cache_config" => array( "type" => "time", "value" => 10000),
	"ngacache_config" => array( "type" => "constant"),
	"indexcache_config" => array( "type" => "constant")
		
		
);