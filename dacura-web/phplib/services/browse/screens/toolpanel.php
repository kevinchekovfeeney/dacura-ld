<div class="dacura-dashboard-panel" id="tool-panel">
<?php if($dacura_server->cid() == "seshat") {?>
	<a href='<?=$service->get_service_url("scraper")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-scraper-button' title="Seshat Scraper">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/Seshat_Logo.png")?>">
			<div class="dacura-button-title">Export Seshat Data</div>
		</div>
	</a>				
	<a href='<?=$service->get_service_url("scraper", array("test"))?>'>
			<div class='dacura-dashboard-button-wide' id='dacura-testparser-button' title="Test Seshat Parser">
		<img class='dacura-button-img' src="<?=$service->url("image", "buttons/events.png")?>">
			<div class="dacura-button-title">Test Seshat Parser</div>
		</div>
	</a>
<?php } 
	if($service->collection_id == "all") {?>
	<a href='<?=$service->get_service_url("schema")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-schema-button' title="Ontology Management">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/schema.png")?>">
			<div class="dacura-button-title">Ontologies</div>
		</div>
	</a>				
	<a href='<?=$service->get_service_url("ld")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-ld-button' title="Linked Data Editor">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/knowledge.png")?>">
			<div class="dacura-button-title">Linked Data Editor</div>
		</div>
	</a>				
	<?php } else {?>
	<a href='<?=$service->get_service_url("schema")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-scraper-button' title="Schema Management">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/schema.png")?>">
			<div class="dacura-button-title">Data Structure</div>
		</div>
	</a>				
	<a href='<?=$service->get_service_url("widget")?>'>
		<div class='dacura-dashboard-button-wide' id='dacura-widget-button' title="UI Widgets">
					<img class='dacura-button-img' src="<?=$service->url("image", "buttons/widget.png")?>">
			<div class="dacura-button-title">User Interface Widgets</div>
		</div>
	</a>				
	<?php } ?>
</div>
