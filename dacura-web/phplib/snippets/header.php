<!DOCTYPE html>
<html>
	<head>
		<meta charset=utf-8 />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
		<title>Dacura</title>
		<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery-ui.css")?>" />
		<link rel="shortcut icon" href="<?=$service->url("images", "favicon2.ico")?>" />
		<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->get_service_file_url('master.css', "core")?>"/>
		<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "opensans.css")?>" />
		<script src="<?=$service->url("js", "jquery-1.9.1.min.js")?>"></script>
		<script>
			<?php include_once($service->settings['path_to_services']."core/dacura.js"); //included for variable interpretation?>
		</script>
		<script src="<?=$service->url("js", "jquery-ui.js")?>"></script>
	</head>
<body>
<!-- The dacura modal dialogue for communicating important messages to the user -->
<div id="dacura-modal" style="display: none">This should be invisible</div>
<div id="content-container">

