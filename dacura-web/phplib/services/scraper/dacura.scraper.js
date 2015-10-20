/*
 * Javascript client code for scraper
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

dacura.scraper = {}
dacura.scraper.apiurl = dacura.system.apiURL();

dacura.scraper.api = {};
dacura.scraper.api.getngalist = function (refresh, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.scraper.apiurl + "/nga";
	if(typeof refresh != "undefined" && refresh == true){
		xhr.data["refresh"] = true;
	}
	return xhr;
}

dacura.scraper.api.getstatus = function (refresh, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.scraper.apiurl + "/status";
	if(typeof refresh != "undefined" && refresh == true){
		xhr.data["refresh"] = true;
	}
	return xhr;
}

dacura.scraper.api.updatestatus = function (nga, oncomp, onmessage, onerror){
	args = {"nga" : nga};
	dacura.system.slowAjax(dacura.scraper.apiurl + "/status", "POST", args, oncomp, onmessage, onerror);
}

dacura.scraper.api.getpolities = function (id, refresh, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.scraper.apiurl + "/polities";
	if(typeof refresh != "undefined" && refresh == true){
		xhr.data["refresh"] = true;
	}
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

dacura.scraper.api.parsePage = function(page, refresh, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	if(typeof refresh != "undefined" && refresh == true){
		xhr.data["refresh"] = true;
	}
	xhr.type = "POST";
	xhr.data.url = page;
	xhr.url = dacura.scraper.apiurl + "/parsepage";
	return xhr;
}

dacura.scraper.abortrebuild = function(){
	dacura.system.abortSlowAjax();
}

dacura.scraper.abortdump = function(){
	dacura.system.abortSlowAjax();
}

dacura.scraper.dump = function (args, oncomp, onmessage, onerror){
	dacura.system.slowAjax(dacura.scraper.apiurl + "/dump", "POST", args, oncomp, onmessage, onerror);
}

dacura.scraper.status = function (args, oncomp, onmessage, onerror){
	//alert("status");
	dacura.system.slowAjax(dacura.scraper.apiurl + "/status", "GET", args, oncomp, onmessage, onerror);
}


dacura.scraper.api.parseValue = function(data, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.type = "POST";
	xhr.url = dacura.scraper.apiurl + "/parse";
	return xhr;
}

dacura.scraper.tidyNGAString = function(contents){
	contents = contents.replace('http://seshat.info/', '');
	var pattern = "_";
    re = new RegExp(pattern, "g");
	contents = contents.replace(re, ' ');
	contents = contents.replace('Select All', '');
	return contents;
}

dacura.scraper.parsePolityString = function(str){
	if(typeof(str) == "undefined"){
		str = "";
	}
	p_details = {
		url: str,
		shorturl: str.substr(0,40),
		polityname: "", 
		period: ""	
	};
	str = str.replace('http://seshat.info/', '');
	re = new RegExp("_", "g");
	str = str.replace(re, ' ');
	re = new RegExp("^([^\(\)]*)([^\)]*)");
	res =  re.exec(str);
	p_details.polityname = res[1]; 
	p_details.period = res[2].substr(1);
	return p_details;
}

