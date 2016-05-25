<script>
var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
if(ldtype.length) {
	dacura.ld.ldo_type = ldtype;
}
var ldtn = tname(); 
var ldtnp = tnplural();
var initfuncs = {};
var refreshfuncs = {};

</script>
<div id='graph-main' class='dacura-ldscreen'>
	<div id='graph-controllers'>
		<div id='graph-controllers-messages'></div>
		<div id="graph-control" class='dch'>
			<div class='pane-header'>Graph<span class='pane-header-value' id='graphstatus'></span>
				<div class='pane-header-more' id='graphmore'>
					<span class='controlsbox'>
						<span class='graphmore'>
							<a class='show-controls' href='javascript:showGraphControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showGraphControls()'></a>
						</span>
						<span class='graphless dch'>
							<a class='show-controls' href='javascript:hideGraphControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideGraphControls()'></a>
						</span>
					</span>
				</div>
			</div>
			<div class='pane-summary' id='graph-summary'>
			</div>
			<div class='pane-contents dch' id='graph-contents'>
				<table id='graphcontroltable' class='pane-full'>
			 		<tbody>
					</tbody>
				</table>
				<div class='graphanalysis'>
					<div class='analysis'>
						<span class='analysis-text'></span>
						<span class='analysis-created'></span>
					</div>
					<div class='analysis-button'><button id='analyse'>Analyse Now</button></div>			
				</div>
			</div>
		</div>
		<div id="schema-control" class='dch'>
			<div class='pane-header'>Schema Graph Validation<span class='pane-header-value' id='schemastatus'></span>  
				<div class='pane-header-more' id='schemamore'>
					<span class='controlsbox'>
						<span class='schemamore'>
							<a class='show-controls' href='javascript:showSchemaControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showSchemaControls()'></a>
						</span>
						<span class='schemaless dch'>
							<a class='show-controls' href='javascript:hideSchemaControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideSchemaControls()'></a>
						</span>
					</span>
				</div>
			</div>
			<div class='pane-summary' id='schema-summary'>
			</div>
			<div class='pane-contents dch' id='schema-contents'>
				<table id='schemacontroltable' class='pane-full'>
			 		<tbody>
					</tbody>
				</table>
			</div>
		</div>
		<div id="instance-control" class='dch'>
			<div class='pane-header'>Instance Graph Validation<span class='pane-header-value' id='instancestatus'></span>  
				<div class='pane-header-more' id='instancemore'>
					<span class='controlsbox'>
						<span class='instancemore'>
							<a class='show-controls' href='javascript:showInstanceControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showInstanceControls()'></a>
						</span>
						<span class='instanceless dch'>
							<a class='show-controls' href='javascript:hideInstanceControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideInstanceControls()'></a>
						</span>
					</span>
				</div>
			</div>
			<div class='pane-summary' id='instance-summary'>
			</div>
			<div class='pane-contents dch' id='instance-contents'>
				<table id='instancecontroltable' class='pane-full'>
			 		<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
	<div id="graph-view-subscreen">
		<div id='ldo-history-screen' class='dacsub dch'>
			<div class='subscreen-header'>
				<span class="subscreen-title">History - browse previous versions of the graph</span>
				<span class='subscreen-close'></span>
			</div>
			<div class='subscreen-body'>
				<?php include_once($service->ssInclude("history"));?>
		 	</div>
		</div>
		<div id='ldo-updates-screen' class='dacsub dch'>
			<div class='subscreen-header'>
				<span class="subscreen-title">Updates - browse all updates that have been submitted to this graph</span>
				<span class='subscreen-close'></span>
			</div>
			<div class='subscreen-body'>
				<div class='tool-tab-info' class='subscreen-messages' id='ldo-updates-msgs'></div>
				<div class='tool-tab-contents' id='ldo-updates-contents'>
					<?php include_once($service->ssInclude("updates"));?>
				</div>
			</div>
		</div>
		<div id='graph-schema-dqs' class='dacsub dch'>
			<div class='subscreen-header'>
				<span class="subscreen-title">DQS Schema Tests</span>
				<span class='subscreen-close'></span>
			</div>
			<div class='subscreen-messages' id="schema-dqs-messages"></div>
			<div class='subscreen-body'>
			</div>
		</div>
		<div id='graph-instance-dqs' class='dacsub dch'>
			<div class='subscreen-header'>
				<span class="subscreen-title">DQS Instance Tests</span>
				<span class='subscreen-close'></span>
			</div>
			<div class='subscreen-messages' id="instance-dqs-messages"></div>
			<div class='subscreen-body'>
			</div>
		</div>
		<div id='idqs-subscreens'>
			<div id='idqs-triples-subscreen' class='dch dacsub dqs-triples'>
		 		<div class='subscreen-header'>
					<span class="subscreen-title">Instance Graph Triples - instance data serialised as triples</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-body'></div>
		 	</div>
		  	<div id='idqs-errors-subscreen' class='dch dacsub dqs-errors'>
		 		<div class='subscreen-header'>
					<span class="subscreen-title">Instance Graph Errors</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-body'></div>
		 	</div>
		 	<div id='idqs-warnings-subscreen' class='dch dacsub dqs-warnings'>
		  		<div class='subscreen-header'>
					<span class="subscreen-title">Instance Graph Warnings</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-body'></div>
		 	</div>
		</div>
		<div id='dqs-subscreens'>
			<div id='dqs-triples-subscreen' class='dch dacsub dqs-triples'>
		 		<div class='subscreen-header'>
					<span class="subscreen-title">Schema Triples - graph ontologies serialised as triples</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-body'></div>
		 	</div>
		  	<div id='dqs-errors-subscreen' class='dch dacsub dqs-errors'>
		 		<div class='subscreen-header'>
					<span class="subscreen-title">Schema Errors</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-body'></div>
		 	</div>
		 	<div id='dqs-warnings-subscreen' class='dch dacsub dqs-warnings'>
		  		<div class='subscreen-header'>
					<span class="subscreen-title">Schema Warnings</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-body'></div>
		 	</div>
		 	<div id='dqs-imports-subscreen' class='dch dacsub dqs-imports'>
		  		<div class='subscreen-header'>
					<span class="subscreen-title">Imported ontologies</span>
					<span class='subscreen-close'></span>
				</div>
				<div class='subscreen-messages' id='imports-messages'></div>
				<div class='subscreen-body'></div>	
		 	</div>
		</div>
	</div>
</div>
<div style='clear: both'></div>
<script>
var update_options = <?php echo isset($params['update_options']) && $params['update_options'] ? $params['update_options'] : "{}";?>;
var test_update_options = <?php echo isset($params['test_update_options']) && $params['test_update_options'] ? $params['test_update_options'] : "{}";?>;
var ldovconfig = <?php echo isset($params['ldov_config']) && $params['ldov_config'] ? $params['ldov_config'] : "{}";?>;
var fetch_args = <?php echo isset($params['fetch_args']) && $params['fetch_args'] ? $params['fetch_args'] : "{}";?>;
var refetch_args = $.extend(true, {}, fetch_args);
refetch_args.options.analysis = 2;

var importer;
var dqsconfig;
var idqsconfig;
var ldov;

var pconf = { resultbox: ".tool-info", busybox: ".tool-holder"};


function initGraphPage(data, pconf){
	if(data.status == "reject"){
		var x = new LDResult(data, pconf);
		return x.show();
	}
	var hconf = {resultbox: "#ldo-history .subscreen-intro-message", busybox: "#ldo-history .subscreen-body"};
	dacura.tool.subscreens["ldo-history"] = hconf;
	initfuncs['ldo-history'](data, "ldo-history");
	var uconf = {resultbox: "#ldo-updates .subscreen-intro-message", busybox: "#ldo-updates .subscreen-body"};
	dacura.tool.subscreens["ldo-updates"] = uconf;
	initfuncs['ldo-updates'](data, "ldo-updates");
	dqsconfig = new DQSConfigurator(data.meta.schema_dqs_tests, <?=$params['default_dqs_tests']?>, <?=$params['dqs_schema_tests']?>, "graph", handleSchemaDQSUpdate);
	idqsconfig = new DQSConfigurator(data.meta.instance_dqs_tests, <?=$params['default_instance_dqs_tests']?>, <?=$params['dqs_instance_tests']?>, "graph", handleInstanceDQSUpdate);
	importer = new OntologyImporter(getExplicitImports(data), <?=$params['available_ontologies']?>, getImplicitImports(data), "graph", handleImportUpdate);
	ldov = new LDOViewer(new LDO(data), pconf, ldovconfig);
	
	showGraphPage(data, pconf);
	$('#analyse').button().click( function(){
		var opts = $.extend(true, {}, fetch_args);
		opts.options.analysis = 2;
		dacura.ld.fetch("<?=$params['id']?>", opts, refreshLDOPage, pconf);		
	});	
	$('.subscreen-close').html(dacura.system.getIcon('back'));
	$('.subscreen-close').click(function(){
  		$('.dacsub').hide("slide", { direction: "up" }, "slow");
  		$('.subscreen-messages').empty(); 
  		$('#graph-controllers').show("slide", { direction: "down" }, "slow");  
		dacura.system.goTo('#graph-main');
	});
	refreshGraphDynamics(data, pconf);	
}


function showInstanceSubscreen(act, data, pconf){
	$("#idqs-" + act + "-subscreen").show();
	dacura.system.goTo('#idqs-'+act + "-subscreen  .subscreen-header");
}

function showSubscreen(act, data, pconf){
	$(".dacsub").hide();
	$('#graph-controllers').hide();
	if (act == "instance-dqs"){
		$('#graph-schema-dqs .subscreen-body').html("");
		$('#graph-instance-dqs .subscreen-body').html("");	
		idqsconfig.draw('#graph-instance-dqs .subscreen-body');
		$('#graph-instance-dqs').show();
  		dacura.system.goTo('#graph-instance-dqs .subscreen-body');		
	}
	else if (act == "schema-dqs" || act == "tests"){
		$('#graph-schema-dqs .subscreen-body').html("");
		$('#graph-instance-dqs .subscreen-body').html("");
		dqsconfig.draw('#graph-schema-dqs .subscreen-body');
		$('#graph-schema-dqs').show();
  		dacura.system.goTo('#graph-schema-dqs .subscreen-body');		
	}
	else {
		if(act == "imports"){
			importer.automatic_imports = getImplicitImports(data);
			importer.reset();
		}
		$('#dqs-subscreens .dqs-'+act).show();
  		dacura.system.goTo('#dqs-'+act + "-subscreen  .subscreen-header");
	}
	$("#graph-" + act).show();
}

function refreshLDOPage(data, pconf){
	if(data.status == "reject"){
		var x = new LDResult(data, pconf);
		return x.show();
	}
	if(typeof data.history == "object"){
		refreshfuncs['ldo-history'](data, "ldo-history");
	}
	if(typeof data.updates == "object"){
		refreshfuncs['ldo-updates'](data, "ldo-updates");
	}
	$('#graphcontroltable tbody').empty();
	$('#graph-summary').empty();
	$('span.graph-actions').remove();
	importer.setImports(getExplicitImports(data), getImplicitImports(data));
	importer.setViewMode();
	showGraphPage(data, pconf);
	refreshGraphDynamics(data, pconf);
	dacura.system.styleJSONLD("td.rawjson");					
}

function refreshGraphDynamics(data, pconf){
	$('.graph-action').click(function(){
		var act = this.id.substring(6);
		var upd = {
			"meta": { "status": act}, 
			"editmode": "update",
			"options" : {"rollback_update_to_pending_on_dqs_reject": 0},
			"format": "json"
		};
		var uconf = {resultbox: "#graph-controllers-messages", busybox: "#graph-controllers"};
		updateGraph(upd, uconf);	
	});
	var hvrin = function(){
		$(this).addClass('uhover');
	};
	var hvrout = function() {
	    $(this).removeClass('uhover');
	}
	var gclick = function(){
		var act = this.id.substring(4);
		$('.dacsub').hide();
		if(act == "history"){
			$('#ldo-history-screen').show();
			$('#ldo-history').show("drop", { direction: "down" });
			dacura.system.goTo('#ldo-history-screen .subscreen-body');
		}
		else if(act == "updates"){
			$('#ldo-updates-screen').show();
			$('#ldo-updates').show("drop", { direction: "down" });
			dacura.system.goTo('#ldo-updates-screen .subscreen-body');		
		}
		else {
			showSubscreen(act, data, pconf);
		}
	};
	var sclick = function(){
		$('.dacsub').hide();
		var act = this.id.substring(4);
		if(act == "warnings" || act == "errors" || act == "triples") {
			showInstanceSubscreen(act, data, pconf);
		}
		else {
			showSubscreen(act, data, pconf);
		}		
	};
	$('#graph-summary .clickable-summary').hover(hvrin, hvrout).click(gclick);
	$('#graphcontroltable tr.control-table-clickable').hover(hvrin, hvrout).click(gclick);	
	$('#schema-summary .clickable-summary').hover(hvrin, hvrout).click(gclick);
	$('#schemacontroltable tr.control-table-clickable').hover(hvrin, hvrout).click(gclick);	
	$('#instance-summary .clickable-summary').hover(hvrin, hvrout).click(sclick);
	$('#instancecontroltable tr.control-table-clickable').hover(hvrin, hvrout).click(sclick);	
}

function showGraphPage(data, pconf){
	dacura.ld.header(data);
	dacura.tool.header.setSubtitle(data.meta.cwurl);	
	dacura.ld.showAnalysisBasics(data, '.analysis-text');
	$('.analysis-created').html(" (created with version " + data.analysis.version + " at " + timeConverter(data.analysis.created, true) + ")");
	var ldo = new LDO(data);
	$('#graphstatus').html(getHeadlineHTML(ldo));
	$('#graph-control').addClass("dacura-" + ldo.status());
	var rows = getGraphStatusRows(data, pconf);
	for(var i = 0; i<rows.length; i++){
		if(rows[i].id != "implicit"){			
			$('#graphcontroltable tbody').append(dacura.ld.getControlTableRow(rows[i]));
		}
		$('#graph-summary').append(dacura.ld.getSummaryTableEntry(rows[i]));
	}
	$('#graphmore').prepend(getGraphActionsHTML(ldo));
	$('#graph-control').show();	
	showSchemaPane(data, pconf);
	showInstancePane(data, pconf);
}

function showInstancePane(data, pconf){
	$('#instancecontroltable tbody').empty();
	$('#instance-summary').empty();
	if(!(typeof data.analysis == 'object' && typeof data.analysis.instance_validation == "object")){
		$('#instancestatus').html("<span class='dqsresulticon'>" + dacura.system.getIcon("error") + "</span><span class='dqsresulttext'>No analysis carried out</span>");
		return;
	}
	var res = new LDGraphResult(data.analysis.instance_validation, "triples", pconf);
	if(data.meta.status == "accept"){
		if(res.status == "accept"){
			$('instance-control').addClass("dqs-success");
		}
		else {
			$('#instance-control').addClass("dqs-failure");	
		}
		$('#instancestatus').html(res.getResultHeadlineHTML());
	}
	else {
		$('#instance-control').addClass("dqs-disabled");
		var html = "<span class='dqsresulticon'>" + dacura.system.getIcon("disabled") + "</span>";
		html += "<span class='dqsresulttext'>Disabled</span>";
		$('#instancestatus').html(html);				
	}
	idqsconfig.fake = true;
	rows = dacura.ld.getDQSRows("#idqs-subscreens", res, data.meta, pconf,  false, idqsconfig);
	idqsconfig.fake = false;
	for(var i = 0; i<rows.length; i++){
		if(rows[i].id == "tests") rows[i].id = "instance-dqs";
		if(rows[i].id != "triples"){			
			$('#instancecontroltable tbody').append(dacura.ld.getControlTableRow(rows[i]));
			$('#instance-summary').append(dacura.ld.getSummaryTableEntry(rows[i]));
		}					
	}	
	$('#instance-control').show();
}	

function showSchemaPane(data, pconf){
	$('#schemacontroltable tbody').empty();
	$('#schema-summary').empty();
	if(!(typeof data.analysis == 'object' && typeof data.analysis.schema_validation == "object")){
		$('#schemastatus').html("<span class='dqsresulticon'>" + dacura.system.getIcon("error") + "</span><span class='dqsresulttext'>No analysis carried out</span>");
		return;
	}
	var res = new LDGraphResult(data.analysis.schema_validation, "triples", pconf);
	if(res.status == "accept"){
		$('schema-control').addClass("dqs-success");
	}
	else {
		$('#schema-control').addClass("dqs-failure");	
	}
	$('#schemastatus').html(res.getResultHeadlineHTML());
	dqsconfig.fake = true;
	rows = dacura.ld.getDQSRows("#dqs-subscreens", res, data.meta, pconf, importer, dqsconfig);
	dqsconfig.fake = false;
	if(typeof data.analysis.entity_classes != "undefined"){
		rowdata = {
			id: "entities",
			unclickable: true,
			icon: dacura.system.getIcon("entity"),
			count: data.analysis.entity_classes.length,
			variable: "entit" + (data.analysis.entity_classes.length == 1 ? "y": "ies"),
			value: data.analysis.entity_classes.join(", "),
			help: "Entity classes are the things that you want to collect data about"		
		}
		rows.push(rowdata);
	}
	for(var i = 0; i<rows.length; i++){
		if(rows[i].id != "triples" && rows[i].id != "imports"){			
			$('#schemacontroltable tbody').append(dacura.ld.getControlTableRow(rows[i]));
			$('#schema-summary').append(dacura.ld.getSummaryTableEntry(rows[i]));
		}					
	}	
	$('#schema-control').show();
}

function hideGraphControls(){
	$('.graphless').hide();
	$('.graphmore').show();
	$('#graph-contents').hide("slide", {"direction": "up"});	
	$('#graph-summary').show("drop", {"direction": "down"});
}

function showGraphControls(){
	$('.graphless').show();
	$('.graphmore').hide();
	$('#graph-summary').hide("drop", {"direction": "down"});
	$('#graph-contents').show("slide", {"direction": "up"});
}		

function hideInstanceControls(){
	$('.instanceless').hide();
	$('.instancemore').show();
	$('#instance-contents').hide("slide", {"direction": "up"});	
	$('#instance-summary').show("drop", {"direction": "down"});
}

function showInstanceControls(){
	$('.instanceless').show();
	$('.instancemore').hide();
	$('#instance-summary').hide("drop", {"direction": "down"});
	$('#instance-contents').show("slide", {"direction": "up"});
}	

function hideSchemaControls(){
	$('.schemaless').hide();
	$('.schemamore').show();
	$('#schema-contents').hide("slide", {"direction": "up"});	
	$('#schema-summary').show("drop", {"direction": "down"});
}

function showSchemaControls(){
	$('.schemaless').show();
	$('.schemamore').hide();
	$('#schema-summary').hide("drop", {"direction": "down"});
	$('#schema-contents').show("slide", {"direction": "up"});
}	

function getGraphStatusRows(data, pconf){
	var rows = [];
	var rowdata = {
		id: "history",
		icon: dacura.system.getIcon("history"),
		variable: data.meta.version,
		count: "version",
		value: "Created " + timeConverter(data.meta.created) + ((history) ? ", last updated: " + timeConverter(data.meta.modified) : "")
	};
	if(typeof data.history != "object" || data.history.length == 0){
		rowdata.unclickable = true;
	}
	rows.push(rowdata);
	if(typeof data.updates == 'object' && data.updates.length){
		rowdata = {
			id: "updates",
			icon: dacura.system.getIcon("updates"),
			count: data.updates.length,
			variable: "update" + (data.updates.length == 1 ? "" : "s"),
		};
		var cnt = {accept: 0, pending: 0, reject: 0};
		for(var i = 0; i < data.updates.length; i++){
			cnt[data.updates[i].status]++;
		}
		rows.push(rowdata);
		rowdata.value = cnt.accept + " accepted, " + cnt.reject + " rejected, " + cnt.pending + " pending";  	
	}
	var imps = getImports(data);
	if(isEmpty(imps)){
		rowdata = {
			id: "imports",
			icon: dacura.system.getIcon("error"),
			count: 0,
			variable: "imports", 
			value: "The schema graph is empty - at least one ontology must be added to the graph before it can be used for quality control"
		};		
		rows.push(rowdata);			
	}
	else {
		var implicits = getImplicitImports(data);
		var explicits = getExplicitImports(data);
		rowdata = {
			id: "imports",
			icon: dacura.system.getIcon("ontology"),
			count: size(imps),
			variable: "import" + (size(imps) == 1 ? "" : "s"),
			value: getImportsSummary(explicits, implicits),			
		};
		rows.push(rowdata);			
		rowdata = {
			id: "implicit",
			count: size(implicits),
			variable: "implicit",
			unclickable: true
		};
		rows.push(rowdata);						
	}
	return rows;		
}

function getImportsSummary(explicits, implicits){
	var html = "Explicit: ";
	for(var i in explicits){
		var url = dacura.system.install_url;
		url += (explicits[i].collection == "all") ? "" : explicits[i].collection;
		url += "/ontology/" + explicits[i].id;
		html += dacura.ld.getOntologyViewHTML(i, url, null, explicits[i].version);
	}
	if(size(implicits) > 0){
		html += " Implicit: ";
		for(var i in implicits){
			var url = dacura.system.install_url;
			url += (implicits[i].collection == "all") ? "" : implicits[i].collection;
			url += "/ontology/" + implicits[i].id;
			html += dacura.ld.getOntologyViewHTML(i, url, null, implicits[i].version);
		}
	}
	return html;
}

function getGraphActionsHTML(ldo){
	var html = "";
	if(ldo.status() == "accept"){
		html += "<span class='graph-actions'><span class='graph-action graph-pending' id='graph-pending'>Deactivate</span></span>";
	}
	else if(ldo.status() == "pending"){
		html += "<span class='graph-actions'><span class='graph-action graph-accept' id='graph-accept'>Activate</span> <span class='graph-action graph-reject' id='graph-reject'>Disable</span></span>";		
	}
	else {
		html += "<span class='graph-actions'><span class='graph-action graph-pending' id='graph-pending'>Enable</span>";
		if(ldo.id != "main"){
			html += "<span class='graph-action graph-delete' id='graph-deleted'>Delete</span>";
		}
		html += "</span>";		
	}
	return html;
}

function getHeadlineHTML(ldo){
	var html = "";
	if(ldo.status() == "accept"){
		html += "<span class='graph-status-result' title='This graph is currently active - instance data is verified by the DQS service at <?=$params['dqsurl']?> and published to the triplestore'>";
		html += "<span class='dqsresulttext'>Active</span> ";
		html += "<span class='dqsresulticon'>" + dacura.system.getIcon("accept") + "</span></span>";
	}
	else if(ldo.status() == "pending"){
		html += "<span class='graph-status-result' title='This graph is currently offline - Dacura will store data written to it, but will not publish it to graph. The graph must be enabled and approved by the DQS Service at <?=$params['dqsurl']?> before it can be published to the triplestore'>";
		html += "<span class='dqsresulttext'>Offline </span> ";		
		html += "<span class='dqsresulticon'>" + dacura.system.getIcon("warning") + "</span></span> ";
	}
	else {
		html += "<span class='graph-status-result' title='This graph is currently disabled - Dacura will not store data in this graph'>";
		html += "<span class='dqsresulttext'>Disabled</span> ";
		html += "<span class='dqsresulticon'>" + dacura.system.getIcon("reject") + "</span></span>";	
	}
	return html;
}

function getImports(data){
	imps = {};
	if(typeof data.contents == "object" && typeof data.contents["_:schema"] == "object" && typeof data.contents["_:schema"]["owl:imports"] == "object"){
		for(var i = 0; i<data.contents["_:schema"]["owl:imports"].length; i++){
			var imp = urlToImport(data.contents["_:schema"]["owl:imports"][i]);
			if(imp){
				imps[imp.id] = imp;
			}
			else {
				alert("Import was not intelligible: " + data.contents["_:schema"]["owl:imports"][i]);
			}
		}
	}
	return imps;	
}

function getImplicitImports(data){
	var imps = getImports(data);
	var exps = getExplicitImports(data);
	for(var k in imps){
		if(typeof exps[k] == "object"){
			delete(imps[k]);
		}
	}
	return imps;
}

function getExplicitImports(data){
	var exps = {};
	var exp = data.meta.explicit_schema_imports;
	var imps = getImports(data);
	if(typeof exp == "object" && imps && size(imps) > 0){
		//might be either 
		if(exp.length > 0){
			for(var i = 0; i < exp.length; i++){
				if(typeof imps[exp[i]] == 'object'){
					exps[exp[i]] = imps[exp[i]];
					exps[exp[i]].version = 0; 
				}			
			}
		}
		else if(size(exp) > 0){
			for(var ex in exp){
				if(typeof imps[exp[ex]] == 'object'){						
					exps[ex] = exp[ex]
				}
			}
		}
	}
	return exps;
}

var handleImportUpdate = function(conf, isauto, test){
	//transform conf back into an owl updates request
	var impconf = {resultbox: "#imports-messages", busybox: "#dqs-imports-subscreen .subscreen-body"};
	imports = [];
	for(var k in conf){
		imports.push(importToURL(conf[k]));
	}
	if(typeof test == "undefined" || !test){
		var options = update_options;
	}
	else {
		var options = test_update_options;
	}
	var upd = {
			options: options,
			format: "json", 
			editmode: "update",
			test: test
		};
	if(imports.length == 0){
		upd.contents = { "_:schema": []};
	}
	else {
		upd.contents = {
			"_:schema": { "owl:imports" : imports }
		};
	}
	updateGraph(upd, impconf, test)	
} 

var handleSchemaDQSUpdate = function(conf, isauto, test){
	var sconf = {resultbox: "#schema-dqs-messages", busybox: "#graph-schema-dqs .subscreen-body"};
	handleDQSUpdate("schema_dqs_tests", conf, isauto, test, sconf);			
}

var handleInstanceDQSUpdate = function(conf, isauto, test){
	var iconf = {resultbox: "#instance-dqs-messages", busybox: "#graph-instance-dqs .subscreen-body"};
	handleDQSUpdate("instance_dqs_tests", conf, isauto, test, iconf);
}

function handleDQSUpdate(which, conf, isauto, test, vconf){
	var upd = {
		meta: {},
		format: "json", 
		editmode: "replace",
		test: test
	};
	if(typeof test == "undefined" || !test){
		var options = update_options;
	}
	else {
		var options = test_update_options;
	}
	if(isauto){
		upd.meta[which] = [];
	}
	else {
		upd.meta[which] = conf;
	}
	updateGraph(upd, vconf)	
} 

function updateGraph(upd, nconf, upd_imports){
	handleResp = function(data, xconf){
		var res = new LDResult(data, nconf);
		if(!res.test && (res.status == "accept" || res.status == "pending")){
			dacura.ld.fetch("<?=$params['id']?>", refetch_args, refreshLDOPage, pconf);	
			if(res.status == "accept"){
				importer.reground();
			}		
		}
		else if(typeof upd_imports != "undefined" && upd_imports){
			if(typeof data.result != "undefined"){
				importer.setImports(getExplicitImports(data.result), getImplicitImports(data.result));			
			}
			else {
				importer.setImports(getExplicitImports(data), getImplicitImports(data));
			}
		}
		res.show();
	}
	dacura.ld.update("<?=$params['id']?>", upd, handleResp, nconf, upd.test);
}

$('document').ready(function(){
	dacura.ld.fetch("<?=$params['id']?>", fetch_args, initGraphPage, pconf);
});
</script>