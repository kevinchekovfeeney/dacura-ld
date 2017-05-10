/**
 * Object to gather together all the functions that operate on frames without changing the state of anything else 
 * frames in frames out. 
 * @returns
 */

function DacuraFrameReader(){}



/**
 * Determines the category of the frame: 
 * 	data | restriction | choice | reference | object 
 *  
 *  There are also logic frames (and | or | xor) but we haven't implemented them yet
 */
DacuraFrameReader.prototype.getFrameCategory = function(elt){
	if(typeof elt != "object"){
		return false;
	}
	if(elt.type == "datatypeProperty"){
		return "data";
	}
	else if(elt.type == "restriction"){
		return "restriction";
	}
	else if(elt.type == "objectProperty" && elt.frame && !isEmpty(elt.frame)){   		
    	if(elt.frame.type == "oneOf"){
            return "choice";
        }
		else if(elt.frame.type == "entity"){
			return "reference";
		}
		else {
			return "object";
		}
	}
	return false;
}

/**
 * Determines whether the passed frame is a 'box' type - an object that is simply a wrapper around a data value
 */
DacuraFrameReader.prototype.isBoxType = function(frame){
	if(frame.frame){
		return this.isBoxedType(frame.frame);
	}
	return false;
}

/**
 * Determines whether the passed frame is a 'boxed' type - a data value that is contained within a box
 * returns false or the actual type if it is boxed
 */
DacuraFrameReader.prototype.isBoxedType = function(frame){
	if(typeof frame == "object" && frame.length == 1){
		frame = frame[0];
	}
	if(typeof frame == 'object' && size(frame) == 1){//boxed types have a single property
		var prop = firstKey(frame);
		var sframe = frame[prop][0];
		if(sframe){
			if(this.isSimpleDataFrame(sframe)){
				return this.getFrameCategory(sframe);
			}
		}
	}
	return false;
}

/**
 * Determines whether the passed frame is a data frame or not (restriction | logic)
 */
DacuraFrameReader.prototype.isDataFrame = function(elt, fcat){
	fcat = (fcat ? fcat : this.getFrameCategory(elt));
	return (fcat == "object" || this.isSimpleDataFrame(elt, fcat));
}

/**
 * Determines whether the passed frame is a simple data frame or not (object | restriction | logic)
 */
DacuraFrameReader.prototype.isSimpleDataFrame = function(elt, fcat){
	fcat = (fcat ? fcat : this.getFrameCategory(elt));
	simples = ["data", "reference", "choice"];
	return simples.indexOf(fcat) != -1;
}


/**
 * Returns the subject id of the frame
 */
DacuraFrameReader.prototype.getFrameSubjectID = function(frame){
	if(typeof frame == "object" && frame.length){
		alert("whoops");
	}
	for(var prop in frame){
		if(typeof frame[prop] == "object" && frame[prop].length){
			return this.getSubjectIDFromFrameArray(frame[prop]);
		}
		else {
			alert("bad frame structure sent for subject " + prop);
			jpr(frame);
		}
	}
	return false;
}

/**
 * Returns the subject id of an array of frames
 */
DacuraFrameReader.prototype.getSubjectIDFromFrameArray = function(frames){
	if(typeof frames == "object" && frames.length){
		for(var i=0; i<frames.length; i++){
			if(frames[i].domainValue && frames[i].domainValue.length){
				return frames[i].domainValue;
			}
		}
	}
	else {
		alert("wrong type sent to get subject id from frame array: expected array");
		jpr(frames);
	}
	return false;
}

DacuraFrameReader.prototype.importValueToFrame = function(val, elt, map, model){
	model.attachCustomRenderer(elt);
    if(typeof val == 'object' && val.length == 0) return;
    if(val === false){
		alert("no value passed to import " + elt.property);
		return false;
	}
    var origval = (typeof val == 'object' ? val[0] : val);
    var fimport = false;
	if(this.hasCustomImport(elt)){
    	fimport = elt.renderer.import(val, elt);
    }
	else {
	    switch(this.getFrameCategory(elt)){
		    case "data": 
		    	fimport = this.importValueToDatatypeFrame(val, elt); 
		    	break;
		    case "choice": 
		    	fimport = this.importValueToOneOfFrame(val, elt); 
		    	break;
		    case "reference": 
		    	fimport = model.importValueToEntityReferenceFrame(val, elt); 
		    	break;
		    case "object": 
		    	fimport = this.importValueToObjectFrame(val, elt, map, model);
		    	break;
			default:
				alert("bad config for property: " + elt.property);
	    }
	}
	return fimport;
}


DacuraFrameReader.prototype.importValueToOneOfFrame = function(val, elt){
	if(typeof val == "object" && val.length == 0) return;
	var index = false;
	if(elt.frame && elt.frame.elements && elt.frame.elements.length){
		for(var i = 0; i<elt.frame.elements.length; i++){
			var ch = elt.frame.elements[i];
			if(ch.class == val){
				index = i;
				break;
			}
			else if(urlFragment(ch.class) == val){
				index = i;
			}
			else if(ch.label && ch.label.data){
				if(ch.label.data.toLowerCase() == val.toLowerCase()){
					index = i;
				}	
				else if(dacura.utils.sloppyMatch(ch.label.data.toLowerCase(), val.toLowerCase())){
					//alert(val + " is a sloppy match to " + ch.label.data);
					this.addNotification(elt, "correction", "Corrected Data", "Imported value " + val + " corrected to " + ch.label.data);
					index = i;
				}
			}
		}
	}
	else {
		alert("bad choice frame");
		jpr(elt.frame);
	}
	if(index !== false){
		elt.frame.domainValue = elt.frame.elements[index].class;
	}
	else {
		elt.frame.domainValue = val;
		this.addNotification(elt, "error", "invalid data", "Imported value " + val + " is not a valid choice");
	}
	return elt.frame.domainValue;
};

DacuraFrameReader.prototype.importValueToSubFrames = function(val, subframes, map, model){
	var fimport = {};
	if(typeof val == 'object' && val.length){
		for(var j = 0; j< subframes.length; j++){
			var subf = subframes[j];
			if(val[j]){
				fimport = this.importValueToFrame(val[j], subf, false, model);				
			}
			else {
				break;
			}
		}
		return fimport;
	}
	else {
		var subf = subframes[0];
		return this.importValueToFrame(val, subf, false, model);
	}	
}

DacuraFrameReader.prototype.importValueToObjectFrame = function(val, elt, map, model){
	var idata = {};
	if(this.isBoxType(elt)){
		idata = {};
		idata[firstKey(elt.frame)] = this.importValueToSubFrames(val, elt.frame[firstKey(elt.frame)], map, model);
	}
	else if(map && map.length){
		var nm = map.shift();
		if(map.length){
			var t = elt.frame[map[0]][0];
			if(t){
				var s = this.importValueToFrame(val, t, map, model);
				if(s){
					if(typeof idata[map[0]] == "undefined"){
						idata[map[0]] = [];
					}
					idata[map[0]].push(s);
				}
			}
		}
		map.unshift(nm);
	}
	else {
		for(var i in elt.frame){
			idata[i] = [];
			for(var j = 0; j< elt.frame[i].length; j++){
				var subf = elt.frame[i][j];
				var s = this.importValueToFrame(val, subf, map, model);
				if(s){
					idata[i].push(s);
				}
			}
		}
	}
	//if(typeof idata['rdf:type'] == "undefined"){
		idata['rdf:type'] = elt.range;
	//}
	return idata;
}

DacuraFrameReader.prototype.importValueToDatatypeFrame = function(val, elt){
	if(typeof val == "object" && val.length == 0) return;
	val = (typeof val == "object" ? val.shift() : val);
	var vstruct = { data: val };
	if(urlFragment(elt.range) == "string"){
		vstruct.lang = "en";
	}
	else {
		vstruct.type = elt.range;
	}
	elt.rangeValue = vstruct;
	return elt.rangeValue;
};

DacuraFrameReader.prototype.addNotification = function(frame, ntype, stype, msg){
	if(typeof frame.notifications == "undefined"){
		frame.notifications = {};
	}
	if(typeof frame.notifications[ntype] == "undefined"){
		frame.notifications[ntype] = {};
	}	
	if(typeof frame.notifications[ntype][stype] == "undefined"){
		frame.notifications[ntype][stype] = [];
	}
	if(typeof msg == "object"){
		msg = JSON.stringify(msg);
	}
	frame.notifications[ntype][stype].push(msg);
}

DacuraFrameReader.prototype.frameContainsErrors = function(frame, no_recursion){
	return this.frameContainsNotificationType(frame, "error", no_recursion);
}

DacuraFrameReader.prototype.frameContainsNotificationType = function(frame, nottype, no_recursion){
	if(typeof frame.notifications != "undefined"){
		if(typeof(frame.notifications[nottype]) != "undefined"){
			return true;
		}
	}
	if(this.getFrameCategory(frame) == "choice" || this.getFrameCategory(frame) == "entity" ){
		if(typeof frame.frame != "undefined" && typeof frame.frame.notifications != "undefined"){
			if(typeof(frame.frame.notifications[nottype]) != "undefined"){
				return true;
			}
		}
	}
	if(this.getFrameCategory(frame) == "object" && !no_recursion){
		for(var k in frame.frame){
			var sframes = frame.frame[k];
			for(var i = 0; i<sframes.length; i++){
				var sframeres = this.frameContainsNotificationType(sframes[i], nottype);
				if(sframeres) return true;
			}
		}
	}
	return false;
}


DacuraFrameReader.prototype.clearNotifications = function(frame, deep){
	this.clearFrameNotifications(frame);
	if(deep){
		if(frame.frame){
			if(typeof frame.frame == "object" && frame.frame.length){
				for(var i = 0; i<frame.frame.length; i++){
					this.clearNotifications(frame.frame[i]);
				}
			}
			else if(typeof frame.frame == "object"){
				if(frame.frame.type){
					this.clearNotifications(frame.frame);
				}
				else {
					for(var k in frame.frame){
						if(frame.frame[k].length){
							for(var j = 0; j<frame.frame[k].length; j++){
								this.clearNotifications(frame.frame[k][j]);
							}
						}
					}
				}
			}
		}
	}
} 

DacuraFrameReader.prototype.clearFrameNotifications = function(frame, ntype, stype, msg){
	if(!ntype){
		if(typeof frame.notifications != "undefined"){
			delete(frame['notifications']);
		}		
	}
	else if(!stype){
		if(typeof frame.notifications != "undefined" && typeof frame.notifications[ntype] != "undefined"){
			delete(frame.notifications[ntype]);
			if(size(frame.notifications) == 0){
				delete(frame['notifications']);				
			}
		}				
	}
	else if(!msg){
		if(typeof frame.notifications != "undefined" && typeof frame.notifications[ntype] != "undefined" && typeof frame.notifications[ntype][stype] != "undefined"){
			delete(frame.notifications[ntype][stype]);
			if(size(frame.notifications[ntype]) == 0){
				delete(frame.notifications[ntype]);				
				if(size(frame.notifications) == 0){
					delete(frame['notifications']);				
				}
			}
		}				
	}
	else {
		if(typeof msg == "object"){
			msg = JSON.stringify(msg);
		}
		if(typeof frame.notifications != "undefined" && typeof frame.notifications[ntype] != "undefined" && 
				typeof frame.notifications[ntype][stype] == "object" && frame.notifications[ntype][stype].indexOf(msg) != -1){
			frame.notifications[ntype][stype].splice(frame.notifications[ntype][stype].indexOf(msg), 1);
			if(frame.notifications[ntype][stype].length == 0){
				delete(frame.notifications[ntype][stype]);
				if(size(frame.notifications[ntype]) == 0){
					delete(frame.notifications[ntype]);				
					if(size(frame.notifications) == 0){
						delete(frame['notifications']);				
					}
				}	
			}
		}	
	}
}

/**
 * Determines what the mode (view|create|edit) of the passed frame is
 */
DacuraFrameReader.prototype.getFrameMode = function(frame){
	if(typeof frame.mode != "undefined"){
		return frame.mode;
	}
	var fcat = this.getFrameCategory(frame);
	if(fcat == "reference" || fcat == "choice"){
		if(typeof frame.frame.mode != "undefined"){
			return frame.frame.mode; 
		}
	}
	if(fcat == "object" && this.isBoxType(frame)){
		return this.getFrameMode(this.getBoxedFrame(frame));
	}
	return false;	
}


/**
 * Determines how many atomic units of data are in the frame (basic non-object values)
 */
DacuraFrameReader.prototype.getFrameAtomCount = function(frame){
	var atoms = 0;
	if(typeof frame == "object"){
		for(var j in frame){
			for(var k = 0; k < frame[j].length; k++){
				var scat = this.getFrameCategory(frame[j][k]);
				if(this.isSimpleDataFrame(frame[j][k], scat)){
					atoms++
				}
				else if(scat == "object"){
					atoms += this.getFrameAtomCount(frame[j][k].frame);
				}
			}
		}
	}
	return atoms;
}

DacuraFrameReader.prototype.isMultigraph = function(frames){
	if(typeof frames == "object" && frames.length){
		return false;
	}
	else if(typeof frames == "object" && size(frames)){
		return true
	}
	return false;
}

DacuraFrameReader.prototype.getGraphClassFrames = function(frames, gurl){
	var cf = {};
	if(typeof frames[gurl] == "object"){
		for(var i=0; i< frames[gurl].length; i++){
			var gc = frames[gurl][i].domain;
			if(typeof cf[gc] == "undefined"){
				cf[gc] = [];
			}
			cf[gc].push(frames[gurl][i]);
		}
	}
	return cf;
}

/**
 * returns a data frame that is contained within a box
 */
DacuraFrameReader.prototype.getBoxedFrame = function(frame){
	if(typeof frame == "object" && frame.length == 1){
		frame = frame[0];
	}
	if(typeof frame == 'object' && size(frame) == 1 && frame[firstKey(frame)].length){
		return frame[firstKey(frame)][0];
	}
	return false;
}

/**
 * Filters a frameset by recursively removing any frame that returns false when passed to the passed function
 */
DacuraFrameReader.prototype.filterFrames = function(frames, func){
	for(var prop in frames){
		for(var i = 0; i<frames[prop].length; i++){
			if(!func(frames[prop][i])){
				frames[prop].splice(i, 1); 
			}
			else {
				var fcat = this.getFrameCategory(frames[prop][i]);
				if(fcat == "object"){
					if(!this.filterFrames(frames[prop][i].frame, func)){
						frames[prop].splice(i, 1);						
					}
				}
			}
		}
		if(frames[prop].length == 0){
			delete(frames[prop]);
		}	
	}
	return size(frames);
}

/**
 * Removes all 'create' mode frames from the entity 
 * used when we switch from edit to view mode - create frames can't exist in view mode
 */
DacuraFrameReader.prototype.removeCreateFrames = function(frames){
	var frames = (frames ? frames : this.frames);
	var self = this;
	var filterfunc = function(frame){
		return self.getFrameMode(frame) != "create";
	}
	if(this.multigraph){
		for(var gurl in frames){
			this.filterFrames(frames[gurl], filterfunc);
		}
	}
	else {
		this.filterFrames(frames, filterfunc);
	}
}

DacuraFrameReader.prototype.sameFrame = function(frame1, frame2){
	if(!frame2){
		jpr(frame1);
	}
	if(frame1.property != frame2.property || frame1.domain != frame2.domain || 
		frame1.range != frame2.range || frame1.domainValue != frame2.domainValue) return false;
	var fcat = this.getFrameCategory(frame1);
	if(fcat != this.getFrameCategory(frame2)) return false;
	if(this.isSimpleDataFrame(frame1, fcat)){
		if(this.getSimpleDatatypeValue(frame1, fcat) != this.getSimpleDatatypeValue(frame2, fcat)) return false; 
	}
	else if(fcat == "object"){
		if(size(frame1.frame) != size(frame2.frame)){
			return false;
		}
		for(var prop in frame1.frame){
			if(typeof frame1.frame[prop] != typeof frame2.frame[prop]) return false;
			if(frame1.frame[prop].length != frame2.frame[prop].length) return false;  
			for(var i = 0; i<frame2.frame[prop].length; i++){
				if(!this.arrayContainsFrame(frame1.frame[prop], frame2.frame[prop][i])) return false;
			}
		}
	}
	return true;
}

DacuraFrameReader.prototype.arrayContainsFrame = function(framearray, frm){
	if(!frm){
		alert("passed missing frame to acf");
		return;
	}
	if(!framearray){
		alert("passed missing frame array to acf");			
	}
	for(var i=0; i<framearray.length; i++){
		if(this.sameFrame(frm, framearray[i])){
			return true;
		}
	}
	return false;
}



/* Cardinality related functions */

DacuraFrameReader.prototype.hasCardinalityConstraint = function(frame, recursive){
	if(frame && frame.restriction && typeof frame.restriction == "object") return true;
	if(recursive && this.getFrameCategory(frame) == "object"){
		for(var k in frame.frame){
			for(var i = 0; i<frame.frame[k].length; i++){
				if(this.hasCardinalityConstraint(frame.frame[k][i]), true){
					return true;
				}
			}
		}
	}
	return false;
}

DacuraFrameReader.prototype.hasMaxCardinality = function(restriction){
	var max = 0;
	if(typeof restriction.cardinality != "undefined"){
		max = restriction.cardinality;
	}
	else if(typeof restriction.maxCardinality != "undefined"){
		max = restriction.maxCardinality;
	}
	else if(typeof restriction.type != "undefined" && restriction.type == "and" && typeof restriction.operands == "object"){
		for(var i = 0; i<restriction.operands.length; i++){
			var nmax = this.hasMaxCardinality(restriction.operands[i]);
			if((nmax < max) || (max == 0 && nmax > 0)){
				max = nmax;
			}
		}
	}
	return max;
}


DacuraFrameReader.prototype.hasMinCardinality = function(restriction){
	var min = 0;
	if(typeof restriction.cardinality != "undefined"){
		min = restriction.cardinality;
	}
	else if(typeof restriction.minCardinality != "undefined"){
		min = restriction.minCardinality;
	}
	else if(typeof restriction.type != "undefined" && restriction.type == "and" && typeof restriction.operands == "object"){
		for(var i = 0; i<restriction.operands.length; i++){
			var nmin = this.hasMinCardinality(restriction.operands[i]);
			if((nmin < min) || (min == 0 && nmin > 0)){
				min = nmin;
			}
		}
	}
	return min;	
}

DacuraFrameReader.prototype.hasCustomDisplay = function(frame, mode){
	if(frame.renderer && typeof frame.renderer.display == 'function' && typeof frame.renderer.hasDisplay == "function" && frame.renderer.hasDisplay(mode)){
		return frame.renderer;
	}
	return false;
}

DacuraFrameReader.prototype.hasCustomExtractor = function(frame){
	if(frame.renderer && typeof frame.renderer.extract == 'function'){
		return frame.renderer;
	}
	return false;
}

DacuraFrameReader.prototype.hasCustomImport = function(frame){
	if(frame.renderer && typeof frame.renderer.import == 'function' ){
		return true;
	}
	return false;
}


DacuraFrameReader.prototype.changeDomainID = function(frame, map){
	for(var prop in frame.frame){	
		for(var i = 0; i<frame.frame[prop].length; i++){
			var sframe = frame.frame[prop][i]
			if(sframe && sframe.domainValue && typeof map[sframe.domainValue] != "undefined"){
				sframe.domainValue = map[sframe.domainValue]; 
			}
			var fcat = this.getFrameCategory(frame.frame[prop][i]);
			if(fcat == "object"){
				this.changeDomainID(frame.frame[prop][i].frame, map);
			}
			else if(fcat == "data"){
				if(sframe.rangeValue && typeof map[sframe.rangeValue.data] != "undefined"){
					sframe.rangeValue.data = map[sframe.rangeValue.data];
				}
			}
			else if(fcat == "choice" || fcat == "reference" && typeof map[sframe.frame.domainValue] != "undefined"){
				//sframe.frame.domainValue = map[sframe.frame.domainValue];				
			}
		}
	}
}

DacuraFrameReader.prototype.getFilledFrameCount = function(frames){
	var f = 0;
	for(var i=0; i<frames.length; i++){
		var nframe = frames[i];
		var fcat = this.getFrameCategory(nframe);
		if(fcat == "object"){
			return frames.length;
		}
		if(fcat == "data" && nframe.rangeValue && nframe.rangeValue.data){
			f++;
		}
		if((fcat == "choice" || fcat == "reference") && nframe.frame.domainValue){
			f++;
		}
	}
	return f;
}

DacuraFrameReader.prototype.getFrameStatistics = function(frames){
	var stats = {
		"errors": 0, "success": 0, "corrected": 0, "warnings": 0, "total": 0	
	};
	for(var prop in frames){
		for(var i=0; i<frames[prop].length; i++){
			got = false;
			if(this.frameContainsNotificationType(frames[prop][i], "correction")){
				stats.corrected++;got=true;
			}
			if(this.frameContainsErrors(frames[prop][i])){
				stats.errors++;got=true;
			}
			if(this.frameContainsNotificationType(frames[prop][i], "accept")){
				stats.success++;got=true;
			}
			if(this.frameContainsNotificationType(frames[prop][i], "warning")){
				stats.warnings++;got=true;
			}
			if(!got){
				alert("no frame data");
				jpr(frames[prop][i]);
			}
			stats.total++;
		}
	}
	return stats;
}

DacuraFrameReader.prototype.collateFrameStatistics = function(statsa, frames){
	var statsb = this.getFrameStatistics(frames);
	for(var t in statsa){
		statsa[t] += statsb[t];
	}
	return statsa;
}


DacuraFrameReader.prototype.cardinalityAllowsMerge = function(framea, frameb){
	for(var prop in framea.frame){
		var aframe = framea.frame[prop][0];
		if(this.hasCardinalityConstraint(aframe) && this.hasMaxCardinality(aframe.restriction)){
			if(this.getFilledFrameCount(framea.frame[prop]) != framea.frame[prop].length){
				alert("no card" + framea.frame[prop].length);
				return false;
			}
			if(this.getFilledFrameCount(framea.frame[prop]) >= this.hasMaxCardinality(aframe.restriction)) {
				return true;
			}
			
		}
	}
	return true;
}

DacuraFrameReader.prototype.getSimpleDatatypeValue = function(frame, fcat){
	fcat = (fcat ? fcat : this.getFrameCategory(frame)); 
	if(fcat == "data"){
		if(frame.rangeValue){
			if(frame.rangeValue.data){
				return frame.rangeValue.data;
			}
			return "";
		}
		return false;
	}
	else if(fcat == "choice" || fcat == "reference"){
		if(frame.frame && frame.frame.domainValue){
			return frame.frame.domainValue;
		}
		return false;
	}
}



DacuraFrameReader.prototype.LDOHasVal = function(vals, val){
	for(var i = 0; i<vals.length; i++){
		var uv = vals[i];
		if(typeof uv == "string" && val == uv){ 
			return true;
		}
		if(typeof uv == "object" && uv.data && typeof val == "object" && val.data && val.data == uv.data){
			return true;
		}
	}
	return false;
} 

DacuraFrameReader.prototype.arrayContainsJSONObject = function(arr, jsonol){
	for(var i = 0; i<arr.length; i++){
		var got = false;
		for(var k in jsonol){
			if(!(typeof jsonol[k] == typeof arr[i][k] && (arr[i][k] == jsonol[k]))){
				got = false;
				break;
			}
			else {
				got = true;
			}
		}
		if(got){
			return true;
		}
	}
	return false;
}


DacuraFrameReader.prototype.addLDO = function(ldoa, ldob){
	for(var pred in ldob){
		if(typeof ldoa[pred] == "undefined"){
			ldoa[pred] = ldob[pred];
		}
		else if(typeof ldoa[pred] == "string"){
			ldoa[pred] = [ldoa[pred]];
		}
		else if(typeof ldoa[pred] == "object" && isJSONObjectLiteral(ldoa[pred])){
			ldoa[pred] = [ldoa[pred]];
		}
		if(typeof ldoa[pred] == "object" && ldoa[pred].length){
			if(typeof ldob[pred] == "object" && ldob[pred].length){
				for(var i = 0; i<ldob[pred].length; i++){
					if(!this.LDOHasVal(ldoa[pred], ldob[pred][i])){
						ldoa[pred].push(ldob[pred][i]);
					}
				}
			}
			else {
				if(!this.LDOHasVal(ldoa[pred], ldob[pred])){
					ldoa[pred].push(ldob[pred]);
				}
			}
		}
		else if(typeof ldoa[pred] == "object" && typeof ldob[pred] == "object"){
			for(var nid in ldob[pred]){
				if(typeof ldoa[pred][nid] == "undefined"){
					ldoa[pred][nid] = ldob[pred][nid];
				}
				else {
					this.addLDO(ldoa[pred][nid], ldob[pred][nid]);					
				}
			}
		}
	}
}

DacuraFrameReader.prototype.extractInvalid = function(frames, cls, from_form){
	var self = this;
	filter = function (frame, depth){
		if(depth == 0){
        	include_subbranch = false;
		}
        if(self.frameContainsErrors(frame, true)){
        	include_subbranch = true;
        }
		if(include_subbranch){
			return true;
		}
        return self.frameContainsErrors(frame);
	}
	return this.extractFromIndexedFrames(frames, cls, filter, from_form);
}

DacuraFrameReader.prototype.extractValid = function(frames, cls, from_form){
	var self = this;
	filter = function (frame, depth){
		if(self.frameContainsErrors(frame)){
        	return false;
        }
        return true;
	}
	return this.extractFromIndexedFrames(frames, cls, filter, from_form);
}


DacuraFrameReader.prototype.extractFromIndexedFrames = function(frames, cls, filter, from_form, depth){
	depth = (depth ? depth : 0);
	if(typeof frames.contents != "undefined"){
		return frames.contents;
	}
	var ldo = {};
	var newvals = {};
	var subjid = false;
	var provs = {};
	for(var prop in frames){
		for(var i = 0; i<frames[prop].length; i++){
	        var tmp = frames[prop][i];
	        var fcat = this.getFrameCategory(tmp);
	        var domtype = (tmp.domain ? tmp.domain : cls);
	  		var domainid = tmp.domainValue;
	        var type = tmp.range;
	        if(entry = this.extractEntry(tmp, type, filter, from_form, depth)){
	        	if(typeof ldo[domainid] == "undefined"){		        		
	        		ldo[domainid] = {'rdf:type' : tmp.domain};
	        	}
	        	if((fcat == "object" || fcat == "reference" ) && typeof entry == "object"){ 
	        		if(typeof ldo[domainid][prop] == "undefined"){
	    				ldo[domainid][prop] = {};
	    			}
		        	for(var k in entry){
		        		if(typeof ldo[domainid][prop][k] == "undefined"){
			        		ldo[domainid][prop][k] = entry[k];
		        		}
		        		else {
		        			this.addLDO(ldo[domainid][prop][k], entry[k])
		        		}
	        		}
	        	}
	        	else if(fcat == "choice"){
	        		if(typeof ldo[domainid][prop] == "undefined"){
	    				ldo[domainid][prop] = [];
	    			}
	        		if(ldo[domainid][prop].indexOf(entry) == -1){
	        			ldo[domainid][prop].push(entry);
	        		}
	        	}
	        	else if(fcat == "data"){
	        		if(typeof ldo[domainid][prop] == "undefined"){
	    				ldo[domainid][prop] = [];
	    			}
	        		if(typeof entry != "object"){
		        		entry = (dacura.utils.isStringType(type) ? {lang: "en", data: entry} : {type: type, data: entry});	        			
	        		}
        			if(!this.arrayContainsJSONObject(ldo[domainid][prop], entry)){
	        			ldo[domainid][prop].push(entry)
        			}
	        	}
	        	else {
	        		alert("bad type");
	        		jpr(tmp);
	        	}
	  		}
	        else {
	        	//alert("no entry " + type);
	        	//jpr(tmp);
	        }
	  	}
	}
  	return ldo;
}

DacuraFrameReader.prototype.extractEntry = function(elt, type, filter, from_form, depth){
	if(typeof filter == "function"){
		if(!filter(elt, depth)){
			return false;
		}
	}
	var ex = this.hasCustomExtractor(elt);
	if(ex){
		var entry = ex.extract(elt, type, depth);
		alert("special extract");
		jpr(entry);
	}
	else if(typeof elt.contents !== 'undefined'){
		return elt.contents;//deals with reading from form inputs
	}	
	else if(!from_form){
	    switch(this.getFrameCategory(elt)){
	    case "data": 
	    	entry = elt.rangeValue;
	    	break;
	    case "choice": 
	    	entry = elt.frame.domainValue;
	    	break;
	    case "reference": 
	    	var entry = {};
	    	entry[elt.frame.domainValue] = {"rdf:type": elt.frame.class};
	    	return entry;
	    	break;
	    case "object": 
			var entry = this.extractFromIndexedFrames(elt.frame, type, filter, from_form, depth+1);
			break;
	    }
	}
	if(typeof entry == "object" && size(entry) == 0){
		//alert("empty entry");
		entry = false;
	}
	return entry;
}

DacuraFrameReader.prototype.stripFramesToPath = function(frame, path){
	var npath = jQuery.extend(true, [], path);
	var nval = npath.shift();
	for(var prop in frame.frame){
		if(prop != npath[0]){
			//alert("deleting " + prop + " not = " + path[0]);
			delete (frame.frame[prop]);
		}
		else if(npath.length > 1){
			for(var i = 0; i<frame.frame[prop].length; i++){
				this.stripFramesToPath(frame.frame[prop][i], npath);
			}
		}
	}
} 

/*
{if(tmp.provenance){
	tmp.provenance.addOutput(entry);
		if(typeof ldo[domainid][this.provpred] == "undefined"){
			ldo[domainid][this.provpred] = {};
		}
		if(typeof ldo[domainid][this.provpred][prop] == "undefined"){
			ldo[domainid][this.provpred][prop] = [];
		}
		//ldo[domainid][this.provpred][prop].push(tmp.provenance);
	}
	if(typeof ldo[domainid][prop] == "undefined"){
		ldo[domainid][prop] = [];
	}
	var already = false;
	for(var i = 0; i<ldo[domainid][prop].length; i++){
		if(typeof entry == "string" && ldo[domainid][prop] == entry || this.isStringType(type) && entry.data == ldo[domainid][prop].data){
			already = true;
		}
	}
	if(!already){
		ldo[domainid][prop].push(entry);
	}
}
else {}
} */
