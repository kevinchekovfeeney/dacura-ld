/**
 * Now the model editor portion
 */
DacuraConsole.prototype.displayModelProperty = function(def, mode, suppress_controls){
	var mode = (mode ? mode : this.context.mode);
	if(mode == "view" || mode == "edit"){
		//fill our class into the form
		var cfv = {
			id: this.context.modelproperty, 
			label: this.current_ontology.getPropertyLabel(this.context.modelproperty), 				
			comment: this.current_ontology.getPropertyComment(this.context.modelproperty),
			domain: this.current_ontology.getPropertyDomain(this.context.modelproperty),
			range: this.current_ontology.getPropertyRange(this.context.modelproperty)
		};
		this.loadPropertyIntoEditor(cfv);
		var cmd = this.current_ontology.getPropertyMetadata(this.context.modelproperty, (def && def.metadata ? def.metadata: false));
		this.setMetadataEditorValues(cmd);
	}
	else if(mode == "create"){
		this.setSubmitButtonLabels("property");
		def = (def ? def : {id: "", label: "", comment: "", domain: "", range: ""});
		this.setModelEditorValues(def);
		this.setMetadataEditorValues(def.metadata); 
	}
	else {
		alert(mode + " property to display");
	}
	this.setModelFormVisibility("property", mode, suppress_controls);
	this.showing_extra = {mode: mode, type: "property"};
} 

DacuraConsole.prototype.displayExternalProperty = function(id, def){
	var cmd = this.current_ontology.getPropertyMetadata(id, def);
	this.loadPropertyIntoEditor({id: "", label: "", comment: "", domain: "", range: ""});
	this.setMetadataEditorValues(cmd);		
	this.setModelFormVisibility("external", this.context.mode);
	this.showing_extra = {mode: this.context.mode, type: "property"};
}


/**
 * Displays the current class in the model editor / viewer
 */
DacuraConsole.prototype.displayModelClass = function(def){
	if(this.context.mode == "view" || this.context.mode == "edit"){
		//fill our class into the form
		var cfv = {
			id: this.context.modelclass, 
			label: this.current_ontology.getClassLabel(this.context.modelclass), 				
			comment: this.current_ontology.getClassComment(this.context.modelclass),
			ctype: this.current_ontology.getClassType(this.context.modelclass)
		};
		if(cfv.ctype == 'entity' || cfv.ctype == "complex"){
			cfv.parent = this.current_ontology.classes[this.context.modelclass]["rdfs:subClassOf"];
		}
		if(cfv.ctype == 'enumerated'){
			cfv.choices = this.current_ontology.getEnumeratedChoices(this.context.modelclass);
		}
		this.loadClassIntoEditor(cfv);
		var cmd = this.current_ontology.getClassMetadata(this.context.modelclass, (def && def.metadata ? def.metadata: false));
		this.setMetadataEditorValues(cmd);
	}
	else {
		this.setSubmitButtonLabels("class");
		this.hideClassExtras();
		this.setModelEditorValues(def);
		this.setMetadataEditorValues(def.metadata);
	}
	this.setModelFormVisibility("class", this.context.mode);
	if(this.context.tool == "model" && this.context.mode == 'edit' && cfv.ctype == "enumerated"){
		this.showEnumeratedTypePicker();
	}
	else {
		this.hideEnumeratedTypePicker();
	}
	this.showing_extra = {mode: this.context.mode, type: "class"};
} 


/*
 * Hides / shows things on the model editor depending on the type (class | property) and mode
 */
DacuraConsole.prototype.setModelFormVisibility = function(type, mode, suppress_controls){
	this.hideAllElementsOnModelForm();
	if(mode == "view"){
		this.showViewElementsOnModelForm();
		jQuery('#' + this.HTMLID + " .console-extra .model-class-extra").hide();		 
	}
	else {
		this.showEditElementsOnModelForm();
		if(mode == "create"){
			this.showCreateElementsOnModelForm();
			this.hideNoCreateElementsOnModelForm();
			jQuery('#' + this.HTMLID + " .console-extra .model-edit-id input").removeAttr('disabled');
		}
		else {
			this.hideCreateElementsOnModelForm();
			this.showNoCreateElementsOnModelForm();		
			jQuery('#' + this.HTMLID + " .console-extra .model-edit-id input").prop("disabled", "true");
		}
	}
	if(type != "property"){
		this.hidePropertyElementsOnModelForm();
	}
	if(type != "class"){
		this.hideClassElementsOnModelForm();		
		jQuery('#' + this.HTMLID + " .console-extra .model-class-extra").hide();		 
	}
	if(type != "external"){
		jQuery('#' + this.HTMLID + " .console-extra .console-model-header").show();
		jQuery('#' + this.HTMLID + " .console-extra .console-model-comment").show();
		this.setMetadataVisibility(mode);
	}
	else {
		this.setMetadataVisibility(type);
	}
	if(suppress_controls){
		this.hideModelFormControls();
	}
	else{
		this.showModelFormControls(mode == "edit" || mode == "create");
	}
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full").show();
}


/**
 * Called to hide everything on model form
 */
DacuraConsole.prototype.hideAllElementsOnModelForm = function(){
	this.hidePropertyElementsOnModelForm();
	this.hideClassElementsOnModelForm();
	this.hideEditElementsOnModelForm();
	this.hideViewElementsOnModelForm();	
	this.hideCreateElementsOnModelForm();
	this.hideNoCreateElementsOnModelForm();
	this.hideOtherAssertions();
	this.hideEnumeratedTypePicker();
	this.hideModelFormControls();
	jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer").hide();		

}

DacuraConsole.prototype.showModelFormControls = function(buttons){
	if(buttons){
		jQuery('#' + this.HTMLID + " .console-extra .console-extra-buttons").show();		
	}
	else {
		jQuery('#' + this.HTMLID + " .console-extra .console-extra-buttons").hide();				
	}
	jQuery('#' + this.HTMLID + " .console-extra .model-actions").show();
}


DacuraConsole.prototype.hideModelFormControls = function(){
	jQuery('#' + this.HTMLID + " .console-extra .console-extra-buttons").hide();
	jQuery('#' + this.HTMLID + " .console-extra .model-actions").hide();
}


DacuraConsole.prototype.showEnumeratedTypePicker = function(){
	jQuery('#' + this.HTMLID + " .enumerated-type").show();
}

DacuraConsole.prototype.hideEnumeratedTypePicker = function(){
	jQuery('#' + this.HTMLID + " .enumerated-type").hide();
}

DacuraConsole.prototype.showPageSummary = function(){
	jQuery('#' + this.HTMLID + " .console-page-summary").show();
}

DacuraConsole.prototype.hidePageSummary = function(){
	jQuery('#' + this.HTMLID + " .console-page-summary").hide();
}

DacuraConsole.prototype.showPropertyElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-property").show();	
}

DacuraConsole.prototype.hidePropertyElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-property").hide();	
}

DacuraConsole.prototype.hideClassElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-class").hide();	
}

DacuraConsole.prototype.showClassElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-class").show();	
}

DacuraConsole.prototype.showEditElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-edit-model").show();
}
DacuraConsole.prototype.hideEditElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-edit-model").hide();
}

DacuraConsole.prototype.showViewElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-view-model").show();
}

DacuraConsole.prototype.hideViewElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-view-model").hide();
}

DacuraConsole.prototype.showCreateElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-createonly").show(); 
}

DacuraConsole.prototype.hideCreateElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-createonly").hide(); 
}

DacuraConsole.prototype.showNoCreateElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-nocreate").show();
}

DacuraConsole.prototype.hideNoCreateElementsOnModelForm = function(){
	jQuery('#' + this.HTMLID + " .console-context-full .console-model-nocreate").hide();	
}

DacuraConsole.prototype.hideClassExtras = function(){
	jQuery("#dacura-console .model-class-extra").hide();		
}

DacuraConsole.prototype.hideOtherAssertions = function(){
	jQuery("#dacura-console .console-other-assertions").hide();		
}

/**
 * Interactions with the ontology api
 * create class
 */
DacuraConsole.prototype.hitUpOntologyAPI = function(test){
	var self = this;
	var f = function(ldr){
		self.showResult("error", ldr.getResultTitle());
		ldr.pconfig = self.full_pconfig;
		ldr.show();
	}
	var s = function(ldr){
		self.showResult(ldr.status, ldr.getResultTitle());
		ldr.pconfig = self.full_pconfig;
		ldr.show();
		//if(!test && (ldr.status == "accept" || ldr.status == "pending")){
			//self.userChangesContext(ncontext);				
		//}
	};
	this.errors = [];
	var deploy = (jQuery('#deploy-model-to-graph').is(':checked') ? true : false);
	var input = this.readModelForm();
	if(this.context.mode == 'harvest'){
		var imptarget = this.getFactoidImportTarget(); 
	}
	if(this.context.mode == 'create' || (this.context.mode == "harvest" && (imptarget === "0"))){
		if(this.model_create_clicked == 'class'){
			if(!this.current_ontology.validateCreateClassInput(input)){
				return this.writeErrorMessage("Error in " + this.current_ontology.errors[0].field + " field", this.current_ontology.errors[0].message);
			}
			this.current_ontology.addClass(input.id, input.label, input.comment, input.ctype, input.parents, input.choices, input.metadata);
		}
		else{
			if(!this.current_ontology.validateCreatePropertyInput(input)){
				return this.writeErrorMessage("Error in " + this.current_ontology.errors[0].field + " field", this.current_ontology.errors[0].message);
			}
			this.current_ontology.addProperty(input.id, input.label, input.comment, input.domain, input.range, input.metadata);			
		}

	}
	else {
		if(this.context.modelclass){
			if(!this.current_ontology.validateUpdateClassInput(input)){
				return this.writeErrorMessage("Error in " + this.current_ontology.errors[0].field + " field", this.current_ontology.errors[0].message);
			}
			this.current_ontology.updateClass(input.id, input.label, input.comment, input.ctype, input.parents, input.choices, input.extras, input.metadata);
		}
		else if(this.context.modelproperty){
			if(!this.current_ontology.validateUpdatePropertyInput(input)){
				return this.writeErrorMessage("Error in " + this.current_ontology.errors[0].field + " field", this.current_ontology.errors[0].message);
			}
			this.current_ontology.updateProperty(input.id, input.label, input.comment, input.domain, input.range, input.extras, input.metadata);
		}
		else { //external property update
			this.current_ontology.addMetaUpdate(imptarget, input.metadata);
		}
	}
	this.client.update("ontology", this.current_ontology, s, f, test, deploy);
}


DacuraConsole.prototype.deleteModelElement = function(test){
	var self = this;
	var f = function(ldr){
		self.showResult("error", ldr.getResultTitle());
		ldr.pconfig = self.full_pconfig;
		ldr.show();
	}
	var s = function(ldr){
		self.showResult(ldr.status, ldr.getResultTitle());
		ldr.pconfig = self.full_pconfig;
		ldr.show();
		self.userChangesContext({mode: "view", modelproperty: false, modelclass: false});			
	};
	if(this.context.modelclass){
		this.current_ontology.deleteClass(this.context.modelclass);
	}
	else {
		this.current_ontology.deleteProperty(this.context.modelproperty);		
	}
	var deploy = (jQuery('#deploy-model-to-graph').is(':checked') ? true : false);
	this.client.update("ontology", this.current_ontology, s, f, test, deploy);
}

/**
 * General state getting / setting functions 
 */

/* loads initial list of visible classes from capabilities */
DacuraConsole.prototype.loadCollectionVisibleModelElements = function(){
	this.visible_classes = {};
	var ecs = this.getEntityClasses();
	for(var i = 0; i<ecs.length; i++){
		if(!ecs[i].id || ecs[i].id == "owl:Nothing") continue;
		var label = ((ecs[i].label && ecs[i].label.data) ? ecs[i].label.data : ecs[i].id);
		this.addClassToVisibleClasses(ecs[i].id, label);
	}
	var bt = this.client.ontology_config.boxtypes;
	if(bt){
		for(var tid in bt){
			var label = ((bt['rdfs:label'] && bt['rdfs:label'].data) ? bt['rdfs:label'].data : tid);
			this.addClassToVisibleClasses(tid, label);
		}
	}
	if(this.client.ontology_config.entity_tag){
		this.addClassToVisibleClasses(this.client.ontology_config.entity_tag, this.client.ontology_config.entity_tag);
	}
}


/* Model Tool initialisations */


/**
 * Initialises the list of ontologies associated with a collection
 */
DacuraConsole.prototype.initCollectionOntologies = function(){
	if(!this.context.collection) {
		jQuery('#' + this.HTMLID + " .context-ontology").hide();
		return;
	}
	var col = this.client.collections[this.context.collection];
	var self = this;
	if(size(col.ontologies) >= 1){
		var html = "<option value=''>Select an Ontology</option>";
		for(var ontid in col.ontologies){
			var sel = (ontid == this.context.ontology ? " selected" : "");
			html += "<option" + sel + " value='" + ontid + "'>" + col.ontologies[ontid].title + "</option>"; 
		}
		var shtml = "<select class='context-ontology-picker'>" + html + "</select>";
		jQuery('#' + this.HTMLID + " .context-ontology-picker-holder").html(shtml);
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
 * @param - callback for when initialisation is complete
 */
DacuraConsole.prototype.initOntologyContents = function(undisplayed){
	var self = this;
	jQuery('#' + self.HTMLID + " select.context-modelclass-picker").html("");
	jQuery('#' + self.HTMLID + " select.context-modelproperty-picker").html("");
	if(!this.context.collection || !this.context.ontology) {
		return this.showMenu();
	}
	var populateOntologySelects = function(ont){
		self.current_ontology = ont;
		if(typeof ont.classes != "undefined" && size(ont.classes) > 0){
			var ph = "Select Class (" + size(ont.classes) + ")";
			var html = "<option value=''>" + ph + "</option>";
			for(var i in ont.classes){
				if(!i || i.length == 0) continue;//deal with phantom classes
				var lab = ont.getClassLabel(i);
				if(!lab) lab = i;
				self.addClassToVisibleClasses(i, lab);
				var sel = (self.context.modelclass && (i == self.context.modelclass)) ? " selected" : "";
				html += "<option value='" + i + "'" + sel + ">" + lab + "</option>";
			}
			var shtml = "<select class='context-modelclass-picker'>" + html + "</select>";
			jQuery('#' + self.HTMLID + " .context-modelclass-picker-holder").html(shtml);
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
			var shtml = "<select class='context-modelproperty-picker'>" + html + "</select>";
			jQuery('#' + self.HTMLID + " .context-modelproperty-picker-holder").html(shtml);
			jQuery('#' + self.HTMLID + " select.context-modelproperty-picker").select2({
				  placeholder: ph,
				  allowClear: true,
				  minimumResultsForSearch: 10		  
			}).on('change', function(){
				self.userChangesContext({"modelproperty": this.value });
			});
		}
		html = "";
		var ents = ont.getEntityClasses();
		for(var i = 0; i < ents.length; i++){
			//var sel = (ents[i] == ep ? " selected" : "");
			var label = ont.getClassLabel(ents[i]);
			if(!label.length) label = ents[i];
			html += "<option value='" + ents[i] + "'" + sel + ">" + label + "</option>";
		}
		if(html.length){
			shtml = "<select class='model-entity-parent-picker'>" + html + "</select>";
			jQuery('#' + self.HTMLID + " .entity-parent-picker").html(shtml);
			jQuery('#' + self.HTMLID + " select.model-entity-parent-picker" ).select2({
				  placeholder: "Select Parent",
				  allowClear: false,
				  minimumResultsForSearch: 50		  
			});
		}
		self.injectClassSelector('#' + self.HTMLID + " .class-parent-picker", "Select Parent Classes", true);
		self.injectClassSelector('#' + self.HTMLID + " .property-domain-select-holder", "Select Domain");
		self.write_in_domains = [];
		self.write_in_ranges = [];
		self.injectClassSelector('#' + self.HTMLID + " .property-range-select-holder", "Select Range");
		self.initPropertySelector(".factoid-import-select-holder");	
		self.importPage();
		self.showMenu(undisplayed);
	}
	var ontologyFail = function(tit, msg, extra){
		self.showResult("error", tit, msg, extra);
	}
	this.current_action = "Loading ontology from server";
	this.client.get("ontology", this.context.ontology, populateOntologySelects, ontologyFail);	
}

/**
 * Initialisation of the model editor - console extra section
 */
DacuraConsole.prototype.initModelEditor = function(){
	var html = "<option value=''>Choose Class Type</option>";
	var choices = {
		'simple': {icon: 'circle', title: 'Simple Class'}, 
		"entity": {icon: 'star', title: 'Entity Class'}, 
		"complex": {icon: 'first-order', title: 'Complex Class'}, 
		"enumerated": {icon: 'list', title: 'Enumerated Type'}
	};
	for(var i in choices){
		html += "<option value='" + i + "'>" + choices[i].title + "</option>";
	}
	var self = this;
	jQuery('#' + this.HTMLID + " .console-extra select.model-classtype-picker").html(html).select2({
		  placeholder: "Choose Class Type",
		  allowClear: false,
		  minimumResultsForSearch: 10,
		  templateResult: function(state){
			  if(!state.id){
				  return state.text;
			  }
			  return jQuery("<span><i class='fa fa-" + choices[state.id].icon + "'> " + choices[state.id].title + "</span>");
		  }
	}).on('change', function(){
		jQuery('#' + self.HTMLID + " .console-extra .model-class-extra").hide();		 
		if(this.value == "entity"){
			jQuery('#' + self.HTMLID + " .console-extra .console-model-header .entity-parent").show();
		}
		else if(this.value == "complex"){
			jQuery('#' + self.HTMLID + " .console-extra .console-model-header .complex-parent").show();
		}
		else if(this.value == "enumerated"){
			jQuery('#' + self.HTMLID + " .console-extra .enumerated-type").show();
		}
	});	
	self.initEnumeratedTypes();
}

/**
 * Injects a class selector element into the dom
 * jqs - the jquery selector to use
 * msg - the 'choose class' message to show by default
 * multiple - if true the box will support multiple select
 */
DacuraConsole.prototype.injectClassSelector = function(jqs, msg, multiple){
	if(!this.context.collection || !this.current_ontology) return;
	var clss = this.current_ontology.classes;
	var options = [];
	var html = "<select class='model-class-selector'" + (multiple ? " multiple='multiple'" : "") + ">";
	if(!multiple){
		html += "<option value=''>" + msg + "</option>";
	}
	for(var i in this.visible_classes){
		html += "<optgroup label='" + i + "'>";
		for(var j in this.visible_classes[i]){
			options.push(j);
			html += "<option value='" + j + "'>" + this.visible_classes[i][j] + "</option>";
		}
		html += "</optgroup>";
	}
	html += "</select>";
	jQuery(jqs).html(html);
	jQuery(jqs + " select").select2({
		  placeholder: msg,
		  allowClear: true,
		  tags: true
	}).on('change', function(){
	});
}

/**
 * Initialisation of the enumerated types fields
 */
DacuraConsole.prototype.initEnumeratedTypes = function(){
	var self = this;
	linkifyElement("th.add-enum", function(){
		var x = jQuery(this).closest('table');
		jQuery('tbody', x).append(self.getEnumeratedTypeInputRowHTML());
		linkifyElement("td.enumerated-type-remove", function(){
			jQuery(this).parent("tr:first").remove();
		});	
	});	
}

/*End of initialisation section - next are simple stuff to manipulate visibility of stuff*/

/**
 * Changes the visibility of model context elements in the top bar
 */
DacuraConsole.prototype.setModelContextVisiblity = function(){
	var col = this.client.collections[this.context.collection];
	jQuery('#' + this.HTMLID + " .console-context .ontology .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelclass .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelclass").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelproperty").hide();
	jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-element-item").hide();
	//jQuery('#' + this.HTMLID + " .console-import .console-import-tool").hide();
	if(size(col.ontologies) == 0){
		jQuery('#' + this.HTMLID + " .console-context .ontology .context-empty").show();
	}
	else {
		if(size(col.ontologies) == 1){
			this.context.ontology = firstKey(col.ontologies);
		}
		var csel = jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").val();
		if(this.context.ontology && this.context.ontology != csel || (!this.context.ontology && csel == "")){
			return jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder select").val(this.context.ontology).trigger("change");
		}
		if(this.context.ontology && size(col.ontologies) > 0){
			if(size(col.ontologies) > 1){
				jQuery('#' + this.HTMLID + " .context-ontology-picked .ontology-title-text").html(col.ontologies[this.context.ontology].title);
				jQuery('#' + this.HTMLID + " .context-ontology-picked").show();
				jQuery('#' + this.HTMLID + " .ontology span.ontology-title-changer").show();
			}
			if(this.current_ontology.properties && size(this.current_ontology.properties) > 0){
				jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder").show();
				var psel = jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").val();
				if(this.context.modelproperty && this.context.modelproperty != psel){
					jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").val(this.context.modelproperty).trigger("change");
				}
				else if(!this.context.modelproperty && psel) {
					jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-select-holder select").val("").trigger("change");					
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
			}
			else {
				jQuery('#' + this.HTMLID + " .console-context .modelclass .context-empty").show();
			}
			if(this.context.mode == "view"){
				jQuery('#' + this.HTMLID + " .console-context .modelclass").show();	
				jQuery('#' + this.HTMLID + " .console-context .modelproperty").show();	
			}
		}
		else {
			if(size(col.ontologies) > 1){
				jQuery('#' + this.HTMLID + " .console-context .ontology .context-select-holder").show();
			}
		}
		if(this.active_roles['architect']){
			if(this.context.ontology){
				jQuery('#' + this.HTMLID + " .console-context .modelclass .context-add-modelclass").show();
				jQuery('#' + this.HTMLID + " .console-context .modelproperty .context-add-modelproperty").show();
			}
		}
	}
	jQuery('#' + this.HTMLID + " .console-context .ontology").show();	
}


/**
 * Loads particular values into the class editor
 */
DacuraConsole.prototype.loadClassIntoEditor = function(bits){
	var label = bits.label;
	if(!label) label = bits.id;
	if(!label) label = "No class label, no class ID!";
	jQuery("#dacura-console .console-extra .console-model-class-label").html(label);
	if(bits.comment && bits.comment.length){
		jQuery("#dacura-console .console-extra .console-view-model-comment .model-comment-label").html(bits.comment);
		jQuery("#dacura-console .console-extra .console-view-model-comment").show(); 
	}
	else {
		jQuery("#dacura-console .console-extra .console-view-model-comment .model-comment-label").html("");
		jQuery("#dacura-console .console-extra .console-view-model-comment").hide(); 
	}
	if(bits.parent){
		jQuery("#dacura-console .console-extra .console-view-model-parents .class-parents").html(this.getParentClassesHTML(bits.parent));
		jQuery("#dacura-console .console-extra .console-view-model-parents").show(); 
	}
	else {
		jQuery("#dacura-console .console-extra .console-view-model-parents .class-parents").html("");
		jQuery("#dacura-console .console-extra .console-view-model-parents").hide(); 
	}
	if(bits.choices){
		jQuery("#dacura-console .console-extra .console-view-enumerated-type .enumerated-choices").html(this.getChoicesHTML(bits.choices));
		jQuery("#dacura-console .console-extra .console-view-enumerated-type").show(); 	
	}
	else {
		jQuery("#dacura-console .console-extra .console-view-enumerated-type").hide(); 			
	}
	jQuery("#dacura-console .console-extra .console-model-class .summary-icon").hide();
	if(bits.ctype){
		jQuery("#dacura-console .console-extra .console-model-class .summary-icon." + bits.ctype).show();		
	}
	else {
		jQuery("#dacura-console .console-extra .console-model-class .summary-icon.unknown").show();		
	}
	//change labels on buttons...
	this.setSubmitButtonLabels("class", bits.id);
	this.setModelEditorValues(bits);
}

/**
 * Loads a particular property into the editor
 * @params simple object with values (label, id, domain, range, comment)
 */
DacuraConsole.prototype.loadPropertyIntoEditor = function(bits){
	var label = bits.label;
	if(!label) label = bits.id;
	if(!label) label = "No property label, no property ID!";
	jQuery("#dacura-console .console-extra .console-model-property-label").html(label);
	if(bits.comment && bits.comment.length){
		jQuery("#dacura-console .console-extra .console-view-model-comment .model-comment-label").html(bits.comment);
		jQuery("#dacura-console .console-extra .console-view-model-comment").show(); 
	}
	else {
		jQuery("#dacura-console .console-extra .console-view-model-comment .model-comment-label").html("");
		jQuery("#dacura-console .console-extra .console-view-model-comment").hide(); 
	}
	if(bits.domain){
		jQuery("#dacura-console .console-extra .console-view-property-domain .property-domain").html(this.getParentClassesHTML(bits.domain));
	}
	else {
		jQuery("#dacura-console .console-extra .console-view-property-domain .property-domain").html("[none]");
	}
	if(bits.range){
		jQuery("#dacura-console .console-extra .console-view-property-range .property-range").html(this.getParentClassesHTML(bits.range));
	}
	else {
		jQuery("#dacura-console .console-extra .console-view-property-domain .property-domain").html("[none]");
	}
	jQuery("#dacura-console .console-extra .console-model-property .summary-icon").hide();
	var ptype = this.current_ontology.getPropertyRangeType(bits.id);
	if(ptype){
		jQuery("#dacura-console .console-extra .console-model-property .summary-icon." + ptype).show();		
	}
	else {
		jQuery("#dacura-console .console-extra .console-model-property .summary-icon.unknown").show();		
	}
	//change labels on buttons...
	this.setSubmitButtonLabels("property", bits.id);
	this.setModelEditorValues(bits);
	this.showOtherAssertions();
}

DacuraConsole.prototype.setModelEditorValues = function(bits){
	if(typeof bits.id == "string"){
		jQuery("#dacura-console .model-edit-id input").val(bits.id);
	}
	if(typeof bits.label == "string"){
		jQuery("#dacura-console .model-edit-label input").val(bits.label);
	}
	if(typeof bits.comment == "string"){
		jQuery("#dacura-console .console-model-comment textarea").val(bits.comment);
	}
	if(typeof bits.ctype == "string"){
		jQuery("#dacura-console .classtype-picker select").val(bits.ctype).trigger("change");
		if(bits.ctype == "entity" && typeof bits.parent == "string"){
			jQuery("#dacura-console .entity-parent-picker select").val(bits.parent).trigger("change");			
		}
		else {
			jQuery("#dacura-console .entity-parent-picker select").val("").trigger("change");
		}
		if(bits.ctype == "complex" && typeof bits.parent != "undefined"){
			if(typeof bits.parent == "string") {
				if(bits.parent) bits.parent = [bits.parent];
				else bits.parent = [];
			}
			for(var i = 0; i<bits.parent.length; i++){
				if(bits.parent[i].length && !this.classIsVisible(bits.parent[i])){
					jQuery("#dacura-console .class-parent-picker select").prepend("<option value='" + bits.parent[i] + "' selected>" + bits.parent[i] + "</option>");
				}
			}
			jQuery("#dacura-console .class-parent-picker select").val(bits.parent).trigger("change");			
		}
		else {
			jQuery("#dacura-console .class-parent-picker select").val("").trigger("change");						
		}
		if(bits.ctype == "enumerated" && typeof bits.choices == "object"){
			var html = "";
			for(var i = 0; i<bits.choices.length; i++){
				html += this.getEnumeratedTypeInputRowHTML(bits.choices[i].id, bits.choices[i].label, bits.choices[i].comment);
			}
			jQuery('#' + this.HTMLID + " table.console-enumerated-type tbody").html(html);
			this.initEnumeratedTypesRow();
		}
		else {
			jQuery('#' + this.HTMLID + " table.console-enumerated-type tbody").html(this.getEnumeratedTypeInputRowHTML());
			this.initEnumeratedTypesRow();
		}
		this.showOtherAssertions();
	}
	if(typeof bits.domain == "string"){
		if(bits.domain.length && !this.classIsVisible(bits.domain) && !selectContainsValue("#dacura-console .property-domain-select-holder select", bits.domain)){
			jQuery("#dacura-console .property-domain-select-holder select").prepend("<option value='" + bits.domain + "'>" + bits.domain + "</option>");
		}
		jQuery("#dacura-console .property-domain-select-holder select").val(bits.domain).trigger("change.select2");					
	}
	if(typeof bits.range == "string"){
		if(bits.range.length && !this.classIsVisible(bits.range) && (this.write_in_ranges.indexOf(bits.range) == -1)){
			//alert(" appending " + bits.range);
			this.write_in_ranges.push(bits.range);
			jQuery("#dacura-console .property-range-select-holder select").prepend("<option value='" + bits.range + "'>" + bits.range+ "</option>");
		}
		jQuery("#dacura-console .property-range-select-holder select").val(bits.range).trigger("change.select2");					
	}
}


DacuraConsole.prototype.readModelForm = function(){
	var input = {};
	input.id = jQuery("#dacura-console .console-extra .model-edit-id input").val().trim();
	input.label = jQuery("#dacura-console .console-extra .model-edit-label input").val().trim();
	input.comment = jQuery("#dacura-console .console-extra .console-model-comment textarea").val().trim();
	if((this.context.mode == "create" && this.model_create_clicked == "class") || (this.context.mode == "edit" && this.context.modelclass)){
		input.ctype = jQuery("#dacura-console select.model-classtype-picker").val();
		if(input.ctype == "enumerated"){
			input.choices = this.readEnumeratedChoices();
		}
		else if(input.ctype == "entity"){
			input.parents = jQuery("#dacura-console .entity-parent-picker select").val();
		}
		else if(input.ctype == "complex"){
			input.parents = jQuery("#dacura-console .class-parent-picker select").val();
			if(this.context.mode == "edit"){
				input.extras = this.readOtherAssertionsFromForm();
			}
		}
	}
	else if((this.context.mode == "create") || this.context.mode == "harvest" || this.context.modelproperty){
		input.domain = jQuery("#dacura-console .property-domain-select-holder select").val().trim();
		input.range = jQuery("#dacura-console .property-range-select-holder select").val().trim();
		if(this.context.mode == "edit"){
			input.extras = this.readOtherAssertionsFromForm();
		}
	}
	else {
		alert("error in reading model form - no context");
	}
	input.metadata = this.readMetadataForm();
	return input;
}


DacuraConsole.prototype.initEnumeratedTypesRow = function(){
	jQuery("#dacura-console td.enumerated-type-remove").hover(function(){
		jQuery(this).addClass('uhover');
	}, function() {
		jQuery(this).removeClass('uhover');
	}).click(function(){
		jQuery(this).parent("tr:first").remove();
	});
}

DacuraConsole.prototype.readEnumeratedChoices = function(){
	var choices = [];
	jQuery("#dacura-console tr.enumerated-type-entry").each(function(){
		var choice = {};
		$this = jQuery(this);
		choice.id = $this.find(".enumerated-type-id input").val().trim();
		choice.label = $this.find(".enumerated-type-label input").val().trim();
		choice.comment = $this.find(".enumerated-type-comment input").val().trim();
		choices.push(choice);
	});
	return choices;
};


DacuraConsole.prototype.readOtherAssertionsFromForm = function(){
	var choices = {}
	jQuery("#dacura-console div.console-other-assertion").each(function(){
		$this = jQuery(this);
		var id = $this.find(".console-assertion-label").text();
		var val = $this.find(".console-assertion-input textarea").val().trim();
		choices[id] = val;
	});
	return choices;
};


DacuraConsole.prototype.setMetadataEditorValues = function(meta){
	this.current_meta = meta;
	jQuery('#' + this.HTMLID + " .metadata-viewer .metadata-harvests .console-metadata-input").html("");
	meta = (meta ? meta : {});
	var importshtml = "";
	if(typeof meta.harvests != "undefined"){
		for(var i =0; i < meta.harvests.length; i++){
			var wl = new webLocator(meta.harvests[i]);
			importshtml += wl.getHTML(this.context.mode);
		}
		if(this.context.mode == "edit"){
			importshtml += new webLocator().getHTML(this.context.mode);//always add space for a new one
		}
	}
	else if(this.context.mode == "edit" && this.context.modelproperty || (this.context.mode == "create" && this.model_create_clicked == "property")){
		importshtml = new webLocator().getHTML(this.context.mode);
	}
	jQuery('#' + this.HTMLID + " .metadata-viewer .metadata-harvests .console-metadata-input").html(importshtml);
	var genhtml = "";
	if(typeof meta.harvested != "undefined"){
		for(var i =0; i < meta.harvested.length; i++){
			var wl = new webLocator(meta.harvested[i]);
			genhtml += wl.getHTML(this.context.mode);
		}
	}
	else if(this.context.mode == "edit"){
		genhtml = new webLocator().getHTML("create");
	}
	jQuery('#' + this.HTMLID + " .metadata-viewer .metadata-harvested .console-metadata-input").html(genhtml);
}

DacuraConsole.prototype.readMetadataForm = function(){
	//temporary should be done by update state management 
	this.current_ontology.updated_meta = {};
	var meta = {};
	jQuery('#' + this.HTMLID + " .metadata-viewer .metadata-harvests .console-metadata-input span.web-locator-input").each(function(i, dom){
		var wl = new webLocator();
		wl.readFromDom(dom);
		if(wl.url && wl.url.length){
			if(typeof meta.harvests == "undefined"){
				meta.harvests = []; 
			}
			meta.harvests.push(wl);
		}
	});
	jQuery('#' + this.HTMLID + " .metadata-viewer .metadata-harvested .console-metadata-input span.web-locator-input").each(function(i, dom){
		var wl = new webLocator();
		wl.readFromDom(dom);
		if(wl.url && wl.url.length){
			if(typeof meta.harvested == "undefined"){
				meta.harvested = []; 
			}
			meta.harvested.push(wl);
		}
	});
	return meta;
}


/**
 * Writing html for model editor
 */
DacuraConsole.prototype.getChoicesHTML = function(choices){
	var html = "";
	for(var i =0; i<choices.length; i++){
		html += "<span class='enumerated-choice-label'>" + choices[i].label + "</span> ";
	}
	return html;
}


function getModelClassLink(cls){
	return "<a href='javascript:dacuraConsole.loadClassDefinition(\"" + cls + "\")'><span class='parent-class-tile'>" + cls + "</a></span>";		
}

DacuraConsole.prototype.getClassContext = function(cls){
	var context = {"modelclass": cls};
	var oid = cls.split(":")[0];
	if(oid != this.context.ontology){
		context.ontology = oid;
	}
	var colid = this.getCollectionIDForOntology(oid);
	if(colid){
		context.collection = colid;
	}
	return context;
}

DacuraConsole.prototype.getCollectionIDForOntology = function(oid){
	for(var i in this.client.collections){
		if(typeof this.client.collections[i].ontologies[oid] != "undefined"){
			return i;
		}
	}
	return false;
}

DacuraConsole.prototype.loadClassDefinition = function(cls){
	var clscol = this.getClassContext(cls);
	clscol.mode = "view";
	clscol.tool = "model";
	this.userChangesContext(clscol);
}

DacuraConsole.prototype.getParentClassesHTML = function(ptypes, mode){
	var html = "";
	if(!ptypes) return "";
	if(ptypes.constructor != Array){
		ptypes = [ptypes];
	}
	
	for(var i = 0; i<ptypes.length; i++){
		//if(mode && mode == "view"){
			html += getModelClassLink(ptypes[i]);		
		//}
		//else {
		//	html += "<span class='parent-class-tile'>" + ptypes[i] + "<span class='remove-parent-class' data-id='" + ptypes[i] + "'>" + dacura.system.getIcon("delete", {title: "remove this parent class"}) + "</span></span>";
		//}
	}
	return html;
} 

DacuraConsole.prototype.getEnumeratedTypeInputRowHTML = function(id, label, comment, rest){
	id = (id ? id : "");
	label = (label ? label : "");
	comment = (comment ? comment : "");
	rest = (rest ? rest : "");
	var html = "<tr class='enumerated-type-entry'>";
	html += "<td class='enumerated-type-number'><input type='hidden' value='" + JSON.stringify(rest) + "'></td>";
	html += "<td class='enumerated-type-id'><input type='text' value='" + id + "'></td>";
	html += "<td class='enumerated-type-label'><input type='text' value='" + label + "'></td>";
	html += "<td class='enumerated-type-comment'><input type='text' value='" + comment + "'></td>";
	html += "<td class='enumerated-type-remove'><i class='fa fa-remove fa-lg'></td>";
	html += "</tr>";
	return html;
};


DacuraConsole.prototype.getOtherAssertionsHTML = function(json){
	var html = "";
	if(json && size(json) > 0){
		for(var key in json){
			html += this.getAssertionHTML(key, json[key]);
		}
	}
	return html;
}

DacuraConsole.prototype.getAssertionHTML = function(key, val){
	if(this.mode == "view"){
		val = this.current_ontology.displayPredicateValue(val);
	}
	else if(typeof val == "object"){
		if(typeof val.lang != "undefined" && typeof val.data != "undefined"){ val = val.data;}
	}
	var html = "<span class='console-other-assertion'>";
	html += "<span class='input-group other-assertion' data-property='" + key + "'>"; 
	html += "<span class='input-group-addon'><i class='fa fa-question fa-fw'></i> " + key + "</span> ";
	html += "<span class='console-assertion-input'><input type='text' value='";
	if(typeof val == "string"){
		html += val + "'>"
	}
	else {
		html += JSON.stringify(val) + "' class='json-input'>";		
	}
	html += "</span></span></span>";
	return html;
};



DacuraConsole.prototype.initPropertySelector = function(css, val){
	if(!this.context.collection || !this.current_ontology) return;
	this.importprops = [];
	var props = this.current_ontology.properties;
	var html = "<option value=''>Not Imported</option><option value='0'>Import to New Property</option>";
	html += "<optgroup label='" + this.current_ontology.id + " properties'>";
	for(var i in props){
		this.importprops.push(i);
		html += "<option value='" + i + "'>" + this.current_ontology.getPropertyLabel(i) + "</option>";
	}
	html += "</optgroup>";
	var shtml = "<select class='factoid-import-picker'>" + html + "</select>";

	var self = this;
	jQuery('#' + this.HTMLID + " " + css).html(shtml);
	jQuery('#' + this.HTMLID + " " + css + " select.factoid-import-picker").select2({
		  placeholder: "Not Imported",
		  width: 200,
		  allowClear: true,
		  tags: true
	}).on('change', function(){
		if(self.context.factoid === false){
			return;//alert("no factoid selected - can't import");
		}
		var foid = self.pagescanner.factoids[self.pagescanner.sequence[self.context.factoid]];
		var h = (foid.getHarvests() ? foid.getHarvests() : "");
		if(this.value === "" && h === ""){
			self.hideUpdateImportButton();		
		}
		else {
			self.showUpdateImportButton();				
		}
		if(this.value === "0"){
			var def = (foid ? foid.getModelPropertyScreenFiller("create") : false);
			self.displayModelProperty(def, "create", true);
		}
		else if(this.value == ""){
			self.hideAllElementsOnModelForm();
		}
		else {
			if(self.current_ontology.hasProperty(this.value)){
				self.context.modelproperty = this.value;
				var def = (foid ? foid.getModelPropertyScreenFiller("edit") : false);
				self.displayModelProperty(def, "edit", true);
			}
			else { // importing to external property
				self.context.modelproperty = false;
				var def = (foid ? foid.getModelPropertyScreenFiller("external") : {});
				self.displayExternalProperty(this.value, def.metadata);
			}
		}
	});
}


DacuraConsole.prototype.showOtherAssertions = function(){
	if(this.context.modelclass){
		var json = this.current_ontology.getOtherAssertions(this.context.modelclass);
	}
	else if(this.context.modelproperty){
		var json = this.current_ontology.getOtherPropertyAssertions(this.context.modelproperty);
	}
	if(json){
		html = this.getOtherAssertionsHTML(json);
		if(html.length){
			jQuery("#dacura-console .console-other-assertions").html(html).show();	
		}
	}
}
