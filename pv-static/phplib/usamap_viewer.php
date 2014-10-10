<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>KML Layers</title>
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px
      }
	  #controls {
		bottom: 3%;
		right: 1%;
		position: absolute;
		padding: 0.3em;
		background: #ECECEC;
		border-radius: 2px;
		box-shadow: gray 2px 2px;
		font-weight: bold;
	  }
    </style>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
    <script>
		var ctaLayer;
		var map;
		var ctaLayerTmp;
		function initialize() {
		  var mapOptions = {
			zoom: 5,
		  }
		  var year = document.getElementById("year").value;
		  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

		  ctaLayer = new google.maps.KmlLayer({
			url: 'http://tcdfame.cs.tcd.ie/kml_selector.php?year='+year
		  });
		  ctaLayer.setMap(map);
		  
		}
		
		function changeMap() {
			var year = document.getElementById("year").value;
			ctaLayer.setMap(null);
			ctaLayer = new google.maps.KmlLayer({
				url: 'http://tcdfame.cs.tcd.ie/kml_selector.php?year='+year
			  });
			
			ctaLayer.setMap(map);
		}

		google.maps.event.addDomListener(window, 'load', initialize);

    </script>
  </head>
  <body>
    <div id="map-canvas"></div>
	<div id="controls">Year: <input type="number" id="year" min="1783" max="2000" onchange="changeMap();" value="2000"/></div>
  </body>
</html>

