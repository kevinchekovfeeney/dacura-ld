<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>USPV Map Viewer</title>
	<script src='../testsite/media/js/jquery.js'></script>
	<script src="../testsite/media/js/jquery-ui-1.10.2.custom.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
	<link href="../testsite/media/css/jquery-ui-1.10.2.custom.min.css" type="text/css" rel="stylesheet">
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px;
		font-family: sans-serif;
      }
	  #controls {
		bottom: 3%;
		right: 1%;
		position: absolute;
		padding: 0.5em;
		background: #ECECEC;
		border-radius: 2px;
		box-shadow: gray 2px 2px;
		font-weight: bold;
		text-align: left;
		font-size: 14px;
		border: 1px solid gray;
	  }
	  #keys {
		bottom: 12%;
		right: 1%;
		position: absolute;
		padding: 0.3em;
		background: #ECECEC;
		border-radius: 2px;
		box-shadow: gray 2px 2px;
		text-align: right;
		width: 172px;
		height: 163px;
	  }
	  #keys:hover {
		cursor: move;
	  }
	  #keytitle {
		font-weight: bold;
		font-size: 14px;
	  }
	  input[type="number"] {
		width: 85px;
	  }
    </style>
    <script>
		var kmlLayer;
		var map;
		var styledMap = [{ featureType: "all", elementType: "labels", stylers: [ { visibility: "off" } ] }];
		var mapOptions = {
			center: new google.maps.LatLng(52.22393365932625, -123.03967999999998),
			zoom: 4,
			styles: styledMap,
			streetViewControl: false
		  }
		  
		function initialize() {
		  
		  var year = document.getElementById("year").value;
		  var markers = document.getElementById("displayMarkers").checked;
		  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

		  kmlLayer = new google.maps.KmlLayer({
			url: 'http://tcdfame.cs.tcd.ie/mapview/kml_loader.php?year='+year+"&markers="+markers+"&s="+Math.random(),
			preserveViewport:true
		  });
		  kmlLayer.setMap(map);
		  
		}
		
		function changeMap() {
			var year = document.getElementById("year").value;
			var autoZoom = document.getElementById("autoZoom").checked;
			if (year > 2000) {
				document.getElementById("year").value = 2000;
				year = 2000;
			} else if (year < 1784) {
				document.getElementById("year").value = 1784;
				year = 1784;
			}
			var markers = document.getElementById("displayMarkers").checked;
			kmlLayer.setMap(null);
			kmlLayer = new google.maps.KmlLayer({
				url: 'http://tcdfame.cs.tcd.ie/mapview/kml_loader.php?year='+year+"&markers="+markers+"&s="+Math.random(),
				preserveViewport: !autoZoom
			  });
			
			kmlLayer.setMap(map);
		}

		google.maps.event.addDomListener(window, 'load', initialize);

    </script>
  </head>
  <body>
    <div id="map-canvas"></div>
	<div id="keys" class="ui-widget-content">
		<table border="0px" cellspacing="4px" cellpadding="2px">
			<tr id="keytitle"><th>Number of events</th><th>Key</th></tr>
			<tr><td align="center">0</td><td class="keybox" style="background-color:#CECECE; opacity:0.6;"></td></tr>
			<tr><td align="center">1-2</td><td class="keybox" style="background-color:#FFAAAA; opacity:0.6;"></td></tr>
			<tr><td align="center">3-5</td><td class="keybox" style="background-color:#FF6669; opacity:0.6;"></td></tr>
			<tr><td align="center">6-9</td><td class="keybox" style="background-color:#FF2020; opacity:0.6;"></td></tr>
			<tr><td align="center">10+</td><td class="keybox" style="background-color:#FF0000; opacity:0.6;"></td></tr>
		</table>
	</div>
	<script>$('#keys').draggable({scroll: false });</script>
	<div id="controls">
		<input type="checkbox" id="displayMarkers" onchange="changeMap();" checked /><label for="displayMarkers">Display markers</label><br/>
		<input type="checkbox" id="autoZoom" checked /><label for="autoZoom">Automatic zoom</label>
		<br/>
		Year: <input type="number" id="year" min="1784" max="2000" onchange="changeMap();" value="2000"/>
	</div>
  </body>
</html>

