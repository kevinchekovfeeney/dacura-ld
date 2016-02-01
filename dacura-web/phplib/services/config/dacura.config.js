/**
 * @file Javascript client code for config management service
 * @author Chekov
 * @license GPL V2
 */

 /** 
 * @namespace config
 * @memberof dacura
 * @summary dacura.config
 * @description Dacura javascript config service module. provides client functions for accessing the dacura configuration management api
 */
dacura.config = {};
dacura.config.apiurl = dacura.system.apiURL();

/**
 * @function getServiceTableRows
 * @memberof dacura.config
 * @summary transforms the services object returned by the API into an array of rows, suitable for passing to dacura.table functions
 * @param {Object} services a- n array, indexed by service ids of service configurations
 * @param {Object} collection - jsonified Collection object
 * @returns {Array} - an array of rows: {id, status, selector}
 */
dacura.config.getServiceTableRows = function(services, collection){
	var rows = [];
	for(s in services){
		var stat = (typeof services[s].status == "string" && services[s].status != "enable") ? "reject" : "accept";
		var row = {id: s, status: stat};
		if(typeof collection == "object"){
			if(stat == "reject" && (typeof collection.config != "object" || typeof collection.config.services != 'object' || typeof collection.config.services[s] != "object" || typeof collection.config.services[s].status != "string" || collection.config.services[s].status != "disable")){
				//row.selector = "disabled in server config";
				continue;
			}
			else if(typeof services[s].status_locked != "undefined" && services[s].status_locked == true){
				row.selector = "<span class='locked'>locked</span>";
			}
			else if(stat == "reject"){
				row.selector = "<button class='update-service' id='" + s + "-enable'>enable</button>";
			}
			else {
				row.selector = "<button class='update-service' id='" + s + "-disable'>disable</button>";					
			}
		}
		rows.push(row);
	}
	return rows;
}

/**
 * @function getFacetButtonHTML
 * @memberof dacura.config
 * @summary Generates the button to represent facets on the service configuration page
 * @param {String} id - the id of the button
 * @param {String} role - the role 
 * @param {String} rtitle - the title of the role
 * @param {String} facet - the facet
 * @param {String} facettitle  - the title of the facet
 * @param {boolean} inactive - true if the button is to be inactive
 * @returns {String} - html representation of button
 */
dacura.config.getFacetButtonHTML = function(id, role, rtitle, facet, facettitle, inactive){
	html = "<div class='dacura-facet-button active' id='" + id + "'>";
	html += "<span class='dacura-role " + role + "' title='" + rtitle + "'></span>";
	html += "<span class='dacura-role-label'>" + rtitle + "</span>";
	html += "<span class='facet-text'>" + facettitle + "</span>";
	if(typeof inactive == "undefined" || inactive == false){
		html += "<span class='dacura-facet-action'>";
		html += "<a href='javascript:facet_remove(\"" + id + "\", \"" + role + "\", \"" + facet + "\")'";
		html += "class='dacura-role-remove'>Remove Access</a>";
		html += "</span>";
	}
	html += "</div>";
	return html;
}

/**
 * @function getCollections
 * @memberof dacura.users
 * @summary retrieve list of collections in system
 * @param {function} onwards = the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.config.getCollections = function(onwards, pconf){
	var ajs = dacura.config.api.listing();
	var msgs = {"busy": "Retrieving list of collections on system", "fail": "Failed to retrive list of collections on server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function fetchCollection
 * @memberof dacura.users
 * @summary retrieve a collection settings
 * @param {string} id - the id of the collection in question
 * @param {function} onwards - the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 * @param {string} part - can be set to specify only a part of the collection config is required (settings, services...)
 */
dacura.config.fetchCollection = function(id, onwards, pconf, part){
	var ajs = dacura.config.api.getCollection(id, part);
	var msgs = {"busy": "Retrieving collection " + id + " configuration from server", "fail": "Failed to retrive configuration of collection "+ id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
}

/**
 * @function updateCollection
 * @memberof dacura.users
 * @summary retrieve a collection settings
 * @param {string} id - the id of the collection in question
 * @param {function} onwards - the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 * @param {string} part - can be set to specify only a part of the collection config is required (settings, services...)
 */
dacura.config.updateCollection = function(data, onwards, targets, part){
	var ajs = dacura.config.api.updateCollection(data.id, data, part);
	var msgs = {"busy": "Updating collection settings", "fail": "Failed to update collection " + data.id};
	ajs.handleTextResult = onwards;
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.deleteCollection = function(id, onwards, targets){
	var ajs = dacura.config.api.deleteCollection(id);
	var msgs = {"busy": "Deleting collection " + id, "fail": "Failed to delete collection " + id};
	ajs.handleTextResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.addCollection = function(data, onwards, targets){
	var ajs = dacura.config.api.createCollection(data);
	var msgs = {"busy": "Creating new collection", "fail": "Failed to create collection " + data.id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);
};

dacura.config.getLogs = function(onwards, targets){
	var ajs = dacura.config.api.getlogs(targets.opts);
	var msgs = {"busy": "Retrieving logs from server", "fail": "Failed to retrive logs from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, targets);	
}

dacura.config.api = {};
dacura.config.api.create = function (data){
	xhr = {"data": data};
	xhr.url = dacura.config.apiurl;
	xhr.type = "POST";
	return xhr;
};

dacura.config.api.del = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl;
	xhr.type = "DELETE";
	return xhr;
};

dacura.config.api.view = function (id){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl;
	return xhr;
};

dacura.config.api.listing = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl + "/list";
	return xhr;
};

dacura.config.api.createCollection = function(data){
	xhr = {};
	xhr.data = data;
	xhr.url = dacura.system.apiURL("config") + "/create";
	xhr.type = "POST";
	return xhr;	
}

dacura.config.api.deleteCollection = function (id){
	xhr = {};
	xhr.url = dacura.system.apiURL("config", id);
	xhr.type = "DELETE";
	return xhr;
};

dacura.config.api.getlogs = function(opts){
	xhr = {};
	xhr.data = opts;
	xhr.url = dacura.system.apiURL("config", dacura.system.cid()) + "/logs";
	return xhr;	
}

dacura.config.api.getCollection = function(id, part){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL("config", id);
	if(typeof part == "string"){
		xhr.url += "/" + part;
	}
	return xhr;	
}

dacura.config.api.updateCollection = function(id, data, part){
	var xhr = {};
	xhr.url = dacura.system.apiURL("config", id);
	if(typeof part == "string"){
		xhr.url += "/" + part;
	}
	xhr.type = "POST";
	xhr.data = JSON.stringify(data);
	xhr.dataType = "json";
    return xhr;	
}
