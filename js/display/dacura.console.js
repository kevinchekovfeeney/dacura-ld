/*
 * UI manager for the dacura console
 */

/**
 * @param config Object - a configuration object containing all the settable options for the console
 * @param console_html - the contents of console.html - the template file containing all the static html possible  
 */
function DacuraConsole(client){
	//dacura url
	this.HTMLID = "dacura-console";
	this.menu_pconfig = {
		resultbox: "#" + this.HTMLID + "-menu-message",
		busybox: "#" + this.HTMLID + "-menu-busybox"
	};
	this.full_pconfig = {
		resultbox: "#console-update-result", 
		busybox: this.menu_pconfig.busybox
	};

	this.client = client;
	//the context in which operations are performed. 
	//general approach is to leave the non-relevant ones untouched to allow for eash switches in context.
	//all changes in context are saved to state...
	this.context = {
		mode: "view",//view|edit|create|harvest
		collection: this.client.current_collection,
		tool: false,//data|model
		ontology: false,
		modelclass: false,
		modelproperty: false,
		candidate: false,
		entityclass: false, 
		candidateproperty: false,
		factoid: false
	};
	//array of contexts detailing the previous state of the context - to support context back / forward browsing
	this.history = [];
	//array of contexts detailing the 'future' states of the context - only active after 'back' has been clicked
	this.future = [];
	//Is the console currently showing something in the extra area? 
	//set to object with type / mode describing what is currently showing in the console-extra pane...
	this.showing_extra = false;
	//cached copies of the current ontology / candidate 
	this.current_ontology = false;
	this.current_candidate = false;
	this.current_meta = false;//state of metadata loaded into metadata area of console 
	//is the console currently showing the roles menu
	this.showing_roles = false;
	//set of roles that are active for the current user
	this.active_roles = [];
	//is the console currently showing the tools menu
	this.showing_tools = false;
	//text describing the current API call - set to allow for population of busy messages
	this.current_action = false;
	//count of the number of properties in the entity dropdown - stashed for convenience as it is calculated several times.
	this.entity_property_count = 0;
	//the classes that are 'visible' in the model editor drop downs - accumulated from all available sources
	//set to true if the page has been imported.
	this.imported = false;
	//two major sub-objects to which much of the heavy lifting is handed off when needed
	this.frameviewer = false;
	this.frameviewerConfig = {
			show_entity_annotations: false, 
			target: "dacura-console-frame-viewer"
	};
	//this.jquery_body_selector = (config && config.jquery_body_selector ? config.jquery_body_selector : "body"); 
	//this.body_before_dacura = jQuery(this.jquery_body_selector).html();
	//jQuery(this.jquery_body_selector).after(console_html);
	jQuery.fn.select2.defaults.set('dropdownCssClass', 's2option'); 
}



DacuraConsole.prototype.displayMenu = function() {
	jQuery('#' + this.HTMLID).show("drop", {direction: "up"});
	jQuery('.dacura-body-wrapper').css('marginTop', 36);
}

DacuraConsole.prototype.undisplayMenu = function() {
	jQuery('#' + this.HTMLID).hide("slide", {direction: "up"});
	jQuery('.dacura-body-wrapper').css('marginTop', 0);
	if(this.pagescanner){
		this.pagescanner.undisplay();
	}
}

/**
 * @param context - the initial context to load
 * @param success - optional callback function - passed the set of capabilities returned by the server
 * @param fail - option failure callback that will be executed - normal behaviour is to show the error message on the console
 */
DacuraConsole.prototype.init = function(context, success, fail){
	var self = this;
	if(typeof fail != "function"){
		fail = function(title, msg, extra){
			self.initMenu();
			self.hideAll();
			self.displayMenu()
			self.showResult("error", title, msg, extra);
		};
	}
	var nsuccess = function(caps){	
		if(self.autoload){
			self.current_action = "Pre-loading resources from server";
			self.client.loadAll(fail);
		}
		context = self.calculateInitialContext(context);
		if(context){
			self.userChangesContext(context, false, true);
			//self.showMenu(true);
		}
		else {
			self.initMenu();
			self.showMenu(true);
			//self.displayMenu();
		}
		if(success){
			success(caps);
		}
	}
	this.client.busy = function(){
		self.showBusy();
	}
	this.client.notbusy = function(){
		self.clearBusy();
	}
	this.current_action = "Initialising Dacura Client";
	this.client.init(nsuccess, fail);
}

DacuraConsole.prototype.calculateInitialContext = function(context){
	if(context.collection){
		var harvs = this.client.getHarvestedConnectors(context.collection, true);
		if(context.tool != "model"){
			if(harvs.created && size(harvs.created)){
				context.candidate = firstKey(harvs.created);
			}
			else if(harvs.updated && size(harvs.updated)){
				context.candidate = firstKey(harvs.updated);
			}
		}
	}
	return context;
}


/* After initialisations, the system works by manipulating the visibility of elements */


/**
 * Core function - called whenever a user changes their context
 * When a user does something, the context change is passed to this function and this then calculates what needs to happen
 * @param ncontext - array with the updated context elements in it 
 * @param special (back|forward|other) -> browser actions need special treatment
 */
DacuraConsole.prototype.userChangesContext = function(ncontext, special, undisplayed){
	menu_shown = false;
	if(!special || special != "back"){
		this.history.push(jQuery.extend({}, true, this.context));
		if(!special){
			this.future = []; //reset future if the user has done something else....			
		}
	}
	else if(special == "back"){
		this.future.push(jQuery.extend({}, true, this.context));	
	}
	if(typeof ncontext.collection != "undefined" && (ncontext.collection != this.context.collection)){
		this.resetContext();
		this.hideFullExtra();
		this.context.collection = ncontext.collection;
		if(this.context.collection){
			this.client.current_collection = this.context.collection;
			if(!this.context.tool){
				this.context.tool = "data";
			}
			this.initCollectionContext(undisplayed);
		}
	}
	if(typeof ncontext.tool == "string" && ncontext.tool != this.context.tool){
		this.hideFullExtra();
		//if(this.pagescanner && this.pagescanner.factoids){
		//	this.importPage();
		//}
		this.context.tool = ncontext.tool;
		this.context.mode = "view";
	} 
	if(typeof ncontext.mode == "string"){
		if(ncontext.mode != this.context.mode){
			if(ncontext.mode == "create"){
				if(this.context.tool == "data"){
					this.context.candidate = false;
				}
				else if(this.context.tool == "model"){
					this.context.modelproperty = false;
					this.context.modelclass = false;				
				}
			}
			if(ncontext.mode == "edit" && this.context.mode == "view" && ((ncontext.tool && ncontext.tool == "model") || (!ncontext.tool & this.context.tool == "model")) && this.context.modelclass){
				jQuery("#dacura-console .classtype-picker select").trigger("change");
			}
			if(ncontext.mode == "harvest" && this.context.tool == "model"){
				jQuery("#dacura-console select.factoid-import-picker").trigger("change");
			}
		}
		if(this.frameviewer){
			this.switchFrameviewerMode(this.context.mode, ncontext.mode);
		}
		this.context.mode = ncontext.mode;
	}
	if(typeof ncontext.candidate != "undefined" && this.context.candidate != ncontext.candidate){
		this.removeFrameviewer();
		this.context.candidate = ncontext.candidate;
		if(this.context.candidate){
			var col = this.client.collections[this.context.collection];
			var cands = col.entities;
			var nec = false;
			for(var etype in cands){
				for(var curl in cands[etype]){
					if(curl && curl == ncontext.candidate){
						ncontext.candidate = cands[etype][curl].id; 	
						this.context.candidate = ncontext.candidate;
					}
					if(cands[etype][curl].id == ncontext.candidate){
						this.context.entityclass = etype;
						break;
					}
				}
			}
			this.initCandidateContext(undisplayed);
			menu_shown = true;
		}
	}
	else if(typeof ncontext.entityclass != "undefined" && this.context.entityclass != ncontext.entityclass){
		this.removeFrameviewer();
		this.context.entityclass = ncontext.entityclass;
		if(this.context.entityclass){
			var ontid = this.context.entityclass.split(":")[0];
			var col = this.client.collections[this.context.collection];
			if(typeof col.ontologies[ontid] != "undefined"){
				this.context.ontology = ontid;
				this.context.modelclass = this.context.entityclass;
			}
			var self = this;
			menu_shown = true;
			this.initCollectionCandidates();
			this.initEntityContext(false, false, undisplayed);
		}
		else {
			this.initCollectionCandidates();
		}
	}
	if(typeof ncontext.factoid != "undefined"){
		this.context.factoid = ncontext.factoid;
	}
	if(typeof ncontext.candidateproperty != "undefined"){
		this.context.candidateproperty = ncontext.candidateproperty;
	}
	if(typeof ncontext.modelclass != "undefined"){
		this.context.modelclass = ncontext.modelclass;
		if(this.context.modelclass){
			this.context.modelproperty = false;
		}
	}
	if(typeof ncontext.modelproperty != "undefined"){
		this.context.modelproperty = ncontext.modelproperty;
		if(this.context.modelproperty){
			this.context.modelclass = false;
		}
	}
	if(this.context.tool == "model" && !this.context.ontology && this.context.collection && size(this.client.collections[this.context.collection].ontologies) == 1){
		ncontext.ontology = firstKey(this.client.collections[this.context.collection].ontologies);
	}
	if(typeof ncontext.ontology != "undefined" && ncontext.ontology != this.context.ontology){
		this.context.ontology = ncontext.ontology;
		if(this.context.ontology == "") {
			this.context.ontology = false;
			this.context.modelclass = false;
			this.context.modelproperty = false;
		}
		this.initOntologyContents(undisplayed);
		menu_shown = true;
	}
	if(!menu_shown) {
		this.showMenu(undisplayed);
	}
	this.client.context = this.context;
}


/**
 * Main visibility defining function - draws the console according to the current context
 */
DacuraConsole.prototype.showMenu = function(undisplayed){
	if(undisplayed) this.initMenu();
	this.setToolCSSClass();
	this.setBrowserButtonVisibility();
	this.setModeIconVisibility();
	this.setRoleIconVisibility();
	this.setResultVisiblity();
	this.setCollectionContextVisibility();
	this.setHarvestContextVisibility();
	this.setFrozenElements();
	if(!this.client.isLoggedIn()){
		this.showLogin();
	}
	else {
		this.showUserIcon();
	}
	if(this.context.mode == "harvest"){
		this.showHarvestMenu();
	}
	else if(this.context.tool == "data"){
		this.hidePageSummary();
		if(this.context.mode == "create"){
			this.displayNewCandidateForm(this.getDefaultNewCandidateFormValues());
		}
		else if(this.context.candidate) {
			this.displayCandidate(this.getDefaultEditCandidateFormValues(this.current_candidate));
		}
		else {
			this.hideFullExtra(true);
		}
	}
	else if(this.context.tool == "model"){
		this.hidePageSummary();
		if(this.context.mode == 'create'){
			if(this.model_create_clicked == 'property'){
				this.displayModelProperty(this.getDefaultNewPropertyFormValues());		
			}
			else {
				this.displayModelClass(this.getDefaultNewClassFormValues());
			}
		}
		else if(this.context.modelclass){
			var def = (this.context.mode == "edit" ? this.getDefaultEditClassFormValues(this.context.modelclass) : false);
			this.displayModelClass(def);
		}
		else if(this.context.modelproperty){
			var def = (this.context.mode == "edit" ? this.getDefaultEditPropertyFormValues(this.context.modelproperty) : false);
			this.displayModelProperty(def);
		}
		else {
			this.hideFullExtra(true);
		}
	}
	if(undisplayed){
		this.displayMenu();
	}
}



DacuraConsole.prototype.showHarvestMenu = function(){
	if(this.context.tool == "model"){
		this.hideFullExtra(true);
		this.displayFactoid();
	}
	else {
		/*if((this.context.entityclass || this.context.candidate) && this.context.candidateproperty){
			if(this.context.candidate) {
				this.displayCandidate(this.getDefaultEditCandidateFormValues(this.current_candidate));
			}
			else {
				this.displayNewCandidateForm(this.getDefaultNewCandidateFormValues());
			}
		}
		else {
			this.hideFullExtra(true);
		}*/
		this.displayFactoid();
		this.hideFullExtra(true);
		var self = this;
		var showFactoids = function(frames){
			var updcb = function(handler, upd, datapoint){
				self.testFrameValue(handler, upd, datapoint, self.request_rate_limit, true);
			}
			self.importFactoids(frames, updcb, true);
		};
		try {
			self.initEntityContext(false, showFactoids);
		}
		catch(e){
			alert(e.stack);
			console.log("Exception thrown", e.stack);
		}
	}
}




/**
 * Returns a simple list [] of urls of properties currently displayed in the frameviewer
 */
DacuraConsole.prototype.getDisplayedProperties = function(){
	if(this.frameviewer){
		return this.frameviewer.getDisplayedProperties();
	}
	return false;
}
/**
 * Returns true if there are any properties currently displayed in the frameviewer
 */
DacuraConsole.prototype.hasDisplayedProperties = function(){
	return (this.frameviewer && this.frameviewer.frames && size(this.frameviewer.frames) > 0);
}

/**
 * Returns true if the passed property is currently displayed in the frameviewer
 */
DacuraConsole.prototype.propertyIsDisplayed = function(prop){
	var dprops = this.getDisplayedProperties();
	if(typeof dprops == "object" && dprops.length){
		if(dprops.indexOf(prop) > -1){
			return true;
		}
	}
	return false;
}

/**
 * Called to safely remove the frame from view
 */
DacuraConsole.prototype.removeFrameviewer = function(){
	if(this.frameviewer){
		jQuery('#' + this.frameviewer.target).html("");
		this.frameviewer.destroy();
		this.frameviewer = false;
	}
}

DacuraConsole.prototype.switchFrameviewerMode = function(from, to){
	if(from == to){
		//alert("frameviewer is already in " + to + " mode - no switch");
	}
	else if(from == "view" && to == "edit"){
		this.frameviewer.resetMode("edit");
	}
	else if(from == 'edit' && to == "view"){
		this.frameviewer.resetMode("view");
		//var props = this.getDisplayedProperties();
		
	}
}


/**
 * Returns roles available to user in current context
 */
DacuraConsole.prototype.getAvailableRoles = function(){
	var colcap = this.context.collection ? this.client.collections[this.context.collection] : false;
	if(!colcap){
		return [];
	}
	return colcap.roles;
} 

/**
 * Fetches an entity metadata data object from a class id (ns:localid) 
 * @param id - id string
 * @return object - entity object or false if unknown
 */
DacuraConsole.prototype.getEntityClassFromID = function(id, gid){
	
	var ecap = this.context.collection ? this.client.collections[this.context.collection].entity_classes : false;
	if(typeof ecap == "object" && size(ecap)){
		if(gid){
			var rel = ecap[gid];
			for(var i = 0; i< rel.length; i++){
				if(rel[i].id == id) return rel[i];
			}				
		}
		else {
			for(var gurl in ecap){
				for(var i = 0; i< ecap[gurl].length; i++){
					if(ecap[gurl][i].id == id) return ecap[gurl][i];
				}				
			}
		}
	}
	else if(typeof ecap == "object" && ecap.length){
		for(var i = 0; i< ecap.length; i++){
			if(ecap[i].id == id) return ecap[i];
		}
	}
	return false;
}


/**
 * visible classes are those that are available for populating model interfaces
 * @param id string - the class id (ns:localid) of the class to check
 * @return boolean - true if the class is visible
 */
DacuraConsole.prototype.classIsVisible = function(id){
	var ns = id.split(":")[0];
	if(ns && typeof this.visible_classes[ns] != "undefined" && this.visible_classes[ns][id] ){
		return true;
	}
	return false;
}

/**
 * adds a class to the list of 'visible' classes available for drop downs
 * @param id string - the class id
 * @param label string - the class label
 */
DacuraConsole.prototype.addClassToVisibleClasses = function(id, label){
	if(!id){
		return alert("Failed to add visible class " + id + " -> " + label);
	}
	var ns = id.split(":")[0];
	if(ns){
		if(typeof this.visible_classes[ns] == "undefined"){
			this.visible_classes[ns] = {};
		}
		this.visible_classes[ns][id] = label;
	}
}

/**
 * Resets the context to empty state
 */
DacuraConsole.prototype.resetContext = function(){
	this.context.mode = "view";
	this.context.collection = false;
	this.context.ontology = false;
	this.context.modelclass = false;
	this.context.modelproperty = false;
	this.context.candidate = false,
	this.context.entityclass = false,
	this.context.factoid = false,
	this.context.candidateproperty = false;
	if(this.frameviewer){
		this.removeFrameviewer();
	}
};


/*html / js initialisations - set up events, etc. */

/**
 * Initialises the menu by setting up all the events that are associated with user actions 
 * All event handling is done here...
 */
DacuraConsole.prototype.initMenu = function(){
	var self = this;
	this.hideFullExtra();
	jQuery('#' + this.HTMLID + " div.console-user-close").click(function(){
		self.undisplayMenu();
	});
	if(this.client.isLoggedIn()){
		self.initCollectionChoices();
		jQuery('#' + this.HTMLID + " .browser-button-back").click(function(){
			self.goBack();
		});
		jQuery('#' + this.HTMLID + " .browser-button-forward").click(function(){
			self.goForward();
		});
		jQuery('#' + this.HTMLID + " .console-user-icon").click(function(){
			if(!self.context.collection) return;
			if(typeof already_clicked == "undefined" || !already_clicked){
				already_clicked = true;
				var comp = function(){already_clicked = false;}
				if(jQuery('#' + self.HTMLID + " .console-user-roles").is(":visible")){
					self.showing_roles = false;
					jQuery('#' + self.HTMLID + " .console-user-roles").hide("slide", {direction: 'right', duration: "slow", complete: comp});
				}
				else {
					self.showing_roles = true;
					jQuery('#' + self.HTMLID + " .console-user-roles").show("slide", {direction: 'right', duration: "slow", complete: comp});					
				}
			}
		});
		jQuery('#' + this.HTMLID + " .console-mode .mode-active").click(function(){
			if(typeof talready_clicked == "undefined" || !talready_clicked){
				talready_clicked = true;
				var comp = function(){setTimeout(function() { talready_clicked = false; }, 500);}
				if(self.showing_tools){
					self.showing_tools = false;
					jQuery('#' + self.HTMLID + " .console-mode span.amode-icon.mode-inactive").hide();
					jQuery('#' + self.HTMLID + " .console-mode span.mode-icons span.mode-icons-inactive").hide("slide", {direction: "left", speed: "fast", complete: comp});
				}
				else {
					self.showing_tools = true;
					jQuery('#' + self.HTMLID + " .console-mode span.amode-icon.mode-inactive").show();					
					jQuery('#' + self.HTMLID + " .console-mode span.amode-icon.mode-inactive.mode-"+self.context.tool).hide();					
					jQuery('#' + self.HTMLID + " .console-mode span.mode-icons span.mode-icons-inactive").show("slide", {direction: "left", speed: "fast", complete: comp});
				}
			}
		});
		jQuery('#' + this.HTMLID + " .console-mode span.amode-icon.mode-inactive").click(function(){
			var ntool = this.id.substring(this.id.lastIndexOf("-") + 1);
			self.userChangesContext({tool: ntool});
		});
		jQuery('#' + this.HTMLID + " .console-role.role-inactive").click(function(){
			var nrole = this.id.substring(this.id.lastIndexOf("-") + 1);
			if(typeof self.active_roles[nrole] == "undefined"){
				self.active_roles[nrole] = true;
				self.showMenu();
			}			
		});
		jQuery('#' + this.HTMLID + " .console-role.role-active").click(function(){
			var orole = this.id.substring(this.id.lastIndexOf("-") + 1);
			if(typeof self.active_roles[orole] != "undefined"){
				delete(self.active_roles[orole]);
				self.showMenu();
			}
		});
		linkifyElement(".summary-view", function(){
			self.userChangesContext({mode: "view"});				
		});	
		linkifyElement(".console-extra .summary-edit", function(){
			self.userChangesContext({mode: "edit"});				
		});	
		linkifyElement(".console-import .console-import-tool", function(){ 
//			/self.importPage();
			if(self.context.mode != "harvest" && self.context.tool == "model"){
				self.userChangesContext({mode: "harvest"});			
			}
			else if(self.context.tool == "data"){
				try {
					self.hitUpCandidateAPI();
				}
				catch(e){
					alert(e.toString());
					alert(e.stack);
				}
			}
			else {
				self.userChangesContext({mode: "view"});							
			}
		});
		//events to support the addition of elements to models / properties to candidates 
		linkifyElement(".context-add-element", function(){ 
			self.userChangesContext({mode: "create"})}
		);
		linkifyElement(".context-add-modelproperty", function(){
			self.model_create_clicked = "property";
			self.userChangesContext({mode: "create"});
		});
		linkifyElement(".context-add-modelclass", function(){
			self.model_create_clicked = "class";
			self.userChangesContext({mode: "create"});
		});
		linkifyElement(".context-new-property", function(){
			jQuery(this).hide();
			var prop = jQuery('#' + self.HTMLID + " select.context-entityproperty-picker").val();
			if(prop.length){
				var isnew = true;
				if(self.current_candidate){
					var props = self.current_candidate.getProperties();
					for(var i=0; i<props.length; i++){
						if(props[i].id == prop){
							isnew = false;
							continue;
						}
					}	
				}
				self.addPropertyToDisplay(prop, isnew);
			}
		});
		linkifyElement(".summary-close", function(){
			var ncontext = {};
			if(self.context.mode == "view"){
				if(self.context.tool == "data" && self.context.candidate){
					ncontext.candidate = false;
				}
				else if(self.context.tool == 'model' && self.context.modelclass){
					ncontext.modelclass = false;
				}
				else if(self.context.tool == 'model' && self.context.modelproperty){
					ncontext.modelproperty = false;
				}
				self.removeFrameviewer();
			}
			else {
				ncontext.mode = 'view';
			}
			self.userChangesContext(ncontext);
		});	
		//element that links back to the relevant page on dacura
		linkifyElement(".summary-dacura", function(){
			var url = false;
			if(self.context.tool == "data"){
				if(self.context.mode == 'create'){
					url = self.client.collections[self.context.collection].url + "/candidate#ldo-create";
				}
				else if(self.context.candidate){
					url = self.current_candidate.meta.cwurl
				}
			}
			else if(self.context.tool == 'model'){
				if(self.context.mode == 'create'){
					url = self.client.collections[self.context.collection].url + "/ontology#ldo-create";
				}
				else if(self.current_ontology) {
					url = self.current_ontology.meta.cwurl	
				}				
			}
			if(url){
				var win = window.open(url, '_blank');
				win.focus();
			}
		});
		
		linkifyElement(".context-ontology span.ontology-title-changer", function(){
			var f = function(){jQuery('#' + self.HTMLID + " .context-ontology-picker-holder").show("fade")};
			jQuery('#' + self.HTMLID + " .context-ontology .context-ontology-picked").hide("fade", f);
		});
		
		linkifyElement(".console-collection span.collection-title-changer", function(){
			var f  = function(){jQuery('#' + self.HTMLID + " .console-collection .context-select-holder").show("fade")};
			jQuery('#' + self.HTMLID + " .console-collection .context-collection-picked").hide("fade", f);
		});
		
		linkifyElement(".pagescan-navigator .motion-first", function(){ self.updateFactoidSelector("first");});
		linkifyElement(".pagescan-navigator .motion-prev", function(){ self.updateFactoidSelector("prev");});
		linkifyElement(".pagescan-navigator .motion-next", function(){ self.updateFactoidSelector("next");});
		linkifyElement(".pagescan-navigator .motion-last", function(){ self.updateFactoidSelector("last");});
		//actual buttons - all updates are done with real buttons, not iconish ones- just for display stuff
		jQuery("#console-button-cancel").button({icons: {primary: "ui-icon-cancel"}}).click(function(){
			self.userChangesContext({mode: "view"});
		});
		jQuery("#console-button-delete").button({icons: {primary: "ui-icon-trash"}}).click(function(){
			if(self.context.candidate){
				self.deleteCandidate(self.context.candidate);
			}
			else {
				self.deleteModelElement();
			}
		});	
		jQuery("#console-button-test").button({icons: {primary: "dacura-help-button-icon"}}).click(function(){
			if(self.context.tool == "data"){
				self.hitUpCandidateAPI(true);
			}
			else {
				self.hitUpOntologyAPI(true);
			}
		});
		jQuery("#console-button-commit").button({icons: {primary: "ui-icon-disk"}}).click(function(){
			if(self.context.tool == "data"){
				self.hitUpCandidateAPI();
			}
			else {
				self.hitUpOntologyAPI();
			}
		});
		jQuery("#console-button-import").button({icons: {primary: "ui-icon-disk"}}).click(function(){
			self.hitUpOntologyAPI();
		});
		jQuery("#console-button-data-import").button({icons: {primary: "ui-icon-disk"}}).click(function(){
			var import_target = self.readDataImportTarget();
			self.userChangesContext(import_target);
			//if(import_target.candidate)
			//self.displayNewCandidateForm(self.getDefaultNewCandidateFormValues());
		});


		self.initModelEditor();
	}
}

/**
 * Initialises the select drop down for the collections available to the user - only done at start up...
 */
DacuraConsole.prototype.initCollectionChoices = function(){
	var cols = this.client.collections;
	var self = this;
	var html = "<option value=''>Select a Collection</option>";
	for(var colid in cols){
		var sel = (colid == this.context.collection ? " selected" : "");
		html += "<option" + sel + " value='" + colid + "'>" + cols[colid].title + "</option>"; 
	}
	var shtml = "<select class='context-collection-picker'>" + html + "</select>";
	jQuery('#' + this.HTMLID + " .context-collection-picker-holder").html(shtml);
	jQuery('#' + this.HTMLID + " select.context-collection-picker").select2({
		  placeholder: "Select a collection",
		  allowClear: true,
		  minimumResultsForSearch: 10,
		  templateResult: function(state){
			  if (!state.id) { return state.text; }
			  return jQuery("<span><img height='14' src='" + cols[state.id].icon + "'> " + cols[state.id].title + "</span>");
		  }
	}).on('change.select2', function(){
		self.userChangesContext({"collection": this.value });
	});
	if(size(cols) == 1){
		jQuery('#' + this.HTMLID + " .console-collection span.collection-title-changer").hide();
	}
}

/**
 * Called when the collection context is changed - sets up the various selectors for the collection
 */
DacuraConsole.prototype.initCollectionContext = function(noimport){
	var cols = this.client.collections;
	if(this.context.collection){
		var html = "<a class='collection-dacura-home' href='" + cols[this.context.collection].url + "' title='Visit the collection home page on Dacura'>";
		html += "<img class='collection-icon' src='" + cols[this.context.collection].icon + "'></a>";
		jQuery('#' + this.HTMLID + " .context-collection-picked .collection-title-icon").html(html);
		jQuery('#' + this.HTMLID + " .context-collection-picked .collection-title-text").attr("title", cols[this.context.collection].title).html(cols[this.context.collection].title);
		jQuery('#' + this.HTMLID + " .console-collection .context-select-holder").hide();
		jQuery('#' + this.HTMLID + " .console-collection").show();
		jQuery('#' + self.HTMLID + " .context-collection-picked").show();
		this.loadCollectionVisibleModelElements();
		this.initCollectionEntityClasses();
		this.initCollectionCandidates();
		this.initCollectionOntologies();
		this.active_roles = jQuery.extend([], this.client.collections[this.context.collection].roles);
		if(this.context.tool == "data"){
			this.importPage();
		}
	}
	else {
		jQuery('#' + self.HTMLID + " .context-collection-picked").hide();
		jQuery('#' + this.HTMLID + " .console-collection").show();
		jQuery('#' + this.HTMLID + " .console-collection .context-select-holder").show();
	}
}


DacuraConsole.prototype.getEntityLabel = function(ec){
	if(ec.meta && ec.meta.label){
		return ec.meta.label;
	}
	else if(ec.id){
		return ec.id;
	}		
}



/**
 * Hides all elements on the topbar bar the branding and close buttons (which we always want)
 */
DacuraConsole.prototype.hideAll = function(){
	jQuery('#' + this.HTMLID + ' .menu-area').hide();
	jQuery('#' + this.HTMLID + ' .menu-area.console-branding').show();	
	jQuery('#' + this.HTMLID + ' .menu-area.console-user-close').show();	
};

/**
 * Called when the user hits the forward browser button
 */
DacuraConsole.prototype.goForward = function(){
	hcontext = this.future.pop();
	this.userChangesContext(hcontext, "forward");
}

/**
 * Called when the user hits the back button on the browser
 */
DacuraConsole.prototype.goBack = function(){
	hcontext = this.history.pop();
	this.userChangesContext(hcontext, "back");	
}

/**
 * Called when a call to the API returns a result - written to the top bar
 * @param type error | warning | accept | reject | pending
 * @param title the message title
 * @param msg the message body
 * @param extra extra material for optional display
 */
DacuraConsole.prototype.showResult = function(type, title, msg, extra){
	jQuery('#' + this.HTMLID + " div.console-resultbox .dacura-result").hide();
	jQuery('#' + this.HTMLID + " div.console-resultbox ." + type).show().attr("title", title + " - " + msg);
	jQuery('#' + this.HTMLID + " div.console-resultbox").show();		
}; 

/*
 * For results from updates - written to the full api screen. 
 */
DacuraConsole.prototype.writeResultMessage = function(type, title, msg, extra){
	dacura.system.writeResultMessage(type, title, '#console-update-result', msg, extra);
}

DacuraConsole.prototype.writeErrorMessage = function(head, body, extra){
	this.writeResultMessage("error", head, body, extra);
}

/**
 * Called to show the busy animation 
 */
DacuraConsole.prototype.showBusy = function(){
	var msg = (this.current_action ? this.current_action : "Fetching information from server");
	jQuery('#dacura-console-menu-busybox').attr("title", msg)
	jQuery('#dacura-console-menu-busybox').show();
} 

/**
 * Called to clear the busy animation
 */
DacuraConsole.prototype.clearBusy = function(){
	jQuery('#dacura-console-menu-busybox').attr("title", "").hide();
} 

/** 
 * Shows the login box in the top bar
 */
DacuraConsole.prototype.showLogin = function(){
	jQuery('#' + this.HTMLID +  ' .console-login').show();		
	jQuery('#' + this.HTMLID + ' .console-user-logged-in').hide();			
}

/** 
 * Shows the user icon 
 */
DacuraConsole.prototype.showUserIcon = function(){
	jQuery('#' + this.HTMLID + ' .console-user-logged-in').show();		
	jQuery('#' + this.HTMLID + ' .console-login').hide();
}

DacuraConsole.prototype.setFrozenElements = function(){
	if(this.context.mode == "view"){
		this.thawNavigation();
	}
	else {
		this.freezeNavigation();
	}
}

DacuraConsole.prototype.setHarvestContextVisibility = function(){
	if(this.context.mode == "harvest"){
		jQuery('#' + this.HTMLID + " .console-import .harvest-element").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-import .harvest-element").hide();
	}
}

/**
 * Disables navigation elements on the top bar 
 */
DacuraConsole.prototype.freezeNavigation = function(){
	jQuery('#' + this.HTMLID + " .console-browser-buttons").hide();
	jQuery('#' + this.HTMLID + " .collection-title-changer").hide();
	jQuery('#' + this.HTMLID + " .ontology-title-changer").hide();
	//jQuery('#' + this.HTMLID + " .console-import").hide();
	//freezeElement('#' + this.HTMLID + " .console-mode", "console-overlay", 0);
	if(this.context.mode != "harvest"){
		this.showEditorActionHeader();
	}
}

DacuraConsole.prototype.showEditorActionHeader = function(){
	var jqid = '#' + this.HTMLID + " .editor-action";
	jQuery(jqid +  " .editor-action-head .summary-icon").hide();
	if(this.context.mode == "create"){
		if(this.context.tool == "model" && this.model_create_clicked == 'property'){
			jQuery(jqid +  " .editor-action-head .modelproperty-icon").show();
			jQuery(jqid +  " .editor-action-text").html("New Property");
		}
		else if(this.context.tool == "model" && this.model_create_clicked == 'class'){
			jQuery(jqid +  " .editor-action-head .modelclass-icon").show();						
			jQuery(jqid +  " .editor-action-text").html("New Class");
		}
		else if(this.context.tool == "data" && this.context.entityclass){
			jQuery(jqid +  " .editor-action-head .entity-icon").show();	
			var lbl = this.context.entityclass;
			var ecs = this.getEntityClasses();
			if(ecs.length >= 1){
				for(var i = 0; i<ecs.length; i++){
					if(this.context.entityclass && ecs[i]['class'] == this.context.entityclass){
						lbl = (typeof ecs[i].label == 'object' ?  ecs[i].label.data : ecs[i].label);				
						break;
					}
				}
			}
			jQuery(jqid +  " .editor-action-text").html("New " + lbl);
		}
		else {
			jpr(this.context);
		}
	}
	else if (this.context.mode == "harvest"){
		//if(this.context.tool == "model"){
			//jQuery(jqid +  " .editor-action-text").html("Harvest");
			jQuery(jqid +  " .editor-action-head .modelproperty-icon").show();						
		//}		
	}
	else {
		if(this.context.tool == "model" && this.context.modelproperty){
			jQuery(jqid +  " .editor-action-text").html("Update property " + this.context.modelproperty);
			jQuery(jqid +  " .editor-action-head .modelproperty-icon").show();						
		}
		else if(this.context.tool == "model" && this.context.modelclass){
			jQuery(jqid +  " .editor-action-text").html("Update class " + this.context.modelclass);
			jQuery(jqid +  " .editor-action-head .modelclass-icon").show();									
		}
		else if(this.context.tool == "data" && this.context.candidate){
			var lab = this.getCandidateEntityClassLabel(this.current_candidate);
			jQuery(jqid +  " .editor-action-text").html("Update " + lab + " " + this.current_candidate.id);
			jQuery(jqid +  " .editor-action-head .entity-icon").show();												
		}
		else {
			jpr(this.context);
		}
	}
	jQuery(jqid).show();
}

DacuraConsole.prototype.thawNavigation = function(){
	jQuery('#' + this.HTMLID + " .console-overlay").remove();	
	jQuery('#' + this.HTMLID + " .console-blotto").remove();	
	jQuery('#' + this.HTMLID + " .console-clotto").remove();
	if(size(this.client.collections) > 1){
		jQuery('#' + this.HTMLID + " .collection-title-changer").show();
	}
	if(this.context.ontology && size(this.client.collections[this.context.collection].ontologies) > 1){
		jQuery('#' + this.HTMLID + " .ontology-title-changer").show();
	}
	jQuery('#' + this.HTMLID + " .console-browser-buttons").show();
	jQuery('#' + this.HTMLID + " .console-import").show();
	jQuery('#' + this.HTMLID + " .editor-action").hide();
}

/** 
 * Sets the css class of the console to the name of the tool (data|model)
 */
DacuraConsole.prototype.setToolCSSClass = function(){
	if(this.context.tool){
		jQuery('#' + this.HTMLID).removeClass();
		jQuery('#' + this.HTMLID).addClass(this.context.tool);		
	}
}

/**
 * Determines which browser buttons to show to the user
 */
DacuraConsole.prototype.setBrowserButtonVisibility = function(){
	if(this.history.length && this.client.isLoggedIn()){
		jQuery('#' + this.HTMLID + " .browser-button-back").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .browser-button-back").hide();	
	}
	if(this.future.length && this.client.isLoggedIn()){
		jQuery('#' + this.HTMLID + " .browser-button-forward").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .browser-button-forward").hide();
	}	
}

/**
 * Determines which mode icons to display for the user
 */
DacuraConsole.prototype.setModeIconVisibility = function(){
	this.showing_tools = false;//always hidden on draw
	jQuery('#' + this.HTMLID + " .console-mode .mode-icons").hide();		
	jQuery('#' + this.HTMLID + " .console-mode .mode-icons .amode-icon").hide();		
	if(this.context.collection == false){
		return;
	}
	jQuery('#' + this.HTMLID + " .console-mode span.amode-icon.mode-" + this.context.tool + ".mode-active").show();		
	jQuery('#' + this.HTMLID + " .console-mode  .mode-icons").show();	
}

/**
 * Determines which role icons to display for the user
 */
DacuraConsole.prototype.setRoleIconVisibility = function(){
	var hasroles = false;
	jQuery('#' + this.HTMLID + " .console-user-roles .role-icons .console-role").hide();		
	jQuery('#' + this.HTMLID + " .console-user-roles .role-icons").hide();		
	var roles = this.getAvailableRoles();
	if(typeof roles["harvester"] == "undefined"){
		jQuery('#' + this.HTMLID + " .console-user-roles .harvester-role").hide();
	}
	else {
		hasroles = true;
		if(typeof this.active_roles['harvester'] == "undefined"){
			jQuery('#' + this.HTMLID + " .console-user-roles .harvester-role .role-inactive").show();		
		}
		else {
			jQuery('#' + this.HTMLID + " .console-user-roles .harvester-role .role-active").show();		
		}
		jQuery('#' + this.HTMLID + " .console-user-roles .harvester-role").show();		
	}
	if(typeof roles["expert"] == "undefined"){
		jQuery('#' + this.HTMLID + " .console-user-roles .expert-role").hide();
	}
	else {
		hasroles = true;
		if(typeof this.active_roles['expert'] == "undefined"){
			jQuery('#' + this.HTMLID + " .console-user-roles .expert-role .role-inactive").show();		
		}
		else {
			jQuery('#' + this.HTMLID + " .console-user-roles .expert-role .role-active").show();		
		}
		jQuery('#' + this.HTMLID + " .console-user-roles .expert-role").show();		
	}
	if(typeof roles["architect"] == "undefined"){
		jQuery('#' + this.HTMLID + " .console-user-roles .architect-role").hide();
	}
	else {
		hasroles = true;
		if(typeof this.active_roles['architect'] == "undefined"){
			jQuery('#' + this.HTMLID + " .console-user-roles .architect-role .role-inactive").show();		
		}
		else {
			jQuery('#' + this.HTMLID + " .console-user-roles .architect-role .role-active").show();		
		}
		jQuery('#' + this.HTMLID + " .console-user-roles .architect-role").show();		
	}
	if(hasroles && this.showing_roles){
		jQuery('#' + this.HTMLID + " .console-user-roles").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-user-roles").hide();
	}
};

/**
 * Determines the visibility of the result icons
 */
DacuraConsole.prototype.setResultVisiblity = function(){
	if(this.action_result){
		jQuery('#' + this.HTMLID + " div.console-resultbox").show();		
	}
	else {
		jQuery('#' + this.HTMLID + " div.console-resultbox").hide();
	}
}

/**
 * Determines which parts of the context to show 
 */
DacuraConsole.prototype.setCollectionContextVisibility = function(){
	if(!this.client.isLoggedIn()){
		return jQuery('#' + this.HTMLID + " .console-context").hide();
	}
	var ocol = jQuery('#' + this.HTMLID + " .console-collection .context-select-holder select").val();
	if(!this.context.collection){
		if(ocol){
			jQuery('#' + this.HTMLID + " .console-collection .context-select-holder select").val("").trigger("change.select2");
		}
		jQuery('#' + this.HTMLID + " .context-collection-picked").hide();
		jQuery('#' + this.HTMLID + " .console-context").hide();
		jQuery('#' + this.HTMLID + " .console-collection .context-select-holder").show();		
	}
	else {
		if(ocol != this.context.collection){
			jQuery('#' + this.HTMLID + " .console-collection .context-select-holder select").val(this.context.collection).trigger("change");			
		}
		jQuery('#' + this.HTMLID + " .context-collection-picked").show();		
		jQuery('#' + this.HTMLID + " .console-collection .context-select-holder").hide();
		jQuery('#' + this.HTMLID + " .console-context .context-element").hide();
		if(this.context.tool == "model"){
			this.setModelContextVisiblity();
		}
		else {
			this.setDataContextVisiblity();
		}
		jQuery('#' + this.HTMLID + " .console-context").show();
	}
}


/* functions to display stuff on the expanded versions of the console */

DacuraConsole.prototype.hideFullExtra = function(setdisp){
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full .console-full-section").hide();
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full").hide();
	if(setdisp) this.showing_extra = false;	
	this.removeFrameviewer();
}



/**
 * Draws the label field in the summary line
 * @param type the type (candidate, class, property)
 * @param ldo the linked data object in question DacuraLDO
 */ 
DacuraConsole.prototype.setSummaryLabel = function(type, ldo){
	var lab = ldo.getLabel(true);
	if(!lab){
		lab = ldo.id + " (no label)";
	}
	var cmt = ldo.getComment(true);
	if(!cmt) cmt = "";
	if(type == "candidate"){
		var clab = this.getCandidateEntityClassLabel(ldo);
		var ecs = this.getEntityClasses();
		var ec = ldo.entityClass(ecs);
		lab = clab + " " + lab;
		cmt = ldo.id + " a " + ec['class'] + " at " + ldo.meta.cwurl + ". " + cmt;
	}
	var labinput = ldo.getLabel(false);
	if(!labinput) { labinput = "";}
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-label input").val(labinput);
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-id input").val(ldo.id);
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-label").attr("title", escapeHtml(cmt)).html(lab);
}

/**
 * Determines the visibility of the icons in the summary line
 * @param type candidate|class|property
 * @param subtype (subtype of type - class: entity, simple, complex, enumerated)
 * @param ldo linked data object
 */
DacuraConsole.prototype.setSummaryIconVisibility = function(type, subtype, ldo){
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .state-icon").hide();
	//jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .context-action").hide();
	if(ldo){
		var statt = "Status: " + ldo.meta.status + ". " + "Version: " + ldo.meta.version; 
		jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .state-icon." + ldo.meta.status).attr("title", statt).show();
		if(this.active_roles['architect']){
			jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-actions .summary-delete").show();
			if(type != "candidate"){
				jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-actions .summary-edit").show();		
			}
		}
		if(type == "candidate" && this.active_roles['harvester']){
			if(this.context.mode == "view"){
				jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .summary-view").hide();	

				jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .summary-edit").show();	
			}
			else {
				jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .summary-edit").hide();	
				jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .summary-view").show();	
				
			}
			//jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .context-close-candidate").show();				
		}
	}
	else {
		jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-icons .summary-element-type ." + type).show();
	}
}


/**
 * Sucks the data out of the frameviewer into a linked data object
 */
DacuraConsole.prototype.getFrameInputs = function(){
	var resp = [];
	if(this.frameviewer){
		resp = this.frameviewer.extract();
	}
	return resp;
}

DacuraConsole.prototype.setMetadataVisibility = function(mode){
	var show = false;
	jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer .metadata-item").hide();
	if((mode != "view" && mode != "external") || (this.current_meta.harvested && this.current_meta.harvested.length)){
		jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer .metadata-harvested").show();
		show = true;
	}
	if((mode == 'create' && this.model_create_clicked == "property") || (mode == "edit" && this.context.modelproperty) || (this.current_meta.harvests && this.current_meta.harvests.length)){
		jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer .metadata-harvests").show();		
		show = true;
	}
	if(show){
		jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer").show();		
	}
	else {
		jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer").hide();		
	}
}



DacuraConsole.prototype.hideHarvester = function(){
	jQuery('#' + this.HTMLID + " .console-import .console-import-tool").hide();
}

DacuraConsole.prototype.showHarvester = function(){
	jQuery('#' + this.HTMLID + " .console-import .console-import-tool").show("slide");
}


/**
 * Changes the labels on the buttons on the editor screen
 */
DacuraConsole.prototype.setSubmitButtonLabels = function(type, label){
	if(this.context.mode == "create"){
		jQuery("#console-button-test").button('option', 'label', "Test new " + type);
		jQuery("#console-button-commit").button('option', 'label', "Create new " + type);		
		jQuery("#console-button-cancel").button('option', 'label', "Cancel");
	}
	else {
		jQuery("#console-button-delete").button('option', 'label', "Delete " + type);
		jQuery("#console-button-cancel").button('option', 'label', "Cancel changes");
		jQuery("#console-button-test").button('option', 'label', "Test changes");
		jQuery("#console-button-commit").button('option', 'label', "Update " + type);
	}
}


//utility function to add hover and attach event to click for icons that are used as buttons
function linkifyElement(jqelement, action){
	jQuery('#dacura-console' + " " + jqelement).hover(function(){
		jQuery(this).addClass('uhover');
	}, function() {
		jQuery(this).removeClass('uhover');
	}).click(action);
}

DacuraConsole.prototype.displayFactoids = function(){
/*	var self = this;
	var valueTester = function(handler, upd, datapoint){
		self.testFrameValue(handler, upd, datapoint, self.request_rate_limit);
	}
	var bringFrames = function(frames){
		if(!self.frameviewer){
			self.frameviewer = new FrameViewer(self.client.getCandidateList(), self.getEntityClasses(true));
			self.frameviewer.init(self.context.entityclass, frames, valueTester);
		}
		self.pagescanner.displayFactoids(self.frameviewer, self.context.entityclass, self.context.candidate, frames);		
	}
	if(this.context.entityclass){
		this.initEntityContext(this.context.entityclass, bringFrames);
	}
	else {*/
		if(!this.context){
			alert("no context");
		}
		else if(this.context.tool == "model"){
			var showcont = function(foid){
				return foid.original.after;
			}
			var showfoid = function(part){
				noshows = ['value', 'locator'];
				if(noshows.indexOf(part) == -1){
					return true;
				}
				return false;
			}
		}
		else {
			var showcont = function(foid){
				return foid.original.after;
			}
			var showfoid = function(part){
				noshows = ['locator'];
				if(noshows.indexOf(part) == -1){
					return true;
				}
				return false;
			}			
		}
		this.pagescanner.displayFactoids(showcont, showfoid);
	
}

DacuraConsole.prototype.importPage = function(){
	this.imported = true;
	this.pagescanner = new DacuraPageScanner(this.jquery_body_selector, this.body_before_dacura);
	var harvested = this.client.getHarvestedConnectors();
	var self = this;
	var comp = function(){
		if(size(self.pagescanner.factoids)){
			self.showHarvester();	
			self.displayFactoids(); 
			self.initFactoidSelector();
		}
		if(self.context.tool == "data" && self.parser_url){
			var func = function(){
				self.displayFactoids();
			}
			self.pagescanner.parseValues(self.parser_url, func);
		}
		//self.initPropertySelector("factoid-import-picker");
		//jQuery('#' + self.HTMLID + " .import-properties").html(self.pagescanner.getScanSummaryHTML());
		//jQuery('#' + self.HTMLID + " .console-page-summary").show();
	}
	//if(this.context.tool == "model"){
	//	var connectors = this.current_ontology.getRelevantConnectors(bareURL());
	//}
	//else {
		var connectors = this.client.getHarvestConnectors();
	//}
	this.pagescanner.scan(connectors, harvested, comp); 
}

DacuraConsole.prototype.pageHasFactoids = function(){
	if(!this.pagescanner || !this.pagescanner.factoids) return false;
	return size(this.pagescanner.factoids);
}

DacuraConsole.prototype.initFactoidSelector = function(){
	jQuery('#' + this.HTMLID + " .pagescan-stats .page-stat").hide();
	if(this.context.tool == "model")
		var statsnoshow = ["empty", "datapoints"]; 
	else {
		var statsnoshow = [];
	}
	for(var i in this.pagescanner.stats){
		if(statsnoshow.indexOf(i) == -1){
			jQuery('#' + this.HTMLID + " .pagescan-stats .page-stat." + i).show();
			jQuery('#' + this.HTMLID + " .pagescan-stats .page-stat." + i + " .page-stat-value").html(this.pagescanner.stats[i].value);			
		}
	}
	if(this.context.factoid){
		jQuery('#' + this.HTMLID + " .pagescan-navigator .current-factoid").html(this.context.factoid);
	}
	if(this.context.mode == "harvest" && this.pagescanner.stats.variables.value > 0){
		jQuery('#' + this.HTMLID + " .pagescan-navigator").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .pagescan-navigator").hide();
	}
	var self = this;
	jQuery("div.embedded-factoid-header").click(function(){
		var uniqid = this.getAttribute("data-value");
		for(var j = 0; j<self.pagescanner.sequence.length; j++){
			if(self.pagescanner.sequence[j] == uniqid){
				self.userChangesContext({factoid: j});
			}
		}
	});
}

DacuraConsole.prototype.updateImportStats = function(){}

DacuraConsole.prototype.updateFactoidSelector = function(type){
	nfoid = false;
	if(this.pagescanner.sequence.length){
		if(type == "first"){
			nfoid = 0;
		}
		else if(type == "last"){
			nfoid = this.pagescanner.sequence.length -1;
		}
		else if(type == "next"){
			if((this.context.factoid !== false) && this.context.factoid < (this.pagescanner.sequence.length -1)){
				nfoid = this.context.factoid+1;
			}
			else if(this.context.factoid === false){
				nfoid = 0;
			}
		}
		else {
			if((this.context.factoid !== false) && this.context.factoid > 0){
				nfoid = this.context.factoid-1;
			}
			else if(this.context.factoid === false){
				nfoid = 0;
			}			
		}
		if(nfoid !== false){
			this.userChangesContext({factoid: nfoid});
		}
	}
}

DacuraConsole.prototype.displayFactoid = function(){
	if(!(this.pagescanner && this.pagescanner.sequence && this.pagescanner.sequence.length)){
		alert("Can't display factoid as no facts scanned");
		return false;
	}
	if(this.context.factoid === false){
		this.context.factoid = 0;
	}
	jQuery('#' + this.HTMLID + " .pagescan-navigator .current-factoid").html(this.context.factoid + 1);	
	var foid = this.pagescanner.factoids[this.pagescanner.sequence[this.context.factoid]];
	var harvests = foid.getHarvests();
	if(this.context.tool == "model"){
		var impto = (harvests ? harvests : "");
		this.setFactoidImportTarget(impto);
	}
	var pl = foid.pagelocator;
	jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-detail").hide();
	jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat").hide();
	if(pl.label){
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-label .factoid-value").html(pl.label);
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-label").show();
	}
	if(pl.sectext){
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-section .factoid-value").html(pl.sectext);
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-section").show();
		
	}
	else if (pl.section){
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-section .factoid-value").html(pl.section);
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-section").show();		
	}
	if(pl.sequence){
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-sequence .factoid-value").html(pl.sequence);
		jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-sequence").show();				
	}
	if(this.context.tool == "data"){
		if(foid.parsed){
			jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-data .factoid-value").html(foid.parsed.value);
			jQuery('#' + this.HTMLID + " .pagescan-factoid .factoid-data").show();				
		}
		var cls = foid.getDataSyntaxClass();
		jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat."+cls).show();
		var ocls = foid.connectionCategory();
		jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat."+ocls).show();
		if(ocls == "unknown"){
			//show the import to any old thing option
		}
		else {
			//var harvested = foid.getHarvested();
			//if(harvested){
			//	jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat.harvested").show();
			//}
			if(harvests){
				jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat.harvests").show();
				//show the selected import map with an option to choose the target
			}
		}
		if(foid.parsed && foid.parsed.datapoints && foid.parsed.datapoints.length){
			jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat.datapoints .factoid-stat-value").html(foid.parsed.datapoints.length);
			jQuery('#' + this.HTMLID + " .pagescan-factoid-stats .factoid-stat.datapoints").show();
		}
		jQuery('#' + this.HTMLID + " .pagescan-factoid-import").hide();
		jQuery('#' + this.HTMLID + " .pagescan-factoid-data-import").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .pagescan-factoid-import").show();
		jQuery('#' + this.HTMLID + " .pagescan-factoid-data-import").hide();
	}
	jQuery('#' + this.HTMLID + " .pagescan-factoid").show();
	if(this.context.tool == "model"){
		jQuery('#' + this.HTMLID + " .console-page-summary").show();
		jQuery('#' + this.HTMLID + " .console-extra").show();
	}
	this.pagescanner.scrollTo(foid.uniqid, "#" + this.HTMLID);
	if(this.context.tool == "data"){
		this.setDataImportTarget(foid);
	}
}

DacuraConsole.prototype.unimportPage = function(){
	this.imported = false;
}

DacuraConsole.prototype.setFactoidImportTarget = function(targets){
	var rtargets = [];
	if(typeof targets == 'object' && targets.length){
		for(var i = 0; i<targets.length; i++){
			if(targets[i].type == "harvests"){
				rtargets.push(targets[i])
			}
		}
	}
	if(rtargets.length){
		var targ = rtargets[0].target;
		if(this.importprops.indexOf(targ) === -1){
			jQuery("#" + this.HTMLID + " select.factoid-import-picker").append("<option value='" + targ + "' selected>" + targ + "</option>");		
			this.importprops.push(targ);
		}
	}
	else {
		var targ = "";
	}
	jQuery('#' + this.HTMLID + " select.factoid-import-picker").val(targ).trigger("change.select2");
	
}

DacuraConsole.prototype.getFactoidImportTarget = function(){
	return jQuery('#' + this.HTMLID + " select.factoid-import-picker").val();
}


DacuraConsole.prototype.showUpdateImportButton = function(){
	jQuery('#' + this.HTMLID + " .factoid-import-buttons").show();
}

DacuraConsole.prototype.hideUpdateImportButton = function(){
	jQuery('#' + this.HTMLID + " .factoid-import-buttons").hide();
}



function throttle(func, wait) {
    var timeout;
    return function() {
        var context = this, args = arguments;
        if (!timeout) {
            // the first time the event fires, we setup a timer, which 
            // is used as a guard to block subsequent calls; once the 
            // timer's handler fires, we reset it and create a new one
            timeout = setTimeout(function() {
                timeout = null;
                func.apply(context, args);
            }, wait);
        }
    }
}

