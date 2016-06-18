<script>
var initfuncs = {};
var refreshfuncs = {};
</script>
<div class='dacura-screen' id='ldoupdate-view-home'>
	<?php if(in_array("update-contents", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='update-contents' title='<?=$params['update_contents_screen_title']?>'>
		<div class='subscreen-intro-message'><?=$params['view_update_contents_intro_msg']?></div>
		<div id="show-update"></div>
	</div>
	<?php } if(in_array("update-commands", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='update-commands' title='<?=$params['update_commands_screen_title']?>'>
		<div class='subscreen-intro-message'><?=$params['view_update_commands_intro_msg']?></div>
		<div id="update-commands"></div>
	</div>
	<?php } if(in_array("ldo-meta", $params['subscreens']))  { ?>
		<?php include_once($service->ssInclude("meta"));?>		
	<?php } if(in_array("update-before", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='update-before' title='<?=$params['update_before_screen_title']?>'>
		<div class='subscreen-intro-message'><?=$params['view_before_intro_msg']?></div>
		<div id="before-ldoupdate"></div>
	</div>
	<?php } if(in_array("update-after", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='update-after' title='<?=$params['update_after_screen_title']?>'>
		<div class='subscreen-intro-message'><?=$params['view_after_intro_msg']?></div>
		<div id="after-ldoupdate"></div>
	</div>
	<?php } ?>	
</div>
<script>
var show_contents_options = <?php echo isset($params['show_contents_options']) && $params['show_contents_options'] ? $params['show_contents_options'] : "{}";?>;
var ldovconfig = <?php echo isset($params['ldov_config']) && $params['ldov_config'] ? $params['ldov_config'] : "{}";?>;
var ldov;

// refresh the page with new data from api 
function refreshLDOPage(data, pconf){
	ldov.ldo = new LDOUpdate(data);
	ldov.show("#show-update", "#before-ldoupdate", "#after-ldoupdate", "#update-commands", "view", show_contents_options, refreshLDOPage);
	dacura.ld.updateHeader(data);
	for(var i in refreshfuncs){
		refreshfuncs[i](data, dacura.tool.subscreens[i]);
	}
}

//initialise the page
function initLDOUpdate(data, pconf){
	if(data.status == "reject"){
		var x = new LDResult(data, pconf);
		return x.show();
	}
	dacura.tool.initScreens("ldoupdate-view-home");
	var ldou = new LDOUpdate(data);
	ldov = new LDOUpdateViewer(ldou, pconf, ldovconfig);
	ldov.show("#show-update", "#before-ldoupdate", "#after-ldoupdate", "#update-commands", "view", show_contents_options, refreshLDOPage);
	dacura.ld.updateHeader(data);
	for(var i in initfuncs){
		initfuncs[i](data, dacura.tool.subscreens[i]);
	}
}

//fetch update to start
$('document').ready(function(){
	var pconf = { resultbox: ".tool-info", busybox: ".tool-holder"};
	dacura.ld.fetch("<?=$params['id']?>", <?=$params['fetch_args']?>, initLDOUpdate, pconf);
});
</script>

