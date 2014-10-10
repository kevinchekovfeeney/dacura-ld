<?php
//Start: 23 May 2014 1:58
require_once("SparqlBridge.php");
require_once("NSURI.php");
require_once("EventRecord.php");

$reference_links[] = "http://en.wikipedia.org/wiki/List_of_incidents_of_civil_unrest_in_the_United_States";

$event_filter = obtainEvent();

obtainReferences($reference_links);

function obtainReferences($reference_links) {
	foreach($reference_links as $ref) {
		$result = file_get_contents($ref);
		dump($result);
		
	}
}

function obtainEvent() {
	$ev = new stdClass();
	$ev->id = "1";
	$ev->category = "riot";
	$ev->month = "September";
	$ev->year = 1885;
	$ev->location = "Rock Springs, WY";
	return $ev;
}

function dump($var) {
echo "<pre>";
var_dump($var);
echo "</pre>";
}

?>