dacura.config = {};
dacura.config.apiurl = dacura.system.apiURL();

dacura.config.api = {};
dacura.config.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	xhr.type = "POST";
	return xhr;
};

dacura.config.api.del = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	xhr.type = "DELETE";
	return xhr;
};

dacura.config.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	return xhr;
};

dacura.config.api.listing = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	return xhr;
};

dacura.config.api.createCollection = function(id){
	xhr = {};
	xhr.data ={};
	
	xhr.url = dacura.system.apiURL(id, "all", "config") + "/create";
	xhr.type = "POST";
	return xhr;	
}

dacura.config.api.createDataset = function(cid, did){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL(cid, did, "config") + "/create";
	xhr.type = "POST";
	return xhr;	
}

dacura.config.api.getCollection = function(id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL(id, "all", "config");
	return xhr;	
}

dacura.config.api.updateCollection = function(id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL(id, "all", "config");
	xhr.type = "POST";
	return xhr;	
}

dacura.config.api.getDataset = function(cid, did){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL(cid, did, "config");
	return xhr;	
}


dacura.config.api.updateDataset = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	xhr.type = "POST";
	return xhr;
};
