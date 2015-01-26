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
require_once('files/seshat-parser.php');

//need to do logging, cookiejar, etc.

class ScraperDacuraServer extends DacuraServer {
	
	var $ch; //curl handle

	/*
	 * These are just a note of the variables which have implicit subsections
	 * 
	 * var $sectionsWithImplicitSubsections = array(
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
	);*/
	
	/*
	 * Public Functions
	 */
	
	function seshatInit($action, $object=""){
		$this->init($action, $object);
		return $this->login();		
	}
	
	/*
	 * Fetches the list of NGAs from the Seshat Main page (the World-30 sample table)
	 * Returns an array of URLs
	 */
	function getNGAList(){
		if($this->settings['scraper']['use_cache']){
			$x = $this->fileman->decache("scraper", $this->settings['scraper']['mainPage']);
			if($x) {
				return $x;
			}
		}
		curl_setopt($this->ch, CURLOPT_URL, $this->settings['scraper']['mainPage']);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve nga list page ".$this->settings['scraper']['mainPage'], curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "warning");
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
		if($this->settings['scraper']['use_cache']){
			$this->fileman->cache("scraper", $this->settings['scraper']['mainPage'], $ngaURLs);				
		}
		$this->logEvent("debug", 200, "get_ngas returned: ".count($ngaURLs)." from seshat main page");
		return $ngaURLs;
	}
	
	
	/*
	 * Fetches a list of polities from a Seshat NGA page
	 * Takes the URL of the polity page
	 * Returns an array of URLs
	 */
	function getPolities($pageURL){
		if($this->settings['scraper']['use_cache']){
			$x = $this->fileman->decache("scraper", $pageURL);
			if($x) return $x;
		}
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
		}
		$dom = new DOMDocument;
		if(!$dom->loadXML($content)){
			return $this->failure_result("$pageURL did not parse correctly", 503, "warning");
		}
		$xpath = new DOMXPath($dom);
		$links = $xpath->query('//a/@href');
		foreach($links as $link){
			$x = $link->value;
			$url = 'http://seshat.info'.$x;
			$polities[] = $url;
		}
		if($this->settings['scraper']['use_cache']){
			$this->fileman->cache("scraper", $pageURL, $polities);				
		}
		$this->logEvent("debug", 200, "get_polities returned: ".count($polities)." from $pageURL");
		return $polities;
	}
	
	
	/**
	 * Produces a dump of the NGA / polity sets passed in
	 * @param $data associative array of NGA name -> polity URL
	 */
	function getDump($data){
		$polities_retrieved = array();
		$summaries = array();
		$headers = array("NGA", "Polity", "Area", "Section", "Variable", "Value From", "Value To",
				"Date From", "Date To", "Fact Type", "Value Note", "Date Note", "Comment");
		$error_headers = array("NGA", "Polity", "Section", "Subsection", "Variable", "Value", "Error");
		$stats = array( "ngas" => 0, "polities" => 0, "errors" => 0, "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
		$polity_failures = array();
		$rows = array();
		$this->start_comet_output();
		$error_op = $this->fileman->startServiceDump("scraper", "Errors", "html", true, true);
		$this->fileman->dumpData($error_op, "<table><tr><th>".implode("</th><th>", $error_headers)."</th></tr>");
		$html_op = $this->fileman->startServiceDump("scraper", "Export", "html", true, true);
		$this->fileman->dumpData($html_op, "<table><tr><th>".implode("</th><th>", $headers)."</th></tr>");
		$tsv_op = $this->fileman->startServiceDump("scraper", "Export", "tsv", true, true);
		$this->fileman->dumpData($tsv_op, implode("\t", $headers)."\n");
		foreach($data as $nga => $polities){
			$stats['ngas']++;
			$summary = array("nga" => $this->formatNGAName($nga), "polities" => count($polities), "failures" => 0, 
					"total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "errors" => 0);
			foreach($polities as $p){
				if(!$p) continue;
				if(!isset($polities_retrieved[$p])){
					$this->logEvent("debug", 200, "retrieving facts from $p");
					$pfacts = $this->getFactsFromURL($p);
					if($pfacts){
						$polities_retrieved[$p] = $pfacts;
						//op errors
						foreach($pfacts['errors'] as $e){
							if(isset($e['variable']) && isset($e['section'])){
								$row = "<tr><td><a href='$nga'>". $this->formatNGAName($nga)."</a></td>";
								$row .= "<td><a href='$p'>". $this->formatNGAName($p)."</a></td>";
								$row .= "<td><a href='$p"."#".$this->unformatSectionName($e['section'])."'>".$e['section']."</a></td>";
								$row .= "<td><a href='$p"."#".$this->unformatSectionName($e['subsection'])."'>".$e['subsection']."</a></td>";
								$row .= "<td>".$e['variable']."</td>";
								$row .= "<td>".$e['value']."</td>";
								$row .= "<td>".$e['comment']."</td></tr>";
								$this->fileman->dumpData($error_op, $row);							
							}	
						}
						$stats['polities']++;
						$this->incorporateStats($stats, $pfacts);
						$msg = $this->formatNGAName($p).$this->statsToString($pfacts, $stats);
						$this->write_comet_update("success", $msg);
						$this->timeEvent("Retrieved $p", "debug");
						$this->logEvent("info", 200, "Successfully retrieved $p : (".$pfacts['total_variables']." variables)");
					}
					else {
						//add to failures
						$polity_failures[] = array($nga, $p, $this->errmsg, $this->errcode);
						$msg = $this->formatNGAName($p)." error: $this->errcode $this->errmsg";
						$this->write_comet_update("error", $msg);						
					}
				}
				else {
					$pfacts = $polities_retrieved[$p];
				}
				$this->incorporateStats($summary, $pfacts);
				if(isset($polities_retrieved[$p])){
					$rows = $this->factListToRows($this->formatNGAName($nga), $this->formatNGAName($p), $polities_retrieved[$p]);
					foreach($rows as $row){
						$this->fileman->dumpData($html_op, "<tr><td>".implode("</td><td>", $row)."</td></tr>");				
						$this->fileman->dumpData($tsv_op, implode("\t", $row)."\n");			
					}
				}
			}
			$summaries[] = $summary;
		}
		$this->fileman->dumpData($html_op, "</table>");				
		$this->fileman->dumpData($error_op, "</table>");				
		$this->fileman->endServiceDump($error_op);
		$this->fileman->endServiceDump($html_op);
		$this->fileman->endServiceDump($tsv_op);
		//start op buffering
		ob_start();
		$this->ucontext->renderScreen("results", array("stats" => $stats, "failures" => $polity_failures, "summary" => $summaries,
				"files" => array("errors" => $this->ucontext->my_url("rest")."/view/".$error_op->filename("rest"), "html" => $this->ucontext->my_url("rest")."/view/".$html_op->filename(), "tsv" => $this->ucontext->my_url("rest")."/view/".$tsv_op->filename())));
		$page = ob_get_contents();
		ob_end_clean();
		$this->end_comet_output("success", $page);
		return true;
	}
	
	function getReport($repname){
		$fpath = $this->ucontext->settings['collections_base'];
		if($this->cid()) $fpath .= $this->cid()."/";
		$fpath .= $this->settings['dump_directory'];
		$fname = $fpath . $repname;
		$fsize = filesize($fname);
		$path_parts = pathinfo($fname);
		$ext = strtolower($path_parts["extension"]);
		if ($fd = fopen ($fname, "r")) {
			switch ($ext) {
				case "tsv" : 
					header("Content-type: text/tab-separated-values");
					header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a file download
					header("Content-length: $fsize");
					header("Cache-control: private"); //use this to open files directly
					break;
				case "html" :
					header("Content-type: Content-Type: text/html; charset=utf-8");
					break;
				default;
				    header("Content-type: application/octet-stream");
					header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
					break;
			}
			while(!feof($fd)) {
				$buffer = fread($fd, 2048);
				echo $buffer;
			}
			fclose ($fd);
			$this->logEvent("debug", 200, ($fsize % 1024) ."kb returned for report $repname");
			return true;
		}
		else {
			return $this->failure_result("Could not open requested report file $repname", 404);
		}
	}
	
	
	
	/*
	 * Dump Statistics - showing how many variables have been retrieved
	 */
	
	function statsToString($fact_list, $second_fl = false){
		$html = "<table class='scraper-report'><tr><th>Vars</th><th>Errors</th><th>Empty</th><th>Data</th></tr>";
		$html .= "<tr><td>". $fact_list['total_variables']."</td><td>".count($fact_list['errors'])."</td><td>".$fact_list['empty'];
		$html .= "</td><td>".$fact_list['lines']."</td></tr>";
		$html .= "</table>";
		if($second_fl){
			$html .= "<h4>totals</h4>";
			$html .= "<table class='scraper-report'><tr><th>Vars</th><th>Errors</th><th>Empty</th><th>Data</th></tr>";
			$html .= "<tr><td>". $second_fl['total_variables']."</td><td>".$second_fl['errors']."</td><td>".$second_fl['empty'];
			$html .= "</td><td>".$second_fl['lines']."</td></tr>";
			$html .= "</table>";
		}
		return $html;
	}
	
	function incorporateStats(&$stats, $nfacts){
		$stats['total_variables'] += $nfacts['total_variables'];
		$stats['empty'] += $nfacts['empty'];
		$stats['complex'] += $nfacts['complex'];
		$stats['lines'] += $nfacts['lines'];
		$stats['errors'] += count($nfacts['errors']);
	}
	

	/*
	 * Functions for turning a seshat page into an array of facts...
	 */
	function getFactsFromURL($pageURL){
		if($this->settings['scraper']['use_cache']){
			$x = $this->fileman->decache("scraper", $pageURL);
			if($x) return $x;
		}
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
		}
		$facts = $this->getFactsFromPage($content);
		if($facts && $this->settings['scraper']['use_cache']){
			$this->fileman->cache("scraper", $pageURL, $facts);
		}
		return $facts;
	}
	
	/*
	 * Takes a seshat page and extracts all of the facts 
	 * Takes the URL of the page
	 * Returns a fact list object (associative array)
	 */
	function getFactsFromPage($content){
		$fact_list = array( "variables" => array(), "errors" => array(), 
				"title" => "", "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "sections" => array());
		// strip out the non-content to minimise collision risk
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
							$sec_fact_list = $this->parseFactsFromString($subsect, $sec_title);
							$sec_fact_list['sections'] = array();
						}
						else {
							$subsec_bits = explode("</span></h3>", $subsect);
							if(count($subsec_bits) == 2){
								$subsec_title = substr($subsec_bits[0], strrpos($subsec_bits[0], ">") + 1);
								$subsec_content = $subsec_bits[1];
								$ssfl = $this->parseFactsFromString($subsec_content, $sec_title, $subsec_title);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$sec_fact_list['sections'][$subsec_title] = $ssfl; 
							}
							else {
								$ssfl = $this->parseFactsFromString($subsect, $sec_title);
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
	function parseFactsFromString($str, $section = "", $subsection = ""){
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
							$e = $this->getEmptyError();
							$e['value'] = $factoid['value_from'];
							$e['variable'] = $key;
							$e['section'] = $section;
							$e['subsection'] = $subsection;
							$e['comment'] = $factoid['comment'];								
							$res['errors'][] = $e;
							//$factoid;
						}
						else {
							$factoids[$key] = $parsedVals;
							$res['lines']++;
						}
					}
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
	
	function getEmptyError(){
		return array("nga" => "", "polity" => "", "section" => "", "subsection" => "", "variable" => "", 
				"value" => "", "comment" => "", "link" => "");
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
							$factoids = array_merge($factoids, $this->processDatedFact($f['value']['value'], $val));
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
	
	function processDatedFact($fact, $orig){
		foreach($fact as $factbit){
			if($factbit['name'] == "undatedfact"){
				$factoids = $this->processUndatedFact($factbit['value']);
			}
			elseif($factbit['name'] == "datevalue"){
				$datebit = $this->processDateValue($factbit['value']);
				if($datebit['type'] == "error"){
					return array($this->createFactoid("", "", "error", $orig, "", "error", "error", "Failed to parse date value"));
				}	
			}
		}
		
		foreach($factoids as $i => $factoid){
			$factoids[$i]['fact_type'] = "complex";
			$factoids[$i]["date_type"] = $datebit['type'];
			if(isset($factoids[$i]['comment']) && $factoids[$i]['comment'] && isset($datebit['comment'])  && $datebit['comment']){
				$factoids[$i]['comment'] .= " - ";
			}
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
		if($fact['name'] == "daterange" && $fact['value'][0] && $fact['value'][1]){
			$from = $fact['value'][0];
			$to = $fact['value'][1];
			$from = $this->processDateValue($from);
			$to = $this->processDateValue($to);
			if(!$to or !$from){
				$datevals = array("type" => "error", "value" => "", "comment" => "failed to read range: ".$fact['value'][0]." " . $fact['value'][1]);	
			}
			else {
				//opr($to);
				$datevals = $this->processDateRange($from, $to);
			}
		}
		elseif($fact['name'] == "singledate"){
			if($fact['value']['name'] == "simpledate"){
				$datevals = array("type" => "simple", "value" => $fact['value']['text'], "comment" => "");
			}
			elseif($fact['value']['name'] == "disagreedate"){
				$date_ranges = array();
				$date_units = array();
				foreach($fact['value']['value'] as $frag){
					if($frag['value']['name'] == "simpledaterange"){
						$date_ranges[] = $this->getDateRangeBounds($frag['value']['value'][0]['text'], $frag['value']['value'][1]['text']);
					}
					elseif($frag['value']['name'] == "simpledate"){
						$date_units[] = $frag['value']['text'];
					}
				}
				if(count($date_ranges) >= count($date_units)){
					$datevals = $this->consolidateDateRanges($date_ranges, $date_units);						
				}
				else {
					$datevals = $this->consolidateDates($fact['value']['value'], "disputed");						
				}
			}
			elseif($fact['value']['name'] == "uncertaindate"){
				//just make a range from the first to the last..
				$datevals = $this->consolidateDates($fact['value']['value'], "uncertain");
			}
		}
		else {
			$datevals = array("type" => "error", "value" => "", "comment" => "strange error ".$fact['value'][0]);				
		}
		return $datevals;
	}
	
	function consolidateDateRanges($ranges, $units){
		$from = array("type" => "", "value" => "", "comment" => "");
		$to = array("type" => "", "value" => "", "comment" => "");
		foreach($units as $unit){
			if($from['type'] == ""){
				$from['type'] = "simple";
				$from['value'] = $unit;
			}
			else {
				if($this->strToYear($from['value']) != $this->strToYear($unit)) $from['type'] = "disputed";
				if($this->strToYear($from['value']) > $this->strToYear($unit)){
					$from['value'] = $unit;
				}
			}	
			if($to['type'] == ""){
				$to['type'] = "simple";
				$to['value'] = $unit;
			}
			else {
				if($this->strToYear($to['value']) != $this->strToYear($unit)) $to['type'] = "disputed";
				if($this->strToYear($to['value']) < $this->strToYear($unit)){
					$to['value'] = $unit;
				}
			}				
		}
		foreach($ranges as $range){
			if($from['type'] == ""){
				$from['type'] = "simple";
				$from['value'] = $range[0];
			}
			else {
				if($this->strToYear($from['value']) != $this->strToYear($range[0])) $from['type'] = "disputed";
				if($this->strToYear($from['value']) > $this->strToYear($range[0])){
					$from['value'] = $range[0];
				}
			}	
			if($to['type'] == ""){
				$to['type'] = "simple";
				$to['value'] = $range[1];
			}
			else {
				if($this->strToYear($to['value']) != $this->strToYear($range[1])) $to['type'] = "disputed";
				if($this->strToYear($to['value']) < $this->strToYear($range[1])){
					$to['value'] = $range[1];
				}
			}				
		}
		$date_comment = "";
		if($from['type'] == "disputed"){
			$date_comment = "The from date is disputed";
		}		
		if($to['type'] == "disputed"){
			if($date_comment) $date_comment .= " - ";
			$date_comment .= "The to date is disputed";
		}		
		$datevals = array("from" => $from['value'], "to" => $to['value'], "type" => "range", "comment" => $date_comment);
		return $datevals;
	}
	
	function processDateRange($from, $to){
		$date_comment = "";
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
		//make sure that we have no unsuffixed values
		if($from['type'] == "simple" ){
			//opr($from);
			if(!$this->containsYearSuffix($from['value'])){
				$sf = $this->getFirstYearSuffix($to['value']);
				if($sf){
					$from['value'].= $sf;
				}
			}
		}
		if($to['type'] == "simple" ){
			if(!$this->containsYearSuffix($to['value'])){
				$sf = $this->getLastYearSuffix($from['value']);
				if($sf){
					$to['value'].= $sf;
				}
			}
		}
		if(!isset($from['value']) or !isset($to['value'])){
			$datevals = array("type" => "error", "value" => "", "comment" => "from or to value not set");				
		}
		elseif($this->strToYear($from['value']) > $this->strToYear($to['value'])){
			$datevals = array("from" => $to['value'], "to" => $from['value'], "type" => "range", "comment" => $date_comment);				
		}
		else {
			$datevals = array("from" => $from['value'], "to" => $to['value'], "type" => "range", "comment" => $date_comment);
		}
		return $datevals;
	}
	
	function containsYearSuffix($str){
		$pattern = "/(\d{1,4})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			return (isset($matches[2]) && $matches[2]);
		}
		return false;
	}
	
	function getFirstYearSuffix($str){
		$pattern = "/(ce|bce|bc)/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[1]) && $matches[1]){
				if($matches[1] == "bc") $matches[1] = "BCE";
				return $matches[1];
			}
		}
		return false;
		
	}
	
	function getLastYearSuffix($str){
		$pattern = "/.*(ce|bce|bc)/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[1]) && $matches[1]){
				if($matches[1] == "bc") $matches[1] = "BCE";
				return $matches[1];
			}
		}
		return false;
	}
	
	function strToYear($str){
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[2]) && (stristr($matches[2], "bc") || stristr($matches[2], "bce"))){
				return (0 - $matches[1]);
			}
			else return $matches[1];
		}
		return false;
	}
	
	/*
	 * In either a disputed or an uncertain date - if they are all ranges, make it a range...
	 */
	function consolidateDates($fragments, $type){
		$earliest = false;
		$latest = false;
		foreach($fragments as $frag){
			$x = false;
			if($type == "disputed" && $frag['value']['name'] == "simpledate"){
				$x = $this->strToYear($frag['value']['text']);
			}
			elseif(isset($frag['name']) && $frag['name'] == "simpledate") {
				$x = $this->strToYear($frag['text']);
			}
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
			else {
				$bounds = false;
				if($type == "disputed" && $frag['value']['name'] == "simpledaterange"){
					$bounds = $this->getDateRangeBounds($frag['value']['value'][0]['text'], $frag['value']['value'][1]['text']);
				}
				elseif(isset($frag['name'] ) && $frag['name'] == "simpledaterange") {
					$bounds = $this->getDateRangeBounds($frag['value'][0]['text'], $frag['value'][1]['text']);
				}
				if($bounds){
					if(($earliest === false) && ($latest === false)){
						$earliest = $this->strToYear($bounds[0]);
						$latest = $this->strToYear($bounds[1]);
					}
					else {
						$earliest = ($earliest < $this->strToYear($bounds[0])) ? $earliest : $this->strToYear($bounds[0]);
						$latest = ($latest > $this->strToYear($bounds[1])) ? $latest : $this->strToYear($bounds[1]);
					}		
				}
				else {
					opr($frag);
					return false;	
				}
			}
		}
		if(($earliest === false) || ($latest === false)){
			return false;
		}
		$str = "";
		if($earliest < 0){
			$str = (0 - $earliest) . "BCE - ";
		}
		else {
			$str = $earliest ."CE - ";
		}
		if($latest < 0){
			$str .= (0 - $latest) . "BCE";
		}
		else {
			$str .= $latest."CE";
		}
		$date_val = "$earliest - $latest";
		return array("type" => $type, "value" => $str, "comment" => "Date is $type");
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
			"absent: [600bce;500bc]-{150bc;90ce;40bc}; [present;big;small]: {150bc;90bc}-{1;67ce}", 
			"{[180,000-270,000]; 604,000}: 423 CE",
			"present: {1380-1450 CE; 1430-1450 CE; 1350-1450 CE}",
			"absent: {380-450 CE; 1450 CE; 150-50 CE}"
		);
		foreach($teststrings as $t){
			echo "<h3>$t</h3>";
			opr($this->parseVariableValue($t));			
		}
	}		
	
	
	
	/*
	 * Log into the Seshat Wiki....
	 */
	function login(){
		$this->ch = curl_init();
		//initial curl setting
		curl_setopt($this->ch, CURLOPT_URL, $this->settings['scraper']['loginUrl']);
		curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->settings['scraper']['cookiejar']);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		if(isset ($this->settings['http_proxy']) && $this->settings['http_proxy']){
			curl_setopt($this->ch, CURLOPT_PROXY, $this->settings['http_proxy']);
		}
		
		$this->ucontext->logger->timeEvent("Start Login", "debug");
		//get token from login page
		$store = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$store){
			$this->ucontext->logger->timeEvent("Logging in Failed");				
			return $this->failure_result("Failed to retrieve login page ".$this->settings['scraper']['loginUrl'], 401, "warning");				
		}
		$this->ucontext->logger->timeEvent("Login Page Retrieved", "debug");				
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
		$this->ucontext->logger->timeEvent("Login Page Parsed", "debug");		
		if(!$loginToken){
			return $this->failure_result("Failed to find login token on login page ".$this->settings['scraper']['loginUrl'], 404, "error");				
		}
		//login
		curl_setopt($this->ch, CURLOPT_POST, 1);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, 'wpName='.$this->settings['scraper']['username'].'&wpPassword='.$this->settings['scraper']['password'].'&wpLoginAttempt=Log+in&wpLoginToken='.$loginToken);
		$store = curl_exec($this->ch);
		$http_status = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
		if($http_status >= 400){
			$this->ucontext->logger->timeEvent("Login Failed");				
			return $this->failure_result("Failed to login to wiki", 400, "warning");
		}
		$this->timeEvent("Login Successful", "info");				
		return true;
	}

	/*
	 * Functions for making url names of pages readable
	 */
	function formatNGAName($url){
		$bits = explode("/", $url);
		$x = $bits[count($bits) - 1];
		return str_replace("_", " ", $x);
	}
	
	function unformatSectionName($tit){
		return str_replace(" ", "_", $tit);
	}
	
	

}

