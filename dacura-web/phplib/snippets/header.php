<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<title>Dacura</title>
<link rel="shortcut icon" href="<?=$service->url("images", "favicon2.ico")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->settings['files_url']?>css/master.css" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "opensans.css")?>" />
<script src="<?=$service->url("js", "jquery-1.9.1.min.js")?>"></script>
<script src="<?=$service->settings['services_url']?>core/dacura.js"></script>
<script>
<?php include_once($service->settings['path_to_services']."core/dacura.system.js"); //included for variable extrapolation?>
</script>
<script src="<?=$service->url("js", "jquery-ui.js")?>"></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery-ui.css")?>" />

<script>
$( document ).ready(function() {
	//$('#dacura-modal').dialog( {modal: true, autoOpen: false});
});
</script>
</head>
<body>
<!-- The dacura modal dialogue for communicating important messages to the user -->
<div id="dacura-modal" style="display: none">This should be invisible</div>
<div id="content-container">

