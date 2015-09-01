<script src='<?=$service->url("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->url("js", "dataTables.jqueryui.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "dataTables.jqueryui.css")?>" />

<div id='fragment-header' class="dch">
	<span class='candidate-subhead fragment-title'></span>
	<span class='fragment-details'></span>
	<span class='fragment-path'></span>
</div>			
<div id='version-header' class="dch">
	<span class='candidate-subhead version-title'></span>
	<span class='version-created'></span>
	<span class='version-replaced'></span>
	<span class='version-details'></span>
</div>			

<div id='update-header' class="dch">
	<span class='candidate-subhead update-title'></span>
	<span class='update-created'></span>
	<span class='update-modified'></span>
	<span class='update-details'></span>
</div>			

<div class="dch" id="show-candidate">
	<?php echo $service->showLDEditor($params);?>
	<div id="candidate-sub-sections" >
		<div id="history-section" class="dch">
			<div class="tool-section-header">
				<span class="section-title">Candidate History</span>
			</div>
		</div>
		<div id="pending-section" class="dch">
			<div class="tool-section-header">
				<span class="section-title">Candidate Update Queue</span>
			</div>
		</div>
	</div>
</div>
	
<div id="tabletemplates" class='dacura-templates'>
	<div id="header-template">
		<table class='candidate-invariants'>
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
 * Called once per page load - sets the candidate context of the view page
 */
dacura.candidate.showHeader = function(cand){
	options = { subtitle: cand.id };
	if(typeof cand.title != "undefined"){
		options.subtitle = cand.title;
	}
	if(typeof cand.image != "undefined"){
		options.image = cand.image;
	}
	options.description = $('#header-template').html();
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
	$('.cand_type').html("<span class='candidate-type'>" + cand.type + "</span>");
	$('.cand_owner').html("<span class='candidate-owner'>" + dtit + "</span>");
	$('.cand_created').html("<span class='candidate-details'>" + metadetails + "</span>");
	$('.cand_status').html("<span class='candidate-status candidate-" + cand.latest_status + "'>" + cand.latest_status + "</span>");
    dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + cand.id , options.subtitle);	
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
	dacura.editor.load("<?=$params['id']?>", dacura.candidate.fetch, dacura.candidate.update);
	$('#show-candidate').show();
});
</script>


