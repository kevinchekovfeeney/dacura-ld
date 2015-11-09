dacura.schema = {}
dacura.schema.apiurl = dacura.system.apiURL();
dacura.schema.api = {};

dacura.schema.api.get = function(){
	xhr = {};
	xhr.url = dacura.schema.apiurl;
	return xhr;
}

dacura.schema.api.get_ontology = function(n, opts){
	xhr = {data: opts};
	xhr.data.entity_type = "ontology";
	//xhr.url = dacura.system.serviceApiURL('ld') + "/" +  n;
	xhr.url = dacura.schema.apiurl + "/" +  n;
	return xhr ;
}

dacura.schema.api.get_graph = function(n, opts){
	xhr = {data: opts};
	xhr.data.entity_type = "graph";
	xhr.url = dacura.schema.apiurl + "/" +  n;
	//xhr.url = dacura.system.serviceApiURL('ld') + "/" +  n;
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
	//xhr.url = dacura.system.serviceApiURL('ld');
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
//	xhr.url = dacura.system.serviceApiURL('ld') + "/" + id;
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
//	xhr.url = dacura.system.serviceApiURL('ld') + "/" + id;
	xhr.url = dacura.schema.apiurl + "/" +  id;
    return xhr;	
};


dacura.schema.api.import_ontology = function(format, entid, title, url, payload){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/import";
	xhr.type = "POST";
	if(format == 'upload'){
		xhr.url += "?id=" + encodeURIComponent(entid) + "&title=" + encodeURIComponent(title) + "&url=" + encodeURIComponent(url);
		xhr.data = payload;
		xhr.processData= false;
	    xhr.contentType = payload.type
	}
	else {
		xhr.data ={ "format": format, "id": entid, "payload": payload, "title" : title, "url": url};		
	}
	return xhr;	
}

dacura.schema.api.calculateDependencies = function(entid){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/" + entid + "/dependencies";
	//xhr.type = "POST";
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

dacura.schema.calculateDependencies = function(id, ownwards){
	var ajs = dacura.schema.api.calculateDependencies(id);
	var msgs = { "busy": "Calculating Ontology " + id + " Dependencies from Server", "fail": "Failed to calculate dependencies for " + id};
	ajs.handleResult = function(obj){
		dacura.schema.showDependencies(obj);
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs);
}

dacura.schema.fetchGraph = function(id, args, onwards, from){
	var ajs = dacura.schema.api.get_graph(id, args);
	var msgs = { "busy": "Fetching graph " + id + " from Server", "fail": "Failed to retrieve graph " + id};
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		dacura.schema.showGraph(obj);
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	//ajs.handleJSONError = dacura.editor.drawUpdateResult;
	dacura.system.invoke(ajs, msgs);	
}

//signature of calls produced by the editor
dacura.schema.fetchOntology = function(id, args, onwards, from){
	var ajs = dacura.schema.api.get_ontology(id, args);
	var msgs = { "busy": "Fetching entity " + id + " from Server", "fail": "Failed to retrieve entity " + id};
	if(typeof from != "undefined"){
		if(from){
			msgs.busy += ": " + from;
			msgs.success += ": " + from;
		}	
	}
	ajs.handleResult = function(obj){
		dacura.schema.showOntology(obj);
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs);
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
dacura.schema.updateOntology = function(id, uobj, onwards, type, test){
	var data = dacura.schema.gatherOntologyDetails();
	uobj.details = data;
	var ajs = dacura.schema.api.update_ontology(id, uobj, test);
	var msgs = { "busy": "Updating ontology " + id + "", "fail": "Failed to update ontology " + id};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs);
}


dacura.schema.updateGraph = function(id, uobj, onwards, type, test){
	var ajs = dacura.schema.api.update_graph(id, uobj, test);
	var msgs = { "busy": "Updating ontology " + id + "", "fail": "Failed to update ontology " + id};
	ajs.handleResult = onwards;
	ajs.handleJSONError = onwards;
	dacura.system.invoke(ajs, msgs);
}

dacura.schema.validateGraphOntologies = function(onts, tests){
	var ajs = dacura.schema.api.validate_graph_ontologies(onts, tests);
	var msgs = { "busy": "validating graph ontologies", "fail": "Failed to validate ontologies for graph"};
	ajs.handleTextResult = function(text){
		var body = onts.length + " Ontologies";//
		if(typeof tests == "object"){
			body += " " + tests.length + " Tests";
		}
		else {
			body += " All Tests";
			tests = "all";
		}
		var extra = {"ontologies": onts, "tests": tests};
		dacura.system.showSuccessResult(body, extra, text);
	}
	ajs.handleResult = function(json){
		var body = json.length + " Errors identified";
		dacura.system.showErrorResult(body, json, "Validation Failed");
	};
	dacura.system.invoke(ajs, msgs);	
}


dacura.schema.fetchSchema = function(onwards){
	var ajs = dacura.schema.api.get();
	var msgs = { "busy": "Fetching schema from server", "fail": "Failed to retrieve schema"};
	var self = this;
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs);
}

dacura.schema.updateSchema = function(onwards){
	var ajs = dacura.schema.api.update(dacura.schema.currentschema);
	var msgs = { "busy": "Updating Schema.", "fail": "Failed to update schema", "success": "Schema successfully updated"};
	var self = this;
	ajs.handleResult = function(obj){
		dacura.system.showSuccessResult();
		dacura.schema.currentschema = obj.result;
		if(typeof onwards != "undefined"){
			onwards(obj.result);
		}
	}
	dacura.system.invoke(ajs, msgs);
}

dacura.schema.importOntology = function(format, entid, enttitle, enturl, payload){
	var ajs = dacura.schema.api.import_ontology(format, entid, enttitle, enturl, payload);
	var msgs = { "busy": "Importing new ontology.", "fail": "Failed to import Ontology", "success": "Ontology successfully imported"};
	ajs.handleResult = function(obj){
		dacura.system.showSuccessResult();
		window.location.href = dacura.system.pageURL() + "/" + obj.result.id;
	}	
	dacura.system.invoke(ajs, msgs);
};

dacura.schema.createGraph = function(name){
	var data = {"type": "graph", "meta" : {"@id": name}};
	var ajs = dacura.schema.api.create_graph(data);
	var msgs = { "busy": "Creating new graph", "fail": "Failed to create new graph", "success": "graph successfully created"};
	dacura.system.invoke(ajs, msgs);	
}

