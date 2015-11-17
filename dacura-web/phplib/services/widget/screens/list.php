<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

 <div id='tab-holder'>
	 <ul id="ld-pane-list" class="dch">
	 	<li><a href="#ld-list">User Interface Widgets</a></li>
	 	<!-- <li><a href="#update-list">LD Entity Update Queue</a></li> -->
	 	<li><a href="#create-widget">Create New UI Widget</a></li>
	 </ul>
	<div id="create-widget">
		<div class="tab-top-message-holder">
			<div class="tool-create-info tool-tab-info" id="create-msgs"></div>
		</div>
		<div id="create-holder" class="dch">
			<div id='entity-graphs'>
			</div>
			<div id='entity-classes' class='ld-list dch'>
				<table id="entity-class-table" class="dcdt display">
					<thead>
					<tr>
						<th>Name</th>
						<th>Label</th>
						<th>Properties</th>
						<th>Version</th>
						<th>Created</th>
						<th>Sortable Created</th>
						<th>Modified</th>
						<th>Sortable Modified</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
			<div id='class-properties' class='ld-list dch'>
				<table id="entity-property-table" class="dcdt display">
					<thead>
					<tr>
						<th>Name</th>
						<th>Range</th>
						<th>Version</th>
						<th>Created</th>
						<th>Sortable Created</th>
						<th>Modified</th>
						<th>Sortable Modified</th>
					</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</div>
	<div id="ld-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="ld-msgs"></div>
		</div>
		<div id="ld-holder" class='ld-list dch'>
			<table id="ld_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<th>Status</th>
					<th>Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Modified</th>
					<th>Sortable Modified</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
	<div id="update-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="update-msgs"></div>
		</div>
		<div id="update-holder" class="ld-list dch">
			<table id="update_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Target</th>
					<th>Type</th>
					<th>Collection</th>
					<th>Dataset</th>
					<th>Status</th>
					<th>From Version</th>
					<th>To Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Modified</th>
					<th>Sortable Modified</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>
</div>

<script>

dacura.widget.getTableInitStrings = function(cands){
	if(typeof cands == "undefined"){
		var init = <?=$dacura_server->getServiceSetting('ld_datatable_init_string', "{}");?>;
	} else {
		var init = <?=$dacura_server->getServiceSetting('updates_datatable_init_string', "{}");?>;
	}
	return init;
}

var entity_urls = [];
var update_urls = [];

dacura.widget.drawEntityListTable = function(data){		
	if(typeof data == "undefined" || data.length == 0){
		$('#ld-holder').show();	
		$('#ld_table').dataTable(dacura.widget.getTableInitStrings()).show(); 
		dacura.system.writeErrorMessage("No Entities Found", '#ld_table .dataTables_empty');		
	}
	else {
		$('#ld_table tbody').html("");
		entity_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('#ld_table tbody').append("<tr class='entityrow' id='ent_" + entity_urls.length + "'>" + 
			"<td>" + obj.id + "</td>" + 
			"<td>" + obj.type + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + "</tr>");
			if(obj.type == "candidate"){
				s = obj.type;
			}	
			else if(obj.type == "graph" || obj.type == "ontology"){
				s = "schema";
			}
			else {
				s = "ld";
			}
			entity_urls[entity_urls.length] = dacura.system.pageURL(obj.collectionid, obj.datasetid, s) + "/" + obj.id;
		}
		$('.entityrow').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.entityrow').click( function (event){
			window.location.href = entity_urls[this.id.substr(4)]
	    }); 		
		$('#ld-holder').show();	
		$('#ld_table').dataTable(dacura.widget.getTableInitStrings());
	}
}

dacura.widget.drawUpdateListTable = function(data){		
	if(typeof data == "undefined" || data.length == 0){
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.widget.getTableInitStrings(true)); 
		dacura.system.writeErrorMessage("No Updates Found", '#update_table .dataTables_empty');		
	}
	else {
		$('#update_table tbody').html("");
		update_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('#update_table tbody').append("<tr class='updaterow' id='update_" + update_urls.length + "'>" + 
			"<td>" + obj.eurid + "</td>" + 
			"<td>" + obj.targetid + "</td>" + 
			"<td>" + obj.type + "</td>" + 
			"<td>" + obj.collectionid + "</td>" + 
			"<td>" + obj.datasetid + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.from_version + "</td>" + 
			"<td>" + obj.to_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + "</tr>");
			if(obj.type == "candidate"){
				s = obj.type;
			}	
			else if(obj.type == "graph" || obj.type == "ontology"){
				s = "schema";
			}
			else {
				s = "ld";
			}
			update_urls[update_urls.length] = dacura.system.pageURL(obj.collectionid, obj.datasetid, s) + "/" + obj.targetid + "/update/" + obj.eurid;			
		}
		$('.updaterow').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.updaterow').click( function (event){
			window.location.href = update_urls[this.id.substr(7)]
	    }); 
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.widget.getTableInitStrings(true)).show();
	}
}

$(function() {
	dacura.system.init({"mode": "tool", "tabbed": true});
	dacura.widget.fetchentitylist(dacura.widget.drawEntityListTable, {resultbox: "#ld-msgs", errorbox: "#ld-msgs", busybox: "#ld-list"});
	dacura.widget.fetchClasses(dacura.widget.drawEntityClassTable, {resultbox: "#create-msgs", errorbox: "#create-msgs", busybox: "#create-widget"});
	//dacura.ld.fetchupdatelist(dacura.ld.drawUpdateListTable, {resultbox: "#update-msgs", errorbox: "#update-msgs", busybox: "#update-list"}); 
	$('#ld-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
           $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
    $('#create-holder').show();
});
	
</script>