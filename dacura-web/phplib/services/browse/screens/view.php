<div id='dashboard-container'>
	<table id='dashboard'>
		<tr class='dashboard-header'><td colspan="2" id='dashboard-header'></td></tr>
		<tr>
			<td id='dashboard-menu'>
				<div id="dashboard-menu-logo">
					<img src="<?=$params['collection_logo']['img']?>" title="<?=$params['collection_logo']['title']?>">
				</div>
				<div id="dashboard-menu-title">
					<?=$params['collection_logo']['title']?>
					<div class='control-panel-title'>Control Panel</div>
				</div>
				<div id="dashboard-menu-blurb">
					<?=$params['collection_blurb']?>
				</div>
				<ul class='dashboard-menu'>
					<?php foreach($params['menu_items'] as $mi) {?>
					<li id="<?=$mi["id"]?>" class="dashboard-menu-item <?php if($mi['selected']) echo " dashboard-menu-selected"?>">
						<a href="<?=$mi['link']?>" title="<?=$mi['contents']?>"><?=$mi['contents']?></a>
					</li>						
					<?php }?>
				</UL>
				<div id="dashboard-menu-panes">
				<?php
					if(isset($params['dashboard_panes'])){ 
						foreach($params['dashboard_panes'] as $dp) {
							echo $dp;
						}
					}?>
				</div>
			</td>
			<td id='dashboard-content'>
				<table id='dashboard-lanes'>
					<tr class="internal-panel">
						<td id="internal-panel">
							<?php foreach($params['internal_services'] as $serv) {?>
							<a href='<?=$serv["url"]?>' title='<?=$serv["help"]?>' alt='<?=$serv["title"]?>'>
								<div class='dacura-dashboard-button'>
									<img class='dacura-button-img' src="<?=$serv["img"]?>">
									<div class="dacura-button-title"><?=$serv["title"]?></div>
								</div>
							</a>
							<?php } ?>
						</td>
					</tr>
					<tr class="data-panel">
						<td id="data-panel">
							<?php foreach($params['data_services'] as $serv) {?>
							<a href='<?=$serv["url"]?>' title='<?=$serv["help"]?>' alt='<?=$serv["title"]?>'>
								<div class='dacura-dashboard-button'>
									<img class='dacura-button-img' src="<?=$serv["img"]?>">
									<div class="dacura-button-title"><?=$serv["title"]?></div>
								</div>
							</a>
							<?php } ?>
						</td>
					</tr>
					<tr class="tool-panel">
						<td id="tool-panel">
							<?php foreach($params['tool_services'] as $serv) {?>
							<a href='<?=$serv["url"]?>' title='<?=$serv["help"]?>' alt='<?=$serv["title"]?>'>
								<div class='dacura-dashboard-button-wide'>
									<img class='dacura-button-img' src="<?=$serv["img"]?>">
									<div class="dacura-button-title"><?=$serv["title"]?></div>
								</div>
							</a>
							<?php } ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</div>



<script>
	$(function() {
	});
</script>

