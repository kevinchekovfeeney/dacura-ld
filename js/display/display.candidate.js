/** 
 * Fetches candidate from api and displays it in the console
 */
DacuraConsole.prototype.displayCandidate = function(){
	var self = this;
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header").show();
	if(this.context.mode == "view"){
		jQuery('#' + this.HTMLID + " .console-extra .console-edit-candidate").hide();
		jQuery('#' + this.HTMLID + " .console-extra .console-view-candidate").show();
		jQuery('#' + this.HTMLID + " .console-extra .console-extra-buttons").hide();
	}
	else {
		this.setSubmitButtonLabels("candidate");
		jQuery('#' + this.HTMLID + " .console-extra .console-extra-buttons").show();
		jQuery('#' + this.HTMLID + " .console-extra .candidate-edit-id input").prop("disabled", "true");
		jQuery('#' + this.HTMLID + " .console-extra .console-view-candidate").hide();
		jQuery('#' + this.HTMLID + " .console-extra .console-edit-candidate").show();
		jQuery('#' + this.HTMLID + " .console-extra .createonly").hide();
		jQuery('#' + this.HTMLID + " .console-extra .candidate-nocreate").show();
	}
	self.loadCandidateToHTML(self.current_candidate);
	self.showing_extra = {mode: self.context.mode, type: "candidate"};
	self.setMetadataVisibility(self.context.mode);
	jQuery('#' + self.HTMLID + " .console-extra .console-context-full").show();
	var failcand = function(){
		alert("fail");
	}
	var displayFrames = function(frames){
		if(self.context.mode == "view"){
			var props = self.current_candidate.getProperties();
		}
		else {
			var props = self.getFullCandidatePropertyList(self.current_candidate, frames);
		}
		self.initCandidatePropertySelector(props);
		//jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer").show();
	};
	this.client.getEmptyFrame(this.context.entityclass, displayFrames, failcand);
	/*
	return;
	var showcand = function(cand){
		if(self.context.mode == "view"){
			var props = cand.getProperties();
		}
		else {
			var props = self.getFullCandidatePropertyList(cand, frames);
		}
		self.initCandidatePropertySelector(props);
		self.showing_extra = {mode: self.context.mode, type: "candidate"};
		self.current_candidate = cand;
		self.loadCandidateToHTML(cand);
		self.setMetadataVisibility(self.context.mode);
		jQuery('#' + self.HTMLID + " .console-extra .console-context-full").show();
		//jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer").show();
		/*
		var showitall = function(frames){
			if(self.frameviewer){
				//jQuery('#' + this.framedivid).html("");
				//self.frameviewer.redraw(self.context.mode);
			}
			else {
				var fvconfig = self.frameviewerConfig;
				fvconfig.target = self.framedivid;
				self.frameviewer = new FrameViewer(self.client.getCandidateList(), self.getEntityClasses(true), fvconfig);
				var updcb = function(handler, upd){
					self.testUpdate(handler, handler, upd, self.context.entityclass, self.context.candidate);
				}
				self.frameviewer.init(self.context.entityclass, frames, updcb);
			}
			//if(self.context.mode == "harvest"){
			//	var factoid_frames = self.frameviewer.importFactoids(self.pagescanner.factoids, true);
			//	self.displayFactoidsOnPage(factoid_frames);
			//}
		}
	};
	var failcand = function(title, msg, extra){
		self.showResult("error", title, msg, extra);
	};
	//in case it's not loaded - but it should be loaded by init
	this.client.get("candidate", this.context.candidate, showcand, failcand); */
} 

DacuraConsole.prototype.displayFactoidFramesOnPage = function(factoid_frames){
	var self = this;
	var f = function(frame){
		var selhtml = self.getDataImportPropertySelectorHTML(frame);
		var npage = "";//we build up the new page body from scratch by stitching the updates into the page text and doing a full text update
		var npage_offset = 0;
		console.time("decorating factoids");
		for(var fid in self.pagescanner.factoids){
			var foid = self.pagescanner.factoids[fid];
			var decorated = foid.decorate(self.pagescanner.factoid_css_class, self.pagescanner.factoid_id_prefix, selhtml);
			npage += self.pagescanner.originalBody.substring(npage_offset, foid.original.location) + decorated + foid.original.full;
			npage_offset = foid.original.location + foid.original.full.length;
		}
		jQuery(self.jquery_body_selector).html(npage + self.pagescanner.originalBody.substring(npage_offset));
		if(selhtml){
			jQuery(self.jquery_body_selector + " .factoid-import-change").click(function(){
				jQuery(this).hide();
				jQuery(this).closest("span.factoid-stat").find(".factoid-stat-value").hide();
				jQuery(this).closest("span.factoid-stat").find(".factoid-import-changer").show();
				jQuery(this).closest("span.factoid-stat").find(".factoid-import-changer select.property-picker").select2({
					placeholder: "Not Imported",
					width: 250,
					allowClear: true,
					tags: true
				}).on('change', function(){
					if(this.value){
						alert("importing to " + this.value);
					}
				});
			});
		}
		console.timeEnd("decorating factoids");
		console.time("adding frames");
		for(var fid in self.pagescanner.factoids){
			//console.time("adding frame " + fid);
			var foid = self.pagescanner.factoids[fid];
			var htmlid = self.pagescanner.factoid_id_prefix + foid.uniqid;
			var hclass = foid.getHarvestsClass();
			if(!hclass) hclass = foid.getHarvests();
			jQuery('#' + htmlid + " select.property-picker").val(hclass);
			if(typeof factoid_frames[foid.uniqid] != "undefined"){
				var frmid = htmlid + "-frameviewer";
				self.frameviewer.draw(factoid_frames[foid.uniqid], "create", frmid, true);
			}
		}
		console.timeEnd("adding frames");
	}
	self.initEntityContext(self.context.entityclass, f);
}

DacuraConsole.prototype.showFactoidFrames = function(factoid_frames){
	for(var fid in this.pagescanner.factoids){
		var foid = this.pagescanner.factoids[fid];
		var htmlid = this.pagescanner.factoid_id_prefix + foid.uniqid;
		if(foid.frames){
			var frmid = htmlid + "-frameviewer";
			this.frameviewer.draw(foid.frames, "create", frmid, true);
		}
		else {
			//alert("no import");
		}
	}
}


DacuraConsole.prototype.initFrameViewer = function(frames, autosubmit){
	if(!this.frameviewer){
		this.frameviewer = new FrameViewer(this.client.getCandidateList(), this.getEntityClasses(true), this.frameviewerConfig, this.client.getFrameRenderingMap());
	}
	var self = this;
	var updcb = function(handler, upd){
		self.testUpdate(handler, handler, upd, self.context.entityclass, self.context.candidate, autosubmit);
	}
	this.frameviewer.init(this.context.entityclass, frames, updcb);
}

DacuraConsole.prototype.getCandidateEntityClassLabel = function(cand){
	var ecs = this.client.getEntityClasses();
	var ec = cand.entityClass(ecs);
	if(ec.label){
		var clab = ec.label.data;
	}
	else if(ec.id){
		var clab = ec.id.split(':')[1];
	}
	else {
		var clab = ec['class'].substring(ec['class'].lastIndexOf('#') + 1);
	}
	return clab;
}

/* Reading and writing to the forms */
DacuraConsole.prototype.loadCandidateToHTML = function(cand){
	this.setSummaryIconVisibility("candidate", false, cand);
	this.setSummaryLabel("candidate", cand);
	this.setMetadataEditorValues(cand.meta);
	if(this.context.mode != "harvest"){
		//jQuery('#' + this.HTMLID + " select.context-entityproperty-picker").val("").trigger("change");	
		jQuery('#' + this.HTMLID + " select.context-candidateproperty-picker").val("").trigger("change");
	}
}

DacuraConsole.prototype.loadNewCandidateToHTML = function(def){
	var def = (def ? def : {id: "", "label": "", "comment": ""});
	if(def.metadata){
		this.setMetadataEditorValues(def.metadata);
	}
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-label input").val(def.label);
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-id input").val(def.id);
	jQuery('#' + this.HTMLID + " .console-extra .candidate-edit-comment textarea").val(def.comment);
	if(this.context.mode != "harvest"){
		jQuery('#' + this.HTMLID + " select.context-entityproperty-picker").val("").trigger("change");	
	}
	else {
		//jQuery('#' + this.HTMLID + " select.context-entityproperty-picker").val(this.context.candidateproperty).trigger("change");	
		
	}
}

DacuraConsole.prototype.getDefaultMetadataValues = function(){
	var purl = window.location.href;
	var wl = new webLocator({url: purl});
	var meta = {harvested: [wl]};
	return meta;
}

DacuraConsole.prototype.getDefaultEditCandidateFormValues = function(cand){
	var defs = {metadata: this.getDefaultMetadataValues()};
	return defs;
}

DacuraConsole.prototype.getDefaultNewCandidateFormValues = function(type){
	var defs = {metadata: this.getDefaultMetadataValues()};
	var purl = window.location.href;
	defs.id = lastURLBit(purl);
	defs.comment = "";
	defs.label = "";
	if(this.pagescanner && this.pagescanner.sequence && this.pagescanner.sequence.length > 0){
		//for(var i in pagescanner.factoids){
			//try to extract the label and comment data from elements on the page
		//}
	}
	return defs;
}

DacuraConsole.prototype.getDefaultNewPropertyFormValues = function(){
	var defs = {metadata: this.getDefaultMetadataValues()};
	defs.id = "";
	defs.comment = "";
	defs.label = "";
	return defs;
}

DacuraConsole.prototype.getDefaultNewClassFormValues = function(){
	var defs = {metadata: this.getDefaultMetadataValues()};
	defs.id = "";
	defs.comment = "";
	defs.label = "";
	defs.ctype = "";
	return defs;
}

DacuraConsole.prototype.getDefaultEditPropertyFormValues = function(prop){
	var defs = {};//{metadata: this.getDefaultMetadataValues()};
	return defs;
}

DacuraConsole.prototype.getDefaultEditClassFormValues = function(cls){
	var defs = {};// {metadata: this.getDefaultMetadataValues()};
	return defs;
}

/**
 * Reads the form and returns a LDO in the correct format for submission to api
 */
DacuraConsole.prototype.getEntityDetailsFromForm = function(cand_id){
	var frame_data = this.getFrameInputs();
	if(frame_data && !isEmpty(frame_data)){
		var ldo = {contents: frame_data};		
	}
	else {
		var ldo = {contents: {"rdf:type": this.context.entityclass}};		
	}
	var label = jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-label input").val();
	var comment = jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-comment textarea").val();
	if(!cand_id){
		var id = jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header .candidate-edit-id input").val();
		if(id && id.length){
			ldo.id = id;
		}
		else {
			var purl = window.location.href;
			var myid = lastURLBit(purl).toLowerCase();
			myid = myid.replace(/\W/g, '');
			if(myid && myid.length){
				ldo.id = myid;
			}
			
		}
	}
	if(label && label.length && typeof ldo.contents["rdfs:label"] == "undefined"){
		ldo.contents["rdfs:label"] = label; 
	}
	if(comment && comment.length && typeof ldo.contents["rdfs:label"] == "undefined"){
		ldo.contents["rdfs:comment"] = comment; 
	}
	var mconf = this.client.getMainGraphConfig(); 
	/*var provs = this.stripProvenance(ldo.contents, this.frameviewer.provpred, mconf.instance);
	if(provs && size(provs)){
		var fmat = this.formatProvenance(provs, cand_id);
		var pconf = this.client.getProvGraphConfig(); 
		if(pconf && mconf){
			ldo.contents[pconf.instance] = fmat;
		}
		else {
			ldo.contents[this.frameviewer.provpred] = fmat;
		}
	}*/
	return ldo;
}

DacuraConsole.prototype.stripProvenance = function(ldo, pstr, mgurl){
	var provs = {};
	if(mgurl && typeof(ldo[mgurl]) != "undefined"){
		nldo = ldo[mgurl];
		nldo = nldo[firstKey(nldo)];
	}
	else {
		nldo = ldo;
	}
	if(typeof nldo[pstr] != "undefined"){
		provs = nldo[pstr];
		delete(nldo[pstr]);
	}
	for(var prop in nldo){
		if(typeof nldo[prop] == 'object' && !nldo[prop].length && size(nldo[prop])){
			for(var nid in nldo[prop]){
				var prov = this.stripProvenance(nldo[prop][nid], pstr);
				if(prov && prov.length){
					provs[prop] = prov;			
				}
			}
		}
	}
	return provs;
}

DacuraConsole.prototype.formatProvenance = function(provs, cand_id){
	var pls = {};
	for(var prop in provs){
		for(var i = 0; i<provs[prop].length; i++){
			var sd = provs[prop][i];
			sd.setProperty(prop);
			var fid = sd.pagelocator.uniqid();
			if(typeof pls[fid] == "undefined"){
				pls[fid] = [];
			}
			pls[fid].push(sd);
		}
	}
	var page = this.getPageURL();
	var provrec = { "rdf:type": "seshatprov:Location", "seshatprov:url": page};
	var facts = {};
	var data = [];
	for(var uid in pls){
		for(var i = 0; i<pls[uid].length; i++){
			if(pls[uid][i].targets && pls[uid][i].targets.length){
				var fact = pls[uid][i].getRDF();
				for(var lk in fact){
					facts[lk] = fact[lk];					
				}	
			}
			else {
				data.push(pls[uid][i].getRDF());
			}
		}
	}
	if(size(facts)){
		provrec['seshatprov:properties'] = facts;
	}
	if(data.length){
		provrec['seshatprov:data'] = data;
	}
	var ret = {};
	if(cand_id){
		ret[cand_id] = {"rdf:type": "seshatprov:Entity", "seshatprov:updatedFrom": provrec};
	}
	else {
		ret["_:"] = {"rdf:type": "seshatprov:Entity", "seshatprov:createdFrom": provrec};
	}
	return ret;
}

DacuraConsole.prototype.getPageURL = function(){
	return window.location.href;
}

DacuraConsole.prototype.importFactoids = function(archetype_frames, display){
	if(!this.pagescanner) return false;
	var self = this;
	var startfv = function(){
		try {
			self.initFrameViewer(archetype_frames);
			var factoid_frames = self.frameviewer.importFactoids(self.pagescanner.factoids);
			self.showFactoidFrames(factoid_frames);
			self.showSeshatFrames();
			return factoid_frames;
		}
		catch(e){
			alert(e.toString() + e.stack);
		}
	};
	if(!this.pagescanner.parsed){
		var func = function(){
			self.displayFactoids();
			startfv();
		}
		self.pagescanner.parseValues(self.parser_url, func);
	}
	else {
		startfv();
	}
}



//last minute down and dirty tweaks for seshat 
DacuraConsole.prototype.showSeshatFrames = function(){
	var gd = document.createElement("span");
	var hasGD = false;
	jQuery("b:contains('General description')").parent().nextUntil("h2").each(function(){
		gd.appendChild(this);
		hasGD = true;
		//jQuery(this).html("");
	});
	if(!hasGD){
		jQuery("b:contains('General Description')").parent().nextUntil("h2").each(function(){
			gd.appendChild(this);
			hasGD = true;
			//jQuery(this).html("");
		});
	}
	if(hasGD){
		jQuery("b:contains('General description')").parent().append("<div id='fvgid'></div>" + jQuery(gd).html());
		jQuery("b:contains('General Description')").parent().append("<div id='fvgid'></div>" + jQuery(gd).html());
		var frames = this.frameviewer.getPropertyFrames("http://dacura.scss.tcd.ie/seshat/ontology/seshat#description");
		if(frames){
			frames[0].rangeValue = {data: jQuery(gd).html(), lang: "en"};
			this.frameviewer.draw(frames, "create", "fvgid", true);
		}
	}
	var rd = document.createElement("ol");
	var n = 1;
	jQuery("ol.references li").each(function(){
		rd.appendChild(this);
		anc = document.createElement("a");
		anc.setAttribute("name", "cite_note-" + n);
		rd.appendChild(anc);
		n++;
	});
	jQuery("ol.references").before("<div id='rfid'></div>");
	var container = document.getElementById("rfid");
	var frames = this.frameviewer.getPropertyFrames("http://dacura.scss.tcd.ie/seshat/ontology/seshat#references");
	if(container && frames){
		frames[0].rangeValue = {data: rd.innerHTML, lang: "en"};
		this.frameviewer.draw(frames, "create", "rfid", true);
	}
	var imgs = [];
	jQuery("div#content img").each(function(){
		var src = jQuery(this).attr("src");
		if(src){
			if(src.charAt(0) == "/"){
				src = "http://seshat.info" + src;
			}
			imgs.push(src);
		}
	});
	frames = this.frameviewer.getPropertyFrames("http://dacura.scss.tcd.ie/seshat/ontology/seshat#hasMap");
	if(frames && imgs.length){
		var nframes = [];
		for(var i=0; i<imgs.length;i++){
			var nframe = jQuery.extend(true, {}, frames[0]);
			nframe.rangeValue = {data: imgs[i], type: "http://www.w3.org/2001/XMLSchema#:anyURI"};
			nframes.push(nframe);
		}
		if(nframes.length){
			jQuery("b:contains('Map')").parent().append("<div id='mapid'></div>");
			this.frameviewer.draw(nframes, "create", "mapid", true);
		}
	}
	//polity list....
}


/* functions that interact with the server */

/**
 * Extracts data from the current version of the form and submits it to the candidate api - uses state 
 * variables to figure out which calls to make
 * @param test - true if it is a test invocation
 */
DacuraConsole.prototype.hitUpCandidateAPI = function(test){
	test = (test ? true : false);
	var self = this;
	var f = function(ldr){
		if(typeof ldr == "object"){
			self.showResult("error", ldr.getResultTitle());
			ldr.pconfig = self.full_pconfig;
			ldr.show();
		}
		else {
			self.showResult("error", "Error from candidate API: " + ldr);
		}
	}
	var s = function(ldr){
		self.showResult(ldr.status, ldr.getResultTitle());
		ldr.pconfig = self.full_pconfig;
		ldr.show();
		if(!test && (ldr.status == "accept" || ldr.status == "pending")){
			var ncontext = {mode: "view"};
			if(self.context.mode == "create" && typeof ldr.result == 'object' && typeof ldr.result.id == "string"){
				ncontext.candidate = ldr.result.id;			
			}
			self.userChangesContext(ncontext);				
		}
	};
	if(this.context.mode == "create" || !this.context.candidate){
		//alert("creation");
		this.createCandidate(s, f, test);
	}
	else {
		alert(this.context.mode);
		this.updateCandidate(s, f, test);					
	}
}

DacuraConsole.prototype.testUpdate = function(success, fail, ldo, ec, candid){
	ldo['rdf:type'] = ec;
	if(candid){
		this.client.updateCandidate(this.context.candidate, ldo, false, success, fail, true);		
	}
	else {
		this.client.createCandidate(false, ldo, false, success, fail, true);
	}
}

DacuraConsole.prototype.createCandidate = function(success, fail, test){
	var basics = this.getEntityDetailsFromForm();
	this.client.createCandidate(basics.id, basics.contents, false, success, fail, test);
}

DacuraConsole.prototype.updateCandidate = function(success, fail, test){
	var basics = this.getEntityDetailsFromForm(this.context.candidate);
	this.client.updateCandidate(this.context.candidate, basics.contents, false, success, fail, test);
}

DacuraConsole.prototype.deleteCandidate = function(test){
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
		self.userChangesContext({mode: "view", candidate: false});			
	};
	self.client.remove("candidate", self.context.candidate, s, f, test);
}

/**
 * Fetches an entity class metadata object from a url
 * @param url - string the url 
 * @return - object or false on failure
 */
DacuraConsole.prototype.getEntityClassFromURL = function(url, gid){
	var ecap = this.context.collection ? this.client.collections[this.context.collection].entity_classes : false;
	if(typeof ecap == "object" && size(ecap)){
		if(gid){
			var rel = ecap[gid];
			for(var i = 0; i< rel.length; i++){
				if(rel[i].class == url) return rel[i];
			}				
		}
		else {
			for(var gurl in ecap){
				for(var i = 0; i< ecap[gurl].length; i++){
					if(ecap[gurl][i].class == url) return ecap[gurl][i];
				}				
			}
		}
	}
	else if(typeof ecap == "object" && ecap.length){
		for(var i = 0; i< ecap.length; i++){
			if(ecap[i].class == url) return ecap[i];
		}
	}
	return false;
}

/**
 * Translates the URL of a candidate into an ID
 * does it by looking up capabilities
 * could also be done by just getting the info after the 
 * @param url string the url in question
 * @return candidate id string or false if unknown
 */
DacuraConsole.prototype.getCandidateIDFromURL = function(url){
	var col = this.client.collections[this.context.collection];
	for(var etype in col.entities){
		if(typeof col.entities[etype][url] == "object"){
			return col.entities[etype][url]['id'];
		}
	}
	return false;
}


/**
 * Called when an entity class is selected (for generating the entity property list)
 * @param cb callback success function
 */
DacuraConsole.prototype.initEntityContext = function(cls, cb, undisplayed){
	var self = this;
	var cls = (cls ? cls : this.context.entityclass);
	var initcon = (cb? cb : function(frame){
		self.initEntityPropertySelector(frame);
		self.showMenu(undisplayed);
		if(self.pageHasFactoids()){
			self.importFactoids(frame);
		}
	});
	var failcand = function(title, msg, extra){
		self.showResult("error", title, msg, extra);
		if(undisplayed){
			self.displayMenu();
		}
	};
	this.current_action = "Loading class frame from server";
	this.client.getEmptyFrame(cls, initcon, failcand);
}

/**
 * Sets up the list of entity classes for the collection
 */
DacuraConsole.prototype.initCollectionEntityClasses = function(){
	jQuery('#' + this.HTMLID + " select.context-entityclass-picker").html("");
	var self = this;
	var html = "<option value=''>Select a Type</option>";
	var ecs = this.getEntityClasses();
	if(ecs.length > 1){
		for(var i = 0; i<ecs.length; i++){
			if(ecs[i].id == "owl:Nothing") continue;
			var sel = (ecs[i]['id'] == this.context.entityclass ? " selected" : "");
			var lbl = (typeof ecs[i].label == 'object' ?  ecs[i].label.data : ecs[i].label);
			if(!lbl) lbl = ecs[i].id;
			html += "<option" + sel + " value='" + ecs[i].class + "'>" + lbl + "</option>"; 
		}
		var shtml = "<select class='context-entityclass-picker'>" + html + "</select>";
		jQuery('#' + this.HTMLID + " .context-entityclass-picker-holder").html(shtml);
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
 * Initialises the list of available properties / entities in the extra screen 
 * @param frame - frame object including the properties
 */
DacuraConsole.prototype.initEntityPropertySelector = function(frame){
	if(typeof frame == "object" && size(frame) && frame.length == 0){
		frame = frame[this.client.getMainGraphURL()];
	}	
	if(frame.length == 0){
		jQuery('#' + this.HTMLID + " .console-candidate-header .entity-properties .entityproperty-picker").hide();				
		jQuery('#' + this.HTMLID + " .console-candidate-header .entity-properties .entityproperty-empty").show();
		return;
	}
	else {
		jQuery('#' + this.HTMLID + " .console-candidate-header .entity-properties .entityproperty-picker").show();				
		jQuery('#' + this.HTMLID + " .console-candidate-header .entity-properties .entityproperty-empty").hide();		
	}

	jQuery('#' + this.HTMLID + " select.context-entityproperty-picker").html("");
	if(!this.context.collection) return;
	this.entity_property_count = frame.length;
	var col = this.client.collections[this.context.collection];
	var self = this;
	var html = "<option value=''>Add New Property Value</option>";
	for(var i = 0; i<frame.length; i++){
		var sel = (self.context.candidateproperty == frame[i].property) ? " selected" : "";
		var lab = (typeof frame[i].label == "object" ? frame[i].label.data :  frame[i].property.after("#"));
		html += "<option" + sel + " value='" + frame[i].property + "'>" + lab + "</option>";
	}
	var shtml = "<select class='context-entityproperty-picker'>" + html + "</select>";
	jQuery('#' + self.HTMLID + " .entityproperty-picker .context-select-holder").html(shtml);
	jQuery('#' + self.HTMLID + " select.context-entityproperty-picker").select2({
		  placeholder: "Add New Property",
		  allowClear: true,
		  minimumResultsForSearch: 10		  
	}).on('change', function(){
		self.context.candidateproperty = this.value;
		if(this.value && this.value.length && (!self.propertyIsDisplayed(this.value))){
			jQuery('#' + self.HTMLID + " .entity-properties .context-new-property").show();
		}	
		else {
			jQuery('#' + self.HTMLID + " .entity-properties .context-new-property").hide();
		}
	});
}

/**
 * Called when a candidate is selected (for generating the candidate property list
 * @param cb callback success function
 */
DacuraConsole.prototype.initCandidateContext = function(undisplayed){
	this.removeFrameviewer();
	var self = this;
	var loadCandidate = function(frames){
		self.initFrameViewer(frames);
		var initcon = function(cand){
			self.current_candidate = cand;
			//self.initCandidatePropertySelector(cand.getProperties());
			self.showMenu(undisplayed);
		};
		var failcand = function(title, msg, extra){
			self.showResult("error", title, msg, extra);
			if(undisplayed){
				self.displayMenu();
			}
		};
		self.current_action = "Loading class Candidate from server";
		self.client.get("candidate", self.context.candidate, initcon, failcand);
	};
	this.initCollectionCandidates();
	this.initEntityContext(false, loadCandidate, undisplayed);
}

/**
 * Initialises the list of available properties for a candidate
 * @param props array of properties to be included
 */
DacuraConsole.prototype.initCandidatePropertySelector = function(props){
	jQuery('#' + this.HTMLID + " select.context-candidateproperty-picker").html("");
	if(!this.context.collection) return;
	if(props.length == 0){
		jQuery('#' + this.HTMLID + " .console-candidate-header .candidate-properties .candidateproperty-picker").hide();				
		jQuery('#' + this.HTMLID + " .console-candidate-header .candidate-properties .candidateproperty-empty").show();
		return;
	}
	else {
		jQuery('#' + this.HTMLID + " .console-candidate-header .candidate-properties .candidateproperty-picker").show();				
		jQuery('#' + this.HTMLID + " .console-candidate-header .candidate-properties .candidateproperty-empty").hide();		
	}
	var col = this.client.collections[this.context.collection];
	var self = this;
	var html = "";//<option value=''>" + props.length + </option>";
	var tota = 0;
	for(var i = 0; i<props.length; i++){
		var sel = (self.context.candidateproperty == props[i].id) ? " selected" : "";
		html += "<option" + sel + " value='" + props[i].id + "'>" + props[i].label + "</option>"; 	
		tota += props[i].count;
	}
	var msg = props.length + " properties (" + tota + " values)";
	if(tota == props.length){
		if(tota == 1){
			msg = "1 property";
		}
		else {
			msg = props.length + " properties";
		}
	}
	shtml = "<select class='context-candidateproperty-picker'><option value=''>" + msg + "</option>" + html + "</select>";
	jQuery('#' + self.HTMLID + " .candidateproperty-picker .context-select-holder").html(shtml);
	jQuery('#' + self.HTMLID + " select.context-candidateproperty-picker").select2({
		  placeholder: msg,
		  minimumResultsForSearch: 10,
		  allowClear: true
	}).on('change', function(){
		//self.context.candidateproperty = this.value;
		if(this.value && this.value.length && (!self.propertyIsDisplayed(this.value))){
			var prop = jQuery('#' + self.HTMLID + " select.context-candidateproperty-picker").val();
			self.addPropertyToDisplay(prop);
		}
	});
}

/**
 * Determines the visibility of top-bar context elements when in data tool
 */
DacuraConsole.prototype.setDataContextVisiblity = function(){
	var col = this.client.collections[this.context.collection];
	jQuery('#' + this.HTMLID + " .console-context .entityclass .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .candidate .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .entityproperty .context-element-item").hide();
	jQuery('#' + this.HTMLID + " .console-context .candidateproperty .context-element-item").hide();
	var ecs = this.getEntityClasses();
	if(ecs.length > 1){
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
		if((this.context.entityclass && col.entities[this.context.entityclass] && size(col.entities[this.context.entityclass]) > 0)){
			jQuery('#' + this.HTMLID + " .console-context .candidate .context-select-holder").show();
		}
		else if((!this.context.entityclass) && (size(col.entities) > 0)){
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
		else{
			jQuery('#' + this.HTMLID + " .console-context .candidate").hide();
		}
		if(this.entity_property_count > 0 && this.context.mode != "view"){
			jQuery('#' + this.HTMLID + " .entity-properties .context-select-holder").show();
			if(this.context.candidateproperty){
				jQuery('#' + this.HTMLID + " .entity-properties .context-new-property").show();
			}
			else {
				jQuery('#' + this.HTMLID + " .entity-properties .context-new-property").hide();				
			}
		}
		else if(this.entity_property_count == 0 && this.context.entityclass) {
			jQuery('#' + this.HTMLID + " .console-context .entityproperty .context-empty").show();
		}
		jQuery('#' + this.HTMLID + " .console-context .entityproperty").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-context .entityclass .context-empty").show();
	}
	if(this.context.mode == "view"){
		jQuery('#' + this.HTMLID + " .console-context .entityclass").show();
	}
	else {
		jQuery('#' + this.HTMLID + " .console-context .entityclass").hide();
	}
	if(this.pageHasFactoids() && this.context.entityclass){
		this.showHarvester();
	}
	else {
		this.hideHarvester();
	}
}

DacuraConsole.prototype.getEntityClasses = function(showall){
	var col = this.client.collections[this.context.collection];
	if(showall){
		if(col){
			 return col.entity_classes;
		}
	}
	else {
		return this.client.getEntityClasses();
	}
}

DacuraConsole.prototype.getFullCandidatePropertyList = function(cand, frames){
	var props = cand.getProperties();
	var taken = [];
	for(var i=0; i<props.length; i++){
		taken.push(props[i].id);
	}
	if(typeof frames == "object" && !frames.length && size(frames)){
		if(typeof frames[this.client.getMainGraphURL()] != "undefined"){
			frames  = frames[this.client.getMainGraphURL()];
		}
		else {
			alert("bad frame structure - can't get candidate property list");
			jpr(frames);
		}
	}
	for(var i=0; i<frames.length; i++){
		if(taken.indexOf(frames[i].property) == -1){
			var lab = (typeof frames[i].label != "undefined" ? frames[i].label.data : frames[i].property.substring(frames[i].property.lastIndexOf('#') + 1));
			props.push({id: frames[i].property, label: lab, count: 0});
		}
	}
	if(props.length){
		props.sort(comparePropertiesByCount);
	}
	return props;
};

/*
 * Same as above only specifically for create
 */
DacuraConsole.prototype.displayNewCandidateForm = function(def){
	this.loadNewCandidateToHTML(def);
	this.setSubmitButtonLabels("candidate");
	jQuery('#' + this.HTMLID + " .console-extra .candidate-edit-id input").removeAttr('disabled');
	jQuery('#' + this.HTMLID + " .console-extra .console-candidate-header").show();
	jQuery('#' + this.HTMLID + " .console-extra .console-edit-candidate").show();
	jQuery('#' + this.HTMLID + " .console-extra .candidate-createonly").show();
	jQuery('#' + this.HTMLID + " .console-extra .console-extra-buttons").show();
	jQuery('#' + this.HTMLID + " .console-extra .console-view-candidate").hide();
	jQuery('#' + this.HTMLID + " .console-extra .nocreate").hide();				
	jQuery('#' + this.HTMLID + " .console-extra .console-context-full").show();
	this.setMetadataVisibility(this.context.mode);
	jQuery('#' + this.HTMLID + " .console-extra .metadata-viewer").show();
	this.showing_extra = {mode: this.context.mode, type: "candidate"};
}

/* updates the display to add properties to them */
//for edit we need to figure out whether the properties are extant or new...
DacuraConsole.prototype.addPropertyToDisplay = function(prop, isnew){
	if(typeof prop == "string" && prop.length){
		if(this.propertyIsDisplayed(prop)){
			alert(prop + " is already displayed");
		}
		else {
			if(!this.hasDisplayedProperties()){			
				//need to open the display
				jQuery("#" + this.frameviewerConfig.target).html("").show();			
				jQuery('#' + this.HTMLID + " .console-extra .console-context-full").show();	
			}
			var self = this;
			var failcprop = function(title, msg, extra){
				self.showResult("error", title, msg, extra);
			};
			var hasprop = self.context.candidate ? self.current_candidate.hasPropertyValue(prop) : false;
			if(hasprop && (this.context.mode == "view" || this.context.mode == "edit" || this.context.mode == "harvest")){
				var showcprop = function(pframe){
					var success = function(emframe){
						var mode = isnew ? "create" : self.context.mode;
						if(!self.frameviewer){
							self.initFrameViewer(emframe);
						}						
						self.frameviewer.draw(pframe, mode);
					}
					self.initEntityContext(false, success);
				}
				var showfprop = function(frames){
					var relframes = [];
					for(var i=0; i<frames.length; i++){
						if(frames[i].property == prop){
							relframes.push(frames[i]);
						}
					}
					var tsuccess = function(eframe){
						var mode = isnew ? "create" : self.context.mode;
						if(!self.frameviewer){
							self.initFrameViewer(eframe);
						}						
						self.frameviewer.draw(relframes, mode);
					}
					self.initEntityContext(false, tsuccess);
				}
				//fallback because multi-value property frame is borked
				var failfprop = function(title, msg, extra){
					self.client.getFilledFrame(self.context.candidate, showfprop, failcprop);					
				}
				if(isnew){
					this.client.getEmptyPropertyFrame(this.context.entityclass, prop, showcprop, failcprop);
				}
				else {
					this.client.getFilledPropertyFrame(this.context.candidate, prop, showcprop, failfprop);					
				}
			}
			else if(this.context.mode == "create" || !hasprop){
				var success = function(eframe){
					if(!self.frameviewer){
						self.initFrameViewer(eframe);
					}
					var showcprop = function(pframe){
						self.frameviewer.draw(pframe, "create");
					}
					self.client.getEmptyPropertyFrame(self.context.entityclass, prop, showcprop, failcprop);
				}
				this.initEntityContext(false, success);
			}
		}
	}
}

DacuraConsole.prototype.setDataImportTarget = function(foid){
	//need to fill in a select which contains 1 entity type 2 entity id [new] 3. 
	var col = this.client.collections[this.context.collection];
	var self = this;
	var harvests = foid.getHarvests();
	if(harvests){
		this.context.candidateproperty = harvests;
	}
	var html = "<option value=''>Select a Type</option>";
	var ecs = self.getEntityClasses();
	if(ecs.length > 1){
		for(var i = 0; i<ecs.length; i++){
			if(ecs[i].id == "owl:Nothing") continue;
			var sel = (ecs[i]['class'] == this.context.entityclass ? " selected" : "");
			var lbl = (typeof ecs[i].label == 'object' ?  ecs[i].label.data : ecs[i].label);
			if(!lbl) lbl = ecs[i].id;
			html += "<option" + sel + " value='" + ecs.class + "'>" + lbl + "</option>"; 
		}
		var shtml = "<select class='entityclass-picker'>" + html + "</select>";
		jQuery('#' + this.HTMLID + " .factoid-data-import.entity-select-holder").html(shtml);
		jQuery('#' + this.HTMLID + " select.entityclass-picker").select2({
			  placeholder: "Select a Type",
			  minimumResultsForSearch: 10,
			  allowClear: true
		}).on('change', function(){
			if(this.value){
				var f = function(frame){
					self.initDataImportPropertySelector(frame);
				}
				self.initDataImportCollectionSelector(this.value);
				self.initEntityContext(this.value, f);
			}
			else {
				this.initDataImportCollectionSelector(false);
				self.removeDataImportPropertySelector();
			}
		});
	}
	this.initDataImportCollectionSelector(false);
}

DacuraConsole.prototype.readDataImportTarget = function(){
	var it = {};
	var ec = jQuery('#' + this.HTMLID + " select.entityclass-picker").val();
	var c =  jQuery('#' + this.HTMLID + " select.candidate-picker").val();
	var cp = jQuery('#' + this.HTMLID + " select.property-picker").val();
	it.entityclass = (ec && ec.length ? ec : false);
	it.candidate = (c && c.length ? c : false);
	it.candidateproperty = (cp && cp.length ? cp : false);
	return it;
}


DacuraConsole.prototype.removeDataImportPropertySelector = function(){
	jQuery('#' + this.HTMLID + " .factoid-data-import.property-select-holder").html("");
}

DacuraConsole.prototype.initDataImportCollectionSelector = function(cls){
	var html = this.getCandidateListHTML(cls);
	if(html){
		var shtml = "<select class='candidate-picker'>" + html + "</select>";
		jQuery('#' + this.HTMLID + " .factoid-data-import.candidate-select-holder").html(shtml);
		jQuery('#' + this.HTMLID + " select.candidate-picker").select2({
			  placeholder: "Select a Candidate",
			  minimumResultsForSearch: 10,
			  allowClear: true
		}).on('change.select2', function(){
			//alert("changed data import candidate to " + this.value)
		});
	}	
}

DacuraConsole.prototype.getDataImportPropertySelectorHTML = function(frame, val){
	var html = "<option value=''>Choose Import Property</option>";
	for(var i = 0; i<frame.length; i++){
		var sel = (val && (val == frame[i].property) ? " selected" : "");
		var lab = (typeof frame[i].label == "object" ? frame[i].label.data :  frame[i].property.after("#"));
		html += "<option" + sel + " value='" + frame[i].property + "'>" + lab + "</option>";
	}
	var shtml = "<select class='property-picker'>" + html + "</select>";
	return shtml;
} 

DacuraConsole.prototype.initDataImportPropertySelector = function(frame){
	if(!this.context || !this.context.collection){
		return
	}
	var self = this;
	var shtml = this.getDataImportPropertySelectorHTML(frame, false);
	jQuery('#' + this.HTMLID + " .factoid-data-import.property-select-holder").html(shtml);
	jQuery('#' + self.HTMLID + " select.property-picker").select2({
		  placeholder: "Choose Import Property",
		  allowClear: true,
		  minimumResultsForSearch: 10		  
	}).on('change', function(){
		//alert(this.value);
	});
};



/* Data tool screen initialisation */

/**
 * Sets up the list of candidates for the collection
 */
DacuraConsole.prototype.initCollectionCandidates = function(){
	var self = this;
	var html = this.getCandidateListHTML(this.context.entityclass);
	if(html){
		var shtml = "<select class='context-candidate-picker'>" + html + "</select>";
		jQuery('#' + this.HTMLID + " .context-candidate-picker-holder").html(shtml);
		jQuery('#' + this.HTMLID + " select.context-candidate-picker").select2({
			  placeholder: "Select a Candidate",
			  minimumResultsForSearch: 10,
			  allowClear: true
		}).on('change.select2', function(){
			self.userChangesContext({"candidate": this.value});
		});
	}
}

DacuraConsole.prototype.getCandidateListHTML = function(cls){
	var html = "<option value=''>Select a Candidate</option>";
	var cands = this.client.getCandidateList(cls);
	if(size(cands) > 1){
		for(var etype in cands){
			var ec = this.getEntityClassFromURL(etype);
			if(ec){
				if(ec.label && ec.label.data){
					var lab = ec.label.data;
				}
				else {
					lab = urlFragment(ec.class);
				}
			}
			else {
				var lab = urlFragment(etype);
			}
			html += "<optgroup label='" + lab + "'>";
			for(var candid in cands[etype]){
				var sel = (cands[etype][candid].id == this.context.candidate ? " selected" : "");
				html += "<option" + sel + " value='" + cands[etype][candid].id + "'>" + this.getEntityLabel(cands[etype][candid]) + "</option>"; 	
			}
			html += "</optgroup>";
		}						
	}
	else if(size(cands) == 1){
		for(var etype in cands){
			for(var candid in cands[etype]){
				var sel = (cands[etype][candid].id == this.context.candidate ? " selected" : "");
				html += "<option" + sel + " value='" + cands[etype][candid].id + "'>" + this.getEntityLabel(cands[etype][candid]) + "</option>"; 	
			}
		}
	}
	else {
		return false;
	}
	return html;
}