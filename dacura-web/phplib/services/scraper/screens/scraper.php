<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->get_service_file_url('style.css')?>" />

<div id="modal-dim"></div>
		<div id="modal">
			<p id="contents"></p>
			<div>
				<button id="cancel">Cancel</button>
			</div>
		</div>
		<div id="main">
			<h1>Seshat Scraper</h1>
			<p>This tool extracts structured data from the Seshat wiki.</p>
			<button id="reset">Reset</button>
			<button id="get-ngas">Choose NGAs to Export</button>
		</div>
		<div id="functionality">
			<div id="nga-display"></div>
			<button id="get-polities">Choose Polities to Export</button>
			<div id="polity-display"></div>
			<button id="get-data">Export Data</button>
			<div class="clear"></div>
		</div>
		<div id="info"></div>
		<script>

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
			}else if(url.indexOf("Main_Page") > -1){
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
			$("#modal #contents").html(contents);
			$("#modal-dim").show();
			$("#modal").show();
			$("body").css("overflow", "none");
		}

		function hideModal(){
			$("#modal-dim").hide();
			$("#modal").hide();
			$("body").css("overflow", "auto");
		}

		function updateModal(contents){
			$("#modal #contents").html(contents);
		}

		function tidyNGAString(contents){
			contents = contents.replace('http://seshat.info/', '');
			contents = contents.replace('_', ' ');
			contents = contents.replace('Select All', '');
			return contents;
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

		var ngaData = [];
		var requests = [];
		var polityData = [];
		var NGAsObtainedCount = 0;
		var politiesObtainedCount = 0;
		var includeAll = false;

		$('document').ready(function(){
			$("#reset").hide();
			$("button").button();
			$("#cancel").click(function(){
				//ngaRequest.abort();
				for(var i = 0;i<requests.length;i++){
					requests[i].abort();
				}
				requests = [];
			});
			$("#reset").click(function(){
				$("#functionality").hide();
				$("#nga-display").hide();
				$("#get-polities").hide();
				$("#polity-display").hide();
				$("#get-data").hide();
				$("#get-polities").hide()
				$("#get-ngas").show();
			});
			$("#get-ngas").click(function(){
				var ajs = dacura.scraper.api.getngalist();
				var self=this;
				ajs.beforeSend = function(){
					showModal("Getting NGA list...");
					//dacura.toolbox.writeBusyMessage('.dacura-wizard-help', "Checking credentials...");
				};
				ajs.complete = function(){
					hideModal();
				};
				$.ajax(ajs)
					.done(function(response, textStatus, jqXHR) {				
						x = JSON.parse(response);
						addition = "<div class='ngaList'><h3>NGAs<span>Select All<input type='checkbox' class='selectAll'></span></h3><div class='ngas'><table>";
						for(var i=0;i<x.length;i++){
							addition += "<tr><td>" + x[i] + "</td><td><input type='checkbox' class='ngaValid' id='" + x[i] + "'></td></tr>";
						}
						addition += "</table></div></div>";
						hideModal();
						$("#nga-display").html(addition);
						$(".ngaList").accordion({
							collapsible: true,
							heightStyle: "content"
						});
						$(".ngaList h3 span").click(function(ev) {
							ev.stopPropagation();
						});
						$("#functionality").show();
						$("#nga-display").show();
						$("#get-polities").show();
						$("#get-ngas").hide();
						$(".selectAll").click(function(){
							if ($(this).is(':checked')) {
								$(this).closest("div.ngaList").find(":checkbox").prop('checked',true);
							}else{
								$(this).closest("div.ngaList").find(":checkbox").prop('checked',false);
							}
						});
						
					})
					.fail(function (jqXHR, textStatus){
						showModal("Scan failed. Error: " + jqXHR.responseText);
					}
				);		
			});

			$("#get-polities").click(function(){
				polityTestArray = [];
				if($('input:checked').length==0){
					alert("nothing selected");
				}else{
					ngaCount = $('input.ngaValid:checked').length;
					showModal("Getting polity lists (" + NGAsObtainedCount + "/" + ngaCount + ") ...");
					var checkedNGAs = $('input.ngaValid:checked').map(function(){return this.id;});
					var ngas = []
					for(var i=0;i<checkedNGAs.length;i++){
						ngas[ngas.length] = checkedNGAs[i];
					}
					divs = "<button id='selectEvery'>Select all polities</button><div id='accordion'>";
					requests = [];
					for(var i = 0; i < ngas.length; i++){
						var ajs = dacura.scraper.api.getpolities(ngas[i]);
						var self=this;
						requests[requests.length] = $.ajax(ajs)
							.done(function(response, textStatus, jqXHR) {				
								NGAsObtainedCount++;
								updateModal("Getting polity lists (" + NGAsObtainedCount + "/" + ngaCount + ") ...");
								a = JSON.parse(response);
								x = a["polities"];
								name = a["payload"][0]["metadata"]["url"];
								addition = "<h3>" + name + "<span>Select All<input type='checkbox' class='selectAll'></span></h3><div class='ngas'><table>";
								for(var i=0;i<x.length;i++){
									if(include(x[i]) && x[i] != name){
										addition += "<tr><td>" + decodeURI(x[i]) + "</td><td><input class='polityValid' type='checkbox' id='" + x[i] + "'></td></tr>";
										if(polityTestArray[x[i]]){
											polityTestArray[x[i]].push(name);
										}else{
											polityTestArray[x[i]] = [];
											polityTestArray[x[i]].push(name);
										}
									}
								}
								addition += "</table></div>";
								divs += addition;
							})
							.fail(function (jqXHR, textStatus){
								updateModal("Scan failed. Error: " + jqXHR.responseText);
							}								
					    );
					}

					$.when.apply($, requests).done(function(){
						divs += "</div>";
						$("#polity-display").html(divs);
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
							$("#selectEvery").button().click(function(){
								$(":checkbox").prop('checked',true);
							});
						});
						$('#nga-display').hide();
						$("#polity-display").show();
						$("#get-data").show();
						$("#get-polities").hide()
						hideModal();
					});
				}
			});

			$('#get-data').click(function(){
				if($('input:checked').length==0){
					//temp - replace with nicer
					alert("nothing selected");
				}else{
					polityCount = $('input.polityValid:checked').length;
					showModal("Getting polity data (" + politiesObtainedCount + "/" + polityCount + ") ...");
					var polities = $('input.polityValid:checked');
					//requests = [];
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
									updateModal("Getting Polity Data failed. Error: " + jqXHR.responseText);
								}
							);
					}
					if(polityData.length > 0){
						$.when.apply($, requests).done(function(){
							updateModal("Scraping complete. Parsing...")
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
									report = formatReport(b);
									errors = formatErrors(b);
									report += "<hr>" + errors;
									$("#info").html(report).show()
									$("#get-polities").hide()
									$("#polity-display").hide();
									$("#get-data").hide();
									$("#functionality").hide();
									hideModal();
								})
								.fail(function (jqXHR, textStatus){
										updateModal("Parsing failed. Error: " +  + jqXHR.responseText);							
									}
								);
							});	
						});
					}
					else {
						updateModal("No polities were retrieved - no data!");							
					}			
				}
			});
		});
		</script>