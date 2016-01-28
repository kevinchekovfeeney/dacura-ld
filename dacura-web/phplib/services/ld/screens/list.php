<div class='dacura-screen' id='ld-tool-home'>
	<div class='dacura-subscreen ld-list' id="entity-list" title="Linked Data Entities">
		<table id="ld_table" class="dcdt display dacura-api-listing">
			<thead>
			<tr>
				<th id='lde-id'>ID</th>
				<th id='lde-type'>Type</th>
				<th id='lde-collectionid'>Collection</th>
				<th id='lde-status'>Status</th>
				<th id='lde-version'>Version</th>
				<th id='dfn-getPrintableCreated'>Created</th>
				<th id='lde-createtime'>Sortable Created</th>
				<th id='dfn-getPrintableModified'>Modified</th>
				<th id='lde-modtime'>Sortable Modified</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class='dacura-subscreen ld-list' id="update-list" title="Updates to Linked Data Entities">
		<table id="update_table" class="dcdt dacura-api-listing display">
			<thead>
			<tr>
				<th id='ldu-eurid'>ID</th>
				<th id='ldu-targetid'>Target</th>
				<th id='ldu-type'>Type</th>
				<th id='ldu-collectionid'>Collection</th>
				<th id='ldu-status'>Status</th>
				<th id='ldu-from_version'>From Version</th>
				<th id='ldu-to_version'>To Version</th>
				<th id='dfu-getPrintableCreated'>Created</th>
				<th id='ldu-createtime'>Sortable Created</th>
				<th id='dfu-getPrintableModified'>Modified</th>
				<th id='ldu-modtime'>Sortable Modified</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class='dacura-subscreen' id="create-entity" title="Create New Linked Data Entities">
		<?php echo $service->showLDResultbox($params);?>
		<?php echo $service->showLDEditor($params);?>
		<P>Why oh why</P>
	</div>
</div>
<script>
$(function() {
	dacura.tool.init({"tabbed": "ld-tool-home"});
	dacura.tool.table.init("ld_table", {
		"screen": "entity-list", 
		"rowClick": function(event, entid) {window.location.href = dacura.system.pageURL() + "/" + entid},		
		"fetch": dacura.ld.fetchentitylist,
		"dtsettings": <?=$params['entity_datatable']?>
	});		
	dacura.tool.table.init("update_table", {
		"screen": "update-list", 
		"fetch": dacura.ld.fetchupdatelist,
		"dtsettings": <?=$params['update_datatable']?>
	});		
	
	dacura.editor.init({"editorheight": "300px", "targets": {resultbox: "#create-entity-msgs", busybox: "#create-entity"}});
	dacura.editor.load(false, false, dacura.ld.create);

});

function getPrintableCreated(obj){
	return timeConverter(obj.createtime);
}

function getPrintableModified(obj){
	return timeConverter(obj.modtime);
}

</script>

