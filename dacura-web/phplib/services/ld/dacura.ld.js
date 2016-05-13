/*
 * This is the javascript / client side of the Linked Data API
 * This file is included by all services that use the API 
 */

dacura.ld = {}
dacura.ld.ldo_type = "ldo";

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

dacura.ld.viewer = {
	format: "json",
	mode: "view",
	div: "",
	
	initCreate: function(formdiv){
		//ldsource 
		var ldsourceid = formdiv + "-ldsource";
		$("#" +ldsourceid + " :radio").click(function(e) {
			var choice = this.id.substring(ldsourceid.length + 1);
			dacura.ld.viewer.showLDInput(formdiv, choice); 
		});
		if($("#" +ldsourceid + " :checked").length){
			dacura.ld.viewer.showLDInput(formdiv, $("#" +ldsourceid + " :checked").attr("id").substring(ldsourceid.length +1));			
		}
	},
	
	hideLDInputs: function(formdiv){
		$('tr#row-'+formdiv + "-ldurl").hide();
		$('tr#row-'+formdiv + "-contents").hide();
		$('tr#row-'+formdiv + "-ldfile").hide();
	},
	
	showLDInput: function(formdiv, format){
		this.hideLDInputs(formdiv); 
		var ft = format == "text" ? "-contents" : "-ld" + format;
		$('tr#row-'+formdiv + ft).show();	
	},
	
	drawLDProps: function(ldprops, mode, format){
		var jqkey = '#'+this.div + " .ld-viewer-body";
		if(format == "json" || format== "jsonld"){
			if(!mode || mode == "view"){
				$(jqkey).html("<div class='dacura-json-viewer'>" + JSON.stringify(ldprops, null, 4) + "</div>");				
			}
			else {
				$(jqkey).html("<div class='dacura-json-editor'>" + JSON.stringify(ldprops, null, 4) + "</div>");			
				
			}
		}
		else if(format == "triples"){
			var html = "<div class='ld-table-viewer'><table class='ld-triples-viewer'><thead><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr></thead><tbody>";
			for (var i in ldprops) {
				var row = "<tr><td>" + ldprops[i][0] + "</td>";
				row += "<td>" + ldprops[i][1] + "</td>";
				if(typeof ldprops[i][2] == "object"){
					row += "<td>" + JSON.stringify(ldprops[i][2]) + "</td>";			
				}
				else {
					row += "<td>" + ldprops[i][2] + "</td></tr>";
				}
				html += row;
			}
			$(jqkey).html(html + "</tbody></table></div>");	
		}
		else if(format == "quads"){
			var html = "<div class='ld-table-viewer'><table class='ld-triples-viewer'><thead><tr><th>Subject</th><th>Predicate</th><th>Object</th><th>Graph</th></tr><thead><tbody>";
			for (var i in ldprops) {
				var row = "<tr><td>" + ldprops[i][0] + "</td>";
				row += "<td>" + ldprops[i][1] + "</td>";
				if(typeof ldprops[i][2] == "object"){
					row += "<td>" + JSON.stringify(ldprops[i][2]) + "</td>";			
				}
				else {
					row += "<td>" + ldprops[i][2] + "</td>";
				}
				row += "<td>" + ldprops[i][3] + "</td></tr>";
				html += row;
			}
			$(jqkey).html(html + "</tbody></table></div>");	
		}
		else if(format == "html"){
			var html = "<div class='dacura-html-viewer'>";
			html += ldprops;
			$(jqkey).html(html + "</table></div>");	
				$('.pidembedded').click(function(event){
					$('#'+ event.target.id + "_objrow").toggle();
				});
		}
		else {
			if(format == "svg"){
				var html = "<object id='svg' type='image/svg+xml'>" + ldprops + "</object>";
			}
			else {
				var html = "<div class='dacura-export-viewer'>" + ldprops + "</div>";
			}
			$(jqkey).html(html);	
					
		}
		$(jqkey).show();
	},
	
	draw: function(ldo, div){
		$('#'+div).html('<div class="ld-main-body"><div class="ld-viewer-header"></div><div class="ld-viewer-body"></div></div>');
		this.div = div;
		this.format = ldo.format;
		this.mode = ldo.mode;
		this.options = ldo.options;
		this.drawLDProps(ldo.contents, this.mode, this.format);
	},
	
	
	loadURL: function(ipfield, target){
		//load url into textbox...
		var url = $('#'+ipfield).val();
		if(!url){
			alert("You must enter a url before loading it");
		}
		else if(!validateURL(url)){
			alert(escapeHtml(url) + " is not a valid url - please fix it and try again");
		}
		else {
			$('#'+target).load($('#'+ipfield).val());
		}
	},
	
	loadFile: function(ipfield, target){
		if (window.File && window.FileReader && window.FileList && window.Blob) {
			alert($('#'+ipfield).val());
			//
			// Great success! All the File APIs are supported.
		} else {
			 alert('The File APIs are not fully supported by this browser - file will be loaded on object creation.');
		}
		//alert(file + tfield)	
	},
	
	validateNew: function(obj){
		var errs = [];
		if(typeof obj.contents == 'string' && obj.contents && typeof obj.format == "string" && dacura.ld.isJSONFormat(obj.format)){
			try {
				x = JSON.parse(obj.contents);
				if(typeof x != "object"){
					errs.push("Contents must contain a well formed json object");
				}
			}
			catch(e){
				errs.push("Contents does not contain well-formed json");
			}
		}
		if(typeof obj.meta == 'string' && obj.meta){
			try {
				x = JSON.parse(obj.meta);
				if(typeof x != "object"){
					errs.push("Meta does not contain a json object");
				}
			}
			catch(e){
				errs.push("Meta does not contain well-formed JSON: "+e.message);
			}
		}
		if(errs.length > 0){
			return errs;
		}
		return false;
	},
	
	parseCreateForm: function(obj, demand_id_token, options){
		apiobj = {};
		if(typeof options != "undefined"){
			apiobj.options = options;
		}
		if(typeof obj.id == "string" && obj.id){
			apiobj[demand_id_token] = obj.id;
		}
		if(typeof obj.meta != "undefined" && obj.meta) {
			apiobj.meta = JSON.parse(obj.meta);
		}
		if(typeof obj.status == "string" && obj.status){
			if(typeof apiobj.meta != "object") {
				apiobj.meta = {};
			}
			apiobj.meta.status = obj.status;
		}
		if(typeof obj.url == "string" && obj.url){
			if(typeof apiobj.meta != "object") {
				apiobj.meta = {};
			}
			apiobj.meta.url = obj.url;
		}
		if(typeof obj.title == "string" && obj.title){
			if(typeof apiobj.meta != "object") {
				apiobj.meta = {};
			}
			apiobj.meta.title = obj.title;
		}
		if(typeof obj.format == "string" && obj.format){
			apiobj.format = obj.format;
		}	
		if(typeof obj.ldtype == "string" && obj.ldtype){
			apiobj.ldtype = obj.ldtype;
		}	
		if(typeof obj.image == "string" && obj.image){
			if(typeof apiobj.meta != "object") apiobj.meta = {};
			apiobj.meta.image = obj.image;
		}
		if(typeof obj.ldsource == "string" && obj.ldsource == "file" && obj.ldfile){
			apiobj.ldfile = obj.ldfile;
		}
		else if(typeof obj.ldsource == "string" && obj.ldsource == "url" && obj.ldurl){
			apiobj.ldurl = obj.ldurl;
		}
		else if (obj.contents) {
			if(typeof apiobj.format == "string" && dacura.ld.isJSONFormat(apiobj.format)){
				try {
					apiobj.contents = JSON.parse(obj.contents);
				}
				catch(e){
					alert("Failed to parse contents as JSON object " + e.message);
					return false;
				}
			}
			else {
				apiobj.contents = obj.contents;
			}	
		}
		return apiobj;		
	}
}

/**
 * Interactions with api
 */
dacura.ld.fetchupdatelist = function(onwards, targets, type, options){
	if(typeof type == "undefined"){
		type = this.ldo_type;
	}
	var ajs = dacura.ld.api.list("updates");
	if(typeof options == "object"){
		ajs.data = {"options": options};	
	}
	var msgs = dacura.ld.msg.fetchupdatelist(true);
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.fetchldolist = function(onwards, targets, type, options){
	if(typeof type == "undefined"){
		type = this.ldtype;
	}
	var ajs = dacura.ld.api.list();
	if(typeof options == "object"){
		ajs.data = {"options": options};	
	}
	var msgs = dacura.ld.msg.fetchldolist(type);
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.ld.fetch = function(id, args, onwards, targets, msgs){
	var ajs = dacura.ld.api.view(id, args);
	if(typeof msgs != "object"){
		var msgs = {"busy": "Retrieving linked data object " + id + " from server", "fail": "Failed to retrieve linked data object "+ id};
	}
	ajs.handleResult = 	onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.create = function(data, onwards, targets, istest){
	var ajs = dacura.ld.api.create(data, istest);
	var msgs = dacura.ld.msg.create(istest, this.ldo_type);
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	//targets.slow = true;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.update = function(id, uobj, onwards, targets, istest){
	var ajs = dacura.ld.api.update(id, uobj, istest);
	var msgs = dacura.ld.msg.update(id, istest, this.ldo_type);
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}


dacura.ld.msg = {};
dacura.ld.msg.plural = function(str){
	if(str == "ontology"){
		return "ontologies";
	}
	return str + "s";
}

dacura.ld.msg.fetch = function(id, type){
	return { "busy": "Fetching " + id + " from Server", "success": "Retrieved " + id + " from server", "fail": "Failed to retrieve " + id};	
};

dacura.ld.msg.fetchldolist = function(type){
	return { "success": "Retrieved list of " + type + " from server", "busy": "Retrieving " + dacura.ld.msg.plural(type) + " list from server", "fail": "Failed to retrieve list of " + dacura.ld.msg.plural(type) + " from server"};
};

dacura.ld.msg.fetchupdatelist = function(type){
	return { "success": "Retrieved list of updates to " + dacura.ld.msg.plural(type) + " from server", "busy": "Retrieving list of updates to " + dacura.ld.msg.plural(type) + " from server", "fail": "Failed to retrieve list of updates to " + dacura.ld.msg.plural(type) + " from server"};
};

dacura.ld.msg.create = function(istest, type){
	if(typeof istest == "undefined" || istest == false){
		return { "success": "Successfully created new " + type, "busy": "Submitting new " + type + " to Dacura API", "fail": type + " submission was unsuccessful"};
	}
	else {
		return { "success": "Test creation of new " + type + " was successful", "busy": "Testing creation of new " + type + " with Dacura API", "fail": "Test creation of new " + type + " failed."};	
	}
}

dacura.ld.msg.update = function(id, istest, type){
	if(typeof istest == "undefined" || istest == false){
		return { "success": "Successfully updated " + type + " " + id, "busy": "Submitting updates to " + type + " " + id + " to Dacura API", "fail": "Updates to " + type + " " + id + " failed."};
	}
	else {
		return { "success": "Updates to " + type + " " + id + " were tested successfully", "busy": "Testing updates to " + type + " " + id  + " with Dacura API", "fail": "Updates to " + type + " " + id + " failed Dacura test."};	
	}
}

dacura.ld.apiurl = dacura.system.apiURL();
dacura.ld.api = {};


dacura.ld.api.create = function (data, test){
	var xhr = {};
	xhr.url = dacura.ld.apiurl;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	if(typeof test != "undefined"){
		data.test = true;
	}
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;
}

dacura.ld.api.update = function (id, data, test){
	var xhr = {};
	xhr.url = dacura.ld.apiurl + "/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	if(typeof test != "undefined" && test != false){
		data.test = true;
	}
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
	var msgs = {busy: "Uploading file to server", success: "File uploaded to server", fail: "Failed to upload file"};
	dacura.system.invoke(xhr, msgs, pconf);
}




