<script src='<?=$service->furl("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->furl("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "dataTables.jqueryui.css")?>" />

<div id='fragment-header' class="dch">
	<span class='entity-subhead fragment-title'></span>
	<span class='fragment-details'></span>
	<span class='fragment-path'></span>
</div>			
<div id='version-header' class="dch">
	<span class='entity-subhead version-title'></span>
	<span class='version-created'></span>
	<span class='version-replaced'></span>
	<span class='version-details'></span>
</div>			

<div id='update-header' class="dch">
	<span class='entity-subhead update-title'></span>
	<span class='update-created'></span>
	<span class='update-modified'></span>
	<span class='update-details'></span>
</div>			

<div class="dch" id="show-entity">
	<?php echo $service->showLDEditor($params);?>
	<div id="entity-sub-sections" >
		<div id="history-section" class="dch">
			<div class="tool-section-header">
				<span class="section-title">Entity History</span>
			</div>
		</div>
		<div id="pending-section" class="dch">
			<div class="tool-section-header">
				<span class="section-title">Entity Update Queue</span>
			</div>
		</div>
	</div>
</div>
	
<div id="tabletemplates" class='dacura-templates'>
	<div id="header-template">
		<table class='entity-invariants'>
			<thead>
				<tr>
					<th>Status</th>
					<th>Type</th>
					<th>Dataset</th>
					<th>Details</th>
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
	<div id="history-template">
		<table class="history_table display">
			<thead>
				<tr>
					<th>Version</th>
					<th>Schema Version</th>
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
	<div id="updates-template">
		<table class="updates_table display">
			<thead>
			<tr>
				<th>Status</th>
				<th>From Version</th>
				<th>To Version</th>
				<th>Created</th>
				<th>Sortable Created</th>
				<th>Updated</th>
				<th>Sortable Updated</th>
				<th>Change From</th>
				<th>Change To</th>
			</tr>
			</thead>
			<tbody></tbody>
		</table>			
	</div>	
</div>
<script>

/*
 * Called once per page load - sets the entity context of the view page
 */
dacura.ld.showHeader = function(ent){
	options = { subtitle: ent.id };
	if(typeof ent.title != "undefined"){
		options.subtitle = ent.title;
	}
	if(typeof cand.image != "undefined"){
		options.image = ent.image;
	}
	options.description = $('#header-template').html();
	dacura.system.updateToolHeader(options);
	if(typeof ent.dataset_title != "undefined"){
		dtit = ent.dataset_title;			
	}
	else if(ent.did == "all"){
		dtit = ent.cid;
	}
	if(typeof ent.metadetails != "undefined"){
		metadetails = ent.metadetails;
	}
	else {
		metadetails = timeConverter(ent.created);
	}
	$('.cand_type').html("<span class='entity-type'>" + ent.type + "</span>");
	$('.cand_owner').html("<span class='entity-owner'>" + dtit + "</span>");
	$('.cand_created').html("<span class='entity-details'>" + metadetails + "</span>");
	$('.cand_status').html("<span class='entity-status entity-" + ent.latest_status + "'>" + ent.latest_status + "</span>");
    dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + ent.id , options.subtitle);	
}


function drawVersionHeader(data){
	
}


function drawUpdateHeader(data){
	
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
	dacura.system.init({"mode": "tool"});
	dacura.editor.init();
	dacura.editor.load("<?=$params['id']?>", dacura.ld.fetch, dacura.ld.update);
	$('#show-entity').show();
});
</script>

