<script>
var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
if(ldtype.length) {
	dacura.ld.ldo_type = ldtype;
}
var ldtn = tname(); 
var ldtnp = tnplural();
var initfuncs = {};
var refreshfuncs = {};
var drawfuncs = {};
</script>
<div class='dacura-screen' id="ld-view-home">
	<?php if(in_array("ldo-contents", $params['subscreens'])) { ?>
		<?php include_once($service->ssInclude("contents"));?>		
	<?php } if(in_array("ldo-meta", $params['subscreens']))  { ?>
		<?php include_once($service->ssInclude("meta"));?>		
	<?php } if(in_array("ldo-history", $params['subscreens'])) { ?>		
		<?php include_once($service->ssInclude("history"));?>		
	<?php } if(in_array("ldo-updates", $params['subscreens'])) { ?>		
		<?php include_once($service->ssInclude("updates"));?>		
	<?php } if(in_array("ldo-analysis", $params['subscreens'])) { ?>
		<?php include_once($service->ssInclude("analysis"));?>		
	<?php } ?>	
</div>
<div style='clear:both'></div>
<script>
var ldovconfig = <?php echo isset($params['ldov_config']) && $params['ldov_config'] ? $params['ldov_config'] : "{}";?>;

var refreshLDOPage = function(data, pconf){
	if(data.status == "reject"){
		var x = new LDResult(data, pconf);
		return x.show();
	}
	dacura.ld.header(data);
	ldov.ldo = new LDO(data);
	for(var i in refreshfuncs){
		refreshfuncs[i](data, dacura.tool.subscreens[i]);
	}
	dacura.system.styleJSONLD("td.rawjson");				
}

function initLDOPage(data, pconf){
	if(data.status == "reject"){
		var x = new LDResult(data, pconf);
		return x.show();
	}
	dacura.ld.header(data);
	var ldo = new LDO(data);
	ldov = new LDOViewer(ldo, pconf, ldovconfig);
	if(size(initfuncs) == 0){
		alert("View page configuration error - no subscreens enabled");
	}
	else {
		dacura.tool.initScreens("ld-view-home");
		for(var i in initfuncs){
			initfuncs[i](data, dacura.tool.subscreens[i]);
		}
	}
	dacura.system.styleJSONLD("td.rawjson");				
}

$('document').ready(function(){
	var pconf = { resultbox: ".tool-info", busybox: ".tool-holder"};
	dacura.ld.fetch("<?=$params['id']?>", <?=$params['fetch_args']?>, initLDOPage, pconf);
});
</script>


