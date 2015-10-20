dacura.candidate = {}
dacura.candidate.apiurl = dacura.system.apiURL();

dacura.candidate.api = {};


dacura.candidate.api.create = function (data, test){
	var xhr = {};
	xhr.url = dacura.candidate.apiurl 
	//xhr.url = dacura.system.serviceApiURL('ld');
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	if(typeof test != "undefined"){
		data.test = true;
	}
	data.entity_type == "candidate";
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;
}


dacura.candidate.api.update = function (id, data){
	var xhr = {};
	//xhr.url = dacura.system.serviceApiURL('ld') + "/" + id;
	xhr.url = dacura.candidate.apiurl + "/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}

dacura.candidate.api.viewUpdate = function(id, args){
	xhr = args;
	xhr.url = dacura.candidate.apiurl + "/" + id;
	//xhr.url =dacura.system.serviceApiURL('ld') + "/" + id;
	return xhr;
}

dacura.candidate.api.del = function (id){
	xhr = {};
	xhr.data ={};
	//xhr.url = dacura.system.serviceApiURL('ld') + "/" + id;
	xhr.url = dacura.candidate.apiurl + "/" + id;

	xhr.type = "DELETE";
	return xhr;
}

dacura.candidate.api.view = function (id, args){
	xhr = {data: args};
	xhr.data.entity_type = "candidate";
	//xhr.url = dacura.system.serviceApiURL('ld') + "/" + id;
	xhr.url = dacura.candidate.apiurl + "/" + id;

	return xhr;
}

dacura.candidate.api.list = function (x){
	xhr = {};
	xhr.data = {};
	if(typeof x != "undefined"){
	
		xhr.data.type = "updates";
	}
	else {
		xhr.data.type = "candidates";		
	}
	xhr.data.entity_type = "candidate";
	//xhr.url = dacura.system.serviceApiURL('ld');
	xhr.url = dacura.candidate.apiurl;
	return xhr;
}

var ajs = dacura.candidate.api.viewNGSkeleton = function(){
	xhr = {};
	xhr.url = dacura.candidate.apiurl + "/" + "ngskeleton";
	return xhr;
}


dacura.candidate.fetchNGSkeleton = function(onwards, targets){
	var ajs = dacura.candidate.api.viewNGSkeleton();
	var msgs = { "busy": "Fetching NG skeleton from Server", "fail": "Failed to retrieve ng skeleton"};
	ajs.handleResult = function(obj){
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs);
}

dacura.candidate.fetch = function(id, args, onwards, from){
	var ajs = dacura.candidate.api.view(id, args);
	var msgs = { "busy": "Fetching entity " + id + " from Server", "fail": "Failed to retrieve entity " + id};
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		if(typeof(from) == "undefined"){//only call this on the first time it is invoked
			dacura.candidate.showHeader(obj);
		}
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs);
}

dacura.candidate.fetchupdatelist = function(onwards){
	var ajs = dacura.candidate.api.list("updates");
	var msgs = { "busy": "Retrieving candidate update list from server", "fail": "Failed to retrieve candidate update list"};
	ajs.handleResult = dacura.candidate.drawUpdateListTable;
	dacura.system.invoke(ajs, msgs);
}

dacura.candidate.fetchcandidatelist = function(){
	var ajs = dacura.candidate.api.list();
	var msgs = { "busy": "Retrieving candidate list from server", "fail": "Failed to retrieve candidate list"};
	ajs.handleResult = dacura.candidate.drawCandidateListTable;
	dacura.system.invoke(ajs, msgs);
};

dacura.candidate.update = function(id, uobj, onwards, type, test){
	var ajs = dacura.candidate.api.update(id, uobj);
	if(typeof test != "undefined" && test){
		msgs = { "busy": "Testing update of " + id + " with Dacura Quality Service", "fail": "Server communication failure in testing update of " + id};
	}
	else {
		var msgs = { "busy": "Updating entity " + id, "fail": "Server communication failure in updating " + id};
	}
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs);
}

dacura.candidate.create = function(data, onwards, istest, targets){
	var ajs = dacura.candidate.api.create(data, istest);
	msgs = { "busy": "Submitting new candidate to Dacura API", "fail": "Candidate Submission was unsuccessful"};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}


