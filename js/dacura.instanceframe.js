/*
 * Represents the frame for an instance of an entity type in single graph...
 */

function DacuraInstanceFrame(dcf, nid) {	
	this.class_frame = dcf;
	this.cls = dcf.cls;
	this.nodeid = (nid ? nid : "_:");
    this.dfr = new DacuraFrameReader();
}


DacuraInstanceFrame.prototype.createFrameFromArchetype = function(archetype, domid, suppress_cardinality){
	var fcat = this.dfr.getFrameCategory(archetype);
	if(!this.dfr.isDataFrame(archetype, fcat)){
		alert("not data frame" + fcat);
		return false;
	}
	var nframe = jQuery.extend(true, {}, archetype);
	if(domid){
		nframe.domainValue = domid;
	}
	this.resetBNIDs(nframe);
	if(fcat == "object"){
		this.attachArchetypeFrames(nframe.frame, archetype.frame);
		if(!suppress_cardinality){
			this.applyCardinalityConstraintsToFrames(nframe.frame);
		}
	}
	nframe.archetype = jQuery.extend(true, {}, archetype);
	return nframe;
}


/**
 * Attaches archetype frames (unfilled, bog-standard frame) to filled frames 
 */
DacuraInstanceFrame.prototype.attachArchetypeFrames = function(frames, archetypes){
	for(var p in frames){
		if(typeof archetypes[p] != "undefined"){
			var archetype = archetypes[p][0];
			for(var i=0; i<frames[p].length; i++){
				var elt = frames[p][i];
				frames[p][i].archetype = jQuery.extend(true, {}, archetype);
				if(frames[p][i].archetype.archetype) {
					alert("bad - embedded archetypes");
				}
				if (this.dfr.getFrameCategory(elt) == "object" && !isEmpty(elt.frame)){
					this.attachArchetypeFrames(elt.frame, archetype.frame);
				}
			}
		}
		else {
			alert("No archetype for " + p);
		}
	}
}

/**
 * Attaches archetype frames (unfilled, bog-standard frame) to filled frames 
 */
DacuraInstanceFrame.prototype.includeArchetypes = DacuraInstanceFrame.prototype.attachArchetypeFrames;


DacuraInstanceFrame.prototype.createFrameFromPath = function(path, domid){
	if(!path.length){
		alert("no path");
		return false;
	}
	var prop = path[0];
	if(typeof this.class_frame.frames[prop] != "object"){
		alert(prop + " is undefined");
		jpr(path);
		return false;
	}
	if(!this.class_frame.frames[prop].length){
		alert(prop + " is an object");
		return false;
	}
	var nframe = this.class_frame.frames[prop][0];
	domid = (domid ? domid : this.dfr.getFrameSubjectID(this.class_frame.frames));
	var nfval = this.createFrameFromArchetype(nframe, domid);
	return nfval;
}






/**
 * Returns the entity id of the object that the frames represent;
 */
DacuraInstanceFrame.prototype.getFrameEntityID = function(){
	return this.nodeid;
}


/* takes a simple array [frame, ...] of frames and converts it to the propertyid => [frame++, ....] internal format */
DacuraInstanceFrame.prototype.addFrames = function(frames){
	if(frames[0].domainValue){
		basent = frames[0].domainValue;
	}
	var iframes = this.indexPropertyFrames(frames, basent);
	for(var k in iframes){
		if(typeof this.frames[k] != "undefined"){
			this.frames[k] = this.frames[k].concat(iframes[k]);
		}
		else {
			this.frames[k] = iframes[k];
		}
	}
	this.applyCardinalityConstraintsToFrames(iframes);
	return iframes;	
}


/* Transforms the frame array into one indexed by property - for ease of subsequent processing */
/**
 * Produces a frame structure that is: 
 * { property_url: [array of property frames] }
 */
DacuraInstanceFrame.prototype.indexPropertyFrames = function(frames, basent, archetypes){
	archetypes = (archetypes ? archetypes : this.class_frame.frames);
	var bnid = this.class_frame.generateBNID();
	if(typeof frames != "object"){
		alert(frames + " passed - should be a frames object");
		return {};
	}
	if(typeof frames == "object" && !frames.length){
		return frames;
	}
	var frameslist = {};
	for (var i = 0; i < frames.length; i++) {
		var elt = frames[i];
		if(typeof elt != "object"){
			alert(elt + " frame: should be object " + i);
			jpr(frames);			
		}
		else if(!elt.property){
			alert("Illegal Frame: no property specified in " + i);
			jpr(elt);
		}
		else {
			var fcat = this.dfr.getFrameCategory(elt);
			if(fcat == "restriction"){
				continue;
			}
			if(typeof frameslist[elt.property] == "undefined"){
				frameslist[elt.property] = [];
			}
			if(archetypes && typeof archetypes[elt.property] != "undefined"){
				elt.archetype = archetypes[elt.property][0];
			}
			if(!elt.domainValue && basent){
				elt.domainValue = basent;
			}
			else if(!elt.domainValue){
				elt.domainValue = bnid;
			}
			if (fcat == "object"){
				var narchs = (elt.archetype ? elt.archetype.frame : false);
				elt.frame = this.indexPropertyFrames(elt.frame, false, narchs);
			}
			frameslist[elt.property].push(elt);
		}
	}
	return frameslist;
}

DacuraInstanceFrame.prototype.resetBNIDs = function(frame, rmap, isdeep){
    rmap = (rmap ? rmap : {});
    if(!isdeep && (!frame.domainValue || !frame.domainValue.length)){
    	frame.domainValue = "_:";
    }
    else if(isdeep) {
    	if(isBNID(frame.domainValue) && frame.domainValue != "_:"){
    		if(typeof rmap[frame.domainValue] == "undefined"){
    			rmap[frame.domainValue] = this.class_frame.generateBNID();
    		}
    		frame.domainValue = rmap[frame.domainValue];
    	}
    }
   	if(this.dfr.getFrameCategory(frame) == "object"){
		for(var prop in frame.frame){
			for(var i=0; i<frame.frame[prop].length; i++){
				this.resetBNIDs(frame.frame[prop][i], rmap, true);
			}
		}
    }
}

DacuraInstanceFrame.prototype.applyCardinalityConstraintsToFrames = function(frames, instance){
	for(var prop in frames){
		if(frames[prop].length == 0){
			delete(frames[prop]);
			continue;
		}
		var fframe = frames[prop][0];
		if(typeof fframe != "object"){
			alert("not object");
			jpr(frames[prop]);
		}
		if(this.dfr.hasCardinalityConstraint(fframe)){
			var min = this.dfr.hasMinCardinality(fframe.restriction);
			while(frames[prop].length < min){
				var cframe = this.createFrameFromArchetype(fframe.archetype, fframe.domainValue, true);
				//var cframe = jQuery.extend(true, {}, fframe.archetype);
				frames[prop].push(cframe);
			}
		}
		if(this.dfr.getFrameCategory(fframe) == "object"){
			this.applyCardinalityConstraintsToFrames(fframe.frame);
		}		   
	}
}
