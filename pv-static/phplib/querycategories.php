<?php
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");

$x = new SparqlBridge("http://dacura.cs.tcd.ie:3030/politicalviolence/query");
$y = new NSURI();
$ent = $y->getnsuri("pv")."PoliticalViolenceEvent";
$source = "http://dacura.cs.tcd.ie:3030/politicalviolence/query";
$schema_graph = "http://dacura.cs.tcd.ie/data/politicalviolence";
$data_graph = "http://dacura.cs.tcd.ie/data/politicalviolence/uspv";
$result = Array();


//Get all the categories from the dataset
$geoQuery = "PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>
			PREFIX pv-ns: <http://dacura.cs.tcd.ie/data/politicalviolence/>

			SELECT DISTINCT ?categories
				{
					GRAPH pv-ns:uspv {
						?p a pv:PoliticalViolenceEvent;
						pv:category ?categories
					}
				}";

$query = $x->askSparql($geoQuery);
$length = count($query->rows);

//Formats the JSON from the API into a suitable format.
if(isset($query->rows[0]["categories"])){
	for($i=0;$i<$length;$i++){
		$result[$i]["name"] = $query->rows[$i]["categories"]["value"];
		$result[$i]["name"]	= substr($result[$i]["name"], strpos($result[$i]["name"], "#")+1);
	}
}else{
	//give a blank array
}

//opr($result);
echo json_encode($result);