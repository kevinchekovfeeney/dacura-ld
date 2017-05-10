
/*
 * Abstract class to put generic functions into - inherited by concrete classes (ontology, graph, candidate) 
 * via prototype mechanism
 */
function DacuraLDO(){}

DacuraLDO.prototype.loadFromLDO = function(ldo){
	this.id = ldo.id;
	this.meta = ldo.meta;
	this.contents = ldo.contents;
	this.fragment_id = ldo.fragment_id;
	this.format = ldo.format;
	this.options = ldo.options;
}

DacuraLDO.prototype.getComment = function(){
	var json = this.contents[this.meta.cwurl];
	if(json){
		if(typeof json['rdfs:comment'] != "undefined"){
			return json['rdfs:comment']['data'];
		}
		if(typeof json['dc:description'] != "undefined"){
			return json['dc:description']['data'];
		}
	}
	return false;
}

DacuraLDO.prototype.getLabel = function(){
	var json = this.contents[this.meta.cwurl];
	if(json){
		if(typeof json['rdfs:label'] != "undefined"){
			return json['rdfs:label']['data'];
		}
		if(typeof json['dc:title'] != "undefined"){
			return json['dc:title']['data'];
		}
	}
	return false;
}

DacuraLDO.prototype.cid = function(){
	return this.meta.cid;
}

/*
 * 3 concrete types of LDO - candidate, graph, ontology
 */

/**
 * First the candidate class - units of instance data, members of an entity class
 */
function DacuraCandidate(config){
	this.filledframe = false;
	this.pframes = false;
	this.pcounts = {};
}

/* inherited functions */
DacuraCandidate.prototype.loadFromLDO = DacuraLDO.prototype.loadFromLDO;
DacuraCandidate.prototype.cid = DacuraLDO.prototype.cid;
DacuraCandidate.prototype.getLabel = DacuraLDO.prototype.getLabel;
DacuraCandidate.prototype.getComment = DacuraLDO.prototype.getComment;

/* Candidate Specific functions */
DacuraCandidate.prototype.setPropertyFrame = function(pid, frame){
	this.pframes[pid] = frame.result;
	return frame.result;
}

DacuraCandidate.prototype.setFrame = function(frame){
	this.filledframe = frame.result;
	return frame.result;
}

DacuraCandidate.prototype.mainGraph = function(){
	var defgraph = this.meta.default_graph;
	return defgraph;
}

DacuraCandidate.prototype.entityClass = function(ecs){
	var mytype = this.meta.type;
	for(var i in ecs){
		if(ecs[i]["class"] == mytype){
			return ecs[i];
		}
	}
	return false;
}

DacuraCandidate.prototype.propertyCount = function(filled_only){
	if(typeof this.pcounts.total == "undefined"){
		this.getProperties();
	}
	if(filled_only) return this.pcounts.filled;
	else return this.pcounts.total;
}

DacuraCandidate.prototype.hasPropertyValue = function(prop){
	var frame = this.filledframe;
	if(typeof frame == "object" && !frame.length && size(frame)){
		frame = frame[this.mainGraph()];
	}
	for(var i = 0; i < frame.length; i++){
		if(frame[i].property == prop){
			return true;
		}
	}
	return false;
}


DacuraCandidate.prototype.getProperties = function(){
	var propcount = {};
	var frame = this.filledframe;
	if(typeof frame == "object" && !frame.length && size(frame)){
		frame = frame[this.mainGraph()];
		//jpr(frame);
	}
	if(!frame){
		alert("bad frame in filled frame");
		return [];
	}
	for(var i = 0; i < frame.length; i++){
		var pid = frame[i].property;
		var lab = (typeof frame[i].label != "undefined" ? frame[i].label.data : pid.substring(pid.lastIndexOf('#') + 1));
		if(typeof propcount[pid] == "undefined"){		
			propcount[pid] = {id: pid, label: lab, count: 1}
		}
		else {
			propcount[pid].count++;
		}
	}
	var props = [];
	for(var k in propcount){
		propcount[k].label = propcount[k].label + " (" + propcount[k].count + ")";
		props.push(propcount[k]);
	}
	props.sort(comparePropertiesByCount);
	return props;
}

var comparePropertiesByCount = function(a,b) {
	if(a.count < b.count){
		return 1;
	}
	if(b.count < a.count){
		return -1;
	}
	if(a.label < b.label){
		return -1;
	}				
	if(a.label > b.label){
		return 1;
	}			
	return 0;	
}


/**
 * Graph class
 */
function DacuraGraph(config){}

/* inherited functions */
DacuraGraph.prototype.loadFromLDO = DacuraLDO.prototype.loadFromLDO;
DacuraGraph.prototype.cid = DacuraLDO.prototype.cid;
DacuraGraph.prototype.getLabel = DacuraLDO.prototype.getLabel;
DacuraGraph.prototype.getComment = DacuraLDO.prototype.getComment;

DacuraGraph.prototype.loadFromModel = function(json, model){
	this.url = json.url;
	this.instance = json.instance;
	this.schema = json.schema;
	this.imports = json.imports;
	this.deploy = json.deploy;
	this.status = json.status;
	this.version = json.version;
	this.entity_classes = (typeof json.entity_classes == "object" ? json.entity_classes : false);
	this.class_frames = {};
	this.property_frames = {};
}

DacuraGraph.prototype.addClassFrame = function(cls, frames, model){
	var nf = new DacuraClassFrame(cls, frames, model);
	this.class_frames[cls] = nf; 
}

