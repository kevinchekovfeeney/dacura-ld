dacura.workflow = {}
dacura.workflow.apiurl = dacura.system.apiURL();

dacura.workflow.api = {};
dacura.workflow.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.workflow.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.workflow.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.workflow.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.workflow.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.workflow.apiurl + "/" + id;
	return xhr;
	}

dacura.workflow.api.listing = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.workflow.apiurl;
	return xhr;

}


dacura.workflow.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.workflow.apiurl + "/" +  id;
	xhr.type = "POST";
	return xhr;
}

