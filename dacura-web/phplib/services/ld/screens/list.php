<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

 <div id='tab-holder'>
	 <ul id="ld-pane-list" class="dch">
	 	<li><a href="#ld-list">LD Entity Queue</a></li>
	 	<li><a href="#update-list">LD Entity Update Queue</a></li>
	 	<li><a href="#create-ld">Create LD Entity</a></li>
	 </ul>
		<div id="create-ld">
			<div class="tool-create-info tool-tab-info" id="create-msgs"></div>
			<div id="create-holder" class="dch">
				<?php echo $service->showLDEditor($params);?>
			</div>
	</div>
	<div id="ld-list">
		<div class="tab-top-message-holder">
			<div class="tool-tab-info" id="ld-msgs"></div>
		</div>
		<div id="ld-holder" class="dch">
			<table id="ld_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<th>Collection</th>
					<th>Dataset ID</th>
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
		<div id="update-holder" class="dch">
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
	<div id="blankplaceholder" style="height: 80px"></div>
</div>

<script>

dacura.ld.getTableInitStrings = function(cands){
	if(typeof cands == "undefined"){
		var init = <?=$dacura_server->getServiceSetting('ld_datatable_init_string', "{}");?>;
	} else {
		var init = <?=$dacura_server->getServiceSetting('updates_datatable_init_string', "{}");?>;
	}
	return init;
}

var entity_urls = [];
var update_urls = [];

dacura.ld.drawEntityListTable = function(data){		
	if(typeof data == "undefined"){
		$('#ld-holder').show();	
		$('#ld_table').dataTable(dacura.ld.getTableInitStrings()).show(); 
		dacura.system.writeErrorMessage("No Entities Found", '.dataTables_empty');		
	}
	else {
		$('#ld_table tbody').html("");
		entity_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('#ld_table tbody').append("<tr class='entityrow' id='ent_" + entity_urls.length + "'>" + 
			"<td>" + obj.id + "</td>" + 
			"<td>" + obj.type + "</td>" + 
			"<td>" + obj.collectionid + "</td>" + 
			"<td>" + obj.datasetid + "</td>" + 
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
		$('#ld_table').dataTable(dacura.ld.getTableInitStrings());
	}
}

dacura.ld.drawUpdateListTable = function(data){		
	if(typeof data == "undefined"){
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.ld.getTableInitStrings(true)); 
		dacura.system.writeErrorMessage("No Updates Found", '.dataTables_empty');		
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
		$('#update_table').dataTable(dacura.ld.getTableInitStrings(true)).show();
	}
}

$(function() {
	dacura.system.init({"mode": "tool", "tabbed": true});
	dacura.editor.init({"editorheight": "300px", "targets": {resultbox: "#create-msgs", errorbox: "#create-msgs", busybox: "#create-holder"}});
	dacura.editor.load(false, false, dacura.ld.create);
	dacura.ld.fetchentitylist();
	dacura.ld.fetchupdatelist(); 
	$('#ld-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
    $('#create-holder').show();
});
	
</script>