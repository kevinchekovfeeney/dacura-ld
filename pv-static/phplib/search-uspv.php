<?php
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");
set_time_limit(1200);

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
//States array
$states = array(
	'AL'=>'Alabama',
	'AK'=>'Alaska',
	'AZ'=>'Arizona',
	'AR'=>'Arkansas',
	'CA'=>'California',
	'CO'=>'Colorado',
	'CT'=>'Connecticut',
	'DE'=>'Delaware',
	'DC'=>'District_of_Columbia',
	'FL'=>'Florida',
	'GA'=>'Georgia',
	'HI'=>'Hawaii',
	'ID'=>'Idaho',
	'IL'=>'Illinois',
	'IN'=>'Indiana',
	'IA'=>'Iowa',
	'KS'=>'Kansas',
	'KY'=>'Kentucky',
	'LA'=>'Louisiana',
	'ME'=>'Maine',
	'MD'=>'Maryland',
	'MA'=>'Massachusetts',
	'MI'=>'Michigan',
	'MN'=>'Minnesota',
	'MS'=>'Mississippi',
	'MO'=>'Missouri',
	'MT'=>'Montana',
	'NE'=>'Nebraska',
	'NV'=>'Nevada',
	'NH'=>'New_Hampshire',
	'NJ'=>'New_Jersey',
	'NM'=>'New_Mexico',
	'NY'=>'New_York',
	'NC'=>'North_Carolina',
	'ND'=>'North_Dakota',
	'OH'=>'Ohio',
	'OK'=>'Oklahoma',
	'OR'=>'Oregon',
	'PA'=>'Pennsylvania',
	'PR'=>'Puerto_Rico',
	'RI'=>'Rhode_Island',
	'SC'=>'South_Carolina',
	'SD'=>'South_Dakota',
	'TN'=>'Tennessee',
	'TX'=>'Texas',
	'UT'=>'Utah',
	'VT'=>'Vermont',
	'VA'=>'Virginia',
	'WA'=>'Washington',
	'WV'=>'West_Virginia',
	'WI'=>'Wisconsin',
	'WY'=>'Wyoming');

//if variables have been passed in by Datatables, then put them in the search
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
	//Check and convert the state in case its on its short formatting
	if (strlen($location) == 2) {
		//Capitalize the state code
		$location = strtoupper($location);
		//Find the state code on the states array
		while (($cur_state = current($states)) !== FALSE) {
			//Obtain and set the state code once its found on the array
			if (key($states) == $location) $location = $cur_state;
			next($states);
		}
	}
	$loc = "pv:location ?l;";
	$loc2 = " . {?l pv:dbpediaLocation <http://dbpedia.org/resource/$location>}";
}

//Needs to be modified to prevent injection attacks
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

//this is going to be off-loaded to the JS - give the js everything it might need, let it pick out what it wants
//turn the array of values into an array for datatables
foreach ($valueArray as $unordered){
	$a = array();
	$cat = key($unordered["category"]["values"]);
	$mot = key($unordered["motivation"]["values"]);
	$motCount = count($unordered["motivation"]["values"]);
	$id = $unordered["idValue"];// . "t";
	$a["id"] = $id;
	$a["date"] = $unordered["edate"]["values"]["$id"."t"]["year"];
	$a["category"] = $unordered["category"]["values"][$cat]["label"];
//	$a["motivation"] = "";
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
	$a["source"] = "";
	$a["description"] = $unordered["description"]["values"]["0"];
	$resultArray[] = $a;
}

//formatting relevant data for the JSON datatables needs
$output = array(
	"sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $rowCount,
    "iTotalDisplayRecords" => $rowCount,
    "aaData" => $resultArray,
    "search" => $search
	//"get" => $_GET,
	//"valueArray" => $valueArray
);

if (isset($_GET['sparql_query']) && $_GET['sparql_query'] == "1") {
	echo $search;
} else {
	echo json_encode( $output );
}