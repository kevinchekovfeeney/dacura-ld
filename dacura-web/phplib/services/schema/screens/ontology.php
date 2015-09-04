<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />
<script>
function showDQSOptions(){
	$('#dqsopts').show();	
}

function validateSchema(id){
	var ajs = dacura.schema.api.validate_ontology(id);
	var msgs = { "busy": "Validating Ontology with Dacura Quality Service", "fail": "Schema validation failed"};
	var self = this;
	ajs.handleResult = function(data){ alert(JSON.stringify(data));};
	dacura.system.invoke(ajs, msgs);
}

$(function() {
	$('.dqsoption').button();
});
</script>
<div id='tab-holder'>
	 <ul id="ontology-pane-list" class="dch">
	 	<li><a href="#ontology-metadata">Metadata</a></li>
	 	<li><a href="#ontology-contents">Contents</a></li>
	 	<li><a href="#ontology-config">Configuration</a></li>
	 </ul>
	<div id="meta-holder">
		<div id="ontology-metadata">
			<table class='graph-summary'>
				<thead><tr><th></th><th></th></tr></thead>
				<tbody>
					<tr>
						<th>Local ID</th>
						<td><span class='ontology-input' id='ontid'></span></td>
					</tr>
					<tr>
						<th>URL (global ID)</th>
						<td><input class='ontology-input' id='onturl'></td>
					</tr>
					<tr>
						<th>Version</th>
						<td><input class='ontology-input' id='ontversion'></td>
					</tr>
					<tr>
						<th>Title</th>
						<td><input class='ontology-input' id='onttitle'></td>
					</tr>
					<tr>
						<th>Status</th>
						<td><input class='ontology-status' id='ontstatus'></td>
					</tr>
					<tr>
						<th>Description</th>
						<td><textarea class='ontology-input' id='ontdescr'></textarea></td>
					</tr>
					<tr>
						<th>History</th>
						<td><span class='ontology-detail' id='ontcreated'></span> <span class='ontology-detail' id='ontmodified'></span></td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
 	<div id="contents-holder">
		<div id='ontology-contents' >
			<div id='lded-msgs'></div>
			<div id='viewont'>
				<?php echo $service->showLDEditor($params);?>
			</div>
		</div>
	</div>
	<div id="config-holder">
		<div id="ontology-config">
			<div id='dqs' class="">
				<div class='title'>Dacura Quality Service</div>
				<a class='button2' href='javascript:validateSchema("<?=$params['id']?>");'>Validate Schema</a>
				<div id='dqs-setting'>Currently configured to run all tests <a href='javascript:showDQSOptions();'>Change</a></div>
				<div id='dqsopts' class='dch'>
					<?= $service->getDQSCheckboxes("schema"); ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
dacura.schema.showOntology = function(obj){
	$('#ontid').html(obj.id);
	$('#onturl').val(obj.url);
	$('#ontversion').val(obj.real_version);
	$('#onttitle').val(obj.title);
	$('#ontstatus').val(obj.status);
	$('#ontdescr').val(obj.description);
	$('#ontcreated').html("created " + timeConverter(obj.created));
	$('#ontmodified').html("modified " + timeConverter(obj.modified));
}

dacura.schema.gatherOntologyDetails = function(){
	var details = {};
	details.url = $('#onturl').val();
	details.title = $('#onttitle').val();
	details.status = $('#ontstatus').val();
	details.description = $('#ontdescr').val();
	details.real_version = $('#ontversion').val();
	return details;
}


function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

$(function() {
	
	dacura.system.init({"mode": "tool", "targets": {resultbox: "#lded-msgs", errorbox: "#lded-msgs", busybox: "#tab-holder"}});
	dacura.editor.init({"entity_type": "ontology"});
	dacura.editor.load("<?=$params['id']?>", dacura.schema.fetchOntology, dacura.schema.updateOntology);
	$('#ontology-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
            clearResultMessage();
        }
    });
});
</script>
