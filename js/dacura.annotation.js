
function DacuraAnnotation(){
	
	this.valid_tags = ["inferred", "dubious", "uncertain", "disputed"];
	this.properties = {
		"rdfs:label": false,
		"rdfs:comment": false,
		"dacura:hasTemporalBounds": false,
		"dacura:hasSpatialBounds" : false,
		"dacura:hasConfidenceQualifier" : false,
		"dacura:hasCitation" : false
	};
	this.qualifiers = ["dacura:hasTemporalBounds", "dacura:hasSpatialBounds", "dacura:hasConfidenceQualifier"];

	this.targets = [];
}

DacuraAnnotation.prototype.hasTarget = function(){
	return this.targets.length;
}

DacuraAnnotation.prototype.getQualifierIcons = function(frm){
	//jpr(this.properties);
	var qs = document.createElement("span");
	qs.setAttribute("class", "value-qualifiers");
	var p = "dacura:hasTemporalBounds";
	if(this.properties[p] && this.properties[p].length){
		var str = this.properties[p][0];
		qs.appendChild(str);
	}
	p = "dacura:hasConfidenceQualifier";
	if(this.properties[p] && this.properties[p].length){
		for(var i = 0; i<this.properties[p].length; i++){
			var x = this.properties[p][i];
			this.attachQualifierIcon(qs, x, frm.tooltip_config);

			//alert("confidence " + i);
		}
	}
	p = "dacura:hasSpatialBounds";
	if(this.properties[p] && this.properties[p].length){
		for(var i = 0; i<this.properties[p].length; i++){
			alert("spatial " + i);
		}
	}
	return qs;
}

DacuraAnnotation.prototype.attachQualifierIcon = function(icondiv, type, tooltip_config){
	var iconmap = {
		"inferred": ["fa-asterisk", "<b>Inferred</b>: although there is no direct evidence, this value can be inferred from other evidence."],
		"disputed": ["fa-fire", "<b>Disputed</b>: this value is disputed."],
		"uncertain": ["fa-exclamation-triangle", "<b>Uncertain</b>: this value is uncertain."],
		"dubious": ["fa-cloud", "<b>Dubious</b>: this value is dubious."]		
	};
	if(iconmap[type]){
		attachIcon(icondiv, "confidence-qualifier", iconmap[type][0], iconmap[type][1], false, tooltip_config);		
	}
	else {
		alert("attempt to attach unknown icon " + type);
	}
}

DacuraAnnotation.prototype.loadFromFrames = function(frame, frm){
	for(var p in frame){
		var propid = urlFragment(p);
		for(var pshort in this.properties){
			if(pshort.split(":")[1] == propid){
				for(var i = 0; i<frame[p].length; i++){
					if(pshort == "dacura:hasConfidenceQualifier"){
						var tag = urlFragment(frame[p][i].frame.domainValue);
						if(tag){
							this.addTag(tag);
						}
					}
					if(pshort == "dacura:hasTemporalBounds"){
						var dv = new DurationViewer();
						this.properties[pshort] = [dv.display(frame[p][i], "view")];
					}
				}
			}
		}
	}
}

DacuraAnnotation.prototype.hasQualifier = function(){
	for(var i = 0; i<this.qualifiers.length; i++){
		if(this.properties[this.qualifiers[i]]) return true;
	}
	return false;
}


DacuraAnnotation.prototype.addTarget = function(target){
	if(this.targets.indexOf(target) == -1){
		this.targets.push(target);
	}
}

DacuraAnnotation.prototype.getPropertyValue = function(prop){
	var propid = urlFragment(prop);
	for(var pshort in this.properties){
		if(pshort.split(":")[1] == propid){
			return this.properties[pshort];
		}
	}
	return false;
}

DacuraAnnotation.prototype.getTargets = function(){
	return this.targets;
}

DacuraAnnotation.prototype.hasProperty = function(prop){
	var propid = urlFragment(prop);
	propid = (propid ? propid : prop.split(":")[1]);
	for(var pshort in this.properties){
		if(pshort.split(":")[1] == propid){
			return this.properties[pshort];
		}
	}
	return false;
}

DacuraAnnotation.prototype.hasContent = function(){
	for(var prop in this.properties){
		if(this.properties[prop]) return true;
	}
	return false;
}


DacuraAnnotation.prototype.setTargetFromFrame = function(frame){
	var targets = this.getMostPreciseAnnotatableFrames(frame);
	for(var i = 0; i<targets.length; i++){
		this.addTarget(targets[i]);
	}
}

DacuraAnnotation.prototype.getMostPreciseAnnotatableFrames = function(frame){
	var fv = new DacuraFrameReader();
	var fid  = fv.getFrameSubjectID(frame.frame);
	var targets = [];
	for(var p in frame.frame){
		for(var i = 0; i<frame.frame[p].length; i++){
			if(fv.getFrameCategory(frame.frame[p][i]) != "object"){
				return [fid];
			}
			targets = targets.concat(this.getMostPreciseAnnotatableFrames(frame.frame[p][i], fv));
		}
	}
	return targets;
}


DacuraAnnotation.prototype.setDateRange = function(from, to){
	to = (to && to.length ? to : from);
	this.properties["dacura:hasTemporalBounds"] = [[from, to]];
}


DacuraAnnotation.prototype.setComment = function(comment){
	if(comment && comment.trim().length){
		this.properties["rdfs:comment"] = comment
	}
};

DacuraAnnotation.prototype.getComment = function(){
	if(this.properties["rdfs:comment"] &&  this.properties["rdfs:comment"]){
		return this.properties["rdfs:comment"];
	}
	return false;
};

DacuraAnnotation.prototype.addTag = function(tag, val){
	if(this.valid_tags.indexOf(tag) == -1) return;
	if(typeof this.properties["dacura:hasConfidenceQualifier"] != "object"){
		this.properties["dacura:hasConfidenceQualifier"] = [];
	}
	this.properties["dacura:hasConfidenceQualifier"].push(tag);
	var x = this.getPropertyValue("dacura#hasConfidenceQualifier");
}

