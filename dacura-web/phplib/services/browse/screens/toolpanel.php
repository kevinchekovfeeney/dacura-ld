<div class="dacura-dashboard-panel" id="tool-panel">
<?php if($dacura_server->cid() == "seshat") {?>
	<a href='<?=$service->get_service_url("scraper")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-scraper-button' title="Seshat Scraper">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/seshat.gif")?>">
			<div class="dacura-button-title">Export Seshat Data</div>
		</div>
	</a>				
	<a href='<?=$service->get_service_url("scraper", array("test"))?>'>
			<div class='dacura-dashboard-button-wide' id='dacura-testparser-button' title="Test Seshat Parser">
		<img class='dacura-button-img' src="<?=$service->url("image", "buttons/events.png")?>">
			<div class="dacura-button-title">Test Seshat Parser</div>
		</div>
	</a>
<?php } ?>
	<a href='<?=$service->get_service_url("schema")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-scraper-button' title="Schema Management">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/schema.png")?>">
			<div class="dacura-button-title">Data Structure</div>
		</div>
	</a>				

</div>
