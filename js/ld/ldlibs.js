/**
 * @file Javascript libraries for interpreting and displaying results from the dacura api
 * @author Chekov
 * @license GPL V2
 * This is the javascript / client side of the Linked Data API
 * It should be a standalone library with no dependencies on the platform
 * as these libraries are used in both platform and console mode
 */
if(typeof dacura.ld != "object"){
	dacura.ld = {}
}
/**
 * @summary generate the html to display a triple table
 * @memberof dacura.ld
 * @param trips {Array} array of triples
 * @param tit {string} the optional table title
 * @returns {String} html 
 */
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
};

/**
 * @summary Generates the HTML to display a json update view 
 * @param def the object with the changes
 * @returns {String}
 */
dacura.ld.getJSONUpdateViewHTML = function(def){
	var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Value Before</th><th>Value After</th></tr></thead><tbody>";
	for(var i in def){
		html += "<tr><td>" + i + "</td>";
		html += "<td class='table-json-viewer'>";
		html += (typeof def[i][0] == "object" ? JSON.stringify(def[i][0], 0, 4) : def[i][0]);
		html += "</td><td class='table-json-viewer'>";
		html += (typeof def[i][1] == "object" ? JSON.stringify(def[i][1], 0, 4) : def[i][1]);
		html += "</td></tr>";
	}
	html += "</tbody></table>";
	return html;
}

/**
 * @summary shows the json view of an object
 * @param inserts the inserted json
 * @param deletes the delete json
 * @returns {String} html
 */
dacura.ld.getJSONViewHTML = function(inserts, deletes){
	if(!inserts || !deletes){
		var html = "<table class='json-graph'><thead><tr><th>Variable</th><th>Value</th></tr></thead><tbody>";
		var def = inserts ? inserts : deletes;
		for(var i in def){
			html += "<tr><td>" + i + "</td><td class='table-json-viewer'>";
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

/**
 * @summary wraps the json in html to display it in
 * @param json {Object} the json object to be wrapped
 * @param mode {string} edit|view 
 * @returns {String} html
 */
dacura.ld.wrapJSON = function(json, mode){
	if(!mode || mode == "view"){
		var html = "<div class='dacura-json-viewer'>" + JSON.stringify(json, null, 4) + "</div>";				
	}
	else {
		var html = "<div class='dacura-json-editor'><textarea class='dacura-json-editor'>" + JSON.stringify(json, null, 4) + "</textarea></div>";			
	}
	return html;
};

/**
 * Returns true if the passed format uses json as its underlying encoding.
 * @param format {String} the format
 * @returns {Boolean} true if it is a json format
 */
dacura.ld.isJSONFormat = function(format){
	if(format == "json" || format == "jsonld" || format == "quads" || "format" == "triples"){
		return true;
	}
	return false;
};

/**
 * @summary generates the html to show the mini ontology pane
 * @param ont {string} ontology id 
 * @param onttit {string} ontology title
 * @param onturl {string} ontology url
 * @param ontv {number} ontology version
 * @returns {String} html
 */
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



/**
 * LDResult object - for interpreting responses from the dacura ld api...
 * @constructor 
 * @param jsondr {Object} json object to initialise result from (from api)
 * @param pconfig {DacuraPageConfig} page configuration
 */
function LDResult(jsondr, pconfig){
	if(typeof jsondr == "undefined") {
		alert("LD result created without any result json initialisation data - not permitted!");
		return;
	}
	if(typeof pconfig == "object"){ 
		this.idprefix = pconfig.resultbox.substring(1);
	}
	this.action = jsondr.action;
	this.status = jsondr.status;
	this.message = jsondr.message;
	this.test = typeof jsondr.test == "undefined" ? false : jsondr.test;
	this.errors = parseRVOList(jsondr.errors);
	this.warnings = parseRVOList(jsondr.warnings);
	this.result = false;
	if(typeof jsondr.result == 'object' &&  jsondr.result.type == "LDO"){
		this.result = new LDO(jsondr.result);
		this.result_type = "LDO";
	}
	else if(typeof jsondr.result == 'object' &&  jsondr.result.type == "LDOUpdate"){
		this.result = new LDOUpdate(jsondr.result);
		this.result_type = "LDOUpdate";
	}
	else if(typeof jsondr.result == "string"){
		this.result = jsondr.result;
	}
	this.dqsgraph = typeof jsondr.graph_dqs == "object" ? new LDGraphResult(jsondr.graph_dqs, "triples", pconfig) : false;
	this.ldgraph = typeof jsondr.graph_ld == "object" ? new LDGraphResult(jsondr.graph_ld, "triples", pconfig) : false;
	this.metagraph = typeof jsondr.graph_meta == "object" ? new LDGraphResult(jsondr.graph_meta, "json", pconfig) : false;
	this.updategraph = typeof jsondr.graph_update == "object" ? new LDGraphResult(jsondr.graph_update, "ld", pconfig) : false;
	this.fragment_id = typeof jsondr.fragment_id == 'undefined' ? false : jsondr.fragment_id;
	this.pconfig = pconfig;
}

/**
 * @summary Does the result have extra fields (errors, warnings, result, graph)? 
 * @returns {Boolean} true if there are extra fields
 */
LDResult.prototype.hasExtraFields = function(){
	return (this.errors.length || this.warnings.length || this.result || this.ldgraph || this.dqsgraph || this.metagraph || this.updategraph);
};

/**
 * @summary retrieves a json object with all of the contents of the extra fields 
 * @returns {Object} {errors, warnings, result, meta, ld, dqs, update}
 */
LDResult.prototype.getExtraFields = function(){
	var subs = {};
	if(this.hasErrors()){
		subs["errors"] = {title: "Errors", content: this.getErrorsHTML()};
	}
	if(this.hasWarnings()){
		subs["warnings"] = {title: "Warnings", content: this.getWarningsHTML()};
	}
	if(typeof this.result == "object" && !isEmpty(this.result)){
		subs["result"] = {title: this.result.ldtype().ucfirst() + ' Contents', content: this.getResultHTML()};
	}
	if(this.metagraph ){
		if(typeof this.result == "object"){
			subs['meta'] = {title: this.result.ldtype().ucfirst() + ' Metadata', content: this.metagraph.getHTML()};
		}
		else {
			subs['meta'] = {title: 'Metadata', content: this.metagraph.getHTML()};	
		}
	}
	if(this.ldgraph){
		subs["ld"] = {title: 'Stored Triples', content: this.ldgraph.getHTML(false)};
	}
	if(this.dqsgraph ){
		subs['dqs'] = {title: 'DQS Published Triples', content: this.dqsgraph.getHTML(false)};
	}
	if(this.updategraph ){
		subs["update"] = {title: 'Updates', content: this.updategraph.getHTML(false)};
	}
	return subs;
};

/**
 * @summary generates the result box title text
 * @returns {String} the title text
 */
LDResult.prototype.getResultTitle = function(){
	var tit = "";
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
		tit += "Accepted but not Published. ";
	}
	else if(this.status == "accept"){
		tit += "Accepted and published. ";
	}
	return tit;
};

/**
 * @summary Does the result include warnings?
 * @returns {Boolean} true if there are warnings
 */
LDResult.prototype.hasWarnings = function(){
	return this.warnings && this.warnings.length > 0;
};

/**
 * @summary Does the result include errors?
 * @returns {Boolean} true if there are errors
 */
LDResult.prototype.hasErrors = function(){
	return this.errors && this.errors.length > 0;
};

/**
 * @summary retrieve a text representation of a list of errors
 * @returns {String} 
 */
LDResult.prototype.getErrorsSummary = function(){
	return summariseRVOList(this.errors);
};

/**
 * @summary retrieve a text representation of a list of warnings
 * @returns {String} 
 */
LDResult.prototype.getWarningsSummary = function(){
	return summariseRVOList(this.warnings);
};

/**
 * @summary gets the text to populate the body of the message box
 */
LDResult.prototype.getResultMessage = function(){
	var msg = "";
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : "";
	}
	else if(typeof this.message == "string"){
		msg = this.message;
	}
	return msg;
};

/**
 * @summary Displays the result by writing it into the page
 */
LDResult.prototype.show = function(){
	var mainmsg = this.getResultMessage();
	var sopts = jQuery.extend(true, {}, this.pconfig.mopts);
	var mopts = {scrollTo: true, icon: true, closeable: true, close_position: "body", test: this.test};
	if(this.test){
		mopts.tprefix = "<div class='test-result' title='" + this.action + " test result'>" + dacura.system.getIcon("test-tube-yellow") + "Test</div>"; 
	}
	var sum = this.getSummaryHTML();
	if(sum.length){
		mopts.more_html = sum;
	}
	this.pconfig.mopts = mopts;
	var extrahtml = this.hasExtraFields() ? this.getExtraHTML() : false;
	dacura.system.writeResultMessage(this.status, this.getResultTitle(), this.pconfig.resultbox, mainmsg, extrahtml, this.pconfig.mopts);
	dacura.system.styleJSONLD(this.pconfig.resultbox + " .rawjson");
	if(this.hasExtraFields()){
		$(this.pconfig.resultbox + " .rb-options").buttonset();
		var self = this;
		$(this.pconfig.resultbox + " .roption").button().click(function(event){
			$(self.pconfig.resultbox + " .result-extra").hide();
			$(self.pconfig.resultbox + " .result-extra-" + this.id.substring(11)).show();				
		});	
	}
	this.pconfig.mopts = sopts;
}

/**
 * @summary generates the html to show the result errors
 * @param type {String} json|triples - type of contents
 * @returns {String} html
 */
LDResult.prototype.getErrorsHTML = function(type){
	var html = "";
	if(this.hasErrors()){
		var errhtml = "";
		for(var i = 0; i < this.errors.length; i++){
			errhtml += this.errors[i].getHTMLRow(type);
		}
		if(errhtml.length > 0){
			html = "<div class='api-error-details'>";
			html += "<table class='rbtable dqs-error-table'>";
			html += "<thead><tr>" + "<th>Type</th><th>Message</th><th>Attributes</th></tr></thead>";
			html += "<tbody>" + errhtml + "</tbody></table></div>";
		}	
	}
	return html;	
}

/**
 * @summary generates the html to show the result warnings
 * @param type {String} json|triples - type of contents
 * @returns {String} html
 */
LDResult.prototype.getWarningsHTML = function(type){
	var html = "";
	if(this.hasWarnings()){
		var errhtml = "";
		for(var i = 0; i < this.warnings.length; i++){
			errhtml += this.warnings[i].getHTMLRow(type);
		}
		if(errhtml.length > 0){
			html = "<div class='api-warning-details'>";
			html += "<table class='rbtable dqs-warning-table'>"; 
			html +="<thead><tr>" + "<th>Type</th><th>Message</th><th>Attributes</th></tr></thead>";
			html += "<tbody>" + errhtml + "</tbody></table></div>";
		}	
	}
	return html;	
}

/**
 * @summary generates the html to show the extra fields in the result
 * @returns {String} html
 */
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

/**
 * @summary generates the html to show the result 
 * @returns {String} html
 */
LDResult.prototype.getResultHTML = function(){
	var html ="<div class='api-graph-testresults'>";
	if(this.result.isEmpty()){
		html += this.result.getEmptyHTML();
	}
	else {
		html += this.result.getContentsHTML("view");
	}
	html += "</div>";
	return html;
}

/**
 * @summary generates the html to show a summary of the result
 * @returns {String} html
 */
LDResult.prototype.getSummaryHTML = function(){
	var html = "";
	if(this.hasWarnings()) {
		var title = this.getWarningsSummary();
		var count = this.warnings.length;
		var text = "warning";
		var icon = dacura.system.getIcon("warning");
		html += getResultSummaryHTMLBlock(count, text, icon, title);
	}
	if(this.hasErrors()) {
		var title = this.getErrorsSummary();
		var count = this.errors.length;
		var text = "error";
		var icon = dacura.system.getIcon("error");
		html += getResultSummaryHTMLBlock(count, text, icon, title);
	}
	if(html){
		html = "<span class='result-summaries'>" + html + "</span>";
	}
	return html;
};

/**
 * @summary generates the html to show the results summary in a block
 * @param count {number} the entry to fill into the summary count field
 * @param text {String} the entry to fill into the summary text field
 * @param icon {String} the html to draw the result icon
 * @param title {String} the title text (for hovering over the block)
 * @returns {String} html
 */
function getResultSummaryHTMLBlock(count, text, icon, title){
	var html = "<span class='result-summary-block'";
	if(typeof title == "string"){
		html += " title='" + escapeHtml(title) + "'>"
	}
	html += "<span class='result-summary-icon'>" + icon + "</span>";
	html += "<span class='result-summary-count'>" + count + "</span>";
	if(count != 1) text += "s";
	html += " <span class='result-summary-text'>" + text + "</span>";
	html += "</span>";
	return html;
};


/**
 * @class Javascript object for interpreting graph update results
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function LDGraphResult
 * @constructor
 * @param jsondr {Object} the json intialisation object (returned by API)
 * @param graphtype {String} ld|dqs|meta|update - which graph is the result about
 * @param pconfig {DacuraPageConfig} page config object
 */
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
	this.errors = parseRVOList(jsondr.errors);
	this.warnings = parseRVOList(jsondr.warnings);
	this.pconfig = pconfig;
	this.hypotethical = jsondr.hypotethical;
}

/**
 * @summary is the result empty (no inserts, no deletes)?
 * @returns {Boolean}
 */
LDGraphResult.prototype.isEmpty = function(){
	return !(this.inserts || this.deletes);
};

/**
 * @summary is the result totally empty (no inserts, deletes, errors or warnings)
 * @returns {Boolean}
 */
LDGraphResult.prototype.isTotallyEmpty = function(){
	return !(this.hasErrors() || this.hasWarnings() || !this.isEmpty());
};

/**
 * Returns the title text of the result
 * @returns {String}
 */
LDGraphResult.prototype.getResultTitle = function(){
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		return this.message.title;
	}
	return this.action;
};

/**
 * @summary generates the html to show a graph result
 * @param show_errors {boolean} should we show errors and warnings?
 * @returns {String} the html
 */
LDGraphResult.prototype.getHTML = function(show_errors){
	var html = "<div class='api-graph-testresults'>";
	if(this.isTotallyEmpty()){
		html += this.getEmptyHTML();
	}
	else {
		html += this.getResultTitleHTML();
		var msg = this.getResultMessageHTML();
		if(msg){
			html += msg;
		}
		if(typeof show_errors != "undefined" && show_errors){
			if(this.hasErrors()){
				html += this.getErrorsHTML();
			}
			if(this.hasWarnings()){
				html += this.getWarningsHTML();
			}
		}
		if(this.tests && this.tests.length > 0){
			html += "<div class='dqs-test-summary'>";
			html += "<div class='graph-result-section-title'>DQS Tests Configured</div>"
			html += this.getTestsSummary();
			html += "</div>";
		}
		if(this.imports && !isEmpty(this.imports)){
			html += "<div class='dqs-imports-summary'>";
			html += "<div class='graph-result-section-title'>Imported Ontologies</div>"
			html += this.getImportsSummary();
			html += "</div>";
		}
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

/**
 * @summary generates the empty box html 
 * @param type {string} 
 * @returns {String}
 */
LDGraphResult.prototype.getEmptyHTML = function(type){
	return "<div class='empty-ldcontents'>Empty</div>";
};

/**
 * @summary generates the html to display the result title
 * @returns {String} html
 */
LDGraphResult.prototype.getResultTitleHTML = function(){
	var html = "<div class='graph-result-title' title='" + this.action + "'>";
	if(this.test || this.hypothetical){
		html += dacura.system.getIcon("test-tube");
	}
	html += dacura.system.getIcon(this.status);
	
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		html += this.message.title;
	}
	else {
		html += this.action;
	}
	html += "</div>";
	return html;
};

/**
 * @summary Generates the result message html
 * @returns {String} html
 */
LDGraphResult.prototype.getResultMessageHTML = function(){
	var html = "<div class='graph-result-message'>";
	var msg = "";
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : false;
	}
	else if(typeof(this.message) == "string") {
		msg = this.message;
	}
	html +=  msg + "</div>";
	return html;
}

/**
 * @summary Generates html to show a summary of the object
 * @returns {String} html
 */
LDGraphResult.prototype.getSummaryHTML = function(){
	var html = "";
	if(this.hasErrors()) html += this.getErrorsSummary();
	if(this.hasWarnings()) html += this.getWarningsSummary();
	if(this.tests) html += this.getTestsSummary();
	if(this.imports) html += this.getImportsSummary();
	if(!this.isEmpty() && this.graphtype == 'triples'){
		html += this.getGraphUpdatesSummary();
	}
	return html;
};

/**
 * @summary Generates html to show a dqs configuration page (to show which tests were configured)
 * @returns {String} html
 */
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

/**
 * @summary Generates html to show a summary of the ontologies that were imported
 * @returns {String} html
 */
LDGraphResult.prototype.getImportsSummary = function(simports){
	var html = "";
	simports = (typeof simports == "object") ? simports : this.imports;
	for(var i in simports){
		var url = dacura.system.install_url;
		url += (simports[i].collection == "all") ? "" : simports[i].collection;
		url += "/ontology/" + simports[i].id;
		html += dacura.ld.getOntologyViewHTML(i, url, null, simports[i].version);
	}
	return html;
};

/**
 * @summary Generates html to show a summary of the DQS tests configured
 * @returns {String} html
 */
LDGraphResult.prototype.getTestsSummary = function(){
	var html = "<span class='graph-summary-element'>";
	if(typeof this.tests == "string"){
		html += this.tests;
	}
	else if(typeof this.tests == "object"){
		if(this.tests.length == 0){
			html += "None";
		}
		else {
			html += this.tests.length + " tests: " + this.tests.join(", ");
		}
	}
	html += "</span>";
	return html;
};

/**
 * @summary Generates html to show a summary of the updates graph result
 * @returns {String} html
 */
LDGraphResult.prototype.getGraphUpdatesSummary = function(){
	var html = "";
	if(this.inserts && this.inserts.length > 0){
		html += "<span class='graph-summary-element graph-inserts-summary'>" + this.inserts.length + " inserts</span>";
	}
	if(this.deletes && this.deletes.length > 0){
		html += (this.inserts && this.inserts.length > 0) ? ", " : "";
		html += "<span class='graph-summary-element graph-deletes-summary'>" + this.deletes.length + " deletes</span>";
	}	
	return html;
};

/**
 * @summary Generates html to show the result 'headline' passed or failed with icon
 * @returns {String} html
 */
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

/**
 * @summary Generates html to show a summary of the result (headline + errors + warnings)
 * @returns {String} html
 */
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
/* a bunch of functions that are identical to LDResult */
LDGraphResult.prototype.getErrorsHTML = LDResult.prototype.getErrorsHTML;
LDGraphResult.prototype.getWarningsHTML = LDResult.prototype.getWarningsHTML;
LDGraphResult.prototype.hasWarnings = LDResult.prototype.hasWarnings;
LDGraphResult.prototype.hasErrors = LDResult.prototype.hasErrors;
LDGraphResult.prototype.getErrorsSummary = LDResult.prototype.getErrorsSummary;
LDGraphResult.prototype.getWarningsSummary = LDResult.prototype.getWarningsSummary;

/**
 * @file Javascript object for parsing and manipulating linked data objects
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function LDO
 * @constructor
 * @param data {Object} the ldo object returned by API
 */
function LDO(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
	this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
}

/**
 * @summary is the ldo empty (no contents)
 * @returns {Boolean} true if empty
 */
LDO.prototype.isEmpty = function(){
	if(typeof this.contents == "undefined") return true;
	if(typeof this.contents == "string") return this.contents.length == 0; 
	if(typeof this.contents == "object") return (isEmpty(this.contents) && this.contents.length == 0);
	return true;
};

/**
 * @summary returns the status of the result (accept|reject|pending)
 * @returns {String}
 */
LDO.prototype.status = function(){
	return this.meta.status;
}

/**
 * @summary Retrieve the api args that were used to fetch the ldo
 * @returns {Object} format, options, ldtype, version (if set)
 */
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

/**
 * @summary Retrieve the ldo's url
 * @returns {String}
 */
LDO.prototype.url = function(){
	return this.meta.cwurl;
}

/**
 * @summary Retrieves the full url of the ldo - with query string
 * @returns {String}
 */
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

/**
 * @summary retrieves the linked data type (candidate, ontology, graph) of the ldo
 * @returns {String}
 */
LDO.prototype.ldtype = function(){
	return this.meta.ldtype;
}

/**
 * @summary reads updated contents from the page to populate the ldo from user input
 * @param jtarget {String} - the jquery selector for where to look for the input form.
 */
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


/**
 * @summary generates the html to show the ldo 
 * @param mode - edit, view, create...
 * @returns {String} html
 */
LDO.prototype.getHTML = function(mode){
	if(!this.contents && !this.meta){
		if(isEmpty(this.inserts) && isEmpty(this.deletes)){
			html = this.getEmptyHTML();		
		}
	}
	else {
		if(this.meta){
			html = this.getMetaHTML(mode);			
		}
		else {
			html = this.getEmptyHTML();		
		}
		if(this.contents){
			html += this.getContentsHTML(mode);			
		}
		else {
			html = this.getEmptyHTML();		
		}
	}
	return html;
};

/**
 * @summary generates the html to show an empty ldo 
 * @param type {String} - ignored
 * @returns {String} html
 */

LDO.prototype.getEmptyHTML = function(type){
	return "<div class='empty-ldcontents'>Empty</div>";
};

/**
 * @summary generates the html to show the ldo meta-data
 * @param mode - edit, view, create...
 * @returns {String} html
 */
LDO.prototype.getMetaHTML = function(mode){
	return dacura.ld.wrapJSON(this.meta);	
};

/**
 * @summary generates the html to show the ldo contents
 * @param mode {String} view|edit|create
 * @returns {String} html
 */
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


/**
 * @file Javascript object for interpreting reasoning violations errors (RVO) returned in responses by the Dacura API
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function parseRVOList
 * @summary Parses a list of RVO errors (as returned by api / DQS)
 * @param jsonlist {Array} json list [] or RVO errors to be parsed
 * @returns {Array} list of parsed RVO objects
 */
function parseRVOList(jsonlist){
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
};

/**
 * @function summariseRVOList
 * @summary Summarises a list of RVO errors 
 * @param rvolist {Array} json list [] of RVO errors 
 * @returns {String} text summarising the list
 */
function summariseRVOList(rvolist){
	if(rvolist.length == 1) return rvolist[0].label;
	var entries = [];
	var bytype = {};
	for(var i = 0; i < rvolist.length; i++){
		if(!rvolist[i].cls){
			if(rvolist[i].label){
				rvolist[i].cls = rvolist[i].label.split(" ").join("");
			}
			else {
				rvolist[i].cls = JSON.stringify(rvolist[i]);
			}
		}
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

/**
 * @constructor
 * @param data {Object} the json object returned by api
 */
function RVO(data){
	if(typeof data != "object"){
		alert("not object");
		return;
	}
	this['class'] = data["class"];
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

/**
 * @summary retrieves the label of the object
 * @param mode {string} 
 * @returns {string}
 */
RVO.prototype.getLabel = function(mode){
	return this.label;
}

/**
 * @summary retrieves the css class of the label (dqs-bp, dqs-rule)
 * @param mode {string} 
 * @returns {string} css class
 */
RVO.prototype.getLabelCls = function(mode){
	if(this.best_practice){
		return "dqs-bp";
	}
	return "dqs-rule";
};

/**
 * @summary retrieves the title of the label
 * @param mode {string} 
 * @returns {string}
 */
RVO.prototype.getLabelTitle = function(mode){
	return this.label + " " + this.comment;
};

/**
 * @summary retrieves the object as a html row
 * @param type {string} 
 * @returns {string} html table row
 */
RVO.prototype.getHTMLRow = function(type){
	var html = "<tr><td title='" + this.comment + "'>"+this.label+"</td><td>"+this.message +"</td>";
	html += "<td>";
	var atrs = this.getAttributes();
    var attributes = "";
    jQuery.each(atrs, function(i, val) {
        attributes += i+": "+val+"<br>";
    });
	if(typeof atrs == "object" && !isEmpty(atrs)) html += attributes;
    if(this.info) html += " " + this.info;
	html += "</td></tr>";
	return html;
};

/**
 * @summary retrieves the attributes of the RVO as a json object
 * @returns {Object}
 */
RVO.prototype.getAttributes = function(){
	var atts = {};
	if(this.subject) atts.subject = this.subject;
	if(this.predicate) atts.predicate = this.predicate;
	if(this.object) atts.object = this.object;
	if(this.property) atts.property = this.property;
	if(this.element) atts.element = this.element;
	if(this['class']) atts['class'] = this['class'];
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
};

/**
 * @file Javascript object for interpreting LDOUpdate objects returned in responses by the Dacura API
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function LDOUpdate
 * @constructor
 * @param data {Object} the ldo update json object (returned by API)
 */
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
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
}

/**
 * @summary Retrieves the linked data type (candidate, ontology, graph) of the update 
 * @returns {String}
 */
LDOUpdate.prototype.ldtype = function(){
	return this.meta.ldtype;
}

/**
 * @summary is the update empty (no update)? 
 * @returns {Boolean} true if empty
 */
LDOUpdate.prototype.isEmpty = function(){
	return size(this.inserts) == 0 && size(this.deletes) == 0;
}

/**
 * @summary retrieves the api arguments used to generate the update
 * @returns {Object} format, options, ldtype
 */
LDOUpdate.prototype.getAPIArgs = function(){
	var args = {
		"format": this.format,
		"options": this.options,
		"ldtype": this.meta.ldtype
	};
	return args;
}

/**
 * @summary generates html to show the forward and backward commands of the update
 * @returns {String} html
 */
LDOUpdate.prototype.getCommandsHTML = function(){
	if(!this.inserts && !this.deletes){
		var html = "<div class='info'>No Updates</div>";		
	}
	else {
		var html = dacura.ld.getJSONViewHTML(this.inserts, this.deletes);	
	}
	return html;
};

/**
 * @summary generates html to show the update object
 * @returns {String} html
 */
LDOUpdate.prototype.getHTML = function(mode){
	var html = "";
	var box = (typeof box == "string") ? box : "";
	if(this.contents){
		if(typeof this.contents.meta == "object"){
			if(size(this.contents.meta) > 0){
				html += "<h3>Update to Metatdata</h3>" ;
				html += dacura.ld.getJSONUpdateViewHTML(this.contents.meta);
			}
		}
		if(typeof this.contents.contents == "object"){
			if(size(this.contents.contents) > 0){
				html += "<h3>Update to Contents</h3>";
				
				html += "<div class='updates-contents-viewer dacura-json-viewer'>"+ JSON.stringify(this.contents.contents, 0, 4) + "</div>";
			}
		}	
	}
	return (html.length ? html : "<div class='dacura-error'>No contents in ldo update object</div>");
}

/**
 * @summary make these the same function
 */
LDOUpdate.prototype.getContentsHTML = LDOUpdate.prototype.getHTML;
