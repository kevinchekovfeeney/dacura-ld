<?php

//require_once("Widgetizer.php");
//require_once("Displayer.php");



//$tzer = new Displayer("http://tcdfame.cs.tcd.ie/data/politicalviolence", "http://tcdfame.cs.tcd.ie:3030/politicalviolence/query");
//$tzer->displayInstances("SELECT ?s ?p ?o WHERE { ?s a <http://kdeg.cs.tcd.ie/ontology/politicalviolence#Report> . ?s ?p ?o.}");
//$tzer->getClassProperties("http://tcdfame.cs.tcd.ie/data/politicalviolence#Event");

?>
<html>
<head>
  <script src="jquery-1.9.1.min.js"></script>
  <script src="jquery-ui-1.10.2.custom.min.js"></script>
  <script src="json2.js"></script>
  <script src="prettyprint.js"></script>
  <script src="widget.js"></script>
  <link rel="stylesheet" type="text/css" href="jquery-ui-1.10.2.custom.min.css" />
  <link rel="stylesheet" type="text/css" href="widget.css" />
  </head>
<body>
Record ID: <input type="text" id='test-widget-ip'></input>
<input type="submit" value="Load" id="test-widget-load">
<input type="submit" value="Clear" id="test-widget-clear">
<div class="dump_result" style="width: 400px;"></div>
<?php 
//echo $widget_html; 

?>
<script>
	$(function(){
		var dw = new dacura_widget;
		dw.drawEmpty({width: 500, title: "Political Violence Event Report"});
		$('#test-widget-clear').button().click(function(e){
			e.preventDefault();
			dw.clearWidget();
		});
		$('#test-widget-load').button().click(function(e){
			e.preventDefault();
			var xid = $('#test-widget-ip').val();
			if(xid == ""){
				alert("You must provide an ID of the record to load");
			}
			else {
				
				$("div.dacura-widget").dialog("open");
				dw.getRecordFromServer(xid);
			}
		});    
	});


</script>
</body>
</html>