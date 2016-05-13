<div id="dqs-control" class='dch'>
	<div class='pane-header'>Quality Analysis <span class='pane-header-value' id='dqsresult'></span>
		<div class='pane-header-more' id='dqsmore'>
			<span class='controlsbox'>
				<span class='dqsmore'>
					<a class='show-controls' href='javascript:showDQSControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showDQSControls()'></a>
				</span>
				<span class='dqsless dch'>
					<a class='show-controls' href='javascript:hideDQSControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideDQSControls()'></a>
				</span>
			</span>
		</div>
	</div>
	<div class='pane-summary' id='dqs-summary'>
	</div>
	<table id='dqscontroltable' class='pane-full dch'>
 		<tbody>
		</tbody>
	</table>
	<div class='dqsanalysis dch'>
		<div class='analysis'>
			<span class='analysis-text'></span>
			<span class='analysis-created'></span>
		</div>
		<div class='analysis-button'><button id='analyse'>Analyse Now</button></div>			
	</div>
</div>
<div id='dqs-subscreens'>
	<div class='dch dacsub dqs-triples'>
 		<div class='subscreen-header'>
			<span id="subscreen-title">Triples - ontology and dependencies serialised as triples</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='subscreen-body'></div>
 	</div>
 	<div class='dch dacsub dqs-tests'>
 		<div class='subscreen-header'>
			<span class="subscreen-title">Quality Service Tests</span>
			<span class="subscreen-title-options">
				<span id='tmaa' class='manauto testsmanauto'>
					<input type='radio' name='tma' id='tests-set-manual' value='manual'><label for='tests-set-manual'>Manual</label>
					<input type='radio' name='tma' id='tests-set-automatic' value='automatic'><label for='tests-set-automatic'>Automatic</label>
				</span>
				<input type='checkbox' class='enable-update tests-enable-update' id='enable-update-tests' value="">
				<label for='enable-update-tests'>Update</label>
			</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='dqs-messages'></div>		
		<div class='subscreen-body'></div>
 	</div>
 	<div class='dch dacsub dqs-errors'>
 		<div class='subscreen-header'>
			<span class="subscreen-title">Errors</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='subscreen-body'></div>
 	</div>
 	<div class='dch dacsub dqs-warnings'>
  		<div class='subscreen-header'>
			<span class="subscreen-title">Warnings</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='subscreen-body'></div>
 	</div>
 	<div class='dch dacsub dqs-imports'>
  		<div class='subscreen-header'>
			<span class="subscreen-title">Imported ontologies</span>
			<span class="subscreen-title-options">
				<span id='imaa' class='manauto'>
					<input type='radio' name='ima' id='imports-set-manual' value='manual'><label for='imports-set-manual'>Manual</label>
					<input type='radio' name='ima' id='imports-set-automatic' value='automatic'><label for='imports-set-automatic'>Automatic</label>
				</span>
				<input type='checkbox' class='enable-update imports-enable-update' id='enable-update-imports' value="">
				<label for='enable-update-imports'>Update</label>
			</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='imports-messages'></div>
		<div class='subscreen-body'>
			<?php include("imports.php")?>
		</div>	
 	</div>
 </div>
 
<script>

function getAutomaticImports(data){
	var available_imports = <?=$params['available_ontologies']?>;
	imps = {};
	if(typeof data.analysis == "object" && typeof data.analysis.dependencies == "object" && typeof data.analysis.dependencies.include_tree == "object"){
		var ents = getTreeEntries(data.analysis.dependencies.include_tree);
		for(var i = 0; i<ents.length; i++){
			imps[ents[i]] = {collection: available_imports[ents[i]].collection, version: 0, id: available_imports[ents[i]].id};
		}
	}
	else {
		if(typeof data.analysis == "object" && typeof data.analysis.validation == "object" && typeof data.analysis.validation.imports == "object"){
			for(var k in data.analysis.validation.imports){
				imps[k] = {
					collection: data.analysis.validation.imports[k].collection,
					version: 0, 
					id: data.analysis.validation.imports[k].id
				};
			}
		}
	}
	return imps;
}

function initDQS(data, pconf){
	showAnalysisBasics(data, '.analysis-text', pconf);
	$('.analysis-created').html(" (created with version " + data.analysis.version + " at " + timeConverter(data.analysis.created, true) + ")");
	var res = new LDGraphResult(data.analysis.validation, "triples", pconf);
	$('#dqsresult').html(res.getResultHeadlineHTML());
	if(res.status == "accept"){
		$('#dqs-control').addClass("dqs-success");
	}
	else {
		$('#dqs-control').addClass("dqs-failure");	
	}
	rows = getDQSRows(res, data.meta, pconf, getAutomaticImports(data));
	for(var i = 0; i<rows.length; i++){
		$('#dqscontroltable tbody').append(getControlTableRow(rows[i]));
		$('#dqs-summary').append(getSummaryTableEntry(rows[i]));					
	}	
	var hvrin = function(){
		$(this).addClass('uhover');
	};
	var hvrout = function() {
	    $(this).removeClass('uhover');
	}
	var hclick = function(){
		var act = this.id.substring(4);
		$('.dacsub').hide();
		$('#dqs-subscreens .dqs-'+act).show("drop", { direction: "down" });
  		dacura.system.goTo('#dqs-subscreens');
	}
	$('#dqscontroltable tr.control-table-clickable').hover(hvrin, hvrout).click(hclick);
	$('#dqs-summary .clickable-summary').hover(hvrin, hvrout).click(hclick);
	$('#dqs-control').show();
	$('#analyse').button().click( function(){
		var opts = <?=$params['fetch_args']?>;
		opts.options.analysis = 2;
		dacura.ld.fetch("<?=$params['id']?>", opts, drawLDOPage, pconf);		
	});
	
}

function hideDQSControls(){
	$('.dqsanalysis').hide();
	$('.dqsless').hide();
	$('.dqsmore').show();
	$('#dqscontroltable').hide("slide", {"direction": "up"});	
	$('#dqs-summary').show("drop", {"direction": "down"});
}

function showDQSControls(){
	$('.dqsless').show();
	$('.dqsmore').hide();
	$('#dqs-summary').hide("drop", {"direction": "down"});
	$('#dqscontroltable').show("slide", {"direction": "up"});
	$('.dqsanalysis').show();
}

function getDQSRows(res, meta, pconf, auto_imports){
	var rows = [];
	if(res.hasWarnings()){
		$('#dqs-subscreens .dqs-warnings .subscreen-body').html(res.getWarningsHTML());
		var rowdata = {
			id: "warnings",
			icon: dacura.system.getIcon("warning"),
			count: res.warnings.length,
			variable: "warning" + (res.warnings.length == 1 ? "" : "s"),
			value: res.getWarningsSummary(),
			help: "Warnings may indicate an error or a lapse in best practice but they do not prevent the use of the ontology to validate instance data"
		};
		rows.push(rowdata);	
	}
	if(res.hasErrors()){
		$('#dqs-subscreens .dqs-errors  .subscreen-body').html(res.getErrorsHTML());
		var rowdata = {
			id: "errors",
			icon: dacura.system.getIcon("error"),
			count: res.errors.length,
			variable: "error" + (res.errors.length == 1 ? "" : "s"),
			value: res.getErrorsSummary(),
			help: "Errors indicate problems with the ontology which will prevent it from being used to validate instance data"
		};	
		rows.push(rowdata);	
	}
	var dqsconfig = new DQSConfigurator(meta.dqs_tests, <?=$params['default_dqs_tests']?>, <?=$params['dqs_schema_tests']?>, "view", handleDQSUpdate);
	dqsconfig.draw('#dqs-subscreens .dqs-tests .subscreen-body');
	if(typeof res.tests != "undefined"){
		rowdata = {id: "tests"};
		rowdata.count = res.tests;
		rowdata.icon = dacura.system.getIcon("pending");
		if(typeof res.tests == "object"){
			rowdata.count = res.tests.length;
			if(rowdata.count == 0){
				rowdata.icon = dacura.system.getIcon("warning");
				rowdata.value = "No tests configured";
			}
			else {
				rowdata.value = dqsconfig.getTestsSummary();				
			}
		}
		else if(res.tests == "all"){
			rowdata.value = dqsconfig.getTestsSummary();				
			rowdata.count = size(dqsconfig.dqs);  			
		}
		rowdata.variable = "test" + ((typeof res.tests == 'object' && res.tests.length == 1) ? "" : "s");
		rowdata.help = "The quality service can be configured to apply many different types of tests to ontologies - the current configuration is listed here";
	}
	else {
		var rowdata = {
			id: "tests",
			icon: dacura.system.getIcon("warning"),
			count: 0,
			variable: "tests",
			value: "No DQS tests configured - this ontology will not be tested by the quality service",
			help: "You must specify quality tests to be used with this ontology"
		};
	}
	rows.push(rowdata);	
	if(typeof res.imports != "undefined"){
		var importer = new OntologyImporter(meta.imports, <?=$params['available_ontologies']?>, auto_imports, "view", handleImportUpdate);
		importer.draw('#dqs-subscreens .dqs-imports .subscreen-body');
		
		$('#imaa').buttonset().click(function(){
			if($('input[name=ima]:checked').val() == "manual"){
				importer.setManual();
			}
			else {
				importer.setAuto();		
			}
		});
		$('#imaa').buttonset("disable");
	
		var rowdata = {
			id: "imports",
			icon: dacura.system.getIcon("ontology"),
			count: size(res.imports),
			variable: "import" + (size(res.imports) == 1 ? "" : "s"),
			value: res.getImportsSummary(meta.imports),
			help: "Ontologies must import those ontologies on which they have a structural dependence. "
		};
	}
	rows.push(rowdata);	
	if(typeof res.inserts != "undefined" && res.inserts.length > 0){
		$('#dqs-subscreens .dqs-triples .subscreen-body').html(dacura.ld.getTripleTableHTML(res.inserts));
		var rowdata = {
			id: "triples",
			icon: dacura.system.getIcon("triples"),
			count: res.inserts.length,
			variable: "triple" + (res.inserts.length == 1 ? "" : "s"),
			value: "",
			actions: "view_triples",
			help: "Ontologies are serialised into a set of triples before being loaded by the DQS"
		};
	}
	else {
		var rowdata = {
			id: "triples",
			unclickable: true,
			icon: dacura.system.getIcon("warning"),
			count: 0,
			variable: "triples",
			value: "The graph for this ontology is currently empty, you must add contents to it before it can be serialised",
			help: "Ontologies are serialised into a set of triples before being loaded by the DQS"
		};
	}
	rows.push(rowdata);
	return rows;
}

var handleImportUpdate = function(conf, isauto, test){
	//transform conf back into an owl updates request
	var pconf = {resultbox: ".imports-messages", busybox: ".dqs-imports" }
	var upd = {
		meta: {"imports": conf },
		options: {show_result: 1, plain: 1},
		format: "json", 
		editmode: "update",
		test: test
	};
	updateOntology(upd, pconf)	
} 

function handleDQSUpdate(conf, isauto, test){
	var pconf = {resultbox: ".dqs-messages", busybox: ".dqs-tests" }
	
	var upd = {
		meta: {},
		format: "json", 
		editmode: "update",
		test: test,
		options: {show_result: 1, plain: 1},
	};
	if(isauto){
		upd.meta["dqs_tests"] = [];
	}
	else {
		upd.meta["dqs_tests"] = conf;
	}
	updateOntology(upd, pconf)	
} 

function updateOntology(upd, pconf){
	handleResp = function(data, pconf){
		var res = new LDResult(data, pconf);
		res.show();
	}
	dacura.ld.update("<?=$params['id']?>", upd, handleResp, pconf, upd.test);
}



function createTestsDynamics(){
	$('.dqs-all-config-element').buttonset();
}



function showAnalysisBasics(data, tgt){
	if(typeof data.analysis != "object"){
		var html = dacura.system.getIcon('warning') + "No analysis produced";
		$(tgt).html(html);				
	}
	else if(data.analysis.version != data.meta.version){
		var upds = data.meta.version  - data.analysis.version; 
		var html = dacura.system.getIcon('warning') + "This analysis is stale, " 
		html += (upds == 1 ? "there has been an update " : " there have been " + upds + " updates");
		html += " since this analysis was created";
		$(tgt).html(html);
	}
	else {
		var html = dacura.system.getIcon('success') + "Analysis is up to date";
		$(tgt).html(html);			
	}
}



</script>