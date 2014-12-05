<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->get_service_file_url('style.css')?>" />
   <div class="tool-header">
   	<span class="tool-title">Seshat Scraper</span>
	<span class="tool-description">This tool extracts structured data from the Seshat wiki.</span>
   </div>
   <div id="scraper-info"></div>
   <div class="scraper-screen" id="scraper-screen-1">
      <div class="tool-buttons">
   		<button class="dacura-button get-ngas" id="get-ngas">1. Choose NGAs to Export &gt;&gt;</button>
   	   </div>
   </div>
   <div class="scraper-screen" id="scraper-screen-2">
   		<div id="nga-display" class="tool-content"></div>
   		<div class="tool-buttons">
	   	<button class="dacura-button back-button get-ngas" id="get-ngas2">Refresh NGA List</button>
	   	<button class="dacura-button forward-button" id="get-polities">2. Choose Polities to Export &gt;&gt;</button>
	   	</div>
   </div>
   <div class="scraper-screen" id="scraper-screen-3">
   		<div id="polity-display" class="tool-content">
   		</div>
   		<div class="tool-buttons">
   			<button class="dacura-button back-button" id="back-ngas">&lt;&lt; Return to NGA List</button>
			<button class="dacura-button forward-button" id="get-data">3. Export Data &gt;&gt;</button>
	   </div>
   </div>
   <div class="scraper-screen" id="scraper-screen-4">
   		<div id="results-display" class="tool-content">
   		</div>
		<div class="tool-buttons">
			<button class="dacura-button back-button" id="back-ngas">&lt;&lt; Change Policy List</button>		
		</div>	
   </div>
   <div style="clear: both; height: '10px'">&nbsp;</div>
	<div id="functionality">
	</div>
	<script>

		function resetScraperScreen(n){
			$('#scraper-screen-'+n +' div.tool-content').html("");
		}

		function hideScraperScreen(n){
			$('#scraper-screen-'+n).hide();	
		}
		
		function showScraperScreen(n){
			$('#scraper-screen-'+n).show();
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

		function showModal(contents){
			dacura.toolbox.showModal(contents);
			//$("#modal #contents").html(contents);
			//$("#modal-dim").show();
			//$("#modal").show();
			//$("body").css("overflow", "none");
		}

		function hideModal(){
			//dacura.toolbox.removeModal();
		}

		function updateModal(contents){
			dacura.toolbox.showModal(contents);
		}

		function tidyNGAString(contents){
			contents = contents.replace('http://seshat.info/', '');
			var pattern = "_";
		    re = new RegExp(pattern, "g");
			contents = contents.replace(re, ' ');
			contents = contents.replace('Select All', '');
			return contents;
		}

		function parsePolityString(str){
			p_details = {
				url: str,
				shorturl: str.substr(0,50),
				polityname: "", 
				period: ""	
			};
			str = str.replace('http://seshat.info/', '');
			re = new RegExp("_", "g");
			str = str.replace(re, ' ');
			re = new RegExp("^([^\(\)]*)([^\)]*)");
			res =  re.exec(str);
			p_details.polityname = res[1]; 
			p_details.period = res[2].substr(1);
			return p_details;
		}
		
		function formatReport(report){
			string = "<h4>Results Summary</h4>";
			string += "<table class='scraper-report'><tr><th>NGA</th><th>Polities</th><th>Variables</th><th>Non-Zero</th><th>Simple</th><th>Complex</th><th>Parse Success</th><th>Parse Failure</th></tr>"
			for(each in report["contents"]){
				nga = report["contents"][each]
				string += "<tr><td>" + nga["nga"] + "</td>";
				string += "<td>" + nga["polityCount"] + "</td>";
				string += "<td>" + nga["totalCount"] + "</td>";
				string += "<td>" + nga["nonZeroCount"] + "</td>";
				string += "<td>" + (nga["nonZeroCount"] - nga["parseCount"]) + "</td>";
				string += "<td>" + nga["parseCount"] + "</td>";
				string += "<td>" + nga["successCount"] + "</td>";
				string += "<td>" + nga["failureCount"] + "</td>";
				string += "</tr>";
			}
			string += "</table>";
			string += "<p><a href='" + report['fileurl'] + "'><b>Download the results</b></a></p>";
			return string;
		}

		function formatErrors(report){
			if(report["errors"].length > 1){
				string = "<h4>Errors encountered in data</h4>";
				string += "<table class='scraper-report'><tr><th>Polity</th><th>Variable</th><th>Error</th></tr>";
				for(var i = 0;i < report["errors"].length; i++){
					var x = report["errors"][i];
					string += "<tr><td><a href='" + x["url"] + "'>" + x["polity"] + "</a></td>";
					string += "<td>" + x["value"][0] + "</td>" + "<td>" + x["errorMessage"] + "</td></tr>"
				}
				string += "</table>";
			}
			else {
				string = "<h4>No errors encountered in data</h4>";
			}
			return string;
		}

		function formatFailures(failures){
			string = "";
			if(failures.length > 0){
				string = "<h4>The following pages could not be scraped:</h4>";
				string += "<table class='scraper-report'><tr><th>Page</th><th>Failure Type</th></tr>";
				for(var i = 0;i < failures.length; i++){
					string += "<tr><td>" + failures[i][0] + "</td><td>" + failures[i][1] + ": " + failures[i][2] + "</td></tr>";
				}
				string += "</table>";
			}
			return string;
		}

		var ngaData = [];
		var requests = [];
		var polityData = [];
		var NGAsObtainedCount = 0;
		var politiesObtainedCount = 0;
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
		var reportStatus = {
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
				$('#scraper-info').html(ngaStatus.message);
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
				$.ajax(ajs)
					.done(function(response, textStatus, jqXHR) {				
						try {
							x = JSON.parse(response);
							addition = "<div class='ngaList'><table class='nga-list seshat-list'><tr><th>NGA</th><th>URL</th><th><input type='checkbox' class='selectAll'>&nbsp;&nbsp;Select All</span></th></tr>";
							for(var i=0;i<x.length;i++){
								if(include(x[i]) ){
									ngaStatus.loaded++; 
									addition += "<tr><td>" + tidyNGAString(x[i]) + "</td><td><a href='" + x[i] + "'>" + x[i] + "</a></td><td><input type='checkbox' class='ngaValid' id='" + x[i] + "'></td></tr>";
								}
							}
							ngaStatus.message = ngaStatus.loaded + " NGAs identified. please select the NGAs for which you wish to export data from the list below";
							addition += "</table></div></div>";
							hideScraperScreen(1);
							showScraperScreen(2);
							$('#scraper-info').html(ngaStatus.message);
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
							$('#scraper-info').html("Error - Could not interpret the server response - please try again later");
						}
					})
					.fail(function (jqXHR, textStatus){
						$('#scraper-info').html("<strong>Retrieval of NGA List failed</strong><br>" + jqXHR.responseText);
					}
				);		
			});

			$("#get-polities").click(function(){
				var polityList = {}; //maps polities to NGAs
				polityStatus.loaded = 0;
				polityStatus.failed = 0;
				polityStatus.message = "";
				
				if($('input:checked').length==0){
					alert("nothing selected - you must select at least one policy to export!");
				}else{
					ngaCount = $('input.ngaValid:checked').length;
					var nganm = ""; if(ngaCount == 1) { nganm = "NGA"; } else { nganm = "NGAs";}
					dacura.toolbox.showModal("<p class='polity-head'>Getting polity lists for " + ngaCount + " " + nganm + "</p><p class='polity-got'></p><p class='polity-next'></p><div class='determinate-progress'></div>");
					$('.determinate-progress').progressbar({
						value: false
					});
					var checkedNGAs = $('input.ngaValid:checked').map(function(){return this.id;});
					var ngas = []
					for(var i=0;i<checkedNGAs.length;i++){
						ngas[ngas.length] = checkedNGAs[i];
					}
					var requests = [];
					var NGAerrorCount = 0;
					NGAsObtainedCount = 0;
					divs = "<div class='polity-intro'>Select the polities to export from the list below <span class='select-all-polities'>Select all polities <input type='checkbox' id='everypolity'></span></div><div id='accordion'>";
					for(var i = 0; i < ngas.length; i++){
						var nga = ngas[i];
						var ajs = dacura.scraper.api.getpolities(nga);
						ajs.nga = ajs.data.nga;
						var numComplete = 0;
						requests[requests.length] = $.ajax(ajs)
							.done(function(response, textStatus, jqXHR) {				
								var myOrder = ++numComplete;
								try {
									a = JSON.parse(response);
									x = a["polities"];
									name = a["payload"][0]["metadata"]["url"];
									var polities = [];
									//remove extraneous polities
									for(var i=0;i<x.length;i++){
										if(include(x[i]) && x[i] != name && x[i].trim() != ""){
											var pol = decodeURI(x[i]);
											if(polities.indexOf(pol) == -1){
											    polities.push(pol);
											}
										}
									}	
									if(polities.length == 0){
										$('.polity-got').html("<p class='seshat-error'>" + tidyNGAString(this.nga) + " did not contain any polities - ignored (" +  myOrder + "/" + ngaCount + ")</p>");
										$('.determinate-progress').progressbar({
											value: (myOrder / ngaCount) * 100
										});
										polityStatus.message += "<span class='seshat-detail seshat-error'>Empty NGA: " + tidyNGAString(this.nga) + " does not contain any polities" + "</span><br>";
										NGAerrorCount++;
									}
									else {
										$('.polity-got').html("Successfully retrieved polity list for " + tidyNGAString(name) + " (" + myOrder + "/" + ngaCount + ")");
										$('.determinate-progress').progressbar({
											value: (myOrder / ngaCount) * 100
										});
										addition = "<h3>" + tidyNGAString(name) + " (" + polities.length + ") <span>Select all <input type='checkbox' class='selectAll'></span></h3><div class='ngas'>";
										addition += "<table class='polity-list seshat-list'><tr><th>Polity</th><th>Period</th><th>URL</th><th></th></tr>";
										for(var i=0;i<polities.length;i++){
											var pdet = parsePolityString(polities[i]);
											addition += "<tr><td>" + pdet.polityname + "</td><td>"+pdet.period+"</td><td>";
											if(pdet.url.length > 50) {
												addition += "<a href='" + pdet.url + "' title='" + pdet.url + "'>" + pdet.url.substr(0,50) + "...</a>";
											
											}
											else {
												addition += "<a href='" + pdet.url + "' title='" + pdet.url + "'>" + pdet.url + "...</a>";
											} 
											addition += "</a></td><td><input class='polityValid' type='checkbox' id='" + polities[i] + "'></td></tr>";
											if(typeof polityList[polities[i]] !== "undefined"){
												polityList[polities[i]].push(name);
											}else{
												polityList[polities[i]] = [];
												polityList[polities[i]].push(name);
											}
										}
										addition += "</table></div>";
										divs += addition;
										NGAsObtainedCount++;
									}
								}
								catch (e) {
									$('.polity-got').html("<p class='seshat-error'>Failed to get polity list for " + tidyNGAString(this.nga) + e + " (" + myOrder + "/" + ngaCount + ")</p>");
									NGAerrorCount++;
									$('.determinate-progress').progressbar({
										value: (myOrder / ngaCount) * 100
									});
									polityStatus.message += "<span class='seshat-detail seshat-error'>Parse error - Server response could not be interpreted for " + tidyNGAString(this.nga)  + "</span><br>";
								}
							})
							.fail(function (jqXHR, textStatus){
								var myOrder = ++numComplete;
								$('.polity-got').html("<p class='seshat-error'>Failed to get polity list for " + tidyNGAString(this.nga) + " (" +  myOrder + "/" + ngaCount + ")</p>");
								$('.determinate-progress').progressbar({
									value: (myOrder / ngaCount) * 100
								});
								NGAerrorCount++;
								polityStatus.message += "<span class='seshat-detail seshat-error'>Network error (" + jqXHR.status + "). Failed to load " + tidyNGAString(this.nga) + "</span><br>";
							}								
					    );
					}
					var checkForCompletion = function(){
						if((NGAsObtainedCount + NGAerrorCount) < ngaCount){
							setTimeout(checkForCompletion, 200);
						}
						else {
							if(NGAsObtainedCount == 0){
								$('#scraper-info').append("<br><span class='seshat-error'><strong>Failed to load any polities</strong></span><br>" + polityStatus.message);
								dacura.toolbox.removeModal();								
							}
							else {		
								polityStatus.failed = NGAerrorCount;
								polityStatus.loaded = NGAsObtainedCount;
								var nganm = ""; if(NGAsObtainedCount== 1) { nganm = "NGA"; } else { nganm = "NGAs";}
								var fnm = ""; if(NGAerrorCount== 1) { fnm = "failure"; } else { fnm = "failures";}
								polityStatus.message = "Retrieved polity lists for " + NGAsObtainedCount + " " + nganm + " (" + NGAerrorCount + " " + fnm + ") " + (Object.keys(polityList).length + 1) + " polities in total.<br>" + polityStatus.message;
								$('#scraper-info').html(polityStatus.message);
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
				if($('input:checked').length==0){
					alert("nothing selected - you must select at least one policy to export!");
				}else{
					
					//first transform checkboxs to a list of polities (edit out duplicates, etc)
					var polities = $('input.polityValid:checked');
					var polityList = {}; //maps polities to NGAs
					for(var i = 0; i < polities.length; i++){
						polityURL = polities[i].id;
						element = $(polities).get(i);
						polityNGAString = $(element).parents("div").prev("h3").text();
						polityNGA = polityNGAString.substr(0, polityNGAString.indexOf("("));
						if(typeof polityList[polityNGA] !== "undefined"){
							polityList[polityNGA].push(name);
						}else{
							polityList[polityNGA] = [];
							polityList[polityNGA].push(polityURL);
						}
					}
					alert(JSON.stringify(polityList));
					exit();
					requests = [];
					failures = [];
					for(var i = 0; i < polities.length; i++){
						polityURL = polities[i].id;
						element = $(polities).get(i);
						polityNGAString = $(element).parents("div").prev("h3").text();
						polityNGA = tidyNGAString(polityNGAString);
						var ajs = dacura.scraper.api.getPolityData(polityNGA, polityURL);
 					    requests[requests.length] = $.ajax(ajs)
							.done(function(response, textStatus, jqXHR) {	
								politiesObtainedCount++;
								updateModal("Getting polity data (" + politiesObtainedCount + "/" + polityCount + ") ...");
								try{
									a = JSON.parse(response);
									polityData[polityData.length] = a;
								}
								catch(e){
									console.log(response);
								}
							})
							.fail(function (jqXHR, textStatus){
									failures[failures.length] = [jqXHR.responseText, jqXHR.status, jqXHR.statusText];
									updateModal("Getting Polity Data failed. Error: " + jqXHR.responseText);
								}
							);
					}
					$.whenAll.apply($, requests).always(function(){
						if(polityData.length > 0){
							failText = "";
							if(failures.length > 0){
								failText = failures.length + " polities could not be scraped.<br>";
							}
							updateModal(failText + "Scraping complete. Parsing...")
							var ajs = dacura.scraper.api.parsePage();
							ajs.data.data = JSON.stringify(polityData);
							$.ajax(ajs)
							.done(function(response, textStatus, jqXHR) {	
								updateModal("Parsing complete! Generating dumps...");
								var ajx = dacura.scraper.api.dump();
								ajx.data.data = response;
								$.ajax(ajx)
								.done(function(r2, textStatus, jqXHR) {	
									updateModal("Polity data generated.");
									try{
										b = JSON.parse(r2);
									}catch(e){
										console.log(r2);
									}
									fails = formatFailures(failures);
									report = formatReport(b);
									errors = formatErrors(b);
									report += fails + "<hr>" + errors;
									$("#info").html(report).show()
									//$("#get-polities").hide()
									$("#polity-display").hide();
									$("#get-data").hide();
									$("#functionality").hide();
									hideModal();
								})
								.fail(function (jqXHR, textStatus){
										//updateModal("Parsing failed. Error: " +  + jqXHR.responseText);
										fails = formatFailures(failures);
										$("#info").html(fails).show()
										$("#get-polities").hide()
										$("#polity-display").hide();
										$("#get-data").hide();
										$("#functionality").hide();
										hideModal();							
									}
								);
							});	
						}
						else {
							updateModal("No polities were retrieved - no data!");							
						}	
					});
				}
			});
		});
		</script>
