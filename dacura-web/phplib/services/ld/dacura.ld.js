/*
 * This is the javascript / client side of the Linked Data API
 * This file is included by all services that use the API 
 */

dacura.ld = {}
dacura.ld.apiurl = dacura.system.apiURL();
dacura.ld.ldo_type = "ldo";
dacura.ld.plurals = {"ldo": "linked data objects"};
dacura.ld.api = {};

dacura.ld.header = function(obj){
	if(obj.meta.latest_version == obj.meta.version){
		params = {"status": obj.meta.latest_status, "version": obj.meta.latest_version};
	}
	else {
		params = {"version": obj.meta.version, "status": obj.meta.status, "latest version": obj.meta.latest_version, "current status": obj.meta.latest_status};
			
	}
	var msg = obj.ldtype + " " + obj.id;
	dacura.tool.header.showEntityHeader(msg, params);	
}

dacura.ld.viewer = {
	format: "json",
	mode: "view",
	div: "",
	init: function(div, options, mode, format){
		$('#'+div).html('<div class="ld-main-body"><div class="ld-viewer-header"></div><div class="ld-viewer-body"></div></div>');
		if(typeof mode == "string"){
			this.mode = mode;					
		}
		if(typeof format == "string"){
			this.format = format;
		}	
		this.div = div;
		if(typeof options.version_browser != "undefined"){
			//draw version browser component
		}
		if(typeof options.can_update != "undefined"){
			//draw update browser component
		}	
		if(mode == "view" && typeof options.formats == "object"){
			//draw radio buttons for choosing format
		}		
	},
	
	initCreate: function(formdiv){
		//ldsource 
		var ldsourceid = formdiv + "-ldsource";
		$("#" +ldsourceid + " :radio").click(function(e) {
			var choice = this.id.substring(ldsourceid.length + 1);
			dacura.ld.viewer.showLDInput(formdiv, choice); 
		});
		this.showLDInput(formdiv, $("#" +ldsourceid + " :checked").attr("id").substring(ldsourceid.length +1));
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
	
	draw: function(ldo){
		this.format = ldo.format;
		this.mode = ldo.mode;
		this.options = ldo.options;
		this.drawLDProps(ldo.contents, this.mode, this.format);
	},
	
	isJSONFormat: function(f){
		if(f == "json" || f == "jsonld" || f == "quads" || f == "triples") return true;
		return false;
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
		if(typeof obj.contents == 'string' && obj.contents && (obj.format == "json" || obj.format == "jsonld" || obj.format == "triples" || obj.format == "quads")){
			try {
				x = JSON.parse(obj.contents);
			}
			catch(e){
				return "Contents does not contain well-formed json";
			}
			if(typeof x != "object"){
				return "Contents must contain a well formed json object";
			}
		}
		if(typeof obj.meta == 'string' && obj.meta){
			try {
				x = JSON.parse(obj.meta);
			}
			catch(e){
				return "Meta does not contain well-formed json";
			}
			if(typeof x != "object"){
				return "Meta must contain a well formed json object";
			}
		}
		return "";
	}
	
}


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
	xhr.url = dacura.ld.apiurl + "/" + encodeURIComponent(id);
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	if(typeof test != "undefined" && test !== false){
		data.test = true;
	}
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}

dacura.ld.api.del = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.ld.apiurl + "/" + encodeURIComponent(id);
	xhr.type = "DELETE";
	return xhr;
}

dacura.ld.api.view = function (id, args){
	xhr = {data: args};
	xhr.url = dacura.ld.apiurl + "/" + encodeURIComponent(id);
	return xhr;
}

dacura.ld.api.list = function (fetch_updates){
	xhr = { url: dacura.ld.apiurl };
	if(typeof fetch_updates != "undefined"){
		xhr.url += "/update";
	}
	return xhr;
}

dacura.ld.msg = {};
dacura.ld.msg.plural = function(str){
	if(typeof dacura.ld.plurals[str] != "undefined"){
		return dacura.ld.plurals[str];
	}
	return "No plural defined for " + str;
}

dacura.ld.msg.fetch = function(id, type){
	return { "busy": "Fetching " + type + " " + id + " from Server", "success": "Retrieved " + type + " " + id + " from server", "fail": "Failed to retrieve " + type + " " + id};	
};

dacura.ld.msg.fetchldolist = function(type){
	return { "success": "Retrieved list of " + dacura.ld.msg.plural(type) + " from server", "busy": "Retrieving " + dacura.ld.msg.plural(type) + " list from server", "fail": "Failed to retrieve list of " + dacura.ld.msg.plural(type) + " from server"};
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


dacura.ld.fetchupdatelist = function(onwards, targets, type){
	if(typeof type == "undefined"){
		type = this.ldo_type;
	}
	var ajs = dacura.ld.api.list("updates");
	var msgs = dacura.ld.msg.fetchupdatelist(true);
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.fetchldolist = function(onwards, targets, type){
	if(typeof type == "undefined"){
		type = this.ldo_type;
	}
	var ajs = dacura.ld.api.list();
	var msgs = dacura.ld.msg.fetchldolist(type);
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.ld.fetch = function(id, args, onwards, targets, from){
	var ajs = dacura.ld.api.view(id, args);
	var msgs = dacura.ld.msg.fetch(id, this.ldo_type);
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		if(typeof obj.status != "undefined" && obj.status != 'accept'){
			ajs.handleJSONError(obj); 
		}
		else {
			//dacura.ld.showHeader(obj);
			if(typeof onwards != "undefined"){
				onwards(obj);
			}
		}
	}
	ajs.handleJSONError = function(json){
		if(typeof targets == "undefined" || typeof targets.resultbox == "undefined" || !targets.resultbox ){
			targets = {resultbox: dacura.system.targets.resultbox};
		}
		if(typeof(dacura.ldresult) != "undefined"){
			dacura.ldresult.update_type = "view";
			var cancel = function(){
				$(targets.resultbox).html("");
			};
			dacura.ldresult.showDecision(json, targets.resultbox, cancel);			
		}
		else {
			dacura.system.showJSONErrorResult(json); 	
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.create = function(data, onwards, targets, istest){
	var ajs = dacura.ld.api.create(data, istest);
	var msgs = dacura.ld.msg.create(istest, this.ldo_type);
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.update = function(id, uobj, onwards, type, targets, istest){
	var ajs = dacura.ld.api.update(id, uobj, istest);
	var msgs = dacura.ld.msg.update(id, istest, this.ldo_type);
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}



dacura.ld.drawVersionHeader = function(data){
	$('.version-title').html("version " + data.version);
	createtxt = "created " + timeConverter(data.version_created);
	$('.version-created').html(	createtxt);
	if(data.version_replaced > 0){	
		repltxt = "replaced " + timeConverter(data.version_replaced); 	
		$('.version-replaced').html(repltxt);
	}
	else {
		$('.version-replaced').html("");	
	}
	$('#version-header').show();
}



//need to be moved into the linked data library 
dacura.ld.setLDSingleValue = function(obj, key, val){
	if(typeof obj != "undefined"){
		for(var k in obj){
			if(typeof obj[k][key] != "undefined"){
				obj[k][key] = val;
			}
		}
	}
}



dacura.ld.setLDToolHeader = function(ldo){
	options = { subtitle: ldo.id };
	if(typeof ldo.title != "undefined"){
		options.subtitle = ldo.title;
	}
	if(typeof ldo.image != "undefined"){
		options.image = ldo.image;
	}
	options.description = $('#ldldo-header-template').html();
	dacura.tool.updateToolHeader(options);
	if(typeof ldo.metadetails != "undefined"){
		metadetails = ldo.metadetails;
	}
	else {
		metadetails = timeConverter(ldo.created);
	}
	$('.ldo_type').html("<span class='ldo-type'>" + ldo.type + "</span>");
	$('.ldo_created').html("<span class='ldo-details'>" + metadetails + "</span>");
	$('.ldo_status').html("<span class='ldo-status ldo-" + ldo.latest_status + "'>" + ldo.latest_status + "</span>");
}




