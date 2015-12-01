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
		dacura.system.writeErrorMessage("Invalid email address entered", '#loginbox-status');
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
	return true;
};

dacura.login.showSuccessPage = function(tit, msg){
	$('.dacura-widget').html("<div class='dacura-widget-title'>" + tit + "</div><div class='dacura-widget-fullmessage'>" + msg + "</div>");	
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

var ltargets = {busybox: '.dacura-widget', resultbox: '#loginbox-status'};


dacura.login.login = function(){
	$('#loginbox-status').html("").hide();
	var uname = $('#dacura-login-email').val();
	var pass = $('#dacura-login-password').val();
	if(!this.isvalidup(uname, pass)){
		return;
	}
	var ajs = dacura.login.api.login();
	ajs.data['login-email'] = uname;
	ajs.data['login-password'] = pass;
	var msgs = {"busy": "Checking credentials", "fail": "Failed to login"};
	ajs.handleResult = function(url){
		if(url != ""){
			window.location.replace(url);
		}
		else {
     		window.location.replace("<?=$service->settings['install_url']?>");
		}		
	}
	dacura.system.invoke(ajs, msgs, ltargets);
};

dacura.login.register = function(){
	$('#loginbox-status').html("").hide();
	var uname = $('#dacura-login-email').val();
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(!this.isvalidup(uname, pass)){
		return;
	}
	if(cpass != pass){
		dacura.system.writeErrorMessage("Error", "passwords do not match", '#loginbox-status');	
		return;
	}
	var ajs = dacura.login.api.register();
	ajs.data['login-email'] = uname;
	ajs.data['login-password'] = pass;
	ajs.handleResult = function(obj, targets){
		dacura.login.showSuccessPage("Registration successfully initiated", obj);		
	};
	var msgs = { "busy": "Registering new account...", "fail": "Registration failed"};
	dacura.system.invoke(ajs, msgs, ltargets);
};

dacura.login.lost = function(){
	$('#loginbox-status').empty().hide();
	var uname = $('#dacura-login-email').val();
	if(!this.isvalidu(uname)){
		return;
	}
	var ajs = dacura.login.api.lost();
	ajs.data['login-email'] = uname;
	var msgs = { "busy": "Requesting password reset...", "fail": "Password reset request failed"};
	ajs.handleResult = function(obj, targets){
		var msg = JSON.parse(jqXHR.responseText);
		dacura.login.showSuccessPage("Password Rest Process Initiated", msg);				
	};
	dacura.system.invoke(ajs, msgs, ltargets);
};


