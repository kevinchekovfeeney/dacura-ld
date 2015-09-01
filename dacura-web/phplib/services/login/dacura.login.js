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
	if(p.length < 3){
		dacura.system.writeErrorMessage("Invalid password entered. Your password must be at least 8 characters long", '#loginbox-status');
		return false;
	}
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
	dacura.login.disablelogin();
	var msgs = {"busy": "Checking credentials", "fail": "Failed to login"};
	ajs.always = function(){
		dacura.login.enablelogin();
	};
	ajs.handleResult = function(url){
		if(url != ""){
			window.location.replace(url);
		}
		else {
     		window.location.replace("<?=$service->settings['install_url']?>");
		}		
	}
	ajs.fail = function (jqXHR, textStatus){
		dacura.system.showErrorResult(jqXHR.responseText );
		dacura.system.clearBusyMessage();
	};
	dacura.system.invoke(ajs, msgs);
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
	ajs.handleResult = function(obj){
		dacura.login.showSuccessPage("Registration successfully initiated", obj);		
	};
	ajs.always = function(){
		dacura.system.clearBusyMessage();
		dacura.login.disableregister();
	};
	var msgs = { "busy": "Registering new account...", "fail": "Registration failed"};
	var targets = {busybox: '.dacura-widget', errorbox: '#loginbox-status', resultbox: '#loginbox-status'};
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.login.lost = function(){
	$('#loginbox-status').empty().hide();
	var uname = $('#dacura-login-email').val();
	if(!this.isvalidu(uname)){
		return;
	}
	var ajs = dacura.login.api.lost();
	ajs.data['login-email'] = uname;
	this.disablelost();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyOverlay('.dacura-widget', "Requesting password reset...", {"makeweight" : false});
	};
	ajs.complete = function(){
		self.enablelost();
		dacura.toolbox.removeBusyOverlay("", 0);		
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			try {
				var msg = JSON.parse(jqXHR.responseText);
				dacura.login.showSuccessPage("Password Rest Process Initiated", msg);				
			}
			catch(e){
				dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: " + e.message );
			}
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};


