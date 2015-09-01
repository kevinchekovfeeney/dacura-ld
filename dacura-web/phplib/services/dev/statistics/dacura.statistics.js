dacura.statistics = {}
dacura.statistics.apiurl = dacura.system.apiURL();
dacura.statistics.api = {};

dacura.statistics.api.generalStats = function (xhr) {
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.statistics.apiurl;
	return xhr;
}

dacura.statistics.api.generalDatedStats = function (startdate, enddate, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.statistics.apiurl + "/" + startdate + "/" + enddate;
	return xhr;
}

dacura.statistics.api.generalUserStats = function (userid, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.statistics.apiurl + "/" + userid;
	return xhr;
}

dacura.statistics.api.generalUserDatedStats = function (startdate, enddate, userid, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.statistics.apiurl + "/" + startdate + "/" + enddate+ "/" + userid;
	return xhr;
}

dacura.statistics.api.detailedUserSession = function (userid, sessionStartTime, xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.statistics.apiurl + "/sessions/" + userid + "/" + sessionStartTime;
	return xhr;
}
