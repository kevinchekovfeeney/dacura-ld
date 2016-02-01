<?php $entity = isset($params['entity']) ? $params['entity'] : "Entity";?>
<script src='<?=$service->furl("js", "jquery.json-editor.js")?>'></script>
<link rel="stylesheet" type="text/css" media="screen" href="<?=$service->furl("css", "jquery.json-editor.css")?>" />


<div class='dacura-ld-editor dch'>
	<div id='ld-view-page'>
		<div class='ld-meta' id='ld-view-header'></div>
		<div class='ld-bar' id='ld-view-header'>
			<div id='ld-view-bar'>
				<table class='ld-bar'>
					<tr>
						<td class='ld-bar ld-bar-left' id="ld-view-bar-left">
							<span class='view-options'>
								    <input type="radio" class='noption foption' checked="checked" id="format_json" name="nformat"><label title="Native Dacura JSON Linked Data Format" for="format_json">Native</label>
									<input type="radio" class="noption foption" id="format_jsonld" name="nformat"><label title="JSON-LD" for="format_jsonld">JSON-LD</label>
									<input type="radio" class="noption foption" id="format_turtle" name="nformat"><label title="Turtle" for="format_turtle">Turtle</label>
									<input type="radio" class='noption foption' id="format_quads" name="nformat"><label title="Triples with named graphs: [Subject - Predicate - Object - Graph]" for="format_quads">Quads</label>
								    <input type="radio" class='noption foption' id="format_html" name="nformat"><label title="Dacura's Generated HTML views" for="format_html">HTML</label>
									<input type="radio" class="noption foption" id='format_ntriples' name="nformat"><label title="N-triples format" for="format_ntriples">N-Triples</label>
									<input type="radio" class="noption foption" id="format_rdfxml" name="nformat"><label title="RDF/XML serialisation" for="format_rdfxml">XML</label>
									<input type="radio" class="noption foption" id="format_dot" name="nformat"><label title="Graphviz graphic visualisation" for="format_dot">Graphviz</label>
									<input type="radio" class="noption foption" id="format_n3" name="nformat"><label title="Notation 3" for="format_n3">N3</label>
									<input type="radio" class="noption foption" id="format_gif" name="nformat"><label title="Graphic Interchange Format" for="format_gif">Gif</label>
									<input type="radio" class="noption foption" id="format_png" name="nformat"><label title="Portable Network Graphics" for="format_png">PNG</label>
									<input type="radio" class="noption foption" id="format_svg" name="nformat"><label title="Scalable Vector Graphics" for="format_svg">SVG</label>
							</span>
						</td>
						<td class='ld-bar ld-bar-centre' id="ld-view-bar-centre">
							
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
						<span class='edit-options'>
						    <input type="radio" class='eoption' checked="checked" id="input_json" name="eformat"><label title="Native Dacura JSON Linked Data Format" for="input_json">Native</label>
						    <input type="radio" class='eoption' id="input_jsonld" name="eformat"><label title="JSON Linked Data Format" for="input_jsonld">JSON-LD</label>
						    <input type="radio" class='eoption' id="input_rdfxml" name="eformat"><label title="RDF/XML" for="input_rdfxml">XML</label>
						    <input type="radio" class='eoption' id="input_turtle" name="eformat"><label title="Turtle Terse RDF Language" for="input_turtle">Turtle</label>
						    <input type="radio" class='eoption' id="input_html" name="eformat"><label title="HTML" for="input_html">HTML</label>
						</span>
					</td>
					<td class='ld-bar ld-bar-centre' id='ld-edit-bar-centre'>
						<span id="ld-edit-update-meta">
									
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
		<div class="ld-meta-bar">
			<ul id='meta-edit-table' class='dch'>
				<li><span class='meta-label'>Status</span><span class='meta-value'>
					<select id='entstatus'><?php echo $service->getEntityStatusOptions();?></select>
				</span></li> 
				</ul>			
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

<script>

dacura.editor = {
	viewMode: "view",
	editMode: "edit",
	testMode: false,
	nformat : "html", //for saving last setting of native format
	eformat : "turtle", //... of export format
	mode: "view",
	currentID: 	"<?=isset($params['id'])? $params['id'] : "" ?>",
	currentEntity: {},
	stage : "",
	updateid : 0,
	editorheight: "400",
	editorwidth: "100%",
	entity_type: "candidate",
	targets: dacura.system.targets,
	
	
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

	clearMessages: function(){
		$(dced.targets.resultbox).html("");	
		$(dced.targets.errorbox).html("");	
	},
		
	drawUpdateResult: function(res){
		if(typeof(dacura.ldresult) != "undefined"){
			var cancel = function(){
				$('.dacura-ld-editor').show();
				$(dced.targets.resultbox).html("");
			};
			
			var upd = function(){
				dced.submit(res.action);
			};

			res.format = dced.options.format;
			
			dacura.ldresult.showDecision(res, dced.targets.resultbox, cancel, upd);			
			$('.dacura-ld-editor').hide();
		}
		else {
			if(res.decision == "reject" || res.errcode > 0){
				dacura.system.showErrorResult(res.msg_body, res, res.decision, dced.targets.errorbox);
			}
			else if(typeof res.warnings == "object" && res.warnings.length > 0){
				dacura.system.showWarningResult(res.msg_body, res, res.decision, dced.targets.resultbox);		
			}
			else {
				dacura.system.showSuccessResult(res.msg_body, res, res.decision, dced.targets.resultbox);		
			}
		}
	},
	
	//the next two are options for sending to the API
	
	options: {
		version: 0,
		format: "json",
		flags: {
			ns: true,
			problems: true,
			links: true,
			typed: true
	 	}
 	},
 	
	setViewArgs: function(args){
		if(typeof args.version != "undefined"){
			dced.options.version = args.version;
		}
		if(typeof args.format != "undefined"){
			dced.options.format = args.format;
		}
		if(typeof args.mode != "undefined"){
			dced.mode = args.mode;
		}
		if(typeof args.flags != "undefined"){
			dced.mode.flags = args.flags;
		}
	},
 	
 	setEditorOptions: function(opts){
		if(typeof opts.args != "undefined"){
			dced.setViewArgs(opts.args);
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
		if(typeof opts.targets != "undefined"){
			dced.targets = opts.targets;
		}
	},

	display: function(){
		dced.setFormatOption(dced.options.format);
		$('.dacura-ld-editor').show();		
	},

	getDisplayFlagsAsString: function(){
		var display = [];
		if(dced.options.flags.ns) display[display.length] = "ns";
		if(dced.options.flags.problems) display[display.length] = "problems";
		if(dced.options.flags.links) display[display.length] = "links";
		if(dced.options.flags.typed) display[display.length] = "typed";
		return display.join("_");
	},

	getQueryString: function() {
		return "?version=" + dced.options.version + "&format=" + dced.options.format + "&display=" + dced.getDisplayFlagsAsString(); 
	},

	load: function(i, f, u, prefetch){
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
			if(typeof prefetch == "undefined"){
				dced.fetch(i, opts, dced.loadEntity, dced.targets);
			}
			else {
				dced.loadEntity(prefetch);
			}
		}
	},

	loadSkeleton: function(obj){
		dced.drawBody(obj.display); 
		dced.display();
	},
	
	fetch: function(){
		alert("No fetch function defined " + dced.getQueryString());	
	},		

	update: function(){
		alert("No update function defined " + dced.getQueryString());			
	},

	readInputObject: function(){
		try {
			uobj = {};
			uobj.meta = dced.getInputMeta();
			uobj.contents = dced.getInputContents();
			if(dced.options.format == "json"){
				uobj.contents = JSON.parse(uobj.contents);				
			}
			else {
				//alert(dced.options.format); 
			}
			return uobj;
		}
		catch(e){
			dacura.system.showErrorResult("your input data has json formatting errors.", e.message, "JSON Parsing Error", dced.targets.errorbox);
			return;
		}	
	},


	getMetaEditHTML: function(meta){
		if(dced.options.format == "json"){
			var html = "<textarea id='ldmeta-input'>";
			if(typeof meta == 'undefined'){
				meta = {};
			} 
			$('#meta-edit-table').hide();
			html += JSON.stringify(meta, null, 4) + "</textarea>";
			return html; 				
		}
		else {
			$('#meta-edit-table').html("");
			$('#meta-edit-table').append("<li><span class='meta-label'>Status</span><span class='meta-value'>" + 
				"<select id='entstatus'><?php echo $service->getEntityStatusOptions();?></select></span></li>");
			if(typeof meta != "undefined" && typeof meta.status != "undefined"){
				$('#entstatus').val(meta.status);	
			}
			$('#meta-edit-table').show();
			//$('#entstatus').val(meta.status);	
			return "";
		}
	},	

	getBodyEditHTML: function(obj){
		var html = "<textarea id='ldprops-input'>";
		if(dced.options.format == "json" || dced.options.format == "jsonld"){
			if(typeof obj == 'undefined'){
				bd = {};
			} 
			else {
				bd = obj.ldprops;
			}
			html += JSON.stringify(bd, null, 4); 			
		} 
		else {
			html += obj.display;
		}
		html += "</textarea>";
		return html; 	
	},

	drawMeta: function(ent){
		$('#entstatus').val(ent.status);	
	},
	

	getInputMeta: function(){
		if(dced.options.format == "json"){
			return 	JSON.parse($('#ldmeta-input').val());
		}
		else {
			var meta = {"status": $('#entstatus').val()};
			return meta;
		}		
	},
	
	getInputContents: function(){
		return 	$('#ldprops-input').val();		
	},
 	
	submit: function(type, test){
		dced.clearMessages();
		var uobj = dced.readInputObject();
		if(typeof uobj != "undefined"){
			uobj.options = {"format": dced.options.format };
			if(typeof test != "undefined"){
				uobj.test = true;
			}
			else if(typeof uobj.test != "undefined") {
				delete uobj.test;
			}
			if(typeof dced.currentEntity.delta != "undefined" && $('#set-update-status').val() != dced.currentEntity.delta.status){
				uobj.updatemeta = { "status": $('#set-update-status').val() };
			}
			if(dced.mode == "edit new"){
				var entity_type = $('#set-status').val();
				uobj.type = entity_type;
				dced.update(uobj, dced.drawUpdateResult, dced.targets, test);
			}
			else {
				//if($('#set-status').val() && $('#set-status').val() != dced.currentEntity.status){
				//	uobj.meta.status = $('#set-status').val();
				//}
				dced.update(dced.currentID, uobj, dced.drawUpdateResult, type, dced.targets, test);
			} 
		}
	},
 	
	refresh: function(source){
		dced.clearMessages();
		var opts = jQuery.extend({}, dced.options);
		opts.display = dced.getDisplayFlagsAsString();
		dced.fetch(dced.currentID, opts, dced.drawEntity, dced.targets, source);
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
		dced.drawMeta(ent);
		$('#ld-editor').html("<div class='dacura-json-editor'>" + dced.getMetaEditHTML(dced.currentEntity.meta) + dced.getBodyEditHTML(dced.currentEntity) + "</div>");
		dced.setMode();
		dced.drawBody(ent.display); 
	},

	drawBody: function(contents){
		if(dced.options.format == "json" || dced.options.format == "jsonld"){
			$('#ld-viewer').html("<div class='dacura-json-viewer'>" + JSON.stringify(contents, null, 4) + "</div>");
		}
		else if(dced.options.format == "triples"){
			var html = "<div class='ld-table-viewer'><table class='ld-triples-viewer'><thead><tr><th>Subject</th><th>Predicate</th><th>Object</th></tr></thead><tbody>";
			for (var i in contents) {
				var row = "<tr><td>" + contents[i][0] + "</td>";
				row += "<td>" + contents[i][1] + "</td>";
				row += "<td>" + contents[i][2] + "</td></tr>";
				html += row;
			}
			$('#ld-viewer').html(html + "</tbody></table></div>");	
		}
		else if(dced.options.format == "quads"){
			var html = "<div class='ld-table-viewer'><table class='ld-triples-viewer'><thead><tr><th>Subject</th><th>Predicate</th><th>Object</th><th>Graph</th></tr><thead><tbody>";
			for (var i in contents) {
				var row = "<tr><td>" + contents[i][0] + "</td>";
				row += "<td>" + contents[i][1] + "</td>";
				row += "<td>" + contents[i][2] + "</td>";
				row += "<td>" + contents[i][3] + "</td></tr>";
				html += row;
			}
			$('#ld-viewer').html(html + "</tbody></table></div>");	
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
			JSONEditor.prototype.ADD_IMG = '<?=$service->furl("images", "icons/add.png")?>';
			JSONEditor.prototype.DELETE_IMG = '<?=$service->furl("images", "icons/delete.png")?>';
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
			$(".edit-options").buttonset();
			$( ".view-options" ).buttonset();
			$( ".foption" ).click(function(event){
				dced.options.format = event.target.id.substr(7);
				dced.setFormatOption(event.target.id.substr(7));
				dced.refresh("loading " + event.target.id.substr(7) + " format");
		    });
			$( ".eoption" ).click(function(event){
				dced.options.format = event.target.id.substr(6);
				dced.setFormatOption(event.target.id.substr(6));
				dced.refresh("loading " + event.target.id.substr(6) + " edit format");
		    });
		    $( ".ifoption" ).click(function(event){
				dced.options.stage = event.target.id.substr(7);
				dced.refresh("stage");
			});
			$( ".doption" ).click(function(event){
				dced.options.flags[event.target.id.substr(8)] = $('#'+event.target.id).is(":checked");
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
				dacura.system.goFullBrowser(dced.targets.busybox);
				$('.browsermin').show();
				$('.browsermax').hide();
			});			  
			$('.editor-min').click(function(){
				dacura.system.leaveFullBrowser(dced.targets.busybox);
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
	},


	
	setCreateMode: function(obj){
		$('#ld-view-page').hide();
		$(".view-update-stage").hide();		
		$('#set-update-status').hide();							
		$('#ld-edit-page').show();
		if(typeof obj != "undefined"){
			$('#ld-editor').append("<div class='dacura-json-editor'>" + this.getMetaEditHTML(obj.meta) + this.getBodyEditHTML(obj) + "</div>");
		}
		else {
			$('#ld-editor').append("<div class='dacura-json-editor'>" + this.getMetaEditHTML() + this.getBodyEditHTML() + "</div>");
		}
		//dced.jsoneditor = new JSONEditor($("#ldprops-input"), dced.editorwidth, dced.editorheight);
		//dced.jsoneditor.doTruncation(true);
		//dced.jsoneditor.showFunctionButtons();
		$('.cancel_edit').hide();
		$('.test_edit span').text("Test entity creation");
		$('.save_edit span').text("Submit new entity");	
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
			//var meta = JSON.stringify(dced.currentEntity.meta, null, 4);
			//var content = JSON.stringify(dced.currentEntity.ldprops, null, 4);
			$('#ld-editor').html("<div class='dacura-json-editor'>" + dced.getMetaEditHTML(dced.currentEntity.meta) + dced.getBodyEditHTML(dced.currentEntity) + "</div>");
			$('#ld-edit-page').show();
			$('#ld-view-page').hide();
			//dced.jsoneditor = new JSONEditor($("#ldprops-input"), dced.editorwidth, dced.editorheight);
			//dced.jsoneditor.doTruncation(true);
			//dced.jsoneditor.showFunctionButtons();
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
				//dacura.system.removeServiceBreadcrumb("bcversion");							
			}
			else {
				$("#ld-forward").button("enable");
				$("#ld-end").button("enable");
				$("#ld-update-next").button("enable");
				$("#ld-update-last").button("enable");
				//dacura.system.addServiceBreadcrumb("<?=$service->my_url()?>/" + this.currentID + "?version=" + v, "Version " + v, "bcversion");									
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
