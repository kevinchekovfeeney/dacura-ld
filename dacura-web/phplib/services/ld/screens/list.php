<script>
//initialisation communicating the context to js from the server
var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
if(ldtype.length) {
	dacura.ld.ldo_type = ldtype;
}
var ldtn = tname();
var ldtnp = tnplural();
var initarg = {"tabbed": 'ld-tool-home'};
var initfuncs = {};

//the initarg and initfuncs arrays are manipulated by the includes below, then this is triggered when the page is fully loaded
$(function() {
	if(size(initfuncs) == 0){
		alert("List page configuration error - no subscreens enabled");
	}
	else if(size(initfuncs) == 1){
		var pconf = { resultbox: ".tool-info", busybox: ".tool-holder"};		
		for(var i in initfuncs){
			initfuncs[i](pconf);
		}
	}
	else {
		dacura.tool.init(initarg);
		for(var i in initfuncs){
			initfuncs[i](dacura.tool.subscreens[i]);
		}
	}
});
</script>
<div class='dacura-screen' id='ld-tool-home'>
	<?php if(in_array("ldo-list", $params['subscreens'])) { ?>
		<?php include("ldolist.php");?>	
	<?php } if(in_array("update-list", $params['subscreens'])) { ?>
		<?php include("updateslist.php");?>		
	<?php } if(in_array("ldo-create", $params['subscreens'])) { ?>
		<?php include("create.php");?>	
    <?php } if(in_array("ldo-export", $params['subscreens'])) { ?>
		<?php include("export.php");?>	
	<?php } ?>
</div>


