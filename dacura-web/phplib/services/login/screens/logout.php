<?php 
/** Logout page
 * 
 * If $params['exexute'] is set, the user will be logged out automatically, 
 * otherwise they will be presented with a button giving them the option of doing so. 
 * @package login/screens
 * @author chekov
 * @copyright GPL v2
 */
?>
<div class='dacura-widget' id='dacura-widget-logout'>
	<div class="dacura-widget-title">Logout of Dacura</div>
	<div id="logoutbox-status" class="dacura-status"></div>
	<div class="dacura-widget-body">
		<p>You are currently logged into Dacura as 
		<strong><?=$params['username']?></strong>.</p>
	</div>
	<div class="dacura-widget-buttons">
		<a class="button logout-button" id='dacura-logout-button' href="javascript:dacura.login.logout('<?=$service->my_url()?>', pconf)">Logout</a>
	</div>
</div>
<script>
var pconf = {
	"resultbox": "#loginbox-status", 
	"busybox": ".dacura-widget", 
	"mopts": {"closeable": false, "icon": true}, 
	"bopts": {busyclass: "small"}};

<?php if($params['execute']) echo "dacura.login.logout('".$service->my_url()."', pconf);" ?>

</script>	
