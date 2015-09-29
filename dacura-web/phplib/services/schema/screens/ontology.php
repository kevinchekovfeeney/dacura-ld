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
	 	<li><a href="#ontology-dependencies">Dependencies</a></li>
	 	<li><a href="#ontology-contents">Contents</a></li>
	 </ul>
	<div id="meta-holder">
		<div id="ontology-dependencies">
		
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
<div id="tabletemplates" class='dacura-templates'>
	<?php echo $service->includeSnippet("ldentity-header")?>
</div>

<script>
dacura.schema.showOntology = function(obj){
	dacura.system.setLDEntityToolHeader(obj);
	//$('#ontid').html(obj.id);
	//$('#onturl').val(obj.url);
	//$('#ontversion').val(obj.real_version);
	//$('#onttitle').val(obj.title);
	//$('#ontstatus').val(obj.status);
	//$('#ontdescr').val(obj.description);
	//$('#ontcreated').html("created " + timeConverter(obj.created));
	//$('#ontmodified').html("modified " + timeConverter(obj.modified));
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
