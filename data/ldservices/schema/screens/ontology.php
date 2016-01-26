<script src='<?=$service->furl("js", "jquery.dataTables.js")?>'></script>
<script src='<?=$service->furl("js", "dataTables.jqueryui.js")?>'></script>
<script src='<?=$service->furl("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "dataTables.jqueryui.css")?>" />
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "jquery.json-editor.css")?>" />

<script>
function showDQSOptions(){
	$('#dqsopts').show();	
}

function validateSchema(id){
	var ajs = dacura.schema.api.validate_ontology(id);
	var msgs = { "busy": "Validating Ontology with Dacura Quality Service", "fail": "Schema validation failed"};
	var self = this;
	ajs.handleResult = function(data){ alert(JSON.stringify(data));};
	dacura.system.invoke(ajs, msgs);
}

$(function() {
	$('.dqsoption').button();
});
</script>
<style>
  .ui-menu { width: 120px; }
</style>

<div id='version-header' class="dch">
	<span class='vc version-title'></span>
	<span class='vc version-created'></span>
	<span class='vc version-replaced'></span>
	<span class='vc version-details'></span>
</div>	

<div id='tab-holder'>
	 <ul id="ontology-pane-list" class="dch">
	 	<li><a href="#ontology-contents">Contents</a></li>
	 	<li><a href="#ontology-meta">Dependencies</a></li>
	 	<li><a href="#ontology-test">Quality Test</a></li>
	 </ul>
	 <div id="dqs-holder">
		<div id='ontology-test' class="dch">
			<div id='dep-msgs'></div>
			<div id='dacura-problems'></div>
			<div id='test-includes'></div>
			<div id='dqs-options'>
				<div class='dqs-embed'>
					<?= $service->showDQSControls("schema", "all"); ?>
					<div style='clear: both'></div>
				</div>
				<div class='dqs-button'>
					<a class='button2' href='javascript:validateOntologies();'>Validate Selected Ontologies</a>
				</div>				
			</div>
		</div>
	 </div>
	 <div id="meta-holder">
		<div id='ontology-meta' class="dch">
			<div id='deps-msgs'></div>
			<div id="ontology-dependencies">
				<div id='deptable' class='ld-list'>
				</div>
				<div id='included_onts'>
				</div>
				<div class="tool-buttons">
		   			<button class="dacura-button depend-button" id="depend-button">Calculate Dependencies</button>
		      	</div>
			</div>
			<div id='singleont-dependencies' class='dch'>
		   		<button class="dacura-button showlist-button" style="float:right">Return to dependency listing</button>
				<div id='singleont-table'></div>
				<div class="tool-buttons">
		   			<button class="dacura-button showlist-button">Return to dependency listing</button>
		      	</div>
			
			</div>
		</div>
	</div>
 	<div id="contents-holder">
		<div id='ontology-contents' class="dch" >
			<div id='lded-msgs'></div>
			<div id='viewont'>
				<?php echo $service->showLDResultbox($params);?>
				<?php echo $service->showLDEditor($params);?>
			</div>
		</div>
	</div>
</div>
<div id="tabletemplates" class='dacura-templates'>
	<?php echo $service->includeSnippet("ldentity-header")?>
	 <div id="depend-template">
	 	<table class='ont-depend'>
			<thead>
				<tr>
					<th>Shorthand</th>
					<th>URL</th>
					<th>Subjects (count)</th>
					<th>Properties (count)</th>
					<th>Structural Links</th>
					<th>Values</th>
					<th>Include</th>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>
	<div id="subject-template">
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
	<div id="property-template">
	 	<table class='property-depend ont-depend'>
			<thead>
				<tr><th colspan='2'>Properties Used</th></tr>
			</thead>
			<tbody>
				<tr>
					<th>Property</th>
					<th>Count</th>
				</tr>
			</tbody>
		</table>
	</div>
	<div id="structural-template">
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
	<div id="object-template">
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
	<div id="toolheader-template">
		<table class='ld-invariants'>
			<thead>
				<tr>
					<th>URL</th>
					<th>Status</th>
					<th>Imported</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td class='ont_uri'></td>
					<td class='ont_status'></td>
					<td class='ont_created'></td>
				</tr>
			</tbody>
		</table>
	</div>
	
</div>

<script>
dacura.schema.showHeader = function(ont){
	options = { title: ont.id + " ontology" };
	if(typeof ont.image != "undefined"){
		options.image = ont.image;
	}
	options.subtitle = ont.meta.title;
	options.description = $('#toolheader-template').html();
	dacura.system.updateToolHeader(options);
	metadetails = timeConverter(ont.created);
	$('.ont_uri').html("<span class='ontology-uri'>" + ont.meta.url + "</span>");
	$('.ont_created').html("<span class='ontology-details'>" + metadetails + "</span>");
	$('.ont_status').html("<span class='ontology-status ontology-" + ont.latest_status + "'>" + ont.latest_status + "</span>");
    dacura.schema.drawVersionHeader(ont);	
}

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

function validateOntologies(){
	var onts = [];
	for (index = 0; index < deps.includes.length; ++index) {	
		if($('#incont_' +  deps.includes[index]).is(":checked")){
			onts.push(deps.includes[index]);
		}	
		else {
			//alert(deps.includes[index] + " not included");
		}
	}
	var tests = dacura.dqs.getSelection("schema");
	dacura.schema.validateGraphOntologies(onts, tests, { resultbox: "#dep-msgs", errorbox: "#dep-msgs", busybox: "#ontology-test", scrollto: "#dep-msgs"});
}

function getDepRow(dep, id, key, isauto){
	var s = dep['distinct_subjects'];
	if(s > 0) s += " (" + dep['subjects_used'] + ")"; 
	var p = dep['distinct_properties'];
	if(p > 0) p += " (" + dep['properties_used'] + ")"; 
	var l = dep['structural_links']; 
	var o = dep['values_used']; 
	if(key == "unknown") dep['url'] = "";
	var html = "<tr class='ontology-list' id='ontology_" + id + "'>" + "<td class='clickable ontology_" + id+ "'>" + key + "</td><td class='clickable'>";
	html += dep['url'] + "</td><td class='clickable'>" + s + "</td><td class='clickable'>" + p + "</td><td class='clickable'>" + l + "</td><td>" + o;
	if(typeof isauto != "undefined" && isauto != false){
		html += "<td class='clickable'>auto</td></tr>";
	}
	else if(typeof isauto != "undefined"){
		html += "<td class='clickable'></td></tr>";	
	}
	else {
		if(dep['distinct_properties'] > 0 || dep['structural_links'] > 0){
			html += "<td class='clickable'>yes</td></tr>";
		}
		else {
			html += "<td class='clickable'>no</td></tr>";		
		}
	}
	return html;
}

dacura.schema.showDependencies = function(d){
	deps = d;
	depids = [];
	var builtins = "";
	var itself = "";
	var imported = "";
	var unknown = "";
	var problems = {"properties": [], "structural": [], "subject": {}};
	var k = $('#depend-template').html();
	$('#deptable').html(k);
	for (var key in deps) {
	  	if (deps.hasOwnProperty(key)) {
			if(!isEmpty(deps[key])){
				if(key == "<?=$params['id']?>"){
					itself = getDepRow(deps[key], depids.length, key, true);
				}
				else if(key == "rdf" || key == "rdfs" || key == "owl" || key == "xsd"){
					builtins += getDepRow(deps[key], depids.length, key, true);
					if(deps[key]['distinct_subjects'] > 0){
						for(var sk in deps[key]['subject']){
							if(typeof problems.subject[sk] == "undefined"){
								problems.subject[sk] = deps[key]['subject'][sk];
							}
							else {
								problems.subject[sk] += deps[key]['subject'][sk];							
							}
						}					
					}
				}
				else if(key == "unknown"){
					problems.properties = deps[key].properties;
					problems.structural = deps[key].structural;
					unknown = getDepRow(deps[key], depids.length, key, false);
				}
				else if(key == "includes" || key == "include_tree"){
				}
				else {
					if(deps[key]['distinct_subjects'] > 0){
						for(var sk in deps[key]['subject']){
							if(typeof problems.subject[sk] == "undefined"){
								problems.subject[sk] = deps[key]['subject'][sk];
							}
							else {
								problems.subject[sk] += deps[key]['subject'][sk];							
							}
						}					
					}
					imported += getDepRow(deps[key], depids.length, key);	
				}
			  	depids[depids.length] = key;		  	 		  					  				    
			}
		}
	}
	$('#deptable table tbody').append(itself + builtins);
	if(imported.length > 0){
	    $('#deptable table tbody').append(imported);
	}
	if(unknown.length > 0){
	    $('#deptable table tbody').append(unknown);
	}
	for(var i = 0; i<depids.length; i++){
	  	$('#ontology_' + i + " td.clickable").click( function (event){
	  		$('#ontology-dependencies').hide();
	  		$('#singleont-dependencies').show();
	  		dacura.schema.showNSDependencies(depids[this.parentNode.id.substr(9) ]);
	    });	
	}
	$('.ontology-list').hover(function(){
		$(this).addClass('userhover');
	}, function() {
	    $(this).removeClass('userhover');
	});
	if(typeof deps['includes'] != "undefined" && typeof deps['include_tree'] != "undefined"){
		dacura.schema.showIncludeTree(deps['includes'], deps['include_tree']);
		var inchtml = "";
		$('#test-includes').html("<div class='title'>Included Ontologies (" + (deps['includes'].length - 1) + ")</div>");
		for(var i = 0; i < deps['includes'].length; i++){
			var sh = deps['includes'][i];
			$('#test-includes').append("<span class='ontinc'><input type='checkbox' checked='checked' id='incont_" + sh + "'> <label for='incont_" + sh + "'>" + sh + "</label></span>"); 
		}			
	}
	//finally the problems
	if(!(isEmpty(problems.properties)  && isEmpty(problems.subject) && isEmpty(problems.structural))){
		var msg = "";
		var tit = "Warning - problems in ontology";
		var extra = "";
		if(!isEmpty(problems.properties)){
			var cnt = 0;
			extra += "<table><tr><th>Properties from unknown ontologies</th><th>Count</td></tr>";
			for(var key in problems.properties){
				cnt++;
				extra += "<tr><td>" + key + "</td><td>" + problems.properties[key] + "</td></tr>";
			}
			extra += "</table>";
			msg += "<span>" + cnt + " properties used from unknown ontologies</span>";
		}
		if(!isEmpty(problems.structural)){
			msg += "<span>" + problems.structural.length + " structural links to unknown ontologies</span>";
			extra += "<table><tr><th colspan='3'>Structural Links to Unknown Ontologies</th></tr>";
			for(i = 0; i< problems.structural.length; i++){
				var s = problems.structural[i];
				extra += "<tr><td>" + s[0] + "</td><td>" + s[1] + "</td><td>" + s[2] + "</td></tr>"; 
			}
			extra += "</table>";
		}
		if(!isEmpty(problems.subject)){
			var cnt = 0;
			extra += "<table><tr><th>Assertions about entities in other ontologies</th></tr>";
			for(var key in problems.subject){
				cnt++;
				extra += "<tr><td>" + key + " (" + problems.subject[key] + ")</td></tr>";
			}
			extra += "</table>";
			msg += "<span>" + cnt + " remote entities modified</span>";
		}
		dacura.system.showWarningResult(msg, extra, tit, '#dacura-problems');
	}
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

dacura.schema.showIncludeTree = function(flat, tree){
	$('#included_onts').html(getTreeAsTable(tree['<?=$params['id']?>']));
	$('#included_onts').append("<div class='include_summary'>" + flat.length + " included ontologies</div>");
	$('#included_onts').append("<div class='include_list'><span class='ontlabel'>" + flat.join("</span><span class='ontlabel'>") + "</span></div>");
	$('.include-treelist').menu();
}


dacura.schema.showNSDependencies = function(ns){
	var dep = deps[ns];
	$('#singleont-table').html("<h3>Dependencies of <?=$params['id']?> on " + ns + "</h3>");
	var hassubj = false;
	for (var key in dep.subject) {
	  	if (dep.subject.hasOwnProperty(key)) {
			if(!hassubj){
				var k = $('#subject-template').html();
				$('#singleont-table').append(k);
				hassubj = true;								
			}
			$('#singleont-table table.subject-depend tbody').append("<tr><td>" + key + "</td><td>" + dep.subject[key] + "</td></tr>");
	  	}
	}
	var hasprops = false;
	for (var key in dep.properties) {
	  	if (dep.properties.hasOwnProperty(key)) {
			if(!hasprops){
				var k = $('#property-template').html();
				$('#singleont-table').append(k);
				hasprops = true;								
			}
			$('#singleont-table table.property-depend tbody').append("<tr><td>" + key + "</td><td>" + dep.properties[key] + "</td></tr>");
	  	}
	}
	if(dep.structural.length > 0){
		var k = $('#structural-template').html();
		$('#singleont-table').append(k);
		for (var i = 0; i < dep.structural.length; i++) {
			$('#singleont-table table.structural-depend tbody').append("<tr><td>" + dep.structural[i][0] + "</td><td>" + 
					dep.structural[i][1] + "</td><td>" + dep.structural[i][2] + "</td></tr>");		
		}	
	}
	if(dep.object.length > 0){
		var k = $('#object-template').html();
		$('#singleont-table').append(k);
		for (var i = 0; i < dep.object.length; i++) {
			$('#singleont-table table.object-depend tbody').append("<tr><td>" + dep.object[i][0] + "</td><td>" + 
				dep.object[i][1] + "</td><td>" + dep.object[i][2] + "</td></tr>");		
		}	
	}
}

function clearResultMessage(){
	dacura.system.clearResultMessage();	
}

function initDecorations(){
	//view format choices
	$("#tab-holder").tabs( {
        "activate": function(event, ui) {
            $( $.fn.dataTable.tables( true ) ).DataTable().columns.adjust();
        }
    });
	
	$('#depend-button').button().click(function(event){
		dacura.schema.calculateDependencies("<?=$params['id']?>");
    });
	$('.showlist-button').button().click(function(event){
  		$('#ontology-dependencies').show();
  		$('#singleont-dependencies').hide();
	});
}


$(function() {
	initDecorations();
	dacura.schema.entity_type = "ontology";
	dacura.system.init({"mode": "tool"});
	dacura.editor.init({"entity_type": "ontology", 
		"targets": {resultbox: "#lded-msgs", errorbox: "#lded-msgs"}, 
		"args": <?=json_encode($params['args']);?>});

	dacura.editor.getMetaEditHTML = function(meta){
		$('#meta-edit-table').html("");
		$('#meta-edit-table').append("<li><span class='meta-label'>Status</span><span class='meta-value'>" + 
			"<select id='entstatus'><?php echo $service->getEntityStatusOptions();?></select></span></li>");
		$('#entstatus').val(meta.status);	
		$('#meta-edit-table').append("<li><span class='meta-label'>URL</span><span class='meta-value'>" + 
				"<input type='text' id='enturl' value='" + meta.url + "'></span></li>");	
		$('#meta-edit-table').append("<li><span class='meta-label'>Title</span><span class='meta-value'>" + 
				"<input type='text' id='enttitle' value='" + meta.title + "'></span></li>");	
		$('#meta-edit-table').show();
		return "";
	};

	dacura.editor.getInputMeta = function(){
		var meta = {"status": $('#entstatus').val(), "title": $('#enttitle').val(), "url": $('#enturl').val()};
		return meta;
	};
	
	var onw = function (obj){
		dacura.editor.load("<?=$params['id']?>", dacura.schema.fetch, dacura.schema.update, obj);
	    dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + obj.id , obj.id);
	    dacura.system.styleJSONLD();
	    $('#ontology-pane-list').show();
		dacura.schema.calculateDependencies("<?=$params['id']?>", {resultbox: "#deps-msgs", errorbox: "#deps-msgs", busybox: "#ontology-meta"});		
	};
	var args = <?=json_encode($params['args']);?>;
	dacura.schema.fetch("<?=$params['id']?>", args, onw);
	
});
</script>
