<?php $bgimage = isset($params['bgimage']) ? $params['bgimage'] : $service->url("image", "Trinity-college-library-dub2.jpg");?>
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
		<script src="<?=$service->url("js", "jquery-2.1.4.min.js")?>"></script>
		<script src="<?=$service->url("js", "jquery-ui.js")?>"></script>
		<script>
			<?php include_once($service->settings['path_to_services']."core/dacura.init.js"); //included for variable interpolation ?>
		</script>
		<script src="<?=$service->get_service_script_url("dacura.js", "core")?>"></script>
		<style>
		#content-container {
		  background: url('<?=$bgimage?>') no-repeat center center fixed;
		  filter: "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?=$bgimage?>', sizingMethod='scale')";
  		  -ms-filter: "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='<?=$bgimage?>', sizingMethod='scale')";
		  -webkit-background-size: cover;
  		  -moz-background-size: cover;
  	      -o-background-size: cover;
  			background-size: cover;
		  
		}		
		</style>
	</head>
<body>
<!-- The dacura modal dialogue for communicating important messages to the user -->
<div id="dacura-modal" style="display: none">This should be invisible</div>
<div id="content-container">
