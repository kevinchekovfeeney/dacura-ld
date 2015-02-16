<div id='dashboard-menu'>
	<div id="dashboard-menu-logo">
		<a href="<?=$params['system_logo']['link']?>">
			<img src="<?=$params['system_logo']['img']?>">
		</a>
		<?php if(isset($params['collection_logo'])){?>
			<div id="collection-logo" style="background-image: url(<?=$params['collection_logo']['img']?>)"><?=$params['collection_logo']['title']?> Collection</div>
			<script>
				$(function() {
					$('#collection-logo').css( 'cursor', 'pointer' );
					$('#collection-logo').click( function (event){
						window.location.href = "<?=$params['collection_logo']['link']?>";
				    }); 
				});
			</script>
		<?php }?>
	</div>
	
	<UL class='dashboard-menu'>
		<?php foreach($params['menu_items'] as $mi) {
			echo "<li id='".$mi["id"]."' class='dashboard-menu-item";
			if($mi['selected']) echo " dashboard-menu-selected";
			echo "'><a href='".$mi['link']."'>".$mi['contents']."</a></li>";
		}?>
	</UL>
	<div id="dashboard-menu-panes">
	<?php foreach($params['dashboard_panes'] as $dp) {
		echo $dp;
	}?>
	</div>
</div>
<style>
	#collection-logo { min-height: 60px; font-weight: bold; color: white;	padding: 10px 2px; font-size: 1.2em; border-top: 2px solid white; border-bottom: 2px solid white;}
</style>
