
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

LDGraphResult.prototype.getEmptyHTML = function(type){
	return "<div class='empty-ldcontents'>Empty</div>";
};

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

LDGraphResult.prototype.getResultTitle = function(){
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		return this.message.title;
	}
	return this.action;
};

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

LDGraphResult.prototype.getGraphUpdatesSummary = function(){
	var html = "";
	if(this.inserts && this.inserts.length > 0){
		html += "<span class='graph-summary-element graph-inserts-summary'>" + this.inserts.length + " inserts</span>";
	}
	if(this.deletes && this.deletes.length > 0){
		html += "<span class='graph-summary-element graph-deletes-summary'>" + this.deletes.length + " deletes</span>";
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
LDGraphResult.prototype.getErrorsSummary = LDResult.prototype.getErrorsSummary;
LDGraphResult.prototype.getWarningsSummary = LDResult.prototype.getWarningsSummary;


LDGraphResult.prototype.isEmpty = function(){
	return !(this.inserts || this.deletes);
};

LDGraphResult.prototype.isTotallyEmpty = function(){
	return !(this.hasErrors() || this.hasWarnings() || !this.isEmpty());
};
