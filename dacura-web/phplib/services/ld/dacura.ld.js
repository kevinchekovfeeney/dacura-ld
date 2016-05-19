/*
 * This is the javascript / client side of the Linked Data API
 * This file is included by all services that use the API 
 */

dacura.ld = {}
dacura.ld.ldo_type = "ldo";

function tname(){
	if(dacura.ld.ldo_type == "ldo") return "linked data object";
	else return dacura.ld.ldo_type; 
}

function tnplural(){
	var p = tname();
	if(p == "ontology"){
		return "ontologies";
	}
	return p + "s";
}

function printCreated(obj){
	return timeConverter(obj.createtime);
}

function printModified(obj){
	return timeConverter(obj.modtime);
}

function printCreatedBy(obj){
	return "<a href='update/" + obj.created_by + "'>" + obj.created_by + "</a>";
}

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
};

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

dacura.ld.wrapJSON = function(json, mode){
	if(!mode || mode == "view"){
		var html = "<div class='dacura-json-viewer'>" + JSON.stringify(json, null, 4) + "</div>";				
	}
	else {
		var html = "<div class='dacura-json-editor'><textarea class='dacura-json-editor'>" + JSON.stringify(json, null, 4) + "</textarea></div>";			
	}
	return html;
};

dacura.ld.isJSONFormat = function(format){
	if(format == "json" || format == "jsonld" || format == "quads" || "format" == "triples"){
		return true;
	}
	return false;
};

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

dacura.ld.getTreeEntries = function(tree){
	var ents = [];
	for(var i in tree){
		if(ents.indexOf(i) == -1){
			ents.push(i);
		}
		if(typeof(tree[i]) == 'object'){
			var ments = getTreeEntries(tree[i]);
			for(var j = 0; j<ments.length; j++){
				if(ents.indexOf(ments[j]) == -1){
					ents.push(ments[j]);
				}
			}
		}
	}
	return ents;
};


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
};

dacura.ld.isJSONFormat = function(f){
	if(f == "json" || f == "jsonld" || f == "quads" || f == "triples") return true;
	return false;
};

/**
 * Interactions with api
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
	//if(typeof targets.always){
	//	ajs.always = targets.always;
	//}
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.apiurl = dacura.system.apiURL();
dacura.ld.api = {};


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

dacura.ld.api.del = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.ld.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.ld.api.view = function (id, args){
	xhr = {data: args};
	xhr.url = dacura.ld.apiurl + "/" + id;
	return xhr;
}

dacura.ld.api.list = function (fetch_updates){
	xhr = { url: dacura.ld.apiurl };
	if(typeof fetch_updates != "undefined"){
		xhr.url += "/update";
	}
	return xhr;
}

dacura.ld.uploadFile = function(payload, onwards, pconf){
	xhr = {};
	xhr.url = dacura.system.apiURL("config") + "/files";
	xhr.type = "POST";
	xhr.data = payload;
	xhr.processData= false;
	xhr.contentType = payload.type;
	xhr.handleResult = onwards;
	//turn off always as this call is always used in a chain - means the handling function can still use busy messages and the buttons remain disabled
	xhr.always = function(){};
	var msgs = {busy: "Uploading file to server", success: "File uploaded to server", fail: "Failed to upload file"};
	dacura.system.invoke(xhr, msgs, pconf);
}





