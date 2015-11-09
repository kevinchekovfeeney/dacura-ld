<div class='dacura-widget' id='dacura-widget-logout'>
	<div class="dacura-widget-title">Logout of Dacura</div>
	<div id="logoutbox-status" class="dacura-status"></div>
	<div class="dacura-widget-body">
		<p>You are currently logged into Dacura as 
		<strong><?=$params['username']?></strong>.</p>
	</div>
	<div class="dacura-widget-buttons">
		<a class="button logout-button" id='dacura-logout-button' href="javascript:dacura.login.logout()">Logout</a>
	</div>
</div>
<script>
dacura.login.logout = function(){
	var ajs = dacura.login.api.logout();//getAjaxSettings('register');
	this.disablelogout();
	var msgs = {"busy": "Signing out"};
	var targets = {busybox: '.dacura-widget', resultbox: '#logoutbox-status'};
	ajs.always = function(){
		dacura.login.enablelogout();
	};
	ajs.done = function(data, textStatus, jqXHR) {
   		window.location.replace("<?=$service->get_service_url("login")?>");
	};
	ajs.fail = function (jqXHR, textStatus){
		dacura.system.showErrorResult("Failed to log out", jqXHR.responseText );
		dacura.system.clearBusyMessage();
	};
	dacura.system.invoke(ajs, msgs, targets);
};


dacura.login.disablelogout = function(){
	$('#dacura-logout-button').unbind("click");
	$('#dacura-logout-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.login.enablelogout = function(){
	$('#dacura-logout-button').unbind("click");		
	$('#dacura-logout-button').click( function(e){
		 e.preventDefault();
		 dacura.login.logout();			
	});
}

<?php if($params['execute']) echo "dacura.login.logout();" ?>

</script>	
