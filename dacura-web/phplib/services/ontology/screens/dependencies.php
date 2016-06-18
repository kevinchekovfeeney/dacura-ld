<div id="dqs-control" class='dacura-control dch'>
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
<div id='alldeps' class='dch'>	
	<div id="dependencies" class='dacura-control'>
		<div class='pane-header'>Dependencies 
			<div class='pane-header-more' id='depcount'>
				<span class='controlsbox'>
					<span class='dependenciesmore'>
						<a class='show-controls' href='javascript:showDependenciesControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showDependenciesControls()'></a>
					</span>
					<span class='dependenciesless dch'>
						<a class='show-controls' href=javascript:hideDependenciesControls()>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideDQSControls()'></a>
					</span>
				</span>
			</div>
		</div>
		<div class='pane-summary' id='dependencies-summary'></div>
	
 		<table id='deptable' class='ont-depend dch'>
			<tbody>
			</tbody>
		</table>
	</div>
	<div id='nsusage' class='dacura-control'>
		<div class='pane-header'>Vocabulary Utilisation 
			<div class='pane-header-more' id='nsusageresult'>
				<span class='controlsbox'>
					<span class='nsmore'>
						<a class='show-controls' href='javascript:showNSUsageControls()'>more</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-s' href='javascript:showNSUsageControls()'></a>
					</span>
					<span class='nsless dch'>
						<a class='show-controls' href=javascript:hideNSUsageControls()>less</a><a class='show-controls show-controls-icon ui-icon ui-icon-carat-1-n' href='javascript:hideNSUsageControls()'></a>
					</span>
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

<div id='structural-links' class='dch dacsub'>
	<div class='subscreen-header'>
		<span id="structural-links-title">Structural Links</span>
		<span class='subscreen-close'></span>
	</div>
	<table class='structural-links rbtable'>
		<thead><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr></thead>
		<tbody></tbody>
	</table>
</div>

<div id="singleont-dependencies" style='clear: both' class='dch dacsub'>
	<div class='subscreen-header'>
		<span id="singleont-dependencies-title"></span>
		<span class='subscreen-close'></span>
	</div>
	<div id="ns-structural"  class='dch'>
	 	<table class='structural-depend rbtable'>
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
	 	<table class='property-depend rbtable'>
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
	 	<table class='subject-depend rbtable'>
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
	 	<table class='object-depend rbtable'>
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
<div id='dqs-subscreens'>
	<div class='dch dacsub dqs-triples'>
 		<div class='subscreen-header'>
			<span class="subscreen-title">Triples - ontology and dependencies serialised as triples</span>
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
				<?php if(isset($params['can_update']) && $params['can_update']){?>
					<input type='checkbox' class='enable-update tests-enable-update' id='enable-update-tests' value="">
					<label for='enable-update-tests'>Update</label>
				<?php } ?>
			</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='subscreen-messages dqs-messages'></div>		
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
				<?php if(isset($params['can_update']) && $params['can_update']){?>
				<input type='checkbox' class='enable-update imports-enable-update' id='enable-update-imports' value="">
				<label for='enable-update-imports'>Update</label>
				<?php } ?>
			</span>
			<span class='subscreen-close'></span>
		</div>
		<div class='subscreen-messages imports-messages'></div>
		<div class='subscreen-body'></div>	
 	</div>
 </div>
<script>
var depids = [];
var test_update_dqs_options = <?php echo isset($params['test_update_dqs_options']) && $params['test_update_dqs_options'] ? $params['test_update_dqs_options'] : "{}";?>;
var update_dqs_options = <?php echo isset($params['update_dqs_options']) && $params['update_dqs_options'] ? $params['update_dqs_options'] : "{}";?>;

/* visibility control functions */

function hideDependenciesControls(){
	$('#deptable').hide("slide", {"direction": "up"});	
	$('.dependenciesless').hide();
	$('.dependenciesmore').show();
	$('#dependencies-summary').show("drop", {"direction": "down"});
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

function showDependenciesControls(){
	$('.dependenciesless').show();
	$('.dependenciesmore').hide();
	$('#dependencies-summary').hide("drop", {"direction": "down"});
	$('#deptable').show("slide", {"direction": "up"});
}

function hideNSUsageControls(){
	$('#nsusagetable').hide("slide", {"direction": "up"});	
	$('.nsless').hide();
	$('.nsmore').show();
	$('#nsusage-summary').show("drop", {"direction": "down"});
}

function showNSUsageControls(){
	$('.nsless').show();
	$('.nsmore').hide();
	$('#nsusage-summary').hide("drop", {"direction": "down"});
	$('#nsusagetable').show("slide", {"direction": "up"});
}

function predicateNSCount(dep){
	return dep['predicates_used'];
}

function predicateNSDistinctCount(dep){
	return dep['distinct_predicates'];
}

function totalNSCount(dep){
	return dep['subjects_used'] + dep['predicates_used'] + dep['values_used'];
}

function structuralNSCount(dep){
	return dep['structural_links'];
}

//generate data for the namespace utilisation row
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

//generate data for the dependencies entry
function getDepRow(dep, id, key, isauto){
	var html = "";
	if(dep['structural_links'] > 0){ 
		html = "<tr class='ontology-list' id='ontology_" + id + "'>" + "<td class='clickable ontology_" + id+ "'>" + dacura.ld.getOntologyViewHTML(key) + "</td><td class='clickable'>";
		html += dep['structural_links'] + "</td></tr>";
	}
	return html; 
}

//display a tree as a table - return html string
function getTreeAsTable(tree){
	var hasentry = false;
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

//generate dependency tree 
function getIncludeTreeHTML(tree){
	var html = "";
	for(var r in tree){
		var stree = {}
		stree[r] = tree[r];
		var treehtml = getTreeAsTable(stree);
		html += treehtml;
		var entries = [];
		if(typeof(tree[r]) == 'object'){
			entries = dacura.ld.getTreeEntries(tree[r]);
			for(var k = 0; k < entries.length; k++){
				html += dacura.ld.getOntologyViewHTML(entries[k], entries[k] + " ontology");
			}
		}
	}
	return html;
}

//add include tree to dom
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
			entries = dacura.ld.getTreeEntries(tree[r]);
			for(var k = 0; k < entries.length; k++){
				$(jqid).append(dacura.ld.getOntologyViewHTML(entries[k], entries[k] + " ontology"));
			}
		}
		cnt += entries.length;
	}
	$(jqid + '_header').html(htxt + " (" + cnt + ")");
	$(jqid + ' .include-treelist').menu();	
}

//generate data for the namespace utilisation entry
function getNSUsageRows(count){
	var rows = [];
	var rowdata = {
		id: "terms",
		count: count.total - count.internal,
		variable: "term" + ((count.total - count.internal) == 1 ? "" : "s"),
		value: "" + (count.total - count.internal),
		help: "Number of terms used from external vocabularies",
		icon: dacura.system.getIcon("term")
	};
	if(rowdata.count == 0){
		rowdata.unclickable = true;
	}
	rows.push(rowdata);
	rowdata = {
		id: "vocabs",
		count: count.namespaces,
		variable: "namespace" + (count.namespaces== 1 ? "" : "s"),
		value: "" + count.namespaces,
		help: "Number of external vocabularies used",
		icon: dacura.system.getIcon("vocab")
	};
	if(rowdata.count == 0){
		rowdata.unclickable = true;
	}
	rows.push(rowdata);
	rowdata = {
		id: "predicates",
		unclickable: true,
		count: count.predicates,
		variable: "predicate" + (count.predicates == 1 ? "" : "s"),
		value: "" + count.predicates,
		help: "Number of predicates used from external vocabularies",
		icon: dacura.system.getIcon("predicate")
	};
	rows.push(rowdata);
	rowdata = {
		unclickable: true,
		id: "distinct",
		count: count.distinct_predicates,
		variable: "distinct",
		value: "" + count.distinct_predicates,
		help: "Number of distinct predicates used from external vocabularies",
	};					
	rows.push(rowdata);
	return rows;	
}

//add table of structural links to dom
function writeStructuralTable(structurals){
	for (var i in structurals) {
		$('.structural-links tbody').append("<tr><td>" + structurals[i][0] + "</td><td>" + structurals[i][1] + "</td><td>" + structurals[i][2] + "</td></tr>");
	}	
}

//get data to populate the dependencies pane
function getDepsRows(count, deps, imported){
	var rows = [];
	rowdata = {
		id: "structural",
		icon: dacura.system.getIcon("structural"),		
		count: count.structural,
		variable: "structural link" + (count.structural == 1 ? "" : "s"),
		value: imported.join(" "),
		help: "Structural links include class relationships (subclassOf, etc) and property relationships",
	};
	if(rowdata.count == 0){
		rowdata.unclickable = true;
	}
	rows.push(rowdata);
	$('#ns-structural').show();	  		
	if(typeof deps['include_tree'] != "undefined"){
		var ndeps = dacura.ld.getTreeEntries(deps.include_tree);
		rowdata = {
			icon: dacura.system.getIcon("dependencies"),
			id: "dependencies",
			count: ndeps.length,
			variable: "dependenc" + (ndeps.length == 1 ? "y" : "ies"),
			value: getIncludeTreeHTML(deps['include_tree']), 
			help: "The dependency chain which is created by structural links between ontologies"
		}
	}
	if(rowdata.count == 0){
		rowdata.unclickable = true;
	}
	rows.push(rowdata);
	if(typeof deps['schema_include_tree'] != "undefined"){
		var ndeps = dacura.ld.getTreeEntries(deps.schema_include_tree);
		rowdata = {
			icon: dacura.system.getIcon("dependencies"),
			id: "schema_dependencies",
			count: ndeps.length,
			variable: "schema link" + (ndeps.length == 1 ? "" : "s"),
			value: getIncludeTreeHTML(deps.schema_include_tree), 
			help: "The ontologies which would be needed to verify updates to this ontology"
		}
	}
	if(rowdata.count == 0){
		rowdata.unclickable = true;
	}
	rows.push(rowdata);
	return rows;
}

//refresh the dependencies from the passed deps object
function refreshDependencies(deps){
	$('#deptable tbody').empty();
	$('#dependencies-summary').empty();
	$('#nsusage-summary').empty();
	showDependencies(deps);
}

//show the dependencies table
function showDependencies(deps){
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
	writeStructuralTable(structurals);
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
	if(itself.length == 0 && builtins.length == 0 && used.length == 0 && unknown.length == 0){
		$('#nsusagetable').remove();
		$('.nsmore').hide();
	}
	
	var nrows = getNSUsageRows(count);
	for(var i = 0; i<nrows.length; i++){
		$('#nsusage-summary').append(dacura.ld.getSummaryTableEntry(nrows[i]));					
	}
	var rows = getDepsRows(count, deps, imported);
	for(var i = 0; i<rows.length; i++){
		$('#deptable tbody').append(dacura.ld.getControlTableRow(rows[i]));
		$('#dependencies-summary').append(dacura.ld.getSummaryTableEntry(rows[i]));					
	}	
	
	$(' .include-treelist').menu();	
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
	  		dacura.system.goTo('#singleont-dependencies');
		});	
	}
	$('#alldeps').show();
	var hvrin = function(){
		$(this).addClass('uhover');
	};
	var hvrout = function() {
	    $(this).removeClass('uhover');
	}
	var hclick = function(){
		var act = this.id.substring(4);
		if(act == "structural"){
			$('.dacsub').hide();
			$('#structural-links').show("drop", { direction: "down" });
	  		dacura.system.goTo('#structural-links');
		}
		else if(act == "dependencies" || act == "schema_dependencies"){
			showDependenciesControls();
		}
		else {
			showNSUsageControls();			
		}
	}
	$('#alldeps .clickable-summary').hover(hvrin, hvrout).click(hclick);	
}

//show the namespace dependencies
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

//generate the list of automatically imported ontologies 
function getAutomaticImports(data){
	var available_imports = <?=$params['available_ontologies']?>;
	imps = {};
	if(typeof data.analysis == "object" && typeof data.analysis.dependencies == "object" && typeof data.analysis.dependencies.include_tree == "object"){
		var ents = dacura.ld.getTreeEntries(data.analysis.dependencies.include_tree);
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

/* dqs configuration management */

function initDQS(data, pconf){
	drawDQS(data, pconf);
	$('#dqs-control').show();	
}

function refreshDQS(data, pconf){
	$('#dqscontroltable tbody').empty();
	$('#dqs-summary').empty();					
	drawDQS(data, pconf);
}

function drawDQS(data, pconf){
	dacura.ld.showAnalysisBasics(data, '.analysis-text');
	$('.analysis-created').html(" (created with version " + data.analysis.version + " at " + timeConverter(data.analysis.created, true) + ")");
	var res = new LDGraphResult(data.analysis.validation, "triples", pconf);
	$('#dqsresult').html(res.getResultHeadlineHTML());
	if(res.status == "accept"){
		$('#dqs-control').addClass("dqs-success");
	}
	else {
		$('#dqs-control').addClass("dqs-failure");	
	}
	var dqsconfig = new DQSConfigurator(data.meta.dqs_tests, <?=$params['default_dqs_tests']?>, <?=$params['dqs_schema_tests']?>, "view", handleDQSUpdate);
	var importer = new OntologyImporter(data.meta.imports, <?=$params['available_ontologies']?>, getAutomaticImports(data), "view", handleImportUpdate);
	if(!can_update){
		importer.show_buttons = false;
		dqsconfig.show_buttons = false;
	}
	rows = dacura.ld.getDQSRows("#dqs-subscreens", res, data.meta, pconf,  importer, dqsconfig);
	for(var i = 0; i<rows.length; i++){
		$('#dqscontroltable tbody').append(dacura.ld.getControlTableRow(rows[i]));
		$('#dqs-summary').append(dacura.ld.getSummaryTableEntry(rows[i]));					
	}
	setDQSDynamics(data, pconf);	
}

//attach events to dqs pane
function setDQSDynamics(data, pconf){
	var hvrin = function(){
		$(this).addClass('uhover');
	};
	var hvrout = function() {
	    $(this).removeClass('uhover');
	}
	var hclick = function(){
		var act = this.id.substring(4);
		$('.dacsub').hide();
		if(act == "imports" || act == "tests"){
			$('.dacura-control').hide();
			$('#dqs-subscreens .dqs-'+act).show("drop", { direction: "down" });			
		}
		else {
			$('#dqs-subscreens .dqs-'+act).show("drop", { direction: "down" });
	  		dacura.system.goTo('#dqs-subscreens .dqs-'+act);
		}
	}
	$('#dqscontroltable tr.control-table-clickable').hover(hvrin, hvrout).click(hclick);
	$('#dqs-summary .clickable-summary').hover(hvrin, hvrout).click(hclick);
	$('#analyse').button().click( function(){
		var opts = <?=$params['fetch_args']?>;
		opts.options.analysis = 2;
		dacura.ld.fetch("<?=$params['id']?>", opts, refreshLDOPage, pconf);		
	});

}

//called when the list of imported ontologies is updated
var handleImportUpdate = function(conf, isauto, test){
	//transform conf back into an owl updates request
	var pconf = {resultbox: ".imports-messages", busybox: ".dqs-imports" }
	var upd = {
		options: (test ? test_update_dqs_options : update_dqs_options ),
		format: "json", 
		test: test
	};
	if(isauto){
		upd.editmode = "update";
		upd.meta = {'imports': []};  
	}
	else {
		upd.editmode = "replace",
		upd.meta = {'imports': conf};  
	}
	updateOntology(upd, pconf)	
} 

//called when the dqs configuration is changed
function handleDQSUpdate(conf, isauto, test){
	var pconf = {resultbox: ".dqs-messages", busybox: ".dqs-tests" }
	var upd = {
		meta: {},
		format: "json", 
		test: test,
		options: (test ? test_update_dqs_options : update_dqs_options )
	};
	if(isauto){
		upd.meta["dqs_tests"] = [];
		upd.editmode = "update";		
	}
	else {
		upd.meta["dqs_tests"] = conf;
		upd.editmode = "replace";
	}
	updateOntology(upd, pconf)	
} 

//called to update the ontology in response to a dqs or import update
function updateOntology(upd, pconf){
	handleResp = function(data, pconf){
		var res = new LDResult(data, pconf);
		if(!res.test && (res.status == "accept" || res.status == "pending")){
			var rconf = { resultbox: ".tool-info", busybox: ".tool-holder"};
			var refreshargs = $.extend(true, {}, ldov.ldo.getAPIArgs());
			refreshargs.options.analysis = 2;
			dacura.ld.fetch("<?=$params['id']?>", refreshargs, refreshLDOPage, rconf);			
		}
		//else if(typeof upd_imports != "undefined" && upd_imports){
		//	importer.setImports(getExplicitImports(data.result), getImplicitImports(data.result));
		//}
		res.show();
	}
	dacura.ld.update("<?=$params['id']?>", upd, handleResp, pconf, upd.test);
}
</script>