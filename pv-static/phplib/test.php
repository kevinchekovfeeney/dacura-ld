<?php
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");

//Building the query
//initialise variables
$startRec = intval($_GET['iDisplayStart']);
$numRec = intval($_GET['iDisplayLength']);
$sortColumnNumber = intval($_GET['iSortCol_0']);
$loc = "";
$loc2 = "";
$startdate = "pv:atTime ?at;";
$startdate2 = " . {?at <http://www.w3.org/2006/time#hasDateTimeDescription> ?asy} . {?asy <http://www.w3.org/2006/time#year> ?sy}";
$startdatefilter = "";
$enddate = "";
$enddate2 = "";
$enddatefilter = "";
$order = "?order";
$fatalitiesOrder = "";
$locationOrder = "";
$optional = "OPTIONAL";

//if variables have been passed in by Datatables, then put them in the search
//Rather complicated, has to had
if($_GET['category'] == "any"){
	$category = "";
}else{
	$category = "pv:category pv:" . $_GET['category'] . ";";
}

if($_GET['motivation'] == "any"){
	$motivation = "";
}else{
	$motivation = "pv:motivation pv:" . $_GET['motivation'] . ";";
}

if(intval($_GET['startDate']) != 0){
	$startyear = intval($_GET['startDate']);
	$startdatefilter = "FILTER (?sy >= \"$startyear\"^^<http://www.w3.org/2001/XMLSchema#gYear>)";
}

if(intval($_GET['endDate']) != 0){
	$endyear = intval($_GET['endDate']);
	$enddatefilter = "FILTER (?sy <= \"$endyear\"^^<http://www.w3.org/2001/XMLSchema#gYear>)";
}

if($_GET['location'] != ""){
	$location = $_GET['location'];
	$loc = "pv:location ?l;";
	$loc2 = " . {?l pv:dbpediaLocation <http://dbpedia.org/resource/$location>}";
}

//this switch controls the sort order of the table - datatables passes it in as a as a number
$sortDirection = $_GET['sSortDir_0'];
switch($sortColumnNumber){
	case 0:
		$sortColumn = "pv:category ?order";
		break;
	case 1:
		$sortColumn = "";
		$order = "?sy";
		break;
	case 2:
		$sortColumn = "pv:category ?order";
		break;
	case 3:
		$sortColumn = "pv:motivation ?order";
		break;
	case 4:
		$sortColumn = "pv:location ?l";
		$locationOrder = ". {?l pv:unstructuredLocation ?order}";
		break;
	case 5:
		$sortColumn = "pv:fatalities ?f";
		$fatalitiesOrder = ". {?f pv:fatalitiesValue ?order}";
		break;
	case 6:
		$sortColumn = "pv:source ?f";
		break;
	case 7:
		$sortColumn = "pv:description ?order";
		break;
}


$x = new SparqlBridge("http://dacura.cs.tcd.ie:3030/politicalviolence/query");
$y = new NSURI();
$ent = $y->getnsuri("pv")."PoliticalViolenceEvent";
$source = "http://dacura.cs.tcd.ie:3030/politicalviolence/query";
$schema_graph = "http://dacura.cs.tcd.ie/data/politicalviolence";
$data_graph = "http://dacura.cs.tcd.ie/data/politicalviolence/uspv";

//query builder
//Builds queries for both getting the data and getting the record count.
//The latter is important as only a small number of possible records are returned each time.
$search = "PREFIX pv-ns: <http://dacura.cs.tcd.ie/data/politicalviolence/>
		PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>

		SELECT ?id{
			GRAPH pv-ns:uspv{
				{?id a pv:PoliticalViolenceEvent;
				$category
				$motivation
				$startdate
				$loc
				$sortColumn}
				$loc2 $startdate2 $fatalitiesOrder $locationOrder
				$startdatefilter $enddatefilter
				}
			}
			ORDER BY $sortDirection($order)
			LIMIT $numRec
			OFFSET $startRec";

$countQuery = "PREFIX pv-ns: <http://dacura.cs.tcd.ie/data/politicalviolence/>
			PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>

			SELECT count(?id){
				GRAPH pv-ns:uspv{
					{?id a pv:PoliticalViolenceEvent;
					$category
					$motivation
					$startdate
					$loc
					$sortColumn}
					$loc2 $startdate2 $enddate2 $fatalitiesOrder $locationOrder
					$startdatefilter $enddatefilter
					}
				}";

$count = $x->askSparql($countQuery);
$rowCount = $count->rows['0']['.1']['value'];

$idArray = $x->getOrderedInstanceIDs($search);
echo sizeof($idArray);
$valueArray = array();
$resultArray = array();

//take returned array of IDs, query each for its data, and add it to the array of values
foreach ($idArray as $id){
	$x = new EventRecord($id);
	$x->setDataSource($source, $schema_graph, $data_graph);
	$x->loadFromDB(false);
	$data = $x->getAsArray();
	$data["idValue"] = $id;
	$valueArray[$id] = $data;
}

//turns the array of values into an array for datatables
foreach ($valueArray as $unordered){
	$a = array();
	$cat = key($unordered["category"]["values"]);
	$mot = key($unordered["motivation"]["values"]);
	$motCount = count($unordered["motivation"]["values"]);
	$id = $unordered["idValue"];// . "t";
	$a["id"] = $id;
	$a["date"] = $unordered["edate"]["values"]["$id"."t"]["year"];
	$a["category"] = $unordered["category"]["values"][$cat]["label"];
	foreach ($unordered["motivation"]["values"] as $motivation){
		if(isset($a["motivation"])){
			$a["motivation"] .= ", " . $motivation["label"];
		}else{
			$a["motivation"] = $motivation["label"];
		}
	}
	if(!isset($a["motivation"])){
		$a["motivation"] = "";
	}
	$a["location"] = $unordered["location"]["values"]["$id"."l"]["unstructured"];
	$a["fatalities"] = $unordered["fatalities"]["values"]["$id"."f"]["unstructured"];
	$a["source"] = $unordered["source"]["values"]["$id"."s"]["unstructured"];
	$a["description"] = $unordered["description"]["values"]["0"];
	$resultArray[] = $a;
}

//formatting relevant data for the JSON datatables needs
$output = array(
	"sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $rowCount,
    "iTotalDisplayRecords" => $rowCount,
    "aaData" => $resultArray,
    "search" => $search,
    "values" => $valueArray
);