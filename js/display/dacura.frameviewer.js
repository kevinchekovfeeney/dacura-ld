/**
 * Utility functions to set up API connection
 */
dacura.frame = {};
dacura.frame.api = {};
dacura.frame.apiurl = dacura.system.apiURL();

dacura.frame.api.getFrame = function (cls) {
    var xhr = {};
    xhr.type = "POST";
    xhr.url = dacura.frame.apiurl + "/frame";
    xhr.data = {'class': cls};
    xhr.dataType = "json";
    return xhr;
};

dacura.frame.api.getFilledFrame = function (id) {
    var xhr = {};
    xhr.type = "GET";
    xhr.url = dacura.frame.apiurl + "/frame/" + id;
    xhr.dataType = "json";
    return xhr;
};

/**
 * The Dacura Frame Viewer Class
 * 
 * Translates frame logic into a user-interface. 
 */
function FrameViewer(entities, entity_classes, fvconfig, typeMap) {	
    this.target = ((fvconfig && fvconfig.target) ? fvconfig.target : false);
    this.frames = {};
    this.mode = "view";
    this.testCallback = false;
    this.archetypes = false;
    this.entities = (entities ? entities : {});
    this.entity_classes = (entity_classes ? entity_classes : []); // class => {label: lab, children: [subsumed_classes]}
    this.deletedObjects = [];//ids of internal objects that have been deleted
    this.tconfig = ((fvconfig && fvconfig.types_config) ? fvconfig.types_config : false);
    this.viewconfig = false;
    this.annotationPredicate = "http://dacura.scss.tcd.ie/ontology/dacura#annotates";    
    this.setViewerConfig((fvconfig ? fvconfig.viewer_config : false));
    if(this.target){	
    	jQuery('#' + this.target).html("");
    }
    this.tooltip_config = { 
    		content: function () {
    			return jQuery(this).prop('title');
    		},
    		show: {delay: 500}
    }
    this.typeMap = (typeMap ? typeMap : {});
}



/**
 * Determines whether a particular icon should be shown in a particular frame
 */
FrameViewer.prototype.showFeature = function(frame, action, mode, boxed, parent, seq){
	if(action == "idtag"){
		return false;
	}
	if(mode == 'create'){
		if(action == "add_value" && this.isSimpleDataFrame(frame)){
			return true;
		}
		if(action == "add_value" || action == "remove" || action == "remove_value"){
			return true;
		}
	}
	if(action == "help"){
		return true;
	}
	if(action == "type" && (mode == "edit" || mode == "create")){
		return this.getFrameTypeHelptext(frame, mode, boxed, parent, seq);
	}
	if(action == "annotate"){
		return true;
	}
	return false;
	if(fname == "remove_property"){
		var configvar = this.viewconfig[mode].property_composable;
		return (configvar == "full" || (isroot && configvar == "basic"));
	}
	else if(fname == "remove_value"){
		if(mode == 'edit' && frame && frame.type == 'datatypeProperty' && frame.mode == "edit"){
			return false;//can't remove basic datatype values without deleting them...
		}
		else if (mode == 'edit' && frame && frame.mode == "edit" && frame.type == 'objectProperty' && 
				frame.frame && (frame.frame.type == "oneOf" || frame.frame.type == "entity")){
			return false;
		}
		var configvar = this.viewconfig[mode].value_composable;
		return (configvar == "full" || configvar == "value" || (isroot && configvar == "basic"));
	}
	else if(fname == "add_datatype"){
		if(mode == "view") return false;
		var configvar = this.viewconfig[mode].value_composable;
		return (configvar == "full" || configvar == "add" || configvar == "value" || (isroot && configvar == "basic"));		
	}
	else if(fname == "add_object"){
		if(mode == "view") return false;
		var configvar = this.viewconfig[mode].value_composable;
		return (configvar == "full" || configvar == "add" || configvar == "value" || (isroot && configvar == "basic"));		
	}
	else if(fname == "help"){
		var configvar = this.viewconfig[mode].help;
		return (configvar == "full" || (isroot && configvar == "basic"));		
	}
	else if(fname == "datatype"){
		var configvar = this.viewconfig[mode].datatype;
		return (configvar == "full" || (isroot && configvar == "basic"));		
	}
	else if(fname == "annotate"){
		if(!this.viewconfig['show_annotations'] || !this.multigraph) return false;
		var configvar = this.viewconfig[mode].annotate ;
		return (configvar == "full" || (isroot && configvar == "basic"));		
	}
	else if(fname == "edit"){
		if(mode != "view") return false;
		var configvar = this.viewconfig["view"].editable;
		return (configvar == "full" || (isroot && configvar == "basic"));		
	}
	else if(fname == "delete"){
		if(mode == "create" || (frame && frame.mode && frame.mode == "create")) return false;		
		var configvar = this.viewconfig[mode].editable;
		return (configvar == "full" || (isroot && configvar == "basic"));		
		//return (configvar == "full" || (isroot && configvar == "basic"));		
	}
	else {
		alert(fname);
	}
}

/**
 * Sets up the configuration of the viewer - defines the features we want to show
 */
FrameViewer.prototype.setViewerConfig = function(vconf){
	if(!this.viewconfig){
		this.viewconfig = {
	    	view: {
	    		property_composable: false,//false | basic | full
	    		value_composable: false,//false | basic |  full
	    		help: "basic",//false | basic | full
	    		datatype: false,//full basic false
	    		annotate: "full",//full basic false
	    		editable: false//full basic false
	    	},
	    	edit: {
	    		property_composable: "full",//false | basic | full
	    		value_composable: "full",//false | basic |  full
	    		help: 'full',
	        	datatype: "full",
	    		annotate: "full",//full basic false
	    		editable: "full"//full basic false
	        },
	        create: {
	    		property_composable: "add",//false | basic | full
	    		value_composable: "add",//false | basic |  full
	        	help: "full",
	            datatype: "full",
	    		annotate: "full",//full basic false
	    		editable: "full"//full basic false
	        },
	        show_annotations: true,
	        show_entity_annotations: false
	    };//false | basic | value | full
	}
	if(vconf){
    	if(vconf.view){
	    	for(var k in vconf.view){
	    		this.viewconfig.view[k] = vconf.view[k];
	    	}
    	}
    	if(vconf.edit){
	    	for(var k in vconf.edit){
	    		this.viewconfig.edit[k] = vconf.edit[k];
	    	}
    	}
    	if(vconf.create){
	    	for(var k in vconf.create){
	    		this.viewconfig.create[k] = vconf.create[k];
	    	}
    	}
    	if(typeof vconf.show_annotations != "undefined"){
    		this.viewconfig.show_annotations = vconf.show_annotations;
    	}
    	if(typeof vconf.show_entity_annotations != "undefined"){
    		this.viewconfig.show_entity_annotations = vconf.show_entity_annotations;
    	}
    	if(typeof vconf.tool_t != "undefined"){
    		this.viewconfig.show_annotations = vconf.show_annotations;
    	}
    }
}




FrameViewer.prototype.getFramesComment = function(framelist) {
	var commentp = "http://www.w3.org/2000/01/rdf-schema#comment";
	for(var i =0; i<framelist.length; i++){
		if(this.getFrameCategory(framelist[i]) == "object"){
			var s = this.getFrameSubjectID(framelist[i].frame);
			var anots = this.getAnnotationsForNode(s);
			if(anots && size(anots)){
				for(var gurl in anots){
					for(var objid in anots[gurl]){
						if(typeof anots[gurl][objid][commentp] == "object" ){
							return anots[gurl][objid][commentp][0].rangeValue.data;
						}
					}
				}
			}
			var next = framelist[i].frame;
			for(var key in next){
				var nx = this.getFramesComment(next[key]);
				if(nx && nx.length){
					return nx;
				}
			}

		}
	}
	return false;
}


/**
 * Returns an array of properties that are currently displayed in the viewer
 */
FrameViewer.prototype.getDisplayedProperties = function(gid, frames){
	var frametest = (frames ? frames : this.frames);
	if(this.multigraph){
		gid = (gid ? gid : this.mainGraph)
		frametest = frametest[gid];
	}
	if(size(frametest) > 0){
		return Object.keys(frametest);
	}
	return [];
}

/**
 * Returns an array of properties that are currently displayed in the viewer
 */
FrameViewer.prototype.isDisplayedProperty = function(prop, gid){
	var dprops = this.getDisplayedProperties(gid);
	if(dprops.indexOf(prop) !== -1){ return true; }
	return false;
}


/**
 * Returns an array of properties that the entity has which are not currently displayed in the viewer
 */
FrameViewer.prototype.getUndisplayedPropertyList = function(gid, frames, archetypes){
	if(!this.multigraph){
		archetypes = (archetypes ? archetypes : this.archetypes);
		frames = (frames ? frames : this.frames );
		var props = [];
		for(var kk in archetypes){
			if(typeof frames[kk] == "undefined"){
				props.push(kk);
			}
		}
		return props;
	}
	else {
		gid = (gid ? gid : this.mainGraph);
		archetypes = (archetypes ? archetypes : this.archetypes[gid]);
		frames = (frames ? frames : this.frames[gid]);
		var props = [];
		for(var kk in archetypes){
			if(typeof frames[kk] == "undefined"){
				props.push(kk);
			}
		}
		return props;
	}		
}




/**
 * Returns the list of undisplayed properties for a particular domain value (subject id)
 */
FrameViewer.prototype.getMissingProperties = function(frames, framestruct, dv){
	var undisp = [];	
	for(var i = 0; i<frames.length; i++){
		var prop = frames[i]['property'];
		if(typeof framestruct != "object" || typeof framestruct[prop] == "undefined"){
			if(dv) frames[i].domainValue = dv;
			undisp.push(frames[i]);
		}
	}
	return undisp;
}
/*
 * DOM / HTML
 */


/**
 *  draws the passed frame array or the internal frame array if not passed 
 */
FrameViewer.prototype.draw = function(frames, mode, target, suppress_entity_annotations){
	target = (target ? target : this.target);
	var container = document.getElementById(target);
	if(!container){
		return alert("no dom node with id " + target + " exists ");
	}
	var first = (size(this.frames) == 0);
    this.mode = ( mode ? mode : (this.mode ? this.mode : "view"));
	mode = (mode ? mode : this.mode);
	if(frames){
	    var iframes = this.addFrames(frames, mode);	    	
	}
	else {
	    var iframes = this.frames;
	}
	if(first && !suppress_entity_annotations) {
		container.appendChild(this.getEntityHeader(mode, container));
	}
	
	var pappy = this.getFrameEntityID();
    if(typeof frames == "object" && frames.length){
    	if(this.multigraph){
        	this.addPropertyFramesToDOM(iframes[this.mainGraph], mode, container);
    	}
    	else {
    		//alert("adding properties to single graph");
       		this.addPropertyFramesToDOM(iframes, mode, container);
    	}
		this.getEntityAnnotationFramesAsDOM(container, mode, iframes, pappy);
    }
    else if(!frames && !this.multigraph){
		this.getEntityAnnotationFramesAsDOM(container, mode, iframes, pappy);
    	this.addPropertyFramesToDOM(iframes, mode, container);    	
    }
    else if(typeof iframes == "object" && size(iframes) && !iframes.length){
    	if(!this.multigraph){
    		alert("help not a multigraph");
    		jpr(iframes);
    	}
		this.getEntityAnnotationFramesAsDOM(container, mode, iframes, pappy);
    	if(typeof iframes[this.mainGraph] != "undefined"){
        	this.addPropertyFramesToDOM(iframes[this.mainGraph], mode, container);
    	}
    }
    fixGoogleMaps();
};


function addChildNodes(doma, domb){
	if(!isElement(doma) || !isElement(domb)){
		return alert("attempt to add child nodes to not a dom element");
	}
	while(domb.childNodes.length){
		doma.appendChild(domb.childNodes[0]);
	}
}

FrameViewer.prototype.getFrameValuesAsRows = function(framelist, fullframes, qualifiers){
	var vals = document.createElement("span");
	vals.setAttribute("class", "dacura-frame-values");
	for(var i =0; i<framelist.length; i++){
		var oneval = document.createElement("span");
		oneval.setAttribute("class", "dacura-frame-value");
		var fcat = this.getFrameCategory(framelist[i]);
		this.attachCustomRenderer(framelist[i]);
		if(disp = this.hasCustomDisplay(framelist[i], "view")){
			oneval.appendChild(disp.display(framelist[i], "view"));
			if(qualifiers){
				addChildNodes(oneval, qualifiers);
			}
			vals.appendChild(oneval);
		}
		else if(this.isSimpleDataFrame(framelist[i], fcat)){
			var entry = this.getSimpleDatatypeValueAsDOM(fcat, framelist[i], "view", framelist, i);
			oneval.appendChild(entry)
			if(qualifiers){
				addChildNodes(oneval, qualifiers);
			}
			vals.appendChild(oneval);
		}
		else if(fcat == "object"){
			var s = this.getFrameSubjectID(framelist[i].frame);
			var anots = this.getAnnotationsForNode(s, fullframes);
			if(anots && size(anots)){
				fqualifiers = this.getFrameQualifiers(anots);
				if(!qualifiers){
					qualifiers = fqualifiers;
				}
				else {
					addChildNodes(qualifiers, fqualifiers);
				}
			}
			var next = framelist[i].frame;
			var k = 0;
			for(var key in next){
				oneval.appendChild(this.getFrameValuesAsRows(next[key], fullframes, qualifiers));
				vals.appendChild(oneval);
				//addChildNodes(vals, qualifiers);
			}

		}
	}
	return vals;
}


FrameViewer.prototype.getFrameQualifiers = function(anots, deep){
	var qspan = document.createElement("span");
	for(var gurl in anots){
		for(var objid in anots[gurl]){
			var anot = new DacuraAnnotation();
			anot.loadFromFrames(anots[gurl][objid], this);
			if(anot.hasQualifier()){
				qspan.appendChild(anot.getQualifierIcons(this));
			}
		}
	}
	return qspan;
}


FrameViewer.prototype.getEntityAnnotationFramesAsDOM = function(container, mode, iframes, pappy){
	if(this.multigraph){
		var anotframes = this.getFrameAnnotationObjects(iframes, pappy);
		if(anotframes && size(anotframes)){
			var msg = this.getAnnotationsSummaryLabel(anotframes, mode);
			var hdr = this.getAnnotationHeaderDOM(msg, "view all", false);
			hdr.setAttribute("id", this.target + "-annotations");
			container.appendChild(hdr);
			this.addAnnotationObjectFrames(container, anotframes, mode);    		    			
		}
	}
	else {
		//var x = this.getObjectHeaderDOM();
		//container.appendChild(x);
	}
}

FrameViewer.prototype.getObjectHeaderDOM = function(frame, mode, boxed){
	var hdr = document.createElement("span");
	if(!frame || this.isBoxType(frame)){
		//var props = this.getUndisplayedPrope
		//overall entity header
		//get annotation classes
		//get provenance classes
	}
	else if(!frame.archetype){
		jpr(frame);

	}
	else if(mode == "edit") {
		var sel = document.createElement("select");
		var opt = document.createElement("option");
		opt.value = "";
		opt.text = "Add Property";
		sel.appendChild(opt);
		var undisp = this.getUndisplayedPropertyList(false, frame.frame, frame.archetype.frame);
		for(var i = 0; i<undisp.length; i++){
			opt = document.createElement("option");
			opt.value = undisp[i];
			opt.text = this.getPropertyLabel(frame.archetype.frame, undisp[i]);
			sel.appendChild(opt);
		}
		if(undisp.length){
			hdr.appendChild(sel);	
		}
	}
	//var hr = document.createElement("hr");
	//hdr.appendChild(hr);
	return hdr;
}

FrameViewer.prototype.getEntityHeader = function(mode, container){
	//show all the annotations...
	var self = this;
	var hdr = document.createElement("span");
	var undisp = this.getUndisplayedPropertyList();
	if(undisp.length){
		var sel = document.createElement("select");
		var opt = document.createElement("option");
		opt.value = "";
		opt.text = "Add Property";
		sel.appendChild(opt);
		for(var i = 0; i<undisp.length; i++){
			opt = document.createElement("option");
			opt.value = undisp[i];
			opt.text = this.getPropertyLabel(this.archetypes, undisp[i]);
			sel.appendChild(opt);
		}
		sel.addEventListener("change", function(){
		    if(this.value != ""){
		    	var dframe = self.createFrameFromArchetype(self.archetypes[this.value][0]);
		    	self.draw([dframe], mode, container);
				self.refreshCardinalityControls();
		    }
		});
		hdr.appendChild(sel);
	}
	if(this.multigraph){
		//show the entity annotations...
		//alert("show annotations header");
	}
	var self = this;
	this.refreshEntityHeaderControls = function(){
		var n = hdr;
		while (n.firstChild) {
			n.removeChild(n.firstChild);
	    }
		n.appendChild(self.getEntityHeader());
	}

	return hdr;
	//var anots = this
	//if(this.getAnnotationClasses())
}




/**
 * Draws the currently undisplayed properties
 */
FrameViewer.prototype.drawUndisplayed = function(frames, mode){
	if(this.multigraph){
		undisp = {};
		var subjid = this.frames[this.mainGraph][firstKey(this.frames[this.mainGraph])][0].domainValue;
		for(var gurl in frames){
			var arches = this.frames[gurl];
			if(typeof arches == "undefined"){
				arches = {};
			}
			var fu = this.getMissingProperties(frames[gurl], arches, subjid);
			if(fu && fu.length){
				undisp[gurl] = fu;						
			}	
		}
		if(undisp && size(undisp)){
			var dframes = this.addFrames(undisp, mode);
			if(dframes && size(dframes)){
				this.draw(dframes);
			}
		}
	}
	else {
		var fu = this.getMissingProperties(frames, this.frames);
		if(fu && fu.length){
			var dframes = this.addFrames(fu, mode);					
			if(dframes && size(dframes)){
				this.draw(dframes);
			}
		}		
	}
}

/**
 * Removes frames that have been drawn onto divs that are not the principal target of the frameviewer
 */
FrameViewer.prototype.undrawDispersedFrames = function(){
	if(this.multigraph){
    	for(var gurl in this.frames){
    		if(this.viewconfig.show_annotations || gurl == this.mainGraph){
            	this.undrawFrames(this.frames[gurl]);    		    			
    		}
    	}
    }
	else {
    	this.undrawFrames(this.frames);    		    					
	}
}

/**
 * Removes all displayed properties from the target
 */
FrameViewer.prototype.undrawFrames = function(frameslist){
	for(var k in frameslist){
		for(var i = 0; i<frameslist.length; i++){
			var nframe = frameslist[i];
			if(nframe.valueDiv){
				nframe.valueDiv.parentNode.removeChild(nframe.valueDiv);
			}
		}
	}
}

/**
 * Changes the mode of the viewer to nmode
 */
FrameViewer.prototype.resetMode = function(nmode){
	if(nmode == "view"){
		this.removeCreateFrames();
	}
	this.redraw(false, nmode);
}

/**
 * Redraws all currently displayed frames
 */
FrameViewer.prototype.redraw = function(config, mode, target){
	this.undraw(target);
	if(config){
		this.setViewerConfig(config);
	}
    mode = (mode ? mode : this.mode);
	this.draw(false, mode, target);
}

/**
 * Does any tidy-up that is needed when frameviewer is shut down
 */
FrameViewer.prototype.destroy = function(frames){
	frames = (frames ? frames: this.frames);
	for (var i = 0; i < frames.length; i++) {
        var elt = frames[i];
        if(custom = this.hasCustomDisplay(elt.range, "view")){
        	if(typeof custom.destroy == 'function'){
        		custom.destroy();
        	}
        }
    	if(elt.frame && !isEmpty(elt.frame)){
    		this.destroy(elt.frame);
    	}
    }
    this.frames = false;
    this.archetypes = false;
    this.cls = false;
	this.target = false;
	this.pconfig = false;
	this.mapconfig = false;
    jQuery('#' + this.target).html("");
}


/**
 * Adds a new frame of a given property to the frameviewer
 */
FrameViewer.prototype.addProperty = function(mode, frame, frames, parent, boxed, cardControls){
	parent = (parent ? parent : false);
	if(mode == 'create'){
		if(!frame.archetype){
			alert("attempt to create frame without archetype");jpr(frame);
			return false;
		}
		var nframe = this.createFrameFromArchetype(frame.archetype);
		if(!nframe){
			alert("Failed to create frame from archetype");
			return false;
		}
		if(frame.domainValue) {
			nframe.domainValue = frame.domainValue;
		}
	}
	else {
		nframe = jQuery.extend(true, {}, frame);
	}
	if(!cardControls && frame.cardControls){
		cardControls = frame.cardControls;
	}
	nframe.mode = mode;
	if(cardControls.DOMTarget){
		var ndom = this.getFrameAsDOM(nframe, mode, parent, parent.length -1, boxed, cardControls);
		cardControls.DOMTarget.appendChild(ndom);
		fixGoogleMaps();
	}
	else {
		alert("not here");
	}
	frames.push(nframe);
	return nframe;
}


FrameViewer.prototype.refreshCardinalityControls = function(frames){
	if(!frames){
		this.refreshEntityHeaderControls();
		if(this.multigraph){
			frames = this.frames[this.mainGraph];
			//for(var gurl in this.frames){
			//	this.hideCardinalityControls(this.frames[gurl]);
				//this.addCardinalityControls(this.frames[gurl]);
			//}
		}
		else {
			frames = this.frames;
		}
	}
	this.hideCardinalityControls(frames);
	this.applyCardinalityConstraintsToFrames(frames);
	this.addCardinalityControls(frames);
}

FrameViewer.prototype.hideCardinalityControls = function(frames){
	for(var prop in frames){
		for(var i = 0; i<frames[prop].length; i++){
			if(frames[prop][i].cardControls){
				this.hideFrameCardinalityControls(frames[prop][i]);
			}	
			if(this.getFrameCategory(frames[prop][i]) == "object"){
				this.hideCardinalityControls(frames[prop][i].frame);
			}
		}
	}
}

FrameViewer.prototype.hideFrameCardinalityControls = function(frame){
	if(!frame.cardControls){
		alert("No cardinality control associated with frame: " + frame.property);
		jpr(frame);
		return;
	}
	if(frame.cardControls.addContainer){
		for(var i = 0; i<frame.cardControls.addContainer.childNodes.length; i++){
			frame.cardControls.addContainer.removeChild(frame.cardControls.addContainer.childNodes[i]);
		}
	}
	if(frame.cardControls.removeValueContainer){
		for(var i = 0; i<frame.cardControls.removeValueContainer.childNodes.length; i++){
			frame.cardControls.removeValueContainer.removeChild(frame.cardControls.removeValueContainer.childNodes[i]);
		}
	}
	if(frame.cardControls.removeContainer){
		for(var i = 0; i<frame.cardControls.removeContainer.childNodes.length; i++){
			frame.cardControls.removeContainer.removeChild(frame.cardControls.removeContainer.childNodes[i]);
		}			
	}
}


FrameViewer.prototype.addCardinalityControls = function(frames, parent, restrictions){
	for(var prop in frames){
		for(var i = 0; i<frames[prop].length; i++){
			if(frames[prop][i].cardControls){
				if(typeof frames[prop][i] != "object"){
					alert("not object x");
					jpr(fframe)
				}

				this.showFrameCardinalityControls(frames[prop][i], prop, i, frames, this.isBoxedType(frames), parent);
			}
			else {
				//alert("no cardinality controls for " + prop + i);
			}
			if(this.getFrameCategory(frames[prop][i]) == "object"){
				this.addCardinalityControls(frames[prop][i].frame, frames[prop][i], restrictions);
			}
		}
	}
}

FrameViewer.prototype.showFrameCardinalityControls = function(frame, prop, seq, parent, boxed, parentframe){
	var showRemove = (seq == 0 && !boxed && frame.cardControls.removeContainer && size(parent)> 1);
	var showAdd = (seq == 0  && frame.cardControls.addContainer);
	var showRemoveValue = (parent[prop].length > 1 && frame.cardControls.removeValueContainer);
	if(this.hasCardinalityConstraint(frame)){
		var min = this.hasMinCardinality(frame.restriction);
		if(min && parent[prop].length <= min){
			showRemoveValue = false;
			showRemove = false;
		}
		var max = this.hasMaxCardinality(frame.restriction);
		if(max && parent[prop].length >= max){
			showAdd = false;
		}
	}
	
	var fcat = this.getFrameCategory(frame);
	var txts = this.getActionHelpTexts(frame, prop, boxed, parentframe, seq);
	if(showRemove){
		attachRemoveIcon(frame.cardControls.removeContainer, txts.remove, 
			function(){frame.cardControls.removeProperty();}, this.tooltip_config);	
	}
	if(showAdd){
		attachAddIcon(frame.cardControls.addContainer, txts.add, 
			function(){frame.cardControls.addProperty();}, this.tooltip_config);		
	}
	if(showRemoveValue){
		attachRemoveIcon(frame.cardControls.removeValueContainer, txts.remove_value,
			function(){frame.cardControls.removePropertyValue(seq);},this.tooltip_config);			
	}
}

FrameViewer.prototype.getActionHelpTexts = function(frame, prop, boxed, parent, seq){
	var prop = this.getPropertyLabel(frame, prop);
	var txts = {
		remove: "Remove property " + prop + " from the viewer - this will not change any data",
		remove_value: "Remove value " + seq + " of " + prop + " from the viewer - this will not change any data"
	}
	thing = (boxed ? this.getFrameLabel(parent) : this.getFrameLabel(frame));
	txts.add = "<strong>" + thing + "</strong> can have multiple different values. <i>Click this button to add a new value</i>";
	return txts;
}

FrameViewer.prototype.getFrameTypeHelptext = function(frame, mode, boxed, parent, seq){
	if(boxed){
		return "";
	}
	if(this.isBoxType(frame) && typeof frame.frame[firstKey(frame.frame)][0] == "object"){
		var label = this.getFrameTypeLabel(frame.frame[firstKey(frame.frame)][0], true);
		//var label = this.getFrameTypeLabel(frame);
		var comment = this.getFrameTypeHelp(frame.frame[firstKey(frame.frame)][0], true);
	}
	else {
		var urlf = urlFragment(frame.range);
		var label = "<strong>" + urlf.charAt(0).toUpperCase() + urlf.slice(1) + "</strong> type";
		var comment = ""//frame.range;	
	}
	var all = "<span class='tooltip-type-label'>" + label + "</span>" + 
		"<div class='tooltip-type-helptext'>" + comment + "</div>";
	return all;
}

FrameViewer.prototype.getFrameTypeLabel = function(frame, box){
	return "<strong>" + this.getFrameLabel(frame) + "</strong>";
}
FrameViewer.prototype.getFrameTypeHelp = function(frame){
	var bcomment = false;
	if(typeof frame.comment == "object" && frame.comment.data){
		bcomment = " <span>" + frame.comment.data + "</span>";
	}
	var fcat = this.getFrameCategory(frame);
	if(fcat == "choice"){
		bcomment += this.getEnumeratedChoicesHelptext(frame.frame.elements, true);
	}
	bcomment = (bcomment ? bcomment : " <i>" + frame.range + "</i> ");
	return bcomment;
}



FrameViewer.prototype.attachCardControlsToNewFrame = function(cardControl, nf, ndom, target, parent){
	var ndom = (ndom ? ndom : false);
	var ncardControls = {
		addContainer:  document.createElement("span"),
		removeValueContainer: document.createElement("span"),
		removeContainer: document.createElement("span"),
		removeTarget: cardControl.removeTarget,
		addTarget: cardControl.addTarget,
		removeValueTarget: cardControl.removeValueTarget,

		addProperty: cardControl.addProperty,
		removeProperty: cardControl.removeProperty,
		removePropertyValue: function(i){
			cardControl.removePropertyValue(i, ndom);
		},
		removeParent: function(){
			cardControl.removePropertyValue();
		}
	}
	var cntlcss = urlFragment(nf.property);
	ncardControls.removeContainer.setAttribute("class", cntlcss + "_remove");
	ncardControls.addContainer.setAttribute("class", cntlcss + "_add");
	ncardControls.removeValueContainer.setAttribute("class", cntlcss + parent.length + "_remove_value");
	nf.cardControls = ncardControls;
	//ncardControls.addProperty();
}

FrameViewer.prototype.generateCardinalityControl = function(aframe, aframes, aparent, aboxed, domContext, asequence){
	var self = this;
	var frame = aframe;
	var frames = aframes;
	var parent = aparent;
	var boxed = aboxed;
	var contx = domContext.containerdiv;
	var propx = domContext.propertydiv;
	var propvalsdiv = (domContext.propvalsdiv ? domContext.propvalsdiv : false);
	var propdiv = (domContext.valdiv ? domContext.valdiv : false);
	var removeTarget = (domContext.removeTarget ? domContext.removeTarget : false);
	var addTarget = (domContext.addTarget ? domContext.addTarget : false);
	var removeValueTarget = (domContext.removeValueTarget ? domContext.removeValueTarget : false);
	var sequence = (asequence ? asequence: false);
	var cardControls = {
		removeProperty: function(){
			if(self.removeProperty(frame.property, parent, contx, propx) == 0){
				if(typeof cardControls.removeParent == "function"){
					cardControls.removeParent();
				}
				else {
					alert("no parent");
				}
			}
			//if(parent){
			//	alert("embedded");
			//}
			//else {
			//	alert("root");
			//}
			self.refreshCardinalityControls();
		},
		addProperty: function(valsdom, bframe, bframes){
			var valsdom = (valsdom ? propvalsdiv : propvalsdiv);
			var dContext = {
				containerdiv: contx,
				propertydiv: propx,
				propvalsdiv: valsdom,
				removeTarget: removeTarget,
				addTarget: addTarget,
				removeValueTarget: removeValueTarget
			}
			var bframes = (bframes ? bframes : frames);
			var bframe = (bframe ? bframe : frame);
			var cc = self.generateCardinalityControl(bframe, bframes, parent, boxed, dContext);
			cc.DOMTarget = valsdom;
			self.addProperty("create", bframe, bframes, parent, boxed, cc);
			self.refreshCardinalityControls();
		},
		removePropertyValue: function(i, pdiv, valsdom, bframe, bframes){
			var valsdom = (valsdom ? propvalsdiv : propvalsdiv);
			var pdiv = (isElement(pdiv) ? pdiv : propdiv);
			var i = (i ? i : sequence);
			var bframes = (bframes ? bframes : frames);
			var bframe = (bframe ? bframe : frame);
			if(self.removePropertyValue(pdiv, i, valsdom, bframe, bframes) == 0){
				cardControls.removeProperty();
			}
			self.refreshCardinalityControls();
		},
		removeParent: function(){
			if(parent && parent.cardControls){
				parent.cardControls.removePropertyValue();
			}
			else {
				//alert("no parent to remove");
			}
		},
		addContainer:  document.createElement("span"),
		removeValueContainer: document.createElement("span"),
		removeContainer: document.createElement("span"),
		removeTarget: removeTarget,
		addTarget: addTarget,
		removeValueTarget: removeValueTarget
	};
	var cntlcss = urlFragment(frame.property);
	cardControls.removeContainer.setAttribute("class", cntlcss + "_remove");
	cardControls.addContainer.setAttribute("class", cntlcss + "_add");
	cardControls.removeValueContainer.setAttribute("class", cntlcss + sequence + "_remove_value");
	return cardControls;
}




FrameViewer.prototype.removeProperty = function(prop, parent, framesDiv, frameDiv){
	if(parent){
		if(typeof parent.frame[prop] == "object"){
			delete(parent.frame[prop]);
			//alert("deleted property " + prop);
		}
		else {
			alert("attempt to remove property " + prop + " from broken frame structure");
			//jpr(parent);
		}
	}
	else {
		if(this.multigraph){
			if(typeof this.frames[this.mainGraph][prop] == "object"){
				delete(this.frames[this.mainGraph][prop]);
			}
			else {
				alert("attempt to remove property " + prop + " which is not displayed");
			}
		}
		else if(typeof this.frames[prop] == "object"){
			delete(this.frames[prop]);
		}
		else {
			alert("attempt to remove property " + prop + " which is not displayed");
		}
	}
	if(framesDiv && frameDiv){
		framesDiv.removeChild(frameDiv);
	}
	return framesDiv.childNodes.length;
}


FrameViewer.prototype.deletePropertyValue = function(framesDiv, valDiv, parent, parentIndex, elt){
	if(elt.frame && !(elt.frame.type == "oneOf" || elt.frame.type == "entity")){
		var domid= this.getFrameSubjectID(elt.frame);
		if(domid){
			this.deletedObjects.push(domid);
			if(framesDiv && valDiv){
				framesDiv.removeChild(valDiv);
			}
		}
		else {
			alert("Attempted to delete a frame that does not have an id - illegal");	
		}
	}
	else {
		this.removePropertyValue(valDiv, parentIndex, framesDiv, parent);
	}	
}

FrameViewer.prototype.removePropertyValue = function(valDiv, parentIndex, holderDiv, frame, parent){		
	holderDiv.removeChild(valDiv);
	if(parent){
		if(parent.length == 1 && parent[0].type == "objectProperty"){ // don't want to delete all property values!
			//if(typeof emptycb == "function"){
				
			//	emptycb(parent[0].property);			
			//}
			parent.splice(parentIndex, 1);
			parent.contents = false;
		}
		else {
			parent.splice(parentIndex, 1);		
		}
	}
	return holderDiv.childNodes.length;
}

FrameViewer.prototype.isStringType = function(type){
	return (!type || (urlFragment(type) == "string"));
}

FrameViewer.prototype.refresh = function(frame, part){
	if(part == "notifications"){
		if(typeof frame.notificationsDiv != "undefined"){
			while (frame.notificationsDiv.firstChild) {
				frame.notificationsDiv.removeChild(frame.notificationsDiv.firstChild);
			}
			this.addNotificationsToDOM(frame);
		}
		else {
			//alert("no divs");
		}
	}
}

FrameViewer.prototype.addNotificationsToDOM = function(frame, valDiv, mode){
	if(typeof frame.notificationsDiv == "undefined" && valDiv){
		frame.notificationsDiv = document.createElement("span");
		frame.notificationsDiv.setAttribute('class', "value-frame-notifications");
		valDiv.appendChild(frame.notificationsDiv);
	}
	var iconmap = {
		"error": "thumbs-down",
		"reject": "thumbs-down",
		"accept": "thumbs-up",
		"success": "thumbs-up", 
		"pending": "spinner fa-spin"
	};
	if(frame.frame){
		if(this.isBoxedType(frame.frame)){
			var cframe = frame.frame[firstKey(frame.frame)][0];
			if(typeof cframe.notifications == "object"){
				frame.notifications = jQuery.extend(true, cframe.notifications, frame.notifications);
			}
		}
	}
	if(typeof frame.notifications == "object"){
		for(var nlevel in frame.notifications){
			var nhtml = "<dl>";
			for(var ntitle in frame.notifications[nlevel]){
				nhtml += "<dt>" + ntitle + "</dt><dd>" + frame.notifications[nlevel][ntitle].join(", ") + "</dd>";
			}
			nhtml += "</dl>";
			var icon = (typeof iconmap[nlevel] == "string" ? iconmap[nlevel] : nlevel);
			var iconDiv = document.createElement("i");
			iconDiv.setAttribute('class', "fa fa-" + icon + " fa-fw");
			iconDiv.setAttribute('title', nhtml);
			jQuery(iconDiv).tooltip(this.tooltip_config);
			frame.notificationsDiv.appendChild(iconDiv);					
		}
	}
}

/* Functions that interact with the DOM */



/**
 * Frame Conceptual Structure
 * 
 * Property Frame = Property Label, Values Frame
 * Values Frame = [Value Frame]
 * Property Label = Property Controls, Label, Values Controls
 * Value Frame = Value Controls, Value, Property Value Controls, Notifications
 * 
 * Frame HTML structure: 
 * <container>
 * <div candidate-property-frame>
 * 	<div property-info>
 * 		<span property-controls>
 * 		<span property-label>
 * 		<span property-icons>
 *  </div>
 *  <div property-values-controls></div>
 * 	<div property-values>
 *      <div single-property-value>
 *      	<div property-value>... more frames with class=property-frame </div>
 *      	<div property-value-controls></div>
 *      </div>
 *      <div single-property-value>...</div>
 * </div>
 * <div class='candidate-property-frame'>...</div>
 * </container>
 */

FrameViewer.prototype.getFrameAsDOM = function(frame, mode, parent, parentIndex, boxed, cardControls){
	fcat = this.getFrameCategory(frame);
   	if(this.isSimpleDataFrame(frame, fcat)){
		var nd = this.getSimpleDatatypeValueAsDOM(fcat, frame, mode);
		if(cardControls){
			this.attachCardControlsToNewFrame(cardControls, frame, nd, cardControls.DOMTarget, parent);
		}
		this.decorateSimpleDatatypeValue(nd, frame, mode, boxed, parent, parentIndex);
	}
	else if(fcat == "object"){
		var nd = this.getObjectPropertyFrameAsDOM(frame, mode, parent, parentIndex, boxed, cardControls.DOMTarget, cardControls);			
	}
	else{
		var nd = document.createElement("span");
		nd.appendTextNode("Frame was not an object or datatype");
	}
	return nd;
}

FrameViewer.prototype.addPropertyFramesToDOM = function(frameslist, mode, container, parent){
	for(var k in frameslist){
		//get property information from first frame...
		if(frameslist[k]){
			var propertyDiv = this.getPropertyFramesAsDOM(frameslist[k], mode, container, parent);
			container.appendChild(propertyDiv);
		}
		else {
			alert(k);
		}
    }
	this.addCardinalityControls(frameslist);
}

FrameViewer.prototype.getPropertyFramesAsDOM = function(frames, mode, container, parent, boxed, cardControls){
	var propertyDiv = document.createElement("div");
    if(!parent){
    	propertyDiv.setAttribute('class', "candidate-property-frame");
    }
    else {
    	propertyDiv.setAttribute('class', "property-frame");        	
    }
    if(frames[0] && frames[0].property){
    	propertyDiv.setAttribute("data-property", frames[0].property);
    }
    var valuesDiv = this.getPropertyFrameValuesAsDOM(frames, mode, parent, propertyDiv, container, boxed, cardControls);
    propertyDiv.appendChild(valuesDiv);
    return propertyDiv;
}



FrameViewer.prototype.getPropertyFrameValuesAsDOM = function(frames, mode, parent, propertyDiv, container, boxed, cardControls){
	var domContext = {
		containerdiv: container,
		propertydiv: propertyDiv
	};
	
	var aframe = frames[0];
	var ldiv = this.getPropertyLabelDom(aframe, mode, parent);
	var propertyControls = document.createElement("span");
	propertyControls.setAttribute('class', 'property-controls');
	var valuesControls = document.createElement("span");
	var propertyValueDiv = document.createElement("div");
    propertyValueDiv.setAttribute('class', 'property-values');
	propertyValueDiv.appendChild(propertyControls);
	if(!boxed && ldiv){
		propertyValueDiv.appendChild(ldiv);
	}
	var self = this;
	var fcat = this.getFrameCategory(aframe);
	if(this.isSimpleDataFrame(aframe, fcat)){
		var entries = document.createElement("span");
		domContext.propvalsdiv = entries;
		entries.setAttribute('class', "datatype-entries");

		for(var i = 0; i<frames.length; i++){
			var entry = this.getSimpleDatatypeValueAsDOM(fcat, frames[i], mode, frames, i, boxed);
			domContext.valdiv = entry;
			domContext.removeTarget = propertyControls;
			frames[i].cardControls = this.generateCardinalityControl(frames[i], frames, parent, boxed, domContext, i);
			this.decorateSimpleDatatypeValue(entry, frames[i], mode, boxed, parent, i);
			entries.appendChild(entry);
		}
		propertyValueDiv.appendChild(entries);
	}
	else if(fcat == "object"){
		var self = this;
		var objectValuesDiv = document.createElement("div");
		domContext.propvalsdiv = objectValuesDiv;		
		propertyValueDiv.appendChild(objectValuesDiv);
		propertyValueDiv.appendChild(valuesControls);
		for(var i = 0; i<frames.length; i++){
			var frm = frames[i];
			this.attachCustomRenderer(frm, true);
			var myval = this.getObjectPropertyHolder();
			domContext.valdiv = myval;
			domContext.removeTarget = propertyControls;
			domContext.addTarget = propertyControls;
			var cc = this.generateCardinalityControl(frm, frames, parent, boxed, domContext, i);
			if(disp = this.hasCustomDisplay(frm, mode)){
				var valentries = disp.display(frm, mode);
			} 
			else {
				var valentries = this.getObjectPropertyValuesAsDOM(frm.frame, mode, frm, boxed, cc);
			}
			myval.appendChild(valentries);
			frm.cardControls = cc;//valentries.cardControl;
			this.decorateObjectValue(myval, frm, mode, i, boxed, parent, i);
			var myheader = this.getObjectHeaderDOM(frm, mode, boxed);
			objectValuesDiv.appendChild(myheader);
			objectValuesDiv.appendChild(myval);
		}
		propertyValueDiv.appendChild(objectValuesDiv);
	}
	else {
		alert("frame is not object or data: " + fcat);
		jpr(aframe);
	}
	return propertyValueDiv;
}

FrameViewer.prototype.getObjectPropertyFrameAsDOM = function(frame, mode, parent, parentIndex, boxed, container, cardControls){
	var myval = this.getObjectPropertyHolder();
	if(cardControls){
		this.attachCardControlsToNewFrame(cardControls, frame, myval, container, parent);
	}
	var valentries = this.getObjectPropertyValuesAsDOM(frame.frame, mode, frame, boxed, cardControls);
	myval.appendChild(valentries);
	this.decorateObjectValue(myval, frame, mode, parentIndex, boxed);
	return myval;
}




FrameViewer.prototype.getObjectPropertyValuesAsDOM = function(framelist, mode, parent, parentIndex, boxed, cardControls){
	var values = document.createElement("span");
	values.setAttribute('class', 'object-value-properties');
    for(var k in framelist){
    	if(framelist[k]){
    		var propertyDiv = this.getPropertyFramesAsDOM(framelist[k], mode, values, parent, this.isBoxedType(framelist), cardControls);
    		values.appendChild(propertyDiv);
    	}
    	else {
    		alert(k);
    	}
    }
    return values;
}

FrameViewer.prototype.getObjectPropertyHolder = function(){
	var wunObj = document.createElement("div");
	wunObj.setAttribute('class', 'object-value');
	return wunObj;
}

FrameViewer.prototype.getFrameLabel = function(frame){
	if(!frame) return "";
	if(frame.label){
		var label = frame.label.data; 
	}else{
		var label = urlFragment(frame.property);
	}
	return label;
}

FrameViewer.prototype.getPropertyLabel = function(frame, prop){
	if(typeof frame[prop] == "object" && frame[prop].length && frame[prop][0]){
		var vl = frame[prop][0];
		if(vl){
			var label = this.getFrameLabel(vl);
		}
		else {
			var label = urlFragment(prop);			
		}
	}
	else {
		var label = urlFragment(prop);
	}
	return label;
}


FrameViewer.prototype.getPropertyLabelDom = function(frame, mode, parent){
	if(!frame) return false;
	var labelDiv = document.createElement("div");
	var lcls = (!parent ? 'candidate-property-info' : "property-info");
	labelDiv.setAttribute('class', lcls);
	var labelSpan = document.createElement("span");
	labelSpan.setAttribute('class', 'property-label');
	var textnode = document.createTextNode(this.getFrameLabel(frame));
	labelSpan.appendChild(textnode);
	labelDiv.appendChild(labelSpan);
	if(frame.comment && frame.comment.data && frame.comment.data.length && this.showFeature(frame, "help", mode, parent)){
		var propHelp = document.createElement("span");
		propHelp.setAttribute('class', "property-values-controls");
		addPropertyHelp(propHelp, frame.comment.data, this.tooltip_config);
		labelDiv.appendChild(propHelp);
	}
	return labelDiv;	
}

FrameViewer.prototype.decorateSimpleDatatypeValue = function(entrys, frame, mode, boxed, parent, parentIndex){
	var entrySuffixes = document.createElement("span");
	var entryControls = document.createElement("span");
	entryControls.setAttribute('class', "datatype-entry-control");        
	entrySuffixes.setAttribute('class', "datatype-entry-suffixes");
	if(label = this.showFeature(frame, "type", mode, boxed, parent, parentIndex)){
		addPropertyType(entrySuffixes, frame.range, label, this.tooltip_config);
	}
	entrys.insertBefore(entryControls, entrys.firstChild);
	if(frame.cardControls){
		this.addCardinalityControlDecoration(frame, entryControls, mode, boxed, parent, parentIndex);
	}
	if(!boxed) {
		this.addNotificationsToDOM(frame, entrySuffixes, mode);
	}
	entrys.appendChild(entrySuffixes);
	
}

FrameViewer.prototype.decorateObjectValue = function(entry, frame, mode, boxed, parent, parentIndex){

	var entrySuffixes = document.createElement("span");
	var entryControls = document.createElement("span");
	if(label = this.showFeature(frame, "type", mode, boxed, parent, parentIndex)){
		addPropertyType(entrySuffixes, frame.range, label, this.tooltip_config);	    
	}
	if(frame.cardControls){
		this.addCardinalityControlDecoration(frame, entryControls, mode, boxed, parent, parentIndex);
	}
	else {
		alert("No card controls");
		jpr(frame);
	}
	entry.insertBefore(entryControls, entry.firstChild);
	entry.appendChild(entrySuffixes);
	var domid = (frame ? this.getFrameSubjectID(frame.frame) : false);
	entryControls.setAttribute('class', "datatype-entry-control");
	if(this.showFeature(frame, "idtag")){
		entryControls.appendChild(this.getFrameSubjectIDTagDOM(frame));
	}
	entrySuffixes.setAttribute('class', "datatype-entry-suffixes");        
	entry.insertBefore(entryControls, entry.firstChild);
	if(!boxed) {
		this.addNotificationsToDOM(frame, entrySuffixes, mode);
		//if(this.frameHasAnnotation(frame)){
			//var anots = this.getAnnotationsForNode(frame.domainValue);
			//if(anots && size(anots)){
				//var doms = this.getAnnotationAsDOM(anots, mode);
			//	entrySuffixes.appendChild(doms);
			//}
			//
		//}
	}
	if(this.showFeature(frame, "annotate", mode, boxed, parent, parentIndex)){
		//alert(domid + " => " + frame.property);
		//jpr(frame.frame);
		if(this.frameHasAnnotation(frame)){
			//alert("annotated " + frame.property);
			this.addValueAnnotationsToDOM(domid, mode, entrySuffixes, true);
			//var anots = this.getAnnotationsForNode(domid);
			//if(anots && size(anots)){
			//	var doms = this.getAnnotationAsDOM(anots, mode);
			//	entrySuffixes.appendChild(doms);
			//}
			//	alert(domid + " => " + frame.property + " has annotation");
		//	var anots = this.getAnnotationsForNode(domid);
		//	if(anots && size(anots)){
		//		var doms = this.getAnnotationAsDOM(anots, mode);
		}
		else if(mode == "create" || mode == "edit"){
			this.addValueAnnotationsToDOM(domid, mode, entrySuffixes, false);			
		}
		
	}
	
	entry.appendChild(entrySuffixes);
}

FrameViewer.prototype.getFrameSubjectIDTagDOM = function(frame){
	var s = this.getFrameSubjectID(frame.frame);
	if(s.substring(0,2) == "_:") s = s.substring(2);
	var spa = document.createElement("span");
	spa.setAttribute("class", "frame-subject-id-tag");
	var i = document.createElement("i");
	i.setAttribute("class", "fa fa-tag");
	spa.appendChild(i);
	var txt = document.createTextNode(" " + s + " ");
	spa.appendChild(txt);
	return spa;
}


FrameViewer.prototype.domContainsCardinalityControl = function(dom, action, frame, seq){
	var css = urlFragment(frame.property);
	if(seq) css += seq;
	css += "_" + action;
	for (var i = 0; i < dom.childNodes.length; i++) {
	    if (dom.childNodes[i].className == css) {
	    	//alert("has already " + action);
	      return true;
	    }        
	}
	return false;
}

FrameViewer.prototype.addCardinalityControlDecoration = function(frame, entryControls, mode, boxed, parent, seq){
	if(frame.cardControls.removeContainer && this.showFeature(frame, "remove", mode, boxed, parent, seq)){
		var targ = (frame.cardControls.removeTarget ? frame.cardControls.removeTarget : entryControls);
		if(!this.domContainsCardinalityControl(targ, "remove", frame)){
			targ.appendChild(frame.cardControls.removeContainer);
		}
	}
	if(frame.cardControls.addContainer && this.showFeature(frame, "add_value", mode, boxed, parent, seq)){
		var targ = (frame.cardControls.addTarget ? frame.cardControls.addTarget : entryControls);
		if(!this.domContainsCardinalityControl(targ, "add", frame)){
			targ.appendChild(frame.cardControls.addContainer);
		}
	}
	if(frame.cardControls.removeValueContainer && this.showFeature(frame, "remove_value", mode, boxed, parent, seq)){
		var targ = (frame.cardControls.removeValueTarget ? frame.cardControls.removeValueTarget : entryControls);
		if(!this.domContainsCardinalityControl(targ, "remove_value", frame, seq)){
			targ.appendChild(frame.cardControls.removeValueContainer);
		}
	}
	
}

FrameViewer.prototype.getSimpleDatatypeValueAsDOM = function(fcat, frame, mode, updateCallback){
	var initcntnts = frame.contents;
	var frameContentsUpdated = function(){
		if(frame.contents != initcntnts){
			//alert("changed to: " + frame.contents);
			initcntnts = frame.contents;
			if(typeof updateCallback == "function"){
				updateCallback();
			}
		}
		else {
			//alert("the same: " + frame.contents);
		}
	}
	this.attachCustomRenderer(frame);
	var entrys = document.createElement("span");
	if(disp = this.hasCustomDisplay(frame, mode)){
		var entry = disp.display(frame, mode, frameContentsUpdated );
	} 
	else {
		if(fcat == "data"){
			var entry = this.getDatatypeValueAsDOM(frame, mode);
		}
		else if(fcat == "choice"){
			var entry = this.getOneOfValueAsDOM(frame.frame, mode);
		}
		else if(fcat == "reference"){
			var entry = this.getEntityReferenceValueAsDOM(frame.frame, mode);
		}
		entry.setAttribute('data-property', frame.property);        
		entry.setAttribute('data-class', frame.range);        
		
		entry.onblur = frameContentsUpdated;
	}
    entrys.appendChild(entry);
   	return entrys;
}



FrameViewer.prototype.getOneOfValueAsDOM = function(frame, mode){
	if(mode == "view"){
    	var nd = document.createElement("span");
    	nd.setAttribute("class", "dacura-oneof-display");
		if(frame.domainValue){
			for(var i = 0; i<frame.elements.length; i++){
				if(frame.elements[i].class == frame.domainValue){
					if(frame.elements[i].label && frame.elements[i].label.data){
						nd.appendChild(document.createTextNode(frame.elements[i].label.data));
					}
					else {
						nd.appendChild(urlFragment(frame.domainValue));					
					}
					continue;
				}				
			}
		}
		return nd;
    }
    else {
		var optInput = document.createElement("select");
		optInput.setAttribute('class', "dacura-oneof-picker");        
		var empty = document.createElement("option");
		empty.text = "Not specified";
		empty.value = "";
		optInput.appendChild(empty);//empty one       
		//optInput.setAttribute("id", "test1");
		var frameOptions = frame.elements;
		if(!frameOptions){
			jpr(frame);
		}
		if(typeof frame.domainValue != "undefined"){
		    optValue = frame.domainValue;
		}
		else {
			optValue = "";
		}
		for(var i=0;i<frameOptions.length;i++){
		    var option = document.createElement("option");
		    if(frameOptions[i].label){
		    	option.text = frameOptions[i].label.data;
		    }
		    else {
		    	option.text = urlFragment(frameOptions[i].class);
		    }
		    option.value = frameOptions[i].class;
		    optInput.appendChild(option);
		    if(optValue && (optValue == frameOptions[i].class)){
		        optInput.selectedIndex = i+1;
		    }
		}
	    this.selectBind(frame, "contents", optInput);
	    return optInput;
    }
}



FrameViewer.prototype.getDatatypeValueAsDOM = function(elt, mode){
	if(mode == "view"){
		var input = document.createElement("span");
		labelValue = "???";
		if(typeof elt.rangeValue != "undefined"){
			labelValue = elt.rangeValue.data;
		}
		if(isNumericType(elt.rangeValue.type) && labelValue > 1000){
			labelValue = numberWithCommas(labelValue);
		}
		var value = document.createTextNode(labelValue);
		input.setAttribute('data-value', labelValue);
		input.appendChild(value);
	}
	else {
		var input = document.createElement("input");
        var inputType = "text";
        switch (urlFragment(elt.range)) {
	        case "boolean" :
	            inputType = 'checkbox';
	            break;
	        default:
	            inputType = 'text';
	            break;
        }
        input.setAttribute('type', inputType);
		if(elt.rangeValue && elt.rangeValue.data){
			input.setAttribute("value", elt.rangeValue.data);
		}
        this.bind(elt, "contents", input);
	}
	return input;
}

FrameViewer.prototype.getEntityReferenceValueAsDOM = function(frame, mode){
	if(mode == "view"){
		var input = document.createElement("span");
		labelValue = "???";
		if(typeof frame.domainValue != "undefined"){
			labelValue = this.getEntityReferenceLabel(frame.class, frame.domainValue);
		}
		var value = document.createTextNode(labelValue);
		input.setAttribute('data-value', labelValue);
		input.appendChild(value);
		return input;
	}
	else {
		var selDiv = document.createElement("select");
		selDiv.setAttribute('class', "dacura-entityref-picker");        

		var opt = document.createElement("option");
		if(frame.label && frame.label.data){
			opt.innerHTML = "Select " + frame.label.data; // whatever property it has
		}
		else {
			opt.innerHTML = "Select " + urlFragment(frame['class']);
		}
		opt.value="";
	    selDiv.appendChild(opt);
	    if(this.entities && this.entities[frame.class]){
	    	this.addEntityOptionsToDOM(selDiv, frame.class, frame.domainValue);
	    }
	    this.selectBind(frame, "contents", selDiv);
		return selDiv;
	}
}

FrameViewer.prototype.getEntityReferenceLabel = function(cls, val){
	var ting = this.entities[cls][val];
	if(ting)
		return this.getEntityLabel(ting);
	var entity_classes = (this.multigraph ? this.entity_classes[this.mainGraph] : this.entity_classes);
	for(var j = 0; j< entity_classes.length; j++){
		var ec = entity_classes[j];
		if(ec.class == cls){
			var kids = entity_classes[j].children;
			for(var k = 0; k < kids.length; k++){
				var ecn = this.entities[kids[k]];
				if(ecn){
					ting = ecn[val];
					if(ting){
						return this.getEntityLabel(ting);
					}
				}
			}
			break;
		}
	}
	return val + "missing(" + cls + ")";
}


FrameViewer.prototype.getEnumeratedChoicesHelptext = function(elements, html){
	var txt = (html ? "<dl>" : "");
	for(var i = 0; i<elements.length; i++){
		var lab = (elements[i].label ? elements[i].label.data : urlFragment(elements[i].class)); 
		txt += (html ? "<dt>" + lab + "</dt>" : lab + ": ");
		var ht = (elements[i].comment ? elements[i].comment.data : ""); 
		txt += (html ? "<dd>" + ht + "</dd>" : ht + "\n");
	}
	txt += (html ? "</dl>" : "");
	return txt;
}


FrameViewer.prototype.addEntityOptionsToDOM = function(seldiv, cls, val){
	var ents = this.getEntityOptions(cls);
	//jpr(ents);
	if(size(ents) == 1 && typeof ents[cls] != "undefined"){
    	var index = 0;
    	var selected = 0;
		for(var i = 0; i<ents[cls].length; i++){
    	//for(var eid in ents[cls]){
			var odiv = document.createElement("option");
			//odiv.text = this.getEntityLabel(ents[cls][eid]);
			odiv.text = ents[cls][i].label;
			odiv.value = ents[cls][i].url;
			//odiv.value = eid;
			seldiv.appendChild(odiv);			
    		index++;
    		if(odiv.value == val){
    			selected = index;
    		}
		}
        seldiv.selectedIndex = selected;
	}
	else {
    	var index = 0;
    	var selected = 0;
		for(var cls in ents){
			var clab = this.getEntityClassLabel(cls);
			var gdiv = document.createElement("optgroup");
			gdiv.label = clab;
			for(var i = 0; i<ents[cls].length; i++){
				var odiv = document.createElement("option");
				odiv.text = ents[cls][i].label;
				odiv.value = ents[cls][i].url;
				gdiv.appendChild(odiv);			
	    		index++;
	    		if(odiv.value == val){
	    			selected = index;
	    		}
			}
			seldiv.appendChild(gdiv);			
		}
        seldiv.selectedIndex = selected;
	}
}

FrameViewer.prototype.getEntityOptions = function(cls){
	var ents = {};
    if(this.entities && this.entities[cls] && size(this.entities[cls])){
    	ents[cls] = [];
    	for(var ent in this.entities[cls]){
    		var erec = {url: ent};
    		erec.label = this.getEntityLabel(this.entities[cls][ent]);
    		ents[cls].push(erec); 
    	}
    }
    var entity_classes = (this.multigraph ? this.entity_classes[this.mainGraph] : this.entity_classes);
	for(var i = 0; i< entity_classes.length; i++){
		if(entity_classes[i].class == cls && typeof entity_classes[i].children == "object"&& entity_classes[i].children.length){
			var ec = entity_classes[i];
			for(var j= 0; j< ec.children.length; j++){
				if(this.entities && this.entities[ec.children[j]] && size(this.entities[ec.children[j]])){
			    	ents[ec.children[j]] = [];
			    	for(var ent in this.entities[ec.children[j]]){
			    		var erec = {url: ent};
			    		erec.label = this.getEntityLabel(this.entities[ec.children[j]][ent]);
			    		ents[ec.children[j]].push(erec); 
			    	}
			    }
			}
		}
	}
	return ents;
}

/**
 * Functions which bind the html value to the frame object..
 */
FrameViewer.prototype.bind = function(obj, prop, elt){
	if(typeof obj[prop] == "undefined"){
	    Object.defineProperty(obj, prop, {
	    	get: function(){return elt.value;}, 
	    	set: function(newValue){elt.value = newValue;},
	    	configurable: true
	    });
	    return true;
	}
	else {
		return false;
	}
}

FrameViewer.prototype.dataBind = function(obj, prop, elt){
    Object.defineProperty(obj, prop, {
        get: function(){return elt.dataset.value;}, 
        set: function(newValue){elt.dataset.value = newValue;},
        configurable: true
    });
}

FrameViewer.prototype.selectBind = function(obj, prop, elt){
    Object.defineProperty(obj, prop, {
        get: function(){return elt.options[elt.selectedIndex].value;}, 
        set: function(newValue){elt.options[elt.selectedIndex].value = newValue;},
        configurable: true
    });
}
FrameViewer.prototype.getAnnotationSummaryLabel = function(cls, annotation){
	var label = "";
	if(!(typeof annotation == "object" && size(annotation))){
		label = "object structure problem";
		alert(label);
	}
	else {
		var clslabel = this.getEntityClassLabel(cls); 
		label = size(annotation) + " " + clslabel + (size(annotation) > 1 ? "s" : ""); 
		if(size(annotation) == 1){
			if(typeof annotation[firstKey(annotation)] == "object" && annotation[firstKey(annotation)].length){
				label += ": " + annotation[firstKey(annotation)][0].label.data + " ";				
				if(annotation[firstKey(annotation)].length > 1){
					label += annotation[firstKey(annotation)].length + " values";
				}
				else {
					label += " 1 value";//JSON.stringify(this.extractFromIndexedFrames(annotation[firstKey(annotation)][0]), annotation[firstKey(annotation)][0]));
				}
			}
			else {
				label += " brokean annotation frame structure";
			}
		}
		else {
			
		}
	}
	return label;
}

FrameViewer.prototype.getAnnotationsSummaryLabel = function(annotations, mode){
	if(size(annotations) == 1){//single graph
		var gurl = firstKey(annotations);
		if(size(annotations[gurl]) == 1){//single object
			return this.getAnnotationSummaryLabel(firstKey(annotations[gurl]), annotations[gurl][firstKey(annotations[gurl])]);			
		}
		else {
			return mode + " annotations (" + size(annotations[gurl]) + " objects available)";
		}
	}
	else {
		var types = 0;
		for(var gurl in annotations){
			types += size(annotations[gurl]);
		}
		return mode + " annotations (" + types + " types available)";
	}
}

FrameViewer.prototype.getAnnotationAsDOM = function(annotation, mode){
	var fullAnnotationsDiv = document.createElement("div");
	fullAnnotationsDiv.setAttribute("class", "embedded-annotations");
	var fvid = Math.random().toString(36);
	fullAnnotationsDiv.setAttribute("id", fvid);
	var hr = document.createElement("hr");
	fullAnnotationsDiv.appendChild(hr);
	this.addAnnotationObjectFrames(fullAnnotationsDiv, annotation, mode);    		    			
	return fullAnnotationsDiv;
	
}


FrameViewer.prototype.addValueAnnotationsToDOM = function(nodeid, mode, valDiv, show){
	var annotation = (nodeid ? this.getAnnotationsForNode(nodeid) : false);
	if(annotation && size(annotation)){
		var msg = this.getAnnotationsSummaryLabel(annotation, mode);
		var imsg = msg;
		var defsel = msg;
	}
	else {
		//attach annotate icon
		var imsg = "Annotate this property value";
		var defsel = false;
	}
	var annotationsHeaderLabel = document.createElement("span");
	if(msg){
		var txt = document.createTextNode(msg);
		annotationsHeaderLabel.appendChild(txt);
	}
	var annotationsHeaderDiv = document.createElement("div");
	annotationsHeaderDiv.appendChild(annotationsHeaderLabel);
	var annotationFullHeader = this.getAnnotationHeaderDOM(false, defsel, true);
	var contentsContainerDiv = document.createElement("div");
	var fullAnnotationsDiv = document.createElement("div");
	fullAnnotationsDiv.setAttribute("class", "embedded-annotations");
	var fvid = Math.random().toString(36);
	fullAnnotationsDiv.setAttribute("id", fvid);
	var self = this;
	var visible = (show ? show : false);
	var removeAnnotations = function(){
		while (fullAnnotationsDiv.firstChild) {
			fullAnnotationsDiv.removeChild(fullAnnotationsDiv.firstChild);
	    }
		while (contentsContainerDiv.firstChild) {
			contentsContainerDiv.removeChild(contentsContainerDiv.firstChild);
	    }
		while (annotationsHeaderDiv.firstChild) {
			annotationsHeaderDiv.removeChild(annotationsHeaderDiv.firstChild);
		}	
		//annotationsHeaderDiv.appendChild(annotationsHeaderLabel);
	}
	var showAnnotations = function(){
		while (annotationsHeaderDiv.firstChild) {
			annotationsHeaderDiv.removeChild(annotationsHeaderDiv.firstChild);
		}	
		annotationsHeaderDiv.appendChild(annotationFullHeader);
		if(annotation && size(annotation)){
			self.addAnnotationObjectFrames(fullAnnotationsDiv, annotation, mode);    		    			
		}
		contentsContainerDiv.appendChild(fullAnnotationsDiv);
	}
	if(visible) showAnnotations();
    attachAnnotateIcon(valDiv, imsg, function(){
    	if(visible){
    		removeAnnotations();
    		visible = false;
    	}
    	else {
    		showAnnotations();
    		visible = true;
    	}
    });
    //valDiv.appendChild(annotationsHeaderDiv);
    valDiv.appendChild(contentsContainerDiv);
}

FrameViewer.prototype.getAnnotationHeaderDOM = function(msg, defsel, embedded){
	var sel = this.getAnnotationEntitySelectorDOM(defsel);
	var selhdr = document.createElement("span");
	selhdr.appendChild(sel);
	annotationsHeader = document.createElement("div");
	annotationsHeader.setAttribute('class', "entity-annotations-header"); 
	if(msg){
		textnode = document.createTextNode(msg);
		annotationsHeader.appendChild(textnode);
	}
	annotationsHeader.appendChild(selhdr);
	return annotationsHeader;
}

/**
 * Adds non-main graph annotation objects to frames
 */
FrameViewer.prototype.addAnnotationObjectFrames = function(containerDiv, frameslist, mode){
	for(var gurl in frameslist){
		for(var cls in frameslist[gurl]){
			//alert("adding " + cls);
			//var clstxt = this.getEntityClassLabel(cls, gurl);
			var annotationDiv = document.createElement("div");
		    annotationDiv.setAttribute('class', "entity-annotation");
		    var annotationHeader = document.createElement("h4");
		    annotationHeader.setAttribute('class', "entity-annotations-header"); 
			//var textnode = document.createTextNode(clstxt);
			//annotationHeader.appendChild(textnode);
			//annotationDiv.appendChild(annotationHeader);
		    //annotationHeader.appendChild(document.createTextNode(this.getFrameSubjectID(frameslist[gurl][cls])));
			//annotationDiv.appendChild(annotationHeader);
		    
			this.addPropertyFramesToDOM(frameslist[gurl][cls], mode, annotationDiv);
			containerDiv.appendChild(annotationDiv);
		}
	}
}




FrameViewer.prototype.getAnnotationEntitySelectorDOM = function(deflabel){
	var selDiv = document.createElement("select");
	if(deflabel){
		var option = document.createElement("option");
		option.text = deflabel;
	    option.value = "";
	    selDiv.appendChild(option);		
	}
	for(var gurl in this.entity_classes){
		if(gurl == this.mainGraph) continue;
		for(var i = 0; i<this.entity_classes[gurl].length; i++){
			var ec = this.entity_classes[gurl][i];
			var fs = this.getFramesWithClass(this.archetypes[gurl], ec['class']);
			if(fs && size(fs)){
				var option = document.createElement("option");
				option.text = this.getEntityClassLabel(ec['class'], gurl);
			    option.value = gurl + " " + ec['class'];
			    selDiv.appendChild(option);
			    selDiv.selectedIndex = 0;
			}
	    }
	}
	/*
	 * 		    selDiv.addEventListener("change", function(){
		    	var bits = this.value.split(" ");
		    	if(bits.length == 2 && (selgurl != bits[0] || selcls != bits[1])){
		    		selgurl = bits[0];
		    		selcls = bits[1];
					var frames = {};
					frames[selgurl] = jQuery.extend(true, [], entity_classes[selgurl][selcls]);
			    	fvcontainer = document.getElementById(fvid);
				    while (fvcontainer.firstChild) {
				    	fvcontainer.removeChild(fvcontainer.firstChild);
				    }
					self.draw(frames, "create", fvid);
		    	}
		    });

	 */
	return selDiv;
	    
}

FrameViewer.prototype.showAnnotationCreateWindow = function(frame, domid) {
	//alert("showing annotations for frame: " + frame.property + " - " + frame.domainValue);
	if(this.multigraph){
		var entity_classes = {};
		for(var gurl in this.archetypes){
			if(gurl != this.mainGraph){
				for(var prop in this.archetypes[gurl]){
					for(var i = 0; i < this.archetypes[gurl][prop].length; i++){
						var arch = jQuery.extend(true, {}, this.archetypes[gurl][prop][i]);
						if(domid){
							arch.domainValue = domid;
						}
						if(typeof entity_classes[gurl] == "undefined"){
							entity_classes[gurl] = {};
						}
						if(typeof entity_classes[gurl][arch.domain] == "undefined"){
							entity_classes[gurl][arch.domain] = {};
						}
						if(typeof entity_classes[gurl][arch.domain][prop] == "undefined"){
							entity_classes[gurl][arch.domain][prop] = [];
						}
						entity_classes[gurl][arch.domain][prop].push(arch);
					}
				}
			}
		}
		if(size(entity_classes)){
			var anotDiv = document.createElement("div");
			anotDiv.setAttribute("title", "Add metadata to " + frame.label.data);
			anotDiv.setAttribute("class", "dacura-annotation-viewer");
			var selDiv = document.createElement("select");
			var anotfvDiv = document.createElement("div");
			var fvid = Math.random().toString(36);
			var selgurl = firstKey(entity_classes);
			var selcls = firstKey(entity_classes[selgurl]);
			anotfvDiv.setAttribute("id", fvid);
			for(var gurl in entity_classes){
				for(var cls in entity_classes[gurl]){
					var option = document.createElement("option");
					if(typeof this.entity_classes[gurl][cls] != "undefined" && this.entity_classes[gurl][cls].label){
					    	option.text = this.entity_classes[gurl][cls].label.data;
				    }
				    else {
				    	option.text = urlFragment(cls);
				    }
				    option.value = gurl + " " + cls;
				    selDiv.appendChild(option);
				    selDiv.selectedIndex = 0;
				    var self = this;
				    selDiv.addEventListener("change", function(){
				    	var bits = this.value.split(" ");
				    	if(bits.length == 2 && (selgurl != bits[0] || selcls != bits[1])){
				    		selgurl = bits[0];
				    		selcls = bits[1];
							var frames = {};
							frames[selgurl] = jQuery.extend(true, [], entity_classes[selgurl][selcls]);
					    	fvcontainer = document.getElementById(fvid);
						    while (fvcontainer.firstChild) {
						    	fvcontainer.removeChild(fvcontainer.firstChild);
						    }
							self.draw(frames, "create", fvid);
				    	}
				    });
				}
			}
			anotDiv.appendChild(selDiv);
			anotDiv.appendChild(anotfvDiv);
			jQuery(anotDiv).dialog();
			var selgurl = firstKey(entity_classes);
			var selcls = firstKey(entity_classes[selgurl]);
			var frames = {};
			frames[selgurl] = arch;
			this.draw(frames, "create", fvid);
		}
	}
}



/* utility functions */

function addPropertyHelp(helpDiv, comment, tooltip_config){
	if(!tooltip_config || !size(tooltip_config)){
		alert("no");
	}
	attachIcon(helpDiv, "property-help", "fa-question-circle-o", comment, tooltip_config);
}

function addPropertyType(div, range, label, tooltip_config){
	var clicked = false;
	var tooltipclick = function(){
		window.open(range);
	}
	attachIcon(div, "property-type", "fa-transgender", label, tooltipclick, tooltip_config);
}

function attachDeleteIcon(frameDiv, msg, func, tooltip_config){
	attachIcon(frameDiv, "delete-property", "fa-close", msg, func, tooltip_config);
}

function attachEditIcon(frameDiv, msg, func, tooltip_config){
	attachIcon(frameDiv, "edit-property", "fa-edit", msg, func, tooltip_config);
}

function attachRemoveIcon(frameDiv, msg, func, tooltip_config){
	attachIcon(frameDiv, "remove-property", "fa-minus-square-o", msg, func, tooltip_config);
}

function attachAnnotateIcon(helpDiv, comment, func, tooltip_config){
	attachIcon(helpDiv, "annotate-property", "fa-sticky-note-o", comment, func, tooltip_config);
}

function attachAddIcon(frameDiv, msg, func, tooltip_config){
	attachIcon(frameDiv, "add-property", "fa-plus-square-o", msg, func, tooltip_config);
}

function fixGoogleMaps(){
	if(typeof google != "undefined"){
    	jQuery(".googleMap").each(function(){
    		google.maps.event.trigger(jQuery(this)[0], 'resize');
    	});
    }
}

function attachIcon(frameDiv, cls, icontxt, msg, func, tooltip_config){
	var addDiv = document.createElement("span");
	addDiv.setAttribute('class', cls);        
	addDiv.setAttribute('title', msg);        
	var icon = document.createElement("i");
	icon.setAttribute('class', "fa " + icontxt);        
	addDiv.appendChild(icon);
	if(typeof func == "function"){
		var nfunc = function (){
			jQuery(addDiv).tooltip( "close");
			func();
		}
		addDiv.addEventListener("click", nfunc);
	}
	frameDiv.appendChild(addDiv);
	if(msg && msg.length){
		jQuery(addDiv).tooltip(tooltip_config);
	}
}


