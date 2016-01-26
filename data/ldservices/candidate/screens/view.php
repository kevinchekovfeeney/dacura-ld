<script src='<?=$service->furl("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->furl("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id='fragment-header' class="dch">
	<span class='candidate-subhead fragment-title'></span>
	<span class='fragment-details'></span>
	<span class='fragment-path'></span>
</div>			
<div id='version-header' class="dch">
	<span class='vc candidate-subhead version-title'></span>
	<span class='vc version-created'></span>
	<span class='vc version-replaced'></span>
	<span class='vc version-details'></span>
</div>			

<div id='update-header' class="dch">
	<span class='candidate-subhead update-title'></span>
	<span class='update-created'></span>
	<span class='update-modified'></span>
	<span class='update-details'></span>
</div>			

<div id='tab-holder'>
	 <ul id="cand-pane-list" class="dch">
	 	<li><a href="#cand-contents">Contents</a></li>
	 	<li><a href="#cand-history">History</a></li>
	 	<li><a href="#cand-updates">Updates</a></li>
	 </ul>
	<div id="contents-holder">
	 	<div id="cand-contents" class='dch'>
	 		<div id='editor-msgs'></div>
			<?php echo $service->showLDResultbox($params);?>
			<?php echo $service->showLDEditor($params);?>
		</div>	
	</div>
 	<div id="cand-history">
		<div id="history-holder" class='ld-list dch'>
			<table class="history_table display">
				<thead>
					<tr>
						<th>Version</th>
						<th>Status</th>
						<th>Created</th>
						<th>Sortable Created</th>
						<th>Changed From</th>
						<th>Changed To</th>
					</tr>
				</thead>
				<tbody>
				</tbody>
			</table>		
		</div>
	</div>
 	<div id="cand-updates">
		<div id="update-holder" class='ld-list dch'>
			<table class="updates_table display">
				<thead>
				<tr>
					<th>ID</th>
					<th>Status</th>
					<th>From Version</th>
					<th>To Version</th>
					<th>Created</th>
					<th>Sortable Created</th>
					<th>Updated</th>
					<th>Sortable Updated</th>
					<th>Change Command</th>
					<th>Rollback Command</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>					
		</div>
	</div>
</div>
	
<div id="tabletemplates" class='dacura-templates'>
	<div id="toolheader-template">
		<table class='ld-invariants'>
			<thead>
				<tr>
					<th>Status</th>
					<th>Type</th>
					<th>Collection</th>
					<th>Created</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class='cand_status'></td>
					<td class='cand_type'></td>
					<td class='cand_owner'></td>
					<td class='cand_created'></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<script>

/*
 * Called once per page load - sets the candidate context of the view page
 */
dacura.candidate.showHeader = function(cand){
	options = { title: cand.id };
	if(typeof cand.title != "undefined"){
		options.subtitle = cand.title;
	}
	if(typeof cand.image != "undefined"){
		options.image = cand.image;
	}
	options.description = $('#toolheader-template').html();
	dacura.system.updateToolHeader(options);
	if(typeof cand.dataset_title != "undefined"){
		dtit = cand.dataset_title;			
	}
	else if(cand.did == "all"){
		dtit = cand.cid;
	}
	if(typeof cand.metadetails != "undefined"){
		metadetails = cand.metadetails;
	}
	else {
		metadetails = timeConverter(cand.created);
	}
	$('.cand_type').html("<span class='candidate-type'>" + cand.meta.type + "</span>");
	$('.cand_owner').html("<span class='candidate-owner'>" + dtit + "</span>");
	$('.cand_created').html("<span class='candidate-details'>" + metadetails + "</span>");
	$('.cand_status').html("<span class='candidate-status candidate-" + cand.latest_status + "'>" + cand.latest_status + "</span>");
	//alert("adding service breadcrumb");
    dacura.candidate.drawVersionHeader(cand);
}



function drawUpdateHeader(data){
	if(typeof data == "undefined" || data.length == 0){
		$('#update-holder').show();	
		$('#update_table').dataTable(dacura.candidate.getTableInitStrings(true)); 
		dacura.system.showErrorResult("No Updates Found", false, "", '#update_table .dataTables_empty');		
	}	
}

function drawUpdateListTable (data){	
	if(typeof data == "undefined" || data == null || data.length == 0){
		$('#update-holder').show();	
		$('.updates_table').dataTable(<?=$dacura_server->getServiceSetting('pending_datatable_init_string', "{}");?>); 
		dacura.system.showErrorResult("No Updates Found", false, "", '.updates_table .dataTables_empty');		
	}
	else {
		$('.updates_table tbody').html("");
		update_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('.updates_table tbody').append("<tr class='updaterow' id='update_" + update_urls.length + "'>" + 
			"<td>" + obj.eurid + "</td>" + 
			"<td>" + obj.status + "</td>" + 
			"<td>" + obj.from_version + "</td>" + 
			"<td>" + obj.to_version + "</td>" + 
			"<td>" + timeConverter(obj.createtime) + "</td>" + 
			"<td>" + obj.createtime + "</td>" + 
			"<td>" + timeConverter(obj.modtime) + "</td>" + 
			"<td>" + obj.modtime + "</td>" + 
			"<td class='rawjson'>" + obj.forward + "</td>" + 
			"<td class='rawjson'>" + obj.backward + "</td>" + "</tr>");
			update_urls[update_urls.length] = dacura.system.pageURL() + "/" + obj.targetid + "/update/" + obj.eurid;			
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
		$('.updates_table').dataTable(<?=$dacura_server->getServiceSetting('pending_datatable_init_string', "{}");?>).show();
	}
}

function drawHistorySection(data){
	if(typeof data == "undefined" || data == null || data.length == 0){
		$('#history-holder').show();	
		$('.history_table').dataTable(<?=$dacura_server->getServiceSetting('history_datatable_init_string', "{}");?>); 
		dacura.system.showErrorResult("No history information found", false, "", '.history_table .dataTables_empty');		
	}
	else {
		$('.history_table tbody').html("");
		history_urls = [];
		for (var i in data) {
			var obj = data[i];
			$('.history_table tbody').append("<tr class='history' id='history_" + history_urls.length + "'>" + 
				"<td>" + obj.version + "</td>" + 
				"<td>" + obj.status + "</td>" + 
				"<td>at " + timeConverter(obj.createtime) + " by update " + obj.created_by + "</td>" + 
				"<td>" + obj.createtime + "</td>" + 
				"<td class='rawjson'>" + obj.backward + "</td>" +
				"<td class='rawjson'>" + obj.forward + "</td>" + 
			+ "</tr>");
			history_urls[history_urls.length] = dacura.system.pageURL() + "/<?=$params['id']?>?version="+obj.version;			
		}
		$('.history').hover(function(){
			$(this).addClass('userhover');
		}, function() {
		    $(this).removeClass('userhover');
		});
		$('.history').click( function (event){
			window.location.href = history_urls[this.id.substr(8)]
	    }); 
		$('#history-holder').show();	
		$('.history_table').dataTable(<?=$dacura_server->getServiceSetting('history_datatable_init_string', "{}");?>).show();
	}
	
	
}

function drawFragmentHeader(data){
	if(typeof data.fragment_id != "undefined"){
		fids = data.fragment_id.split("/");
		fid = fids[fids.length -1];
		fdets = data.fragment_details;
		fpaths = data.fragment_paths;
		fpathhtml = "<div class='fragment-paths'>";
		for(i in fpaths){
			fpathhtml += "<span class='fragment-path'>";
			fpathhtml += "<span class='fragment-step'>" + data.id + "</span><span class='fragment-step'>";
			fpathhtml += fpaths[i].join("</span><span class='fragment-step'>");
			fpathhtml += "</span><span class='fragment-step'>" + data.fragment_id + "</span></span>";
		}
		fpathhtml += "</div>";
		$('#fragment-data').html("<span class='fragment-title-label'>Fragment</span> <span class='fragment-title'>" + fid + "</span><span class='fragment-details'>" + fdets + "</span>" + fpathhtml);
		$('#fragment-data').show();
	}	
}

function isUpdateID(id){
	return id.substr(0,7) == "update/";
}

$('document').ready(function(){
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
	dacura.system.init({"mode": "tabbed"});
	dacura.editor.init({"targets": { resultbox: "#editor-msgs", errorbox: "#editor-msgs", busybox: '#cand-contents'}, 
		"args": <?=json_encode($params['args']);?>});
	dacura.editor.getMetaEditHTML = function(meta){
		/*if(dacura.editor.options.format == "json"){
			var html = "<textarea id='ldmeta-input'>";
			if(typeof meta == 'undefined'){
				meta = {};
			} 
			$('#meta-edit-table').hide();
			html += JSON.stringify(meta, null, 4) + "</textarea>";
			return html; 				
		}
		else {*/
			$('#entstatus').val(meta.status);	
			$('#meta-edit-table').show();
			//$('#entstatus').val(meta.status);	
			return "";
		//}
	

	};

	dacura.editor.getInputMeta = function(){
		var meta = {"status": $('#entstatus').val()};
		return meta;
	};
	
	var onw = function (obj){
		//here's where we load the history and the update list...
		if(typeof(obj.history) != "undefined" && obj.history.length > 0){
			drawHistorySection(obj.history);
			$('#history-holder').show();
		}
		if(typeof obj.updates != "undefined" && obj.updates.length > 0){
			drawUpdateListTable(obj.updates);
			$('#update-holder').show();
		}
		dacura.editor.load("<?=$params['id']?>", dacura.candidate.fetch, dacura.candidate.update, obj);
		dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + obj.id , obj.id);
		dacura.system.styleJSONLD();		
		$('#cand-pane-list').show();
	};
	dacura.candidate.fetch("<?=$params['id']?>", <?=json_encode($params['args']);?>, onw, { resultbox: "#editor-msgs", errorbox: "#editor-msgs", busybox: '#cand-contents'});
});
</script>

