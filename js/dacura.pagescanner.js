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
function DacuraPageScanner(){
	this.factoids = {};//fid => factoid object of factoids found on page
	this.sequence = [];//sequence of factoid ids found on page in order of apprearance
	this.factoid_id_prefix = "dacura_scanned_fact";
	this.factoid_css_class = this.factoid_id_prefix;
	this.selected_factoid_css = "dacura_selected_fact";
	this.stats = {};
	this.current_factoid = false;
	this.missingHarvested = [];//harvested records that point to missing factoids
	this.parsed = false;
}

DacuraPageScanner.prototype.init = function(scan){
	this.scan_config = scan;
	if(scan.type == "webpage"){
		this.rawData = jQuery(scan.jquery_body_selector).html();		
		this.context_jquery_selector = scan.context_jquery_selector;// ':header span.mw-headline';
	}
	else {
		
	}
	if(scan.regex_config){
		this.regex = this.getRegexString(scan.regex_config);
	}
	/*
	this.regex_config = {
		variable_label_start: "♠",
		variable_label_end: "♣",
		variable_value_end: "♥",
		stopping_patterns: ["</dd>", "<h"],
		indices: { full: 0, head: 1, label: 2, value: 3, after: 4}
	};*/
	//this.regex = "(♠([^♠♥♣]*)♣([^♠♥]*)♥)([^♠]*)";
}

DacuraPageScanner.prototype.getRegexString = function(config){
	var str = "(" + config.variable_label_start;
	str += "([^" + config.variable_label_start + config.variable_label_end + 
		config.variable_value_end + "]*)" + config.variable_label_end;
	str += "([^" + config.variable_label_start + config.variable_value_end + "]*)" 
		+ config.variable_value_end + ")";
	str += "([^" + config.variable_label_start  + "]*)";
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
	//console.time("calculating contexts");
	var pagecontexts = this.calculatePageContexts(this.rawData, this.context_jquery_selector);
	//console.timeEnd("calculating contexts");
	//console.time("regex scan");	
	if(this.regex){
		var rawfacts = this.regexScan(this.regex, this.rawData);
		//console.timeEnd("regex scan");
		//console.time("assembling factoids");	
		this.assembleFactoids(rawfacts, pagecontexts);
	}
	//console.timeEnd("assembling factoids");
	//console.time("finding harvested");	
	this.findHarvested(harvested);
	//console.timeEnd("finding harvested");
	//console.time("finding connections");	
	this.findConnections(connectors);
	//console.timeEnd("finding connections");
	//console.time("categorisation & stats");	
	this.categoriseFactoids();
	this.generateStats();
	if(this.scan_config.parser_url){
		this.parseValues(this.scan_config.parser_url, function(){
			if(typeof complete == "function") complete();
		});
	}
	else {
		for(i in this.factoids){
			if(this.factoids[i].original.value.length && !this.factoids[i].parsed){
				if(this.scan_config['parseFacts']){
					var parsed = this.scan_config['parseFacts'](this.factoids[i].original.value);
				}
				else {
					var dmf = new DacuraFactoid();
					var parsed = dmf.getValueAsParsed(this.factoids[i].original.value);
				}
				this.factoids[i].parsed = parsed;
			}
		}
		if(typeof complete == "function") complete();
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
		for(var i in this.scan_config.regex_config.indices){
			var fp = matches[this.scan_config.regex_config.indices[i]];
			factParts[i] = (fp ? (i == "full" ? fp: fp.trim()) : "");
		}
		if(factParts.after.length){
			for(var j = 0; j<this.scan_config.regex_config.stopping_patterns.length; j++){
				bits = factParts.after.split(this.scan_config.regex_config.stopping_patterns[j]);
				if(bits.length > 1){
					var comment = bits.shift();
					comment.trim();
					if(comment.substring(0, 4) == "</b>"){
						comment = comment.substring(4);
					}
					var self = this;
					factParts.comment = dacura.utils.tidyHTML(comment, function(domnode){
						return self.isStopNode(domnode);
					});
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

DacuraPageScanner.prototype.isStopNode = function(domnode){
	var stoppers = (this.scan_config.stoppers ? this.scan_config.stoppers : []);
	var stopper_texts = (this.scan_config.stopper_texts ? this.scan_config.stopper_texts : []);
	var nn = domnode.nodeName;
	if(nn && nn.length){
		if(stoppers.indexOf(nn.toLowerCase()) != -1){
			return true;
		}
		var txt = jQuery(domnode).text();
		if(txt && txt.length && stopper_texts.indexOf(txt.toLowerCase().trim()) !== -1){
			return true;
		}
	}
	return false;
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
		this.factoids[fid] = new DacuraFactoid(fid, rawfactoid);
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
			self.parsed = true;
			callback();
		}
		catch(e){
			//dacuraConsole.showResult("error", "Failed to parse server parse response", e.message);
		}
	})
	.fail(function (jqXHR, textStatus){
		//dacuraConsole.showResult("error", "Failed to contact server to parse variables", jqXHR.responseText);
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
			if(this.factoids[i].frames){
				var fs = 0;
				for(var g in this.factoids[i].frames){
					for(var prop in this.factoids[i].frames[g]){
						fs += this.factoids[i].frames[g][prop].length;
					}
				}
				if(typeof this.stats['frames'] == "undefined"){					
					this.stats['frames'] = {css: 'frames', label: 'frames', value: 1, data: fs};
				}
				else {
					this.stats['frames']['value']++;
					this.stats['frames']['data']+= fs;
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


function removeHTML (html){
	//return html;
	if(typeof tmpdivx == "undefined"){
		tmpdivx = document.createElement("DIV");
	}
	tmpdivx.innerHTML = html;
    return tmpdivx.innerText;
}




