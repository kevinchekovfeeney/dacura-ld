<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<?php echo $service->showLDResultbox($params);?>

<div id='version-header' class="dch">
	<span class='vc version-title'></span>
	<span class='vc version-created'></span>
	<span class='vc version-replaced'></span>
	<span class='vc version-details'></span>
</div>	


<div id='tab-holder'>
	 <ul id="graph-pane-list" class="dch">
	 	<li><a href="#graph-contents">Local Ontology</a></li>
	 	<li><a href="#graph-imports">Imported Ontologies</a></li>
	 </ul>
	<div id="imports-holder">
		<div id="graph-imports" class="dch">
			<div id='import-msgs'></div>
				<div id='ont-list'>
					<?php if(isset($params['ontologies'])) {?>
						<table class="ontology_table display">
							<thead>
							<tr>
								<th>ID</th>
								<th>Status</th>
								<th>Version</th>
								<th>Import</th>
							</tr>
							</thead>
							<tbody>
							<?php foreach($params['ontologies'] as $id => $body){ ?>
								<tr class='ontology-list'>
									<td><?=$body['id']?></td>
									<td><?=$body['status']?></td>
									<td><?=$body['version']?></td>
									<td><input type='checkbox' class='ontologies-selected' id='<?=$body['id']?>'></td></tr>
							<?php } ?>
							</tbody>
						</table>
					<?php } ?>
				</div>
				<div class='dqs-embed'>
					<?= $service->showDQSControls("both", array()); ?>
				</div>
				<div class="tool-buttons">
	   			<button class="dacura-button test-imports" id="test-imports">Test Graph Configuration</button>
	   			<button class="dacura-button deploy-imports" id="deploy-imports">Deploy Graph Configuration</button>
	   		</div>
		</div>
	</div>
 	<div id="contents-holder">
		<div id='graph-contents' class="dch">
			<div id='graph-msgs'></div>
			<div id='viewont'>
				<?php echo $service->showLDEditor($params);?>
			</div>
		</div>
	</div>
</div>
<div id="tabletemplates" style="display:none">
	<div id="ontology-template">
	</div>
	<div id="toolheader-template">
		<table class='ld-invariants'>
			<thead>
				<tr>
					<th>URL</th>
					<th>Status</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class='graph_uri'></td>
					<td class='graph_status'></td>
					<td class='graph_created'></td>
				</tr>
			</tbody>
		</table>
	</div>
	
</div>


<script>

dacura.schema.showGraphHeader = function(gra){
	options = { title: gra.id + " graph" };
	if(typeof gra.image != "undefined"){
		options.image = gra.image;
	}
	///gra.subtitle = ont.meta.title;
	options.description = $('#toolheader-template').html();
	dacura.system.updateToolHeader(options);
	metadetails = timeConverter(gra.created);
	$('.graph_uri').html("<span class='graph-uri'>" + gra.meta.url + "</span>");
	$('.graph_created').html("<span class='graph-details'>" + metadetails + "</span>");
	$('.graph_status').html("<span class='graph-status graph-" + gra.latest_status + "'>" + gra.latest_status + "</span>");
    dacura.schema.drawVersionHeader(gra);	    
}

ontids = [];



dacura.schema.showImportedOntologies = function(imports){
	for(var i = 0; i < imports.length; i++){
		$('#' + imports[i]).prop('checked', true);
	}
};

dacura.schema.showSelectedDQS = function(schema, instance){
	for(var i = 0; i < schema.length; i++){
		$('#' + schema[i]).prop('checked', true).button("refresh");
	}
	for(var i = 0; i < instance.length; i++){
		$('#' + instance[i]).prop('checked', true).button("refresh");
	}
};


dacura.schema.showGraph = function(obj){
	dacura.schema.showGraphHeader(obj);
	if(typeof obj.meta.imports != "undefined"){
		dacura.schema.showImportedOntologies(obj.meta.imports);	
	}
	if(typeof obj.meta.schema_dqs != "undefined"){
		dacura.schema.showSelectedDQS(obj.meta.schema_dqs, obj.meta.instance_dqs);	
	}
}

function getSelectedOntologies(){
	selected = [];
	$('.ontology_table input:checked').each(function() {
	    selected.push($(this).attr('id'));
	});
	return selected;		
}



function getSelectedDQS(){
	var dqs = { "schema_dqs": dacura.dqs.getSelection("schema"), "instance_dqs" : dacura.dqs.getSelection("instance")};
	return dqs;		
}


function showImportResult(res, test){
	//dacura.system.showResult(res);
	//$(import-msg
	dacura.ldresult.showDecision(res, test, '#import-msgs', "Import Ontologies");
	/*if(res.decision == "reject" || res.errcode > 0){
		dacura.system.showErrorResult(res.msg_body, res, res.decision, '#import-msgs');
	}
	else if(typeof res.warnings == "object" && res.warnings.length > 0){
		dacura.system.showWarningResult(res.msg_body, res, res.decision, '#import-msgs');		
	}
	else {
		dacura.system.showSuccessResult(res.msg_body, res, res.decision, '#import-msgs');		
	}*/
	
}

function initDecorations(){
	//view format choices
	//quality check choices
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
	$('#test-imports').button().click(function(event){
		//get ids of selected ones...
		//
		var onts = getSelectedOntologies();
		var dqs = getSelectedDQS();
		var updateobj = {"meta": dqs};
		updateobj.meta.imports = onts;
		dacura.schema.updateGraph("<?=$params['id']?>", updateobj, showImportResult, "import", true);				
    });
	$('#deploy-imports').button().click(function(event){
		var onts = getSelectedOntologies();
		var dqs = getSelectedDQS();
		var updateobj = {"meta": dqs};
		updateobj.meta.imports = onts;		dacura.schema.updateGraph("<?=$params['id']?>", updateobj, showImportResult, "import");				
	});	
}

$(function() {
	initDecorations();
	dacura.system.init({"mode": "tool", "targets": {resultbox: "#graph-msgs", errorbox: "#graph-msgs", busybox: "#tab-holder"}});
	dacura.editor.init({"entity_type": "ontology"});
	var onw = function (obj){
		dacura.editor.load("<?=$params['id']?>", dacura.schema.fetchGraph, dacura.schema.updateGraph, obj);
		dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + obj.id , obj.id);	
	    $('#graph-pane-list').show();
	};
	dacura.schema.fetchGraph("<?=$params['id']?>", [], onw);
});
</script>