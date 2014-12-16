<META HTTP-EQUIV='Content-Type' CONTENT='text/html; charset=utf-8'>
<?php
/*

parsing tool for the seshat wiki scraper
Copyright (C) 2014 Odhran Gavin/Dacura Team?

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

require_once 'seshat-parser.php';
require_once 'seshat-parser-helper.php';

$username = 'gavin';
$password = 'cheguevara';
$loginUrl = 'http://seshat.info/w/index.php?title=Special:UserLogin&action=submitlogin';
$pageURL = 'http://seshat.info/Zimbabwe_Plateau';
$mainPage = 'http://seshat.info/Main_Page';
$dlparent = '/ancestor::dl/preceding-sibling::h3[1]/span[@class!="editsection"]/text()';
$h3parent = '/../preceding-sibling::h3[1]/span[@class!="editsection"]/text()';
$h2parent = '/../preceding-sibling::h2[1]/span[@class!="editsection"]/text()';
$multiCodes = array("human sacrifice of an out-group member", "typical size of participating group", "willingness to die for each other", "enslavement", "slaves", "cost", "inclusiveness (audience)", "vigil/sleep deprivation", "human sacrifice of a relative", "energy proportion", "community as a whole", "singing", "markets", "looting", "extermination", "human sacrifice of an in-group member", "risk of death", "obligations to each other's families", "currency", "holders of special offices or [positions", "expulsion", "height", "deportation", "orthodoxy checks", "food storage sites", "enemies of any group member are my enemies", "ra", "ports", "health costs", "sack", "alcohol", "entertainment", "frequency per participant", "inclusiveness (participants)", "euphoria-inducing drugs (other than alcohol)", "none", "frequency for the audience", "orthopraxy checks", "bridges", "targeted massacre", "mutilation", "other", "frequency for the ritual specialist", "alternative names", "dancing", "name", "professional military officers", "feasting", "drinking water supply systems", "expert", "fear", "disgust", "others", "bronze", "poisoning/dysphoria-causing drugs", "general massacre", "humiliation", "foodstuffs", "sex", "extent", "metals", "roads", "productivity", "honour code", "property/valuable items", "elders", "destruction", "iron", "fasting", "duration", "raw materials", "oath taking", "canals", "fabrics", "animal sacrifice", "irrigation systems", "annexation", "pain", "territorial proportion", "rape", "ethnocide", "polity territory", "synchronous movement", "steel", "animals", "slavery", "inclusiveness (ritual specialist)", "other");

set_time_limit(0);
ini_set('memory_limit', '512M');

function URLtoTitle($url){
	$titleList = explode('/', $url);
	$title = end($titleList);
	$title = str_replace('_', ' ', $title);
	return $title;
}

function pageGrab($pageURL, $ch, $type, $nga = ""){
	curl_setopt($ch, CURLOPT_URL, $pageURL);
	$content = curl_exec($ch);
	$dom = new DOMDocument;
	$dom->loadXML($content);
	$xpath = new DOMXPath($dom);

	//determine type and links etc
	if($type == 'nga'){
		$ngaName = URLtoTitle($pageURL);
		$link = $pageURL;
		$polityName = '';
	}elseif($type = 'polity'){
		$ngaName = URLtoTitle($nga);
		$link = $pageURL;
		$polityName = URLtoTitle($pageURL);
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
	}else{
		foreach($facts as $factNode) {
			$fact = $factNode->ownerDocument->saveXML($factNode);
		    $parsedFact = $fact;
		    if(multicodesPresent($parsedFact) && $parsedFact[1] !== 'ERROR IN SOURCE'){
		    	$parsedFact = multicodeHandling($parsedFact, $factNode, $xpath);
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
	global $multiCodes;
	if(in_array(strtolower($fact[0]), $multiCodes)){
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

function splitCheck($fact){
	if(contains($fact, ":")){
		return True;
	}elseif(contains($fact, ";")){
		return True;
	}elseif(contains($fact, "[")){
		return True;
	}elseif(contains($fact, "{")){
		return True;
	}else{
		return False;
	}
}

function parseFact($factString){
	$possibleHTML = False;
	$factParts = explode("♣", $factString);
	if(count($factParts) != 2){
		$message = "The fact cannot be split correctly. Please check that the delimiters are correct.";
		$returnValue = ["name" => $factString, "value" => "", "error" => True, "message" => $message, "format" => False];
		return $returnValue;
	}
	//remove ends
	$a = explode("♠", $factParts[0]);
	$name = trim($a[1]);
	$b = explode("♥", $factParts[1]);
	$value = trim($b[0]);
	$value = str_replace("&lt;", "<", $value);
	$value = str_replace("&gt;", ">", $value);

	if(contains($value, "<") or contains($value, ">")){
		$possibleHTML = True;
		$value = strip_tags($value);
		// if(preg_match("\<.*>.*\<\\/.*>", $value)){
			// $value = str_replace("<", "", $value);
			// $value = str_replace(">", "", $value);
			// $returnValue = ["name" => $name, "value" => $value, "error" => True, "message" => $message, "format" => False];
			// return $returnValue;
		// }
	}

	if(splitCheck($value)){
		if($value[0] == "'" or $value[0] == '"'){
			//this is only temporary testing = will change to stripping these thing and continuing
			$message = "Enclosing quotation marks. Please report this error to scraper maintainer.";
			$returnValue = ["name" => $name, "value" => $value, "error" => True, "message" => "", "format" => False];
			return $returnValue;
		}
		$p = new seshatParsing($value);
		$parsedFact = $p->match_factcontainer();
		$parsedFactText = $parsedFact["text"];
		if($parsedFact and strlen($parsedFactText) == strlen($value)){
			if(contains($value, "-[") or contains($value, "]-")){
				$message = "Daterange false positive. Please report this error to scraper maintainer.";
				$returnValue = ["name" => array(), "value" => array(), "error" => True, "message" => $message, "format" => False];
			}else{
				$message = "Correctly parsed.";
				$returnValue = ["name" => $name, "value" => $parsedFact, "error" => False, "message" => $message, "format" => True];
			}
		}elseif($parsedFact){
			$message = "Possibly correct, check.";
			$returnValue = ["name" => $name, "value" => $parsedFact, "error" => False, "message" => $message, "format" => True];
		}else{
			// temporary removal - return an error for now
			// $errorParse = new seshatErrorParsing($factParts[1]);
			// $errorFactText = $errorParse["text"];
			// if($errorParse and strlen($errorFactText) == strlen($value)){
			// 	$message = "Incorrect separators detected - replace commas with semicolons where appropriate.";
			// 	$returnValue = ["name" => $name, "value" => $value, "error" => True, "message" => $message, "format" => False];
			// }else{
			// 	$message = "This fact could not be parsed.";
			// 	$returnValue = ["name" => $name, "value" => $value, "error" => True, "message" => $message, "format" => False];
			// }
			$message = "Testing - error parsing shim.";
			$returnValue = ["name" => $name, "value" => $value, "error" => True, "message" => $message, "format" => False];
		}
	}else{
		$returnValue = ["name" => $name, "value" => $value, "error" => False, "message" => "", "format" => False];
	}
	if($possibleHTML){
		$returnValue["message"] = $returnValue["message"]." | This fact may contains HTML tags.";
	}
	return $returnValue;
}

error_reporting(E_ALL);

$ch = curl_init();

//initial curl setting
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookie.txt');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_PROXY, 'proxy.cs.tcd.ie');
curl_setopt($ch, CURLOPT_PROXYPORT, 8080);

//get token from login page
$store = curl_exec($ch);
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

//login
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'wpName='.$username.'&wpPassword='.$password.'&wpLoginAttempt=Log+in&wpLoginToken='.$loginToken);
$store = curl_exec($ch);

$type = $_POST["type"];

if($type==="nga"){
	curl_setopt($ch, CURLOPT_URL, $mainPage);
	$content = curl_exec($ch);
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
	echo json_encode($ngaURLs);
}elseif($type==="polity"){
	$nga = $_POST["data"];
	$polities = array();
	$ngaArray = array();
		curl_setopt($ch, CURLOPT_URL, $nga);
		$content = curl_exec($ch);
		$dom = new DOMDocument;
		$dom->loadXML($content);
		$xpath = new DOMXPath($dom);
		$temp = pageGrab($nga, $ch, 'nga');
		$ngaArray[] = $temp;
		$links = $xpath->query('//a/@href');
		foreach($links as $link){
			$x = $link->value;
			$url = 'http://seshat.info'.$x;
			$polities[] = $url;
		}
	$return = array("polities" => $polities, "payload" => $ngaArray);
	echo json_encode($return);
}elseif($type==="data"){
	$polity = $_POST["data"];
	$nga = $_POST["nga"];
	$polityArray = array();
	$temp = pageGrab($polity, $ch, 'polity', $nga);
	$polityArray[] = $temp;
	$return = $polityArray;
	echo json_encode($return);
}elseif($type==="parse"){
	$failCount = 0;
	$goodCount = 0;
	$parseCount = 0;
	$data = json_decode($_POST["data"]);
	$formattedArray = array();
	$formattedArray[] = "NGA\tPolity|tVariable\tKey 1\tKey 2\tValue 1\tValue 2\tUncertainty\tDisagreement\tNotes";
	for($i=0;$i<count($data);$i++){
		$nga = $data[$i]->metadata->nga;
		$polity = $data[$i]->metadata->polity;
		$facts = $data[$i]->data;
		foreach($facts as $fact){
			$parsed = parseFact($fact->contents);
			if($parsed["format"]){
				$parsed["value"] = formatFact($parsed["value"]);
				$temp = variableArrayToLines($nga, $polity, $parsed);
				foreach($temp as $x){
					$formattedArray[] = $x;
				}
			}else{
				$line = $nga."\t".$polity."\t".$parsed["name"]."\t\t".$parsed["value"]."\tVAL2\t\t\t".$parsed["message"];
				$formattedArray[] = $line;
			}
		}
	}
	//all the logging and report stuff happens here now
	//TEMPORARY - replace with output and error generation
	$fileName = 'test.tsv';
	$file = fopen($fileName, "w");
	foreach($formattedArray as $item){
		$item = $item."\n";
		fwrite($file, $item);
	}
	fclose($file);
}elseif($type="validate"){
	//code for handling validation goes here
}
?>