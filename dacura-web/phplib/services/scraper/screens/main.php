<div class="dacura-dashboard-panel">
	<a href='<?=$service->get_service_url()?>/export'>
			<div class='dacura-dashboard-button' id='dacura-export-button' title="Export data from the wiki to CSV">
			<img class='dacura-button-img' src="<?=$service->furl("image", "buttons/export.png")?>">
			<div class="dacura-button-title">Export Data</div>
		</div>
	</a>
	<a href='<?=$service->get_service_url()?>/status'>
			<div class='dacura-dashboard-button' id='dacura-sources-button' title="Get an up to date status of the wiki">
			<img class='dacura-button-img' src="<?=$service->furl("image", "buttons/status.png")?>">
			<div class="dacura-button-title">Current State</div>
		</div>
	</a>
	<a href='<?=$service->get_service_url()?>/history'>
			<div class='dacura-dashboard-button' id='dacura-sources-button' title="Historical Statistics of wiki data collection">
			<img class='dacura-button-img' src="<?=$service->furl("image", "buttons/stats.jpg")?>">
			<div class="dacura-button-title">History</div>
		</div>
	</a>
	<a href='<?=$service->get_service_url()?>/test'>
			<div class='dacura-dashboard-button' id='dacura-sources-button' title="Test exporting variables and pages">
			<img class='dacura-button-img' src="<?=$service->furl("image", "buttons/syntax.png")?>">
			<div class="dacura-button-title">Test Syntax</div>
		</div>
	</a>
	<a href='<?=$service->get_service_url()?>/syntax'>
			<div class='dacura-dashboard-button' id='dacura-sources-button' title="A guide to the syntax of Seshat Variables">
			<img class='dacura-button-img' src="<?=$service->furl("image", "buttons/syntax2.png")?>">
			<div class="dacura-button-title">Syntax Guide</div>
		</div>
	</a>
</div>
<div style="clear: both;">&nbsp;</div>
<script>
	function updateWikiStatus(){
		//alert("pressed update");
		dacura.scraper.status();
				
	}
</script>