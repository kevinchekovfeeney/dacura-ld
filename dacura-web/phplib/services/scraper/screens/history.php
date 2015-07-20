<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->get_service_file_url('style.css')?>" />
   <div class="tool-header">
   	<span class="tool-title">Seshat Scraper - Historical Views</span>
	<span class="tool-description">This tool shows trends in data collection on the Seshat wiki.</span>
   </div>
   <div id="history-info"></div>
   <div class="history-screen" id="history-screen-1">
      <div class="tool-buttons">
   		<button class="dacura-button get-ngas" id="get-ngas">Retrieve History&gt;&gt;</button>
   	   </div>
   </div>
   <div class="history-screen" id="history-screen-2">
   		<div id="nga-display" class="tool-content"></div>
   		<div class="tool-buttons">
	   	<button class="dacura-button back-button get-ngas" id="get-ngas2">Refresh NGA List</button>
	   	<button class="dacura-button forward-button" id="get-polities">2. Choose Polities to Export &gt;&gt;</button>
	   	</div>
   </div>
   <div class="history-screen" id="history-screen-3">
   		<div id="polity-display" class="tool-content">
   		</div>
   		<div class="tool-buttons">
   			<button class="dacura-button back-button" id="back-ngas">&lt;&lt; Return to NGA List</button>
			<button class="dacura-button forward-button" id="get-data">3. Export Data &gt;&gt;</button>
	   </div>
   </div>
   <div class="history-screen" id="history-screen-4">
   		<div id="results-display" class="tool-content">
   		</div>
		<div class="tool-buttons">
			<button class="dacura-button back-button" id="back-polities">&lt;&lt; Change Polity List</button>		
		</div>	
   </div>
   <div style="clear: both; height: '10px'">&nbsp;</div>
	<div id="functionality">
	</div>
	<script>

		function resetScraperScreen(n){
			$('#history-screen-'+n +' div.tool-content').html("");
		}

		function hideScraperScreen(n){
			$('#history-screen-'+n).hide();	
		}
		
		function showScraperScreen(n){
			$('#history-screen-'+n).show();
		}
	
		function include(url){
			//change to case statement
			if(includeAll){
				return true;
			}else if(url.indexOf("&") > -1){
				return false;
			}else if(url.indexOf("#") > -1){
				return false;
			}else if(url.indexOf("User") > -1){
				return false;
			}else if(url.indexOf("Special") > -1){
				return false;
			}else if(url.indexOf("Conflicts:") > -1){
				return false;
			}else if(url.indexOf("Main_Page") > -1){
				return false;
			}else if(url.indexOf("File:") > -1){
				return false;
			}else if(url.indexOf("Memento") > -1){
				return false;
			}else if(url.indexOf("Code_book") > -1){
				return false;
			}else if(url.indexOf("Macrostate_Inventory") > -1){
				return false;
			}else if(url.indexOf("Seshat.info") > -1){
				return false;
			}else if(url.indexOf("mediawiki") > -1){
				return false;
			}else if(url.indexOf(".jpg") > -1){
				return false;
			}else if(url.indexOf(".gif") > -1){
				return false;
			}else if(url.indexOf(".pdf") > -1){
				return false;
			}else if(url.indexOf(".png") > -1){
				return false;
			}else if(url.indexOf("infohttp") > -1){
				return false;
			}else if(url.indexOf("Productivity_Template") > -1){
				return false;
			}else if(url.indexOf("Talk") > -1){
				return false;
			}
			return true;
		}

		var requests = [];
		var NGAsObtainedCount = 0;
		var includeAll = false;

		var ngaStatus = {
			loaded: 0,
			failed: 0,
			message: ""
		};
		var polityStatus = {
			loaded: 0,
			failed: 0,
			message: ""
		};
		var dataStatus = {
			loaded: 0,
			failed: 0,
			message: ""
		};

		$('document').ready(function(){
			$("button").button();
			showScraperScreen(1);
			$("#cancel").click(function(){
				//ngaRequest.abort();
				for(var i = 0;i<requests.length;i++){
					requests[i].abort();
				}
				requests = [];
			});

			$("#back-ngas").click(function(){
				hideScraperScreen(3);
				showScraperScreen(2);
				resetScraperScreen(3);
				$('#history-info').html(ngaStatus.message);
			});

			
			$("#back-polities").click(function(){
				hideScraperScreen(4);
				showScraperScreen(3);
				resetScraperScreen(4);
				$('#history-info').html(polityStatus.message);
			});
			
			$(".get-ngas").click(function(){
				ngaStatus.loaded = 0;
				ngaStatus.failed = 0;
				ngaStatus.message = "";
				
				var ajs = dacura.scraper.api.getngalist();
				var self=this;
				ajs.beforeSend = function(){
					dacura.toolbox.showModal("<p>Retrieving NGA list from Seshat Wiki</p><div class='indeterminate-progress'></div>");
					$('.indeterminate-progress').progressbar({
						value: false
					});
				};
				ajs.complete = function(){
					dacura.toolbox.removeModal();
				};
				dacura.toolbox.setModalProperties({ 
					"buttons": [
						{
							"text": "Cancel",
							"click": function() {
								jqax.responseText = "Aborted by User";
								jqax.abort();
								$( this ).dialog( "close" );
							}
						}
					], 
				});
				var jqax =	$.ajax(ajs)
					.done(function(response, textStatus, jqXHR) {				
						try {
							x = JSON.parse(response);
							addition = "<div class='ngaList'><table class='nga-list seshat-list'><tr><th>NGA</th><th>URL</th><th><input type='checkbox' class='selectAll'>&nbsp;&nbsp;Select All</span></th></tr>";
							for(var i=0;i<x.length;i++){
								if(include(x[i]) ){
									ngaStatus.loaded++; 
									addition += "<tr><td>" + dacura.scraper.tidyNGAString(x[i]) + "</td><td><a href='" + x[i] + "'>" + x[i] + "</a></td><td><input type='checkbox' class='ngaValid' id='" + x[i] + "'></td></tr>";
								}
							}
							ngaStatus.message = ngaStatus.loaded + " NGAs identified. please select the NGAs for which you wish to export data from the list below";
							addition += "</table></div></div>";
							hideScraperScreen(1);
							showScraperScreen(2);
							$('#history-info').html(ngaStatus.message);
							$("#nga-display").html(addition).show();
							$(".ngaList h3 span").click(function(ev) {
								ev.stopPropagation();
							});
							$(".selectAll").click(function(){
								if ($(this).is(':checked')) {
									$(this).closest("div.ngaList").find(":checkbox").prop('checked',true);
								}else{
									$(this).closest("div.ngaList").find(":checkbox").prop('checked',false);
								}
							});
						}
						catch(e) 
						{
							$('#history-info').html("Error - Could not interpret the server response " + e.message + " - please try again later");
						}
					})
					.fail(function (jqXHR, textStatus){
						$('#history-info').html("<strong>Retrieval of NGA List failed</strong><br>" + jqXHR.responseText);
					}
				);
				
			});

			$("#get-polities").click(function(){
				var aborted = false;
				var polityList = {}; //maps polities to NGAs
				polityStatus.loaded = 0;
				polityStatus.failed = 0;
				polityStatus.message = "";
				if($('input:checked').length==0){
					alert("nothing selected - you must select at least one policy to export!");
				}else{
					ngaCount = $('input.ngaValid:checked').length;
					var nganm = ""; if(ngaCount == 1) { nganm = "NGA"; } else { nganm = "NGAs";}
					var checkedNGAs = $('input.ngaValid:checked').map(function(){return this.id;});
					var ngas = []
					for(var i=0;i<checkedNGAs.length;i++){
						ngas[ngas.length] = checkedNGAs[i];
					}
					var requests = [];
					var NGAerrorCount = 0;
					NGAsObtainedCount = 0;
					dacura.toolbox.setModalProperties({ 
						"width": 400,
						"buttons": [
							{
								"text": "Cancel",
								"click": function() {
									$('#history-info').html("<br><span class='seshat-error'><strong>Loading of polities aborted by user</strong></span><br>");
									for (var i = 0; i < requests.length; i++) {
										requests[i].status = 0;
									    requests[i].abort();
									}
									aborted = true;
									$( this ).dialog( "close" );
								}
							}
						], 
					});
					dacura.toolbox.showModal("<p class='polity-head'>Getting polity lists for " + ngaCount + " " + nganm + "</p><p class='polity-got'></p><p class='polity-next'></p><div class='determinate-progress'></div>");
					$('.determinate-progress').progressbar({
						value: false
					});
					
					divs = "<div class='polity-intro'>Select the polities to export from the list below <span class='select-all-polities'>Select all polities <input type='checkbox' id='everypolity'></span></div><div id='accordion'>";
					for(var i = 0; i < ngas.length; i++){
						var nga = ngas[i];
						var ajs = dacura.scraper.api.getpolities(nga);
						ajs.nga = ajs.data.nga;
						var numComplete = 0;
						requests[requests.length] = $.ajax(ajs)
							.done(function(response, textStatus, jqXHR) {				
								var name = dacura.scraper.tidyNGAString(this.nga);
								var myOrder = ++numComplete;
								try {
									x = JSON.parse(response);
									//x = a["polities"];
									var polities = [];
									//remove extraneous polities
									for(var i=0;i<x.length;i++){
										if(include(x[i]) && x[i] != this.nga && x[i].trim() != ""){
											var pol = decodeURI(x[i]);
											if(polities.indexOf(pol) == -1){
											    polities.push(pol);
											}
										}
									}	
									if(polities.length == 0){
										$('.polity-got').html("<p class='seshat-error'>" + name + " did not contain any polities - ignored (" +  myOrder + "/" + ngaCount + ")</p>");
										$('.determinate-progress').progressbar({
											value: (myOrder / ngaCount) * 100
										});
										polityStatus.message += "<span class='seshat-detail seshat-error'>Empty NGA: " + name + " does not contain any polities" + "</span><br>";
										NGAerrorCount++;
									}
									else {
										$('.polity-got').html("Successfully retrieved polity list for " + name + " (" + myOrder + "/" + ngaCount + ")");
										$('.determinate-progress').progressbar({
											value: (myOrder / ngaCount) * 100
										});
										addition = "<h3>" + name + " (" + polities.length + ") <span>Select all <input type='checkbox' class='selectAll'></span></h3><div class='ngas'>";
										addition += "<table class='polity-list seshat-list' title='" + this.nga +"'><tr><th>Polity</th><th>Period</th><th>URL</th><th></th></tr>";
										for(var i=0;i<polities.length;i++){
											var pdet = dacura.scraper.parsePolityString(polities[i]);
											addition += "<tr><td>" + pdet.polityname + "</td><td>"+pdet.period+"</td><td>";
											if(pdet.url.length > 50) {
												addition += "<a href='" + pdet.url + "' title='" + pdet.url + "'>" + pdet.url.substr(0,50) + "...</a>";
											}
											else {
												addition += "<a href='" + pdet.url + "' title='" + pdet.url + "'>" + pdet.url + "...</a>";
											} 
											addition += "</a></td><td><input class='polityValid' type='checkbox' title='" + polities[i] + "'></td></tr>";
											if(typeof polityList[polities[i]] !== "undefined"){
												polityList[polities[i]].push(this.nga);
											}else{
												polityList[polities[i]] = [];
												polityList[polities[i]].push(this.nga);
											}
										}
										addition += "</table></div>";
										divs += addition;
										NGAsObtainedCount++;
									}
								}
								catch (e) {
									$('.polity-got').html("<p class='seshat-error'>Failed to get polity list for " + name + " " + e.message + " (" + myOrder + "/" + ngaCount + ")</p>");
									NGAerrorCount++;
									$('.determinate-progress').progressbar({
										value: (myOrder / ngaCount) * 100
									});
									polityStatus.message += "<span class='seshat-detail seshat-error'>Parse error - Server response could not be interpreted for " + name  + "</span><br>";
								}
							})
							.fail(function (jqXHR, textStatus){
								if(jqXHR.status != 0){
									var myOrder = ++numComplete;
									$('.polity-got').html("<p class='seshat-error'>Failed to get polity list for " + dacura.scraper.tidyNGAString(this.nga) + " (" +  myOrder + "/" + ngaCount + ")</p>");
									$('.determinate-progress').progressbar({
										value: (myOrder / ngaCount) * 100
									});
									NGAerrorCount++;
									polityStatus.message += "<span class='seshat-detail seshat-error'>Network error (" + jqXHR.status + "). Failed to load " + dacura.scraper.tidyNGAString(this.nga) + "</span><br>";
								}
							}								
					    );
					}
					var checkForCompletion = function(){
						if(aborted){
							return;
						}
						if((NGAsObtainedCount + NGAerrorCount) < ngaCount){
							setTimeout(checkForCompletion, 200);
						}
						else {
							if(NGAsObtainedCount == 0){
								$('#history-info').append("<br><span class='seshat-error'><strong>Failed to load any polities</strong></span><br>" + polityStatus.message);
								dacura.toolbox.removeModal();								
							}
							else {		
								polityStatus.failed = NGAerrorCount;
								polityStatus.loaded = NGAsObtainedCount;
								var nganm = ""; if(NGAsObtainedCount== 1) { nganm = "NGA"; } else { nganm = "NGAs";}
								var fnm = ""; if(NGAerrorCount== 1) { fnm = "failure"; } else { fnm = "failures";}
								polityStatus.message = "Retrieved polity lists for " + NGAsObtainedCount + " " + nganm + " (" + NGAerrorCount + " " + fnm + ") " + (Object.keys(polityList).length) + " polities in total.<br>";
								$('#history-info').html(polityStatus.message);
								dacura.toolbox.removeModal();
								hideScraperScreen(2);
								showScraperScreen(3);
								divs += "</div>";
								$("#polity-display").html(divs).show();
								$(function(){
									$("#accordion").accordion({
										collapsible: true,
										heightStyle: "content"
									});
									$("#accordion h3 span").click(function(ev){
										ev.stopPropagation();
									});
									$(".selectAll").click(function(){
										if ($(this).is(':checked')) {
											$(this).closest("h3").next().find(":checkbox").prop('checked',true);
										}else{
											$(this).closest("h3").next().find(":checkbox").prop('checked',false);
										}
									});
									$("#everypolity").click(function(){
										if ($(this).is(':checked')) {
											$(":checkbox").prop('checked',true);
										}
										else {
											$(":checkbox").prop('checked',false);
										}
									});
								});
							}
						}
					};
					checkForCompletion();
				}
			});

			
			$('#get-data').click(function(){
				dataStatus.loaded = 0;
				dataStatus.failed = 0;
				dataStatus.message = "";
				var jajax = "";
				if($('input:checked').length==0){
					alert("nothing selected - you must select at least one policy to export!");
				}else{
					$('#history-info').html("");
					var polities = [];
					var ngas = [];
					dacura.toolbox.setModalProperties({ 
						"width": 400,
						"minHeight": 400,
						"buttons": [
							{
								"text": "Cancel",
								"click": function() {
									$('#history-info').html("<br><span class='seshat-error'><strong>Loading of data aborted by user</strong></span><br>");
									dacura.scraper.abortdump();
									aborted = true;
									$( this ).dialog( "close" );
								}
							}
						], 
					});
					$('input.polityValid:checked').each(function (i) {
						polityURL = $( this ).attr("title");
						polityNGA = $( this ).parents("table").attr("title");
						if(polities.indexOf(polityURL) == -1){
							polities.push(polityURL);
						}
						if(ngas.indexOf(polityNGA) == -1){
							ngas.push(polityNGA);
						}
					});
					var polityList = {}; //maps polities to NGAs
					$('input.polityValid:checked').each(function (i) {
						polityURL = $( this ).attr("title");
						polityNGA = $( this ).parents("table").attr("title");
						if(typeof polityList[polityNGA] !== "undefined"){
							polityList[polityNGA].push(polityURL);
						}else{
							polityList[polityNGA] = [];
							polityList[polityNGA].push(polityURL);
						}
					});
					var onm = function(msgs){
						for(var i = 0; i < msgs.length; i++){
							try {
								var res = JSON.parse(msgs[i]);
								if(res && res.message_type == "comet_update"){
									if(res.status == "error"){
										dataStatus.failed++;
									}
									else {
										dataStatus.loaded++;
									}
									$('p.data-got').html(res.payload);
									$('.determinate-progress').progressbar({
										value: (dataStatus.loaded / polities.length) * 100
									});
																					
								}
							}
							catch(e) 
							{							
								$('.data-got').html("<p class='seshat-error'>Failed to parse message from server: " + e.message + msgs[i] + "</p>");
							}
						}
					};
					var onc = function(res) {
						try {
							var pl = JSON.parse(res);
							if(pl.status == "error"){
								$('#history-info').html("Failed to export data dump " + pl.payload);
							}
							else {
								hideScraperScreen(3);
								showScraperScreen(4);
								$('#results-display').html(pl.payload).show();
								dataStatus.message = "Exported data dump with data from " + dataStatus.loaded + " polities - " + dataStatus.failed + " polities failed";
								$('#history-info').html(dataStatus.message);
							}
						}
						catch(e){
							$('#history-info').html("Failed to export data dump " + e.message);
						}						
						dacura.toolbox.removeModal();
					};
					dataStatus.message = "Retrieving data for " + polities.length + " polities in " + ngas.length + " NGAs";
					$('#history-info').html(dataStatus.message);
					dacura.toolbox.showModal("<p class='polity-head'>Retrieving variables from Seshat Wiki</p><p class='data-got'></p><div class='determinate-progress'></div>");
					$('.determinate-progress').progressbar({
						value: false
					});
					dacura.scraper.dump({"polities" : JSON.stringify(polityList)}, onc, onm); 
				}
			});
		});
		</script>
