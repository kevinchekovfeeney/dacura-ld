function DacuraModel(json, client){
	this.collection = json.collection_id;
	this.demand_id_token = (json.demand_id_token ? json.demand_id_token : false);
	this.dfr = new DacuraFrameReader();
	//this.ontologies = (typeof json.ontologies == "object" ? json.ontologies : false);
	this.graphs = {};
	for(var key in json.graphs){
		var ng = new DacuraGraph();
		ng.loadFromModel(json.graphs[key]);
		this.graphs[key] = ng;
	}
	this.mainGraph = this.getGraphInstanceURLFromID(json.mainGraph);
    this.annotationPredicate = "http://dacura.scss.tcd.ie/ontology/dacura#annotates";    
	this.entities = (typeof json.entities == "object" ? json.entities: false);
	this.frame_renderers = (typeof json.frame_renderers == "object" ? json.frame_renderers : []);
	this.client = client;
}

DacuraModel.prototype.getRequiredViewerLibs = function(){
	var libs = [];
	for(var i in this.frame_renderers){
		if(this.frame_renderers[i].file && libs.indexOf(this.frame_renderers[i].file) == -1){
			libs.push(this.frame_renderers[i].file);
		}
		if(this.frame_renderers[i].libs){
			for(var j = 0; j<this.frame_renderers[i].libs.length; j++){
				var nlib = this.frame_renderers[i].libs[j];
				if(libs.indexOf(nlib) == -1){
					libs.push(nlib);
				}
			}
		} 
	}
	return libs;
}

DacuraModel.prototype.getGraphInstanceURLFromID = function(gid){
	if(typeof this.graphs[gid] == "object"){
		return this.graphs[gid].instance;
	}
}

DacuraModel.prototype.getMainGraph = function(){
	var gid = this.getGraphIDFromInstanceURL(this.mainGraph);
	return this.graphs[gid];
}

DacuraModel.prototype.getMainGraphID = function(){
	var gid = this.getGraphIDFromInstanceURL(this.mainGraph);
	return gid;
}

DacuraModel.prototype.getGraphIDFromInstanceURL = function(gurl){
	for(var gid in this.graphs){
		if(this.graphs[gid].instance == gurl){
			return gid;
		}
	}
	return false;
}

DacuraModel.prototype.isMainGraphInstanceURL = function(gurl){
	return gurl == this.mainGraph;
	//var mg = this.graphs[this.mainGraph];
	//return (mg && (mg.instance == gurl));
}

DacuraModel.prototype.getAnnotationTargetGraph = function(){
	for(var gid in this.graphs){
		g = this.graphs[gid];
		if(g.entity_classes){
			for(var i=0; i<g.entity_classes.length; i++){
				var ec = g.entity_classes[i];
				if(ec.annotation && !ec.abstract){
					return g;
				}
			}
		}
	}
	return this.getMainGraph();
}


DacuraModel.prototype.getAnnotationTargetClass = function(targetGraph){
	var ecs = targetGraph.entity_classes;
	for(var i=0; i<ecs.length; i++){
		if(ecs[i].annotation && ! ecs[i].abstract){
			return ecs[i].class
		}
	}
	return false;
}


DacuraModel.prototype.testLDO = function(success, fail, ldo, ec, candid){
	ldo['rdf:type'] = ec;
	var self = this;
	if(typeof this.pending_tests == "undefined"){
		this.pending_tests = 0; 
	}
	this.pending_tests++;
	var nsuccess = function(res){
		self.pending_tests--;
		if(success){ success(res) };
	}
	var nfail = function(res){
		self.pending_tests--;
		if(fail){ fail(res) };
	}
	if(candid){
		this.client.updateCandidate(candid, ldo, false, nsuccess, nfail, true);		
	}
	else {
		this.client.createCandidate(false, ldo, false, nsuccess, nfail, true);
	}
}

DacuraModel.prototype.import = function(factoids, config){
	var cls = firstKey(this.entities);
	var self = this;
	if(config && config.mode && config.mode == "model"){
		
	}
	else {
		this.client.getEmptyFrame(cls, false, function(frame){
			if(self.dfr.isMultigraph(frame.result)){
				for(var gurl in frame.result){
					var cfs = self.dfr.getGraphClassFrames(frame.result, gurl);
					if(size(cfs)){
						if(self.isMainGraphInstanceURL(gurl)){
							var cls = firstKey(cfs);
						}
						for(var k in cfs){
							var gid = self.getGraphIDFromInstanceURL(gurl);
							if(gid){
								self.graphs[gid].addClassFrame(k, cfs[k], this);
							}
						}						
					}
				}
			}
			else {
				var cls = frame.result[0].domain;
				self.graphs[firstKey(self.graphs)].addClassFrame(cls, frame.result, this); 
			}
			self.importFactoidsAsEntityClass(factoids, cls, config);
		});
	}
}

DacuraModel.prototype.importFactoidsAsEntityClass = function(factoids, entity_class, config){
	var nframes = {};
	var self = this;
	var epr = new EntityProvenanceRecord(config.location, config.location_type, config.agent, config.user);
	var newentid = lastURLBit(config.location).toLowerCase();
	alert("new id: " + newentid);
	self.all_launched = false;
	var testCallback = function(handler, ldo){
		
		var nhandler = function(dresult){
			handler(dresult);
			if(self.all_launched && self.pending_tests == 0){
				self.client.scanner.generateStats();
				jpr(self.client.scanner.stats);
				var fstats = {};
				for(var gurl in nframes){
					var onegs = self.dfr.getFrameStatistics(nframes[gurl]);
					fstats[gurl] = onegs;
				}
				jpr(fstats);
				var ldo = self.extract(nframes, entity_class, epr);
				var dealwitherrs = function(sres){
					if(size(ldo.reject)){
						alert("dealing with errors");
						jpr(ldo.reject);

						//self.client.update("");
					}
					else {
						alert("no errors");
					}
				}
				var failcreate = function(something){
					alert("failed to create");
				}
				if(size(ldo.accept)){
					self.client.createCandidate(newentid, ldo.accept, false, dealwitherrs, failcreate);
				}
				//var eldo = self.extract(nframes, entity_class, efilter);
				//jpr(eldo);
			}
		}
		self.testLDO(nhandler, nhandler, ldo, entity_class);
	}
	for(var i in factoids){
		var foid = factoids[i];
		if(!foid.parsed.datapoints){
			//alert(" factoid " + i + " missing datapoints");
			continue;
		}			
		else if(foid.parsed.datapoints.length == 0){
			alert(" factoid " + i + " has no datapoints");
			continue;
		}
		var foid_frames = this.importFactoidToFrames(foid, entity_class, testCallback);
		if(foid_frames && size(foid_frames)){
			var imported = this.mergeImportedFactoid(nframes, foid_frames); 
			if(imported && size(imported)){
				foid.frames = imported;
			}
		}
	}
	self.all_launched = true;
	return nframes;
}

DacuraModel.prototype.getClassFrame = function(cls){
	for(var gid in this.graphs){
		var g = this.graphs[gid];
		if(typeof g.class_frames[cls] !== "undefined"){
			return g.class_frames[cls];
		}	
	}
	return false;
	
}


/**
 * returns {gid: [path1, path2,...],,,
 */
DacuraModel.prototype.mapPropertyToFrame = function(property, entity_class, frames){
	var paths = [];
	var ec = this.getClassFrame(entity_class);
	if(!frames){
		paths = this.mapPropertyToFrame(property, entity_class, ec.frames); 
	}
	else {
		if(typeof frames[property] != "undefined"){
			paths.push(property);
		}
		for(var prop in frames){
			if(this.dfr.getFrameCategory(frames[prop][0]) == "object" && !this.dfr.isBoxedType(frames[prop][0])){
				var spaths = this.mapPropertyToFrame(property, entity_class, frames[prop][0].frame);
				if(spaths && spaths.length){
					paths.push(frames[prop][0].property);
					paths = paths.concat(spaths);
					break;
				}
			}			
		}
	}
	return paths;
}


//takes an atomic 'factoid' (web location) and tries to import it
DacuraModel.prototype.importFactoidToFrames = function(factoid, entity_class, testCallback){
	var prop = factoid.getHarvestsClass();
	if(!prop) {
		return false;
	}
	var map = this.mapPropertyToFrame(prop, entity_class);
	if(!map.length){
		//alert("no mapping found to " + prop);
		//
		return false;		
	}
	else {
		prop = map[0];
	}
	var factoidanot = new DacuraAnnotation();
	if(factoid.original.after){
		var fcdom = dacura.utils.tidyDOMFromHTML(factoid.original.after);
		if(fcdom && (x = jQuery(fcdom).html().trim())){
			factoidanot.setComment(x);
		}
	}
	var factframes = {};
	var anotframes = {};
	var provframes = {};
	var gmodel = this.getMainGraph();
	var emodel = gmodel.class_frames[entity_class];
	var instance = emodel.createInstance();
	for(var i = 0; i<factoid.parsed.datapoints.length; i++){
		var dp = factoid.parsed.datapoints[i];
		var dpanot = false;
		if(dp.date_type && dp.date_type.length){ //date type => "" | simple | range	
			dpanot = new DacuraAnnotation();
			dpanot.setDateRange(dp.date_from, dp.date_to);
		}
		if(dp.value_type == "range"){
			var fv = '[' + dp.value_from + "-" + dp.value_to + "]";//(parseInt(dp.value_from) + parseInt(dp.value_to))/2;	
		}	
		else {
			var fv = dp.value_from;
		}
		fv = fv.trim();
		if(fv.toLowerCase().substring(0, 8) == "inferred"){
			fv = fv.substring(8).trim();
			if(!dpanot) dpanot = new DacuraAnnotation();
			dpanot.addTag("inferred");
		}
		if(dp.value_type == "disputed" || dp.value_type == "uncertain"){
			if(!dpanot) dpanot = new DacuraAnnotation();
			dpanot.addTag(dp.value_type);			
		}
		var nframe = instance.createFrameFromPath(map);	
		if(!nframe){
			alert("failed to create frame for " + prop); 
			continue;
		}
		else {
			if(map.length > 1){
				this.dfr.stripFramesToPath(nframe, map);
			}
		}
		var ncat = this.dfr.getFrameCategory(nframe);
		this.importValueToFrame(fv, nframe, map, testCallback);
		if(ncat == "object"){
			var fid = this.dfr.getFrameSubjectID(nframe.frame);
			if(!fid){
				continue;
			}
			if(dpanot){
				dpanot.setTargetFromFrame(nframe, this);	
				if(factoid.parsed.datapoints.length == 1){
					dpanot.setComment(factoidanot.getComment());
					factoidanot = dpanot;
				}
				else {
					factoidanot.setTargetFromFrame(nframe, this);	
					var nframes = this.createAnnotationFrames(dpanot);
					for(var p in nframes){
						if(typeof anotframes[p] == "undefined"){
							anotframes[p] = [];
						}
						anotframes[p] = anotframes[p].concat(nframes[p]); 
					}
				}
				//if the length is 1, we just copy it to the factoidanot
			}
			else {
				factoidanot.setTargetFromFrame(nframe, this);	
			}
		}
		if(typeof(factframes[prop]) == "undefined"){
			factframes[prop] = [nframe];
		}
		else {
			factframes[prop].push(nframe);
		}
	}
	if(factoidanot.hasContent() && !factoidanot.hasTarget()){
		//alert("annotation has content but no target");
		factoidanot.addTarget("_:");
		//jpr(factoidanot);
	}
	if(factoidanot.hasContent() && factoidanot.hasTarget()){
		var nframes = this.createAnnotationFrames(factoidanot);
		for(var p in nframes){
			if(typeof anotframes[p] == "undefined"){
				anotframes[p] = [];
			}
			anotframes[p] = anotframes[p].concat(nframes[p]); 
		}
	}
	var rframes = {};
	var tg = this.getAnnotationTargetGraph();
	if(tg.instance == this.mainGraph && size(anotframes) && size(factframes)){
		rframes[tg.instance] = factframes.concat(anotframes);		
	}
	else {
		if(size(factframes)){
			rframes[this.mainGraph] = factframes;
		}
		if(size(anotframes)){
			rframes[tg.instance] = anotframes;
		}
	}
	if(size(rframes)){
		factoid.frames = rframes;				
	}
	return rframes;
}

DacuraModel.prototype.importValueToFrame = function(val, elt, map, testCallback){
    var fimport = this.dfr.importValueToFrame(val, elt, map, this);
    if(fimport){
    	var updjson = {};
        updjson[elt.property] = fimport;
	    if(testCallback && (!this.dfr.frameContainsErrors(elt))){
	    	this.dfr.addNotification(elt, "pending", "testing data", val);
	    	var self = this;
	    	var processTestResult = function(dresult){
	    		self.dfr.clearFrameNotifications(elt, "pending", "testing data");
	    		var melt = elt;
				self.setNotificationFromTestResult(melt, dresult);
	    	}
			testCallback(processTestResult, updjson);
			//send it off to the server 
	    }
	    else if(!this.dfr.frameContainsErrors(elt)){
    		this.dfr.addNotification(elt, "accept", "imported data");	    		
		}
    }
    return fimport;
}

DacuraModel.prototype.importValueToEntityReferenceFrame = function(val, elt){
	if(typeof val == "object" && val.length == 0) return;
	val = (typeof val == "object" ? val.shift() : val);
	var cid = urlFragment(elt.frame.class);
	if(cid == "Thing" || cid == "Nothing"){
		var entid = val;
	}
	else {
		var entid = this.getEntIDFromImportString(val, elt.frame);
	}
	if(entid){
		elt.frame.domainValue = entid;		
	}
	else {
		elt.frame.domainValue = val;		
		this.dfr.addNotification(elt, "error", "invalid data", "Imported value " + val + " is not a valid entity");
	}
	return elt.frame.domainValue;
};


DacuraModel.prototype.setNotificationFromTestResult = function(f, dresult){
	var res = (dresult.status && dresult.status != "pending" ? dresult.status : "error");
	var ldr = new LDResult(dresult);
	this.dfr.addNotification(f, res, "importing data", ldr.getResultMessage());
}


/*
 * Takes a set of existing frames and merges them with a new set - does object formation
 */
DacuraModel.prototype.mergeImportedFactoid = function(existing, newbies, map){
	map = (map ? map : {});
	var imported = {};
	imported[this.mainGraph] = {};
	var main = {};
	//need to add 
	for(var prop in newbies[this.mainGraph]){
		//first we merge internally to newbies....
		var merged = [];
		for(var i = 0; i< newbies[this.mainGraph][prop].length; i++){
			this.mergeFrames(merged, newbies[this.mainGraph][prop][i], map, existing, newbies, true);
		}
		if(typeof existing[this.mainGraph] == "undefined") existing[this.mainGraph] = {};
		if(typeof existing[this.mainGraph][prop] == "undefined") existing[this.mainGraph][prop] = [];
		for(var i = 0; i<merged.length; i++){
 			this.mergeFrames(existing[this.mainGraph][prop], merged[i], map, existing, newbies, true);
		}
 		imported[this.mainGraph][prop] = merged;		
	}
	for(var gurl in newbies){
		if(gurl == this.mainGraph) continue;
		if(size(map)){
			for(var xprop in newbies[gurl]){
				if(xprop == this.annotationPredicate){
					var targets = [];
					var deletes = [];
					for(var j = 0; j<newbies[gurl][xprop].length; j++){
						var targ = newbies[gurl][xprop][j].domainValue;
						if(typeof map[targ] != "undefined"){
							targ = map[targ];
							newbies[gurl][xprop][j].domainValue = targ;
						}
						if(targets.indexOf(targ) == -1){
							targets.push(targ);								
						}					
						else {
							deletes.push(j);
						}
					}
					for(var i = 0; i<deletes.length; i++){
						var delindx = deletes[i];
						newbies[gurl][xprop].splice(delindx, 1);
					}
					if(size(newbies[gurl][xprop]) == 0){
						delete(newbies[gurl][xprop]);
					}
					if(size(newbies[gurl]) == 0){
						delete(newbies[gurl]);
					}					
				}
			}
		}
		if(newbies[gurl]){
			imported[gurl] = newbies[gurl];
		}
		if(!existing[gurl]) existing[gurl] = {};
		for(var cprop in newbies[gurl]){
			if(!existing[gurl][cprop]) existing[gurl][cprop] = [];
			existing[gurl][cprop] = existing[gurl][cprop].concat(newbies[gurl][cprop]);
		}
	}
	return imported;
}



DacuraModel.prototype.canMergeObjects = function(frames, frameb, multi, newmulti){
	if(this.dfr.getFrameCategory(frameb) != "object"){
		//alert(this.getFrameCategory(frameb))
		return false;	
	}
	if(!frames.length) return false;
	framea = frames[0];
	//if(framea.property != frameb.property) return false;
	var index = false;
	for(var i =0; i<frames.length; i++){
		if(this.dfr.cardinalityAllowsMerge(frames[i], frameb)){
			if(this.annotationsAllowMerge(frames[i], frameb, multi, newmulti)){
				//if(!(this.frameContainsErrors(frames[i]) || this.frameContainsErrors(frameb))){
					return i;					
				//}
			}
		}
	}
	return false;
};


//two objects with different annotations can't be merged...
DacuraModel.prototype.annotationsAllowMerge = function(framea, frameb, multi, nmulti){
	var sid = this.dfr.getFrameSubjectID(framea.frame);
	var anots = this.getAnnotationsForNode(sid, multi);
	var banots = this.getAnnotationsForNode(sid, nmulti);
	var nsid = this.dfr.getFrameSubjectID(frameb.frame);
	var canots = this.getAnnotationsForNode(nsid, nmulti);
	if(canots && size(canots) || banots && size(banots) || anots && size(anots)){
		if(size(canots) && (size(banots) || size(anots))){
			if(!size(anots)){
				return this.identicalAnnotations(canots, banots);
			}
			else {
				alert("houston problem");
			}
		}
		else {
			return false;
		}
	}
	return true;
}

DacuraModel.prototype.identicalAnnotations = function(anots, banots){
	if(size(anots) != size(banots)) return false;
	for(var gurl in anots){
		if(!banots[gurl] || size(anots[gurl]) != size(banots[gurl])){
			return false;
		}
		for(var k in anots[gurl]){
			if(typeof banots[gurl][k] == "undefined"){
				return false;
			}
		}
	}
	return true;
}

DacuraModel.prototype.mergeFrames = function(frames, frameb, map, existing, newbies, internal){
	map = (map ? map : {});
	var index = this.canMergeObjects(frames, frameb, existing, newbies);
	if(index !== false && frames[index]){
		var ndid = this.dfr.getFrameSubjectID(frames[index].frame);
		var odid = this.dfr.getFrameSubjectID(frameb.frame);
		if(odid != ndid){
			//alert("xxing " + odid + " => " + ndid);
			var xmap = {};
			xmap[odid] = ndid;
			this.dfr.changeDomainID(frameb, xmap);
			map[odid] = ndid;
		}
		if(true){ //was internal ???
			for(var prop in frameb.frame){
				for(var i = 0; i<frameb.frame[prop].length; i++){
					frameb.frame[prop].domainValue = ndid;
				}
				if(typeof frames[index].frame[prop] == "undefined"){
					frames[index].frame[prop] = frameb.frame[prop];
				}
				else {
					for(var i = 0; i<frameb.frame[prop].length; i++){
						this.mergeFrames(frames[index].frame[prop], frameb.frame[prop][i], map, existing);
					}
				}
			}
		}
		return map;
	}
	else {
		frames.push(frameb);
		return map;
	}
}



DacuraModel.prototype.getEntIDFromImportString = function(val, frame, model){
	var cls = frame.class;
	var gmodel = this.getMainGraph();
	var entity_classes = gmodel.entity_classes;
	for(var j = 0; j< entity_classes.length; j++){
		var ec = entity_classes[j];
		if(ec.class == cls){
			var kids = entity_classes[j].children;
			kids.push(cls);
			for(var k = 0; k < kids.length; k++){
				if(typeof this.entities[kids[k]] != "undefined" && typeof this.entities[kids[k]][val] != "undefined"){
					return val;
				}
			}
			for(var l = 0; l < kids.length; l++){
				for(var eid in this.entities[kids[l]]){
					var elab = this.getEntityLabel(this.entities[kids[l]][eid]);
					if( elab == val){
						return eid;
					}
				}
			}
			break;
		}
	}
	return false;
}

DacuraModel.prototype.attachCustomRenderer = function(frame, x){
	if(custom = this.hasCustomRenderer(frame.property)){
    	frame.renderer = custom;
	}
	else if(custom = this.hasCustomRenderer(frame.range)){
    	frame.renderer = custom;
	}
}


DacuraModel.prototype.hasCustomRenderer = function(type){
	if(typeof this.frame_renderers[type] == "object"){
		var cls = this.frame_renderers[type]['class'];
		var file = this.frame_renderers[type]['file'];
		if(cls){
			var args = this.frame_renderers[type]['args'];
			//var obj = window[cls](args);
			var obj = eval("new " + cls + "()");
			if(args){
				obj.init(args);
			}
			return obj;
		}
	}
	return false;
}


DacuraModel.prototype.init = function(){
	//alert("Model initialised");
}

DacuraModel.prototype.createAnnotationFrames = function(annotation){
	var targetGraph = this.getAnnotationTargetGraph(annotation);
	var ec = this.getAnnotationTargetClass(targetGraph);
	if(!ec){
		return;
	}
	var amodel = targetGraph.class_frames[ec];
	var imodel = amodel.createInstance();
	var cframes = amodel.frames;
	var aframes = {};
	var nanid = amodel.generateBNID();
	for(var prop in cframes){
		if(annotation.hasProperty(prop)){
			if(typeof aframes[prop] == "undefined"){
				aframes[prop] = [];
			}
			var pval = annotation.getPropertyValue(prop);
			if(pval && typeof pval == "object" && pval.length){
				for(var j = 0; j<pval.length; j++){
					var nframe = imodel.createFrameFromArchetype(cframes[prop][0], nanid);
					this.importValueToFrame(pval[j], nframe);
					aframes[prop].push(nframe);
				}
			}
			else if(pval){
				var nframe = imodel.createFrameFromArchetype(cframes[prop][0], nanid);
				this.importValueToFrame(pval, nframe);
				aframes[prop].push(nframe);
			}
		}					
	}
	if(typeof aframes[this.annotationPredicate] == "undefined"){
		aframes[this.annotationPredicate] = [];
	}
	var targets = annotation.getTargets();
	for(var i = 0; i<targets.length; i++){
		var nframe = imodel.createFrameFromArchetype(cframes[this.annotationPredicate][0], nanid);
		this.importValueToFrame(targets[i], nframe);
		aframes[this.annotationPredicate].push(nframe);
	}
	return aframes;	
}


DacuraModel.prototype.importAnnotationToFrame = function(fimport, annotation, fid){
	var targetGraph = this.getAnnotationTargetGraph(annotation);
	if(targetGraph.instance != this.mainGraph){
		var ecs = this.entity_classes[targetGraph];
		var aframes = {};
		for(var i=0; i<ecs.length; i++){
			if(ecs[i].annotation && ! ecs[i].abstract){
				var cframes = this.getFramesWithClass(this.archetypes[targetGraph], ecs[i].class);
				for(var prop in cframes){
					if(annotation.hasProperty(prop)){
						if(typeof aframes[prop] == "undefined"){
							aframes[prop] = [];
						}
						var pval = annotation.getPropertyValue(prop);
						if(pval && typeof pval == "object" && pval.length){
							for(var j = 0; j<pval.length; j++){
								var nframe = jQuery.extend(true, {}, cframes[prop][0]);
								this.importValueToFrame(pval[j], nframe);
								aframes[prop].push(nframe);
							}
						}
						else if(pval){
							var nframe = jQuery.extend(true, {}, cframes[prop][0]);
							this.importValueToFrame(pval, nframe);
							aframes[prop].push(nframe);
						}
					}					
				}
				if(size(aframes)){
					if(typeof aframes[this.annotationPredicate] == "undefined"){
						aframes[this.annotationPredicate] = [];
						//alert("adding " + prop + " to " + fid);
					}
					var nframe = jQuery.extend(true, {}, cframes[this.annotationPredicate][0]);
					this.importValueToFrame(fid, nframe);
					aframes[this.annotationPredicate].push(nframe);
				}
			}
		}
		if(size(aframes)){
			fimport[targetGraph] = aframes;
		}
		
	}
	else {
		//we have to explicitly include the forward link with the object...
	}
}




DacuraModel.prototype.setFrameProvenance = function(elt, prov){
	elt.provenance = prov;
}


DacuraModel.prototype.getEntityLabel = function(ec){
	if(ec.meta && ec.meta.label){
		return ec.meta.label;
	}
	else if(ec.id){
		return ec.id;
	}		
}

DacuraModel.prototype.getEntityClassLabel = function(cls, gid){
	if(this.multigraph){
		if(gid && typeof this.entity_classes[gid] == "object"){
			var ec = this.entity_classes[gid][cls];
		}
		else if(typeof this.entity_classes[this.mainGraph] == "object"){
			var ec = this.entity_classes[this.mainGraph][cls];
		}
	}
	else {
	    var ec = this.entity_classes[cls];
	}
	if(ec){
		if(ec.label && ec.label.data){
			var lab = ec.label.data;
		}
		else {
			var lab = urlFragment(ec.class);
		}
	}
	if(!lab){
		var lab = urlFragment(cls);
	}
	return lab;
}



/* annotations */

DacuraModel.prototype.frameHasAnnotation = function(frame){
	var s = this.getFrameSubjectID(frame.frame);
	if(s){
		var x = this.getAnnotationsForNode(s);
		if(x && size(x)) {
			return x;
		}
		else {
			//alert("none for " + s);
		}
		return false;
	}
	else {
		var x = false;
	}
	return x;
}

DacuraModel.prototype.getAnnotationsForNode = function(nodeid, frames){
	frames = (frames ? frames: this.frames);
	//alert("no annotation for " + nodeid);
	var tg = this.getAnnotationTargetGraph();
	return this.getFrameAnnotationObjects(frames, nodeid, tg.instance);
}

//extracts data from the frames into an ld object
DacuraModel.prototype.extract = function (frames, cls, epr) {
	mgldo = {"accept": {}, "reject": {}};
	var self = this;
	for(var gurl in frames){
		var gframes = this.dfr.extractValid(frames[gurl], cls, false);
		if(size(gframes)){
			mgldo['accept'][gurl] = gframes;
		}
		var eframes = this.dfr.extractInvalid(frames[gurl], cls, false);
		
		if(size(eframes)){
			mgldo['reject'][gurl] = eframes;
		}
		//if(gurl == this.mainGraph && size(mgldo) && size(mgldo['accept'][gurl]) == 0){ //no content but annotations. 
		//	mgldo['accept'][gurl] = {"rdf:type": cls};
		//}		
		//else if(gurl == this.mainGraph && size(mgldo) && size(mgldo['reject'][gurl]) == 0){ //no content but annotations. 
		//	mgldo['reject'][gurl] = {"rdf:type": cls};
		//}		
	}
	var eprov = epr.getRDF();
	jpr(eprov);
	return mgldo;
}



/**
 * does class a subsume class b?
 * @param a string class in id format (ns:localid)
 * @param b string class in id format (ns:localid)
 * @return true if a subsumes b
 */
DacuraModel.prototype.classSubsumesClass = function(a, b, gurl){
	if (a == b){
		return true;
	}
	var ecs = this.collections[this.current_collection].entity_classes;
	if(gurl && typeof ecs == "object" && size(ecs) && ecs.length == 0){
		ecs = ecs[gurl];		
	}
	for(var i = 0; i< ecs.length; i++){
		if(ecs[i].class == a){
			var kids = ecs[i].children;
			if(kids && kids.length && kids.indexOf(b) !== -1){
				return true;
			}
			return false;
		}	
	}
	return false;
}


DacuraModel.prototype.getMainGraphConfig = function(){
	var col = this.collections[this.current_collection];
	if(typeof col.graphs['main'] != "undefined"){
		return col.graphs['main']; 
	}
	return false;
}

DacuraModel.prototype.getFrameRenderingMap = function(){
	return this.frame_renderers;
}


DacuraModel.prototype.getProvGraphConfig = function(){
	var col = this.collections[this.current_collection];
	if(typeof col.graphs['prov'] != "undefined"){
		return col.graphs['prov']; 
	}
	if(typeof col.graphs['provenance'] != "undefined"){
		return col.graphs['provenance']; 
	}
	return false;
}


DacuraModel.prototype.getGraphsToRedeployForOntologyUpdate = function(ontid){
	var cid = this.current_collection;	
	var graphs = {};
	for(var gid in this.collections[cid].graphs){
		var dacgraph = this.collections[cid].graphs[gid];
		if(typeof dacgraph.imports[ontid] == 'object' && typeof dacgraph.deploy != 'undefined'){
			graphs[gid] = dacgraph.deploy;
		}
	}	
	return graphs;	
}

/**
 * returns an object indexed by graph url of annotation objects that can be added to a frame
 */
DacuraModel.prototype.getFrameAnnotationObjects = function(iframes, domid, gid){
	var aobjs = {};
	if(this.multigraph){
		if(gid){
			var gframes = this.getGraphAnnotationObjects(iframes, gid, domid);
			if(size(gframes)){
				aobjs[gid] = gframes;
			}
		}
		else {
			for(var gurl in iframes){
				if(gurl == this.mainGraph) continue;
				var gframes = this.getGraphAnnotationObjects(iframes, gurl, domid);
				if(size(gframes)){
					aobjs[gurl] = gframes;
				}					
			}
    	}
	}
	return aobjs;
}

DacuraModel.prototype.getGraphAnnotationObjects = function(iframes, gid, domid){
	var rframes = {};
	if(iframes[gid]){
		if(domid){
			var objids = [];
			var fframes = this.getFramesWithAttributeValue(iframes[gid], {"property": this.annotationPredicate, "rangeValue": domid});
			for(var prop in fframes){
				for (var i = 0; i<fframes[prop].length; i++){
					var fsid = fframes[prop][i].domainValue;
					if(objids.indexOf(fsid) == -1){
						objids.push(fsid);
					}
				}
			}
			for(var j = 0; j < objids.length; j++){
				rframes[objids[j]] = this.getFramesWithDomainID(iframes[gid], objids[j], true);				
			}
		}
		else {
			var gframes = iframes[gid];
			if(size(gframes)){
				var gframes = this.divideFramesByClass(gframes);
				for(var objid in gframes){
					//alert("trying " + objid);
					if(typeof iframes[gid][objid] == "undefined"){
						//jpr(iframes[gid]);
					}
					else {
						//alert("def");
					}
					rframes[objid] = this.getFramesWithDomainID(iframes[gid], objid, true);
					//jpr(rframes[objid] );
				}
			}
		}
	}
	return rframes;
}


/**
 * Returns a object, indexed by property, containing the frames with a specific subject id
 */
DacuraModel.prototype.getFramesWithDomainID = function(framestruct, domid, recursive){
	return this.getFramesWithAttributeValue(framestruct, {"domainValue": domid}, recursive);
}

/**
 * Returns a object, indexed by property, containing the frames with a specific class
 */
DacuraModel.prototype.getFramesWithClass = function(framestruct, cls, recursive){
	var x = this.getFramesWithAttributeValue(framestruct, {"domain": cls}, recursive);
	return x;
}

/**
 * Returns na object, indexed by property, containing the frames with a specific class
 */
DacuraModel.prototype.divideFramesByClass = function(framestruct, recursive){
	return this.divideFramesByAttribute(framestruct, "domainValue", recursive);	
}

/**
 * Returns an object, indexed by property, containing the frames divided by a particular attribute key
 */
DacuraModel.prototype.divideFramesByAttribute = function(framestruct, key, recursive){
	var attvals = this.getFramesAttributeValues(framestruct, key, recursive);
	var dframes = {};
	for(var i = 0; i<attvals.length; i++){
		var propmatch = {};
		propmatch[key] = attvals[i];
		var fs = this.getFramesWithAttributeValue(framestruct, propmatch, recursive);
		if(fs && size(fs)){
			dframes[attvals[i]] = fs;
		}
	}
	return dframes;
}

/**
 * Returns an array, containing the values of frames with specific attribute key
 */
DacuraModel.prototype.getFramesAttributeValues = function(framestruct, key, recursive){
	var attvals = [];
	for(var prop in framestruct){
		if(typeof framestruct[prop] == "object" && framestruct[prop].length){
			for(var i = 0; i<framestruct[prop].length; i++){
				var frame = framestruct[prop][i];
				if(typeof frame[key] != "undefined"){
					if(attvals.indexOf(frame[key]) == -1){
						attvals.push(frame[key]);
					}
				}
			}
		}
		else if(typeof framestruct[prop] == 'object' && size(framestruct[prop])){
			alert("borken structure");
			var frame = framestruct[prop];
			if(typeof frame[key] != "undefined"){
				if(attvals.indexOf(frame[key]) == -1){
					attvals.push(frame[key]);
				}
			}
		}
	} 	
	return attvals;
}

DacuraModel.prototype.frameMatchesKey = function(frame, keys){
	for(var prop in keys){
		if(prop == "rangeValue"){
			if(!frame[prop] || keys[prop] != frame[prop].data){
				return false;
			}
		}
		else if(typeof frame[prop] != typeof keys[prop] || !frame[prop]){
			return false;
		}
		else if(keys[prop] != frame[prop]){
			return false;
		}
	}
	return true;
}

/**
 * Returns an object, indexed by property, containing the frames that have a particular value for a key
 */
DacuraModel.prototype.getFramesWithAttributeValue = function(framestruct, key_val, recursive){
	var dframes = {};
	for(var prop in framestruct){
		if(typeof framestruct[prop] == "object" && framestruct[prop].length){
			for(var i = 0; i<framestruct[prop].length; i++){
				var frame = framestruct[prop][i];
				if(this.frameMatchesKey(frame, key_val)){
					if(typeof dframes[prop] == "undefined"){
						dframes[prop] = [];
					}
					if(!this.arrayContainsFrame(dframes[prop], frame)){
						dframes[prop].push(frame);
					}
				}
				else if(recursive && this.getFrameCategory(frame) == "object"){
					nframes = this.getFramesWithAttribute(framestruct[prop][i].frame, firstKey(key_val), true);	
					for(var nprop in nframes){
						if(typeof dframes[nprop] == "undefined"){
							dframes[nprop] = nframes[nprop];
						}
						else {
							for(var k = 0; k<nframes[nprop].length; k++){
								if(nframes[nprop][k] && !this.arrayContainsFrame(dframes[nprop], nframes[nprop][k])){
									dframes[nprop].push(nframes[nprop][k]);
								}
							}
						}
					}
				}
			}
		}
		else if(typeof framestruct[prop] == 'object' && size(framestruct[prop])){
			alert("simple object - not array - halp!");
		}
	}
	return dframes;
}



DacuraModel.prototype.getFramesWithAttribute = function(framestruct, attr, recursive){
	if(framestruct[attr]){
		return framestruct[attr];
	}
	return false;
}

