<div class='dacura-widget' id='dacura-widget-reset-password'>
	<div class="dacura-widget-intro">Reset your password</div>
	<table class="dc-dialog">
		<tr class="dc-login-field"><th>New Password</th><td><input class="dc-login-input" id="dacura-login-password" type="password" value=""></td>
		<tr class="dc-login-field"><th>Confirm Password</th><td><input class="dc-login-input" id="dacura-login-password-confirm" type="password" value=""></td>
		<tr><td colspan="2" id="resetbox-status" class="dacura-status"></td></tr>
	</table>
	<div class="dacura-widget-buttons">
		<a class="button cancel-button" href="<?=$dacura_settings['install_url']?>">Cancel</a>
		<a class="button reset-button" id='dacura-reset-password-button' href="javascript:dacura.system.resetpassword()">Reset Password</a>
		<a class="button login-button" id='dacura-login-button' href="<?=$dacura_settings['install_url']?>login">Login</a>
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
dacura.system.resetpassword = function(){
	var ajs = dacura.system.getAjaxSettings('resetpassword');
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(cpass != pass){
		this.writeErrorMessage('#loginbox-status', "Error: passwords do not match");	
		return;
	}
	if(!this.isvalidp(pass)){
		return;
	}
	ajs.data['userid'] = "<?=$context?>";
	ajs.data['login-password'] = pass;
	this.disablereset();
	var self=this;
	ajs.beforeSend = function(){
		self.writeBusyMessage('#resetbox-status', "Updating password");
	};
	ajs.complete = function(){
		self.enablereset();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
	     	if(self.mode == 'local'){
	     		$('.dc-login-field').hide();
	     		$('#dacura-reset-password-button').hide();
	     		$('#dacura-login-button').show();
	     		$('.dacura-widget-intro').hide();
	     		$('.cancel-button').hide();
	     		self.writeSuccessMessage('#resetbox-status', jqXHR.responseText );
	     	}    
		})
		.fail(function (jqXHR, textStatus){
			self.writeErrorMessage('#resetbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};


dacura.system.disablereset = function(){
	$('#dacura-reset-password-button').unbind("click");
	$('#dacura-reset-password-button').click( function(e){
		 e.preventDefault();
	});		
}

dacura.system.enablereset = function(){
	$('#dacura-reset-password-button').unbind("click");		
	$('#dacura-reset-password-button').click( function(e){
		 e.preventDefault();
		 dacura.system.resetpassword();			
	});
}


</script>	
