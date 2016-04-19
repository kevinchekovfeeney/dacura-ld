<div class='dacura-screen' id='ldoupdate-view-home'>
	<?php if(in_array("ldo-contents", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-contents' title='Contents'>
		<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
		<div id="show-ldo"></div>
	</div>
	<?php } if(in_array("ldo-meta", $params['subscreens']))  { ?>
	<div class="dacura-subscreen" id='ldo-meta' title='Metadata'>
		<div class='subscreen-intro-message'><?=$params['meta_intro_msg']?></div>
		<?php echo $service->getInputTableHTML("ldoupdate-details", $params['create_ldo_fields'], array("display_type" => "create"));?>
		<div class="subscreen-buttons">
			<button id='ldotestcreate' class='dacura-test-create subscreen-button'><?=$params['testcreate_button_text']?></button>		
			<?php if(isset($params['direct_create_allowed']) && $params['direct_create_allowed']) { ?>
			<button id='ldocreate' class='dacura-create subscreen-button'><?=$params['create_button_text']?></button>
			<?php } ?>
		</div>
	</div>
	<?php } if(in_array("ldo-before", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-before' title='Before'>
		<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
		<div id="before-ldoupdate"></div>
	</div>
	<?php } if(in_array("ldo-after", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-after' title='After'>
		<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
		<div id="after-ldoupdate"></div>
	</div>
	<?php } if(in_array("ldo-analysis", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-analysis' title='Analysis'>
		<div class='subscreen-intro-message'><?=$params['analysis_intro_msg']?></div>
		<div id="show-analysis"></div>
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
function drawLDOUpdate(data){
	dacura.tool.initScreens("ldoupdate-view-home");
	
	dacura.ld.header(data);
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

	dacura.ld.viewer.draw(data.changed, "after-ldoupdate");
	dacura.ld.viewer.draw(data.original,"before-ldoupdate");
	var ob = {};
	ob.contents = data.insert;
	ob.format = "json";
	dacura.ld.viewer.draw(ob, "show-ldo");
	
	dacura.tool.form.init("ldoraw", {});
	var fp = {"format": data.format, "meta": JSON.stringify(data.meta, 0, 4)};
	if(typeof data.insert == "string"){
		fp.contents = data.insert;
	}
	else {
		fp.contents = JSON.stringify(data.insert, 0, 4); 
	}
	dacura.tool.form.populate("ldoraw", fp);
	//dacura.editor.load("<?=$params['id']?>", dacura.ld.fetch, dacura.ld.update);
	//jpr(data);
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

$('document').ready(function(){
	var pconf = { resultbox: ".tool-info", busybox: "#ldoupdate-view-home"};
	dacura.ld.fetch("<?=$params['id']?>", <?=$params['fetch_args']?>, drawLDOUpdate, pconf);
	//dacura.editor.load("<?=$params['id']?>", dacura.ld.fetch, dacura.ld.update);
	//$('#show-ldo').show();
});
</script>

