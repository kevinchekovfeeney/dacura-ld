
/**
 * Factoid object
 * @constructor
 * @param uid {String} unique id for this factoid
 * @param details {Object}
 * @param config {Object}
 */
function DacuraFactoid(uid, details, config){
	this.uniqid = uid;
	this.warning = false;//true if the factoid invokes a warning
	this.pagelocator = details.pagelocator;
	this.original = {
		location : details.location,//page offset in bytes of where factoid was located within body.	
		full: details.full, 
		head: details.head,
		label: details.label,
		value: details.value,
		comment: details.comment,
		after: details.after
	};//original html values as found on page, for full section, encoded (variable header), label, value bit, text after, text before
	this.config = config;//factoid configuration object 
	this.data = []; //
	this.parsed = false;
	//this.annotation = this.generateAnnotation(this.original.before, this.original.after);
	this.tags = {};
}

DacuraFactoid.prototype.setParsedResult = function(result){
	this.parsed = result;
	//factParts.notes = factParts.notes.split(/<[hH]/)[0].trim();
	///"value" => $val,
	//"result_code" => "",
	//"result_message" => "",
	//"datapoints" => array()
}



DacuraFactoid.prototype.connectionCategory = function(){
	return this.tags['type'];
}

DacuraFactoid.prototype.getHarvestsClass = function(){
	var harvs = this.getHarvests();
	if(harvs){
		for(var j=0; j<harvs.length; j++){
			if(harvs[j].type == "harvests" && typeof harvs[j].target_class != "undefined"){
				return harvs[j].target_class;
			}
		}
	}
	return false;
}

DacuraFactoid.prototype.getHarvested = function(){
	return this.harvested;
}

DacuraFactoid.prototype.getHarvests = function(){
	return this.connectors;
}

DacuraFactoid.prototype.getRelevantHarvests = function(frames){
	var rels = [];
	var harvests = this.getHarvests();
	if(harvests){
		for(var i=0; i<harvests.length; i++){
			if(frames){
				for(var j=0; j<frames.length; j++){
					if(harvests[i].target_class == frames[j].property){
						rels.push(harvests[i]);
					}
				}				
			}
			else {
				rels.push(harvests[i]);
			}
		}
	}
	return rels;
}

DacuraFactoid.prototype.setRelevantHarvestFrames = function(frames){
	this.frames = [];
	var harvests = this.getHarvests();
	if(harvests && frames){
		for(var i=0; i<harvests.length; i++){
			for(var j=0; j<frames.length; j++){
				if(harvests[i].target_class == frames[j].property){
					this.frames.push(frames[j]);
				}
			}
		}
	}
}

DacuraFactoid.prototype.getTag = function(t){
	return this.tags[t];
}

DacuraFactoid.prototype.addTag = function(t, v){
	if(typeof this.tags[t] == "undefined"){
		this.tags[t] = v;
	}
	else if(typeof this.tags[t] == "object"){
		this.tags[t].push(v);
	}
	else {
		this.tags[t] = [this.tags[t]];
		this.tags[t].push(v);
	}
}


//locator has candidate_id or candidate_type
DacuraFactoid.prototype.forAPI = function(){
	var contents = this.getContentsAsLD();
	if(!contents) {
		return false;
	}
	var upd = {
		"contents": contents	
	}
	if(this.locator.candidate_id){
		upd.cid = this.locator.candidate_id;
	}
	else {
		upd.ctype = this.locator.candidate_type;	
	}
	return upd;
}

//calls the import function of the locator (?) 
DacuraFactoid.prototype.getContentsAsLD = function(){
	if(this.locator.target_type == "candidate" && this.locator.import){
		return this.locator.import(this);
	}
	else {
		//nothing we don't automatically try to import schemata at the moment. 
		return false;
	}
}

//generates an annotation object from the text before and after the factoid
DacuraFactoid.prototype.generateAnnotation = function(before, after){
	return after;
}


DacuraFactoid.prototype.getDataSyntaxClass = function(){
	return (this.parsed ? this.parsed.result_code : "empty");
}

DacuraFactoid.prototype.locatorMatch = function(ploc){
	if(this.pagelocator && this.pagelocator.includes(ploc)){
		return true;
	}
	return false;
}

DacuraFactoid.prototype.getValueAsParsed = function(v){
	var parsed = {
			result_code: "simple",
			value: v,
			datapoints: [{
				date_from: "",
				date_to: "",
				date_type: "",
				value_from: v,
				value_to: "",
				value_type: "simple",
				fact_type: "simple",
				comment: ""
			}]
	};
	return parsed;
}

//return a summary entry object to represent the factoid
DacuraFactoid.prototype.getAsSummaryEntry = function(){
	var sc = this.getDataSyntaxClass();
	if(!sc){
		sc = "unparsed";
	}
	var se = {css: sc, label: sc, value: 1};
	if(this.parsed && this.parsed.datapoints){
		se.data = this.parsed.datapoints.length;
	}
	return se;
}

//add the factoid's information to the statistical summary object
DacuraFactoid.prototype.addToSummaryEntry = function(ostats){
	ostats.value++;
	if(this.parsed && this.parsed.datapoints){
		ostats.data += this.parsed.datapoints.length;
	}
	return ostats;
}


