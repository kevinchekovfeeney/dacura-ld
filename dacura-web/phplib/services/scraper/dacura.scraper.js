dacura.scraper = {}
dacura.scraper.apiurl = dacura.system.apiURL();

dacura.scraper.api = {};
dacura.scraper.api.getngalist = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.scraper.apiurl + "/nga";
	return xhr;
}

dacura.scraper.api.getpolities = function (id, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.scraper.apiurl + "/polities";
	xhr.data.nga = id;
	xhr.type = "POST";
	return xhr;
}

dacura.scraper.api.getPolityData = function (nga, polity, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.data.nga = nga;
	xhr.data.polity = polity;
	xhr.url = dacura.scraper.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.scraper.api.parsePage = function(data, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.type = "POST";
	xhr.url = dacura.scraper.apiurl + "/parse";
	return xhr;
}


dacura.scraper.api.dump = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.scraper.apiurl + "/dump";
	xhr.type = "POST";
	return xhr;
}



