<div class='dacura-screen' id="ld-view-home">
	<?php if(in_array("ldo-contents", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-contents' title='Contents'>
		<div class='subscreen-intro-message'><?=$params['contents_intro_msg']?></div>
		<?php include_once("contents.php");?>		
	</div>
	<?php } if(in_array("ldo-meta", $params['subscreens']))  { ?>
	<div class="dacura-subscreen" id='ldo-meta' title='Metadata'>
		<div class='subscreen-intro-message'><?=$params['meta_intro_msg']?></div>		
		<?php include_once("meta.php");?>		
	</div>
	<?php } if(in_array("ldo-analysis", $params['subscreens'])) { ?>
	<div class="dacura-subscreen" id='ldo-analysis' title='Analysis'>
		<div class='subscreen-intro-message'><?=$params['analysis_intro_msg']?></div>
		<?php include_once("analysis.php");?>		
	</div>
	<?php } if(in_array("ldo-history", $params['subscreens'])) { ?>		
	<div class='dacura-subscreen' id='ldo-history' title="History">
		<div class='subscreen-intro-message'><?=$params['history_intro_msg']?></div>
		<?php include_once("history.php");?>		
	</div>	
	<?php } if(in_array("ldo-updates", $params['subscreens'])) { ?>		
	<div class='dacura-subscreen' id='ldo-updates' title="Updates">
		<div class='subscreen-intro-message'><?=$params['updates_intro_msg']?></div>
		<?php include_once("updates.php");?>		
	</div>
	<?php } ?>	
</div>
<div style='clear:both'></div>
<script>

function drawLDOPage(data, pconf){
	if(data.status == "reject"){
		var x = new LDResult(data, pconf);
		return x.show();
	}
	dacura.ld.header(data);
	dacura.tool.initScreens("ld-view-home");
	<?php if(in_array("ldo-contents", $params['subscreens'])) { ?>
		initContents(data, "ldo-contents");	
	<?php } ?>	
	<?php if(in_array("ldo-meta", $params['subscreens'])) { ?>
		initMeta(data, "ldo-meta");	
	<?php } ?>	
	
	<?php if(in_array("ldo-history", $params['subscreens'])) { ?>		
		if(typeof data.history == "object"){
			initHistoryTable(data.history, "ldo-history");
			//dacura.tool.enableTab("ld-view-home", 'ldo-history');	
		}
		else {
			dacura.tool.disableTab("ld-view-home", 'ldo-history');	
		}
	<?php } ?>	
	<?php if(in_array("ldo-updates", $params['subscreens'])) { ?>		
		if(typeof data.updates == "object"){
			//dacura.tool.enableTab("ld-view-home", 'ldo-updates');	
			initUpdatesTable(data.updates, "ldo-updates");
		}
		else {
			dacura.tool.disableTab("ld-view-home", 'ldo-updates');	
		}	
	<?php } ?>	
	<?php if(in_array("ldo-analysis", $params['subscreens'])) { ?>		
		if(typeof data.analysis == "object"){
			//dacura.tool.enableTab("ld-view-home", 'ldo-analysis');	
			initAnalysis(data, dacura.tool.subscreens["ldo-analysis"]);
		}
		else {
			dacura.tool.disableTab("ld-view-home", 'ldo-analysis');	
		}	
	<?php } ?>	
	dacura.system.styleJSONLD("td.rawjson");	
}

function printCreated(obj){
	return timeConverter(obj.createtime);
}

function printModified(obj){
	return timeConverter(obj.modtime);
}

function printCreatedBy(obj){
	return "<a href='../update/" + obj.created_by + "'>" + obj.created_by + "</a>";
}



$('document').ready(function(){
	var pconf = { resultbox: ".tool-info", busybox: ".tool-holder"};
	dacura.ld.fetch("<?=$params['id']?>", <?=$params['fetch_args']?>, drawLDOPage, pconf);
});
</script>


