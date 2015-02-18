// grab and display code for the seshat wiki scraper
// part of dacura
// Copyright (C) 2014 Dacura Team
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.



//for now, a bookmarklet which throws in a button
var dacura = {
		grabber: {}
};

dacura.grabber.getParsedTableHTML = function(variable, factoids){
	//alert(factoids.length);
	var html = "<table class='variable_datapoints'><tr><th>Row</th><th>Name</th><th>Value (from)</th><th>Value (to)</th>";
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

dacura.grabber.insertResultPane = function (){
	var pane = "<div id='validator-results'><div id='validator-branding'>"; 
	pane += "<img height='24' src='<?=$service->url('image', 'dacura-logo-simple.png')?>'></div>";
	pane += "<div id='validator-name'>Seshat Validation Tool</div>";
	pane += "<div id='validator-stats'></div>";
	pane += "<div id='validator-controls'></div>"
	pane += "<div id='validator-close'><button id='validator-close-button'>Clear</button></div>"
	pane += "<div id='validator-variable'></div>"
	pane += "</div>";
	$("body").append(pane);
	$("#validator-results").hide();	
};

//https://developer.mozilla.org/en-US/docs/Using_XPath
dacura.grabber.getXPathForElement = function(el, xml){
	var xpath = '';
	var pos, tempitem2;
	while(el !== xml.documentElement){
		pos = 0;
		tempitem2 = el;
		while(tempitem2) {
			if (tempitem2.nodeType === 1 && tempitem2.nodeName === el.nodeName){ // If it is ELEMENT_NODE of the same name
				pos += 1;
			}
			tempitem2 = tempitem2.previousSibling;
		}
		xpath = el.nodeName + "[" + pos + ']' + '/' +xpath;
		el = el.parentNode;
	}
	xpath = '//' + xml.documentElement.nodeName + '/' + xpath;
	xpath = xpath.replace(/\/$/, '');
	return xpath;
};



dacura.grabber.makeContent = function (contents){
	var x = '<span class="pop">' + contents + '</span>';
	return x;
};


dacura.grabber.grab = function(page){
	//this function grabs all the facts on the page
	factCollection = page.evaluate('//*[text()[contains(., "♠")]]', page, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null)
	facts = []
	for(var i = 0;i < factCollection.snapshotLength;i++){
		node = factCollection.snapshotItem(i);
		xpath = this.getXPathForElement(node, page);
		factParts = {"id": (i+1), "location": xpath};
		text = node.innerHTML;
		if(text.indexOf("♣") < 0 || text.indexOf("♥") < 0 ){
			factParts.parsed = { "result_code" : "error", "result_message" : "incorrectly formatted variable, use ♠ VAR ♣ VAL ♥"}
			factParts.contents = "";
			factParts.varname = "";
		}
		else {
			factParts.contents = text.substring(text.indexOf("♣")+1, text.indexOf("♥")-1).trim();
			factParts.varname = text.substring(text.indexOf("♠")+1, text.indexOf("♣")-1).trim();
			if(factParts.contents.length == 0){
				factParts.parsed = { "result_code" : "empty", "result_message" : "No value entered yet"}				
			}
		}
		facts[facts.length] = factParts;
	}
	return facts;
};

dacura.grabber.displayFacts = function (){
	var stats = {"error": 0, "warning": 0, "complex": 0, "simple" : 0, "empty": 0};
	error_sequence = [];
	var json = dacura.grabber.pageFacts;
	for(var i = 0;i < json.length;i++){
		stats[json[i]["parsed"]["result_code"]]++;
		if(json[i].parsed.result_code == "error" || json[i].parsed.result_code == "warning"){
			error_sequence[error_sequence.length] = json[i].id;
		}
		xpath = json[i]["location"];
		node = document.evaluate(xpath, document, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null).snapshotItem(0);
		dacura.grabber.decorateFact(node, json[i]["parsed"], json[i]["id"]);
	}
	$('.seshatFact').click(function(){
		var fid = $(this).attr("id").substring(5);
		//alert(fid);
		dacura.grabber.loadFact(fid);
	});
	//write into the results pane..
	dacura.grabber.displayPageStats(stats);
	dacura.grabber.error_ids = error_sequence;
	dacura.grabber.displayPageControls();
	$('#validator-variable').hide();
	$('#validator-results').slideDown("slow");
};


dacura.grabber.displayPageControls = function(){
	$("button.validation-errors").remove();
	if(dacura.grabber.error_ids.length == 0){
		//do nothing
	}
	else if(dacura.grabber.error_ids.length == 1){
		$('#validator-controls').prepend("<button class='validation-errors' id='load_fact_" +dacura.grabber.error_ids[0]+"'>View Error</button>");
	}
	else {
		if(typeof dacura.grabber.current_error == "undefined" || dacura.grabber.current_error == null ){
			$('#validator-controls').prepend("<button class='validation-errors' id='load_fact_" +dacura.grabber.error_ids[0]+"'>Next Error</button>");
		}
		else {
			for(i=0; i<dacura.grabber.error_ids.length; i++){
				if(dacura.grabber.error_ids[i] == dacura.grabber.current_error){
					if(i == (dacura.grabber.error_ids.length - 1)){
						$('#validator-controls').prepend("<button class='validation-errors' id='load_fact_" +dacura.grabber.error_ids[i-1]+"'>Previous Error</button>");
					}
					else if(i == 0){
						$('#validator-controls').prepend("<button class='validation-errors' id='load_fact_" +dacura.grabber.error_ids[i+1]+"'>Next Error</button>");						
					}
					else{
						$('#validator-controls').prepend("<button class='validation-errors' id='load_fact_" +dacura.grabber.error_ids[i+1]+"'>Next Error</button>");						
						$('#validator-controls').prepend("<button class='validation-errors' id='load_fact_" +dacura.grabber.error_ids[i-1]+"'>Previous Error</button>");						
					}
				}
			}
		}
	}
	$('.validation-errors').button().click(function(){
		var jqid = $(this).attr("id").substring(10);
		dacura.grabber.loadFact(jqid);
	});

}

dacura.grabber.loadFact = function(id){
	var fact = 	dacura.grabber.pageFacts[id-1];
	if(fact.varname == "") fact.varname = "~";
	if(fact.contents == "") fact.contents = "~";
	if(fact.result_message == "") fact.result_message = "~";
	var html = "<dl><dt>Variable:</dt><dd>"+ fact.varname + "</dd><dt>Value:</dt><dd>" + fact.contents  
			+ "</dd><dt>Result:</dt><dd>" + fact.parsed.result_code + "</dd>" + 
			"<dt>Message:</dt><dd>" + fact.parsed.result_message + "</dd><dt>Datapoints:</dt>";
	var dpcount = 0;
	if(typeof fact.parsed.datapoints == "object"){
		for (var k in fact.parsed.datapoints){
			dpcount++;
		}
		html += "<dd>" + dpcount + " <a id='dptoggle' href='javascript:dacura.grabber.toggleDatapoints(\"" + id + "\")'>(show)</a></dd></dl>";
		html += this.getParsedTableHTML(fact.varname, fact.parsed.datapoints);
	}
	else {
		html += "<dd>0</dd>";
	}
	if(fact.parsed.result_code == "error" || fact.parsed.result_code == "warning"){
		dacura.grabber.current_error = id;
	}
	else {
		dacura.grabber.current_error = null;
	}
	dacura.grabber.displayPageControls();
	$('#validator-variable').slideUp("fast", function() {
		$('#validator-variable').html(html).slideDown("slow", function() {
			var faketop = $('#validator-results').height();
			$('html, body').animate({
				scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
			}, 2000);
		});		
		// Animation complete.
	});

}

dacura.grabber.toggleDatapoints = function(id){
	if($('#dptoggle').html() == "(show)"){
		$('#dptoggle').html("(hide)"); 
	}
	else {
		$('#dptoggle').html("(show)"); 
	}
	$('.variable_datapoints').toggle();
	var faketop = $('#validator-results').height();
	$('html, body').animate({
		scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
	}, 2000);
}

dacura.grabber.displayPageStats = function(stats){
	$('#validator-stats').html("<dl><dt class='seshatCorrect'>Correct</dt><dd class='seshatCorrect'>" + (stats.complex + stats.simple) + "</dd>" + 
		"<dt class='seshatEmpty'>Empty</dt><dd class='seshatEmpty'>" + (stats.empty) + "</dd>" + 
		"<dt class='seshatError'>Problems</dt><dd class='seshatError'>" + (stats.error + stats.warning) + "</dd></dl>");
}

dacura.grabber.decorateFact = function(node, parsed, id){
	node.classList.add("seshatFact");
    $(node).attr("id", "fact_" + id);
	if(parsed.result_code == "error" || parsed.result_code == "warning"){
		node.classList.add("seshatError");
		$(node).attr("title", parsed.result_message);
		$(node).prepend("<img class='seshat_fact_img seshat_error' src='<?=$service->get_service_file_url('error.png')?>' alt='error' title='error parsing variable'>");
	}
	else if(parsed.result_code == "empty"){
		node.classList.add("seshatEmpty");
		$(node).prepend("<img class='seshat_fact_img seshat_empty' src='<?=$service->get_service_file_url('empty.png')?>' alt='empty' title='empty variable' class='empty'>");
	}
	else if(parsed.result_code == "complex" || parsed.result_code == "simple"){
		node.classList.add("seshatCorrect");
		$(node).prepend("<img class='seshat_fact_img seshat_correct' src='<?=$service->get_service_file_url('correct.png')?>' alt='correct' title='variable parsed correctly'>");
	}
	else {
		node.classList.add("seshatUnknown");
		$(node).prepend("<img class='seshat_fact_img seshat_unknown' src='<?=$service->get_service_file_url('unknown.png')?>' alt='unknown' title='variable parser returned unknonwn value'>");
	}
}


dacura.grabber.parsePageFacts = function(){
	if(pageParsed){
		dacura.grabber.displayFacts();
		return;
	}
	var pfacts = [];
	var fact_ids = [];
	for(i in dacura.grabber.pageFacts){
		if(typeof dacura.grabber.pageFacts[i].parsed != "object"){
			pfacts[pfacts.length] = dacura.grabber.pageFacts[i].contents;
			fact_ids[fact_ids.length] = i;			
		}
	}
	xhr = {};
	xhr.data = { "data" : JSON.stringify(pfacts)};
	xhr.url = "<?=$service->my_url('rest')?>/validate";
	xhr.type = "POST";
	xhr.beforeSend = function(){
		var msg = fact_ids.length + " Variables being analysed";
		dacura.grabber.showBusyMessage(msg);
	};
	xhr.complete = function(){
		grabison = false;
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var results = JSON.parse(response);
			for(i in results){
				dacura.grabber.pageFacts[fact_ids[i]].parsed = results[i];
			}
			dacura.grabber.displayFacts();
			pageParsed = true;
			dacura.grabber.clearBusyMessage();
		}
		catch(e){
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + jqXHR.responseText);
		grabison = false;
	});
};

dacura.grabber.showBusyMessage = function(msg){
	$('body').append("<div id='grabber-busy'><img class='dialog-busy' src='<?=$service->url('image', 'ajax-loader.gif')?>'> "+msg+"</div>");
	$('#grabber-busy').dialog({
		 modal: true,
		 title: "Analysing Page",
		 buttons: {
			 cancel: function() {
				 $( this ).dialog( "close" );
				 dacura.grabber.clear();
			 }
		 }
	});
};
dacura.grabber.clearBusyMessage = function(){
	$('#grabber-busy').remove();
};

dacura.grabber.showParseErrorMessage= function(msg){
	$('#grabber-busy').html("<div class='seshatError'>Error: " + msg + "</div>");
};

dacura.grabber.clear = function(){
	grabison = false;
	$('#validator-variable').slideUp("fast");
	$('#validator-results').slideUp("slow");
	$('.seshat_fact_img').remove();
	$('.seshatFact').unbind('click');
	$('.seshatFact').removeClass("seshatFact seshatError seshatEmpty seshatCorrect");
};




var grabison = false;
var pageParsed = false;
dacura.grabber.error_ids = [];
if($("#ca-grab").length){
	//do nothing
}
else if(!$("#ca-view").length){
	alert("You can only invoke this validator on a Seshat media wiki page!");
}
else{
	dacura.grabber.pageFacts = dacura.grabber.grab(document);
	style=document.createElement("link");
	style.setAttribute("rel", "stylesheet");
	style.setAttribute("type", "text/css");
	style.setAttribute("href", "<?=$service->url('css', 'jquery-ui.css')?>");
	document.head.appendChild(style);
	style=document.createElement("link");
	style.setAttribute("rel", "stylesheet");
	style.setAttribute("type", "text/css");
	style.setAttribute("href", "<?=$service->get_service_file_url('grab.css')?>");
	document.head.appendChild(style);
	dacura.grabber.insertResultPane();
	$("<li id='ca-grab'><span><a>Validate</a></span></li>").insertBefore("#ca-view");
	$('#ca-grab').click(function(){
		if(grabison){
			return;
		}
		grabison = true;
		$('button#validator-close-button').button().click(function(){
			dacura.grabber.clear();
		});
		dacura.grabber.parsePageFacts();
	});
};


