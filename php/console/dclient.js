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

DacuraClient.prototype.update = function(type, obj, success, fail, deploy){
	var cid = (obj.meta.cid ? obj.meta.cid : this.current_collection);
	var col = this.collections[cid];
	if(!this.checkAgainstCapabilities(cid, col, type, obj.id, "update")){
		fail = (fail ? fail : this.fail);
		if(fail) fail(this.getErrorTitle(), this.getErrorMessage(), this.getErrorExtra());
		return;
	}
	if(type == "ontology"){
		this.updateOntology(obj.id, obj.getRDF(), obj.getMetaUpdate(), success, fail, deploy);
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
				if(typeof col.candidates[id] == "undefined"){
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
  if(thing == "ontology" && action == "update"){
	  args.editmode = "replace";
	  args.options.show_result = 1;
	  args.options.show_dqs_triples = 1;
	  args.options.show_ld_triples = 1;
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
			var cfg  = this.ontology_config;
			cfg.dtoken = this.getDemandIDToken()
			var dobj = new DacuraOntology(cfg);
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
		if(!this.collections[cand.cid()]){
			this.collections[cand.cid()] = new DacuraCollectionCapability(cand.cid());
		}
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

DacuraClient.prototype.loadDacuraOntology = function(action, ldr_or_ldo, ontid){
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
		var pframe = self.loadEntityClassPropertyFrame(cls, propid, frame);
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
	if(col && typeof col.cache.candidates[id] == "object" && typeof col.cache.candidates[id].filledframe == "object" ){
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
	if(col && typeof col.cache.candidates[candid] == "object" && typeof col.cache.candidates[candid].pframes == "object" && typeof col.cache.candidates[candid].pframes[propid] == "object" ){
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

/*DacuraClient.prototype.getEntityClasses = function(success, fail){
	var self = this;
	var nsuccess = function(json){
		var ents = self.loadEntityClasses(json);
		if(success){
			success(ents);
		}
	}
	return this.dispatch(this.APIURL("candidate", "entities"), this.getXHRTemplate(nsuccess, fail));
};*/

/*
 * @param cls string the class in question - if blank all candidates are returned
 * @return list of candidate metadata object indexed by entity id
 */
DacuraClient.prototype.getCandidateList = function(cls){
	var col = this.collections[this.current_collection];
	var ec = this.getEntityClasses();
	if(ec.length > 1 && size(col.entities) > 0){
		if(cls){
			var cands = {};
			for(var etype in col.entities){
				if(this.classSubsumesClass(cls, etype, col.getMainGraphURL())){
					cands[etype] = [];
					for(var candid in col.entities[etype]){
						cands[etype][candid] = col.entities[etype][candid];
					}
				}
			}
			return cands;
		}
		else {
			return col.entities;
		}
	}
	return {};
}

DacuraClient.prototype.getMainGraphURL = function(){
	var col = this.collections[this.current_collection];
	return col.getMainGraphURL();
}

DacuraClient.prototype.getEntityClasses = function(graph){
	var col = this.collections[this.current_collection];
	if(typeof col.entity_classes == "object" && size(col.entity_classes) && !col.entity_classes.length){
		if(graph && typeof col.entity_classes[graph] == 'object'){t
			return col.entity_classes[graph];
		}
		else {
			var mgurl = col.getMainGraphURL();
			if(mgurl && typeof col.entity_classes[mgurl] != "undefined"){
				return col.entity_classes[mgurl];
			}
		}
	}
	else {
		return col.entity_classes;
	}
	return [];
}

/**
 * does class a subsume class b?
 * @param a string class in id format (ns:localid)
 * @param b string class in id format (ns:localid)
 * @return true if a subsumes b
 */
DacuraClient.prototype.classSubsumesClass = function(a, b, gurl){
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

DacuraClient.prototype.getHarvestConnectors = function(){
	var col = this.collections[this.current_collection];
	var cons = {};
	if(typeof col.harvests == "object"){
		for(var i=0; i<col.harvests.length; i++){
			var pl = new pageLocator(col.harvests[i].pagelocator.label, col.harvests[i].pagelocator.section, false, col.harvests[i].pagelocator.sequence);
			var coid = pl.uniqid();
			var connector = new pageConnector(pl, "harvests", col.harvests[i].target, col.harvests[i].target_class);
			if(typeof cons[coid] == "undefined"){
				cons[coid] = [];
			}
			cons[coid].push(connector);		
		}
	}
	return cons;
}

DacuraClient.prototype.getHarvestedConnectors = function(cole, raw){
	cole = (cole ? cole : this.current_collection);
	var cons = {};
	var col = (cole ? this.collections[cole] : false);
	if(col && typeof col.harvested == "object"){
		if(raw) return col.harvested;
		for(var part in col.harvested){
			for(var curl in col.harvested[part]){
				for(var i = 0; i<col.harvested[part][curl].length; i++){
					var rec = col.harvested[part][curl][i];
					var fpr = new FactProvenanceRecord(rec);
					fpr.type = part;
					if(typeof cons[curl] == "undefined"){
						cons[curl] = [];
					}
					cons[curl].push(fpr);
				}
			}
		}
	}
	return cons;
}

DacuraClient.prototype.getMainGraphConfig = function(){
	var col = this.collections[this.current_collection];
	if(typeof col.graphs['main'] != "undefined"){
		return col.graphs['main']; 
	}
	return false;
}

DacuraClient.prototype.getFrameRenderingMap = function(){
	var col = this.collections[this.current_collection];
	if(typeof col.frame_renderers != "undefined"){
		return col.frame_renderers; 
	}
	return false;
}


DacuraClient.prototype.getProvGraphConfig = function(){
	var col = this.collections[this.current_collection];
	if(typeof col.graphs['prov'] != "undefined"){
		return col.graphs['prov']; 
	}
	if(typeof col.graphs['provenance'] != "undefined"){
		return col.graphs['provenance']; 
	}
	return false;
}


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
		//ldr.result = self.loaDacuraOntology("create", ldr);
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
		var ont = self.loadDacuraOntology("view", ldo);
		if(success) {
			return success(ont);
		}
	}
	return this.dispatch(this.APIURL("ontology", ontid), this.getXHRTemplate(nsuccess, fail, this.APIArgs("view", "ontology", ontid)));
}

DacuraClient.prototype.updateOntology = function(ontid, props, meta, success, fail, test, deploy){
	var ldupdate = this.APIArgs("update", "ontology", ontid, test);
	if(props){
		ldupdate.contents = props;
	}
	if(meta){
		ldupdate.meta = meta;
	}
	var self = this;
	var nsuccess = function(ldr){
		if(!ldr.test && (ldr.status == "pending" || ldr.status == "accept")){
			if(deploy){
				var gids = self.getGraphsToRedeployForOntologyUpdate(ontid);
				for(var gid in gids){
					self.updateGraph(gid, gids[gid], false);
				}
			}
			self.collections[self.current_collection].cache.ontologies[ontid] = false;			
		}
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
	this.user = ((typeof caps == 'object' && typeof caps.user == 'object')? caps.user : false);
	this.ontology_config = (typeof caps.ontology_config == 'object' ? caps.ontology_config : false);
	for(var colid in caps.collections){
		this.collections[colid] = new DacuraCollectionCapability(colid);
		this.collections[colid].init(caps.collections[colid]);
	}
	if(caps.current_collection){
		this.current_collection = caps.current_collection;		
	}
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
			//this.getEmptyFrame(this.collections[colid].entity_classes[clsid]['class'], false, fail);
		}
	}
}

/**
 * a collection capability basically just stores a bunch of lookupable stuff for clients
*/

function DacuraCollectionCapability(collection_id){
	this.collectionid = collection_id;
	this.demand_id_token = false;
	this.title = "Anonymous Collection";
	this.url = false;
	this.ontologies = false;
	this.graphs = false;
	this.candidates = false;
	this.entities = false;
	this.entity_classes = false;
	this.harvests = false;
	this.harvested = false;
	this.frame_renderers = false;
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
	this.entities = (typeof json.entities == "object" ? json.entities : false);
	this.entity_classes = (typeof json.entity_classes == "object" ? json.entity_classes : false);
	this.harvests = (typeof json.harvests == "object" ? json.harvests : false);
	this.harvested = (typeof json.harvested == "object" ? json.harvested : false);
	this.roles = (typeof json.roles == "object" ? json.roles : []);
	this.frame_renderers = (typeof json.frame_renderers == "object" ? json.frame_renderers : []);
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

/**
 * Ontology class
 */
function DacuraOntology(config){
	this.request_id_token = config.dtoken;
	this.boxtypes = config.boxtypes;
	this.entity_tag = config.entity_tag;
	this.normals = ["rdfs:subClassOf", "rdf:type", "rdfs:label", "rdfs:comment", "owl:oneOf"];
	this.normalprops = ["rdfs:domain", "rdf:type", "rdfs:label", "rdfs:comment", "rdfs:range"];
	this.properties = {};
	this.classes = {};
	this.others = {};//assertions that are not properties or classes
	this.bncounter = 0;
	this.updated = {};//updated properties/classes id => rdf
	this.updated_meta = {};//updated metadata elements
	this.deleted = [];
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
		else {
			this.others[id] = this.contents[id];
		}
	}
	this.tree = this.buildNodeTree();
	this.entity_classes = this.calculateEntityClasses();	
}

/* inherited functions */
//DacuraOntology.prototype.getLabel = DacuraLDO.prototype.getLabel;
//DacuraOntology.prototype.getComment = DacuraLDO.prototype.getComment;
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

//transforms a simple array [] into an rdf first, rest style linked list
DacuraOntology.prototype.listToRDF = function(choices, bnids){
	if(choices.length == 0) return "rdf:nil";
	//choice to obj
	var choice = choices.shift();
	var bnid = ((bnids && bnids.length) ? bnids.shift() : false);
	var rdffirst = {};
	if(choice.id){
		var nid = choice.id;
		delete(choice["id"]);
		rdffirst[nid] = choice;
	}
	else {
		rdffirst = choice;//id will be automagically generated
	}
	var rdfl = {"rdf:first": jQuery.extend({}, rdffirst)};
	rdfl["rdf:rest"] = this.listToRDF(choices, bnids); 
	if(bnid){
		var ret = {};
		ret[bnid] = rdfl;
		return ret;
	}
	else {	
		return rdfl;
	}
}

DacuraOntology.prototype.RDFToList = function(rdflist, depth){
	var nlist = [];
	if(rdflist['rdf:first']){
		nlist.push(rdflist['rdf:first']);
	}
	else {
		alert("rdf list error: no first at depth " + depth);
		jpr(rdflist);
	}
	if(rdflist['rdf:rest']){
		if(typeof rdflist['rdf:rest'] == "string"){
			if(rdflist['rdf:rest'] != "rdf:nil"){
				var json = this.contents[rdflist['rdf:rest']];
				if(json){
					nlist = nlist.concat(this.RDFToList(json, depth+1));
				}
				else {
					alert("nothing found for "+ rdflist['rdf:rest'] + " " + depth);
				}
			}
		}
		else if(typeof rdflist['rdf:rest'] == "object"){
			var fk = firstKey(rdflist['rdf:rest']);
			nlist = nlist.concat(this.RDFToList(rdflist['rdf:rest'][fk]));
		}
		else {
			alert("no type for " + rdflist['rdf:rest'] +  " in rdf list at depth " + depth);
		}
	}
	else {
		alert("rdf:list error - no rdf:rest element found at depth " + depth);
	}
	return nlist;
}

DacuraOntology.prototype.getClassRDF = function(cid, label, comment, type, parent, choices, others){
	var nclass = {"rdfs:label": label, "rdfs:comment": comment, "rdf:type": "owl:Class"};
	if(type == "enumerated"){
		nclass["rdfs:subClassOf"] = "dacura:Enumerated";
		var oclass = this.classes[cid];
		var bnids = ((oclass && oclass["owl:oneOf"]) && size(oclass["owl:oneOf"])) ? this.extractStructuralBNIDsFromRDFList(oclass["owl:oneOf"]) : false;
		nclass["owl:oneOf"] = this.getEnumeratedTypeChoicesAsRDF(cid, choices, bnids);			
	}
	else if(parent && parent.length){
		nclass["rdfs:subClassOf"] = parent;
	}
	if(others){
		for(var k in others){
			nclass[k] = others[k];
		}
	}
	return nclass;
}

DacuraOntology.prototype.getPropertyRDF = function(label, comment, domain, range, others){
	var nprop = {"rdfs:label": label, "rdfs:comment": comment};
	if(range.substring(0,3) == "xsd" || range.substring(0,3) == "xdd"){
		nprop["rdf:type"] = "owl:DatatypeProperty";
	}
	else {
		nprop["rdf:type"] = "owl:ObjectProperty";
	}
	nprop['rdfs:range'] = range;
	nprop['rdfs:domain'] = domain;
	if(others){
		for(var k in others){
			nprop[k] = others[k];
		}
	}
	return nprop;
}

/*
 * Retrieves the stuctural bnids used to construct the rdf list (not the element ids - the ids that owl:oneOf and rdf:rest point to
 */
DacuraOntology.prototype.extractStructuralBNIDsFromRDFList = function(oldOneOf, follow_unembedded){
	var lst = [];
	if(!oldOneOf || oldOneOf == "rdf:nil"){
		return lst;
	}
	if(typeof oldOneOf == "object"){
		var id = firstKey(oldOneOf);
		lst.push(id);
		var listelem = oldOneOf[id];
	}
	else {
		lst.push(oldOneOf);
		this.deleted.push(oldOneOf);
		var listelem = this.contents[oldOneOf];
		if(typeof listelem['rdf:first'] == "string"){
			this.deleted.push(listelem['rdf:first']);
		}
	}
	if(listelem && typeof listelem == "object" && typeof listelem['rdf:rest'] != "undefined"){
		var nlst = this.extractStructuralBNIDsFromRDFList(listelem['rdf:rest']);
		lst = lst.concat(nlst);
	}			
	return lst;
}


DacuraOntology.prototype.getEnumeratedTypeChoicesAsRDF = function(cid, choices, bnidlist){
	var rdflist = [];
	for(var i = 0; i<choices.length; i++){
		var onec = {"rdfs:label": choices[i].label, "rdfs:comment": choices[i].comment, "rdf:type": cid};
		if(choices[i].id){
			if(choices[i].id.indexOf(':') == -1){
				onec.id = this.id + ":" + choices[i].id;//could also use bn
				//onec.id = "_:" + choices[i].id;
			}
			else {
				onec.id = choices[i].id;
			}
		}
		rdflist.push(onec);
	}
	return this.listToRDF(rdflist, bnidlist);
}

DacuraOntology.prototype.addMetaUpdate = function(id, meta){
	if(typeof this.updated_meta != "object"){
		this.updated_meta = {};
	}
	if(typeof this.updated_meta.elements != "object"){
		this.updated_meta.elements = {};
	}
	if(typeof this.updated_meta.elements[id] != "object"){
		this.updated_meta.elements[id] = {};
	}
	for(var k in meta){
		if(typeof this.updated_meta.elements[id][k] == "undefined"){
			this.updated_meta.elements[id][k] = meta[k];
		}
		else {
			this.updated_meta.elements[id][k] = this.updated_meta.elements[id][k].concat(meta[k]);
		}
	}
}

DacuraOntology.prototype.hasProperty = function(id){
	var cid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	return (typeof this.properties[cid] != "undefined")
}


DacuraOntology.prototype.hasClass = function(id){
	var cid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	return (typeof this.classes[cid] != "undefined")
}

DacuraOntology.prototype.addClass = function(id, label, comment, type, parent, choices, meta){
	var cid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	this.updated[cid] = this.getClassRDF(cid, label, comment, type, parent, choices);
	if(meta){
		this.addMetaUpdate(cid, meta);
	}
}

DacuraOntology.prototype.updateClass = function(id, label, comment, type, parent, choices, others, meta){
	this.updated[id] = this.getClassRDF(id, label, comment, type, parent, choices, others);
	if(meta){
		this.addMetaUpdate(id, meta);
	}
}

DacuraOntology.prototype.addProperty = function(id, label, comment, domain, range, meta){
	var pid = ((id.indexOf(':') == -1) ? this.id + ":" + id : id);
	this.updated[pid] = this.getPropertyRDF(label, comment, domain, range);
	if(meta){
		this.addMetaUpdate(pid, meta);
	}
}

DacuraOntology.prototype.updateProperty = function(id, label, comment, domain, range, others, meta){
	this.updated[id] = this.getPropertyRDF(label, comment, domain, range, others);
	if(meta){
		this.addMetaUpdate(id, meta);
	}
}

DacuraOntology.prototype.deleteClass = function(id){
	this.deleted.push(id);
}

DacuraOntology.prototype.deleteProperty = function(id){
	this.deleted.push(id);
}


DacuraOntology.prototype.getRDF = function(ignore_updates){
	var rdf = {};
	for(var i in this.classes){
		rdf[i] = this.classes[i];
	}
	for(var i in this.properties){
		rdf[i] = this.properties[i];
	}
	for(var i in this.others){
		rdf[i] = this.others[i];
	}
	if(!ignore_updates){
		for(var j in this.updated){
			rdf[j] = this.updated[j];
		}
		for(var i in rdf){
			if(this.deleted.indexOf(i) != -1){
				delete(rdf[i]);
			}
		}
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

DacuraOntology.prototype.getPropertyRange = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:range']  != "undefined"){
			return json['rdfs:range'];
		}
	}
	return "";
}

DacuraOntology.prototype.getElementMetadata = function(elid, key){
	if(typeof this.meta.elements == "object" && typeof this.meta.elements[elid] == "object" && typeof this.meta.elements[elid][key] != "undefined"){
		return this.meta.elements[elid][key];
	}
	return false;
}

DacuraOntology.prototype.getPropertyMetadata = function(prop, defs){
	defs = (defs ? defs : {});
	var md = {};
	var hd = this.getElementMetadata(prop, "harvested");
	if(hd !== false){
		md.harvested = jQuery.extend([], hd, true);
	}
	else if(defs.harvested){
		md.harvested = jQuery.extend([], defs.harvested, true);
	}
	var hs = this.getElementMetadata(prop, "harvests");
	if(hs !== false){
		md.harvests = jQuery.extend([], hs, true);
		if(typeof defs.harvests == "object"){
			for(var i = 0; i<defs.harvests.length; i++){
				var wl = new webLocator(defs.harvests[i]);
				var has = false;
				for(var j=0; j<hs.length; j++){
					var wl2 = new webLocator(hs[j]);
					if(wl2.sameAs(wl)){
						has = true;
						continue;
					}
					else {
						//jpr({orig: hs[j], n: defs.harvests[j]});
					}
				}
				if(!has){
					md.harvests.push(defs.harvests[i]);
				}
			}
		}
	}
	else if(defs.harvests){
		md.harvests = jQuery.extend([], defs.harvests, true);		
	}
	return md; 
}

DacuraOntology.prototype.getRelevantConnectors = function(url){
	var rels = {};
	for(var elid in this.meta.elements){
		for(var ctype in this.meta.elements[elid]){
			if(this.meta.elements[elid][ctype].length){
				for(var i=0; i<this.meta.elements[elid][ctype].length; i++){
					var wl = new webLocator(this.meta.elements[elid][ctype][i]);
					if(wl.matchesURL(url)){
						if(wl.pagelocator){
							var coid = wl.pagelocator.uniqid();
							var connector = new pageConnector(wl.pagelocator, ctype, elid);
							if(typeof rels[coid] == "undefined"){
								rels[coid] = [];
							}
							rels[coid].push(connector);
						}
					}
				}
			}
		}
	}
	return rels;
}

DacuraOntology.prototype.getClassMetadata = function(cls, defs){
	var md = (defs ? defs : {});
	var hd = this.getElementMetadata(cls, "harvested");
	if(hd !== false){
		md.harvested = hd;
	}
	return md; 
}

DacuraOntology.prototype.getMetaUpdate = function(){
	var mu = jQuery.extend({}, this.meta, true);
	if(typeof this.updated_meta.elements == "object"){
		if(typeof mu.elements == "undefined"){
			mu.elements = {};
		}
		for(var el in this.updated_meta.elements){
			if(size(this.updated_meta.elements[el])){
				mu.elements[el] = this.updated_meta.elements[el];
			}
			else if(mu.elements[el]){
				delete(mu.elements[el]); 
			}
		}
	}
	return mu;
}

DacuraOntology.prototype.getComment = function(json){
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

DacuraOntology.prototype.getLabel = function(json){
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
	return this.getLabel(this.classes[cls]);
}

DacuraOntology.prototype.getClassComment = function(cls){
	return this.getComment(this.classes[cls]);
}

DacuraOntology.prototype.getPropertyComment = function(cls){
	return this.getComment(this.properties[cls]);
}

DacuraOntology.prototype.getPropertyLabel = function(cls){
	return this.getLabel(this.properties[cls]);
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

DacuraOntology.prototype.getOtherPropertyAssertions = function(prop){
	var json = this.properties[prop];
	var others = {};
	if(typeof json == "object"){
		for(var i in json){
			if(this.normalprops.indexOf(i) == -1){
				others[i] = json[i];
			}
		}
	}
	return others;	
}


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
				else if(this.isEntityClass(json['rdfs:subClassOf'])) {
					return "entity";
				}
				return "complex";
			}
		}
		else {
			return "simple";
		}
	}
	else {
		alert(cls + " is empty");
	}
	return "";
}

DacuraOntology.prototype.getEnumeratedChoices = function(cls){
	var json = this.classes[cls];
	var choices = [];
	if(typeof json == "object"){
		if(typeof json['owl:oneOf'] == "string"){
			if(this.contents[json['owl:oneOf']]){
				var set = this.contents[json['owl:oneOf']];
			}
			else {
				alert("not supposed to be string: " + json['owl:oneOf'])
			}
		}
		else if(typeof json['owl:oneOf'] == "object"){
			var set = json['owl:oneOf'][firstKey(json['owl:oneOf'])]; 
		}
		else {
			return [];
		}
		var choices = this.RDFToList(set, 0);
		var nchoices = [];
		for(var i=0; i<choices.length; i++){
			if(typeof choices[i] == "string"){
				nchoice = {id: choices[i]};
				if(this.contents[choices[i]]){
					nchoice.label = this.getLabel(this.contents[choices[i]]);
					nchoice.comment = this.getComment(this.contents[choices[i]]);
				}
				else {
					alert("No classes for " + choices[i]);
				}
			}
			else if(typeof choices[i] == "object"){
				var id = firstKey(choices[i]);
				nchoice = {id: id, label: this.getLabel(choices[i][id]), comment: this.getComment(choices[i][id])};
			}
			else {
				nchoice = {id: "??", label: "??", comment: "??"};
			}
			nchoices.push(nchoice);
		}
		
		return nchoices;
	}
	else {
		alert(json);
	}
	return [];
}


DacuraOntology.prototype.getRangeObjectType = function(range){
	if(!range || !range.length) return "";
	return (range.split(":")[0] == this.id ? "local" : "remote");
}

DacuraOntology.prototype.wrapEmpty = function(text){
	return "<span class='console-element-empty'>" + text + "</span>";
}

DacuraOntology.prototype.validateUpdateClassInput = function(input) {
	this.errors = [];
	var cls = ((input.id.indexOf(':') == -1) ? this.id + ":" + input.id : input.id);
	var orig = this.classes[cls];
	if(typeof orig != "object"){
		this.errors.push({field: "id", message: "Attempt to update class " + cls + " which does not exist"});
		return false;
	}
	return this.validateClassFormInput(input);
}

DacuraOntology.prototype.validateCreatePropertyInput = function(input) {
	this.errors = [];
	if(!this.validateFormBasics(input)){
		return false;
	}
	if(!input.domain.length){
		this.errors.push({field: "domain", message: "Missing domain class"});
		return false;	
	}
	if(typeof input.range == "undefined" || !input.range.length){
		this.errors.push({field: "range", message: "Missing range"});
		return false;	
	}
	return true;
}

DacuraOntology.prototype.validateUpdatePropertyInput = function(input) {
	if(!this.validateCreatePropertyInput(input)){
		return false;
	}
	var pid = ((input.id.indexOf(':') == -1) ? this.id + ":" + input.id : input.id);
	var orig = this.properties[pid];
	if(typeof orig != "object"){
		this.errors.push({field: "id", message: "Attempt to update property " + pid + " which does not exist"});
		return false;
	}
	return true;
}

DacuraOntology.prototype.validateCreateClassInput = function(input) {
	this.errors = [];
	var cid = ((input.id.indexOf(':') == -1) ? this.id + ":" + input.id : input.id);
	if(typeof this.contents[cid] != "undefined"){
		this.errors.push({field: "id", message: "An entity with the id " + input.id + " already exists in the ontology"});
		return false;	
	}
	return this.validateClassFormInput(input);
}

DacuraOntology.prototype.validateFormBasics = function(input){
	var id = ((input.id.indexOf(':') == -1) ? input.id : input.id.split(":")[1]);
	if(!id.length){
		this.errors.push({field: "id", message: "Missing identifier"});
		return false;
	}
	if(id.length < 2){
		this.errors.push({field: "id", message: "Identifiers must be at least 2 characters long"});
		return false;
	}
	if(/[^a-zA-Z0-9_\-]/.test(id)){
		this.errors.push({field: "id", message: "Identifiers can only consist of alphanumerics"});
		return false;
	}
	if(!input.label.length){
		this.errors.push({field: "label", message: "Missing label"});
		return false;
	}
	if(input.label.trim().length < 3){
		this.errors.push({field: "label", message: "Class labels must be at least 3 characters long"});
		return false;
	}
	if(!input.comment.length){
		this.errors.push({field: "comment", message: "Missing class comment"});
		return false;
	}
	return true;
}

DacuraOntology.prototype.validateClassFormInput = function(input){
	if(!this.validateFormBasics(input)){
		return false;
	}
	if(input.ctype == "entity"){
		if(input.parents && typeof input.parents == 'object' && input.parents.length != 1){
			this.errors.push({field: "entity", message: "Entity classes should have only one parent class, " + input.parents.length + " found"});
			return false;
		}
	}
	else if(input.ctype == "enumerated"){
		if(!input.choices || input.choices.length == 0){
			this.errors.push({field: "enumerated", message: "No choices selected - you must create at least one choice for an enumerated type class."});
			return false;
		}
		var entries = 0;
		for(var i = 0; i < input.choices.length; i++){
			var choice = input.choices[i];
			if(choice.label || choice.comment || choice.id){
				entries++;
				if(!choice.label){
					this.errors.push({field: "enumerated", message: "Choice " + (i+1) + " is missing a label - every choice needs a label"});
					return false;
				}
				if(choice.label.length < 3){
					this.errors.push({field: "enumerated", message: "Choice " + (i+1) + " has a label that is too short - it must be at least 3 characters long"});
					return false;
				}
			}
		}
		if(entries == 0){
			this.errors.push({field: "enumerated", message: "No choices selected - you must create at least one choice for an enumerated type class."});
			return false;
		}
	}
	return true;
};

/**
 * These guys are not currently used - gone for simpler option of updating whole ontology at one go
 
DacuraOntology.prototype.getUpdateAsRDF = function(input){
	var rdf = this.getInputAsRDF(input, "update");
	var cid = this.id + ":" + input.id;
	//need to figure out which bits we changed
	var orig = this.classes[cid];
	for(var i in orig){
		if(typeof rdf[cid][i] == "undefined"){
			rdf[cid][i] = []; //code for deleting it in linked data land
		}
	}
	if(input.ctype == "enumerated"){
		var choices = this.getEnumeratedChoices(cid);
		for(var i = 0; i < choices.length; i++){
			if(typeof rdf[cid]["owl:oneOf"][choices[i].id] == "undefined"){
				rdf[cid]["owl:oneOf"][choices[i].id] = []; //code for deleting it in linked data land
			}
		}
	}
	return rdf;
} 

DacuraOntology.prototype.getUpdatePropertyAsRDF = function(input){
	return this.getInputPropertyAsRDF(input);
}


DacuraOntology.prototype.getInputPropertyAsRDF = function(input){
	var rdf = {};
	var cid = this.id + ":" + input.id;
	rdf[cid] = {};
	rdf[cid]["rdfs:label"] = input.label;
	if(input.comment.length){
		rdf[cid]["rdfs:comment"] = input.comment;
	}
	if(input.units){
		rdf[cid]["dacura:units"] = input.units;
	}
	rdf[cid]["rdfs:domain"] = input.domain;
	rdf[cid]["rdfs:range"] = input.range;
	if(input.rtype == "object" || input.rtype == "boxed"){
		rdf[cid]["rdf:type"] = "owl:ObjectProperty"; 
	}
	else {
		rdf[cid]["rdf:type"] = "owl:DatatypeProperty"; 
	}
	return rdf;
}

DacuraOntology.prototype.getInputAsRDF = function(input, mode){
	var rdf = {};
	var mode = (mode ? mode : "create");
	var cid = this.id + ":" + input.id;
	rdf[cid] = {};
	rdf[cid]["rdf:type"] = "owl:Class";
	rdf[cid]["rdfs:label"] = input.label;
	if(input.comment.length){
		rdf[cid]["rdfs:comment"] = input.comment;
	}
	if(typeof input.parents == "object" && input.parents.length > 0){
		rdf[cid]["rdfs:subClassOf"] = input.parents; 
	}
	else if(input.parents && input.parents.length){
		rdf[cid]["rdfs:subClassOf"] = input.parents; 
	}
	if(input.ctype == "enumerated"){
		rdf[cid]["rdfs:subClassOf"] = "dacura:Enumerated";
		if(mode == "update"){
			var orig_choices = this.getEnumeratedChoices(cid);
			rdf[cid]["owl:oneOf"] = {}; 
			for(var i = 0; i< input.choices.length ; i++){
				if(input.choices[i].label.length == 0) continue;
				var choice = {"rdf:type": cid, "rdfs:label": input.choices[i].label};
				if(input.choices[i].comment.length){
					choice['rdfs:comment'] = input.choices[i].comment;
				}
				var bnid = ((input.choices[i].id.substring(0, 2) == "_:") ? input.choices[i].id.substring(2) : input.choices[i].id);
				if(bnid.length){
					var new_one = true;
					for(var k = 0; k<orig_choices.length; k++){
						if(orig_choices[k].id == "_:" + bnid){
							new_one = false;
							break;
						}
					}
					if(new_one){
						choice[this.request_id_token] = bnid;
					}
					rdf[cid]["owl:oneOf"]["_:" + bnid] = choice;
				}
				else {
					rdf[cid]["owl:oneOf"]["_:" + this.id + "_" + ++this.bnid] = choice;			
				}
			}						
		}
		else {
			rdf[cid]["owl:oneOf"] = []; 
			for(var i = 0; i< input.choices.length ; i++){
				var choice = {"rdf:type": cid, "rdfs:label": input.choices[i].label};
				if(input.choices[i].comment.length){
					choice['rdfs:comment'] = input.choices[i].comment;
				}
				if(input.choices[i].id.length){
					choice[this.request_id_token] = input.choices[i].id;
				}
				rdf[cid]["owl:oneOf"].push(choice);
			}			
		}
	}
	if(input.ctype == "complex"){
		var assertions = this.readOtherAssertionsFromForm();
		for(var a in assertions){
			try {
				var val = JSON.parse(assertions[a]);
			}
			catch(e){
				val = assertions[a];				
			}
			rdf[cid][a] = val; 
		}
	}
	return rdf;
}
*/
