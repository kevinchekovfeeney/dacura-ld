function DacuraClient(dacura_url){
	this.collections = {};
	this.waiting = 0;
	this.user = false;
	this.ontology_config = false;
	this.config = false;
	this.current_collection = false;
	this.dacuraAPIURL = dacura_url + "rest/";
	this.clearCallbacks();
	this.errors = [];
}

//needs to contact the server to get the cards we have to play with
DacuraClient.prototype.init = function(success, fail){
	var self = this;
	var nsuccess = function(caps){
		self.loadCapabilities(caps);
		success(caps);
	}
	this.getSettingsFromServer(nsuccess, fail);
}

DacuraClient.prototype.get = function(type, id, success, fail){
	cid = this.current_collection;
	var col = this.collections[cid];
	if(!this.checkAgainstCapabilities(cid, col, type, id, "view")){
		fail = (fail ? fail : this.fail);
		if(fail) fail(this.getErrorTitle(), this.getErrorMessage(), this.getErrorExtra());
		return;
	}
	if(type == "ontology"){
		if(typeof col.cache.ontologies[id] == "object"){
			success(col.cache.ontologies[id]);
		}
		else {
			this.fetchOntology(id, success, fail);
		}
	}
	else if(type == "graph"){
		if(typeof col.cache.graphs[id] == "object"){
			success(col.cache.graphs[id]);
		}
		else {
			this.fetchGraph(id, success, fail);
		}
	}
	else if(type == "candidate"){
		if(typeof col.cache.candidates[id] == "object"){
			success(col.cache.candidates[id]);
		}
		else {
			this.fetchCandidate(id, success, fail);
		}	
	}
}

DacuraClient.prototype.update = function(type, obj, success, fail){
	var cid = (obj.meta.cid ? obj.meta.cid : this.current_collection);
	var col = this.collections[cid];
	if(!this.checkAgainstCapabilities(cid, col, type, obj.id, "update")){
		fail = (fail ? fail : this.fail);
		if(fail) fail(this.getErrorTitle(), this.getErrorMessage(), this.getErrorExtra());
		return;
	}
	if(type == "ontology"){
		this.updateOntology(obj.id, obj.getRDF(), obj.meta, success, fail);
	}
	else if(type == "graph"){
		this.updateGraph(obj.id, obj.getRDF(), obj.meta, success, fail);
	}
	else if(type == "candidate"){
		this.updateCandidate(obj.id, obj.getRDF(), obj.meta, success, fail);
	}
}

DacuraClient.prototype.create = function(type, obj, success, fail, test){
	var cid = (obj.meta && obj.meta.cid ? obj.meta.cid : this.current_collection);
	var col = this.collections[cid];
	if(!this.checkAgainstCapabilities(cid, col, type, false, "create")){
		fail = (fail ? fail : this.fail);
		if(fail) fail(this.getErrorTitle(), this.getErrorMessage(), this.getErrorExtra());
		return;
	}
	if(type == "ontology"){
		this.createOntology(obj.id, obj.getRDF(), obj.meta, success, fail, test);
	}
	else if(type == "graph"){
		this.createGraph(obj.id, obj.getRDF(), obj.meta, success, fail, test);
	}
	else if(type == "candidate"){
		this.createCandidate(obj.id, obj.contents, obj.meta, success, fail, test);
	}
}

DacuraClient.prototype.remove = function(type, id, success, fail, test){
	var cid = this.current_collection;
	var col = this.collections[cid];
	if(!this.checkAgainstCapabilities(cid, col, type, id, "view")){
		fail = (fail ? fail : this.fail);
		if(fail) fail(this.getErrorTitle(), this.getErrorMessage(), this.getErrorExtra());
		return;
	}
	if(type == "ontology"){
		this.deleteOntology(id, success, fail, test);
	}
	else if(type == "graph"){
		this.deleteGraph(id, success, fail, test);
	}
	else if(type == "candidate"){
		this.deleteCandidate(id, success, fail, test);
	}
}

DacuraClient.prototype.isLoggedIn = function(){
	if(this.user && this.user.name != "anonymous") return true;
	return false;
}

DacuraClient.prototype.checkAgainstCapabilities = function(cid, col, type, id, action){
	this.errors = [];
	if(!col){
		this.errors.push({title: "Unknown Collection", "message": "You do not have access to a collection with id " + cid})
	}
	else {
		if(action != "create"){
			if(type == "candidate"){
				var gotcha = false;
				for(var etype in col.candidates){
					if(typeof col.candidates[etype][id] != "undefined"){
						gotcha = true;
						break;
					}
				}
				if(!gotcha){
					this.errors.push({title: "Unknown Candidate", "message": "You do not have access to a candidate with id " + id + " in collection " + cid})
				}		
			}
			else if(type == "graph"){
				if(typeof col.graphs[id] == "undefined"){
					this.errors.push({title: "Unknown Graph", "message": "You do not have access to a graph with id " + id + " in collection " + cid})
				}		
				
			}
			else if(type == "ontology"){
				if(typeof col.ontologies[id] == "undefined"){
					this.errors.push({title: "Unknown Ontology", "message": "You do not have access to an ontology with id " + id + " in collection " + cid})
				}
			}
		}
	}
	return (this.errors.length == 0);
}

DacuraClient.prototype.clearCallbacks = function(){
	this.busy = false;
	this.notbusy = false;
	this.success = false;
	this.failure = false;
} 

DacuraClient.prototype.APIURL = function(service, entityid, collection_id){
	var url = this.dacuraAPIURL;
	var colfrag = (collection_id ? collection_id : this.current_collection);
	if(!colfrag || colfrag.length == 0 || colfrag == "all"){
		url += service + "/";
	}
	else {
		url += colfrag + "/" + service + "/";
	}
	if(entityid && entityid.length){
		url += entityid;
	}
	return url;
}

DacuraClient.prototype.APIArgs = function(action, thing, id, test, format){
  var args = {
	format: (format ? format : "json"),
	ldtype: thing,
	options: this.getAPIOptions(action, thing, test)
  };
  if(test){
	args.test = 1;
  }
  return args;
}


//flags that govern what is returned by the api
DacuraClient.prototype.getAPIOptions = function(action, thing, test){
	var basic = {plain: 1, history: 0, updates: 0};
	return basic;
}


DacuraClient.prototype.refreshCapabilities = function(caps){
	//this.capabilities = caps;
} 

DacuraClient.prototype.getDemandIDToken = function(){
	if(this.current_collection){
		return this.collections[this.current_collection].demand_id_token;
	}
	return false;
}


DacuraClient.prototype.getErrorTitle = function(){
	var err = this.errors[0];
	if(err){
		return err.title;
	}
	return false;
}

DacuraClient.prototype.getErrorMessage = function(){
	var err = this.errors[0];
	if(err){
		return err.message;
	}
	return false;
}

DacuraClient.prototype.getErrorExtra = function(){
	var err = this.errors.shift();
	if(this.errors.length){
		return this.errors;
	}
	return false;
}

DacuraClient.prototype.setCallbacks = function(cbs){
	if(cbs){
		this.busy = (typeof cbs.busy == 'function' ? cbs.busy : false);
		this.notbusy = (typeof cbs.notbusy == 'function' ? cbs.notbusy : false);
		this.success = (typeof cbs.success == 'function' ? cbs.success : false);
		this.failure = (typeof cbs.failure == 'function' ? cbs.failure : false);
	}
	else {
		this.clearCallbacks();
	}
}

DacuraClient.prototype.getSettingsFromServer = function(success, fail, subsettings){
	this.dispatch(this.APIURL("console"), this.getXHRTemplate(success, fail, subsettings));
}



DacuraClient.prototype.getObjectFromAPIJSON = function(action, ldr_or_ldo, type){
	var ldo = false, dobj = false;
	if(action == "create" || action == "update"){
		if(ldr_or_ldo.result_type == "LDO"){
			ldo = ldr_or_ldo.result;
		}
	}
	else { 
		ldo = ldr_or_ldo;	
	}
	if(ldo){
		var cid = this.current_collection;
		if(type == "ontology"){
			var dobj = new DacuraOntology(this.ontology_config);
		}
		else if(type == "graph"){
			var dobj = new DacuraGraph();			
		}
		else if(type == "candidate"){
			var dobj = new DacuraCandidate();			
		}
		dobj.loadFromLDO(ldo);
		return dobj;

	}
	return false;
}

DacuraClient.prototype.loadCandidate = function(action, ldr_or_ldo, candid){
	var cand = this.getObjectFromAPIJSON(action, ldr_or_ldo, "candidate");
	if(cand){
		this.collections[cand.cid()].update("candidate", action, cand);
	}
	else {
		var cid = this.current_collection;
		if(cid){
			this.collections[cid].update("candidate", action, candid);
		}
	}
	return cand;
}

DacuraClient.prototype.loadOntology = function(action, ldr_or_ldo, ontid){
	var ont = this.getObjectFromAPIJSON(action, ldr_or_ldo, "ontology");
	if(ont){
		this.collections[ont.cid()].update("ontology", action, ont);
	}
	else {
		var cid = this.current_collection;
		this.collections[cid].update("ontology", action, ontid);
	}
	return ont;
}

DacuraClient.prototype.loadGraph = function(action, ldr_or_ldo, gid){
	var graph = this.getObjectFromAPIJSON(action, ldr_or_ldo, "graph");
	if(graph){
		this.collections[graph.cid()].update("graph", action, graph);
	}
	else {
		var cid = this.current_collection;
		this.collections[cid].update("graph", action, gid);
	}
	return graph;
}

DacuraClient.prototype.loadCandidateFrame = function(id, frame){
	var cid = this.current_collection;	
	return this.collections[cid].updateCandidateFrame(id, frame);
}

DacuraClient.prototype.loadEntityClassFrame = function(entity_class, frame){
	var cid = this.current_collection;	
	return this.collections[cid].update("entity_class", entity_class, frame.result);
}

DacuraClient.prototype.loadEntityClassPropertyFrame = function(entity_class, property, frame){
	var cid = this.current_collection;	
	return this.collections[cid].updatePropertyFrame(entity_class, property, frame);
}

DacuraClient.prototype.loadCandidatePropertyFrame = function(candid, prop, frame){
	var cid = this.current_collection;	
	return this.collections[cid].updateCandidatePropertyFrame(candid, prop, frame);
}

DacuraClient.prototype.loadEntityClasses = function(classes){
	var cid = this.current_collection;	
	this.collections[cid].updateEntityClasses(classes);
	return classes;
}

DacuraClient.prototype.getGraphsToRedeployForOntologyUpdate = function(ontid){
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

/* Candidate API */

DacuraClient.prototype.createCandidate = function(cid, props, meta, success, fail, test){
	var ldcreate = this.APIArgs("create", "candidate", cid, test);
	ldcreate.contents = (props ? props : {});
	//ldcreate.contents['rdf:type'] = ctype; 
	if(meta){
		ldcreate.meta = meta;
	}
	var dit = this.getDemandIDToken();
	if(dit && cid && cid.length){
		ldcreate[dit] = cid;
	}
	success = (success ? success : this.success);
	fail = (fail ? fail : this.fail);
	var self = this;
	var nsuccess = function(ldr){
		ldr.result = self.loadCandidate("create", ldr);
		if(success) {
			return success(ldr);
		}
	}
	return this.dispatch(this.APIURL("candidate"), this.getXHRUpdateTemplate(nsuccess, fail, ldcreate));
}

DacuraClient.prototype.fetchCandidate = function(cid, success, fail, format){
	success = (success ? success : this.success);
	fail = (fail ? fail : this.fail);
	var self = this;
	var nsuccess = function(json){		
		var ldo = new LDO(json);
		var candidate = self.loadCandidate("view", ldo);
		var ffsuccess = function(ff){
			success(candidate);
		}
		self.getFilledFrame(cid, ffsuccess, fail);//load candidate frame too
	}
	return this.dispatch(this.APIURL("candidate", cid), this.getXHRTemplate(nsuccess, fail, this.APIArgs("view", "candidate", cid)));
}

DacuraClient.prototype.updateCandidate = function(cid, props, meta, success, fail, test){
	var ldupdate = this.APIArgs("update", "candidate", cid, test);
	if(props){
		ldupdate.contents = props;
	}
	if(meta){
		ldupdate.meta = meta;
	}
	var self = this;
	var nsuccess = function(ldr){
		ldr.result = self.loadCandidate("update", ldr);
		if(success) {
			return success(ldr);
		}
	}
	return this.dispatch(this.APIURL("candidate", cid), this.getXHRUpdateTemplate(nsuccess, fail, ldupdate));
}

DacuraClient.prototype.deleteCandidate = function(cid, success, fail, test){
	this.updateCandidate(cid, false, {status: "deleted"}, success, fail, test);
}

DacuraClient.prototype.getEmptyFrame = function(entity_class, success, fail){
	var self = this;
	cid = this.current_collection;
	var col = this.collections[cid];
	if(typeof col.cache.class_frames[entity_class] == "object"){
		return success(col.cache.class_frames[entity_class]);
	}
	var nsuccess = function(frame){
		var cframe = self.loadEntityClassFrame(entity_class, frame);
		if(success){
			success(cframe);
		}
	}
	var xhr = this.getFrameXHRTemplate(nsuccess, fail, {"class": entity_class});
	xhr.type = "POST";
	return this.dispatch(this.APIURL("candidate", "frame"), xhr);
};

DacuraClient.prototype.getEmptyPropertyFrame = function(cls, propid, success, fail){
	var self = this;
	var nsuccess = function(frame){
		var pframe = self.loadEntityClassPropertyFrame(cls	, propid, frame);
		if(success){
			success(pframe);
		}
	}
	var xhr = this.getFrameXHRTemplate(nsuccess, fail, {"class": cls, "property": propid});
	xhr.type = "POST";
	return this.dispatch(this.APIURL("candidate", "propertyframe"), xhr);
};

DacuraClient.prototype.getFilledFrame = function(id, success, fail){
	cid = this.current_collection;
	var col = this.collections[cid];
	if(typeof col.cache.candidates[id] == "object" && typeof col.cache.candidates[id].filledframe == "object" ){
		return success(col.cache.candidates[id].filledframe);
	}
	var self = this;
	var nsuccess = function(frame){
		var cframe = self.loadCandidateFrame(id, frame);
		if(success){
			success(cframe);
		}
	}
	return this.dispatch(this.APIURL("candidate/frame", id), this.getFrameXHRTemplate(nsuccess, fail));
};

DacuraClient.prototype.getFilledPropertyFrame = function(candid, propid, success, fail){
	cid = this.current_collection;
	var col = this.collections[cid];
	if(typeof col.cache.candidates[candid] == "object" && typeof col.cache.candidates[candid].pframes == "object" && typeof col.cache.candidates[candid].pframes[propid] == "object" ){
		return success(col.cache.candidates[id].pframes[propid]);
	}
	var self = this;
	var nsuccess = function(frame){
		var pframe = self.loadCandidatePropertyFrame(candid, propid, frame);
		if(success){
			success(pframe);
		}
	}
	var xhr = this.getFrameXHRTemplate(nsuccess, fail, { "property": propid} );
	xhr.type = "POST";
	return this.dispatch(this.APIURL("candidate/propertyframe", candid), xhr);
};

DacuraClient.prototype.getEntityClasses = function(success, fail){
	var self = this;
	var nsuccess = function(json){
		var ents = self.loadEntityClasses(json);
		if(success){
			success(ents);
		}
	}
	return this.dispatch(this.APIURL("candidate", "entities"), this.getXHRTemplate(nsuccess, fail));
};

/* Ontology API */
DacuraClient.prototype.createOntology = function(ontid, props, meta, success, fail, test){
	var ldcreate = this.APIArgs("create", "ontology", ontid, test);
	ldcreate.contents = (props ? props : {});
	if(meta){
		ldcreate.meta = meta;
	}
	var dit = this.getDemandIDToken();
	if(dit && ontid && ontid.length){
		ldcreate[dit] = ontid;
	}
	success = (success ? success : this.success);
	fail = (fail ? fail : this.fail);
	var self = this;
	var nsuccess = function(ldr){
		ldr.result = self.loadOntology("create", ldr);
		if(success) {
			return success(ldr);
		}
	}
	return this.dispatch(this.APIURL("ontology"), this.getXHRUpdateTemplate(nsuccess, fail, ldcreate));
}

DacuraClient.prototype.fetchOntology = function(ontid, success, fail, format){
	success = (success ? success : this.success);
	fail = (fail ? fail : this.fail);
	var self = this;
	var nsuccess = function(json){
		var ldo = new LDO(json);
		var ont = self.loadOntology("view", ldo);
		if(success) {
			return success(ont);
		}
	}
	return this.dispatch(this.APIURL("ontology", ontid), this.getXHRTemplate(nsuccess, fail, this.APIArgs("view", "ontology", ontid)));
}

DacuraClient.prototype.updateOntology = function(ontid, props, meta, success, fail, test){
	var ldupdate = this.APIArgs("update", "ontology", ontid, test);
	if(props){
		ldupdate.contents = props;
	}
	if(meta){
		ldupdate.meta = meta;
	}
	var self = this;
	var nsuccess = function(ldr){
		ldr.result = self.loadOntology("update", ldr);
		if(success) {
			return success(ldr);
		}
	}
	return this.dispatch(this.APIURL("ontology", ontid), this.getXHRUpdateTemplate(nsuccess, fail, ldupdate));
}

DacuraClient.prototype.deleteOntology = function(ontid, success, fail, test){
	this.updateOntology(ontid, false, {status: "deleted"}, success, fail, test);
}

/* Graph API */
DacuraClient.prototype.createGraph = function(gid, props, meta, success, fail, test){
	var ldcreate = this.APIArgs("create", "graph", gid, test);
	ldcreate.contents = (props ? props : {});
	if(meta){
		ldcreate.meta = meta;
	}
	var dit = this.getDemandIDToken();
	if(dit && gid && gid.length){
		ldcreate[dit] = gid;
	}
	success = (success ? success : this.success);
	fail = (fail ? fail : this.fail);
	var self = this;
	var nsuccess = function(ldr){
		ldr.result = self.loadGraph("create", ldr, gid);
		if(success) {
			return success(ldr);
		}
	}
	return this.dispatch(this.APIURL("graph"), this.getXHRUpdateTemplate(nsuccess, fail, ldcreate));
}

DacuraClient.prototype.fetchGraph = function(gid, success, fail){
	success = (success ? success : this.success);
	fail = (fail ? fail : this.fail);
	var self = this;
	var nsuccess = function(json){
		var ldo = new LDO(json);
		var graph = self.loadGraph("view", ldo, gid);
		if(success) {
			return success(graph);
		}
	}
	return this.dispatch(this.APIURL("graph", gid), this.getXHRTemplate(nsuccess, fail, this.APIArgs("view", "graph", gid)));
}

DacuraClient.prototype.updateGraph = function(gid, props, meta, success, fail, test){
	var ldupdate = this.APIArgs("update", "graph", gid, test);
	if(props){
		ldupdate.contents = props;
	}
	if(meta){
		ldupdate.meta = meta;
	}
	var self = this;
	var nsuccess = function(ldr){
		ldr.result = self.loadGraph("update", ldr);
		if(success) {
			return success(ldr);
		}
	}
	return this.dispatch(this.APIURL("graph", gid), this.getXHRUpdateTemplate(nsuccess, fail, ldupdate));
}

/* common generic functions for api interactions 
 * In each case we set up a simple wrapper which marshals the output into the proper javascript object,
 * then calls the externally set callback to return a nicely wrapped up object to the calling script
 * */

DacuraClient.prototype.getXHRTemplate = function(success, fail, data){
	var self = this;
	var xhr = {};
	if(data){
		xhr.data = data;
	}
	xhr.xhrFields = {
	    withCredentials: true
	};
	xhr.done = function(response, textStatus, jqXHR) {
		if(success){
			try {
				var json = (typeof response == "object") ? response : JSON.parse(response);
			}
			catch(e){
				if(fail){
					fail("Failed to parse server response", e.message, jqXHR.responseText);
				}
			}
			success(json);
		}
	};
	xhr.fail = function(jqXHR, textStatus, errorThrown){
		if(fail){
			if(jqXHR.responseText && jqXHR.responseText.length > 0){
				try{
					var jsonerror = JSON.parse(jqXHR.responseText);
					var ldr = new LDResult(jsonerror);
					fail(ldr);
				}
				catch(e){
					fail("Server returned error response", textStatus + " " + errorThrown + " " + jqXHR.responseText, jqXHR);
				};
			}
			else {
				fail("Server returned error response", textStatus + " " + errorThrown + " (empty body)", jqXHR);
			}			
		}	
	}	
	return xhr;
}

DacuraClient.prototype.getFrameXHRTemplate = function(success, fail, data){
	if(success){
		return this.getXHRTemplate(success, fail, data);
	}
	return this.getXHRTemplate(false, fail);
}

DacuraClient.prototype.getXHRUpdateTemplate = function(success, fail, data){
	if(success){
		var nsuccess = function(json){
			var ldr = new LDResult(json);
			success(ldr);
		}
		var xhr = this.getXHRTemplate(nsuccess, fail);
	}
	else {
		var xhr = this.getXHRTemplate(false, fail);
	}
	xhr.data = JSON.stringify(data);
	xhr.type = "POST";
	return xhr;
}

DacuraClient.prototype.dispatch = function(url, xhr){
	var done = xhr.done;
	var self = this;
	var fail  = xhr.fail;
	delete(xhr.done);
	delete(xhr.fail);
	if(this.busy){
		xhr.beforeSend = this.busy;
	}
	this.waiting++;
	var always = function(){
		self.waiting--;
		if(self.waiting <= 0 && self.notbusy){
			self.notbusy();
		}
	};
	xhr.url = url;
	return $.ajax(xhr).done(done).fail(fail).always(always);	
}

DacuraClient.prototype.loadCapabilities = function(caps){
	this.user = (typeof caps.user == 'object' ? caps.user : false);
	this.ontology_config = (typeof caps.ontology_config == 'object' ? caps.ontology_config : false);
	for(var colid in caps.collections){
		this.collections[colid] = new DacuraCollectionCapability(colid);
		this.collections[colid].init(caps.collections[colid]);
	}
	if(caps.current_collection){
		this.current_collection = caps.current_collection;		
	}
	//else {
	//	if(size(this.collections) > 0){
	//		this.current_collection = firstKey(this.collections);
	//	}
	//}
} 

DacuraClient.prototype.loadAll = function(fail){
	for(var colid in this.collections){
		this.current_collection = colid;
		for(var ontid in this.collections[colid].ontologies){
			this.get("ontology", ontid, false, fail);
		}
		for(var candid in this.collections[colid].candidates){
			this.get("candidate", candid, false, fail);
		}
		for(var graphid in this.collections[colid].graphs){
			this.get("graph", graphid, false, fail);
		}
		for(var clsid in this.collections[colid].entity_classes){
			this.getEmptyFrame(this.collections[colid].entity_classes[clsid]['class'], false, fail);
		}
	}
}

/**
 * 
*/

function DacuraCollectionCapability(collection_id){
	this.collectionid = collection_id;
	this.demand_id_token = false;
	this.title = "Anonymous Collection";
	this.url = false;
	this.ontologies = false;
	this.graphs = false;
	this.candidates = false;
	this.entity_classes = false;
	this.roles = [];
	this.cache = {
		ontologies: {},
		graphs: {},
		candidates: {},
		class_frames: {}
	};
}

DacuraCollectionCapability.prototype.init = function(json){
	this.title = json.title;
	this.url = json.url;
	this.icon = json.icon;
	this.demand_id_token = (json.demand_id_token ? json.demand_id_token : false) ;
	this.ontologies = (typeof json.ontologies == "object" ? json.ontologies : false);
	this.graphs = (typeof json.graphs == "object" ? json.graphs : false);
	this.candidates = (typeof json.candidates == "object" ? json.candidates : false);
	this.entity_classes = (typeof json.entity_classes == "object" ? json.entity_classes : false);
	this.roles = (typeof json.roles == "object" ? json.roles : []);
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
	return this.cache.class_frames[entity_class].setPropertyFrame(property, frame);	
}

DacuraCollectionCapability.prototype.updateCandidatePropertyFrame = function(candid, prop, frame){
	return this.cache.candidates[candid].setPropertyFrame(prop, frame);	
}

DacuraCollectionCapability.prototype.updateCandidateFrame = function(candid, frame){
	return this.cache.candidates[candid].setFrame(frame);	
}

DacuraCollectionCapability.prototype.updateEntityClasses = function(classes){
	this.entity_classes = {};
	for(var i = 0; i<classes.length; i++){
		this.entity_classes[classes[i]['id']] = classes[i];
	}
	for(ec in this.cache.class_frames){
		if(typeof this.entity_classes[ec] == "undefined"){
			delete(this.cache.class_frames[ec]);
		}
	}
};

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
		this.getProperties(filled_only);
	}
	if(filled_only) return this.pcounts.filled;
	else return this.pcounts.total;
}

DacuraCandidate.prototype.getProperties = function(filled_only){
	var empties = [];
	var filled = [];
	var frame = this.filledframe;
	for(var i = 0; i < frame.length; i++){
		var val = frame[i].value;
		var pid = frame[i].property;
		var lab = (typeof frame[i].label != "undefined" ? frame[i].label.data : pid.substring(pid.lastIndexOf('#') + 1));
		if(typeof val != "undefined"){
			if(typeof val == "string"){
				if(val.length == 0){
					empties.push({id: pid, label: lab});
				}
				else {
					filled.push({id: pid, label: lab + " (1)", count: 1});
				}
			}
			else if(isJSONObjectLiteral(val)){
				filled.push({id: pid, label: lab + " (1)", count: 1});
			}
			else if(typeof val == "object"){
				filled.push({id: pid, label: lab + "(" + val + ")", count: val.length });				
			} 
			else {
				jpr(frame[i]);
			}
		}
		else {
			empties.push({id: pid, label: lab});	
		}
	}
	this.pcounts.total = frame.length;
	this.pcounts.filled = filled.length;
	this.pcounts.empty = empties.length;
	//sort properties by count, then alphabetical
	filled.sort(comparePropertiesByCount);
	if(filled_only) return filled; 
	else return filled.concat(empties);
}

var comparePropertiesByCount = function(a,b) {
	if(a.count < b.count){
		return -1;
	}
	if(b.count < a.count){
		return 1;
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

/**
 * Ontology class
 */
function DacuraOntology(config){
	this.boxtypes = config.boxtypes;
	this.entity_tag = config.entity_tag;
	this.normals = ["rdfs:subClassOf", "rdf:type", "rdfs:label", "rdfs:comment", "owl:oneOf"];
	this.properties = {};
	this.classes = {};
}

DacuraOntology.prototype.loadFromLDO = function(ldo){
	this.id = ldo.id;
	this.meta = ldo.meta;
	this.contents = ldo.contents;
	this.fragment_id = ldo.fragment_id;
	this.format = ldo.format;
	this.options = ldo.options;
	for(var id in ldo.contents){
		if(this.isProperty(this.contents[id])){
	    	this.properties[id] = this.contents[id];				
		}
		else if(this.isClass(this.contents[id])){
	    	this.classes[id] = this.contents[id];
	    }
	}
	this.tree = this.buildNodeTree();
	this.entity_classes = this.calculateEntityClasses();	
}

/* inherited functions */
DacuraOntology.prototype.getLabel = DacuraLDO.prototype.getLabel;
DacuraOntology.prototype.getComment = DacuraLDO.prototype.getComment;
DacuraOntology.prototype.cid = DacuraLDO.prototype.cid;

/* Ontology specific functions */
DacuraOntology.prototype.isEntityClass = function(cls){
	var ecls = this.getEntityClasses();
	return ecls.indexOf(cls) != -1;
}

//building internal structure
DacuraOntology.prototype.buildNodeTree = function() {
	nodes = {};
	for(var i in this.classes){
		if(typeof nodes[i] == "undefined"){
			nodes[i] = {parents: [], children: []};
		}
		var x = this.classes[i]['rdfs:subClassOf'];
		if(typeof x == "string"){
			if(nodes[i].parents.indexOf(x) == -1){
				nodes[i].parents.push(x);
				if(typeof nodes[x] == "undefined"){
					nodes[x] = {parents: [], children: [i]};
				}
				else {
					if(nodes[x].children.indexOf(i) == -1){
						nodes[x].children.push(i);
					}
				}
			}
		}
		else if(typeof x == "object"){
			for(var j = 0; j< x.length; j++){
				if(nodes[i].parents.indexOf(x[j]) == -1){
					nodes[i].parents.push(x[j]);
					if(typeof nodes[x[j]] == "undefined"){
						nodes[x[j]] = {parents: [], children: [i]};
					}
					else {
						if(nodes[x[j]].children.indexOf(i) == -1){
							nodes[x[j]].children.push(i);
						}
					}
				}
			}
		}
	}
	return nodes;
}

DacuraOntology.prototype.calculateEntityClasses = function(){
	var cls = [];
	var totry = [this.entity_tag];
	while(totry.length > 0){
		var ec = totry.shift();
		if(cls.indexOf(ec) == -1){
			cls.push(ec);
			if(typeof this.tree[ec] == "object"){
				for(var i = 0; i<this.tree[ec].children.length; i++){
					totry.push(this.tree[ec].children[i]);
				}
			}
		}
	}
	return cls;
}

DacuraOntology.prototype.getEntityClasses = function(){
	if(typeof this.entity_classes != "object"){
		this.entity_classes = this.calculateEntityClasses();
	}
	return this.entity_classes;	
}

DacuraOntology.prototype.getBoxTypes = function(){
	return this.boxtypes;
}

DacuraOntology.prototype.isDacuraBoxType = function(bt){
	return (this.hasBoxedTypes() && typeof this.boxtypes[bt] != "undefined");
}

DacuraOntology.prototype.hasBoxedTypes = function(){
	return (size(this.boxtypes) > 0);
}

DacuraOntology.prototype.addClass = function(id, label, comment, type, parent, choices){
	var cid = this.id + ":" + id;
	this.classes[cid] = {"rdfs:label": label, "rdfs:comment": comment, "rdf:type": "owl:Class"};
	if(type == "enumerated"){
		this.classes[cid]["rdfs:subClassOf"] = "dacura:Enumerated";
		this.classes[cid]["owl:OneOf"] = choices;
	}
	else if(parent && parent.length){
		this.classes[cid]["rdfs:subClassOf"] = parent;
	}
}

DacuraOntology.prototype.addProperty = function(id, label, comment, domain, range){
	var pid = this.id + ":" + id;
	this.properties[pid] = {"rdfs:label": label, "rdfs:comment": comment};
	if(range.substring(0,3) == "xsd"){
		this.properties[pid]["rdf:type"] = "owl:DatatypeProperty";
	}
	else {
		this.properties[pid]["rdf:type"] = "owl:ObjectProperty";
	}
	this.properties[pid]['rdfs:range'] = range;
	this.properties[pid]['rdfs:domain'] = domain;
}

DacuraOntology.prototype.getRDF = function(){
	var rdf = {};
	for(var i in this.classes){
		rdf[i] = this.classes[i];
	}
	for(var i in this.properties){
		rdf[i] = this.properties[i];
	}
	return rdf;
}


/* functions for extracting information from predicates in the ontology */

DacuraOntology.prototype.isProperty = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:DatatypeProperty" || json['rdf:type'] == "owl:ObjectProperty" || json['rdf:type'] == "rdf:Property") return true;
	}
	if(typeof json['rdfs:subPropertyOf'] != "undefined") return true;
	if(typeof json['rdfs:range'] != "undefined") return true;
	if(typeof json['rdfs:domain'] != "undefined") return true;
	return false;
}

DacuraOntology.prototype.isClass = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:Class" || json['rdf:type'] == "rdfs:Class") return true;
	}
	if(typeof json['rdfs:subClassOf'] != "undefined") return true;
	return false;
}

DacuraOntology.prototype.getPropertyRangeType = function(prop) {
	var json = this.properties[prop];
	if(typeof json == "object"){
		if(typeof json['rdf:type'] != "undefined"){
			if(json['rdf:type'] == "owl:ObjectProperty"){
				if(this.isDacuraBoxType(json['rdfs:range'])){
					return "boxed";
				}
				return "object";
			}
			if(json['rdf:type'] == "owl:DatatypeProperty"){
				return "literal";
			}	
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyDomain = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:domain']  != "undefined"){
			return json['rdfs:domain'];
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyUnits = function(prop){
	var json = this.properties[prop];
	if(typeof json == "object"){
		if(typeof json['dacura:units']  != "undefined"){
			return json['dacura:units']['data'];
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyRange = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:range']  != "undefined"){
			return json['rdfs:range'];
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyLabel = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:label'] != "undefined"){
			return json['rdfs:label']['data'];
		}
		if(typeof json['dc:title'] != "undefined"){
			return json['dc:title']['data'];
		}
	}
	return "";
}

DacuraOntology.prototype.getClassLabel = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:label'] != "undefined"){
			return json['rdfs:label']['data'];
		}
		if(typeof json['dc:title'] != "undefined"){
			return json['dc:title']['data'];
		}
	}
	return "";
}

DacuraOntology.prototype.getClassComment = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:comment'] != "undefined"){
			return json['rdfs:comment']['data'];
		}
		if(typeof json['dc:description'] != "undefined"){
			return json['dc:description']['data'];
		}
	}
	return "";
}

DacuraOntology.prototype.getPropertyComment = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:comment'] != "undefined"){
			return json['rdfs:comment']['data'];
		}
		if(typeof json['dc:description'] != "undefined"){
			return json['dc:description']['data'];
		}
	}
	return "";
}


DacuraOntology.prototype.getParentClasses = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:subClassOf'] == "object"){
			return json['rdfs:subClassOf'];
		}
		else if(typeof json['rdfs:subClassOf'] == "string"){
			return [json['rdfs:subClassOf']];
		}
	}
	return [];
}

DacuraOntology.prototype.getEntityParent = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:subClassOf'] != "undefined"){
			var x = json['rdfs:subClassOf'];
			if(typeof x == "object") x = x[0];
			return x;
		};
	}
	return "";
};

DacuraOntology.prototype.getOtherAssertions = function(cls){
	var json = this.classes[cls];
	var others = {};
	if(typeof json == "object"){
		for(var i in json){
			if(this.normals.indexOf(i) == -1){
				others[i] = json[i];
			}
		}
	}
	return others;	
}

DacuraOntology.prototype.getClassType = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		for(var i in json){
			if(this.normals.indexOf(i) == -1){
				return "complex";
			}
		}
		if(typeof json['rdfs:subClassOf'] != "undefined"){
			if(typeof json['rdfs:subClassOf'] == "object"){
				if(json['rdfs:subClassOf'].length > 1){
					return "complex";
				}
				else if(json['rdfs:subClassOf'].length == 1){
					if(this.isEntityClass(cls)) {
						return "entity";
					}
					else {
						return "complex";
					}
				}
			}
			else {
				if(json['rdfs:subClassOf'] == "dacura:Enumerated"){
					return "enumerated";
				}
			}
		} 
	}
	return "";
}

DacuraOntology.prototype.getEnumeratedChoices = function(cls){
	var json = this.classes[cls];
	var choices = [];
	if(typeof json == "object"){
		if(typeof json['owl:oneOf'] != "undefined"){
			var set = json['owl:oneOf'];
			for(var id in set){
				var choice = {"id": id};
				var rest = {};
				if(typeof set[id] == "object"){
					for(var key in set[id]){
						if(key == "rdfs:label"){
							choice.label = set[id][key]['data'];
						}
						if(key == "rdfs:comment"){
							choice.comment = set[id][key]['data'];
						}
						else if(key != "rdf:type") {
							rest[key] = set[id][key];
						}
					}
				}
				if(size(rest) > 0){
					choice.rest = rest;
				}
				choices.push(choice);
			}
		}
	}
	return choices;
}


DacuraOntology.prototype.getRangeObjectType = function(range){
	if(!range || !range.length) return "";
	return (range.split(":")[0] == this.id ? "local" : "remote");
}


