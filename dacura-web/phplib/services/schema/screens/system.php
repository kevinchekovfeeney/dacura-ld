<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id='tab-holder'>
	 <ul id="schema-pane-list" class="dch">
	 	<li><a href="#ontology-list">Ontologies</a></li>
	 	<li><a href="#import-ontology">Import New Ontology</a></li>
	 </ul>
	<div id="ontology-holder">
		<div id="import-ontology" class="dch">
			<div class="tool-import-info tool-tab-info" id="import-msgs"></div>
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
				<table class='dc-wizard'>
					<thead><tr><th class="left"></th><th class="right"></th></tr></thead>
					<tbody>
						<tr class='urlmode'><th>URL</th><td><input type="text" id='url-input'></td></tr>
						<tr class='uploadmode'><th>Choose a file to upload</th><td><input type="file" name='fileup' id='file-input'></td></tr>
					</tbody>
				</table>
				<div class='input-text textmode'>
					<div class='input-text-title'>Paste the ontology into the text box below</div>
					<textarea id='tximp'>
					</textarea>
				</div>
			</div>
			<div class="tool-buttons">
	   			<button class="dacura-button urlmode import-button" id="url-button">Import from URL</button>
	      		<button class="dacura-button uploadmode import-button" id="file-button">Upload File</button>
	      		<button class="dacura-button textmode import-button" id="text-button">Import from Textbox</button>
	      	</div>
		</div>
	</div>
	<div id="ontology-holder">
		<div id="ontology-list" class="dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="ontology-msgs"></div>
			</div>
			<div id='ontology-table-holder'></div>
			<div id='dqs-tests'>
				<div class='dqs-choose dch'>Choose the constraints to apply</div>
				<div class='dqs-button'>
					<input type='checkbox' checked="checked" title='select all tests' id='dqs-all' value="all"><label for='dqs-all'>Apply all Constraints</label>
					<a class='button2' href='javascript:validateOntologies();'>Validate Ontologies</a>
				</div>
				<div id='dqsopts' class='dch'>
					<?= $service->getDQSCheckboxes("schema"); ?>
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
		else {
		}
	}
	if(!($('input:checkbox#dqs-all').is(":checked"))){
		var tests = [];
		$('input:checkbox.dqsoption').each(function () {
			if(this.checked){
				tests.push($(this).val());
			}
		  });
		var x = JSON.stringify(onts, 0, 4) + " " + JSON.stringify(tests, 0, 4);
		alert(x);
		dacura.schema.validateGraphOntologies(onts, tests);
	}
	else {
		dacura.schema.validateGraphOntologies(onts);
	}
	//ids[this.id.substr(9)]
}

function drawOntologies(onts){
	var k = $('#ontology-template').html();
	$('#ontology-table-holder').html(k);
	for (var key in onts) {
	  	if (onts.hasOwnProperty(key)) {
			if(!isEmpty(onts[key])){
			 	$('#ontology-table-holder .ontology_table tbody').append("<tr class='ontology-list' id='ontology_" + ontids.length + "'><td>" + onts[key]['id'] + "</td><td>" + 
				  	onts[key]['url'] + "</td><td class='ontology_" + ontids.length + "'>" + onts[key]["title"] +
				  	"</td><td class='ontology_" + ontids.length + "'>" + onts[key]["status"] + "</td><td class='ontology_" + ontids.length + "'>" + onts[key]["version"] + "</td><td>" + 
				  	"<input type='checkbox' + id='dqsontology_" + ontids.length + "'" + "></td></tr>");
			  	$('.ontology_' + ontids.length).click( function (event){
					window.location.href = ontids[this.parentNode.id.substr(9)];
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
	$('#ontology-table-holder .ontology_table').dataTable(<?=$dacura_server->getServiceSetting('ontology_datatable_init', "{}");?>);
}

var drawSchema = function(sch){
	dacura.schema.currentschema = sch;
	drawOntologies(sch.ontologies);
}

function setImportFormat(format){
	dacura.schema.importFormat = format;
	if(format == "upload"){
		$('.dc-wizard').show();
		$('.uploadmode').show();		
		$('.urlmode').hide();
		$('.textmode').hide();	
	}
	else if(format == "text"){
		$('.dc-wizard').hide();
		$('.textmode').show();
		$('.uploadmode').hide();
		$('.urlmode').hide();
	}
	else {
		$('.dc-wizard').show();
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
	//quality check choices
	$('.dqsoption').button();
	$( "#dqs-all" ).button().click(function(event){
		if($('#dqs-all').is(":checked")){
			$("input:checkbox.dqsoption").prop('checked', true).button("refresh").button("disable");
			$('#dqsopts').hide();
			$('.dqs-choose').hide();
		}
		else {
			$("input:checkbox.dqsoption").button("enable");		
			$('#dqsopts').show();
			$('.dqs-choose').show();
		}					
	});
	//import button
	$('.import-button').button().click(function (event){
		clearResultMessage();
		var payload = false;
		if(dacura.schema.importFormat == "text"){
			payload = $('#tximp').val();
			alert(payload);
		}
		else if(dacura.schema.importFormat == "url"){
			payload = $('#url-input').val();
		}
		else {
			payload = document.getElementById('file-input').files[0];
		}
		dacura.schema.importOntology(dacura.schema.importFormat, false, payload);
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
            clearResultMessage();
        }
    });
    dacura.schema.fetchSchema(drawSchema);
});
</script>