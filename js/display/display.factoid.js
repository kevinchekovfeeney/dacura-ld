
DacuraFactoid.prototype.getRecordIcon = function(res, msg, cnt){
	cnt = (cnt ? cnt : "");
	msg = (msg ? msg : "");
	if(res == "error"){
		iconhtml = "<i class='fa fa-exclamation fa-fw' title='" + msg + "'>" + cnt + "</i>";		
	}
	else if(res == "warning"){
		iconhtml = "<i class='fa fa-warning fa-fw' title='" + msg + "'>" + cnt + "</i>";				
	}
	else if(res == "accept"){
		iconhtml = "<i class='fa fa-thumbs-up fa-fw' title='" + msg + "'>" + cnt + "</i>";				
	}
	return iconhtml;
}

DacuraFactoid.prototype.getValueHTML = function(){
	var cls = this.getDataSyntaxClass();
	var iconhtml = "";
	switch(cls){
	case "simple": 
		//iconhtml += "<i class='fa fa-cube fa-fw' title='Simple Value'></i>";
		break;
	case "complex": 
		if(this.parsed.datapoints && this.parsed.datapoints.length > 1){
			iconhtml += "<i class='fa fa-line-chart fa-fw' title='Multiple Datapoints'></i>" + this.parsed.datapoints.length;
		}
		else {
			iconhtml += "<i class='fa fa-cubes fa-fw' title='Complex Value'></i>";
		}
		break;
	case "empty": 
		iconhtml += "<i class='fa fa-battery-empty fa-fw' title='Empty'></i>";
		break;
	case "error": 
		iconhtml += "<i class='fa fa-exclamation-circle fa-fw' title='Error in coding syntax'></i>";
		break;
	case "warning": 
		iconhtml += "<i class='fa fa-warning fa-fw' title='Warning in coding syntax'></i>";
		break;	
	default: 
		iconhtml += "<i class='fa fa-exclamation-circle fa-fw' title='Unknown syntax class "+ cls + "'></i>";
		break;	
	}
	var val = (this.parsed ? this.parsed.value : this.original.value)
	var html = "<span class='factoid-detail label-group factoid-value' title='Value of the variable'>";
	html += "<span class='label-group-addon summary-icon'><i class='fa fa-bar-chart fa-fw'></i></span>";
	html += " <span class='factoid-value'>" + val + "</span> " + iconhtml;
	html += "</span>";		
	return html;
}

DacuraFactoid.prototype.getStatHTML = function(stat, title, icon, val, edit){
	var html = "<span class='factoid-stat label-group " + stat + "' title='" + title + "'>";
	html += "<span class='label-group-addon summary-icon'><i class='fa " + icon + " fa-fw'></i></span>";
	if(val) html +="<span class='factoid-stat-value'>" + val + "</span>";
	if(val && edit){
		html += " <span class='factoid-import-change'>change</span>";
		html += "<span class='dch factoid-import-changer'>";
		html += edit;
		html += "</span>";
	}
	html += "</span>";
	return html;
}


DacuraFactoid.prototype.getValueTypeIcon = function(){
	var html = "<img class='factoid-icon factoid-" + this.getDataSyntaxClass() + "' src='" + this.config.iconbase + this.getDataSyntaxClass() + ".png'>";
	return html;
}

DacuraFactoid.prototype.getResultIcon = function(which){
	var html = "";
	var sr = this.getDataSyntaxClass();
	if(sr && sr != "empty" && sr != "error"){
		if(this.warning){
			html = " <img class='factoid-icon factoid-warning' src='" + this.config.iconbase + "warning.png'>";	
		}
		else {
			html = sr + " <img class='factoid-icon factoid-success' src='" + this.config.iconbase + "success.png'>";
		}
	}
	return html;
}


DacuraFactoid.prototype.getPropertyIcon = function(){
	var t = this.connectionCategory();
	var html = "<img class='factoid-icon factoid-" + t + "' src='" + this.config.iconbase + t + ".png'>";
	return html;
}


DacuraFactoid.prototype.getModelPropertyScreenFiller = function(mode, mapURL){
	var def = {};
	if(mode == "create"){
		def.id = this.uniqid;
		if(typeof this.original == "object" && typeof this.original.label != "undefined"){
			def.label = this.original.label;
		}
		if(typeof this.original == "object" && typeof this.original.comment != "undefined"){
			def.comment = this.original.comment.trim();
		}
		else {
			def.comment = "";
		}
	}
	def.metadata = {};
	var wrgen = new webLocator();
	wrgen.pagelocator = this.pagelocator;
	if(mode != "external"){
		wrgen.url = window.location.href;
		def.metadata.harvested = [jQuery.extend({}, wrgen)];
	}

	if(typeof mapURL == "function"){
		wrgen.url = mapURL(window.location.href);		
	}
	else {
		wrgen.url = document.location.origin;
	}

	def.metadata.harvests = [jQuery.extend({}, wrgen)];
	return def;
}


//returns the html to decorate the factoid on the page 
dPageFactoid.prototype.decorate = function(css_class, id_prefix, func) {
	var html = "<div class='" + css_class + " " + this.getDataSyntaxClass()  + "' id='" + id_prefix + this.uniqid + "'>" 
		+ "<div class='embedded-factoid-header' data-value='" + this.uniqid + "'>" + this.getHTML(func) + "</div>";
	var fvid = id_prefix + this.uniqid + "-frameviewer";
	html += "<div class='embedded-frameviewer' id='" + fvid + "'></div>";
	html += "</div>";		
	return html;
}

//return html to represent the factoid
DacuraFactoid.prototype.getHTML = function(showPart){
	var html = "<span class='pagescan-factoid'>";
	if(showPart("locator")){
		if(showPart("locator.section") && this.pagelocator && (this.pagelocator.sectext || this.pagelocator.section)){
			var lab = (this.pagelocator.sectext ? this.pagelocator.sectext : this.pagelocator.section);
			html += "<span class='factoid-detail label-group factoid-section' title='Page section in which the variable occurs'>";
			html += "<span class='label-group-addon summary-icon'><i class='fa fa-th fa-fw'></i></span>";
			html += "<span class='factoid-value'>" + lab + "</span>";
			html += "</span>";
		}
		if(showPart("locator.sequence") && this.pagelocator && this.pagelocator.sequence){
			html += "<span class='factoid-detail label-group factoid-sequence' title='Sequence number of the variable'>";
			html += "<span class='label-group-addon summary-icon'><i class='fa fa-hashtag fa-fw'></i></span>";
			html += "<span class='factoid-value'>" + this.pagelocator.sequence + "</span>";
			html += "</span>";		
		}
	}
	if(showPart("label") && this.pagelocator && this.pagelocator.label){
		var iconhtml = "";
		if(this.connectors && this.connectors.length){
			iconhtml += "<i class='fa fa-cloud-upload fa-fw' title='" + this.getHarvestsLabel() + "'></i>";
		}
		else {
			iconhtml += "<i class='fa fa-question fa-fw' title='Label does not match any input rules'></i>";
		}
		html += "<span class='factoid-detail label-group factoid-label' title='Label of the variable'>";
		html += "<span class='label-group-addon summary-icon'><i class='fa fa-tag fa-fw'></i></span>";
		html += "<span class='factoid-value'>" + this.pagelocator.label + "</span>" + iconhtml;
		html += "</span>";
	}
	
	if(showPart("value")){
		html += this.getValueHTML(showPart);
	}
	if(showPart("harvests")){
		html += this.getHarvestsHTML(showPart);
	}
	if(showPart("harvested") && this.getHarvested()){
		//show the state of the harvesting... -> did it go to a or b
		html += "<span class='factoid-detail label-group factoid-harvested'>";
		html += "<span class='label-group-addon summary-icon'><i class='fa fa-arrow-up fa-fw'></i></span>";
		var hicon = this.getHarvestedIcon(harvests, candid);
		html += "<span class='factoid-value'>" + hicon + "</span>";
		html += "</span>";		
	}
	html += "</span>";
	return html;
}

DacuraFactoid.prototype.getHarvestsHTML = function(showPart){
	return this.getConnectionHarvestsHTML();
}



DacuraFactoid.prototype.getConnectionHarvestsHTML = function(frames){
	html = "";
	if(this.connectors && this.connectors.length){
		html += "<span class='factoid-detail label-group factoid-harvests'>";
		html += "<span class='label-group-addon summary-icon'><i class='fa fa-arrow-right fa-fw'></i></span>";
		var harvests = this.getRelevantHarvests(frames);
		if(harvests && harvests.length){
			hval = this.getHarvestsLabel(harvests);
		}
		else {
			hval = "<i class='fa fa-question fa-fw' title='No valid frames for " + entity_class + "'></i>";
		}
		html += "<span class='factoid-value'>" + hval + "</span>";
		html += "</span>";		
	}
	return html;
}

DacuraFactoid.prototype.getConnectionCategoryHTML = function(selhtml){
	var ocls = this.connectionCategory();
	var cls = this.getDataSyntaxClass();
	var showimp = (cls == "simple" || cls == "complex" || cls == "warning");
	var html = "";
	switch(ocls){
	case "unknown": 
		var msg = (showimp ? "No import specified" : false); 
		html = this.getStatHTML(ocls, "Unknown", "fa-question", msg, selhtml);
		break;
	case "harvests": 
		var msg = (showimp ? this.getHarvestedLabel() : false);
		
		html = this.getStatHTML(ocls, "Property matches an import rule", "fa-cloud-upload", msg, selhtml);
		break;
	case "harvested": 
		var msg = (showimp ? "imported to " + this.getHarvestedLabel() : false);
		html = this.getStatHTML(ocls, "Property definition", "fa-id-badge", msg, selhtml);
		break;
	}
	return html;
}

DacuraFactoid.prototype.getHarvestsLabel = function(harvests){
	var harvests = (harvests ? harvests : this.getHarvests());
	var msg = false;
	if(harvests.length){
		if(harvests.length > 1){
			msg = "Imports to " + harvests.length + " properties:";
			for(var i=0; i<harvests.length; i++){
				msg += " " + harvests[i].target;
			}
		}
		else {
			msg = "Imports to " + harvests[0].target;
		}
	}
	return msg;
}


DacuraFactoid.prototype.getHarvestedLabel = function(){
	var msg = "";
	if(size(this.harvested) > 1){
		msg = size(this.harvested) + " entities";
		var cnt = 0; 
		for(var ent in this.harvested){
			cnt += this.harvested[ent].length;
		}
		msg += " (" + cnt + " properties)";
	}
	else if(size(this.harvested) == 1){
		var props = this.harvested[firstKey(this.harvested)];
		if(props.length == 0){
			alert("no props for " + firstKey(this.harvested));
		}
		else if(props.length == 1){
			msg = "entity " + firstKey(this.harvested) + " property " + props[0].property;
		}
		else {
			msg = "entity " + firstKey(this.harvested) + " " + props.length + " properties";
		}
	}
	else {
		msg = "Nothing harvested";
	}
	return msg;
}

DacuraFactoid.prototype.getHarvestedIcon = function(harvests, candid){
	var harvested = this.getHarvested();
	var iconhtml = "";
	if(!harvested){
		iconhtml += "<i class='fa fa-battery-empty fa-fw' title='Not Harvested'></i>";
	}
	else if(size(harvested) > 1 || harvested[firstKey(harvested)].length > 1){
		var reses = {};
		for(var cid in harvested){
			for(var i = 0; i<harvested[cid].length; i++){
				var res = this.checkSingleHarvestRecord(harvested[cid][0], harvests, cid, candid);
				if(typeof reses[res.status] == "undefined"){
					reses[res.status] = res;
				}
				else {
					reses[res.status].count++;
					reses[res.status].msg += " - " + res.msg;
				}
				for(var k in reses){
					iconhtml += this.getRecordIcon(k, reses[k].msg, reses[k].count);
				}
			}
		}
		if(size(harvested) > 1){
			iconhtml += this.getRecordIcon("warning", "data has been imported to multiple entities", size(harvested));			
		}
	}
	else {
		var res = this.checkSingleHarvestRecord(harvested[firstKey(harvested)][0], harvests, firstKey(harvested), candid);
		iconhtml += this.getRecordIcon(res.status, res.msg)
	}
	return iconhtml;
}

DacuraFactoid.prototype.checkSingleHarvestRecord = function(fpr, harvests, cid, candid){
	var res = {status: false, msg: "no msg", count: 1};
	if(!(fpr.input && fpr.input.trim().length)){
		res.status = "error";
		res.msg = "No input in frame provenance record";
	}
	else if(!(this.original.value && this.original.value.trim().length)){
		res.status = "error";
		res.msg = "No input in factoid on page";
	}	
	else if(fpr.input.trim() != this.original.value.trim()){
		res.status = "error";
		res.msg = "Mismatch in imported value: " + fpr.input.trim() + " != " + this.original.value.trim();
	}
	else if(cid != candid){
		res.msg = "Data imported to different candidate. Imported to " + cid + " but page is set to import to " + candid;
		res.status = "warning";		
	}
	else {
		if(harvests){
			var wrong = false;
			for(var k =0; k<harvests.length; k++){
				if(harvests[k].target_class != fpr.property){
					res.msg = "Data imported to different property. Imported to " + fpr.property + " but page is set to import to " + harvests[k].target_class;
					res.status = "warning";
					return res;
				}
			}
		}
		res.msg = "Data imported successfully to " + fpr.property;
		res.status = "accept";
	}
	return res;
}


