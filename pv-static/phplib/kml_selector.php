<?php

require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");

if (isset($_GET['year'])) {
	//Get the year from get GET parameters
	$year = $_GET['year'];
	
	//Load the KML file
	$xml = simplexml_load_file("usa_states.kml", 'SimpleXMLElement', LIBXML_NOCDATA);

	$statesToDelete = array();
	//Iterate on every state on the KML file
	for ($pl = 0; $pl < count($xml->Document->Folder->Placemark); $pl++) {
		preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->begin, $timeBegin);
		preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->end, $timeEnd);
		
		//Rename the state without the year
		$xml->Document->Folder->Placemark[$pl]->name = (string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[1];
		
		//Set the description of every state accordingly
		$xml->Document->Folder->Placemark[$pl]->description = "Established: ".$timeBegin[0]."</br>Type: ".(string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[9];
		
		//Mark to delete the states not in the range of the selected year
		if ($year <= $timeBegin[1] || $year > $timeEnd[1]) {
			$statesToDelete[] = $pl;
		}
	}

	//Iterate through the marked states and delete them
	for ($pl = count($statesToDelete)-1; $pl >= 0; $pl--) {
		unset($xml->Document->Folder->Placemark[$statesToDelete[$pl]]);
	}
	
	//Obtain the coordinates of the events on the specified year and create the markers for them
	$events = obtainEvents($year);
	$xml = saveEventMarkersOnKML($events, $xml);
	//Return the modified KML
	echo $xml->asXML();
} else {
	echo "No year specified";
}

function obtainEvents($year) {
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
					?id pv:atTime ?time.
					?id pv:location ?loc.
					{?time <http://www.w3.org/2006/time#hasDateTimeDescription> ?asy} .
					{?asy <http://www.w3.org/2006/time#year> ?sy}
					
					FILTER(?sy = '$year'^^<http://www.w3.org/2001/XMLSchema#gYear>)
				}
			}";
			
	$idArray = $x->getOrderedInstanceIDs($search);
	$valueArray = array();
	$resultArray = array();

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

function saveEventMarkersOnKML($ev, $kml) {
	//Create new markers on the map with the coordinates of every event and the category of the event as a title
	foreach ($ev as $e) {
		$kml->Document->Folder->Placemark[]->name = $e['category']['values'][key($e['category']['values'])]['label'];
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->Point->coordinates = $e['location']['values'][key($e['location']['values'])]['lat'].",".$e['location']['values'][key($e['location']['values'])]['long'];
	}
	
	return $kml;
}