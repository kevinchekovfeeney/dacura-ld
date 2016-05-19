<script>
var ldtype = "<?=isset($params['ldtype']) ? $params['ldtype'] : ""?>";
if(ldtype.length) {
	dacura.ld.ldo_type = ldtype;
}
var ldtn = tname(); 
var ldtnp = tnplural();
var ldid = "<?= $params['id'];?>";
var initfuncs = {};
var refreshfuncs = {};
var drawfuncs = {};
</script>
<div id="graph-view-home">
</div>
<div id="graph-view-subscreen">
</div>
<div id='graph-history' class='dacsub dch'>
	<div class='subscreen-header'>
		<span id="subscreen-title">History - browse previous versions of the graph</span>
		<span class='subscreen-close'></span>
	</div>
	<div class='subscreen-body'>
		<?php include_once("phplib/services/ld/screens/history.php");?></div>
 	</div>
</div>
<div id='graph-updates' class='dacsub dch'>
	<div class='subscreen-header'>
		<span id="subscreen-title">Updates - browse all updates that have been submitted to this graph</span>
		<span class='subscreen-close'></span>
	</div>
	<div class='subscreen-body'>
		<?php include_once("phplib/services/ld/screens/updates.php");?>
	</div>
</div>
<div id='graph-schema-dqs' class='dacsub dch'>
	<div class='subscreen-header'>
		<span id="subscreen-title">Quality Service Schema Graph Configuration</span>
		<span class='subscreen-close'></span>
	</div>
	<div class='subscreen-body'>
		
	</div>
</div>
<div id='graph-instance-dqs' class='dacsub dch'>
	<div class='subscreen-header'>
		<span id="subscreen-title">Quality Service Instance Graph Configuration</span>
		<span class='subscreen-close'></span>
	</div>
	<div class='subscreen-body'>
	</div>
</div>
<div id='graph-imports' class='dacsub dch'>
	<div class='subscreen-header'>
		<span id="subscreen-title">Ontologies Imported by Schema Graph</span>
		<span class='subscreen-close'></span>
	</div>
	<div class='subscreen-body'>
	</div>
</div>
<style>

span#graph-subscreen-close {
	float: right;
}

span#graph-subscreen-close:hover {
	cursor: pointer;
}

th.graph-name {
	padding-top: 8px;
	text-align: left;
	font-weight: 600;
}

th.graph-url {
	padding-top: 8px;
}
th.graph-url a {
	text-decoration: none;
	font-weight: 300;
	font-size: 0.9em;
}

a.implicit-import {
	text-decoration: none;
	font-weight: 350;
}

a.explicit-import {
	text-decoration: none;
	font-weight: 550;
}

#graph-subscreen-header {
	background-color: #eff4f8;
	border: 1px solid #d2e8f8;
	margin: 6px 0;
	font-size: 1.2em;
	padding: 0.5em 1em;
}

#graph-details {
	width: 100%;
	background-color: #eff4f8;
	border: 1px solid #d2e8f8;
	margin: 6px 0;
}

#graph-schema {
	width: 100%;
	background-color: #eff8f4;
	border: 1px solid #d2f8e8;
	margin: 6px 0;
}

#graph-instance {
	width: 100%;
	background-color: #eff8f4;
	border: 1px solid #d2f8e8;
	margin: 6px 0;
}

#graph-instance table,
#graph-schema table,
#graph-details table {
	width: 100%;
	margin: 2px 10px;
}

td.rdtitle {
	font-weight: 550;
	
}

td.rdactions {
	font-size: 0.85em;
	text-align: right;
	padding-right: 10px;
	min-width: 180px;
}

td.rdinfo {
	font-weight: 400;
	font-size: 0.85em;
} 
</style>

<script>
var pconf = { resultbox: ".tool-info", busybox: ".tool-holder"};
var update_options = <?php echo isset($params['update_options']) && $params['update_options'] ? $params['update_options'] : "{}";?>;
var test_update_options = <?php echo isset($params['test_update_options']) && $params['test_update_options'] ? $params['test_update_options'] : "{}";?>;

var importer;
var dqsconfig;
var idqsconfig;

var handleImportUpdate = function(conf, isauto, test){
	//transform conf back into an owl updates request
	imports = [];
	for(var k in conf){
		imports.push(importToURL(conf[k]));
	}
	if(typeof test == "undefined" || !test){
		var options = test_update_options;
	}
	else {
		var options = test_update_options;
	}
	var upd = {
		contents: {
			"_:schema": { "owl:imports" : imports }
		},
		options: options,
		format: "json", 
		editmode: "update",
		test: test
	};
	updateGraph(upd, pconf)	
} 

var handleSchemaDQSUpdate = function(conf, isauto, test){
	handleDQSUpdate("schema_dqs_tests", conf, isauto, test);			
}

var handleInstanceDQSUpdate = function(conf, isauto, test){
	handleDQSUpdate("instance_dqs_tests", conf, isauto, test);
}

function handleDQSUpdate(which, conf, isauto, test){
	var upd = {
		meta: {},
		format: "json", 
		editmode: "update",
		test: test
	};
	if(isauto){
		upd.meta[which] = [];
	}
	else {
		upd.meta[which] = conf;
	}
	updateGraph(upd, pconf)	
} 


function drawGraphPage(data, pconf){
	dacura.ld.header(data);
	dacura.tool.header.setSubtitle(data.meta.cwurl);
	if(typeof data.history == "object"){
		dacura.tool.subscreens["ldo-history"] = pconf;
		initHistoryTable(data.history, "ldo-history");
	}
	if(typeof data.updates == "object"){
		dacura.tool.subscreens["ldo-updates"] = pconf;
		initUpdatesTable(data.updates, "ldo-updates");
	}
	dqsconfig = new DQSConfigurator(data.meta.schema_dqs_tests, <?=$params['default_dqs_tests']?>, <?=$params['dqs_schema_tests']?>, "graph", handleSchemaDQSUpdate);
	idqsconfig = new DQSConfigurator(data.meta.instance_dqs_tests, <?=$params['default_instance_dqs_tests']?>, <?=$params['dqs_instance_tests']?>, "graph", handleInstanceDQSUpdate);
	importer = new OntologyImporter(getExplicitImports(data), <?=$params['available_ontologies']?>, getImplicitImports(data), "graph", handleImportUpdate);
	importer.draw('#graph-imports .subscreen-body');
	$('#graph-view-home').html(getPageHTML(data));
	$('.rdaction').button().click(function(){
		dacura.system.clearResultMessage(pconf.resultbox);
		var act = this.id;
		if(act == "accept" || act == "reject" || act == "delete" || act == "pending"){
			var upd = {
				"meta": { "status": act}, 
				"editmode": "update",
				"options" : {"rollback_update_to_pending_on_dqs_reject": 0},
				"format": "json"
			};
			updateGraph(upd, pconf);
		}
		else {
			showSubscreen(act, data, pconf);	
		}
	});
}

function getSubscreenHeaderHTML(act, data, pconf){
	var titles = {
		"updateimport": "Update the list of ontologies imported by the graph's schema",
	}
	var x = titles[act];
	x = x ? x : act;
	x += " <span id='graph-subscreen-close'>" + dacura.system.resulticons['error'] + "</span>"; 
	return x;
}

function getSubscreenBodyHTML(act, data, pconf){
}

function showSubscreen(act, data, pconf){
	$("#graph-view-home").hide();
	$(".dacsub").hide();
	if(act == "imports"){
		importer.setViewMode();
	}
	else if (act == "instance-dqs"){
		$('#graph-schema-dqs .subscreen-body').html("");
		$('#graph-instance-dqs .subscreen-body').html("");	
		idqsconfig.draw('#graph-instance-dqs .subscreen-body');
	}
	else if (act == "schema-dqs"){
		$('#graph-schema-dqs .subscreen-body').html("");
		$('#graph-instance-dqs .subscreen-body').html("");
		dqsconfig.draw('#graph-schema-dqs .subscreen-body');
	}
	$("#graph-" + act).show();
	$('.subscreen-close').html(dacura.system.getIcon('error'));
	$('.subscreen-close').click(function(){
  		$('.dacsub').hide("slide", { direction: "up" }, "slow");
  		$("#graph-view-home").show();
  		dacura.system.goTo("#graph-view-home");
	});
}

function updateGraph(upd, pconf){
	handleResp = function(data, pconf){
		var res = new LDResult(data, pconf);
		if(res.status == "accept" && !res.test){
			dacura.ld.fetch("<?=$params['id']?>", <?=$params['view_page_options']?>, drawGraphPage, pconf);			
		}
		res.show();
	}
	dacura.ld.update("<?=$params['id']?>", upd, handleResp, pconf, upd.test);
}

function getPageHTML(data){
	var html = "<div id='graph-details'>" + getGraphDetailsTableHTML(data) + "</div>";
	html += "<div id='graph-schema'>" + getSchemaGraphTableHTML(data) + "</div>";
	html += "<div id='graph-instance'>" + getInstanceGraphTableHTML(data) + "</div>";
	return html;
}

function getStatusRow(data){
	var rd = {
		title: "Status",
		data: dacura.system.resulticons[data.meta.status]
	}; 
	if(data.meta.status == "accept"){
		rd.info = "This graph is currently <strong>enabled</strong> - instance data is verified by the DQS service at <?=$params['dqsurl']?> and published to the triplestore.";
		rd.actions = {"pending": "Disable Graph"};
	}
	else if(data.meta.status == "pending"){
		rd.info = "This graph is currently <strong>partially enabled</strong> - data will be written to the objectstore. The graph must be enabled and approved by the DQS Service at <?=$params['dqsurl']?> before it can be published to the triplestore";
		rd.actions = {accept: "Enable Graph", reject: "Disable Graph" };
	}
	else {
		rd.info = "This graph is currently disabled - no data will be written to the graph";
		rd.actions = {"pending": "Enable Graph"};
		if(data.id != "main"){
			rd.actions["graphdel"] = "Delete Graph"; 
		}	
	}
	return rowdataToRowHTML(rd);
}

function getHistoryRow(data){
	var rd = {
		title: "Version",
		data: "v." + data.meta.version,
		info: "Created " + timeConverter(data.meta.created)
	};
	if(data.history){
		rd.actions = {history: "View History"}; 
		rd.info += ", last updated: " + timeConverter(data.meta.modified);
	}
	return rowdataToRowHTML(rd);
}

function getUpdatesRow(data){
	if(data.updates && data.updates.length){
		var rd = {
			title: "Updates",
			data: data.updates.length,
			actions: {updates: "View Updates"} 
		};
		var cnt = {accept: 0, pending: 0, reject: 0};
		for(var i = 0; i < data.updates.length; i++){
			cnt[data.updates[i].status]++;
		}
		rd.info = cnt.accept + " accepted, " + cnt.reject + " rejected, " + cnt.pending + " pending";  
		return rowdataToRowHTML(rd);
	}
	return "";
}

function getImports(data){
	imps = {};
	if(typeof data.contents == "object" && typeof data.contents["_:schema"] == "object" && typeof data.contents["_:schema"]["owl:imports"] == "object"){
		for(var i = 0; i<data.contents["_:schema"]["owl:imports"].length; i++){
			var imp = urlToImport(data.contents["_:schema"]["owl:imports"][i]);
			if(imp){
				imps[imp.id] = imp;
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

function getImportedRow(data){
	var rd = {
		title: "Imported Ontologies",
		actions: {"imports": "Update Imports"} 
	};
	if(typeof data.contents == "object" && typeof data.contents["_:schema"] == "object" && typeof data.contents["_:schema"]["owl:imports"] == "object"){
		rd.data = data.contents["_:schema"]["owl:imports"].length; 
		rd.info = "";
		for(var i = 0; i < data.contents["_:schema"]["owl:imports"].length; i++){
			var url = data.contents["_:schema"]["owl:imports"][i];
			var meat = url.substring(url.lastIndexOf("/")+1);
			var bits = meat.split("?version=");
			if(bits.length == 1){
				
			}
			else {
				var cls = "implicit-import";
				if(typeof data.meta.explicit_schema_imports == "object" && data.meta.explicit_schema_imports.indexOf(bits[0]) != -1){
					cls = "explicit-import";
				}
				rd.info += "<a title='" + cls + " " + url + "' href='" + url + "' class='" + cls + "'>" + bits[0] + " (v" + bits[1] + ")</a> ";			
			}
		}
	}
	else {
		rd.data = dacura.system.getIcon("error");
		rd.info = "The schema graph is empty - at least one ontology must be added to the graph before it can be used for quality control";
	}
	return rowdataToRowHTML(rd);	
}

function getTriplesRow(data){
	var rd = {
		title: "Triples",
		data: data.analysis.schema_triples,
		info: "Divided up by ontologies maybe?",
		actions: {} 
	};
	if(data.meta.status == "accept"){
		rd.actions.viewgraph = "View Graph";
	}
	return rowdataToRowHTML(rd);		
}

function getDQSRow(data){
	var rd = {
		data: "default",
		title: "Quality Service",
		info: "Need to get this from analysis",
		actions: {"schema-dqs": "Update Schema Tests"} 
	};
	if(typeof data.meta["schema_dqs_tests"] != "undefined"){
		rd.data = "explicit";
		rd.info = typeof data.meta["schema_dqs_tests"] == "object" ? data.meta["schema_dqs_tests"].join(", ") : data.meta["schema_dqs_tests"];
	}
	return rowdataToRowHTML(rd);		
}

function getEntitiesRow(data){

	var rd = {
		title: "Entity Classes",
		info: "Need to get this from analysis",
		actions: {},
		data: 0
	};
	if(typeof data.analysis.entity_classes == 'object'){
		rd.data = data.analysis.entity_classes.length;
		rd.info = data.analysis.entity_classes.join(", ");
	}
	return rowdataToRowHTML(rd);		
}

function getInstancesRow(data){
	var rd = {
		title: "Candidates",
		info: "List of count by candidate - Need to get this from analysis",
		actions: {},
		data: "?"
	};
	return rowdataToRowHTML(rd);		
}		

function getInstanceTriplesRow(data){
	var rd = {
		title: "Triples",
		info: "List of count by instance type - Need to get this from analysis",
		actions: {},
		data: "?"
	};
	if(data.meta.status == "accept"){
		rd.actions.viewinstancegraph = "View Instance Graph";
	}
	return rowdataToRowHTML(rd);			
}

function getInstanceDQSRow(data){
	var rd = {
		data: "default",
		title: "Quality Service",
		info: "Need to get this from analysis",
		actions: {"instance-dqs": "Update Instance Tests"} 
	};
	if(typeof data.meta["instance_dqs_tests"] != "undefined"){
		rd.data = "explicit";
		rd.info = typeof data.meta["instance_dqs_tests"] == "object" ? data.meta["instance_dqs_tests"].join(", ") : data.meta["instance_dqs_tests"];
	}
	return rowdataToRowHTML(rd);			
}


function rowdataToRowHTML(data){
	var html = "<tr><td class='rdtitle'>" + data.title + "</td><td class='rddata'>" + data.data + "</td>";
	html += "<td class='rdinfo'>" + data.info + "</td>";
	html += "<td class='rdactions'>";
	for(var key in data.actions){
		if(typeof data.actions[key] != "undefined"){
			html += "<button class='rdaction' id='" + key + "'>" + data.actions[key] + "</button> ";
		}
	}
	html += "</button>";
	return html;
}

function getGraphDetailsTableHTML(data){
	var html = '<table class="graph-details details-table"><tbody>';
	html += getStatusRow(data);
	html += getHistoryRow(data);
	html += getUpdatesRow(data);
	html += "</tbody></table>";
	return html;
}

function getGraphHeaderHTML(name, url){
	html = "<thead><tr><th class='graph-name'>" + name + "</th>";
	html += "<th colspan='3' class='graph-url'><a href='" + url + "'>" + url + "</th>";
	html += "</tr></thead>";
	return html;
}

function getSchemaGraphTableHTML(data){
	var html = '<table class="graph-details schema-table">';
	html += getGraphHeaderHTML("Schema Graph", data.meta.cwurl + "/schema");
	html += "<tbody>";
	html += getImportedRow(data);
	html += getDQSRow(data);
	html += getTriplesRow(data);
	if(data.meta.status == "accept"){
		html += getEntitiesRow(data);
	}
	html += "</tbody></table>";
	return html;
}

function getInstanceGraphTableHTML(data){
	var html = '<table class="graph-details instance-table">';
	html += getGraphHeaderHTML("Instance Graph", data.meta.cwurl);
	html += "<tbody>";
	html += getInstanceDQSRow(data);
	html += getInstancesRow(data);
	html += getInstanceTriplesRow(data);
	html += "</tbody></table>";
	return html;
}


$('document').ready(function(){
	dacura.ld.fetch("<?=$params['id']?>", <?=$params['view_page_options']?>, drawGraphPage, pconf);
});
</script>