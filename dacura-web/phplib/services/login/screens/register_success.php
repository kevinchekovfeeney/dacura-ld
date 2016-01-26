<?php 
/** Success screen for registration
 * 
 * Screen to show to user when they successfully complete registration 
 * @package login/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-widget' id='dacura-widget-success'>	
	<div class="dacura-widget-title">Registration Successful</div>
	<div class='dacura-widget-fullmessage'>
		<P>Welcome to dacura! You have completed the registration process.</P> 
		<p>You can now go ahead and log in to Dacura.<p>
		<div class="dacura-widget-buttons">
			<a class="button2 login-button" id='dacura-login-button' href="<?=$service->get_service_url("login")?>">Login</a>
		</div>
	</div>
</div>
</div>