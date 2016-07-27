/**
 * @file Javascript object for viewing linked data object updates returned in responses by the Dacura API
 * @author Chekov
 * @license GPL V2
 */

/**
 * @constructor 
 * @param ldou {Ojbect} ldo update json object returned by api
 * @param pconf {DacuraPageConfig} page config object
 * @param vconf {Object} viewer config object
 */
function LDOUpdateViewer(ldou, pconf, vconf){
	this.ldo = ldou;
	this.pconf = pconf;
	this.emode = "view";
	this.target = "";
	this.before_target = "";
	this.after_target = "";
	this.commands_target = "";
	this.prefix = "";
	if(typeof vconf == "object"){
		this.init(vconf);
	}
}

/**
 * Generates the html to represent the options bar on top of the viewer
 * @returns {String} html
 */
LDOUpdateViewer.prototype.showOptionsBar = function(){
	var html = "<div class='update-view-bar update-bar'><span class='view-update-formats'>";
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
	html += "</span>";
	if(this.view_options){
		html += "<span class='view-update-options'>";
		for(var i in this.view_options){
			html += "<input type='checkbox' class='ld-control ld-bar-option' id='" + this.prefix + "-option-" + i + "' ";
			if(this.ldo.options[i] == 1){
				html += "checked";
			}
			html += " /><label for='" + this.prefix + "-option-" + i + "'>" + this.view_options[i] + "</label>";
		}
		html += "</span>";
	}
	html += "</div>";
	return html;
} 

/**
 * Called to initialise the options bar (attach events, etc) 
 * @param callback {function} update result callback
 */
LDOUpdateViewer.prototype.initOptionsBar = function(callback){
	var self = this;
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

/**
 * Called to redraw the ldo update contents and trigger the callback function to allow the rest of the page to redraw itself
 * @param args {Object} args to be passed to fetch
 * @param msgs {Object} messages to be displayed while busy
 * @param callback {function} callback for other elements to refresh themselves
 */
LDOUpdateViewer.prototype.refreshPage = function(args, msgs, callback){
	var id = "update/" + this.ldo.id;
	var self = this;//this becomes bound to the callback...
	if(typeof callback == "function") {
		var ref = function (data, pconf) { 
			callback(data, pconf);
		};
	}
	else {
		var ref = function (data, pconf) { 
			self.ldo = new LDOUpdate(data);
			self.show(self.target, self.before_target, self.after_target, self.emode, {});
		};
	}
	dacura.ld.fetch(id, args, ref, this.pconf, msgs);
}

/**
 * No need to have these distinct in this object
 */
LDOUpdateViewer.prototype.refresh = LDOUpdateViewer.prototype.refreshPage; 

/**
 * Is this an update viewer?
 * @returns {boolean} true - yes it is
 */
LDOUpdateViewer.prototype.isLDOUpdate = function(){
	return true;
}; 

/**
 * Retrieve the ld type (candidate, ontology, graph) of the object being updated
 * @returns {string} the ld type
 */
LDOUpdateViewer.prototype.ldtype = function(){
	return this.ldo.meta.ldtype;
}

/**
 * Specifies the targets (jquery selectors) where the viewer will output
 * @param t {String} main viewer jquery selector
 * @param b {String} 'before' view jquery selector
 * @param a {String} 'after' view jquery selector
 * @param c {String} 'commands' box jquery selector - where the buttons go
 */
LDOUpdateViewer.prototype.setTargets = function(t, b, a, c){
	this.setTarget(t);
	this.before_target = b;
	this.after_target = a;
	this.commands_target = c;
}

/**
 * Displays the viewer in the page
 * @param target  {String} main viewer jquery selector
 * @param btarget {String} 'before' view jquery selector
 * @param atarget {String} 'after' view jquery selector
 * @param ctarget {String} 'commands' box jquery selector - where the buttons go
 * @param mode {String} edit|view|create
 * @param callback {function} refresh page callback
 */
LDOUpdateViewer.prototype.show = function(target, btarget, atarget, ctarget, mode, callback){
	this.setTargets(target, btarget, atarget, ctarget);
	this.emode = (typeof mode == "string") ? mode : this.emode;	
	this.showContents(mode, callback);
	this.showCommands(mode, callback);
	this.showOriginal(mode, callback);
	this.showChanged(mode, callback);
	this.initOptionsBar(callback);

};

/**
 * Displays the contents of the update
 * @param mode {String} view|edit
 * @param callback {function} the update callback function to handle update results
 */
LDOUpdateViewer.prototype.showContents = function(mode, callback){
	$(this.target).empty();
	var body = "<div class='ldo-viewer-contents' id='" + this.prefix + "-ldo-viewer-contents'>";
	if(this.emode == 'import'){
		body += this.getImportBodyHTML() + "</div>";
		$(this.target).append(body);
		this.tooltipLDImport();
	}
	else {
		var box = this.showStatusBox();
		body += this.ldo.getContentsHTML(this.emode) + "</div>";
		$(this.target).append(box + body);
		this.initStatusUpdateButtons(this.pconf, callback);
		if(this.emode != "view"){
			$(this.target).append(this.getUpdateButtonsHTML(this.emode));
			this.initUpdateButtons(this.pconf, callback);
		}
	}
};

/**
 * Called to update an ldo update via the server
 * @param upd {Object} the update object
 * @param resultCallback {function} the function that will handle the result
 * @param pageconfig {DacuraPageConfig} page config
 */
LDOUpdateViewer.prototype.update = function(upd, resultCallback, pageconfig){
	var id = "update/" + this.ldo.id;
	pageconfig = typeof pageconfig == "object" ? pageconfig : this.pconf;
	var self = this;//this becomes bound to the callback...
	if(typeof handleResp != "function"){
		handleResp = function(data, pconf){
			var res = new LDResult(data, pconf);
			if(res.status == "accept" && !res.test){
				var idstr = self.ldo.ldtype().ucfirst() + " " + self.ldo.id;
				msgs = {busy: "Loading updated update to " + idstr + " from Dacura API", "fail": "Failed to retrieve updated update to" + idstr + " from server"};
				self.refreshPage(self.ldo.getAPIArgs(), msgs, resultCallback);
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
 * Initialises the status update buttons by attaching events
 * @param pconf {DacuraPageConfig} pconfig object
 * @param callback {function} callback function to handle result of updates
 */
LDOUpdateViewer.prototype.initStatusUpdateButtons = function(pconf, callback){
	var self = this;
	$('.update-status-action').button().click(function(){
		var act = this.id.substring(14);
		if(act == 'reject' || act == "accept"){
			var upd = {"umeta": {"status": act}, "editmode": "update", "format": "json", "ldtype": self.ldo.ldtype()};
		}
		else if(act == 'rollback'){
			var upd = {"umeta": {"status": "reject"}, "editmode": "update", "format": "json", "ldtype": self.ldo.ldtype()};
		}
		else if(act == 'modernise'){
			var upd = {"umeta": {"from_version": self.ldo.original.latest_version}, "editmode": "update", "format": "json", "ldtype": self.ldo.ldtype()};					
		}
		self.update(upd, callback, pconf);	
	});
}

/**
 * Generates the html to represent the status box on the update page
 * @returns {String} html
 */
LDOUpdateViewer.prototype.showStatusBox = function(){
	var html = "<div class='update-status-box dacura-" + this.ldo.meta.status +"'>";
	html += "<div class='update-status-header'>Update ";
	if(this.ldo.meta.status == "accept"){
		html += "was accepted " + dacura.system.getIcon(this.ldo.meta.status);
	}
	else if(this.ldo.meta.status == 'pending'){
		html += "is pending approval " + dacura.system.getIcon(this.ldo.meta.status);	
	}
	else if(this.ldo.meta.status == 'reject'){
		html += "was rejected " + dacura.system.getIcon(this.ldo.meta.status);	
	}
	html += "</div>";
	if(this.view_actions && this.ldo.meta.status != 'reject'){
		html += "<div class='update-status-actions'>";
		if(this.ldo.meta.status == "accept"){
			if(this.ldo.meta.to_version == this.ldo.original.meta.latest_version){
				html += "<button id='update-status-rollback' class='update-status-action'>Undo</button>";		
			}
			else {
				
				var vers = (this.ldo.original.meta.latest_version - this.ldo.meta.to_version);
				html += "<span class='update-status-details'>" + vers + " update" + ((vers == 1) ? " has" : "s have");
				html += " been accepted since this update - it cannot be altered now</span>";
			}
		}
		else if(this.ldo.meta.status == "pending"){
			html += "<button id='update-status-accept' class='update-status-action'>Accept</button> ";		
			html += "<button id='update-status-reject' class='update-status-action'>Reject</button> ";
			if(this.ldo.meta.from_version != this.ldo.original.meta.latest_version){
				html += "<span class='update-status-details'>This update was made against an old version of the "+ this.ldo.ldtype() + " v" + this.ldo.meta.from_version;
				var vers = this.ldo.original.meta.latest_version - this.ldo.meta.from_version;
				html += vers + " update" + (vers == 1) ? " has" : "s have";
				html += " been accepted since this update. It should be applied to the current version before it can be accepted.";
				html += "<button id='update-status-modernise' class='update-status-action'>Modernise</button></span> ";
			}
		}
		html += "</div>";
	}
	html += "<div class='update-status-details'>"
	html += "Update took place at " + timeConverter(this.ldo.meta.created) + " on version "	+ this.ldo.meta.from_version;
	if(this.ldo.meta.created != this.ldo.meta.modified){
		html += " and was last modified at " + timeConverter(this.ldo.meta.modified); 
	}
	if(this.ldo.meta.to_version > 0){
		html += ", creating version " + this.ldo.meta.to_version;
	}
	html += "</div></div>";
	return html;
}

/**
 * Displays the command buttons on the screen 
 * @param mode {String} edit|view
 */
LDOUpdateViewer.prototype.showCommands = function(mode){
	$(this.commands_target).empty();
	var body = "<div class='ldo-viewer-commands dacura-json-viewer' id='" + this.prefix + "-commands-ldo-viewer-contents'>";
	body += this.ldo.getCommandsHTML(this.emode) + "</div>";
	$(this.commands_target).append(body);
}

/**
 * Shows the "before" version of the ldo 
 */
LDOUpdateViewer.prototype.showOriginal = function(){
	$(this.before_target).empty();
	$(this.before_target).append(this.showOptionsBar());
	var body = "<div class='ldo-viewer-contents' id='" + this.prefix + "-before-ldo-viewer-contents'>";
	body += this.ldo.original.getContentsHTML(this.emode) + "</div>";
	$(this.before_target).append(body);
}

/**
 * Shows the "after" version of the ldo 
 */
LDOUpdateViewer.prototype.showChanged = function(){
	$(this.after_target).empty();
	$(this.after_target).append(this.showOptionsBar());
	var body = "<div class='ldo-viewer-contents' id='" + this.prefix + "-after-ldo-viewer-contents'>";
	body += this.ldo.changed.getContentsHTML(this.emode) + "</div>";
	$(this.after_target).append(body);
}


/* inherited from ldoviewer */
LDOUpdateViewer.prototype.init = LDOViewer.prototype.init;
LDOUpdateViewer.prototype.tooltipLDImport = LDOViewer.prototype.tooltipLDImport;
LDOUpdateViewer.prototype.showImportOptionsBar = LDOViewer.prototype.showImportOptionsBar;
LDOUpdateViewer.prototype.showEditOptionsBar = LDOViewer.prototype.showEditOptionsBar; 
LDOUpdateViewer.prototype.getUpdateButtonsHTML = LDOViewer.prototype.getUpdateButtonsHTML;
LDOUpdateViewer.prototype.initUpdateButtons = LDOViewer.prototype.initUpdateButtons;
LDOUpdateViewer.prototype.getImportData = LDOViewer.prototype.getImportData;
LDOUpdateViewer.prototype.setImportType = LDOViewer.prototype.setImportType;
LDOUpdateViewer.prototype.replaceContents = LDOViewer.prototype.replaceContents;
LDOUpdateViewer.prototype.getImportBodyHTML = LDOViewer.prototype.getImportBodyHTML;
LDOUpdateViewer.prototype.setTarget = LDOViewer.prototype.setTarget;
LDOUpdateViewer.prototype.showFrame = LDOViewer.prototype.showFrame;
LDOUpdateViewer.prototype.initFrameView = LDOViewer.prototype.initFrameView;
LDOUpdateViewer.prototype.handleViewAction = LDOViewer.prototype.handleViewAction;
LDOUpdateViewer.prototype.clearEditMode = LDOViewer.prototype.clearEditMode;
LDOUpdateViewer.prototype.loadImportMode = LDOViewer.prototype.loadImportMode;
LDOUpdateViewer.prototype.loadEditMode = LDOViewer.prototype.loadEditMode;
LDOUpdateViewer.prototype.handleViewFormatUpdate = LDOViewer.prototype.handleViewFormatUpdate;
LDOUpdateViewer.prototype.handleViewOptionUpdate = LDOViewer.prototype.handleViewOptionUpdate;

