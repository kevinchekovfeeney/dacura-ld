dacura.schema = {}
dacura.schema.apiurl = dacura.system.apiURL();
dacura.schema.api = {};

dacura.schema.api.get = function(){
	xhr = {};
	if(dacura.system.collectionid == "all" && dacura.system.datasetid == "all"){
		xhr.data = {"entity_type": "ontology"};
	}
	else {
		xhr.data = {"entity_type": "ontology"};	
	}
	xhr.url = dacura.system.serviceApiURL('ld');
	return xhr;
}

dacura.schema.api.get_ontology = function(n, opts){
	xhr = {data: opts};
	//xhr.data.entity_type = "ontology";
	xhr.url = dacura.system.serviceApiURL('ld') + "/" +  n;
	return xhr;
}

dacura.schema.api.get_graph = function(n){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/" + n;
	return xhr;
}

dacura.schema.api.update = function(sc){
	var xhr = {};
	xhr.url = dacura.schema.apiurl;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(sc);
    xhr.dataType = "json";
    return xhr;
}

dacura.schema.api.import_ontology = function(format, entid, payload){
	xhr = {};
	xhr.url = dacura.schema.apiurl + "/import?id=" + entid;
	xhr.type = "POST";
	if(format == 'upload'){
		xhr.data = payload;
		xhr.processData= false;
	    xhr.contentType = payload.type
	}
	else {
		xhr.data ={ "format": format, "id": entid, "payload": payload};		
	}
	return xhr;	
}

dacura.schema.api.update_ontology = function(id, uobj){
	var xhr = {};
	xhr.url = dacura.schema.apiurl + "/ontology/" + id;
	xhr.type = "POST";
	xhr.contentType = 'application/json'; 
	xhr.data = JSON.stringify(uobj);
	xhr.dataType = "json";
    return xhr;	
};

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
	ajs.handleJSONError = dacura.editor.drawUpdateResult;
	dacura.system.invoke(ajs, msgs);
}

//signature of calls produced by the editor
dacura.schema.updateOntology = function(id, uobj, onwards, type, test){
	var data = dacura.schema.gatherOntologyDetails();
	uobj.details = data;
	var ajs = dacura.schema.api.update_ontology(id, uobj);
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

dacura.schema.importOntology = function(format, dqs, payload){
	var ajs = dacura.schema.api.import_ontology(format, dqs, payload);
	var msgs = { "busy": "Importing new ontology.", "fail": "Failed to import Ontology", "success": "Ontology successfully imported"};
	dacura.system.invoke(ajs, msgs);
};



