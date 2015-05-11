dacura.candidate = {}
dacura.candidate.apiurl = dacura.system.apiURL();

dacura.candidate.api = {};


dacura.candidate.api.create = function (data){
	var xhr = {};
	xhr.url = dacura.candidate.apiurl;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;
}

dacura.candidate.api.update = function (id, data){
	var xhr = {};
	xhr.url = dacura.candidate.apiurl + "/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
    xhr.dataType = "json";
    return xhr;	
}

dacura.candidate.api.del = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.candidate.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.candidate.api.view = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.candidate.apiurl + "/" + id;
	return xhr;
}

dacura.candidate.api.list = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.candidate.apiurl;
	return xhr;
}



