<?php 
/** Error screen for login
 * 
 * The login service has its own error screens and does not use those from core because the user isn't logged in and we don't 
 * want to load the system until they are.
 * @package login/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-widget dacura-error' id='dacura-widget-error'>	
	<div class="dacura-widget-title"><?=$params['title']?></div>
	<div class="dacura-widget-body">
		<?=$params['message']?>
		<?php if(isset($params['showlogin']) && $params['showlogin']){?>
			<p><?=$params['showlogin']?></p>
			<div class="dacura-widget-buttons">
				<a class="button2 login-button" id='dacura-login-button' href="<?=$service->my_url()?>">Login</a>
			</div>
		<?php } ?>
	</div>
</div>
