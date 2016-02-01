<script src='<?=$service->furl("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->furl("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "dataTables.jqueryui.css")?>" />

<div id='tab-holder'>
	 <ul id="schema-pane-list" class="dch">
	 	<li><a href="#graphs-list">Graphs</a></li>
	 	<li><a href="#create-graph">Create Graph</a></li>
	 </ul>
	<div id="graphs-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="graphs-msgs"></div>
		</div>
		<div id='graphs-table-holder' class='dch'></div>
	</div>
	<div id="create-graph">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="create-msgs">
				<?php echo $service->showLDResultbox($params);?>
			</div>
		</div>
		<div id='create-holder' class='dch'>
			<table class='dc-wizard'>
				<tr><th></th><td></td></tr>
				<tr><th>Graph Name</th><td><input type='text' id='graphname'></td></tr>
			</table>
			<div class="tool-buttons">
	   			<button class="dacura-button create-graph-button" id="create-button">Create Graph</button>
	      	</div>
		</div>
	</div>
</div>

<div id="tabletemplates" style="display:none">
	<div id="graphs-template">
		<table class="graphs_table display">
			<thead>
			<tr>
				<th>ID</th>
				<th>Version</th>
				<th>Status</th>
				<th>History</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>
	</div>
</div>
<script>

function drawGraphs(graphs){
	var k = $('#graphs-template').html();
	$('#graphs-table-holder').html(k);
	var ids = [];
	for (var key in graphs) {
	  if (graphs.hasOwnProperty(key)) {
		  $('#graphs-table-holder .graphs_table tbody').append(
			  "<tr class='graph-list' id='graph_" + ids.length + "'>" + 
			  	"<td>" + graphs[key].id + "</td>" + 
				"<td>" + graphs[key].version + "</td>" + 
				"<td>" + graphs[key].status + "</td>" + 
				"<td>" + timeConverter(graphs[key].createtime) + "</td></tr>" 
		   );
			$('#graph_' + ids.length).click( function (event){
				//alert(ids[this.id.substr(6)]);
				window.location.href = dacura.system.pageURL() + "/" + ids[this.id.substr(6)];
		  		//dacura.schema.fetchGraph(ids[this.id.substr(6)], drawGraph);
		    });
		  	ids[ids.length] = graphs[key].id;		  	 		  	
		}
	}
	$('.graph-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	$('#graphs-table-holder').show();
	$('#graphs-table-holder .graphs_table').dataTable({"jQueryUI": true, "searching": false, "info": false});
	if(ids.length == 0){
		dacura.system.showErrorResult("", false, "No Graphs Found in system", '#graphs-table-holder .dataTables_empty');		
	}
}

function showCreateResult(obj){
	if(typeof(dacura.ldresult) != "undefined"){
		dacura.ldresult.update_type = "create";
		var cancel = function(){
			$('#create-msgs').html("");
		};
		dacura.ldresult.showDecision(json, '#create-msgs', cancel);			
	}
	else {
		dacura.system.showJSONErrorResult(json, '#create-msgs'); 	
	}
}

function initDecorations(){
	//view format choices
	$('#create-button').button().click(function (event){
		dacura.schema.createGraph($('#graphname').val(), {scrollto: '#create-msgs', resultbox: '#create-msgs', errorbox: '#create-msgs', busybox: '#create-graph'}, showCreateResult);
	});
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

function showUpdateNSButton(){
	$('#save-namespaces').show();
}

$(function() {
	initDecorations();
	dacura.tool.init({"tabbed": "tab-holder"});
	$('#create-holder').show();
	dacura.schema.entity_type = "graph";
	dacura.schema.fetchentitylist(drawGraphs, {resultbox: '#graphs-msgs', errorbox: '#graphs-msgs', busybox: '#graphs-lists'});
});
</script>