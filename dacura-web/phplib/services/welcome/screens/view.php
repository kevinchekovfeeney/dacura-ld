<div id='dashboard-container'>
	<div id='dashboard-menu'>
		<UL class='dashboard-menu'><li class='dashboard-menu-selected'>Welcome to Dacura</li></UL>
	</div>
	<div id='dashboard-content'>
		<div id='dashboard-tasks'>
			<div class="dacura-dashboard-panel-container">
				Available Dacura tools
				<div class="dacura-dashboard-panel" id="management-panel">
					<a href='<?=$service->get_service_url("scraper")?>'>
						<div class='dacura-dashboard-button' id='dacura-users-button' title="Export Data">
							<img class='dacura-button-img' src="<?=$service->url("image", "seshat-squatting.gif")?>">
							<div class="dacura-button-title">Export Seshat Data</div>
						</div>
					</a>
					<hr style='clear: both'>
					<div class="dacura-welcome">
					This is the home page for the Seshat project on the Dacura Platform. As more tools are developed, they will appear on the screen above.  
					Please note that this page, and all of the seshat data, is private and access is limited to Seshat administrators. In order to use the tools above, you need to have a Seshat Administrator Role.  
					</div>				
				</div>
				
			</div>
		</div>
	</div>
</div>
