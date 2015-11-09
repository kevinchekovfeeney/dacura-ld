dacura.ld = {}
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

dacura.ld.api.update = function (id, data){
	var xhr = {};
	xhr.url = dacura.ld.apiurl + "/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}

dacura.ld.api.viewUpdate = function(id, args){
	xhr = args;
	xhr.url = dacura.ld.apiurl + "/" + id;
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

dacura.ld.api.list = function (type, x){
	xhr = {};
	xhr.data = {"entity_type" : type};
	if(typeof x != "undefined"){
		xhr.data.type = "updates";
	}
	xhr.url = dacura.ld.apiurl;
	return xhr;
}

dacura.ld.fetch = function(id, args, onwards, from){
	var ajs = dacura.ld.api.view(id, args);
	var msgs = { "busy": "Fetching entity " + id + " from Server", "fail": "Failed to retrieve entity " + id};
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		if(typeof(from) == "undefined"){//only call this on the first time it is invoked
			dacura.ld.showHeader(obj);
		}
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs);
}

dacura.ld.fetchupdatelist = function(type, onwards){
	var ajs = dacura.ld.api.list(type, "updates");
	var targets = {resultbox: "#update-msgs", errorbox: "#update-msgs", busybox: "#update-holder"};
	var msgs = { "busy": "Retrieving ld update list from server", "fail": "Failed to retrieve ld update list"};
	ajs.handleResult = dacura.ld.drawUpdateListTable;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.fetchentitylist = function(type){
	var ajs = dacura.ld.api.list(type);
	var targets = {resultbox: "#ld-msgs", errorbox: "#ld-msgs", busybox: "#ld-holder"};
	var msgs = { "busy": "Retrieving ld list from server", "fail": "Failed to retrieve ld list"};
	ajs.handleResult = dacura.ld.drawEntityListTable;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.ld.update = function(id, uobj, onwards, type, test, targets){
	var ajs = dacura.ld.api.update(id, uobj);
	if(typeof test != "undefined" && test){
		msgs = { "busy": "Testing update of " + id + " with Dacura Quality Service", "fail": "Server communication failure in testing update of " + id};
	}
	else {
		var msgs = { "busy": "Updating entity " + id, "fail": "Server communication failure in updating " + id};
	}
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.ld.create = function(data, onwards, istest, targets){
	var ajs = dacura.ld.api.create(data, istest);
	msgs = { "busy": "Submitting new entity to Dacura API", "fail": "Entity Submission was unsuccessful"};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}


