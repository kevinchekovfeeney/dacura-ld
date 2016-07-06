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


var onturl = "http://localhost/dacura/rest/seshat/ontology/seshat";
var candurl = "http://localhost/dacura/rest/seshat/candidate/";
var enturl = "";
var graphurl = "http://tcd:3020/data/seshattiny.ttl";
var defurl = "http://tcd:3020/dacura/def";
var seshat_ns = "http://dacura.scss.tcd.ie/ontology/seshat#";

//for now, a bookmarklet which throws in a button
var dacura = {
		grabber: {}
};

dacura.grabber.getParsedTableHTML = function(variable, factoids){
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

dacura.grabber.insertValidationResultPane = function (){
	var pane = "<div id='validator-results'><div id='validator-branding'>"; 
	pane += "<img height='24' src='<?=$service->furl('image', 'dacura-logo-simple.png')?>'></div>";
	pane += "<div id='validator-name'>Codebook Analysis Tool</div>";
	pane += "<div id='validator-stats'></div>";
	pane += "<div id='validator-controls'>";
	//pane += "<button id='validator-ontologize-button'>Connect To Ontology</button>";
	pane += "</div>";
	pane += "<div id='validator-close'><button id='validator-close-button'>Clear</button></div>";
	pane += "<div id='validator-variable'></div>";
	pane += "<div id='ontologize' style='display:none'>" + this.getOntologizeForm() + "</div>";
	pane += "</div>";
	$("body").append(pane);
	$("#validator-results").hide();	
};

var varnames = {};

var pagecontexts = {};
var locators = {};

dacura.grabber.uniqifyFacts = function(){
	for(var i = 0; i< this.pageFacts.length; i++){
		var factoid = this.pageFacts[i];
		if(size(varnames[factoid.varname]) == 1){
			this.pageFacts[i].uniqid = factoid.varname;
		}
		else {
			this.pageFacts[i].uniqid = factoid.varname + "_" + "repeated"; 
			var seq = 1;
			for(var id in varnames[factoid.varname]){
				if(varnames[factoid.varname][id].id == factoid.id){
					this.pageFacts[i].uniqid = factoid.varname + "_" + seq;	
					break;
				}
				seq++;	
			}
		}
	}
}

dacura.grabber.getFactContextLocator = function(factoid){
	//most recent header text....
	//locate the nearest header
	var hloc = 0;
	for(var loc in pagecontexts){
		if(loc < factoid.location && loc > hloc){
			hloc = loc;
		}
	}
	return pagecontexts[hloc];
}

dacura.grabber.calculatePageContexts = function(page){
	var headerids = {};
	var pheaders = $(':header span.mw-headline');
	for(var j = 0; j< pheaders.length; j++){
		var hid = pheaders[j].id;
		if(hid.length){
			var htext = $(pheaders[j]).text();
			if(htext.substring(htext.length - 6) == "[edit]"){
				htext = htext.substring(htext.length - 6); 
			}
			var regexstr = "id\\s*=\\s*['\"]" + escapeRegExp(hid) + "['\"]";
			var re = new RegExp(regexstr, "gmi");
			var hids = 0;
			var hmatch;
			while(heads = re.exec(page)){
				hmatch = heads.index;
				hids++;
			}
			if(hids != 1){
				alert("failed to find unique header id for header " + hid + " " + hids);
			}
			else {
				pagecontexts[hmatch] = {id: hid, text: htext};
			}
		}
	}	
}

dacura.grabber.grabFacts = function(){
	var page = $('#bodyContent').html();
	this.originalpage = page;
	this.calculatePageContexts(page);
	var regex = /(♠([^♠♥]*)♣([^♠♥]*)♥)([^♠]*)/gm;
	var i = 0;
	var facts = [];
	while(matches = regex.exec(page)){
		factParts = {
			"id": (i+1),
			"location": matches.index, 
			"full": matches[1],
			"length" : matches[1].length, 
			"varname": matches[2].trim(),
			"contents": matches[3].trim(),
			"notes": matches[4].trim().substring(4).trim()
		};
		factParts.notes = factParts.notes.split(/<[hH]/)[0].trim();
		if(factParts.notes.substring(factParts.notes.length - 3) == "<b>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 3).trim();
		}
		if(factParts.notes.substring(factParts.notes.length - 3) == "<p>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 3).trim();
		}
		if(factParts.notes.substring(factParts.notes.length - 4) == "</p>"){
			factParts.notes = factParts.notes.substring(0, factParts.notes.length - 4).trim();
		}
		//factParts.notes = factParts.notes.substring(0, factParts.notes.length - 6);
		if(factParts.varname.length == 0){
			factParts.parsed = { "result_code" : "error", "result_message" : "Variable name is missing"}				
		}
		else {
			if(factParts.contents.length == 0){
				factParts.parsed = { "result_code" : "empty", "result_message" : "No value entered yet"}				
			}
			if(typeof varnames[factParts.varname] == "undefined"){
				varnames[factParts.varname] = {};
			}
			varnames[factParts.varname][factParts.id] = factParts;
		}
		var locator = this.getFactContextLocator(factParts);
		if(locator){
			locid = locator.id;
			var flocid = locid + factParts.varname;
			if(typeof locators[flocid] != "undefined"){
				locid += "#" + (++locators[flocid]);
			}
			else {
				locators[flocid] = 1;
			}
			factParts.pattern = locid;
		}
		else {
			factParts.pattern = "";
		}
		facts[i++] = factParts;
	}
	return facts;
}

dacura.grabber.getCodepageToOntologyHTML = function(fid, fact, prop){
	var html = "<div id='" + fid + "-ontinput' class='property-editor'>";
	html += dacura.grabber.getPropertySubject(fid, fact, prop) + " "; 
	html += dacura.grabber.getEntityType(fid, fact, prop) + " "; 
	html += dacura.grabber.getVariableProperty(fid, fact, prop) + " "; 
	html += "Value: " + prop['rdfs:range'];
	html += "<div class='valinput'>" + prop['rdfs:label']['data'];
	html += "<input type='text' value='" + fact.contents + "'>";
	html += "</div>";
	if(typeof this.frame == "object"){
		html += "<div class='cp-frame'>We have an empty frame and we will show the property " + prop.id + "</div>";
	}
	else {
		html += "<div class='cp-frame'></div>";	
	}
	html += dacura.grabber.getCreateEntityButton(fid, fact, prop) + " "; 
	html += "</div>";
	return html;
} 

dacura.grabber.getEntityType = function(fid, fact, prop){
	var html = "<select class='entity-type' id='" + fid + "-entity'>";
	html += "<option value='owl:Nothing'>Select Type</option>";
	for(var i = 0; i<dacura.grabber.entity_classes.length; i++){
		var cname = dacura.grabber.entity_classes[i].split('#')[1];
		if(cname != 'Nothing'){
			var ns = dacura.grabber.entity_classes[i].split('#')[0];
			ns = ns.substring(ns.lastIndexOf("/") + 1);
			var ids = ns + ":" + cname;
			html += "<option value='" + ids + "'>" + ids + "</option>";
		}
	}
	html += "</select>";
	return html;
}

dacura.grabber.getPropertySubject = function(fid, fact, prop){
	var subj = entid;
	var html = "<span class='variable-property variable-property-subject'> Subject <input type='text' id='" + fid + "-subject' value='" + subj + "'></span>";
	return html;
}

dacura.grabber.getCodebookToOntologyHTML = function(fid, fact){
	var html = "<div id='" + fid + "-ontinput' class='property-editor'>";
	fpropmap = fact.varname + "♣";
	if(typeof this.ontologisedProperties[fpropmap] != "undefined"){
		var ontprop = this.ontologisedProperties[fpropmap];
	}
	else {
		fpropmap = fact.varname + "♣" + fact.pattern;
		if(typeof this.ontologisedProperties[fpropmap] != "undefined"){
			var ontprop = this.ontologisedProperties[fpropmap];
		}
		else {
			var ontprop = false;
		}
	}
	html += dacura.grabber.getPropertyHeadline(fid, fact, ontprop);
	html += dacura.grabber.getPropertyTriple(fid, fact, ontprop);
	html += dacura.grabber.getVariableHelp(fid, fact, ontprop) + " "; 
	html += dacura.grabber.getAddPropertyButton(fid, fact, ontprop) + " "; 
	html += "</div>";
	return html;
}

dacura.grabber.getInstances = function(fid, fact){
    var pname = toTitleCase(fact.uniqid);
    pname = pname.charAt(0).toLowerCase() + pname.slice(1);;
	pname = pname.replace(/\W/g, '');
	pname = seshat_ns + pname;
    var xhttp = new XMLHttpRequest();
    var html = "<table style='width:100%'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
    xhttp.onreadystatechange = function(response) {
        if(xhttp.readyState === 4) {
            if(xhttp.status === 200) {
                var jsonResponse = JSON.parse(xhttp.responseText);
                for(var entry in jsonResponse){
                   html += "<tr><td>"+jsonResponse[entry][0]+"</td><td>"+pname+"</td><td>"+jsonResponse[entry][1].data+"</td></tr>"
                }
            }
        }
    }
    xhttp.open("POST",defurl,false);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=show inst of "+pname+" in "+graphurl);
    return html+"</table>";
}

dacura.grabber.newInstance = function(fact){
    var pname = toTitleCase(fact.uniqid);
    pname = pname.charAt(0).toLowerCase() + pname.slice(1);;
	pname = pname.replace(/\W/g, '');
	pname = seshat_ns + pname;
    return "<table border='0'><tr><td>Subject: <input type='text' id='subject' size='35'></td><td>Predicate: <input type='text' id='predicate' value='"+pname+"' size='35'></td><td>Object: <input type='text' id='object' size='35'></td><td><input type='button' value='Create' onclick='javascript:dacura.grabber.createNewInstance(document.getElementById(\"subject\").value,document.getElementById(\"predicate\").value,document.getElementById(\"object\").value)'></td></tr></table>";
}

dacura.grabber.createNewInstance = function(s,p,o){
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function(response){
        if(xhttp.readyState == 4) {
            if(xhttp.status == 200) {
                alert(xhttp.responseText);
            }
        }
    }
    xhttp.open("POST",defurl,false);
    xhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
    xhttp.send("query=add "+s+" "+p+" "+o+" to "+graphurl);
}

dacura.grabber.getPropertyHeadline = function(fid, fact, ontprop){
	var html = "<div class='property-headline'>";
	html += "<span class='variable-property variable-property-varname'>♠ " + fact.varname + " ♣</span> ";
	html += dacura.grabber.getVariableLabel(fid, fact, ontprop) + " "; 
	html += dacura.grabber.getVariablePattern(fid, fact, ontprop) + " "; 
	html += "</div>";
	return html;
}

dacura.grabber.getPropertyTriple = function(id, fact, ontprop){
	var html = "<div class='dacura-property-triple'>";
	html += this.getVariableDomain(id, fact, ontprop);
	html += this.getVariableProperty(id, fact, ontprop); 
    html += this.getVariableRange(id, fact, ontprop);
	html += "</div>";
	return html;
}

dacura.grabber.getVariablePattern = function(id, fact, ontprop){
	var ptn = fact.pattern; 
	if(ontprop && typeof ontprop["dacura:pattern"] != "undefined") {
		var data = ontprop["dacura:pattern"]['data'];
		ptn = data.substring(data.lastIndexOf("♣")+1);
	}
	var html = "<span class='variable-property variable-property-pattern'> Page Context: <input type='text' id='" + id + "-pattern' value='" + ptn + "'></span>";
	return html;
}

dacura.grabber.getCreateEntityButton = function(id, fact, ontprop){
	var html = "<div class='property-buttons'><button class='cancel-action' id='cancel-" + id + "-update'>Cancel</button>";	
	html += "<button class='create-entity' id='create-entity-" + id + "'>Create Entity</button>";	
	html += "</div>";
	return html;	
}

dacura.grabber.getAddPropertyButton = function(id, fact, ontprop){
	var html = "<div class='property-buttons'><button class='cancel-action' id='cancel-" + id + "-update'>Cancel</button>";	
	if(ontprop){
		html += "<button class='add-property' id='add-property-" + id + "'>Update Property</button>";	
	}
	else {
		html += "<button class='add-property' id='add-property-" + id + "'>Add Property</button>";
	}
	html += "</div>";
	return html;
}

dacura.grabber.getVariableLabel = function(fid, fact, ontprop){
	if(ontprop && typeof ontprop['rdfs:label'] != "undefined"){
		var flabel = ontprop['rdfs:label']['data'];
	}
	else {
		var flabel = (typeof fact.varname == "string") ? fact.varname: "";
	}
	var html = "<span class='variable-property-label'>Label <input type='text' id='" + fid + "-label' value='" + flabel + "'></span>";
	return html;
}

dacura.grabber.getVariableHelp = function(fid, fact, ontprop){
	var notes = (ontprop && typeof ontprop['rdfs:comment'] != "undefined") ? ontprop['rdfs:comment']['data'] : fact.notes;
	var html = "<div class='variable-property-help'>Help text<textarea id='" + fid + "-help'>" + notes + "</textarea></div>";
	return html;
}

dacura.grabber.getVariableProperty = function(fid, fact, ontprop){
	if(typeof ontprop.id != "undefined"){
		var pname = ontprop.id;
	}
	else {
		var pname = toTitleCase(fact.uniqid);
		pname = pname.charAt(0).toLowerCase() + pname.slice(1);;
		pname = pname.replace(/\W/g, '');
		pname = onturl.substring(onturl.lastIndexOf("/") + 1) + ":" + pname;
	}
	var html = "<div class='variable-property-predicate'>Predicate <input type='text' class='ontology-input' id='" + fid + "-type' value='" + pname + "'></div>";
	return html;
}

dacura.grabber.getVariableCardinality = function(fid){
	var html = "<span class='variable-property-cardinality'><input type='checkbox' id='" + fid + "-cardinality'><label for='" + fid + "-cardinality'>Multiple Values</label></span>";
	return html;
}

dacura.grabber.getVariableRange = function(fid, fact, ontprop){
	
	var html = "<div class='variable-property-range' id='" + fid + "-variable-property-range'>Range: <select id='" + fid + "-rangeselect'>";
	if(ontprop && ontprop['rdf:type'] == "owl:DatatypeProperty"){
		html += "<option selected value='literal'>Literal</option>"; 
	}
	else {
		html += "<option value='literal'>Literal</option>"; 			
	}
	if(ontprop && ontprop['rdf:type'] == "owl:ObjectProperty"){
		html += "<option selected value='object'>Object</option>"; 	
	}
	else {
		html += "<option value='object'>Object</option>"; 
	}
	html += "<option value='choice'>Choice</option>"; 
	html += "</select> <span class='variable-property-range-input'>";
	html += "</span>" + this.getVariableCardinality(fid) + "</div>";
	return html;
}

dacura.grabber.getVariableDomain = function(fid, fact, ontprop){
	var dom = (ontprop && typeof ontprop["rdfs:domain"] == "string") ? ontprop["rdfs:domain"] : "";
	var html = "<div class='variable-property-domain'>Domain <input type='text' id='" + fid + "-domain' value='" + dom + "'>";
	html += "</div>";
	return html;
}

dacura.grabber.displayFacts = function (){
	var stats = {"error": 0, "warning": 0, "complex": 0, "simple" : 0, "empty": 0};
	error_sequence = [];
	var json = dacura.grabber.pageFacts;
	//this.originalpage = $('#bodyContent').html();//document.body.innerHTML;
	var npage = "";
	var npage_offset = 0;
	for(var i = 0;i < json.length;i++){
		stats[json[i]["parsed"]["result_code"]]++;
		if(json[i].parsed.result_code == "error" || json[i].parsed.result_code == "warning"){
			error_sequence[error_sequence.length] = json[i].id;
		}
		parsed = json[i].parsed;
		var cstr = "seshatFact";
		var imgstr = "";
		if(parsed.result_code == "error"){
			cstr += " seshatError";
			imgstr = "<img class='seshat_fact_img seshat_error' src='<?=$service->get_service_file_url('error.png')?>' alt='error' title='error parsing variable'> ";
		}
		else if(parsed.result_code == "warning"){
			cstr += " seshatWarning";
			imgstr = "<img class='seshat_fact_img seshat_warning' src='<?=$service->get_service_file_url('error.png')?>' alt='error' title='variable warning'> ";
		}
		else if(parsed.result_code == "empty"){
			cstr += " seshatEmpty";
			imgstr = "<img class='seshat_fact_img seshat_empty' src='<?=$service->get_service_file_url('empty.png')?>' alt='error' title='variable empty'> ";
		}
		else {
			cstr += " seshatCorrect";
			imgstr = "<img class='seshat_fact_img seshat_correct' src='<?=$service->get_service_file_url('correct.png')?>' alt='error' title='variable parsed correctly'> ";
		}

		var sd = "<div class='" + cstr + "' id='fact_" + json[i]["id"] + "'>" + imgstr + json[i]["full"] + "</div>";
		//now update the page....
		
		npage += this.originalpage.substring(npage_offset, json[i]["location"]) + sd;
		npage_offset = json[i]["location"] + json[i]["length"];
	}

	$('#bodyContent').html(npage + this.originalpage.substring(npage_offset));
	$('.seshatFact').click(function(){
		var fid = $(this).attr("id").substring(5);
		dacura.grabber.loadFact(fid);
	});
	$('.seshatFact').hover(function(){
		$(this).addClass("seshatFactSelected");
	},function() {
		$( this ).removeClass( "seshatFactSelected" );
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

String.prototype.ucfirst = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

dacura.grabber.loadFact = function(id){
	var fact = 	dacura.grabber.pageFacts[id-1];
	if(fact.varname == "") fact.varname = "~";
	if(fact.contents == "") fact.contents = "~";
	if(fact.result_message == "") fact.result_message = "~";
	var fpropmap = fact.varname + "♣";
	if(typeof this.ontologisedProperties[fpropmap] != "undefined"){
		var prop = this.ontologisedProperties[fpropmap];
	}
	else {
		fpropmap = fact.varname + "♣" + fact.pattern;
		if(typeof this.ontologisedProperties[fpropmap] != "undefined"){
			var prop = this.ontologisedProperties[fpropmap];
		}
		else {
			var prop = false;
		}
	}
	if(this.isCodebook()){
		if(prop){
			var html = "<dl class='parsedVariable ontologyvar'><dt>Variable:</dt><dd class='varname'>"+ fact.varname + "</dd>";  
			html += "<dt>Predicate:</dt><dd class='varname'>" + prop.id + "</dd>";
			html += "<dt>Domain:</dt><dd class='varname'>" + prop['rdfs:domain'] + "</dd>";
			html += "<dt>Range:</dt><dd class='varname'>" + prop['rdfs:range'] + "</dd>";
			html += "<dt></dt><dd><a id='onttoggle' href='javascript:dacura.grabber.toggleOntology(\"" + id + "\")'>Update</a></dd></dl>";
			html += "<div class='ontologyvar'><p><strong>" + prop['rdfs:label']['data'] + "</strong> " + prop['rdfs:comment']['data'] + "</p></div>";
		}	
		else {
			var html = "<dl class='parsedVariable ontologyvar'><dt>Variable:</dt><dd class='varname'>"+ fact.varname + "</dd>";  
			html += "<dt>Not in ontology</dt><dd><a id='onttoggle' href='javascript:dacura.grabber.toggleOntology(\"" + id + "\")'>Add to ontology</a></dd></dl>";
		}
		html += "<div class='vartoontology'> " + this.getCodebookToOntologyHTML(id, fact) + "</div>";
		
	}
	else {
		var html = "<dl class='parsedVariable'><dt>Variable:</dt><dd class='varname'>"+ fact.varname + "</dd><dt>Value:</dt><dd class='varval'>" + fact.contents  
			+ "</dd><dt>Type:</dt><dd class='seshatResult seshat" + fact.parsed.result_code.ucfirst() + "' >" + fact.parsed.result_code.ucfirst() + "</dd>" + 
			"<dt>Message:</dt><dd class='varmsg'>" + fact.parsed.result_message + "</dd><dt>Datapoints:</dt>";
		var dpcount = 0;
		if(typeof fact.parsed.datapoints == "object"){
			for (var k in fact.parsed.datapoints){
				dpcount++;
			}
			html += "<dd>" + dpcount + " <a id='dptoggle' href='javascript:dacura.grabber.toggleDatapoints(\"" + id + "\")'>(show)</a></dd>";
		}
		else {
			html += "<dd>0</dd>";
		}
		if(prop){
			html += "<dt>Ontology</dt><dd><a href='javascript:dacura.grabber.toggleOntology(\"" + id + "\")'>Import</a></dd>";			
		}
        html += "<dt>Instances:</dt><dd><a id='insttoggle' href='javascript:dacura.grabber.toggleInstances(\"" + id + "\")'>(show)</a> <a id='newinsttoggle' href='javascript:dacura.grabber.toggleNewInstance(\"" + id + "\")'>New Instance</a></dd>";
		html += "</dl>";
		if(dpcount > 0){	
			html += "<div class='vardatapoints'>" + this.getParsedTableHTML(fact.uniqid, fact.parsed.datapoints) + "</div>";
		}
        html += "<div class='varinstances'>"+this.getInstances(id, fact)+"</div>";
        html += "<div class='varnewinstance'>"+this.newInstance(fact)+"</div>";
		if(prop){
			html += "<div class='vartoontology'> " + this.getCodepageToOntologyHTML(id, fact, prop) + " " + "</div>";
		}
	}
	if(fact.parsed.result_code == "error" || fact.parsed.result_code == "warning"){
		dacura.grabber.current_error = id;
	}
	else {
		dacura.grabber.current_error = null;
	}
	$('#validator-variable').html(html);
	dacura.grabber.initOntologyForm(id, prop);
	dacura.grabber.displayPageControls();
	$('#validator-variable').slideUp("fast", function() {
		$('#validator-variable').slideDown("slow", function() {
			var faketop = $('#validator-results').height();
			$('html, body').animate({
				scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
			}, 2000);
		});		
		// Animation complete.
	});
}


dacura.grabber.initOntologyForm = function(fid, ontprop) {
	var html = dacura.grabber.drawRangeInput(fid, ontprop);
	$('.variable-property-range-input').html(html);
	$("#"+ fid + "-rangeselect").change(function (){
		dacura.grabber.showRangeTypeInput(this.value);
	});
	if(ontprop && typeof ontprop['rdfs:range'] != "undefined"){
		if(ontprop['rdf:type'] == "owl:ObjectProperty"){
			dacura.grabber.showRangeTypeInput("object");			
		}
		else {
			dacura.grabber.showRangeTypeInput("literal");			
		}
	}
	else {
		dacura.grabber.showRangeTypeInput("literal");
	}
	$('#validator-variable select.entity-type').change(function(){
		if(this.value != "owl:Nothing"){
			dacura.grabber.loadEntityTypeFrame(this.value, fid, ontprop);
		}
	});
	$('button.cancel-action').button().click(function(){
		dacura.grabber.toggleOntology();
	}); 
	$('button.create-entity').button().click(function(){
		forminputs = dacura.grabber.getEntityFormInputs(fid);
		dacura.grabber.addEntityToGraph(forminputs);
	}); 
	$('button.add-property').button().click(function(){
		forminputs = dacura.grabber.getPropertyFormInputs(fid);
		dacura.grabber.addPropertyToOntology(forminputs);
	}); 
};

dacura.grabber.getEntityFormInputs = function(){
	
};


dacura.grabber.addPropertyToOntology = function(forminputs){
	var x = forminputs.property;
	var ldupdate = {
		editmode: "update",
		format: "json",
		contents: {}
	}
	ldupdate.contents[x] = dacura.grabber.getInputsAsLD(forminputs);
	var xhr = {};
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.url = onturl;
	xhr.type = "POST";
	xhr.data = JSON.stringify(ldupdate);
	xhr.dataType = "json";
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var cx = JSON.parse(response);
			jpr(cx);
		}
		catch(e){
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + e.message);
			grabison = false;
		}
	});

	//return xhr;	
	//jpr(ldupdate);
}

dacura.grabber.getInputsAsLD = function(forminputs){
	var ld = {};
	ld["dacura:pattern"] = forminputs.pattern;
	ld["rdfs:domain"] = forminputs.domain;
	ld["rdfs:comment"] = forminputs.help;
	ld["rdfs:label"] = forminputs.label;
	if(forminputs.range.type == "literal"){
		ld["rdf:type"] = "owl:DatatypeProperty";
		ld["rdfs:range"] = "xsd:"+forminputs.range.xsd;
		ld["dacura:units"] = forminputs.range.units;
	}
	else if(forminputs.range.type == "object"){
		ld["rdf:type"] = "owl:ObjectProperty";
		ld["rdfs:range"] = forminputs.range.oclass;
	}
	else if(forminputs.range.type == "choice"){
		ld["rdf:type"] = "owl:ObjectProperty";
		ld["rdfs:range"] = forminputs.range.choice;
	}
	return ld;
}

dacura.grabber.getPropertyFormInputs = function(fid){
	var pfact = this.pageFacts[fid-1];
	var fact = {};
	fact.pattern = pfact.varname + "♣" + $('#' + fid + '-pattern').val();
	fact.domain = $('#' + fid + '-domain').val();
	fact.property = $('#' + fid + '-type').val();
	fact.label = $('#' + fid + '-label').val();
	fact.help = $('#' + fid + '-help').val();
	fact.range = this.getPropertyRangeInputs(fid);
	return fact;
};


dacura.grabber.getPropertyRangeInputs = function(fid){
	var range = {};
	range.type = $('#' + fid + '-rangeselect').val();
	if(range.type == "literal"){
		range.units = $('#' + fid + '-range-units').val(); 
		range.xsd = $('#' + fid + '-xsd-type').val(); 
	}
	else if(range.type == "object"){
		range.oclass = $('#' + fid + '-range-object').val(); 
	}
	else if(range.type == "choice"){
		range.choice = $('#' + fid + '-choice').val();
	}
	return range;
} 

dacura.grabber.showRangeTypeInput = function(type){
	$('span.range-input').hide();
	$('.range-input-' + type ).show();
}

dacura.grabber.drawRangeInput = function(fid, ontprop){
	var range = (ontprop && typeof ontprop['rdfs:range'] != "undefined") ? ontprop['rdfs:range'] : false;
	var xsd = (typeof range == "string" && range.substring(0, 4) == "xsd:") ? range.substring(4) : "string";
	var units = (ontprop && typeof ontprop['dacura:units'] != "undefined") ? ontprop['dacura:units']["data"] : "";
	var html = "<span class='range-input range-input-literal'>";
	html += "XSD Type: " + "<input type='text' value='" + xsd + "' id='" + fid + "-xsd-type'> ";
	html += "Units: " + "<input type='text' value='" + units + "' id='" + fid + "-range-units'>";
	html += "</span>";
	html += "<span class='range-input range-input-object'>";
	var objc = (ontprop && typeof ontprop['rdf:type'] != "undefined" && ontprop['rdf:type'] == "owl:ObjectProperty") ? ontprop['rdfs:range'] : "";
	html += "Class: " + "<input type='text' value='" + objc + "' id='" + fid + "-range-object' value=''> </span>";	
	html += "<span class='range-input range-input-choice'>";
	html += "Choices " + "<input type='text' id='" + fid + "-choice'> ";	
	html += "</span>";
	return html;
}

dacura.grabber.toggleOntology = function(id){
	$('.vartoontology').toggle();
	$('.ontologyvar').toggle();	
	//$('#validator-variable select.entity-type').selectmenu("refresh");
	var faketop = $('#validator-results').height();
	$('html, body').animate({
		scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
	}, 2000);
}


dacura.grabber.toggleDatapoints = function(id){
	if($('#dptoggle').html() == "(show)"){
		$('#dptoggle').html("(hide)"); 
	}
	else {
		$('#dptoggle').html("(show)"); 
	}
	$('.vardatapoints').toggle();
	var faketop = $('#validator-results').height();
	$('html, body').animate({
		scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
	}, 2000);
}

dacura.grabber.toggleInstances = function(id) {
    if($('#insttoggle').html() == "(show)"){
        $('#insttoggle').html("(hide)");
    }
    else {
        $('#insttoggle').html("(show)");
    }
    $('.varinstances').toggle();
	var faketop = $('#validator-results').height();
	$('html, body').animate({
		scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
	}, 2000);
}

dacura.grabber.toggleNewInstance = function(id) {
    $('.varnewinstance').toggle();
	var faketop = $('#validator-results').height();
	$('html, body').animate({
		scrollTop: $("#fact_" + id).offset().top - (faketop + 20)
	}, 2000);
}

dacura.grabber.displayPageStats = function(stats){
	$('#validator-stats').html("<dl><dt class='seshatCorrect'>Variables</dt><dd class='seshatCorrect'>" + (stats.empty + stats.complex + stats.simple) + "</dd>" + 
		"<dt class='seshatEmpty'>Filled Correctly</dt><dd class='seshatEmpty'>" + (stats.complex + stats.simple)  + "</dd>" + 
		"<dt class='seshatError'>Problems</dt><dd class='seshatError'>" + (stats.error + stats.warning) + "</dd></dl>");
}

dacura.grabber.getOntologizeForm = function(){
	var html = "<div class='ontologize-wikipage'><select><option value='codebook'>Codebook Page</option><option value='datapage'>Data Page</option></select> ";
	html += " URL <input class='ontolgize-url'> <button class='ontologize'>Ontologize</button></div>";
	return html;
	//either datasheet / codebook sheet
	//url of ontology / graph
}

dacura.grabber.sendFactsToParser = function(){
	var pfacts = [];
	var fact_ids = [];
	if(dacura.grabber.pageFacts.length == 0){
		dacura.grabber.showErrorMessage("No Seshat facts were found in the page. Seshat facts are encoded as ♠ VAR ♠ VALUE ♥");	
		return;
	}
	for(i in dacura.grabber.pageFacts){
		if(typeof dacura.grabber.pageFacts[i].parsed != "object"){
			pfacts[pfacts.length] = dacura.grabber.pageFacts[i].contents;
			fact_ids[fact_ids.length] = i;			
		}
	}
	if(pfacts.length == 0){
		//whole page parsed already
		dacura.grabber.displayFacts();
		return;
	}
	xhr = {};
	//xhr.xhrFields = {
	 //  withCredentials: true
	//};
		
	xhr.data = { "data" : JSON.stringify(pfacts)};
    //xhr.dataType = "json";
    //xhr.data = JSON.stringify(pfacts);
	xhr.url = "<?=$service->my_url('rest')?>/validate";
	xhr.type = "POST";
	xhr.beforeSend = function(){
		var msg = fact_ids.length + " Variables being analysed";
		dacura.grabber.showBusyMessage(msg);
		dacura.grabber.grabison = true;
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var results = JSON.parse(response);
			for(i in results){
				dacura.grabber.pageFacts[fact_ids[i]].parsed = results[i];
			}
			dacura.grabber.clearBusyMessage();
			dacura.grabber.displayFacts();
			$('button#validator-ontologize-button').button().click(function(){
				$('#ontologize').toggle();
			});
			$('button#validator-close-button').button().click(function(){
				dacura.grabber.clear();
			});
			dacura.grabber.getOntology(onturl);
			dacura.grabber.grabison = false;
		}
		catch(e){
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + e.message);
			dacura.grabber.grabison = false;
		}
	})
	.fail(function (jqXHR, textStatus){
		dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + jqXHR.responseText);
		alert(JSON.stringify(jqXHR));
		dacura.grabber.grabison = false;
	});
};

dacura.grabber.isClass = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:Class" || json['rdf:type'] == "rdfs:Class") return true;
	}
	if(typeof json['rdfs:subClassOf'] != "undefined") return true;
	return false;
}

dacura.grabber.ontologisedProperties = {};
dacura.grabber.classes = {};
dacura.grabber.entity_classes = [];
dacura.grabber.getCandidateData = function(candid){
	xhr = {};
	xhr.url = candurl + candid;	
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.data = {format: "html", options: {plain: 1}};
	var msg = "Attempting to fetch entity data from " + xhr.url;
	xhr.beforeSend = function(){
		dacura.grabber.showBusyMessage(msg);
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			dacura.grabber.candidate = JSON.parse(response);
		}
		catch(e){
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse candidate: " + e.message);
		}
	})
	.fail(function(response){
		//alert("failed to find candidate " + xhr.url);
		xhr = {};
		xhr.xhrFields = {
		    withCredentials: true
		};
		xhr.url = candurl + "entities";
		$.ajax(xhr)
		.done(function(response, textStatus, jqXHR) {
			dacura.grabber.clearBusyMessage();
			try {
				dacura.grabber.entity_classes = JSON.parse(response);
			}
			catch(e){
				dacura.grabber.showParseErrorMessage("Failed to parse entity classes response from server: " + e.message);
			}
		})
		.fail(function(){
			dacura.grabber.clearBusyMessage();
			alert("failed to find any entity classes for " + candurl);
		});
	});
}

dacura.grabber.getPropertyFrameHTML = function(frame, prop, fid){
	if(typeof frame == "object" && typeof prop == "object"){
		var html = "We have empty frame and property object - ready to render";
	}
	else {
		if(typeof frame == "object"){
			var html = "We have a frame but no prop";
		}
		else {
			var html = "We have no frame";	
		}
	}
	return html;
}

dacura.grabber.loadEntityTypeFrame = function(type, fid, prop){
	xhr = {};
	xhr.url = candurl + "frame";
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.type = "POST";
	xhr.data = {"class": type};
	var msg = "Attempting to fetch " + type + "class frame from " + xhr.url;
	xhr.beforeSend = function(){
		dacura.grabber.showBusyMessage(msg);
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			dacura.grabber.clearBusyMessage();
			var res = JSON.parse(response);
			if(res.status == "accept" && typeof res.result != "undefined"){				
				dacura.grabber.frame = (typeof res.result == "object") ? res.result : JSON.parse(res.result);
				var x = '#'+ fid + "-ontinput > div.cp-frame";
				var y = dacura.grabber.getPropertyFrameHTML(dacura.grabber.frame, prop, fid);
				$(x).html(y);
			}
			else{
				alert("failed to read frame for " + type);
			}
		}
		catch(e){
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse candidate: " + e.message);
		}
	})
	.fail(function(response){
		dacura.grabber.clearBusyMessage();
		alert("Failed to retrieve class " + type + " class frame from " + xhr.url);
	});
};

dacura.grabber.getOntology = function(url){
	xhr = {};
	xhr.url = url;
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.data = {format: "json", options: {plain: 1}};
	xhr.beforeSend = function(){
		var msg = "Fetching ontology from " + url;
		dacura.grabber.showBusyMessage(msg);
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			dacura.grabber.ontology = JSON.parse(response);
			for(var id in dacura.grabber.ontology.contents){
				if(typeof dacura.grabber.ontology.contents[id]["dacura:pattern"] != "undefined"){
					dacura.grabber.ontologisedProperties[dacura.grabber.ontology.contents[id]["dacura:pattern"]["data"]] = dacura.grabber.ontology.contents[id];
					dacura.grabber.ontologisedProperties[dacura.grabber.ontology.contents[id]["dacura:pattern"]["data"]]['id'] = id;
				}
			    if(dacura.grabber.isClass(dacura.grabber.ontology.contents[id])){
			    	dacura.grabber.classes[id] = dacura.grabber.ontology.contents[id];
			    }
			}
			dacura.grabber.clearBusyMessage();
			if(dacura.grabber.isCodebook()){
				var html = "<dt class='seshatCorrect'>Ontology</dt><dd class='seshatInfo'>" + onturl.substring(onturl.lastIndexOf("/") + 1) + "</dd>";
				html += "<dt>Properties</dt><dd class='seshatInfo'>" + size(dacura.grabber.ontologisedProperties) + "</dd>";
				html += "<dt>Classes</dt><dd class='seshatInfo'>" + size(dacura.grabber.classes) + "</dd>";
				$('#validator-stats dl').append(html);
			}
			else {
				var pid = window.location.href.split("?")[0];
				pid = pid.split("#")[0];
				entid = pid.substring(pid.lastIndexOf("/") + 1);	
				dacura.grabber.getCandidateData(entid);
			}	
		}
		catch(e){
			dacura.grabber.clearBusyMessage();
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + e.message);
			grabison = false;
		}
	}).fail( function(){
		dacura.grabber.clearBusyMessage();
	});
}

dacura.grabber.updateBusyMessage = function(msg){
	$('#dialog-busy-text').html(msg);
}

dacura.grabber.showBusyMessage = function(msg){
	$('body').append("<div id='grabber-busy'><img class='dialog-busy' src='<?=$service->furl('images/icons', 'ajax-loader.gif')?>'><div id='dialog-busy-text'>"+msg+"</div></div>");
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

dacura.grabber.showErrorMessage= function(msg){
	dacura.grabber.showBusyMessage(msg);
	dacura.grabber.showParseErrorMessage(msg);
}

dacura.grabber.showParseErrorMessage= function(msg){
	$('#grabber-busy').html("<div class='seshatError'>Error: " + msg + "</div>");
};

dacura.grabber.clear = function(){
	dacura.grabber.grabison = false;
	$('#validator-variable').slideUp("fast");
	$('#validator-results').slideUp("slow");
	$('#bodyContent').html(dacura.grabber.originalpage);
	$('#ca-grab').click(dacura.grabber.invoke);
};

dacura.grabber.isCodebook = function(){
	var pstr = window.location.href.split('?')[0];
	pstr = pstr.split('#')[0];
	return pstr.endsWith("Code_book");
}

dacura.grabber.parsePage = function(page, refresh, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data = {};
	}
	if(typeof refresh != "undefined" && refresh == true){
		xhr.data["refresh"] = true;
	}
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.type = "POST";
	xhr.data.url = page;
	xhr.url ="<?=$service->my_url('rest')?>/parsepage";
	xhr.beforeSend = function(){
		var msg = "Wiki code page being analysed";
		dacura.grabber.showBusyMessage(msg);
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var results = JSON.parse(response);
			for(i in results){
				dacura.grabber.pageFacts[fact_ids[i]].parsed = results[i];
			}
			dacura.grabber.clearBusyMessage();
			dacura.grabber.displayFacts();
			$('button#validator-close-button').button().click(function(){
				dacura.grabber.clear();
			});
		}
		catch(e){
			dacura.grabber.showParseErrorMessage("Failed to contact server to parse variables: " + e.message);
			grabison = false;
		}
	});
}

dacura.grabber.grabison = false;
dacura.grabber.error_ids = [];

$(document).ready(function() {
	//if($("#ca-grab").length){
		//do nothing - the grabber has already been added to the page
	//}
	//else
	if ($('#ca-view').length){
		$('#validator-results').remove();
		$('#ca-grab').remove();
		style=document.createElement("link");
		style.setAttribute("rel", "stylesheet");
		style.setAttribute("type", "text/css");
		style.setAttribute("href", "<?=$service->furl('css', 'jquery-ui.css')?>");
		document.body.appendChild(style);
		style=document.createElement("link");
		style.setAttribute("rel", "stylesheet");
		style.setAttribute("type", "text/css");
		style.setAttribute("href", "<?=$service->get_service_file_url('grab.css')?>");
		document.body.appendChild(style);
		dacura.grabber.insertValidationResultPane();
		dacura.grabber.pageFacts = dacura.grabber.grabFacts();
		dacura.grabber.uniqifyFacts();
		$("<li id='ca-grab'><span><a>Validate</a></span></li>").insertBefore("#ca-view");
		dacura.grabber.sendFactsToParser();	
	}
	else {
		//do nothing - can only be invoked on a media wiki page with the view tab
	}
});


function size(obj){
	return Object.keys(obj).length
}

function jpr(obj){
	alert(JSON.stringify(obj));
}

function toTitleCase(str) {
    return str.replace(/\w\S*/g, function(txt){return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}

function escapeRegExp(str) {
	return str.replace(/[\-\[\]\/\{\}\(\)\*\+\?\.\\\^\$\|]/g, "\\$&");
}
/*
 * Loads via following in con: 
 * 
	var script = document.createElement("script");
	script.src = "http://localhost/dacura/rest/scraper/grabscript";
	document.body.appendChild(script);
*/