<script>
//var Ontimporter = new OntologyImporter(orig, $params['available_ontologies'], autos, view, ucallback);
//var DQS = new DQSConfigurator(saved, def, avs, mode, ucallback);


</script>

<div class="dacura-subscreen" id='ldo-analysis' title='<?=$params['analysis_screen_title']?>'>
	<div class='subscreen-intro-message'><?=$params['analysis_intro_msg']?></div>
	<div id='analysis-screen' class='dch'>
		<?php include("dependencies.php");?>
	</div>
	<div style='clear:both'></div>
</div>

<script>

var initAnalysis = function(data, pconf){
	if(typeof data.analysis.validation == "object"){
		initDQS(data, pconf);
	}
	if(typeof data.analysis.dependencies == "object"){
		showDependencies(data.analysis.dependencies);
	}		
	$('#analysis-screen').show();
	$('.subscreen-close').html(dacura.system.getIcon('back'));
	$('.subscreen-close').click(function(){
  		$('.dacura-control').show();
  		$('.subscreen-messages').empty(); 
		$('.dacsub').hide("slide", { direction: "up" }, "slow");
		dacura.system.goTo('#analysis-screen');
	});
	$('#analysis-screen').tooltip(<?=$params['tooltip_config']?>);
};

var refreshAnalysis = function(data, pconf){
	if(typeof data.analysis.validation == "object"){
		refreshDQS(data, pconf);
	}
	if(typeof data.analysis.dependencies == "object"){
		refreshDependencies(data.analysis.dependencies);
	}			
};

initfuncs["ldo-analysis"] = initAnalysis;
refreshfuncs["ldo-analysis"] = refreshAnalysis;



</script>