
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
		html += "</table>";
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
		 	<li><a href="#scraper-test">Test Variable</a></li>
		 	<?php if($dacura_server->userHasRole("admin")) {?><li><a href="#scraper-testpage">Test URL</a></li><?php }?>
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
		<div id="scraper-test" class="scraper-pane dch">
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
		<?php if($dacura_server->userHasRole("admin")) {?>
			<div id="scraper-testpage" class="scraper-pane dch">
				<div id="tpaddmsg"></div>
				<div class="tpholder">
					<label>URL:</label> <input id='parseurl' type='text' size=40>
				</div>	
				<div class="pcsection pcbuttons">
					<a class="button2" href="javascript:dacura.scraper.testpage()">Test Page</a>
				</div>
				<div class="tpresults"></div>
			</div>
		<?php } ?>		
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
					var html = "<div id='scraper-results'>";
					html += "<h3>Results</h3><dl>";
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

	<?php if($dacura_server->userHasRole("admin")) {?>
		dacura.scraper.testpage = function(){
			$('#testpage-results').remove();
			$("#tpaddmsg").html("");
			var ajs = dacura.scraper.api.parsePage();
			ajs.data.url = $('#parseurl').val();
			if(!dacura.toolbox.validateURL(ajs.data.url)){
				return dacura.toolbox.writeErrorMessage("#tpaddmsg", "Error: " + ajs.data.url + " is not a valid url");					
			}
			ajs.beforeSend = function(){
				dacura.toolbox.writeBusyOverlay("#scraper-testpage", "Fetching " + ajs.data.url);
			};
			ajs.complete = function(){
				dacura.toolbox.removeBusyOverlay("#scraper-testpage");
			};
			$.ajax(ajs)
				.done(function(data, textStatus, jqXHR) {
					try {
						var x = JSON.parse(data);
						var html = "<div id='testpage-results'>";
						html += "<h3>Results</h3>";
						html += "<table><thead><tr><th>NGA</th><th>Polity</th><th>Section</th><th>Subsection</th><th>Variable</th><th>Value From</th><th>Value To</th><th>";
						html += "Date From</th><th>Date To</th><th>Fact Type</th><th>Value Note</th><th>Date Note</th><th>Comment</th></tr><thead><tbody>";
						for(var i = 0; i < x.length; i++){
							html + "<tr>";
							for(j=0; j<x[i].length; j++) {
								html += "<td>" + x[i][j] + "</td>";
							}
							html += "</tr>";
						}
						html += "</tbody></table>";
						html += "</div>";
						$('.tpresults').html(html);
					}
					catch(e){
						dacura.toolbox.writeErrorMessage("#tpaddmsg", "Error: " + e.message);					
					}
				})
				.fail(function (jqXHR, textStatus){
					dacura.toolbox.writeErrorMessage("#tpaddmsg", "Error: " + jqXHR.responseText );
				});	
		};
	<?php } ?>
	
    $('document').ready(function(){
		$("button").button();
		$("#scraper-pane-list").show();
		$("#scraper-pane-holder").tabs();
	});
	</script>
