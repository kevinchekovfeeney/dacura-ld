<?php
//Start: 23 May 2014 1:58
require_once("SparqlBridge.php");
require_once("EventRecord.php");
set_time_limit(1200);
$months = array("January"=>"Jan",
				"February"=>"Feb",
				"March"=>"Mar",
				"April"=>"Apr",
				"May"=>"May",
				"June"=>"Jun",
				"July"=>"Jul",
				"August"=>"Aug",
				"September"=>"Sept",
				"October"=>"Oct",
				"November"=>"Nov",
				"December"=>"Dec");
				
$states = array('AL'=>'Alabama',
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
			
$categories = array("riot", "lynching", "terrorism", "war", "assassination", "insurrection", "rampage");

$page = (isset($_GET['page'])?$_GET['page']:0);

$reference_links[] = "http://en.wikipedia.org/wiki/List_of_incidents_of_civil_unrest_in_the_United_States";

$event_filter = obtainEvents();

$event_references = obtainReferences($reference_links);

$event_matches = compareEvents($event_filter, $event_references);

displayMatches($event_matches);

function displayMatches($matches) {
	global $states;
	global $page;
	?>
	<html><head><title>Matching events results</title>
	<style>body{background:#CECECE;}.match{color:green;}.unmatch{color:red;}</style></head><body>
	<center><h1>Event match results</h1></center>
	<center><?php if ($page > 0) echo "<a href=event_linker.php?page=".intval($page-1).">Previous page</a> Page $page "; ?><a href=event_linker.php?page=<?php echo intval($page+1); ?>>Next page</a></center>
	<center><table border="1"><tr><th>Event ID</th><th>Ev. Year/Ref. Year</th><th>Ev. Month/Ref. Month</th><th>Ev. Category/Ref. Category</th><th>Ev. Location/Ref. Location</th><th>Possible links</th></tr>
	<?php
		foreach($matches as $match) {
			$lnk = "";
			$state = array();
			preg_match("/([A-Z]+)$/", $match->location_filter, $state);
			if (isset($state[0])) {
				$match->location_filter = (isset($states[$state[0]])?$states[$state[0]]:$match->location_filter);
			}
			foreach ($match->links as $l) { $lnk .= "<a href=".$l.">".$l."</a><input type=radio name=".$match->id_filter." value=".$l."/><br>"; }
			echo "<tr><td><a href=".$match->id_filter.">".$match->id_filter."</a></td><td class=".(($match->year_filter == $match->year_ref)?"match":"unmatch").">".$match->year_filter." / ".$match->year_ref."</td><td class=".(($match->month_filter == $match->month_ref)?"match":"unmatch").">".$match->month_filter." / ".$match->month_ref."</td><td class=".(($match->category_filter == $match->category_ref)?"match":"unmatch").">".$match->category_filter." / ".$match->category_ref."</td><td class=".((strpos($match->location_ref,$match->location_filter)>-1)?"match":"unmatch").">".$match->location_filter." / ".$match->location_ref."</td><td>".$lnk."</td></tr>";
		}
	?>
	</table></center></body></html>
	<?php
}

function obtainReferences($reference_links) {
	foreach($reference_links as $ref) {
		$result = getPageContent($ref);
		
		//Get the entries on the page that match with the year of the event
		preg_match_all("/(\d{4}\s.*)/i", $result, $output_array);
		
		$event_references = array();
		
		//Find for events on the page and create event references entries
		if (count($output_array) > 0) {
			for ($e = 0; $e < count($output_array[0]); $e++) {
				if (intval(substr($output_array[0][$e], 0, 4)) >= 1701 && intval(substr($output_array[0][$e], 0, 4)) <= 2014) {
					$event_references[] = new stdClass();
					$event_references[count($event_references)-1]->id = count($event_references)-1;
					$event_references[count($event_references)-1]->year = intval(substr($output_array[0][$e], 0, 4));
					$event_references[count($event_references)-1]->month = findMonth($output_array[0][$e]);
					$event_references[count($event_references)-1]->category = findCategory($output_array[0][$e]);
					$event_references[count($event_references)-1]->location = @findLocation($output_array[0][$e]);
					$event_references[count($event_references)-1]->event_name = @findName($output_array[0][$e]);
					$event_references[count($event_references)-1]->links = @findLinks($output_array[0][$e]);
				}
			}
		}
		//Return all the event references found on the page
		return $event_references;
	}
}

function findMonth($text) {
	global $months;
	foreach($months as $m) {
		if (stripos($text, $m)) {
			return array_keys($months, $m)[0];
		} else if (stripos($text, key($months))) {
			return key($months);
		}
	}
	return "N/A";
}

function findCategory($text) {
	global $categories;
	foreach($categories as $c) {
		if (stripos($text, $c)) {
			return $c;
		}
	}
	return "N/A";
}

function findLocation($text) {
	$doc = new DOMDocument();
	$doc->loadHTML($text);
	$links = $doc->getElementsByTagName("a");
	$count = 0;
	foreach ($links as $l) {
		if ($count > 0) {
			return $l->getAttribute("title");
		}
		$count++;
	}
	return "N/A";
}

function findLinks($text) {
	$link_list = array();
	$doc = new DOMDocument();
	$doc->loadHTML($text);
	$links = $doc->getElementsByTagName("a");
	foreach ($links as $l) {
		$link_list[] = "http://en.wikipedia.org".$l->getAttribute("href");
	}
	return $link_list;
}

function findName($text) {
	$doc = new DOMDocument();
	$doc->loadHTML($text);
	$links = $doc->getElementsByTagName("a");
	foreach ($links as $l) {
		return $l->getAttribute("title");
	}
	return "N/A";
}

function getPageContent($page) {
	// Define a context for HTTP.
	$aContext = array(
		 'http' => array(
			 'proxy' => 'tcp://134.226.56.7:8080', // This needs to be the server and the port of the Proxy Server.
			 'request_fulluri' => true,
			 ),
		 );
	$cxContext = stream_context_create($aContext);
	
	//Get the page contents
	return file_get_contents($page, true, $cxContext);
}

function compareEvents($filter, $refs) {
	$matches = array();
	foreach ($filter as $f) {
		foreach ($refs as $ref) {
			if ($f->year == $ref->year) {
				if ($f->month == $ref->month) {
					$match = new stdClass();
					$match->id_filter = $f->id;
					$match->id_ref = $ref->id;
					$match->year_filter = $f->year;
					$match->year_ref = $ref->year;
					$match->month_filter = $f->month;
					$match->month_ref = $ref->month;
					$match->category_filter = $f->category;
					$match->category_ref = $ref->category;
					$match->location_filter = $f->location;
					$match->location_ref = $ref->location;
					$match->links = $ref->links;
					$matches[] = $match;
				}
			}
		}
	}
	return $matches;
}

function obtainEvents() {
	global $months;
	global $page;
	$events = array();
	$mArr = array_keys($months);
	$min = 0;
	$max = 100;
	if (isset($_GET['page'])) { $min = $page*100; $max = $page*100+100; }
	$valueArray = getEvents($min, $max);
	foreach ($valueArray as $event) {
		$ev = new stdClass();
		$ev->id = $event['idValue'];
		$ev->category = $event['category']['values'][key($event['category']['values'])]['label'];
		$ev->month = (isset($event['edate']['values'][key($event['edate']['values'])]['month']))?$mArr[intval($event['edate']['values'][key($event['edate']['values'])]['month'])-1]:"N/A";
		$ev->year = intval($event['edate']['values'][key($event['edate']['values'])]['year']);
		$ev->location = $event['location']['values'][key($event['location']['values'])]['unstructured'];
		$events[] = $ev;
	}
	return $events;
}

function dump($var) {
echo "<pre>";
var_dump($var);
echo "</pre>";
}

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