/**
 * @file Javascript client code for ld management service
 * @author Chekov
 * @license GPL V2
 * This is the javascript / client side of the Linked Data API
 * This file is included by all services that use the API 
 */

 /** 
 * @namespace ld
 * @memberof dacura
 * @summary dacura.ld
 * @description Dacura javascript ld service module. provides client functions for accessing the dacura ld management api
 */
if(typeof dacura.ld != "object"){
	dacura.ld = {}
}
dacura.ld.ldo_type = "ldo";


/**
 * @summary get the select drop down for drawing the ontology select pane
 * @param ont {string} the id of the ontology
 * @param onttit {string} the title of the ontology
 * @param ontv {number} the version number of the ontology
 * @param ontlv {number} the latest version number of the ontology
 * @returns {String} html
 */
dacura.ld.getOntologySelectHTML = function(ont, onttit, ontv, ontlv){
	if(typeof ontv != "undefined"){
		var html = "<span class='ontlabel ontlabelrem' id='imported_ontology_" + ont + "'>";
		html += "<span class='ontid-label' title='" + onttit + "'>" + ont + "</span>";
		html += "<span class='remove-ont' id='remove_ontology_" + ont + "'>" + dacura.system.getIcon('error') + "</span>";
		html += "<span class='ont-version-selector'>";
		html += "<select class='imported_ontology_version' id='imported_ontology_version_" + ont + "'><option value='0'";
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
	}
	else {
		var html = "<span class='ontlabel ontlabeladd' title='Click to add ontology " + onttit + "' id='add_ontology_" + ont + "'>";
		html += ont;
		html += " <span class='add-ont'>" + dacura.system.getIcon('add') + "</span>";
	}
	html += "</span>";
	return html;
};

/**
 * @summary generates the html for a control table row (little summary boxes on graph home page)
 * @param rowdata {object} configuration object for row with unclickable, help, id, count, variable, value, icon
 * @returns {String} html
 */
dacura.ld.getControlTableRow = function(rowdata){
	var html ="<tr class='control-table";
	if(typeof rowdata.unclickable != "undefined" && rowdata.unclickable){
		html += " unclickable-row";
	}
	else {
		html += " control-table-clickable";
	}
	html +="' id='row_" + rowdata.id + "'>";
	if(typeof rowdata.icon != "undefined"){
		html += "<td class='control-table-icon' title='" + escapeHtml(rowdata.help) + "'>" + rowdata.icon + "</td>";
	}
	else {
		//html += "<td class='control-table-empty'>" + "</td>";
	}
	html += "<td class='control-table-number' id='" + rowdata.id + "-count'>" + rowdata.count + "</td>" +
	"<td class='control-table-variable' title='" + escapeHtml(rowdata.help) + "'>" + rowdata.variable + "</td>" +
	"<td class='control-table-value'>" + rowdata.value + "</td></tr>";
	return html;
};

/**
 * @summary generates the html to depict a 'summary' row in a html table
 * @param rowdata {object} configuration object for row with unclickable, help, id, count, variable, value, icon
 * @returns {String} html
 */
dacura.ld.getSummaryTableEntry = function(rowdata){
	var html = "<div class='summary-entry";
	if(typeof rowdata.unclickable != "undefined" && rowdata.unclickable){
		html += " unclickable-summary";
	}
	else {
		html += " clickable-summary";
	}
	html += "'";
	if(rowdata.id){
		html += " id='sum_" + rowdata.id + "'";
	}
	if(typeof rowdata.icon != "undefined"){
		html += "><span class='summary-icon' title='" + rowdata.help + "'>" + rowdata.icon + "</span>";
	}
	else {
		html += ">";
	}
	html +=	"<span class='summary-value' title='" + escapeHtml(rowdata.value) + "'>"  + rowdata.count + "</span> " +
	"<span class='summary-variable' title='" + escapeHtml(rowdata.value) + "'>" + rowdata.variable + "</span></div>";
	return html;
};

/**
 * Gets a list of DQS rows for entry into summary and control tables (as above)
 * @param target {String} jquery selector for target to write into
 * @param res {LDResult} ld result object as returned by dacura api
 * @param meta {Object} metadata json array
 * @param pconf {PageConfig} page config object
 * @param importer {OntologyImporter} ontology importer object
 * @param dqsconfig {DQSConfigurator} dqs configuration object
 * @param include_triples {boolean} if true, triples table should be printer
 * @returns {Array} array of rowdata for passing to getControlTableRow, getSummaryTableRow
 */
dacura.ld.getDQSRows = function(target, res, meta, pconf, importer, dqsconfig, include_triples){
	var rows = [];
	if(res.hasWarnings()){
		$(target + ' .dqs-warnings .subscreen-body').html(res.getWarningsHTML());
		var rowdata = {
			id: "warnings",
			icon: dacura.system.getIcon("warning"),
			count: res.warnings.length,
			variable: "warning" + (res.warnings.length == 1 ? "" : "s"),
			value: res.getWarningsSummary(),
			help: "Warnings may indicate an error or a lapse in best practice but they do not prevent the use of the ontology to validate instance data"
		};
		rows.push(rowdata);	
	}
	if(res.hasErrors()){
		$(target + ' .dqs-errors  .subscreen-body').html(res.getErrorsHTML());
		var rowdata = {
			id: "errors",
			icon: dacura.system.getIcon("error"),
			count: res.errors.length,
			variable: "error" + (res.errors.length == 1 ? "" : "s"),
			value: res.getErrorsSummary(),
			help: "Errors indicate problems with the ontology which will prevent it from being used to validate instance data"
		};	
		rows.push(rowdata);	
	}
	dqsconfig.draw(target + ' .dqs-tests .subscreen-body');
	if(typeof res.tests != "undefined"){
		rowdata = {id: "tests"};
		rowdata.count = res.tests;
		rowdata.icon = dacura.system.getIcon("pending");
		if(typeof res.tests == "object"){
			rowdata.count = res.tests.length;
			if(rowdata.count == 0){
				rowdata.icon = dacura.system.getIcon("warning");
				rowdata.value = "No tests configured";
			}
			else {
				rowdata.value = dqsconfig.getTestsSummary();				
			}
		}
		else if(res.tests == "all"){
			rowdata.value = dqsconfig.getTestsSummary();				
			rowdata.count = size(dqsconfig.dqs);  			
		}
		rowdata.variable = "test" + ((typeof res.tests == 'object' && res.tests.length == 1) ? "" : "s");
		rowdata.help = "The quality service can be configured to apply many different types of tests to ontologies - the current configuration is listed here";
	}
	else {
		var rowdata = {
			id: "tests",
			icon: dacura.system.getIcon("warning"),
			count: 0,
			variable: "tests",
			value: "No DQS tests configured - this ontology will not be tested by the quality service",
			help: "You must specify quality tests to be used with this ontology"
		};
	}
	rows.push(rowdata);	
	if(typeof importer == "object" && typeof res.imports != "undefined"){
		importer.draw(target + ' .dqs-imports .subscreen-body');
		$('#imaa').buttonset().click(function(){
			if($('input[name=ima]:checked').val() == "manual"){
				importer.setManual();
			}
			else {
				importer.setAuto();		
			}
		});
		$('#imaa').buttonset("disable");
	
		var rowdata = {
			id: "imports",
			icon: dacura.system.getIcon("ontology"),
			count: size(res.imports),
			variable: "import" + (size(res.imports) == 1 ? "" : "s"),
			value: res.getImportsSummary(meta.imports),
			help: "Ontologies must import those ontologies on which they have a structural dependence. "
		};
		rows.push(rowdata);	
	}
	if(typeof res.inserts != "undefined" && res.inserts.length > 0){
		$(target + ' .dqs-triples .subscreen-body').html(dacura.ld.getTripleTableHTML(res.inserts));
		var rowdata = {
			id: "triples",
			icon: dacura.system.getIcon("triples"),
			count: res.inserts.length,
			variable: "triple" + (res.inserts.length == 1 ? "" : "s"),
			value: "",
			actions: "view_triples",
			help: "Ontologies are serialised into a set of triples before being loaded by the DQS"
		};
	}
	else {
		var rowdata = {
			id: "triples",
			unclickable: true,
			icon: dacura.system.getIcon("warning"),
			count: 0,
			variable: "triples",
			value: "The graph for this ontology is currently empty, you must add contents to it before it can be serialised",
			help: "Ontologies are serialised into a set of triples before being loaded by the DQS"
		};
	}
	rows.push(rowdata);
	return rows;
}


/**
 * @summary shows the basic information about an object's analysis 
 * @param data {object} linked data object
 * @param tgt {string} the jquery expression where the output will be written
 */
dacura.ld.showAnalysisBasics = function(data, tgt){
	if(typeof data.analysis != "object"){
		var html = dacura.system.getIcon('warning') + "No analysis produced";
		$(tgt).html(html);				
	}
	else if(data.analysis.version != data.meta.version){
		var upds = data.meta.version  - data.analysis.version; 
		var html = dacura.system.getIcon('warning') + "This analysis is stale, " 
		html += (upds == 1 ? "there has been an update " : " there have been " + upds + " updates");
		html += " since this analysis was created";
		$(tgt).html(html);
	}
	else {
		var html = dacura.system.getIcon('success') + "Analysis is up to date";
		$(tgt).html(html);			
	}
};

/**
 * Flattens a tree structure into a list of its constituent elements
 * @param tree {Object} the tree structure of the object
 * @returns {Array} an array of strings each being an entry
 */
dacura.ld.getTreeEntries = function(tree){
	var ents = [];
	for(var i in tree){
		if(ents.indexOf(i) == -1){
			ents.push(i);
		}
		if(typeof(tree[i]) == 'object'){
			var ments = dacura.ld.getTreeEntries(tree[i]);
			for(var j = 0; j<ments.length; j++){
				if(ents.indexOf(ments[j]) == -1){
					ents.push(ments[j]);
				}
			}
		}
	}
	return ents;
};

/**
 * @summary parses the results of multiple sequential updates
 * @param results {Object} object with id => result
 * @param status {String} status of return
 * @param isldo {boolean} is this a linked data object? (rather than an update)
 * @returns {Object} object representing overall result with fields: {title, status, msg, extra} 
 */
dacura.ld.parseMultiResults = function(results, status, isldo){
	var ut = (typeof isldo == "undefined") ? "Update to " : " "; 
	var utn = (typeof isldo == "undefined") ? "Updates to " : " "; 
	var errs = [];
	var warns = [];
	var wins = [];
	var getTable = function(vars){
		var html = "<table class='rbtable multi-update-results'><thead><tr><th class='multi-update-id'>" + ut + tname() + "</th><th class='multi-update-msg'>Message</th></tr></thead><tbody>";
		for(var i = 0; i<vars.length; i++){
			html += "<tr><td class='multi-update-id'>" + vars[i].id + "</td><td class='multi-update-msg'>" + vars[i].text + "</td></tr>";
		}
		html += "</tbody></table>";
		return html
	}
	for(var i in results){
		if(results[i].status == "accept"){
			wins.push({id: i, text: "successfully updated to status " + status})
		}
		else if(results[i].status == "reject"){
			if(typeof results[i].message == "object"){
				errs.push({id: i, text: results[i].message.title + " - " + results[i].message.body});
			}
			else {
				errs.push({id: i, text: results[i].message})	
			}
		}
		else if(results[i].status == "pending"){
			if(typeof results[i].message == "object"){
				warns.push({id: i, text: results[i].message.title + " - " + results[i].message.body});
			}
			else {
				warns.push({id: i, text: results[i].message})	
			}
		}
		else {
			alert(results[i].status);
		}
	}
	var x = {};	
	if(errs.length == 0 && warns.length== 0){
		x.status = "success";
		x.msg = "<span class='multi-update-summary multi-update-successes'>";
		if(wins.length== 1 ) {
			x.title = "Successfully updated 1 " + ut + tname();
			x.msg += "The " + ut + tname() + " had its status updated to state: " + status;
		}
		else {
			x.title = "Successfully updated " + wins.length + " " + utn + tnplural();
			x.msg += "The " + utn + tnplural() + " had their statuses updated to state: " + status;
		}
		x.msg += " </span>";
		x.extra = getTable(wins);
	}
	else if(wins.length > 0 || warns.length > 0) {
		x.status = "warning";
		x.title = "Some problems were encountered"
		x.msg = " <span class='multi-update-summary multi-update-errors'>" + errs.length + " " + ((errs.length == 1) ? ut + tname() : utn + tnplural()) + " failed </span> "; 
		x.msg += "<span class='multi-update-summary multi-update-warnings'>" + warns.length + " " + ((warns.length == 1) ?  ut + tname() :  utn + tnplural()) + " pending approval </span>"; 
		x.msg += "<span class='multi-update-summary multi-update-successes'>" + wins.length + " " + ((wins.length == 1) ?  ut + tname() :  utn + tnplural()) + " successfully updated to " + status + " </span>";
		x.extra = (errs.length > 0) ? "<h4>Errors</h4>" + getTable(errs) : "";
		x.extra += (wins.length > 0) ? "<h4>Successful</h4>" + getTable(wins) : "";			
		x.extra += (warns.length > 0) ? "<h4>Requiring Approval</h4>" + getTable(warns) : "";
	}
	else {
		x.status = "error";
		x.title = "Updates failed";
		x.msg = "<span class='multi-update-summary multi-update-successes'>"  + errs.length+ " ";
		if(errs.length == 1) {
			x.msg += ut + tname() + " failed"; 
		}
		else {
			x.msg += utn + tnplural() + " failed";
		}
		x.msg += " </span>";
		if (errs.length > 0) x.extra = getTable(errs);
	}
	return x;
}

/**
 * Called to update the status of multiple ldos at once by the list pages
 * @param upd {Object} the update json object
 * @param ids {Array} array of the ids of the objects being updated
 * @param status {String} the status they're being updated to
 * @param pconf {PageConfig} the page config object
 * @param rdatas {Object} array of the data in the row of the id
 * @param tabid {String} the id of the table in question
 * @param resetupdtype - if set the update type will be reset to the rdata.type each call
 */
dacura.ld.multiUpdateStatus = function(upd, ids, status, pconf, rdatas, tabid, resetupdtype){
	pconf.chained = (typeof pconf.chained == 'undefined') ? 1 : pconf.chained + 1;	
	var nid = ids.shift();
	var rdata = rdatas.shift();
	if(typeof results == "undefined"){
		results = {};
	}
	if(typeof resetupdtype != "undefined"){
		upd.ldtype = rdata.type;
	}
	dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	var onwards = function(data, pconf){
		results[nid] = data;
		if(isEmpty(ids)){
			var x = dacura.ld.parseMultiResults(results, status, resetupdtype);
			dacura.system.writeResultMessage(x.status, x.title, pconf.resultbox, x.msg, x.extra, pconf.mopts)
			results = {};
			//reset url to this context
			dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, dacura.system.cid());
			if(typeof tabid != "undefined"){
				dacura.tool.subscreens[dacura.tool.tables[tabid]["screen"]].lastchain = true;
				dacura.tool.table.refresh(tabid);	
			}
		}
		else {
			dacura.ld.multiUpdateStatus(upd, ids, status, pconf, rdatas, tabid, resetupdtype);			
		}
	}
	//for global scope we need to change api url...
	if(typeof rdata == "object" && typeof rdata.collectionid == "string"){
		dacura.ld.apiurl = dacura.system.apiURL(dacura.system.pagecontext.service, rdata.collectionid);
	}
	if(typeof resetupdtype != "undefined"){
		dacura.ld.update(nid, upd, onwards, pconf);	
	}
	else {
		dacura.ld.update("update/" + nid, upd, onwards, pconf);
	}
}

/**
 * @summary Generates html to show a single dqs pane html
 * @param i {number} sequence number
 * @param obj {Object} json rvo object for the dqs test in question
 */
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

/**
 * @summary Draws the header section of ld pages
 * @param obj {Object} ldo object
 */
dacura.ld.header = function(obj){
	if(obj.meta.latest_version == obj.meta.version){
		params = {"status": obj.meta.latest_status, "version": obj.meta.latest_version};
	}
	else {
		params = {"version": obj.meta.version, "status": obj.meta.status, "latest version": obj.meta.latest_version, "current status": obj.meta.latest_status};
			
	}
	var msg = obj.meta.ldtype.ucfirst() + " " + obj.id;
	dacura.tool.header.addBreadcrumb(obj.meta.cwurl, obj.meta.ldtype + " " + obj.id, "ldid");
	dacura.tool.header.showEntityHeader(msg, params);
	if(typeof obj.meta.title == "string"){
		dacura.tool.header.setSubtitle(obj.meta.title);
	}
	else if(typeof obj.meta.url == "string"){
		dacura.tool.header.setSubtitle(obj.meta.url);			
	}
	else if(typeof obj.meta.type == "string"){
		dacura.tool.header.setSubtitle(obj.meta.type);						
	}
	//if(this is where we write in the draw fragment header bit)
};

/**
 * @summary draws the header on pages where updates are being viewed
 * @param obj {Object} ldo
 */
dacura.ld.updateHeader = function(obj){
	var params = {status: obj.meta.status, "from version": obj.meta.from_version};
	if(obj.meta.to_version != 0){
		params["to version"] = obj.meta.to_version;
	}
	var msg = obj.meta.targetid + " " + obj.meta.ldtype.ucfirst() + " - update " + obj.id;
	dacura.tool.header.addBreadcrumb(obj.original.meta.cwurl, obj.meta.ldtype + " " + obj.original.id, "ldid");
	var objurl = obj.original.meta.cwurl.substring(0, obj.original.meta.cwurl.lastIndexOf("/")) + "/update/" + obj.id;
	dacura.tool.header.addBreadcrumb(objurl, "Update " + obj.id, "udid");
	dacura.tool.header.showEntityHeader(msg, params);
}

/**
 * @summary is the passed format a json encoding?
 * @returns {boolean} true if it is
 */
dacura.ld.isJSONFormat = function(f){
	if(f == "json" || f == "jsonld" || f == "quads" || f == "triples") return true;
	return false;
};

/* Interactions with api */

/**
 * @summary fetches the list of updates from the server
 * @param onwards {function} the function to handle the result
 * @param targets {PageConfig} page config object
 * @param type {String} the type of objects being updated (candidate, ontology, graph)
 */
dacura.ld.fetchupdatelist = function(onwards, targets, type, options){
	if(typeof type == "undefined"){
		type = this.ldo_type;
	}
	var ajs = dacura.ld.api.list("updates");
	if(typeof options == "object" && !isEmpty(options)){
		ajs.data = {"options": options};	
	}
	var msgs = { "success": "Retrieved list of updates to " + tnplural() + " from server", "busy": "Retrieving list of updates to " + tnplural() + " from server", "fail": "Failed to retrieve list of updates to " + tnplural() + " from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

/**
 * @summary fetches a list of linked data objects from the server
 * @param onwards {function} the function to handle the result
 * @param targets {PageConfig} page config object
 * @param type {String} the type of objects being updated (candidate, ontology, graph)
 * @param options {Array} the options to the list function
 */
dacura.ld.fetchldolist = function(onwards, targets, type, options){
	if(typeof type == "undefined"){
		type = this.ldo_type;
	}
	var ajs = dacura.ld.api.list();
	if(typeof options == "object" && !isEmpty(options)){
		ajs.data = {"options": options};	
	}
	var msgs = { "success": "Retrieved list of " + tnplural() + " from server", "busy": "Retrieving " + tnplural() + " list from server", "fail": "Failed to retrieve list of " + tnplural() + " from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

/**
 * @summary fetches a linked data object from the server
 * @param id {string} id of the ldo to be fetched
 * @param args {Array} array of arguments to pass to api
 * @param onwards {function} the function to handle the result
 * @param targets {PageConfig} page config object
 * @param msgs {Array} Messages to display whilst busy
 */
dacura.ld.fetch = function(id, args, onwards, targets, msgs){
	var ajs = dacura.ld.api.view(id, args);
	if(typeof msgs != "object"){
		var msgs = {
			"busy": "Retrieving " + tname() + " " + id + " from API", 
			"fail": "Failed to retrieve " + tname() + " "+ id
		};
	}
	ajs.handleResult = 	onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

/**
 * Called to create a new LDO on the server
 * @param data {Object} the json object to initialise the ldo
 * @param onwards {function} callback to handle result
 * @param targets {PageConfig} page configuration object
 * @param istest {boolean} if true, this is a test invocation
 */
dacura.ld.create = function(data, onwards, targets, istest){
	var ajs = dacura.ld.api.create(data, istest);
	if(typeof istest == "undefined" || istest == false){
		var msgs = { 
			"success": "Successfully created new " + tname() , 
			"busy": "Submitting new " + tname() + " to Dacura API", 
			"fail": tname() + " submission was unsuccessful"};
	}
	else {
		var msgs = { 
			"success": "Test creation of new " + tname() + " was successful", 
			"busy": "Testing creation of new " + tname() + " with Dacura API", 
			"fail": "Test creation of new " + tname() + " failed."
		};	
	}
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	//targets.slow = true;
	dacura.system.invoke(ajs, msgs, targets);
}

/**
 * @summary update an ldo on the server
 * @param id {string} the id of the ldo to update
 * @param uobj {Object} json update object
 * @param onwards {function} callback function to handle result
 * @param targets {PageConfig} page configuration object
 * @param istest {boolean} if true, this is a test invocation
 */
dacura.ld.update = function(id, uobj, onwards, targets, istest){
	var ajs = dacura.ld.api.update(id, uobj, istest);
	if(typeof istest == "undefined" || istest == false){
		var msgs = { 
			"success": "Successfully updated " + tname() + " " + id, 
			"busy": "Submitting updates to " + tname() + " " + id + " to Dacura API", 
			"fail": "Updates to " + tname() + " " + id + " failed."
		};
	}
	else {
		var msgs = { 
			"success": "Updates to " + tname() + " " + id + " were tested successfully", 
			"busy": "Testing updates to " + tname() + " " + id  + " with Dacura API", 
			"fail": "Updates to " + tname() + " " + id + " failed Dacura test."};	
	}
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.apiurl = dacura.system.apiURL();
/** 
 * @namespace api
 * @memberof dacura.ld
 * @summary dacura.ld.api
 * @description Dacura ld service api - each one returns an object with url, type and data set, ready for ajaxing
 */
dacura.ld.api = {};

/**
 * @summary Creates ajax object for create call
 * @param data {Object} the ldo to be created
 * @param test {boolean} true if this is a test
 * @returns {Object} xhr 
 */
dacura.ld.api.create = function (data, test){
	var xhr = {};
	xhr.url = dacura.ld.apiurl;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	data.test = (typeof test == "undefined") ? false : test;
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;
}

/**
 * @summary Creates ajax object for update call
 * @param id {string} the id of the ldo to be created
 * @param data {Object} the ldo to be created
 * @param test {boolean} true if this is a test
 * @returns {Object} xhr 
 */
dacura.ld.api.update = function (id, data, test){
	var xhr = {};
	xhr.url = dacura.ld.apiurl + "/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	data.test = (typeof test == "undefined") ? false : test;
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}

/**
 * @summary Creates ajax object for delete call
 * @param id {string} the id of the ldo to be created
 * @returns {Object} xhr 
 */
dacura.ld.api.del = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.ld.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
}

/**
 * @summary Creates ajax object for view call
 * @param id {string} the id of the ldo to be created
 * @param args {Array} arguments to api call
 * @returns {Object} xhr 
 */
dacura.ld.api.view = function (id, args){
	xhr = {data: args};
	xhr.url = dacura.ld.apiurl + "/" + id;
	return xhr;
}

/**
 * @summary Creates ajax object for list call
 * @param fetch_updates if set, we are fetching the list of updates, not ldos
 * @returns {Object} xhr 
 */
dacura.ld.api.list = function (fetch_updates){
	xhr = { url: dacura.ld.apiurl };
	if(typeof fetch_updates != "undefined"){
		xhr.url += "/update";
	}
	return xhr;
}

/* some utility functions */

/**
 * @summary returns the current linked data typename (ontology, candidate...)
 */
function tname(){
	if(dacura.ld.ldo_type == "ldo") return "linked data object";
	else return dacura.ld.ldo_type; 
}

/**
 * @summary returns the plural of the current linked data typename
 */
function tnplural(){
	var p = tname();
	if(p == "ontology"){
		return "ontologies";
	}
	return p + "s";
}

/**
 * @summary Simple function to print an object's created time in tables
 * @param {object} obj the current row object
 * @returns {string} the printed time
 */
function printCreated(obj){
	return timeConverter(obj.createtime);
}

/**
 * @summary Simple function to print an object's modified time in tables
 * @param {object} obj the current row object
 * @returns {string} the printed time
 */
function printModified(obj){
	return timeConverter(obj.modtime);
}

/**
 * @summary Simple function to print an object's creator in tables
 * @param {object} obj the current row object
 * @returns {string} the printed time
 */
function printCreatedBy(obj){
	return "<a href='update/" + obj.created_by + "'>" + obj.created_by + "</a>";
}

/**
 * @summary This needs to be patched back into the actual code - currently hanging loose here
 * @param data
 */
function drawFragmentHeader(data){
	if(typeof data.fragment_id != "undefined"){
		fids = data.fragment_id.split("/");
		fid = fids[fids.length -1];
		fdets = data.fragment_details;
		fpaths = data.fragment_paths;
		fpathhtml = "<div class='fragment-paths'>";
		for(i in fpaths){
			fpathhtml += "<span class='fragment-path'>";
			fpathhtml += "<span class='fragment-step'>" + data.id + "</span><span class='fragment-step'>";
			fpathhtml += fpaths[i].join("</span><span class='fragment-step'>");
			fpathhtml += "</span><span class='fragment-step'>" + data.fragment_id + "</span></span>";
		}
		fpathhtml += "</div>";
		$('#fragment-data').html("<span class='fragment-title-label'>Fragment</span> <span class='fragment-title'>" + fid + "</span><span class='fragment-details'>" + fdets + "</span>" + fpathhtml);
		$('#fragment-data').show();
	}	
}
