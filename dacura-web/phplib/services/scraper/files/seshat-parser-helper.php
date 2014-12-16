<?php
function separate_dates_from_values($fact){
	$left = $fact['value'][0]["text"];
	$right = $fact['value'][1]["text"];
	$separated_vals = array();
	$date_full_pattern = "/(([0-9]{1,4}\s*(bce|bc|ce)?)\s*-\s*)?([0-9]{1,4}\s*(bc|bce|ce))/i";
	$matches = array();
	if(preg_match($date_full_pattern, $left, $matches)){
		$separated_vals['date_from'] = $matches[2];
		if(isset($matches[4]) && $matches[4]){
			$separated_vals['date_to'] = $matches[4];
		}
		$separated_vals['orig'] = $left;
		$separated_vals['value'] = format_statement($fact['value'][1]);
		$separated_vals['error'] = false;
	}
	elseif(preg_match($date_full_pattern, $right, $matches)){
		$separated_vals['date_from'] = $matches[2];
		if(isset($matches[4]) && $matches[4]){
			$separated_vals['date_to'] = $matches[4];
		}
		$separated_vals['orig'] = $right;
		$separated_vals['error'] = false;
		$separated_vals['value'] = format_statement($fact['value'][0]);
	}
	else {
		$separated_vals['date_from'] = $left;
		$separated_vals['error'] = "Failed to find a valid date string";
		$separated_vals['value'] = format_statement($fact['value'][1]);
	}
	return $separated_vals;
}

function format_statement($statement){
	$statementValue = $statement["value"];
	if(array_key_exists("name", $statementValue)){
		if($statementValue["name"] == "string"){
			return ["value" => $statementValue["text"], "uncertain" => False, "disagreement" => False];
		}elseif($statementValue["name"] == "uncertainty"){
			return format_uncertainty($statementValue);
		}elseif($statementValue["name"] == "disagreement"){
			return format_disagreement($statementValue);
		}elseif($statementValue["name"] == "range"){
			return format_range($statementValue);
		}else{
			///ERROR
		}
	}
}

function format_range($range){
	//check if range is numeric from $range["text"]
	//assuming not numerical for now
	$isNumeric = False;
	if($isNumeric){
		//pass
	}else{
		$values = array();
		$strings = $range["value"];
		foreach($strings as $item){
			$values[] = $item["text"];
		}
	}
	return ["value" => $values, "uncertain" => False, "disagreement" => False];
}

function format_uncertainty($uncertainty){
	$values = array();
	$strings = $uncertainty["value"];
	if(array_key_exists("name", $strings)){
		$values[] = $strings["text"];
	}else{
		foreach($strings as $item){
			$values[] = $item["text"];
		}
	}
	return ["value" => $values, "uncertain" => True, "disagreement" => False];
}

function format_disagreement($disagreement){
	$values = array();
	$valuesTemp = array();
	$uncertain = False;
	$strings = $disagreement["value"];
	foreach($strings as $item){
		$x = format_statement($item);
		if($x["uncertain"]){
			$uncertain == True;
		}
		$values[] = $x["value"];
	}
	return ["value" => $values, "uncertain" => $uncertain, "disagreement" => True];
}

function format_singleton($fact){
	$statement = $fact["value"];
	if($statement["name"] != "statement"){
		//ERROR
	}else{
		$keys = ["value" => "", "uncertain" => False, "disagreement" => False];
		$values = format_statement($statement);
		$message = "";
		return ["date_from" => "", "date_to" => "", "value" => $values, "message" => $message];
	}
}

function format_keyvalue($fact){
    return separate_dates_from_values($fact);
}

function formatFact($fact){
	if($fact["name"] != "factcontainer"){
		return False;
	}
	$container = $fact["value"];
	$disagreeAll = False;
	$uncertainAll = False;
	if($container["name"] != "factstatement" AND $container["name"] != "disagreefactstatement" AND $container["name"] != "uncertainfactstatement"){
		return False;
	}
	if($container["name"] == "disagreefactstatement"){
		$disagreeAll = True;
	}elseif($container["name"] == "uncertainfactstatement"){
		$uncertainAll = True;
	}
	$containedFacts = array();
	if(array_key_exists("name", $container["value"])){
		if($container["value"]["name"] != "fact"){
			echo "KEY: '".$container["value"]["name"]."'<br>";
		}else{
			$containedFacts[] = $container["value"];
		}
	}elseif(array_key_exists(0, $container["value"])){
		$containedFacts = $container["value"];
	}else{
		//ERROR
	}
	$factArray = array();
	foreach($containedFacts as $item){
		if($item["name"] != "fact"){
			//ERROR
		}else{
			$basefact = $item["value"];
			$uncertainFact = False;
			if($basefact["name"] == "uncertainbasefact"){
				$uncertainFact = True;
			}
			$factContents = $basefact["value"];
			if($factContents["name"] == "singleton"){
				$x = format_singleton($factContents);
			}elseif($factContents["name"] == "keyvalue"){
				$x = format_keyvalue($factContents);
			}else{
				//ERROR
			}
		}
		if($x === null){
			//ERROR
		}elseif(!array_key_exists("values", $x)){
			//ERROR
		}else{
			if(array_key_exists("keys", $x)){
				if(array_key_exists("value", $x["keys"])){
					if(gettype($x["keys"]["value"]) == "array"){
						if(count($x["keys"]["value"]) > 2){
							echo "KEYS > 2<br>";
						}
					}
				}else{
					//ERROR
				}
			}
			$testingFlagIgnore = False;
			if(array_key_exists("values", $x)){
				if(gettype($x["values"]["value"]) == "array"){
					if(count($x["values"]["value"]) > 2){
						//need to handle this - make a handling function
						$testingFlagIgnore = True;
					}elseif(array_key_exists(1, $x["values"]["value"])){
						if(gettype($x["values"]["value"][1]) == "array"){
							//need to handle this - make a handling function
							$testingFlagIgnore = True;
						}elseif(gettype($x["values"]["value"][0]) == "array"){
							//need to handle this - make a handling function
							$testingFlagIgnore = True;
						}else{
							$factArray[] = $x;
						}
					}elseif(array_key_exists(0, $x["values"]["value"])){
						if(gettype($x["values"]["value"][0]) == "array"){
							//need to handle this - make a handling function
							$testingFlagIgnore = True;
						}else{
							$factArray[] = $x;
						}
					}else{
						//ERROR
					}
				}else{
					$factArray[] = $x;
				}
			}else{
				//ERROR
			}
		}
	}
	if(count($factArray) == 0 and !$testingFlagIgnore){
		//ERROR
	}
	return $factArray;
}

function variableArrayToLines($nga, $polity, $parsedArray){
	$variableName = $parsedArray["name"];
	$variableArray = $parsedArray["value"];
	$errorMessage = $parsedArray["message"];
	$startLine = $nga."\t".$polity."\t".$variableName."\t";
	$otherLine = "\t\t\t";
	$tempArray = array();
	$doneArray = array();
	foreach($variableArray as $part){
		if($part === null or gettype($part) == "string" or !array_key_exists("keys", $part)){
			//ERROR
		}else{
			$keys = $part["keys"]["value"];
			if(gettype($keys) == "array"){
				$key1 = $keys[0];
				$key2 = $keys[1];
			}else{
				$key1 = $keys;
				$key2 = "";
			}
			$values = $part["values"]["value"];
			if(gettype($values) == "array"){
				if(count($values) == 1){
					$val1 = $values[0];
					$val2 = "";
				}elseif(count($values) == 2){
					$val1 = $values[0];
					$val2 = $values[1];
				}else{
					//ERROR
				}
			}else{
				$val1 = $values;
				$val2 = "";
			}
			$dis = "";
			$unc = "";
			$notes = "";
			$tempArray[] = $key1."\t".$key2."\t".$val1."\t".$val2."\t".$unc."\t".$dis."\t".$notes;
		}
	}
	for($i = 0;$i < count($tempArray);$i++){
		if($i == 0){
			$doneArray[] = $startLine.$tempArray[0];
		}else{
			$doneArray[] = $otherLine.$tempArray[$i];
		}
	}
	return $doneArray;
}