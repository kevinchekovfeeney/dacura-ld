/**
 * LDO Viewer object - useful functions for representing Linked data objects in html
 */
function LDOViewer(ldo, pconf, vconf){
	this.ldo = ldo;
	this.pconf = pconf;
	this.emode = "view";
	this.viewstyle = "raw";
	this.target = "";
	if(typeof voncf == "object"){
		this.init(vconf);
	}
}

LDOViewer.prototype.init = function(vconf){
	if(typeof vconf.emode == "string"){
		this.emode = vconf.emode;
	}
	if(typeof vconf.viewstyle == "string"){
		this.viewstyle = vconf.viewstyle;
	}
	if(typeof vconf.target != "string"){
		alert("LDO Viewer called without a target!");
	}
	else {
		this.target = vconf.target;
		this.prefix = this.target.substring(1);//get rid of the #
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
	this.show_options = true;
}

LDOViewer.prototype.show = function(vconf, showconf){
	if(typeof vconf == "object"){
		this.init(vconf);
	}
	if(typeof showconf == "undefined"){
		showconf = {};
	}
	if(typeof showconf.show_options == "undefined"){
		showconf.show_options = this.show_options;
	}
	if(typeof showconf.show_buttons == "undefined"){
		showconf.show_buttons = true;
	}
	$(this.target).html("");
	if(this.show_options){
		$(this.target).append(this.showOptionsBar());
		this.initOptionsBar();
	}
	var body = "<div class='ldo-viewer-contents' id='" + this.prefix + "-ldo-viewer-contents'>";
	body += this.ldo.getContentsHTML(this.emode, this.impmode, this.prefix) + "</div>";
	$(this.target).append(body);
	if(this.ldo.format == "html" && this.ldo.ldtype() == "candidate" && typeof this.ldo.contents == "object"){
		this.initFrameView();
	}
	if(showconf.show_buttons && this.emode != "view"){
		$(this.target).append(this.getUpdateButtonsHTML(this.emode));
		this.initUpdateButtons();
	}
}

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

LDOViewer.prototype.showViewOptionsBar = function(){
	var html = "<div class='ld-view-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	if(this.view_formats){
		html += "<select class='ld-view-formats ld-control'>";
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
	if(this.view_options){
		html += "<span class='ld-view-options'>";
		for(var i in this.view_options){
			html += "<input type='checkbox' class='ld-control ld-bar-option' id='" + this.prefix + "-option-" + i + "' ";
			if(this.ldo.options[i] == 1){
				html += "checked";
			}
			html += " /><label for='" + this.prefix + "-option-" + i + "'>" + this.view_options[i].title + "</label>";
		}
		html += "</span>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-right'>";
	if(this.view_actions){
		html += "<span class='ld-update-actions'>";
		if(this.ldo.meta.version != this.ldo.meta.latest_version){
			if(typeof this.view_actions['restore'] == "string"){
				html += "<button class='ldo-actions ld-control' title='" + this.view_actions["restore"] + "' id='"+ this.prefix + "-action-restore'>" + this.view_actions["restore"] + "</button>";								
			}
			if(typeof this.view_actions['export'] == "string"){
				html += "<button class='ldo-actions ld-control' title='" + this.view_actions["export"] + "' id='"+ this.prefix + "-action-export'>" + this.view_actions["export"] + "</button>";								
			}
		}
		else {
			for(var i in this.view_actions){
				if(i == "restore") continue;
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

LDOViewer.prototype.showImportOptionsBar = function(){
	var html = "<div class='ld-import-bar ld-bar'><table class='ld-bar'><tr><td class='ld-bar ld-bar-left'>";
	html += "<strong class='import-bar-label'>Import Format:</strong> ";
	if(this.edit_formats){
		html += "<select id='" + this.prefix + "-ldformat' + class='ld-import-formats ld-control'>";
		html += "<option class='foption ld-bar-format' value='0' id='" + this.prefix + "-format-" + i + "' selected>Auto-detect</option>";							
		for(var i in this.edit_formats){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-format-" + i + "'>" + this.edit_formats[i] + "</option>";							
		}
		html += "</select>";
	}
	html += "</td>";
	html += "<td class='ld-bar ld-bar-centre'><span id='" + this.prefix + "-ld-uploadtype'>";
	html += '<input type="radio" class="ldimportoption" checked value="textbox" id="'+ this.prefix + '-importtext" name="' + this.prefix + '-importformat"><label for="' + this.prefix + '-importtext" >Paste into Textbox</label>';
	html += '<input type="radio" class="ldimportoption" value="url" id="' + this.prefix + '-importurl" name="' + this.prefix + '-importformat"><label for="'+ this.prefix + '-importurl">Import from URL</label>';
	html += '<input type="radio" class="ldimportoption" value="file" id="'+ this.prefix + '-importupload" name="' + this.prefix + '-importformat"><label for="' + this.prefix + '-importupload">Upload File</label>';
	html += "</span></td>";
	html += "<td class='ld-bar ld-bar-right'>";
	html += "<span class='ld-update-actions'>";
	html += "<button class='ldo-actions ld-control' title='Cancel Import' id='"+ this.prefix + "-action-cancel'>Cancel Import</button>";
	html += "<span>";
	html += "</td></tr></table></div>";
	return html;
}

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
		html += "<select class='ld-edit-modes api-control'>";
		for(var i in this.editmode_options){
			html += "<option class='foption ld-bar-format' value='" + i + "' id='" + this.prefix + "-editmode-" + i + "'>" + this.editmode_options[i] + "</option>";							
		}
		html += "</select></div>";
	}
	if(this.result_options){
		html += "<div class='editbar-options result-options'>";
		html += "<select class='ld-result-modes api-control'>";
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

LDOViewer.prototype.initOptionsBar = function(){
	var self = this;
	$('button.ld-control').button().click(function(){
		var act = this.id.substring(this.id.lastIndexOf("-")+1);
		self.handleViewAction(act);
	});
	if(this.emode == "view"){
		$('input.ld-control').button().click(function(){
			var opt = this.id.substring(this.id.lastIndexOf("-")+1);
			var val = $('#' + this.id).attr('checked');
			self.handleViewOptionUpdate(opt, !val)
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
		$('select.ld-import-formats').selectmenu();
	}
}

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

LDOViewer.prototype.initUpdateButtons = function(){
	var self = this;
	if(this.emode == "edit"){
		$('.subscreen-button').button().click(function(){
			var act = this.id.substring(this.id.lastIndexOf("-")+1);
			if(act == "cancelupdate"){
				self.handleViewAction("cancel")
			}
			else { //act is update
				var updated = self.ldo.getUpdatedContents(self.target);
				if(dacura.ld.isJSONFormat(self.ldo.format)){
					try {
						updated = JSON.parse(updated);
					}
					catch(e){
						alert(e.message);
						return;
					}
				}
				var test = (act == "testupdate") ? 1 : 0;
				var j = $(self.target + " .ld-edit-modes").val();
				var em = j ? j : "replace";
				j = $(self.target + " .ld-result-modes").val();
				var rem = j ? j : 0;
				var opts = {"show_result": rem};
				if(rem == 2){
					opts.show_changed = 1;
					opts.show_original = 1;
					opts.show_delta = 1;
				}
				$(self.target + ' .editbar-options input:checkbox').each(function(){
					var act = "show_" + this.id.substring(this.id.lastIndexOf("-")+1)+ "_triples";
					if($(this).is(":checked")){
						opts[act] = "1";
					}	
				});
				var upd = {
					'ldtype': self.ldo.ldtype(), 
					"test": test, 
					"contents": updated, 
					"editmode": em,
					"options" : opts,
					"format": self.ldo.format
				};
				self.update(upd);	
				//assemble our options for update...
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
				var updated = self.getImportData(true);
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
						dacura.ld.uploadFile(updated.ldfile, handleUpload, this.pconf)
					}
					else {
						self.update(updated);
					}
				}
			}
		});
	}
};
		

LDOViewer.prototype.getImportData = function(validate){
	updobj = {'ldtype': this.ldo.ldtype(), "editmode": "replace"};
	updobj.options = {show_result: 1};
	var format = $("#" + this.prefix + "-ldformat").val();
	if(format && format != "0"){
		updobj.format = format;
	}
	var it = $("#" + this.prefix + "-ld-uploadtype :radio:checked").val();
	if(it == "url"){
		var url = $('#' + this.prefix + "-ldurl").val();
		if(!url){
			alert("You must enter a url before loading it");
			return false;
		}
		else if(!validateURL(url)){
			alert(escapeHtml(url) + " is not a valid url - please fix it and try again");
			return false;
		}
		else {
			updobj.ldurl = url; 
		}
	}
	else if(it == 'file'){
		payload = document.getElementById(this.prefix + "-ldfile").files[0];
		if(!payload){
			alert("You must choose a file to upload");
			return false;
		}
		else {
			updobj.ldfile = payload;
		}
	}
	else {
		var text = $('#' + this.prefix + "-ldtext").val();
		if(!text){
			alert("You must paste text into the textbox to import data");
			return false;
		}
		else {
			if(typeof updobj.format == "string" && dacura.ld.isJSONFormat(updobj.format)){
				try {
					updobj.contents = JSON.parse(text);
				}
				catch(e){
					alert("Failed to parse contents as JSON object " + e.message);
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
		
		
LDOViewer.prototype.setImportType = function(v){
	if(v != this.impmode){
		this.impmode = v;
		this.replaceContents(this.ldo.getContentsHTML(this.emode, this.impmode, this.prefix)); 
	}
};
LDOViewer.prototype.replaceContents = function(html){
	$('#' + this.prefix + "-ldo-viewer-contents").html(html);
}

LDOViewer.prototype.initFrameView = function(){
	var pconf = this.pconf;
	obusy = pconf.busybox;
	pconf.busybox = "#dacura-frame-viewer";
	var cls = this.ldo.meta.type;
	if(typeof this.ldo.contents != "object"){
		this.ldo.contents = JSON.parse(this.ldo.contents);
	}
	var frameobj = {result: json.stringify(this.ldo.contents)};
	var frameid = dacura.frame.draw(cls,frameobj,pconf,'frame-container');
	dacura.frame.fillFrame(this.ldo, pconf, 'frame-container'); 
	pconf.busybox = obusy;
	dacura.frame.initInteractors();
};

LDOViewer.prototype.handleViewAction = function(act){
	if(act == "export"){
		window.location.href = this.ldo.fullURL() + "&direct=1";	
	}
	else if(act == "accept" || act == "pending" || act == "reject"){
		var upd = {'ldtype': this.ldo.ldtype(), "meta": {"status": act}, "editmode": "update", "format": "json"};
		this.update(upd);
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

LDOViewer.prototype.clearEditMode = function(){
	var args = typeof this.savedargs == "object" ? this.savedargs : this.ldo.getAPIArgs();
	delete(this.savedargs);
	var idstr = this.ldo.ldtype().ucfirst() + " " + this.ldo.id;
	msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
	this.emode="view";
	this.refresh(args, msgs);
};

LDOViewer.prototype.loadImportMode = function(){
	this.emode = 'import';
	this.impmode = 'textbox';
	this.show();
}

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
		//self.ldo = new LDO(data);
		//self.show();
	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

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

LDOViewer.prototype.handleViewOptionUpdate = function(opt, val){
	var opts = this.ldo.options;
	if(val && (typeof opts[opt] == "undefined" || opts[opt] == false)){
		opts[opt] = 1;
	}	
	else if(!val && opts[opt] == true){
		opts[opt] = 0;
	}
	else {
		return alert(opt + " is set to " + val + " no change");
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

LDOViewer.prototype.refreshPage = function(args, msgs){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
	var self = this;//this becomes bound to the callback...
	var handleResp = function(data, pconf){
		dacura.ld.header(data);
		if(typeof data.history == "object" && $('#ldo-history').length){
			dacura.tool.table.reincarnate("history_table", data.history);		
		}
		if(typeof data.updates == "object" && $('#ldo-updates').length){
			dacura.tool.table.reincarnate("updates_table", data.updates);		
		}
		dacura.system.styleJSONLD("td.rawjson");	
		self.ldo = new LDO(data);
		self.show();
	}
	$(this.pconf.resultbox).empty();
	dacura.ld.fetch(id, args, handleResp, this.pconf, msgs);
};

LDOViewer.prototype.update = function(upd, handleResp){
	var id = this.ldo.id;
	if(this.ldo.fragment_id){ 
		id = id + "/" + this.ldo.fragment_id;
	}
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
				self.refreshPage(args, msgs);
			}
			else if(res.status == "pending" && !res.test){
				var args = typeof self.savedargs == "object" ? self.savedargs : self.ldo.getAPIArgs();
				delete(self.savedargs);
				var idstr = self.ldo.ldtype().ucfirst() + " " + self.ldo.id;
				msgs = {busy: "Loading " + idstr + " in view mode from server", "fail": "Failed to retrieve " + idstr + " in view mode from server"};
				self.emode="view";
				self.refreshPage(args, msgs);			
			}
			res.show();
		}
		//jpr(data);
	}
	dacura.ld.update(id, upd, handleResp, this.pconf, upd.test);
}

/**
 * LDO - Linked Data Object - for parsing the LDO objects sent in the result field of the api response
 */

function LDO(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
	this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
}


LDO.prototype.getHTML = function(mode){
	if(!this.contents && !this.meta){
		if(isEmpty(this.inserts) && isEmpty(this.deletes)){
			html = "<div class='info'>No changes to graph</div>";		
		}
	}
	else {
		html = this.getMetaHTML(mode) + this.getContentsHTML(mode);
	}
	return html;
}

LDO.prototype.getMetaHTML = function(mode){
	return dacura.ld.wrapJSON(this.meta);	
}

LDO.prototype.getUpdatedContents = function(jtarget){
	if(this.format == "json" || this.format== "jsonld"){
		return $(jtarget + ' textarea.dacura-json-editor').val();
	}
	else if(this.format == "triples" || this.format == "quads"){
		alert("update not done for this format");
	}	
	else if(this.format == "html"){
		alert("update not done for this format");		
	}
	else {
		return $(jtarget + ' textarea.dacura-text-editor').val();
	}
}

LDO.prototype.getContentsHTML = function(mode, impmode, prefix){
	if(mode == 'import'){
		if(typeof impmode == "undefined") impmode = "url";
		if(impmode == "file"){
			var hcont = wrapIPFieldInDacuraForm("File upload", "<input class='ldimport-input dacura-regular-input' type='file' id='" + prefix + "-ldfile' value=''>", "Choose a file on your local computer to upload");
		}
		else if (impmode == "url"){
			var hcont = wrapIPFieldInDacuraForm("Import from URL", "<input class='dacura-long-input ldimport-input' type='text' id='" + prefix + "-ldurl' value=''>", "Enter the url of a linked data file to import");	
		}
		else {
			var hcont = "<textarea class='dacura-import-editor ldimport-input' type='text' id='" + prefix + "-ldtext'></textarea>";		
		}
		return "<div class='ld-import-holder'>" + hcont + "</div>";
	}
	if(this.format == "json" || this.format== "jsonld"){
		return dacura.ld.wrapJSON(this.contents, mode);
	}
	else if(this.format == "triples" || this.format == "quads"){
		return dacura.ld.getTripleTableHTML(this.contents, mode);
	}
	else if(this.format == "html"){
		if(this.ldtype() == "candidate" && typeof this.contents == "object"){
			return "<div id='dacura-frame-viewer'></div>" 
		}
		else {
			return "<div class='dacura-html-viewer'>" + this.contents + "</div>";
		}
	}
	else if(this.format == "svg"){
		return "<object id='svg' type='image/svg+xml'>" + this.contents + "</object>";
	}
	else {
		if(mode == "edit"){
			return "<div class='dacura-export-editor'><textarea class='dacura-text-editor'>" + this.contents + "</textarea></div>";			
		}
		else {
			return "<div class='dacura-export-viewer'>" + this.contents + "</div>";
		}
	}	
};

LDO.prototype.getAPIArgs = function(){
	var args = {
		"format": this.format,
		"options": this.options,
		"ldtype": this.meta.ldtype
	};
	if(this.meta.version != this.meta.latest_version){
		args.version = this.meta.version;
	}
	return args;
}

LDO.prototype.url = function(){
	return this.meta.cwurl;
}


LDO.prototype.fullURL = function(){
	var url = this.url() + "?";
	var args = {"ldtype": this.ldtype()};
	if(this.format){
		args['format'] = this.format;
	}
	if(this.meta.version != this.meta.latest_version){
		args['version'] = this.meta.version;
	}
	for(var i in this.options){
		if(i == "ns" || i == "addressable"){
			args['options[' + i + ']'] = this.options[i];
		}
	}
	for(var j in args){
		url += j + "=" + args[j] + "&";
	}
	return url.substring(0, url.length-1);
}

LDO.prototype.ldtype = function(){
	return this.meta.ldtype;
}

/**
 * LDOUpdate object for interpreting LDOUpdate objects returned in responses by the Dacura API
 */

function LDOUpdate(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.inserts = typeof data.insert == "undefined" ? false : data.insert;
	this.deletes = typeof data["delete"] == "undefined" ? false : data["delete"];
	//this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
	this.changed = typeof data.changed == "undefined" ? false : new LDO(data.changed);
	this.original = typeof data.original == "undefined" ? false : new LDO(data.original);
}

LDOUpdate.prototype.getHTML = function(mode){
	if(!this.inserts && !this.deletes){
		html = "<div class='info'>No Updates</div>";		
	}
	else {
		html = "<h2>Forward</h2>";
		html += "<div class='dacura-json-viewer forward-json'>";
		html += JSON.stringify(this.inserts);
		html += "</div>";
		html += "<h2>Backward</h2>";
		html += "<div class='dacura-json-viewer backward-json'>";
		html += JSON.stringify(this.deletes);
		html += "</div>";
		if(this.changed){
			html += "<h2>After</h2>";
			html += this.changed.getHTML(mode);
		}
		if(this.original){
			html += "<h2>Before</h2>";
			html += this.original.getHTML(mode);
		}
	}
	return html;
}

/**
 * LDResult object - for interpreting responses from the dacura ld api...
 */
function LDResult(jsondr, pconfig){
	if(typeof jsondr == "undefined") {
		alert("LD result created without any result json initialisation data - not permitted!");
		return;
	}
	this.idprefix = pconfig.resultbox.substring(1);
	this.action = jsondr.action;
	this.status = jsondr.status;
	this.message = jsondr.message;
	this.test = typeof jsondr.test == "undefined" ? false : jsondr.test;
	this.errors = dacura.ld.parseRVOList(jsondr.errors);
	this.warnings = dacura.ld.parseRVOList(jsondr.warnings);
	this.result = false;
	if(typeof jsondr.result == 'object' &&  jsondr.result.type == "LDO"){
		this.result = new LDO(jsondr.result);
	}
	else if(typeof jsondr.result == 'object' &&  jsondr.result.type == "LDOUpdate"){
		this.result = new LDOUpdate(jsondr.result);
	}
	this.dqsgraph = typeof jsondr.graph_dqs == "object" ? new LDGraphResult(jsondr.graph_dqs, "triples", pconfig) : false;
	this.ldgraph = typeof jsondr.graph_ld == "object" ? new LDGraphResult(jsondr.graph_ld, "triples", pconfig) : false;
	this.metagraph = typeof jsondr.graph_meta == "object" ? new LDGraphResult(jsondr.graph_meta, "json", pconfig) : false;
	this.updategraph = typeof jsondr.graph_update == "object" ? new LDGraphResult(jsondr.graph_update, "ld", pconfig) : false;
	this.fragment_id = typeof jsondr.fragment_id == 'undefined' ? false : jsondr.fragment_id;
	this.pconfig = pconfig;
}

LDResult.prototype.show = function(rconfig){
	var mainmsg = this.getResultMessage();
	var errhtml = this.getErrorsHTML() + this.getWarningsHTML();
	if(mainmsg && errhtml){
		mainmsg += errhtml;
	}
	else if(errhtml){
		mainmsg = errhtml;
	}
	var extrahtml = this.hasExtraFields() ? this.getExtraHTML() : false;
	dacura.system.writeResultMessage(this.status, this.getResultTitle(), this.pconfig.resultbox, mainmsg, extrahtml, this.pconfig.mopts);
	if(this.hasExtraFields()){
		$(this.pconfig.resultbox + " .rb-options").buttonset();
		var self = this;
		$(this.pconfig.resultbox + " .roption").button().click(function(event){
			$(self.pconfig.resultbox + " .result-extra").hide();
			$(self.pconfig.resultbox + " .result-extra-" + this.id.substring(11)).show();				
		});	
	}
}

LDResult.prototype.getErrorsHTML = function(type){
	var html = "";
	if(this.hasErrors()){
		var errhtml = "";
		for(var i = 0; i < this.errors.length; i++){
			errhtml += this.errors[i].getHTML(type);
		}
		if(errhtml.length > 0){
			html = "<div class='api-error-details'>";
			html += "<h4>Errors</h4>"
			html += "<table class='rbtable dqs-error-table'>";
			var thead = "<thead><tr>" + "<th>Error</th><th>Message</th><th>Attributes</th></thead>";
			html += "<tbody>" + errhtml + "</tbody></table></div>";
		}	
	}
	return html;	
}

LDResult.prototype.getWarningsHTML = function(type){
	var html = "";
	if(this.hasWarnings()){
		var errhtml = "";
		for(var i = 0; i < this.warnings.length; i++){
			errhtml += this.warnings[i].getHTML(type);
		}
		if(errhtml.length > 0){
			html = "<div class='api-error-details'>";
			html += "<table class='rbtable dqs-warning-table'>"; 
			var thead = "<thead><tr>" + "<th>Error</th><th>Message</th><th>Attributes</th></thead>";
			html += "<tbody>" + errhtml + "</tbody></table></div>";
		}	
	}
	return html;	
}

LDResult.prototype.getExtraHTML = function(){
	if(!this.hasExtraFields()){
		return "";
	}
	var extras = this.getExtraFields();
	var headhtml = "<div class='ld-resultbox-options'><span class='rb-options'>";
	var bodyhtml = 	"<div class='ld-resultbox-content'>";
	var j = 0;
	var extras = this.getExtraFields();
	for(var i in extras){
		var sel = (j++ == 0) ? " checked" : "";
		dch = (sel == "" ? " dch" : "");
		headhtml += "<input type='radio' class='resoption roption'" + sel +" id='show_extra_" + i + "' name='result_extra_fields'><label class='resoption' title='" + extras[i].title + "' for='show_extra_" + i + "'>" + extras[i].title + "</label>";
		bodyhtml += "<div class='result-extra " + dch + " result-extra-" + i + "'>" + extras[i].content + "</div>";
	}
	headhtml += "</span></div>";
	bodyhtml += "</div>";
	return headhtml + bodyhtml;
}

LDResult.prototype.getResultHTML = function(){
	var html ="<div class='api-graph-testresults'>";
	if(this.status == "reject"){
		html += "<h2>" + this.result.meta.ldtype.ucfirst() + " " + this.result.id + " " + this.action.substring(0,6) + " rejected" + "</h2>";		
	}
	else {
		html += "<h2>" + this.result.meta.ldtype.ucfirst() + " " + this.result.id + " " + this.action.substring(0,6) + "d" + "</h2>";
	}
	if(this.test){
		html += "<P>" + dacura.ld.testResultMsg + "</P>";
	}
	html += this.result.getHTML() + "</div>";
	return html;
}

LDResult.prototype.hasExtraFields = function(){
	return (this.result || this.ldgraph || this.dqsgraph || this.metagraph || this.updategraph);
}

LDResult.prototype.getExtraFields = function(){
	var subs = {};
	if(!isEmpty(this.result)){
		subs["result"] = {title: 'Linked Data Object', content: this.getResultHTML()};
	}
	if(this.ldgraph){
		subs["ld"] = {title: 'Linked Data Object Updates', content: this.ldgraph.getHTML()};
	}
	if(this.dqsgraph ){
		subs['dqs'] = {title: 'DQS Triplestore Updates', content: this.dqsgraph.getHTML()};
	}
	if(this.metagraph ){
		subs['meta'] = {title: 'Metadata Updates', content: this.metagraph.getHTML()};
	}
	if(this.updategraph ){
		subs["update"] = {title: 'Update Graph Updates', content: this.updategraph.getHTML()};
	}
	return subs;
}


/**
 * @summary generates the result box title text
 */
LDResult.prototype.getResultTitle = function(rconfig){
	tit = "";//this.action.ucfirst() + " - "; 
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		tit += this.message.title;
	}
	else if(this.message){
		tit += this.message;
	}
	else if(this.status == "reject"){
		tit += " Failed. ";
	}
	else if(this.status == "pending"){
		tit += (this.test) ? " Requires approval. " : " Accepted: awaiting approval. ";
	}
	else if(this.status == "accept"){
		tit += (this.test) ? " Approved. " : " Accepted and published. ";
	}
	return tit;
};

LDResult.prototype.hasWarnings = function(){
	return this.warnings && this.warnings.length > 0;
};

LDResult.prototype.hasErrors = function(){
	return this.errors && this.errors.length > 0;
};

/**
 * @summary gets the text to populate the body of the message box
 */
LDResult.prototype.getResultMessage = function(rconfig){
	var msg = false;
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : false;
	}
	//else if(typeof(this.message) == "string") {
	//	msg = this.message;
	//}
	return msg;
};


function LDGraphResult(jsondr, graphtype, pconfig){
	this.graphtype = graphtype;
	this.tests = typeof jsondr.tests == "undefined" ? false : jsondr.tests;
	this.imports = typeof jsondr.imports == "undefined" ? false : jsondr.imports;
	this.inserts = typeof jsondr.inserts == "undefined" ? false : jsondr.inserts;
	this.deletes = typeof jsondr.deletes == "undefined" ? false : jsondr.deletes;
	this.action = jsondr.action;
	this.status = jsondr.status;
	this.message = jsondr.message;
	this.test = typeof jsondr.test == "undefined" ? false : jsondr.test;
	this.errors = dacura.ld.parseRVOList(jsondr.errors);
	this.warnings = dacura.ld.parseRVOList(jsondr.warnings);
	this.pconfig = pconfig;
	this.hypotethical = jsondr.hypotethical;
}

LDGraphResult.prototype.getResultTitle = function(){
	if(typeof this.message == "object" && typeof this.message.title != "undefined"){
		return this.message.title;
	}
	return this.action;
};

LDGraphResult.prototype.getResultMessage = function(){
	var msg = "";
	if(typeof(this.message) == "object"){
		msg = typeof this.message.body != "undefined" ? this.message.body : false;
	}
	else if(typeof(this.message) == "string") {
		msg = this.message;
	}
	if(!this.isEmpty()){
		if(this.hypotethical){
			msg += "<P>" + dacura.ld.hypoResultMsg + "</P>";
		}
		else if(this.test){
			msg += "<P>" + dacura.ld.testResultMsg + "</P>";
		}
	}
	if(this.tests !== false){
		if(typeof this.tests == "object"){
			if(isEmpty(this.tests)){
				msg += "<p>No tests configured (schema free publishing)</p>";
			}
			else {
				msg += "<p>Tests configured: " + this.tests.join(", ") + "</p>";
			}
		}
		else {
			msg += "<P>" + this.tests.ucfirst() + " tests configured</p>";
		}
	}
	return msg;
}

LDGraphResult.prototype.getDQSConfigPage = function(dqs, current){
	var html = "<div class='dqsconfig'><div class='dqs-all-config-element'>";
	html += "<input type='radio' id='dqs-radio-all' name='dqsall' value='all' ";
	if(current == "all"){
		html += " checked";
	}
	html += "><label for='dqs-radio-all'>All Tests</label>";
	html += "<input type='radio' id='dqs-radio-notall' name='dqsall' value='notall'";
	if(typeof current == 'object' && current.length > 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-notall'>Choose Tests</label>"
	html += "<input type='radio' id='dqs-radio-none' name='dqsall' value='none'";
	if(current.length == 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-none'>No Tests</label>";
	html += "</div>";
	var includes = [];
	var available = [];
	if(current == "all"){
		for(var i in dqs){
			includes.push(dacura.ld.getDQSHTML(i, dqs[i], "implicit"));
		}
	}
	else if(current.length == 0){
		for(var i in dqs){
			available.push(dacura.ld.getDQSHTML(i, dqs[i], "add"));
		}
	}
	else {
		for(var i in dqs){
			if(current.indexOf(i) == -1){
				available.push(dacura.ld.getDQSHTML(i, dqs[i], "add"));				
			}
			else {
				includes.push(dacura.ld.getDQSHTML(i, dqs[i], "remove"));				
			}
		}	
	}
	html += "<div class='dqs-includes'>" + includes.join(" ") + "</div>";
	html += "<div class='dqs-available'>" + available.join(" ") + "</div>";
	return html;
}


LDGraphResult.prototype.getImportsSummary = function(simports){
	var html = "";
	for(var i in this.imports){
		var url = dacura.system.install_url;
		url += (this.imports[i].collection == "all") ? "" : this.imports[i].collection;
		url += "/ontology/" + this.imports[i].id;
		html += dacura.ld.getOntologyViewHTML(i, url, null, this.imports[i].version);
	}
	return html;
};

LDGraphResult.prototype.getResultHeadlineHTML = function(){
	var html = "<span class='dqsresulticon'>";
	if(this.status == "accept"){
		html += dacura.system.getIcon("accept");
		html += "</span> <span class='dqsresulttext'>Passed</span>";
	}
	else {
		html += dacura.system.getIcon("reject");	
		html += "</span> <span class='dqsresulttext'>Failed</span>";
	}
	return html;
}
LDGraphResult.prototype.getResultSummaryHTML = function(){
	var html = "<div class='dqsresult'>";
	html += this.getResultHeadlineHTML();
	if(this.hasErrors()){
		html += "<span class='dqserrors'>";
		html += dacura.system.getIcon("error") + this.errors.length + " problem";
		if(this.warnings.length != 1) html += "s";
		html += "</span>";
	}
	if(this.hasWarnings()){
		html += "<span class='dqswarnings'>";
		html += dacura.system.getIcon("warning") + this.warnings.length + " warning";
		if(this.warnings.length != 1) html += "s";
		html += "</span>";
	}
	html += "</div>";
	return html;
}
LDGraphResult.prototype.getErrorsHTML = LDResult.prototype.getErrorsHTML;
LDGraphResult.prototype.getWarningsHTML = LDResult.prototype.getWarningsHTML;
LDGraphResult.prototype.hasWarnings = LDResult.prototype.hasWarnings;
LDGraphResult.prototype.hasErrors = LDResult.prototype.hasErrors;

LDGraphResult.prototype.isEmpty = function(){
	return !(this.inserts || this.deletes);
}

LDGraphResult.prototype.getErrorsSummary = function(){
	return summariseRVOList(this.errors)
}
LDGraphResult.prototype.getWarningsSummary = function(){
	return summariseRVOList(this.warnings)
}

LDGraphResult.prototype.getHTML = function(){
	var msg = this.getResultMessage();
	var title = this.getResultTitle();
	var html ="<div class='api-graph-testresults'>";
	html += "<h2>" + title + "</h2>";
	if(msg){
		html += msg;
	}
	if(this.hasErrors()){
		html += this.getErrorsHTML();
	}
	if(this.hasWarnings()){
		html += this.getWarningsHTML();
	}
	if(!this.isEmpty()){
		if(this.graphtype == 'triples'){
			if(this.inserts && this.inserts.length > 0){
				html += dacura.ld.getTripleTableHTML(this.inserts, "Quads Inserted", true); 
			}
			if(this.deletes && this.deletes.length > 0){
				html += dacura.ld.getTripleTableHTML(this.deletes, "Quads Deleted", true); 
			}
		}
		else {
			html += dacura.ld.getJSONViewHTML(this.inserts, this.deletes);
		}
	}
	html += "</div>";
	return html;
};

function RVO(data){
	if(typeof data != "object"){
		alert("not object");
		return;
	}
	this.best_practice = data.best_practice;
	this.cls = data.cls;
	this.message = data.message;
	this.info = data.info;
	this.subject = data.subject;
	this.predicate = data.predicate;
	this.object = data.object;
	this.property = data.property;
	this.element = data.element;
	this.label = data.label;
	this.comment = data.comment;
	this.path = data.path;
	this.constraintType = data.constraintType;
	this.cardinality = data.cardinality;
	this.value = data.value;
	this.qualifiedOn = data.qualifiedOn;
	this.parentProperty = data.parentProperty;
	this.parentDomain = data.parentDomain;
	this.domain = data.domain;
	this.range = data.range;
	this.parentRange = data.parentRange;
	this.parentProperty = data.parentProperty;	
}

RVO.prototype.getLabel = function(mode){
	return this.label;
}

RVO.prototype.getLabelCls = function(mode){
	if(this.best_practice){
		return "dqs-bp";
	}
	return "dqs-rule";
}

RVO.prototype.getLabelTitle = function(mode){
	return this.label + " " + this.comment;
}

RVO.prototype.getHTML = function(type){
	return "<tr><td title='" + this.comment + "'>"+this.label+"</td><td>"+this.message +"</td><td>" + this.info + "</td><td class='rawjson'>" + JSON.stringify(this.getAttributes(), 0, 4) + "</td></tr>";
}

function summariseRVOList(rvolist){
	if(rvolist.length == 1) return this.label;
	var entries = [];
	var bytype = {};
	for(var i = 0; i < rvolist.length; i++){
		if(typeof bytype[rvolist[i].cls] == "undefined"){
			bytype[rvolist[i].cls] = [];			
		}
		bytype[rvolist[i].cls].push(rvolist[i]);
	}
	for(var j in bytype){
		if(bytype[j].length == 1){
			entries.push("1 " + bytype[j][0].label); 
		}
		else {
			entries.push(bytype[j].length + " " + bytype[j][0].label + "s"); 	
		}
	}
	return entries.join(", ");
}


RVO.prototype.getAttributes = function(){
	var atts = {};
	if(this.subject) atts.subject = this.subject;
	if(this.predicate) atts.predicate = this.predicate;
	if(this.object) atts.object = this.object;
	if(this.property) atts.property = this.property;
	if(this.element) atts.element = this.element;
	//if(this.label) atts.label = this.label;
	//if(this.comment) atts.comment = this.comment;
	if(this.path) atts.path = this.path;
	if(this.constraintType) atts.constraintType = this.constraintType;
	if(this.cardinality) atts.cardinality = this.cardinality;
	if(this.value) atts.value = this.value;
	if(this.qualifiedOn) atts.qualifiedOn = this.qualifiedOn;
	if(this.parentProperty) atts.parentProperty = this.parentProperty;
	if(this.parentDomain) atts.parentDomain = this.parentDomain;
	if(this.domain) atts.domain = this.domain;
	if(this.range) atts.range = this.range; 
	if(this.parentRange) atts.parentRange = this.parentRange; 
	if(this.parentProperty) atts.parentProperty = this.parentProperty;
	return atts;
}

function OntologyImporter(orig, available, autos, mode, ucallback){
	this.available_imports = available;
	this.automatic_imports = autos;
	this.updatecallback = ucallback;
	if(typeof orig != "object" || this.importListsMatch(orig, autos)){
		this.orig = autos;
		this.isauto = true; 
		this.orig_isauto = true;
	}
	else {
		this.orig = orig;
		this.orig_isauto = false;
		this.isauto = false;
	}
	this.current = $.extend(true, {}, this.orig); 
	this.mode = typeof mode == "undefined" ? "view" : mode;
	if(this.mode == "graph"){
		this.mode = 'edit';
		this.ldtype = 'graph';
		this.isauto = false;
		this.orig_isauto = false;		
	}
	else {
		this.ldtype = "ontology";
	}
}

OntologyImporter.prototype.setManual = function(){
	if(this.isauto){
		this.isauto = false;
		this.registerImportEvent();
		this.refresh();
	}
}

OntologyImporter.prototype.setAuto = function(){
	if(!this.isauto){
		this.isauto = true;
		this.registerImportEvent();
		this.current = $.extend(true, {}, this.automatic_imports ) 
		this.refresh();
	}
}

OntologyImporter.prototype.changeOntologyVersion = function(ontid, newv){
	this.current[ontid].version = newv;
	this.registerImportEvent();	
}


OntologyImporter.prototype.testUpdate = function(){
	if(this.hasStateChange()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, true);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes - test");
	}
}

OntologyImporter.prototype.Update = function(){
	if(this.hasStateChange()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, false);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes");
	}
}


OntologyImporter.prototype.hasStateChange = function(){
	if(this.isauto != this.orig_isauto) return true;
	return !this.importListsMatch(this.orig, this.current);
}

OntologyImporter.prototype.registerImportEvent = function(){
	if(this.hasStateChange()){
		$('#update-imports').show();
		$('#enable-update-imports').hide();
		if(this.ldtype == "graph"){
			$('#graph-update').hide();
		}
	}
	else {
		$('#update-imports').hide();
		$('#enable-update-imports').show();
	}
}

OntologyImporter.prototype.importListsMatch = function(a, b){
	for(var k in a){
		if(typeof b[k] != "object"){
			return false;
		}
		for(var j in a[k]){
			if(typeof b[k][j] == "undefined" || b[k][j] != a[k][j]){
				return false;
			}
		} 
	}
	for(var k in b){
		if(typeof a[k] != "object"){
			return false;
		}
		for(var j in b[k]){
			if(typeof a[k][j] == "undefined"){
				return false;
			}
		} 
	}
	return true;	
}

OntologyImporter.prototype.refresh = function(){
	$('.manual-imports').html("");
	$('.auto-imports').html("");
	$('.system-ontologies-contents').html("");
	$('.local-ontologies-contents').html("");
	if(size(this.current) > 0){
		for(var k in this.current){
			var tit = this.getOntHTMLTitle(k, this.current[k].version);
			if(typeof this.available_imports[k] != "undefined"){
				if(this.mode == "edit"){
					$('.manual-imports').append(dacura.ld.getOntologySelectHTML(k, tit, this.current[k].version, this.available_imports[k].version));
				}
				else {
					$('.manual-imports').append(dacura.ld.getOntologyViewHTML(k, tit, null, this.current[k].version));			
				} 						
			}
			else {
				alert(k + " ontology is imported but unknown");
				$('.manual-imports').append(dacura.ld.getOntologySelectHTML(k, tit, this.current[k].version)); 				
			}
		}
	}
	else {
		$('.manual-imports').html(this.getEmptyImportsHTML());	
	}
	if(size(this.automatic_imports) > 0){
		for(var k in this.automatic_imports){
			var tit = this.getOntHTMLTitle(k, this.automatic_imports[k].version);
			if(typeof this.available_imports[k] != "undefined"){
				$('.auto-imports').append(dacura.ld.getOntologyViewHTML(k, tit, null, this.available_imports[k].version)); 						
			}		
			else {
				alert(k + " ontology is imported but unknown");
				$('.auto-imports').append(dacura.ld.getOntologyViewHTML(k, tit, null, this.available_imports[k].version)); 				
			}
		}
	}
	else {
		$('.auto-imports').html(this.getEmptyImportsHTML());
	}
	var lonts = {};
	var sonts = {};
	for(var k in this.available_imports){
		if(typeof this.current[k] == "undefined"){
			if(this.available_imports[k].collection == "all"){
				sonts[k] = this.available_imports[k];
			}
			else {
				lonts[k] = this.available_imports[k];
			}
		}		
	}
	for(var k in lonts){
		if(this.mode == 'edit' && !this.isauto){
			$('.local-ontologies-contents').append(dacura.ld.getOntologySelectHTML(k, this.getOntHTMLTitle(k)));
		}
		else {
			$('.local-ontologies-contents').append(dacura.ld.getOntologyViewHTML(k, this.getOntHTMLTitle(k), null, lonts[k].version));
		}
	}
	for(var k in sonts){
		if(this.mode == 'edit' && !this.isauto){
			$('.system-ontologies-contents').append(dacura.ld.getOntologySelectHTML(k, this.getOntHTMLTitle(k)));
		}
		else {
			$('.system-ontologies-contents').append(dacura.ld.getOntologyViewHTML(k, this.getOntHTMLTitle(k), null, sonts[k].version));
		}
	}
	if(size(lonts) > 0 && this.mode == "edit"){
		$('.local-ontologies').show();	
	}
	else {
		$('.local-ontologies').hide();
	}
	if(this.mode != "edit"){
		$('#update-imports').hide();
	}
	else if(this.hasStateChange()){
		$('#update-imports').show();
	}
	if(size(sonts) > 0 && this.mode == "edit"){
		$('.system-ontologies').show();		
	}
	else {
		$('.system-ontologies').hide();
	}
	this.setImportsVisibility();
	var self = this;
	if(!this.isauto && this.mode == "edit"){
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
			self.add(this.id.substring(13));
		});
		$('span.remove-ont').click(function(){
			self.remove(this.id.substring(16));
		});
		$('select.imported_ontology_version').selectmenu({
			width: 100,
			change: function( event, ui ) {
				self.changeOntologyVersion(this.id.substring(26), this.value);
			}
		});
	}
}

OntologyImporter.prototype.setImportsVisibility = function(){
	if(this.isauto){
		$('.auto-imports').show();
		$('.auto-imports-header').show();
		$('.manual-imports').hide();
		$('.manual-imports-header').hide();
	}
	else if(this.ldtype == "graph"){
		if(size(this.automatic_imports) > 0){
			$('.auto-imports').show();
			$('.auto-imports-header').show();
		}
		else {
			$('.auto-imports-header').hide();
			$('.auto-imports').hide();		
		}
		$('.manual-imports').show();
		$('.manual-imports-header').show();
		//$('#update-imports').show();		
	}
	else {
		$('.auto-imports-header').hide();
		$('.manual-imports-header').show();
		$('.manual-imports').show();
		$('.auto-imports').hide();	
	}
}

OntologyImporter.prototype.getEmptyImportsHTML = function(){
	var html = "<div class='empty-imports'>No ontologies imported</div>";
	return html;
}

OntologyImporter.prototype.setViewMode = function(){
	$('#enable-update-imports').prop("checked", false);
	if(this.mode != "view"){
		$('#enable-update-imports').button('option', 'label', 'Update Configuration');
		$('#enable-update-imports').button("refresh");		
		if(this.ldtype == "ontology"){
			$('#imaa').buttonset("disable");
		}
		else {
			$('#graph-update').show();			
		}
		this.mode = "view";
		this.refresh();
	}
}

OntologyImporter.prototype.setEditMode = function(){
	$('#enable-update-imports').prop("checked", true);
	if(this.mode != "edit"){
	    $('#enable-update-imports').button('option', 'label', 'Cancel Update');
		$('#enable-update-imports').button("refresh");			    
		if(this.ldtype != "graph"){
			$('#imaa').buttonset("enable");
		}
		this.mode = "edit";
		if(this.isauto){
			this.isauto = false;
			$('#imports-set-manual').prop("checked", true);
			$('#imaa').buttonset("refresh");			
		}		
		this.refresh();
	}
}

OntologyImporter.prototype.getButtonsHTML = function(){
	var html = "<div id='update-imports' class='subscreen-buttons dch'>";
	html += "<button id='cancelupdateimport' class='dacura-update-cancel subscreen-button'>Cancel Changes</button>";		
	html += "<button id='testupdateimport' class='dacura-test-update subscreen-button'>Test New Import Configuration</button>";		
	html += "<button id='updateimport' class='dacura-update subscreen-button'>Save New Import Configuration</button>";
	html += "</div>";	
	if(this.ldtype == "graph"){
		html += "<div id='graph-update' class='subscreen-buttons'>";
		html += "<input type='checkbox' class='enable-update imports-enable-update' id='enable-update-imports'>";
		html += "<label for='enable-update-imports'>Update Configuration</label>";
		html += "</div>";
	}
	return html;
}

OntologyImporter.prototype.draw = function(target){
	var html = "<div class='manual-imports-header'>Imported Ontologies (selected by user)</div>";
	html += "<div class='manual-imports'></div>";
	html += "<div class='auto-imports-header'>Imported Ontologies (automatically calculated by Dacura)</div>";
	html += "<div class='auto-imports'></div>";
	html += this.getButtonsHTML();
	html += "<div class='local-ontologies'>";
	html += "<div class='local-ontologies-header'>Available Local Ontologies</div>";
	html += "<div class='local-ontologies-contents'>";
	html += "</div></div>";
	html += "<div class='system-ontologies'>";
	html += "<div class='system-ontologies-header'>Available System Ontologies</div>";
	html += "<div class='system-ontologies-contents'>";
	html += "</div></div>";
	html += "</div>";
	$(target).html(html);
	var self = this;
	$('#cancelupdateimport').button().click( function(){
		self.cancelUpdate();
	});
	$('#testupdateimport').button().click( function(){
		self.testUpdate();
	});	
	$('#updateimport').button().click( function(){
		self.Update();
	});	
	if(this.ldtype == "ontology"){	
		if(this.isauto){
			$('#imports-set-automatic').prop("checked", true);
		}
		else {
			$('#imports-set-manual').prop("checked", true);
		}
	}
	this.initUpdateButton();
	this.refresh();	
}

OntologyImporter.prototype.initUpdateButton = function(){
	var txt = "Update Configuration";
	if(this.mode == 'edit'){
		txt = "Cancel Update";
		$('#enable-update-imports').prop("checked", true);
	}
	else {
		$('#enable-update-imports').prop("checked", false);
	}
	var self = this;
	$('#enable-update-imports').button({
		label: txt
	}).click(function(){
		if($('#enable-update-imports').is(':checked')){
			self.setEditMode();
		}
		else {
			self.setViewMode();		
		}
	});
}


OntologyImporter.prototype.cancelUpdate = function(){
	this.current = $.extend(true, {}, this.orig); 
	this.setViewMode();
	$('#imports-set-manual').prop("checked", true);
	
}

OntologyImporter.prototype.remove = function(ontid){
	//this.current.splice(ontid, 1);
	delete(this.current[ontid]);
	if(isEmpty(this.current)){
		$('.manual-imports').html(this.getEmptyImportsHTML());		
	}
	this.registerImportEvent();	
	$("#imported_ontology_" + ontid).remove();
	
	if(typeof this.available_imports[ontid] != "undefined"){
		var avont = this.available_imports[ontid];
		if(avont.collection == "all"){
			$('div.system-ontologies-contents').prepend(dacura.ld.getOntologySelectHTML(ontid, this.getOntHTMLTitle(ontid)));
			$('div.system-ontologies').show();
		}
		else {
			$('div.local-ontologies-contents').prepend(dacura.ld.getOntologySelectHTML(ontid, this.getOntHTMLTitle(ontid)));		
			$('div.local-ontologies').show();
		}
		var self = this;
		$("#add_ontology_" + ontid).hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			self.add(this.id.substring(13));
		});
	}	
}


OntologyImporter.prototype.add = function(ontid){
	var avont = this.available_imports[ontid];
	if(isEmpty(this.current)){
		$('.manual-imports').html("");	
		$('.auto-imports').html("");	
	}
	this.current[ontid] = {id: ontid, version: 0, collection: avont.collection};
	this.registerImportEvent();
	var tit = this.getOntHTMLTitle(ontid, 0);
	$("div.manual-imports").append(dacura.ld.getOntologySelectHTML(ontid, tit, 0, avont.version));
	var self = this;
	$("#remove_ontology_" + ontid).hover(function(){
		$(this).addClass('uhover');
	}, function() {
	    $(this).removeClass('uhover');
	}).click(function(){
		self.remove(this.id.substring(16));
	});
	$('#imported_ontology_version_'+ontid).selectmenu({
		width: 100,
		change: function( event, ui ) {
			self.changeOntologyVersion(ontid, this.value);
		}
	});
	$("#add_ontology_" + ontid).remove();
	if(!$("div.system-ontologies-contents span").length){
		$("div.system-ontologies").hide();
	}
	if(!$("div.local-ontologies-contents span").length){
		$("div.local-ontologies").hide();
	}
}

OntologyImporter.prototype.getOntHTMLTitle = function(ontid, overridev){
	var ontv = this.available_imports[ontid];
	if(typeof ontv == "undefined"){
		alert("unidentified ontology " + ontid);
		return "Unknown ontology " + ontid;
	}
	var tit = ontv.id;
	if(typeof ontv.title == "string" && ontv.title){
		tit += ": " + ontv.title;
	}
	var html = "<div class='tooltiph'>" + tit + "</div>";
	var v = typeof overridev == "undefined" ? ontv.version : overridev;
	
	html += "<span class='oht'>" + (v == 0 ? "Latest Version (" + ontv.version + ")" : "Version: " + v) + "</span>"; 
	html += " <span class='oht'>Collection: " + ontv.collection + "</span>"; 
	html += " <span class='oht'>URL: " + ontv.url + "</span>"; 
	return escapeHtml(html);
}

function DQSConfigurator(saved, def, avs, mode, ucallback){
	this.updatecallback = ucallback;
	this.mode = typeof mode == "undefined" ? "view" : mode;
	this.def = [];
	if(typeof def == 'object'){
		for(var i in def){
			this.def.push(i);
		}
	}
	else {
		this.def = def;
	}
	this.dqs = avs;
	if(mode == "graph"){
		this.ldtype = "graph";
		this.mode = "view";
	}
	else {
		this.ldtype = "ontology";
	}
	this.saved = typeof saved == "object" ? saved : false;
	if(this.saved){
		this.original = this.saved;
		if(typeof this.saved == 'object'){
			this.current = $.extend(true, [], this.saved) 
		}
		else {
			this.current = this.saved;
		}
	}
	else {
		this.original = this.def;
		if(typeof this.def == 'object'){
			this.current = $.extend(true, [], this.def) 
		}
		else {
			this.current = this.def;
		}
	}
	this.isauto = this.saved ? false : true;
	if(this.isauto){
		this.original_isauto = true;
	}
	else {
		this.original_isauto = false;
	}
}

DQSConfigurator.prototype.testUpdate = function(){
	if(this.hasChanged()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, true);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes");
	}
}

DQSConfigurator.prototype.Update = function(){
	if(this.hasChanged()){
		if(typeof this.updatecallback == "function"){
			this.updatecallback(this.current, this.isauto, false);
		}
		else {		
			jpr(this.current);
		}
	}
	else {
		alert("No changes");
	}
}


DQSConfigurator.prototype.hasChanged = function(){
	if(this.isauto != this.original_isauto) return true;
	if(typeof this.current != typeof this.original) return true;
	if(this.current == "all" && this.original == "all") return false;
	for(var i = 0; i < this.current.length; i++){
		if(this.original.indexOf(this.current[i]) == -1) return true;
	}
	for(var i = 0; i < this.original.length; i++){
		if(this.current.indexOf(this.original[i]) == -1) return true;
	}
	return false;
}

DQSConfigurator.prototype.setEditMode = function(){
	if(this.mode != "edit"){
		$('#enable-update-tests').prop("checked", true);
	    $('#enable-update-tests').button('option', 'label', 'Cancel Update');
		$('#enable-update-tests').button("refresh");			    
		this.mode = "edit";
		$('#tmaa').buttonset("enable");
		if(this.isauto){
			this.isauto = false;
			$('#tests-set-manual').prop("checked", true);
			$('#tmaa').buttonset("refresh");			
		}
		this.refresh();
	}
}



DQSConfigurator.prototype.setViewMode = function(){
	$('#enable-update-tests').prop("checked", false);
	if(this.mode == "edit"){
		$('#enable-update-tests').button('option', 'label', 'Update Configuration');
		$('#enable-update-tests').button("refresh");
		$('#tmaa').buttonset("disable");
		if(this.ldtype == "graph"){
			$('#graph-tests-update').show();			
		}
		this.mode = "view";
		this.refresh();
	}
}

DQSConfigurator.prototype.setAuto = function(){
	if(!this.isauto){
		this.isauto = true;
		if(typeof this.def == 'object'){
			this.current = $.extend(true, [], this.def) 
		}
		else {
			this.current = this.def;
		}
		this.refresh();
	}
}

DQSConfigurator.prototype.setManual = function(){
	if(this.isauto){
		this.isauto = false;
		this.refresh();
	}
}

DQSConfigurator.prototype.getTestsSummary = function(){
	var html = "<span class='dqs-summary'>";
	if(this.current == 'all'){
		html += "all</span><span class='implicit-dqs'>";
		for(var i in this.dqs){
			html += dacura.ld.getDQSHTML(i, this.dqs[i], "view");
		}
	}
	else if(isEmpty(this.current)){
		html += "No tests configured";
	}
	else {
		for(var i = 0; i<this.current.length; i++){
			html += dacura.ld.getDQSHTML(this.current[i], this.dqs[this.current[i]], "view");	
		}
	}
	html += "</span>";
	return html;
}

DQSConfigurator.prototype.getChooseAllHTML = function(){
	var html = "<input type='radio' id='dqs-radio-all' name='dqsall' value='all'";
	if(this.current == "all"){
		html += " checked";
	}
	html += "><label for='dqs-radio-all'>All Tests</label>";
	html += "<input type='radio' id='dqs-radio-none' name='dqsall' value='none'";
	if(this.current.length == 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-none'>No Tests</label>";
	html += "<input type='radio' id='dqs-radio-notall' name='dqsall' value='notall' ";
	if(typeof this.current == 'object' && this.current.length > 0){
		html += " checked";
	}
	html += "><label for='dqs-radio-notall'>Choose Tests</label>";
	return html;
}

DQSConfigurator.prototype.refresh = function(){
	var includes = [];
	var available = [];
	if(this.current == "all"){
		for(var i in this.dqs){
			includes.push(dacura.ld.getDQSHTML(i, this.dqs[i], "implicit"));
		}
	}
	else if(this.current.length == 0){
		var x = (this.mode == "edit" && !this.isauto ? "add": "tile");
		for(var i in this.dqs){
			available.push(dacura.ld.getDQSHTML(i, this.dqs[i], x));
		}
	}
	else {
		for(var i in this.dqs){
			if(this.current.indexOf(i) == -1){
				var x = (this.mode == "edit" && !this.isauto ? "add": "tile");
				available.push(dacura.ld.getDQSHTML(i, this.dqs[i], x));				
			}
			else {
				var x = (this.mode == "edit" && !this.isauto ? "remove": "tile");
				includes.push(dacura.ld.getDQSHTML(i, this.dqs[i], x));				
			}
		}	
	}
	if(includes.length > 0){
		$('.dqs-includes').html(includes.join(" "));
	}
	else {
		$('.dqs-includes').html(this.getEmptyTestsHTML());
	}
	if(available.length > 0){
		$('.dqs-available').html(available.join(" "));
	}
	else {
		$('.dqs-available').html(this.getEmptyTestsHTML());
	}
	$('.dqs-all-config-element').html(this.getChooseAllHTML(this.mode));	
	var self = this;
	$('.dqs-all-config-element').buttonset().click(function(){
		if($('.dqs-all-config-element input:checked').val() == "all"){
			if(self.current != "all"){
				self.current = "all";
				self.refresh();
			}			
		}
		else if($('.dqs-all-config-element input:checked').val() == "none"){
			if(typeof self.current != "object" || self.current.length > 0){
				self.current = [];
				self.refresh();
			}					
		}
		else {
			if(typeof self.current != "object" && self.current == "all"){
				self.current = self.getAllTestsArray();
				self.refresh();
			}
		}	
			
	});
	if(this.mode == "view" || this.isauto){
		$('.dqs-all-config-element').buttonset("disable");
	}
	else {
		$('.dqstile-add').hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			self.add(this.id.substring(8));
		});
		$('.remove-dqs').hover(function(){
			$(this).addClass('uhover');
		}, function() {
		    $(this).removeClass('uhover');
		}).click(function(){
			self.remove(this.id.substring(11));
		});
	}
	if(this.mode == "edit" && this.hasChanged()){
		$('#dqs-buttons').show();
		$('#graph-tests-update').hide();
	}
	else {
		$('#dqs-buttons').hide();		
		$('#graph-tests-update').show();
	}
}

DQSConfigurator.prototype.cancelUpdate = function(){
	if(typeof this.original == 'object'){
		this.current = $.extend(true, [], this.original); 
	}
	else {
		this.current = this.original;
	}
	this.isauto = this.original_isauto 
	this.setViewMode();
}

DQSConfigurator.prototype.add = function(id){
	if(typeof this.current == "object" && this.current.indexOf(id) == -1){
		this.current.push(id);
		this.refresh();
	}
} 

DQSConfigurator.prototype.remove = function(id){
	if(typeof this.current == "object" && this.current.indexOf(id) != -1){
		this.current.splice(this.current.indexOf(id), 1);
		this.refresh();
	}
	else {
		alert("failed with " + id);
	}
}

DQSConfigurator.prototype.getEmptyTestsHTML = function(){
	var html = "<div class='empty-tests'>No DQS tests configured</div>";
	return html;
}

DQSConfigurator.prototype.getAllTestsArray = function(){
	var allt = [];
	for(var k in this.dqs){
		allt.push(k);
	}
	return allt;
}

DQSConfigurator.prototype.getButtonsHTML = function (){
	var html = "<div id='dqs-buttons' class='subscreen-buttons dch'>";
	html += "<button id='cancelupdatetests' class='dacura-update-cancel subscreen-button'>Cancel Changes</button>";		
	html += "<button id='testupdatetests' class='dacura-test-update subscreen-button'>Test DQS Configuration</button>";		
	html += "<button id='updatetests' class='dacura-update subscreen-button'>Save DQS Configuration</button>";
	html += "</div>";	
	if(this.ldtype == "graph"){
		html += "<span id='tmaa' class='manauto testsmanauto'>";
		html +=	"<input type='radio' name='tma' id='tests-set-manual' value='manual'><label for='tests-set-manual'>Manual</label>";
		html += "<input type='radio' name='tma' id='tests-set-automatic' value='automatic'><label for='tests-set-automatic'>Automatic</label>";
		html += "</span>";		
		html += "<div id='graph-tests-update' class='subscreen-buttons'>";
		html += "<input type='checkbox' class='enable-update tests-enable-update' id='enable-update-tests'>";
		html += "<label for='enable-update-tests'>Update Configuration</label>";
		html += "</div>";		
	}
	return html;
}


DQSConfigurator.prototype.draw = function(jq, mode, dontfill){
	if(typeof mode != "undefined"){
		this.mode = mode; 
	}
	var html = "<div class='dqsconfig'>";
	html += "<div class='dqs-all-config-element'></div>";
	html += "<div class='dqs-includes-title'>Currently Included Tests</div>";
	html += "<div class='dqs-includes'></div>";
	html += this.getButtonsHTML();
	html += "<div class='dqs-available-title'>Available Tests</div>";
	html += "<div class='dqs-available'></div>";
	html += "</div>";
	$(jq).html(html);
	var self = this;
	$('#cancelupdatetests').button().click( function(){
		self.cancelUpdate();
	});
	$('#testupdatetests').button().click( function(){
		self.testUpdate();
	});	
	$('#updatetests').button().click( function(){
		self.Update();
	});		
	if(this.isauto){
		$('#tests-set-automatic').prop("checked", true);
	}
	else {
		$('#tests-set-manual').prop("checked", true);
	}
	if(this.mode == 'edit'){
		$('#enable-update-tests').prop("checked", true);
	}
	else {
		$('#enable-update-tests').prop("checked", false);
	}
	this.initUpdateButton();
	if(typeof dontfill == "undefined"){	
		this.refresh();
	}
}

DQSConfigurator.prototype.initUpdateButton = function(){
	var txt = "Update Configuration";
	if(this.mode == 'edit'){
		txt = "Cancel Update";
		$('#enable-update-tests').prop("checked", true);
	}
	else {
		$('#enable-update-tests').prop("checked", false);
	}
	var self = this;
	$('#enable-update-tests').button({
		label: txt
	}).click(function(){
		if($('#enable-update-tests').is(':checked')){
			self.setEditMode();
		}
		else {
			self.setViewMode();		
		}
	});
	$('#tmaa').buttonset().click(function(){
		if($('input[name=tma]:checked').val() == "manual"){
			self.setManual();
		}
		else {
			self.setAuto();		
		}
	});	
	$('#tmaa').buttonset("disable");	
}

function wrapIPFieldInDacuraForm(l, d, h){
	var html = '<table class="dacura-property-table dacura-edit-table  property-table-top property-table-odd property-table-level-1">';
	html += '<thead></thead><tbody><tr class="dacura-property-spacer"></tr>';
	html += '<tr class="dacura-property first-row row-1 last-row">';
	html += '<td class="dacura-property-label">' + l + '</td><td class="dacura-property-value">';
	html += '<table class="dacura-property-value-bundle"><tbody><tr><td class="dacura-property-input">';
	html +=  d + '</td><td class="dacura-property-help">' + h + '</td></tr></tbody></table></td></tr>'
	html += '<tr class="dacura-property-spacer"></tr></tbody></table>';
	return html;	
}
