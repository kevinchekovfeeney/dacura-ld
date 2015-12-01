<div class='dacura-screen' id='system-config'>
	<?php if(in_array("system-configuration", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="system-configuration" title="System Settings">
		<div class='subscreen-intro-message'>system wide settings for this dacura server</div>
		<?php echo $service->getInputTableHTML("sysconfig", "view", $params['sysconfig_fields']);
			foreach($params['service_tables'] as $id => $fields){
				echo "<h3>".ucfirst($id)." Service Configuration</h3>";
				echo $service->getInputTableHTML($id."-service-table", "view", $fields);
			}
		?>		
	</div>
	<?php } if(in_array("list-collections", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='list-collections' title="Configure Collections">
		<table id="collections-table" class="dacura-api-listing">
			<thead>
			<tr>
				<th id="dlo-id" title="The internal ID of the collection - a component in all collection internal URLs">ID</th>
				<th id="dlo-name" title="The title of the collection - expressed in natural language">Title</th>
				<th id="dlo-status" title="Only collections with status 'accept' are in use.">Status</th>
				<th id="dfn-getDatasetCount" title="How many sub-datasets exist within this collection">Datasets</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<?php } if(in_array("add-collection", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="add-collection" title="Create New Collection">
		<div class='subscreen-intro-message'>Choose a title and id for the new collection, then hit the create button</div>
		<?php echo $service->getInputTableHTML("collection-details", "create", $params['create_collection_fields']);?>
		<div class="subscreen-buttons">
			<button id='collectioncreate' class='dacura-create subscreen-button'>Create New Collection</button>
		</div>
	</div>
	<?php } if(in_array("view-logs", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id="view-logs" title="View Server Logs">
		<div class='subscreen-intro-message'>View the Dacura request and event logs.</div>
		<?php echo RequestLog::getAsListingTable("logtable")?>
	</div>	
	<?php } ?>
</div>
<script>

function getDatasetCount(obj){
	if(typeof obj.datasets == "undefined") return 0;
	return size(obj.datasets);
}

function loadCollection(id){
	dacura.system.switchContext(id, "all");	
}

function inputError(obj){
	if(typeof obj.id == "undefined" || typeof obj.title == "undefined"){
		return "bad reading of object from input";
	}
	if(obj.id.length < 2 || obj.title.length < 5){
		return "The ID must be at least 2 characters long and the title must be at least 5";
	}
	return false;
}

function showCreateSuccess(txt, targets){
	dacura.system.showSuccessResult("You will now be able to configure and activate this collection", false, "Collection with id: " + txt + " successfully created", targets.resultbox);
	setTimeout(dacura.system.switchContext(txt, "all"), 3000);
}

function showUpdateSuccess(txt, targets){
	dacura.system.showSuccessResult("Updates successfully saved", txt, false, targets.resultbox);
}


$(function() {
	dacura.system.init({
		"mode": "tool", 
		"tabbed": 'system-config', 
		"listings": {
			"collections-table": {
				"screen": "list-collections", 
				"fetch": dacura.config.getCollections,
				"rowClick": loadCollection, 
				"settings": <?=$params['dacura_table_settings']?>
			},
			"logtable": {
				"screen": "view-logs", 
				"fetch": dacura.config.getLogs,
				"rowClick": function(){ alert("clicked row")},
				"settings": <?=$params['log_table_settings']?>				
			}
		}, 
		"buttons": {
			"collectioncreate": {
				"screen": "add-collection",
				"source": "collection-details",
				"validate": inputError, 
				"submit": dacura.config.addCollection, 
				"result": showCreateSuccess
			},
			"systemconfigupdate": {
				"screen": "system-configuration",			
				"source": "sysconfig",
				"result": showUpdateSuccess
			}
		}
	});
});
</script>