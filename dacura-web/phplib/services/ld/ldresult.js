
/**
 * LDResult object - for interpreting responses from the dacura ld api...
 */
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
	dacura.system.styleJSONLD(this.pconfig.resultbox + " .rawjson")
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
	if(this.result.isEmpty()){
		html += this.result.getEmptyHTML();
	}
	else {
		html += this.result.getContentsHTML("view");
	}
	html += "</div>";
	return html;
}

LDResult.prototype.hasExtraFields = function(){
	return (this.errors.length || this.warnings.length || this.result || this.ldgraph || this.dqsgraph || this.metagraph || this.updategraph);
}

LDResult.prototype.getExtraFields = function(){
	var subs = {};
	if(this.hasErrors()){
		subs["errors"] = {title: "Errors", content: this.getErrorsHTML()};
	}
	if(this.hasWarnings()){
		subs["warnings"] = {title: "Warnings", content: this.getWarningsHTML()};
	}
	if(!isEmpty(this.result)){
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
}

/**
 * @summary generates the result box title text
 */
LDResult.prototype.getResultTitle = function(rconfig){
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

LDResult.prototype.hasWarnings = function(){
	return this.warnings && this.warnings.length > 0;
};

LDResult.prototype.hasErrors = function(){
	return this.errors && this.errors.length > 0;
};

LDResult.prototype.getErrorsSummary = function(){
	return summariseRVOList(this.errors);
};

LDResult.prototype.getWarningsSummary = function(){
	return summariseRVOList(this.warnings);
};

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
 * @summary gets the text to populate the body of the message box
 */
LDResult.prototype.getResultMessage = function(rconfig){
	var msg = "";
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : "";
	}
	else {
		//msg = this.message;
	}
	//msg += this.getSummaryHTML();
	//else if(typeof(this.message) == "string") {
	//	msg = this.message;
	//}
	return msg;
};

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
}
