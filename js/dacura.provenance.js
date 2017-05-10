function EntityProvenanceRecord(url, urltype, agent, user){
	this.start_time = dacura.utils.getXSDDateString();
	this.activity_type = "create";
	this.software_agent = agent;
	if(user){
		this.user_agent = user;
	}
	else {
		this.user_agent = false;
	}
	this.end_time = false;
	this.location = url;
	this.location_type = urltype;
	this.generation_activity_id = "_:generation_activity";
}

EntityProvenanceRecord.prototype.setTarget = function(entity_id){
	this.entity_id = entity_id;
}

EntityProvenanceRecord.prototype.getRDF = function(){
	var statements = {};
	if(!this.end_time){
		this.end_time = dacura.utils.getXSDDateString();
	}
	if(!this.entity_id){
		this.entity_id = "_:";
	}
	//entity
	statements[this.entity_id] = {
		"rdf:type": "prov:Entity",
		"prov:wasGeneratedBy":  this.generation_activity_id
	}; 
	//activity
	statements[this.generation_activity_id] = {
		"rdf:type": "prov:Activity",
		"prov:generated":  [this.entity_id],
		"prov:startedAtTime": this.start_time,
		"prov:endedAtTime": this.end_time,
		"prov:wasAssociatedWith": [this.software_agent],
		"prov:atLocation": [this.location]
	};
	if(this.user_agent){
		statements[this.generation_activity_id]["prov:wasAssociatedWith"].push(this.user_agent);	
	}
	//agents
	statements[this.software_agent] = {
		"rdf:type": "prov:SoftwareAgent"
	}
	if(this.user_agent){
		statements[this.user_agent] = {
			"rdf:type": "prov:Person"
		}
	}
	statements[this.location] = {
		"rdf:type": ["prov:Location", this.location_type]
	}
	return statements;
	//locations
}

function FactProvenanceRecord(bits){
	this.outputs = [];
	this.targets = [];
	this.property = false;
	this.pagelocator = false;
	this.input = false;
	if(typeof bits == 'object'){
		if(bits.label || bits.section){
			this.pagelocator = new pageLocator(bits.label, bits.section, bits.sectext, bits.sequence);
		}
		if(bits.property){
			this.property = bits.property;
		}
		if(bits.input){
			this.input = bits.input;
		}
		if(bits.outputs){
			this.outputs = bits.outputs;
		}
		if(bits.output){
			this.outputs = bits.output;
		}
		if(bits.targets){
			this.targets = bits.targets;
		}
		if(bits.objects){
			this.targets = bits.objects;//old name
		}
	}	
}

FactProvenanceRecord.prototype.initFromFactoid = function(foid){
	this.pagelocator = foid.pagelocator;
	this.input = foid.original.value;
}

FactProvenanceRecord.prototype.fid = function(){
	if(this.pagelocator){
		return this.pagelocator.uniqid();
	}
	return false;
}

FactProvenanceRecord.prototype.setProperty = function(prop){
	this.property = prop;
}

FactProvenanceRecord.prototype.addOutput = function(thing){
	this.outputs.push(thing);
}

FactProvenanceRecord.prototype.addTarget = function(thing){
	this.targets.push(thing);
}

FactProvenanceRecord.prototype.getRDF = function (){
	var rdfblob = this.pagelocator;
	if(this.property && this.property.length){
		rdfblob.property = this.property;
	}
	if(this.input && this.input.length){
		rdfblob.input = this.input;
	}
	var harvs = false;
	if(this.outputs && this.outputs.length){
		rdfblob.outputs = this.outputs;
	}
	else if(this.targets && size(this.targets)){
		harvs = this.targets;
	}
	var rec = {"rdf:type": "seshatprov:Location"};
	rec['seshatprov:locator'] = JSON.stringify(rdfblob);
	var nrec = {};
	if(harvs){
		for(var i = 0; i<harvs.length; i++){
			nrec[harvs[i]] = {"rdf:type": "seshatprov:Property", "seshatprov:createdFrom": rec};
		}
	}
	else {
		nrec = rec;
	}
	return nrec;
}
