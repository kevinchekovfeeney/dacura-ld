<div class='dacura-screen' id='candidate-page'>
	<div id="candidate-list" class='dacura-subscreen ld-list' title="Instance Data Entities">
		<table id="candidate_table" class="dcdt display">
			<thead>
			<tr>
				<th id='cpx-id'>ID</th>
				<th id='cpx-type'>Type</th>
				<th id='cpx-collectionid'>Collection ID</th>
				<th id='cpx-status'>Status</th>
				<th id='cpx-version'>Version</th>
				<th id='cpx-meta-schemaversion'>Schema Version</th>
				<th id='dfn-getPrintableCreated'>Created</th>
				<th id='cpx-createtime'>Sortable Created</th>
				<th id="dfn-getPrintableModified">Modified</th>
				<th id="cpx-modtime">Sortable Modified</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="update-list" class='dacura-subscreen ld-list' title="Updates to Instance Data">
		<table id="update_table" class="dcdt display">
			<thead>
			<tr>
				<th id='cpu-eurid'>ID</th>
				<th id='cpu-targetid'>Candidate</th>
				<th id='cpu-collectionid'>Collection ID</th>
				<th id='cpu-status'>Status</th>
				<th id='cpu-from_version'>From Version</th>
				<th id='cpu-to_version'>To Version</th>
				<th id='cpu-meta-schemaversion'>Schema Version</th>
				<th id='cpx-createtime'>Created</th>
				<th id='dfn-getPrintableCreated'>Sortable Created</th>
				<th id="cpx-modtime">Modified</th>
				<th id='dfn-getPrintableModified'>Sortable Modified</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div class='dacura-subscreen' id="create-candidate" title="Create New Instance Data Entity">
		<?php echo $service->showLDResultbox($params);?>
		<?php echo $service->showLDEditor($params);?>		
	</div>
</div>

<script>

function getPrintableCreated(obj){
	return timeConverter(obj.createtime);
}

function getPrintableModified(obj){
	return timeConverter(obj.modtime);
}

$(function() {
	dacura.system.init({
		"mode": "tool", 
		"tabbed": "candidate-page", 
		"listings": {
			"ld_table": {
				"screen": "candidate-list", 
				"fetch": dacura.ld.fetchcandidatelist,
				"settings": <?=$params['candidate_datatable']?>
			},
			"update_table": {
				"screen": "update-list", 
				"fetch": dacura.ld.fetchupdatelist,
				"settings": <?=$params['update_datatable']?>				
			}
		}, 
	});
	dacura.editor.init({"editorheight": "400px", "targets": { resultbox: "#create-candidate-msgs", errorbox: "#create-candidate-msgs", busybox: '#create-holder'}, 
		"args": <?=json_encode($params['args']);?>});

	dacura.editor.getMetaEditHTML = function(meta){
		$('#meta-edit-table').show();
		return "";
	};
	dacura.editor.getInputMeta = function(){
		var meta = {"status": $('#entstatus').val()};
		return meta;
	};
	dacura.editor.load(false, false, dacura.candidate.create);
});
	
</script>