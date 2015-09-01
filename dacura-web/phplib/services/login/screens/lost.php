<div class='dacura-widget' id='dacura-widget-reset-password'>
	<div class="dacura-widget-title">Password Reset</div>
	<div class="dacura-widget-body">
		<p class="dacura-widget-intro"><?=$params['greeting']?> To reset your password enter a new password in the boxes below.</p>
		<table class="dc-dialog">
			<tr class="dc-lost-field"><th>New Password</th><td><input class="dc-login-input" id="dacura-login-password" type="password" value=""></td>
			<tr class="dc-lost-field"><th>Confirm Password</th><td><input class="dc-login-input" id="dacura-login-password-confirm" type="password" value=""></td>
			<tr><td colspan="2" id="resetbox-status" class="dacura-status"></td></tr>
		</table>
	</div>
	<div class="dacura-widget-buttons">
		<a class="button reset-button" id='dacura-reset-password-button' href="javascript:dacura.login.resetpassword()">Reset Password</a>
		<a class="button" id='dacura-login-button' href="<?=$service->get_service_url("login")?>">Login</a>
	</div>
</div>

<script>
dacura.login.resetpassword = function(){
	var ajs = dacura.login.api.reset();
	$('#resetbox-status').html("");
	var pass = $('#dacura-login-password').val();
	var cpass = $('#dacura-login-password-confirm').val();
	if(cpass != pass){
		dacura.toolbox.writeErrorMessage('#resetbox-status', "Error: passwords do not match");	
		return;
	}
	if(!this.isvalidp(pass)){
		dacura.toolbox.writeErrorMessage('#resetbox-status', "Error: password is invalid");	
		return;
	}
	ajs.data['userid'] = "<?=$params['userid']?>";
	ajs.data['login-password'] = pass;
	this.disablereset();
	var self=this;
	ajs.beforeSend = function(){
		dacura.toolbox.writeBusyOverlay('.dacura-widget', "Updating Password...", {"makeweight" : false});
	};
	ajs.complete = function(){
		dacura.toolbox.removeBusyOverlay("", 0);		
		self.enablereset();
	};
	$.ajax(ajs)
		.done(function(data, textStatus, jqXHR) {
     		$('.dc-lost-field').hide();
     		$('#dacura-reset-password-button').hide();
     		$('#dacura-login-button').show();
			try {
				var msg = JSON.parse(jqXHR.responseText);
				showSuccessPage("Password Successfully Reset", msg);
			}
			catch(e){
				dacura.toolbox.writeErrorMessage('#resetbox-status', "Error: " + e.message );
			}
		})
		.fail(function (jqXHR, textStatus){
			dacura.toolbox.writeErrorMessage('#resetbox-status', "Error: " + jqXHR.responseText );
		}
	);	
};

function showSuccessPage(tit, msg){
	$('.dacura-widget-title').html(tit);
	$('.dacura-widget-body').html(msg);
}

dacura.login.disablereset = function(){
	$('#dacura-reset-password-button').unbind("click");
	$('#dacura-reset-password-button').click( function(e){
		 e.preventDefault();
	});		
};

dacura.login.enablereset = function(){
	$('#dacura-reset-password-button').unbind("click");		
	$('#dacura-reset-password-button').click( function(e){
		 e.preventDefault();
		 dacura.login.resetpassword();			
	});
};
$(function() {
	$('#dacura-login-button').hide();
});
</script>	
