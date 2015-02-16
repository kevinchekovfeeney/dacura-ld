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

dacura.scraper.abortdump = function(){
	dacura.toolbox.abortSlowAjax();
}

dacura.scraper.dump = function (args, oncomp, onmessage, onerror){
	//dacura.toolbox.slowAjax(dacura.scraper.apiurl + "/dump", "POST", args, oncomp, onmessage, onerror);
	dacura.toolbox.slowAjax(dacura.scraper.apiurl + "/dump", "POST", args, oncomp, onmessage);
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

