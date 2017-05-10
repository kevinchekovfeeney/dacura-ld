/*
 * page scanner object - responsible for scanning page and 
 */

/**
 * @summary generate the html to display a triple table
 * @memberof dacura.pageScanner
 * @param bodyContents {String} HTML string of original page contents to be scanned
 * @param config {Object} Page Scanner Configuration Object
 * @param modelmode {Binary} true if the page is being scanned for model elements, false for datapoints
*/
function DacuraPageScanner(jquery_body_selector, bodyContents){
	this.factoids = {};//fid => factoid object of factoids found on page
	this.sequence = [];//sequence of factoid ids found on page in order of apprearance
	this.factoid_id_prefix = "dacura_scanned_fact";
	this.factoid_css_class = this.factoid_id_prefix;
	this.selected_factoid_css = "dacura_selected_fact";
	this.context_jquery_selector = ':header span.mw-headline';
	this.regex_config = {
		variable_label_start: "♠",
		variable_label_end: "♣",
		variable_value_end: "♥",
		variable_prelude: "",//"(<b>)?\s*",
		variable_value_stop: "",//"\s*(</b>)?",			
		stopping_patterns: ["</dd>", "<h"],
		indices: { full: 0, head: 1, label: 2, value: 3, after: 4}
	};
	//this.regex = "(♠([^♠♥♣]*)♣([^♠♥]*)♥)([^♠]*)";
	this.regex = this.getRegexString(this.regex_config);
	this.stats = {};
	this.originalBody = bodyContents;
	this.current_factoid = false;
	this.jquery_body_selector = jquery_body_selector;
	this.missingHarvested = [];//harvested records that point to missing factoids
	this.parsed = false;
}

DacuraPageScanner.prototype.getRegexString = function(config){
	var str = "(" + config.variable_prelude + config.variable_label_start;
	str += "([^" + config.variable_label_start + config.variable_label_end + 
		config.variable_value_end + "]*)" + config.variable_label_end;
	str += "([^" + config.variable_label_start + config.variable_value_end + "]*)" 
		+ config.variable_value_end + config.variable_value_stop + ")";
	str += "([^" + config.variable_label_start  + "]*)";
	//str += "|" + config.variable_label_start + ")";
	//str += "([^" + config.variable_label_start + "]*)";
	//alert(str);
	return str;
}

//called to scan the page - if a parser url is defined in config, the factoids are first sent for parsing
//then they are sent to the analyse function, before the display factoids function and the complete function are called in turn
//connectors : arrray of connector objects that apply to this page
	// connectors are factoids on the page that are already imported to the collection
//locators: array of locator objects that apply to this page -> locators are used to map factoids on the page to properties in the dataset
//updcallback: function that is used to test a variable value against the Dacura API
//complete: function called after analyse and display factoids have completed
DacuraPageScanner.prototype.scan = function(connectors, harvested, complete){
	console.time("calculating contexts");
	var pagecontexts = this.calculatePageContexts(this.originalBody, this.context_jquery_selector);
	console.timeEnd("calculating contexts");
	console.time("regex scan");	
	var rawfacts = this.regexScan(this.regex, this.originalBody);
	console.timeEnd("regex scan");
	console.time("assembling factoids");	
	this.assembleFactoids(rawfacts, pagecontexts);
	console.timeEnd("assembling factoids");
	console.time("finding harvested");	
	this.findHarvested(harvested);
	console.timeEnd("finding harvested");
	console.time("finding connections");	
	this.findConnections(connectors);
	console.timeEnd("finding connections");
	console.time("categorisation & stats");	
	this.categoriseFactoids();
	this.generateStats();
	console.timeEnd("categorisation & stats");	
	if(typeof complete == "function"){
		console.time("callback");	
		complete();
		console.timeEnd("callback");	
	}
}

//
/**
 * @summary calculates the context (in terms of byte offset of factoids encoded in the page
 * @param page {String} page html string
 * @param selector {String} jquery selector used to identify headings / sections within the page to allow for 
 * variable disambiguation
 * @return {Object} byte index => {id: header id, text: header text}
 */
DacuraPageScanner.prototype.calculatePageContexts = function(page, selector){
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

/**
 * @summary: uses a regular expression to parse all of the encoded facts found on the page.
 * @param regex {String} the regex to use
 * @param html {String} the HTML page to apply it to
 * @return rawfacts {Array} an array of objects: {
 * 	location: byte index of factoid, 
 *  original: full contents of matched factoid including annotations
 *  head: Contents of factoid formal part: ♠...♣...♥, 
 *  label: contents of factoid label ♠...♣, 
 *  value: contents of factoid formal value part: ♣...♥, 
 *  after: contents of html after factoid and before next factoid
 *  } 
 */
DacuraPageScanner.prototype.regexScan = function(regex, html){	
	var regex = new RegExp(regex, "gm");
	var rawfacts = [];
	while(matches = regex.exec(html)){
		factParts = {
			"location": matches.index, 
		};
		for(var i in this.regex_config.indices){
			var fp = matches[this.regex_config.indices[i]];
			factParts[i] = (fp ? (i == "full" ? fp: fp.trim()) : "");
		}
		if(factParts.after.length){
			for(var j = 0; j<this.regex_config.stopping_patterns.length; j++){
				bits = factParts.after.split(this.regex_config.stopping_patterns[j]);
				if(bits.length > 1){
					var comment = bits.shift();
					comment.trim();
					if(comment.substring(0, 4) == "</b>"){
						comment = comment.substring(4);
					}
					factParts.comment = cleanUpHTML(comment);
					continue;
				}
			}
			if(!factParts.comment){
				factParts.comment = factParts.after;
			}
		}
		rawfacts.push(factParts);
	}
	return rawfacts;
}

/**
 * @summary assembles array of factoid objects from rawfacts returned by regex scan and 
 * contexts returned by page contexts
 */
DacuraPageScanner.prototype.assembleFactoids = function(rawfacts, pagecontexts){
	var locators = [];
	for(var i = 0; i < rawfacts.length; i++){
		var fid = this.generateFactoidID(rawfacts[i].label, i);
		var rawfactoid = rawfacts[i];
		if(pagecontexts){
			rawfactoid.pagelocator = this.getPageLocator(rawfactoid, pagecontexts);
			var flocid = rawfactoid.pagelocator.section + rawfactoid.label;
			if(typeof locators[flocid] == "undefined"){
				locators[flocid] = 0;
			}
			else {
				rawfactoid.pagelocator.sequence = ++locators[flocid];
			}
		}
		this.factoids[fid] = new dPageFactoid(fid, rawfactoid);
		this.sequence.push(fid);
	}
}

/**
 * @summary generates a unique id for a factoid in a page from its label and sequence in the page
 * @param label {String} the string in the variable label
 * @param sequence {Integer} the order on the page in which the variable appears
 */
DacuraPageScanner.prototype.generateFactoidID = function(label, sequence){
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

/**
 * @summary: turns a label into a regular variable id (or generates an id if the label is empty p_sequence)
 */
DacuraPageScanner.prototype.idifyLabel = function(label, sequence){
	if(!label.length) {
		return "p_" + sequence;
	}
	var pname = toTitleCase(label);
	pname = pname.charAt(0).toLowerCase() + pname.slice(1);
	pname = pname.replace(/\W/g, '');
	return pname;
}


/**
 * @summary maps a raw factoid to its page context
 * @param factoid {Object} as returned by regex scan
 * @param pagecontexts {Array} pagecontexts returned by calculatePageContexts
 * @return locator object for factoid
 */
DacuraPageScanner.prototype.getPageLocator = function(rawfactoid, pagecontexts){
	var hloc = 0;
	for(var loc in pagecontexts){
		var nloc = parseInt(loc);
		if(nloc > rawfactoid.location) {
			break;
		}
		hloc = nloc;
	}
	if(hloc == 0){
		return new pageLocator(rawfactoid.label, "", "");
	}
	else {
		var pcl = pagecontexts[hloc];
		return new pageLocator(rawfactoid.label, pcl.id, pcl.text);
	}
}

/*in codebook building mode (schema definition), factoids can be associated with property definitions 
 - in this mode all factoids on a page are either mapped to existing properties or are not... 
-> those that are not are candidates for importing. 
*/


/**
 * @summary cycles through factoids on page and assigns connectors to each, 
 * */
DacuraPageScanner.prototype.findHarvested = function(harvested){
	for(var enturl in harvested){
		for(var i = 0; i<harvested[enturl].length; i++){
			var imprec = harvested[enturl][i];
			var relfoid = this.findFactoid(imprec.pagelocator);
			if(!relfoid){
				this.missingHarvested.push(imprec);
				jpr(imprec);
				alert("missing " + imprec.fid());
			}
			else {
				if(!relfoid.locatorMatch(imprec.pagelocator)){
					alert("strange: locator mismatch in matched factoid");
				}
				if(typeof relfoid.harvested == "undefined"){
					relfoid.harvested = {};
				}
				if(typeof relfoid.harvested[enturl] == "undefined"){
					relfoid.harvested[enturl] = [];
				}
				relfoid.harvested[enturl].push(imprec);
			}
		}
	}
} 

DacuraPageScanner.prototype.scrollTo = function(uid, jq){
	if(jq){
		var faketop = jQuery(jq).height();		
		$('html, body').animate({
			scrollTop: jQuery("#" + this.factoid_id_prefix + uid).offset().top - (faketop + 20)
		}, 2000);
	}
}


DacuraPageScanner.prototype.findFactoid = function(pl){
	for(var i in this.factoids){
		var foid = this.factoids[i];
		if(foid.pagelocator.uniqid() == pl.uniqid()){
			return foid;
		}
	}
	return false;
}

/**
 * @summary cycles through factoids on page and assigns connectors to each, 
 * */
DacuraPageScanner.prototype.findConnections = function(connectors){
	for(var i in this.factoids){
		var foid = this.factoids[i];
		if(typeof foid != "object"){
			alert("not object");
		}
		for(var plid in connectors){
			for(var li = 0; li < connectors[plid].length; li++){
				if(foid.locatorMatch(connectors[plid][li].locator)){
					if(typeof foid.connectors == "undefined"){
						this.factoids[i].connectors = [];
					}
					this.factoids[i].connectors.push(connectors[plid][li]);
				}							
			}
		}
	}
} 



//in general, every factoid can be: 
//A. associated with a property definition (via property definition locator) (e.g. code page entries)
	//clicking on such factoids should always bring up the relevant definition in the console. 
	//the model can be imported from a codebook but never shadows it...
//											or
//B. associated with a property datapoint (via property data locator match) (normal code pages)
//we should always include links to load either the property data list or the property definition into the console, 
//factoids can be associated with properties in 3 ways:
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

/**
 * @summary adds tags to the factoids to represent 
 * a) the type of factoid connection to the dataset [unknown, imported model property, imported model class, imported datapoint]
 * b) the status of the factoid [?]
 * c) since_import: [changed, unchanged] whether it has been changed since import 
 */
DacuraPageScanner.prototype.categoriseFactoids = function(){
	for(var i in this.factoids){
		var foid = this.factoids[i];
		var ftype = "unknown";
		if(typeof foid.harvested == 'object' && size(foid.harvested)){
			ftype = "harvested";
		}
		else if(typeof foid.connectors == "object"  && foid.connectors.length){
			ftype = "harvests";		
		}
		this.factoids[i].addTag("type", ftype);
	}
};


//we first send all values to the parser service, updating the factoids states
/**
 * @summary adds parsed result to factoid objects 
 */
DacuraPageScanner.prototype.parseValues = function(url, callback){
	var pfacts = [];
	var fact_ids = [];
	if(this.factoids.length == 0){
		console.log("No factoids were found in " + window.location.href);	
		return;
	}
	for(i in this.factoids){
		if(this.factoids[i].original.value.length && !this.factoids[i].parsed){
			pfacts[pfacts.length] = this.factoids[i].original.value.trim();
			fact_ids[fact_ids.length] = i;			
		}
	}
	if(pfacts.length == 0){
		//whole page parsed analysed already
		return callback();
	}
	var self = this;
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
				self.factoids[fact_ids[i]].setParsedResult(results[i]);
			}
			this.parsed = true;
			callback();
		}
		catch(e){
			dacuraConsole.showResult("error", "Failed to parse server parse response", e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		dacuraConsole.showResult("error", "Failed to contact server to parse variables", jqXHR.responseText);
	});
};

//when it comes to the values, for those that are not associated with a property, the classification is,
// as before: 
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

/**
 * @summary analyse each factoid value on the page by submitting a frame with the property set to that value
 * here is where we do type-checking - client or server?
 * 
 * @param update {function} function for sending factoid value for analysis to dacura api
 * 	function takes two arguments: (updateldobject - returned by factoid.forAPI(),complete). 
 * @param complete {Function} callbackfunction called when the analysis is complete for all variables in the 
 * 	page given result array
 */
DacuraPageScanner.prototype.analyse = function(update, complete){
	if(this.modelmode){
		return complete([]);
	}
	var max = 100;//maximum number of simultaneous property checks that we will launch
	var launched = 0;//number currently launched
	var results = [];//array of results of analysis requests passed to complete
	//wrap the incoming complete function in local complete stuff.
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
		var sc = this.factoids[i].getDataSyntaxClass()
		if(sc && sc != "error" && sc != "empty"){
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

DacuraPageScanner.prototype.displayFactoids = function(showContextFunc, showFactoidPartsFunc){
	var npage = "";//we build up the new page body from scratch by stitching the updates into the page text and doing a full text update
	var npage_offset = 0;
	//jQuery(this.jquery_body_selector).hide();
	for(var fid in this.factoids){
		var foid = this.factoids[fid];
		npage += this.originalBody.substring(npage_offset, foid.original.location); 
		npage += foid.decorate(this.factoid_css_class, this.factoid_id_prefix, showFactoidPartsFunc);
		npage += showContextFunc(foid);
		npage_offset = foid.original.location + (foid.original.full.length);
	}
	npage += this.originalBody.substring(npage_offset);
	jQuery(this.jquery_body_selector).html(npage).show("pulsate", {}, 800);
} 

DacuraPageScanner.prototype.displayFrameFactoids = function(frameviewer, entity_class, candidate, frames, settings){
	var selhtml = false;
	var vtype = (settings && settings.view_style ? settings.view_style : "replace");
	for(var fid in this.factoids){
		var foid = this.factoids[fid];
		if(frameviewer && frames){
			foid.setRelevantHarvestFrames(frames);
			//get frames specifically for this factoid by checking harvests
		}
		var decorated = foid.decorate(this.factoid_css_class, this.factoid_id_prefix, frameviewer, entity_class, candidate, frames);
		if(vtype == "simple"){
			npage += this.originalBody.substring(npage_offset, foid.original.location) + decorated + foid.original.full;
		}
		else if(vtype == "replace"){
			npage += this.originalBody.substring(npage_offset, foid.original.location) + decorated + foid.original.after;
		}
		npage_offset = foid.original.location + foid.original.full.length;
	}
	jQuery(this.jquery_body_selector).html(npage + this.originalBody.substring(npage_offset));
	if(frameviewer && frames){
		for(var fid in this.factoids){
			var foid = this.factoids[fid];
			var vmode = "create";
			if(foid.frames && foid.frames.length){
				var htmlid = this.factoid_id_prefix + foid.uniqid;
				var frmid = htmlid + "-frameviewer";
				frameviewer.draw(foid.frames, vmode, frmid);
			}
		}
		
	}
}

//analysis stuff over - now just display
DacuraPageScanner.prototype.undisplay = function(){
	jQuery(this.jquery_body_selector).html(this.originalBody);
	
}

//called when a factoid is clicked upon on the page...
//default is to call show factoid - can be overriden by setting a loadFactoidHandler(fid) function
DacuraPageScanner.prototype.loadFactoid = function(fid, viewcallback, updcallback){
	this.current_factoid = fid;
	if(typeof viewcallback == "function"){
		viewcallback(fid, updcallback);
	}
	else {
		this.showFactoid(fid, updcallback);
	}
}

//default behaviour when factoid on page is clicked - popup
DacuraPageScanner.prototype.showFactoid = function(fid, updcallback){
	alert(fid + " called to view factoid - empty function");
	//jpr(this.factoids[fid]);
}

//generates a chunk of html to represent a summary of the statistics from a page scan
//called by the complete function to show summary of scan results on jobby
DacuraPageScanner.prototype.getScanSummaryHTML = function(){
	var html = "<div class='page-scan-summary'>Summary: <dl>";
	for(var i in this.stats){
		html += "<dt class='" + this.stats[i].css + "'>" + this.stats[i].label + "</dt><dd class='" + this.stats[i].css + "'>" + this.stats[i].value + "</dd>";
	}
	html += "</dl></div>";
	return html;
}

//any of these guys may be overwritten by target specific page scanning scripts


//generates the statistics for factoids found in the page
DacuraPageScanner.prototype.generateStats = function (){
	this.stats = {"variables": 	{css: "variables", label: "total", value: 0}, 
		"datapoints": 	{css: "total", label: "datapoints", value: 0}
	};
	//loops through the factoids and aggregations statistics by connection category and statistical class
	//connection category: 
	//statistical class: 
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
		if(!this.modelmode){
			if(this.factoids[i].parsed && this.factoids[i].parsed.datapoints){
				this.stats.datapoints.value += this.factoids[i].parsed.datapoints.length;
			}
			var scls = this.factoids[i].getDataSyntaxClass();
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
}

//utility function for tidying up the title text of the factoid page context
DacuraPageScanner.prototype.cleanContextTitle = function(text){
	if(text.substring(text.length - 6) == "[edit]"){
		text = text.substring(text.length - 6); 
	}	
	return text;
}

/**
 * Factoid object
 * @constructor
 * @param uid {String} unique id for this factoid
 * @param details {Object}
 * @param config {Object}
 */
function dPageFactoid(uid, details, config){
	this.uniqid = uid;
	this.warning = false;//true if the factoid invokes a warning
	this.pagelocator = details.pagelocator;
	this.original = {
		location : details.location,//page offset in bytes of where factoid was located within body.	
		full: details.full, 
		head: details.head,
		label: details.label,
		value: details.value,
		comment: details.comment,
		after: details.after
	};//original html values as found on page, for full section, encoded (variable header), label, value bit, text after, text before
	this.config = config;//factoid configuration object 
	this.data = []; //
	this.parsed = false;
	//this.annotation = this.generateAnnotation(this.original.before, this.original.after);
	this.tags = {};
}

dPageFactoid.prototype.setParsedResult = function(result){
	this.parsed = result;
	//factParts.notes = factParts.notes.split(/<[hH]/)[0].trim();
	///"value" => $val,
	//"result_code" => "",
	//"result_message" => "",
	//"datapoints" => array()
}

dPageFactoid.prototype.getModelPropertyScreenFiller = function(mode, mapURL){
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

dPageFactoid.prototype.connectionCategory = function(){
	return this.tags['type'];
}

dPageFactoid.prototype.getHarvestsClass = function(){
	var harvs = this.getHarvests();
	if(harvs){
		for(var j=0; j<harvs.length; j++){
			if(harvs[j].type == "harvests" && typeof harvs[j].target_class != "undefined"){
				return harvs[j].target_class;
			}
		}
	}
	return false;
}

dPageFactoid.prototype.getHarvested = function(){
	return this.harvested;
}

dPageFactoid.prototype.getHarvests = function(){
	return this.connectors;
}

dPageFactoid.prototype.getRelevantHarvests = function(frames){
	var rels = [];
	var harvests = this.getHarvests();
	if(harvests){
		for(var i=0; i<harvests.length; i++){
			if(frames){
				for(var j=0; j<frames.length; j++){
					if(harvests[i].target_class == frames[j].property){
						rels.push(harvests[i]);
					}
				}				
			}
			else {
				rels.push(harvests[i]);
			}
		}
	}
	return rels;
}

dPageFactoid.prototype.setRelevantHarvestFrames = function(frames){
	this.frames = [];
	var harvests = this.getHarvests();
	if(harvests && frames){
		for(var i=0; i<harvests.length; i++){
			for(var j=0; j<frames.length; j++){
				if(harvests[i].target_class == frames[j].property){
					this.frames.push(frames[j]);
				}
			}
		}
	}
}

dPageFactoid.prototype.getTag = function(t){
	return this.tags[t];
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
	var html = "<img class='factoid-icon factoid-" + this.getDataSyntaxClass() + "' src='" + this.config.iconbase + this.getDataSyntaxClass() + ".png'>";
	return html;
}

dPageFactoid.prototype.getResultIcon = function(which){
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

//locator has candidate_id or candidate_type
dPageFactoid.prototype.forAPI = function(){
	var contents = this.getContentsAsLD();
	if(!contents) {
		return false;
	}
	var upd = {
		"contents": contents	
	}
	if(this.locator.candidate_id){
		upd.cid = this.locator.candidate_id;
	}
	else {
		upd.ctype = this.locator.candidate_type;	
	}
	return upd;
}

//calls the import function of the locator (?) 
dPageFactoid.prototype.getContentsAsLD = function(){
	if(this.locator.target_type == "candidate" && this.locator.import){
		return this.locator.import(this);
	}
	else {
		//nothing we don't automatically try to import schemata at the moment. 
		return false;
	}
}

//generates an annotation object from the text before and after the factoid
dPageFactoid.prototype.generateAnnotation = function(before, after){
	return after;
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
dPageFactoid.prototype.getHTML = function(showPart){
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

dPageFactoid.prototype.getHarvestsHTML = function(showPart){
	return this.getConnectionHarvestsHTML();
}



dPageFactoid.prototype.getConnectionHarvestsHTML = function(frames){
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

dPageFactoid.prototype.getConnectionCategoryHTML = function(selhtml){
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

dPageFactoid.prototype.getHarvestsLabel = function(harvests){
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


dPageFactoid.prototype.getHarvestedLabel = function(){
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

dPageFactoid.prototype.getHarvestedIcon = function(harvests, candid){
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

dPageFactoid.prototype.checkSingleHarvestRecord = function(fpr, harvests, cid, candid){
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

dPageFactoid.prototype.getRecordIcon = function(res, msg, cnt){
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

dPageFactoid.prototype.getValueHTML = function(){
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

dPageFactoid.prototype.getStatHTML = function(stat, title, icon, val, edit){
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

//return a summary entry object to represent the factoid
dPageFactoid.prototype.getAsSummaryEntry = function(){
	var sc = this.getDataSyntaxClass();
	if(!sc){
		sc = "unparsed";
	}
	var se = {css: sc, label: sc, value: 1};
	if(this.parsed && this.parsed.datapoints){
		se.data = this.parsed.datapoints.length;
	}
	return se;
}

//add the factoid's information to the statistical summary object
dPageFactoid.prototype.addToSummaryEntry = function(ostats){
	ostats.value++;
	if(this.parsed && this.parsed.datapoints){
		ostats.data += this.parsed.datapoints.length;
	}
	return ostats;
}

dPageFactoid.prototype.getDataSyntaxClass = function(){
	return (this.parsed ? this.parsed.result_code : "empty");
}

dPageFactoid.prototype.locatorMatch = function(ploc){
	if(this.pagelocator && this.pagelocator.includes(ploc)){
		return true;
	}
	return false;
}


function webLocator(bits){
	bits = (bits ? bits : {url: ""});
	this.url = bits.url;
	if(bits.label || bits.section || bits.sequence){
		this.pagelocator = new pageLocator(bits.label, bits.section, bits.sectext, bits.sequence);
	}
	else if(bits.pagelocator){
		this.pagelocator = new pageLocator(bits.pagelocator.label, bits.pagelocator.section, bits.pagelocator.sectext, bits.pagelocator.sequence);
	}
}

webLocator.prototype.sameAs = function(other){
	if(this.url !== other.url) return false;
	if(this.pagelocator){
		if(!other.pagelocator) return false;
		return this.pagelocator.sameAs(other.pagelocator);
	}
	return true;
}

webLocator.prototype.matchesURL = function(url){
	return (url.substring(0, this.url.length) == this.url);
}

webLocator.prototype.getHTML = function(mode){
	var html = "<span class='web-locator-input " + mode + "'>";
	html += this.getElementHTML("url", "fa-link", this.url, "The web-page URL", mode);
	if(mode != "view" || this.pagelocator){
		if(mode != "view" || this.pagelocator.label){
			var label = (this.pagelocator && this.pagelocator.label ? this.pagelocator.label  : "");
			html += this.getElementHTML("label", "fa-tag", label, "The label on the page", mode);
		}
		if(mode != "view" || this.pagelocator.section){
			var sect = (this.pagelocator && this.pagelocator.section ? this.pagelocator.section : "");
			html += this.getElementHTML("section", "fa-key", sect, "The HTML ID of the section in which it appears", mode);
		}
		if(mode != "view" || this.pagelocator.sequence){
			var seq = (this.pagelocator && this.pagelocator.sequence ? this.pagelocator.sequence : "");
			html += this.getElementHTML("sequence", "fa-hashtag", seq, "The sequence number of the variable", mode);
		}
	}
	html += "</span>";
	return html;
};

webLocator.prototype.getElementHTML = function(el, icon, val, ph, mode){
	var ic = (mode == "view" ? "label-group" : "input-group");
	var html = "<span class='" + ic + " web-locator-" + mode + "'>";
	val = (val ? val : "");
	html += "<span title='" + ph + "' class='" + ic + "-addon'><i class='fa " + icon + " fa-fw'></i></span>";
	html += "<span class='" + el + " console-field-input'>";
	if(mode == "view"){
		html += val;
	}
	else {
		html += "<input class='" + el + "' type='text' value='" + val + "'>";		
	}
	html += "</span>";
	html += "</span>";
	return html;
}

webLocator.prototype.readFromDom = function(domel){
	this.url = jQuery("input.url", domel).val().trim();
	var lab = jQuery("input.label", domel).val().trim();
	var sec = jQuery("input.section", domel).val().trim();
	var seq = jQuery("input.sequence", domel).val().trim();
	if(lab || sec || seq){
		this.pagelocator = new pageLocator(lab, sec, null, seq);
	}
};



function pageLocator(label, secid, sectext, sequence){
	this.label = label;
	this.section = secid;
	this.sequence = sequence;
	if(sectext){
		this.sectext = sectext;
	}
}

pageLocator.prototype.uniqid = function(){
	var id = "label=" + this.label;
	if(this.section){
		id+="section=" + this.section;
	}
	if(this.sequence){
		id+= "sequence=" + this.sequence;
	}
	return id;
}

pageLocator.prototype.sameAs = function(other){
	if(this.label != other.label) return false;
	if((this.section || other.section) && this.section != other.section) return false;
	if((this.sequence || other.sequence) && this.sequence != other.sequence) return false;
	return true;
}

pageLocator.prototype.includes = function(other){
	if(typeof other != "object" || isEmpty(other)){
		alert("empty " + other);
		return false;
	}
	if(this.label != other.label){
		//alert(this.label + "!=" + other.label);
		return false;
	}
	if(this.section && this.section != other.section){
		//alert("label: " + this.label + " section: " + this.section + " != " + other.section);
		return false;
	}
	if((this.sequence || other.sequence) && this.sequence != other.sequence){
		//alert("label: " + this.label + " section: "+ this.section + " sequence: " + this.sequence + " != " + other.sequence);
		return false;
	}
	//alert(this.uniqid() + " <-> " + other.uniqid());
	return true;
}

function pageConnector(pl, connection_type, elid, eltype){
	this.locator = pl
	this.type = connection_type;
	this.target = elid;
	this.target_class = eltype;
}



//helper function to clean up sections of html to make it proper
function cleanUpHTML(html){
	if(typeof tmpdivx == "undefined"){
		tmpdivx = document.createElement("DIV");
	}
	//parse as xhtml to remove all extraneous / unclosed html tags
	if(html && html.length){
		//alert(html);
		//alert(x);
		//return x;
		tmpdivx.innerHTML = html;
	    var html = tmpdivx.innerHTML;
	    var wrap = jQuery("<div>" + html + "</div>");
	    wrap.find(":empty").remove();
	    html = wrap.html();
	}
	return html;
}

function removeHTML (html){
	//return html;
	if(typeof tmpdivx == "undefined"){
		tmpdivx = document.createElement("DIV");
	}
	tmpdivx.innerHTML = html;
    return tmpdivx.innerText;
}




