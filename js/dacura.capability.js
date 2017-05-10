
/**
 * a collection capability basically just stores a bunch of lookupable stuff for clients
*/

function DacuraCollectionCapability(collection_id){
	this.collectionid = collection_id;
	this.title = "Anonymous Collection";
	this.url = false;
	this.services = {};
	this.roles = {};
	this.cache = {
		ontologies: {},
		graphs: {},
		candidates: {},
	};
}

DacuraCollectionCapability.prototype.hasAuthorityForAction = function(service, id, action){
	//if(typeof this.services[service] == "undefined") return false;
	return true;
}

DacuraCollectionCapability.prototype.init = function(json){
	this.title = json.title;
	this.url = json.url;
	this.icon = json.icon;
	this.demand_id_token = json.demand_id_token;
	this.services = json.services;
	this.roles = (typeof json.roles == "object" ? json.roles : {});
}

DacuraCollectionCapability.prototype.update = function(type, action, thing){
	if(typeof thing == "object"){
		if(type == "ontology"){
			this.cache.ontologies[thing.id] = thing;
		}
		else if(type == "graph"){
			this.cache.graphs[thing.id] = thing;
		}
		else if(type == "candidate"){
			this.cache.candidates[thing.id] = thing;
		}
		else if(type == "entity_class"){
			this.cache.class_frames[action] = thing;
		}
	}
	else {
		if(action == 'update'){
			if(type == "ontology"){
				delete(this.cache.ontologies[thing]);
			}
			else if(type == "graph"){
				delete(this.cache.graphs[thing]);
			}
			else if(type == "candidate"){
				delete(this.cache.candidates[thing]);
			}
			else if(type == "entity_class"){
				delete(this.cache.class_frames[action]);
			}
		}
		else if(action == 'create'){
		//do nowt for the moment, going to have to load the object	
		}	
	}
	return thing;
}

DacuraCollectionCapability.prototype.updatePropertyFrame = function(entity_class, property, frame){
	return frame.result;
	//return this.cache.class_frames[entity_class].setPropertyFrame(property, frame);	
}

DacuraCollectionCapability.prototype.updateCandidatePropertyFrame = function(candid, prop, frame){
	return this.cache.candidates[candid].setPropertyFrame(prop, frame);	
}

DacuraCollectionCapability.prototype.updateCandidateFrame = function(candid, frame){
	return this.cache.candidates[candid].setFrame(frame);	
}

DacuraCollectionCapability.prototype.updateEntityClasses = function(classes){
	this.entity_classes = {};
	if(typeof classes == "object" && size(classes)){
		for(var gurl in classes){
			this.entity_classes[gurl] = {};
			for(var i = 0; i<classes.length; i++){
				this.entity_classes[gurl][classes[i]['id']] = classes[gurl][i];
			}
		}
		var mg = this.getMainGraphURL();
		for(ec in this.cache.class_frames){
			if(typeof this.entity_classes[mg][ec] == "undefined"){
				delete(this.cache.class_frames[mg][ec]);
			}
		}

	}
	else if(typeof classes == "object" && classes.length){
		for(var i = 0; i<classes.length; i++){
			this.entity_classes[classes[i]['id']] = classes[i];
		}
		for(ec in this.cache.class_frames){
			if(typeof this.entity_classes[ec] == "undefined"){
				delete(this.cache.class_frames[ec]);
			}
		}
	}
};

DacuraCollectionCapability.prototype.getMainGraphURL = function(){
	for(var id in this.graphs){
		if(typeof this.graphs[id].instance != "undefined"){
			return this.graphs[id].instance;
		}
	}
}