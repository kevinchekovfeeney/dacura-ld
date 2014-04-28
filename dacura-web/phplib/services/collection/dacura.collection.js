dacura.collection = {}
dacura.collection.apiurl = "<?=$service->get_service_url('collection', array(), true)?>";

dacura.collection.api = {};
dacura.collection.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.collection.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.collection.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.collection.apiurl + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.collection.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.collection.apiurl + id;
}


dacura.collection.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.collection.apiurl + id;
	xhr.type = "POST";
	return xhr;
}
