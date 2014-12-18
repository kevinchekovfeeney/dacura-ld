<?php

/*
 * The service for scraping datasets from the seshat wiki
 *
 * Created By: Odhran
 * Creation Date: 20/11/2014
 * Contributors: Chekov
 * Modifications: 20/11/2014 - 07/12/2014
 * Licence: GPL v2
 */


require_once("phplib/DacuraServer.php");
require_once('files/chekovparse.php');

require_once('files/seshat-parser.php');
require_once('files/seshat-parser-helper.php');

//need to do logging, cookiejar, etc.

class ScraperDacuraServer extends DacuraServer {
	
	var $ch; //curl handle
	var $cookiejar = "C:\\Temp\\dacura\\cookiejar.txt";
	var $parser_service_url = 'http://localhost:1234/parser';
	var $username = 'gavin';
	var $password = 'cheguevara';
	var $loginUrl = 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin';
	var $mainPage = 'http://seshat.info/Main_Page';

	var $sectionsWithImplicitSubsections = array(
			"Polity variables" => array("Scope of the central government", "Taxation", "Type of taxes", "Taxes are imposed on"), 
			"Agriculture" => array("Shifting cultivation", "Agricultural technology", "Cultivators / Agri-businesses", "Soil preparation", "Carbohydrate source #1", "Carbohydrate source #2"
	));
	var $subsectionsWithImplicitSubsections = array(
			"Specialized Buildings: polity owned" => array("The most impressive/costly building(s)"), 
			"Information" => array("Measurement System", "Writing System", "Kinds of Written Documents"), 
			"Other" => array("Money", "Postal System"), 
			"Military Technologies" => array("Projectiles", "Handheld weapons", "Animals used in warfare", "Armor", "Naval technology", "Fortifications", "Other technologies"), 
			"Largest scale collective ritual of the official cult" => array("Dysphoric elements", "Euphoric elements", "Cohesion", "Costs of participation"),
			"Most widespread collective ritual of the official cult" => array("Dysphoric elements", "Euphoric elements", "Cohesion", "Costs of participation"),
			"Most frequent collective ritual of the official cult" => array("Dysphoric elements", "Euphoric elements", "Cohesion", "Costs of participation"),
			"Most euphoric collective ritual of the official cult" => array("Dysphoric elements", "Euphoric elements", "Cohesion", "Costs of participation"),
			"Most dysphoric collective ritual" => array("Dysphoric elements", "Euphoric elements", "Cohesion", "Costs of participation"),
			"Production" => array("Agriculture intensity", "Food storage"), 
			"Technology" => array("Metallurgy"),
			"Specialized buildings that are not polity-owned" => array("The most impressive/costly building(s)"),
			"Trade" => array("Main imports", "Main exports", "Land transport"),
			"Structural Inequality" => array("Legal distinctions between"),
			"Kinship" => array("Marriage customs"),
			"Punishment" => array("Execution can be imposed by", "Exile can be imposed by", "Corporal punishment can be imposed by", "Ostracism can by imposed by", "Seizure of property can by imposed by", "Supernatural sanctions can be imposed by"),
			"Other" => array("Consumption")				
	);
	
	/*
	 * Public Functions
	 */
	
	function init(){
		return $this->login();		
	}
	
	/*
	 * Fetches the list of NGAs from the Seshat Main page (the World-30 sample table)
	 * Returns an array of URLs
	 */
	function getNGAList(){
		curl_setopt($this->ch, CURLOPT_URL, $this->mainPage);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve nga list page $this->mainPage. ", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		$dom = new DOMDocument;
		$dom->loadXML($content);
		$xpath = new DOMXPath($dom);
		$links = $xpath->query('//table[@class="wikitable"][1]//a/@href');
		$ngaURLs = array();
		foreach($links as $link){
			$x = $link->value;
			$url = 'http://seshat.info'.$x;
			$ngaURLs[] = $url;
		}
		return $ngaURLs;
	}
	
	
	/*
	 * Fetches a list of polities from a Seshat NGA page
	 * Takes the URL of the page
	 * Returns an array of URLs
	 */
	function getPolities($pageURL){
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		$dom = new DOMDocument;
		if(!$dom->loadXML($content)){
			return $this->failure_result("$pageURL did not parse correctly", 503);
		}
		$xpath = new DOMXPath($dom);
		$links = $xpath->query('//a/@href');
		foreach($links as $link){
			$x = $link->value;
			$url = 'http://seshat.info'.$x;
			$polities[] = $url;
		}
		return $polities;
	}
	
	
	/**
	 * Produces a dump of the NGA / polity sets passed in
	 * @param $data associative array of NGA name -> polity URL
	 */
	function getDump($data){
		$polities_retrieved = array();
		$polity_errors = array();
		$rows = array();
		foreach($data as $nga => $polities){
			foreach($polities as $p){
				if(!isset($polities_retrieved[$p])){
					$pfacts = $this->getFactsFromURL($p);
					if($pfacts){
						//if($pfacts["total_variables"] > 0){
							$polities_retrieved[$p] = $pfacts;
						//}
					}
				}
				$rows = array_merge($rows, $this->factListToRows($nga, $p, $polities_retrieved[$p]));
			}
		}
		$headers = array("NGA", "Polity", "Area", "Section", "Variable", "Value From", "Value To",
				"Date From", "Date To", "Fact Type", "Value Note", "Date Note", "Comment");
		echo "<table><tr><th>".implode("</th><th>", $headers)."</th></tr>";
		foreach($rows as $row){
			echo "<tr><td>".implode("</td><td>", $row)."</td></tr>";				
		}
		echo "</table>";
	}
	
	
	function getFactsFromURL($pageURL){
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		return $this->getFactsFromPage($content);
	}
	
	/*
	 * Takes a seshat page and extracts all of the facts 
	 * Takes the URL of the page
	 * Returns a fact list object (associative array)
	 */
	function getFactsFromPage($content){
		$fact_list = array( "variables" => array(), "errors" => array(), 
				"title" => "", "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "sections" => array());
		// strip out the non-content
		if(strpos($content, "<div class=\"printfooter\">") && strpos($content, "<h1><span class=\"editsection\">")){
				$content = substr($content, strpos($content, "<h1><span class=\"editsection\">"), strpos($content, "<div class=\"printfooter\">") - strpos($content, "<h1><span class=\"editsection\">"));
		}
		// Divide into main sections....
		$sections = explode("<h2>", $content);
		$i = 0;
		foreach($sections as $sect){
			if(++$i == 1){ 	//variables that appear before the first section -> page level variables 
				$sfl = $this->parseFactsFromString($sect);
				$this->updateFactStats($fact_list, $sfl);
				$fact_list['variables'] = array_merge($fact_list['variables'], $sfl['variables']);
			}
			else {
				//divide into sub-sections
				$sec_bits = explode("</span></h2>", $sect);
				if(count($sec_bits) == 2){
					$sec_title = substr($sec_bits[0], strrpos($sec_bits[0], ">")+1);
					$sec_content = $sec_bits[1];
					$subsects = explode("<h3>", $sec_content);
					$j = 0;
					foreach($subsects as $subsect){
						if(++$j == 1){ //sub-section level variables
							$sec_fact_list = $this->parseFactsFromString($subsect);
							$sec_fact_list['sections'] = array();
						}
						else {
							$subsec_bits = explode("</span></h3>", $subsect);
							if(count($subsec_bits) == 2){
								$subsec_title = substr($subsec_bits[0], strrpos($subsec_bits[0], ">") + 1);
								$subsec_content = $subsec_bits[1];
								$ssfl = $this->parseFactsFromString($subsec_content);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$sec_fact_list['sections'][$subsec_title] = $ssfl; 
							}
							else {
								$ssfl = $this->parseFactsFromString($subsect);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$sec_fact_list = array_merge($sec_fact_list, $ssfl['variables']);
							}								
						}
					}
					$this->updateFactStats($fact_list, $sec_fact_list);
					$fact_list["sections"][$sec_title] = $sec_fact_list;
					
				}
				else {  //do our best...
					$sfl = $this->parseFactsFromString($sect);
					$this->updateFactStats($fact_list, $sfl);
					$fact_list['variables'] = array_merge($fact_list['variables'], $sfl['variables']);
				}
			}
		}
		return $fact_list;
	}

	function updateFactStats(&$myfl, $newfl){
		$myfl['total_variables'] += $newfl['total_variables'];
		$myfl['empty'] += $newfl['empty'];
		$myfl['complex'] += $newfl['complex'];
		$myfl['lines'] += $newfl['lines'];
		$myfl['errors'] = array_merge($myfl['errors'], $newfl['errors']);
	}

	/*
	 * Creates an array of name => value for variables found in the text
	 * If there are repeated keys, it appends _n to the duplicates to retain the values.
	 * Returns a FactList...
	 */
	function parseFactsFromString($str){
		$pattern = '/\x{2660}([^\x{2660}\x{2665}]*)\x{2663}([^\x{2660}]*)\x{2665}/u';
		$matches = array();
		$factoids = array();
		$res = array("variables" => array(), "errors" => array(), "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
		if(preg_match_all($pattern, $str, $matches)){
			for($i = 0; $i< count($matches[0]); $i++){
				$key = $matches[1][$i];
				$val = trim($matches[2][$i]);
				if(isset($factoids[$key])){
					$n = 1;
					while(isset($factoids[$key."_$n"])){
						$n++;
					}
					$key = $key."_$n";
				}
				$res['total_variables']++;
				if($val == ""){
					$factoids[$key] = "";
					$res['empty']++;
				}
				else {
					$parsedVals = $this->parseVariableValue($val);
					if(count($parsedVals) > 1){
						$res['complex']++;
						$res['lines'] += count($parsedVals);
					}
					else {
						$factoid = $parsedVals[0];
						if($factoid['fact_type'] == "complex"){
							$res['complex']++;
						}
						if($factoid['value_type'] == "error"){
							$res['errors'][] = $factoid;
						}
						else {
							$res['lines']++;
						}
					}
					$factoids[$key] = $parsedVals;
				}
			}
		}
		$res["variables"] = $factoids;
		return $res;
	}
	

	/**
	 *
	 * @param string $from (date that value holds from)
	 * @param string $to (date that value holds to)
	 * @param string $dtype (date type: simple, range, disputed, uncertain)
	 * @param string $vfrom (value of variable - from value if range)
	 * @param string $vto (to value when range of variable.
	 * @param string $vtype (value type -> disputed, range, uncertain)
	 * @param string $facttype (complex | simple | error)
	 * @param string $comment
	 * @return associative array with a key for each parameter
	 */
	function createFactoid($from, $to, $dtype, $vfrom, $vto, $vtype, $facttype, $comment = ""){
		return array("date_from" => $from, "date_to" => $to, "date_type" => $dtype,
				"value_from" => $vfrom, "value_to" => $vto, "value_type" => $vtype,
				"fact_type" => $facttype, "comment" => $comment);
	}
	
	/*
	 * These Functions are for navigating the parse tree..
	 */
	
	/**
	 * 
	 * @param string $t the raw text value of the variable 
	 * @return simple array of 'factoids' -> basic information about the fact in a table format
	 */
	function parseVariableValue($t){
		$val = strip_tags($t);
		$val = trim(html_entity_decode($val));
		$factoids = array();
		if(strpbrk($val, ":;[{")){
			$p = new seshat2Parsing($val);
			$parsedFact = $p->match_fact();
			if($parsedFact){
				if($parsedFact['text'] == $val){
					$fragment_contents = $parsedFact['value'];
					if(isset($fragment_contents[0])){ //array of fragments...
						$fragments = $fragment_contents;
					}
					else {
						$fragments = array($fragment_contents);
					}
					foreach($fragments as $f){
						if($f['value']['name'] == "undatedfact"){
							$mini_factoids = $this->processUndatedFact($f['value']['value']);
							foreach($mini_factoids as $mf){
								$factoids[] = $this->createFactoid("", "", "", $mf['value_from'], $mf['value_to'], $mf['value_type'], "complex", "");
							}
						}
						else {
							$factoids = array_merge($factoids, $this->processDatedFact($f['value']['value']));
						}
					}
				}
				else {
					$x = "The parser managed to parse this fragment: " .$parsedFact['text'];
					$factoids[] = $this->createFactoid("", "", "", $val, "", "error", "complex",
							"The parser could not parse this fact [$val] - it has a formatting error. $x" );
				}
			}
			else {
				$factoids[] = $this->createFactoid("", "", "", $val, "", "error", "complex",
						"The parser could not parse this fact [$val] - it has a formatting error");
			}
		}
		else {
			$factoids[] = $this->createFactoid("", "", "", $val, "", "simple", "simple", "");
		}
		return $factoids;
	}

	/*
	 * Takes a $fact (node from parse tree) representing a dated fact
	 * Returns an array of factoids. 
	 */
	
	function processDatedFact($fact){
		foreach($fact as $factbit){
			if($factbit['name'] == "undatedfact"){
				$factoids = $this->processUndatedFact($factbit['value']);
			}
			elseif($factbit['name'] == "datevalue"){
				$datebit = $this->processDateValue($factbit['value']);
			}
		}
		foreach($factoids as $i => $factoid){
			$factoids[$i]['fact_type'] = "complex";
			$factoids[$i]["date_type"] = $datebit['type'];
			$factoids[$i]["comment"] .= (isset($datebit['comment']) ? $datebit['comment'] : "");
			if($datebit["type"] == "range"){
				$factoids[$i]["date_to"] = $datebit['to'];
				$factoids[$i]["date_from"] = $datebit['from'];
			}
			else {
				$factoids[$i]["date_from"] = $datebit['value'];
			}
		}
		return $factoids;
	}
	
	
	
	/*
	 * Undated facts can be either string | complex
	 */
	function processUndatedFact($fact){
		$factoids = array();
		if($fact['name'] == 'string'){
			$factoids[] = $this->createFactoid("","", "", $fact['text'], "", "simple", "", "");
		}
		elseif($fact['name'] == 'uncertainrange') {
			$factoids[] = $this->createFactoid("","", "", $fact['value'][0]['text'], $fact['value'][1]['text'], "range", "complex", "");
		}
		elseif(($fact['name'] == 'disagreelist') ){
			foreach($fact['value'] as $i => $disagreefragment){			
				if(($disagreefragment['value']['name']) == "string"){
					$factoids[] = $this->createFactoid("","", "", $disagreefragment['value']['text'], "", "disputed", "complex", "");	
				}
				else {
					$factoids[] = $this->createFactoid("","", "", $disagreefragment['value']['value'][0]['text'], $disagreefragment['value']['value'][1]['text'], "disputed", "complex", "");	
				}
			}
		}
		else {
			foreach($fact['value'] as $i => $possibleval){
				$factoids[] = $this->createFactoid("","", "", $possibleval['text'], "", "uncertain", "complex", "The fact is uncertain");
			}
		}
		return $factoids;
	}
	
	/*
	 * For processing the date part of a factoid
	 */
	function processDateValue($fact){
		$datevals = array();
		$date_comment = "";
		if($fact['name'] == "daterange"){
			$from = $fact['value'][0];
			$to = $fact['value'][1];
			$from = $this->processDateValue($from);
			$to = $this->processDateValue($to);
			if($to['type'] == 'disputed' && $from['type'] == 'disputed' ){
				$date_comment = "Both from and to dates are disputed";
			}
			elseif($from['type'] == 'disputed'){
				$date_comment = "The from date is disputed";
			}
			elseif($to['type'] == 'disputed'){
				$date_comment = "The to date is disputed";
			}
			if($from['type'] == "uncertain" && $to['type'] == 'uncertain'){
				$date_comment = "Both from and to dates are uncertain";
			}
			elseif($from['type'] == 'uncertain'){
				$str = "The from date is uncertain";
				$date_comment .= (strlen($date_comment) == 0) ? $str : " - " . $str;
			}
			elseif($to['type'] == 'uncertain'){
				$str = "The to date is uncertain";
				$date_comment .= (strlen($date_comment) == 0) ? $str : " - " . $str;
			}
			$datevals = array("from" => $from['value'], "to" => $to['value'], "type" => "range", "comment" => $date_comment);
		}
		elseif($fact['name'] == "singledate"){
			//singledate: value:uncertaindate | value:disagreedate | value:simpledate
			if($fact['value']['name'] == "simpledate"){
				$datevals = array("type" => "simple", "value" => $fact['value']['text'], "comment" => "");
			}
			elseif($fact['value']['name'] == "disagreedate"){
				$datevals = $this->consolidateDates($fact['value']['value']);
				//$datevals = array("type" => "disputed", "value" => "xxx", "comment" => "Date is disputed");
			}
			elseif($fact['value']['name'] == "uncertaindate"){
				//just make a range from the first to the last..
				$dstr = $fact['value']['value'][0]['text']."-";
				$dstr .= $fact['value']['value'][count($fact['value']['value'])-1]['text'];
				$datevals = array("type" => "uncertain", "value" => $dstr, "comment" => "Date is uncertain");
			}
		}
		return $datevals;
	}
	
	function strToYear($str){
		$pattern = "/(\d{1,4})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[2]) && (strcasecmp("bc", $matches[2]) || strcasecmp("bce", $matches[2]))){
				return (0 - $matches[1]);
			}
			else return $matches[1];
		}
		return false;
	}
	
	function consolidateDates($fragments){
		$earliest = false;
		$latest = false;
		foreach($fragments as $frag){
			if($frag['value']['name'] == "simpledate"){
				$x = $this->strToYear($frag['value']['text']);
				if($x !== false){
					if(($earliest === false) && ($latest === false)){
						$earliest = $x;
						$latest = $x;
					}
					else {
						$earliest = ($earliest < $x) ? $earliest : $x;
						$latest = ($latest > $x) ? $latest : $x;
					}
				}
			}
			elseif($frag['value']['name'] == "simpledaterange"){
				$x = $this->strToYear($frag['value']['value'][0]['text']);
				if(($earliest === false) && ($latest === false)){
					$earliest = $x;
					$latest = $x;
				}
				else {
					$earliest = ($earliest < $x) ? $earliest : $x;
					$latest = ($latest > $x) ? $latest : $x;
				}
				$x = $this->strToYear($frag['value']['value'][1]['text']);
				$earliest = ($earliest < $x) ? $earliest : $x;
				$latest = ($latest > $x) ? $latest : $x;
			}
		}
		if(($earliest === false) || ($latest === false)){
			return false;
		}
		$date_val = ($earliest < 0) ? (0-$earliest) . "BCE" : $earliest . "CE" ; 
		$date_val .= " - ";
		$date_val .= ($latest < 0) ? (0-$latest) . "BCE" : $latest. "CE"; 
		return array("type" => "disputed", "value" => $date_val, "comment" => "Date is disputed");
	}
	
	
	/*
	 * deals with the problem of missing suffixes in ranges
	 * If a suffix is missing, copy it from the other half of the range
	 * Also, ensure that left < right
	 */
	function getDateRangeBounds($dr1, $dr2){
		$bounds = array();
		$pattern = "/(\d{1,4})\s*(ce|bce|bc)?/i";
		$matches = array();
		$matches2 = array();
		if(preg_match($pattern, $dr1, $matches)){
			if(!isset($matches[2]) or !$matches[2]){
				if(preg_match($pattern, $dr2, $matches2)){
					if(isset($matches2[2])){
						$dr1 .= $matches2[2];
					}
					else {
						return false;
					}
				}
				else {
					return false;
				}
			}
			else {
				if(preg_match($pattern, $dr2, $matches2)){
					if(!isset($matches2[2]) or !$matches2[2]){
						$dr2 .= $matches[2];
					}
					else {
						return false;
					}
				}
				else {
					return false;
				}
			}
		}
		if($this->strToYear($dr1) > $this->strToYear($dr2)){
			return array($dr2, $dr1);
		}
		return array($dr1, $dr2);
	}
	
	/*
	 * Functions for converting lists of facts into tabular format...
	 */
	
	/**
	 * @param string $nga - the name of the NGA that the factlist belongs to
	 * @param url $polity - the url of the polity page that the factlist belongs to
	 * @param factlist $fl - fact list object - as returned by getFactsFromPage
	 * @param boolean  $include_empties - include variables with empty values
	 * @return array of rows -> each row is a factoid
	 */
	function factListToRows($nga, $polity, $fl, $include_empties = false){
		$rows = array();
		if(!isset($fl["sections"]) or count($fl["sections"]) == 0){
			return $rows;
		}
		foreach($fl["sections"] as $sname => $section){
			foreach($section["variables"] as $varname => $varvals){
				if(is_array($varvals)){
					foreach($varvals as $val){
						$rows[] = array($nga, $polity, $sname, "", $varname, $val['value_from'], $val['value_to'], $val['date_from'], $val['date_to'], $val['fact_type'], $val['value_type'], $val['date_type'], $val['comment']);
					}
				}
				else {//empty
					if($include_empties){
						$rows[] = array($nga, $polity, $sname, "", $varname, "", "", "", "", "empty", "", "", "");
					}
				}
			}
			if(isset($section["sections"])){
				foreach($section["sections"] as $subsname => $subsection){
					foreach($subsection["variables"] as $varname => $varvals){
						if(is_array($varvals)){
							foreach($varvals as $val){
								$rows[] = array($nga, $polity, $sname, $subsname, $varname, $val['value_from'], $val['value_to'], $val['date_from'], $val['date_to'], $val['fact_type'], $val['value_type'], $val['date_type'], $val['comment']);
							}
						}
						else {//empty
							if($include_empties){
								$rows[] = array($nga, $polity, $sname, $subsname, $varname, "", "", "", "", "empty", "", "", "");
							}
						}
					}
				}
			}
		}
		return $rows;
	}	
	
	/**
 	 * @param string $nga - the name of the NGA that the factlist belongs to
	 * @param url $polity - the url of the polity page that the factlist belongs to
	 * @param factlist $fl - fact list object - as returned by getFactsFromPage
	 * @param string $type - html | tsv -> type of table to produce
	 * @param boolean  $include_empties - include variables with empty values
	 * @return array of rows -> each row is a factoid
	 */
	function factListToTable($nga, $polity, $fl, $type="html", $include_empties = false){
		$headers = array("NGA", "Polity", "Area", "Section", "Variable", "Value From", "Value To", 
				"Date From", "Date To", "Fact Type", "Value Note", "Date Note", "Comment");
		if($type == "html"){
			$op = "<table><theader><tr><th>";
			$op .= implode("</th><th>", $headers)."</th></tr></theader>";
		}
		else {
			$op = implode("\t", $headers)."\n";
		}
		$rows = $this->factListToRows($nga, $polity, $fl, $include_empties);
		foreach($rows as $row){
			if($type == "html"){
				$op .= "<tr><td>".implode("</td><td>", $row)."</td></tr>";
			}
			else {
				$op .= implode("\t", $row)."\n";
			}	
		}
		if($type == "html"){
			$op .= "</table>";
		}
		return $op;
	}
	
	function testParser(){
		$teststrings = array( "axy",
			"axy; afa",
			"[bbb; dda]",
			"[by soldiers; by state]",
			"[zaa-afa]",
			"{zaa; afa}",
			"5,300,000: 120bce",
			"5,300,000: 220bce-7bce",
			"5,300,000: 120bce-75bce; 6,100,000:75-30ce",
			"[1,500,000 - 2,000,000]: 100bce",
			"absent: 500bc-{150bc;90bc}; present: {150bc;90bc}-1ce",
			"absent: 500bc-150bc; [absent; present]:150bc-90bc; present: 90bc-1ce",
			"absent: [600bce;500bc]-{150bc;90bc;40bc}; [present;big;small]: {150bc;90bc}-{1;67ce}", 
			"{[180,000-270,000]; 604,000}: 423 CE",
			"present: {1380-1450 CE; 1430-1450 CE; 1350-1450 CE}",
			"absent: {380-450 CE; 1450 CE; 150 CE}"
		);
		foreach($teststrings as $t){
			opr($this->parseVariableValue($t));			
		}
	}		
	
	function parsePage($data){
		$this->ch = curl_init();
		curl_setopt($this->ch, CURLOPT_URL, $this->parser_service_url);	
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
		$content = curl_exec($this->ch);
		if(!$content){
			return $this->failure_result("Failed to parse page.", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		if(substr($content, -1) == "1"){
			$content = substr($content, 0, -1);
		}
		return $content;
	}
	
	/*
	 * Data is an array of {nga_name => [polityurl1, polityurl2, ...]} 
	 */
/*	function getDump($data){
		$ngaReport = array();
		$ngaList = array();
		$all = false;
		$output = "NGA\tPolity\tName\tKey\tValue\tError\n";
		$errors = "<html><head><title>Seshat Error Report</title></head><body><table>";
		$errorArray = array();
		$report = array();
		$report['entries'] = 0;
		for($i = 0; $i < count($data); $i++){
			$report['entries']++;
			$x = $data[$i];
			$ngaOut = $x->metadata->nga;
			if(!in_array($ngaOut, $ngaList)){
				$ngaList[] = $ngaOut;
				$ngaReport[$ngaOut] = array();
				$ngaReport[$ngaOut]["nga"] = $ngaOut;
				$ngaReport[$ngaOut]["polityCount"] = 0;
				$ngaReport[$ngaOut]["totalCount"] = 0;
				$ngaReport[$ngaOut]["nonZeroCount"] = 0;
				$ngaReport[$ngaOut]["parseCount"] = 0;
				$ngaReport[$ngaOut]["successCount"] = 0;
				$ngaReport[$ngaOut]["failureCount"] = 0;
			}
			$ngaReport[$ngaOut]["polityCount"] += 1;
			$polityOut = $x->metadata->polity;
			$polityURL = $x->metadata->url;
			$output = $output.$ngaOut."\t".$polityOut."\n";
			if(isset($x->data)){
				foreach($x->data as $item){
					$ngaReport[$ngaOut]["totalCount"] += 1;
					if($all === true or $item->value[1] != ""){
						$ngaReport[$ngaOut]["nonZeroCount"] += 1;
						if(gettype($item->value[1]) == "array"){
							if($item->error === True){
								$errors = $errors."<tr><td><a href='".$polityURL."'>".$polityOut."</a></td><td>".$item->value[0]."</td><td>".$item->errorMessage."</td></tr>";
								$item->url = $polityURL;
								$item->polity = $polityOut;
								$errorArray[] = $item;
							}
							if($item->error === True){
								$output = $output.$ngaOut."\t".$polityOut."\t".$item->value[0]."\t\t".$item->value[1][0]."\t".$item->errorMessage."\n";
							}else{
								// $output = $output."\t".$item->value[1][0]."\t".json_encode($item->value[1][1])."\n";
								$parsedValues = $this->formatValue($item->value[1][1], $item->value[1][0]);
								for($j = 0;$j < count($parsedValues);$j++){
									if(gettype($parsedValues[$j]) == "array"){
										$output = $output.$ngaOut."\t".$polityOut."\t".$item->value[0]."\t".trim($parsedValues[$j][0])."\t".trim($parsedValues[$j][1])."\t".$item->errorMessage."\n";
									}else{
										$output = $output.$ngaOut."\t".$polityOut."\t".$item->value[0]."\t\t".$parsedValues[$j]."\t".$item->errorMessage."\n";
									}
								}
							}
							//$output = $output.$ngaOut."\t".$polityOut."\t".$item->value[0]."\t".$item->value[1][1]."\t".$item->errorMessage."\n";
							$ngaReport[$ngaOut]["parseCount"] += 1;
							if($item->error === True){
								$ngaReport[$ngaOut]["failureCount"] += 1;
							}else{
								$ngaReport[$ngaOut]["successCount"] += 1;
							}
						}else{
							$output = $output.$ngaOut."\t".$polityOut."\t".$item->value[0]."\t".$item->value[1]."\t".$item->errorMessage."\n";
						}
					}
				}
			}else{
				//do nothing
				
			}
			$output = $output."\n";
		}
		$errors = $errors."</table></body></html>";
		$fileName = $this->log("dump", $output);
		$errorFile = $this->log("dumperrors", $errors);
		$fileurl = $this->getURLofLogfile($fileName);
		$report["filename"] = $fileName;
		$report["fileurl"] = $fileurl;
		$report["errorfile"] = $errorFile;
		$report["contents"] = $ngaReport;
		$report["errors"] = $errorArray;
		return $report;
	}
	*/

	
	
	function login(){
		$this->ch = curl_init();
		//initial curl setting
		curl_setopt($this->ch, CURLOPT_URL, $this->loginUrl);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookiejar);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		if(isset ($this->settings['http_proxy']) && $this->settings['http_proxy']){
			curl_setopt($this->ch, CURLOPT_PROXY, $this->settings['http_proxy']);
		}
		
		//get token from login page
		$store = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$store){
			return $this->failure_result("Failed to retrieve login page $this->loginUrl. ", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));				
		}
		$loginToken = false;
		$dom = new DOMDocument;
		$dom->loadHTML($store);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query('//input');
		foreach($nodes as $node) {
			$nodename = $node->getAttribute('name');
			if($nodename == 'wpLoginToken'){
				$loginToken = $node->getAttribute('value');
			}
		}
		if(!$loginToken){
			return $this->failure_result("Failed to find login token on login page $this->loginUrl. ", 404);				
		}
		//login
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, 'wpName='.$this->username.'&wpPassword='.$this->password.'&wpLoginAttempt=Log+in&wpLoginToken='.$loginToken);
		$store = curl_exec($this->ch);
		$http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($http_status >= 400){
			return $this->failure_result("Failed to login to wiki", 400);
		}
		return true;
	}
}

class ScraperDacuraAjaxServer extends ScraperDacuraServer {
	function failure_result($msg, $code){
		return $this->write_error($msg, $code);
	}
}
