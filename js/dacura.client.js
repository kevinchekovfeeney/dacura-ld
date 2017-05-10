function DacuraClient(params){
	params = this.setDacuraParams(params);
	this.reset();
	this.url = params.url;
	this.agent = dacura.sparams.agent;
	this.console_libs = dacura.sparams.console_libs;
	this.rest_extension = params.rest; 
	this.current_collection = params.collection;
	this.current_location = (params.location ? params.location : window.location.href);
	this.location_map = {};
	this.libraries = [];
	this.request_rate_limit = (params.request_rate_limit ? params.request_rate_limit : 50);
	this.pending_requests = 0;
}


DacuraClient.prototype.setDacuraParams = function(params){
	if(typeof dacura == "object" && typeof dacura.system == "object"){
		if(!params.url) params.url = dacura.url();
		if(!params.rest) params.rest = dacura.rest_path();
		if(typeof params.context == "undefined"){
			params.context = {collection: dacura.cid()};
		}
	}
	return params;
}

DacuraClient.prototype.loadLibrary = function(url, then){
	if(this.libraries.indexOf(url) !== -1){
		then();
	}
	else {
		this.libraries.push(url);
		jQuery.getScript(url, then);
	}
}

DacuraClient.prototype.loadLibraries = function(urls, then){
	var num = urls.length;
	var gotOne = function(){
		num--;
		if(num == 0){
			then();
		}
	}
	for(var i = 0; i<urls.length; i++){
		this.loadLibrary(urls[i], gotOne);
	}
}



DacuraClient.prototype.reset = function(success, fail){
	this.collections = {};
	this.libraries = {};
	this.current_collection = "";
	this.user = false;
	this.errors = [];
}


//needs to contact the server to get the cards we have to play with
DacuraClient.prototype.init = function(context, callback){
	var self = this;
	var loc = (context.location ? context.location : self.current_location);
	var nsuccess = function(caps){
		self.loadCapabilities(caps);
		if(loc){
			var ccallback = function(){
				if(context.mode && context.mode == "console"){
					self.loadConsole(callback);					
				}
				else {
					callback();
				}
			}
			var ncallback = function(){
				self.loadCollectionModel(ccallback);
			}
			self.setLocation(loc, ncallback);
		}
		else {
			alert("no loc");
		}
	}
	this.loadCapabilitiesFromServer(nsuccess);
}

DacuraClient.prototype.loadConsole = function(callback){
	var showConsole = function(){
		var dcon = new DacuraConsole(this);
		callback();
	}
	this.loadLibraries(this.console_libs, showConsole);
}


DacuraClient.prototype.setLocation = function(url, callback){
	var self = this;
	var scanComplete = function(){
		self.scanner.generateStats();
		callback();
	}
	var lmapget = function(lmap){
		var dol = function(){
			self.scanner = new DacuraPageScanner();
			self.scanner.init(lmap.scan);
			if(lmap.scan.autoload){
				var connectors = self.getHarvestConnectors(lmap.harvests);
				self.scanner.scan(connectors, lmap.harvested, scanComplete);
			}
			else {
				callback();
			}
		}
		if(lmap.scan){
			if(lmap.scan.script){
				self.loadLibraries(lmap.scan.script, dol)
			}
			else {
				dol();
			}
			//scan page...
		}
		self.current_location = url;
		self.location_type = "dacura:SeshatWikiPage";
		self.location_map = lmap;
	}	
	this.dispatch(this.APIURL("core", "location") + "?location=" + this.current_location , this.getXHRTemplate(lmapget));
}	


DacuraClient.prototype.getHarvestConnectors = function(harvests){
	var cons = {};
	if(typeof harvests == "object"){
		for(var i=0; i< harvests.length; i++){
			var pl = new pageLocator(harvests[i].pagelocator.label, harvests[i].pagelocator.section, false, harvests[i].pagelocator.sequence);
			var coid = pl.uniqid();
			var connector = new pageConnector(pl, "harvests", harvests[i].target, harvests[i].target_class);
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

DacuraClient.prototype.loadCapabilitiesFromServer = function(success, fail){
	this.dispatch(this.APIURL("core", "capabilities", false), this.getXHRTemplate(success, fail));
}	


DacuraClient.prototype.import = function(config){
	config = (config ? config : {});
	if(size(this.scanner.factoids)){
		var self = this;
		config.location = self.current_location;
		config.location_type = self.location_type;
		config.agent = self.agent;
		config.user = self.user.url;
		var fitModel = function(model){
			model.import(self.scanner.factoids, config);
		}
		this.loadCollectionModel(fitModel);
	}
}



DacuraClient.prototype.loadCollectionModel = function(success, fail){
	if(typeof this.collections[this.current_collection].model == "object"){
		return success(this.collections[this.current_collection].model);
	}
	var self = this;
	var nsuccess = function(caps){
		var model = self.loadModel(caps);
		var libs = model.getRequiredViewerLibs();
		var dol = function(){
			if(typeof success == "function"){
				success(model);
			}
		}
		self.loadLibraries(libs, dol);
	}
	this.dispatch(this.APIURL("core", "model"), this.getXHRTemplate(nsuccess, fail));
}

DacuraClient.prototype.loadModel = function(model_json){
	var cid = (typeof this.current_collection == "string" ? this.current_collection : "all");
	var dm = new DacuraModel(model_json, this);
	//dm.init();
	this.collections[cid]['model'] = dm;
	return dm;
}

DacuraClient.prototype.loadCapabilities = function(caps){
	this.user = ((typeof caps == 'object' && typeof caps.user == 'object')? caps.user : false);
	for(var colid in caps.collections){
		this.collections[colid] = new DacuraCollectionCapability(colid);
		this.collections[colid].init(caps.collections[colid]);
	}
}


DacuraClient.prototype.APIURL = function(service, entityid, collection_id){
	var url = this.url + this.rest_extension  + "/";
	if(typeof collection_id != "undefined"){
		if(collection_id){
			url += collection_id + "/";
		}
	}
	else {
		if(this.current_collection){
			url += this.current_collection + "/";
		}
	}
	url += service + "/";
	if(entityid && entityid.length){
		url += entityid;
	}
	return url;
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
	if(this.user && this.user.status != "anonymous") return true;
	return false;
}

DacuraClient.prototype.checkAgainstCapabilities = function(cid, col, type, id, action){
	this.errors = [];
	if(!col){
		this.errors.push({title: "Unknown Collection", "message": "You do not have access to a collection with id " + cid})
	}
	else {
		if(!col.hasAuthorityForAction(type, id, action)){
			this.errors.push({title: "Access Denied", "message": "You do not have access to " + action + " " + type + " " + id});
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
			var dobj = new DacuraOntology();
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
		if(typeof success == "function"){
			success(candidate);
		}
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

DacuraClient.prototype.getEmptyFrame = function(entity_class, gid, success, fail){
	cid = this.current_collection;
	var pparams = {"class": entity_class};
	if(gid){
		pparams.graph = gid;
	}
	var xhr = this.getFrameXHRTemplate(success, fail, pparams);
	xhr.type = "POST";
	return this.dispatch(this.APIURL("candidate", "frame"), xhr);
};

DacuraClient.prototype.getEmptyPropertyFrame = function(cls, propid, success, fail){
	var xhr = this.getFrameXHRTemplate(success, fail, {"class": cls, "property": propid});
	xhr.type = "POST";
	return this.dispatch(this.APIURL("candidate", "propertyframe"), xhr);
};

DacuraClient.prototype.getFilledFrame = function(id, success, fail){
	cid = this.current_collection;
	return this.dispatch(this.APIURL("candidate/frame", id), this.getFrameXHRTemplate(success, fail));
};

DacuraClient.prototype.getFilledPropertyFrame = function(candid, propid, success, fail){
	cid = this.current_collection;
	var xhr = this.getFrameXHRTemplate(nsuccess, fail, { "property": propid} );
	xhr.type = "POST";
	return this.dispatch(this.APIURL("candidate/propertyframe", candid), xhr);
};


/* Ontology API */
DacuraClient.prototype.createOntology = function(ontid, props, meta, success, fail, test){
	var ldcreate = this.APIArgs("create", "ontology", ontid, test);
	ldcreate.contents = (props ? props : {});
	if(meta){
		ldcreate.meta = meta;
	}
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
	var self = this;
	var done = xhr.done;
	var fail  = xhr.fail;
	delete(xhr.done);
	delete(xhr.fail);
	if(self.busy){
		xhr.beforeSend = self.busy;
	}
	var always = function(){
		self.pending_requests--;
		if(self.pending_requests <= 0 && self.notbusy){
			self.notbusy();
		}
	};
	xhr.url = url;
	var deferUntilLimit = function() {
		if(self.request_rate_limit && self.pending_requests >= self.request_rate_limit){
			setTimeout(function() { deferUntilLimit() }, 50);						
		}
		else {
			self.pending_requests++;	
			return $.ajax(xhr).done(done).fail(fail).always(always);	
		}
	}
	deferUntilLimit();
}


