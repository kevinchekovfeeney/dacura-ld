dacura.users = {}
dacura.users.apiurl = dacura.system.apiURL();

dacura.users.api = {};
dacura.users.api.create = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.users.api.del = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" + id;
	xhr.type = "DELETE";
	return xhr;
}

dacura.users.api.view = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" + id;
	return xhr;
	}

dacura.users.api.listing = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl;
	return xhr;

}


dacura.users.api.update = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" +  id;
	xhr.type = "POST";
	return xhr;
}


dacura.users.api.delrole = function (uid, rid, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" + uid + "/role/" + rid;
	xhr.type = "DELETE";
	return xhr;
}

dacura.users.api.viewrole = function (uid, rid, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" +  uid + "/role/" + rid;
	return xhr;
}


dacura.users.api.updaterole = function (uid, rid, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" +  uid + "/role/" + rid;
	xhr.type = "POST";
	return xhr;
}

dacura.users.api.createrole = function (uid, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.users.apiurl + "/" + uid + "/role";
	xhr.type = "POST";
	return xhr;
}

dacura.users.api.getRoleOptions = function(uid){
	xhr = {};
	xhr.url = dacura.users.apiurl + "/" + uid + "/roleoptions";
	return xhr;
}

