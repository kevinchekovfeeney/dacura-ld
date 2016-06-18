/**
 * @file Javascript object for interpreting LDOUpdate objects returned in responses by the Dacura API
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function LDOUpdate
 * @constructor
 * @param data {Object} the ldo update json object (returned by API)
 */
function LDOUpdate(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.inserts = typeof data.insert == "undefined" ? false : data.insert;
	this.deletes = typeof data["delete"] == "undefined" ? false : data["delete"];
	//this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
	this.changed = typeof data.changed == "undefined" ? false : new LDO(data.changed);
	this.original = typeof data.original == "undefined" ? false : new LDO(data.original);
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
}

/**
 * @summary Retrieves the linked data type (candidate, ontology, graph) of the update 
 * @returns {String}
 */
LDOUpdate.prototype.ldtype = function(){
	return this.meta.ldtype;
}

/**
 * @summary is the update empty (no update)? 
 * @returns {Boolean} true if empty
 */
LDOUpdate.prototype.isEmpty = function(){
	return size(this.inserts) == 0 && size(this.deletes) == 0;
}

/**
 * @summary retrieves the api arguments used to generate the update
 * @returns {Object} format, options, ldtype
 */
LDOUpdate.prototype.getAPIArgs = function(){
	var args = {
		"format": this.format,
		"options": this.options,
		"ldtype": this.meta.ldtype
	};
	return args;
}

/**
 * @summary generates html to show the forward and backward commands of the update
 * @returns {String} html
 */
LDOUpdate.prototype.getCommandsHTML = function(){
	if(!this.inserts && !this.deletes){
		var html = "<div class='info'>No Updates</div>";		
	}
	else {
		var html = dacura.ld.getJSONViewHTML(this.inserts, this.deletes);	
	}
	return html;
};

/**
 * @summary generates html to show the update object
 * @returns {String} html
 */
LDOUpdate.prototype.getHTML = function(mode){
	var html = "";
	var box = (typeof box == "string") ? box : "";
	if(this.contents){
		if(typeof this.contents.meta == "object"){
			if(size(this.contents.meta) > 0){
				html += "<h3>Update to Metatdata</h3>" ;
				html += dacura.ld.getJSONUpdateViewHTML(this.contents.meta);
			}
		}
		if(typeof this.contents.contents == "object"){
			if(size(this.contents.contents) > 0){
				html += "<h3>Update to Contents</h3>";
				
				html += "<div class='updates-contents-viewer dacura-json-viewer'>"+ JSON.stringify(this.contents.contents, 0, 4) + "</div>";
			}
		}	
	}
	return (html.length ? html : "<div class='dacura-error'>No contents in ldo update object</div>");
}

/**
 * @summary make these the same function
 */
LDOUpdate.prototype.getContentsHTML = LDOUpdate.prototype.getHTML;
