
/**
 * LDOUpdate object for interpreting LDOUpdate objects returned in responses by the Dacura API
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
}

LDOUpdate.prototype.getHTML = function(mode){
	if(!this.inserts && !this.deletes){
		html = "<div class='info'>No Updates</div>";		
	}
	else {
		html = "<h2>Forward</h2>";
		html += "<div class='dacura-json-viewer forward-json'>";
		html += JSON.stringify(this.inserts);
		html += "</div>";
		html += "<h2>Backward</h2>";
		html += "<div class='dacura-json-viewer backward-json'>";
		html += JSON.stringify(this.deletes);
		html += "</div>";
		if(this.changed){
			html += "<h2>After</h2>";
			html += this.changed.getHTML(mode);
		}
		if(this.original){
			html += "<h2>Before</h2>";
			html += this.original.getHTML(mode);
		}
	}
	return html;
}
