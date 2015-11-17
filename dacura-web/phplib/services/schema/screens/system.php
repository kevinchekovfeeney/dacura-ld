<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id='tab-holder'>
	 <ul id="schema-pane-list" class="dch">
	 	<li><a href="#ontology-list">Ontologies</a></li>
	 	<li><a href="#import-ontology">Import New Ontology</a></li>
	 </ul>
	<div id="import-ontology">
		<div class="tab-top-message-holder">
			<div class="tool-import-info tool-tab-info" id="import-msgs"></div>
		</div>
		<div id="import-holder" class='dch'>
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
	<div id="ontology-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="ontology-msgs"></div>
			
		</div>
		<div id='ontology-list-holder' class='dch'>
			<div id='ontology-table-holder' class='ld-list'></div>
			<div id='ontology-tests-messages'>
				<?php echo $service->showLDResultbox($params);?>
			</div>
			<div id='dqs-ontology-tests' class='dqs-validator'>
				<div class='dqs-button'>
					<a class='button2' href='javascript:validateOntologies();'>Validate Selected Ontologies</a>
				</div>
				<div class='dqs-embed'>
					<?= $service->showDQSControls("schema", "all"); ?>
					<div style='clear: both'></div>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="tabletemplates" style="display:none">
	<div id="ontology-template">
		<table class="ontology_table display">
			<thead>
			<tr>
				<th>ID</th>
				<th>URL</th>
				<th>Title</th>
				<th>Status</th>
				<th>Version</th>
				<th>Validate</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
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
	dacura.schema.validateGraphOntologies(onts, tests, {scrollto: "#ontology-tests-messages", resultbox: "#ontology-tests-messages", errorbox: "#ontology-tests-messages", busybox: "#ontology-list"});
}

function getMetaProperty(meta, key, def){
	if(typeof meta[key] == "undefined"){
		return def;
	}
	return meta[key];
}

function drawOntologies(onts){
	var k = $('#ontology-template').html();
	$('#ontology-table-holder').html(k);
	for (var key in onts) {
	  	if (onts.hasOwnProperty(key)) {
			if(!isEmpty(onts[key])){
				var url = getMetaProperty(onts[key]["meta"], "url", "unknown");
				var title = getMetaProperty(onts[key]["meta"], "title", "none");
				var shorthand = getMetaProperty(onts[key]["meta"], "shorthand", "none");
			 	$('#ontology-table-holder .ontology_table tbody').append("<tr class='ontology-list' id='ontology_" + ontids.length + "'><td class='ontology_" + ontids.length + "'>" + 
					 	onts[key]['id'] + "</td><td class='ontology_" + ontids.length + "'>" + url + "</td><td class='ontology_" + ontids.length + "'>" + title +
				  	"</td><td class='ontology_" + ontids.length + "'>" + onts[key].status + "</td><td class='ontology_" + ontids.length + "'>" + onts[key]["version"] + "</td><td>" + 
				  	"<input type='checkbox' + id='dqsontology_" + ontids.length + "'" + "></td></tr>");
			  	$('.ontology_' + ontids.length).click( function (event){
				  	window.location.href = "schema/" + ontids[this.parentNode.id.substr(9)];
			    });
			  	ontids[ontids.length] = onts[key].id;		  	 		  	
			}
		}
	}
	$('.ontology-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	if(ontids.length == 0){
		$('#ontology-list-holder').show();
		$('#dqs-ontology-tests').hide();
		$('#ontology-table-holder .ontology_table').dataTable(<?=$dacura_server->getServiceSetting('ontology_datatable_init', "{}");?>);
		$('#update_table').dataTable(<?=$dacura_server->getServiceSetting('ontology_datatable_init', "{}");?>);	
		dacura.system.writeErrorMessage("No Ontologies Found", '#ontology-table-holder .dataTables_empty');		
	}	
	else {
		$('#ontology-list-holder').show();
		$('#ontology-table-holder .ontology_table').dataTable(<?=$dacura_server->getServiceSetting('ontology_datatable_init', "{}");?>);
	}
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
		dacura.schema.importOntology(dacura.schema.importFormat, entid, enttitle, enturl, payload, {resultbox: "#import-msgs", errorbox: "#import-msgs", busybox: "#import-holder"});
	});
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

$(function() {
	initDecorations();
	setImportFormat("url");
	dacura.system.init({"mode": "tool", "tabbed": true, "targets": {resultbox: ".tool-tab-info", errorbox: ".tool-tab-info", busybox: "#tab-holder"}});
	$('#schema-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
	$('#import-holder').show();
	dacura.schema.entity_type = "ontology";
	dacura.schema.fetchentitylist(drawOntologies, {resultbox: "#ontology-msgs", errorbox: "#ontology-msgs", busybox: "#ontology-list"}); 
});
</script>