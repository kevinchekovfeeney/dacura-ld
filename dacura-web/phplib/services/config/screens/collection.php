<div class='dacura-screen' id='collection-config'>
	<?php if(in_array("collection-settings", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='collection-settings' title="Collection Settings">
		<div title="Update Collection Details" class='subscreen-intro-message'><?=$params['settings_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("collection-details", "update", $params['update_collection_fields']);?>
		<div class="subscreen-buttons">
			<button id='collectiondelete' class='dacura-delete subscreen-button'>Delete Collection</button>
			<button id='collectionupdate' class='dacura-update subscreen-button'>Update Collection</button>
		</div>
	</div>
	<?php } if(in_array("list-datasets", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='list-datasets' title="View Datasets">
		<table id="datasets-table" class="dacura-api-listing">
			<thead>
			<tr>
				<th id="dlo-id" title="The internal ID of the dataset - a component in all collection internal URLs">ID</th>
				<th id="dlo-name" title="The title of the dataset - expressed in natural language">Title</th>
				<th id="dlo-status" title="Only datasets with status 'accept' are in use.">Status</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<?php } if(in_array("dataset-add", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='dataset-add' title="Create new Dataset">
		<div class='subscreen-intro-message'><?=$params['add_dataset_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("dataset-details", "create", $params['create_dataset_fields']);?>
		<div class="subscreen-buttons">
			<button id='datasetcreate' class='dacura-create subscreen-button'>Create Dataset</button>
		</div>
	</div>
	<?php } if(in_array("colconfig", $params['subscreens'])) { ?>
	<div class='dacura-subscreen' id='colconfig' title="Collection Details">
			<?php echo $service->getInputTableHTML("cconfig", "view", $params['cconfig_fields']);?>
	</div>
	<?php } ?>	
</div>

<script>

function drawCollection(obj){
	if(typeof obj.datasets != "undefined"){
		var rows = [];
		for(var dsid in obj.datasets){
			rows[rows.length] = obj.datasets[dsid];
		}
		dacura.system.drawDacuraListingTable("datasets-table", rows);
	}
	var nobj = obj.config;
	nobj.id = obj.id;
	nobj.name = obj.name;
	nobj.status = obj.status;
	dacura.system.drawDacuraUpdateObject("collection-details", nobj);
}

function showUpdatedCollection(obj, targets){
	drawCollection(obj);
	dacura.system.showSuccessResult("Updates successfully saved", obj, "Collection " + obj.id + " updated", targets.resultbox);
}

function showDeleteResult(obj, targets){
	dacura.system.showWarningResult("This collection has been deleted", false, "Collection <?=$params['cid']?> deleted", targets.resultbox);
}

$(function() {
	dacura.system.init({
		"mode": "tool", 
		"tabbed": 'collection-config', 
		"entity_id": "<?=$params['cid']?>",
		"load": dacura.config.fetchCollection,
		"draw": drawCollection,
		"buttons": {
			"collectionupdate": {
				"screen": "collection-settings",
				"source": "collection-details",
				"submit": dacura.config.updateCollection, 
				"result": showUpdatedCollection
			},
			"collectiondelete": {
				"screen": "collection-settings",
				"gather": function(){ return "<?=$params['cid']?>"},
				"submit": dacura.config.deleteCollection, 
				"result": showDeleteResult			
			}
		}
		
	});
});


</script>
