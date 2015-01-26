/*
 * Javascript client code for login service
 *
 * Created By: Chekov
 * Contributors:
 * Creation Date: 12/01/2015
 * Licence: GPL v2
 */

dacura.login = {}
dacura.login.apiurl = dacura.system.apiURL();


/*
 * Some helper functions
 */
dacura.login.isvalidu = function(u){
	if(u.length < 3){
		dacura.toolbox.writeErrorMessage('#loginbox-status', "Invalid email address entered");
		return false;
	}
	return true;
};


dacura.login.isvalidup = function(u, p){
	if(!this.isvalidu(u)){
		return false;
	}
	if(!this.isvalidp(p)){
		return false;
	}
	return true;
};

dacura.login.isvalidp = function(p){
	if(p.length < 3){
		dacura.toolbox.writeErrorMessage('#loginbox-status', "Invalid password entered. Your password must be at least 8 characters long");
		return false;
	}
	return true;
};


/*
 * Api access
 */
dacura.login.api = {};
dacura.login.api.login = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.login.apiurl;
	xhr.type = "POST";
	return xhr;
}

dacura.login.api.logout = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.login.apiurl;
	xhr.type = "DELETE";
	return xhr;
}

dacura.login.api.register = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.login.apiurl + "/register";
	xhr.type = "POST";
	return xhr;
}

dacura.login.api.lost = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.login.apiurl + "/lost";
	xhr.type = "POST";
	return xhr;
}

dacura.login.api.reset = function (xhr){
	if(typeof xhr == "undefined"){
		xhr = {};
		xhr.data ={};
	}
	xhr.url = dacura.login.apiurl + "/reset";
	xhr.type = "POST";
	return xhr;
}

