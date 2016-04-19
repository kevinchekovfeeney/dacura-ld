dacura.widget = dacura.ld;
dacura.widget.entity_type = "widget";
dacura.widget.plurals.widget = "widgets";

dacura.widget.api.fetchentityclasses = function(graphid){
	xhr = {};
	xhr.url = dacura.system.apiURL("schema");
	xhr.url += "/structure/";
	if(typeof graphid != "undefined"){
		xhr.url += encodeURIComponent(graphid);
	}
	return xhr;
}

dacura.widget.api.fetchclassproperties = function(graphid, classname){
	xhr = {};
	xhr.data = {
			"graph": graphid,
			"class": classname
	};
	//alert(encodeURIComponent(classname));
	xhr.url = dacura.system.apiURL("schema") + "/structure/";
	//xhr.url += "/structure/"+ encodeURIComponent(graphid) + "/" + encodeURIComponent(classname);
	return xhr;
}

dacura.widget.fetchClasses = function(onwards, targets, graphid){
	var ajs = dacura.widget.api.fetchentityclasses(graphid);
	var msgs = { "busy": "Fetching entity classes from server", "fail": "Failed to retrieve entity classes"};
	ajs.handleResult = function(obj){
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}


dacura.widget.fetchClassProperties = function(graphid, classname, onwards, targets){
	var ajs = dacura.widget.api.fetchclassproperties(graphid, classname);
	var msgs = { "busy": "Fetching entity classes from server", "fail": "Failed to retrieve entity classes"};
	ajs.handleResult = function(obj){
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}

