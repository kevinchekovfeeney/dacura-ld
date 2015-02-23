<?php
if($dacura_server){
	$u = $dacura_server->getUser();
	if($u){
		$n = $u->name;
		$n = ($n) ? $n : $u->email; 		
		?>
		<ul id="user-actions-menubar">
			<li class='first'><?=$n?>
			<ul>
				<li><span class="ui-icon ui-icon-disk"></span>	<a href='<?= $service->get_service_url("login", array("logout"));?>'>Logout</a></li>
			</ul>
			</li>
		</ul>
		<script>
			$(document).ready(function() {
				$('#user-actions-menubar').menu({position: { my: "right top", at: "right bottom" }});
			});
		</script>
<?php }
}


