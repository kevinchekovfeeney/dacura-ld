/**
 * @file Javascript client code for login service
 * @author Chekov
 * @license GPL V2
 */

 /** 
 * @namespace login
 * @memberof dacura
 * @summary dacura.login
 * @description Dacura javascript login service module. provides client functions for accessing the dacura login api
 */
dacura.login = {}
dacura.login.apiurl = dacura.system.apiURL();

/**
 * @function resetpassword
 * @memberof dacura.login
 * @summary dacura.login.resetpassword
 * @description resets the user's password after they select a new one
 * @param {Number} uid - the user id
 * @param {DacuraPageConfig} pconf - page configuration object with details of where to write messages
 * @param {string} [action] - what user action promted the reset?
  */
dacura.login.resetpassword = function(uid, pconf, action){
	dacura.system.clearResultMessage();
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(cpass != pass){
		dacura.system.showErrorResult("Please try again, making sure that you enter the same password in both boxes", "Error - passwords don't match", pconf.resultbox);	
		return;
	}
	if(!this.isvalidp(pass)){
		dacura.system.showErrorResult("passwords must be at least 6 characters long", "Error - selected password is invalid", pconf.resultbox);	
		return;
	}
	var ajs = dacura.login.api.reset(uid, pass, action);
	var self=this;
	ajs.handleResult = function(msg, pconf){
		dacura.system.showSuccessResult(msg, "Password Successfully Reset", pconf.resultbox);
		$('.dc-dialog').hide();
		$('#dacura-reset-password-button').hide();
		$('#dacura-login-button').show();
	};
	var msgs = {"busy": "Reseting password", "fail": "Password update failed"};
	dacura.system.invoke(ajs, msgs, pconf);
};


/**
 * @function login
 * @memberof dacura.login
 * @summary called to log into system
 * @param {DacuraPageConfig} [ltargets] - communication configuration for the API call
 */
dacura.login.login = function(surl, ltargets){
	dacura.system.clearResultMessage();
	var uname = $('#dacura-login-email').val();
	var pass = $('#dacura-login-password').val();
	if(!this.isvalidup(uname, pass)){
		dacura.system.showErrorResult("Invalid data entered", "User input Error", ltargets.resultbox, false, ltargets.mopts);
		return;
	}
	var ajs = dacura.login.api.login();
	ajs.data['login-email'] = uname;
	ajs.data['login-password'] = pass;
	var msgs = {"busy": "Checking credentials", "fail": "Login attempt failed - please try again"};
	ajs.always = function(){};
	ajs.fail = function(jqXHR){
		dacura.system.clearBusyMessage(ltargets.busybox);
		dacura.system.showErrorResult(jqXHR.responseText, msgs.fail, ltargets.resultbox, false, ltargets.mopts);										
	}
	ajs.handleResult = function(url){
		if(url != ""){
			window.location.replace(url);
		}
		else {
     		window.location.replace(surl);
		}		
	}
	dacura.system.invoke(ajs, msgs, ltargets);
};

/**
 * @function logout
 * @memberof dacura.login
 * @summary called to log out of the system
 * @param {string} lurl- the url to load if logout is successful
 * @param {DacuraPageConfig} [ltargets] - communication configuration for the API call
 */
dacura.login.logout = function(lurl, ltargets){
	var ajs = dacura.login.api.logout();
	var msgs = {"busy": "Signing out"};
	ajs.always = function(){};//leave busy in place
	ajs.done = function(data, textStatus, jqXHR) {
		window.location.replace(lurl);
	};
	ajs.fail = function (jqXHR, textStatus){
		dacura.system.showErrorResult("Failed to log out", jqXHR.responseText );
		dacura.system.clearBusyMessage();
	};
	dacura.system.invoke(ajs, msgs, ltargets);
};

/**
 * @function register
 * @memberof dacura.login
 * @summary called to register new user
 * @param {DacuraPageConfig} [ltargets] - communication configuration for the API call
 */
dacura.login.register = function(ltargets){
	dacura.system.clearResultMessage();
	var uname = $('#dacura-login-email').val();
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(!this.isvalidup(uname, pass)){
		return;
	}
	if(cpass != pass){
		dacura.system.showErrorResult("Registration Error", "Passwords do not match", ltargets.resultbox, false, ltargets.mopts);	
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

/**
 * @function lost
 * @memberof dacura.login
 * @summary called to request new password
 * @param {DacuraPageConfig} [ltargets] - communication configuration for the API call
 */
dacura.login.lost = function(ltargets){
	dacura.system.clearResultMessage();
	var uname = $('#dacura-login-email').val();
	if(!this.isvalidu(uname)){
		return;
	}
	var ajs = dacura.login.api.lost();
	ajs.data['login-email'] = uname;
	var msgs = { "busy": "Requesting password reset...", "fail": "Password reset request failed"};
	ajs.handleResult = function(msg, targets){
		dacura.login.showSuccessPage("Password Rest Process Initiated", msg);				
	};
	dacura.system.invoke(ajs, msgs, ltargets);
};

/**
 * @function isvalidu
 * @memberof dacura.login
 * @summary very basic client side validation of email address 
 * @description just checks that it is at least 3 characters long - all real validation happens at server side
 * @param {string} u email address entered by user
 * @return {Boolean} true if the address is valid
 */
dacura.login.isvalidu = function(u){
	if(u.length < 3){
		return false;
	}
	return true;
};

/**
 * @function isvalidp
 * @memberof dacura.login
 * @summary client side validation of password
 * @description does nothing all password validation happens at server side
 * @param {string} p password entered by user
 * @return {Boolean} true if the password is valid
 */
dacura.login.isvalidp = function(p){
	return true;
};

/**
 * @function isvalidup
 * @memberof dacura.login
 * @summary client side validation of username and password
 * @description just checks minimum email length, all validation happens server-side
 * @param {string} p password entered by user
 * @return {Boolean} true if the password is valid
 */
dacura.login.isvalidup = function(u, p){
	return (this.isvalidu(u) && this.isvalidp(p));
};

/**
 * @function showSuccessPage
 * @memberof dacura.login
 * @summary shows the success page after successfully reseting password or registering
 * @param {string} tit - the message title
 * @param {string} msg - the message body
 */
dacura.login.showSuccessPage = function(tit, msg){
	$('.dacura-widget').html("<div class='dacura-widget-title'>" + tit + "</div><div class='dacura-widget-fullmessage'>" + msg + "</div>");	
};

/** 
 * @namespace api
 * @memberof dacura.login
 * @summary dacura.login.api
 * @description Dacura login core api - each one returns an object with url, type and data set, ready for ajaxing
 */
dacura.login.api = {};

/**
 * @function login
 * @memberof dacura.login.api
 * @summary POST to login
 */
dacura.login.api.login = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.login.apiurl;
	xhr.type = "POST";
	return xhr;
}

/**
 * @function logout
 * @memberof dacura.login.api
 * @summary DELETE to login
 */
dacura.login.api.logout = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.login.apiurl;
	xhr.type = "DELETE";
	return xhr;
}

/**
 * @function register
 * @memberof dacura.login.api
 * @summary POST to login/register
 */
dacura.login.api.register = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.login.apiurl + "/register";
	xhr.type = "POST";
	return xhr;
}

/**
 * @function lost
 * @memberof dacura.login.api
 * @summary POST to login/lost
 */
dacura.login.api.lost = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.login.apiurl + "/lost";
	xhr.type = "POST";
	return xhr;
}

/**
 * @function reset
 * @memberof dacura.login.api
 * @summary POST to login/reset
 * @param {Number} id - user id
 * @param {string} pass - new password
 * @param {string} action - the action [invite,lost,profile] that triggered the password change
 */
dacura.login.api.reset = function (id, pass, action){
	xhr = {};
	xhr.data = { 
		'action': action,
		'userid': id, 
		'login-password': pass
	};
	xhr.url = dacura.login.apiurl + "/reset";
	xhr.type = "POST";
	return xhr;
}
