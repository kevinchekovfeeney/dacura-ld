<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<div id="page-holder">
	<div id='graph-msgs'></div>

	<div id='summary-status'><div id='graph-description'></div>
		<table class='graph-summary'>
			<tbody>
				<tr>
					<td></td>
					<td></td>
				</tr>
			</tbody>
		</table>
	</div>
	<div id='checking-status'>
		Graph Manager: <select id='checking-select'>
			<?= $service->getCheckingOptions(); ?>
		</select>
		<a class="button2 dch" id='checking-enable' href='javascript:enableChecking()'>Enable</a>
	</div>
	<div id="ontology-list">
		<table class="ontology_table display">
			<thead>
			<tr>
				<th>ID</th>
				<th>URL</th>
				<th>Title</th>
				<th>Status</th>
				<th>Version</th>
				<th>Include</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
	<div id='summary-dashboard' class="dacura-dashboard-panel">
		<a href='<?=$service->get_service_url()?>/export'>
				<div class='dacura-dashboard-button' id='dacura-export-button' title="Export data from the wiki to CSV">
				<img class='dacura-button-img' src="<?=$service->url("image", "buttons/export.png")?>">
				<div class="dacura-button-title">Imported Ontologies</div>
			</div>
		</a>
		<a href='<?=$service->get_service_url()?>/status'>
				<div class='dacura-dashboard-button' id='dacura-sources-button' title="Get an up to date status of the wiki">
				<img class='dacura-button-img' src="<?=$service->url("image", "buttons/status.png")?>">
				<div class="dacura-button-title">Local Ontology</div>
			</div>
		</a>
		<a href='<?=$service->get_service_url()?>/history'>
				<div class='dacura-dashboard-button' id='dacura-sources-button' title="Historical Statistics of wiki data collection">
				<img class='dacura-button-img' src="<?=$service->url("image", "buttons/stats.jpg")?>">
				<div class="dacura-button-title">Data Structure</div>
			</div>
		</a>
		<a href='<?=$service->get_service_url()?>/test'>
				<div class='dacura-dashboard-button' id='dacura-sources-button' title="Test exporting variables and pages">
				<img class='dacura-button-img' src="<?=$service->url("image", "buttons/syntax.png")?>">
				<div class="dacura-button-title">Forms</div>
			</div>
		</a>
	</div>
	<div id='summary-footnote' style="clear:both">
		??
	</div>
</div>

<script>

function drawSummaryTable(graph){
	$('.graph-summary tbody').append("<tr><th>Local ID</th><td>" + graph.local_id + "</td></tr>");
	$('.graph-summary tbody').append("<tr><th>Instance Graph</th><td>" + graph.instance_graph + "</td></tr>");
	$('.graph-summary tbody').append("<tr><th>Schema Graph</th><td>" + graph.schema_graph + "</td></tr>");
	$('.graph-summary tbody').append("<tr><th>Schema Namespace</th><td>" + graph.schema + "</td></tr>");
	$('#graph-description').html(graph.description);
}

function drawCheckingStatusBox(graph){
	if(typeof graph.checking == "undefined" || graph.checking != "dqs"){
		$('#checking-enable').show();
	}
	else {
		$('checking-select').val(graph.checking);
	} 			

}

var ids = [];

function drawOntologies(onts){
	for (var key in onts) {
	  	if (onts.hasOwnProperty(key)) {
			if(!isEmpty(onts[key])){
			 	$('.ontology_table tbody').append("<tr class='ontology-list'><td>" + onts[key]['id'] + "</td><td>" + 
				  	onts[key]['url'] + "</td><td>" + onts[key]["title"] +
				  	"</td><td>" + onts[key]["status"] + "</td><td>" + onts[key]["version"] + "</td><td>" + 
				  	"<input type='checkbox' + id='ontology_" + ids.length + "'" + "></td></tr>");
			  	ids[ids.length] = onts[key].id;		  	 		  	
				  	
			}
		}
	}
	$('.ontology_table').dataTable({"jQueryUI": true, "searching": false, "info": false});
}

var drawGraph = function(schema){
	clearResultMessage();
	for (var key in schema.graphs) {
		  if (schema.graphs.hasOwnProperty(key)) {
			if((typeof schema.graphs[key].local_id != "undefined") && schema.graphs[key].local_id == '<?= $params['graphid'];?>'){
				var graph = schema.graphs[key];
				drawSummaryTable(graph);
				drawOntologies(schema.ontologies);
				
			}
			else {
			}
		 }
	}
};

function enableChecking(){
	var onts = [];
	for (index = 0; index < ids.length; ++index) {	
		var full_id = ids[index];
		if($('#ontology_' + index).is(":checked")){
			onts.push(full_id);
		}
		else {
		}
	}
	dacura.schema.validateGraphOntologies(onts);
	//ids[this.id.substr(9)]
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}


$(function() {
	dacura.system.init({"mode": "tool", "targets": {resultbox: "#graph-msgs", errorbox: "#graph-msgs", busybox: "#page-holder"}});
	dacura.schema.fetchSchema(drawGraph);
});
</script>