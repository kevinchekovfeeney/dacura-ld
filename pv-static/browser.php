<?php
require_once("SparqlBridge.php");
require_once("NSURI.php");

$startRec = intval($_GET['iDisplayStart']);
$numRec = intval($_GET['iDisplayLength']);
$sortColumnNumber = intval($_GET['iSortCol_0']);
//Needs to be modified to prevent injection attacks
$sortDirection = $_GET['sSortDir_0'];
//turns sortColumnNumber into sortColumn - replace with function to do properly later
switch($sortColumnNumber){
	case 0:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#startDate>";
		break;
	case 1:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#endDate>";
		break;
	case 2:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#category>";
		break;
	case 3:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#motivation>";
		break;
	case 4:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#location>";
		break;
	case 5:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#fatalities>";
		break;
	case 6:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#source>";
		break;
	case 7:
		$sortColumn = "<http://tcdfame.cs.tcd.ie/data/politicalviolence#description>";
		break;
}


$x = new SparqlBridge("http://tcdfame.cs.tcd.ie:3030/politicalviolence/query");
$y = new NSURI();
$ent = $y->getnsuri("pv")."Event";
$valueArray = $x->getInstanceArray($ent, "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv", "http://tcdfame.cs.tcd.ie/data/politicalviolence", $sortColumn, $startRec, $numRec, $sortDirection);
$labels = $x->getEntityProperties($ent, "http://tcdfame.cs.tcd.ie/data/politicalviolence");
//This needs to be generalised out
$countQuery = "SELECT count(?id){ 
  GRAPH <http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv> {
    ?id a <http://tcdfame.cs.tcd.ie/data/politicalviolence#Event>.
  }
}";
$count = $x->askSparql($countQuery);
$rowCount = $count->rows['0']['.1']['value'];

//echo "<pre>";
//$jam = $x->getInstanceArray($ent, "http://tcdfame.cs.tcd.ie/data/politicalviolence/uspv", "http://tcdfame.cs.tcd.ie/data/politicalviolence", 0, 25);
//print_r($jam);
//echo "</pre>";

$output = array(
	"sEcho" => intval($_GET['sEcho']),
    "iTotalRecords" => $rowCount,	//HACK FOR TEST
    "iTotalDisplayRecords" => $rowCount,//25,	//DITTO
    "aaData" => array()
);

foreach ($valueArray as $key => $v){
	$a = array();
	$a['key'] = $key;
	foreach($labels as $l){
		if (!isset($v[$l['Property']])){
			$a[$l['propertylabel']] = "";
		}else{
			$a[$l['propertylabel']] = $v[$l['Property']];
		}
	}
	$output['aaData'][] = $a;
}


echo json_encode( $output );