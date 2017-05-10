<div id='dacura-console'> 
<div class='login-console'>
	<div class='console-branding'><img height='24' src='images/dacura-logo-simple.png'></div>
	<div class='console-user'></div>
</div>
</div>
<script>
var params = <?=json_encode($params)?>;

var loginconsole = {};
loginconsole.showLoginBox = function(){
	var html = "<span class='login-topbar'>email: <input class='login-email' type='text'> password: <input class='login-pass' type='password'> <button class='logingo'>Login</button></span>";
	$('#dacura-console .console-user').html(html);
	$('.logingo').button().click(function(){
		var pass = $('.login-topbar .login-pass').val();
		var email = $('.login-topbar .login-email').val();
		xhr = {};
		xhr.xhrFields = {
		    withCredentials: true
		};
		xhr.data ={};
		xhr.url = params.loginurl;
		xhr.type = "POST";
		xhr.data['login-email'] = email;
		xhr.data['login-password'] = pass;
		$.ajax(xhr).done(function(response, textStatus, jqXHR) {
			loginconsole.reload();
		}).fail(function (jqXHR, textStatus, errorThrown){
			//if(jqXHR.responseText && jqXHR.responseText.length > 0){
			alert("login failed");
		});
	});
}

loginconsole.reload = function(){
	xhr = {};
	xhr.url = params.homeurl;	
	xhr.xhrFields = {
	    withCredentials: true
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		$("body").append(response);
	})
	.fail(function(response){
		alert(response);
	});
}


loginconsole.showLoginBox();
</script>