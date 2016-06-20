/**
 * @file Javascript object for interpreting reasoning violations errors (RVO) returned in responses by the Dacura API
 * @author Chekov
 * @license GPL V2
 */

/**
 * @function parseRVOList
 * @summary Parses a list of RVO errors (as returned by api / DQS)
 * @param jsonlist {Array} json list [] or RVO errors to be parsed
 * @returns {Array} list of parsed RVO objects
 */
function parseRVOList(jsonlist){
	if(typeof jsonlist != 'object' || jsonlist.length == 0){
		return [];
	}
	var l = [];
	for(var i = 0; i< jsonlist.length; i++){
		if(typeof jsonlist[i] == "object"){
			l.push(new RVO(jsonlist[i]));
		}
	}
	return l;
};

/**
 * @function summariseRVOList
 * @summary Summarises a list of RVO errors 
 * @param rvolist {Array} json list [] of RVO errors 
 * @returns {String} text summarising the list
 */
function summariseRVOList(rvolist){
	if(rvolist.length == 1) return rvolist[0].label;
	var entries = [];
	var bytype = {};
	for(var i = 0; i < rvolist.length; i++){
		if(!rvolist[i].cls){
			rvolist[i].cls = rvolist[i].label.split(" ").join("");
		}
		if(typeof bytype[rvolist[i].cls] == "undefined"){
			bytype[rvolist[i].cls] = [];			
		}
		bytype[rvolist[i].cls].push(rvolist[i]);
	}
	for(var j in bytype){
		if(bytype[j].length == 1){
			entries.push("1 " + bytype[j][0].label); 
		}
		else {
			entries.push(bytype[j].length + " " + bytype[j][0].label + "s"); 	
		}
	}
	return entries.join(", ");
}

/**
 * @constructor
 * @param data {Object} the json object returned by api
 */
function RVO(data){
	if(typeof data != "object"){
		alert("not object");
		return;
	}
	this['class'] = data["class"];
	this.best_practice = data.best_practice;
	this.cls = data.cls;
	this.message = data.message;
	this.info = data.info;
	this.subject = data.subject;
	this.predicate = data.predicate;
	this.object = data.object;
	this.property = data.property;
	this.element = data.element;
	this.label = data.label;
	this.comment = data.comment;
	this.path = data.path;
	this.constraintType = data.constraintType;
	this.cardinality = data.cardinality;
	this.value = data.value;
	this.qualifiedOn = data.qualifiedOn;
	this.parentProperty = data.parentProperty;
	this.parentDomain = data.parentDomain;
	this.domain = data.domain;
	this.range = data.range;
	this.parentRange = data.parentRange;
	this.parentProperty = data.parentProperty;	
}

/**
 * @summary retrieves the label of the object
 * @param mode {string} 
 * @returns {string}
 */
RVO.prototype.getLabel = function(mode){
	return this.label;
}

/**
 * @summary retrieves the css class of the label (dqs-bp, dqs-rule)
 * @param mode {string} 
 * @returns {string} css class
 */
RVO.prototype.getLabelCls = function(mode){
	if(this.best_practice){
		return "dqs-bp";
	}
	return "dqs-rule";
};

/**
 * @summary retrieves the title of the label
 * @param mode {string} 
 * @returns {string}
 */
RVO.prototype.getLabelTitle = function(mode){
	return this.label + " " + this.comment;
};

/**
 * @summary retrieves the object as a html row
 * @param type {string} 
 * @returns {string} html table row
 */
RVO.prototype.getHTMLRow = function(type){
	var html = "<tr><td title='" + this.comment + "'>"+this.label+"</td><td>"+this.message +"</td>";
	html += "<td>";
	var atrs = this.getAttributes();
	if(typeof atrs == "object" && !isEmpty(atrs)) html += "<div class='rawjson'>" + JSON.stringify(atrs, 0, 4) + "</div>";
	if(this.info) html += " " + this.info;
	html += "</td></tr>";
	return html;
};

/**
 * @summary retrieves the attributes of the RVO as a json object
 * @returns {Object}
 */
RVO.prototype.getAttributes = function(){
	var atts = {};
	if(this.subject) atts.subject = this.subject;
	if(this.predicate) atts.predicate = this.predicate;
	if(this.object) atts.object = this.object;
	if(this.property) atts.property = this.property;
	if(this.element) atts.element = this.element;
	if(this['class']) atts['class'] = this['class'];
	//if(this.comment) atts.comment = this.comment;
	if(this.path) atts.path = this.path;
	if(this.constraintType) atts.constraintType = this.constraintType;
	if(this.cardinality) atts.cardinality = this.cardinality;
	if(this.value) atts.value = this.value;
	if(this.qualifiedOn) atts.qualifiedOn = this.qualifiedOn;
	if(this.parentProperty) atts.parentProperty = this.parentProperty;
	if(this.parentDomain) atts.parentDomain = this.parentDomain;
	if(this.domain) atts.domain = this.domain;
	if(this.range) atts.range = this.range; 
	if(this.parentRange) atts.parentRange = this.parentRange; 
	if(this.parentProperty) atts.parentProperty = this.parentProperty;
	return atts;
};

