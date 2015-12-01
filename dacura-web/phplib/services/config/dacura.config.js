dacura.config = {};
dacura.config.apiurl = dacura.system.apiURL();

dacura.config.api = {};
dacura.config.api.create = function (data){
	xhr = {"data": data};
	xhr.url = dacura.config.apiurl;
	xhr.type = "POST";
	return xhr;
};

dacura.config.api.del = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl;
	xhr.type = "DELETE";
	return xhr;
};

dacura.config.api.view = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl;
	return xhr;
};

dacura.config.api.listing = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl;
	return xhr;
};

dacura.config.api.createCollection = function(data){
	xhr = {};
	xhr.data = data;
	xhr.url = dacura.system.apiURL(data.id, "all", "config") + "/create";
	xhr.type = "POST";
	return xhr;	
}

dacura.config.api.deleteCollection = function (id){
	xhr = {};
	xhr.url = dacura.system.apiURL(id, "all", "config");
	xhr.type = "DELETE";
	return xhr;
};


dacura.config.api.getlogs = function(opts){
	xhr = {};
	xhr.data = opts;
	xhr.url = dacura.system.apiURL("all", "all", "config") + "/logs";
	return xhr;	
}


dacura.config.api.getCollection = function(id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL(id, "all", "config");
	return xhr;	
}

dacura.config.api.updateCollection = function(id, data){
	var xhr = {};
	xhr.url = dacura.system.apiURL(id, "all", "config");
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}

dacura.config.fetchCollection = function(id, onwards, targets){
	var ajs = dacura.config.api.getCollection(id);
	var msgs = {"busy": "Retrieving collection " + id + " configuration from server", "fail": "Failed to retrive configuration of collection "+ id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.config.getCollections = function(onwards, targets){
	var ajs = dacura.config.api.listing();
	var msgs = {"busy": "Retrieving list of collections on system", "fail": "Failed to retrive list of collections on server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.updateCollection = function(data, onwards, targets){
	var ajs = dacura.config.api.updateCollection(data.id, data);
	var msgs = {"busy": "Updating collection settings", "fail": "Failed to update collection " + data.id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.deleteCollection = function(id, onwards, targets){
	var ajs = dacura.config.api.deleteCollection(id);
	var msgs = {"busy": "Deleting collection " + id, "fail": "Failed to delete collection " + id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.addCollection = function(data, onwards, targets){
	var ajs = dacura.config.api.createCollection(data);
	var msgs = {"busy": "Creating new collection", "fail": "Failed to create collection " + data.id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.getLogs = function(onwards, targets){
	var ajs = dacura.config.api.getlogs(targets.opts);
	var msgs = {"busy": "Retrieving logs from server", "fail": "Failed to retrive logs from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);	
}
