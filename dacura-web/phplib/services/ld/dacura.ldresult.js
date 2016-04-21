dacura.ld.testResultMsg = "(no changes have been made to the object store as this was a test invocation.)";
dacura.ld.hypoResultMsg = "(this is a hypotethical result - no changes will be made to the graph until the object is published.)";
dacura.ld.parseRVOList = function(jsonlist){
	if(typeof jsonlist != 'object' || jsonlist.length == 0){
		return [];
	}
	var l = [];
	for(var i = 0; i< jsonlist.length; i++){
		l.push(new RVO(jsonlist[i]));
	}
	return l;
}

dacura.ld.getTripleTableHTML = function(trips, tit){
	var html = "";
	if(trips.length > 0){
		isquads = trips[0].length == 4;
		html += "<div class='api-triplestable-title'>" + tit + "</div>";
		html += "<table class='rbtable'>";
		html += "<thead><tr><th>Subject</th><th>Predicate</th><th>Object</th>";
		if(isquads){
			html += "<th>Graph</th>";
		}
		html += "</tr></thead><tbody>";
		for(var i = 0; i < trips.length; i++){
			if(typeof trips[i][2] == "object"){
				trips[i][2] = JSON.stringify(trips[i][2]);
			}
			html += "<tr><td>" + trips[i][0] + "</td><td>" + trips[i][1] + "</td><td>" + trips[i][2] + "</td>";
			if(isquads){
				html += "<td>" + trips[i][3] + "</td>";
			}
			html += "</tr>";				
		}
		html += "</tbody></table>";
	}
	return html;
} 

dacura.ld.getJSONViewHTML = function (inserts, deletes){
	if(!inserts || !deletes){
		var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Value</th></tr></thead><tbody>";
		var def = inserts ? inserts : deletes;
		for(var i in def){
			html += "<tr><td>" + i + "</td><td class='dacura-json-viewer'>";
			html += (typeof def[i] == "object" ? JSON.stringify(def[i], 0, 4) : def[i]);
			html += "</td></tr>";
		}
		html += "</tbody></table>";
	}
	else {
		var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Before</th><th>After</th></tr></thead><tbody>";
		if(typeof inserts == "object"){
			for(var i in inserts){
				html += "<tr><td>" + i + "</td><td class='dacura-json-viewer'>";
				if(typeof deletes == "object" && typeof deletes[i] != "undefined"){
					html += typeof deletes[i] == "object" ? JSON.stringify(deletes[i], 0, 4) : deletes[i];
				}
				else {
					html += "not defined";
				}
				html += "</td><td class='dacura-json-viewer'>" + (typeof inserts[i] == "object" ? JSON.stringify(inserts[i], 0, 4) : inserts[i]);
				html += "</td></tr>";
			}
		}
		if(typeof deletes == "object"){
			for(var i in deletes){
				if(typeof inserts != "object" || typeof inserts[i] == "undefined"){
					html += "<tr><td>" + i + "</td><td class='dacura-json-viewer'>undefined</td><td class='dacura-json-viewer'>";	
					html += typeof deletes[i] == "object" ? JSON.stringify(deletes[i], 0, 4) : deletes[i];
					html += "</td></tr>";
				}
			}
		}
		html += "</tbody></table>";
	}
	return html;
};

dacura.ld.wrapJSON = function(json, mode){
	if(!mode || mode == "view"){
		var html = "<div class='dacura-json-viewer'>" + JSON.stringify(json, null, 4) + "</div>";				
	}
	else {
		var html = "<div class='dacura-json-editor'><textarea>" + JSON.stringify(json, null, 4) + "</textarea></div>";			
	}
	return html;
}

function LDResult(jsondr, pconfig){
	if(typeof jsondr == "undefined") {
		alert("LD result created without any result json initialisation data - not permitted!");
		return;
	}
	this.idprefix = pconfig.resultbox.substring(1);
	this.action = jsondr.action;
	this.status = jsondr.status;
	this.message = jsondr.message;
	this.test = typeof jsondr.test == "undefined" ? false : jsondr.test;
	this.errors = dacura.ld.parseRVOList(jsondr.errors);
	this.warnings = dacura.ld.parseRVOList(jsondr.warnings);
	this.result = false;
	if(typeof jsondr.result == 'object' &&  jsondr.result.type == "LDO"){
		this.result = new LDO(jsondr.result);
	}
	else if(typeof jsondr.result == 'object' &&  jsondr.result.type == "LDOUpdate"){
		this.result = new LDOUpdate();
	}
	this.dqsgraph = typeof jsondr.graph_dqs == "object" ? new LDGraphResult(jsondr.graph_dqs, "triples", pconfig) : false;
	this.ldgraph = typeof jsondr.graph_ld == "object" ? new LDGraphResult(jsondr.graph_ld, "triples", pconfig) : false;
	this.metagraph = typeof jsondr.graph_meta == "object" ? new LDGraphResult(jsondr.graph_meta, "json", pconfig) : false;
	this.updategraph = typeof jsondr.graph_update == "object" ? new LDGraphResult(jsondr.graph_update, "ld", pconfig) : false;
	this.fragment_id = typeof jsondr.fragment_id == 'undefined' ? false : jsondr.fragment_id;
	this.pconfig = pconfig;
}


LDResult.prototype.show = function(rconfig){
	var mainmsg = this.getResultMessage();
	var errhtml = this.getErrorsHTML() + this.getWarningsHTML();
	if(mainmsg && errhtml){
		mainmsg += errhtml;
	}
	else if(errhtml){
		mainmsg = errhtml;
	}
	var extrahtml = this.hasExtraFields() ? this.getExtraHTML() : false;
	dacura.system.writeResultMessage(this.status, this.getResultTitle(), this.pconfig.resultbox, mainmsg, extrahtml, this.pconfig.mopts);
	if(this.hasExtraFields()){
		$(this.pconfig.resultbox + " .rb-options").buttonset();
		var self = this;
		$(this.pconfig.resultbox + " .roption").button().click(function(event){
			$(self.pconfig.resultbox + " .result-extra").hide();
			$(self.pconfig.resultbox + " .result-extra-" + this.id.substring(11)).show();				
		});	
	}
}

LDResult.prototype.getErrorsHTML = function(type){
	var html = "";
	if(this.hasErrors()){
		var errhtml = "";
		for(var i = 0; i < this.errors.length; i++){
			errhtml += this.errors[i].getHTML(type);
		}
		if(errhtml.length > 0){
			//if(type == "table"){
				html = "<div class='api-error-details'>";
				html += "<h4>Errors</h4>"
				html += "<table class='rbtable dqs-error-table'>";
				var thead = "<thead><tr>" + "<th>Error</th><th>Message</th><th>Attributes</th></thead>";
				html += "<tbody>" + errhtml + "</tbody></table></div>";
			//}
		}	
	}
	return html;	
}

LDResult.prototype.getWarningsHTML = function(type){
	var html = "";
	if(this.hasWarnings()){
		var errhtml = "";
		for(var i = 0; i < this.warnings.length; i++){
			errhtml += this.warnings[i].getHTML(type);
		}
		if(errhtml.length > 0){
			html = "<div class='api-error-details'>";
			html += "<h4>Warnings</h4>"
			html += "<table class='rbtable dqs-warning-table'>"; 
			var thead = "<thead><tr>" + "<th>Error</th><th>Message</th><th>Attributes</th></thead>";
			html += "<tbody>" + errhtml + "</tbody></table></div>";
		}	
	}
	return html;	
}

LDResult.prototype.getExtraHTML = function(){
	if(!this.hasExtraFields()){
		return "";
	}
	var extras = this.getExtraFields();
	var headhtml = "<div class='ld-resultbox-options'><span class='rb-options'>";
	var bodyhtml = 	"<div class='ld-resultbox-content'>";
	var j = 0;
	var extras = this.getExtraFields();
	for(var i in extras){
		var sel = (j++ == 0) ? " checked" : "";
		dch = (sel == "" ? " dch" : "");
		headhtml += "<input type='radio' class='resoption roption'" + sel +" id='show_extra_" + i + "' name='result_extra_fields'><label class='resoption' title='" + extras[i].title + "' for='show_extra_" + i + "'>" + extras[i].title + "</label>";
		bodyhtml += "<div class='result-extra " + dch + " result-extra-" + i + "'>" + extras[i].content + "</div>";
	}
	headhtml += "</span></div>";
	bodyhtml += "</div>";
	return headhtml + bodyhtml;
}

LDResult.prototype.getResultHTML = function(){
	var html ="<div class='api-graph-testresults'>";
	if(this.status == "reject"){
		html += "<h2>" + this.result.meta.ldtype.ucfirst() + " " + this.result.id + " " + this.action.substring(0,6) + " rejected" + "</h2>";		
	}
	else {
		html += "<h2>" + this.result.meta.ldtype.ucfirst() + " " + this.result.id + " " + this.action.substring(0,6) + "d" + "</h2>";
	}
	if(this.test){
		html += "<P>" + dacura.ld.testResultMsg + "</P>";
	}
	html += this.result.getHTML() + "</div>";
	return html;
}

LDResult.prototype.hasExtraFields = function(){
	return (this.result || this.ldgraph || this.dqsgraph || this.metagraph || this.updategraph);
}

LDResult.prototype.getExtraFields = function(){
	var subs = {};
	if(this.result){
		subs["result"] = {title: 'Linked Data Object', content: this.getResultHTML()};
	}
	if(this.ldgraph){
		subs["ld"] = {title: 'Linked Data Object Updates', content: this.ldgraph.getHTML()};
	}
	if(this.dqsgraph ){
		subs['dqs'] = {title: 'DQS Triplestore Updates', content: this.dqsgraph.getHTML()};
	}
	if(this.metagraph ){
		subs['meta'] = {title: 'Metadata Updates', content: this.metagraph.getHTML()};
	}
	if(this.updategraph ){
		subs["update"] = {title: 'Update Graph Updates', content: this.updategraph.getHTML()};
	}
	return subs;
}


/**
 * @summary generates the result box title text
 */
LDResult.prototype.getResultTitle = function(rconfig){
	tit = "";//this.action.ucfirst() + " - "; 
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		tit += this.message.title;
	}
	else if(this.message){
		tit += this.message;
	}
	else if(this.status == "reject"){
		tit += " Failed. ";
	}
	else if(this.status == "pending"){
		tit += (this.test) ? " Requires approval. " : " Accepted: awaiting approval. ";
	}
	else if(this.status == "accept"){
		tit += (this.test) ? " Approved. " : " Accepted and published. ";
	}
	return tit;
};

LDResult.prototype.hasWarnings = function(){
	return this.warnings && this.warnings.length > 0;
};

LDResult.prototype.hasErrors = function(){
	return this.errors && this.errors.length > 0;
};

/**
 * @summary gets the text to populate the body of the message box
 */
LDResult.prototype.getResultMessage = function(rconfig){
	var msg = false;
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : false;
	}
	//else if(typeof(this.message) == "string") {
	//	msg = this.message;
	//}
	return msg;
};


function LDGraphResult(jsondr, graphtype, pconfig){
	this.graphtype = graphtype;
	this.tests = typeof jsondr.tests == "undefined" ? false : jsondr.tests;
	this.inserts = typeof jsondr.inserts == "undefined" ? false : jsondr.inserts;
	this.deletes = typeof jsondr.deletes == "undefined" ? false : jsondr.deletes;
	this.action = jsondr.action;
	this.status = jsondr.status;
	this.message = jsondr.message;
	this.test = typeof jsondr.test == "undefined" ? false : jsondr.test;
	this.errors = dacura.ld.parseRVOList(jsondr.errors);
	this.warnings = dacura.ld.parseRVOList(jsondr.warnings);
	this.pconfig = pconfig;
	this.hypotethical = jsondr.hypotethical;
}

LDGraphResult.prototype.getResultTitle = function(){
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		return this.message.title;
	}
	return this.action;
};

LDGraphResult.prototype.getResultMessage = function(){
	var msg = "";
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : false;
	}
	else if(typeof(this.message) == "string") {
		msg = this.message;
	}
	if(!this.isEmpty()){
		if(this.hypotethical){
			msg += "<P>" + dacura.ld.hypoResultMsg + "</P>";
		}
		else if(this.test){
			msg += "<P>" + dacura.ld.testResultMsg + "</P>";
		}
	}
	if(this.tests !== false){
		if(typeof this.tests == "object"){
			if(isEmpty(this.tests)){
				msg += "<p>No tests configured (schema free publishing)</p>";
			}
			else {
				msg += "<p>Tests configured: " + this.tests.join(", ") + "</p>";
			}
		}
		else {
			msg += "<P>" + this.tests.ucfirst() + " tests configured</p>";
		}
	}
	return msg;
}

LDGraphResult.prototype.getErrorsHTML = LDResult.prototype.getErrorsHTML;
LDGraphResult.prototype.getWarningsHTML = LDResult.prototype.getWarningsHTML;
LDGraphResult.prototype.hasWarnings = LDResult.prototype.hasWarnings;
LDGraphResult.prototype.hasErrors = LDResult.prototype.hasErrors;

LDGraphResult.prototype.isEmpty = function(){
	return !(this.inserts || this.deletes);
}

LDGraphResult.prototype.getHTML = function(){
	var msg = this.getResultMessage();
	var title = this.getResultTitle();
	var html ="<div class='api-graph-testresults'>";
	html += "<h2>" + title + "</h2>";
	if(msg){
		html += msg;
	}
	if(this.hasErrors()){
		html += this.getErrorsHTML();
	}
	if(this.hasWarnings()){
		html += this.getWarningsHTML();
	}
	if(!this.isEmpty()){
		if(this.graphtype == 'triples'){
			if(this.inserts && this.inserts.length > 0){
				html += dacura.ld.getTripleTableHTML(this.inserts, "Quads Inserted", true); 
			}
			if(this.deletes && this.deletes.length > 0){
				html += dacura.ld.getTripleTableHTML(this.deletes, "Quads Deleted", true); 
			}
		}
		else {
			html += dacura.ld.getJSONViewHTML(this.inserts, this.deletes);
		}
	}
	html += "</div>";
	return html;
};



function RVO(data){
	this.cls = data.cls;
	this.message = data.message;
	this.info = data.info;
	this.subject = data.subject;
	this.predicate = data.predicate;
	this.object = data.object;
	this.property = data.property;
	this.element = data.element;
	this.label = data.label;
	this.comment = data.comment;
	this.path = data.path;
	this.constraintType = data.constraintType;
	this.cardinality = data.cardinality;
	this.value = data.value;
	this.qualifiedOn = data.qualifiedOn;
	this.parentProperty = data.parentProperty;
	this.parentDomain = data.parentDomain;
	this.domain = data.domain;
	this.range = data.range;
	this.parentRange = data.parentRange;
	this.parentProperty = data.parentProperty;
}

RVO.prototype.getAttributes = function(){
	var atts = {};
	if(this.subject) atts.subject = this.subject;
	if(this.predicate) atts.predicate = this.predicate;
	if(this.object) atts.object = this.object;
	if(this.property) atts.property = this.property;
	if(this.element) atts.element = this.element;
	//if(this.label) atts.label = this.label;
	//if(this.comment) atts.comment = this.comment;
	if(this.path) atts.path = this.path;
	if(this.constraintType) atts.constraintType = this.constraintType;
	if(this.cardinality) atts.cardinality = this.cardinality;
	if(this.value) atts.value = this.value;
	if(this.qualifiedOn) atts.qualifiedOn = this.qualifiedOn;
	if(this.parentProperty) atts.parentProperty = this.parentProperty;
	if(this.parentDomain) atts.parentDomain = this.parentDomain;
	if(this.domain) atts.domain = this.domain;
	if(this.range) atts.range = this.range; 
	if(this.parentRange) atts.parentRange = this.parentRange; 
	if(this.parentProperty) atts.parentProperty = this.parentProperty;
	return atts;

}

RVO.prototype.getHTML = function(type){
	return "<tr><td title='" + this.comment + "'>"+this.label+"</td><td>"+this.message +"</td><td>" + this.info + "</td><td class='rawjson'>" + JSON.stringify(this.getAttributes(), 0, 4) + "</td></tr>";
}	




function LDOUpdate(data){}

function LDOViewer(ldo, pconf){
	this.ldo = ldo;
	this.pconf = pconf;
	this.emode = "view";
	this.viewstyle = "raw";
	this.target = "";
}

LDOViewer.prototype.init = function(vconf){
	if(typeof vconf.emode == "string"){
		this.emode = vconf.emode;
	}
	if(typeof vconf.viewstyle == "string"){
		this.viewstyle = vconf.viewstyle;
	}
	if(typeof vconf.target != "string"){
		alert("LDO Viewer called without a target!");
	}
	else {
		this.target = vconf.target;
		this.prefix = this.target.substring(1);//get rid of the #
	}
	if(typeof vconf.view_formats == "object"){
		this.view_formats = vconf.view_formats;
	}
	else {
		this.view_formats = false;
	}
	if(typeof vconf.view_actions == "object"){
		this.view_actions = vconf.view_actions;
	}
	else {
		this.view_actions = false;
	}
	if(typeof vconf.view_options == "object"){
		this.view_options = vconf.view_options;
	}
	else {
		this.view_options = false;
	}
	this.show_options = true;
}

LDOViewer.prototype.show = function(vconf){
	this.init(vconf);
	$(this.target).html("");
	if(this.show_options){
		$(this.target).append(this.showOptionsBar());
	}
	$(this.target).append(this.ldo.getContentsHTML(this.emode));
	//html += ;
	//if(this.viewstyle == "raw"){
	//	this.showRaw();	
	//}
	//else {
	//	$(this.target).html(html);
	//}
}

LDOViewer.prototype.showOptionsBar = function(){
	var html = "<div class='ld-view-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	if(this.emode == "view"){
		if(this.view_formats){
			html += "<select class='ld-view-formats'>";
			for(var i in this.view_formats){
				html += "<option class='foption ld-bar-format' id='" + this.prefix + "_format_" + i + "'>" + this.view_formats[i] + "</option>";							
			}
			html += "</select>";
		}
		html += "</td>";
		html += "<td class='ld-bar ld-bar-centre>";
		if(this.view_options){
			html += "<span class='ld-view-options'>";
			for(var i in this.view_options){
				html += "<input type='checkbox' class='ld-bar-option' id='" + this.prefix + "_option_" + i + "' "; 
				if(this.view_options[i].value){
					html += "checked";
				}
				html += " /><label for='" + this.prefix + "_option_" + i + "'>" + this.view_options[i].title + "</label>";
			}
		}
		html += "</td>";
		html += "<td class='ld-bar ld-bar-right'>";
		if(this.view_actions){
			html += "<span class='ld-update-actions'>";
			for(var i in this.view_actions){
				html += "<button class='ldo-actions' title='" + this.view_actions[i] + "' id='"+ this.prefix + "-action-" + i + "'>" + this.view_actions[i] + "</button>";	
			}
			html += "</span>";
		}
	}
	html += "</td></tr></table><span class='browsermax editor-max ui-icon ui-icon-arrow-4-diag'></span>";
	html += "<span class='browsermin dch editor-min ui-icon ui-icon-closethick'></span></div>";
	return html;
}


LDOViewer.prototype.showRaw = function(){
	$(this.target).html(this.ldo.getContentsHTML());	
}

function LDO(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
}


LDO.prototype.getHTML = function(mode){
	if(!this.contents && !this.meta){
		if(isEmpty(this.inserts) && isEmpty(this.deletes)){
			html += "<div class='info'>No changes to graph</div>";		
		}
	}
	else {
		html = this.getMetaHTML(mode) + this.getContentsHTML(mode);
	}
	return html;
}

LDO.prototype.getMetaHTML = function(mode){
	return dacura.ld.wrapJSON(this.meta);	
}

LDO.prototype.getContentsHTML = function(mode){
	if(this.format == "json" || this.format== "jsonld"){
		return dacura.ld.wrapJSON(this.contents, mode);
	}
	else if(this.format == "triples" || this.format == "quads"){
		return dacura.ld.getTripleTableHTML(this.contents);
	}
	else if(this.format == "html"){
		return "<div class='dacura-html-viewer'>" + this.contents + "</div>";
	}
	else if(this.format == "svg"){
		return "<object id='svg' type='image/svg+xml'>" + this.contents + "</object>";
	}
	else {
		return "<div class='dacura-export-viewer'>" + this.contents + "</div>";
	}	
};

/*
dacura.ldresult = {
	update_type: "update",
	counts: { "errors" : 0, "warnings": 0, "dqs_errors": 0, "candidate_updates": 0, "report_updates": 0, "meta_updates": 0}	
};

dacura.ldresult.showDecision = function(dcm, jq, cancel, confirm, shortmode){
	dacura.ldresult.counts = { "errors" : 0, "warnings": 0, "dqs_errors": 0, "candidate_updates": 0, "report_updates": 0, "meta_updates": 0}
	var hasdepth = false;
	var hasextra = false;
	$(jq).html($('#ld-resultbox').html());
	$(jq + ' .result-title').html(this.getDecisionTitle(dcm));
	var cls = dacura.ldresult.getResultClass(dcm);
	$(jq + ' .result-icon').addClass("result-" + cls);
	$(jq + ' .result-icon').html(dacura.system.resulticons[cls]);	
	var msg = dacura.ldresult.getResultMessage(dcm, dacura.ldresult.update_type);
	$(jq + ' .ld-resultbox .mbody').html(msg);
	$(jq + ' .ld-resultbox').addClass("dacura-" + cls);
	$(jq + ' .ld-resultbox').show();
	if(typeof confirm != "undefined"){
		$(jq + ' .mbuttons button.confirm-update').button().click(confirm).show();
	}
	else {
		$(jq + ' .mbuttons button.confirm-update').hide();
	}
	if(typeof cancel != "undefined"){
		$(jq + ' .mbuttons button.cancel-update').button().click(cancel).show;
	}
	else {
		$(jq + ' .mbuttons button.cancel-update').hide();
	}
	$(jq + ' .mbuttons').show();
	
	if(dcm.result){
		hasdepth = true;
		hasextra = true;
		if(typeof dcm.result.forward != "undefined"){
			$(jq + ' div.ld-forward').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.forward, false, 4) + "</div>");
			$(jq + ' div.ld-backward').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.backward, false, 4)+ "</div>");
			if(dcm.format == "json" || dcm.format == "jsonld"){
				$(jq + ' div.ld-before').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.original.display, null, 4) + "</div>");
				$(jq + ' div.ld-after').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.changed.display, null, 4) + "</div>");
				$(jq + ' div.ld-change').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.display) + "</div>");
			}
			else if(dcm.format == 'html'){
				$(jq + ' div.ld-before').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.original.display + "</div>");		
				$(jq + ' div.ld-after').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.changed.display + "</div>");		
				$(jq + ' div.ld-change').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.display + "</div>");			
			}
			else {
				$(jq + ' div.ld-before').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.original.display + "</div>");		
				$(jq + ' div.ld-after').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.changed.display + "</div>");		
				$(jq + ' div.ld-change').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.display + "</div>");		
			}
		}
		else {
			if(dcm.format == "json" || dcm.format == "jsonld"){
				$(jq + ' div.ld-change').append("<div class='rb-json-result dacura-json-viewer'>" + JSON.stringify(dcm.result.display) + "</div>");
			}
			else if(dcm.format == 'html'){
				$(jq + ' div.ld-change').append("<div class='rb-html-result dacura-html-viewer'>" + dcm.result.display + "</div>");			
			}
			else {
				$(jq + ' div.ld-change').append("<div class='rb-text-result dacura-text-viewer'>" + dcm.result.display + "</div>");		
			}
		}
	}
	else {
		$(jq + ' .resoption').hide();
	}
	if(typeof(dcm.report_graph_update) != "undefined" && dcm.report_graph_update != null){
	 	for (var name in dcm.report_graph_update.errors) {
	 		if(typeof dcm.report_graph_update.errors[name] != "undefined" && dcm.report_graph_update.errors[name].length > 0){
	 			$(jq + ' .error-details').append(this.getErrorDetailsTable(dcm.report_graph_update.errors[name]));
	 			dacura.system.styleJSONLD();
	 	 	}
	 	}
	 	if(dacura.ldresult.counts.dqs_errors > 0){
		 	hasextra = true;
			$(jq + ' label.dqs').html(dacura.ldresult.counts.dqs_errors + " Quality Violations"); 	
	 	}
	 	else {
			$(jq + ' .dqs').hide(); 		 	
		 }	 	
	}
	else {
		$(jq + ' .dqs').hide(); 	
	} 	
	if(typeof dcm.update_graph_update != "undefined" && dcm.update_graph_update != null){
		var x = this.getUpdateGraphUpdateHTML(dcm);
		if(x != ""){
	 		$(jq + ' div.ld-updates').append(x);
	 		hasextra = true;
		}
 		else {
 			$(jq + ' .updoption').hide(); 	 		
 	 	} 	
	}
 	else {
		$(jq + ' .updoption').hide(); 	
 	}
 	if(typeof dcm.candidate_graph_update != "undefined" && dcm.candidate_graph_update != null){
 		$(jq + ' div.ld-candidate').append(this.getCandidateGraphUpdateHTML(dcm));
 		if(dacura.ldresult.counts.candidate_updates > 0){
			$(jq + ' label.candoption').html(dacura.ldresult.counts.candidate_updates + " Linked Data Updates"); 
			hasextra = true;
 		}
 		else {
 			$(jq + ' .candoption').hide(); 		
 	 	}		
 	}
 	else {
		$(jq + ' .candoption').hide(); 	
 	}
 	if(typeof dcm.report_graph_update != "undefined" && dcm.report_graph_update != null){
 		$(jq + ' div.ld-report').append(this.getReportGraphUpdateHTML(dcm));
 		if(dacura.ldresult.counts.report_updates > 0){
			$(jq + ' label.repoption').html(dacura.ldresult.counts.report_updates + " Triplestore Updates"); 
			hasextra = true;
 		}
 		else {
 			$(jq + ' .repoption').hide(); 	
 	 	}			
	}
 	else {
		$(jq + ' .repoption').hide(); 	
 	}
	$(jq + ' .metaoption').hide(); 	
	$(jq + " .ld-extra").hide();
	if(dacura.ldresult.counts.dqs_errors > 0){
		$(jq + ' #show_dqs').attr("checked", "checked");		
		$(jq + ' .ld-dqs').show(); 
	}
	else if(hasdepth){
		$(jq + ' #show-change').attr("checked", "checked"); 		
		$(jq + ' .ld-change').show(); 
	}
	if(hasextra){
		$(jq + ' .ld-resultbox-extra').show(); 	
	 	$(jq + " .rb-options").buttonset();
		$(jq + " .roption").button().click(function(event){
			$(jq + " .ld-extra").hide();	
			$(jq + " .ld-" + this.id.substring(5)).show();				
		});			
	}
	$(jq).show();	
}


dacura.ldresult.getDecisionTitle = function (dcm){
	if(dcm.decision == "accept"){
		if(dcm.test){
			return dcm.msg_title + " " + dcm.action + " was tested and approved ";
		}
		else {
			return dcm.msg_title + " " + dcm.action + " was accepted and published";
		}	
	}	
	else if(dcm.decision == "pending"){
		if(dcm.test){
			return dcm.msg_title + " " + dcm.action + " will require approval";
		}
		else {
			return dcm.msg_title + " " + dcm.action + " was submitted for approval";
		}	
	}
	else if(dcm.decision == "reject"){
		if(dcm.test){
			return "Test " + dcm.action + " - " + dcm.msg_title;
		}
		else {
			return dcm.action + " - " + dcm.msg_title;
		}
	}
	else {
		return dcm.msg_title + " (? " + dcm.decision + " ?)";
	}
}


dacura.ldresult.getStatusChangeWarningsHTML = function(dcm){
	var html = "";
	if(typeof dcm.candidate_graph_update != "undefined" && dcm.candidate_graph_update != null && typeof dcm.candidate_graph_update.meta != "undefined"){
		html = "<div class='rb-status-change'>";
		for (var key in dcm.candidate_graph_update.meta) {
			html += "<span class='rb-status-key'>" + key + " changed </span>" + 
				"<span class='rb-status-orig'>from " + dcm.candidate_graph_update.meta[key][0] + "</span>" + 
				"<span class='rb-status-changed'>to " + dcm.candidate_graph_update.meta[key][1] + "</span>";
		}	
		html += "</div>";
	}
	return html;
}

dacura.ldresult.getWarningsHTML = function(dcm){
	var html = "";
	if(typeof dcm.warnings != "undefined" && dcm.warnings.length > 0){
		var errhtml = "";
		for(var i = 0; i < dcm.warnings.length; i++){
			dacura.ldresult.counts.warnings++;
			errhtml += "<div class='rbwarning'>Warning: <span class='action'>" + dcm.warnings[i].action +
				"</span><span class='title'>" + dcm.warnings[i].msg_title + "</span><span class='body'>" + 
				dcm.warnings[i].msg_body + "</span></div>";
		}
		if(errhtml.length > 0){
			html = "<div class='api-warning-details'>" + errhtml + "</div>";
		}	
	}
	return html;	
}

dacura.ldresult.getErrorsHTML = function(dcm){
	var html = "";
	if(typeof dcm.errors != "undefined" && dcm.errors.length > 0){
		var errhtml = "";
		for(var i = 0; i < dcm.errors.length; i++){
			dacura.ldresult.counts.errors++;
			errhtml += "<div class='rberror'>Error: <span class='action'>" + dcm.errors[i].action +
				"</span><span class='title'>" + dcm.errors[i].msg_title + "</span><span class='body'>" + 
				dcm.errors[i].msg_body + "</span></div>";
		}
		if(errhtml.length > 0){
			html = "<div class='api-warning-details'>" + errhtml + "</div>";
		}	
	}
	return html;	
}

dacura.ldresult.getErrorDetailsTable = function(errors){
	var html = "<table class='rbtable dqs-error-table'><thead><tr>" + 
		"<th>Error</th><th>Message</th><th>Attributes</th></thead><tbody>";
	html += this.getErrorDetailsHTML(errors);
	html += "</tbody></table>";
	return html;
}

dacura.ldresult.getErrorDetailsHTML = function(errors){
	if(typeof errors != "undefined"){
		var errhtml = "";
		for (var key in errors) {
			dacura.ldresult.counts.dqs_errors++;
			  if (errors.hasOwnProperty(key)) {
					//errhtml += "<tr><td>" + key + "</td><td>" + JSON.stringify(errors[key], 0, 4) + "</td></tr>";
					errhtml += "<tr><td>"+errors[key].error+"</td><td>"+errors[key].message +"</td><td class='rawjson'>";
					delete(errors[key].message);
					delete(errors[key].error);
					errhtml += JSON.stringify(errors[key], 0, 4) + "</td></tr>";
			  }
		}
	}
	return errhtml;
}	

dacura.ldresult.getReportGraphUpdateHTML = function(dcm){
	var rupdates = dcm.report_graph_update;
	var html ="<div class='api-graph-testresults report-graph'>";
	if(rupdates.hypothetical || (rupdates.inserts.length == 0 && rupdates.deletes.length == 0)){
		html += "<div class='info'>No changes to report graph</div>";		
	}
	if((rupdates.inserts.length > 0 || rupdates.deletes.length > 0)){
		dacura.ldresult.counts.report_updates = rupdates.inserts.length + rupdates.deletes.length; 
		var insword = "inserted";
		var delword = "deleted"
		if(dcm.test || dcm.decision != "accept"){
			if(rupdates.hypothetical){
				insphrase = "would be " + insword;
				delphrase = "would be " + delword;
			}
			else {
				insphrase = "will be " + insword;
				delphrase = "will be " + delword;
			}
		}
		else {
			insphrase = insword;
			delphrase = delword;		
		}
		var instext = rupdates.inserts.length + " quad";
		if(rupdates.inserts.length != 1) instext += "s" 
		instext += " " + insphrase;
		var deltext = rupdates.deletes.length + " quad";
		if(rupdates.deletes.length != 1) deltext += "s";
		deltext += " " + delphrase;
		if(rupdates.hypothetical){
			html += "<div class='api-report-hypotheticals'>";	
		}
		else {
			html += "<div class='title'>Report Graph " + instext + " " + deltext + "</div>";
			html += "<div class='api-report-updates'>";
		}
		if(rupdates.inserts.length > 0){
			html += this.getTripleTableHTML(rupdates.inserts, "Quads " + insword, true, "report-insert-triples"); 
		}
		if(rupdates.deletes.length > 0){
			html += this.getTripleTableHTML(rupdates.deletes, "Quads " + delword, true, "report-delete-triples"); 
		}
		return html + "</div>";
	}
}

dacura.ldresult.getUpdateGraphUpdateHTML = function(dcm){
	var html ="<div class='api-graph-testresults update-graph'>";
	//if(typeof dcm.update_graph_update.meta != "undefined"){
	//	html += this.getMetaUpdatesHTML(dcm.update_graph_update.meta);
	//}
	if((typeof dcm.update_graph_update.inserts.forward == "undefined" || dcm.update_graph_update.inserts.forward == "") &&
		(typeof dcm.update_graph_update.inserts.backward == "undefined" || dcm.update_graph_update.inserts.backward == "") && 
		(typeof dcm.update_graph_update.deletes.forward == "undefined" || dcm.update_graph_update.deletes.forward == "") &&
		(typeof dcm.update_graph_update.deletes.backward == "undefined" || dcm.update_graph_update.deletes.backward == "")){
		return "";		
	}
	else {
		html += "<div class='info'>Changes to update graph</div>";		
		html += getJSONUpdateTableHTML(dcm.update_graph_update);
	}	
	return html + "</div>";
};

dacura.ldresult.getCandidateGraphUpdateHTML = function(dcm){
	var cupdates = dcm.candidate_graph_update;
	var html ="<div class='api-graph-testresults candidate-graph'>";
	if(cupdates.hypothetical || (cupdates.inserts.length == 0 && cupdates.deletes.length == 0)){
		html += "<div class='title'>No changes to candidate graph</div>";		
	}
	if(typeof cupdates.meta != "undefined"){
		var mhtml = this.getMetaUpdatesHTML(cupdates.meta);
		if(cupdates.hypothetical){
			html += "<div class='api-candidate-meta api-candidate-hypotheticals'>" + mhtml + "</div>";
		}
		else {
			html += "<div class='api-candidate-meta'>" + mhtml + "</div>";			
		}
	}
	if(!(cupdates.inserts.length == 0 && cupdates.deletes.length == 0)){
		dacura.ldresult.counts.candidate_updates = cupdates.inserts.length + cupdates.deletes.length; 
		var insword = "inserted";
		var delword = "deleted"
		if(dcm.test || dcm.decision != "accept"){
			if(cupdates.hypothetical){
				insphrase = "would be " + insword;
				delphrase = "would be " + delword;
			}
			insphrase = "will be " + insword;
			delphrase = "will be " + delword;
		}
		else {
			insphrase = insword;
			delphrase = delword;		
		}
		var instext = cupdates.inserts.length + " triple";
		if(cupdates.inserts.length != 1) instext += "s" 
		instext += " " + insphrase;
		var deltext = cupdates.deletes.length + " triple";
		if(cupdates.deletes.length != 1) deltext += "s";
		deltext += " " + delphrase;
		if(cupdates.hypothetical){
			html += "<div class='api-candidate-hypotheticals'>";	
		}
		else {
			html += "<div class='title'>Candidate Graph " + instext + " " + deltext + "</div>";
			html += "<div class='api-candidate-updates'>";		
		}
		if(cupdates.inserts.length > 0){
			html += this.getTripleTableHTML(cupdates.inserts, "Triples " + insword, false, "candidate-insert-triples"); 
		}
		if(cupdates.deletes.length > 0){
			html += this.getTripleTableHTML(cupdates.deletes, "Triples " + delword, false, "candidate-delete-triples"); 
		}
		return html + "</div>";
	}
	else {
		return html + "</div>";	
	}
}

dacura.ldresult.getMetaUpdatesHTML = function(meta){
	var thtml = "";
	for (var key in meta) {
		  if (meta.hasOwnProperty(key)) {
			  dacura.ldresult.counts.meta_updates++; 
			  thtml += key + ": "; 
			  if(typeof meta[key] == "object" && meta[key] != null){
				  thtml += meta[key][0] + " " + meta[key][1] + "<br>";
			  }
			  else {
				  thtml += meta[key] + "<br>";					  
			  }
		  }
	}
	if(thtml.length > 0){
		thtml = "<div class='rbdecision info'><h3>State</h3>" + thtml + "</div>";
	}
	return thtml;	
}


dacura.ldresult.getTripleTableHTML = function(trips, tit, isquads, cls){
	var html = "";
	if(trips.length > 0){
		html += "<div class='api-triplestable-title cls'>" + tit + "</div>";
		html += "<table class='rbtable'>";
		html += "<thead><tr><th>Subject</th><th>Predicate</th><th>Object</th>";
		if(isquads){
			html += "<th>Graph</th>";
		}
		html += "</tr></thead><tbody>";
		for(var i = 0; i < trips.length; i++){
			dacura.ldresult.numtriples++;
			if(typeof trips[i][2] == "object"){
				trips[i][2] = JSON.stringify(trips[i][2]);
			}
			html += "<tr><td>" + trips[i][0] + "</td><td>" + trips[i][1] + "</td><td>" + trips[i][2] + "</td>";
			if(isquads){
				html += "<td>" + trips[i][3] + "</td>";
			}
			html += "</tr>";				
		}
		html += "</tbody></table>";
	}
	return html;
} 

function getJSONUpdateTableHTML(cupdates){
	var af = "";
	if(typeof cupdates.inserts.forward != "undefined" && cupdates.inserts.forward != ""){
		af = JSON.stringify(cupdates.inserts.forward);
	}
	var ab = "";
	if(typeof cupdates.inserts.backward != "undefined" && cupdates.inserts.backward != ""){
		ab = JSON.stringify(cupdates.inserts.backward);
	}
	var df = "";
	if(typeof cupdates.deletes.forward != "undefined" && cupdates.deletes.forward != ""){
		df = JSON.stringify(cupdates.deletes.forward);
	}
	var db = "";
	if(typeof cupdates.deletes.backward != "undefined" && cupdates.deletes.backward != ""){
		db = JSON.stringify(cupdates.deletes.backward);
	}
	var html = "";
	if(af != "" || ab != "" || df != "" || db != ""){
		html = "<div class='info'>";
		if(af != "" || ab != ""){
			html += "Added: <td class='json-frag'>" + af + " (Forward Graph) - " + ab + " (Backward Graph)";
		}
		if(df != "" || db != ""){
				html += "Deleted: <td class='json-frag'>" + df + " (Forward Graph)" + db + " (Backward Graph)";
		}
		html += "</div>";
	}
	return html;
}
*/

