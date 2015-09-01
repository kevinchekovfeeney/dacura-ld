
dacura.candidates = {}
dacura.candidates.apiurl = "<?=$service->get_service_url('candidates', array(), true)?>";
dacura.candidates.api = {};
dacura.candidates.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidates.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.candidates.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidates.apiurl + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.candidates.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidates.apiurl + id;
}


dacura.candidates.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidates.apiurl + id;
	xhr.type = "POST";
	return xhr;
}
