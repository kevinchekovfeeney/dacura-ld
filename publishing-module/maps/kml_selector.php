<?php
/*Return a KML file out of an existing, static KML file with the data from the
specified year and few tweaks to the tooltips, colors of the polygons and event markers*/
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");
error_reporting(E_ALL);
global $vis_data;

if (isset($_GET['year']) && isset($_GET['data'])) {
	//Get the year from get GET parameters
	$year = $_GET['year'];
	$markers = true;
	//Get the boolean for load or not the markers of the events
	if (isset($_GET['markers'])) {
		$markers = $_GET['markers'];
	}
	//Get the data from the GET parameter
	$vis_data = json_decode(base64_decode($_GET['data']));
	
	//Check for the region
	if ($vis_data[0]->region == "USA") {
		//Load the KML file with the USA states over time
		$xml = simplexml_load_file("usa_states.kml", 'SimpleXMLElement', LIBXML_NOCDATA);

		$statesToDelete = array();
		//Iterate on every state on the KML file
		for ($pl = 0; $pl < count($xml->Document->Folder->Placemark); $pl++) {
			preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->begin, $timeBegin);
			preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->end, $timeEnd);
			
			//Mark to delete the states not in the range of the selected year
			if ($year <= $timeBegin[1] || $year > $timeEnd[1]) {
				$statesToDelete[] = $pl;
			}
		}

		//Iterate through the marked states and delete them
		for ($pl = count($statesToDelete)-1; $pl >= 0; $pl--) {
			unset($xml->Document->Folder->Placemark[$statesToDelete[$pl]]);
		}
		
		
		//Obtain the coordinates of the events on the specified year
		$events = obtainEvents($year);
		//Check on which state are the events and set the value of it
		countEventsPerState($events, $xml);
		
		//Update the name and description of the states
		for ($pl = 0; $pl < count($xml->Document->Folder->Placemark); $pl++) {
			preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->begin, $timeBegin);
			preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->end, $timeEnd);
			
			//Rename the state without the year
			$xml->Document->Folder->Placemark[$pl]->name = (string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[1];
			
			//A counter with the amount of events on that state for that year
			$eventsCount = (isset($xml->Document->Folder->Placemark[$pl]->pv_events) ? count($xml->Document->Folder->Placemark[$pl]->pv_events->pv_event) : 0);
			
			//Set the description of every state accordingly
			$xml->Document->Folder->Placemark[$pl]->description = "Established: ".$timeBegin[0]."<br/>Type: ".(string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[9].
			"<br/>Events on this state in $year: $eventsCount<br/><br/><a href=browse-uspv.html?location=".str_replace(" ", "_", $xml->Document->Folder->Placemark[$pl]->name).">View all the events of this state</a>";
			
			//Change the color of the polygon depending on the amount of events on the state
			$evAmount = "none";
			if ($eventsCount >= 1) {
				$evAmount = "low";
			}
			if ($eventsCount >= 3) {
				$evAmount = "medium";
			}
			if ($eventsCount >= 5) {
				$evAmount = "high";
			}
			if ($eventsCount >= 9) {
				$evAmount = "xhigh";
			}
			$xml->Document->Folder->Placemark[$pl]->styleUrl = "#".$evAmount;
		}
		
		//Save the events on the KML (only if markers option is true)
		if ($markers == "true") {
			$xml = saveEventMarkersOnKML($events, $xml);
		}
		//Return the modified KML
		echo $xml->asXML();
	} else {
		//Only USA region implemented so far
		echo "Not implemented";
	}
} else {
	echo "No year or data specified";
}

//Add the IDs of the events on every state based on the lat and long coords
function countEventsPerState($ev, $kml) {
	$states = $kml->Document->Folder->Placemark;
	foreach ($states as $s) {
		$polygons = $s->MultiGeometry->Polygon;
		//Every polygon is a shape that must be checked
		foreach ($polygons as $poly) {
			//Obtain the lat and long coordinates that form the polygons
			preg_match_all("/(\-?\d*.\d*),(\-?\d*.\d*),0/", (string)$poly->outerBoundaryIs->LinearRing->coordinates[0], $coords);
			$coordArray = array();
			//Save the lat and long coords of the polygon. Multiplying the values by a huge number for make the ray-casting algorithm works well
			for ($c = 0; $c < count($coords[0]); $c++) {
				$coordArray[$c]["long"] = $coords[1][$c]*100000;
				$coordArray[$c]["lat"] = $coords[2][$c]*100000;
			}
			$point = array();
			//Check for every event lat and long if its inside this polygon or not
			foreach ($ev as $idx=>$e) {
				//Multiplying the values by a huge number for make the ray-casting algorithm works well
				$point["lat"] = $e['location']['values'][key($e['location']['values'])]['lat']*100000;
				$point["long"] = $e['location']['values'][key($e['location']['values'])]['long']*100000;
				//Check using the ray-casting algorithm if the event is inside the polygon
				if (containsLocationInsideBoundaries($point, $coordArray)) {
					$s->pv_events->pv_event[] = $e['idValue'];
				}
			}
		}
	}
}

//Get the events from the SPARQL endpoint
function obtainEvents($year) {
	global $vis_data;
	//Get the date and location URIs
	$date = (string)$vis_data[6]->event_date;
	$location = (string)$vis_data[7]->event_coords;
	
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
					?id <$date> ?time.
					?id <$location> ?loc.
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

//Add the events obtained from the SPARQL endpoint on the KML file
function saveEventMarkersOnKML($ev, $kml) {
	global $vis_data;
	//Get the fields that must be loaded on the name and description
	$ev_name = explode("#", $vis_data[4]->event_name);
	$ev_name = $ev_name[1];
	$ev_desc = explode("#", $vis_data[5]->event_desc);
	$ev_desc = $ev_desc[1];
	$ev_location = explode("#", $vis_data[7]->event_coords);
	$ev_location = $ev_location[1];
	
	//Create new markers on the map with the coordinates of every event and the category of the event as a title
	foreach ($ev as $e) {
		$kml->Document->Folder->Placemark[]->name = getField($e, $ev_name);
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->description = "Location: ".getField($e, $ev_location).
			"<br/>Motivation: ".getField($e, "motivation")."<br>Fatalities: ".getField($e, "fatalities").
			"<br/><br/>".getField($e, $ev_desc)."<br/><br/><a href=".getField($e, "link").">Link</a>";
		//We move the latitude an small, random amount for avoid overlapping when there are more than 2 events on the same coordinates
		$kml->Document->Folder->Placemark[count($kml->Document->Folder->Placemark)-1]->Point->coordinates = (getField($e, "lat")-(rand(0,10)/1000)).",".getField($e, "long");
		$fatalities = getField($e, "fatalities");
		
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

//Get the data from the field specified
//If the structure of the SPARQL changes, this is what you must edit
function getField($e, $code) {
	switch ($code) {
		case "category":
			return $e['category']['values'][key($e['category']['values'])]['label'];
			break;
		case "motivation":
			return $e['motivation']['values'][key($e['motivation']['values'])]['label'];
			break;
		case "location":
			return $e['location']['values'][key($e['location']['values'])]['unstructured'];
			break;
		case "description":
			return $e['description']['values'][0];
			break;
		case "fatalities":
			return $e['fatalities']['values'][key($e['fatalities']['values'])]['unstructured'];
			break;
		case "source":
			break;
		case "atTime":
			break;
		case "id":
			return $e['idValue'];
			break;
		case "lat":
			return $e['location']['values'][key($e['location']['values'])]['long'];
			break;
		case "long":
			return $e['location']['values'][key($e['location']['values'])]['lat'];
			break;
	}
}

//Ray-casting algorithm, calculates if an event is inside the polygon of an state
//FROM: http://websystemsengineering.blogspot.ie/2014/02/latlong-ray-casting-algorithm-for-php.html
function containsLocationInsideBoundaries($location, $vertices) 
{
	if ( count( $vertices ) <= 2 )
	{
		return false;
	}
  
	// just to be sure, we'll reset the keys of the vertices array
	// so they'll be incremental
	$tmp = array();
  
	foreach ( $vertices as $v )
	{
		$tmp[] = $v;
	}
  
	$vertices = $tmp;
  
	// let's produce all edges

	$edges = array();

	foreach ( $vertices as $k => $vertix )
	{
		if ( array_key_exists( ( $k+1 ) , $vertices ) )
		{
			$edges[] = array( $vertix, $vertices[ ( $k + 1 ) ] );
		}
  
	}// foreach
  
	$intersections_count = 0;

	// let's see if there are intersections.
	foreach ( $edges as $edge )
	{
		if (_rayIntersectSeg( $location, $edge ) )
		{
			$intersections_count++;
		}

	} // foreach
  
	return _odd( $intersections_count );
}

function _rayIntersectSeg( $p, $edge )
{
	$_tiny = 1;
	$_huge = PHP_INT_MAX;
  
	$a = $edge[0];
	$b = $edge[1];

	if ( $a['long'] > $b['long'] )
	{
		$a = $edge[1];
		$b = $edge[0];
	}
  
	$intersect = false;

	if ( ( $p['long'] > $b['long'] || $p['long'] < $a['long'] )
			|| ( $p['lat'] > max( $a['lat'], $b['lat'] ) ) )
	{
		return false;
	}

	if ( $p['lat'] < min( $a['lat'], $b['lat'] ) )
	{
		$intersect = true;
	}
	else
	{
		if ( abs( $a['lat'] - $b['lat'] ) > $_tiny )
		{
			$m_red = ( $b['long'] - $a['long'] ) / floatval( $b['lat'] - $a['lat'] );
		}
		else
		{
			$m_red = $_huge;
		}

		if ( abs( $a['lat'] - $p['lat'] ) > $_tiny )
		{
			$m_blue = ( $p['long'] - $a['long'] ) / floatval( $p['lat'] - $a['lat'] );
		}
		else
		{
			$m_blue = $_huge;
		}

		$intersect = ( $m_blue >= $m_red );

	} // else

	return $intersect;

} // _rayIntersectSeg

function _odd( $x )
{
	return ( ($x%2) == 1 );
} // _odd

//End of Ray-casting algorithm