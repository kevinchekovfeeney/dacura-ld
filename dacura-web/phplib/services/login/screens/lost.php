<div class='dacura-widget' id='dacura-widget-reset-password'>
	<div class="dacura-widget-intro"><?=$params['greeting']?> Reset your password: enter a new password in the boxes below.</div>
	<table class="dc-dialog">
		<tr class="dc-login-field"><th>New Password</th><td><input class="dc-login-input" id="dacura-login-password" type="password" value=""></td>
		<tr class="dc-login-field"><th>Confirm Password</th><td><input class="dc-login-input" id="dacura-login-password-confirm" type="password" value=""></td>
		<tr><td colspan="2" id="resetbox-status" class="dacura-status"></td></tr>
	</table>
	<div class="dacura-widget-buttons">
		<a class="button cancel-button" href="<?=$service->settings['install_url']?>">Cancel</a>
		<a class="button reset-button" id='dacura-reset-password-button' href="javascript:dacura.login.resetpassword()">Reset Password</a>
		<a class="button login-button" id='dacura-login-button' href="<?=$service->get_service_url("login")?>">Login</a>
	</div>
</div>
	<style>
	#dacura-login-button {
	 display: none;
	}
	
    #dacura-widget-reset-password {
		width: 460px;
		margin: 60px auto 10px auto;
		color: white;
	}

	#dacura-widget-reset-password .dacura-widget-intro {		
	}
	
	
	#dacura-widget-reset-password table{
		color: white;
		width: 90%;
		margin: 6px auto 10px auto;
	}

	#dacura-widget-reset-password th {
		text-align: right;
		width: 34%;
		font-size: 18px;
	}
    
	#dacura-widget-reset-password td {
		text-align: left;
		padding: 6px 2px 2px 8px;	
	}
	
	#dacura-widget-reset-password td input {
		width: 100%;
		padding: 4px 2px;
		font-size: 18px;
		border-radius: 4px;	
	}

	td#resetbox-status {
		border-radius: 2px; 
		display: none;
		padding-top: 12px;
		padding-bottom: 12px;
	}
	
	</style>

<script>
dacura.login.resetpassword = function(){
	var ajs = dacura.login.api.reset();
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(cpass != pass){
		dacura.toolbox.writeErrorMessage('#loginbox-status', "Error: passwords do not match");	
		return;
	}
	if(!this.isvalidp(pass)){
		return;
	}
	ajs.data['userid'] = "<?=$params['userid']?>";
	ajs.data['login-password'] = pass;
	this.disablereset();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyMessage('#resetbox-status', "Updating password");
	};
	ajs.complete = function(){
		self.enablereset();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
     		$('.dc-login-field').hide();
     		$('#dacura-reset-password-button').hide();
     		$('#dacura-login-button').show();
     		$('.dacura-widget-intro').hide();
     		$('.cancel-button').hide();
     		dacura.toolbox.writeSuccessMessage('#resetbox-status', jqXHR.responseText );
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#resetbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};


dacura.login.disablereset = function(){
	$('#dacura-reset-password-button').unbind("click");
	$('#dacura-reset-password-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.login.enablereset = function(){
	$('#dacura-reset-password-button').unbind("click");		
	$('#dacura-reset-password-button').click( function(e){
		 e.preventDefault();
		 dacura.login.resetpassword();			
	});
}


</script>	
