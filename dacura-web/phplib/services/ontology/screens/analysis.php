<div id="show-analysis">

<div id="ontology-dependencies">
	<div id="dqs-control">
		<div class='pane-header'>Quality Analysis <div class='pane-header-value' id='dqsresult'></div></div>
		<div class='pane-summary' id='dqs-summary'>
		</div>
		<div class='dqsless dch'>
			<span class='controlsbox'>
				<a class='show-controls' href='javascript:hideDQSControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideDQSControls()'></a>
			</span>
		</div>
		<table id='dqscontroltable' class='pane-full dch '>
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
	<div id="dependencies">
		<div class='pane-header'>Dependencies 
			<div class='pane-header-more' id='depcount'>
				<span class='controlsbox'>
					<a class='show-controls dependenciesmore' href='javascript:showDependenciesControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showDependenciesControls()'></a>
				</span>
			</div>
		</div>
		<div class='pane-summary' id='dependencies-summary'></div>
	
 		<table id='deptable' class='ont-depend dch'>
			<tbody>
			</tbody>
		</table>
	</div>
	<div id='nsusage'>
		<div class='pane-header'>Vocabulary Utilisation 
			<div class='pane-header-more' id='nsusageresult'>
				<span class='controlsbox'>
					<a class='show-controls nsmore' href='javascript:showNSUsageControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showNSUsageControls()'></a>
				</span>
			</div>
		</div>
		<div class='pane-summary' id='nsusage-summary'></div>
		<table id='nsusagetable' class='ont-depend dch'>
			<thead>
				<tr>
					<th>Namespace</th>
					<th>Subjects</th>
					<th>Predicates</th>
					<th>Values</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>	
</div>
<div style='clear: both'>&nbsp;</div>
<div id="analysis-subscreen" class='dch'>
	<div class='subscreen-header'>
		<span id="subscreen-title"></span>
		<span class='subscreen-close'></span>
	</div>
	<div class='subscreen-body'></div>
</div>
<div id="singleont-dependencies" class='dch'>
	<div class='subscreen-header'>
		<span id="singleont-dependencies-title"></span>
		<span class='subscreen-close'></span>
	</div>
	<div id="ns-structural"  class='dch'>
	 	<table class='structural-depend ont-depend'>
			<thead>
				<tr><th colspan='3'>Structural Links</th></tr>
			</thead>
			<tbody>
				<tr>
					<th>Subject</th>
					<th>Property</th>
					<th>Object</th>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="ns-property" class='dch'>
	 	<table class='property-depend ont-depend'>
			<thead>
				<tr><th colspan='2'>Predicates Used</th></tr>
			</thead>
			<tbody>
				<tr>
					<th>Property</th>
					<th>Count</th>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="ns-subject" class='dch'>
	 	<table class='subject-depend ont-depend'>
			<thead>
				<tr><th colspan='2'>Subjects of assertions</th></tr>
			</thead>
			<tbody>
				<tr>
					<th>Subject</th>
					<th>Count</th>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="ns-object" class='dch'>
	 	<table class='object-depend ont-depend'>
			<thead>
				<tr><th colspan='3'>Values Used</th></tr>
			</thead>
			<tbody>
				<tr>
					<th>Subject</th>
					<th>Property</th>
					<th>Object</th>
				</tr>
			</tbody>
		</table>
	</div>
</div>
<div style='clear: both'>&nbsp;</div>
</div>
<style>

.subscreen-header {
	background-color: #eff4f8;
	border: 1px solid #d2e8f8;
	margin: 6px 0;
	font-size: 1.2em;
	padding: 0.5em 1em;
}

span.subscreen-close {
	float: right;
}

span.subscreen-close:hover {
	cursor: pointer;
}
</style>
<script>

function setStyleMisc(){
	$('.rawjson').each(function(){
	    var text = $(this).html();
	    $(this).html(text.substring(0, 50) + "...");
	    text = JSON.stringify(JSON.parse(text), 0, 4);
	    $(this).attr("title", text);
	});
}

var depids = [];
var deps;
var testids = [];

function getNSUsageRow(dep, id, key, isauto){
	var s = dep['subjects_used']; ;
	if(dep['distinct_subjects'] > 1) s += " (" + dep['distinct_subjects'] + " distinct)"; 
	var p = dep['predicates_used'];
	if(dep['predicates_used'] > 1) p += " (" + dep['distinct_predicates'] + " distinct)"; 
	var o = dep['values_used']; 
	var html = "<tr class='ontology-list' id='ontology_" + id + "'>" + "<td class='clickable ontology_" + id + "'>";
	html += dacura.ld.getOntologyViewHTML(key, dep['url'], "");
	html += "</td><td class='clickable'>" + s + "</td><td class='clickable'>";
	html += p + "</td><td class='clickable'>" + o + "</td></tr>";
	return html;
}

function predicateNSCount(dep){
	return dep['predicates_used'];
}

function predicateNSDistinctCount(dep){
	return dep['distinct_predicates'];
}

function getOntTitle(ontv){
	var tit = "id: " + ontv.id + ", collection: " + ontv.collection + ", version: " + ontv.version + ", url: " + ontv.url;
	if(typeof ontv.title == "string" && ontv.title){
		tit += ", title: " + ontv.title;
	}
	return tit;
}

var original_import_state = {
	isauto: true	
};

var import_editor_state = {
	isauto: true
};

function importEditorHasStateChange(){
	if(original_import_state.isauto && import_editor_state.isauto){
		return false;
	}
	if(original_import_state.isauto != import_editor_state.isauto){
		return true;
	}
	for(var k in original_import_state.imports){
		if(typeof import_editor_state.imports[k] != "object"){
			return true;
		}
		for(var j in original_import_state.imports[k]){
			if(typeof import_editor_state.imports[k][j] == "undefined" || import_editor_state.imports[k][j] != original_import_state.imports[k][j]){
				return true;
			}
		} 
	}
	for(var k in import_editor_state.imports){
		if(typeof original_import_state.imports[k] != "object"){
			return true;
		}
		for(var j in import_editor_state.imports[k]){
			if(typeof original_import_state.imports[k][j] == "undefined"){
				return true;
			}
		} 
	}
	return false;	
}

function getImportPickerPage(a, smeta){
	var setting = "automatic";
	if(typeof smeta == "object"){
		for(var i in smeta){
			if(smeta[i].version != 0){
				setting = "manual";
				continue;
			}
		}
	}
	if(setting == "manual"){
		original_import_state.isauto = false;
		import_editor_state.isauto = false;
		original_import_state.imports = $.extend(true, {}, smeta);//clone
		import_editor_state.imports = smeta;
	}
	var avonts = <?= $params["available_ontologies"] ?>;
	var html = "<div class='import-picker'><input type='checkbox' id='import-automatic'";
	if(setting == "automatic") html += " checked"; 
	html += "><label for='import-automatic'>Automatic</label>";
	html += "<span class='auto-imports" + (setting == "manual" ? " dch": "") + "'>";
	for(var k in a){
		var tit = "id: " + a[k].id + ", collection:" + a[k].collection + ", version:" + a[k].version
		html += dacura.ld.getOntologyViewHTML(k, tit, null, a[k].version); 				
	}
	html += "</span>";
	html += "<span class='manual-imports" + (setting == "automatic" ? " dch": "") + "'>";
	for(var k in a){
		var tit = "id: " + a[k].id + ", collection:" + a[k].collection + ", version:" + a[k].version
		if(typeof avonts[k] != "undefined"){
			html += dacura.ld.getOntologySelectHTML(k, tit, a[k].version, avonts[k].version); 						
		}
		else {
			alert(k + " ontology is imported but unknown");
			html += dacura.ld.getOntologySelectHTML(k, tit, a[k].version); 				
		}
	}
	html += "</span><div id='update-imports' class='subscreen-buttons dch'>";
	html += "<button id='cancelupdateimport' class='dacura-update-cancel subscreen-button'>Cancel Changes</button>";		
	html += "<button id='testupdateimport' class='dacura-test-update subscreen-button'>Test New Import Configuration</button>";		
	html += "<button id='updateimport' class='dacura-update subscreen-button'>Save New Import Configuration</button>";
	html += "</div>";	
	var lonts = {};
	var sonts = {};
	for(var k in avonts){
		if(typeof a.k == "undefined"){
			if(avonts[k].collection == "all"){
				sonts[k] = avonts[k];
			}
			else {
				lonts[k] = avonts[k];
			}
		}		
	}
	if(size(lonts) > 0){
		html += "<div class='manual-imports local-ontologies" + (setting == "automatic" ? " dch": "") + "'>";
		html += "<div class='local-ontologies-header'>Available Local Ontologies</div>";
		html += "<div class='local-ontologies-contents'>";
		for(var k in lonts){
			html += dacura.ld.getOntologySelectHTML(k, getOntTitle(avonts[k])); 				
		}
		html += "</div></div>";
	}
	if(size(sonts) > 0){
		html += "<div class='manual-imports system-ontologies" + (setting == "automatic" ? " dch": "") + "'>";
		html += "<div class='system-ontologies-header'>Available System Ontologies</div>";
		html += "<div class='system-ontologies-contents'>";
		for(var k in sonts){
			html += dacura.ld.getOntologySelectHTML(k, getOntTitle(avonts[k])); 				
		}
		html += "</div></div>";
	}
	html += "</div>";
	return html;
}


function totalNSCount(dep){
	return dep['subjects_used'] + dep['predicates_used'] + dep['values_used'];
}

function structuralNSCount(dep){
	return dep['structural_links'];
}

function getDepRow(dep, id, key, isauto){
	var html = "";
	if(dep['structural_links'] > 0){ 
		html = "<tr class='ontology-list' id='ontology_" + id + "'>" + "<td class='clickable ontology_" + id+ "'>" + dacura.ld.getOntologyViewHTML(key) + "</td><td class='clickable'>";
		html += dep['structural_links'] + "</td></tr>";
	}
	return html; 
}

function getControlTableRow(rowdata){
	var html ="<tr class='control-table' id='row_" + rowdata.id + "'>"; 
		if(typeof rowdata.icon != "undefined"){
			html += "<td class='control-table-icon' title='" + escapeHtml(rowdata.help) + "'>" + rowdata.icon + "</td>"; 
		}
		else {
			html += "<td class='control-table-empty'>" + "</td>";
		}
		html += "<td class='control-table-number' id='" + rowdata.id + "-count'>" + rowdata.count + "</td>" + 
		"<td class='control-table-variable' title='" + escapeHtml(rowdata.help) + "'>" + rowdata.variable + "</td>" + 
		"<td class='control-table-value'>" + rowdata.value + "</td></tr>";
	return html;
}

function getSummaryTableEntry(rowdata){
	var html = "<div class='summary-entry'";
	if(rowdata.id){
		html += " id='st_" + rowdata.id + "'";
	}
	if(typeof rowdata.icon != "undefined"){
		html += "><span class='summary-icon' title='" + rowdata.help + "'>" + rowdata.icon + "</span>"; 
	}
	else {
		html += ">";
	}	
	html +=	"<span class='summary-value' title='" + escapeHtml(escapeQuotes(rowdata.value)) + "'>"  + rowdata.count + "</span> " +
		"<span class='summary-variable' title='" + escapeHtml(escapeQuotes(rowdata.value)) + "'>" + rowdata.variable + "</span></div>";
	return html;
}

var showDependencies = function(deps){
	depids = [];
	var count = {unknown: 0, internal: 0, builtins: 0, structural: 0, total: 0, predicates: 0, distinct_predicates: 0, namespaces: 0};
	var itself = "";
	var unknown = "";
	var builtins = [];
	var imported = [];
	var used = [];
	var structurals = [];
	for (var key in deps) {
		if(!isEmpty(deps[key])){
			if(key == "<?=$params['id']?>" || key == "_"){
				count.internal += totalNSCount(deps[key]);
				itself = getNSUsageRow(deps[key], depids.length, key, true);
			}
			else if(key == "rdf" || key == "rdfs" || key == "owl" || key == "xsd"){
				count.namespaces++;
				count.builtins += totalNSCount(deps[key]);
				builtins.push(getNSUsageRow(deps[key], depids.length, key, true));
			}
	 		else if(key == "unknown"){
		 		count.unknown += totalNSCount(deps[key]); 
				unknown = getNSUsageRow(deps[key], depids.length, key, false);
			}
			else if(!(key == "includes" || key == "include_tree" || key == "schema_include_tree")){
				count.namespaces++;
				if(deps[key]['structural_links'] > 0){ 
					count.structural += structuralNSCount(deps[key]);
					for(var k = 0; k < deps[key]['structural_links']; k++){
						structurals.push(deps[key]['structural'][k]);
					}
					imported.push(dacura.ld.getOntologyViewHTML(key) + " " + deps[key]['structural_links']);
				}
				used.push(getNSUsageRow(deps[key], depids.length, key));
			}
			if(!(key == "includes" || key == "include_tree" || key == "schema_include_tree")){
				count.total += totalNSCount(deps[key]);
				if(!(key == "<?=$params['id']?>" || key == "_")){
					if(deps[key]['predicates_used']){
						count.predicates += deps[key]['predicates_used'];
						count.distinct_predicates += deps[key]['distinct_predicates'];
					};
				}	
			}	
			depids[depids.length] = key;		  	 		  					  				    
		}
	}
	var rowdata = {
		id: "terms",
		count: count.total - count.internal,
		variable: "term" + ((count.total - count.internal) == 1 ? "" : "s"),
		value: "" + (count.total - count.internal),
		help: "Number of terms used from external vocabularies",
		icon: dacura.system.resulticons.pending
	};
	$('#nsusage-summary').append(getSummaryTableEntry(rowdata));
	rowdata = {
		id: "vocabs",
		count: count.namespaces,
		variable: "vocab" + (count.namespaces== 1 ? "" : "s"),
		value: "" + count.namespaces,
		help: "Number of external vocabularies used",
		icon: dacura.system.resulticons.pending
	};
	$('#nsusage-summary').append(getSummaryTableEntry(rowdata));
	rowdata = {
		id: "predicates",
		count: count.predicates,
		variable: "predicate" + (count.predicates == 1 ? "" : "s"),
		value: "" + count.predicates,
		help: "Number of predicates used from external vocabularies",
		icon: dacura.system.resulticons.pending
	};
	$('#nsusage-summary').append(getSummaryTableEntry(rowdata));
	rowdata = {
		id: "distinct",
		count: count.distinct_predicates,
		variable: "distinct",
		value: "" + count.distinct_predicates,
		help: "Number of distinct predicates used from external vocabularies",
		icon: dacura.system.resulticons.info
	};					
	$('#nsusage-summary').append(getSummaryTableEntry(rowdata));
	
	var rows = [];
	rowdata = {
		id: "structural",
		count: count.structural,
		variable: "structural link" + (count.structural == 1 ? "" : "s"),
		value: imported.join(" "),
		help: "Structural links include class relationships (subclassOf, etc) and property relationships",
	};
	rows.push(rowdata);
	subscreens['structural'] = "<table class='structural-depend ont-depend'><thead><tr><th colspan='3'>Structural Links</th></tr></thead><tbody>";
	for (var i in structurals) {
		subscreens['structural'] += "<tr><td>" + structurals[i][0] + "</td><td>" + 
				structurals[i][1] + "</td><td>" + structurals[i][2] + "</td></tr>";		
	}
	subscreens['structural'] += "</tbody></table>";
	$('#ns-structural').show();	  		
	if(typeof deps['include_tree'] != "undefined"){
		var ndeps = getTreeEntries(deps.include_tree);
		rowdata = {
			id: "dependencies",
			count: ndeps.length,
			variable: "dependenc" + (ndeps.length == 1 ? "y" : "ies"),
			value: getIncludeTreeHTML(deps['include_tree']), 
			help: "The dependency chain which is created by structural links between ontologies"
		}
	}
	rows.push(rowdata);
	if(typeof deps['schema_include_tree'] != "undefined"){
		var ndeps = getTreeEntries(deps.schema_include_tree);
		rowdata = {
			id: "schema_dependencies",
			count: ndeps.length,
			variable: "schema vocab" + (ndeps.length == 1 ? "" : "s"),
			value: getIncludeTreeHTML(deps.schema_include_tree), 
			help: "The ontologies which would be needed to verify updates to this ontology"
		}
	}
	rows.push(rowdata);
	for(var i = 0; i<rows.length; i++){
		$('#deptable tbody').append(getControlTableRow(rows[i]));
		$('#dependencies-summary').append(getSummaryTableEntry(rows[i]));					
	}	

	$(' .include-treelist').menu();	
	$('#nsusagetable tbody').html("");
	if(itself.length > 0){
	    $('#nsusagetable tbody').append(itself);
	}
	if(builtins.length > 0){
	    $('#nsusagetable tbody').append(builtins.join());			
	}
	if(used.length > 0){
	    $('#nsusagetable tbody').append(used.join());			
	}
	if(unknown.length > 0){
	    $('#nsusagetable tbody').append(unknown);
	}
	for(var i = 0; i<depids.length; i++){
	  	$('#ontology_' + i + " td.clickable").click( function (event){
	  		if(depids[this.parentNode.id.substr(9)] == "_"){
	
		  	}
	  		if(depids[this.parentNode.id.substr(9)] == "<?=$params['id']?>"){
		  		var ledeps = deps["_"];
		  		var medeps = deps[depids[this.parentNode.id.substr(9)]];
		  		if(ledeps){
					if(typeof medeps.subject == "object"){
						for(var k in ledeps.subject){
							medeps.subject[k] = ledeps.subject[k]; 
						}
					}
					if(typeof medeps.object == "object"){
						for(var i = 0; i < ledeps.object.length; i++){
						 	medeps.object.push(ledeps.object[i]);
						}
					}
					if(typeof medeps.structural == "object") {
						for(var i = 0; i < ledeps.structural.length; i++){
						 	medeps.structural.push(ledeps.structural[i]);
						}
					}
					if(typeof medeps.predicate == "object") {
						for(var k in ledeps.predicate){
							medeps.predicate[k] = predicate.subject[k]; 
						}
					}
				}
		  		showNSDependencies(medeps, depids[this.parentNode.id.substr(9)]);
	  		
		  	}
	  		else {
		  		showNSDependencies(deps[depids[this.parentNode.id.substr(9)]], depids[this.parentNode.id.substr(9)]);
	  		}
	  		$('#singleont-dependencies').show();
	  		$('#analysis-subscreen').hide();
	  		dacura.system.goTo('#singleont-dependencies');
		});	
	}
	$('.ontology-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});

	for(var i = 0; i<depids.length; i++){
	  	$('#ontology_' + i + " td.clickable").click( function (event){
	  		showNSDependencies(deps[depids[this.parentNode.id.substr(9)]], depids[this.parentNode.id.substr(9)]);
	  		$('#singleont-dependencies').show();
	  		$('#analysis-subscreen').hide();
	  		dacura.system.goTo('#singleont-dependencies');
	  		$('.subscreen-close').click(function(){
	  	  		dacura.system.goTo('#ontology-dependencies');
	  	  		$('#singleont-dependencies').hide();
	  		});	
		});	
	}
}

function showSubscreen(key, contents){
	stitle = {
		errors: "Errors",
		warnings: "Warnings",
		tests: "DQS Tests",
		triples: "Ontology and dependencies serialised as triples",
		imports: "Imported ontologies",
		structural: "Structural links"
	};
	$('#subscreen-title').html(stitle[key]);
	$('.subscreen-body').html(contents);
	$('#analysis-subscreen').show();
	dacura.system.goTo('#analysis-subscreen');
	$('#singleont-dependencies').hide();
	$('.subscreen-close').click(function(){
  		$('#ontology-dependencies').show();
  		dacura.system.goTo('#ontology-dependencies');
  		$('#analysis-subscreen').hide();
	});
	if(key == "imports"){
		createImportDynamics();
	}
	else if(key == "tests"){
		createTestsDynamics();	
	}
}

function createTestsDynamics(){
	$('.dqs-all-config-element').buttonset();
}

function createImportDynamics(){
	$('span.remove-ont').hover(function(){
		$(this).addClass('uhover');
	}, function() {
	    $(this).removeClass('uhover');
	});
	$('span.ontlabeladd').hover(function(){
		$(this).addClass('uhover');
	}, function() {
	    $(this).removeClass('uhover');
	});
	$('span.ontlabeladd').click(function(){
		addOntologyToImports(this.id.substring(13));
	});
	$('span.remove-ont').click(function(){
		removeOntologyFromImports(this.id.substring(16));
	});
	$('#cancelupdateimport').button().click(function(){
		alert("cancel");
	});
	$('#testupdateimport').button().click(function(){
		jpr(import_editor_state);
	});
	$('select.imported_ontology_version').selectmenu({
		change: function( event, ui ) {
			changeOntologyVersion(this.id.substring(26), this.value);
		}
	});
	$('#import-automatic').button().click(function(){
		if(this.checked){
			setAutomatic();
		}
		else {
			setManual();
		}
	});
	$('#updateimport').button().click(function(){
		alert("import");	
	});
}


function registerImportEvent(){
	if(importEditorHasStateChange()){
		$('#update-imports').show();
	}
	else {
		$('#update-imports').hide();
	}
}



function addOntologyToImports(ontid){
	var avonts = <?= $params['available_ontologies'] ?>;
	if(typeof avonts[ontid] == "undefined"){
		alert(ontid + " is imported but unknown");
		return;
	}
	var avont = avonts[ontid];
	import_editor_state.imports[ontid] = {id: ontid, version: 0};
	registerImportEvent();
	var tit = getOntTitle(avont);
	$("span.manual-imports").append(dacura.ld.getOntologySelectHTML(ontid, tit, 0, avont.version));
	$("#remove_ontology_" + ontid).hover(function(){
		$(this).addClass('uhover');
	}, function() {
	    $(this).removeClass('uhover');
	}).click(function(){
		removeOntologyFromImports(this.id.substring(16));
	});
	$('#imported_ontology_version_'+ontid).selectmenu({
		change: function( event, ui ) {
			changeOntologyVersion(ontid, this.value);
		}
	});
	$("#add_ontology_" + ontid).remove();
	
}

function removeOntologyFromImports(ontid){
	delete(import_editor_state.imports[ontid]);
	registerImportEvent();	
	$("#imported_ontology_" + ontid).remove();
	var avonts = <?= $params['available_ontologies'] ?>;
	if(typeof avonts[ontid] != "undefined"){
		var avont = avonts[ontid];
		if(avont.collection == "all"){
			$('div.system-ontologies-contents').prepend(dacura.ld.getOntologySelectHTML(ontid, getOntTitle(avont)));
		}
		else {
			$('div.local-ontologies-contents').prepend(dacura.ld.getOntologySelectHTML(ontid, getOntTitle(avont)));		
		}
		$("#add_ontology_" + ontid).hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			addOntologyToImports(this.id.substring(13));
		});
	}	
}


function changeOntologyVersion(ontid, newv){
	import_editor_state.imports[ontid].version = newv;
	registerImportEvent();	
}

function setAutomatic(){
	import_editor_state.isauto = true;
	registerImportEvent();
	$('.manual-imports').hide();
	$('.auto-imports').show();
	
}

function setManual(){
	import_editor_state.isauto = false;
	registerImportEvent();
	$('.manual-imports').show();
	$('.auto-imports').hide();	
}


function getTreeAsTable(tree){
	var hasentry = false;
	//var html = "<table class='include-tree'>";
	var html = "<ul class='include-treelist'>";
	for(var r in tree){
		hasentry = true;
		html += "<li>" + r;
		html += getTreeAsTable(tree[r]);		
		html += "</li>";
	}
	html += "</ul>";
	if(hasentry){
		return html;
	}
	return "";
}

function getIncludeTreeHTML(tree){
	var html = "";
	for(var r in tree){
		var stree = {}
		stree[r] = tree[r];
		var treehtml = getTreeAsTable(stree);
		html += treehtml;
		var entries = [];
		if(typeof(tree[r]) == 'object'){
			entries = getTreeEntries(tree[r]);
			for(var k = 0; k < entries.length; k++){
				html += dacura.ld.getOntologyViewHTML(entries[k], entries[k] + " ontology");
			}
		}
	}
	return html;
}

function showIncludeTree(tree, jqid, htxt){
	var cnt = 0;
	$(jqid).html("");
	for(var r in tree){
		cnt++;
		var stree = {}
		stree[r] = tree[r];
		var treehtml = getTreeAsTable(stree);
		$(jqid).append(treehtml);
		var entries = [];
		if(typeof(tree[r]) == 'object'){
			entries = getTreeEntries(tree[r]);
			for(var k = 0; k < entries.length; k++){
				$(jqid).append(dacura.ld.getOntologyViewHTML(entries[k], entries[k] + " ontology"));
			}
		}
		cnt += entries.length;
	}
	$(jqid + '_header').html(htxt + " (" + cnt + ")");
	$(jqid + ' .include-treelist').menu();	
}

function getTreeEntries(tree){
	var ents = [];
	for(var i in tree){
		if(ents.indexOf(i) == -1){
			ents.push(i);
		}
		if(typeof(tree[i]) == 'object'){
			var ments = getTreeEntries(tree[i]);
			for(var j = 0; j<ments.length; j++){
				if(ents.indexOf(ments[j]) == -1){
					ents.push(ments[j]);
				}
			}
		}
	}
	return ents;
}


function showNSDependencies(dep, ns){
	$('#singleont-dependencies-title').html("Dependencies on " + ns + " ontology");
	var hassubj = false;
	if(typeof dep.subject == "object" && size(dep.subject) > 0){
		$('table.subject-depend tbody').empty();
		for (var key in dep.subject) {
	  		$('table.subject-depend tbody').append("<tr><td>" + key + "</td><td>" + dep.subject[key] + "</td></tr>");
	  	}
		$('#ns-subject').show();	  	
	}
	else {
		$('#ns-subject').hide();	  			
	}
	if(typeof dep.predicate == "object" && size(dep.predicate) > 0){
		$('.property-depend tbody').empty();
		for (var key in dep.predicate) {	
			$('.property-depend tbody').append("<tr><td>" + key + "</td><td>" + dep.predicate[key] + "</td></tr>");
		}
		$('#ns-property').show();	  			
	}
	else {
		$('#ns-property').hide();	  	
	}
	if(typeof dep.structural == "object" && dep.structural.length > 0){
		$('table.structural-depend tbody').empty();
		for (var i = 0; i < dep.structural.length; i++) {
			$('table.structural-depend tbody').append("<tr><td>" + dep.structural[i][0] + "</td><td>" + 
					dep.structural[i][1] + "</td><td>" + dep.structural[i][2] + "</td></tr>");		
		}
		$('#ns-structural').show();	  			
	}
	else {
		$('#ns-structural').hide();	  			
	}
	if(typeof dep.object == "object" && dep.object.length > 0){
		$('table.object-depend tbody').empty();
		for (var i = 0; i < dep.object.length; i++) {
			$('table.object-depend tbody').append("<tr><td>" + dep.object[i][0] + "</td><td>" + 
				dep.object[i][1] + "</td><td>" + dep.object[i][2] + "</td></tr>");		
		}
		$('#ns-object').show();	  			
	}
	else {
		$('#ns-object').hide();	  			
	}
	$('#analysis-subscreen').hide();
	$('#singleont-dependencies').show();
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

function initDecorations(){
	$('#ontology-dependencies').show();
	$('#analyse').button();
	
	$('#singleont-dependencies').hide();
	$('.subscreen-close').html(dacura.system.resulticons['error']);
}

function getDQSRows(res, imports){
	var rows = [];
	$('#dqsresult').html(res.getResultHeadlineHTML());
	if(res.hasWarnings()){
		subscreens["warnings"] = res.getWarningsHTML();
		var rowdata = {
			id: "warnings",
			icon: dacura.system.resulticons.warning,
			count: res.warnings.length,
			variable: "warning" + (res.warnings.length == 1 ? "" : "s"),
			value: res.getWarningsSummary(),
			actions: "view_warnings",
			help: "Warnings may indicate an error or a lapse in best practice but they do not prevent the use of the ontology to validate instance data"
		};
		rows.push(rowdata);	
	}
	if(res.hasErrors()){
		subscreens["errors"] = res.getErrorsHTML();
		var rowdata = {
			id: "errors",
			icon: dacura.system.resulticons.error,
			count: res.errors.length,
			variable: "error" + (res.errors.length == 1 ? "" : "s"),
			value: res.getErrorsSummary(),
			actions: "view_errors",
			help: "Errors indicate problems with the ontology which will prevent it from being used to validate instance data"
		};	
		rows.push(rowdata);	
	}
	if(typeof res.tests != "undefined"){
		var dqstests = <?= $params["dqs_schema_tests"] ?>;
		subscreens["tests"] = res.getDQSConfigPage(dqstests, "all");
		rowdata = {id: "tests"};
		rowdata.count = res.tests;
		rowdata.icon = dacura.system.resulticons.pending;
		if(typeof res.tests == "object"){
			rowdata.count = res.tests.length;
			if(rowdata.count == 0){
				rowdata.icon = dacura.system.resulticons.warning;
				rowdata.value = "No tests configured";
			}
			else {
				rowdata.value = res.getTestsSummary(dqstests);				
			}
		}
		else if(res.tests == "all"){
			rowdata.value = res.getTestsSummary(dqstests);				
			rowdata.count = size(dqstests);  			
		}
		rowdata.variable = "test" + ((typeof res.tests == 'object' && res.tests.length == 1) ? "" : "s");
		rowdata.actions = "config_dqs";
		rowdata.help = "The quality service can be configured to apply many different types of tests to ontologies - the current configuration is listed here";
	}
	else {
		var dqstests = <?= $params["dqs_schema_tests"] ?>;
		subscreens["tests"] = res.getDQSConfigPage(dqstests, "all");
		var rowdata = {
			id: "tests",
			icon: dacura.system.resulticons.warning,
			count: 0,
			variable: "tests",
			value: "No DQS tests configured - this ontology will not be tested by the quality service",
			actions: "config_dqs",
			help: "You must specify quality tests to be used with this ontology"
		};
	}
	rows.push(rowdata);	
	if(typeof res.imports != "undefined"){
		subscreens["imports"] = getImportPickerPage(res.imports, imports);
		var rowdata = {
			id: "imports",
			icon: dacura.system.resulticons.info,
			count: size(res.imports),
			variable: "import" + (size(res.imports) == 1 ? "" : "s"),
			value: res.getImportsSummary(imports),
			actions: "config_imports",
			help: "Ontologies must import those ontologies on which they have a structural dependence. "
		};
	}
	rows.push(rowdata);	
	if(typeof res.inserts != "undefined" && res.inserts.length > 0){
		subscreens["triples"] = dacura.ld.getTripleTableHTML(res.inserts);
		var rowdata = {
			id: "triples",
			icon: dacura.system.resulticons.info,
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
			icon: dacura.system.resulticons.warning,
			count: 0,
			variable: "triples",
			value: "The graph for this ontology is currently empty, you must add contents to it before it can be serialised",
			help: "Ontologies are serialised into a set of triples before being loaded by the DQS"
		};
	}
	rows.push(rowdata);
	return rows;
}

var subscreens = {};

function analyse(data, pconf){
	initDecorations();
	if(typeof data.analysis == "object"){
		$('.analysis-created').html(" (created with version " + data.meta.version + " at " + timeConverter(data.analysis.created, true) + ")");
		if(data.analysis.version != data.meta.version){
			var upds = data.meta.version  - data.analysis.version; 
			var html = dacura.system.resulticons.warning + " this analysis is stale, " 
			html += (upds == 1 ? "there has been an update " : " there have been " + upds + " updates");
			html += " since this analysis was created";
			$('.analysis-text').html(html);
		}
		else {
			var html = dacura.system.resulticons.success + "Analysis is up to date";
			$('.analysis-text').html(html);			
		}
	}
	if(typeof data.analysis.dependencies == "object"){
		showDependencies(data.analysis.dependencies);
	}
	if(typeof data.analysis.validation == "object"){
		var res = new LDGraphResult(data.analysis.validation, "triples", pconf);
		rows = getDQSRows(res, data.meta.imports);
		for(var i = 0; i<rows.length; i++){
			$('#dqscontroltable tbody').append(getControlTableRow(rows[i]));
			$('#dqs-summary').append(getSummaryTableEntry(rows[i]));					
		}	
		$('#dqs-summary').append("<div class='dqsmore'><span class='controlsbox'><a class='show-controls' href='javascript:showDQSControls()'>more</a> <a class='show-controls-icon show-controls ui-icon ui-icon-carat-1-s' href='javascript:showDQSControls()'></a></span></div>");
	}
	$('#dqscontroltable tr').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	$('#dqscontroltable tr').click(function(){
		var act = this.id.substring(4);
		showSubscreen(act, subscreens[act]);
	});
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

function hideDependenciesControls(){
	$('#dependencies-summary').show();
	$('#deptable').hide();	
	var html = "<span class='controlsbox'><a class='show-controls dependenciesmore' href='javascript:showDependenciesControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showDependenciesControls()'></a></span>";
	$('#depcount').html(html);	
}

function showDependenciesControls(){
	$('#dependencies-summary').hide();
	$('#deptable').show();
	var html = "<span class='controlsbox'><a class='show-controls dependenciesless' href='javascript:hideDependenciesControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideDependenciesControls()'></a></span>";
	$('#depcount').html(html);	
}

function hideNSUsageControls(){
	$('#nsusage-summary').show();
	$('#nsusagetable').hide();
	var html = "<span class='controlsbox'><a class='show-controls nsmore' href='javascript:showNSUsageControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showNSUsageControls()'></a></span>";
	$('#nsusageresult').html(html);	
}

function showNSUsageControls(){
	$('#nsusage-summary').hide();
	$('#nsusagetable').show();
	var html = "<span class='controlsbox'><a class='show-controls nsless' href='javascript:hideNSUsageControls()'>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideNSUsageControls()'></a></span>";
	$('#nsusageresult').html(html);	
}

function hideDQSControls(){
	$('#dqs-summary').show();
	$('.dqsanalysis').hide();
	$('.dqsless').hide();
	$('#dqscontroltable').hide();
}

function showDQSControls(){
	$('.dqsless').show();
	$('#dqs-summary').hide();
	$('.dqsanalysis').show();
	$('#dqscontroltable').show();
}

</script>