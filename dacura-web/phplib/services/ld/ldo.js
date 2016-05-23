
/**
 * LDO - Linked Data Object - for parsing the LDO objects sent in the result field of the api response
 */

function LDO(data){
	this.id = data.id;
	this.meta = typeof data.meta == "undefined" ? false : data.meta;
	this.contents = typeof data.contents == "undefined" ? false : data.contents;
	this.fragment_id = typeof data.fragment_id == "undefined" ? false : data.fragment_id;
	this.format = typeof data.format == "undefined" ? "json" : data.format;
	this.options = typeof data.options == "undefined" ? [] : data.options;
}

LDO.prototype.isEmpty = function(){
	if(typeof this.contents == "undefined") return true;
	if(typeof this.contents == "string") return this.contents.length == 0; 
	if(typeof this.contents == "object") return (isEmpty(this.contents) && this.contents.length == 0);
	return true;
};

LDO.prototype.status = function(){
	return this.meta.status;
}

LDO.prototype.getEmptyHTML = function(type){
	return "<div class='empty-ldcontents'>Empty</div>";
};

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

LDO.prototype.getMetaHTML = function(mode){
	return dacura.ld.wrapJSON(this.meta);	
};

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

LDO.prototype.url = function(){
	return this.meta.cwurl;
}


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

LDO.prototype.ldtype = function(){
	return this.meta.ldtype;
}
