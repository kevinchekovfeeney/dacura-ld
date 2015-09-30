<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<div id='tab-holder'>
	 <ul id="graph-pane-list" class="dch">
	 	<li><a href="#graph-contents">Local Ontology</a></li>
	 	<li><a href="#graph-imports">Imported Ontologies</a></li>
	 </ul>
	<div id="imports-holder">
		<div id="graph-imports">
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
			<div class="tool-buttons">
	   			<button class="dacura-button test-imports" id="test-imports">Test Importing Ontologies</button>
	   			<button class="dacura-button deploy-imports" id="deploy-imports">Deploy Imported Ontologies</button>
	   		</div>
		</div>
	</div>
 	<div id="contents-holder">
		<div id='graph-contents'>
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
</div>

<script>
ontids = [];



dacura.schema.showImportedOntologies = function(imports){
	for(var i = 0; i < imports.length; i++){
		$('#' + imports[i]).prop('checked', true);
	}
};


dacura.schema.showGraph = function(obj){
	dacura.system.setLDEntityToolHeader(obj);
	if(typeof obj.meta.imports != "undefined"){
		dacura.schema.showImportedOntologies(obj.meta.imports);	
	}
	
}

function getSelectedOntologies(){
	selected = [];
	$('.ontology_table input:checked').each(function() {
	    selected.push($(this).attr('id'));
	});
	return selected;		
}

function showImportResult(res){
	//dacura.system.showResult(res);
	//$(import-msgs
	if(res.decision == "reject" || res.errcode > 0){
		dacura.system.showErrorResult(res.msg_body, res, res.decision, '#import-msgs');
	}
	else if(typeof res.warnings == "object" && res.warnings.length > 0){
		dacura.system.showWarningResult(res.msg_body, res, res.decision, '#import-msgs');		
	}
	else {
		dacura.system.showSuccessResult(res.msg_body, res, res.decision, '#import-msgs');		
	}
	
}

function initDecorations(){
	//view format choices
	//quality check choices
	$('#test-imports').button().click(function(event){
		//get ids of selected ones...
		//
		var onts = getSelectedOntologies();
		var updateobj = {"meta": {"imports" : onts}};
		dacura.schema.updateGraph("<?=$params['id']?>", updateobj, showImportResult, "import", true);				
    });
	$('#deploy-imports').button().click(function(event){
		var onts = getSelectedOntologies();
		var updateobj = {"meta": {"imports" : onts}};
		dacura.schema.updateGraph("<?=$params['id']?>", updateobj, showImportResult, "import");				
	});	
}

$(function() {
	initDecorations();
	dacura.system.init({"mode": "tool", "targets": {resultbox: "#graph-msgs", errorbox: "#graph-msgs", busybox: "#tab-holder"}});
	dacura.editor.init({"entity_type": "ontology"});
	dacura.editor.load("<?=$params['id']?>", dacura.schema.fetchGraph, dacura.schema.updateGraph);
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
    $('#graph-pane-list').show();
    //dacura.schema.fetchSchema(dacura.schema.showImportedOntologies);	
});
</script>