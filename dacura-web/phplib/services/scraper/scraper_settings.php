<?php
$settings = array(
	"cookiejar" => $dacura_settings['storage_base']."cookiejar.txt",
	"use_cache" => true,
	"username" => 'gavin',
	"password" => 'cheguevara',
	"loginUrl" => 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin',
	"mainPage" => 'http://seshat.info/Main_Page',
	"grabScriptFiles" => array(
			$dacura_settings['path_to_files']."js/jquery-ui-1.10.2.custom.min.js"
	),
	"cache_config" => array( "type" => "time", "value" => 10000)
);