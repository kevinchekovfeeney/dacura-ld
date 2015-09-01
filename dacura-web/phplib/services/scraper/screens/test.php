

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
		 	<li><a href="#scraper-test">Test Variable Value</a></li>
		 	<?php if($dacura_server->userHasRole("admin")) {?><li><a href="#scraper-testpage">Test Page</a></li><?php }?>
		</ul>
		<div id="scraper-test" class="scraper-pane dch">
			<div class="sholder">
				<p>Enter a seshat value into the box below to see how the Scraper will turn the value into datapoints</p>
				<table><tr><td>
				<div id="saddmsg"></div>
				<textarea id="seshatvalue"></textarea>
				</td><td align="center" valign="bottom">
					<a class="button2" href="javascript:dacura.scraper.test()">Analyse</a>
					<a id="clear" class="dch" style="margin-bottom: 4px" href="javascript:dacura.scraper.cleartest()">Clear</a>
					
				</td>
				</tr></table>
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
		$('#clear').hide();	
	}		
			
	dacura.scraper.test = function(){
		$('#scraper-results').remove();
		$('#clear').show();
		$("#saddmsg").html("");
		var ajs = dacura.scraper.api.parseValue();
		ajs.data.data = $('#seshatvalue').val();
		$.ajax(ajs)
			.done(function(data, textStatus, jqXHR) {
				try {
					var x = JSON.parse(data);					
					var html = "<div id='scraper-results' class='test-results test-results-" + x.result_code + "'>";
					if(x.result_code == "error"){
						html += "<h3>Error :<i>" + x.value + "</i></h3>";
						html += "<p>" + x.result_message + "</p>";
					}
					else {
						html += "<h3>Results for <i>" + x.value + "</i></h3>";
					}
					if(x.result_code == "warning"){
						html += "<p><strong>Warning</strong>: " + x.result_message + "</p>";
					}
					if(typeof x.datapoints !== "undefined"){
						html += dacura.scraper.getParseTableHTML('VAR', x.datapoints);
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
			var page = $('#parseurl').val();
			if(!dacura.toolbox.validateURL(page)){
				return dacura.toolbox.writeErrorMessage("#tpaddmsg", "Error: " + ajs.data.url + " is not a valid url");					
			}
			var ajs = dacura.scraper.api.parsePage(page);
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
