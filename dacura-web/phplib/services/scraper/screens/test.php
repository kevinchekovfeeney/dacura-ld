
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->get_service_file_url('style.css')?>" />
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
		return html;
	};
</script>
<div class="tool-header">
   	<span class="tool-title">Seshat Variable Test Tool</span>
	<span class="tool-description">This tool allows users to test Seshat variable values.</span>
   </div>
   <div id="scraper-pane-holder">
		 <ul id="scraper-pane-list" class="dch">
		 	<li><a href="#scraper-examples">Examples</a></li>
		 <li><a href="#scraper-test">Test</a></li>
		</ul>
		<div id="scraper-examples" class="scraper-pane dch pcdatatables">
			<?php if(isset($params['examples']) && isset($params['examples']['good'])){
				echo "<h2>Examples of Best Practice Usage</h2>";
				$i = 0;
				foreach($params['examples']["good"] as $val => $meta){
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
			if(isset($params['examples']['discouraged'])){
				echo "<H2>Examples of Legal but Discouraged Usage</h2>";
				foreach($params['examples']["discouraged"] as $val => $meta){
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
			if(isset($params['examples']['warning'])){
				echo "<H2>Examples of illegal usage that will raise a warning</h2>";
				foreach($params['examples']["warning"] as $val => $meta){
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
			}?>
		</div>
		<div id="scraper-test" class="scraper-pane dch pcdatatables">
			<div id="saddmsg"></div>
			<div class="sholder">
				<label class="seshatvar">♠ VAR ♣ </label><textarea id="seshatvalue"></textarea><label class="seshatvar"> ♥</label>
			</div>	
			<div class="pcsection pcbuttons">
				<a class="button2" href="javascript:dacura.scraper.cleartest()">Clear</a>
				<a class="button2" href="javascript:dacura.scraper.test()">Test Variable</a>
			</div>
			<div class="sresults"></div>
		</div>
   </div>

<script>

	dacura.scraper.cleartest = function(){
		$('#scraper-results').remove();
		$("#saddmsg").html("");
		$('#seshatvalue').val("");
	}		
			
	dacura.scraper.test = function(){
		$('#scraper-results').remove();
		$("#saddmsg").html("");
		var ajs = dacura.scraper.api.parseValue();
		ajs.data.data = $('#seshatvalue').val();
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				try {
					var x = JSON.parse(data);
					var html = "<div id='scraper-results'><dl>";
					html += "<h3>Results</h3>";
					html += "<dt>Value</dt><dd>" + x.value + "</dd>";
					html += "<dt>Result Code</dt><dd>" + x.result_code + "</dd>";
					html += "<dt>Result Message</dt><dd>" + x.result_message + "</dd>";
					if(typeof x.datapoints !== "undefined"){
						html += "<dt>Datapoints</dt><dd class='datapoints'>" + dacura.scraper.getParseTableHTML('VAR', x.datapoints) + "</dd>";
					}
					html += "</dl></div>";
					$('.sresults').html(html);
				}
				catch(e){
					dacura.toolbox.writeErrorMessage("#saddmsg", "Error: " + e.message);
					
				}
			})
			.fail(function (jqXHR, textStatus){
				dacura.toolbox.writeErrorMessage("#saddmsg", "Error: " + jqXHR.responseText );
			}
		);	
	};

    $('document').ready(function(){
		$("button").button();
		$("#scraper-pane-list").show();
		$("#scraper-pane-holder").tabs();
	});
	</script>
