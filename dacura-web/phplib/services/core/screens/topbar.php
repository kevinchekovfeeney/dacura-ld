<?php
/**
 * The bar at the top of most dacura pages
 *
 * includes some context information and a link to their profile page
 * @package core/screens
 * @author chekov
 * @copyright GPL v2
*/?>
<div id="dacura-header">
	<div class="dacura-logo">
		<a href="<?=$service->durl()?>" title="Dacura Home"><img src="<?=$service->furl("image", "dacura-logo-simple.png")?>" height="24"></a>
	</div>
	<div class="topbar-context">
		<ul id="utopbar-context">
		<?php foreach($params['context'] as $cbit){ ?>
			<li title="<?=$cbit['name']?>" class="<?=isset($cbit['class']) ? $cbit['class'] : "";?>"><img src="<?=$cbit['icon']?>"> <a href="<?=$cbit['url']?>"><?=$cbit['name']?></a></li>
		<?php } ?>
		</ul>
	</div>
	<div class="topbar-user-context">
		<?php if(isset($params['username'])){?>
			<a href="<?=$params['profileurl']?>">
				<span class="username"  title="<?=$params['activity']?>">
						<img src="<?=$params['usericon']?>" />
						<label><?=$params['username']?></label>
				</span>
			</a>
			<span class="usersettings">
			<a href="<?=$params['logouturl']?>">
				<label>logout</label>
				<img src='<?= $service->furl("image", "buttons/config_icon.png")?>'>
			</a>
			</span>			
			<div class="useroptions dch">
				<ul id="user-actions-menubar">
					<li>
						<span class="ui-icon ui-icon-disk"></span>
						<a href='<?= $service->get_service_url("login", array("logout"));?>'>Logout</a> <?=$params['activity']?>
					</li>					
					<li>
						<select class='dccontextchanger' id='dccollectioncontext'>
						<?php 
							$choices = $dacura_server->getUserAvailableContexts();
							foreach($choices as $i => $choice){
								echo "<option value='$i'"; 
								if($i == $service->cid() or ($i == "all" && !$service->cid())) echo " selected";
								echo ">".$choice['title']."</option>";
							}?>
						</select>
						<input id='dcchangecontext' type='submit' value="go">	
					</li>
				</ul>
			</div>
		<?php }?>
	</div>
</div>
	


<script>
$(document).ready(function() {
	$('.dccontextchanger').change(function(){
		dacura.system.switchContext($('#dccollectioncontext').val());
	});
});	

</script>