<div class="dacura-subscreen" id='ldo-meta' title='<?=$params['meta_screen_title']?>'>
	<div class='subscreen-intro-message'><?=$params['meta_intro_msg']?></div>		
	<div class='update-meta-holder dch'>
		<?php echo $service->getInputTableHTML("update-meta", $params['update_meta_fields'], $params['update_meta_config']);?>
		<div class="subscreen-buttons" id='update-meta-buttons'>
			<?php if(isset($params['show_update_meta_test_button']) && $params['show_update_meta_test_button']) {?>
			<button id='testmetaupdate' class='dacura-test-update subscreen-button'><?=$params['test_update_meta_button_text']?></button>		
			<?php } if(isset($params['show_update_meta_button']) && $params['show_update_meta_button']) {?>
			<button id='metaupdate' class='dacura-update subscreen-button'><?=$params['update_meta_button_text']?></button>
			<?php } ?>
		</div>
	</div>
</div>
<script>
var tooltip = <?php echo isset($params['help_tooltip_config']) ? $params['help_tooltip_config'] :  "{}" ?>;
var fburl = "<?php echo $service->getFileBrowserURL()?>";
var show_update_button = <?php echo (isset($params['show_update_meta_button']) && $params['show_update_meta_button']) ? "true" : "false";?>;
var show_test_button = <?php echo (isset($params['show_update_meta_test_button']) && $params['show_update_meta_test_button']) ? "true" : "false" ?>;
var test_update_meta_options = <?php echo isset($params['test_update_meta_options']) && $params['test_update_meta_options'] ? $params['test_update_meta_options'] : "{}";?>;
var update_meta_options = <?php echo isset($params['update_meta_options']) && $params['update_meta_options'] ? $params['update_meta_options'] : "{}";?>;


var initMeta = function(data, pconf){
	dacura.tool.form.init("update-meta", {tooltip: tooltip, fburl: fburl});
	if(show_test_button){
		dacura.tool.button.init("testmetaupdate",  {
			"screen": "ldo-meta",			
			"source": "update-meta",
			"submit": function(obj, result, pconf) { updateMeta(obj, result, pconf, true);},
			"result": function(md, pconf) {}
		});
	}
	if(show_update_button){
		dacura.tool.button.init("metaupdate",  {
			"screen": "ldo-meta",			
			"source": "update-meta",
			"submit": function(obj, result, pconf) { updateMeta(obj, result, pconf, false);},
			"result": function(mdata, pconf) {}
		});
	}
	var fp = {"format": data.format};
	for(var k in data.meta){
		fp[k] = (typeof data.meta[k] == "object") ? JSON.stringify(data.meta[k], 0, 4) : data.meta[k];
	}
	fp.meta = JSON.stringify(data.meta, 0, 4);
	dacura.tool.form.populate("update-meta", fp);
	$('.update-meta-holder').show();
};

var refreshMeta = function(data, pconf){
	var fp = {"format": data.format, "meta": JSON.stringify(data.meta, 0, 4)};
	dacura.tool.form.populate("update-meta", fp);
};

refreshfuncs["ldo-meta"] = refreshMeta;
initfuncs["ldo-meta"] = initMeta;


function updateMeta(obj, result, pconf, test){
	var uobj = {};
	if(typeof obj.meta != "undefined" && obj.meta.length > 0){
		uobj.meta = JSON.parse(obj.meta);
		uobj.ldtype = obj.meta.ldtype;
		uobj.version = obj.meta.version;		
	}
	else {
		uobj.meta = {};
		uobj.ldtype = ldov.ldtype();
		uobj.version = ldov.ldo.meta.version;
	}
	for(var k in obj){
		if(k != "meta"){
			uobj.meta[k] = obj[k]
		}
	}
	uobj.options = (test) ? test_update_meta_options : update_meta_options;
	uobj.test = test;
	uobj.editmode = "update";
	uobj.format = "json";
	var handler = function(data, pconf){
		if(typeof data.meta == 'object'){
			refreshLDOPage(data, pconf);			
			result(data, pconf);
		}
	}
	if(ldov.isLDOUpdate()){
		uobj.umeta = uobj.meta;
		delete(uobj['meta']);
	}
	ldov.update(uobj, handler, pconf);
}

</script>