/*
 * page scanner object - responsible for scanning page and 
 */
dacura.pageScanner = {};
dacura.pageScanner.init = function(bodyContents, dataset_context, config, modelmode){
	this.factoids = {};
	this.sequence = [];
	this.factoid_id_prefix = "dacura_scanned_fact";
	this.factoid_css_class = this.factoid_id_prefix;
	this.selected_factoid_css = "dacura_selected_fact";
	this.context_jquery_selector = ':header';//':header span.mw-headline'
	this.regex = "(♠([^♠♥♣]*)♣([^♠♥]*)♥)([^♠]*)";
	this.stats = [];
	this.originalBody = bodyContents;
	this.config = config;
	this.factoid_config = config.factoid_config;
	this.loadFactoidHandler = (typeof config.load_callback == "function" ? config.load_callback : false);
	this.dataset_context = dataset_context;
}

dacura.pageScanner.loadFactoid = function(fid){
	if(typeof this.loadFactoidHandler == "function"){
		//this.loadFactoidHandler(fid);
		this.showFactoid(fid);
	}
}

dacura.pageScanner.showFactoid = function(fid){
	dconsole.loadExtra(JSON.stringify(this.factoids[fid]))
}

dacura.pageScanner.getScanSummaryHTML = function(){
	this.generateStats();
	var html = "<div class='page-scan-summary'><dl>";
	for(var i in this.stats){
		html += "<dt class='" + this.stats[i].css + "'>" + this.stats[i].label + "</dt><dd class='" + this.stats[i].css + "'>" + this.stats[i].value + "</dd>";
	}
	html += "</dl></div>";
	return html;
}

//any of these guys may be overwritten by target specific page scanning scripts


dacura.pageScanner.generateStats = function (){
	this.stats = {"variables": 	{css: "variables", label: "total", value: 0}, 
		"datapoints": 	{css: "total", label: "datapoints", value: 0}
	};
	for(var i in this.factoids){
		var ccls = this.factoids[i].connectionCategory();
		if(ccls && ccls.length){
			if(typeof this.stats[ccls] == "undefined"){
				this.stats[ccls] = {css: ccls, label: ccls, value: 1, data: this.factoids[i].data.length};
			}
			else {
				this.stats[ccls] = this.factoids[i].addToSummaryEntry(this.stats[ccls]);
			}
		}
		this.stats.variables.value++;
		this.stats.datapoints.value += this.factoids[i].data.length;
	}
	for(var i in this.factoids){
		var scls = this.factoids[i].statisticalClass();
		if(scls && scls.length){
			if(typeof this.stats[scls] == "undefined"){
				this.stats[scls] = this.factoids[i].getAsSummaryEntry();
			}
			else {
				this.stats[scls] = this.factoids[i].addToSummaryEntry(this.stats[scls]);
			}
		}
	}
}

//injects html / css into the page to highlight factoids that were found in the page
dacura.pageScanner.displayFactoids = function (){
	var npage = "";//we build up the new page body from scratch by stitching the updates into the page text and doing a full text update
	var npage_offset = 0;
	for(var i in this.factoids){
		var decorated = this.factoids[i].decorate(this.factoid_css_class, this.factoid_id_prefix);
		npage += this.originalBody.substring(npage_offset, this.factoids[i].location) + decorated;
		npage_offset = this.factoids[i].location + this.factoids[i].original.length;
	}
	jQuery(this.config.jquery_body_selector).html(npage + this.originalBody.substring(npage_offset));
	jQuery("." + this.factoid_css_class).click(function(){
		var factoid_id = jQuery(this).attr("id").substring(dacura.pageScanner.factoid_id_prefix.length);
		dacura.pageScanner.loadFactoid(factoid_id);
	});
	jQuery("." + this.factoid_css_class).hover(function(){
		jQuery(this).addClass(this.selected_factoid_css);
	},function() {
		jQuery( this ).removeClass( this.selected_factoid_css );
	});
};

//we first send all values to the parser service, update the 
dacura.pageScanner.parseValues = function(url, callback){
	var pfacts = [];
	var fact_ids = [];
	if(this.factoids.length == 0){
		console.log("No factoids were found in " + window.location.href);	
		return;
	}
	for(i in this.factoids){
		if(this.factoids[i].value.length && !this.factoids[i].parsed){
			pfacts[pfacts.length] = this.factoids[i].value;
			fact_ids[fact_ids.length] = i;			
		}
	}
	if(pfacts.length == 0){
		//whole page parsed analysed already
		return callback();
	}
	xhr = {};
	xhr.data = { "data" : JSON.stringify(pfacts)};
	xhr.url = url;
	xhr.type = "POST";
	$.ajax(xhr)
	.done(function(response, textStatus, jqXHR) {
		try {
			var results = JSON.parse(response);
			for(i in results){
				//dacura.pageScanner.factoids[fact_ids[i]].result_code = results[i].result_code;
				dacura.pageScanner.factoids[fact_ids[i]].setParsedResult(results[i]);
			}
			callback();
		}
		catch(e){
			dconsole.writeResultMessage("error", "Failed to parse server parse response", e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		dconsole.writeResultMessage("error", "Failed to contact server to parse variables", jqXHR.responseText);
	});
};

dacura.pageScanner.scan = function(connectors, locators, updcallback, complete){
	var pagecontexts = this.calculatePageContexts(this.originalBody, this.context_jquery_selector);
	var rawfacts = this.regexScan(this.regex, this.originalBody);
	this.assembleFactoids(rawfacts, pagecontexts);
	this.findConnections(connectors, locators);
	this.categoriseFactoids();
	var callback = function(){
		var ncomplete = function(results){
			dacura.pageScanner.displayFactoids();
			complete(results);
		}
		dacura.pageScanner.analyse(updcallback, ncomplete);
	}
	if(this.config.parser_url){
		this.parseValues(this.config.parser_url, callback);
	}
	else {
		callback();
	}
}; 

//in general, every factoid can be: 
//A. associated with a property definition (via property definition locator) (e.g. code page entries)
	//clicking on such factoids should always bring up the relevant definition in the console. 
	//the model can be imported from a codebook but never shadows it...
//											or
//B. associated with a property datapoint (via property data locator match) (normal code pages)
//we should always include links to load either the property data list and the property definition into the console, factoids can be associated with properties in 3 ways:
	//imported - data has already been imported from this location, and fully owned by dacura which will ignore any subsequent changes
	// 			 we should just highlight any changes between the value that was imported and the existing value....
	//shadowed - data has already been imported successfully by dacura from this factoid location but the data may change in 
	//			 which case the new value will be reimported to replace the existing value
	//not imported: data has not been imported from this factoid location before
//											or
//C. not associated with a property
//in codebook building mode, it means - 'can be imported'
//in harvesting mode, it means, not recognised - possible typo
//When it comes to the data dimension, we deal with the following sub-categories: 
//imported 
//unchanged - provide link to show exactly what property values this datapoint was imported to - and doubleplusgood sign
//changed - provide link as above, provide options to turn on shadowing, re-import -> can never 'unimport' 
//shadowed
//unchanged - provide link to show exactly what property values this datapoint was imported to - and doubleplusgood sign
//changed - attempt auto-import if value allows - extra plus bad if fails
//not imported - provide link to enable import if value allows. 
dacura.pageScanner.categoriseFactoids = function(){
	for(var i in this.factoids){
		var foid = this.factoids[i];
		if(!foid.location.target_type){
			this.factoids[i].addTag("type", "unknown");
		}
		else {
			//type is either property, class or data
			this.factoids[i].addTag("type", foid.location.target_type);
			//status is either imported, shadowed or new
			this.factoids[i].addTag("status", foid.location.status);
			if(foid.location.value && foid.location.value == this.factoids[i].value){
				this.factoids[i].addTag("since_import", "unchanged");			
			}
			else {
				this.factoids[i].addTag("since_import", "changed");		
			}
		}
	}
};


//when it comes to the values, for those that are not associated with a property, the classification is, as before: 
//empty
//error - does not scan
//warning - goes against encoding best-practice
//simple - single datapoint with simple value
//complex - complex codes in single datapoint (date boundaries, uncertainty)
//multiple - multiple values (disagreements, multiple non date-overlapping values)
//for all property-associated factoids, we know more things, where we have multiple values, each value will be assessed seperately
//value_import_result: 
	//success: automated import procedure worked
	//warning: automated import triggered best practice warnings but will work (we either push these to pending or to accepted)
	//failure: the primary value information could not be imported through the automated process (type checking)
//value_imported_to: some structure showing what the value has become with some information about what was done with it
//annotation_import_result: 
	//success: high confidence that the surrounding information was correctly imported. 
	//empty: all good - show it
	//warning: captured context but it contains complex internal structure that will need human intervention -> pending...
	//failure: couldn't make anything of what was found, what was there couldn't be turned into reasonable context...
//annotation_imported_to: some structure 
dacura.pageScanner.analyse = function(update, complete){
	var max = 100;
	var launched = 0;
	var results = [];
	var ucomplete = function(res){
		launched--;
		results.push(res);
		if(launched <= 0){
			complete(results);
		}
	}
	function deferUntilBeneathThreshold(method, a1, a2) {
		if (launched < max)
	    	method(a1, a2);
		else
	    	setTimeout(function() { deferUntilBeneathThreshold(method, a1, a2) }, 50);
	}
	var never_launched = true;
	for(var i in this.factoids){
		if(this.factoids[i].result_code != "error" && this.factoids[i].result_code != "empty"){
			var upd = this.factoids[i].forAPI();
			if(upd){
				never_launched = false;
				launched++;
				deferUntilBeneathThreshold(update, upd, ucomplete); 
			}
		}
	}
	if(never_launched){
		complete(results);	
	}
};

//in codebook building mode (schema definition), factoids can be associated with property definitions - in this mode all 
//factoids on a page are either mapped to existing properties or are not... -> those that are not are candidates for importing. 
dacura.pageScanner.findConnections = function(connectors, locators){
	for(var i in this.factoids){
		var foid = this.factoids[i];
		for(var j = 0; j<connectors.length; j++){
			if(this.locatorMatchesFactoid(foid, connectors[j].locator)){
				this.factoids[i].connector = connectors[j];
				continue;
			}			
		}
		if(!this.factoids[i].connector){
			for(var j = 0; j<locators.length; j++){
				if(this.locatorMatchesFactoid(foid, locators[j])){
					//make the connection...
					this.factoids[i].locator = locators[j];
					continue;
				}			
			}			
		}
	}
	for(var j = 0; j<connectors.length; j++){
		var nfactoid = this.locatorCreatesFactoid(connectors[j].locator);
		if(nfactoid){
			this.factoids[nfactoid.id] = nfactoid;
		}			
	}
	for(var j = 0; j<locators.length; j++){
		if(!this.factoids[j].connector){
			var nfactoid = this.locatorCreatesFactoid(connectors[j].locator);
			if(nfactoid){
				this.factoids[nfactoid.id] = nfactoid;
			}			
		}
	}			
} 

dacura.pageScanner.locatorCreatesFactoid = function(locator){
	if(locator.type == "pattern"){
		var relevanthtml = (locator.jquerySelector ? jQuery(locator.jquerySelector).html() : this.originalBody);
		
		return false;
	}
	return false;
};

dacura.pageScanner.locatorCreatesFactoid = function(factoid, locator){
	if(locator.type == "factoid"){
		if(factoid.locator.id == locator.id && factoid.locator.label == locator.label && factoid.locator.sequence == locator.sequence){
			return true;
		}
	}
	return false;
}

dacura.pageScanner.cleanContextTitle = function(text){
	if(text.substring(text.length - 6) == "[edit]"){
		text = text.substring(text.length - 6); 
	}	
	return text;
}

dacura.pageScanner.calculatePageContexts = function(page, selector){
	var headerids = {};
	var pagecontexts = {};
	var pheaders = jQuery(selector);
	for(var j = 0; j< pheaders.length; j++){
		var hid = pheaders[j].id;
		if(hid.length){
			var htext = this.cleanContextTitle(jQuery(pheaders[j]).text());
			var regexstr = "id\\s*=\\s*['\"]" + escapeRegExp(hid) + "['\"]";
			var re = new RegExp(regexstr, "gmi");
			var hids = 0;
			var hmatch;
			while(heads = re.exec(page)){
				hmatch = heads.index;
				hids++;
			}
			if(hids != 1){
				console.log("failed to find unique id for element " + hid + " " + hids);
			}
			else {
				pagecontexts[hmatch] = {id: hid, text: htext};
			}
		}
	}
	return pagecontexts;
}

dacura.pageScanner.getFactContextLocator = function(factoid, pagecontexts){
	var hloc = 0;
	for(var loc in pagecontexts){
		if(loc < factoid.location && loc > hloc){
			hloc = loc;
		}
	}
	return pagecontexts[hloc];
}

dacura.pageScanner.regexScan = function(regex, html){	
	var regex = new RegExp(regex, "gm");
	var rawfacts = [];
	while(matches = regex.exec(html)){
		factParts = {
			"location": matches.index, 
			"original": matches[0],
			"head": matches[1],
			"label": matches[2].trim(),
			"value": matches[3].trim(),
			"after": matches[4].trim()
		};
		if(size(rawfacts) > 0){
			factParts.before = html.substring(rawfacts[rawfacts.length-1].location, factParts.original.length).trim();
		}
		else {
			factParts.before = html.substring(0, factParts.location).trim();
		}
		rawfacts.push(factParts);
	}
	return rawfacts;
}

dacura.pageScanner.assembleFactoids = function(rawfacts, pagecontexts){
	var locators = {};
	for(var i = 0; i < rawfacts.length; i++){
		var fid = this.generateFactoidID(rawfacts[i].label, i);
		var factParts = rawfacts[i];
		this.sequence.push(fid);
		if(pagecontexts){
			var locator = this.getFactContextLocator(factParts, pagecontexts);
			if(locator){
				factParts.locator = { type: "factoid"};
				factParts.locator.id = locator.id;
				var flocid = locator.id + factParts.label;
				if(typeof locators[flocid] != "undefined"){
					locators[flocid] = 0;
				}
				else {
					factParts.locator.sequence = ++locators[flocid];
				}
			}
		}
		this.factoids[fid] = new dPageFactoid(fid, factParts, this.factoid_config);
	}
}

dacura.pageScanner.idifyLabel = function(label, sequence){
	if(!label.length) {
		return "p_" + sequence;
	}
	var pname = toTitleCase(label);
	pname = pname.charAt(0).toLowerCase() + pname.slice(1);
	pname = pname.replace(/\W/g, '');
	return pname;
}

dacura.pageScanner.generateFactoidID = function(label, sequence){
	var starting_point = this.idifyLabel(label, sequence);
	if(typeof this.factoids[starting_point] == "undefined") {
		return starting_point;
	}
	else {
		var seq = 1;
		while(typeof this.factoids[starting_point+"_"+seq] != "undefined"){
			seq++;
		}
		return starting_point+"_"+seq;
	}
}

function dPageFactoid(uid, details, config){
	this.uniqid = uid;
	this.warning = false;
	this.data = [];
	this.config = config;
	this.location = details.location;//page offset in bytes of where factoid was located within body.
	this.original = details.original;//the full contents of the factoid section selected
	this.head = details.head;//the label and value parts....
	this.label = details.label;//the label by which the factoid is to be known 
	this.value = details.value;//the value associated with the factoid / label if present..
	if(!this.value.length){
		this.result_code = "empty";
	}
	else {
		this.result_code = "unparsed";
	}
	if(this.result_code == "warning"){
		this.warning = true;
	}
	this.annotation = this.generateAnnotation(details.before, details.after);
	this.process(details);//to allow for extensibility...
	this.tags = {};
}

dPageFactoid.prototype.setParsedResult = function(result){
	this.result_code = result.result_code;
	this.data = result.datapoints;
	if(this.data.length > 1){
		this.result_code = "multiple";
	}
	//factParts.notes = factParts.notes.split(/<[hH]/)[0].trim();
	///"value" => $val,
	//"result_code" => "",
	//"result_message" => "",
	//"datapoints" => array()

}

dPageFactoid.prototype.connectionCategory = function(){
	return this.tags['type'];
}


dPageFactoid.prototype.addTag = function(t, v){
	if(typeof this.tags[t] == "undefined"){
		this.tags[t] = v;
	}
	else if(typeof this.tags[t] == "object"){
		this.tags[t].push(v);
	}
	else {
		this.tags[t] = [this.tags[t]];
		this.tags[t].push(v);
	}
}

dPageFactoid.prototype.getPropertyIcon = function(){
	var t = this.connectionCategory();
	var html = "<img class='factoid-icon factoid-" + t + "' src='" + this.config.iconbase + t + ".png'>";
	return html;
}


dPageFactoid.prototype.getValueTypeIcon = function(){
	var html = "<img class='factoid-icon factoid-" + this.result_code + "' src='" + this.config.iconbase + this.result_code + ".png'>";
	return html;
}

dPageFactoid.prototype.getResultIcon = function(which){
	var html = "";
	if(this.result_code != "empty" && this.result_code != "error"){
		if(this.warning){
			html = " <img class='factoid-icon factoid-warning' src='" + this.config.iconbase + "warning.png'>";	
		}
		else {
			html = " <img class='factoid-icon factoid-success' src='" + this.config.iconbase + "success.png'>";
		}
	}
	return html;
}

dPageFactoid.prototype.forAPI = function(){
	var contents = this.getContentsAsLD();
	if(!contents) {
		return false;
	}
	var upd = {
		"contents": contents	
	}
	if(this.location.candidate_id){
		upd.cid = this.location.candidate_id;
	}
	else {
		upd.ctype = this.location.candidate_type;	
	}
	return upd;
}

dPageFactoid.prototype.getContentsAsLD = function(){

	if(this.location.target_type == "candidate" && this.location.import){
		return this.location.import(this);
	}
	else {
		//nothing we don't automatically try to import schemata at the moment. 
		return false;
	}
}

dPageFactoid.prototype.generateAnnotation = function(before, after){
	return after;
}

function cleanUpHTML(html){
	return html;
	if(typeof tmpdivx == "undefined"){
		tmpdivx = document.createElement("DIV");
	}
	//parse as xhtml to remove all extraneous / unclosed html tags
	if(html && html.length){
		//var x= jQuery(html).html();
		//alert(html);
		//alert(x);
		//return x;
		tmpdivx.innerHTML = html;
	    return tmpdivx.innerHTML;
		//parser = new DOMParser();
		//xmlDoc = parser.parseFromString(html,"text/html");
		
	}
	return html;
}

function removeHTML (html){
	return html;
	if(typeof tmpdivx == "undefined"){
		tmpdivx = document.createElement("DIV");
	}
	tmpdivx.innerHTML = html;
    return tmpdivx.innerText;
}



dPageFactoid.prototype.process = function(details){
	this.generateAnnotation(details.before, details.after);
}

dPageFactoid.prototype.getHTML = function(){
	var html = this.getPropertyIcon() + this.label + this.getValueTypeIcon() + this.value + this.getResultIcon() + this.annotation;
	return html;
}

dPageFactoid.prototype.getAsSummaryEntry = function(){
	var se = {css: this.result_code, label: this.result_code, value: 1, data: this.data.length};
	return se;
}

dPageFactoid.prototype.addToSummaryEntry = function(ostats){
	ostats.value++;
	ostats.data += this.data.length;
	return ostats;
}


//returns the html to decorate the 
dPageFactoid.prototype.decorate = function(id_prefix, css_class) {
	var html = "<div class='" + css_class + " " + this.result_code + "' id='" + id_prefix + this.uniqid + "'>" + this.getHTML() + "</div>";
	return html;
}

dPageFactoid.prototype.statisticalClass = function(){
	return this.result_code;
}
