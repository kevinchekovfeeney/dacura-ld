var dconsole = {
	mode: "menu",
	loaded_properties: {},
	loaded_ontologies: {},
	loaded_graphs: {},
	loaded_candidates: {},
	create_frames: {},
	current_ontology: false,
	current_frame: false,
	current_graph: false,
	lasttoggleid: 0,
	menu_pconfig: {
		resultbox: "#dacura-console-menu-message",
		busybox: "#dacura-console"
	}
};

dconsole.init = function(html){
	dconsole.scanPage(html, dacura.params.context);
	dconsole.showUserOptions(dacura.params.context.mode);
	dconsole.setContext(dacura.params.context);
	//dconsole.setContext(dacura.params.context, dconsole.scanPage);
}

dconsole.getURLForOntologyID = function(id){
	if(typeof dacura.params.collection_contents.ontologies != "object") return "";
	for(var i = 0; i<dacura.params.collection_contents.ontologies.length; i++){
		if(dacura.params.collection_contents.ontologies[i].id == id){
			return dacura.params.collection_contents.ontologies[i].url;
		}
	}
	return "";
}

dconsole.getIDForOntologyURL = function(url){
	if(typeof dacura.params.collection_contents.ontologies != "object") return "";
	for(var i = 0; i<dacura.params.collection_contents.ontologies.length; i++){
		if(dacura.params.collection_contents.ontologies[i].url == url){
			return dacura.params.collection_contents.ontologies[i].id;
		}
	}
	return "";
}

dconsole.display = function(type, id){
	if(type == "class"){
		idbits = id.split(":");
		if(this.current_ontology && this.current_ontology.id == idbits[0]){
			if(typeof this.current_ontology.classes[id] == "undefined"){
				return alert("No such class as " + idbits[1] + " in " + idbits[0]);
			}
			else {
				this.ontologyMode = true;
				this.setContext({"class": id});
			}
		}
		else {
			if(typeof this.loaded_ontologies[idbits[0]] == "undefined"){
				var onturl = this.getURLForOntologyID(idbits[0]);
				if(onturl){
					this.ontologyMode = true;
					this.setContext({"ontology": onturl, "class": id});				
				}
				else {
					return alert("No known ontologies with url " + onturl);
				}				
			}
			else {
				this.ontologyMode = true;
				this.setContext({"ontid": idbits[0], "class": id});							
			}
		}
	}
}

dconsole.showUserOptions = function(mode){
	this.mode = 'menu';
	jQuery('#dacura-console .console-context .collection').html(dacura.params.context.title);
	jQuery('#dacura-console .console-user').html(this.getUserMenuHTML());
	jQuery('#console-user-actions').menu({
		  icons: { submenu: "ui-icon-circle-triangle-w" }
	});
	jQuery('#dacura-console .console-controls').html(this.getControlsHTML());
	if(typeof mode == "string" && mode == "model"){
		dconsole.setOntologyMode();
	}
	else {
		dconsole.setDataMode();
	}
	//var consoleZindex = jQuery('#dacura-console').css('z-index');
	//var gzindex = parseInt(consoleZindex) + 1;
	//this.grabWikiFacts();
	jQuery('.console-user-context').hover(
	  	function(){ 
	      	jQuery(this).addClass('ui-state-focus');
	      	jQuery('.console-user-menu').show("blind");
	      	jQuery('#console-user-actions').menu("refresh"); 
	    },
	  	function(){ 
	      	jQuery(this).removeClass('ui-state-focus'); 
	      	jQuery('.console-user-menu').hide("fade", "slow");
	    }
	);
};

dconsole.setContext = function(context, callback){
	if(context && this.ontologyMode){
		if(typeof context["ontology"] == "string"){
			jQuery('#dacura-console .console-context select.console-ontology').val(context["ontology"]);
			jQuery('#dacura-console .console-context select.console-ontology').selectmenu("refresh");
			dconsole.changeOntology(context, callback);
		}
		else if(typeof context["ontid"] == "string"){
			var nont = this.loaded_ontologies[context["ontid"]];
			if(nont){
				this.current_ontology = nont;
				this.loadOntologyDetails(nont, context, callback);
			}		
			else {
				alert("attempted to load an unknown ontology " + context["ontid"]);
			}
		}	
		else if(typeof context["class"] == "string"){
			jQuery('#dacura-console .console-context select.console-class-list').val(context['class']);
			jQuery('#dacura-console .console-context select.console-class-list').selectmenu("refresh");
			dconsole.changeClass(context, callback);
		}
		else if(typeof context["property"] == "string"){
			jQuery('#dacura-console .console-context select.console-property-list').val(context['property']);
			jQuery('#dacura-console .console-context select.console-property-list').selectmenu("refresh");
			dconsole.changeModelProperty(context, callback);
		}
		else {
			if(typeof callback == "function") callback(context);			
		}
	}
	else if(context){
		if(typeof context.type == "string"){
			jQuery('#dacura-console .console-context select.console-entity-type').val(context.type);
			jQuery('#dacura-console .console-context select.console-entity-type').selectmenu("refresh");
			dconsole.changeEntityType(context, callback);
		}
		else if(typeof context.entity == "string"){
			jQuery('#dacura-console .console-context select.console-entity-list').val(context.entity);
			jQuery('#dacura-console .console-context select.console-entity-list').selectmenu("refresh");	
			dconsole.changeEntityList(context, callback);
		}	
		else if(typeof context.property == "string"){
			jQuery('#dacura-console .console-context select.console-properties').val(context.property);
			jQuery('#dacura-console .console-context select.console-properties').selectmenu("refresh");	
			dconsole.changeProperty(context, callback);
		}
		else {
			if(typeof callback == "function") callback(context);
		}
	}
	else {
		if(typeof callback == "function") callback(false);
	}
}

/* functions for managing visibility, layout, context changes */

/**
 * Highest level switch - between collections 
 */
dconsole.switchCollectionContext = function(target){
	dacura.params.apiurl = dacura.params.baseapiurl;
	if(target != "all"){
		dacura.params.apiurl += target + "/";
	}
	dconsole.reload(dacura.params.context);
}

/**
 * Second highest level switch - between model and data modes 
 */
dconsole.setDataMode = function(){
 	dconsole.clearContext();
 	dconsole.ontologyMode = false;	
	jQuery('#dacura-console .console-context .entitytype').html(this.getEntityTypeSelectorHTML());
	jQuery('#dacura-console select.console-entity-type').selectmenu({
		  change: dconsole.changeEntityType, width: 180
	});
}

dconsole.setOntologyMode = function(){
	dconsole.ontologyMode = true;	
	dconsole.clearContext();
	jQuery('#dacura-console .console-context .entitytype').html(this.getOntologySelectorHTML());
	jQuery('#dacura-console select.console-ontology').selectmenu({
		  change: dconsole.changeOntology, width: 180
	});
}

dconsole.clearOntology = function(){
	dconsole.clearSubContext();
	dconsole.clearExtra();
}

dconsole.clearOntologyMode = function(){
	dconsole.setDataMode();
}

dconsole.toggleOntologyMode = function(){
	if(this.ontologyMode){
		dconsole.clearOntologyMode();
	}
	else {
		dconsole.setOntologyMode();
	}
}

/* clear the state of the console or various parts of it */
dconsole.clear = function(){
	jQuery('#dacura-console .console-extra').slideUp("fast");
	jQuery('#dacura-console .console-context .context-element').empty();
	jQuery('#dacura-console .console-stats').empty();
	jQuery('#dacura-console .console-controls').empty();
	jQuery('#dacura-console .console-user').empty();
	jQuery('#dacura-console .console-extra').empty();
	jQuery('#dacura-console #dacura-console-menu-message').empty();
}

dconsole.clearContext = function(){
	jQuery('#dacura-console .console-context .entitytype').html("");
	jQuery('#dacura-console .console-context .entities').html("");
	jQuery('#dacura-console .console-context .properties').html("");
}

dconsole.clearSubContext = function(){
	jQuery('#dacura-console .console-context .entities').html("");
	jQuery('#dacura-console .console-context .properties').html("");
}

/* 
 * the console uses 6 subscreens which are loaded via the loadExtra function
 * ontology mode: create class, create property, view/update class, view/update property
 * data mode: create candidate, view/update candidate
 *
 * These functions manage the loading and unloading of the various different subscreens 
 * 
 */
 dconsole.loadExtra = function(html, callback){
	jQuery('#dacura-console .console-extra').html(html).hide();
	jQuery('#dacura-console .console-extra').slideDown("medium", callback);
 }

 dconsole.clearExtra = function(callback){
	dconsole.mode = 'menu';
	jQuery('#dacura-console .console-extra').slideUp("fast", callback).html("");
 }

 /* data mode - creating and viewing candidates via frames */ 
 dconsole.showCreateCandidate = function(){
	dconsole.mode = "create";
	jQuery('#dacura-console .createentity').hide();	
	var enttype = jQuery('#dacura-console .console-context select.console-entity-type').val();
	jQuery( "#dacura-console .console-context select.console-entity-type" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-entity-list" ).selectmenu( "disable" );
	dconsole.loadExtra(this.getCreateCandidateHTML(enttype));
	jQuery('#dacura-console button.create-new-entity').button().click(function(){
		var newentid = jQuery('#dacura-console .console-extra .new-entity-id').val();
		var entprops = dconsole.getFilledCreatedProperties();
		dconsole.createCandidate(enttype, newentid, entprops, false, dconsole.menu_pconfig);
	});
	jQuery('#dacura-console button.test-create-new-entity').button().click(function(){
		var newentid = jQuery('#dacura-console .console-extra .new-entity-id').val();
		var entprops = dconsole.getFilledCreatedProperties();
		dconsole.createCandidate(enttype, newentid, entprops, false, dconsole.menu_pconfig, true);
	});
	jQuery('#dacura-console button.cancel-new-entity').button().click(function(){
		dconsole.closeCreateCandidate();
	});
};

dconsole.closeCreateCandidate = function(callback){
	jQuery( "#dacura-console .console-context select.console-entity-type" ).selectmenu( "enable" );
	jQuery( "#dacura-console .console-context select.console-entity-list" ).selectmenu( "enable" );
	jQuery('#dacura-console .createentity').show();	
	dconsole.loaded_properties = {};
	dconsole.clearExtra();
};

dconsole.closeViewCandidate = dconsole.closeCreateCandidate; 

/* model mode - creating and updating classes and properties of ontologies */

dconsole.showCreateClass = function(){
	dconsole.mode = "create";
	jQuery('#dacura-console .createclass').hide();	
	jQuery('#dacura-console .createmodelproperty').hide();
	jQuery( "#dacura-console .console-context select.console-ontology" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-property-list" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-class-list" ).selectmenu( "disable" );
	dconsole.loadExtra(dconsole.getCreateClassHTML());
	dconsole.current_ontology.initCreateClass(dconsole.submitNewClass);
}

dconsole.showClass = function(cls){
	//jQuery( "#dacura-console .console-context select.console-ontology" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-property-list" ).val("").selectmenu( "refresh" );
	dconsole.mode = "view";
	dconsole.loadExtra(dconsole.getViewClassHTML(cls));
	dconsole.current_ontology.initUpdateClass(dconsole.submitUpdatedClass, dconsole.deleteClass);
}

dconsole.showCreateModelProperty = function(){
	dconsole.mode = "create";
	jQuery('#dacura-console .createmodelproperty').hide();	
	jQuery('#dacura-console .createclass').hide();	
	jQuery( "#dacura-console .console-context select.console-ontology" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-property-list" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-class-list" ).selectmenu( "disable" );
	dconsole.loadExtra(dconsole.getCreatePropertyHTML());
	dconsole.current_ontology.initCreateProperty(dconsole.submitNewProperty);
}

dconsole.showModelProperty = function(prop){
	jQuery( "#dacura-console .console-context select.console-class-list" ).val("").selectmenu( "refresh" );	
	//jQuery( "#dacura-console .console-context select.console-ontology" ).selectmenu( "disable" );
	dconsole.mode = "view";
	dconsole.loadExtra(dconsole.getViewModelPropertyHTML(prop));
	dconsole.current_ontology.initUpdateProperty(dconsole.submitUpdatedProperty, dconsole.deleteModelProperty);
}

dconsole.clearModelSubcreen = function(){
	dconsole.mode = "menu";
	jQuery('#dacura-console .createclass').show();	
	jQuery('#dacura-console .createmodelproperty').show();
	jQuery( "#dacura-console .console-context select.console-ontology" ).selectmenu( "enable" );
	jQuery( "#dacura-console .console-context select.console-property-list" ).selectmenu( "enable" );
	jQuery( "#dacura-console .console-context select.console-class-list" ).selectmenu( "enable" );
	dconsole.clearExtra();
}

/* handling state updates on main menu - data mode */

dconsole.loadEntityType = function(cls) {
	if(typeof dacura.params.collection_contents.entity_classes[cls] == "object" && typeof dacura.params.collection_contents.entity_classes[cls].label == "object"){
		var clsname = dacura.params.collection_contents.entity_classes[cls].label.data;
	}
	else {		
		var clsname = cls.substring(cls.lastIndexOf('#')+1);
	}
	jQuery('#dacura-console .console-context .entities').html(this.getEntitySelectorHTML(cls, clsname));
	jQuery('#dacura-console .console-context select.console-entity-list').selectmenu({
	  change: dconsole.changeEntityList, width: 180
	});
	jQuery('#dacura-console .createentity').show();
	var lf = function(frame){
		dconsole.addCreateFrame(cls, frame);
		jQuery('#dacura-console .console-context .properties').html(dconsole.getChooseCandidatePropertiesHTML(frame, "", true));
		jQuery('#dacura-console .console-context .properties select.console-properties').selectmenu({
			  change: dconsole.changeProperty, width: 180
		});
	}			  
	dconsole.getEmptyFrame(cls, this.menu_pconfig, lf);
};

dconsole.changeEntityType = function(context, callback){
	var type = jQuery('#dacura-console .console-context select.console-entity-type').val();
	if(type.length){
		dconsole.loadEntityType(type);
	}
	else {
	   dconsole.clearEntityType();
	}
	if(typeof callback == "function") { callback(context) };		
}

dconsole.clearEntityType = function(){
	jQuery('#dacura-console .console-context .properties').html("");
	jQuery('#dacura-console .console-context .entities').html("");
	jQuery('#createentity').hide();			  
};

dconsole.changeEntityList = function(){
	var entid = jQuery('#dacura-console .console-context select.console-entity-list').val();
	var propval = jQuery('#dacura-console .console-context .properties select.console-properties').val();
	if(entid.length){
		var lc = function(frame){
			dconsole.loadCandidate(entid, frame);
		    if(propval.length){
		    	dconsole.showCandidate(entid, propval);
		    }
		}
	  	dconsole.getFilledFrame(entid, {}, lc);
	  	jQuery('#dacura-console .createentity').hide();
  	}
  	else {
		jQuery('#dacura-console .createentity').show();		  
  	}
}

dconsole.changeProperty = function (){
	  var entid = jQuery('#dacura-console .console-context select.console-entity-list').val();
	  var propval = jQuery('#dacura-console .console-context .properties select.console-properties').val();
	  if(propval.length){
		jQuery('#dacura-console .createproperty').show();			  
	  }
	  else {
		 jQuery('#dacura-console .createproperty').hide();			  				  
	  }
	  if(entid && propval.length){
		  if(this.mode == "menu"){
		      dconsole.showCandidate(entid, propval);
		  }
		  else {
			  //let them add it with the plus button....	
		  }
	  }
};


dconsole.removePropertyField = function(prop){
	delete (this.loaded_properties[prop]);
	jQuery("div[data-id='" + prop + "']").remove();
}

dconsole.createProperty = function(){
	var enttype = jQuery('#dacura-console .console-context select.console-entity-type').val();
	var entid = jQuery('#dacura-console .console-context select.console-entity-list').val();
	var prop = jQuery('#dacura-console .console-context select.console-properties').val();
	if(!entid || !entid.length){
		if(this.mode != "create"){
			this.showCreateCandidate();
		}
		if(typeof this.loaded_properties[prop] != "undefined"){
			alert(prop + " property has already been added");
		}
		else {
			//load a property frame and add it to the create form
			var callback = function(frame){
				dconsole.addPropertyToCreate(prop, frame);
			};
			dconsole.getEmptyPropertyFrame(enttype, prop, dconsole.menu_pconfig, callback);
		}			
	}
	else {
		if(this.mode != "view"){
			dconsole.showCandidate(entid, prop);
		}	
		if(typeof this.loaded_properties[prop] != "undefined"){
			alert(prop + " property has already been added");
		}
		else {
			//load a property frame and add it to the create form
			var callback = function(frame){
				dconsole.addPropertyToView(prop, frame);
			};
			dconsole.getFilledPropertyFrame(entid, prop, dconsole.menu_pconfig, callback);
		}			
	}
}

dconsole.showCandidate = function(entid, propid){
	jQuery( "#dacura-console .console-context select.console-entity-type" ).selectmenu( "disable" );
	dconsole.loadExtra(dconsole.getViewCandidateHTML(entid, propid));
	this.mode = "view";
	
}

dconsole.getFilledCreatedProperties = function(){
	//this is where we get all of the data from the frames and suck it back into the console
	return {};
}

dconsole.addPropertyToCreate = function(prop, frame){
	dconsole.loaded_properties[prop] = frame; 
	jQuery('#dacura-console .console-extra-payload').append(this.getPropertyFieldHTML(prop, frame));
	var cls = jQuery( "#dacura-console .console-context select.console-entity-type" ).val();
	//data-id='" + prop + "' 
	var target = 'create-dacura-property-' + prop;
	var fv = new FrameViewer(cls, target, this.menu_pconfig);
	fv.draw([frame], "view");
}

dconsole.addPropertyToView = function(prop, frame){
	dconsole.loaded_properties[prop] = frame; 
	jQuery('#dacura-console .console-extra-payload').append(this.getPropertyFieldHTML(prop, frame));
	var cls = jQuery( "#dacura-console .console-context select.console-entity-type" ).val();
	var target = 'dacura-property-' + prop;
	var elid = document.getElementById(target);
	var fv = new FrameViewer(cls, target, this.menu_pconfig);
	fv.draw([frame], "view");
}

dconsole.loadCandidate = function(entid, frame){
	this.addCandidateFrame(entid, frame);
	var val = jQuery('#dacura-console .console-context .properties select.console-properties').val();
	jQuery('#dacura-console .console-context .properties').html(dconsole.getChooseCandidatePropertiesHTML(frame, val));
	jQuery('#dacura-console .console-context .properties select.console-properties').selectmenu({
		  change: dconsole.changeProperty, width: 180
	});	
}


dconsole.addCreateFrame = function(cls, frame){
	this.current_frame = frame;
	this.create_frames[cls] = frame;
}

dconsole.addCandidateFrame = function(id, frame){
	this.current_frame = frame;
	this.loaded_candidates[id] = frame;
}

/* state updates on main menu, model mode */

dconsole.changeOntology = function(context, callback){
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(onturl && onturl.length){
		var ontid = dconsole.getIDForOntologyURL(onturl);
		if(ontid && typeof dconsole.loaded_ontologies[ontid] != "undefined"){
			dconsole.current_ontology = dconsole.loaded_ontologies[ontid];
			dconsole.clearSubContext();
			dconsole.loadOntologyDetails(dconsole.current_ontology, context, callback); 
		}
		else {
			var wlod = function(ont){
				dconsole.loadOntologyDetails(ont, context, callback);
			}
			dconsole.loadOntology(onturl, dconsole.menu_pconfig, dacura.params.view_args, wlod);
		}
	}
	else {
		dconsole.clearOntology();
		if(typeof callback == "function") { callback(context) };
	}
}

dconsole.loadOntologyDetails = function(ont, context, callback){
	var cval = (context && typeof context['class'] == "string") ? context['class'] : "";
	jQuery('#dacura-console .console-context .entities').html(dconsole.getClassSelectorHTML(ont, cval));
	jQuery('#dacura-console .console-context select.console-class-list').selectmenu({
		  change: dconsole.changeClass, width: 180
	});
	var pval = (context && typeof context['property'] == "string") ? context['property'] : "";
	jQuery('#dacura-console .console-context .properties').html(dconsole.getPropertiesSelectorHTML(ont, pval));
	jQuery('#dacura-console .console-context select.console-property-list').selectmenu({
		  change: dconsole.changeModelProperty, width: 180
	});
	if(cval.length){
		dconsole.changeClass(context, callback);
	}
	else if(pval.length){
		dconsole.changeModelProperty(context, callback);
	}
	else if(typeof callback == "function") { 
		callback(context); 
	};
}

dconsole.changeClass = function(context, callback){
	var cls = jQuery('#dacura-console .console-context select.console-class-list').val();
	if(cls.length){
		dconsole.showClass(cls);
	}
	else {
	   dconsole.clearModelSubcreen();
	}
	if(typeof callback == "function") { callback(context) };	
}

dconsole.changeModelProperty = function(context, callback){
	var prop = jQuery('#dacura-console select.console-property-list').val();
	if(prop.length){
		dconsole.showModelProperty(prop);
	}
	else {
	   dconsole.clearModelSubcreen();
	}
	if(typeof callback == "function") { callback(context) };	
}

/* html generation of main console menu */

dconsole.getUserMenuHTML = function(){
	var html = '<div class="console-user-context">';
	html += '<a href="' + dacura.params.profileurl + '">';
	html += '<span class="username" title="' + dacura.params.username + '"><img height="24" class="uicon" src="' + dacura.params.usericon + '" />';
	html += "</span></a>";
	html += "<div class='console-user-menu dch'>";
	html += '<ul id="console-user-actions">';
	if(typeof dacura.params.context.collection != "undefined"){
		for(var i in dacura.params.collection_choices){
			if(i == dacura.params.context.collection){
			 	html += "<li class='ui-state-disabled'>" + dacura.params.collection_choices[i].title + "</li>";				
			}
			else {
			 	html += '<li><a href="javascript:dconsole.switchCollectionContext(\'' + i + '\')">' + dacura.params.collection_choices[i].title + '</a></li>';				
			}
		}
	}
	html += "<li>--</li><li>";
	html += '<span class="ui-icon ui-icon-disk"></span>';
	html += "<a href='" + dacura.params.dacuraurl + "'>Dacura Home</a></li><li>"; 
	html += '<span class="ui-icon ui-icon-disk"></span>';
	html += "<a href='" + dacura.params.logouturl + "'>Logout</a></li>"; 
	html += "</ul></div></div>";
	return html;
};

dconsole.getOntologySelectorHTML = function(){
	if(dacura.params.collection_contents.ontologies.length == 0){
		var html = "<span class='no-ontologies'>No ontologies</span>";
	}
	else {
		var html = "<select class='console-ontology'><option value=''>Select Ontology</option>";
		for(var i = 0; i < dacura.params.collection_contents.ontologies.length; i++){
			html += "<option value='" + dacura.params.collection_contents.ontologies[i]['url'] + "'>" + dacura.params.collection_contents.ontologies[i]['title'] + "</option>";
		}
		html += "</select>";
	}
	return html;	
};

dconsole.getClassSelectorHTML = function(ont, val){
	if(typeof ont.classes == "undefined" || size(ont.classes) == 0){
		var html = " <span class='empty-ontology'>no classes defined</span>";
	}
	else {		
		var html = "<select class='console-class-list'>";
		html += "<option value=''>Select Class (" + size(ont.classes) + ")</option>";
		for(var i in ont.classes){
			var sel = (val && val.length && val == i) ? " selected" : "";
			html += "<option value='" + i + "'" + sel + ">" + ont.getClassLabel(i) + "</option>";
		}
		html += "</select>";
	}
	html += "<span class='createclass'><a href='javascript:dconsole.showCreateClass()'>" + dacura.params.new_thing_icon + "</a></span>";
	return html;
}

dconsole.getPropertiesSelectorHTML = function(ont, val){
	if(typeof ont.properties == "undefined" || size(ont.properties) == 0){
		var html = " <span class='empty-ontology'>no properties defined</span>";
	}
	else {
		var html = "<select class='console-property-list'><option value=''>Select Property (" + size(ont.properties) + ")</option>";
		for(var i in ont.properties){
			var sel = (val && val.length && val == i) ? " selected" : "";
			html += "<option value='" + i + "'" + sel + ">" + ont.getPropertyLabel(i) + "</option>";
		}
		html += "</select>";
	}
	html += "<span class='createmodelproperty'><a href='javascript:dconsole.showCreateModelProperty()'>" + dacura.params.new_thing_icon + "</a></span>";
	return html;
}

dconsole.isJSONObjectLiteral = function(json){
	if(typeof json.data == "undefined") return false;
	if((typeof json.type == "undefined" || json.type.length == 0) && typeof json.lang == "undefined" || json.lang.length) return false;
    for(var i in json){
		if(i != "lang" && i != "data" && i != "type") return false;
    }
    return true;
}

dconsole.getChooseCandidatePropertiesHTML = function(frame, val, unfilled){
	unfilled = (typeof unfilled == "undefined" || unfilled);
	var html = "<select class='console-properties'><option value=''>Choose a property</option>";
	var empties = [];
	var filled = [];
	if(unfilled){
		for(var i = 0; i < frame.length; i++){
			html += "<option value='" + frame[i]['property'] + "'" + sel + ">" + frame[i]['label']['data'] + "</option>";
		}
	}
	else {
		for(var i = 0; i < frame.length; i++){
			if(typeof frame[i].value != "undefined"){
				if(typeof frame[i].value == "string"){
					if(frame[i].value.length == 0){
						empties.push({id: frame[i]['property'], label: frame[i].label.data});
					}
					else {
						filled.push({id: frame[i]['property'], label: frame[i].label.data + " (1)", count: 1});
					}
				}
				else if(this.isJSONObjectLiteral(frame[i].value)){
					filled.push({id: frame[i]['property'], label: frame[i].label.data + " (1)", count: 1});
				}
				else if(typeof frame[i].value == "object"){
					filled.push({id: frame[i]['property'], label: frame[i].label.data + "(" + frame[i].value.length + ")", count: frame[i].value.length });				
				} 
				else {
					jpr(frame[i]);
				}
			}
			else {
				empties.push({id: frame[i]['property'], label: frame[i].label.data});	
			}
		}
		//sort properties filled properties by count, then alphabetical
		var comparePropertiesByCount = function(a,b) {
			if(a.count < b.count){
				return -1;
			}
			if(b.count < a.count){
				return 1;
			}
			if(a.label < b.label){
				return -1;
			}				
			if(a.label > b.label){
				return 1;
			}			
			return 0;	
		}
		filled.sort(comparePropertiesByCount);
		for(var i = 0; i < filled.length; i++){
			var sel = (val && val.length && val == filled[i].id) ? " selected" : "";
			html += "<option class='filled-property' value='" + filled[i].id + "'" + sel + ">" + filled[i].label + "</option>";		
		}	
		for(var i = 0; i < empties.length; i++){
			var sel = (val && val.length && val == empties[i].id) ? " selected" : "";
			html += "<option class='empty-property' value=''" + sel + ">" + empties[i].label + "</option>";		
		}	
	}
	html += "</select><span class='createproperty'><a href='javascript:dconsole.createProperty()'>" + dacura.params.new_thing_icon + "</a></span>";
	html += "<span class='viewproperty'>" + dacura.params.view_property_icon + "</span>";
	return html;
}

dconsole.getEntityTypeSelectorHTML = function(){
	var html = "<select class='console-entity-type'><option value=''>Select Entity Type</option>";
	for(var i = 0; i < dacura.params.collection_contents.entity_classes.length; i++){
		if(typeof dacura.params.collection_contents.entity_classes[i] == "string"){
			var clsname = dacura.params.collection_contents.entity_classes[i].substring(dacura.params.collection_contents.entity_classes[i].lastIndexOf('#')+1);
			if(clsname != "Nothing"){
				html += "<option value='" + dacura.params.collection_contents.entity_classes[i] + "'>" + clsname + "</option>";
			}
		}
		else {
			if(typeof dacura.params.collection_contents.entity_classes[i] == "object"){
				if(typeof dacura.params.collection_contents.entity_classes[i]['class'] == "string" && (urlFragment(dacura.params.collection_contents.entity_classes[i]['class']) == "Nothing")){ continue; }
				if(typeof dacura.params.collection_contents.entity_classes[i].label == "object"){
					var label = dacura.params.collection_contents.entity_classes[i].label.data;
				}
				else {
					var label = (typeof dacura.params.collection_contents.entity_classes[i].id != "undefined" ? dacura.params.collection_contents.entity_classes[i].id : "no label");
				}
			}
			else {
				var label = "no label";
			}
			html += "<option value='" + dacura.params.collection_contents.entity_classes[i]['class'] + "'>" + label + "</option>";
		}
	}
	html += "</select><span class='createentity'><a href='javascript:dconsole.showCreateCandidate()'>" + dacura.params.new_thing_icon + "</a></span>";
	return html;
};

dconsole.getEntitySelectorHTML = function(cls, clsname){
	if(typeof dacura.params.collection_contents.entities[cls] == "undefined" || dacura.params.collection_contents.entities[cls].length == 0){
		return "";
	}
	var html = "<select class='console-entity-list'><option value=''>Select " + clsname + "</option>";
	for(var i = 0; i < dacura.params.collection_contents.entities[cls].length; i++){
		html += "<option value='" + dacura.params.collection_contents.entities[cls][i] + "'>" + dacura.params.collection_contents.entities[cls][i] + "</option>";
	}
	html += "</select>";
	return html;
};

/* html to populate the extended rolled-down version of the console */

dconsole.getCreateFieldHTML = function(label, entry, extra){
	var html = "<div class='console-create-field'>";
	html += "<span class='label'>" + label + "</span>";
	html += "<span class='entry'>" + entry + "</span>";
	html += "<span class='extra'>" + extra + "</span>";
	html += "</div>";
	return html;
}

dconsole.getCreateFormButtons = function(etype){
	var html = "<div class='console-extra-buttons'>";
	html += this.getConsoleMessageField();
	html += "<button class='cancel-new-entity'>Cancel</button>";
	html += "<button class='test test-create-new-entity'>Test adding new " + etype + "</button>";
	html += "<button class='create-new-entity'>Add " + etype + "</button>";
	html += "</div>";
	return html;
}

dconsole.getPropertyFieldHTML = function (prop, frame){
	var html = "<div id='dacura-property-" + prop + "' class='console-create-field'>";
	html += "<span class='label'>" + urlFragment(prop) + "</span>";
	html += "<span class='entry'><input type='text' value=''></span>";
	html += "<span class='extra'>" + this.getRemovePropertyHTML(prop) + "</span>";
	html += "</div>";
	return html;
}

dconsole.getConsoleMessageField = function(){
	var html = "<div id='dacura-console-extra-message' class='console-user-message console-create-message'></div>";
	return html;
}


dconsole.getViewCandidateHTML = function(entid, type, property){
	var html = "<div class='console-extra-payload view-entity'>";
	html += "<span class='etype'>"+ urlFragment(type) + "</span> ";
	html += "<span class='eid'>"+ entid + "</span> ";
	html += "<span class='eproperty'>"+ urlFragment(property) + "</span>";
	html += "<button class='close-view-entity'>Close</button>";
	html += "</div>";
	return html;
};


dconsole.getCreateCandidateHTML = function(enttype){
	var etype = urlFragment(enttype);
	var html = "<div class='console-create-payload'>";
	html += this.getCreateFieldHTML(etype + " id", "<input type='text' class='new-entity-id' value='" + lastURLBit() + "'> ", "");
	html += "</div>";
	//html += this.getEntityProvenanceField();
	html += this.getCreateFormButtons(etype);
	return html;
};

dconsole.getRemovePropertyHTML = function(prop){
	var html = "<a href='javascript:dconsole.removePropertyField(\"" + prop + "\")'>" + dacura.params.remove_property_icon + "</a>";
	return html;
}

dconsole.getCreateClassHTML = function(){
	var html = "<div class='console-extra-screen console-create-class'>";
	html += this.current_ontology.getCreateClassHTML();
	html += "</div>";
	return html;
};

dconsole.getViewClassHTML = function(cls){
	var html = "<div class='console-extra-screen console-view-class'>";
	html += this.current_ontology.getViewClassHTML(cls);	
	html += "</div>";
	return html;
};


dconsole.getCreatePropertyHTML = function(){
	var html = "<div class='console-extra-screen console-create-property'>";
	html += this.current_ontology.getCreatePropertyHTML();
	html += "</div>";
	return html;
};


dconsole.getViewModelPropertyHTML = function(prop){
	var html = "<div class='console-extra-screen console-view-model-property'>";
	html += this.current_ontology.getViewPropertyHTML(prop);	
	html += "</div>";
	return html;
};

dconsole.getControlsHTML = function(){
	var html = "<span class='ontology-mode'><a href='javascript:dconsole.toggleOntologyMode()'>" + dacura.params.change_mode_icon + "</a>";
	return html;
};


/* common generic functions for api interactions */

dconsole.getXHRUpdateTemplate = function(data, pconfig, scb, fcb){
	var xhr = {};
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.data = JSON.stringify(data);
	xhr.type = "POST";
	xhr.dataType = "json";
	xhr.beforeSend = function(){
		dconsole.setBusy(pconfig);
	}
	xhr.always = dconsole.notBusy;
	if(scb){
		xhr.done = scb;
	}
	else {
		xhr.done = function(response, textStatus, jqXHR) {
			try {
				var cx = (typeof response == "object") ? response : JSON.parse(response);
			}
			catch(e){
				dconsole.writeResultMessage("error", "Failed to parse server response", e.message, jqXHR.responseText);
			}
			var ldr = new LDResult(cx, pconfig);
			ldr.show();
			if(ldr.status == "accept" && typeof pconfig.context == 'object'){
				dconsole.reload(pconfig.context);
			}
		};
	}
	if(fcb){
		xhr.fail = fcb;
	}
	else {
		xhr.fail = function(jqXHR, textStatus, errorThrown){
			if(jqXHR.responseText && jqXHR.responseText.length > 0){
				try{
					jsonerror = JSON.parse(jqXHR.responseText);
					var ldr = new LDResult(jsonerror, pconfig);
					ldr.show();
				}
				catch(e){
					dconsole.writeResultMessage("error", "Failed to parse server error message", e.message, jqXHR.responseText);
				};
			}
			else {
				dconsole.writeResultMessage("error", "Server response indicates failure", textStatus);
			}
		};	
	}
	return xhr;
}

dconsole.getXHRTemplate = function(pconfig, success_callback){
	var xhr = {};
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.done = function(response, textStatus, jqXHR) {
		try {
			var json = (typeof response == "object") ? response : JSON.parse(response);
		}
		catch(e){
			return dconsole.writeResultMessage("error", "Failed to parse server response", e.message, pconfig, jqXHR.responseText);
		}
		success_callback(json);
	};
	xhr.fail = function(jqXHR, textStatus, errorThrown){
		if(jqXHR.responseText && jqXHR.responseText.length > 0){
			try{
				jsonerror = JSON.parse(jqXHR.responseText);
			}
			catch(e){
				return dconsole.writeResultMessage("error", "Failed to parse server error message", e.message, pconfig, jqXHR.responseText);
			};
			var ldr = new LDResult(jsonerror, pconfig);
			ldr.show();
	
		}
		else {
			dconsole.writeResultMessage("error", "Server response indicates failure", textStatus, pconfig);
		}
	};
	xhr.beforeSend = function(){
		dconsole.setBusy(pconfig);
	};
	xhr.always = dconsole.notBusy;
	return xhr;
}

dconsole.getFrameXHRTemplate = function(pconfig, success_callback){
	var new_callback = function(res){
		if(res.status == "accept" && typeof res.result != "undefined"){				
			var frame = (typeof res.result == "object") ? res.result : JSON.parse(res.result);
			dconsole.current_frame = frame;
			if(typeof success_callback == "function"){
				success_callback(frame);
			}
		}
		else {
			dconsole.writeResultMessage("error", "Frame response malformed", "No frame found in result field", pconfig, res);	
		}
	};
	var xhr = this.getXHRTemplate(pconfig, new_callback);
	xhr.beforeSend = function(){};
	return xhr;
}

dconsole.dispatchXHR = function(xhr){
	var done = xhr.done;
	var always = xhr.always;
	var fail  = xhr.fail;
	delete(xhr.done);
	delete(xhr.always);
	delete(xhr.fail);
	return $.ajax(xhr).done(done).fail(fail).always(always);	
}

dconsole.writeResultMessage = function(type, title, msg, extra, opts){
	var jqueryid = (dconsole.mode == "menu") ? "#dacura-console-menu-message" : "#dacura-console-extra-message";
	dacura.system.writeResultMessage(type, title, jqueryid, msg, extra, opts);
}

dconsole.clearResultMessages = function(){
	var jqueryid = (dconsole.mode == "menu") ? "#dacura-console-menu-message" : "#dacura-console-extra-message";
	$(jqueryid).html("");
}

dconsole.getMenuBusyHTML = function(bconf){
	return "busy doing stuff";
}

dconsole.setBusy = function(bconf){
	if(dconsole.mode == "menu"){
		jQuery('#dacura-console-menu-message').html(dconsole.getMenuBusyHTML(bconf));
	}
	else {
		jQuery('#dacura-console-extra-message').html(dconsole.getMenuBusyHTML(bconf));	
	}
}

dconsole.notBusy = function(){
	if(dconsole.mode == "menu"){
		jQuery('#dacura-console-menu-message').html("");
	}
	else {
		//jQuery('#dacura-console-extra-message').html("");	
	}
}

/* wrappers around API for particular functions */
 
function first(obj) {
    for (var a in obj) return a;
} 

dconsole.submitUpdatedProperty = function(rdf, test){
	var pid = first(rdf);
	var pconfig = { "resultbox": "#dacura-console-extra-message", "busybox": "#dacura-console"};
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(!onturl.length){
		this.writeResultMessage("error", "No ontology loaded", "Attempt to add property to unknown ontology");
	}
	else {
		if(test){
			var options = dacura.params.test_update_ontology_options;
		}
		else {
			var options = dacura.params.update_ontology_options;			
			pconfig.context = {mode: "model", ontology: onturl, property: pid};
		}
		dconsole.updateOntology(onturl, rdf, false, options, pconfig, test);
	}
}

dconsole.submitNewProperty = function(rdf, test){
	var pid = first(rdf);
	var pconfig = { "resultbox": "#dacura-console-extra-message", "busybox": "#dacura-console"};
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(!onturl.length){
		this.writeResultMessage("error", "No ontology loaded", "Attempt to add property to unknown ontology");
	}
	else {
		if(test){
			var options = dacura.params.test_update_ontology_options;
		}
		else {
			var options = dacura.params.update_ontology_options;			
			pconfig.context = {mode: "model", ontology: onturl, property: pid};
		}
		dconsole.updateOntology(onturl, rdf, false, options, pconfig, test);
	}
}

dconsole.deleteModelProperty = function(prop){
	var rdf = {};
	rdf[prop] = {};
	var pconfig = { "resultbox": "#dacura-console-extra-message", "busybox": "#dacura-console"};
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(!onturl.length){
		this.writeResultMessage("error", "No ontology loaded", "Attempt to add property to unknown ontology");
	}
	else {
		var options = dacura.params.update_ontology_options;			
		pconfig.context = {mode: "model", ontology: onturl};
		dconsole.updateOntology(onturl, rdf, false, options, pconfig);
	}
}


dconsole.submitNewClass = function(rdf, test){
	var cid = first(rdf);
	var pconfig = { "resultbox": "#dacura-console-extra-message", "busybox": "#dacura-console"};
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(!onturl.length){
		this.writeResultMessage("error", "No ontology loaded", "Attempt to add property to unknown ontology");
	}
	else {
		if(test){
			var options = dacura.params.test_update_ontology_options;
		}
		else {
			var options = dacura.params.update_ontology_options;			
			pconfig.context = {mode: "model", ontology: onturl, "class": cid};
		}
		dconsole.updateOntology(onturl, rdf, false, options, pconfig, test);
	}
}

dconsole.deleteClass = function(cls){
	var rdf = {};
	rdf[cls] = {};
	var pconfig = { "resultbox": "#dacura-console-extra-message", "busybox": "#dacura-console"};
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(!onturl.length){
		this.writeResultMessage("error", "No ontology loaded", "Attempt to add property to unknown ontology");
	}
	else {
		var options = dacura.params.update_ontology_options;			
		pconfig.context = {mode: "model", ontology: onturl};
		dconsole.updateOntology(onturl, rdf, false, options, pconfig);
	}
}

dconsole.submitUpdatedClass = function (rdf, test){
	var cid = first(rdf);
	var pconfig = { "resultbox": "#dacura-console-extra-message", "busybox": "#dacura-console"};
	var onturl = jQuery('#dacura-console .console-context select.console-ontology').val();
	if(!onturl.length){
		this.writeResultMessage("error", "No ontology loaded", "Attempt to add property to unknown ontology");
	}
	else {
		if(test){
			var options = dacura.params.test_update_ontology_options;
		}
		else {
			var options = dacura.params.update_ontology_options;			
			pconfig.context = {mode: "model", ontology: onturl, "class": cid};
		}
		dconsole.updateOntology(onturl, rdf, false, options, pconfig, test);
	}
}
 

/* Candidate API */

dconsole.createCandidate = function(ctype, cid, props, meta, pconfig, test, callback, failcallback){
	var ldcreate = {
		format: "json",
		ldtype: "candidate",
		contents: {},
		options: dacura.params.create_options
	};
	if(props){
		ldcreate.contents = props;
	}
	if(meta){
		ldcreate.meta = meta;
	}
	ldcreate.contents['rdf:type'] = ctype; 
	if(test){
		ldcreate.test = 1;
		ldcreate.options = dacura.params.test_create_options;
	}
	else {
		pconfig.context = {mode: "data", type: ctype, entity: cid};
	}
	if(cid){
		ldcreate[dacura.params.demand_id_token] = cid;
	}
	var xhr = this.getXHRUpdateTemplate(ldcreate, pconfig, callback, failcallback);
	xhr.url = dacura.params.apiurl + "candidate";
	return this.dispatchXHR(xhr);
}

dconsole.updateCandidate = function(cid, props, meta, pconfig, test, callback, failcallback){
	var ldupdate = {
		format: "json",
		ldtype: "candidate",
		options: dacura.params.update_options
	};
	if(props){
		ldupdate.contents = props;
	}
	if(test){
		ldupdate.test = 1;
		ldupdate.options = dacura.params.test_update_options;
	}
	else {
		pconfig.context = {mode: "data", type: etype, entity: cid};
	}
	var etype = jQuery('#dacura-console .console-context select.console-entity-type').val();
	var xhr = this.getXHRUpdateTemplate(ldupdate, pconfig, callback, failcallback);
	xhr.url = dacura.params.apiurl + "candidate/" + cid;
	return this.dispatchXHR(xhr);	
}


dconsole.getEmptyFrame = function(cls, pconfig, callback){
	var xhr = this.getFrameXHRTemplate(pconfig, callback);
	xhr.url = dacura.params.apiurl + "candidate/frame";
	xhr.type = "POST";
	xhr.data = {"class": cls};
	return this.dispatchXHR(xhr);	
};

dconsole.getFilledFrame = function(id, pconfig, callback){
	var xhr = this.getFrameXHRTemplate(pconfig, callback);
	xhr.url = dacura.params.apiurl + "candidate/frame/" + id;
	return this.dispatchXHR(xhr);	
};

dconsole.getFilledPropertyFrame = function(entid, propid, pconfig, callback){
	var xhr = this.getFrameXHRTemplate(pconfig, callback);
	xhr.type = "POST";
	xhr.url = dacura.params.apiurl + "candidate/propertyframe/" + entid;
	xhr.data = { "property": propid};
	return this.dispatchXHR(xhr);	
};

dconsole.getEmptyPropertyFrame = function(cls, propid, pconfig, callback){
	var xhr = this.getFrameXHRTemplate(pconfig, callback);
	xhr.url = dacura.params.apiurl + "candidate/propertyframe";
	xhr.type = "POST";
	xhr.data = {"class": cls, "property": propid};
	return this.dispatchXHR(xhr);	
};

dconsole.getEntityClasses = function(pconfig, callback){
	var xhr = this.getXHRTemplate(pconfig, callback);
	xhr.url = dacura.params.apiurl + "candidate/entities";
	return this.dispatchXHR(xhr);	
};

/* ontology / graph api - note we don't support creating or deleting graphs and ontologies in the tool for now */ 


dconsole.loadOntology = function(url, pconfig, args, callback, failcallback){
	var newcallback = function(json){
		var ont = new dOntology(json, dacura.params.ontology_config);
		dconsole.current_ontology = ont;
		dconsole.loaded_ontologies[ont.id] = ont;
		if(typeof callback == "function"){
			callback(ont);
		}		
	}
	var xhr = this.getXHRTemplate(pconfig, newcallback);
	xhr.url = url;
	xhr.data = args;
	return this.dispatchXHR(xhr);	
}

dconsole.updateOntology = function(onturl, rdf, meta, options, pconfig, test, callback, failcallback){
	var ldupdate = {
		format: "json",
		editmode: "update",
		ldtype: "ontology",
		options: options
	};
	if(test){
		ldupdate.test = 1;
	}
	if(meta){
		ldupdate.meta = meta;
	}	
	if(rdf){
		ldupdate.contents = rdf;	
	}
	var ontid = lastURLBit(onturl);
	var ncallback = function(response){
		try {
			var cx = (typeof response == "object") ? response : JSON.parse(response);
		}
		catch(e){
			dconsole.writeResultMessage("error", "Failed to parse server response", e.message, jqXHR.responseText);
		}
		var ldr = new LDResult(cx, pconfig);
		ldr.show();
		if(ldr.status == "accept" && !ldr.test){
			if(typeof pconfig.context == 'object'){
				dconsole.reload(pconfig.context);
			}
			var cfunc = function(){
				//alert(gurl);
				i--;
			};
			var gtore = dconsole.getGraphsToRedeploy(ontid);
			var i = size(gtore);
			for(var gurl in gtore){
				dconsole.updateGraph(gurl, gtore[gurl], false, dacura.params.deploy_options, pconfig, false, cfunc, failcallback);
			}		
			if(typeof(callback) == "function") {
				callback(ldr);
			}	
		}		
	}
	var xhr = this.getXHRUpdateTemplate(ldupdate, pconfig, ncallback, failcallback);
	xhr.url = onturl;
	return this.dispatchXHR(xhr);	
}

dconsole.getGraphsToRedeploy = function(ontid){
	var graphs = {};
	if(typeof dacura.params.collection_contents.graphs == "object"){
		for(var gid in dacura.params.collection_contents.graphs){
			if(typeof dacura.params.collection_contents.graphs[gid].imports[ontid] == "object" && 
					typeof dacura.params.collection_contents.graphs[gid].deploy == "object" ){
				graphs[dacura.params.collection_contents.graphs[gid].url] = dacura.params.collection_contents.graphs[gid].deploy;
			}
		} 
	}
	return graphs;	
}

dconsole.loadGraph = function(url, pconfig, args, callback, failcallback){
	var newcallback = function(json){
		var graph = new dGraph(json);
		dconsole.current_graph = graph;
		dconsole.loaded_graphs[graph.id] = graph;
		if(typeof callback == "function"){
			callback(graph);
		}		
	}
	var xhr = this.getXHRTemplate(pconfig, newcallback);
	xhr.url = url;
	xhr.data = args;
	return this.dispatchXHR(xhr);	
}

dconsole.updateGraph = function(gurl, rdf, meta, options, pconfig, test, callback, failcallback){
	var ldupdate = {
		format: "json",
		editmode: "update",
		ldtype: "graph",
		options: options
	};	
	if(test){
		ldupdate.test = 1;
	}
	if(meta){
		ldupdate.meta = meta;
	}	
	if(rdf){
		ldupdate.contents = rdf;	
	}
	var xhr = this.getXHRUpdateTemplate(ldupdate, pconfig, callback, failcallback);
	xhr.url = gurl;
	return this.dispatchXHR(xhr);	
}

dconsole.scanPage = function(html, context){
	if(!html || !html.length){
		return;
	}
	var complete= function(results){
		//jpr(results);
		//dconsole.loadExtra(dacura.pageScanner.getScanSummaryHTML());
	}
	var upd = function(upd, callback){
		if(upd.cid){
			dconsole.updateCandidate(upd.cid, upd.contents, false, this.menu_pconfig, true, callback);			
		}
		else {
			dconsole.createCandidate(upd.ctype, false, upd.contents, false, this.menu_pconfig, true, callback);
		}		
	}
	var sconfig = dacura.params.collection_contents.scanner_config;
	sconfig.load_callback = function(x){
		jpr(x);
	}
	dacura.pageScanner.init(html, context, sconfig, this.ontologyMode);
	dacura.pageScanner.scan(sconfig.connectors, sconfig.locators, upd, complete);
}

/* contacts the server and resets various values in response to context switches or state changes */
dconsole.reload = function(context){
	xhr = {};
	xhr.url = dacura.params.apiurl + "console/reload";
	xhr.xhrFields = {
	    withCredentials: true
	};
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		jQuery('body').append(response);
		//dconsole.mode = "menu";
		dconsole.clear();
		var mode = (typeof context == "object" && typeof context.mode == "string" ? context.mode : "data");
		dconsole.showUserOptions(mode);
		if(typeof context == "object"){
			if(typeof context.ontology == "string"){
				delete(dconsole.loaded_ontologies[dconsole.current_ontology.id]);
				delete(dconsole.current_ontology);
			}
			if(context.mode == "model"){
				dconsole.ontologyMode = true;
			}
			dconsole.setContext(context);
		}
	})
	.fail(function(response){
		alert("Failed to reload console from " + xhr.url);
	});	
}
