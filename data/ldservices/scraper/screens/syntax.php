<?php 
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
?>

<script>
	dacura.scraper.getParseTableHTML = function(variable, factoids){
		//alert(factoids.length);
		var html = "<table><tr><th>Row</th><th>Name</th><th>Value (from)</th><th>Value (to)</th>";
		html += "<th>Date (from)</th><th>Date (to)</th><th>Value Type</th><th>Date Type</th><th>Notes</th></tr>";
		for(var i = 0; i<factoids.length; i++){
			factoid = factoids[i];
			html += "<tr>";
			html += "<td>" + (i+1) + "</td>";
			html += "<td>" + variable + "</td>";
			html += "<td>" + factoid.value_from + "</td>";
			html += "<td>" + factoid.value_to + "</td>";
			html += "<td>" + factoid.date_from + "</td>";
			html += "<td>" + factoid.date_to + "</td>";
			html += "<td>" + factoid.value_type + "</td>";
			html += "<td>" + factoid.date_type + "</td>";
			html += "<td>" + factoid.comment + "</td>";
			html += "</tr>";
		}
		html += "</table>";
		return html;
	};
</script>

 <div id="scraper-pane-holder">
	 <ul id="scraper-pane-list" class="dch">
	 	<li><a href="#scraper-syntax-guide">Coding Rules</a></li>
	 	<li><a href="#scraper-good">Examples</a></li>
	 	<li><a href="#scraper-warning">Warnings</a></li>
	 	<li><a href="#scraper-discouraged">Common Errors</a></li>
 	</ul>
	<div id="scraper-syntax-guide">
		<p><span class='formal_variable'>VARIABLE</span> ♣ <span class='variable_value'>VALUE</span> ♥ marks a variable 
		where VARIABLE is the name of a variable in the Seshat codebook and VALUE is a value encoded according to the Seshat syntax using <span class='variable_value'>{}[];-:</span> as special characters.</p>
		<ol>
		<li><strong>Multiple Values ;</strong> are separated with the semi-colon character: <span class='variable_value'>VALUE1; VALUE2</span>
		<li><strong>Uncertainty []</strong> is indicated by surrounding a value in square brackets: <span class='variable_value'>[ VALUE ]</span>
		<li><strong>Disputed {}</strong> values are indicated by surrounding the value in curly brackets: <span class='variable_value'>{ VALUE }</span>
		<li><strong>Ranges -</strong> are indicated with a dash character:  <span class='variable_value'>VALUE1 - VALUE2</span>
		<li><strong>Dates :</strong> are placed to the left of a value and followed by a colon: <span class='variable_value'>100CE: VALUE</span>
		</ol>
		<p>Multiple dates, ranges, disputed and uncertain codes can be combined to express complex values.  
	</div>
	
 	<?php 
		foreach($examples as $k => $v){			
			echo "<div id='scraper-$k' class='scraper-pane dch'>";
				
			$i = 1;
			foreach($v as $val => $meta){
				$result = $dacura_server->parseVariableValue($val);
				echo "<h3>".$i++.". ".$meta["type"]."</h3>";
				echo "<dl><dt>Example</dt><dd>♠ <span class='formal_variable'>VAR</span> ♣ <span class='variable_value'>$val</span> ♥</dd>";
				echo "<dt>Meaning</dt><dd>".$meta["interpretation"]."</dd>";
				echo "<dt>Notes</dt><dd>".$meta["note"]."</dd>";
				if($result["result_code"] == "error" or $result["result_code"] == "empty"){
					echo "<dt>Parser Results</dt>";
					echo "<dd>".$result["result_code"].": ".$result["result_message"]."</dd>";
				}
				elseif(isset($result['datapoints']) && is_array($result['datapoints']) && count($result['datapoints'] > 0)){
					echo "<dt>Datapoints (shows how the variable will be flattened into rows in a spreadsheet)</dt>";
					echo "<dd class='datapoints' id='$k"."_$i'></dd>";
					echo '<script>$'."('#$k"."_$i').html(dacura.scraper.getParseTableHTML('VAR', ".json_encode($result['datapoints'])."));</script>";
					
				}
				echo "</dl>";
			}	
			echo "</div>";
		}
		//opr($params);
/*
			foreach($examples["good"] as $val => $meta){
				$i++;
				echo "<h3>$i. ".$meta["type"]."</h3>";
				echo "<dl><dt>Example</dt><dd>♠ <span class='formal_variable'>VAR</span> ♣ <span class='variable_value'>$val</span> ♥</dd>";
				echo "<dt>Meaning</dt><dd>".$meta["interpretation"]."</dd>";
				echo "<dt>Notes</dt><dd>".$meta["note"]."</dd>";
				if($meta["result"]["result_code"] == "error" or $meta["result"]["result_code"] == "empty"){
					echo "<dt>Parser Results</dt>";
					echo "<dd>".$meta["result"]["result_code"].": ".$meta["result"]["result_message"]."</dd>";
				}
				elseif(isset($meta["result"]['datapoints']) && is_array($meta["result"]['datapoints']) && count($meta["result"]['datapoints'] > 0)){
					echo "<dt>Datapoints (shows how the variable will be flattened into rows in a spreadsheet)</dt>";
					echo "<dd class='datapoints' id='good_$i'></dd>";
					echo '<script>$'."('#good_$i').html(dacura.scraper.getParseTableHTML('VAR', ".json_encode($meta["result"]['datapoints'])."));</script>";
				}
				echo "</dl>";	
			}
		}
		if(isset($examples['discouraged'])){
			echo "<H2>Examples of Legal but Discouraged Usage</h2>";
			foreach($examples["discouraged"] as $val => $meta){
				$i++;
				echo "<h3>$i. ".$meta["type"]."</h3>";
				echo "<dl><dt>Example</dt><dd>♠ <span class='formal_variable'>VAR</span> ♣ <span class='variable_value'>$val</span> ♥</dd>";
				echo "<dt>Meaning</dt><dd>".$meta["interpretation"]."</dd>";
				echo "<dt>Notes</dt><dd>".$meta["note"]."</dd>";
				if($meta["result"]["result_code"] == "error" or $meta["result"]["result_code"] == "empty"){
					echo "<dt>Parser Results</dt>";
					echo "<dd>".$meta["result"]["result_code"].": ".$meta["result"]["result_message"]."</dd>";
				}
				elseif(isset($meta["result"]['datapoints']) && is_array($meta["result"]['datapoints']) && count($meta["result"]['datapoints'] > 0)){
					echo "<dt>Datapoints (shows how the variable will be flattened into rows in a spreadsheet)</dt>";
					echo "<dd class='datapoints' id='discouraged_$i'></dd>";
					echo '<script>$'."('#discouraged_$i').html(dacura.scraper.getParseTableHTML('VAR', ".json_encode($meta["result"]['datapoints'])."));</script>";
				}
				echo "</dl>";	
			}				
		}
		if(isset($examples['warning'])){
			echo "<H2>Examples of illegal usage that will raise a warning</h2>";
			foreach($examples["warning"] as $val => $meta){
				$i++;
				echo "<h3>$i. ".$meta["type"]."</h3>";
				echo "<dl><dt>Example</dt><dd>♠ <span class='formal_variable'>VAR</span> ♣ <span class='variable_value'>$val</span> ♥</dd>";
				echo "<dt>Meaning</dt><dd>".$meta["interpretation"]."</dd>";
				echo "<dt>Notes</dt><dd>".$meta["note"]."</dd>";
				echo "<dt>Parser Results</dt>";
				echo "<dd>".$meta["result"]["result_code"].": ".$meta["result"]["result_message"]."</dd>";
				if(isset($meta["result"]['datapoints']) && is_array($meta["result"]['datapoints']) && count($meta["result"]['datapoints'] > 0)){
					echo "<dt>Datapoints (shows how the variable will be flattened into rows in a spreadsheet)</dt>";
					echo "<dd class='datapoints' id='discouraged_$i'></dd>";
					echo '<script>$'."('#discouraged_$i').html(dacura.scraper.getParseTableHTML('VAR', ".json_encode($meta["result"]['datapoints'])."));</script>";
				}
				echo "</dl>";
			}
		}*/
	?>
	</div>
	<script>
	
	    $('document').ready(function(){
			$("#scraper-pane-list").show();
			$("#scraper-pane-holder").tabs();
		});
	</script>
