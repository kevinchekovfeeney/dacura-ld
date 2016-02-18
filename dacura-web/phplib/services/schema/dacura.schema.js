dacura.schema = dacura.ld;
dacura.schema.plurals = {"ontology": "ontologies", "graph": "graphs"};

dacura.schema.api.import_ontology = function(format, ldoid, title, url, payload){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/import";
	xhr.type = "POST";
	if(format == 'upload'){
		xhr.url += "?id=" + encodeURIComponent(ldoid) + "&title=" + encodeURIComponent(title) + "&url=" + encodeURIComponent(url);
		xhr.data = payload;
		xhr.processData= false;
	    xhr.contentType = payload.type
	}
	else {
		xhr.data ={ "format": format, "id": ldoid, "payload": payload, "title" : title, "url": url};		
	}
	return xhr;	
}

dacura.schema.api.calculateDependencies = function(ldoid){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/" + ldoid + "/dependencies";
	return xhr;
}


dacura.schema.api.validate_ontology = function(n){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/validate/" + n;
	return xhr;
}

dacura.schema.api.validate_graph_ontologies = function(onts, tests){
	if(typeof tests == "undefined"){
		tests = "all";
	}
	var xhr = {};
	var data = {"tests" : tests, "ontologies" : onts};
	xhr.url = dacura.schema.apiurl + "/validate_ontologies/";
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}

dacura.schema.calculateDependencies = function(id, ownwards, targets){
	var ajs = dacura.schema.api.calculateDependencies(id);
	var msgs = { "busy": "Calculating ontology " + id + " dependencies from server", "fail": "Failed to calculate dependencies for " + id};
	ajs.handleResult = function(obj){
		dacura.schema.showDependencies(obj);
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.schema.importOntology = function(format, ldoid, ldotitle, ldourl, payload, targets){
	var ajs = dacura.schema.api.import_ontology(format, ldoid, ldotitle, ldourl, payload);
	var msgs = { "busy": "Importing new ontology.", "fail": "Failed to import Ontology", "success": "Ontology successfully imported"};
	ajs.handleResult = function(obj){
		dacura.system.showSuccessResult();
		window.location.href = dacura.system.pageURL() + "/" + obj.result.id;
	}
	ajs.handleJSONError = function(json){
		if(typeof targets == "undefined" || typeof targets.resultbox == "undefined" || !targets.resultbox ){
			targets = {resultbox: dacura.system.targets.resultbox};
		}
		if(typeof(dacura.ldresult) != "undefined"){
			dacura.ldresult.update_type = "import";
			var cancel = function(){
				$(targets.resultbox).html("");
			};
			dacura.ldresult.showDecision(json, targets.resultbox, cancel);			
		}
		else {
			dacura.system.showJSONErrorResult(json, targets.resultbox); 	
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
};


dacura.schema.validateGraphOntologies = function(onts, tests, targets){
	var ajs = dacura.schema.api.validate_graph_ontologies(onts, tests);
	var msgs = { "busy": "validating graph ontologies", "fail": "Failed to validate ontologies for graph"};
	ajs.handleTextResult = function(text){
		var body = onts.length + " Ontologies";
		if(typeof tests == "object"){
			body += " " + tests.length + " Tests";
		}
		else {
			body += " All Tests";
			tests = "all";
		}
		var extra = {"ontologies": onts, "tests": tests};
		dacura.system.showSuccessResult(body, extra, text, targets.resultbox);
	}
	ajs.handleResult = function(json){
		var body = json.length + " Errors identified";
		if(typeof dacura.ldresult == "undefined"){
			dacura.system.showErrorResult(body, json, "Validation Failed");
		}
		else {
			//dacura.system.showErrorResult(body, json, "Validation Failed");
			var x = dacura.ldresult.getErrorDetailsTable(json);
			dacura.system.showErrorResult(body + x , null, "Validation Failed", targets.resultbox);			
		}
		dacura.system.styleJSONLD();	
	};
	dacura.system.invoke(ajs, msgs, targets);	
};

dacura.schema.createGraph = function(name, targets, onwards){
	var data = {"type": "graph", "meta" : {"@id": name}};
	this.create(data, onwards, targets);
}


/*
 {}
dacura.schema.apiurl = dacura.system.apiURL();
dacura.schema.api = {};
dacura.schema.api.get = function(){
	xhr = {};
	xhr.url = dacura.schema.apiurl;
	return xhr;
}

dacura.schema.api.get_ontology = function(n, opts){
	xhr = {data: opts};
	xhr.url = dacura.schema.apiurl + "/" +  n;
	return xhr ;
}

dacura.schema.api.get_graph = function(n, opts){
	xhr = {data: opts};
	xhr.url = dacura.schema.apiurl + "/" +  n;
	return xhr;
}

dacura.schema.api.create_graph = function(data, test){
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	if(typeof test != "undefined"){
		data.test = true;
	}
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
	xhr.url = dacura.schema.apiurl;
	return xhr;	
}

dacura.schema.api.update_ontology = function(id, uobj, test){
	var xhr = {};
	xhr.type = "POST";
	xhr.contentType = 'application/json';
	uobj.type = "ontology";
	if(typeof test != "undefined"){
		uobj.test = true;
	}
	xhr.data = JSON.stringify(uobj);
	xhr.dataType = "json";
	xhr.url = dacura.schema.apiurl + "/" +  id;
    return xhr;	
};

dacura.schema.api.update_graph = function(id, uobj, test){
	var xhr = {};
	xhr.type = "POST";
	xhr.contentType = 'application/json';
	uobj.type = "graph";
	if(typeof test != "undefined"){
		uobj.test = true;
	}
	xhr.data = JSON.stringify(uobj);
	xhr.dataType = "json";
	xhr.url = dacura.schema.apiurl + "/" +  id;
    return xhr;	
};




dacura.schema.fetchGraph = function(id, args, onwards, targets, from){
	var ajs = dacura.schema.api.get_graph(id, args);
	var msgs = { "busy": "Fetching graph " + id + " from Server", "fail": "Failed to retrieve graph " + id};
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		if(typeof obj.decision != "undefined" && obj.decision != 'accept'){
			ajs.handleJSONError(obj); 
		}
		else {
			dacura.schema.showGraph(obj);
			if(typeof onwards != "undefined"){
				onwards(obj);
			}
		}
	}
	ajs.handleJSONError = function(json){
		if(typeof targets == "undefined" || typeof targets.resultbox == "undefined" || !targets.resultbox ){
			targets = {resultbox: dacura.system.targets.resultbox};
		}
		if(typeof(dacura.ldresult) != "undefined"){
			dacura.ldresult.update_type = "view";
			var cancel = function(){
				$(targets.resultbox).html("");
			};
			dacura.ldresult.showDecision(json, targets.resultbox, cancel);			
		}
		else {
			dacura.system.showJSONErrorResult(json, targets.resultbox); 	
		}
	}
	dacura.system.invoke(ajs, msgs, targets);	
}

//signature of calls produced by the editor
dacura.schema.fetchOntology = function(id, args, onwards, targets, from){
	var ajs = dacura.schema.api.get_ontology(id, args);
	var msgs = { "busy": "Fetching ldo " + id + " from Server", "fail": "Failed to retrieve ldo " + id};
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		if(typeof obj.decision != "undefined" && obj.decision != 'accept'){
			ajs.handleJSONError(obj); 
		}
		else {
			dacura.schema.showOntology(obj);
			if(typeof onwards != "undefined"){
				onwards(obj);
			}
		}
	}
	ajs.handleJSONError = function(json){
		if(typeof targets == "undefined" || typeof targets.resultbox == "undefined" || !targets.resultbox ){
			targets = {resultbox: dacura.system.targets.resultbox};
		}
		if(typeof(dacura.ldresult) != "undefined"){
			dacura.ldresult.update_type = "view";
			var cancel = function(){
				$(targets.resultbox).html("");
			};
			dacura.ldresult.showDecision(json, targets.resultbox, cancel);			
		}
		else {
			dacura.system.showJSONErrorResult(json, targets.resultbox); 	
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}

dacura.schema.drawVersionHeader = function(data){
	$('.version-title').html("version " + data.version);
	createtxt = "created " + timeConverter(data.version_created);
	$('.version-created').html(	createtxt);
	if(data.version_replaced > 0){	
		repltxt = "replaced " + timeConverter(data.version_replaced); 	
		$('.version-replaced').html(repltxt);
	}
	else {
		$('.version-replaced').html("");	
	}
	$('#version-header').show();
}



//signature of calls produced by the editor
dacura.schema.updateOntology = function(id, uobj, onwards, type, targets, test){
	var ajs = dacura.schema.api.update_ontology(id, uobj, test);
	var msgs = { "busy": "Updating ontology " + id + "", "fail": "Failed to update ontology " + id};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}


dacura.schema.updateGraph = function(id, uobj, onwards, type, targets, test){
	var ajs = dacura.schema.api.update_graph(id, uobj, test);
	var msgs = { "busy": "Updating graph " + id + "", "fail": "Failed to update graph " + id};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs, targets);
}


dacura.schema.fetchSchema = function(onwards, targets){
	var ajs = dacura.schema.api.get();
	var msgs = { "busy": "Fetching named graph configuration from the server", "fail": "Failed to retrieve named graphs"};
	var self = this;
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
	
};

dacura.schema.fetchOntologyList = function(onwards, targets){
	var ajs = dacura.schema.api.get();
	var msgs = { "busy": "Fetching list of ontologies from the server", "fail": "Failed to retrieve ontology"};
	var self = this;
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};




dacura.schema.createGraph = function(name, targets){
	var data = {"type": "graph", "meta" : {"@id": name}};
	var ajs = dacura.schema.api.create_graph(data);
	var msgs = { "busy": "Creating new graph", "fail": "Failed to create new graph", "success": "graph successfully created"};
	ajs.handleJSONError = function(json){
		if(typeof targets == "undefined" || typeof targets.resultbox == "undefined" || !targets.resultbox ){
			targets = {resultbox: dacura.system.targets.resultbox};
		}
		if(typeof(dacura.ldresult) != "undefined"){
			dacura.ldresult.update_type = "import";
			var cancel = function(){
				$(targets.resultbox).html("");
			};
			dacura.ldresult.showDecision(json, targets.resultbox, cancel);			
		}
		else {
			dacura.system.showJSONErrorResult(json, targets.resultbox); 	
		}
	}
	ajs.handleResult = ajs.handleJSONError;
	dacura.system.invoke(ajs, msgs, targets);

}*/

