<?php
/*
geoquery.php
------------
Queries the triplestore to get counts of Political Violence Events on a per-state basis.
Returns a JSON array of states and counts (blank object if null returned by RDF endpoint).
TODO: Fix filters for fatalities (currently bug returns weird results.)
*/
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");
error_reporting(0);

$x = new SparqlBridge("http://dacura.cs.tcd.ie:3030/politicalviolence/query");
$y = new NSURI();
$ent = $y->getnsuri("pv")."PoliticalViolenceEvent";
$source = "http://dacura.cs.tcd.ie:3030/politicalviolence/query";
$schema_graph = "http://dacura.cs.tcd.ie/data/politicalviolence";
$data_graph = "http://dacura.cs.tcd.ie/data/politicalviolence/uspv";
$result = Array();
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

//Setting variables passed to the query form.
if(isset($_GET['startDate'])){
	$startyear = intval($_GET['startDate']);
	$startdatefilter = "FILTER (?x >= \"$startyear\"^^<http://www.w3.org/2001/XMLSchema#gYear>)";
}else{
	$startdatefilter = "";
}

if(isset($_GET['endDate'])){
	$endyear = intval($_GET['endDate']);
	$enddatefilter = "FILTER (?x <= \"$endyear\"^^<http://www.w3.org/2001/XMLSchema#gYear>)";
}else{
	$enddatefilter = "";
}

if(isset($_GET['motivation'])){
	$motivation = $_GET['motivation'];
	$motivationfilter = "; pv:motivation pv:$motivation";
}else{
	$motivationfilter = "";
}

if(isset($_GET['category'])){
	$category = $_GET['category'];
	$categoryfilter = "; pv:category pv:$category";
}else{
	$categoryfilter = "";
}

if(isset($_GET['categories'])){
	$categories = explode(",", $_GET['categories']);
}

if(isset($_GET['minFatalities'])){
	$minfatalities = intval($_GET['minFatalities']);
	$minfatalitiesfilter = "FILTER (?fv >= \"$minfatalities\"^^xsd:integer)";
}else{
	$minfatalitiesfilter = "";
}

if(isset($_GET['maxFatalities'])){
	$maxfatalities = intval($_GET['maxFatalities']);
	$maxfatalitiesfilter = "FILTER (?fv <= \"$maxfatalities\"^^xsd:integer)";
}else{
	$maxfatalitiesfilter = "";
}

if (count($categories) > 0) {
	$categoryfilter = "";
	for ($c = 0; $c < count($categories)-1; $c++) {
		$categoryfilter .= " ?cat = pv:".$categories[$c]." || ";
	}
	$categoryfilter .= " ?cat = pv:".$categories[count($categories)-1];
	//Query to count the number of results per state
	$geoQuery = "PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>
				PREFIX owltime: <http://www.w3.org/2006/time#>
				PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

				SELECT ?p (COUNT(?p) as ?pCount) WHERE{
					GRAPH <http://dacura.cs.tcd.ie/data/politicalviolence/uspv>{
						{?id a pv:PoliticalViolenceEvent;
						pv:location ?l;
						pv:atTime ?yd;
						pv:fatalities ?f;
						pv:category ?cat;
						$motivationfilter}
						. {?l pv:dbpediaLocation ?p}
						. {?yd owltime:hasDateTimeDescription ?y}
						. {?y owltime:year ?x}
						. {?f pv:fatalitiesValue ?fv}
						$startdatefilter
						$enddatefilter
						$minfatalitiesfilter
						$maxfatalitiesfilter
					} FILTER( $categoryfilter )
				}

				GROUP BY ?p
				ORDER BY asc(?p)";
				
	$query = $x->askSparql($geoQuery);
	$length = count($query->rows);
	if(isset($query->rows[0]["p"])){
		for($i=0;$i<$length;$i++){
			$URIname = $query->rows[$i]["p"]["value"];
			$splitname = explode("/", $URIname);
			$name = $splitname[4];
			$abbr = array_keys($states,$name);
			$result[$i]["name"] = $abbr[0];
			$result[$i]["count"] = $query->rows[$i]["pCount"]["value"];
		}
	}else{
		//give a blank array
	}
} else {
//Query to count the number of results per state
$geoQuery = "PREFIX pv: <http://dacura.cs.tcd.ie/data/politicalviolence#>
			PREFIX owltime: <http://www.w3.org/2006/time#>
			PREFIX xsd: <http://www.w3.org/2001/XMLSchema#>

			SELECT ?p (COUNT(?p) as ?pCount) WHERE{
				GRAPH <http://dacura.cs.tcd.ie/data/politicalviolence/uspv>{
					{?id a pv:PoliticalViolenceEvent;
					pv:location ?l;
					pv:atTime ?yd;
					pv:fatalities ?f
					$categoryfilter
					$motivationfilter}
					. {?l pv:dbpediaLocation ?p}
					. {?yd owltime:hasDateTimeDescription ?y}
					. {?y owltime:year ?x}
					. {?f pv:fatalitiesValue ?fv}
					$startdatefilter
					$enddatefilter
					$minfatalitiesfilter
					$maxfatalitiesfilter
				}
			}

			GROUP BY ?p
			ORDER BY asc(?p)";
			
$query = $x->askSparql($geoQuery);
$length = count($query->rows);

//Formats the JSON from the API into a suitable format.
if(isset($query->rows[0]["p"])){
	for($i=0;$i<$length;$i++){
		$URIname = $query->rows[$i]["p"]["value"];
		$splitname = explode("/", $URIname);
		$name = $splitname[4];
		$abbr = array_keys($states,$name);
		$result[$i]["name"] = $abbr[0];
		$result[$i]["count"] = $query->rows[$i]["pCount"]["value"];
	}
}else{
	//give a blank array
}
}


//opr($result);
echo json_encode($result);