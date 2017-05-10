/**
 * @file Javascript object for viewing linked data objects returned in responses by the Dacura API
 * @author Chekov
 * @license GPL V2
 */

/**
 * @constructor 
 * @param ldo {Ojbect} ldo json object returned by api
 * @param pconf {DacuraPageConfig} page config object
 * @param vconf {Object} viewer config object
 */
function LDOViewer(ldo, pconf, vconf){
	this.ldo = ldo;
	this.pconf = pconf;
	this.emode = "view";
	this.target = "";
	this.prefix = "";
	if(typeof vconf == "object"){
		this.init(vconf);
	}
}

/**
 * Retrieves the ldtype (candidate, ontology, graph) of the viewer
 * @returns {String} ldtype
 */
LDOViewer.prototype.ldtype = function(){
	if(typeof this.ldo == "object"){
		return this.ldo.ldtype();
	}
	return this.ldtype;
};

/**
 * Initialises the viewer with the config passed
 * @param vconf {Object} viewer config object 
 */
LDOViewer.prototype.init = function(vconf){
	if(typeof vconf.emode == "string"){
		this.emode = vconf.emode;
	}
	if(typeof vconf.target == "string"){
		this.setTarget(vconf.target, vconf.options_target);
	}
	if(typeof vconf.view_formats == "object"){
		this.view_formats = vconf.view_formats;
	}
	else {
		this.view_formats = false;
	}
	if(typeof vconf.edit_formats == "object"){
		this.edit_formats = vconf.edit_formats;
	}
	else {
		this.edit_formats = false;
	}
	if(typeof vconf.view_actions == "object"){
		this.view_actions = vconf.view_actions;
	}
	else {
		this.view_actions = false;
	}
	if(typeof vconf.view_options == "object"){
		this.view_options = vconf.view_options;
	}
	else {
		this.view_options = false;
	}
	if(typeof vconf.view_graph_options == "object"){
		this.view_graph_options = vconf.view_graph_options;
	}
	else {
		this.view_graph_options = false;
	}
	if(typeof vconf.editmode_options == "object"){
		this.editmode_options = vconf.editmode_options;
	}
	else {
		this.editmode_options = false;
	}
	if(typeof vconf.result_options == "object"){
		this.result_options = vconf.result_options;
	}
	else {
		this.result_options = false;
	}
	if(typeof vconf.show_cancel != "undefined"){
		this.show_cancel = vconf.show_cancel;
	}
	else {
		this.show_cancel = true;
	}
	if(typeof vconf.show_options != "undefined"){
		this.show_options = vconf.show_options ;
	}
	else {
		this.show_options = true;
	}
	this.show_fileupload = (typeof vconf.fileupload != "undefined") ? vconf.fileupload : false;
	this.show_buttons = (typeof vconf.show_buttons != "undefined") ? vconf.show_buttons : false;
	this.test_update_options = (typeof vconf.test_update_options != "undefined") ? vconf.test_update_options : {};
	this.update_options = (typeof vconf.update_options != "undefined") ? vconf.update_options : {};
	this.allow_empty_contents = (typeof vconf.allow_empty_contents != "undefined") ? vconf.allow_empty_contents : false;
	this.ldimport_header = (typeof vconf.ldimport_header != "undefined") ? vconf.ldimport_header : false;
	this.tooltip = (typeof vconf.tooltip != "undefined") ? vconf.tooltip :  { content: function () {	return $(this).prop('title');}};
	this.entities = (typeof vconf.entities != "undefined") ? vconf.entities : {};
	this.entity_classes = (typeof vconf.entity_classes != "undefined") ? vconf.entity_classes : {};
	this.frame_renderers = (typeof vconf.frame_renderers != "undefined") ? vconf.frame_renderers : {};
}

/**
 * Called to draw the viewer into the screen
 * @param target {String} the jquery selector in which the viewer will be drawn
 * @param mode {String} view|edit|create
 * @param callback {function} the callback that will be called upon update
 */
LDOViewer.prototype.show = function(target, mode, callback, tooltip_config){
	if(typeof target == "string"){
		this.setTarget(target);
	}	
	this.emode = (typeof mode == "string") ? mode : this.emode;	
	if(this.frm) this.frm.destroy();
	$(this.target).html("");
	if(this.show_options){
		$(this.options_target).append(this.showOptionsBar());
		this.initOptionsBar(callback);
	}
	var body = "<div class='ldo-viewer-contents' id='" + this.prefix + "-ldo-viewer-contents'>";
	if(this.emode == 'import'){
		body += this.getImportBodyHTML() + "</div>";
		$(this.target).append(body);
		this.tooltipLDImport();
	}
	else {
		if(this.ldo.format == "html" && this.ldo.ldtype() == "candidate"){
			$(this.target).append(body + "</div>");
			this.showFrame(this.ldo, this.prefix + "-ldo-viewer-contents", this.emode, tooltip_config);
		}
		else {
			body += this.ldo.getContentsHTML(this.emode) + "</div>";
			$(this.target).append(body);	
		}
	}
	if(this.show_buttons && this.emode != "view"){
		$(this.target).append(this.getUpdateButtonsHTML(this.emode));
		this.initUpdateButtons(this.pconf, callback);
	}
};

/**
 * Called to add jquery ui tooltips to the viewer
 */
LDOViewer.prototype.tooltipLDImport = function(){
	$('.ld-import-holder .dacura-property-help').each(function(){
		$(this).html(dacura.system.getIcon('help-icon', {cls: 'helpicon', title: $(this).html()}));
	});
	$('.ld-import-holder .helpicon').tooltip(this.tooltip);
};

/**
 * Generates the html for showing the options bar on top of the viewer
 * @returns {String} html
 */
LDOViewer.prototype.showOptionsBar = function(){
	if(this.emode == "view"){
		var html = this.showViewOptionsBar();
	}
	else if(this.emode == "edit"){
		var html = this.showEditOptionsBar();	
	}
	else {
		var html = this.showImportOptionsBar();	
	}
	return html;
};

/**
 * Generates the html for showing the options bar in view mode on top of the viewer
 * @returns {String} html
 */
LDOViewer.prototype.showViewOptionsBar = function(){
	var html = "<div class='ld-view-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	if(this.view_formats){
		html += "<select class='ld-view-formats ld-control dacura-select'>";
		for(var i in this.view_formats){
			var sel = "";
			if(this.ldo.format == i){
				sel = "selected "
			}
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-format-" + i + "' " + sel + ">" + this.view_formats[i] + "</option>";							
		}
		html += "</select>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-centre'>";
	if(this.ldo.format == "html"){
		html += "<span class='ld-view-options'>";
		html += "<input type='checkbox' class='ld-control ld-bar-option' id='" + this.prefix + "-option-annotations' ";
		html += "checked";
		html += " /><label for='" + this.prefix + "-option-annotations'>Annotations</label>";
		html += "</span>";
		this.ldo.options["annotations"] == true;
	}
	else {
		if(this.view_options){
			html += "<span class='ld-view-options'>";
			for(var i in this.view_options){
				html += "<input type='checkbox' class='ld-control ld-bar-option' id='" + this.prefix + "-option-" + i + "' ";
				if(this.ldo.options[i] == 1){
					html += "checked";
				}
				html += " /><label for='" + this.prefix + "-option-" + i + "'>" + this.view_options[i] + "</label>";
			}
			html += "</span>";
		}
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-right'>";
	if(this.view_actions){
		html += "<span class='ld-update-actions'>";
		if(this.ldo.meta.version != this.ldo.meta.latest_version){
			if(typeof this.view_actions['restore'] == "string"){
				html += "<button class='ldo-actions ld-control' title='" + this.view_actions["restore"] + "' id='"+ this.prefix + "-action-restore'>" + this.view_actions["restore"] + "</button>";								
			}
			if(this.ldo.format != "html" && typeof this.view_actions['export'] == "string"){
				html += "<button class='ldo-actions ld-control' title='" + this.view_actions["export"] + "' id='"+ this.prefix + "-action-export'>" + this.view_actions["export"] + "</button>";								
			}
		}
		else {
			for(var i in this.view_actions){
				if(i == "restore") continue;
				if(this.ldo.format == "html" && (i == "export" || i == "import")) continue;
				if(i == "edit" && this.edit_formats && typeof(this.edit_formats[this.ldo.format]) == "undefined") continue;
				if((this.ldo.meta.status == "accept" && i != "reject" && i != "accept") || 
						(this.ldo.meta.status == 'pending' && i != 'pending') || 
						(this.ldo.meta.status == "reject" && i != "reject" && i != "accept" && i!= "pending")){
					html += "<button class='ldo-actions ld-control' title='" + this.view_actions[i] + "' id='"+ this.prefix + "-action-" + i + "'>" + this.view_actions[i] + "</button>";
				}
			}
		}
		html += "</span>";
	}
	html += "</td></tr></table></div>";
	return html;
};

/**
 * Generates the html for showing the options bar on top of the importer
 * @returns {String} html
 */
LDOViewer.prototype.showImportOptionsBar = function(){
	var html = "<div class='ld-import-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	html += "<span id='" + this.prefix + "-ld-uploadtype'>";
	html += '<input type="radio" class="ldimportoption"  value="textbox" id="'+ this.prefix + '-importtext" name="' + this.prefix + '-importformat"><label for="' + this.prefix + '-importtext" >Paste into Textbox</label>';
	html += '<input type="radio" class="ldimportoption" checked value="url" id="' + this.prefix + '-importurl" name="' + this.prefix + '-importformat"><label for="'+ this.prefix + '-importurl">Import from URL</label>';
	if(this.show_fileupload){
		html += '<input type="radio" class="ldimportoption" value="file" id="'+ this.prefix + '-importupload" name="' + this.prefix + '-importformat"><label for="' + this.prefix + '-importupload">Upload File</label>';
	}
	html += "</span>";
	html += "</td>";
	html += "<td class='ld-bar ld-bar-centre'>";
	if(this.ldimport_header){
		html += "<span class='ldimport-header'>" + this.ldimport_header + "</span>";
	}
	if(this.show_cancel){
		html += "<span class='ld-update-actions'>";
		html += "<button class='ldo-actions ld-control' title='Cancel Import' id='"+ this.prefix + "-action-cancel'>Cancel Import</button>";
		html += "<span>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-right'>";
	html += "<strong class='import-bar-label'>Import Format:</strong> ";
	if(this.edit_formats){
		html += "<select id='" + this.prefix + "-ldformat' + class='ld-import-formats ld-control dacura-select'>";
		html += "<option class='foption ld-bar-format' value='0' id='" + this.prefix + "-format-" + i + "' selected>Auto-detect</option>";							
		for(var i in this.edit_formats){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-format-" + i + "'>" + this.edit_formats[i] + "</option>";							
		}
		html += "</select>";
	}
	html += "</td></tr></table></div>";
	return html;
}

/**
 * Generates the html for showing the options bar in edit mode on top of the viewer
 * @returns {String} html
 */
LDOViewer.prototype.showEditOptionsBar = function(){
	var html = "<div class='ld-edit-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	tit = "format: " + this.view_formats[this.ldo.format];
	if(typeof(this.ldo.options) == 'object' && this.ldo.options.ns){
		tit += ", Prefixes on";
	}
	else {
		tit += ", Prefixes off";
	}
	if(typeof(this.ldo.options) == 'object' && this.ldo.options.addressable){
		tit += ", Addressable blank nodes";
	}
	else {
		tit += ", Normal blank nodes";	
	}
	html += "<strong title='" + tit + "'>Edit Mode (" + this.ldo.format + ")</strong>";
	html += "</td>";
	html += "<td class='ld-bar ld-bar-centre'>";
	if(this.editmode_options){
		html += "<div class='editbar-options editmode-options'>";
		html += "<select class='ld-edit-modes api-control dacura-select'>";
		for(var i in this.editmode_options){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-editmode-" + i + "'>" + this.editmode_options[i] + "</option>";							
		}
		html += "</select></div>";
	}
	if(this.result_options){
		html += "<div class='editbar-options result-options'>";
		html += "<select class='ld-result-modes api-control dacura-select'>";
		for(var i in this.result_options){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-resultoption-" + i + "'>" + this.result_options[i] + "</option>";							
		}
		html += "</select></div>";
	}			
	if(this.view_graph_options){
		html += "<div class='editbar-options view-graph-options'>";
		for(var i in this.view_graph_options){
			html += "<input type='checkbox' class='api-control ld-api-option' id='" + this.prefix + "-graphoption-" + i + "'";
			html += " /><label for='" + this.prefix + "-graphoption-" + i + "'>" + this.view_graph_options[i] + "</label>";
		}
		html += "</div>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-right'>";
	html += "<span class='ld-update-actions'>";
	html += "<button class='ldo-actions ld-control' title='Cancel Editing' id='"+ this.prefix + "-action-cancel'>Cancel Editing</button>";
	html += "<span>";
	html += "</td></tr></table></div>";
	return html;
}

/**
 * Initialises the viewer's options bar by attaching events, etc
 * @param callback {function} the callback function to call upon update of the options
 */
LDOViewer.prototype.initOptionsBar = function(callback){
	var self = this;
	$('button.ld-control').button().click(function(){
		var act = this.id.substring(this.id.lastIndexOf("-")+1);
		self.handleViewAction(act, callback);
	});
	if(this.emode == "view"){
		$('input.ld-control').button().click(function(){
			var opt = this.id.substring(this.id.lastIndexOf("-")+1);
			if(self.ldo.format == "html"){
				var ck = $('#' + this.id).attr('checked');
				showing = (typeof showing != "undefined" ? showing : ck);
				if(showing && self.frm){
					self.frm.redraw({show_annotations: false});
					showing = false;
				}
				else if(self.frm) {
					self.frm.redraw({show_annotations: true});
					showing = true;
				}
			}
			else {
				var val = $('#' + this.id).attr('checked');
				self.handleViewOptionUpdate(opt, !val);
			}
		});
		//$('span.ld-view-options').buttonset();
		$('select.ld-control').selectmenu({change:function(){
			var format = $('#'+this.id).val();
			self.handleViewFormatUpdate(format);}
		});
	}
	else if(this.emode == "edit") {
		$('button.api-control').button();
		$('.view-graph-options').buttonset();
		$('select.api-control').selectmenu();
	}
	else if(this.emode == "import"){
		var self = this;
		$("#" + this.prefix + "-ld-uploadtype").buttonset().click(function(){
			v = $("#" + this.id +" :radio:checked").val();
			self.setImportType(v);
		});
		$('button.api-control').button();
		$('select.ld-import-formats').selectmenu({width: 200});
	}
}

/**
 * Generates the html for showing the update buttons
 * @returns {String} html
 */
LDOViewer.prototype.getUpdateButtonsHTML = function(mode){
	var html = '<div class="subscreen-buttons">';
	if(mode == 'edit'){		
		html += "<button id='" + this.prefix + "-cancelupdate' class='dacura-cancel-update subscreen-button'>Cancel Update</button>"	
		html += "<button id='" + this.prefix + "-testupdate' class='dacura-test-update subscreen-button'>Test Update</button>";	
		html += "<button id='" + this.prefix + "-update' class='dacura-test-update subscreen-button'>Update</button>";	
	}
	else if (mode == 'import'){
		html += "<button id='" + this.prefix + "-cancelimport' class='dacura-cancel-update subscreen-button'>Cancel Import</button>"	
		html += "<button id='" + this.prefix + "-testimport' class='dacura-test-update subscreen-button'>Test</button>";	
		html += "<button id='" + this.prefix + "-import' class='dacura-test-update subscreen-button'>Import Now</button>";			
	}
	html += "</div>";
	return html;
};

/**
 * Intialises the update buttons by adding various events, etc
 * @param tpconf {DacuraPageConfig} page configuration object
 * @param callback {function} update callback function
 */
LDOViewer.prototype.initUpdateButtons = function(tpconf, callback){
	var self = this;
	if(this.emode == "edit"){
		$('.subscreen-button').button().click(function(){
			var act = this.id.substring(this.id.lastIndexOf("-")+1);
			if(act == "cancelupdate"){
				self.handleViewAction("cancel")
			}
			else { //action is update or test update
				if(self.ldo.format == "html"){
					var updated = self.frm.extract();
					//jpr(updated);
				}
				else {
					var updated = self.ldo.getUpdatedContents(self.target);
					if(typeof self.frm == "object" && self.ldo.format == "html"){
						updated = $.extend(true, updated, self.frm.extract());	
					}
					if(dacura.ld.isJSONFormat(self.ldo.format)){
						try {
							updated = JSON.parse(updated);
						}
						catch(e){
							alert(e.message);
							return;
						}
					}
				}
				var test = (act == "testupdate") ? 1 : 0;
				var opts = (test ? self.test_update_options : self.update_options);
				if(self.editmode_options){
					var j = $(self.target + " .ld-edit-modes").val();
					var em = j ? j : "replace";					
				}
				else {
					var em = "update";
				}
				if(self.result_options){
					j = $(self.target + " .ld-result-modes").val();
					var rem = j ? j : 0;
					opts.show_result = rem;
					if(rem == 2){
						opts.show_changed = 1;
						opts.show_original = 1;
						opts.show_delta = 1;
					}
				}
				if(self.view_graph_options){
					$(self.target + ' .editbar-options input:checkbox').each(function(){
						var act = "show_" + this.id.substring(this.id.lastIndexOf("-")+1)+ "_triples";
						if($(this).is(":checked")){
							opts[act] = "1";
						}
						else {
							opts[act] = 0;
						}
					});					
				}
				var upd = {
					'ldtype': self.ldo.ldtype(), 
					"test": test, 
					"contents": updated, 
					"editmode": em,
					"options" : opts,
					"format": (self.ldo.format == "html" ? "json" : self.ldo.format)
				};
				self.update(upd, callback, tpconf);	
			}
		});
	}
	else if(this.emode == "import"){
		$('.subscreen-button').button().click(function(){
			var act = this.id.substring(this.id.lastIndexOf("-")+1);
			if(act == "cancelimport"){
				self.emode = 'view';
				self.show();
			}
			else { //act is update
				updobj = {'ldtype': self.ldo.ldtype(), "editmode": "replace"};
				updobj.options = {show_result: 1};
				var updated = self.getImportData(updobj, true);
				if(updated){ 
					var test = (act == "testimport") ? 1 : 0;
					if(test){
						updated.test = 1;
					}
					if(typeof updobj.ldfile != "undefined"){
						var handleUpload = function(fname){
							updated.ldfile = fname;
							self.update(updated);
						}
						dacura.upload.uploadFile(updated.ldfile, handleUpload, tpconf)
					}
					else {
						self.update(updated, callback, tpconf);
					}
				}
			}
		});
	}
};

/**
 * Retrieves the data that has been input by the user and packs it into a json object
 * @param updobj {object} json object to add the inputs to
 * @param validate {boolean} if true, the data should be validated before being returned
 * @returns {object} the object with format & ldurl|ldfile|contents filled in from the import screen
 */
LDOViewer.prototype.getImportData = function(updobj, validate){
	var format = $("#" + this.prefix + "-ldformat").val();
	if(format && format != "0"){
		updobj.format = format;
	}
	var it = $("#" + this.prefix + "-ld-uploadtype :radio:checked").val();
	if(it == "url"){
		var url = $('#' + this.prefix + "-ldurl").val();
		if(validate && !url){
			alert("You must enter a url before loading it");
			return false;
		}
		else if(validate && !validateURL(url)){
			alert(escapeHtml(url) + " is not a valid url - please fix it and try again");
			return false;
		}
		else {
			updobj.ldurl = url; 
		}
	}
	else if(it == 'file'){
		payload = document.getElementById(this.prefix + "-ldfile").files[0];
		if(validate && !payload){
			alert("You must choose a file to upload");
			return false;
		}
		else {
			updobj.ldfile = payload;
		}
	}
	else {
		var text = $('#' + this.prefix + "-ldtext").val();
		if(validate && !text){
			alert("You must paste text into the textbox to import data");
			return false;
		}
		else {
			if(typeof updobj.format == "string" && dacura.ld.isJSONFormat(updobj.format)){
				try {
					updobj.contents = JSON.parse(text);
				}
				catch(e){
					alert(updobj.format + " is a json format, incorrect json entered in contents: " + e.message);
					return false;
				}
			}
			else {
				updobj.contents = text;
			}				
		}
	}
	return updobj;
};

/**
 * Sets the import type 
 * @param v {String} the new import mode
 */
LDOViewer.prototype.setImportType = function(v){
	if(v != this.impmode){
		this.impmode = v;
		this.replaceContents(this.getImportBodyHTML());
		this.tooltipLDImport();	
	}
};

/**
 * Replaces the contents of the viewer with the passed html
 * @param html {string} new contents
 */
LDOViewer.prototype.replaceContents = function(html){
	$('#' + this.prefix + "-ldo-viewer-contents").html(html);
};

/**
 * Generates the html to show the body of the imports section 
 * @returns {String} html
 */
LDOViewer.prototype.getImportBodyHTML = function(){
	if(typeof this.impmode == "undefined") this.impmode = "url";
	if(this.impmode == "file"){
		var hcont = wrapIPFieldInDacuraForm("File upload", "<input class='ldimport-input dacura-regular-input' type='file' id='" + this.prefix + "-ldfile' value=''>", "Choose a file on your local computer to upload");
	}
	else if (this.impmode == "url"){
		var hcont = wrapIPFieldInDacuraForm("Import from URL", "<input class='dacura-long-input ldimport-input' type='text' id='" + this.prefix + "-ldurl' value=''>", "Enter the url of a linked data file to import");	
	}
	else {
		var hcont = "<textarea class='dacura-import-editor ldimport-input' type='text' id='" + this.prefix + "-ldtext'></textarea>";		
	}
	return "<div class='ld-import-holder'>" + hcont + "</div>";
};

/**
 * Sets the jquery target selector for the viewer
 * @param jqid the jquery selector for the viewer
 * @param barjqid the jquery selector for the options bar
 */
LDOViewer.prototype.setTarget = function(jqid, barjqid){
	this.target = jqid;
	this.prefix = jqid.substring(1);
	this.options_target = (typeof barjqid != "undefined") ? barjqid : this.target;
}

/**
 * Called when there is a frame associated with an ldo
 * @param ldo {String} the class of the frame
 * @param target {String} the jquery place where the frame will be written
 * @param mode {String} the view|edit|create mode of the viewer
 */
LDOViewer.prototype.showFrame = function(ldo, target, mode, tooltip_config){
	if(mode == 'create'){
		var cls = ldo;
	}
	else {
		var cls = ldo.meta.type;
	}
	var frmconfig = { target: target, page_config: this.pconf, tooltip_config: tooltip_config };
	this.frm = new FrameViewer(this.entities, this.entity_classes, frmconfig, this.frame_renderers);
	var self = this;
	var msgs = { "success": "Retrieved frame for "+cls + " class from server", "busy": "retrieving frame for "+cls + " class from server", "fail": "Failed to retrieve frame for class " + cls + " from server"};
	var ajs = dacura.frame.api.getFrame(cls);
	if(ldo && ldo.id){
		ajs.handleResult = function(resultobj, pconf){
			var archetypes = resultobj.result;
			if(typeof archetypes == "string"){
				try {
					archetypes = JSON.parse(archetypes);
				}	
				catch(e){
					alert("Failed to parse frames from sever: " + e.message);
					return;
				}
			}
			self.frm.init(cls, archetypes);
			msgs = { "success": "Retrieved frame for candidate "+ldo.id+ " from server", "busy": "retrieving frame for candidate "+ldo.id+ " class from server", "fail": "Failed to retrieve frame for candidate " + ldo.id+ " from server"};
			var ajx = dacura.frame.api.getFilledFrame(ldo.id);	
			ajx.handleResult = function(resultobj, pconf){
				var frames = resultobj.result;
				if(typeof frames == "string"){
					try {
						frames = JSON.parse(frames);
					}	
					catch(e){
						alert("Failed to parse frames from sever: " + e.message);
						return;
					}
				}
				if(mode == "edit"){
					var drawn = self.frm.draw(frames, "edit");
					self.frm.drawUndisplayed(archetypes, "create");					
				}
				else {
					self.frm.draw(frames, mode);
				}
			}
			dacura.system.invoke(ajx, msgs, this.pconf);	
		};
	}
	else {
		ajs.handleResult = function(resultobj, pconf){
			var frames = resultobj.result;
			if(typeof frames == "string"){
				try {
					frames = JSON.parse(frames);
				}	
				catch(e){
					alert("Failed to parse frames from sever: " + e.message);
					return;
				}
			}
			self.frm.init(cls, frames);
			self.frm.draw(frames, mode);
		};	
	}
	dacura.system.invoke(ajs, msgs, this.pconf);	
};

/**
 * Called to handle the situation when a user clicks on some action button while in view mode
 * @param act {string} the action
 * @param callback {function} the callback function to pass to the update function (if required by action)
 */
LDOViewer.prototype.handleViewAction = function(act, callback){
	if(act == "export"){
		window.location.href = this.ldo.fullURL() + "&direct=1";	
	}
	else if(act == "accept" || act == "pending" || act == "reject"){
		var upd = {'ldtype': this.ldo.ldtype(), "meta": {"status": act}, "editmode": "update", "format": "json"};
		this.update(upd, callback);
	}
	else if(act == "restore"){
		alert("restore needs to be written");
	}
	else if(act == "import"){
		this.loadImportMode();
	}
	else if(act == "edit"){
		this.loadEditMode();
	}
	else if(act == "cancel"){
		if(this.emode == "import"){
			this.emode = "view";
			this.show();
		}	
		else {
			this.clearEditMode();
		}
	}
};

/**
 * Returns the viewer to view mode from edit mode
 */
LDOViewer.prototype.clearEditMode = function(){
	var args = typeof this.savedargs == "object" ? this.savedargs : this.ldo.getAPIArgs();
	delete(this.savedargs);
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id;
	msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
	this.emode="view";
	this.refresh(args, msgs);
};

/**
 * Load the viewer's import mode
 */
LDOViewer.prototype.loadImportMode = function(){
	this.emode = 'import';
	this.impmode = 'url';
	this.show();
}

/**
 * Load the viewer's edit mode settings
 */
LDOViewer.prototype.loadEditMode = function(){
	var args = this.ldo.getAPIArgs();
	this.savedargs = jQuery.extend(true, {}, args);
	if(typeof args.options != "object"){
		args.options = {"plain": 1};
	}
	else {
		for(var i in args.options){
			if(i != "ns" && i != "addressable"){
				delete(args.options[i]);
			}
		}
		args.options.plain = 1;
	}
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id;
	msgs = {busy: "Loading " + idstr + " in edit mode from server", "fail": "Failed to retrieve " + idstr + " in edit mode from server"};
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	var handleResp = function(data, pconf){
		self.ldo = new LDO(data);
		self.emode = "edit";
		self.show();

	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

/**
 * Called when a user chooses a different format for the ldo
 * @param format {string} the chosen format
 */
LDOViewer.prototype.handleViewFormatUpdate = function(format){
	if(format != this.ldo.format){
		var args = this.ldo.getAPIArgs();
		args.format = format;
		var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id + " in " + this.view_formats[format] + " format";
		msgs = {busy: "Fetching " + idstr + " from server", "fail": "Failed to retrieve " + idstr + " from server"};
		this.refresh(args, msgs);
	}
	else {
		alert("format will not change: still "+this.ldo.format);
	}
};

/**
 * Called when a user changes some of the view options on the options bar
 * @param opt {String} the option that was changed
 * @param val {String} the value the option has been changed to
 */
LDOViewer.prototype.handleViewOptionUpdate = function(opt, val){
	var opts = this.ldo.options;
	if(val && (typeof opts[opt] == "undefined" || opts[opt] == false)){
		opts[opt] = 1;
	}	
	else if(!val && opts[opt] == true){
		opts[opt] = 0;
	}
	else {
		return alert(opt + " is set to " + val + " no change");c
	}
	var args = this.ldo.getAPIArgs();
	args.options = opts;
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id + " with option " + this.view_options[opt].title;
	if(opts[opt]){ 
		idstr += " enabled";
	}
	else {
		idstr += " disabled";
	}
	msgs = {busy: "Fetching " + idstr + " from server", "fail": "Failed to retrieve " + idstr + " from server"};
	this.refresh(args, msgs);
};

/** 
 * Called to refresh the entire ldo page from the server when an ldo has been updated 
 * @param args {Object} argument array to be sent to server
 * @param msgs {Object} messages array to be displayed while busy
 * @param callback {function} callback function to refresh page elements
 */
LDOViewer.prototype.refreshPage = function(args, msgs, callback){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	if(typeof callback == "function") {
		var ref = function (data, pconf) { 
			callback(data, pconf);
		};
	}
	else {
		var ref = function (data, pconf) { 
			self.ldo = new LDO(data);
			self.show();
		};
	}
	dacura.ld.fetch(id, args, ref, this.pconf, msgs);
};

/* refresh ..*/
/**
 * Variation on above that does not call callback - only rewrites the body of the ldo, not the rest of the stuff like history
 * @param args {Object} argument array to send to server ldo fetch
 * @param msgs {Object} messages array to be displayed while busy
 */
LDOViewer.prototype.refresh = function(args, msgs){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	var handleResp = function(data, pconf){
		self.ldo = new LDO(data);
		self.show();
	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

/**
 * Called to validate an ldo created with the new ldo form
 * @param obj {Object} json object with various data extracted from form into it
 * @returns {Array} an error of errors or false if no errors (ie false = valid)
 */
LDOViewer.prototype.validateNew = function(obj){
	var errs = [];
	this.getImportData(obj, false);
	if(typeof obj.contents == 'string' && obj.contents && typeof obj.format == "string" && dacura.ld.isJSONFormat(obj.format)){
		try {
			x = JSON.parse(obj.contents);
			if(typeof x != "object"){
				errs.push("Contents must contain a well formed json object");
			}
		}
		catch(e){
			errs.push("Contents does not contain well-formed json");
		}
	}
	if(typeof obj.meta == 'string' && obj.meta){
		try {
			x = JSON.parse(obj.meta);
			if(typeof x != "object"){
				errs.push("Meta does not contain a json object");
			}
		}
		catch(e){
			errs.push("Meta does not contain well-formed JSON: "+e.message);
		}
	}
	if(!this.allow_empty_contents){
		if(typeof this.ldurl != "undefined" && !this.ldurl){
			errs.push("URL field is empty: you must enter a valid URL from which to import the " + this.ldtype());
		}
		else if(this.ldurl &&  !validateURL(this.ldurl)){
			errs.push(this.ldurl + " is not a valid url: you must enter a valid URL from which to import the " + this.ldtype());			
		}
		if(typeof this.ldfile != "undefined" && !this.ldfile){
			errs.push("File field empty: you must choose a file from which to import the " + this.ldtype());						
		}
		if(typeof this.contents != "undefined" && !this.contents){
			errs.push("Text box empty: you must paste the linked data contents into the textbox below to import the " + this.ldtype());						
		}
	}
	if(errs.length > 0){
		return errs;
	}
	return false;
};

/**
 * Reads input data from the create form to populate the linked data object
 * @param obj {Object} the object directly read from the form
 * @param demand_id_token {string} the string that signifies a demand id
 * @param options {Object} the options to be passed to api
 * @param pconf {DacuraPageConfig} page config object
 * @returns {Object} json object with form inputs filled in ready to send to api
 */
LDOViewer.prototype.readCreateForm = function(obj, demand_id_token, options, pconf){
	apiobj = {};
	if(typeof options != "undefined"){
		apiobj.options = options;
	}
	if(typeof obj.id == "string" && obj.id){
		apiobj[demand_id_token] = obj.id;
	}
	if(typeof obj.meta != "undefined" && obj.meta) {
		apiobj.meta = JSON.parse(obj.meta);
	}
	else {
		apiobj.meta = {};		
	}
	if(typeof obj.status == "string" && obj.status){
		apiobj.meta.status = obj.status;
	}
	else {
		apiobj.meta.status = "accept";
	}
	if(typeof obj.url == "string" && obj.url){
		apiobj.meta.url = obj.url;
	}
	if(typeof obj.title == "string" && obj.title){
		apiobj.meta.title = obj.title;
	}
	if(typeof obj.ldtype == "string" && obj.ldtype){
		apiobj.ldtype = obj.ldtype;
	}
	this.getImportData(apiobj, false);
	if(typeof obj.ldsource == "string" && obj.ldsource == "file" && obj.ldfile){
		apiobj.ldfile = obj.ldfile;
	}
	else if(typeof obj.ldsource == "string" && obj.ldsource == "url" && obj.ldurl){
		apiobj.ldurl = obj.ldurl;
	}
	else if (obj.contents) {
		if(typeof apiobj.format == "string" && dacura.ld.isJSONFormat(apiobj.format) && typeof obj.contents == "string"){
			try {
				apiobj.contents = JSON.parse(obj.contents);
				
			}
			catch(e){
				alert("Failed to parse contents as JSON object " + e.message);
				return false;
			}
		}
		else {
			apiobj.contents = obj.contents;
		}	
	}
	if(isEmpty(apiobj.meta)){
		delete(apiobj.meta);
	}
	return apiobj;		
};

/**
 * Called to create a new LDO
 * @param created {Object} the new ldo object
 * @param result {function} callback for handling result of api call
 * @param test_flag {boolean} if true, just a test invocation
 */
LDOViewer.prototype.create = function(created, result, test_flag){
	var self = this;
	if(typeof created.ldfile != "undefined" && typeof created.uploaded_earlier == 'undefined'){
		var handleUpload = function(fname, xpconf){
			created.ldfile = fname;
			created.uploaded_earlier = true;
			self.create(created, result, test_flag);
		}
		var upconf = {}
		dacura.upload.uploadFile(created.ldfile, handleUpload, self.pconf);
	}
	else {
		dacura.ld.create(created, result, self.pconf, test_flag);
	}	
};

/**
 * Called to update an existing LDO on the server
 * @param upd {Object} the ldo update object
 * @param resultCallback {function} the function that will be called to handle the result
 * @param pageconfig {DacuraPageConfig} the page config
 * @param handleResp 
 */
LDOViewer.prototype.update = function(upd, resultCallback, pageconfig, handleResp){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	pageconfig = typeof pageconfig == "object" ? pageconfig : this.pconf;
	var self = this;//this becomes bound to the callback...
	if(typeof handleResp != "function"){
		handleResp = function(data, pconf){
			var res = new LDResult(data, pconf);
			if(res.status == "accept" && !res.test){
				var args = typeof self.savedargs == "object" ? self.savedargs : self.ldo.getAPIArgs();
				delete(self.savedargs);
				var idstr = self.ldo.ldtype().ucfirst() + " " + self.ldo.id;
				msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
				self.emode="view";
				self.refreshPage(args, msgs, resultCallback);
			}
			else if(res.status == "pending" && !res.test){
				var args = typeof self.savedargs == "object" ? self.savedargs : self.ldo.getAPIArgs();
				delete(self.savedargs);
				var idstr = self.ldo.ldtype().ucfirst() + " " + self.ldo.id;
				msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
				self.emode="view";
				self.refreshPage(args, msgs, resultCallback);			
			}
			else {
				if(typeof resultCallback == "function"){
					resultCallback(data, pconf);
				}
			}
			res.show();
		}
	}
	dacura.ld.update(id, upd, handleResp, pageconfig, upd.test);
};

/**
 * Is this a ldoupdate viewer object - used to distinguish between this object and ldoupdateviewer object
 * @returns {Boolean} false - 
 */
LDOViewer.prototype.isLDOUpdate = function(){
	return false;
};

/**
 * Wraps an input field in dacura form html - to add ldo fields to dacura forms 
 * @function wrapIPFieldInDacuraForm
 * @param l {string} label - the field label
 * @param d {string} input element html
 * @param h {string} help text
 * @returns {String} html
 */
function wrapIPFieldInDacuraForm(l, d, h){
	var html = '<table class="dacura-property-table" id="jsonvphoney" style="border: 0">';
	html += '<thead></thead><tbody><tr class="dacura-property-spacer"></tr>';
	html += '<tr class="dacura-property first-row row-1 last-row">';
	html += '<td class="dacura-property-label">' + l + '</td><td class="dacura-property-value">';
	html += '<table style="border: 0" class="dacura-property-value-bundle"><tbody><tr><td class="dacura-property-input">';
	html +=  d + '</td><td class="dacura-property-help">' + h + '</td></tr></tbody></table></td></tr>'
	html += '<tr class="dacura-property-spacer"></tr></tbody></table>';
	return html;	
}