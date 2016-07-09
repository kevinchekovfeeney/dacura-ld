//variable that changes with each class - global variable as hangover from something...
var parentClasses = [];

function dOntology(json, config){
	this.id = json.id;
	this.bnid = 0;
	this.boxtypes = config.boxtypes;
	this.capabilities = config.capabilities;
	this.entity_tag = config.entity_tag;;
	this.request_id_token = config.request_id_token;
	this.normals = ["rdfs:subClassOf", "rdf:type", "rdfs:label", "rdfs:comment", "owl:oneOf"];
	this.contents = json.contents;
	this.meta = json.meta;
	this.properties = {};
	this.classes = {};
	this.errors = [];
	for(var id in this.contents){
		if(this.isProperty(this.contents[id])){
	    	this.properties[id] = this.contents[id];				
		}
		else if(this.isClass(this.contents[id])){
	    	this.classes[id] = this.contents[id];
	    }
	}
	this.tree = this.buildNodeTree();
	this.entity_classes = this.calculateEntityClasses();
	this.setHelpTexts(config.helptexts);
	this.focus = false;
}

dOntology.prototype.setHelpTexts = function(msgs){
	this.help = {};
	this.help.class_id = (typeof msgs.class_id == "string") ? msgs.class_id : "The id of the class - no spaces or punctuation allowed, traditionally starts with a capital letter and uses camel case";
	this.help.class_label = (typeof msgs.class_label == "string") ? msgs.class_label : "The class's label - the text string that will represent it";
	this.help.property_id = (typeof msgs.property_id == "string") ? msgs.property_id : "The id of the property - no spaces or punctuation allowed, traditionally starts with a lowercase letter";
	this.help.property_label = (typeof msgs.class_label == "string") ? msgs.class_label : "The property's label - the text string that will represent the property";
}

dOntology.prototype.getHelpText = function(id){
	if(this.help[id]) return this.help[id];
	return "help text for " + id;
}

dOntology.prototype.getEntityClasses = function(){
	if(typeof this.entity_classes != "object"){
		this.entity_classes = this.calculateEntityClasses();
	}
	return this.entity_classes;	
}

dOntology.prototype.getBoxTypes = function(){
	return this.boxtypes;
}

dOntology.prototype.isDacuraBoxType = function(bt){
	return (this.hasBoxedTypes() && typeof this.boxtypes[bt] != "undefined");
}

dOntology.prototype.hasBoxedTypes = function(){
	return (size(this.boxtypes) > 0);
}

dOntology.prototype.addClass = function(id, label, comment, type, parent, choices){
	var cid = this.id + ":" + id;
	this.classes[cid] = {"rdfs:label": label, "rdfs:comment": comment, "rdf:type": "owl:Class"};
	if(type == "enumerated"){
		this.classes[cid]["rdfs:subClassOf"] = "dacura:Enumerated";
		this.classes[cid]["owl:OneOf"] = choices;
	}
	else if(parent && parent.length){
		this.classes[cid]["rdfs:subClassOf"] = parent;
	}
}

dOntology.prototype.addProperty = function(id, label, comment, domain, range){
	var pid = this.id + ":" + id;
	this.properties[pid] = {"rdfs:label": label, "rdfs:comment": comment};
	if(domain.substring(0,3) == "xsd"){
		this.properties[pid]["rdf:type"] = "owl:DatatypeProperty";
	}
	else {
		this.properties[pid]["rdf:type"] = "owl:ObjectProperty";
	}
	this.properties[pid]['rdfs:range'] = range;
	this.properties[pid]['rdfs:domain'] = domain;
}

dOntology.prototype.getRDF = function(){
	var rdf = {};
	for(var i in this.classes){
		rdf[i] = this.classes[i];
	}
	for(var i in this.properties){
		rdf[i] = this.properties[i];
	}
	return rdf;
}

dOntology.prototype.calculateEntityClasses = function(){
	var cls = [];
	var totry = [this.entity_tag];
	while(totry.length > 0){
		var ec = totry.shift();
		if(cls.indexOf(ec) == -1){
			cls.push(ec);
			if(typeof this.tree[ec] == "object"){
				for(var i = 0; i<this.tree[ec].children.length; i++){
					totry.push(this.tree[ec].children[i]);
				}
			}
		}
	}
	return cls;
}

dOntology.prototype.isEntityClass = function(cls){
	var ecls = this.getEntityClasses();
	return ecls.indexOf(cls) != -1;
}

dOntology.prototype.buildNodeTree = function() {
	nodes = {};
	for(var i in this.classes){
		if(typeof nodes[i] == "undefined"){
			nodes[i] = {parents: [], children: []};
		}
		var x = this.classes[i]['rdfs:subClassOf'];
		if(typeof x == "string"){
			if(nodes[i].parents.indexOf(x) == -1){
				nodes[i].parents.push(x);
				if(typeof nodes[x] == "undefined"){
					nodes[x] = {parents: [], children: [i]};
				}
				else {
					if(nodes[x].children.indexOf(i) == -1){
						nodes[x].children.push(i);
					}
				}
			}
		}
		else if(typeof x == "object"){
			for(var j = 0; j< x.length; j++){
				if(nodes[i].parents.indexOf(x[j]) == -1){
					nodes[i].parents.push(x[j]);
					if(typeof nodes[x[j]] == "undefined"){
						nodes[x[j]] = {parents: [], children: [i]};
					}
					else {
						if(nodes[x[j]].children.indexOf(i) == -1){
							nodes[x[j]].children.push(i);
						}
					}
				}
			}
		}
	}
	return nodes;
}

/* functions for extracting information from predicates in the ontology */

dOntology.prototype.isProperty = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:DatatypeProperty" || json['rdf:type'] == "owl:ObjectProperty" || json['rdf:type'] == "rdf:Property") return true;
	}
	if(typeof json['rdfs:subPropertyOf'] != "undefined") return true;
	if(typeof json['rdfs:range'] != "undefined") return true;
	if(typeof json['rdfs:domain'] != "undefined") return true;
	return false;
}

dOntology.prototype.isClass = function(json){
	if(typeof json['rdf:type'] != "undefined"){
		if(json['rdf:type'] == "owl:Class" || json['rdf:type'] == "rdfs:Class") return true;
	}
	if(typeof json['rdfs:subClassOf'] != "undefined") return true;
	return false;
}

dOntology.prototype.getPropertyRangeType = function(prop) {
	var json = this.properties[prop];
	if(typeof json == "object"){
		if(typeof json['rdf:type'] != "undefined"){
			if(json['rdf:type'] == "owl:ObjectProperty"){
				if(this.isDacuraBoxType(json['rdfs:range'])){
					return "boxed";
				}
				return "object";
			}
			if(json['rdf:type'] == "owl:DatatypeProperty"){
				return "literal";
			}	
		}
	}
	return "";
}

dOntology.prototype.getPropertyDomain = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:domain']  != "undefined"){
			return json['rdfs:domain'];
		}
	}
	return "";
}

dOntology.prototype.getPropertyUnits = function(prop){
	var json = this.properties[prop];
	if(typeof json == "object"){
		if(typeof json['dacura:units']  != "undefined"){
			return json['dacura:units']['data'];
		}
	}
	return "";
}



dOntology.prototype.getPropertyRange = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:range']  != "undefined"){
			return json['rdfs:range'];
		}
	}
	return "";
}

dOntology.prototype.getPropertyLabel = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:label'] != "undefined"){
			return json['rdfs:label']['data'];
		}
		if(typeof json['dc:title'] != "undefined"){
			return json['dc:title']['data'];
		}
	}
	return "";
}

dOntology.prototype.getClassLabel = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:label'] != "undefined"){
			return json['rdfs:label']['data'];
		}
		if(typeof json['dc:title'] != "undefined"){
			return json['dc:title']['data'];
		}
	}
	return "";
}

dOntology.prototype.getParentClasses = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:subClassOf'] == "object"){
			return json['rdfs:subClassOf'];
		}
		else if(typeof json['rdfs:subClassOf'] == "string"){
			return [json['rdfs:subClassOf']];
		}
	}
	return [];
}

dOntology.prototype.getEntityParent = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:subClassOf'] != "undefined"){
			var x = json['rdfs:subClassOf'];
			if(typeof x == "object") x = x[0];
			return x;
		};
	}
	return "";
};

dOntology.prototype.getOtherAssertions = function(cls){
	var json = this.classes[cls];
	var others = {};
	if(typeof json == "object"){
		for(var i in json){
			if(this.normals.indexOf(i) == -1){
				others[i] = json[i];
			}
		}
	}
	return others;	
}

dOntology.prototype.getClassType = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		for(var i in json){
			if(this.normals.indexOf(i) == -1){
				return "complex";
			}
		}
		if(typeof json['rdfs:subClassOf'] != "undefined"){
			if(typeof json['rdfs:subClassOf'] == "object"){
				if(json['rdfs:subClassOf'].length > 1){
					return "complex";
				}
				else if(json['rdfs:subClassOf'].length == 1){
					if(this.isEntityClass(cls)) {
						return "entity";
					}
					else {
						return "complex";
					}
				}
			}
			else {
				if(json['rdfs:subClassOf'] == "dacura:Enumerated"){
					return "enumerated";
				}
			}
		} 
	}
	return "";
}

dOntology.prototype.getEnumeratedChoices = function(cls){
	var json = this.classes[cls];
	var choices = [];
	if(typeof json == "object"){
		if(typeof json['owl:oneOf'] != "undefined"){
			var set = json['owl:oneOf'];
			for(var id in set){
				var choice = {"id": id};
				var rest = {};
				if(typeof set[id] == "object"){
					for(var key in set[id]){
						if(key == "rdfs:label"){
							choice.label = set[id][key]['data'];
						}
						if(key == "rdfs:comment"){
							choice.comment = set[id][key]['data'];
						}
						else if(key != "rdf:type") {
							rest[key] = set[id][key];
						}
					}
				}
				if(size(rest) > 0){
					choice.rest = rest;
				}
				choices.push(choice);
			}
		}
	}
	return choices;
}

dOntology.prototype.getClassComment = function(cls){
	var json = this.classes[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:comment'] != "undefined"){
			return json['rdfs:comment']['data'];
		}
		if(typeof json['dc:description'] != "undefined"){
			return json['dc:description']['data'];
		}
	}
	return "";
}

dOntology.prototype.getPropertyComment = function(cls){
	var json = this.properties[cls];
	if(typeof json == "object"){
		if(typeof json['rdfs:comment'] != "undefined"){
			return json['rdfs:comment']['data'];
		}
		if(typeof json['dc:description'] != "undefined"){
			return json['dc:description']['data'];
		}
	}
	return "";
}

dOntology.prototype.getUpdateAsRDF = function(input){
	var rdf = this.getInputAsRDF(input, "update");
	var cid = this.id + ":" + input.id;
	//need to figure out which bits we changed
	var orig = this.classes[cid];
	for(var i in orig){
		if(typeof rdf[cid][i] == "undefined"){
			rdf[cid][i] = []; //code for deleting it in linked data land
		}
	}
	if(input.ctype == "enumerated"){
		var choices = this.getEnumeratedChoices(cid);
		for(var i = 0; i < choices.length; i++){
			if(typeof rdf[cid]["owl:oneOf"][choices[i].id] == "undefined"){
				rdf[cid]["owl:oneOf"][choices[i].id] = []; //code for deleting it in linked data land
			}
		}
	}
	return rdf;
} 

dOntology.prototype.getUpdatePropertyAsRDF = function(input){
	return this.getInputPropertyAsRDF(input);
}


dOntology.prototype.getInputPropertyAsRDF = function(input){
	var rdf = {};
	var cid = this.id + ":" + input.id;
	rdf[cid] = {};
	rdf[cid]["rdfs:label"] = input.label;
	if(input.comment.length){
		rdf[cid]["rdfs:comment"] = input.comment;
	}
	if(input.units){
		rdf[cid]["dacura:units"] = input.units;
	}
	rdf[cid]["rdfs:domain"] = input.domain;
	rdf[cid]["rdfs:range"] = input.range;
	if(input.rtype == "object" || input.rtype == "boxed"){
		rdf[cid]["rdf:type"] = "owl:ObjectProperty"; 
	}
	else {
		rdf[cid]["rdf:type"] = "owl:DatatypeProperty"; 
	}
	return rdf;
}

dOntology.prototype.getInputAsRDF = function(input, mode){
	var rdf = {};
	var mode = (mode ? mode : "create");
	var cid = this.id + ":" + input.id;
	rdf[cid] = {};
	rdf[cid]["rdf:type"] = "owl:Class";
	rdf[cid]["rdfs:label"] = input.label;
	if(input.comment.length){
		rdf[cid]["rdfs:comment"] = input.comment;
	}
	if(typeof input.parents == "object" && input.parents.length > 0){
		rdf[cid]["rdfs:subClassOf"] = input.parents; 
	}
	else if(input.parents && input.parents.length){
		rdf[cid]["rdfs:subClassOf"] = input.parents; 
	}
	if(input.ctype == "enumerated"){
		rdf[cid]["rdfs:subClassOf"] = "dacura:Enumerated";
		if(mode == "update"){
			var orig_choices = this.getEnumeratedChoices(cid);
			rdf[cid]["owl:oneOf"] = {}; 
			for(var i = 0; i< input.choices.length ; i++){
				if(input.choices[i].label.length == 0) continue;
				var choice = {"rdf:type": cid, "rdfs:label": input.choices[i].label};
				if(input.choices[i].comment.length){
					choice['rdfs:comment'] = input.choices[i].comment;
				}
				var bnid = ((input.choices[i].id.substring(0, 2) == "_:") ? input.choices[i].id.substring(2) : input.choices[i].id);
				if(bnid.length){
					var new_one = true;
					for(var k = 0; k<orig_choices.length; k++){
						if(orig_choices[k].id == "_:" + bnid){
							new_one = false;
							break;
						}
					}
					if(new_one){
						choice[this.request_id_token] = bnid;
					}
					rdf[cid]["owl:oneOf"]["_:" + bnid] = choice;
				}
				else {
					rdf[cid]["owl:oneOf"]["_:" + this.id + "_" + ++this.bnid] = choice;			
				}
			}						
		}
		else {
			rdf[cid]["owl:oneOf"] = []; 
			for(var i = 0; i< input.choices.length ; i++){
				var choice = {"rdf:type": cid, "rdfs:label": input.choices[i].label};
				if(input.choices[i].comment.length){
					choice['rdfs:comment'] = input.choices[i].comment;
				}
				if(input.choices[i].id.length){
					choice[this.request_id_token] = input.choices[i].id;
				}
				rdf[cid]["owl:oneOf"].push(choice);
			}			
		}
	}
	if(input.ctype == "complex"){
		var assertions = this.readOtherAssertionsFromForm();
		for(var a in assertions){
			try {
				var val = JSON.parse(assertions[a]);
			}
			catch(e){
				val = assertions[a];				
			}
			rdf[cid][a] = val; 
		}
	}
	return rdf;
}

dOntology.prototype.validateUpdateInput = function(input) {
	var cls = this.id + ":" + input.id;
	var orig = this.classes[cls];
	if(typeof orig != "object"){
		this.errors.push({field: "id", message: "Attempt to update class " + input.id + " which does not exist"});
		return false;
	}
	return this.validateClassFormInput(input);
}

dOntology.prototype.validateCreatePropertyInput = function(input) {
	if(!this.validateFormBasics(input)){
		return false;
	}
	if(!input.domain.length){
		this.errors.push({field: "domain", message: "Missing domain class"});
		return false;	
	}
	if(typeof input.range == "undefined" || !input.range.length){
		this.errors.push({field: "range", message: "Missing range"});
		return false;	
	}
	return true;
}

dOntology.prototype.validateUpdatePropertyInput = function(input) {
	if(!this.validateCreatePropertyInput(input)){
		return false;
	}
	var prop = this.id + ":" + input.id;
	var orig = this.properties[prop];
	if(typeof orig != "object"){
		this.errors.push({field: "id", message: "Attempt to update property " + input.id + " which does not exist"});
		return false;
	}
	return true;
}

dOntology.prototype.validateCreateInput = function(input) {
	if(typeof this.contents[this.id + ":" + input.id] != "undefined"){
		this.errors.push({field: "id", message: "An entity with the id " + input.id + " already exists in the ontology"});
		return false;	
	}
	return this.validateClassFormInput(input);
}

dOntology.prototype.validateFormBasics = function(input){
	if(!input.id.length){
		this.errors.push({field: "id", message: "Missing identifier"});
		return false;
	}
	if(input.id.length < 2){
		this.errors.push({field: "id", message: "Identifiers must be at least 2 characters long"});
		return false;
	}
	if(/[^a-zA-Z0-9]/.test(input.id)){
		this.errors.push({field: "id", message: "Identifiers can only consist of alphanumerics"});
		return false;
	}
	if(!input.label.length){
		this.errors.push({field: "label", message: "Missing label"});
		return false;
	}
	if(input.label.trim().length < 3){
		this.errors.push({field: "label", message: "Class labels must be at least 3 characters long"});
		return false;
	}
	if(!input.comment.length){
		this.errors.push({field: "comment", message: "Missing class comment"});
		return false;
	}
	return true;
}

dOntology.prototype.validateClassFormInput = function(input){
	if(!this.validateFormBasics(input)){
		return false;
	}
	if(input.ctype == "entity"){
		if(input.parents && input.parents.length != 1){
			this.errors.push({field: "entity", message: "Entity classes should have only one parent class, " + this.parents.length + " found"});
			return false;
		}
	}
	else if(input.ctype == "enumerated"){
		if(!input.choices || input.choices.length == 0){
			this.errors.push({field: "enumerated", message: "No choices selected - you must create at least one choice for an enumerated type class."});
			return false;
		}
		var entries = 0;
		for(var i = 0; i < input.choices.length; i++){
			var choice = input.choices[i];
			if(choice.label || choice.comment || choice.id){
				entries++;
				if(!choice.label){
					this.errors.push({field: "enumerated", message: "Choice " + (i+1) + " is missing a label - every choice needs a label"});
					return false;
				}
				if(choice.label.length < 3){
					this.errors.push({field: "enumerated", message: "Choice " + (i+1) + " has a label that is too short - it must be at least 3 characters long"});
					return false;
				}
			}
		}
		if(entries == 0){
			this.errors.push({field: "enumerated", message: "No choices selected - you must create at least one choice for an enumerated type class."});
			return false;
		}
	}
	return true;
};

dOntology.prototype.readPropertyForm = function(){
	var input = {};
	input.id = jQuery("#dacura-console .property-id .console-field-input input").val();
	input.label = jQuery("#dacura-console .property-label .console-field-input input").val();
	input.comment = jQuery("#dacura-console .property-comment .console-field-input textarea").val();
	input.dtype = jQuery("#dacura-console .console-field-input .domain-type-select").val();
	if(input.dtype == "local"){
		input.domain = jQuery("#dacura-console .console-field-input .local-domain-list").val();
	}
	else {
		input.domain = jQuery("#dacura-console .console-field-input .remote-domain-input").val();		
	}
	input.rtype = jQuery("#dacura-console .console-field-input .property-rangetype-select").val();
	if(input.rtype == "literal"){
		input.range = "xsd:" + jQuery("#dacura-console .console-field-input .range-input-literal .xsdtype").val();
		input.units = jQuery("#dacura-console .console-field-input .range-input-literal .xsdunits").val();
	}
	else if(input.rtype == "boxed"){
		input.range = jQuery("#dacura-console .console-field-input .range-input-boxedliteral .range-box-types").val();
		input.units = jQuery("#dacura-console .console-field-input .range-input-boxedliteral .xsdunits").val();
	}
	else {
		input.rotype = jQuery("#dacura-console .console-field-input .range-objecttype-select").val();
		if(input.rotype == "local"){
			input.range = jQuery("#dacura-console .console-field-input .local-range-list").val();
		}
		else {
			input.range = jQuery("#dacura-console .console-field-input .remote-range-input").val();
		}
	}
	return input;
}

dOntology.prototype.setClassFormValues = function(bits){
	if(typeof bits.id == "string"){
		jQuery("#dacura-console .class-id .console-field-input input").val(bits.id);
	}
	if(typeof bits.label == "string"){
		jQuery("#dacura-console .class-label .console-field-input input").val(bits.label);
	}
	if(typeof bits.comment == "string"){
		jQuery("#dacura-console .class-comment .console-field-input textarea").val(bits.comment);
	}
}

dOntology.prototype.readForm = function(){
	var input = {};
	input.id = jQuery("#dacura-console .class-id .console-field-input input").val();
	input.label = jQuery("#dacura-console .class-label .console-field-input input").val();
	input.comment = jQuery("#dacura-console .class-comment .console-field-input textarea").val();
	input.ctype = jQuery("#dacura-console .class-type .console-field-input .class-type-select").val();
	if(input.ctype == "enumerated"){
		input.choices = this.readEnumeratedChoices();
	}
	else if(input.ctype == "entity"){
		var p = jQuery("#dacura-console .entity-parent .console-field-input select.entity-parent-list").val();
		p = (p? p : this.entity_tag);
		input.parents = [p];
	}
	else if(input.ctype == "complex"){
		input.parents = parentClasses;
		input.extras = this.readOtherAssertionsFromForm();
	}
	return input;
}

dOntology.prototype.readEnumeratedChoices = function(){
	var choices = [];
	jQuery("#dacura-console tr.enumerated-type-entry").each(function(){
		var choice = {};
		$this = jQuery(this);
		choice.id = $this.find(".enumerated-type-id input").val();
		choice.label = $this.find(".enumerated-type-label input").val();
		choice.comment = $this.find(".enumerated-type-comment input").val();
		choices.push(choice);
	});
	return choices;
};


dOntology.prototype.readOtherAssertionsFromForm = function(){
	var choices = {}
	jQuery("#dacura-console div.console-other-assertion").each(function(){
		$this = jQuery(this);
		var id = $this.find(".console-assertion-label").text();
		var val = $this.find(".console-assertion-input textarea").val();
		choices[id] = val;
	});
	return choices;
};

dOntology.prototype.writeErrorMessage = function(head, body, extra){
	dconsole.writeResultMessage("error", head, body, extra);
}

dOntology.prototype.writeWarningMessage = function(head, body, extra){ 
	dconsole.writeResultMessage("warning", head, body, extra);
}

dOntology.prototype.getHelpFieldHTML = function(id){
	var txt = this.getHelpText(id);
	var icon = dacura.system.getIcon("help", {title: escapeHtml(txt)});
	var html = "<span class='console-field-help'>" + icon + "</span>";
	return html;
}

dOntology.prototype.getMessageFieldHTML = function(){
	return "<div class='console-button-message' id='dacura-console-extra-message'></div>";
}

dOntology.prototype.displayPredicateValue = function(val){
	if(typeof val == "object"){
		if(typeof val.data == "string" && typeof val.lang == "string"){
			return val.data;
		}
		if(typeof val.type == "string" && typeof val.data == "string"){
			return val.data + " (<span class='xsdunits' title='" + escapeQuotes(val.type) + "'>" + urlFragment(val.type) + ")</span>";
		}
		return JSON.stringify(val);	
	}
	return val;
}

dOntology.prototype.getAssertionHTML = function(key, val, mode){
	if(mode && mode == "view"){
		val = this.displayPredicateValue(val);
	}
	else if(typeof val == "object"){
		val = JSON.stringify(val);
	}
	var html = "<div class='console-other-assertion'>";
	html += "<span class='console-field other-assertion'>"; 
	html += "<span class='console-assertion-label'>" + key + "</span>";
	html += this.getHelpFieldHTML("other_assertion");
	html += "<span class='console-assertion-input'>";
	if(mode == "view"){
		html += "<p class='other-assertion-readonly'>" + val + "</p>";
	}
	else {
		html += "<textarea>" + val + "</textarea>";
	}
	html += "</span>";
	html += "</span>";
	html += "</div>";
	return html;
};

dOntology.prototype.setViewMode = function(){
	this.mode = "view";
	jQuery("#dacura-console .console-edit-mode").fadeOut("fast");
	jQuery("#dacura-console .console-view-mode").fadeIn("slow");
	jQuery( "#dacura-console .console-context select.console-property-list" ).selectmenu( "enable" );
	jQuery( "#dacura-console .console-context select.console-class-list" ).selectmenu( "enable" );
}

dOntology.prototype.setEditMode = function(){
	this.mode = "edit";
	jQuery( "#dacura-console .console-context select.console-property-list" ).selectmenu( "disable" );
	jQuery( "#dacura-console .console-context select.console-class-list" ).selectmenu( "disable" );
	jQuery("#dacura-console .console-edit-mode").fadeIn("slow");
	jQuery("#dacura-console .console-view-mode").fadeOut("fast");
	this.refreshPropertyPage();
}


/* html generation for displaying classes */

dOntology.prototype.getCreateClassHTML = function(){
	var html = "";
	html += this.getCreateClassTitle();
	html += this.getClassHeadline();
	html += "<div class='console-class-extra'>"
	html += this.getEnumeratedTypePicker();
	if(size(this.classes) > 0){
		html += this.getParentClassSelect(); 
		html += this.getParentClassAddHTML();
	}
	html += "</div>";
	html += this.getAddClassButtons(); 
	return html;
}

dOntology.prototype.getViewClassHTML = function(cls){
	var html = "<div class='console-view-mode console-view-class'>";
	html += this.getReadOnlyClassHTML(cls);
	html += "</div>";
	html += "<div class='console-edit-mode dch console-edit-class'>";
	html += this.getUpdateClassTitle(cls);
	html += this.getClassHeadline(cls);
	html += "<div class='console-class-extra'>"
	html += this.getEnumeratedTypePicker(cls); 
	html += this.getParentClassSelect(cls); 
	html += this.getParentClassAddHTML(cls);
	html += this.getOtherAssertionsViewer(cls, "edit");
	html += "</div>";
	html += this.getUpdateClassButtons(); 
	html += "</div>";
	return html;
}


dOntology.prototype.getCreateClassTitle = function(){
	return "<div class='console-extra-title'>Add new class to " + this.id + " " + this.getHelpFieldHTML("new_class") + "</div>";
}

dOntology.prototype.getClassHeadingDetails = function(cls){
	var dets = {id: cls};
	dets.ctype = this.getClassType(cls);
	dets.tit = "a simple object";
	dets.icon = dacura.system.getIcon("object", {title: escapeHtml(dets.tit)});
	if(dets.ctype == "enumerated"){
		dets.tit = "an enumerated type class";
		dets.icon = dacura.system.getIcon("enum", {title: escapeHtml(dets.tit)});
	}
	else if(dets.ctype == "entity"){
		dets.tit = "an entity class";
		dets.icon = dacura.system.getIcon("entity", {title: escapeHtml(dets.tit)});
	}
	else if(dets.ctype == "complex"){
		dets.tit = "a complex object";
		dets.icon = dacura.system.getIcon("complex", {title: escapeHtml(dets.tit)});		
	}
	var label = this.getClassLabel(cls);
	dets.label = (label.length) ? cls : label;
	return dets;
}

dOntology.prototype.getViewClassTitle = function(cls){
	var dets = this.getClassHeadingDetails(cls);
	return "<div class='console-extra-view-title'>" + this.getHelpFieldHTML("view_" + dets.ctype + "_class") +  " Viewing class " + cls + " " + dets.icon + " </div>";
}
dOntology.prototype.getUpdateClassTitle = function(cls){
	var dets = this.getClassHeadingDetails(cls);
	return "<div class='console-extra-title'>Editing Class " + dets.icon + " " + cls + " " + this.getHelpFieldHTML("edit_" + dets.ctype + "_class") + "</div>";	
}

dOntology.prototype.wrapEmpty = function(text){
	return "<span class='console-element-empty'>" + text + "</span>";
}

dOntology.prototype.getReadOnlyClassHTML = function(cls){
	var html = this.getViewClassTitle(cls);
	var ctype = this.getClassType(cls);
	var lbl = this.getClassLabel(cls);
	var comment = this.getClassComment(cls);
	html += this.getViewModeButtons();
	html += "<div class='console-class-intro'><span class='view-class console-class-label'>";
	html += (lbl.length) ? lbl : this.wrapEmpty("No label");
	html += "</span>";
	html += "<span class='view-class console-class-comment'>";
	html += (comment.length) ? comment : this.wrapEmpty("No comment");
	html += "</span>";
	if(ctype == "complex"){
		html += this.getParentClassSelect(cls, "view"); 
	}
	else if(ctype == "entity"){
		html += this.getEntityParentSelect(cls, "view"); 	
	}
	html += "</div>";
	if(ctype == "enumerated"){
		html += "<div class='console-class-enumerated'>";
		html += "<div class='enumerated-view-heading'>";
		var choices = this.getEnumeratedChoices(cls);
		html += "An enumerated type with " + choices.length + (choices.length == 1 ? " choice" : " choices");
		html += "</div>";
		html += this.getEnumeratedTypeViewer(cls);
		html += "</div>";
	}
	//html += "<div class='console-class-ancestry'>";
	
	//html += this.getClassAncestryHTML(cls);
	//html += "</div>";
	//html += "<div class='console-class-properties'>";
	//html += this.getClassPropertiesHTML(cls);
	//html += "</div>";
	if(ctype == "complex"){
		html += "<div class='console-class-other'>";
		html += this.getOtherAssertionsViewer(cls, "view");
		html += "</div>";
	}
	return html;
}


dOntology.prototype.getClassHeadline = function(cls){
	var html = "<div class='class-headline'>";
	html += "<div class='headline-box-narrow'>";
	html += this.getClassIDHTML(cls); 
	html += this.getClassLabelHTML(cls); 
	html += this.getClassTypeSelect(cls);
	html += this.getEntityParentSelect(cls); 
	html += "</div>";
	html += "<div class='headline-box'>";
	html += this.getClassHelp(cls) + " "; 
	html += "</div>";
	html += "</div>";
	return html;
}

dOntology.prototype.getClassLabelHTML = function(cls){
	var flabel = this.getClassLabel(cls);
	var html = "<span class='console-field class-label'>"; 
	html += "<span class='console-field-label'>Label</span>";
	html += "<span class='console-field-input'><input type='text' value='" + flabel + "'></span>";
	html += this.getHelpFieldHTML("class_label");
	html += "</span>";
	return html;
}

dOntology.prototype.getClassIDHTML = function(cls){
	var cls = (cls ? cls.split(":")[1] : "");
	if(cls.length){
		var html = "<span style='display:none' class='console-field class-id'>";
	}
	else {
		var html = "<span class='console-field class-id'>";
	}
	if(cls.length){
		html += "<span class='console-field-input'>";
		html += "<input type='hidden' value='" + cls + "'>";
		html += "</span>";
	}
	else {
		html += "<span class='console-field-label'>Class ID</span>";
		html += "<span class='console-field-input'>";
		html += "<input type='text' value='" + cls + "'></span>";
		html += this.getHelpFieldHTML("class_id");
		html += "</span>";
	}
	html += "</span>";
	return html;
} 

dOntology.prototype.getClassHelp = function(cls){
	var help = this.getClassComment(cls);
	var html = "<span class='console-field class-comment'>"; 
	html += "<span class='console-top-label'>Class Description " + this.getHelpFieldHTML("class_comment")+"</span>";
	html += "<span class='console-field-input'><textarea>" + help + "</textarea></span>";
	html += "</span>";
	return html;
}

dOntology.prototype.getClassTypeSelect = function(cls){
	var ctype = this.getClassType(cls);
	var html = "<span class='console-field class-type'>"; 
	html += "<span class='console-field-label'>Type</span>";
	html += "<span class='console-field-input'>";
	html += "<select class='class-type-select'>";
	html += "<option value=''>Simple Object</option>";
	var sel = (ctype == "entity" ? " selected" : "");
	html += "<option value='entity'" + sel + ">Entity</option>"; 			
	var sel = (ctype == "enumerated" ? " selected" : "");
	html += "<option value='enumerated'" + sel + ">Enumerated Type</option>"; 
	if(size(this.classes) > 0){
		var sel = (ctype == "complex" ? " selected" : "");
		html += "<option value='complex'" + sel + ">Complex Object</option>"; 		
	}
	html += "</select></span>";
	html += this.getHelpFieldHTML("class_type");
	html += "</span>";
	return html;
}

dOntology.prototype.getEnumeratedChoicesAsRows = function(cls, mode){
	var choices = this.getEnumeratedChoices(cls);
	var html = "";
	for(var i = 0; i<choices.length; i++){
		if(mode == "view"){
			html += this.getEnumeratedTypeViewRowHTML(choices[i].id, choices[i].label, choices[i].comment);	
		}
		else {
			html += this.getEnumeratedTypeInputRowHTML(choices[i].id, choices[i].label, choices[i].comment, choices[i].rest);
		}
	}
	return html;
}

dOntology.prototype.getEnumeratedTypeViewRowHTML = function(id, label, comment){
	id = (id ? id : "");
	label = (label ? label : "");
	comment = (comment ? comment : "");
	var html = "<tr class='enumerated-type-entry'>";
	html += "<td class='enumerated-type-id'>" + id + "</td>";
	html += "<td class='enumerated-type-label'>" + label + "</td>";
	html += "<td class='enumerated-type-comment'>" + comment + "</td>";
	html += "</tr>";
	return html;
};


dOntology.prototype.getEnumeratedTypeInputRowHTML = function(id, label, comment, rest){
	id = (id ? id : "");
	label = (label ? label : "");
	comment = (comment ? comment : "");
	rest = (rest ? rest : "");
	var html = "<tr class='enumerated-type-entry'>";
	html += "<td class='enumerated-type-number'><input type='hidden' value='" + JSON.stringify(rest) + "'></td>";
	html += "<td class='enumerated-type-id'><input type='text' value='" + id + "'></td>";
	html += "<td class='enumerated-type-label'><input type='text' value='" + label + "'></td>";
	html += "<td class='enumerated-type-comment'><input type='text' value='" + comment + "'></td>";
	html += "<td class='enumerated-type-remove'>" + dacura.system.getIcon("delete", {title: "remove this row from the list of available choices"}) + "</td>";
	html += "</tr>";
	return html;
};

dOntology.prototype.getEnumeratedTypePicker = function(cls){
	var html = "<table class='console-enumerated-type'><thead>";
	html += "<th></th>";
	html += "<th>id " + this.getHelpFieldHTML("enumerated_type_id") + "</th>";
	html += "<th>label " + this.getHelpFieldHTML("enumerated_type_label") + "</th>";
	html += "<th>description " + this.getHelpFieldHTML("enumerated_type_comment") + "</th>";
	html += "<th class='add-enum'>" + dacura.system.getIcon("add", {title: "add another row to the list of available choices"}) + "</th>";
	html += "</tr></thead><tbody>";
	html += this.getEnumeratedChoicesAsRows(cls, "edit");
	html += this.getEnumeratedTypeInputRowHTML();
	html += "</tbody></table>";
	return html;
}

dOntology.prototype.getEnumeratedTypeViewer = function(cls){
	var html = "<table class='console-enumerated-type-viewer'><thead>";
	html += "<th>id " + this.getHelpFieldHTML("enumerated_type_id") + "</th>";
	html += "<th>label " + this.getHelpFieldHTML("enumerated_type_label") + "</th>";
	html += "<th>description " + this.getHelpFieldHTML("enumerated_type_comment") + "</th>";
	html += "</tr></thead><tbody>";
	html += this.getEnumeratedChoicesAsRows(cls, "view");
	html += "</tbody></table>";
	return html;
}

function getModelClassLink(cls){
	return "<a href='javascript:dconsole.display(\"class\", \"" + cls + "\")'><span class='parent-class-tile'>" + cls + "</a></span>";		
}

function getParentClassesHTML(ptypes, mode){
	var html = "";
	for(var i = 0; i<ptypes.length; i++){
		if(mode && mode == "view"){
			html += getModelClassLink(ptypes[i]);		
		}
		else {
			html += "<span class='parent-class-tile'>" + ptypes[i] + "<span class='remove-parent-class' data-id='" + ptypes[i] + "'>" + dacura.system.getIcon("delete", {title: "remove this parent class"}) + "</span></span>";
		}
	}
	return html;
} 

dOntology.prototype.getEntityParentSelect = function(cls, mode){
	var ep = this.getEntityParent(cls);
	var ents = this.getEntityClasses();
	if(mode && mode == "view"){
		var html = "";
	}
	else {
		var html = "<div class='console-entity-parent dch'>";
	}
	var css = (mode && mode == "view") ? "console-view" : "console-field";

	html += "<span class='" + css + " entity-parent'>"; 
	html += "<span class='" + css + "-label'>Subclass of</span>";
	html += "<span class='" + css + "-input'>";
	if(mode && mode == "view"){
		//var label = this.getClassLabel(ep);
		//if(!label.length) label = ep;
		var mcl = getModelClassLink(ep);
		html += "<span class='parent-class-tile'>" + mcl + "</span>";		
	}
	else {
		html += "<select class='entity-parent-list'>";
		for(var i = 0; i < ents.length; i++){
			var sel = (ents[i] == ep ? " selected" : "");
			var label = this.getClassLabel(ents[i]);
			if(!label.length) label = ents[i];
			html += "<option value='" + ents[i] + "'" + sel + ">" + label + "</option>";
		}
		html += "</select>";
	}
	html += "</span>";
	html += this.getHelpFieldHTML("add_entity_parent");
	html += "</span>";
	if(!(mode && mode == "view")){
		html += "</div>";
	}
	return html;
}

dOntology.prototype.getParentClassAddHTML = function(cls){
	var html = "<span class='console-field console-class-parent'>";
	html += "<span class='console-field-label'>Add Parent</span>";
	html += "<span class='console-field-input'>";
	html += "<span class='console-parent-select'>";
	html += "<select class='parent-type-select'>";
	html += "<option value='local'>Local Class</option>";
	html += "<option value='remote'>Imported Class</option>";
	html += "</select></span>";
	html += "<span class='local-parent'>";
	html += "<select class='local-parent-list'>";
	html += "<option value=''>Choose Class</option>";
	for(var i in this.classes){
		if(!cls || cls != i){
			html += "<option value='" + i + "'>" + this.getClassLabel(i) + "</option>";
		}
	}
	html += "</select>";
	html += "</span>";
	html += "<span class='remote-parent'>";
	html += "<input type='text' value=''>";
	html += "</span>";
	html += "<span class='add-parent-class'>" + dacura.system.getIcon("create") + "</span>";
	html += this.getHelpFieldHTML("add_parent");
	html += "</span></span>";
	return html;
} 

dOntology.prototype.getParentClassSelect = function(cls, mode){
	parentClasses = this.getParentClasses(cls);
	if(mode && mode == "view" && parentClasses.length == 0){
		return "";
	}
	var css = (mode && mode == "view" ? "console-field" : "console-field");
	var html = "<span class='" + css + " console-class-parent'>"; 
	html += "<span class='" + css + "-label'>Subclass of</span>";
	html += "<span class='" + css + "-input'>";
	html += "<span class='" + css + "-parents'>";
	html += getParentClassesHTML(parentClasses, mode);
	html += "</span>";
	html += this.getHelpFieldHTML("parent_classes");
	html += "</span></span>";
	return html;
}

dOntology.prototype.getOtherAssertionsViewer = function(cls, mode){
	var json = this.getOtherAssertions(cls);
	var html = "";
	if(json && size(json) > 0){
		html += "<div class='console-other-assertions'>";
		for(var key in json){
			html += this.getAssertionHTML(key, json[key], mode);
		}
		html += "</div>";
	}
	return html;
}

dOntology.prototype.getClassAncestryHTML = function(cls){
	
	return "<span>class ancestry goes here</span>";
}


dOntology.prototype.getClassPropertiesHTML = function(cls){
	return "<span>class properties go here</span>";
}


/* same for properties */


dOntology.prototype.getViewPropertyHTML = function(prop){
	var html = "<div class='console-view-mode console-view-property'>";
	html += this.getReadOnlyPropertyHTML(prop);
	html += this.getViewModeButtons();
	html += "</div>";
	html += "<div class='console-edit-mode dch console-edit-property'>";
	html += this.getUpdatePropertyTitle(prop);
	html += this.getPropertyHTML(prop);
	html += this.getUpdatePropertyButtons(); 
	html += "</div>";
	return html;
};

dOntology.prototype.getCreatePropertyHTML = function(){
	var html = this.getCreatePropertyTitle();
	html += this.getPropertyHTML(false);
	html += this.getAddPropertyButtons(); 
	return html;
};

dOntology.prototype.getReadOnlyPropertyHTML = function(prop){
	return "read only " + prop;
}

dOntology.prototype.getCreatePropertyTitle = function(){
	return "<div class='console-extra-title'>Add new property to " + this.id + " " + this.getHelpFieldHTML("new_property") + "</div>";
}

dOntology.prototype.getUpdatePropertyTitle = function(prop){
	var json = this.properties[prop];
	if(typeof json == "object" && typeof json.label == "object"){
		label = json.label.data;
		title = prop;
	}
	else {
		label = prop;
		title = prop;
	}
	return "<div class='console-extra-title' title='" + title + "'>Update " + label + " property in " + this.id + " " + this.getHelpFieldHTML("update_property") + "</div>";
}

dOntology.prototype.getPropertyHTML = function(prop, mode){
	var html = "<div class='property-headline'>";
	html += "<div class='headline-box-narrow'>";
	html += this.getPropertyIDHTML(prop, mode); 
	html += this.getPropertyLabelHTML(prop, mode); 
	html += this.getDomainSelect(prop, mode);
	html += "</div>";
	html += "<div class='headline-box'>";
	html += this.getPropertyHelp(prop, mode) + " "; 
	html += "</div>";
	html += "<div class='headline-box-full'>";
	html += this.getRangeSelect(prop, mode); 
	html += "</div>";
	html += "</div>";
	return html;	
}

dOntology.prototype.getPropertyLabelHTML = function(prop){
	var flabel = this.getPropertyLabel(prop);
	var html = "<span class='console-field property-label'>"; 
	html += "<span class='console-field-label'>Label</span>";
	html += "<span class='console-field-input'><input type='text' value='" + flabel + "'></span>";
	html += this.getHelpFieldHTML("property_label");
	html += "</span>";
	return html;
}

dOntology.prototype.getPropertyIDHTML = function(prop){
	var prop = (prop ? prop.split(":")[1] : "");
	var html = "<span class='console-field property-id'>";
	html += "<span class='console-field-label'>Property ID</span>";
	html += "<span class='console-field-input'><input type='text' value='" + prop + "'></span>";
	html += this.getHelpFieldHTML("property_id");
	html += "</span>";
	return html;
} 

dOntology.prototype.getPropertyHelp = function(prop){
	var help = this.getPropertyComment(prop);
	var html = "<span class='console-field property-comment'>"; 
	html += "<span class='console-top-label'>Property Description " + this.getHelpFieldHTML("property_comment") + "</span>";
	html += "<span class='console-field-input'><textarea>" + help + "</textarea></span>";
	html += "</span>";
	return html;
}

dOntology.prototype.getDomainSelect = function(prop){
	var dom = this.getPropertyDomain(prop);
	if(dom.length && typeof this.classes[dom] == "undefined"){
		var dtype = "remote";
	}
	else {
		dtype = "local";
	}
	var html = "<div class='console-property-domain'>";
	html += "<span class='console-field domain-type'>"; 
	html += "<span class='console-field-label'>Domain</span>";
	html += "<span class='console-field-input'>";
	html += "<span class='console-field-domains'>";
	html += "<span class='domain-type'>";
	html += "<select class='pdip domain-type-select'>";
	var sel = (dtype == "local" ? " selected" : "");
	html += "<option value='local'" + sel + ">Local Class</option>";
	var sel = (dtype == "remote" ? " selected" : "");
	html += "<option value='remote'" + sel + ">Imported Class</option>";
	html += "</select>";
	html += "</span>";	
	if(dtype == "remote"){
		html += "<span class='dch local-domain'>";
	}
	else {
		html += "<span class='local-domain'>";
	}
	html += "<select class='pdip local-domain-list'>";
	html += "<option value=''>Choose Class</option>";
	for(var i in this.classes){
		if(!prop || prop != i){
			var sel = ((dtype == "local" && dom == i ) ? " selected" : "");
			html += "<option" + sel + " value='" + i + "'>" + this.getClassLabel(i) + "</option>";
		}
	}
	html += "</select>";
	html += "</span>";
	if(dtype == "remote"){
		html += "<span class='remote-domain'>";
	}
	else {
		html += "<span class='remote-domain dch'>";
	}
	var val = ((dtype == "remote" && dom.length) ? dom : "");
	html += "<input pdip class='remote-domain-input' type='text' value='" + val + "'>";
	html += "</span>";
	html += "</span>";
	html += this.getHelpFieldHTML("property_domain");
	html += "</span>";
	html += "</div>";
	return html;
}


dOntology.prototype.getRangeSelect = function(prop){
	var rtype = this.getPropertyRangeType(prop);
	var rng = this.getPropertyRange(prop);
	var html = "<div class='console-property-range'>";
	html += "<span class='console-field property-range'>"; 
	html += "<span class='console-field-label'>Range</span>";
	html += "<span class='console-field-input'>";
	html += "<select class='property-rangetype-select'>";
	var sel = (rtype == "object" || !rtype.length ? " selected" : "");
	html += "<option value='object'" + sel + ">Object</option>"; 	
	if(this.hasBoxedTypes()){
		sel = (rtype == "boxed") ? " selected" : "";
		html += "<option value='boxed'" + sel + ">Boxed Literal</option>"; 
	}
	sel = (rtype == "literal") ? " selected" : "";
	html += "<option value='literal'" + sel + ">Literal</option>"; 
	html += "</select>";
	var val = (rtype == 'literal' ? rng: "");
	html += this.getRangeLiteralHTML(val, prop);		
	if(this.hasBoxedTypes()){
		val = (rtype == 'boxed' ? rng: "");
		html += this.getRangeBoxedLiteralHTML(val, prop);		
	}
	val = (rtype == 'object' ? rng: "");
	html += this.getRangeObjectHTML(val);
	html += "</span></span></div>";
	return html;
}

dOntology.prototype.getRangeBoxedLiteralHTML = function(range, prop){
	var units = this.getPropertyUnits(prop);
	var boxtypes = this.getBoxTypes();
	//jpr(boxtypes);
	var xsd = (typeof range == "string" && range.substring(0, 4) == "xsd:") ? range.substring(4) : "string";
	var html = "<span class='range-input range-input-boxedliteral dch'>";
	html += "<select class='range-box-types'>";
	html += "<option value=''>Choose type</option>";
	for(var i in boxtypes){
		var sel = (range && range.length && range == i ? " selected" : "");
		var label = (typeof(boxtypes[i]["rdfs:label"]) != "undefined" ? boxtypes[i]["rdfs:label"].data : i);
		html += "<option class='range-box-type' value='" + i + "'" + sel + ">" + label + "</option>";;
	}
	html += "</select>";
	html += "<span class='boxed-units'>Units: " + "<input type='text' value='" + units + "' class='xsdunits'></span>";
	html += "</span>";
	return html;
}


dOntology.prototype.getRangeLiteralHTML = function(range, prop){
	var units = this.getPropertyUnits(prop);
	var xsd = (typeof range == "string" && range.substring(0, 4) == "xsd:") ? range.substring(4) : "string";
	var html = "<span class='range-input range-input-literal dch'>";
	html += " Type: " + "<input class='xsdtype' type='text' value='" + xsd + "'> ";
	html += "Units: " + "<input type='text' value='" + units + "' class='xsdunits'> ";
	html += "</span>";
	return html;
}

dOntology.prototype.getRangeObjectHTML = function(range){
	var html = "<span class='range-input range-input-object'>";
	html += "<span class='console-field-domains'>";
	var rotype = this.getRangeObjectType(range);
	html += "<span class='range-type'>";
	html += "<select class='range-objecttype-select'>";
	var sel = ((rotype == "local") ? " selected" : "");
	html += "<option value='local'" + sel + ">Local Class</option>";
	sel = ((rotype == "remote") ? " selected" : "");
	html += "<option value='remote'" + sel + ">Imported Class</option>";
	html += "</select>";
	html += "</span>";
	html += "<span class='local-range'>";
	html += "<select class='local-range-list'>";
	html += "<option value=''>Choose Class</option>";
	for(var i in this.classes){
		var sel = ((rotype == "local" && range && range.length && i == range) ? " selected" : "");
		html += "<option value='" + i + "'" + sel + ">" + this.getClassLabel(i) + "</option>";
	}
	html += "</select>";
	html += "</span>";
	html += "<span class='remote-range dch'>";
	var val = ((rotype == 'remote' && range && range.length) ? range : "");
	html += "<input class='remote-range-input' type='text' value='" + val + "'>";
	html += "</span>";
	html += "</span>";
	html += this.getHelpFieldHTML("property_range");
	html += "</span>";
	html += "</span>";
	return html;
}

dOntology.prototype.getRangeObjectType = function(range){
	if(!range || !range.length) return "";
	return (range.split(":")[0] == this.id ? "local" : "remote");
}

/* buttons */

dOntology.prototype.getViewModeButtons = function(){
	var html ="";
	html += "<div class='console-extra-buttons view-update-buttons'>";
	if(this.capabilities.indexOf("test_update") != -1 || this.capabilities.indexOf("update") != -1){
		html += "<button class='set-edit-mode'>Edit</button>";	
	}
	html += "<button class='close-subscreen close-view-screen'>Close</button>";	
	html += "</div>";
	return html;
}

dOntology.prototype.getAddPropertyButtons = function(){
	var html = this.getMessageFieldHTML();
	html += "<div class='console-extra-buttons property-create-buttons'>";
	html += "<button class='close-subscreen cancel-property-create'>Cancel</button>";
	if(this.capabilities.indexOf("test_update") != -1){
		html += "<button class='test test-add-property'>Test Adding Property</button>";	
	}
	if(this.capabilities.indexOf("update") != -1){
		html += "<button class='add-property'>Add Property</button>";
	}
	html += "</div>";
	return html;
}

dOntology.prototype.getUpdatePropertyButtons = function(){
	var html = this.getMessageFieldHTML();
	html += "<div class='console-extra-buttons property-update-buttons'>";
	html += "<button class='cancel-edit cancel-property-update'>Cancel</button>";	
	if(this.capabilities.indexOf("test_update") != -1){
		html += "<button class='test test-update-property'>Test Changes</button>";	
	}
	if(this.capabilities.indexOf("update") != -1){
		html += "<button class='update-property'>Update Property</button>";
		html += "<button class='delete-property'>Delete Property</button>";	
	}
	html += "</div>";
	return html;
}

dOntology.prototype.getAddClassButtons = function(){
	var html = this.getMessageFieldHTML();
	html += "<div class='console-extra-buttons class-create-buttons'>";
	html += "<button class='close-subscreen cancel-class-create'>Cancel</button>";
	if(this.capabilities.indexOf("test_update") != -1){
		html += "<button class='test test-add-class'>Test Adding Class</button>";	
	}	
	if(this.capabilities.indexOf("update") != -1){
		html += "<button class='add-class'>Add Class</button>";	
	}
	html += "</div>";
	return html;
}

dOntology.prototype.getUpdateClassButtons = function(){
	var html = this.getMessageFieldHTML();
	html += "<div class='console-extra-buttons class-update-buttons'>";
	html += "<button class='cancel-edit cancel-class-update'>Cancel</button>";	
	if(this.capabilities.indexOf("test_update") != -1){
		html += "<button class='test test-update-class'>Test Changes</button>";	
	}
	if(this.capabilities.indexOf("update") != -1){
		html += "<button class='update-class'>Update Class</button>";	
		html += "<button class='delete-class'>Delete Class</button>";
	}
	html += "</div>";
	return html;
}

/* dynamics of showing different elements when choices are made */

dOntology.prototype.refreshPropertyPage = function(){
	jQuery("#dacura-console .console-extra select.domain-type-select").selectmenu("refresh");
	jQuery("#dacura-console .console-extra select.local-range-list").selectmenu("refresh");
	jQuery("#dacura-console .console-extra select.local-domain-list").selectmenu("refresh");
	jQuery("#dacura-console .console-extra select.range-objecttype-select").selectmenu("refresh");
}

dOntology.prototype.changeDomainClassType = function(){
	var pctype = jQuery("#dacura-console .console-extra select.domain-type-select").val();
	if(pctype == "remote"){
		jQuery("#dacura-console .console-extra .remote-domain").show();
		jQuery("#dacura-console .console-extra .local-domain").hide();
	}
	else {
		jQuery("#dacura-console .console-extra .remote-domain").hide();
		jQuery("#dacura-console .console-extra .local-domain").show();
		jQuery("#dacura-console .console-extra select.domain-type-select").selectmenu("refresh");
		jQuery("#dacura-console .console-extra select.local-domain-list").selectmenu("refresh");
	}	
}

dOntology.prototype.changeRangeClassType = function(){
	var pctype = jQuery("#dacura-console .console-extra select.range-objecttype-select").val();
	if(pctype == "remote"){
		pctype = jQuery("#dacura-console .console-extra .remote-range").show();
		pctype = jQuery("#dacura-console .console-extra .local-range").hide();
	}
	else {
		pctype = jQuery("#dacura-console .console-extra .remote-range").hide();
		pctype = jQuery("#dacura-console .console-extra .local-range").show();
		jQuery("#dacura-console .console-extra select.local-range-list").selectmenu("refresh");
	}	
}

dOntology.prototype.changeRangeType = function(){
	var rtype = jQuery("#dacura-console .console-extra select.property-rangetype-select").val();
	if(rtype == "literal"){
		pctype = jQuery("#dacura-console .console-extra .range-input-object").hide();
		pctype = jQuery("#dacura-console .console-extra .range-input-boxedliteral").hide();
		pctype = jQuery("#dacura-console .console-extra .range-input-literal").show();	
	}
	else if(rtype == "boxed"){
		pctype = jQuery("#dacura-console .console-extra .range-input-object").hide();
		pctype = jQuery("#dacura-console .console-extra .range-input-literal").hide();
		pctype = jQuery("#dacura-console .console-extra .range-input-boxedliteral").show();
		jQuery("#dacura-console .console-extra select.range-box-types").selectmenu("refresh");
	}
	else {
		pctype = jQuery("#dacura-console .console-extra .range-input-boxedliteral").hide();
		pctype = jQuery("#dacura-console .console-extra .range-input-literal").hide();
		pctype = jQuery("#dacura-console .console-extra .range-input-object").show();
		jQuery("#dacura-console .console-extra select.local-range-list").selectmenu("refresh");
		jQuery("#dacura-console .console-extra select.range-objecttype-select").selectmenu("refresh");
	}	
}

dOntology.prototype.changeParentClassType = function(){
	var pctype = jQuery("#dacura-console .console-extra select.parent-type-select").val();
	if(pctype == "remote"){
		pctype = jQuery("#dacura-console .console-extra .remote-parent").show();
		pctype = jQuery("#dacura-console .console-extra .local-parent").hide();
	}
	else {
		pctype = jQuery("#dacura-console .console-extra .remote-parent").hide();
		pctype = jQuery("#dacura-console .console-extra .local-parent").show();
		jQuery("#dacura-console .console-extra select.local-parent-list").selectmenu("refresh");
	}
}

dOntology.prototype.changeClassType = function(){
	var ctype = jQuery("#dacura-console .console-extra select.class-type-select").val();
	if(!ctype){
		jQuery("#dacura-console .console-extra .console-class-extra").slideUp("fast");
		jQuery("#dacura-console .console-extra .console-class-parent").hide();	
		jQuery("#dacura-console .console-extra .console-entity-parent").hide();	
		jQuery("#dacura-console .console-extra .console-enumerated-type").hide();
	}
	else {
		if(ctype == "enumerated"){
			jQuery("#dacura-console .console-extra .console-class-extra").slideDown("fast");
			jQuery("#dacura-console .console-extra .console-enumerated-type").show();
			jQuery("#dacura-console .console-extra .console-class-parent").hide();	
			jQuery("#dacura-console .console-extra .console-entity-parent").hide();	
		}
		else {
			jQuery("#dacura-console .console-extra .console-enumerated-type").hide();
			if(ctype == "entity"){
				jQuery("#dacura-console .console-extra .console-class-extra").slideUp("fast");
				jQuery("#dacura-console .console-extra .console-class-parent").hide();	
				jQuery("#dacura-console .console-extra .console-entity-parent").show();	
				jQuery("#dacura-console .console-extra select.entity-parent-list").selectmenu("refresh");
			}
			else if(ctype == "complex"){
				jQuery("#dacura-console .console-extra .console-class-extra").slideDown("fast");
				jQuery("#dacura-console .console-extra .console-class-parent").show();	
				jQuery("#dacura-console .console-extra .console-entity-parent").hide();	
				jQuery("#dacura-console .console-extra select.parent-type-select").selectmenu("refresh");
				jQuery("#dacura-console .console-extra select.local-parent-list").selectmenu("refresh");
			}
		}
	}
}

dOntology.prototype.addParentClass = function(){
	//figure out if we are local or remote and then add the classes to the array of parent classes
	var ptype = jQuery("#dacura-console .console-extra select.parent-type-select").val();
	if(ptype == "remote"){
		var pval = jQuery("#dacura-console .console-extra .remote-parent input").val();
	}
	else {
		var pval = jQuery("#dacura-console .console-extra select.local-parent-list").val();
	}
	if(parentClasses.indexOf(pval) == -1){
		parentClasses.push(pval);
	}
	var xh = getParentClassesHTML(parentClasses);
	jQuery("#dacura-console .console-extra .console-field-parents").html(xh).show();
	initRemoveParents();
}

dOntology.prototype.initSubscreen = function(){
	var self = this;
	jQuery("#dacura-console .console-extra button.close-subscreen").button({icons: {primary: "ui-icon-close"}}).click(function(){
		dconsole.clearModelSubcreen();
	});
	jQuery("#dacura-console .console-extra button.cancel-edit").button({icons: {primary: "ui-icon-cancel"}}).click(function(){
		self.setViewMode();
	});
	jQuery("#dacura-console .console-extra button.set-edit-mode").button({icons: {primary: "ui-icon-pencil"}}).click(function(){
		self.setEditMode();
	});
}

dOntology.prototype.initClassScreen = function(){
	this.initSubscreen();
	this.focus = "class";
	var self = this;
	jQuery("#dacura-console span.add-parent-class").click(self.addParentClass);
	jQuery("#dacura-console .console-extra select.local-parent-list").selectmenu({width: 150});
	jQuery("#dacura-console .console-extra select.entity-parent-list").selectmenu({width: 200});
	jQuery("#dacura-console .console-extra select.parent-type-select").selectmenu({
		  change: self.changeParentClassType,
		  width: 150

	});	  
	jQuery("#dacura-console .console-extra select.class-type-select").selectmenu({ 
		  change: self.changeClassType,
		  width: 200
	});
	this.changeClassType();
	this.changeParentClassType();
	initRemoveParents();
	this.initEnumeratedTypes();
}

dOntology.prototype.initUpdateClass = function(update_callback, delete_callback){
	var self = this;
	self.initClassScreen();
	jQuery("#dacura-console .console-extra-buttons button.update-class").button({icons: {primary: "ui-icon-disk"}}).click(function(){
		self.updateClass(update_callback);
	});
	jQuery("#dacura-console .console-extra-buttons button.test-update-class").button({icons: {primary: "dacura-help-button-icon"}}).click(function(){
		self.updateClass(update_callback, true);
	});
	jQuery("#dacura-console .console-extra-buttons button.delete-class").button({icons: {primary: "ui-icon-trash"}}).click(function(){
		self.deleteClass(delete_callback);
	});
}

dOntology.prototype.initCreateClass = function(create_callback){
	var self = this;
	self.initClassScreen();
	jQuery("#dacura-console .console-extra button.test-add-class").button({icons: {primary: "dacura-help-button-icon"}}).click(function(){
		self.createClass(create_callback, true);	
	});
	jQuery("#dacura-console .console-extra button.add-class").button({icons: {primary: "ui-icon-disk"}}).click(function(){
		self.createClass(create_callback);	
	});
	var text = "";
    if (window.getSelection) {
        text = window.getSelection().toString();
    } else if (document.selection && document.selection.type != "Control") {
        text = document.selection.createRange().text;
    }
    if(text.length){
    	var prepop = {};
    	if (text.indexOf(' ') === -1){
    		var pid = text;
    		var comment = "";
    	}
    	else {
    		var pid = text.substring(0, text.indexOf(' '));
    		var comment = text.substring(text.indexOf(' ')+1);
    	}
    	var i = 1;
    	var npid = pid
    	while(typeof this.classes[npid] != "undefined"){
    		npid = pid + "_" + i++;
    	}
            
		var pname = toTitleCase(npid);
		//pname = pname.charAt(0).toLowerCase() + pname.slice(1);;
		//pname = pname.replace(/\W/g, '');
		//pname = onturl.substring(onturl.lastIndexOf("/") + 1) + ":" + pname;
    	
    	this.setClassFormValues({comment: comment, id: npid, label: npid});
    }
	//self.changeClassType();
}

dOntology.prototype.initPropertyScreen = function(){
	var self = this;
	this.focus = "property";
	self.initSubscreen();
	jQuery("#dacura-console .console-extra select.range-box-types").selectmenu( {width: 150});
	jQuery("#dacura-console .console-extra select.local-domain-list").selectmenu({width: 170});
	jQuery("#dacura-console .console-extra select.property-rangetype-select").selectmenu({ 
		 change: self.changeRangeType, width: 150
	});
	jQuery("#dacura-console .console-extra select.range-objecttype-select").selectmenu({
		 change: self.changeRangeClassType, width: 150
	});
	jQuery("#dacura-console .console-extra select.domain-type-select").selectmenu({
		 change: self.changeDomainClassType, width: 140
	});
	jQuery("#dacura-console .console-extra select.local-range-list").selectmenu({width: 200});	  
}

dOntology.prototype.initCreateProperty = function(callback){
	var self = this;
	self.initPropertyScreen();
	jQuery("#dacura-console .console-extra button.add-property").button({icons: {primary: "ui-icon-disk"}}).click(function(){
		self.createProperty(callback);	
	});
	jQuery("#dacura-console .console-extra button.test-add-property").button({icons: {primary: "dacura-help-button-icon"}}).click(function(){
		self.createProperty(callback, true);	
	});
}

dOntology.prototype.initUpdateProperty = function(update_callback, delete_callback){
	var self = this;
	self.initPropertyScreen();
	jQuery("#dacura-console .console-extra button.update-property").button({icons: {primary: "ui-icon-disk"}}).click(function(){
		self.updateProperty(update_callback);	
	});
	jQuery("#dacura-console .console-extra button.test-update-property").button({icons: {primary: "dacura-help-button-icon"}}).click(function(){
		self.updateProperty(update_callback, true);	
	});
	jQuery("#dacura-console .console-extra button.delete-property").button({icons: {primary: "ui-icon-trash"}}).click(function(){
		self.deleteProperty(delete_callback);
	});
	self.changeRangeType();
	self.changeDomainClassType();
	self.changeRangeClassType();
}

function initRemoveParents(){
	jQuery("#dacura-console .remove-parent-class").hover(function(){
		jQuery(this).addClass('uhover');
	}, function() {
		jQuery(this).removeClass('uhover');
	}).click(function(){
		var cls = jQuery(this).attr("data-id");
		var index = parentClasses.indexOf(cls);
		if(index > -1){
			parentClasses.splice(index, 1);
		}
		jQuery("#dacura-console .console-extra .console-field-parents").html(getParentClassesHTML(parentClasses));
		initRemoveParents();
	});
}

dOntology.prototype.initEnumeratedTypes = function(){
	var self = this;
	jQuery("#dacura-console td.enumerated-type-remove").hover(function(){
		jQuery(this).addClass('uhover');
	}, function() {
		jQuery(this).removeClass('uhover');
	}).click(function(){
		jQuery(this).parent("tr:first").remove();
	});
	jQuery("#dacura-console th.add-enum").hover(function(){
		jQuery(this).addClass('uhover');
	}, function() {
		jQuery(this).removeClass('uhover');
	}).click(function(){
		var x = jQuery(this).closest('table');
		jQuery('tbody', x).append(self.getEnumeratedTypeInputRowHTML());
		jQuery("#dacura-console td.enumerated-type-remove").hover(function(){
			jQuery(this).addClass('uhover');
		}, function() {
			jQuery(this).removeClass('uhover');
		}).click(function(){
			jQuery(this).parent("tr:first").remove();
		});	
	});	
}


dOntology.prototype.createClass = function(create_callback, test){
	dconsole.clearResultMessages();
	this.errors = [];
	var input = this.readForm();
	if(this.validateCreateInput(input)){
		var rdf = this.getInputAsRDF(input);
		create_callback(rdf, test);
	}
	else {
		this.writeErrorMessage("Error in " + this.errors[0].field + " field", this.errors[0].message);
	}
}

dOntology.prototype.updateClass = function(callback, test){
	dconsole.clearResultMessages();
	this.errors = [];
	var input = this.readForm();
	if(this.validateUpdateInput(input)){
		//need to do more here .... figure out what has changed..
		var rdf = this.getUpdateAsRDF(input);
		if(rdf){
			callback(rdf, test);
		}
		else {
			this.writeErrorMessage("Error in " + this.errors[0].field + " field", this.errors[0].message);			
		}
	}
	else {
		this.writeErrorMessage("Error in " + this.errors[0].field + " field", this.errors[0].message);
	}
}

dOntology.prototype.deleteClass = function(delete_callback){
	dconsole.clearResultMessages();
	var id = jQuery("#dacura-console .class-id .console-field-input input").val();
	var cls = this.id + ":" + id;
	this.writeWarningMessage("Class " + cls + "will be deleted");
	delete_callback(cls);
}

dOntology.prototype.createProperty = function(callback, test){
	dconsole.clearResultMessages();
	this.errors = [];
	var input = this.readPropertyForm();
	if(this.validateCreatePropertyInput(input)){
		var rdf = this.getInputPropertyAsRDF(input);
		callback(rdf, test);
	}
	else {
		this.writeErrorMessage("Error in " + this.errors[0].field + " field", this.errors[0].message);
	}
}

dOntology.prototype.updateProperty = function(callback, test){
	dconsole.clearResultMessages();
	this.errors = [];
	var input = this.readPropertyForm();
	if(this.validateUpdatePropertyInput(input)){
		//need to do more here .... figure out what has changed..
		var rdf = this.getUpdatePropertyAsRDF(input);
		if(rdf){
			callback(rdf, test);
		}
		else {
			this.writeErrorMessage("Error in " + this.errors[0].field + " field", this.errors[0].message);			
		}
	}
	else {
		this.writeErrorMessage("Error in " + this.errors[0].field + " field", this.errors[0].message);
	}
}

dOntology.prototype.deleteProperty = function(delete_callback){
	dconsole.clearResultMessages();
	var id = jQuery("#dacura-console .property-id .console-field-input input").val();
	var prop = this.id + ":" + id;
	this.writeWarningMessage("Property " + prop + "will be deleted");
	delete_callback(prop);
}
