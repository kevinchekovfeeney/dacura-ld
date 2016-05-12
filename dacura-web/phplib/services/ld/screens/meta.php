<div class='update-meta-holder dch'>
<?php echo $service->getInputTableHTML("update-meta", $params['update_meta_fields'], array("display_type" => "edit"));?>
<div class="subscreen-buttons" id='update-meta-buttons'>
	<button id='testmetaupdate' class='dacura-test-update subscreen-button'><?=$params['test_update_meta_button_text']?></button>		
	<button id='metaupdate' class='dacura-update subscreen-button'><?=$params['update_meta_button_text']?></button>
</div>
</div>
<script>
function initMeta(data, screen){
	dacura.tool.form.init("update-meta", {icon: "<?= $service->furl("images", "icons/help.png")?>"});
	dacura.tool.button.init("testmetaupdate",  {
		"screen": screen,			
		"source": "update-meta",
		"submit": function(obj, result, pconf) { updateMeta(obj, result, pconf, true);},
		"result": function(data, pconf) { showTestMetaResult(data, pconf);}
	});
	dacura.tool.button.init("metaupdate",  {
		"screen": "ldo-meta",			
		"source": "update-meta",
		"submit": function(obj, result, pconf) { updateMeta(obj, result, pconf, false);},
		"result": function(data, pconf) { showUpdateMetaResult(data, pconf);}
	});
	var fp = {"format": data.format, "meta": JSON.stringify(data.meta, 0, 4)};
	dacura.tool.form.populate("update-meta", fp);
	$('.update-meta-holder').show();
}

function updateMeta(obj, result, pconf, test){
	obj.meta = JSON.parse(obj.meta);
	obj.ldtype = obj.meta.ldtype;
	obj.version = obj.meta.version;
	dacura.ld.update("<?=$params['id']?>", obj, result, pconf, test);
}

function showTestMetaResult(data, pconf){
	var x = new LDResult(data, pconf);
	x.show();		
}

function showUpdateMetaResult(data, pconf){
	var x = new LDResult(data, pconf);
	x.show();		
}
</script>