<?php 
/** Main login page
 * 
 * Includes scripts for switching between functions: register, reset password, login
 * @package login/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
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
		<a class="button login-button login" id='dacura-login-button' href="javascript:dacura.login.login('<?=$service->durl()?>', pconf)">Log in</a>
		<a class="button login-button register" id='dacura-register-button' href="javascript:dacura.login.register(pconf)">Register</a>
		<a class="button login-button lost" id='dacura-lost-button' href="javascript:dacura.login.lost(pconf)">Reset password</a>
	</div>
</div>

<script>
var pconf = {
	"resultbox": "#loginbox-status", 
	"busybox": ".dacura-widget", 
	"mopts": {"closeable": false, "icon": true}, 
	"bopts": {busyclass: "small"}
};

dacura.login.showregister = function(){
	dacura.login.loginpagestate = "register";
	$('.dacura-widget-title').html("Register a New Account");
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login-button').hide();
	$('.dc-login-input').val("");
	$('.register').show();	
};

dacura.login.showlost = function(){
	$('.dacura-widget-title').html("Reset Lost Password");
	dacura.login.loginpagestate = "lost";
	$('.dc-login-field').hide(); 
	$('span.loginlink').hide(); 
	$('.login-button').hide();
	$('.lost').show();	
};

dacura.login.showlogin = function(){
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
			dacura.login.login('<?=$service->durl()?>', pconf);
		}
	});
	$('#dacura-login-password-confirm').keypress(function(e) {
		if (dacura.login.loginpagestate == "register" && e.keyCode == $.ui.keyCode.ENTER) {
			dacura.login.register(pconf);
		}
	});
	$('#dacura-login-email').keypress(function(e) {
		if (dacura.login.loginpagestate == "lost" && e.keyCode == $.ui.keyCode.ENTER) {
			dacura.login.lost(pconf);
		}
	});
	dacura.system.init({"targets": pconf});
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
});
</script>
