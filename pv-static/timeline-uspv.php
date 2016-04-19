<html>
	<head>
		<title>USPV Timeline</title>
		<script src='media/js/jquery.js'></script>
		<script src="media/js/jquery-ui-1.10.2.custom.min.js"></script>
		<link href="media/css/bootstrap.min.css" rel="stylesheet" media="screen">
		<script src="media/js/bootstrap.js"></script>
		<link rel="stylesheet" href="media/css/timeline.css" type="text/css" charset="utf-8">
		<link rel="stylesheet" href="media/css/timeglider/jquery-ui-1.10.3.custom.css" type="text/css" charset="utf-8">
		<link rel="stylesheet" href="media/css/timeglider/Timeglider.css" type="text/css" charset="utf-8">
		<link rel="stylesheet" href="media/css/timeglider/timeglider.datepicker.css" type="text/css" charset="utf-8">
		<script src="media/js/timeglider/underscore-min.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/backbone-min.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/json2.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/jquery.tmpl.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/ba-tinyPubSub.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/jquery.mousewheel.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/jquery.ui.ipad.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/globalize.js" type="text/javascript" charset="utf-8"></script>	
		<script src="media/js/timeglider/ba-debug.min.js" type="text/javascript" charset="utf-8"></script>
		<script src="media/js/timeglider/timeglider-1.0.1.min.js" type="text/javascript" charset="utf-8"></script>
	</head>
	<body>
		<div class="row-fluid" id="header">
			<div class="span1 offset1 logo">
				<img src="media/images/logo.png"  width="100" height="100" alt="Dacura Logo" />
			</div>
			<div class="span8 title">
				<ul class="breadcrumb">
					<li><a href="index.html">Home</a> <span class="divider">/</span></li>
					<li class="active">United States Political Violence Timeline</li>
				</ul>
				<h1>United States Political Violence Timeline<small>1780-2010</small></h1>
			</div>
		</div>
		<div class="row-fluid content"> 
			<div id='timeline' style="height: 85%; width: 100%;"></div>
			<script>
			$(document).ready(function () { 
			  var tg1 = $("#timeline").timeline({
				 "data_source":"media/json/uspv.php?year="+<?php if (isset($_GET['year'])) { echo $_GET['year']; } else { echo "1800"; } ?>,
				 "min_zoom":30,
				 "max_zoom":35,
				 "icon_folder":"media/img/timeline_icons/",
				 "show_footer":true,
			 });
			});
			</script>
		</div>
	</body>
</html>