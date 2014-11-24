<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

<title>Dacura</title>
<link rel="shortcut icon" href="$service->settings['files_url']images/favicon2.ico" />
<link href='http://fonts.googleapis.com/css?family=Open+Sans:300,600' rel='stylesheet' type='text/css'>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->settings['files_url']?>css/master.css" />
<script src="<?=$service->settings['files_url']?>js/jquery.js"></script>
<script src="<?=$service->settings['services_url']?>core/dacura.js"></script>
<script>
<?php include_once($service->settings['path_to_services']."core/dacura.system.js");?>

dacura.system.mode = "local";
</script>
<script src="<?=$service->settings['files_url']?>js/jquery-ui-1.10.2.custom.min.js"></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->settings['files_url']?>css/jquery-ui-1.10.2.custom.min.css"" />
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
<div id="content-container">

