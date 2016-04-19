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
dacura.config.updateCollection = function(data, onwards, pconf, part){
	var ajs = dacura.config.api.updateCollection(data.id, data, part);
	var msgs = {"busy": "Updating collection settings", "fail": "Failed to update collection " + data.id};
	ajs.handleTextResult = onwards;
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function deleteCollection 
 * @memberof dacura.users
 * @summary delete a collection
 * @param {string} id - the id of the collection in question
 * @param {function} onwards - the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.config.deleteCollection = function(id, onwards, pconf){
	var ajs = dacura.config.api.deleteCollection(id);
	var msgs = {"busy": "Deleting collection " + id, "fail": "Failed to delete collection " + id};
	ajs.handleTextResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function addCollection  
 * @memberof dacura.users
 * @summary adds a new collection to the system
 * @param {Object} data - an object containing the details of the new collection.
 * @param {String} data.id - the id of the new collection
 * @param {String} data.title - the title of the new collection
 * @param {function} onwards - the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.config.addCollection = function(data, onwards, pconf){
	var ajs = dacura.config.api.createCollection(data);
	var msgs = {"busy": "Creating new collection", "fail": "Failed to create collection " + data.id};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);
};

/**
 * @function getLogs  
 * @memberof dacura.users
 * @summary retrieves the listing of logs from the server
 * @param {function} onwards - the success callback function
 * @param {DacuraPageConfig} pconf - page configuration object 
 */
dacura.config.getLogs = function(onwards, pconf){
	var ajs = dacura.config.api.getlogs(pconf.opts);
	var msgs = {"busy": "Retrieving logs from server", "fail": "Failed to retrive logs from server"};
	ajs.handleResult = onwards;
	dacura.system.invoke(ajs, msgs, pconf);	
}

/** 
 * @namespace api
 * @memberof dacura.config
 * @summary dacura.config.api
 * @description Dacura config service api - each one returns an object with url, type and data set, ready for ajaxing
 */
dacura.config.api = {};

/**
 * @function listing
 * @memberof dacura.config.api
 * @summary GET / list collections
 */
dacura.config.api.listing = function (){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.config.apiurl + "/list";
	return xhr;
};

/**
 * @function createCollection
 * @memberof dacura.config.api
 * @summary GET / list collections
 */
dacura.config.api.createCollection = function(data){
	xhr = {};
	xhr.data = data;
	xhr.url = dacura.system.apiURL("config") + "/create";
	xhr.type = "POST";
	return xhr;	
}

/**
 * @function deleteCollection
 * @memberof dacura.config.api
 * @summary DELETE to config api /
 */
dacura.config.api.deleteCollection = function (id){
	xhr = {};
	xhr.url = dacura.system.apiURL("config", id);
	xhr.type = "DELETE";
	return xhr;
};

/**
 * @function getlogs
 * @memberof dacura.config.api
 * @summary GET logs object
 * @param {Object} opts - options object
 */
dacura.config.api.getlogs = function(opts){
	xhr = {};
	xhr.data = opts;
	xhr.url = dacura.system.apiURL("config", dacura.system.cid()) + "/logs";
	return xhr;	
}

/**
 * @function getCollection
 * @memberof dacura.config.api
 * @summary GET collection object
 * @param {String} id - the id of the collection  
 * @param {String} part - the part of the collection  
 */
dacura.config.api.getCollection = function(id, part){
	xhr = {};
	xhr.data ={};
	xhr.url = dacura.system.apiURL("config", id);
	if(typeof part == "string"){
		xhr.url += "/" + part;
	}
	return xhr;	
}


/**
 * @function updateCollection
 * @memberof dacura.config.api
 * @summary GET collection object
 * @param {String} id - the id of the collection  
 * @param {String} data - the json collection object
 * @param {String} part - the part of the collection  
 */
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
