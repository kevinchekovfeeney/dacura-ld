<script>
//initialisation communicating the context to js from php
var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
if(ldtype.length) {
	dacura.ld.ldo_type = ldtype;
}
var ldtn = tname();
var ldtnp = tnplural();
var initarg = {"tabbed": 'ld-tool-home'};
var initfuncs = {};

//the initarg and initfuncs arrays are populated by the includes below, 
//the intifuncs are all called when the page is fully loaded
$(function() {
	if(size(initfuncs) == 0){
		alert("List page configuration error - no subscreens enabled");
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


