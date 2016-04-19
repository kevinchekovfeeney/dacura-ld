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
		<a href="<?=$service->durl()?>" title="Dacura Home"><img src="<?=$service->furl("images", "system/dacura-logo-simple.png")?>" height="24"></a>
	</div>
	<div class="topbar-context">
		<ul id="utopbar-context">
		<?php foreach($params['context'] as $n => $cbit){ 
			if($n == count($params['context']) - 1) $cbit['class'] .= " ucontext-active";
			?>
			<li title="<?=$cbit['name']?>" class="<?=isset($cbit['class']) ? $cbit['class'] : "";?>"><img class='topbar-icon' src="<?=$cbit['icon']?>"> <a href="<?=$cbit['url']?>"><?=$cbit['name']?></a></li>
		<?php } ?>
		</ul>
	</div>
	<?php if(isset($params['username'])){?>
	<div class="topbar-user-context">
		<a href="<?=$params['profileurl']?>">
			<span class="username">
				<img class='uicon' src="<?=$params['usericon']?>" />
				<label id='uname'> <?=$params['username']?></label>
				<img class='uconfigicon' src='<?= $service->furl("images", "services/config_icon.png")?>'>
			</span>
		</a>
		<div class='topbar-user-menu dch'>
			<ul id="user-actions-menubar">
				<li>
					<span class="ui-icon ui-icon-disk"></span>
					<a href='<?= $service->get_service_url("login", array("logout"));?>'>Logout</a> 
				</li>					
				<?php $choices = $dacura_server->getUserAvailableContexts(); if(count($choices) > 1){?>
				<li>--</li>
				<?php foreach($choices as $i => $choice){ ?>
					<?php if($i == $service->cid() or ($i == "all" && !$service->cid())){?>
					 	<li class='ui-state-disabled'><?php echo $choice['title']?></li>
					 <?php } else { ?>
					 	<li><a href="javascript:dacura.system.switchContext('<?=$i?>')"><?php echo $choice['title']?></a></li>
				<?php }}}?>
			</ul>
		</div>
	</div>
	<?php } elseif(false) {?>
	<div class="topbar-user-login">
		<label for='uname'>email</label>
		<input name='uname' type='text' id='topbar-username'>
		<label for='upass'>password</label>
		<input type='password' id='topbar-password'>
		<a href="#" class='topbarlogin'>login</a>
	</div>
	<?php } else {?>
		<div class="topbar-user-login">
			<a href="<?php echo $service->get_service_url("login")?>" class='topbar-goto-login'>login</a>	
		</div>
	<?php }?>
	
</div>
	


<script>
$(document).ready(function() {
	$('#user-actions-menubar').menu({
		  icons: { submenu: "ui-icon-circle-triangle-w" }
	});
	$('.topbar-user-context').hover(
    	function(){ 
        	$(this).addClass('ui-state-focus');
        	$('.topbar-user-menu').show("blind");
        	$('#user-actions-menubar').menu("refresh"); 
        },
    	function(){ 
        	$(this).removeClass('ui-state-focus'); 
        	$('.topbar-user-menu').hide("fade");
        }
    );
	$('button.topbarlogin').button().click(function(){
		alert($('#topbar-password').val() + $('#topbar-username').val());
	});
});	

</script>