<script src='<?=$service->url("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->url("css", "jquery.json-editor.css")?>" />

<div class="phony-div-for-css">
<style>

.ld-view-header {
position: relative;
}

.browsermax {
	float: right;
	margin-right: -20px;
	margin-top: -50px;
	font-size: 0.7em;
	cursor: pointer;
}

.browsermin {
	margin-top: -50px;
	float: right;
	cursor: pointer;
}

table.ld-bar {
	width: 100%;
	margin-bottom: 0;
	margin-top: 0;
}
td.ld-bar {
	padding-top: 2px;
  	padding-bottom: 2px;
}

td.ld-bar-left {
  	padding-left: 4px;
  	text-align: left;
}
td.ld-bar-centre {
  	text-align: center;
  	padding-left: 4px;
  	padding-right: 4px;
}

td#ld-view-bar-left {
} 

td#ld-action-bar-left {
} 
td#ld-view-bar-centre {
	text-align: left;
}
td.ld-bar-right {
	text-align: right;
	padding-right: 14px;
}

div#ld-view-bar {
  	background-color: #d5f9e9;
  	border: 1px solid #aaa;
	font-size: 0.7em;
	border-radius: 5px 5px 0 0;
	padding: 2px 0;
}
      
div#ld-action-bar {
	font-size: 0.6em;
	background-color: #f8efef;
	color: #222;
    border-bottom: 1px solid #888;
    border-left: 1px solid #aaa;
    border-right: 1px solid #aaa;
}
 
#ld-action-table {
 	border-collapse: collapse;
 	border-bottom: 1px solid white;
 	border-top: 1px solid white;
}
 
#ld-action-table td {
 	padding: 2px 4px; 
 	border: 0;
}

div#ld-version-controls,
div#ld-update-controls {
	display: inline-block;
}


div#ld-edit-bar {
	border-radius: 5px 5px 0 0;
	font-size: 0.75em;
   	background-color: #aadaff;
   	padding: 2px;
}

div#ld-footer-bar{
	font-size: 0.85em;
   	background-color: #aadaff;
   	padding: 2px;
}  


#ld-action-bar-centre {
	text-align: left;
}

#ld-edit-bar-right {
	text-align: right;
}  
  
div.ld-main-body {
 	border: 1px solid #aaa;
 	background-color: #fdfdfd;
 	margin-top: -1px;
}  
  
div#ld-viewer {
  	margin: 2px 8px;
	white-space: pre;
 	unicode-bidi: embed;
 	font-size: 0.85em;
 }

div#ld-editor {
  	border: 1px solid #aaa;
  	background-color: #cceaff;
}

div#ld-editor textarea {
	padding-top: 6px;
	margin: 2px auto 2px auto;
	width: 100%;
	min-height: 300px;
}
 
.ld-table-viewer {
 	unicode-bidi: embed;
 	font-size: 0.85em;
 }
 
 .decision-header {
 	display: inline-block;
 }

  .decision-header .capi-decision {
 	font-size: 1px;
 	width: 50px;
 }
 
 span.result-icon {
 	float: left;
 }
 
 span.result-icon img{
 	height: 24px;
 	margin-top: -2px;
 	margin-left; -2px; 
 	margin-right: 6px;
 }
 
 
</style>
</div>

<div class='dacura-ld-editor dch'>
	<div id='ld-view-page'>
		<div class='ld-bar' id='ld-view-header'>
			<div id='ld-view-bar'>
				<table class='ld-bar'>
					<tr>
						<td class='ld-bar ld-bar-left' id="ld-view-bar-left">
							<span class='view-native-export'>
							    <input type="radio" class='neoption' id="format_native" checked="checked" value='native' name="neformat"><label title="Native Dacura Linked Data Formats" for="format_native">Native</label>
							    <input type="radio" class='neoption' id="format_export" value='export' name="neformat"><label title="Public formats for data export" for="format_export">External</label>
							</span>
							<span class='view-options'>
								<span class='view-options-native'>
								    <input type="radio" class='noption foption' checked="checked" id="format_json" name="nformat"><label title="Native Dacura JSON Linked Data Format" for="format_json">JSON</label>
								    <input type="radio" class='noption foption' id="format_triples" name="nformat"><label title="Simple Triple Export: [Subject - Predicate - Object]" for="format_triples">Triples</label>
									<input type="radio" class='noption foption' id="format_quads" name="nformat"><label title="Triples with named graphs: [Subject - Predicate - Object - Graph]" for="format_quads">Quads</label>
								    <input type="radio" class='noption foption' id="format_html" name="nformat"><label title="Dacura's Generated HTML views" for="format_html">HTML</label>
								</span>
								<span class='view-options-export'>
								    <input type="radio" class='eoption foption' checked="checked" id="format_turtle" name="eformat"><label title="Turtle terse RDF triple language" for="format_turtle">Turtle</label>
									<input type="radio" class="eoption foption" id='format_ntriples' name="eformat"><label title="N-triples format" for="format_ntriples">N-Triples</label>
									<input type="radio" class="eoption foption" id="format_rdfxml" name="eformat"><label title="RDF/XML serialisation" for="format_rdfxml">XML</label>
									<input type="radio" class="eoption foption" id="format_jsonld" name="eformat"><label title="JSON-LD" for="format_jsonld">JSON-LD</label>
									<input type="radio" class="eoption foption" id="format_dot" name="eformat"><label title="Graphviz graphic visualisation" for="format_dot">Graphviz</label>
									<input type="radio" class="eoption foption" id="format_n3" name="eformat"><label title="Notation 3" for="format_n3">N3</label>
									<input type="radio" class="eoption foption" id="format_gif" name="eformat"><label title="Graphic Interchange Format" for="format_gif">Gif</label>
									<input type="radio" class="eoption foption" id="format_png" name="eformat"><label title="Portable Network Graphics" for="format_png">PNG</label>
									<input type="radio" class="eoption foption" id="format_svg" name="eformat"><label title="Scalable Vector Graphics" for="format_svg">SVG</label>
								</span>
							</span>
						</td>
						<td class='ld-bar ld-bar-centre' id="ld-view-bar-centre">
							<span class="native-suboptions">
								<input type="checkbox" class='doption' id="display_typed" name="display"><label title="Include type information for literals" for="display_typed">Types</label>
								<input type="checkbox" class="doption" id="display_ns" checked="checked" name="display"><label title="Use namespace prefixes to shorten URLs" for="display_ns">Namespaces</label>
							    <input type="checkbox" class="doption" id="display_links" name="display"><label title="Display URLs as links" for="display_links">Links</label>
							    <input type="checkbox" class="doption" id="display_problems" checked="checked" name="display"><label title="Highlight problems with your data" for="display_problems">Problems</label>
							</span>
						</td>					
						<td class='ld-bar ld-bar-right' id="ld-view-bar-right">
							<span class='ld-update-actions'>
								<button title="Make this version the live version of the <?=$entity?>" id="action-restore">Restore</button>
								<button title="Modify this update to the <?=$entity?> - beware this is changing the past!" id="action-modify">Modify</button>					
								<input type="checkbox" id="show-version-controls"><label for='show-version-controls' title="Show Version Browser Controls">History</label>
								<button title="Edit this <?=$entity?>" id="action-edit">Edit</button>
							</span>
						</td>
					</tr>	
				</table>
				<span class="browsermax editor-max ui-icon ui-icon-arrow-4-diag"></span>
				<span class="browsermin dch editor-min ui-icon ui-icon-closethick"></span>				
			</div>
			<div class='ld-bar dch' id='ld-action-bar'>
				<table class='ld-bar' id='ld-action-table'>
					<tr>
						<td class='ld-bar ld-bar-left' id='ld-action-bar-left'>
							<div id="ld-update-controls">
								<button id="ld-update-freeze">Freezes this update</button>
								<button id="ld-update-first">Show update that cause the <?=$entity?> to be created.</button>
								<button id="ld-update-previous">Show the previous update to the <?=$entity?></button>
								<button id="ld-update-current">Update</button>
								<button id="ld-update-next">Show next update to the <?=$entity?></button>
								<button id="ld-update-last">Show most recent update to the <?=$entity?></button>
							</div>
							<div id="ld-version-controls">
								<button id="ld-freeze">Freezes the version at this version</button>
								<button id="ld-beginning">Show original version of <?=$entity?></button>
								<button id="ld-rewind">Show previous version of <?=$entity?></button>
								<button id="ld-current">Version</button>
								<button id="ld-forward">Show next version of <?=$entity?></button>
								<button id="ld-end">Show latest version of <?=$entity?></button>
							</div>	
						</td>
						<td class='ld-bar ld-bar-centre' id='ld-action-bar-centre'>

						</td>
						<td class='ld-bar ld-bar-right' id='ld-action-bar-right'>
							<span class="view-update-stage">
								<span id="view-update-label">Update View</span>
								<input type="radio" class="uview" id="stage_before" name="jformat"><label for="stage_before">State of the <?=$entity?> before update</label>
								<input type="radio" class="uview" id="stage_forward" name="jformat"><label for="stage_forward">Change that caused the <?=$entity?> to be updated</label>
								<input type="radio" class="uview" id="stage_full" name="jformat"><label for="stage_full">Display what has changed in the <?=$entity?>.</label>
								<input type="radio" class="uview" id="stage_backward" name="jformat"><label for="stage_backward">The change that would undo the change to the <?=$entity?></label>
								<input type="radio" class="uview" id="stage_after" name="jformat"><label for="stage_after">State of the <?=$entity?> after update</label>					
							</span>					
						</td>
					</tr>
				</table>
			</div>
		</div>
		<div class="ld-main-body">
			<div id="ld-viewer"></div>
		</div>
	</div>
	
	<div id="ld-edit-page">
		<div class='ld-bar' id='ld-edit-bar'>
			<table class='ld-bar' id='ld-edit-table'>
				<tr>
					<td class='ld-bar ld-bar-left' id='ld-edit-bar-left'>
						<span id="ld-edit-meta">
							<select id='set-status'><?=$params['status_options']?></select>						
						</span>
					</td>
					<td class='ld-bar ld-bar-centre' id='ld-edit-bar-centre'>
						<span id="ld-edit-update-meta">
							<select id='set-update-status'><?=$params['update_status_options']?></select>
						</span>
					</td>
					<td class='ld-bar ld-bar-right' id='ld-edit-bar-right'>
	    	    		<button class='edit-action cancel_edit'>Finish Editing</button>
			    		<button class='edit-action test_edit'>Test Changes</button>
			    		<button class='edit-action save_edit'>Submit Changes</button>
					</td>
				</tr>
			</table>
			<span class="browsermax editor-max ui-icon ui-icon-arrow-4-diag"></span>				
			<span class="browsermin editor-min dch ui-icon ui-icon-closethick"></span>				
		</div>	
		<div class="ld-main-body">
			<div id="ld-editor"></div>
		</div>
	
		<div class='ld-bar' id="ld-footer-bar">
			<table class='ld-bar' id='ld-footer-table'>
				<tr>
					<td class='ld-bar ld-bar-left' id='ld-footer-bar-left'>
						<span id="ld-edit-status">
						</span>
					</td>
					<td class='ld-bar ld-bar-centre' id='ld-footer-bar-centre'>
						<span id="ld-edit-update-status">
						</span>
					</td>
					<td class='ld-bar ld-bar-right' id='ld-footer-bar-right'>
	    	    		<button class='edit-action cancel_edit'>Finish Editing</button>
			    		<button class='edit-action test_edit'>Test Changes</button>
			    		<button class='edit-action save_edit'>Submit Changes</button>
					</td>
				</tr>
			</table>
		</div>

	</div>
</div>
<div id="decisiontemplates" class='dacura-templates'>
	<div id='header-template'>
		<div class="decision-header">
			<span class="capi-decision"></span>
			<span class="capi-action"></span>
			<span class="capi-title"></span>
		</div>
	</div>	
</div>


<script>

dacura.editor = {
	viewMode: "view",
	editMode: "edit",	
	nformat : "html", //for saving last setting of native format
	eformat : "turtle", //... of export format
	mode: "<?= isset($params['mode'])? $params['mode'] : "view" ?>",
	currentID: 	"<?=isset($params['id'])? $params['id'] : "" ?>",
	currentEntity: {},
	stage : "",
	updateid : 0,
	editorheight: "400",
	editorwidth: "100%",
	entity_type: "candidate",

	decisionTexts: {
		"create": {
			"reject": "Your submission was not accepted by the System",		
			"candidate": "Your submission has been accepted as a candidate entity. It has not been published to the evidence base.",			
			"report": "Your submission has been accepted as a report and published to the evidence base",
			"interpretation": "Your submission has been accepted as an interpetation and added to the analysis base"
		},
		"update": {
			"reject": "Your update was rejected by the System",		
			"accept": "Your update has been accepted by the system and the entity has been updated",		
			"pending": "Your update is awaiting approval",
			"interpretation": "Your submission has been accepted as an interpretation by the system and added to the analysis base"				
		}
	},
		
	drawUpdateResult: function(res){
		function getHeader(res, msgs, imgs){
			var t = $($('#header-template').html().trim());
			$(t).addClass("decision-" + res.decision);
			//$(t).find('.capi-decision').html(imgs[res.decision]);
			$(t).find('.capi-action').html(res.action).attr("title", msgs[res.decision]);
			if(typeof res.msg_title == "undefined"){
				res.msg_title = dacura.system.msgs.fail;
			}
			$(t).find('.capi-title').html(res.msg_title);
			return $('<div>').append($(t).clone()).html(); 
		}
		if(dced.mode == "edit new"){
			msgs = dced.decisionTexts.create;
		}
		else {
			msgs = dced.decisionTexts.update;
		}
		var tit = getHeader(res, msgs);
		if(res.decision == "reject" || res.errcode > 0){
			dacura.system.showErrorResult(res.msg_body, res, tit);
		}
		else if(typeof res.warnings == "object" && res.warnings.length > 0){
			dacura.system.showWarningResult(res.msg_body, res, tit);		
		}
		else {
			dacura.system.showSuccessResult(res.msg_body, res, tit);		
		}
	},
	
	//the next two are options for sending to the API
	
	options: {
		version: <?=isset($params['version'])? $params['version'] : 0; ?>,
		format: "<?=isset($params['format'])? $params['format'] : "json"; ?>",
 	},
 	flags: {
		ns: <?=isset($params['ns']) ? $params['ns'] : "true";?>,
		problems: <?=isset($params['problems'])? $params['problems'] : "true";?>,
		links: <?=isset($params['links'])? $params['links'] : "false";?>,
		typed: <?=isset($params['typed'])? $params['typed'] : "true"?>
 	},

 	setEditorOptions: function(opts){
		if(typeof opts.mode != "undefined"){
			dced.mode = opts.mode;
		}
		if(typeof opts.editorheight != "undefined"){
			dced.editorheight = opts.editorheight ;
		}
		if(typeof opts.editorwidth != "undefined"){
			dced.editorwidth = opts.editorwidth;
		}
		if(typeof opts.entity_type != "undefined"){
			dced.entity_type = opts.entity_type;
		}
	},

	display: function(){
		dced.setMode();
		dced.setFormatOption(dced.options.format);
		$("#display_ns").prop('checked', dced.flags.ns).button("refresh");					
		$("#display_problems").prop('checked', dced.flags.problems).button("refresh");					
		$("#display_links").prop('checked', dced.flags.links).button("refresh");					
		$("#display_typed").prop('checked', dced.flags.typed).button("refresh");	
		$('.dacura-ld-editor').show();		
	},

	getDisplayFlagsAsString: function(){
		var display = [];
		if(dced.flags.ns) display[display.length] = "ns";
		if(dced.flags.problems) display[display.length] = "problems";
		if(dced.flags.links) display[display.length] = "links";
		if(dced.flags.typed) display[display.length] = "typed";
		return display.join("_");
	},

	getQueryString: function() {
		return "?version=" + dced.options.version + "&format=" + dced.options.format + "&display=" + dced.getDisplayFlagsAsString(); 
	},

	load: function(i, f, u){
		if(i == false && f == false){
			dced.mode = "edit new";
			dced.setCreateMode();
			dced.update = u;
			$('.dacura-ld-editor').show();		
		}
		else {
			dced.fetch = f;
			dced.update = u;
			dced.currentID = i;
			var opts = jQuery.extend({}, dced.options);
			opts.display = dced.getDisplayFlagsAsString();
			dced.fetch(i, opts, dced.loadEntity);
		}
	},
	
	fetch: function(){
		alert("No fetch function defined " + dced.getQueryString());	
	},		

	update: function(){
		alert("No update function defined " + dced.getQueryString());			
	},
 	
	submit: function(type, test){
		dacura.system.clearMessages();
		var uobj = $('#ldprops-input').val();
		if(uobj.length < 2){
			return dacura.system.showErrorResult("Textbox is empty! Please input some data before updating!", null, "No data entered");		
		}
		try {
			uobj = JSON.parse(uobj);
			uobj.options = jQuery.extend({}, dced.options);
			uobj.options.display = dced.getDisplayFlagsAsString();			
			if(typeof test != "undefined"){
				uobj.test = true;
			}
			if(dced.entity_type == "ontology"){

			}
			else if($('#set-status').val() != dced.currentEntity.status){
				if(typeof uobj.meta == "undefined"){
					uobj.meta = { "status": $('#set-status').val()};
				}
				else {
					dacura.system.setLDSingleValue(uobj.meta, "status", $('#set-status').val());
				}
			}
			if(typeof dced.currentEntity.delta != "undefined" && $('#set-update-status').val() != dced.currentEntity.delta.status){
				uobj.updatemeta = { "status": $('#set-update-status').val() };
			}
			dced.update(dced.currentID, uobj, dced.drawUpdateResult, type, test); 
		}
		catch(e){
			dacura.system.showErrorResult("JSON Parsing Error - your data has formatting errors.", e.message);
			return;
		}		
	},
 	
	refresh: function(source){
		dacura.system.clearMessages();
		var opts = jQuery.extend({}, dced.options);
		opts.display = dced.getDisplayFlagsAsString();
		dced.fetch(dced.currentID, opts, dced.drawEntity, source);
	},

	//called first time the entity is loaded - hides display until everything is ready
	loadEntity: function(ent){
		dced.drawEntity(ent);
		dced.display();
	},
	
	drawEntity: function(ent){
		dced.currentEntity = ent;
		dced.options.version = ent.version;
		dced.setVersion(ent.version, ent.latest_version);
		$('#set-status').val(ent.status);
		dced.setMode(dced.viewMode);
		dced.drawBody(ent.display); 
	},

	drawBody: function(contents){
		if(dced.options.format == "json"){
			$('#ld-viewer').html("<div class='dacura-json-viewer'>" + JSON.stringify(contents, null, 4) + "</div>");
		}
		else if(dced.options.format == "triples"){
			var html = "<div class='ld-table-viewer'><table class='ld-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr>";
			for (var i in contents) {
				var row = "<tr><td>" + contents[i][0] + "</td>";
				row += "<td>" + contents[i][1] + "</td>";
				row += "<td>" + contents[i][2] + contents[i][3] + "</td></tr>";
				html += row;
			}
			$('#ld-viewer').html(html + "</table></div>");	
		}
		else if(dced.options.format == "quads"){
			var html = "<div class='ld-table-viewer'><table class='ld-triples-viewer'><tr><th>Subject</th><th>Predicate</th><th>Object</th><th>Graph</th></tr>";
			for (var i in contents) {
				var row = "<tr><td>" + contents[i][0] + "</td>";
				row += "<td>" + contents[i][1] + "</td>";
				row += "<td>" + contents[i][2] + "</td>";
				row += "<td>" + contents[i][3] + "</td></tr>";
				html += row;
			}
			$('#ld-viewer').html(html + "</table></div>");	
		}
		else if(dced.options.format == "html"){
				var html = "<div class='dacura-html-viewer'>";
				html += contents;
				$('#ld-viewer').html(html + "</table></div>");	
				//dacura.candidate.collapseAllEmbedded();
				$('.pidembedded').click(function(event){
					$('#'+ event.target.id + "_objrow").toggle();
				});
		}
		else {
			if(dced.options.format == "svg"){
				var html = "<object id='svg' type='image/svg+xml'>" + contents + "</object>";
			}
			else {
				var html = "<div class='dacura-export-viewer'>" + contents + "</div>";
			}
			$('#ld-viewer').html(html);	
					
		}
		$('#ld-viewer').show();
	},
	
	init: function(opts){
		if(typeof opts != "undefined"){
			dced.setEditorOptions(opts);
		}
		
		function initEditor(){
			JSONEditor.prototype.ADD_IMG = '<?=$service->url("image", "add.png")?>';
			JSONEditor.prototype.DELETE_IMG = '<?=$service->url("image", "delete.png")?>';
		}
		
		function initVersionNavigation(options){
			$( "#ld-version-controls").buttonset();
			$( "#ld-freeze" ).button({
				text: false,
		      	icons: {
		        	primary: "ui-icon-pin-s"
		      	}
	      	}).click(function(){
				if(dced.mode == "view_history_frozen"){
					dced.setMode("view_history");
				}
				else {
					dced.setMode("view_history_frozen");
				}
			});			    						
			$( "#ld-beginning" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-start"
		      	}
		    }).click(function(){
		    	dced.options.version = 1;
		    	dced.refresh("loading version 1");
		    });
			$( "#ld-rewind" ).button({
		      	text: false,
			    icons: {
			        primary: "ui-icon-seek-prev"
			    }
		    }).click(function(){
		    	dced.options.version--;
		    	dced.refresh("loading version " + dced.options.version);
		    });
		    $("#ld-current").button().click(function(){
				dced.setMode("view_history");
			});			    
		    $("#ld-forward").button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-next"
		    	}
		    }).click(function(){
		    	dced.options.version++;
		    	dced.refresh("loading version " + dced.options.version);
		    });
		    $( "#ld-end" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-end"
		      	}
		    }).click(function(){
		    	dced.options.version = 0;
		    	dced.refresh("loading latest version");
		    });
		}
		
		function initUpdateNavigation(){
			$( "#ld-update-controls").buttonset();
			$( "#ld-update-first" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-start"
		      	}
		    }).click(function(){
		    	dced.options.version = 1;
		    	dced.refresh("version");
		    });
			$( "#ld-update-freeze" ).button({
				text: false,
		      	icons: {
		        	primary: "ui-icon-pin-s"
		      	}
	      	}).click(function(){
				if(dced.mode == "view_update_frozen"){
					dced.setMode("view_update");
				}
				else {
					dced.setMode("view_update_frozen");
				}
			});			    
			
			$( "#ld-update-current" ).button().click(function(){
				dced.setMode("view_update");
			});			    
			$( "#ld-update-previous" ).button({
		      	text: false,
			    icons: {
			        primary: "ui-icon-seek-prev"
			    }
		    }).click(function(){
		    	dced.options.version--;
		    	dced.refresh("version");
		    });
		    $("#ld-update-next").button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-next"
		    	}
		    }).click(function(){
		    	dced.options.version++;
		    	dced.refresh("version");
		    });
		    $( "#ld-update-last" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-end"
		      	}
		    }).click(function(){
		    	dced.options.version = 0;
		    	dced.refresh("version");
		    });
		}
		
		function initStageButtons(){
			$( ".view-update-stage" ).buttonset();
			
			$( "#stage_before" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-start"
		      	}
		    });
			$( "#stage_backward" ).button({
		      	text: false,
			    icons: {
			        primary: "ui-icon-seek-prev"
			    }
		    });
		    $("#stage_forward").button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-next"
		    	}
		    });
		    $( "#stage_after" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-seek-end"
		      	}
		    });	
		    $( "#stage_full" ).button({
		      	text: false,
		      	icons: {
		        	primary: "ui-icon-shuffle"
		      	}
		    });	
		    $(".uview").button().click(function(event){
				dced.showUpdateStage(event.target.id.substr(6));
			});	
		}

		function initFormatButtons(){ 			
			//view format choices
			$( ".view-native-export" ).buttonset().click(function(event){
				if($('#format_native').is(":checked")){
					dced.setFormatSubOptions("native");
				}
				else {
					dced.setFormatSubOptions("export");				
				}
			});
			$( ".view-options-native" ).buttonset();
			$( ".view-options-export" ).buttonset();
			$( ".native-suboptions" ).buttonset();
			$( ".foption" ).click(function(event){
				dced.options.format = event.target.id.substr(7);
				dced.setFormatOption(event.target.id.substr(7));
				dced.refresh("loading " + event.target.id.substr(7) + " format");
		    });
			$( ".ifoption" ).click(function(event){
				dced.options.stage = event.target.id.substr(7);
				dced.refresh("stage");
			});
			$( ".doption" ).click(function(event){
				dced.flags[event.target.id.substr(8)] = $('#'+event.target.id).is(":checked");
				dced.refresh("highlight");
			});
		}

		function initActionButtons(){
		    $( ".test_edit" ).button({
		      	icons: {
		        	primary: "ui-icon-help"
		      	}
		  	}).click(function(){
				dced.submit(dced.editMode, true);
			});
			$( ".save_edit" ).button({
		      	icons: {
		        	primary: "ui-icon-check"
		      	}
		  	}).click(function(){
				dced.submit(dced.editMode);
			});
		  	$('.cancel_edit').button({
		      	icons: {
		        	primary: "ui-icon-closethick"
		      	}
		  	}).click(function(){
				dced.setMode(dced.viewMode);
			});		
			$('#show-version-controls').button({
		      	icons: {
		        	primary: "ui-icon-clipboard"
		      	}
		  	}).click(function(){
			  	if($('#show-version-controls').is(":checked")){
					$('#ld-action-bar').show();
				}
			  	else {
					$('#ld-action-bar').hide();			  	
				}
			});
			$('#action-edit').button({
		      	icons: {
		        	primary: "ui-icon-pencil"
		      	}
		  	}).click(function(){
				dced.setMode(dced.editMode);
			});
			$('#action-modify').button({
		      	icons: {
		        	primary: "ui-icon-pencil"
		      	}
		  	}).click(function(){
				dced.setMode("edit_update");
			});
			$('#action-restore').button({
		      	icons: {
		        	primary: "ui-icon-refresh"
		      	}
		  	}).click(function(){
				dced.submit("restore");
			});	
			$('.editor-max').click(function(){
				dacura.system.goFullBrowser();
				$('.browsermin').show();
				$('.browsermax').hide();
			});			  
			$('.editor-min').click(function(){
				dacura.system.leaveFullBrowser();
				$('.browsermax').show();
				$('.browsermin').hide();
			});			  
		}	
		initVersionNavigation();
		initUpdateNavigation();
		initFormatButtons();
		initActionButtons();
		initStageButtons();
		initEditor();
		//$('.dacura-ld-editor').tooltip();
	},//init

	setFormatOption: function(opt){
		$("#format_" + opt).prop('checked', true).button("refresh");					
		if(opt == "json" || opt == "triples" || opt == "quads" || opt == "html"){
			dced.setFormatSubOptions("native");
			$("#format_native").prop('checked', true).button("refresh");					
			dced.nformat = opt;
			if(opt != "html"){
				$('.native-suboptions').show();
			}
			else {
				$('.native-suboptions').hide();						
			}
		}
		else {
			dced.setFormatSubOptions("export");
			$("#format_export").prop('checked', true).button("refresh");					
			dced.eformat = opt;
			$('.native-suboptions').hide();			
		}
	},
	
	setFormatSubOptions: function(setting){
		if(setting == "export") {
		 	$(".view-options-native").hide();
		 	$(".view-options-export").show();
		 	$(".native-suboptions").hide();
		}
		else {
		 	$(".view-options-native").show();
		 	$(".view-options-export").hide();
		 	if(dced.nformat != "html"){
			 	$(".native-suboptions").show();
		 	}
		}
	},

	setCreateMode: function(){
		$('#ld-view-page').hide();
		$(".view-update-stage").hide();		
		$('#set-update-status').hide();							
		$('#ld-edit-page').show();
		$('#ld-editor').append("<div class='dacura-json-editor'>" + 
				"<textarea id='ldprops-input'>{}</textarea>" + 
				"<div class='dch' id='ld-experimental' contentEditable=true>{}</div>"); 
		dced.jsoneditor = new JSONEditor($("#ldprops-input"), dced.editorwidth, dced.editorheight);
		dced.jsoneditor.doTruncation(true);
		dced.jsoneditor.showFunctionButtons();
		$('.cancel_edit').hide();
		$('.test_edit span').text("Test new candidate");
		$('.save_edit span').text("Submit new candidate");	
	},

	setMode: function(mode){
		if(typeof mode != "undefined"){
			dced.mode = mode;					
		}
		if(dced.mode.substring(0,4) == "view"){
			$('.dacura-json-editor').remove();
			dced.viewMode = dced.mode;
			$('#ld-view-page').show();
			$('#ld-edit-page').hide();
			if(dced.mode == "view_update"){
				$(".view-update-stage").show();
			}
			else {
				$(".view-update-stage").hide();				
			}
			//$('#ld-version-controls button').button( "option", "disabled", false);
			//$('#ld-update-controls button').button( "option", "disabled", false);									
			if(dced.mode == "view"){
				$('#action-modify').hide();
				$('#action-restore').hide();
				$('#action-edit').show();
			}
			else if(dced.mode == "view_update"){
				$('#action-modify').show();
				$('#action-restore').hide();
				$('#action-edit').hide();
				$('#ld-current').show();
			}
			else if(dced.mode == "view_history"){
				$('#action-modify').hide();
				$('#action-restore').show();
				$('#action-edit').hide();										
			}
			else if(dced.mode == "view_history_frozen"){
				$('#action-modify').show();
				$('#action-restore').hide();
				$('#action-edit').hide();
				$('#ld-version-controls button').button( "option", "disabled", true);
				$('#ld-freeze').button( "option", "disabled", false);				
			}
			else if(dced.mode == "view_update_frozen"){
				$('#action-modify').hide();
				$('#action-restore').show();
				$('#action-edit').show();													
				$('#ld-update-controls button').button( "option", "disabled", true);									
				$('#ld-update-freeze').button( "option", "disabled", false);				
			}
		}
		else if(dced.mode.substring(0,4) == "edit"){
			dced.editMode = dced.mode;
			var content = JSON.stringify(dced.currentEntity.ldprops, null, 4);
			$('#ld-view-page').hide();
			$('#ld-edit-page').show();
			$('#ld-editor').append("<div class='dacura-json-editor'>" + 
					"<textarea id='ldprops-input'>" +  content + "</textarea>" + 
					"<div class='dch' id='ld-experimental' contentEditable=true>" + content + "</div>"); 
			//dced.jsoneditor = new JSONEditor($("#ldprops-input"), dced.editorwidth, dced.editorheight);
			//dced.jsoneditor.doTruncation(true);
			//dced.jsoneditor.showFunctionButtons();
			if(mode == "edit"){
				$('#set-update-status').hide();				
			}
			else if(mode == "edit_update"){
				$('#set-update-status').show();							
			}
		}	
	},
	
	setVersion: function(v, lv){
		dced.options.version = v;
		if(typeof lv != "undefined" && !(v == 1 && v == lv)){
			if (v == 1){
				$("#ld-rewind" ).button("disable");
				$("#ld-beginning" ).button("disable");
				$("#ld-update-previous" ).button("disable");
				$("#ld-update-first" ).button("disable");
			}
			else {
				$("#ld-update-previous").button("enable");
				$("#ld-update-first" ).button("enable");
				$("#ld-rewind").button("enable");
				$("#ld-beginning" ).button("enable");
			}
			if(v == lv){
				$("#ld-forward" ).button("disable");
				$("#ld-end" ).button("disable");
				$("#ld-update-next" ).button("disable");
				$("#ld-update-last" ).button("disable");
				dacura.system.removeServiceBreadcrumb("bcversion");							
			}
			else {
				$("#ld-forward").button("enable");
				$("#ld-end").button("enable");
				$("#ld-update-next").button("enable");
				$("#ld-update-last").button("enable");
				dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + this.currentID + "?version=" + v, "Version " + v, "bcversion");									
			}
		}
		else {
			$('#ld-version-controls').hide();
			$('#ld-update-controls').hide();
			$('#show-version-controls').button( "option", "disabled", true);							
		}
		$('#ld-current span').text("Version " + dced.options.version);
		$('#ld-update-current span').text("Update " + dced.updateid);
				
	},

	showUpdateStage: function(stage){
		if(stage == "backward"){
			dced.drawBody(dced.currentEntity.delta.backward);
		}
		else if(stage == "forward"){
			dced.drawBody(dced.currentEntity.delta.forward);		
		}
		else if(stage == "before"){
			dced.drawBody(dced.currentEntity.original);		
		}
		else if(stage == "after"){
			dced.drawBody(dced.currentEntity.display);			
		}
		else {
			dced.drawBody(dced.currentEntity.delta.display);				
		}
		//dacura.candidate.display(screen);
	}
};//editor object
var dced = dacura.editor;//shorthand to avoid typing whole object name or worrying about binding on callbacks
</script>