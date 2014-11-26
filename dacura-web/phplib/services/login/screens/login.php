<div class='dacura-widget' id='dacura-widget-login'>
	<div class="dacura-widget-intro">Log in to dacura</div>
	<table class="dc-dialog">
		<tr class="dc-login-field login register lost"><th>Email Address</th><td><input class="dc-login-input" id="dacura-login-email" type="text" value=""></td>
		<tr class="dc-login-field login register"><th>Password</th><td><input class="dc-login-input" id="dacura-login-password" type="password" value=""></td>
		<tr class="dc-login-field register"><th>Confirm Password</th><td><input class="dc-login-input" id="dacura-login-password-confirm" type="password" value=""></td>
		<tr><td colspan=2>
			<div class='loginlinks'>
			<span class="loginlink register lost" ><a href="javascript:dacura.login.showlogin();">login</a> | </span>
			<span class="loginlink lost login"><a href="javascript:dacura.login.showregister();">register</a> </span><span class="loginlink login">|</span>
			<span class="loginlink register login"><a href="javascript:javascript:dacura.login.showlost();">lost password</a></span></div>
		</td></tr>
		<tr><td colspan="2" id="loginbox-status" class="dacura-status"></td></tr>
	</table>
	<a class="button" href="<?=$service->settings['install_url']?>">Cancel</a>
	<a class="button login-button login" id='dacura-login-button' href="javascript:dacura.login.login()">Log in</a>
	<a class="button login-button register" id='dacura-register-button' href="javascript:dacura.login.register()">Register</a>
	<a class="button login-button lost" id='dacura-lost-button' href="javascript:dacura.login.lost()">Reset password</a>
</div>

	<style>
    #dacura-widget-login {
		width: 460px;
		margin: 60px auto 10px auto;
		color: white;
	}

	#dacura-widget-login .dacura-widget-intro {		
	}
	
	
	.dc-login-field{ 
		display: none;
	}
	
	#dacura-widget-login table{
		color: white;
		width: 90%;
		margin: 6px auto 10px auto;
	}

	#dacura-widget-login th {
		text-align: right;
		width: 34%;
		font-size: 18px;
	}
    
	#dacura-widget-login td {
		text-align: left;
		padding: 6px 2px 2px 8px;	
	}
	
	#dacura-widget-login td input {
		width: 100%;
		padding: 4px 2px;
		font-size: 18px;
		border-radius: 4px;	
	}
	div.loginlinks {
		text-align: right;
		margin-top: -10px;
		margin-right: -8px;
	}
	span.loginlink a {
		color: white;
		text-decoration: none;
		font-size: 0.9em;
	}
	span.loginlink a:hover {
		color: white;
		text-decoration: underline;
		font-size: 0.9em;
	}
	td#loginbox-status {
		border-radius: 2px; 
		display: none;
		padding-top: 12px;
		padding-bottom: 12px;
	}
	
	</style>
<script>

dacura.login.disablelogin = function(){
	$('#dacura-login-button').unbind("click");
	$('#dacura-login-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.login.enablelogin = function(){
	$('#dacura-login-button').unbind("click");		
	$('#dacura-login-button').click( function(e){
		 e.preventDefault();
		 dacura.login.login();			
	});
}

dacura.login.disableregister = function(){
	$('#dacura-register-button').unbind("click");
	$('#dacura-register-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.login.enableregister = function(){
	$('#dacura-register-button').unbind("click");		
	$('#dacura-register-button').click( function(e){
		 e.preventDefault();
		 dacura.login.register();			
	});
}

dacura.login.disablelost = function(){
	$('#dacura-lost-button').unbind("click");
	$('#dacura-lost-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.login.enablelost = function(){
	$('#dacura-lost-button').unbind("click");		
	$('#dacura-lost-button').click( function(e){
		 e.preventDefault();
		 dacura.login.lost();			
	});
}




dacura.login.login = function(){
	$('#loginbox-status').empty().hide();
	var uname = $('#dacura-login-email').val();
	var pass = $('#dacura-login-password').val();
	if(!this.isvalidup(uname, pass)){
		return;
	}
	var ajs = dacura.login.api.login();
	ajs.data['login-email'] = uname;
	ajs.data['login-password'] = pass;

	this.disablelogin();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('#loginbox-status', "Checking credentials...");
	};
	ajs.complete = function(){
		self.enablelogin();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
	     	if(self.mode == 'local'){
				if(data != ""){
					window.location.replace(data);
				}
				else {
		     		//window.location.replace("<?=$service->settings['install_url']?>");
				}
			}    
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.login.register = function(){
	$('#loginbox-status').empty().hide();
	var uname = $('#dacura-login-email').val();
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(!this.isvalidup(uname, pass)){
		return;
	}
	if(cpass != pass){
		dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: passwords do not match");	
		return;
	}
	var ajs = dacura.login.api.register();//getAjaxSettings('register');
	ajs.data['login-email'] = uname;
	ajs.data['login-password'] = pass;

	this.disableregister();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('#loginbox-status', "Registering new account...");
	};
	ajs.complete = function(){
		self.enableregister();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			$('#maincontrol').empty();
			dacura.toolbox.showSuccessPage('#maincontrol', jqXHR.responseText);
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.login.lost = function(){
	$('#loginbox-status').empty().hide();
	var uname = $('#dacura-login-email').val();
	if(!this.isvalidu(uname)){
		return;
	}
	var ajs = dacura.login.api.lost();//getAjaxSettings('register');
	ajs.data['login-email'] = uname;
	this.disablelost();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('#loginbox-status', "Requesting password reset...");
	};
	ajs.complete = function(){
		self.enablelost();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
			$('#maincontrol').empty("fade");
			dacura.toolbox.showSuccessPage('#maincontrol', jqXHR.responseText);
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};

dacura.login.showregister = function(){
	$('#loginbox-status').empty().hide();
	dacura.login.loginpagestate = "register";
	$('.dacura-widget-intro').html("Register a new account on the Dacura system");
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login-button').hide();
	$('.dc-login-input').val("");
	$('.register').show();	
};

dacura.login.showlost = function(){
	$('#loginbox-status').empty().hide();
	$('.dacura-widget-intro').html("Enter your email address in the field below and we will email you a new password.");
	dacura.login.loginpagestate = "lost";
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login-button').hide();
	$('.lost').show();	
};

dacura.login.showlogin = function(){
	$('#loginbox-status').empty().hide();
	$('.dacura-widget-intro').html("Log in to Dacura");
	dacura.login.loginpagestate = "login";
	$('.login-button').hide();
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login').show();
}




$(function() {
	$('#dacura-login-password').keypress(function(e) {
		if (dacura.login.loginpagestate == "login" && e.keyCode == $.ui.keyCode.ENTER) {
			dacura.login.login();
		}
	});
	$('#dacura-login-password-confirm').keypress(function(e) {
		if (dacura.login.loginpagestate == "register" && e.keyCode == $.ui.keyCode.ENTER) {
			dacura.login.register();
		}
	});
	$('#dacura-login-email').keypress(function(e) {
		if (dacura.login.loginpagestate == "lost" && e.keyCode == $.ui.keyCode.ENTER) {
			dacura.login.lost();
		}
	});
	<?php 
	if(isset($params['active_function']) && $params['active_function'] == 'register'){
		echo "dacura.login.showregister();";
	} 
	elseif(isset($params['active_function']) && $params['active_function'] == 'lost'){
		echo "dacura.login.showlost();";
	}
	else echo "dacura.login.showlogin();";
	?>
	
});
</script>
