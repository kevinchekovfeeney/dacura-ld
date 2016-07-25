var internal_reference_to_console = false;

function DacuraConsole(config){
	//dacura url
	$.fn.select2.defaults.set('dropdownCssClass', 's2option');
	this.durl = config.durl;
	this.autoload = (config.autoload ? true : false);
	this.HTMLID = config.id;
	this.menu_pconfig = {
		resultbox: "#" + this.HTMLID + "-menu-message",
		busybox: "#" + this.HTMLID + "-menu-busybox"
	};
	this.ModelBuilder = new DacuraModelBuilder();
	this.client = new DacuraClient(this.durl);
	this.context = {
		mode: "view",//view|update|create
		collection: false,
		tool: false,
		graph: false,
		ontology: false,
		modelclass: false,
		modelproperty: false,
		candidate: false,
		entityclass: false, 
		candidateproperty: false
	};
	this.displayed_properties = [];
	this.entity_property_count = 0;
	this.current_ontology = false;
	this.current_candidate = false;
	this.current_action = false;
	this.showing_roles = false;
	this.showing_tools = false;
	this.network_errors = [];
	this.history = [];
	this.active_roles = [];
	this.future = [];
	internal_reference_to_console = this;//just so we can refer to it from <a href='javascript: stuff
}

//we try to draw all the necessary html in the init functions
//then just manipulate the visibility of elements in response to user actions
DacuraConsole.prototype.init = function(success, fail){
	var self = this;
	var nfail = function(title, msg, extra){
		self.initMenu();
		self.hideAll();
		jQuery('#' + self.HTMLID).show("drop", {direction: "up"});
		self.showResult("error", title, msg, extra);
		if(typeof fail == "function"){
			fail(title, msg, extra);
		}
	}
	var nsuccess = function(caps){	
		if(typeof caps.context == "object"){
			self.context = caps.context;
			self.modelBuilder.init(self.context);
		}
		if(self.autoload){
			self.current_action = "Pre-loading resources from server";
			self.client.loadAll(fail);
		}
		self.initMenu();
		self.showMenu();
		jQuery('#' + self.HTMLID).show("drop", {direction: "up"});
		success(caps);
	}
	this.client.busy = function(){
		self.showBusy();
	}
	this.client.notbusy = function(){
		self.clearBusy();
	}
	this.current_action = "Initialising Dacura Client";
	this.client.init(nsuccess, nfail);	
}

/**
 * Initialises the menu by setting up all the events that are associated with user actions 
 * All event handling is done here...
 */
DacuraConsole.prototype.initMenu = function(){
	var self = this;
	jQuery('#' + this.HTMLID + " img").each(function(){
		jQuery(this).attr("src", self.durl + "phplib/services/console/" + jQuery(this).attr("src"));
	});
	jQuery('#' + this.HTMLID + " div.console-user-close").click(function(){
		jQuery('#' + self.HTMLID).hide("slide", {direction: "up"});
	});
	if(this.client.isLoggedIn()){
		self.initCollectionContext();
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
		jQuery('#' + this.HTMLID + " .context-add-element").click(function(){
			self.userChangesContext({mode: "create"});				
		});
		jQuery('#' + this.HTMLID + " .context-add-property").click(function(){
			if(self.context.candidate){
				var prop = jQuery('#' + self.HTMLID + " select.context-candidateproperty-picker").val();
				jQuery('#' + self.HTMLID + " .console-context .candidateproperty .context-add-property").hide("fade");				
			}
			else {
				var prop = jQuery('#' + self.HTMLID + " select.context-entityproperty-picker").val();	
				//jQuery('#' + self.HTMLID + " .console-context .entityproperty .context-add-property").hide("fade");				
			}
			self.addPropertyToDisplay(prop);
		});
		jQuery('#' + this.HTMLID + " .console-mode .mode-active").click(function(){
			if(typeof talready_clicked == "undefined" || !talready_clicked){
				talready_clicked = true;
				var comp = function(){talready_clicked = false;}
				if(self.showing_tools){
					self.showing_tools = false;
					jQuery('#' + self.HTMLID + " .console-mode span.amode-icon.mode-inactive:not(.mode-" + self.context.tool + ")").hide("slide", {direction: 'left', duration: "fast", complete: comp});
				}
				else {
					self.showing_tools = true;
					jQuery('#' + self.HTMLID + " .console-mode span.amode-icon.mode-inactive:not(.mode-" + self.context.tool + ")").show("slide", {direction: 'left', duration: "fast", complete: comp});					
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
		jQuery('#' + this.HTMLID + " .console-extra .summary-close").click(function(){
			var ncontext = {};
			if(self.context.mode != "view"){
				ncontext.mode = 'view';
			}
			if(self.context.candidate){
				ncontext.candidate = false;
			}
			else if(self.context.modelclass){
				ncontext.modelclass = false;
			}
			else if(self.context.modelproperty){
				ncontext.modelproperty = false;
			}
			self.userChangesContext(ncontext);
		});	
		jQuery('#' + this.HTMLID + " .console-extra .summary-dacura").click(function(){
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
				//window.location.href = self.current_ontology.meta.cwurl;
			}
			else {
				alert("Error - no url could be determined for this entity");
			}
		});
		jQuery('#' + this.HTMLID + " .console-extra .summary-edit").click(function(){
			self.userChangesContext({mode: "edit"});				

			/*if(self.context.candidate){
				alert("set edit mode for candidate " + self.context.candidate);
			}
			else if(self.context.modelclass){
				alert("set edit mode for class" + self.context.modelclass);
			}
			else if(self.context.modelproperty){
				alert("set edit mode for property" + self.context.modelproperty);
			}*/
		});	
		jQuery('#' + this.HTMLID + " .console-extra .summary-delete").click(function(){
			if(self.context.candidate){
				alert("delete candidate " + self.context.candidate);
			}
			else if(self.context.modelclass){
				alert("delete class" + self.context.modelclass);
			}
			else if(self.context.modelproperty){
				alert("delete property" + self.context.modelproperty);
			}
		});	
		jQuery('#' + this.HTMLID + " .console-extra .summary-create .docreate").click(function(){
			if(self.context.tool == "data"){
				self.createCandidate();
			}
		});
		jQuery('#' + this.HTMLID + " .console-extra .summary-create .testcreate").click(function(){
			if(self.context.tool == "data"){
				self.createCandidate(true);
			}
		});
	}
}

DacuraConsole.prototype.createCandidate = function(test){
	var self = this;
	var basics = self.getNewEntityDetailsFromForm();
	var fa = function(title, msg, extra){
		self.showResult("error", title, msg, extra);
	};
	var su = function(x){
		jpr(x);
	}
	self.client.create("candidate", basics, su, fa, test);
}

/**
 * Called when the collection context is changed - sets up the various selectors for the collection
 */
DacuraConsole.prototype.initCollectionContext = function(){
	var cols = this.client.collections;
	var self = this;
	var html = "<option value=''>Select a Collection</option>";
	for(var colid in cols){
		var sel = (colid == this.context.collection ? " selected" : "");
		html += "<option" + sel + " value='" + colid + "'>" + cols[colid].title + "</option>"; 
	}
	jQuery('#' + this.HTMLID + " select.context-collection-picker").html(html);
	jQuery('#' + this.HTMLID + " select.context-collection-picker").select2({
		  placeholder: "Select a collection",
		  allowClear: true,
		  minimumResultsForSearch: 10,
		  templateResult: function(state){
			  if (!state.id) { return state.text; }
			  return jQuery("<span><img height='14' src='" + cols[state.id].icon + "'> " + cols[state.id].title + "</span>");
		  }
	});
	if(this.context.collection){
		var html = "<a class='collection-dacura-home' href='" + cols[this.context.collection].url + "' title='Visit the collection home page on Dacura'>";
		html += "<img class='collection-icon' src='" + cols[this.context.collection].icon + "'></a>";
		jQuery('#' + this.HTMLID + " .context-collection-picked .collection-title-icon").html(html);
		jQuery('#' + this.HTMLID + " .context-collection-picked .collection-title-text").attr("title", cols[this.context.collection].title).html(cols[this.context.collection].title);
		jQuery('#' + this.HTMLID + " .console-collection .context-select-holder").hide();
		jQuery('#' + this.HTMLID + " .console-collection").show();
		jQuery('#' + self.HTMLID + " .context-collection-picked").show();
		if(size(cols) == 1){
			jQuery('#' + this.HTMLID + " .console-collection span.collection-title-changer").hide();
		}
		jQuery('#' + this.HTMLID + " .console-collection span.collection-title-changer").click(function(){
			var f  = function(){jQuery('#' + self.HTMLID + " .console-collection .context-select-holder").show("fade")};
			jQuery('#' + self.HTMLID + " .console-collection .context-collection-picked").hide("fade", f);
		});
		this.initCollectionEntityClasses();
		this.initCollectionCandidates();
		this.initCollectionOntologies();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-collection").show();
		jQuery('#' + self.HTMLID + " .console-collection .context-select-holder").show();
	}
	jQuery('#' + this.HTMLID + " select.context-collection-picker").on('change', function(){
		self.userChangesContext({"collection": this.value });
	});
}

/**
 * Sets up the list of candidates for the collection
 */
DacuraConsole.prototype.initCollectionCandidates = function(){
	var self = this;
	jQuery('#' + this.HTMLID + " select.context-candidate-picker").html("");
	var col = this.client.collections[this.context.collection];
	if(col.entity_classes.length > 1){
		if(size(col.candidates) > 0){
			html = "<option value=''>Select a Candidate</option>";
			if(this.context.entityclass){
				for(var candid in col.candidates[this.context.entityclass]){
					var sel = (candid == this.context.candidate ? " selected" : "");
					html += "<option" + sel + " value='" + candid + "'>" + candid + "</option>"; 
				}
			}
			else {
				for(var etype in col.candidates){
					html += "<optgroup label='" + etype + "'>";
					for(var candid in col.candidates[etype]){
						var sel = (candid == this.context.candidate ? " selected" : "");
						html += "<option" + sel + " value='" + candid + "'>" + candid + "</option>"; 	
					}
					html += "</optgroup>";
				}				
			}
			jQuery('#' + this.HTMLID + " select.context-candidate-picker").html(html);
			jQuery('#' + this.HTMLID + " select.context-candidate-picker").select2({
				  placeholder: "Select a Candidate",
				  minimumResultsForSearch: 10,
				  allowClear: true
			}).on('change.select2', function(){
				self.userChangesContext({"candidate": this.value });
			});;
		}
	}	
}

/**
 * Sets up the list of entity classes for the collection
 */
DacuraConsole.prototype.initCollectionEntityClasses = function(){
	jQuery('#' + this.HTMLID + " select.context-entityclass-picker").html("");
	var col = this.client.collections[this.context.collection];
	var self = this;
	var html = "<option value=''>Select a Type</option>";
	if(col.entity_classes.length > 1){
		for(var i = 0; i<col.entity_classes.length; i++){
			if(col.entity_classes[i].id == "owl:Nothing") continue;
			var sel = (col.entity_classes[i]['id'] == this.context.entityclass ? " selected" : "");
			var lbl = (typeof col.entity_classes[i].label == 'object' ?  col.entity_classes[i].label.data : col.entity_classes[i].label);
			if(!lbl) lbl = col.entity_classes[i].id;
			html += "<option" + sel + " value='" + col.entity_classes[i].id + "'>" + lbl + "</option>"; 
		}
		jQuery('#' + this.HTMLID + " select.context-entityclass-picker").html(html);
		jQuery('#' + this.HTMLID + " select.context-entityclass-picker").select2({
			  placeholder: "Select a Type",
			  minimumResultsForSearch: 10,
			  allowClear: true
		}).on('change', function(){
			self.userChangesContext({"entityclass": this.value });
		});
	}
}

/**
 * Called when an entity class is selected (for generating the entity property list)
 * @param cb callback success function
 */
DacuraConsole.prototype.initEntityContext = function(cb){
	var self = this;
	var initcon = function(frame){
		self.initEntityPropertySelector(frame);
		cb();
	};
	var failcand = function(title, msg, extra){
		self.showResult("error", title, msg, extra);
	};
	this.current_action = "Loading class frame from server";
	this.client.getEmptyFrame(this.context.entityclass, initcon, failcand);
}

/**
 * Initialises the list of available properties for a candidate
 * @param props array of properties to be included
 */
DacuraConsole.prototype.initEntityPropertySelector = function(frame){
	jQuery('#' + this.HTMLID + " select.context-entityproperty-picker").html("");
	if(!this.context.collection) return;
	this.entity_property_count = frame.length;
	var col = this.client.collections[this.context.collection];
	var self = this;
	var html = "<option value=''>Select a Property</option>";
	for(var i = 0; i<frame.length; i++){
		var sel = (self.context.candidateproperty == frame[i].property) ? " selected" : "";
		var lab = (typeof frame[i].label == "object" ? frame[i].label.data :  frame[i].property.after("#"));
		html += "<option" + sel + " value='" + frame[i].property + "'>" + lab + "</option>"; 	
		jQuery('#' + self.HTMLID + " select.context-entityproperty-picker").html(html);
		jQuery('#' + self.HTMLID + " select.context-entityproperty-picker").select2({
			  placeholder: "Select a Property",
			  allowClear: true,
			  minimumResultsForSearch: 10		  
		}).on('change', function(){
			//alert(this.value + " is the property");
			//self.userChangesContext({"candidateproperty": this.value });//overload this for ease of context switching.
		});
	}
}


/**
 * Called when a candidate is selected (for generating the candidate property list
 * @param cb callback success function
 */
DacuraConsole.prototype.initCandidateContext = function(cb){
	var self = this;
	var initcon = function(cand){
		self.current_candidate = cand;
		self.initCandidatePropertySelector(cand.getProperties(self.context.mode == "view"));
		cb();
	};
	var failcand = function(title, msg, extra){
		self.showResult("error", title, msg, extra);
	};
	this.current_action = "Loading class Candidate from server";
	this.client.get("candidate", this.context.candidate, initcon, failcand);
}

/**
 * Initialises the list of available properties for a candidate
 * @param props array of properties to be included
 */
DacuraConsole.prototype.initCandidatePropertySelector = function(props){
	jQuery('#' + this.HTMLID + " select.context-candidateproperty-picker").html("");
	if(!this.context.collection) return;
	var col = this.client.collections[this.context.collection];
	var self = this;
	var html = "<option value=''>Select a Property</option>";
	for(var i = 0; i<props.length; i++){
		var sel = (self.context.candidateproperty == props[i].id) ? " selected" : "";
		html += "<option" + sel + " value='" + props[i].id + "'>" + props[i].label + "</option>"; 	
		jQuery('#' + self.HTMLID + " select.context-candidateproperty-picker").html(html);
		jQuery('#' + self.HTMLID + " select.context-candidateproperty-picker").select2({
			  placeholder: "Select a Property",
			  minimumResultsForSearch: 10		  
		}).on('change', function(){
			self.context.candidateproperty = this.value;
			if(this.value && this.value.length && (self.displayed_properties.indexOf(this.value) === -1)){
				jQuery('#' + self.HTMLID + " .console-context .candidateproperty .context-add-property").show("fade");
			}
			else {
				jQuery('#' + self.HTMLID + " .console-context .candidateproperty .context-add-property").hide("fade");				
			}
		});
	}
}

/**
 * Initialises the list of ontologies associated with a collection
 */
DacuraConsole.prototype.initCollectionOntologies = function(){
	if(!this.context.collection) return;
	var col = this.client.collections[this.context.collection];
	var self = this;
	var html = "<option value=''>Select an Ontology</option>";
	if(size(col.ontologies) > 0){
		for(var ontid in col.ontologies){
			var sel = (ontid == this.context.ontology ? " selected" : "");
			html += "<option" + sel + " value='" + ontid + "'>" + col.ontologies[ontid].title + "</option>"; 
		}
		jQuery('#' + this.HTMLID + " select.context-ontology-picker").html(html);
		jQuery('#' + this.HTMLID + " select.context-ontology-picker").select2({
			  placeholder: "Select an ontology",
			  allowClear: true,
			  minimumResultsForSearch: 10		  
		}).on('change', function(){
			self.userChangesContext({"ontology": this.value });
		});
	}	
}

/**
 * Initialises the contents of the ontology (classes & properties)
 */
DacuraConsole.prototype.initOntologyContents = function(cb){
	var self = this;
	jQuery('#' + self.HTMLID + " select.context-modelclass-picker").html("");
	jQuery('#' + self.HTMLID + " select.context-modelproperty-picker").html("");
	if(!this.context.collection || !this.context.ontology) {
		return cb();
	}
	var populateOntologySelects = function(ont){
		self.current_ontology = ont;
		if(typeof ont.classes != "undefined" && size(ont.classes) > 0){
			var ph = "Select Class (" + size(ont.classes) + ")";
			var html = "<option value=''>" + ph + "</option>";
			for(var i in ont.classes){
				var sel = (self.context.modelclass && (i == self.context.modelclass)) ? " selected" : "";
				html += "<option value='" + i + "'" + sel + ">" + ont.getClassLabel(i) + "</option>";
			}
			jQuery('#' + self.HTMLID + " select.context-modelclass-picker").html(html);
			jQuery('#' + self.HTMLID + " select.context-modelclass-picker").select2({
				  placeholder: ph,
				  allowClear: true,
				  minimumResultsForSearch: 10		  
			}).on('change', function(){
				self.userChangesContext({ "modelclass": this.value });
			});
		}
		if(typeof ont.properties != "undefined" && size(ont.properties) > 0){
			var ph = "Select Property (" + size(ont.properties) + ")";
			var html = "<option value=''>" + ph + "</option>";
			for(var i in ont.properties){
				var sel = (self.context.modelproperty && (i == self.context.modelproperty)) ? " selected" : "";
				html += "<option value='" + i + "'" + sel + ">" + ont.getPropertyLabel(i) + "</option>";
			}
			jQuery('#' + self.HTMLID + " select.context-modelproperty-picker").html(html);
			jQuery('#' + self.HTMLID + " select.context-modelproperty-picker").select2({
				  placeholder: ph,
				  allowClear: true,
				  minimumResultsForSearch: 10		  
			}).on('change', function(){
				self.userChangesContext({"modelproperty": this.value });
			});
		}
		cb();
	}
	var ontologyFail = function(tit, msg, extra){
		self.showResult("error", tit, msg, extra);
	}
	this.current_action = "Loading ontology from server";
	this.client.get("ontology", this.context.ontology, populateOntologySelects, ontologyFail);	
}

/* After initialisations, the system works by manipulating the visibility of elements */

/**
 * Core function - called whenever a user changes their context
 * When a user does something, the context change is passed to this function and this then calculates what needs to happen
 * @param ncontext - array with the updated context elements in it 
 * @param special (back|forward|other) -> browser actions need special treatment
 */
DacuraConsole.prototype.userChangesContext = function(ncontext, special){
	menu_shown = false;
	this.displayed_properties = [];//always reset on context change
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
		this.context.collection = ncontext.collection;
		if(this.context.collection){
			this.client.current_collection = this.context.collection;
			if(!this.context.tool){
				this.context.tool = "data";
			}
			this.initCollectionContext();
		}
	}
	if(typeof ncontext.tool == "string" && ncontext.tool != this.context.tool){
		this.context.tool = ncontext.tool;
	} 
	if(typeof ncontext.mode == "string") this.context.mode = ncontext.mode;
	if(typeof ncontext.candidate != "undefined" && this.context.candidate != ncontext.candidate){
		this.context.candidate = ncontext.candidate;
		if(this.context.candidate){
			var col = this.client.collections[this.context.collection];
			var cands = col.candidates;
			for(var etype in cands){
				if(typeof cands[etype][ncontext.candidate] != "undefined"){
					ncontext.entityclass = etype;
					break;
				}
			}
			var self = this;
			var cb = function(){
				self.showMenu();
			}
			menu_shown = true;
			this.initCandidateContext(cb);
		}
	}
	if(typeof ncontext.entityclass != "undefined" && this.context.entityclass != ncontext.entityclass){
		this.context.entityclass = ncontext.entityclass;
		if(this.context.entityclass){
			var ontid = this.context.entityclass.split(":")[0];
			var col = this.client.collections[this.context.collection];
			if(typeof col.ontologies[ontid] != "undefined"){
				this.context.ontology = ontid;
				this.context.modelclass = this.context.entityclass;
			}
			var self = this;
			var cb = function(){
				self.showMenu();
			}
			menu_shown = true;
			this.initCollectionCandidates();
			this.initEntityContext(cb);
		}
		else {
			this.initCollectionCandidates();
		}
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
	else if(typeof ncontext.modelproperty != "undefined"){
		this.context.modelproperty = ncontext.modelproperty;
		if(this.context.modelproperty){
			this.context.modelclass = false;
		}
	}
	if(typeof ncontext.ontology != "undefined"){
		this.context.ontology = ncontext.ontology;
		if(this.context.ontology == "") this.context.ontology = false;
		var self = this;
		var cb = function(){
			self.showMenu();
		}
		menu_shown = true;
		this.initOntologyContents(cb);
	}
	if(!menu_shown) {
		this.showMenu();
	}
	this.client.context = this.context;
}

/**
 * Resets the context to default
 */
DacuraConsole.prototype.resetContext = function(){
	this.context.mode = "view";
	this.context.collection = false;
	this.context.ontology = false;
	this.context.modelclass = false;
	this.context.modelproperty = false;
	this.context.candidate = false,
	this.context.entityclass = false, 
	this.context.candidateproperty = false;
};

/**
 * Hides all elements on the topbar bar the branding and close buttons (which we always want)
 */
DacuraConsole.prototype.hideAll = function(){
	jQuery('#dacura-console .menu-area').hide();
	jQuery('#dacura-console .menu-area.console-branding').show();	
	jQuery('#dacura-console .menu-area.console-user-close').show();	
	console-collection
};

/**
 * Disables navigation elements on the top bar 
 */
DacuraConsole.prototype.freezeNavigation = function(){
	freezeElement('#' + this.HTMLID + " .console-branding", 0.2, "console-overlay");
	freezeElement('#' + this.HTMLID + " .console-collection", 0.2, "console-overlay");
	freezeElement('#' + this.HTMLID + " .console-mode", 0.2, "console-overlay");
	freezeElement('#' + this.HTMLID + " .console-user-logged-in", 0.2, "console-overlay");	
	freezeElement('#' + this.HTMLID + " .entityclass", 0.2, "console-overlay");	
}

DacuraConsole.prototype.thawNavigation = function(){
	$('#' + this.HTMLID + " .console-overlay").remove();	
}


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

/**
 * Main visibility defining function - draws the console according to the current context
 */
DacuraConsole.prototype.showMenu = function(){
	this.setFrozenElements();
	this.setToolCSSClass();
	this.setBrowserButtonVisibility();
	this.setModeIconVisibility();
	this.setRoleIconVisibility();
	this.setResultVisiblity();
	this.setCollectionContextVisibility();
	if(!this.client.isLoggedIn()){
		this.showLogin();
	}
	else {
		this.showUserIcon();
	}
	if(this.context.tool == "data" && this.context.candidate){
		this.displayCandidate();
	}
	else if(this.context.tool == "model" && this.context.modelclass){
		this.displayModelClass();
	}
	else if(this.context.tool == "model" && this.context.modelproperty){
		this.displayModelProperty();
	}
	else if(this.context.mode == 'create' && this.context.tool == "data"){
		this.displayNewCandidateForm();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-extra .console-context-summary").hide();
		jQuery('#' + this.HTMLID + " .console-extra .console-context-full").hide();
	}
}

DacuraConsole.prototype.setFrozenElements = function(){
	if(this.context.mode == "view"){
		this.thawNavigation();
	}
	else {
		this.freezeNavigation();
	}
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
 * Determines which role icaons to display for the user
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

/**
 * Determines the visibility of context elements when in data mode
 */
DacuraConsole.prototype.setDataContextVisiblity = function(){
	var col = this.client.collections[this.context.collection];
	jQuery('#' + this.HTMLID + " .console-context .entityclass .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .candidate .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .entityproperty .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .candidateproperty .context-element-item").hide();
	if(col.entity_classes.length > 1){
		jQuery('#' + this.HTMLID + " .console-context .entityclass .context-select-holder").show();
		if(this.context.entityclass){
			if((this.active_roles['harvester'] || this.active_roles['expert']) && (this.context.mode == 'view')){
				jQuery('#' + this.HTMLID + " .console-context .entityclass .context-add-element").show();
			}
			jQuery('#' + this.HTMLID + " .console-context .entityclass .context-select-holder select").val(this.context.entityclass).trigger("change.select2");
		}
		else {
			jQuery('#' + this.HTMLID + " .console-context .entityclass .context-select-holder select").show().trigger("change.select2");					
		}
		if((this.context.entityclass && col.candidates[this.context.entityclass] && size(col.candidates[this.context.entityclass]) > 0)){
			jQuery('#' + this.HTMLID + " .console-context .candidate .context-select-holder").show();
		}
		else if((!this.context.entityclass) && (size(col.candidates) > 0)){
			jQuery('#' + this.HTMLID + " .console-context .candidate .context-select-holder").show();					
		}
		else {
			jQuery('#' + this.HTMLID + " .console-context .candidate .context-empty").show();			
		}
		var csel = jQuery('#' + this.HTMLID + " .console-context .candidate .context-select-holder select").val();
		if(this.context.candidate && this.context.candidate != csel){
			jQuery('#' + this.HTMLID + " .console-context .candidate .context-select-holder select").val(this.context.candidate).trigger("change");//provokes chained update
		}
		else if(!this.context.candidate && csel){
			jQuery('#' + this.HTMLID + " .console-context .candidate .context-select-holder select").val("").trigger("change.select2");//don't provoke chained update			
		}
		else if(this.context.candidate){
			if(this.current_candidate){
				if(this.current_candidate.propertyCount(true) > 0) {
					jQuery('#' + this.HTMLID + " .console-context .candidateproperty .context-select-holder").show();
					if(this.context.candidateproperty){
						jQuery('#' + this.HTMLID + " .console-context .candidateproperty .context-add-property").show();
					}
				}
				else {
					jQuery('#' + this.HTMLID + " .console-context .candidateproperty .context-empty").show();
				}
			}
			jQuery('#' + this.HTMLID + " .console-context .candidateproperty").show();
		}
		if(this.context.mode == 'view'){
			jQuery('#' + this.HTMLID + " .console-context .candidate").show();
		}
		else {
			jQuery('#' + this.HTMLID + " .console-context .candidate").hide();
			if(this.entity_property_count > 0){
				jQuery('#' + this.HTMLID + " .console-context .entityproperty .context-select-holder").show();
				jQuery('#' + this.HTMLID + " .console-context .entityproperty .context-add-property").show();
			}
			else {
				jQuery('#' + this.HTMLID + " .console-context .entityproperty .context-empty").show();
			}
			jQuery('#' + this.HTMLID + " .console-context .entityproperty").show();
		}
	}
	else {
		jQuery('#' + this.HTMLID + " .console-context .entityclass .context-empty").show();
	}
	if(this.context.candidate){
		jQuery('#' + this.HTMLID + " .console-context .entityclass").hide();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-context .entityclass").show();
	}
}

/**
 * Changes the visibility of model context elements 
 */
DacuraConsole.prototype.setModelContextVisiblity = function(){
	var col = this.client.collections[this.context.collection];
	jQuery('#' + this.HTMLID + " .console-context .ontology .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelclass .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelclass").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelproperty").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").attr("disabled", false);
	jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").attr("disabled", false);
	if(size(col.ontologies) > 0){
		jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder").show();
		var csel = jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").val();
		if(this.context.ontology && this.context.ontology != csel){
			jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").val(this.context.ontology).trigger("change");
		}
		else if(!this.context.ontology && csel) {
			jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").val("").trigger("change");					
		}
		if(this.context.ontology){
			if(this.current_ontology.properties && size(this.current_ontology.properties) > 0){
				jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder").show();
				var psel = jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").val();
				if(this.context.modelproperty && this.context.modelproperty != psel){
					jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").val(this.context.modelproperty).trigger("change");
				}
				else if(!this.context.modelproperty && psel) {
					jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").val("").trigger("change");					
				}
				if(this.context.modelproperty){
					jQuery('#' + this.HTMLID + " .console-context .modelclass .context-select-holder select").attr("disabled", true);
					jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").attr("disabled", true);
				}
			}
			else {
				jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-empty").show();
			}
			if(this.current_ontology.classes && size(this.current_ontology.classes) > 0){
				jQuery('#' + this.HTMLID + " .console-context .modelclass .context-select-holder").show();
				var csel = jQuery('#' + this.HTMLID + " .console-context .modelclass .context-select-holder select").val();
				if(this.context.modelclass && this.context.modelclass != csel){
					jQuery('#' + this.HTMLID + " .console-context .modelclass .context-select-holder select").val(this.context.modelclass).trigger("change");
				}
				else if(!this.context.modelclass && csel) {
					jQuery('#' + this.HTMLID + " .console-context .modelclass .context-select-holder select").val("").trigger("change");					
				}
				if(this.context.modelclass){
					jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").attr("disabled", true);
					jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").attr("disabled", true);
				}
			}
			else {
				jQuery('#' + this.HTMLID + " .console-context .modelclass .context-empty").show();
			}
			jQuery('#' + this.HTMLID + " .console-context .modelclass").show();	
			jQuery('#' + this.HTMLID + " .console-context .modelproperty").show();	
		}
	}
	else {
		jQuery('#' + this.HTMLID + " .console-context .ontology .context-empty").show();
	}
	if(this.active_roles['architect']){
		if(this.context.ontology && !(this.context.modelclass || this.context.modelproperty)){
			jQuery('#' + this.HTMLID + " .console-context .modelclass .context-add-element").show();
			jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-add-element").show();
		}
	}
	jQuery('#' + this.HTMLID + " .console-context .ontology").show();	
}

/* functions to display stuff on the expanded versions of the console */

DacuraConsole.prototype.displayCandidate = function(){
	var self = this;
	jQuery('#' + self.HTMLID + " .console-extra .console-context-summary .summary-details").show();
	jQuery('#' + self.HTMLID + " .console-extra .console-context-summary .summary-create").hide();
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full .frame-viewer").html("");			
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full").hide();				
	var showcand = function(cand){
		self.current_candidate = cand;
		self.setSummaryIconVisibility("candidate", false, cand);
		self.setSummaryLabel("candidate", cand);
		jQuery('#' + self.HTMLID + " .console-extra .console-context-summary").show();
	};
	var failcand = function(title, msg, extra){
		self.showResult("error", title, msg, extra);
	};
	//in case it's not loaded - but it should be loaded by init
	this.client.get("candidate", this.context.candidate, showcand, failcand);
} 

DacuraConsole.prototype.displayNewCandidateForm = function(){
	jQuery('#' + self.HTMLID + " .console-extra .console-context-summary .summary-create").show();
	jQuery('#' + self.HTMLID + " .console-extra .console-context-summary .summary-details").hide();
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full .frame-viewer").html("");			
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full").hide();		
	this.setSummaryIconVisibility("candidate", this.context.entityclass);
	var ec = this.getEntityClassFromID(this.context.entityclass);
	var lab = "New " + (ec.label ? ec.label.data : ec.id);
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-identifier").html(lab);
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary").show();
} 

DacuraConsole.prototype.getNewEntityDetailsFromForm = function(){
	var id = jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-create .entity-id").val();
	var label = jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-create .entity-label").val();
	var comm = jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-create .entity-comment").val();
	var struct = {
		id: id,
		contents: {
			"rdf:type": this.context.entityclass,
			"rdfs:label": label, 
			"rdfs:comment": comm
		}
	};
	if(id && id.length){
		struct.id = id;
	}
	var ips = this.getFrameInputs();
	for(var i in ips){
		struct.contents[i] = ips[i];
	}
	return struct;
}

DacuraConsole.prototype.getFrameInputs = function(){
	var resp = {};
	if(typeof this.fv != "undefined"){
		this.fv.cls = this.context.entityclass;
		resp = this.fv.extract();
	//for(var i = 0; i<this.displayed_properties.length; i++){
	//	resp[this.displayed_properties[i]] = "get from frame";
	//}
	//jpr(resp);
	}
	return resp;
}

DacuraConsole.prototype.displayModelProperty = function(){
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-content").html("here comes the property");
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary").show();
} 

DacuraConsole.prototype.displayModelClass = function(){
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-content").html("here comes the class");
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary").show();
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
		var ec = ldo.entityClass(this.client.collections[this.context.collection].entity_classes);
		if(ec.label){
			clab = ec.label.data;
		}
		else if(ec.id){
			clab = ec.id.split(':')[1];
		}
		else {
			clab = ec['class'].substring(ec['class'].lastIndexOf('#') + 1);
		}
		lab = clab + " " + lab;
		cmt = ldo.id + " a " + ec['class'] + " at " + ldo.meta.cwurl + ". " + cmt;
	}
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-identifier").attr("title", escapeHtml(cmt)).html(lab);
}

/**
 * Determines the visibility of the icons in the summary line
 * @param type candidate|class|property
 * @param subtype (subtype of type - class: entity, simple, complex, enumerated)
 * @param ldo linked data object
 */
DacuraConsole.prototype.setSummaryIconVisibility = function(type, subtype, ldo){
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-icons .summary-icon").hide();
	jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-actions .summary-action").hide();
	if(ldo){
		var statt = "Status: " + ldo.meta.status + ". " + "Version: " + ldo.meta.version; 
		jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-icons .summary-element-status ." + ldo.meta.status).attr("title", statt).show();
		jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-icons .summary-element-type ." + type).show();
		if(subtype){
			jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-icons .summary-element-subtype ." + type + " ." + subtype).show(); 
		}
		if(this.active_roles['architect']){
			jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-actions .summary-delete").show();
			if(type != "candidate"){
				jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-actions .summary-edit").show();		
			}
		}
		if(type == "candidate" && this.active_roles['harvester']){
			jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-actions .summary-edit").show();				
		}
	}
	else {
		jQuery('#' + this.HTMLID + " .console-extra .console-context-summary .summary-icons .summary-element-type ." + type).show();
	}
}

/* updates the display to add properties to them */
DacuraConsole.prototype.addPropertyToDisplay = function(prop){
	if(typeof prop == "string" && prop.length){
		if(this.displayed_properties.indexOf(prop) !== -1){
			alert(prop + " is already displayed");
		}
		else {
			if(this.displayed_properties.length == 0){			
				//need to open the display
				jQuery('#' + this.HTMLID + " .console-extra .console-context-full .frame-viewer").show();			
				jQuery('#' + this.HTMLID + " .console-extra .console-context-full").show();	
			}
			var self = this;
			this.displayed_properties.push(prop);
	
			var pend = prop.after("/");
			var bits = pend.split('#');
			var htmlid = bits[0] + "_" + bits[1];
			jQuery('#' + this.HTMLID + " .console-extra .console-context-full .frame-viewer").append("<div class='pframe-holder' id='" + htmlid + "'></div>");
			var showcprop = function(pframe){
				self.fv = new FrameViewer("x", htmlid, {});
				self.fv.draw([pframe], self.context.mode);
				var nh = self.getRemoveFrameIcon(htmlid, prop);
				jQuery('#' + htmlid).append(nh);
			}
			var failcprop = function(title, msg, extra){
				self.showResult("error", title, msg, extra);
			};
			if(this.context.mode == "view"){
				this.client.getFilledPropertyFrame(this.context.candidate, prop, showcprop, failcprop);
			}
			else if(this.context.mode == "create"){	
				this.client.getEmptyPropertyFrame(this.context.entityclass, prop, showcprop, failcprop);
			}
		}
	}
}

DacuraConsole.prototype.getRemoveFrameIcon = function(htmlid, prop){
	var html = "<div class='remove-property'><a href='javascript:internal_reference_to_console.removePropertyFromDisplay(\"" + htmlid + "\", \"" + prop + "\")'>";
	html += '<i class="fa fa-minus-square-o fa-lg"></i></a></div>';
	return html;
}

DacuraConsole.prototype.removePropertyFromDisplay = function(htmlid, prop){
	var pos = this.displayed_properties.indexOf(prop);
	if(pos === -1){
		return alert(prop + " is not displayed - cant be removed!");
	}
	else {
		jQuery('#' + htmlid).remove();
		this.displayed_properties.splice(pos, 1);
		if(this.displayed_properties.length == 0){
			jQuery('#' + this.HTMLID + " .console-extra .console-context-full").hide();	
		}
		if(this.context.candidateproperty == prop){
			jQuery('#' + this.HTMLID + " .console-context .candidateproperty .context-add-property").show("fade");				
		}
	}
}


/* miscellaneous */

/**
 * Returns a list of the user's available roles for the context
 * @returns array of roles
 */
DacuraConsole.prototype.getAvailableRoles = function(){
	var colcap = this.context.collection ? this.client.collections[this.context.collection] : false;
	if(!colcap){
		return [];
	}
	return colcap.roles;
} 

DacuraConsole.prototype.getEntityClassFromID = function(id){
	var ecap = this.context.collection ? this.client.collections[this.context.collection].entity_classes : false;
	for(var i = 0; i< ecap.length; i++){
		if(ecap[i].id == id) return ecap[i];
	}
}



function DacuraModelBuilder(config){}
