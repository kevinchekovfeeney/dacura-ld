dacura.ld.testResultMsg = "(no changes have been made to the object store as this was a test invocation.)";
dacura.ld.hypoResultMsg = "(this is a hypotethical result - no changes will be made to the graph until the object is published.)";
dacura.ld.parseRVOList = function(jsonlist){
	if(typeof jsonlist != 'object' || jsonlist.length == 0){
		return [];
	}
	var l = [];
	for(var i = 0; i< jsonlist.length; i++){
		if(typeof jsonlist[i] == "object"){
			l.push(new RVO(jsonlist[i]));
		}
	}
	return l;
}

dacura.ld.getTripleTableHTML = function(trips, tit){
	var html = "";
	if(trips.length > 0){
		isquads = trips[0].length == 4;
		if(typeof tit == "string" && tit.length){
			html += "<div class='api-triplestable-title'>" + tit + "</div>";
		}
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
		var html = "<div class='dacura-json-editor'><textarea class='dacura-json-editor'>" + JSON.stringify(json, null, 4) + "</textarea></div>";			
	}
	return html;
}

dacura.ld.isJSONFormat = function(format){
	if(format == "json" || format == "jsonld" || format == "quads" || "format" == "triples"){
		return true;
	}
	return false;
}

dacura.ld.getOntologyViewHTML = function(ont, onttit, onturl, ontv){
	var html = "<span class='ontlabel'";
	if(onttit) html +=" title='" + onttit+ "'";
	html += ">";
	if(onturl){	
		html += "<a href='" + onturl + "'>" + ont;
		if(typeof ontv != "undefined"){
			html += ontv == 0 ? " (latest)" : " (v" + ontv + ")";	
		}
		html += "</a>";
	}
	else {
		html += ont;
		if(typeof ontv != "undefined"){
			html += ontv == 0 ? " (latest)" : " (v" + ontv + ")";	
		}
	}
	html += "</span>";
	return html;
};

dacura.ld.getOntologySelectHTML = function(ont, onttit, ontv, ontlv){
	if(typeof ontv != "undefined"){
		var html = "<span class='ontlabel ontlabelrem' title='" + onttit + "' id='imported_ontology_" + ont + "'>";
		html += ont + "<span class='ont-version-selector'>";
		html += " <select class='imported_ontology_version' id='imported_ontology_version_" + ont + "'><option value='0'";
		if(ontv == 0) html += " selected";
		html += ">latest version</option>";
		if(typeof ontlv != "undefined"){
			for(var k = ontlv; k > 0; k--){
				html += "<option value='" + k + "'";
				if(k == ontv){
					html += " selected";
				}
				html += ">version " + k + "</option>";
			}
		}
		html += "</select></span>";	
		html += "<span class='remove-ont' id='remove_ontology_" + ont + "'>" + dacura.system.getIcon('error') + "</span>";
	}
	else {
		var html = "<span class='ontlabel ontlabeladd' title='Click to add ontology " + onttit + "' id='add_ontology_" + ont + "'>";
		html += ont;
		html += " <span class='add-ont'>" + dacura.system.getIcon('add') + "</span>";
	}
	html += "</span>";
	return html;
};

dacura.ld.getDQSHTML = function(i, obj, type){
	rvo = new RVO(obj);
	var xtit = "";
	if(type == 'add'){
		xtit = "Click to add the test to the configuration</br> ";
	}
	var html = "<span title='" + xtit + rvo.getLabelTitle(type) + "' class='dqstile dqstile-" + type + " " + rvo.getLabelCls(type) + "'";
	if(type == "add"){
		html += " id='add_dqs_" + i + "'";
	}
	html += ">" + rvo.getLabel(type);
	if(type == 'remove'){
		var rtit = "Click to remove the test from the configuration";

		html += " <span class='remove-dqs' title='" + rtit + "' id='remove_dqs_" + i + "'>" + dacura.system.getIcon('error') + "</span>";
	}
	else if(type == "add"){
		html += " <span class='add-dqs'>" + dacura.system.getIcon('add') + "</span>";	
	}
	html += "</span> ";
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
		this.result = new LDOUpdate(jsondr.result);
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
	if(!isEmpty(this.result)){
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
	this.imports = typeof jsondr.imports == "undefined" ? false : jsondr.imports;
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



LDGraphResult.prototype.getDQSConfigPage = function(dqs, current){
	var html = "<div class='dqsconfig'><div class='dqs-all-config-element'>";
	html += "<input type='radio' id='dqs-radio-all' name='dqsall' value='all' ";
	if(current == "all"){
		html += " checked";
	}
	html += "><label for='dqs-radio-all'>All Tests</label>";
	html += "<input type='radio' id='dqs-radio-notall' name='dqsall' value='notall'";
	if(typeof current == 'object' && current.length > 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-notall'>Choose Tests</label>"
	html += "<input type='radio' id='dqs-radio-none' name='dqsall' value='none'";
	if(current.length == 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-none'>No Tests</label>";
	html += "</div>";
	var includes = [];
	var available = [];
	if(current == "all"){
		for(var i in dqs){
			includes.push(dacura.ld.getDQSHTML(i, dqs[i], "implicit"));
		}
	}
	else if(current.length == 0){
		for(var i in dqs){
			available.push(dacura.ld.getDQSHTML(i, dqs[i], "add"));
		}
	}
	else {
		for(var i in dqs){
			if(current.indexOf(i) == -1){
				available.push(dacura.ld.getDQSHTML(i, dqs[i], "add"));				
			}
			else {
				includes.push(dacura.ld.getDQSHTML(i, dqs[i], "remove"));				
			}
		}	
	}
	html += "<div class='dqs-includes'>" + includes.join(" ") + "</div>";
	html += "<div class='dqs-available'>" + available.join(" ") + "</div>";
	return html;
}


LDGraphResult.prototype.getImportsSummary = function(simports){
	var html = "";
	for(var i in this.imports){
		var url = dacura.system.install_url;
		url += (this.imports[i].collection == "all") ? "" : this.imports[i].collection;
		url += "/ontology/" + this.imports[i].id;
		html += dacura.ld.getOntologyViewHTML(i, url, null, this.imports[i].version);
	}
	return html;
};

LDGraphResult.prototype.getResultHeadlineHTML = function(){
	var html = "<span class='dqsresulticon'>";
	if(this.status == "accept"){
		html += dacura.system.getIcon("accept");
		html += "</span> <span class='dqsresulttext'>Passed</span>";
	}
	else {
		html += dacura.system.getIcon("reject");	
		html += "</span> <span class='dqsresulttext'>Failed</span>";
	}
	return html;
}
LDGraphResult.prototype.getResultSummaryHTML = function(){
	var html = "<div class='dqsresult'>";
	html += this.getResultHeadlineHTML();
	if(this.hasErrors()){
		html += "<span class='dqserrors'>";
		html += dacura.system.getIcon("error") + this.errors.length + " problem";
		if(this.warnings.length != 1) html += "s";
		html += "</span>";
	}
	if(this.hasWarnings()){
		html += "<span class='dqswarnings'>";
		html += dacura.system.getIcon("warning") + this.warnings.length + " warning";
		if(this.warnings.length != 1) html += "s";
		html += "</span>";
	}
	html += "</div>";
	return html;
}
LDGraphResult.prototype.getErrorsHTML = LDResult.prototype.getErrorsHTML;
LDGraphResult.prototype.getWarningsHTML = LDResult.prototype.getWarningsHTML;
LDGraphResult.prototype.hasWarnings = LDResult.prototype.hasWarnings;
LDGraphResult.prototype.hasErrors = LDResult.prototype.hasErrors;

LDGraphResult.prototype.isEmpty = function(){
	return !(this.inserts || this.deletes);
}

LDGraphResult.prototype.getErrorsSummary = function(){
	return summariseRVOList(this.errors)
}
LDGraphResult.prototype.getWarningsSummary = function(){
	return summariseRVOList(this.warnings)
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
	if(typeof data != "object"){
		alert("not object");
		return;
	}
	this.best_practice = data.best_practice;
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

RVO.prototype.getLabel = function(mode){
	return this.label;
}

RVO.prototype.getLabelCls = function(mode){
	if(this.best_practice){
		return "dqs-bp";
	}
	return "dqs-rule";
}

RVO.prototype.getLabelTitle = function(mode){
	return this.label + " " + this.comment;
}

RVO.prototype.getHTML = function(type){
	return "<tr><td title='" + this.comment + "'>"+this.label+"</td><td>"+this.message +"</td><td>" + this.info + "</td><td class='rawjson'>" + JSON.stringify(this.getAttributes(), 0, 4) + "</td></tr>";
}

function summariseRVOList(rvolist){
	if(rvolist.length == 1) return this.label;
	var entries = [];
	var bytype = {};
	for(var i = 0; i < rvolist.length; i++){
		if(typeof bytype[rvolist[i].cls] == "undefined"){
			bytype[rvolist[i].cls] = [];			
		}
		bytype[rvolist[i].cls].push(rvolist[i]);
	}
	for(var j in bytype){
		if(bytype[j].length == 1){
			entries.push("1 " + bytype[j][0].label); 
		}
		else {
			entries.push(bytype[j].length + " " + bytype[j][0].label + "s"); 	
		}
	}
	return entries.join(", ");
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

function LDOViewer(ldo, pconf, vconf){
	this.ldo = ldo;
	this.pconf = pconf;
	this.emode = "view";
	this.viewstyle = "raw";
	this.target = "";
	if(typeof voncf == "object"){
		this.init(vconf);
	}
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
	if(typeof vconf.edit_formats == "object"){
		this.edit_formats = vconf.edit_formats;
	}
	else {
		this.edit_formats = false;
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
	if(typeof vconf.view_graph_options == "object"){
		this.view_graph_options = vconf.view_graph_options;
	}
	else {
		this.view_graph_options = false;
	}
	if(typeof vconf.editmode_options == "object"){
		this.editmode_options = vconf.editmode_options;
	}
	else {
		this.editmode_options = false;
	}
	if(typeof vconf.result_options == "object"){
		this.result_options = vconf.result_options;
	}
	else {
		this.result_options = false;
	}
	this.show_options = true;
}

LDOViewer.prototype.show = function(vconf){
	if(typeof vconf == "object"){
		this.init(vconf);
	}
	$(this.target).html("");
	var self = this;
	if(this.show_options){
		$(this.target).append(this.showOptionsBar());
		$('button.ld-control').button().click(function(){
			var act = this.id.substring(this.id.lastIndexOf("-")+1);
			self.handleViewAction(act);
		});
		if(this.emode == "view"){
			$('input.ld-control').button().click(function(){
				var opt = this.id.substring(this.id.lastIndexOf("-")+1);
				var val = $('#' + this.id).attr('checked');
				self.handleViewOptionUpdate(opt, !val)
			});
			//$('span.ld-view-options').buttonset();
			$('select.ld-control').selectmenu({change:function(){
				var format = $('#'+this.id).val();
				self.handleViewFormatUpdate(format);}
			});
		}
		else {
			$('button.api-control').button();
			$('.view-graph-options').buttonset();
			$('select.api-control').selectmenu();
		}
	}
	$(this.target).append(this.ldo.getContentsHTML(this.emode));
	if(this.ldo.format == "html" && this.ldo.ldtype() == "candidate" && typeof this.ldo.contents == "object"){
		this.initFrameView();
	}
	if(this.emode == "edit"){
		$(this.target).append(this.getUpdateButtonsHTML());	
		$('.subscreen-button').button().click(function(){
			var act = this.id.substring(this.id.lastIndexOf("-")+1);
			if(act == "cancelupdate"){
				self.handleViewAction("cancel")
			}
			else { 
				var updated = self.ldo.getUpdatedContents(self.target);
				if(dacura.ld.isJSONFormat(self.ldo.format)){
					try {
						updated = JSON.parse(updated);
					}
					catch(e){
						alert(e.message);
						return;
					}
				}
				var test = (act == "testupdate") ? 1 : 0;
				var j = $(self.target + " .ld-edit-modes").val();
				var em = j ? j : "replace";
				j = $(self.target + " .ld-result-modes").val();
				var rem = j ? j : 0;
				var opts = {"show_result": rem};
				if(rem == 2){
					opts.show_changed = 1;
					opts.show_original = 1;
					opts.show_delta = 1;
				}
				$(self.target + ' .editbar-options input:checkbox').each(function(){
					var act = "show_" + this.id.substring(this.id.lastIndexOf("-")+1)+ "_triples";
					if($(this).is(":checked")){
						opts[act] = "1";
					}	
				});
				var upd = {
					'ldtype': self.ldo.ldtype(), 
					"test": test, 
					"contents": updated, 
					"editmode": em,
					"options" : opts,
					"format": self.ldo.format
				};
				self.update(upd);	
				//assemble our options for update...
			}
		});
	}
}

LDOViewer.prototype.initFrameView = function(){
	var pconf = this.pconf;
	obusy = pconf.busybox;
	pconf.busybox = "#dacura-frame-viewer";
	var cls = this.ldo.meta.type;
	if(typeof this.ldo.contents == "object"){
		this.ldo.contents = JSON.stringify(this.ldo.contents);
	}
	var frameobj = {result: this.ldo.contents};
	var frameid = dacura.frame.draw(cls,frameobj,pconf,'frame-container');
	dacura.frame.fillFrame(this.ldo, pconf, 'frame-container'); 
	pconf.busybox = obusy;
	dacura.frame.initInteractors();
}

LDOViewer.prototype.getUpdateButtonsHTML = function(){
	var html = '<div class="subscreen-buttons">'
	html += "<button id='" + this.prefix + "-cancelupdate' class='dacura-cancel-update subscreen-button'>Cancel Update</button>"	
	html += "<button id='" + this.prefix + "-testupdate' class='dacura-test-update subscreen-button'>Test Update</button>";	
	html += "<button id='" + this.prefix + "-update' class='dacura-test-update subscreen-button'>Update</button>";	
	html += "</div>";
	return html;
};

LDOViewer.prototype.handleViewAction = function(act){
	if(act == "export"){
		window.location.href = this.ldo.fullURL() + "&direct=1";	
	}
	else if(act == "accept" || act == "pending" || act == "reject"){
		var upd = {'ldtype': this.ldo.ldtype(), "meta": {"status": act}, "editmode": "update", "format": "json"};
		this.update(upd);
	}
	else if(act == "restore"){
		alert("restore needs to be written");
	}
	else if(act == "import"){
		alert("import!!");//this.loadEditMode();
	}
	else if(act == "edit"){
		this.loadEditMode();
	}
	else if(act == "cancel"){
		this.clearEditMode();
	}
};

LDOViewer.prototype.clearEditMode = function(){
	var args = typeof this.savedargs == "object" ? this.savedargs : this.ldo.getAPIArgs();
	delete(this.savedargs);
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id;
	msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
	this.emode="view";
	this.refresh(args, msgs);
};


LDOViewer.prototype.loadEditMode = function(){
	var args = this.ldo.getAPIArgs();
	this.savedargs = jQuery.extend(true, {}, args);
	if(typeof args.options != "object"){
		args.options = {"plain": 1};
	}
	else {
		for(var i in args.options){
			if(i != "ns" && i != "addressable"){
				delete(args.options[i]);
			}
		}
		args.options.plain = 1;
	}
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id;
	msgs = {busy: "Loading " + idstr + " in edit mode from server", "fail": "Failed to retrieve " + idstr + " in edit mode from server"};
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	var handleResp = function(data, pconf){
		self.ldo = new LDO(data);
		self.emode = "edit";
		self.show();
		//self.ldo = new LDO(data);
		//self.show();
	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

LDOViewer.prototype.handleViewFormatUpdate = function(format){
	if(format != this.ldo.format){
		var args = this.ldo.getAPIArgs();
		args.format = format;
		var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id + " in " + this.view_formats[format] + " format";
		msgs = {busy: "Fetching " + idstr + " from server", "fail": "Failed to retrieve " + idstr + " from server"};
		this.refresh(args, msgs);
	}
	else {
		alert("format will not change: still "+this.ldo.format);
	}
};

LDOViewer.prototype.handleViewOptionUpdate = function(opt, val){
	var opts = this.ldo.options;
	if(val && (typeof opts[opt] == "undefined" || opts[opt] == false)){
		opts[opt] = 1;
	}	
	else if(!val && opts[opt] == true){
		opts[opt] = 0;
	}
	else {
		return alert(opt + " is set to " + val + " no change");
	}
	var args = this.ldo.getAPIArgs();
	args.options = opts;
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id + " with option " + this.view_options[opt].title;
	if(opts[opt]){ 
		idstr += " enabled";
	}
	else {
		idstr += " disabled";
	}
	msgs = {busy: "Fetching " + idstr + " from server", "fail": "Failed to retrieve " + idstr + " from server"};
	this.refresh(args, msgs);
};

LDOViewer.prototype.refresh = function(args, msgs){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	var handleResp = function(data, pconf){
		self.ldo = new LDO(data);
		self.show();
	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

LDOViewer.prototype.refreshPage = function(args, msgs){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	var handleResp = function(data, pconf){
		dacura.ld.header(data);
		if(typeof data.history == "object" && $('#ldo-history').length){
			dacura.tool.table.reincarnate("history_table", data.history);		
		}
		if(typeof data.updates == "object" && $('#ldo-updates').length){
			dacura.tool.table.reincarnate("updates_table", data.updates);		
		}
		dacura.system.styleJSONLD("td.rawjson");	
		self.ldo = new LDO(data);
		self.show();
	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

LDOViewer.prototype.update = function(upd, handleResp){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	if(typeof handleResp != "function"){
		handleResp = function(data, pconf){
			var res = new LDResult(data, pconf);
			if(res.status == "accept" && !res.test){
				var args = typeof self.savedargs == "object" ? self.savedargs : self.ldo.getAPIArgs();
				delete(self.savedargs);
				var idstr = self.ldo.ldtype().ucfirst() + " " + self.ldo.id;
				msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
				self.emode="view";
				self.refreshPage(args, msgs);
			}
			else if(res.status == "pending" && !res.test){
				var args = typeof self.savedargs == "object" ? self.savedargs : self.ldo.getAPIArgs();
				delete(self.savedargs);
				var idstr = self.ldo.ldtype().ucfirst() + " " + self.ldo.id;
				msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
				self.emode="view";
				self.refreshPage(args, msgs);			
			}
			res.show();
		}
		//jpr(data);
	}
	dacura.ld.update(id, upd, handleResp, this.pconf, upd.test);
}

LDOViewer.prototype.showOptionsBar = function(){
	if(this.emode == "view"){
		var html = this.showViewOptionsBar();
	}
	else {
		var html = this.showEditOptionsBar();	
	}
	html += "<span class='browsermax editor-max ui-icon ui-icon-arrow-4-diag'></span>";
	html += "<span class='browsermin dch editor-min ui-icon ui-icon-closethick'></span></div>";
	return html;
};

LDOViewer.prototype.showViewOptionsBar = function(){
	var html = "<div class='ld-view-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	if(this.view_formats){
		html += "<select class='ld-view-formats ld-control'>";
		for(var i in this.view_formats){
			var sel = "";
			if(this.ldo.format == i){
				sel = "selected "
			}
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-format-" + i + "' " + sel + ">" + this.view_formats[i] + "</option>";							
		}
		html += "</select>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-centre'>";
	if(this.view_options){
		html += "<span class='ld-view-options'>";
		for(var i in this.view_options){
			html += "<input type='checkbox' class='ld-control ld-bar-option' id='" + this.prefix + "-option-" + i + "' ";
			if(this.ldo.options[i] == 1){
				html += "checked";
			}
			html += " /><label for='" + this.prefix + "-option-" + i + "'>" + this.view_options[i].title + "</label>";
		}
		html += "</span>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-right'>";
	if(this.view_actions){
		html += "<span class='ld-update-actions'>";
		if(this.ldo.meta.version != this.ldo.meta.latest_version){
			if(typeof this.view_actions['restore'] == "string"){
				html += "<button class='ldo-actions ld-control' title='" + this.view_actions["restore"] + "' id='"+ this.prefix + "-action-restore'>" + this.view_actions["restore"] + "</button>";								
			}
			if(typeof this.view_actions['export'] == "string"){
				html += "<button class='ldo-actions ld-control' title='" + this.view_actions["export"] + "' id='"+ this.prefix + "-action-export'>" + this.view_actions["export"] + "</button>";								
			}
		}
		else {
			for(var i in this.view_actions){
				if(i == "restore") continue;
				if(i == "edit" && this.edit_formats && typeof(this.edit_formats[this.ldo.format]) == "undefined") continue;
				if((this.ldo.meta.status == "accept" && i != "reject" && i != "accept") || 
						(this.ldo.meta.status == 'pending' && i != 'pending') || 
						(this.ldo.meta.status == "reject" && i != "reject" && i != "accept" && i!= "pending")){
					html += "<button class='ldo-actions ld-control' title='" + this.view_actions[i] + "' id='"+ this.prefix + "-action-" + i + "'>" + this.view_actions[i] + "</button>";
				}
			}
		}
		html += "</span>";
	}
	html += "</td></tr></table>";
	return html;
};

LDOViewer.prototype.showEditOptionsBar = function(){
	var html = "<div class='ld-edit-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	tit = "format: " + this.view_formats[this.ldo.format];
	if(typeof(this.ldo.options) == 'object' && this.ldo.options.ns){
		tit += ", Prefixes on";
	}
	else {
		tit += ", Prefixes off";
	}
	if(typeof(this.ldo.options) == 'object' && this.ldo.options.addressable){
		tit += ", Addressable blank nodes";
	}
	else {
		tit += ", Normal blank nodes";	
	}
	html += "<strong title='" + tit + "'>Edit Mode (" + this.ldo.format + ")</strong>";
	html += "</td>";
	html += "<td class='ld-bar ld-bar-centre'>";
	if(this.editmode_options){
		html += "<div class='editbar-options editmode-options'>";
		html += "<select class='ld-edit-modes api-control'>";
		for(var i in this.editmode_options){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-editmode-" + i + "'>" + this.editmode_options[i] + "</option>";							
		}
		html += "</select></div>";
	}
	if(this.result_options){
		html += "<div class='editbar-options result-options'>";
		html += "<select class='ld-result-modes api-control'>";
		for(var i in this.result_options){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-resultoption-" + i + "'>" + this.result_options[i] + "</option>";							
		}
		html += "</select></div>";
	}			
	if(this.view_graph_options){
		html += "<div class='editbar-options view-graph-options'>";
		for(var i in this.view_graph_options){
			html += "<input type='checkbox' class='api-control ld-api-option' id='" + this.prefix + "-graphoption-" + i + "'";
			html += " /><label for='" + this.prefix + "-graphoption-" + i + "'>" + this.view_graph_options[i] + "</label>";
		}
		html += "</div>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-right'>";
	html += "<span class='ld-update-actions'>";
	html += "<button class='ldo-actions ld-control' title='Cancel Editing' id='"+ this.prefix + "-action-cancel'>Cancel Editing</button>";
	html += "<span>";
	html += "</td></tr></table>";
	return html;
}


LDOViewer.prototype.showRaw = function(){
	$(this.target).html(this.ldo.getContentsHTML());	
}

function LDO(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
	this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
}


LDO.prototype.getHTML = function(mode){
	if(!this.contents && !this.meta){
		if(isEmpty(this.inserts) && isEmpty(this.deletes)){
			html = "<div class='info'>No changes to graph</div>";		
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

LDO.prototype.getUpdatedContents = function(jtarget){
	if(this.format == "json" || this.format== "jsonld"){
		return $(jtarget + ' textarea.dacura-json-editor').val();
	}
	else if(this.format == "triples" || this.format == "quads"){
		alert("update not done for this format");
	}	
	else if(this.format == "html"){
		alert("update not done for this format");		
	}
	else {
		return $(jtarget + ' textarea.dacura-text-editor').val();
	}
}

LDO.prototype.getContentsHTML = function(mode){
	if(this.format == "json" || this.format== "jsonld"){
		return dacura.ld.wrapJSON(this.contents, mode);
	}
	else if(this.format == "triples" || this.format == "quads"){
		return dacura.ld.getTripleTableHTML(this.contents, mode);
	}
	else if(this.format == "html"){
		if(this.ldtype() == "candidate" && typeof this.contents == "object"){
			return "<div id='dacura-frame-viewer'></div>" 
		}
		else {
			return "<div class='dacura-html-viewer'>" + this.contents + "</div>";
		}
	}
	else if(this.format == "svg"){
		return "<object id='svg' type='image/svg+xml'>" + this.contents + "</object>";
	}
	else {
		if(mode == "edit"){
			return "<div class='dacura-export-editor'><textarea class='dacura-text-editor'>" + this.contents + "</textarea></div>";			
		}
		else {
			return "<div class='dacura-export-viewer'>" + this.contents + "</div>";
		}
	}	
};

LDO.prototype.getAPIArgs = function(){
	var args = {
		"format": this.format,
		"options": this.options,
		"ldtype": this.meta.ldtype
	};
	if(this.meta.version != this.meta.latest_version){
		args.version = this.meta.version;
	}
	return args;
}

LDO.prototype.url = function(){
	return this.meta.cwurl;
}


LDO.prototype.fullURL = function(){
	var url = this.url() + "?";
	var args = {"ldtype": this.ldtype()};
	if(this.format){
		args['format'] = this.format;
	}
	if(this.meta.version != this.meta.latest_version){
		args['version'] = this.meta.version;
	}
	for(var i in this.options){
		if(i == "ns" || i == "addressable"){
			args['options[' + i + ']'] = this.options[i];
		}
	}
	for(var j in args){
		url += j + "=" + args[j] + "&";
	}
	return url.substring(0, url.length-1);
}

LDO.prototype.ldtype = function(){
	return this.meta.ldtype;
}

function LDOUpdate(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.inserts = typeof data.insert == "undefined" ? false : data.insert;
	this.deletes = typeof data["delete"] == "undefined" ? false : data["delete"];
	//this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
	this.changed = typeof data.changed == "undefined" ? false : new LDO(data.changed);
	this.original = typeof data.original == "undefined" ? false : new LDO(data.original);
}

LDOUpdate.prototype.getHTML = function(mode){
	if(!this.inserts && !this.deletes){
		html = "<div class='info'>No Updates</div>";		
	}
	else {
		html = "<h2>Forward</h2>";
		html += JSON.stringify(this.inserts);
		html += "<h2>Backward</h2>";
		html += JSON.stringify(this.deletes);
		if(this.changed){
			html += "<h2>After</h2>";
			html += this.changed.getHTML(mode);
		}
		if(this.original){
			html += "<h2>Before</h2>";
			html += this.original.getHTML(mode);
		}
	}
	return html;
}

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

