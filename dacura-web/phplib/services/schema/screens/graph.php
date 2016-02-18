
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
						<div class='ont-title'>Ontologies available for import
							<span class='ont-select-all'><input type='checkbox' id='ont-select-all'><label for='ont-select-all'>Select All</label></span>						
						</div>
						<div class='ont-import-list'>
						<?php foreach($params['ontologies'] as $id => $body){ 
								$title = "Status: ".$body['status'] . "\nVersion: ". $body['version'];
								if(isset($body['meta'])){
									if(isset($body['meta']['title'])){
										$title .= "\nTitle: ".$body['meta']['title'];
									}
									if(isset($body['meta']['url'])){
										$title .= "\nURL: ".$body['meta']['url'];
									}											
								}
						?>
							<span class='ontoption'>
								<input title='<?=$title?>' type='checkbox' class='ontologies-selected' id='<?=$body['id']?>'> 
								<label title='<?=$title?>' for='<?= $body['id']?>'><?=$body['id']?></label></span>
							<?php } ?>
					<?php } ?>
					</div>
				</div>
				<div class='dqs-options'>
					<div class='ont-title'>Quality Service constraints to enforce
						<span class='dqs-select-all'><input type='checkbox' id='dqs-all'><label for='dqs-all'>Select All</label></span>
					</div>
					<div class='dqs-embed'>
						<?= $service->showDQSControls("both", array()); ?>
					</div>
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
				<?php echo $service->showLDResultbox($params);?>
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

dacura.schema.showHeader = function(gra){
	options = { title: gra.id + " graph" };
	if(typeof gra.image != "undefined"){
		options.image = gra.image;
	}
	///gra.subtitle = ont.meta.title;
	options.description = $('#toolheader-template').html();
	dacura.tool.header.update(options);
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
	if(typeof schema != "undefined" && schema){
		for(var i = 0; i < schema.length; i++){
			$('#' + schema[i]).prop('checked', true).button("refresh");
		}
	}
	if(typeof instance != "undefined" && instance){
		for(var i = 0; i < instance.length; i++){
			$('#' + instance[i]).prop('checked', true).button("refresh");
		}
	}
};


dacura.schema.showGraph = function(obj){
	if(typeof obj.meta.imports != "undefined"){
		dacura.schema.showImportedOntologies(obj.meta.imports);	
	}
	if(typeof obj.meta.schema_dqs != "undefined"){
		dacura.schema.showSelectedDQS(obj.meta.schema_dqs, obj.meta.instance_dqs);	
	}
}

function getSelectedOntologies(){
	selected = [];
	$('.ont-import-list input:checked').each(function() {
	    selected.push($(this).attr('id'));
	});
	return selected;		
}



function getSelectedDQS(){
	var dqs = { "schema_dqs": dacura.dqs.getSelection("schema"), "instance_dqs" : dacura.dqs.getSelection("instance")};
	return dqs;		
}


function showImportResult(res){
	var imptargets = { resultbox: '#import-msgs', errorbox: '#import-msgs', busybox: "#graph-imports", scrollto:'#import-msgs'};
	var cancel = function(){
		$(imptargets.resultbox).html("");
	};
	var upd = function(){
		delete(dacura.schema.lastUpdateObject["test"]);
		dacura.schema.update("<?=$params['id']?>", dacura.schema.lastUpdateObject, showImportResult, "import", imptargets);				
	};
	res.format = "json";
	
	dacura.ldresult.showDecision(res, imptargets.resultbox, cancel, upd);			
}

function initDecorations(){
	//view format choices
	//quality check choices
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
	$( "#ont-select-all" ).button().click(function(event){
		if($('#ont-select-all').is(":checked")){
			$("input:checkbox.ontologies-selected").prop('checked', true).button("refresh");
		}
		else {
			$("input:checkbox.ontologies-selected").prop('checked', false).button("refresh");
		}					
	});
	var imptargets = {resultbox: "#import-msgs", errorbox: "#import-msgs", busybox: "#graph-imports", scrollto: "#import-msgs"};
	$('#test-imports').button().click(function(event){
		//get ids of selected ones...
		//
		var onts = getSelectedOntologies();
		var dqs = getSelectedDQS();
		var updateobj = {"meta": dqs};
		updateobj.meta.imports = onts;
		dacura.schema.lastUpdateObject = updateobj;
		dacura.schema.update("<?=$params['id']?>", updateobj, showImportResult, "import", imptargets, true);				
    });
	$('#deploy-imports').button().click(function(event){
	    var onts = getSelectedOntologies();
		var dqs = getSelectedDQS();
		var updateobj = {"meta": dqs};
		updateobj.meta.imports = onts;		
		dacura.schema.lastUpdateObject = updateobj;
		dacura.schema.update("<?=$params['id']?>", updateobj, showImportResult, "import", imptargets);				
	});	
}

$(function() {
	dacura.schema.lastUpdateObject = false;
	dacura.schema.ldo_type = "graph";
	initDecorations();
	dacura.system.init({"mode": "tool"});
	dacura.editor.init({"ldo_type": "ontology", "targets": {resultbox: "#graph-msgs", busybox: "#graph-contents", scrollto: "#graph-msgs"},		
		"args": <?=json_encode($params['args']);?>});
	dacura.editor.getMetaEditHTML = function(meta){
		$('#meta-edit-table').html("");
		$('#meta-edit-table').append("<li><span class='meta-label'>Status</span><span class='meta-value'>" + 
			"<select id='entstatus'><?php echo $service->getLDOStatusOptions();?></select></span></li>");
		$('#entstatus').val(meta.status);	
		$('#meta-edit-table').append("<li><span class='meta-label'>URL</span><span class='meta-value'>" + 
				"<input type='text' id='enturl' value='" + meta.url + "'></span></li>");	
		$('#meta-edit-table').show();
		return "";
	};

	dacura.editor.getInputMeta = function(){
		var meta = {"status": $('#entstatus').val(), "url": $('#enturl').val()};
		return meta;
	};
	
	var onw = function (obj){
		dacura.editor.load("<?=$params['id']?>", dacura.schema.fetch, dacura.schema.update, obj);
		//dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + obj.id , obj.id);	
		dacura.schema.showGraph(obj);
	    $('#graph-pane-list').show();
	};
	var args = <?=json_encode($params['args']);?>;
	dacura.schema.fetch("<?=$params['id']?>", args, onw, {resultbox: "#graph-msgs", errorbox: "#graph-msgs", busybox: "#graph-contents"});
});
</script>