<div id="showmain" class="dch">
	<div class="maintitle">Seshat World Sample 30</div>
	<table class="status-stats" id='overall-stats'>
		<thead>
			<tr>
				<th>NGAs</th>
				<th>Polities</th>
				<th>Variables</th>
				<th>Datapoints</th>
				<th>Missing</th>
				<th>Errors</th>
				<th>Warnings</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<div class="maintitle">Natural Geographic Areas</div>
	<table id='nga-list' class='nga-list seshat-list'>
		<thead>
			<tr>
				<th>NGA</th>
				<th>Polities</th>
				<th>Variables</th>
				<th>Datapoints</th>
				<th>Missing</th>
				<th>Errors</th>
				<th>Warnings</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<div id="mainrebuild">
		Last full dataset rebuild: <span class="lastrebuild" id="lastmainrebuild"></span>
		<button class="dacura-button" id="rebuild">Rebuild Full Dataset</button>
	</div>
</div>

<div id="shownga" class="dch">
	<div class="maintitle"><span class="entitytitle" id="ngatitle"></span><span class="entityurl" id="ngaurl"></span></div>
	<table class="status-stats" id='nga-stats'>
		<thead>
			<tr>
				<th>Polities</th>
				<th>Variables</th>
				<th>Datapoints</th>
				<th>Missing</th>
				<th>Errors</th>
				<th>Warnings</th>
			</tr>
		</thead>
		<tbody>
		</tbody>
	</table>
	<div class="nosub" id="nosubnga"></div>
	<div id="subnga">
		<div id="nga-contents" class="maintitle">Polities and other pages in the NGA</div>
		<table id='nga-page' class='polity-list seshat-list'>
		<thead>
			<tr>
				<th>Polity</th>
				<th>Period</th>
				<th>Variables</th>
				<th>Datapoints</th>
				<th>Missing</th>
				<th>Errors</th>
				<th>Warnings</th>
			</tr>
		</thead>
		<tbody></tbody>
		</table>
	</div>
	<div id="mainrebuild">
		Last NGA rebuild: <span class="lastrebuild" id="lastngarebuild"></span>
		<button class="dacura-button" id="rebuildnga">Rebuild NGA Dataset</button>
	</div>
</div>

<div id="showpolity" class="dch">
	<div class="maintitle"><span class="entitytitle" id="politytitle"></span><span class="entityurl" id="polityurl"></span></div>
	<table class="status-stats" id='polity-stats'>
		<thead>
			<tr>
				<th>Variables</th>
				<th>Datapoints</th>
				<th>Missing</th>
				<th>Errors</th>
				<th>Warnings</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
	<div class="nosub" id="nopolity"></div>
	<div id="subpolity">
		<div class="maintitle">Variables</div>
		<table id='polity-page' class='polity-page seshat-list'>
			<thead>
				<tr>
					<th>Section</th>
					<th>Subsection</th> 
					<th>Variable</th>
					<th>Value From</th>
					<th>Value To</th>
					<th>Date From</th>
					<th>Date To</th>
					<th>Fact Type</th>
					<th>Value Note</th>
					<th>Date Note</th>
					<th>Comment</th>
				</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="mainrebuild">
		Last Page rebuild: <span class="lastrebuild" id="lastpagerebuild"></span>
		<button class="dacura-button" id="get-page">Rebuild Page</button>
	</div>
</div>

<script>
var currentStatus = null;
var currentNGA = null;
var currentPage = null;

/**
 * First the functions that interact with the server
*/

/*
 	Loads the system status from the server.
 	refresh -> boolean - whether to force the system to refresh the status from the wiki (nuke cache)
 	complete, the function to call on completion
 */
function loadStatus(refresh, complete){
	var ajs = dacura.scraper.api.getstatus(refresh);
	var self=this;
	ajs.beforeSend = function(){
		dacura.system.showModal("<p>Loading System Status</p><div class='indeterminate-progress'></div>");
		$('.indeterminate-progress').progressbar({
			value: false
		});
	};
	ajs.complete = function(){
		dacura.system.removeModal();
	};
	dacura.system.setModalProperties({ 
		"buttons": [
			{
				"text": "Cancel",
				"click": function() {
					jqax.responseText = "Status Check Aborted by User";
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
				updateStatus(x);			
				complete();
			}
			catch(e){				
				dacura.system.showErrorResult("Retrieval of System Status failed", false, "Error - Could not interpret the server response: " + e.message, '#tool-info');
			}
		})
		.fail(function (jqXHR, textStatus){
			dacura.system.showErrorResult("Retrieval of System Status failed", false, "Error - server response indicated failure " + jqXHR.responseText, '#tool-info');
		}
	);
};

/**
 * rebuilds the datasets from the seshat wiki 
 * nga: the url of the nga to be rebuilt (if nga is not passed, a full system rebuild will take place)  
 */
function rebuild(complete, nga){
	var rebuildStatus = {};
	rebuildStatus.success = 0;
	rebuildStatus.fail = 0;
	rebuildStatus.error = 0;
	dacura.system.setModalProperties({ 
		"width": 400,
		"minHeight": 300,
		"buttons": [{
			"text": "Cancel",
			"click": function() {
				dacura.system.showErrorResult("Rebuilding datasets aborted by user", false, "A full dataset rebuild will be needed to avoid inconsistency", '#tool-info');
				dacura.scraper.abortrebuild();
				aborted = true;
				$( this ).dialog( "close" );
			}
		}], 
	});
	var onm = function(msgs){
		for(var i = 0; i < msgs.length; i++){
			try {
				var res = JSON.parse(msgs[i]);
				var msg = "";
				if(res && res.message_type == "comet_update"){
					if(res.status == "error"){
						rebuildStatus.fail++;
						msg = "<p class='seshat-error'>" + res.payload + "</p>";
					}
					else if(res.status == "progress"){
						$('.determinate-progress').progressbar({
							value: res.payload
						});
					}
					else if(res.status == "phase"){
						$('p.phase').html(res.payload);
					}
					else {
						rebuildStatus.success++;
						$('p.data-got').html(res.payload);
					}												
				}
			}
			catch(e) 
			{							
				$('.data-got').html("<p class='seshat-error'>Failed to parse message from server: " + e.message + msgs[i] + "</p>");
				rebuildStatus.error++;
			}
		}
	};
	var onc = function(res) {
		try {
			var pl = JSON.parse(res);
			if(pl.status == "error"){		
				dacura.system.showErrorResult("Dataset rebuilding failed", false, pl.payload, '#tool-info');
			}
			else {
				updateStatus(pl.payload); 
				complete(nga);
				if(typeof nga != "undefined"){
					msg = "Rebuilt dataset for NGA " + nga + ". ";
				}
				msg += rebuildStatus.success + " OK, " + rebuildStatus.fail + " FAIL, " + rebuildStatus.error + " Error";
				dacura.system.showSuccessResult(msg, "", "", '#tool-info');
			}
		}
		catch(e){
			dacura.system.showErrorResult("Dataset rebuilding failed", false, e.message, '#tool-info');
		}						
		dacura.system.removeModal();
	};
	dacura.system.showModal("<p class='updatestatus-head'>Rebuilding Datasets from Seshat Wiki</p><p class='phase'></p><div class='determinate-progress'></div><p class='data-got'></p>");
	$('.determinate-progress').progressbar({
		value: false
	});
	dacura.scraper.api.updatestatus(nga, onc, onm);
};


function loadPage(page, refresh, draw){
	$('#subpolity').hide();
	var ajs = dacura.scraper.api.parsePage(page, refresh);
	ajs.data.url = page;
	var self=this;
	ajs.beforeSend = function(){
		dacura.system.showModal("<p>Extracting variables from page</p><div class='indeterminate-progress'></div>");
		$('.indeterminate-progress').progressbar({
			value: false
		});
	};
	ajs.complete = function(){
		dacura.system.removeModal();
	};
	dacura.system.setModalProperties({ 
		"buttons": [
			{
				"text": "Cancel",
				"click": function() {
					jqax.responseText = "Dataset Extraction Aborted by User";
					jqax.abort();
					$( this ).dialog( "close" );
				}
			}
		], 
	});			
	var jqax =	$.ajax(ajs)
		.done(function(response, textStatus, jqXHR) {				
			try {
				$('#subpolity').show();
				x = JSON.parse(response);
				currentPage = page;
				draw(x);
				loadStatus(false, function(){}); 
			}
			catch(e) 
			{
				dacura.system.showErrorResult("Page parsing failed", false, e.message, '#tool-info');	
			}
		})
		.fail(function (jqXHR, textStatus){
			dacura.system.showErrorResult("Page retrieval failed", false, jqXHR.responseText, '#tool-info');	
		}
	);
}

var drawNGAList = function(){
	$('#nga-list tbody').html();
	var x = currentStatus["nga_list"][1];
	for(var i=0; i<x.length;i++){
		line = "<tr class='clicknga' id='nga_" + i + "'><td title='" + x[i] + "'>" + dacura.scraper.tidyNGAString(x[i]) + "</td>";
		line += getNGAStatsLine(x[i]);
		$('#nga-list tbody').append(line);
	}
	$('.clicknga').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	$('.clicknga').click( function (event){
		showNGAPage(currentStatus["nga_list"][1][this.id.substr(4)], false);
    }); 
}

var drawPolityList = function(nga){
	updateNGAStats(nga);
	$('#ngatitle').html(dacura.scraper.tidyNGAString(nga));
	$('#ngaurl').html(nga);
	currentNGA = nga;
	$('#nga-page tbody').html("");
	var x = currentStatus["ngas"][nga][1];
	if(x.length == 0){
		$('#subnga').hide();
		dacura.system.showWarningResult("No polity or variable pages listed at " + nga, "", "", '#nosubnga');
	}
	else {
		$('#nosubnga').html("");
		$('#subnga').show();
		for(var i=0; i<x.length;i++){
			var pdet = dacura.scraper.parsePolityString(x[i]);
			var addition = "<tr class='clickpage' id='polity" + i + "'><td title='" + pdet.url + "'>" + pdet.polityname + "</td><td>"+pdet.period+"</td>";
			addition += getPolityStatsLine(x[i]);
			addition += "</tr>";
			$('#nga-page tbody').append(addition);
		}
		$('.clickpage').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.clickpage').click( function (event){	
			showPage(currentStatus["ngas"][nga][1][this.id.substr(6)], false);
	    }); 
	}
}

var drawVariableList = function(x){
	var upd = currentStatus["polities"][currentPage][0];
	var d = new Date(upd*1000);
	$('#lastpagerebuild').html(d.toString());
	updatePolityStats(currentPage);
	$('#polity-page tbody').html();
	if(x.length == 0){
		$('#subpolity').hide();
		dacura.system.showWarningResult("No variables found in " + currentPage, false, "", '#nosubpolity');
	}
	else {
		$('#nosubpolity').html("");
		$('#subpolity').show();
		for(var i = 0; i < x.length; i++){
			var html = "<tr>";
			for(j=2; j<x[i].length; j++) {
				html += "<td>" + x[i][j] + "</td>";
			}
			html += "</tr>";
			$('#polity-page tbody').append(html);
		}
	}
}

function showNGAList(){
	var upd = currentStatus["nga_list"][0];
	var d = new Date(upd*1000);
	$('ul.tool-breadcrumbs').html("");
	$('#lastmainrebuild').html(d.toString());
	$('#shownga').hide();
	$('#tool-info').html("");
	$('#showpolity').hide();
	dacura.system.setToolSubtitle("View the status of the dataset by NGA");
	drawNGAList();
	$('#showmain').show();
}

function showNGAPage(nga, refresh){
	$('#tool-info').html("");
	var nganame = dacura.scraper.tidyNGAString(nga);
	$('ul.tool-breadcrumbs').html("<li class='bclink' id='bchome'><a href='javascript:showNGAList()'>Seshat dataset</a></li><li class='bccontext'><a href='javascript:showNGAPage(\"" + nga + "\")'>" + nganame + "</a></li>");
	if(typeof currentStatus["ngas"] == "undefined" || typeof currentStatus["ngas"][nga] == "undefined"){
		rebuild(drawPolityList, nga);		
	}
	else {
		var upd = currentStatus["ngas"][nga][0];
		var d = new Date(upd*1000);
		$('#lastngarebuild').html(d.toString());
		drawPolityList(nga, refresh);
	}
	$('#showmain').hide();
	$('#showpolity').hide();
	dacura.system.setToolSubtitle("View the Polities and other pages in the NGA");
	$('#shownga').show();
}


function showPage(p, refresh){
	var bchtml = "<li class='bclink' id='bchome'><a href='javascript:showNGAList()'>Seshat dataset</a></li>";
	if(typeof(currentNGA) != "undefined"){
		var nganame = dacura.scraper.tidyNGAString(currentNGA);
		var pdets = dacura.scraper.parsePolityString(p);
		bchtml += "<li class='bccontext'><a href='javascript:showNGAPage(\"" + currentNGA + "\")'>" + nganame + "</a></li>";
		bchtml += "<li class='bccontext'><a href='javascript:showPage(\"" + p + "\")'>" + pdets.polityname + "</a></li>";
		$('#politytitle').html(pdets.polityname);
		$('#polityurl').html(pdets.url);
	}
	$('ul.tool-breadcrumbs').html(bchtml);
	if(typeof currentStatus["polities"][p] == "undefined"){
		//alert("should be user driven?");
		loadPage(p, refresh, drawVariableList);
	}
	else {
		var upd = currentStatus["polities"][p][0];
		var d = new Date(upd*1000);
		$('#lastpagerebuild').html(d.toString());
		updatePolityStats(p);
		loadPage(p, refresh, drawVariableList);		
	}
	$('#showmain').hide();
	$('#shownga').hide();
	$('#tool-info').html("");
	$('#showpolity').show();
	dacura.system.setToolSubtitle("View the extracted variables");
}    
    



function osize(obj) {
    var size = 0, key;
    for (key in obj) {
        if (obj.hasOwnProperty(key)) size++;
    }
    return size;
};

var updatePolityStats = function(p) {
	var html = "";
	var stats = currentStatus['polities'][p][1]["stats"];
	for(var i = 0; i<stats.length; i++){
		html += "<td>" + stats[i] + "</td>";
	}
	$('#polity-stats tbody').html("<tr>" + html + "</tr>");
}

var updateNGAStats = function(nga) {
	var html = "";
	if(typeof currentStatus['ngas'][nga] != "undefined"){
		var pols = currentStatus['ngas'][nga][1].length;
		html = "<td>" + pols + "</td>";
	}
	else {
		html = "<td>?</td>";
	}
	if(pols == 0){
		html += "<td>0</td><td>0</td><td>0</td><td>0</td><td>0</td>";
	}
	else if(typeof(currentStatus['ngastats'][nga]) ==  "undefined"){
		html += "<td>?</td><td>?</td><td>?</td><td>?</td><td>?</td>";
	}
	else {
		var stats = currentStatus['ngastats'][nga];
		for(var i = 0; i<stats.length; i++){
			html += "<td>" + stats[i] + "</td>";
		}
	}
	$('#nga-stats tbody').html("<tr>" + html + "</tr>");
};

var updateStatus = function(status) {
	currentStatus = status;
	var ngas = status["nga_list"][1].length;
	var pols = osize(status["polities"]);
	var html = "<tr><td>" + ngas + "</td><td>" + pols + "</td>";
	if(typeof(status["stats"]) != "undefined"){
		for(var i = 0; i < status["stats"].length; i++){
			html += "<td>" + status["stats"][i] + "</td>";
		}
		$('#overall-stats tbody').html(html + "</tr>");
	}
	else {
		$('#overall-stats tbody').html("<td>?</td><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td>");
	}
};

var getNGAStatsLine = function(nga){
	if(typeof currentStatus["ngas"] == "undefined" || typeof currentStatus["ngas"][nga] == "undefined"){
		return "<td>?</td><td>?</td><td>?</td><td>?</td><td>?</td><td>?</td>";
	}
	var pols = currentStatus["ngas"][nga][1].length;
	var html = "<td>" + pols + "</td>";
	if(pols == 0){
		html += "<td>0</td><td>0</td><td>0</td><td>0</td><td>0</td>";
	}
	else if(typeof currentStatus["ngastats"][nga] != "undefined"){
		var stats = currentStatus["ngastats"][nga];
		for(var i = 0; i < currentStatus["ngastats"][nga].length; i++){
			html += "<td>"  + currentStatus["ngastats"][nga][i] + "</td>";
		}
	}
	else {
		html += "<td>?</td><td>?</td><td>?</td><td>?</td><td>?</td>";
	}
	return html;
}

var getPolityStatsLine = function(p){
	if(typeof currentStatus["polities"][p] == "undefined" || typeof currentStatus["polities"][p][1]["stats"] == "undefined"){
		return "<td>?</td><td>?</td><td>?</td><td>?</td><td>?</td>";
	}
	var stats = currentStatus["polities"][p][1]["stats"];
	var html = "";
	for(var i = 0; i < stats.length; i++){
		html += "<td>"  + stats[i] + "</td>";
	}
	return html;
}

$('document').ready(function(){
	$("#get-page").click(function(){
		loadPage(currentPage, true, drawVariableList);
	});
	$("#rebuild").click(function(){
		rebuild(showNGAList);
	});
	$("#rebuildnga").click(function(){
		rebuild(showNGAPage, currentNGA);
	});
	$("button").button();
	loadStatus(false, showNGAList);	
});

</script>




<?php
