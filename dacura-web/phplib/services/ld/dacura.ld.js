/*
 * This is the javascript / client side of the Linked Data API
 * This file is included by all services that use the API 
 */

dacura.ld = {}
dacura.ld.apiurl = dacura.system.apiURL();
dacura.ld.entity_type = "entity";
dacura.ld.plurals = {"entity": "entities"};
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
	xhr.url = dacura.ld.apiurl + "/" + encodeURIComponent(id);
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	if(typeof test != "undefined"){
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

dacura.ld.api.list = function (type, fetch_updates){
	xhr = {};
	//xhr.data = {"entity_type" : type};
	if(typeof fetch_updates != "undefined"){
		xhr.data = {type: "updates"};
	}
	xhr.url = dacura.ld.apiurl;
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

dacura.ld.msg.fetchentitylist = function(type){
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
		type = this.entity_type;
	}
	var ajs = dacura.ld.api.list(type, "updates");
	var msgs = dacura.ld.msg.fetchupdatelist(type);
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.fetchentitylist = function(onwards, targets, type){
	if(typeof type == "undefined"){
		type = this.entity_type;
	}
	var ajs = dacura.ld.api.list(type);
	var msgs = dacura.ld.msg.fetchentitylist(type);
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.ld.fetch = function(id, args, onwards, targets, from){
	var ajs = dacura.ld.api.view(id, args);
	var msgs = dacura.ld.msg.fetch(id, this.entity_type);
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		if(typeof obj.decision != "undefined" && obj.decision != 'accept'){
			ajs.handleJSONError(obj); 
		}
		else {
			dacura.ld.showHeader(obj);
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
			ajs.showJSONErrorResult(json); 	
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.create = function(data, onwards, targets, istest){
	var ajs = dacura.ld.api.create(data, istest);
	var msgs = dacura.ld.msg.create(istest, this.entity_type);
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.update = function(id, uobj, onwards, type, targets, istest){
	var ajs = dacura.ld.api.update(id, uobj);
	var msgs = dacura.ld.msg.update(id, istest, this.entity_type);
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




