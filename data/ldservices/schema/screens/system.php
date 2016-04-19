<?php echo $service->showLDResultbox($params);?>
<div class='dacura-screen' id='system-schema'>
	<div class='dacura-subscreen' id="ontology-list" title="Imported Ontologies">
		<div class="ld-list">
			<table id="ontology_table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id='otd-id'>ID</th>
					<th id='otd-meta-url'>URL</th>
					<th id='otd-meta-title'>Title</th>
					<th id='otd-status'>Status</th>
					<th id='otd-version'>Version</th>
					<th id='dfn-rowselector'>Validate</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>	
		<div id='dqs-ontology-tests' class='dqs-validator'>
			<div id='ontology-test-msgs'></div>
			<div class='dqs-button'>
					<a class='button2' href='javascript:validateOntologies();'>Validate Selected Ontologies</a>
				</div>
				<div class='dqs-embed'>
					<?= $service->showDQSControls("schema", "all"); ?>
					<div style='clear: both'></div>
				</div>
		</div>
	</div>
	<div class='dacura-subscreen' id='import-ontology' title="Import Ontology">
		<div id='view-bar'>
			<table>
				<tr>
					<td id="view-bar-left">
						<span class='view-option view-mode' id="import-format">
						    <input type="radio" class='foption' id="import-url" name="format" checked="checked"><label for="import-url">URL</label>
						    <input type="radio" class='foption' id="import-upload" name="format"><label for="import-upload">File Upload</label>
						    <input type="radio" class='foption' id="import-text" name="format"><label for="import-text">Text</label>
				    	</span>
					</td>
					<td id="view-bar-centre">
					</td>
					<td id="view-bar-right">
					</td>
				</tr>	
			</table>
		</div>
		<div class='import-content'>
			<table class='import-table dc-wizard'>
				<thead><tr><th class="left"></th><th class="right"></th></tr></thead>
				<tbody>
					<tr class='id'><th id='entid'>ID</th><td id='id-input-cell'><input type="text" id='id-input'> (leave blank for auto-generated id)</td></tr>
					<tr class='title'><th id='entid'>Title</th><td id='id-input-cell'><input type="text" id='title-input'></td></tr>
					<tr><th>URL</th><td><input type="text" id='url-input'></td></tr>
					<tr class='uploadmode'><th>Choose a file to upload</th><td><input type="file" name='fileup' id='file-input'></td></tr>
					<tr class='textmode'><td colspan=2><div>Paste the ontology into the text box below</div><textarea id='tximp'></textarea></td></tr>
				</tbody>
			</table>			
		</div>
		<div class="tool-buttons">
   			<button class="dacura-button urlmode import-button" id="url-button">Import from URL</button>
      		<button class="dacura-button uploadmode import-button" id="file-button">Upload File</button>
      		<button class="dacura-button textmode import-button" id="text-button">Import from Textbox</button>
      	</div>
	</div>
</div>
<script>
dacura.schema.importFormat = "url";
var ontids = [];

function validateOntologies(){
	var onts = [];
	for (index = 0; index < ontids.length; ++index) {	
		var full_id = ontids[index];
		if($('#dqsontology_' + index).is(":checked")){
			onts.push(full_id);
		}
	}
	var tests = dacura.dqs.getSelection("schema");
	dacura.schema.validateGraphOntologies(onts, tests, {scrollto: "#ontology-test-msgs", resultbox: "#ontology-test-msgs", busybox: "#ontology-list"});
}



function setImportFormat(format){
	dacura.schema.importFormat = format;
	if(format == "upload"){
		$('.uploadmode').show();		
		$('.urlmode').hide();
		$('.textmode').hide();	
	}
	else if(format == "text"){
		$('.textmode').show();
		$('.uploadmode').hide();
		$('.urlmode').hide();
	}
	else {
		$('.textmode').hide();
		$('.uploadmode').hide();
		$('.urlmode').show();
	}
}

function initDecorations(){
	//view format choices
	$( "#import-format" ).buttonset();
	$( ".foption" ).click(function(event){
		clearResultMessage();
		setImportFormat(event.target.id.substring(7));
    });
	
	//import button
	$('.import-button').button().click(function (event){
		clearResultMessage();
		var payload = false;
		if(dacura.schema.importFormat == "text"){
			payload = $('#tximp').val();
			
		}
		else if(dacura.schema.importFormat != "url"){
			payload = document.getElementById('file-input').files[0];
		}
		var entid =  $('#id-input').val();
		var enttitle = $('#title-input').val();
		var enturl = $('#url-input').val();
		dacura.schema.importOntology(dacura.schema.importFormat, entid, enttitle, enturl, payload, {resultbox: "#import-ontology-msgs", busybox: "#import-ontology"});
	});
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

$(function() {
	initDecorations();
	setImportFormat("url");
	dacura.system.init({
		"mode": "tool", 
		"tabbed": "system-schema",
		"listings": {
			"ontology_table" : {
				"screen": "ontology-list", 
				"fetch": dacura.ld.fetchentitylist,
				"settings": <?=$params['ontology_datatable']?>	
			}
		}
	});
	//dacura.schema.fetchentitylist(drawOntologies, {resultbox: "#ontology-msgs", errorbox: "#ontology-msgs", busybox: "#ontology-list"}); 
});
</script>