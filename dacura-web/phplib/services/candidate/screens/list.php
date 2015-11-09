<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

 <div id='tab-holder'>
	 <ul id="candidate-pane-list" class="dch">
	 	<li><a href="#candidate-list">Candidate Queue</a></li>
	 	<li><a href="#update-list">Candidate Update Queue</a></li>
	 	<li><a href="#create-candidate">Create Candidate</a></li>
	 </ul>
	<div id="create-holder" class="dch">
		<div id="create-candidate">
			<div class="tool-create-info tool-tab-info" id="create-msgs"></div>
			<?php echo $service->showLDEditor($params);?>
		</div>
	</div>
	<div id="candidate-holder" class="dch">
		<div id="candidate-list">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="candidate-msgs"></div>
			</div>
			<table id="candidate_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<?php if (isset($params['show_collection']) && $params['show_collection']) echo "<th>Collection ID</th>"?>
					<th>Status</th>
					<th>Version</th>
					<th>Schema Version</th>
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
	<div id="update-holder" class="dch">
		<div id="update-list">
			<div class="tab-top-message-holder">
				<div class="tool-tab-info" id="update-msgs"></div>
			</div>
			<table id="update_table" class="dcdt display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Candidate</th>
					<?php if (isset($params['show_collection']) && $params['show_collection']) echo "<th>Collection ID</th>"?>
					<th>Status</th>
					<th>From Version</th>
					<th>To Version</th>
					<th>Schema Version</th>
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

dacura.candidate.getCorrectedTableInitStrings = function(cands){
	if(typeof cands == "undefined"){
		var init = <?=$dacura_server->getServiceSetting('candidate_datatable_init_string', "{}");?>;
	<?php if (isset($params['show_collection']) && $params['show_collection']){?>
		init.aoColumns[5]["iDataSort"] = 7;	
		init.aoColumns[7]["iDataSort"] = 9;
		init.order[0] = 6;			
		init.aoColumns.unshift(null);
	<?php } ?>
	} else {
		var init = <?=$dacura_server->getServiceSetting('updates_datatable_init_string', "{}");?>;
		<?php if (isset($params['show_collection']) && $params['show_collection']) {?>
			init.aoColumns[6]["iDataSort"] = 8;	
			init.aoColumns[8]["iDataSort"] = 10;
			init.order[0] = 7;			
			init.aoColumns.unshift(null);
		<?php } ?>
	}
	return init;
}

var cand_urls = [];


dacura.candidate.drawCandidateListTable = function(data){		
	if(typeof data == "undefined"){
		$('#candidate-holder').show();	
		$('#candidate_table').dataTable(dacura.candidate.getCorrectedTableInitStrings()).show(); 
		dacura.system.writeErrorMessage("No Candidates Found", '.dataTables_empty');		
	}
	else {
		$('#candidate_table tbody').html("");
		cand_urls = [];
		
		for (var i in data) {
			var obj = data[i];
			var type = getMetaProperty(obj.meta, "type", "unknown");
			$('#candidate_table tbody').append("<tr class='candrow' id='can_" + cand_urls.length + "'>" + 
			"<td>" + obj.id + "</td>" + 
			"<td>" + type + "</td>" + 
			<?php if (isset($params['show_collection']) && $params['show_collection']) echo '"<td>" + obj.collectionid + "</td>" + '?>
			<?php if (isset($params['show_dataset']) && $params['show_dataset']) echo '"<td>" + obj.datasetid + "</td>" + '?>
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.version + "</td>" + 
			"<td>1</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + "</tr>");	
			cand_urls[cand_urls.length] = dacura.system.pageURL(obj.collectionid, obj.datasetid, "candidate") + "/" + obj.id;					
		}
		$('.candrow').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.candrow').click( function (event){
			window.location.href = cand_urls[this.id.substr(4)]		
	    }); 
		$('#candidate-holder').show();	
		$('#candidate_table').dataTable(dacura.candidate.getCorrectedTableInitStrings());
	}
}

dacura.candidate.drawUpdateListTable = function(data){		
	if(typeof data == "undefined"){
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.candidate.getCorrectedTableInitStrings(true)); 
		dacura.system.writeErrorMessage("No Candidates Found", '.dataTables_empty');		
	}
	else {
		$('#update_table tbody').html("");
		for (var i in data) {
			var obj = data[i];
			$('#update_table tbody').append("<tr id='update" + obj.curid + "'>" + 
			"<td>" + obj.eurid + "</td>" + 
			"<td>" + obj.targetid + "</td>" + 
			<?php if (isset($params['show_collection']) && $params['show_collection']) echo '"<td>" + obj.collectionid + "</td>" + '?>
			<?php if (isset($params['show_dataset']) && $params['show_dataset']) echo '"<td>" + obj.datasetid + "</td>" + '?>
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.from_version + "</td>" + 
			"<td>" + obj.to_version + "</td>" + 
			"<td>" + obj.schema_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + "</tr>");
			$('#update'+obj.curid).hover(function(){
				$(this).addClass('userhover');
			}, function() {
			    $(this).removeClass('userhover');
			});
			$('#update'+obj.curid).click( function (event){
				window.location.href = dacura.system.pageURL() + "/update/" + this.id.substr(6);
		    }); 
		}	
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.candidate.getCorrectedTableInitStrings(true)).show();
	}
}

$(function() {
	dacura.system.init({"mode": "tool", "tabbed": true, "targets": {resultbox: "#create-msgs", errorbox: "#create-msgs", busybox: "#create-holder"}});
	dacura.editor.init({"editorheight": "200px"});
	dacura.editor.load(false, dacura.candidate.fetchNGSkeleton, dacura.candidate.create);
	$('#create-holder').show();
	dacura.candidate.fetchupdatelist(); 
	dacura.candidate.fetchcandidatelist();
	$('#candidate-pane-list').show();
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
});
	
</script>