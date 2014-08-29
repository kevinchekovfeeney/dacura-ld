<?php
/* This script takes a statc KML file and filters it, returning only the events
 * that satisfy the set of filters given.
 * $start and $end delimit the range in which shown events must have occured
 * $year defines the year whose state borders will be shown. In the default mode,
 * $year will be 2000. In the range mode, $year will be the $end year. In any
 * mode, if $year > 2000, the script will show the state borders recorded for 2000
 */

set_time_limit(0);

if (isset($_GET['mode'])) {
	//get the mode requested
	$mode = $_GET['mode'];
	if($mode == 'default'){
		//if mode is default, the state borders used will be the ones from 2000
		$year = 2000;
	}
	else{
		if (isset($_GET['end']))
			$year = $_GET['end'];
		//$past2000 = false;
		if($year > 2000){
			//	$past2000 = true;
			$year = 2000;
		}
	}
	
	//Get the start and end years to be used as filters
	if (isset($_GET['end']))
		$end = $_GET['end'];
	if (isset($_GET['start']))
		$start = $_GET['start'];
	
	//Get the boolean to load or not the markers of the events
	if (isset($_GET['markers'])) {
		$markers = $_GET['markers'];
	}
	
	//Get the category filter, if any
	if (isset($_GET['category'])) {
		$category = $_GET['category'];
	}
	
	//Get the number of motivation filters set by the user
	if (isset($_GET['motn'])) {
		$motn = $_GET['motn'];
	}
	
	//Prepare an array of motivation filters
	$motivations = array();
	for($i = 0; $i < $motn; $i++){
		$index = 'mot' . $i;
		$motivations[$i] = $_GET[$index];
	}
	
	//Load the KML file with the USA states over time and all events ever recorded
	$xml = simplexml_load_file("kmlDefault.xml", 'SimpleXMLElement', LIBXML_NOCDATA);
	
	//find out how many states that particular year had
	$n_states = 0;
	for($a = 0; $a < count($xml->Document->Folder->Placemark); $a++){
		if(count($xml->Document->Folder->Placemark[$a]->ExtendedData) == 1)
			$n_states++;
	}
	
	$statesToDelete = array();
	//Iterate on every state on the KML file
	for ($pl = 0; $pl < $n_states; $pl++) {
		preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->begin, $timeBegin);
		preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->end, $timeEnd);
		
		//Mark to delete the states not in the range of the selected year
		if ($year <= $timeBegin[1] || $year > $timeEnd[1]) {
			$statesToDelete[] = $pl;
		}
	}

	$n_states -= count($statesToDelete) + 1;
	//Iterate through the marked states and delete them
	for ($pl = count($statesToDelete)-1; $pl >= 0; $pl--) {
		unset($xml->Document->Folder->Placemark[$statesToDelete[$pl]]);
	}
	
	//updates the kml given the filters set by the user
	$xml = filterEvents($mode, $start, $end, $category, $motivations, $motn, $n_states, $xml);
	
	//Check on which state are the events and set the value of it
	$xml = countEventsPerState($xml, $n_states);
	
	// find out which state had the biggest number of events
	// TODO access this variable to dynamically change the keys on usamap_viewer
	$max = 0;
	for($pl = 0; $pl < $n_states; $pl++){
		if(count($xml->Document->Folder->Placemark[$pl]->pv_events) == 1){
			$n_events = count($xml->Document->Folder->Placemark[$pl]->pv_events->pv_event);
			if($n_events > $max)
				$max = $n_events;
		}
	}
	if($max < 4)
		$max = 4;
	$countBlockSize = floor($max / 4);
	
	//Update the name and description of the states
	for ($pl = 0; $pl < $n_states; $pl++) {
		preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->begin, $timeBegin);
		//preg_match("/(\d{4})-(\d{2})-(\d{2})/", $xml->Document->Folder->Placemark[$pl]->TimeSpan->end, $timeEnd);
	
		//Rename the state without the year
		$xml->Document->Folder->Placemark[$pl]->name = (string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[1];
		
		//A counter with the amount of events on that state for that year
		$eventsCount = (isset($xml->Document->Folder->Placemark[$pl]->pv_events) ? count($xml->Document->Folder->Placemark[$pl]->pv_events->pv_event) : 0);
	
		//Set the description of every state accordingly
		if($mode == 'year'){
			$xml->Document->Folder->Placemark[$pl]->description = "Established: ".$timeBegin[0]."<br/>Type: ".(string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[9].
			"<br/>Events in this state in $year: $eventsCount<br/><br/><a href=browse-uspv.html?location=".str_replace(" ", "_", $xml->Document->Folder->Placemark[$pl]->name).">View all the events of this state</a>";
		}
		else{
			$xml->Document->Folder->Placemark[$pl]->description = "Established: ".$timeBegin[0]."<br/>Type: ".(string)$xml->Document->Folder->Placemark[$pl]->ExtendedData->SchemaData->SimpleData[9].
			"<br/>Number of events recorded in this state: $eventsCount<br/><br/><a href=browse-uspv.html?location=".str_replace(" ", "_", $xml->Document->Folder->Placemark[$pl]->name).">View all the events of this state</a>";
		}
		
		//this dynamically changes the color of the polygons
		$evAmount = "none";
		if ($eventsCount <= $countBlockSize && $eventsCount >= 1) {
			$evAmount = "low";
		}
		if ($eventsCount <= $countBlockSize*2 && $eventsCount >= ($countBlockSize+1)) {
			$evAmount = "medium";
		}
		if ($eventsCount <= $countBlockSize*3 && $eventsCount >= (($countBlockSize*2)+1)) {
			$evAmount = "high";
		}
		if ($eventsCount <= $max && $eventsCount >= (($countBlockSize*3)+1)) {
			$evAmount = "xhigh";
		}
		$xml->Document->Folder->Placemark[$pl]->styleUrl = "#".$evAmount;
	}
	
	echo $xml->asXML();
}

//Adds the category of each event to its state based on the lat and long coords
function countEventsPerState($kml, $n) {
	$eventIndexes = array();
	$i = 0;
	for($a = $n; $a < count($kml->Document->Folder->Placemark); $a++){
		if(isset($kml->Document->Folder->Placemark[$a]->motivation))
			$eventIndexes[$i++] = $a;
	}
	
	for($a = 0; $a < $n; $a++){
			$polygons = $kml->Document->Folder->Placemark[$a]->MultiGeometry->Polygon;
			foreach ($polygons as $poly) {
			//Obtain the lat and long coordinates that form the polygons
			preg_match_all("/(\-?\d*.\d*),(\-?\d*.\d*),0/", (string)$poly->outerBoundaryIs->LinearRing->coordinates[0], $coords);
			$coordArray = array();
			//Save the lat and long coords of the polygon. Multiplying the values by a huge number to make the ray-casting algorithm work well
			for ($c = 0; $c < count($coords[0]); $c++) {
				$coordArray[$c]["long"] = $coords[1][$c]*100000;
				$coordArray[$c]["lat"] = $coords[2][$c]*100000;
			}
			$point = array();
			$i = 0;
			
			for ($j = 0; $j < count($eventIndexes); $j++){
				$temp = explode(",", $kml->Document->Folder->Placemark[$eventIndexes[$i]]->Point->coordinates);
				$point["long"] = $temp[0] * 100000;
				$point["lat"] = $temp[1] * 100000;
				if (containsLocationInsideBoundaries($point, $coordArray)) {
					$kml->Document->Folder->Placemark[$a]->pv_events->pv_event[] = $kml->Document->Folder->Placemark[$eventIndexes[$i]]->name;
				}
				$i++;
			}
		}		
	}
	
	return $kml; 
}

//Empties the <Placemark> objects that don't meet the filters set by the user
function filterEvents($mode, $start, $end, $category, $motivations, $motn, $n, $kml){
	if($mode != 'default'){
		if($category != 'all'){
			for($a = $n; $a < count($kml->Document->Folder->Placemark); $a++){
				if($start > $kml->Document->Folder->Placemark[$a]->year || $end < $kml->Document->Folder->Placemark[$a]->year || $category != $kml->Document->Folder->Placemark[$a]->name)
					$kml->Document->Folder->Placemark[$a] = "";
				if($motivations[0] != 'all'){
					$delete = true;
					for($b = 0; $b < $motn; $b++){
						if($motivations[$b] == $kml->Document->Folder->Placemark[$a]->motivation)
							$delete = false;
					}
					if($delete == true)
						$kml->Document->Folder->Placemark[$a] = "";
				}
			}
		}
		else{
			for($a = $n; $a < count($kml->Document->Folder->Placemark); $a++){
				if($start > $kml->Document->Folder->Placemark[$a]->year || $end < $kml->Document->Folder->Placemark[$a]->year)
					$kml->Document->Folder->Placemark[$a] = "";
				if($motivations[0] != 'all'){
					$delete = true;
					for($b = 0; $b < $motn; $b++){
						if($motivations[$b] == $kml->Document->Folder->Placemark[$a]->motivation)
							$delete = false;
					}
					if($delete == true)
						$kml->Document->Folder->Placemark[$a] = "";
				}
			}
		}
	}
	else{ //mode is default
		if($category != 'all'){
			for($a = $n; $a < count($kml->Document->Folder->Placemark); $a++){
				if($category != $kml->Document->Folder->Placemark[$a]->name)
					$kml->Document->Folder->Placemark[$a] = "";
				if($motivations[0] != 'all'){
					$delete = true;
					for($b = 0; $b < $motn; $b++){
						if($motivations[$b] == $kml->Document->Folder->Placemark[$a]->motivation)
							$delete = false;
					}
					if($delete == true)
						$kml->Document->Folder->Placemark[$a] = "";
				}
			}
		}
		else{
			for($a = $n; $a < count($kml->Document->Folder->Placemark); $a++){
				if($motivations[0] != 'all'){
					$delete = true;
					for($b = 0; $b < $motn; $b++){
						if($motivations[$b] == $kml->Document->Folder->Placemark[$a]->motivation)
							$delete = false;
					}
					if($delete == true)
						$kml->Document->Folder->Placemark[$a] = "";
				}
			}
		}
	}
	
	return $kml;
}

//Ray-casting algorithm, calculates if an event is inside the polygon of a state
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