 /* 
 * Represents a frame for a single class in a single graph
 */
function DacuraClassFrame(cls, frames, model) {	
	this.model = model;
    this.dfr = new DacuraFrameReader();
	this.frames = this.indexPropertyFrames(frames, "_:");
	this.cls = cls;
}

DacuraClassFrame.prototype.generateBNID = function() {
	if(typeof this.bncounter == "undefined"){
		this.bncounter = 0;
	}
	this.bncounter++;
	return "_:" + this.bncounter;
}

DacuraClassFrame.prototype.createInstance = function(){
	var x = new DacuraInstanceFrame(this);
	return x;
}

/**
 * Returns an array of properties that the class has
 */
DacuraClassFrame.prototype.getPropertyFrames = function(prop){
	if(typeof this.frames[prop] != "undefined"){
		return this.frames[prop];
	}
	return false;
}


/**
 * Returns true if the property exists in the frames
 */
DacuraClassFrame.prototype.hasProperty = function(prop){
	return (typeof this.frames[prop] != "undefined");
}

DacuraClassFrame.prototype.indexPropertyFrames = function(frames, basent, parent){
	var bnid = this.generateBNID();
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
			if(parent){
				elt.parent = parent;
			}
			if(!elt.domainValue && basent){
				elt.domainValue = basent;
			}
			else if(!elt.domainValue){
				elt.domainValue = bnid;
			}
			if (fcat == "object"){
				elt.frame = this.indexPropertyFrames(elt.frame, false);
			}
			frameslist[elt.property].push(elt);
		}
	}
	return frameslist;
}



