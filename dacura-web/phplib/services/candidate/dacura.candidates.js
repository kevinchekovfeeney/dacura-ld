
dacura.candidate = {}
dacura.candidate.apiurl = "<?=$service->get_service_url('candidates', array(), true)?>";
dacura.candidate.api = {};
dacura.candidate.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.candidate.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.candidate.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + id;
}


dacura.candidate.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.candidate.apiurl + id;
	xhr.type = "POST";
	return xhr;
}
