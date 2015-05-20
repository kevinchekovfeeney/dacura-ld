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
require_once('files/seshat.parser.php');

//need to do logging, cookiejar, etc.

class ScraperDacuraServer extends DacuraServer {
	
	var $ch; //curl handle
	var $content_start_html = '<h1><span class="mw-headline"';
	var	$content_end_html = "<div class=\"printfooter\">";
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
		ini_set("memory_limit","512M");
		$this->init($action, $object);
		return $this->login();		
	}
	
	/*
	 * Fetches the list of NGAs from the Seshat Main page (the World-30 sample table)
	 * Returns an array of URLs
	 */
	function getNGAList($suppress_cache = false){
		if($this->settings['scraper']['use_cache'] && !$suppress_cache){
			$x = $this->fileman->decache("scraper", $this->settings['scraper']['mainPage'], $this->ch);
			if($x) {
				return $x;
			}
		}
		curl_setopt($this->ch, CURLOPT_URL, $this->settings['scraper']['mainPage']);
		//curl_setopt($this->ch, CURLOPT_HEADER, true);
		curl_setopt($this->ch, CURLOPT_FILETIME, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve nga list page ".$this->settings['scraper']['mainPage'], curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "warning");
		}
		$fmod = curl_getinfo($this->ch, CURLINFO_FILETIME);
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
			$config = $this->settings['scraper']['cache_config'];
			$config["url"] = $this->settings['scraper']['mainPage'];
			$this->fileman->cache("scraper", $this->settings['scraper']['mainPage'], $ngaURLs, $config);				
		}
		$this->logEvent("debug", 200, "get_ngas returned: ".count($ngaURLs)." from seshat main page");
		return $ngaURLs;
	}
	
	
	/*
	 * Fetches a list of polities from a Seshat NGA page
	 * Takes the URL of the polity page
	 * Returns an array of URLs
	 */
	function getPolities($pageURL, $suppress_cache = false ){
		if($this->settings['scraper']['use_cache'] && !$suppress_cache){
			$x = $this->fileman->decache("scraper", $pageURL, $this->ch);
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
			if(strstr($x, "seshat.info:") or $x[0] == ":"){
				continue;
			}
			if(!strstr($x, "seshat.info")){
				$url = 'http://seshat.info'.$x;
			}	
			$polities[] = $url;
		}
		if($this->settings['scraper']['use_cache']){
			$config = $this->settings['scraper']['cache_config'];
			$config["url"] = $pageURL;
			$this->fileman->cache("scraper", $pageURL, $polities, $config);				
		}
		$this->logEvent("debug", 200, "get_polities returned: ".count($polities)." from $pageURL");
		return $polities;
	}
	
	/**
	 * Produces a dump of the NGA / polity sets passed in
	 * @param $data associative array of NGA name -> polity URL
	 * @param $suppress_cache - turn off cache for this call
	 */
	function getDump($data, $suppress_cache = false){
		$polities_retrieved = array();
		$summaries = array();
		$headers = array("NGA", "Polity", "Section", "Subsection", "Variable", "Value From", "Value To",
				"Date From", "Date To", "Fact Type", "Value Note", "Date Note", "Comment");
		$error_headers = array("NGA", "Polity", "Section", "Subsection", "Variable", "Type", "Value", "Error");
		$stats = array( "ngas" => 0, "polities" => 0, "errors" => 0, "warnings" => 0, "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
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
			$summary = array("nga" => $this->formatNGAName($nga), "polities" => count($polities), "failures" => 0, "warnings" => 0,
					"total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "errors" => 0);
			foreach($polities as $p){
				if(!$p) continue;
				if(!isset($polities_retrieved[$p])){
					$this->logEvent("debug", 200, "retrieving facts from $p");
					$pfacts = $this->getFactsFromURL($p, $suppress_cache);
					if($pfacts){
						$polities_retrieved[$p] = $pfacts;
						//op errors
						foreach($pfacts['errors'] as $e){
							if(isset($e['variable']) && isset($e['section'])){
								$row = $this->getErrorTableRowHTML($e, $nga, $p, "error");
								$this->fileman->dumpData($error_op, $row);							
							}	
						}
						foreach($pfacts['warnings'] as $e){
							if(isset($e['variable']) && isset($e['section'])){
								$row = $this->getErrorTableRowHTML($e, $nga, $p, "warning");
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
		$tab_style = "<style>th,td { padding: 2px; border: 1px solid black; }\ntable { border-collapse: collapse}</stlye>";
		$this->fileman->dumpData($html_op, "</table>$tab_style");				
		$this->fileman->dumpData($error_op, "</table>$tab_style");				
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
	
	function getErrorTableRowHTML($e, $nga, $p, $t){
		$row = "<tr><td><a href='$nga'>". $this->formatNGAName($nga)."</a></td>";
		$row .= "<td><a href='$p'>". $this->formatNGAName($p)."</a></td>";
		$row .= "<td><a href='$p"."#".$this->unformatSectionName($e['section'])."'>".$e['section']."</a></td>";
		$row .= "<td><a href='$p"."#".$this->unformatSectionName($e['subsection'])."'>".$e['subsection']."</a></td>";
		$row .= "<td>".$e['variable']."</td>";
		$row .= "<td>".$t."</td>";
		$row .= "<td>".$e['value']."</td>";
		$row .= "<td>".$e['comment']."</td></tr>";
		return $row;
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
					header("Content-Type: text/html; charset=utf-8");
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
		$stats['warnings'] += count($nfacts['warnings']);
	}

	/*
	 * Functions for turning a seshat page into an array of facts...
	 */
	function getFactsFromURL($pageURL, $suppress_cache = false){
		$content = false;
		if($this->settings['scraper']['use_cache'] && !$suppress_cache){
			$content = $this->fileman->decache("scraper", $pageURL, $this->ch);
		}
		if(!$content){
			curl_setopt($this->ch, CURLOPT_URL, $pageURL);
			$content = curl_exec($this->ch);
			if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
				return $this->failure_result("Failed to retrieve url: $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
			}	
			if($content && $this->settings['scraper']['use_cache']){
				$config = $this->settings['scraper']['cache_config'];
				$config["url"] = $pageURL;
				$this->fileman->cache("scraper", $pageURL, $content, $config);
			}
		}
		$facts = $this->getFactsFromPage($content);
		return $facts;
	}


	/**
	 *
	 */
	function generateSchema(){
		curl_setopt($this->ch, CURLOPT_URL, $this->settings['scraper']['codeBook']);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve url: $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "info");
		}
		echo("#Main Variables (polity-based)\n");
		$bits = explode("Main Variables (polity-based)", $content);
		$content = $bits[count($bits)-1];//ditch the early bit;
		$bits = explode("</dl>", $content);
		array_pop($bits);
		$content = implode("</dl>", $bits); 
		$sections = explode("<h2>", $content);
		foreach($sections as $sect){
			$properties = array();
			$sec_bits = explode("</span></h2>", $sect);
			if(count($sec_bits) == 2){
				$sec_title = substr($sec_bits[0], strrpos($sec_bits[0], ">")+1);
				echo("#$sec_title");
				mb_regex_encoding('UTF-8');
				$pieces = mb_split('♠', $sec_bits[1]);
				foreach($pieces as $piece){
					$sub_pieces = mb_split("♣.*♥", $piece);
					
					$property_name = trim(strip_tags($sub_pieces[0]));
					if($property_name){
						$property_comment = trim(strip_tags($sub_pieces[1]));
						$property_nam = str_replace(array("(", ")", "-"), " ", $property_name);
						$names_pieces = preg_split("/\s+/", $property_nam);
						//echo count($names_pieces);
						$pname = "";
						foreach($names_pieces as $np){
							$pname .= ucfirst($np);
						}
						$domain = $this->mapVariableToDomain($pname);
						if($domain){
							$range = $this->mapVariableToRange($pname);
							$prop_assertions = array();
							$prop_assertions[] = 'seshat:'.$pname." a ".$range[0].";\n";
							$prop_assertions[] = "\trdfs:label \"$property_name\";\n"; 
							$prop_assertions[] = "\trdfs:domain $domain;\n";
							$prop_assertions[] = "\trdfs:range  $range[1];\n";
							if($this->allowsMultipleValues($pname) === false){
								$prop_assertions[] = "\trdfs:subClassOf [ a owl:Restriction ;\n";
								$prop_assertions[] = "\t\towl:maxCardinality 1 ;\n";
								$prop_assertions[] = "\t\towl:onProperty sghd:$pname\n";
								$prop_assertions[] = "\t\t] ;\n";
							}
							$prop_assertions[] = "\trdfs:comment \"$property_comment\" .\n\n";
							opr($prop_assertions);
							foreach($prop_assertions as $p){
								//echo $p;
							}
						}
					}
				}
				//get rid of all text up to first variable...
			}		
		}
		//rdfs:subClassOf
		//[ a       owl:Restriction ;
		//owl:maxCardinality 1 ;
		//owl:onProperty :hours
		//] ;
		
		//$facts = $this->getFactsFromPage($content);
		//mb_regex_encoding('UTF-8');
		//$pieces = mb_split('♠', $content);
		//get rid of all text up to first variable...
		//array_shift($pieces);
		//opr($pieces);
		return true;
 
		//$cfacts = $this->getSchemaFromURL($this->settings['scraper']['codeBook']);
	
	}
	
	//The following 3 functions are mappings to rdf for each variable
	
	function allowsMultipleValues($property_name){
		$map = array(
			"AlternativeNames"	
		);
		return in_array($property_name, $map);
	}
	
	function mapVariableToDomain($property_name, $context = ""){
		$map = array(
			"RA" => false, 
			"Expert" => false,
			"Duration" => "seshat:TemporalEntity"
		);
		if(isset($map[$property_name])) {
			return $map[$property_name];
		}
		return "seshat:SocialOrganisation";
	}
	
	function mapVariableToRange($property_name){
		$map = array(
			"Duration" => array("owl:ObjectProperty", "time:Interval"), 
			"UTMZone" => array("owl:ObjectProperty", "tzont:TimeZone"),
			"PeakDate" => array("owl:ObjectProperty", "time:TemporalEntity")
		);
		return (isset($map[$property_name])) ? $map[$property_name] : array("owl:DataProperty", "xsd:string");
	}
	
	/*
	 * Takes a seshat page and extracts all of the facts 
	 * Takes the URL of the page
	 * Returns a fact list object (associative array)
	 */
	function getFactsFromPage($content){
		$fact_list = array( "variables" => array(), "errors" => array(), "warnings" => array(),
				"title" => "", "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0, "sections" => array());
		// strip out the non-content to minimise collision risk
		$content_start_offset = strpos($content, $this->content_start_html);
		$content_end_offset = strpos($content, $this->content_end_html);
		if($content_start_offset && $content_end_offset){
				$content = substr($content, $content_start_offset , $content_end_offset - $content_start_offset);
		}
		// Divide into main sections....
		$sections = explode("<h2>", $content);
		$i = 0;
		//echo count($sections) . " sections found\n";
		foreach($sections as $sect){
			++$i;
			if($i == 1){ 	//variables that appear before the first section -> page level variables 
				$sfl = $this->parseFactsFromString($sect);
				$this->updateFactStats($fact_list, $sfl);
				$fact_list['variables'] = array_merge($fact_list['variables'], $sfl['variables']);
			}
			else {
				//divide into sub-sections
				$sec_bits = explode("</span></h2>", $sect);
				if(count($sec_bits) == 2){
					$sec_head_start_pos = strpos($sec_bits[0], ">") + 1;
					$sec_head_end_pos = strpos(substr($sec_bits[0], 1), "<") + 1;
					$sec_title = substr($sec_bits[0], $sec_head_start_pos, $sec_head_end_pos - $sec_head_start_pos);
					if(!$sec_title) $sec_title = "Unknown";
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
								$subsec_head_start_pos = strpos($subsec_bits[0], ">") + 1;
								$subsec_head_end_pos = strpos(substr($subsec_bits[0], 1), "<") + 1;
								$subsec_title = substr($subsec_bits[0], $subsec_head_start_pos, $subsec_head_end_pos - $subsec_head_start_pos);
								if(!$subsec_title) $subsec_title = "Unknown";
								$subsec_content = $subsec_bits[1];
								$ssfl = $this->parseFactsFromString($subsec_content, $sec_title, $subsec_title);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$k = 0;
								$nsubtitle = $subsec_title;
								while(isset($sec_fact_list['sections'][$nsubtitle])){
									$nsubtitle = $subsec_title ."_".$k++;
								}
								$sec_fact_list['sections'][$nsubtitle] = $ssfl; 
							}
							else {
								$ssfl = $this->parseFactsFromString($subsect, $sec_title);
								$this->updateFactStats($sec_fact_list, $ssfl);
								$sec_fact_list = array_merge($sec_fact_list, $ssfl['variables']);
							}								
						}
					}
					$this->updateFactStats($fact_list, $sec_fact_list);
					$k = 0;
					$nsectitle = $sec_title;
					while(isset($sec_fact_list['sections'][$nsectitle])){
						$nsectitle = $sec_title ."_".$k++;
					}
					$fact_list["sections"][$nsectitle] = $sec_fact_list;
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
		$myfl['warnings'] = array_merge($myfl['warnings'], $newfl['warnings']);
	}

	/*
	 * Creates an array of name => value for variables found in the text
	 * If there are repeated keys, it appends _n to the duplicates to retain the values.
	 * Returns a FactList...
	 */
	function parseFactsFromString($str, $section = "", $subsection = ""){
		$pattern = '/\x{2660}([^\x{2660}\x{2665}]*)\x{2663}([^\x{2660}]*)\x{2665}/Uu';
		$matches = array();
		$factoids = array();
		$res = array("variables" => array(), "errors" => array(), "warnings" => array(), "total_variables" => 0, "empty" => 0, "complex" => 0, "lines" => 0);
		if(preg_match_all($pattern, $str, $matches)){
			for($i = 0; $i< count($matches[0]); $i++){
				$key = trim($matches[1][$i]);
				$val = trim($matches[2][$i]);
				if(isset($factoids[$key])){
					$n = 1;
					while(isset($factoids[$key."_$n"])){
						$n++;
					}
					$key = $key."_$n";
				}
				$res['total_variables']++;
				$parsed = $this->parseVariableValue($val);
				if($parsed['result_code'] == "complex"){
					$res['complex']++;
					$res['lines'] += count($parsed['datapoints']);
					$factoids[$key] = $parsed['datapoints'];
				}
				elseif($parsed['result_code'] == "warning"){
					$res['complex']++;
					$res['lines'] += count($parsed['datapoints']);
					$factoids[$key] = $parsed['datapoints'];
					$e = $this->getEmptyWarning();
					$e['value'] = $parsed['value'];
					$e['variable'] = $key;
					$e['section'] = $section;
					$e['subsection'] = $subsection;
					$e['comment'] = $parsed['result_message'];
					$res['warnings'][] = $e;
									}		
				elseif($parsed['result_code'] == "empty"){
					$factoids[$key] = "";
					$res['empty']++;
				}		
				elseif($parsed['result_code'] == "error"){
					$res['complex']++;
					$e = $this->getEmptyError();
					$e['value'] = $parsed['value'];
					$e['variable'] = $key;
					$e['section'] = $section;
					$e['subsection'] = $subsection;
					$e['comment'] = $parsed['result_message'];
					$res['errors'][] = $e;
				}
				else {
					$res['lines']++;
					$factoids[$key] = $parsed['datapoints'];
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
		return array("type" => "error", "nga" => "", "polity" => "", "section" => "", "subsection" => "", "variable" => "", 
				"value" => "", "comment" => "", "link" => "");
	}
	
	function getEmptyWarning(){
		return array("type" => "warning", "nga" => "", "polity" => "", "section" => "", "subsection" => "", "variable" => "",
			"value" => "", "comment" => "", "link" => "");
	}
	
	/*
	 * These Functions are for navigating the parse tree..
	 */
	
	/**
	 * 
	 * @param string $t the raw text value of the variable 
	 * @return associative array: 
	 *   "value" => initial_value_of_variable
	 * 	 "result_code" => error | empty | simple | complex
	 *   "result_message => "string to show to user"
	 *   "datapoints" => simple array of 'factoids' -> basic information about the fact in a table format
	 */
	function parseVariableValue($t){
		$val = strip_tags($t);
		$val = trim(html_entity_decode($val));
		$ret_val = array(
			"value" => $val,
			"result_code" => "",
			"result_message" => "",
			"datapoints" => array()
		);
		if($val == ""){
			$ret_val["result_code"] = "empty";
			return $ret_val;
		}
		$factoids = array();
		if(strpbrk($val, ":;[{")){
			$p = new seshatParsing($val);
			$parsedFact = $p->match_fact();
			if($parsedFact){
				if($parsedFact['text'] == $val){
					$ret_val["result_code"] = "complex";
					$fragment_contents = $parsedFact['value'];
					if(isset($fragment_contents[0])){ //array of fragments...
						$fragments = $fragment_contents;
					}
					else {
						$fragments = array($fragment_contents);
					}
					foreach($fragments as $f){
						if($f['name'] == "undatedfact"){
							$mini_factoids = $this->processUndatedFact($f['value']);
							foreach($mini_factoids as $mf){
								$factoids[] = $this->createFactoid("", "", "", $mf['value_from'], $mf['value_to'], $mf['value_type'], "complex", "");
							}
						}
						else {
							$n_factoids = $this->processDatedFact($f['value'], $val);
							if(is_array($n_factoids)){
								$factoids = array_merge($factoids, $n_factoids);
							}
							else {							
								$ret_val["result_code"] = "error";
								$ret_val["result_message"] = "The date value could not be parsed: " .$f['text'];
								break;
							}
						}
					}
				}
				else {
					$ret_val = $this->lastChanceSaloon($val);
					if($ret_val["result_code"] == "error"){
						$ret_val["result_message"] = " This fragment: \"" .$parsedFact['text'].
						"\" parsed ok. The fragment following \"".substr($val, strlen($parsedFact['text']))."\" contains the error.";
					}
					return $ret_val;
				}
			}
			else {
				return $this->lastChanceSaloon($val);
			}
		}
		else {
			$ret_val["result_code"] = "simple";
			$ret_val["result_message"] = "Simple Value.";		
			$factoids[] = $this->createFactoid("", "", "", $val, "", "simple", "simple", "");
		}
		$ret_val['datapoints'] = $factoids;
		return $ret_val;
	}

	/*
	 * Last gasp attempt to allow known common slips through, while raising a warning
	 */
	function lastChanceSaloon($txt){
		$ret_val = array("value" => $txt, "result_code" => "error");
		$factoids = false;
		//1 uncertainty list items surrounded by [] and separated by , instead of 
		$is_bad_list = false;
		if(strpos($txt, '[') === 0 && strpos($txt, ']') == (strlen($txt) -1)){
			$is_bad_list = "uncertain";
		}
		elseif(strpos($txt, '{') === 0 && strpos($txt, '}') == (strlen($txt) -1)){
			$is_bad_list = "disputed";
		}
		if($is_bad_list && strpos($txt, ",") !== false){
			$vals = explode(",", substr($txt, 1, strlen($txt)-2));
			foreach($vals as $val){
				$factoids[] = $this->createFactoid("", "", "", $val, "", $is_bad_list, "complex", "warning - Using comma (,) instead of semi-colon (;) to separate list of $is_bad_list values");
			}
			$ret_val['result_message'] = "Warning: using comma (,) instead of semi-colon (;) to separate list of $is_bad_list values";
		}
		elseif($is_bad_list && !strpbrk(substr($txt, 1, strlen($txt)-3), ":;[{,")){
			$factoids = array($this->createFactoid("", "", "", trim(substr($txt, 1, strlen($txt)-2)), "", $is_bad_list, "simple", "warning - Surrounding a simple value in $is_bad_list brackets"));				
			$ret_val['result_message'] = "Surrounding a simple value in $is_bad_list brackets";
		}
		elseif(!$is_bad_list && strpos($txt, ";") !== false && !strpbrk($txt, ":[{,")){
			$vals = explode(";", $txt);
			$factoids = array();
			foreach($vals as $val){
				if($val){
					$factoids[] = $this->createFactoid("", "", "", $val, "", "list", "complex", "");
				}
			}
			$ret_val['result_message'] = "list of variable values";
		}
		if($factoids){
			$ret_val['datapoints'] = $factoids;
			$ret_val['result_code'] = "warning";
			if($ret_val['result_message'] == "list of variable values"){ //ugly hack
				$ret_val['result_code'] = "complex";
			}
		}
		else {
			$ret_val["result_message"] = "Value $txt failed to parse. It has a formatting error. ";				
		}
		return $ret_val;
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
				if(!$datebit){
					return $this->failure_result("Failed to parse date part ".$factbit['text'].$this->errmsg, $this->errcode);
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
				return $this->failure_result("failed to read dates in date range: ".$fact['value'][0]." " . $fact['value'].$this->errmsg, $this->errcode);
			}
			else {
				$datevals = $this->processDateRange($from, $to);
				if(!$datevals) return false;
			}
		}
		elseif($fact['name'] == "singledate" && isset($fact['value']['name']) && $fact['value']['name'] == "simpledate"){
			$normdate = $this->normaliseSuffix($fact['value']['text']);
			if(!$normdate){
				return $this->failure_result("failed to read date to find suffix ".$fact['value']['text'], 500);
			}
			$datevals = array("type" => "simple", "value" => $normdate, "comment" => "");
		}
		elseif($fact['name'] == "disagreedate" || ($fact['name'] == "singledate" && $fact['value']['name'] == "disagreedate")){
			$base = ($fact['name'] == "disagreedate")? $fact['value'] : $fact['value']['value'];
			$date_ranges = array();
			$date_units = array();
			foreach($base as $frag){
				if($frag['value']['name'] == "simpledaterange"){
					$dr = $this->getDateRangeBounds($frag['value']['value'][0]['text'], $frag['value']['value'][1]['text']);
					if(!$dr){
						return false;
					}
					$date_ranges[] = $dr;
				}
				elseif($frag['value']['name'] == "simpledate"){
					$normdate = $this->normaliseSuffix($frag['value']['text']);
					$date_units[] = $normdate;
				}
			}
			if(count($date_ranges) >= count($date_units)){
				$datevals = $this->consolidateDateRanges($date_ranges, $date_units);
			}
			else {
				$datevals = $this->consolidateDates($base, "disputed");
			}
		}
		elseif($fact['name'] == "uncertaindate" || ($fact['name'] == "singledate" && $fact['value']['name'] == "uncertaindate")){
			//just make a range from the first to the last..
			$base = ($fact['name'] == "uncertaindate")? $fact['value'] : $fact['value']['value'];
			$date_ranges = array();
			$date_units = array();
			foreach($base as $frag){
				if($frag['name'] == "simpledaterange"){
					$dr = $this->getDateRangeBounds($frag['value'][0]['text'], $frag['value'][1]['text']);
					if(!$dr){
						return false;
					}
					$date_ranges[] = $dr; 
						
				}
				elseif($frag['name'] == "simpledate"){
					$date_units[] = $frag['text'];
				}
			}
			if(count($date_ranges) >= count($date_units)){
				$datevals = $this->consolidateDateRanges($date_ranges, $date_units);
			}
			else {
				$datevals = $this->consolidateDates($base, "uncertain");
			}
		}
		else {
			return $this->failure_result("strange error parsing node: ".$fact['name'], 500);
		}
		return $datevals;
	}
	
	function normaliseSuffix($txt){
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		if(preg_match($pattern, $txt, $matches)){
			if(isset($matches[2])){
				if(stristr($matches[2], "bc") || stristr($matches[2], "bce")){
					return $matches[1]."BCE";
				}
				else return $matches[1]."CE";
			}
			return $matches[1];
		}
		return false;
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
			$msg = "Could not detect correct values in range" . ((isset($from['value'])) ? "From: ".$from['value'] : " no from detected");
			$msg .= ((isset($to['value'])) ? " To: ".$to['value'] : " no to detected");
			return $this->failure_result($msg, 400);
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
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
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
				if(stristr($matches[1], "bc") || stristr($matches[1], "bce")){
					return "BCE";
				}
				return "CE";
			}
		}
		return false;
	}
	
	function getLastYearSuffix($str){
		$pattern = "/.*(ce|bce|bc)/i";
		$matches = array();
		if(preg_match($pattern, $str, $matches)){
			if(isset($matches[1]) && $matches[1]){
				if(stristr($matches[1], "bc") || stristr($matches[1], "bce")){
					return "BCE";
				}
				return "CE";
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
		$pattern = "/(\d{1,5})\s*(ce|bce|bc)?/i";
		$matches = array();
		$matches2 = array();
		if(preg_match($pattern, $dr1, $matches)){
			if(!isset($matches[2]) or !$matches[2]){
				if(preg_match($pattern, $dr2, $matches2)){
					if(isset($matches2[2])){
						if(stristr($matches2[2], "bc") || stristr($matches2[2], "bce")){
							$dr1 .= "BCE";
							$dr2 = $matches2[1]."BCE";
						}
						else {
							$dr1 .= "CE";
							$dr2 = $matches2[1]."CE";
						}
					}
					else {
						return $this->failure_result("No era suffix found on either side of date-range", 400);
					}
				}
				else {
					return $this->failure_result("From side of date range is not a valid date", 400);
				}
			}
			else {
				if(stristr($matches[2], "bc") || stristr($matches[2], "bce")){
					$dr1 = ($matches[1])."BCE";
				}
				else {
					$dr1 = ($matches[1])."CE";						
				}
				if(preg_match($pattern, $dr2, $matches2)){
					if(!isset($matches2[2]) || !$matches2[2]){
						if(stristr($matches[2], "bc") || stristr($matches[2], "bce")){
							$dr2 .= "BCE";
						}
						else {
							$dr2 .= "CE";
						}
						$dr2 .= $matches[2];
					}
					else {
						if(stristr($matches2[2], "bc") || stristr($matches2[2], "bce")){
							$dr2 = ($matches2[1])."BCE";
						}
						else {
							$dr2 = ($matches2[1])."CE";
						}
					}
				}
				else {
					return $this->failure_result("From side of date range is not a valid date", 400);
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

	
	function parseCanonicalExamples(){
		$examples = array();
		$examples['good'] = array( 
			"present" => array(
				"type" => "Simple Value",
				"interpretation" => "The variable has the value \"present\" throughout the polity's lifetime.",
				"note" => "MUST not contain the characters \"[\", \"]\", \"{\", \"}\", \";\" or \":\"))"), 
			"[by soldiers; by state]" => array(
				"type" => "Uncertain Value", 
				"interpretation" => "The value of the variable is either \"by soldiers\" or \"by state\", but it is not known which, throughout the polity's lifetime.",
				"note" => "The values must not contain the dash character \"-\", in addition to the special characters: \"[\", \"]\", \"{\", \"}\", \";\" or \":\")"),
			"[5,000-15,000]" => array(
				"type" => "Value Range", 
				"interpretation" => "The variable has a value between 5,000 and 15,000, but it is not known where exactly on the range it is, and this is the case throughout the polity's lifetime.",
				"note" => "The values should be numeric as a range is not meaningful otherwise. The values must not contain the dash character \"-\", or other special characters: (\"[\", \"]\", \"{\", \"}\", \";\" or \":\")."),
			"{sheep; horse; goat}" => array(
				"type" => "Disputed Value", 
				"interpretation" => "The value of the variable is disputed. Credible experts disagree as to whether the value is sheep, goat or horse and this is the case throughout the polity's lifetime.",
				"note" => "The values must not contain the dash character \"-\", or other special characters: (\"[\", \"]\", \"{\", \"}\", \";\" or \":\")."),
			"5,300,000: 120bce" => array(
				"type" => "Dated Value",
				"interpretation" => "The value of the variable is 5,300,000 in the year 120bce",
				"note" => "No assumptions can be made as to the value of the variable at any other date."),
			"5,300,000: 120bce-75bce; 6,100,000:75bce-30ce" => array(
				"type" => "Dated Value List",
				"interpretation" => "The value of the variable changed over the lifetime of the polity. It was 5,300,000 between 120BCE and 75BCE and 6,100,000 between 75BCE and 30CE",
				"note" => "Ideally, dated value lists should cover the entire lifespan of the polity."),				
			"[1,500,000 - 2,000,000]: 100bce" => array(
				"type" => "Dated Value Range",
				"interpretation" => "The value of the variable was between 1.5 million and 2 million in the year 100BCE. It is not known where on this range the real value was.",
				"note" => "This only tells us the value for a single point in time"),
			"1; 2; john; tree; rhubarb; fruit salad" => array(
				"type" => "Variable with a list of values",
				"interpretation" => "The value of the variable is simultaneously 1, 2, john, tree, rhubarb and fruit salad.",
				"note" => "Semi-colons signify lists of values, all of which hold."),
			"232: 500bce-90bce; 321: 90BCE-15ce; 324: 15CE-45CE" => array(
				"type" => "Changing Value over Time",
				"interpretation" => "The value of the variable was 232 from 500BCE, it changed to 321 in 90BCE, then changed again to 324 in 15CE and remained at that value until 45CE. However the date of the change is disputed - 150BCE and 90BCE are two proposed dates for the change.",
				"note" => "Ideally, the date ranges should cover the entire polity lifetime."),
			"absent: 500bce-{150bce;90bce}; present: {150bce;90bce}-1ce" => array(
				"type" => "Value Change at Disputed Date",
				"interpretation" => "The value of the variable was \"absent\" from 500BCE, then it changed to \"present\" which it remained at until the year 1CE. However the date of the change is disputed - 150BCE and 90BCE are two proposed dates for the change.",
				"note" => "All credible proposed dates should be included."),
			"absent: 450bce-[90bce;1ce]; present: [90bce;1ce]-53ce" => array(
				"type" => "Value Change at Uncertain Date",
				"interpretation" => "The value of the variable was \"absent\" from 450BCE, then it changed to \"present\" which it remained at until the year 53CE. However the date of the change is uncertain - 1CE and 90BCE are two possibilities for the change.",
				"note" => "All credible proposed dates should be included."),
			"absent: 500bce-150bce; {absent; present}:150bce-90bce; present: 90bce-1ce" => array(
				"type" => "Value Disputed During Date Range",
				"interpretation" => "The value of the variable was \"absent\" from 500BCE to 150BCE, from 150BCE to 90BCE, it was either \"absent\" or \"present\", then from 90BCE to ICE it was \"present\"",
				"note" => "This is a re-stating of the above example, focusing on the disputed value rather than the disputed date.  It is semantically identical."),
			"present: [1380 CE - 1450 CE; 1430 CE - 1450 CE; 1350 CE - 1450 CE]" => array(
				"type" => "Period of value unknown",
				"interpretation" => "The value of the variable was \"present\" for a period which either ran from 1380-1450 CE or from a period from 1430-1450 CE, or from a period from 1350-1450 CE",
				"note" => "This is semantically different than the disputed change times examples above: we are saying that the value holds for one of these distinct ranges.")
		);
		$examples['discouraged'] = array( 
			"present: 1380-1450 CE" => array(
				"type" => "Dates without Suffix",
				"interpretation" => "The value is present from 1380CE to 1450CE.",
				"note" => "It is always better to add suffixes (bce, ce) to dates in date ranges to remove any chance or mistakes."
			),
			"4: 1380-1450 BCE" => array(
				"type" => "Date Range out of Sequence",
				"interpretation" => "The value was 4 from 1380CE to 1450CE.",
				"note" => "Date ranges should always be from earlier date to later date."
			),
			"[goat;sheep;pig]: {150bc;90bc}-{1;67ce}" => array(
				"type" => "Uncertain Value and Date",
				"interpretation" => "The value of the variable became either \"goat\", \"pig\" or \"sheep\" in either 150BCE or 90BCE, the date being disputed and remained at that value until either 1CE or 67CE, which is also a matter of dispute.",
				"note" => "You should make either the date or the value uncertain, but not both. It introduces too much uncertainty into the value to be useful."
			),
				"goat: [600bce;500bc]-{150bc;90ce;40bc}; [goat;sheep;pig]: {150bc;90bc}-{1;67ce}" => array(
				"type" => "Overly complex sequence",
				"interpretation" => "The value of the variable was \"goat\" from a period that started either in 600BCE or 500BCE, and continued until one of 3 disputed dates: 40BCE, 150BCE or 90BCE.
					Then it changed to either \"goat\", \"pig\" or \"sheep\" in either 150BCE or 90BCE, the date being disputed and remained at that value until either 1CE or 67CE, which is also a matter of dispute.",
				"note" => "People typically can't follow the logic of such statements and will make mistakes, contradictions, etc once statements become this complex and hedged with uncertainty."
			),
			"{[180,000-270,000]; 604,000}: 423 CE"	=> array(
				"type" => "Value Range within Disputed Value",
				"interpretation" => "The value of the variable was in 423CE is disputed. One opinion is that it was between 180,000 and 270,000, another is that it was 604,000.",
				"note" => "Overly complex - hard to reliably turn into datapoints."
			),
			"absent: {380-450 CE; 1450 CE; 150-50 CE}" => array(
				"type" => "Date Ranges mixed with Single Dates",
				"interpretation" => "The value of the variable was \"absent\" for some period, but the period is disputed between 380CE - 450CE and 50CE - 150CE. Another opinion states that the value was absent in 1450CE.",
				"note" => "It is ambiguous whether the ranges refer to an uncertain particular date, or to a long-running process. "
			),
			"absent: {450 CE; 1450 CE; 150-50 CE}" => array(
				"type" => "Date Ranges mixed with Single Dates",
				"interpretation" => "The value of the variable was \"absent\" on a particular date but that date is disputed. It is either 450CE, 1450CE or some date between 50CE and 150CE.",
				"note" => "If most of the dates in such a list are single dates, ranges are interpreted as constraints on single dates, not date ranges."
			)		
		);
		$examples['warning'] = array(
			"[absent,present]" => array(
					"type" => "Uncertain values separated by a comma",
					"interpretation" => "The value is absent or present, which one is unknown.",
					"note" => "Lists of values need to be divided by a semi-colon."
			),				
			"{absent,present}" => array(
					"type" => "Disputed values separated by a comma",
					"interpretation" => "The value is absent or present, which one is disputed.",
					"note" => "Lists of values need to be divided by a semi-colon."
			),				
			"{absent}" => array(
					"type" => "Single disputed value",
					"interpretation" => "The value is absent although this is disputed.",
					"note" => "Disputed values need alternatives."
			),				
			"[present]" => array(
					"type" => "Single uncertain value",
					"interpretation" => "The value is present, although this is not certain.",
					"note" => "Uncertain values need alternatives."
			),				
		);
		
		foreach($examples as $set => $examps){
			foreach($examps as $val => $meta){
				$examples[$set][$val]['result'] = $this->parseVariableValue($val);
			}
		}
		return $examples;
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
			return $this->failure_result("Failed to retrieve login page ".$this->settings['scraper']['loginUrl'], curl_getinfo($this->ch, CURLINFO_HTTP_CODE), "warning");				
		}
		$this->ucontext->logger->timeEvent("Login Page Retrieved", "debug");				
		$loginToken = false;
		$dom = new DOMDocument;
		libxml_use_internal_errors(true);
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
		libxml_clear_errors();
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
	
	function parsePolityName($url){
		$p_details = array(
			"url" => $url,
			"shorturl" => substr($url, 0,40),
			"polityname" => "");
		$bits = explode("/", $url);
		$x = $bits[count($bits) - 1];
		$p_details['polityname'] = str_replace("_", " ", $x);
		return $p_details;
	}
	
	function unformatSectionName($tit){
		return str_replace(" ", "_", $tit);
	}

}

