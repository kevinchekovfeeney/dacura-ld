<?php
/*Return a KML file out of an existing, static KML file with the data from the
specified year and few tweaks to the tooltips, colors of the polygons and event markers*/
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");
set_time_limit(0);


	//Load the KML file with the USA states over time
	$xml = simplexml_load_file("usa_states.kml", 'SimpleXMLElement', LIBXML_NOCDATA);
	
	//Obtain the coordinates of the events on the specified year
	$events = obtainEvents();
	
	$xml = saveEventMarkersOnKML($events, $xml);
	//Return the modified KML if mode == year or save the new version on the server if mode == default
	
	$myfile = fopen("kmlDefault.kml", "w");
	fwrite($myfile, $xml->asXML());
	fclose($myfile);
	return;

//Get the events from the SPARQL endpoint
function obtainEvents() {
	$x = new SparqlBridge("http://dacura.cs.tcd.ie:3030/politicalviolence/query");
	$y = new NSURI();
	
	$ent = $y->getnsuri("pv")."PoliticalViolenceEvent";
	$source = "http://dacura.cs.tcd.ie:3030/politicalviolence/query";
	$schema_graph = "http://dacura.cs.tcd.ie/data/politicalviolence";
	$data_graph = "http://dacura.cs.tcd.ie/data/politicalviolence/uspv";
	$search = "PREFIX pv-ns: <http://dacura.cs.tcd.ie/data/politicalviolence/>
		PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>

		SELECT ?id
		{
			GRAPH pv-ns:uspv {
				?id a pv:PoliticalViolenceEvent.
				?id pv:location ?loc.
		}}";
			
	$idArray = $x->getOrderedInstanceIDs($search);
	$valueArray = array();

	//take returned array of IDs, query each, and add it to the array of values
	foreach ($idArray as $id){
		$x = new EventRecord($id);
		$x->setDataSource($source, $schema_graph, $data_graph);
		$x->loadFromDB(false);
		$data = $x->getAsArray();
		$data["idValue"] = $id;
		$valueArray[$id] = $data;
	}
	
	return $valueArray;
}

//Add the events obtained from the SPARQL endpoint on the KML file
function saveEventMarkersOnKML($ev, $kml) {
	//Create new markers on the map with the coordinates of every event and the category of the event as a title
	
	foreach ($ev as $e) {
		$kml->Document->Folder->Placemark[]->name = $e['category']['values'][key($e['category']['values'])]['label'];
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->description = "Location: ".$e['location']['values'][key($e['location']['values'])]['unstructured'].
			"<br/>Year: ".$e['edate']['values'][key($e['edate']['values'])]['year'].
			"<br/>Motivation: ".$e['motivation']['values'][key($e['motivation']['values'])]['label']."<br>Fatalities: ".$e['fatalities']['values'][key($e['fatalities']['values'])]['unstructured'].
			"<br/><br/>".$e['description']['values'][0]."<br/><br/><a href=".$e['idValue'].">Link</a>";
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->year = $e['edate']['values'][key($e['edate']['values'])]['year'];
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->motivation = $e['motivation']['values'][key($e['motivation']['values'])]['label'];
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->Point->coordinates = ($e['location']['values'][key($e['location']['values'])]['long']-(rand(0,10)/1000)).",".($e['location']['values'][key($e['location']['values'])]['lat']);
		$fatalities = $e['fatalities']['values'][key($e['fatalities']['values'])]['unstructured'];
		
		//Change the color of the marker depending on the amount of fatalities for the event
		$color = "white";
		if (intval($fatalities) == 1) {
			$icon = "green";
		}
		if (intval($fatalities) > 1) {
			$icon = "yellow";
		}
		if (intval($fatalities) > 3) {
			$icon = "blue";
		}
		if (intval($fatalities) > 5) {
			$icon = "red";
		}
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->styleUrl = "#".$icon;
	}
	
	return $kml;
}