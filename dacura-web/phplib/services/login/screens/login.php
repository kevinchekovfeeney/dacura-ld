<div class='dacura-widget dch' id='dacura-widget-login'>	
	<div class="dacura-widget-title">Log in to dacura</div>
	<div class='loginlinks'>
			<span class="loginlink register lost" ><a href="javascript:dacura.login.showlogin();">login</a></span><span class="loginlink lost login"><a href="javascript:dacura.login.showregister();" title='Register a new account on Dacura'>register</a></span><span class="loginlink login"></span><span class="loginlink register login"><a href="javascript:javascript:dacura.login.showlost();" title='Rest a lost password'>lost password</a></span>
	</div>
	<div id="loginbox-status" class="dacura-status"></div>			
	<div class="dacura-widget-body">
		<table class="dc-dialog">
			<tr class="dc-login-field login register lost"><th>Email Address</th><td><input class="dc-login-input" id="dacura-login-email" type="text" value=""></td>
			<tr class="dc-login-field login register"><th>Password</th><td><input class="dc-login-input" id="dacura-login-password" type="password" value=""></td>
			<tr class="dc-login-field register"><th>Confirm Password</th><td><input class="dc-login-input" id="dacura-login-password-confirm" type="password" value=""></td>
		</table>
	</div>
	<div class="dacura-widget-buttons">
		<a class="button login-button login" id='dacura-login-button' href="javascript:dacura.login.login()">Log in</a>
		<a class="button login-button register" id='dacura-register-button' href="javascript:dacura.login.register()">Register</a>
		<a class="button login-button lost" id='dacura-lost-button' href="javascript:dacura.login.lost()">Reset password</a>
	</div>
</div>

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


dacura.login.showregister = function(){
	$('#loginbox-status').empty().hide();
	dacura.login.loginpagestate = "register";
	$('.dacura-widget-title').html("Register a New Account");
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login-button').hide();
	$('.dc-login-input').val("");
	$('.register').show();	
};

dacura.login.showlost = function(){
	$('#loginbox-status').empty().hide();
	$('.dacura-widget-title').html("Reset Lost Password");
	dacura.login.loginpagestate = "lost";
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login-button').hide();
	$('.lost').show();	
};

dacura.login.showlogin = function(){
	$('#loginbox-status').empty().hide();
	$('.dacura-widget-title').html("Log in to Dacura");
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
	dacura.system.init({"mode": "widget", 
		"targets": { "errorbox": "#loginbox-status", "resultbox": "#loginbox-status", "busybox": ".dacura-widget"}
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
	
	$('#dacura-widget-login').show();
	//$(document).tooltip();
});
</script>
