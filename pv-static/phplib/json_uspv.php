<?php
//Execute this script with Google Chrome,
//as Firefox throws timeout after 5 minutes (this can take a long time depending on the SPARQL endpoint speed)
require_once("SparqlBridge.php");
require_once("EventRecord.php");
error_reporting(0);
set_time_limit(1200);

//Basic timeline config
$timeline_title = "Detailed Events Display";
$initial_zoom = "35";
$timezone = "+01:00";
$output = "../media/json/uspv.php";

//Initialize the array of events
$events = array();
//Iterate through all the events
for ($i = 0; $i < 1599; $i+=100) {
//Get the events from the selected range
$valueArray = getEvents($i, 100);
foreach ($valueArray as $event) {
	//Generate the date of the event
	if (isset($event['edate']['values'][key($event['edate']['values'])]['month'])) {
		$date = $event['edate']['values'][key($event['edate']['values'])]['year']."-".$event['edate']['values'][key($event['edate']['values'])]['month']."-15 12:00:00";
	} else {
		$date = $event['edate']['values'][key($event['edate']['values'])]['year']."-01-15 12:00:00";
	}
	
	$motivation = $event['motivation']['values'][key($event['motivation']['values'])]['label'];

	if (!isset($motivation)) {
		$motivation = "unknown";
	}
	//Icon name == category
	$icon = preg_replace('/\s+/', '', $event['category']['values'][key($event['category']['values'])]['label']).".png";
	//Description details
	$desc = "Motivation: ".$motivation."<br>Location: ".$event['location']['values'][key($event['location']['values'])]['unstructured']."<br>Fatalities: ".$event['fatalities']['values'][key($event['fatalities']['values'])]['unstructured']."<br><br>".$event['description']['values'][0];
	//Put all together into an array
	$idArray = explode("/", $event['idValue']);
	$ev = array("id"=>$idArray[6], "title"=>$event['category']['values'][key($event['category']['values'])]['label'], "description"=>$desc, "startdate"=>$date, "enddate"=>$date, "date_display"=>"month", "link"=>$event['idValue'], "importance"=>40, "icon"=>$icon);
	//Add the recently created array into the events array stack
	$events[] = $ev;
}
}
//Resulting array
$result = array(array("id"=>"political_violence", "title"=>$timeline_title, "focus_date"=>'<?php if (isset($_GET[\'year\'])) { echo $_GET[\'year\']; } else { echo \'1800\'; } ?>-01-01 12:00:00', "initial_zoom"=>$initial_zoom,
 "timezone"=>$timezone, "events"=>$events));

//Write the JSON into the specified file
file_put_contents($output, json_encode($result));

echo "Success. File saved on $output";

function getEvents($offset, $limit) {
	//Open the connection with the SPARQL endpoint
	$sparql = new SparqlBridge("http://dacura.cs.tcd.ie:3030/politicalviolence/query");
	$source = "http://dacura.cs.tcd.ie:3030/politicalviolence/query";
	$schema_graph = "http://dacura.cs.tcd.ie/data/politicalviolence";
	$data_graph = "http://dacura.cs.tcd.ie/data/politicalviolence/uspv";
	//Query the pv events
	$qry = "PREFIX pv-ns: <http://dacura.cs.tcd.ie/data/politicalviolence/>
			PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>

			SELECT *
			{
				GRAPH pv-ns:uspv {?id a pv:PoliticalViolenceEvent}
			} OFFSET $offset LIMIT $limit";

	$idArray = $sparql->getOrderedInstanceIDs($qry);
	$valueArray = array();
	$resultArray = array();

	//take returned array of IDs, query each, and add it to the array of values
	foreach ($idArray as $id){
		$sparql = new EventRecord($id);
		$sparql->setDataSource($source, $schema_graph, $data_graph);
		$sparql->loadFromDB(false);
		$data = $sparql->getAsArray();
		$data["idValue"] = $id;
		$valueArray[$id] = $data;
	}
	
	return $valueArray;
}

?>