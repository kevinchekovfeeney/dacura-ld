dacura.dataset = {}
dacura.dataset.apiurl = "<?=$service->get_service_url('dataset', array(), true)?>";

dacura.dataset.api = {};
dacura.dataset.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.dataset.apiurl;
	xhr.type = "POST";
	return xhr;
};

dacura.dataset.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.dataset.apiurl + id;
	xhr.type = "DELETE";
	return xhr;
};

dacura.dataset.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.dataset.apiurl + id;
};


dacura.dataset.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.dataset.apiurl + id;
	xhr.type = "POST";
	return xhr;
};
