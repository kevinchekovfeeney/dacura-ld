<?php
$install_base = $dacura_settings['install_url'];
$service_base = $dacura_settings['services_url']."candidate_viewer/";
$file_base = $dacura_settings['files_url'];


?>
<!DOCTYPE html>
<html>
<head>
<meta charset=utf-8 />

<title>Dacura</title>
<link rel="shortcut icon" href="<?=$install_base?>media/images/favicon2.ico" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service_base?>candidate_viewer.css" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$file_base?>css/jquery-ui-1.10.2.custom.min.css" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$file_base?>css/tool.css" />
<script src="<?=$install_base?>media/js/jquery.js"></script>
<script src="<?=$install_base?>media/js/dacura.js"></script>
<script src="<?=$install_base?>media/js/widget.js"></script>
<script src="<?=$install_base?>media/js/jquery.panzoom.min.js"></script>

<script src="<?=$install_base?>media/js/jquery-ui-1.10.2.custom.min.js"></script>
<!--[if IE]>
<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
<![endif]-->
</head>
<body>
