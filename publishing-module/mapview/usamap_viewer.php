<!DOCTYPE html>
<html>
  <head>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <title>USPV Map Viewer</title>
	<script src='jquery.js'></script>
	<script src="jquery-ui-1.10.2.custom.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=false"></script>
	<link href="jquery-ui-1.10.2.custom.min.css" type="text/css" rel="stylesheet">
    <style>
      html, body, #map-canvas {
        height: 100%;
        margin: 0px;
        padding: 0px;
		font-family: sans-serif;
      }
	  #controls {
		bottom: 5%;
		right: 82%;
		position: absolute;
		padding: 0.5em;
		background: #ECECEC;
		border-radius: 2px;
		box-shadow: gray 2px 2px;
		font-weight: bold;
		text-align: left;
		font-size: 14px;
		border: 1px solid gray;
		width: 200px;
	  }
	  #keys {
		bottom: 5%;
		right: 3%;
		position: absolute;
		padding: 0.3em;
		background: #ECECEC;
		border-radius: 2px;
		box-shadow: gray 2px 2px;
		text-align: right;
		width: 172px;
		height: 163px;
	  }
	  #filters {
	    bottom: 82%;
		right: 3%;
		position: absolute;
		padding: 0.3em;
		background: #ECECEC;
		border-radius: 2px;
		box-shadow: gray 2px 2px;
		text-align: left;
		width: 214px;
		height: 50px;
	  }
	  #motivations{
	    font-size:12px;
	    background: #ECECEC;
	    border-radius: 2px;
		box-shadow: gray 2px 2px;
		width: 219px;
	    
	  }
	  #filters:hover {
		cursor: move;
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
			center: new google.maps.LatLng(45.22393365932625, -113.03967999999998),
			zoom: 3,
			styles: styledMap,
			streetViewControl: false
		  }
		  
		function initialize() {

		  $('#rangeinput').hide();
		  $('#motivations').hide();
		  var markers = document.getElementById("displayMarkers").checked;
		  
		  map = new google.maps.Map(document.getElementById('map-canvas'), mapOptions);

		  kmlLayer = new google.maps.KmlLayer({
			url: 'http://dacura.cs.tcd.ie/mapview/kml_loader3.php?mode=year&year=2010&start=2010&end=2010&markers=true&category=all&mot0=all&motn=1',
			preserveViewport:true
		  });
		  kmlLayer.setMap(map);
		  
		}
		
		function changeMap() {
			var mode = $('input:radio[name=mode]:checked').val();
			var autoZoom = document.getElementById("autoZoom").checked;
			var markers = document.getElementById("displayMarkers").checked;

			var e = document.getElementById("categories");
			var category = e.options[e.selectedIndex].value;

			var motivations = [];
			var motFilter = document.getElementById("motivation_list").checked;
			if(motFilter == false){
				motivations[0] = 'all'; 
			}
			else{
				$("#motivations input:checked").each(function() {
				motivations.push($(this).val());
			});
			}
			
			switch(mode){
				case "year":
					var year = document.getElementById("year").value;
					if (year > 2010) {
						document.getElementById("year").value = 2010;
						year = 2010;
					} else if (year < 1784) {
						document.getElementById("year").value = 1784;
						year = 1784;
					}
					var start = year;
					var end = year;
					break;
				
				case "default":
					var year = 2000;
					var start = year;
					var end = year;
					break;
				
				case "range":
					var start = document.getElementById("start").value;
					if (start < 1784) {
						document.getElementById("start").value = 1784;
						start = 1784;
					}
					var end = document.getElementById("end").value;
					if (end > 2010) {
						document.getElementById("end").value = 2010;
						end = 2010;
					}
					var year = end; 
					break;
				
			}
			var myurl = 'http://dacura.cs.tcd.ie/mapview/kml_loader3.php?mode='+mode+
						'&year='+year+'&start='+start+'&end='+end+'&markers='+markers+
						'&category='+category;
			
			if(motFilter == true && motivations.length == 0){
				myurl += '&mot0=all&motn=1';
			}
			else{
				var i = 0;
				for(i = 0; i < motivations.length; i++){
					myurl += '&mot'+i+'='+motivations[i];
				}
				myurl += '&motn='+motivations.length;
			}  

			console.log(myurl);	
			kmlLayer.setMap(null);

			
			kmlLayer = new google.maps.KmlLayer({
				url: myurl,
				preserveViewport: !autoZoom
			});
			kmlLayer.setMap(map);
		}

		function uncheckAll() { // this will uncheck every motivation
			$('.motivation').each(function() { 
				this.checked = false;                        
			});
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
	<div id="filters"><b>Categories:</b>
		<select name="categories" id="categories" onchange="changeMap();">
			<option value="all" selected>All</option>
			<option value="assassination">Assassinations</option>
			<option value="compilation">Compilations</option>
			<option value="execution">Executions</option>
			<option value="insurrection">Insurrections</option>
			<option value="lynching">Lynchings</option>
			<option value="mass_suicide">Mass Suicides</option>
			<option value="rampage">Rampages</option>
			<option value="riot">Riots</option>
			<option value="terrorism">Terrorism</option>
			<option value="war">Wars</option>
			<option value="unknows">Unknown</option>
		</select>
		<input type="checkbox" id="motivation_list" onchange="var temp = document.getElementById('motivation_list').checked; if (temp == true){ $('#motivations').show(); uncheckAll();} else {$('#motivations').hide(); changeMap();}"/><b>Filter by motivations</b><br>
		<div id="motivations" onchange="changeMap();">
			<table>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="criminal">Criminal<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="economic">Economic<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="education">Education<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="ethnic">Ethnic<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="extralegal">Extralegal<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="family">Family<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="indian">Indian<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="insane">Insane<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="labor">Labor<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="land">Land<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="military">Military<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="nativist">Nativist<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="personal">Personal<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="political">Political<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="prison">Prison<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="race">Race<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="religion">Religion<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="revenge">Revenge<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="section">Section<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="sex">Sex<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="shipping">Shopping<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="work">Work<br></td></tr>
			<tr><td><input class= "motivation" type="checkbox" name="motivation" value="unknown">Unknown<br></td>
			<td><input class= "motivation" type="checkbox" name="motivation" value="other">Other<br></td></tr>
			</table>
		</div>
		
	</div>
	<div id="controls">
		<input type="checkbox" id="displayMarkers" onchange="changeMap();" checked /><label for="displayMarkers">Display markers</label><br/>
		<input type="checkbox" id="autoZoom" checked /><label for="autoZoom">Automatic zoom</label><br><br>Mode:<br>
		<input type="radio" name="mode" value="default" onchange="$('#yearinput').hide(); $('#rangeinput').hide(); changeMap();">Default 
		<input type="radio" name="mode" value="year" onchange="$('#yearinput').show(); $('#rangeinput').hide();" checked>Year
		<input type="radio" name="mode" value="range" onchange="$('#rangeinput').show(); $('#yearinput').hide();">Range
		<br/>
		<div id="yearinput">Year: <input type="number" id="year" min="1784" max="2010" onchange="changeMap();" value="2010"/></div>
		<div id="rangeinput">
			Start: <input type="number" id="start" min="1784" max="2010" onchange="changeMap();" value="1784"/>
			<br>End: <input type="number" id="end" min="1784" max="2010" onchange="changeMap();" value="2010"/>
		</div>
	</div>
	<script>$('#keys').draggable({scroll: true });
	$('#filters').draggable({scroll: true });</script>
  </body>
</html>