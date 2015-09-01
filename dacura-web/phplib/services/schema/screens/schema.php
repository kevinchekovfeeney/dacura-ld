<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id='tab-holder'>
	 <ul id="schema-pane-list" class="dch">
	 	<li><a href="#graph-summary">Summary</a></li>
	 	<li><a href="#graphs-list">Graphs</a></li>
	 	<li><a href="#namespaces-list">Namespaces</a></li>
	 	<li><a href="#import-ontology">Import Ontology</a></li>
	 	<li><a href="#ontology-list">Ontologies</a></li>
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
								<span id="checking-options" class="view-option view-mode">
									<input type="checkbox" class="doption" id="dqs" name="dqs" checked="checked"><label for="dqs">Check with Quality Service</label>
								</span>
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
		</div>
	</div>
	<div id="namespaces-holder">
		<div id="namespaces-list" class="dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="namespaces-msgs"></div>
			</div>
			<div id='namespaces-table-holder'></div>
			<div class="tool-buttons">
	   			<button class="dacura-button save-ns-button dch" id="save-namespaces">Save Changes</button>			
			</div>			
		</div>
	</div>
	<div id="graphs-holder">
		<div id="graphs-list" class="dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="graphs-msgs"></div>
			</div>
			<div id='graphs-table-holder'></div>
		</div>
	</div>
	<div id="graph-summary-holder">
		<div id="graph-summary" class="dch">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="graph-summary-msgs"></div>
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
				<th>Type</th>
				<th>Title</th>
				<th>Status</th>
				<th>Version</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="graphs-template">
		<table class="graphs_table display">
			<thead>
			<tr>
				<th>ID</th>
				<th>Schema</th>
				<th>Description</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id="namespaces-template">
		<table class="namespaces_table display">
			<thead>
			<tr>
				<th>Shorthand</th>
				<th>URL</th>
				<th>Action</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>
<script>

dacura.schema.importFormat = "url";

function drawOntologies(onts){
	var k = $('#ontology-template').html();
	$('#ontology-table-holder').html(k);
	var ids = [];
	for (var key in onts) {
	  	if (onts.hasOwnProperty(key)) {
			if(!isEmpty(onts[key])){
			 	$('#ontology-table-holder .ontology_table tbody').append("<tr class='ontology-list' id='ontology_" + ids.length + "'><td>" + onts[key]['id'] + "</td><td>" + 
				  	onts[key]['url'] + "</td><td>" + onts[key]["title"] + "</td><td>" + onts[key]["type"] +
				  	"</td><td>" + onts[key]["status"] + "</td><td>" + onts[key]["version"] + "</td></tr>");
			  	$('#ontology_' + ids.length).click( function (event){
					window.location.href = ids[this.id.substr(9)];
			    });
			  	ids[ids.length] = onts[key].id;		  	 		  	
				  	
			}
		}
	}
	$('.ontology-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	
	$('#ontology-table-holder .ontology_table').dataTable({"jQueryUI": true, "searching": false, "info": false});
		
}

function drawNamespaces(prefixes){
	var k = $('#namespaces-template').html();
	$('#namespaces-table-holder').html(k);
	for (var key in prefixes) {
	  if (prefixes.hasOwnProperty(key)) {
		  if(!isEmpty(prefixes[key])){
		  	var del = getDelNSHTML(key);
		  	$('#namespaces-table-holder .namespaces_table tbody').append("<tr><td>" + prefixes[key]['shorthand'] + "</td><td>" + prefixes[key]["url"] + "</td><td class='action'>" + del + "</td></tr>");
		  }
	  }
	}
	var add = getAddNSHTML();
	$('#namespaces-table-holder .namespaces_table tbody').append("<tr><td><span class='dch'>z</span><input id='newns-prefix'></td><td><input id='newns-url'></td><td class='action'>" + add + "</td></tr>");
	$('#namespaces-table-holder .namespaces_table').dataTable({"jQueryUI": true, "searching": false, "info": false});
	$('.add-ns').button({
    	text: false,
		icons: {
        	primary: "ui-icon-plus"
      	}
	});
	$('.del-ns').button({
	  	text: false,
        icons: {
        	primary: "ui-icon-minus"
      	}
	});
}

function drawGraphs(graphs){
	var k = $('#graphs-template').html();
	$('#graphs-table-holder').html(k);
	var ids = [];
	for (var key in graphs) {
	  if (graphs.hasOwnProperty(key)) {
		  $('#graphs-table-holder .graphs_table tbody')
		    
		  	.append("<tr class='graph-list' id='graph_" + ids.length + "'><td>" + key + "</td><td>" + graphs[key].schema + "</td><td>" + graphs[key].description +"</td></tr>");
		  	$('#graph_' + ids.length).click( function (event){
				window.location.href = dacura.system.pageURL() + "/" + ids[this.id.substr(6)];
		  		//dacura.schema.fetchGraph(ids[this.id.substr(6)], drawGraph);
		    });
		  	ids[ids.length] = graphs[key].local_id;		  	 		  	
		}
	}
	$('.graph-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	$('#graphs-table-holder .graphs_table').dataTable({"jQueryUI": true, "searching": false, "info": false});
}

var drawGraph = function(graph){
	clearResultMessage();
	alert(JSON.stringify(graph));
}

var drawSchema = function(sch){
	dacura.schema.currentschema = sch;
	drawNamespaces(sch.namespaces);
	drawGraphs(sch.graphs);
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
	$( "#checking-options" ).buttonset();
	$( "#dqs" ).click(function(event){
		clearResultMessage();
	});
	//import button
	$('.import-button').button().click(function (event){
		clearResultMessage();
		var dqs = false;
		if($("#dqs").is(':checked')){
			dqs = true;
		}
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
		dacura.schema.importOntology(dacura.schema.importFormat, dqs, payload);
	});
	//save Namespaces button
	$('.save-ns-button').button().click(function (event){
		clearResultMessage();
		dacura.schema.updateSchema(drawSchema);		
	}); 
}

function addNS(){
	var sh = $('#newns-prefix').val();
	var url = $('#newns-url').val();
	//if(typeof dacura.schema.currentschema.namespaces[sh] != "undefined"){
	//	alert(sh + "is already defined!");
	//}
	//else {
		dacura.schema.currentschema.namespaces["_:BN"] = {"shorthand": sh, "url": url};	
		drawNamespaces(dacura.schema.currentschema.namespaces);	
		showUpdateNSButton();	
	//}
}

function delNS(id){
	if(typeof dacura.schema.currentschema.namespaces[id] != "undefined"){
		dacura.schema.currentschema.namespaces[id] = {};
		drawNamespaces(dacura.schema.currentschema.namespaces);		
		showUpdateNSButton();
	}
	else {
		alert("not deleted " + id);
	}
}

function getAddNSHTML(sh){
	var html = "<a class='add-ns button' href='javascript:addNS()'>Add</a>";
	return html;
}

function getDelNSHTML(sh){
	var html = "<a class='del-ns button' href='javascript:delNS(\"" + sh + "\")'>Delete</a>";
	return html;
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

function showUpdateNSButton(){
	$('#save-namespaces').show();
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