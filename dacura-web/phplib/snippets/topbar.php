<?php
$install_base = $service->settings['install_url'];
?>
<div id="dacura-header">
<div class="logo"><a href="<?=$install_base?>"><img src="<?=$install_base?>media/images/dacura-logo-simple.png" height=36></a></div>
<?php if(isset($dacura_server) && $dacura_server->userHasRole("admin", "all", "all")) {?>
	<div class="topbar-context">
	<?php $service->renderScreen("available_context", array("type" => "admin"), "core");?>
	</div>
<?php }?>
</div>