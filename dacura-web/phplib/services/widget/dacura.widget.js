dacura.widget = dacura.ld;
dacura.widget.entity_type = "widget";
dacura.widget.plurals.widget = "widgets";

dacura.widget.api.fetchentityclasses = function(graphid){
	xhr = {};
	xhr.url = dacura.system.serviceApiURL("schema");
	xhr.url += "/structure/";
	if(typeof graphid != "undefined"){
		xhr.url += graphid;
	}
	return xhr;
}

dacura.widget.fetchClasses = function(ownwards, targets, graphid){
	var ajs = dacura.widget.api.fetchentityclasses(graphid);
	var msgs = { "busy": "Fetching entity classes from server", "fail": "Failed to retrieve entity classes"};
	ajs.handleResult = function(obj){
		if(typeof onwards != "undefined"){
			onwards(obj);
		}
	}
	dacura.system.invoke(ajs, msgs, targets);
}