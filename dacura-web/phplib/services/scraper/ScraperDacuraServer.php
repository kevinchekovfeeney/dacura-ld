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
require_once('files/seshat-parser-helper.php');

//need to do logging, cookiejar, etc.

class ScraperDacuraServer extends DacuraServer {
	
	var $ch; //curl handle
	var $cookiejar = "C:\\Temp\\dacura\\cookiejar.txt";
	var $parser_service_url = 'http://localhost:1234/parser';
	var $username = 'gavin';
	var $password = 'cheguevara';
	var $loginUrl = 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin';
	//var $pageURL = 'http://seshat.info/Zimbabwe_Plateau';
	var $mainPage = 'http://seshat.info/Main_Page';
	//var $dlparent = '/ancestor::dl/preceding-sibling::h3[1]/span[@class!="editsection"]/text()';
	//var $h3parent = '/../preceding-sibling::h3[1]/span[@class!="editsection"]/text()';
	///var $h2parent = '/../preceding-sibling::h2[1]/span[@class!="editsection"]/text()';
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

	var $multiCodes = array(			//an array of variables that can have multi-codes associated with them
			"human sacrifice of an out-group member", 
			"typical size of participating group", 
			"willingness to die for each other", 
			"enslavement", 
			"slaves", 
			"cost", 
			"inclusiveness (audience)", 
			"vigil/sleep deprivation", 
			"human sacrifice of a relative", 
			"energy proportion", 
			"community as a whole", 
			"singing", 
			"markets", 
			"looting", 
			"extermination", 
			"human sacrifice of an in-group member", 
			"risk of death", 
			"obligations to each other's families", 
			"currency", 
			"holders of special offices or [positions", 
			"expulsion", 
			"height", 
			"deportation", 
			"orthodoxy checks", 
			"food storage sites", 
			"enemies of any group member are my enemies", 
			"ra", 
			"ports", 
			"health costs", 
			"sack", 
			"alcohol", 
			"entertainment", 
			"frequency per participant", 
			"inclusiveness (participants)", 
			"euphoria-inducing drugs (other than alcohol)", 
			"none", 
			"frequency for the audience", 
			"orthopraxy checks", 
			"bridges", 
			"targeted massacre", 
			"mutilation", 
			"other", 
			"frequency for the ritual specialist", 
			"alternative names", 
			"dancing", 
			"name", 
			"professional military officers", 
			"feasting", 
			"drinking water supply systems", 
			"expert", 
			"fear", 
			"disgust", 
			"others", 
			"bronze", 
			"poisoning/dysphoria-causing drugs", 
			"general massacre", 
			"humiliation", 
			"foodstuffs", 
			"sex", 
			"extent", 
			"metals", 
			"roads", 
			"productivity", 
			"honour code", 
			"property/valuable items", 
			"elders", 
			"destruction", 
			"iron", 
			"fasting", 
			"duration", 
			"raw materials", 
			"oath taking", 
			"canals", 
			"fabrics", 
			"animal sacrifice", 
			"irrigation systems", 
			"annexation", 
			"pain", 
			"territorial proportion", 
			"rape", 
			"ethnocide", 
			"polity territory", 
			"synchronous movement", 
			"steel", 
			"animals", 
			"slavery", 
			"inclusiveness (ritual specialist)", 
			"other");
	
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

	
	/*
	 * Creates an array of name => value for variables found in the text
	 * If there are repeated keys, it appends _n to the duplicates to retain the values.
	 */
	function parseFactsFromString($str){
		$pattern = '/\x{2660}([^\x{2660}\x{2665}]*)\x{2663}([^\x{2660}]*)\x{2665}/u';
		$matches = array();
		$factoids = array();
		if(preg_match_all($pattern, $str, $matches)){
			for($i = 0; $i< count($matches[0]); $i++){
				if(isset($factoids[trim($matches[1][$i])])){
					$n = 1;
					while(isset($factoids[trim($matches[1][$i])."_$n"])){
						$n++;
					}
					$nv = $this->processFactValue(trim($matches[2][$i]));
					$factoids[trim($matches[1][$i])."_$n"] = $nv;
				}
				else {
					$factoids[trim($matches[1][$i])] = $this->processFactValue(trim($matches[2][$i]));
				}
			}
		}
		return $factoids;	
	}
	
	function processFactValue($val){
		$val = strip_tags($val);
		$val = trim(html_entity_decode($val));
		if(strpbrk($val, ":;[{")){
			$p = new seshatParsing($val);
			$parsedFact = $p->match_factcontainer();
			if($parsedFact){
				if($parsedFact['text'] == $val){
					$expandedFact = formatFact($parsedFact);
					$returnValue = ["value" => $expandedFact, "type" => "complex", "error" => false, "message" => "", "format" => True];
				}
				else {
					$returnValue = ["value" => $val, "type" => "complex", "error" => True, "message" => "The parser could not complete parse this fact - it has a formatting error", "format" => false];	
				}
			}
			else {
				$returnValue = ["value" => $val, "type" => "complex", "error" => True, "message" => "The parser could not parse this fact - it is not correctly formatted", "format" => False];
			}
		}
		else {
			$returnValue = ["value" => $val, "type" => "simple", "error" => false, "message" => "", "format" => False];
		}
		return $returnValue;
	}
	
	
	
	
	function getDump($data){
		$polities_retrieved = array();
		$polity_errors = array();
		foreach($data as $nga => $polities){
			foreach($polities as $p){
				if(!isset($polities_retrieved[$p])){
					$pfacts = $this->getFactsFromPage($p);
					if($pfacts){
						if($pfacts["total_variables"] > 0){
							$polities_retrieved[$p] = $pfacts;
						}
					}
				}
			}
		}
		echo json_encode($polities_retrieved);
	}
	
	/*
	 * Takes a seshat page and extracts all of the facts 
	 * Takes the URL of the page
	 * Returns an associative array of variable_name -> value (unparsed string)
	 */
	function getFactsFromPage($pageURL){
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		/*
		 * strip out the non-content
		 */
		if(strpos($content, "<div class=\"printfooter\">") && strpos($content, "<h1><span class=\"editsection\">")){
				$content = substr($content, strpos($content, "<h1><span class=\"editsection\">"), strpos($content, "<div class=\"printfooter\">") - strpos($content, "<h1><span class=\"editsection\">"));
		}
		/*
		 * Divide into main sections....
		 */
		$fact_list = array( "title" => "", "variables" => array(), "sections" => array(), "total_variables" => 0);
		$sections = explode("<h2>", $content);
		$i = 0;
		foreach($sections as $sect){
			//the first section can contain section level variables...
			if(++$i == 1){
				$fact_list['variables'] = $this->parseFactsFromString($sect);
				$fact_list["total_variables"] +=  count($fact_list['variables']);
			}
			else {
				$sec_bits = explode("</span></h2>", $sect);
				if(count($sec_bits) == 2){
					$sec_title = substr($sec_bits[0], strrpos($sec_bits[0], ">")+1);
					$sec_content = $sec_bits[1];
					$subsects = explode("<h3>", $sec_content);
					$j = 0;
					foreach($subsects as $subsect){
						if(++$j == 1){
							$factList["sections"][$sec_title]['variables'] = $this->parseFactsFromString($subsect);						
							$fact_list["total_variables"] +=  count($factList["sections"][$sec_title]['variables']);
								
						}
						else {
							$subsec_bits = explode("</span></h3>", $subsect);
							if(count($subsec_bits) == 2){
								$subsec_title = substr($subsec_bits[0], strrpos($subsec_bits[0], ">") + 1);
								$subsec_content = $subsec_bits[1];
								$factList["sections"][$sec_title]["sections"][$subsec_title] = array(
										"title" => $subsec_title, "variables" => $this->parseFactsFromString($subsec_content), "sections" => array(), "content" => "");
								$fact_list["total_variables"] +=  count($factList["sections"][$sec_title]['variables']);
							}
							else {
								$factList["sections"][$sec_title]['variables'] = $this->parseFactsFromString($subsect);
								$fact_list["total_variables"] +=  count($factList["sections"][$sec_title]['variables']);
							}								
						}
					}
				}
				else {
					$fact_list['variables'] = $this->parseFactsFromString($sect);						
					$fact_list["total_variables"] +=  count($fact_list['variables']);
				}
			}
		}
		return $factList;
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
	function formatValue($value, $factString){
		$returnValue = array();
		// print_r
		foreach($value[1] as $fact){
			if($fact[0] == "fact" or $fact[0] == "factstatement"){
				if($fact[3][0] == "keyvalue"){
					$a = $fact[3][3][0];
					$b = $fact[3][3][1];
					$returnValue[][0] = substr($factString, $a[1], ($a[2]-$a[1]));
					$returnValue[][0] = substr($factString, $b[1], ($b[2]-$b[1]));
				}else{
					$returnValue[] = substr($factString, $fact[1], ($fact[2]-$fact[1]));
				}
			}
		}
		return $returnValue;
	}
	
	//set_time_limit(1200);
	//ini_set('memory_limit', '512M');
	
	function URLtoTitle($url){
		$titleList = explode('/', $url);
		$title = end($titleList);
		$title = str_replace('_', ' ', $title);
		return $title;
	}
	

	
	function contains($string, $search){
		return strpos($string, $search) !== false;
	}
	
	function multicodesPresent($fact){
		if(in_array(strtolower($fact[0]), $this->multiCodes)){
			return true;
		}
		return false;
	}
	
	function multicodeHandling($fact, $factNode, $xpath){
		global $dlparent, $h3parent, $h2parent;
		$path = $factNode->getNodePath();
		$dlquery = $xpath->query($path.$dlparent);
		$h3query = $xpath->query($path.$h3parent);
		$h2query = $xpath->query($path.$h2parent);
		if($dlquery->length == 1){
			$section = $dlquery->item(0)->wholeText;
			$fact[0] = $section.' - '.$fact[0];
		}elseif($h3query->length == 1){
			$section = $h3query->item(0)->wholeText;
			$fact[0] = $section.' - '.$fact[0];
		}elseif($h2query->length == 1){
			$section = $h2query->item(0)->wholeText;
			$fact[0] = $section.' - '.$fact[0];
		}elseif($dlquery->length != 0){
			$fact[1] = $fact[1]."\tERROR - MULTIPLE PARENTS";
			echo 'ERROR - MULTIPLE PARENTS: '.$fact[0].': '.$fact[1].'<br>';
		}elseif($h3query->length != 0){
			$fact[1] = $fact[1]."\tERROR - MULTIPLE PARENTS";
			echo 'ERROR - MULTIPLE PARENTS: '.$fact[0].': '.$fact[1].'<br>';
		}elseif($h2query->length != 0){
			$fact[1] = $fact[1]."\tERROR - MULTIPLE PARENTS";
			echo 'ERROR - MULTIPLE PARENTS: '.$fact[0].': '.$fact[1].'<br>';
		}else{
			echo 'ERROR - NO PARENTS: '.$fact[0].': '.$fact[1].'<br>';
		}
		return $fact;
	}
	
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
