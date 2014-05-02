dacura.config = {}
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
}

dacura.config.api.del = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	xhr.type = "DELETE";
	return xhr;
}

dacura.config.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	return xhr;
	}

dacura.config.api.listing = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	return xhr;

}


dacura.config.api.update = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.config.apiurl;
	xhr.type = "POST";
	return xhr;
}
