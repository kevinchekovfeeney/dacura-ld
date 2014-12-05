<?php

/*
 * Class representing a collection of datasets in the Dacura System
 * Collections are the highest level division of dacura context.
 *
 * Created By: Odhran
 * Creation Date: 20/11/2014
 * Contributors: Chekov
 * Modifications: 20/11/2014
 * Licence: GPL v2
 */


include_once("phplib/DacuraServer.php");

//need to do logging, cookiejar, etc.

class ScraperDacuraServer extends DacuraServer {
	
	var $ch; //curl handle
	var $cookiejar = "C:\\Temp\\dacura\\cookiejar.txt";
	var $parser_service_url = 'http://localhost:1234/parser';
	var $username = 'gavin';
	var $password = 'cheguevara';
	var $loginUrl = 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin';
	var $pageURL = 'http://seshat.info/Zimbabwe_Plateau';
	var $mainPage = 'http://seshat.info/Main_Page';
	var $dlparent = '/ancestor::dl/preceding-sibling::h3[1]/span[@class!="editsection"]/text()';
	var $h3parent = '/../preceding-sibling::h3[1]/span[@class!="editsection"]/text()';
	var $h2parent = '/../preceding-sibling::h2[1]/span[@class!="editsection"]/text()';
	var $multiCodes = array(
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
	
	function getPolities($nga){
		$polities = array();
		$ngaArray = array();
		curl_setopt($this->ch, CURLOPT_URL, $nga);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve nga page $nga.", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		$dom = new DOMDocument;
		$dom->loadXML($content);
		$xpath = new DOMXPath($dom);
		$temp = $this->pageGrab($nga, 'nga');
		if($temp){
			$ngaArray[] = $temp;
			$links = $xpath->query('//a/@href');
			foreach($links as $link){
				$x = $link->value;
				$url = 'http://seshat.info'.$x;
				$polities[] = $url;
			}
			return array("polities" => $polities, "payload" => $ngaArray);
		}
	}
	
	function getData($nga, $polity){
		return $this->pageGrab($polity, 'polity', $nga);
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
	
	function getDump($data){
		//opr($data);
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
	
	function pageGrab($pageURL, $type, $nga = ""){
		curl_setopt($this->ch, CURLOPT_URL, $pageURL);
		$content = curl_exec($this->ch);
		if(curl_getinfo($this->ch, CURLINFO_HTTP_CODE) != 200 || !$content){
			return $this->failure_result("Failed to retrieve $pageURL", curl_getinfo($this->ch, CURLINFO_HTTP_CODE));
		}
		$dom = new DOMDocument;
		$dom->loadXML($content);
		$xpath = new DOMXPath($dom);
	
		//determine type and links etc
		if($type == 'nga'){
			$ngaName = $this->URLtoTitle($pageURL);
			$link = $pageURL;
			$polityName = '';
		}elseif($type = 'polity'){
			$ngaName = $this->URLtoTitle($nga);
			$link = $pageURL;
			$polityName = $this->URLtoTitle($pageURL);
		}
	
		$headers = $xpath->query('//h1//text()[normalize-space()]');
		$polityName = $headers->item(0)->wholeText;
		$facts = $xpath->query('//*[text()[contains(., "♠")]]');
		$factObject = array();
		$factObject["metadata"]["nga"] = $ngaName;
		$factObject["metadata"]["polity"] = $polityName;
		$factObject["metadata"]["url"] = $pageURL;
		$factObject["metadata"]["user"] = "x_scraper_user_id";
	
		if ($facts->length == 0){
			$factObject["data"] = array();
		}
		else {
			foreach($facts as $factNode) {
				$fact = $factNode->ownerDocument->saveXML($factNode);
				$parsedFact = $fact;
				if($this->multicodesPresent($parsedFact) && $parsedFact[1] !== 'ERROR IN SOURCE'){
					$parsedFact = $this->multicodeHandling($parsedFact, $factNode, $xpath);
					$fact = '♠ '.$parsedFact[0].' ♣ '.$parsedFact[1].' ♥';
				}
				if ($parsedFact[1] != ''){
					$factObject["data"][]["contents"] = $parsedFact;
				}
			}
		}
		return $factObject;
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