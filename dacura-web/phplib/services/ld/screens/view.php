<div class='dacura-screen' id='ld-view-home'>
	<?php if(in_array("ldo-contents", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-contents' title='Contents'>
		<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
		<div id="show-ldo"></div>
	</div>
	<?php } if(in_array("ldo-meta", $params['subscreens']))  { ?>
	<div class="dacura-subscreen" id='ldo-meta' title='Metadata'>
		<div class='subscreen-intro-message'><?=$params['meta_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("ldo-details", $params['create_ldo_fields'], array("display_type" => "create"));?>
		<div class="subscreen-buttons">
			<button id='ldotestcreate' class='dacura-test-create subscreen-button'><?=$params['testcreate_button_text']?></button>		
			<?php if(isset($params['direct_create_allowed']) && $params['direct_create_allowed']) { ?>
			<button id='ldocreate' class='dacura-create subscreen-button'><?=$params['create_button_text']?></button>
			<?php } ?>
		</div>
		
	</div>
	<?php } if(in_array("ldo-analysis", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-analysis' title='Analysis'>
		<div class='subscreen-intro-message'><?=$params['analysis_intro_msg']?></div>
		<div id="show-analysis"></div>
	</div>
	<?php } if(in_array("ldo-history", $params['subscreens'])) { ?>		
	<div class='dacura-subscreen' id='ldo-history' title="History">
		<div class='subscreen-intro-message'><?=$params['history_intro_msg']?></div>
		<div class='tholder' id='history-table-holder'>
			<table id="history_table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id="hto-version" title="The Version Number">Version</th>
					<th id="hto-status" title="<?=isset($params['ldtype']) ? $params['ldtype'] : "linked data object"?> status">Status</th>
					<th id="hto-createtime">Sortable Created</th>
					<th id="dfn-printCreated" title="Date and time of version creation">Created</th>
					<th id="hto-created_by">Sortable Created By</th>
					<th id="dfn-printCreatedBy" title="Created by update">Update ID</th>
					<th id="hto-backward" title="Changed From" class="rawjson">Changed From</th>
					<th id="hto-forward" title="Changed To" class="rawjson">Changed to</th>
				</tr>
				</thead>
				<tbody></tbody>
			</table>
		</div>
	</div>	

	<?php } if(in_array("ldo-updates", $params['subscreens'])) { ?>		
	<div class='dacura-subscreen' id='ldo-updates' title="Updates">
		<div class='subscreen-intro-message'><?=$params['updates_intro_msg']?></div>
		<div class='tholder' id='updates-table-holder'>
			<table id="updates_table" class="dacura-api-listing">
				<thead>
				<tr>
					<th id="uto-eurid" title='Update ID'>ID</th>
					<th id="uto-status" title='current status of this update'>Status</th>
					<th id="uto-targetid" title='current status of this update'>Target ID</th>
					<th id="uto-from_version" title='Version that this update was applied to'>Applied to version</th>
					<th id="uto-to_version" title='Version that this update created'>created version</th>
					<th id="uto-createtime">Sortable Created</th>
					<th id="dfx-printCreated" title="Date and time of update creation">Created</th>
					<th id="uto-modtime">Sortable modified</th>
					<th id="dfx-printModified" title="Date and time of last update modification">Last Modified</th>
					</tr> 
			</thead>
			<tbody>
			</tbody>
			</table>		
		</div>
	</div>
	<?php } if(in_array("ldo-raw", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-raw' title='Raw'>
		<div class='subscreen-intro-message'><?=$params['raw_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("ldoraw", $params['raw_ldo_fields'], array("display_type" => "edit"));?>
		<div class="subscreen-buttons">
			<button id='testrawedit' class='dacura-test-create subscreen-button'><?=$params['testcreate_button_text']?></button>		
			<?php if(isset($params['direct_create_allowed']) && $params['direct_create_allowed']) { ?>
			<button id='rawcreate' class='dacura-create subscreen-button'><?=$params['create_button_text']?></button>
			<?php } ?>
		</div>		
	</div>
	<?php } ?>	
</div>
<script>
function printCreated(obj){
	return timeConverter(obj.createtime);
}

function printModified(obj){
	return timeConverter(obj.modtime);
}

function printCreatedBy(obj){
	return "<a href='../update/" + obj.created_by + "'>" + obj.created_by + "</a>";
}

var ldo_loaded = false;
var updates_loaded = false;
var history_loaded = false;

/*
 * Called once per page load - sets the ldo context of the view page
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
	dacura.tool.update(options);
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
	$('.cand_type').html("<span class='ldo-type'>" + ent.type + "</span>");
	$('.cand_owner').html("<span class='ldo-owner'>" + dtit + "</span>");
	$('.cand_created').html("<span class='ldo-details'>" + metadetails + "</span>");
	$('.cand_status').html("<span class='ldo-status ldo-" + ent.latest_status + "'>" + ent.latest_status + "</span>");
    dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + ent.id , options.subtitle);	
}


function submitRawTest(obj, result, pconf){
	submitRaw(obj, result, pconf, true);
}
function submitRawUpdate(obj, result, pconf){
	submitRaw(obj, result, pconf, false);
}
function submitRaw(obj, result, pconf, test){
	<?php if(count($params['update_options']) > 0){
		echo "obj.options = ".json_encode($params['update_options']).";";
	}?>	
	obj.meta = JSON.parse(obj.meta);
	obj.ldtype = obj.meta.ldtype;
	if(dacura.ld.isJSONFormat(obj.format)){
		obj.contents = JSON.parse(obj.contents);
	}
	obj.version = obj.meta.version;
	dacura.ld.update("<?=$params['id']?>", obj, result, pconf, test);
}

function showUpdateSuccess(data){
	jpr(data);
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

function drawLDO(data){
	dacura.tool.initScreens("ld-view-home");
	
	dacura.ld.header(data);
	dacura.tool.table.init("history_table", {
		"screen": "ldo-history", 
		"dtsettings": <?=$params['history_datatable']?>
	}, data.history);		
	dacura.tool.table.init("updates_table", {
		"screen": "ldo-updates", 
		"dtsettings": <?=$params['updates_datatable']?>
	}, data.updates);		
	dacura.system.styleJSONLD("td.rawjson");	
	dacura.ld.viewer.draw(data, "show-ldo");
	dacura.tool.button.init("testrawedit",  {
		"screen": "ldo-raw",			
		"source": "ldoraw",
		"submit": submitRawTest,
		"result": function(data, pconf) { showUpdateSuccess(data, pconf, "LDO Updated OK");}
	});
	dacura.tool.button.init("rawcreate",  {
		"screen": "ldo-raw",			
		"source": "ldoraw",
		"submit": submitRawUpdate,
		"result": function(data, pconf) { showUpdateSuccess(data, pconf, "LDO Updated OK");}
	});
	dacura.tool.form.init("ldoraw", {});
	var fp = {"format": data.format, "meta": JSON.stringify(data.meta, 0, 4)};
	if(typeof data.contents == "string"){
		fp.contents = data.contents;
	}
	else {
		fp.contents = JSON.stringify(data.contents, 0, 4); 
	}
	dacura.tool.form.populate("ldoraw", fp);
	//dacura.editor.load("<?=$params['id']?>", dacura.ld.fetch, dacura.ld.update);
	//jpr(data);
}

$('document').ready(function(){
	var pconf = { resultbox: ".tool-info", busybox: "#ld-view-home"};
	dacura.ld.fetch("<?=$params['id']?>", <?=$params['fetch_args']?>, drawLDO, pconf);
	//dacura.editor.load("<?=$params['id']?>", dacura.ld.fetch, dacura.ld.update);
	//$('#show-ldo').show();
});
</script>


