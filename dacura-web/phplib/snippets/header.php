<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<title>Dacura</title>
<link rel="shortcut icon" href="<?=$service->url("images", "favicon2.ico")?>" />
<link href='http://fonts.googleapis.com/css?family=Open+Sans:300,600' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->settings['files_url']?>css/master.css" />
<script src="<?=$service->url("js", "jquery-1.9.1.min.js")?>"></script>
<script src="<?=$service->settings['services_url']?>core/dacura.js"></script>
<script>
<?php include_once($service->settings['path_to_services']."core/dacura.system.js"); //included for variable extrapolation?>
</script>
<script src="<?=$service->url("js", "jquery-ui.js")?>"></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery-ui.css")?>" />
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
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

