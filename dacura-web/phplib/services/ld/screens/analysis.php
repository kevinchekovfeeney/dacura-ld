<div class="dacura-subscreen" id='ldo-analysis' title='<?=$params['analysis_screen_title']?>'>
	<div class='subscreen-intro-message'><?=$params['analysis_intro_msg']?></div>
	<div id='analysis-screen' class='dch'>
		<?php include("dqs.php");?>
		<?php include("dependencies.php");?>
	</div>
</div>

<script>

var initAnalysis = function(data, pconf){
	pconf.subscreen = '#analysis-subscreen';
	showAnalysisBasics(data, "#analysis-main");
	if(typeof data.analysis.validation == "object"){
		initDQS(data, pconf);
	}
	if(typeof data.analysis.dependencies == "object"){
		showDependencies(data.analysis.dependencies);
	}		
	$('#analysis-screen').show();
	$('.subscreen-close').html(dacura.system.getIcon('error'));
	$('.subscreen-close').click(function(){
  		$('.dacsub').hide("slide", { direction: "up" }, "slow");
		dacura.system.goTo(pconf.busybox);
	});
	$('#analysis-screen').tooltip(<?=$params['tooltip_config']?>);
};

initfuncs["ldo-contents"] = initContents;


function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

function initDecorations(){
	$('#singleont-dependencies').hide();
}


var subscreens = {};

function analyse(data, pconf){
	initDecorations();
	if(typeof data.analysis == "object"){
		showAnalysisBasics(data);
	}
	if(typeof data.analysis.dependencies == "object"){
		showDependencies(data.analysis.dependencies);
	}
	$('.summary-entry').hover(function(){
		$(this).addClass('divhover');
	}, function() {
	    $(this).removeClass('divhover');
	});
	$('.summary-entry').click(function(){
		var act = this.id.substring(3);
		if(act == "warnings" || act == "errors" || act == "tests" || act == "imports" || act == "triples" || act == "structural"){
			showSubscreen(act, subscreens[act]);
		}
		else if(act == "dependencies" || act == "schema_dependencies"){
			showDependenciesControls();
		}
		else {
			showNSUsageControls();			
		}
	});
}


</script>