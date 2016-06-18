/**
 * @file Javascript object for parsing and manipulating linked data objects
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function LDO
 * @constructor
 * @param data {Object} the ldo object returned by API
 */
function LDO(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
	this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
}

/**
 * @summary is the ldo empty (no contents)
 * @returns {Boolean} true if empty
 */
LDO.prototype.isEmpty = function(){
	if(typeof this.contents == "undefined") return true;
	if(typeof this.contents == "string") return this.contents.length == 0; 
	if(typeof this.contents == "object") return (isEmpty(this.contents) && this.contents.length == 0);
	return true;
};

/**
 * @summary returns the status of the result (accept|reject|pending)
 * @returns {String}
 */
LDO.prototype.status = function(){
	return this.meta.status;
}

/**
 * @summary Retrieve the api args that were used to fetch the ldo
 * @returns {Object} format, options, ldtype, version (if set)
 */
LDO.prototype.getAPIArgs = function(){
	var args = {
		"format": this.format,
		"options": this.options,
		"ldtype": this.meta.ldtype
	};
	if(this.meta.version != this.meta.latest_version){
		args.version = this.meta.version;
	}
	return args;
}

/**
 * @summary Retrieve the ldo's url
 * @returns {String}
 */
LDO.prototype.url = function(){
	return this.meta.cwurl;
}

/**
 * @summary Retrieves the full url of the ldo - with query string
 * @returns {String}
 */
LDO.prototype.fullURL = function(){
	var url = this.url() + "?";
	var args = {"ldtype": this.ldtype()};
	if(this.format){
		args['format'] = this.format;
	}
	if(this.meta.version != this.meta.latest_version){
		args['version'] = this.meta.version;
	}
	for(var i in this.options){
		if(i == "ns" || i == "addressable"){
			args['options[' + i + ']'] = this.options[i];
		}
	}
	for(var j in args){
		url += j + "=" + args[j] + "&";
	}
	return url.substring(0, url.length-1);
}

/**
 * @summary retrieves the linked data type (candidate, ontology, graph) of the ldo
 * @returns {String}
 */
LDO.prototype.ldtype = function(){
	return this.meta.ldtype;
}

/**
 * @summary reads updated contents from the page to populate the ldo from user input
 * @param jtarget {String} - the jquery selector for where to look for the input form.
 */
LDO.prototype.getUpdatedContents = function(jtarget){
	if(this.format == "json" || this.format== "jsonld"){
		return $(jtarget + ' textarea.dacura-json-editor').val();
	}
	else if(this.format == "triples" || this.format == "quads"){
		alert("update not done for this format");
	}	
	else if(this.format == "html"){
		alert("update not done for this format");		
	}
	else {
		return $(jtarget + ' textarea.dacura-text-editor').val();
	}
}


/**
 * @summary generates the html to show the ldo 
 * @param mode - edit, view, create...
 * @returns {String} html
 */
LDO.prototype.getHTML = function(mode){
	if(!this.contents && !this.meta){
		if(isEmpty(this.inserts) && isEmpty(this.deletes)){
			html = this.getEmptyHTML();		
		}
	}
	else {
		if(this.meta){
			html = this.getMetaHTML(mode);			
		}
		else {
			html = this.getEmptyHTML();		
		}
		if(this.contents){
			html += this.getContentsHTML(mode);			
		}
		else {
			html = this.getEmptyHTML();		
		}
	}
	return html;
};

/**
 * @summary generates the html to show an empty ldo 
 * @param type {String} - ignored
 * @returns {String} html
 */

LDO.prototype.getEmptyHTML = function(type){
	return "<div class='empty-ldcontents'>Empty</div>";
};

/**
 * @summary generates the html to show the ldo meta-data
 * @param mode - edit, view, create...
 * @returns {String} html
 */
LDO.prototype.getMetaHTML = function(mode){
	return dacura.ld.wrapJSON(this.meta);	
};

/**
 * @summary generates the html to show the ldo contents
 * @param mode {String} view|edit|create
 * @returns {String} html
 */
LDO.prototype.getContentsHTML = function(mode){
	if(this.format == "json" || this.format== "jsonld"){
		return dacura.ld.wrapJSON(this.contents, mode);
	}
	else if(this.format == "triples" || this.format == "quads"){
		return dacura.ld.getTripleTableHTML(this.contents, mode);
	}
	else if(this.format == "html"){
		if(this.ldtype() == "candidate" && typeof this.contents == "object"){
			return "<div id='dacura-frame-viewer'></div>" 
		}
		else {
			return "<div class='dacura-html-viewer'>" + this.contents + "</div>";
		}
	}
	else if(this.format == "svg"){
		return "<object id='svg' type='image/svg+xml'>" + this.contents + "</object>";
	}
	else {
		if(mode == "edit"){
			return "<div class='dacura-export-editor'><textarea class='dacura-text-editor'>" + this.contents + "</textarea></div>";			
		}
		else {
			return "<div class='dacura-export-viewer'>" + this.contents + "</div>";
		}
	}	
};

