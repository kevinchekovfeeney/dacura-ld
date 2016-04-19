<?php 
/** 
 * Password reset page
 * 
 * Presents the user with two password inputs which must match and submits them to the dacura api
 * @package login/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-widget' id='dacura-widget-reset-password'>
	<div class="dacura-widget-title">Password Reset</div>
	<div class="dacura-widget-body">
		<div id="resetbox-status"></div>
		<table class="dc-dialog">
			<tr class="dc-lost-field"><th>New Password</th><td><input class="dc-login-input" id="dacura-login-password" type="password" value=""></td>
			<tr class="dc-lost-field"><th>Confirm Password</th><td><input class="dc-login-input" id="dacura-login-password-confirm" type="password" value=""></td>
		</table>
	</div>
	<div class="dacura-widget-buttons">
		<a class="button reset-button" id='dacura-reset-password-button' href="#">Reset Password</a>
		<a class="button" id='dacura-login-button' href="<?=$service->my_url()?>">Login</a>
	</div>
</div>

<script>

$(function() {
	var pconf = {
			"resultbox": "#resetbox-status", 
			"busybox": ".dacura-widget", 
			"mopts": {"closeable": false, "icon": true}, 
			"bopts": {busyclass: "small"},
	};
	$('#dacura-login-password-confirm').keypress(function(e) {
		if (e.keyCode == $.ui.keyCode.ENTER) {
			dacura.login.resetpassword('<?=$params['userid']?>', pconf, "lost");
		}
	});
	$('#dacura-reset-password-button').click(function(e){
		e.preventDefault();
		dacura.login.resetpassword('<?=$params['userid']?>', pconf, "lost");
	});		
	$('#dacura-login-button').hide();
	dacura.system.showInfoResult('<?=$params['reset_instruction']?>', '<?=$params['greeting']?>', pconf.resultbox, false, {closeable:false, icon:true});
});
</script>	
